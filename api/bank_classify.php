<?php
/**
 * 통장 거래 분류 · 적재된 bank_transactions 의 자동분류 조회 + 사람 확정.
 * 응답 규약: {ok,data,error}.
 *
 * GET  ?action=list[&account_id=&only=unconfirmed|all&limit=200]
 *        → { categories:[{code,name}], transactions:[{...}] }
 * POST ?action=confirm   body: { items:[{id, code}], ... }
 *        → 선택 거래의 account_code/account_name 갱신 + is_confirmed=1
 * POST ?action=reclassify  body: { account_id? }
 *        → 미확정 거래를 규칙기반으로 재분류(제안만 갱신, 확정본은 건드리지 않음)
 *
 * 가드레일: 인증/CSRF, admin·manager, PDO prepared, 트랜잭션. 세무·금액 계산 로직 불변
 *   (계정 분류 메타 account_code/name/is_confirmed/ai_confidence 만 수정).
 */

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/bank/classify.php';
require_once __DIR__ . '/../includes/bank/learn.php';   // 1층 학습 루프
require_once __DIR__ . '/../includes/bank/rag.php';     // 2층 검색기반 제안
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();
if (!$pdo) apiError('DB_UNAVAILABLE', '데이터베이스에 연결할 수 없습니다.', 500);

try {
    switch ($action) {

        // ─── 분류 대상 거래 조회 ───
        case 'list': {
            apiRequireAdminOrManager(); // confirm/reclassify 와 동일 가드 · 계좌·거래 열람도 admin/manager 한정
            $accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
            $only      = $_GET['only'] ?? 'all';
            $limit     = min(500, max(1, (int)($_GET['limit'] ?? 200)));

            $where = []; $args = [];
            if ($accountId > 0) { $where[] = 't.account_id = ?'; $args[] = $accountId; }
            if ($only === 'unconfirmed') { $where[] = 't.is_confirmed = 0'; }
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = "SELECT t.id, t.account_id, t.transaction_date, t.description, t.amount, t.tx_type,
                           t.balance, t.account_code, t.account_name, t.ai_confidence, t.is_confirmed,
                           a.bank_name, a.account_alias
                    FROM bank_transactions t
                    LEFT JOIN bank_accounts a ON a.id = t.account_id
                    $whereSql
                    ORDER BY t.transaction_date DESC, t.id DESC
                    LIMIT $limit";
            $st = $pdo->prepare($sql);
            $st->execute($args);
            $txs = $st->fetchAll(PDO::FETCH_ASSOC);

            $cats = $pdo->query("SELECT code, name FROM account_categories WHERE is_active = 1 ORDER BY sort_order, code")
                        ->fetchAll(PDO::FETCH_ASSOC);

            apiOk(['categories' => $cats, 'transactions' => $txs]);
        }

        // ─── 사람 확정(선택 거래의 계정 확정) ───
        case 'confirm': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD_NOT_ALLOWED', 'POST 만 허용됩니다.', 405);
            apiRequireAdminOrManager();
            $in = apiJsonInput();
            $items = $in['items'] ?? [];
            if (!is_array($items) || !$items) apiError('BAD_INPUT', '확정할 항목이 없습니다.', 400);

            $catMap = bank_category_map();
            $actor = (string)($GLOBALS['currentUserName'] ?? $GLOBALS['currentUserId'] ?? 'user');
            $updated = 0; $learned = 0;
            $pdo->beginTransaction();
            try {
                // 확정 전 거래 상태 조회(학습 근거: 적요·거래처·이전 제안코드)
                $sel = $pdo->prepare("SELECT id, description, tx_type, counterparty, account_code, amount, transaction_date FROM bank_transactions WHERE id = ?");
                $upd = $pdo->prepare(
                    "UPDATE bank_transactions
                     SET account_code = ?, account_name = ?, is_confirmed = 1
                     WHERE id = ?"
                );
                foreach ($items as $it) {
                    $id   = (int)($it['id'] ?? 0);
                    $code = trim((string)($it['code'] ?? ''));
                    if ($id <= 0 || $code === '' || !isset($catMap[$code])) continue;

                    $sel->execute([$id]);
                    $tx = $sel->fetch(PDO::FETCH_ASSOC);
                    if (!$tx) continue;

                    $upd->execute([$code, $catMap[$code], $id]);
                    $updated += $upd->rowCount();

                    // 사람 확정 → 학습(패턴 강화/생성 + 이력)
                    try {
                        bank_learn_from_confirmation($pdo, $tx, $code, $catMap[$code], $actor);
                        $learned++;
                    } catch (Throwable $le) {
                        error_log('[bank_classify] 학습 실패(무시): ' . $le->getMessage());
                    }
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            apiOk(['updated' => $updated, 'learned' => $learned]);
        }

        // ─── 미확정 거래 규칙기반 재분류(제안만 갱신) ───
        case 'reclassify': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD_NOT_ALLOWED', 'POST 만 허용됩니다.', 405);
            apiRequireAdminOrManager();
            $in = apiJsonInput();
            $accountId = (int)($in['account_id'] ?? 0);

            $where = 'WHERE is_confirmed = 0'; $args = [];
            if ($accountId > 0) { $where .= ' AND account_id = ?'; $args[] = $accountId; }
            $rows = $pdo->prepare("SELECT id, description, tx_type, counterparty, amount, transaction_date FROM bank_transactions $where");
            $rows->execute($args);

            $changed = 0; $bySource = ['learned' => 0, 'rule' => 0, 'rag' => 0];
            $pdo->beginTransaction();
            try {
                $upd = $pdo->prepare("UPDATE bank_transactions SET account_code = ?, account_name = ?, ai_confidence = ? WHERE id = ?");
                foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    // 학습규칙(텍스트→금액·주기) → 정적규칙 (smart)
                    $c = bank_classify_smart($pdo, (string)$r['description'], (string)$r['tx_type'], (string)$r['counterparty'],
                                             (int)$r['amount'], (string)$r['transaction_date']);
                    // 저신뢰(신규·모호)면 검색기반 RAG 제안이 더 나은지 확인
                    if ((int)$c['confidence'] < 60) {
                        $rag = bank_rag_suggest($pdo, $r);
                        if ($rag && (int)$rag['confidence'] > (int)$c['confidence']) $c = $rag;
                    }
                    $upd->execute([$c['code'], $c['name'], (int)$c['confidence'], (int)$r['id']]);
                    $changed += $upd->rowCount();
                    $src = $c['source'] ?? 'rule';
                    if (isset($bySource[$src])) $bySource[$src]++;
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            apiOk(['reclassified' => $changed, 'by_source' => $bySource]);
        }

        default:
            apiError('UNKNOWN_ACTION', '알 수 없는 action 입니다.', 400);
    }
} catch (Throwable $e) {
    error_log('[bank_classify] ' . $e->getMessage());
    apiError('SERVER_ERROR', '서버 오류가 발생했습니다.', 500);
}
