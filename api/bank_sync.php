<?php
/**
 * 은행 거래내역 동기화 · 조회 결과를 bank_transactions 에 영속화 + 규칙기반 자동분류.
 *
 * 기존 단절(조회는 되나 DB 저장이 없던 구간)을 복구하는 핵심 엔드포인트.
 * 응답 규약: 신규 {ok,data,error} (includes/api_common.php).
 *
 * POST ?action=sync
 *   body: { bankCode, accountNumber, accountPassword?, residentNumber?, startDate(YYYYMMDD), endDate(YYYYMMDD), accountAlias? }
 *   - 샌드박스(mock) 모드에선 비밀번호/주민번호 없이도 동작.
 *   - 계좌가 bank_accounts 에 없으면 자동 생성(동의 처리), 있으면 매핑.
 *   - 거래는 (account_id+일자+금액+적요+잔액) 기준 멱등 UPSERT → 재동기화 안전.
 *   - 적재 시 규칙기반 분류(account_code/name/ai_confidence) 부여, is_confirmed=0(사람 확정 전).
 *
 * 가드레일: 인증/CSRF(api_auth), admin·manager 한정, PDO prepared, 2테이블 쓰기 트랜잭션,
 *   계좌비밀번호·주민번호는 절대 저장/로깅하지 않음(거래내역만 적재).
 */

require_once __DIR__ . '/../includes/api_auth.php';      // 세션 + 로그인 + CSRF
require_once __DIR__ . '/../includes/api_common.php';    // apiOk / apiError / apiJsonInput / 권한
require_once __DIR__ . '/../includes/bank/provider.php'; // bank_provider_request, BANKAPI_BANKS
require_once __DIR__ . '/../includes/bank/classify.php'; // bank_classify_one
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? 'sync';
if ($action !== 'sync') apiError('UNKNOWN_ACTION', '알 수 없는 action 입니다.', 400);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD_NOT_ALLOWED', 'POST 만 허용됩니다.', 405);
apiRequireAdminOrManager();

$in = apiJsonInput();
$bankCode      = trim($in['bankCode'] ?? '');
$accountNumber = preg_replace('/[^0-9]/', '', $in['accountNumber'] ?? '');
$startDate     = preg_replace('/[^0-9]/', '', $in['startDate'] ?? '') ?: date('Ymd', strtotime('-1 month'));
$endDate       = preg_replace('/[^0-9]/', '', $in['endDate'] ?? '') ?: date('Ymd');
$accountAlias  = trim($in['accountAlias'] ?? '');

if (!$bankCode || !$accountNumber) apiError('BAD_INPUT', '은행코드와 계좌번호는 필수입니다.', 400);
if (!isset(BANKAPI_BANKS[$bankCode])) {
    apiError('BAD_INPUT', '지원하지 않는 은행입니다. 지원: ' . bankapi_supported_bank_codes(), 400);
}
$bankName = BANKAPI_BANKS[$bankCode];

// ─── 1) 거래내역 조회(제공자: 실연동 or 샌드박스) ───
$res = bank_provider_request('POST', '/v1/transactions', [
    'bankCode'        => $bankCode,
    'accountNumber'   => $accountNumber,
    'accountPassword' => $in['accountPassword'] ?? '',   // 저장 안 함 · 제공자 호출에만 사용
    'residentNumber'  => preg_replace('/[^0-9]/', '', $in['residentNumber'] ?? ''),
    'startDate'       => $startDate,
    'endDate'         => $endDate,
]);
if (empty($res['ok'])) {
    $msg = $res['data']['message'] ?? $res['data']['error'] ?? '거래내역 조회 실패';
    apiError('PROVIDER_ERROR', $msg, 502, ['provider_status' => $res['status'] ?? 0]);
}
$txs = $res['data']['transactions'] ?? (is_array($res['data']) ? $res['data'] : []);
if (!is_array($txs)) $txs = [];

// ─── 2) 적재(트랜잭션) ───
$pdo = getDBConnection();
if (!$pdo) apiError('DB_UNAVAILABLE', '데이터베이스에 연결할 수 없습니다.', 500);

$saved = 0; $skipped = 0; $fetched = count($txs);
try {
    $pdo->beginTransaction();

    // 계좌 매핑/생성 · account_no 의 대시/공백을 제거한 숫자 기준으로 비교(기존 '123-456-789012' 샘플과 중복 생성 방지)
    $sel = $pdo->prepare("SELECT id FROM bank_accounts WHERE REPLACE(REPLACE(account_no, '-', ''), ' ', '') = ? LIMIT 1");
    $sel->execute([$accountNumber]);
    $accountId = (int)($sel->fetchColumn() ?: 0);
    if (!$accountId) {
        $ins = $pdo->prepare(
            "INSERT INTO bank_accounts (company_id, bank_name, account_no, account_alias, owner_name, consent_agreed, consent_agreed_at, is_active)
             VALUES (1, ?, ?, ?, ?, 1, NOW(), 1)"
        );
        $alias = $accountAlias !== '' ? $accountAlias : ($bankName . ' ' . substr($accountNumber, -4));
        $owner = (string)($_SESSION['user']['name'] ?? '연동계좌');
        $ins->execute([$bankName, $accountNumber, $alias, $owner]);
        $accountId = (int)$pdo->lastInsertId();
    }

    // 중복 검사용 prepared.
    //   balance 가 NULL/값 양쪽 모두에서 일관 매칭되도록 명시적 NULL 비교(spaceship 의 NULL<=>값=false 함정 회피).
    $dup = $pdo->prepare(
        "SELECT id FROM bank_transactions
         WHERE account_id = ? AND transaction_date = ? AND amount = ? AND description = ?
           AND ((balance IS NULL AND ? IS NULL) OR balance = ?)
         LIMIT 1"
    );
    $insTx = $pdo->prepare(
        "INSERT INTO bank_transactions
            (account_id, transaction_date, description, amount, tx_type, balance, account_code, account_name, ai_confidence, is_confirmed)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
    );

    foreach ($txs as $tx) {
        $ymd  = preg_replace('/[^0-9]/', '', (string)($tx['transactionDate'] ?? $tx['date'] ?? ''));
        $date = strlen($ymd) === 8 ? substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2) : null;
        if (!$date) { $skipped++; continue; }
        $desc   = (string)($tx['description'] ?? $tx['memo'] ?? $tx['content'] ?? '');
        $amount = (int)($tx['amount'] ?? 0);
        $rawType = (string)($tx['transactionType'] ?? $tx['type'] ?? '');
        $txType = (mb_strpos($rawType, '입금') !== false || preg_match('/in|deposit/i', $rawType)) ? '입금' : '출금';
        $balance = isset($tx['balance']) && $tx['balance'] !== '' ? (int)$tx['balance'] : null;

        // 멱등: 동일 거래 존재하면 스킵 (balance 는 NULL/값 비교 2회 바인딩)
        $dup->execute([$accountId, $date, $amount, $desc, $balance, $balance]);
        if ($dup->fetchColumn()) { $skipped++; continue; }

        // 규칙기반 분류(제안 메타) · 세무·금액 계산은 건드리지 않음
        $cls = bank_classify_one($desc, $txType);

        $insTx->execute([
            $accountId, $date, $desc, $amount, $txType, $balance,
            $cls['code'], $cls['name'], (int)$cls['confidence'],
        ]);
        $saved++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[bank_sync] ' . $e->getMessage());
    apiError('SYNC_FAILED', '동기화 중 오류가 발생했습니다.', 500);
}

apiOk([
    'account_id' => $accountId,
    'fetched'    => $fetched,
    'saved'      => $saved,
    'skipped'    => $skipped,
    'sandbox'    => bank_provider_is_sandbox(),
    'period'     => ['start' => $startDate, 'end' => $endDate],
]);
