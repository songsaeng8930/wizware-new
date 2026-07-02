<?php
/**
 * AI 기능 API 엔드포인트
 *
 * GET  ?action=get_models             사용 가능한 AI 모델 목록
 * GET  ?action=get_config             현재 AI 설정 (기본 프로바이더/모델)
 * POST ?action=classify_transactions  통장 거래 AI 분류
 * POST ?action=enhance_match          세금계산서 매칭 AI 보강
 * POST ?action=bulk_enhance_match     세금계산서 일괄 AI 매칭
 * POST ?action=report_insights        세무 리포트 AI 인사이트
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/ai_client.php';
require_once __DIR__ . '/../config/database.php';

set_time_limit(120);

$action = $_GET['action'] ?? '';

$aiWriteActions = [
    'classify_transactions', 'classify_by_patterns', 'save_classification',
    'create_classify_pattern', 'update_classify_pattern', 'toggle_classify_pattern',
    'delete_classify_pattern', 'bulk_import_patterns', 'create_lock_rule',
    'bulk_pattern_action', 'enhance_match', 'bulk_enhance_match', 'report_insights',
];
if (in_array($action, $aiWriteActions, true)) {
    apiRequireAdminOrManager();
}

try {
    match ($action) {
        'get_models'             => handleGetModels(),
        'get_config'             => handleGetConfig(),
        'classify_transactions'  => handleClassifyTransactions(),
        'classify_by_patterns'   => handleClassifyByPatterns(),
        'save_classification'    => handleSaveClassification(),
        'get_classify_patterns'  => handleGetClassifyPatterns(),
        'create_classify_pattern'=> handleCreateClassifyPattern(),
        'preview_pattern_match'  => handlePreviewPatternMatch(),
        'update_classify_pattern'=> handleUpdateClassifyPattern(),
        'toggle_classify_pattern'=> handleToggleClassifyPattern(),
        'delete_classify_pattern'=> handleDeleteClassifyPattern(),
        'bulk_import_patterns'   => handleBulkImportPatterns(),
        'create_lock_rule'       => handleCreateLockRule(),
        'bulk_pattern_action'    => handleBulkPatternAction(),
        'get_classify_history'   => handleGetClassifyHistory(),
        'enhance_match'          => handleEnhanceMatch(),
        'bulk_enhance_match'     => handleBulkEnhanceMatch(),
        'report_insights'        => handleReportInsights(),
        default                  => apiError('UNKNOWN_ACTION', "알 수 없는 action: " . htmlspecialchars($action)),
    };
} catch (Throwable $e) {
    error_log('api/ai.php error: ' . $e->getMessage());
    apiError('SERVER_ERROR', '서버 오류: ' . $e->getMessage(), 500);
}

// ─── 모델 목록 ───

function handleGetModels(): void
{
    $provider = $_GET['provider'] ?? null;
    $models = ai_get_models($provider ?: null);
    apiOk(['models' => $models]);
}

// ─── AI 설정 조회 ───

function handleGetConfig(): void
{
    $config = ai_load_config();
    $configured = [];
    foreach (array_keys(AI_PROVIDERS) as $p) {
        $configured[$p] = ai_configured($p);
    }

    apiOk([
        'provider'   => $config['ai_provider'] ?? '',
        'model'      => $config['ai_model'] ?? '',
        'configured' => $configured,
    ]);
}

// ─── 통장 거래 AI 분류 ───

function handleClassifyTransactions(): void
{
    $input = apiJsonInput();
    $transactions = $input['transactions'] ?? [];

    if (empty($transactions) || !is_array($transactions)) {
        apiError('MISSING_DATA', '분류할 거래내역이 없습니다.');
    }

    $maxBatch = 20;
    if (count($transactions) > $maxBatch) {
        apiError('BATCH_LIMIT', "한 번에 최대 {$maxBatch}건까지 처리 가능합니다.");
    }

    $provider = $input['provider'] ?? ai_get_default_provider();
    $model    = $input['model']    ?? ai_get_default_model();

    if (!$provider) {
        apiError('NO_PROVIDER', 'AI 프로바이더가 설정되지 않았습니다. API 설정에서 AI 서비스를 먼저 설정하세요.');
    }

    $pdo = getDBConnection();
    $categories = loadAccountCategories($pdo);

    if (empty($categories)) {
        apiError('NO_CATEGORIES', '계정과목이 등록되지 않았습니다.');
    }

    $patterns = loadActivePatterns($pdo);
    $confirmedExamples = loadConfirmedExamples($pdo, 50);
    $systemPrompt = buildClassifySystemPrompt($categories, $patterns, $confirmedExamples);
    $userPrompt   = buildClassifyUserPrompt($transactions);

    $result = ai_request($provider, $model, $systemPrompt, $userPrompt, [
        'max_tokens'  => 4096,
        'temperature' => 0.1,
        'json_mode'   => ($provider === 'openai' || $provider === 'google'),
    ]);

    if (!$result['ok']) {
        apiError('AI_ERROR', 'AI 호출 실패: ' . ($result['error'] ?? '알 수 없는 오류'), 502);
    }

    $parsed = ai_parse_json_array($result['content']);

    if ($parsed === null) {
        apiError('PARSE_ERROR', 'AI 응답을 파싱할 수 없습니다.', 502);
    }

    $validCodes = array_column($categories, 'code');
    $codeToName = array_column($categories, 'name', 'code');
    $results = [];

    foreach ($parsed as $item) {
        $code = $item['account_code'] ?? '';
        if (!in_array($code, $validCodes, true)) {
            $code = '';
        }
        $results[] = [
            'id'           => $item['id'] ?? 0,
            'account_code' => $code,
            'account_name' => $code ? ($codeToName[$code] ?? $item['account_name'] ?? '') : '',
            'confidence'   => max(0, min(100, (int)($item['confidence'] ?? 0))),
            'reason'       => $item['reason'] ?? '',
        ];
    }

    apiOk([
        'results'       => $results,
        'usage'         => $result['usage'],
        'model_used'    => $model,
        'provider_used' => $provider,
    ]);
}

// ─── 패턴 기반 자동 분류 ───

function handleClassifyByPatterns(): void
{
    $input = apiJsonInput();
    $txIds = $input['transaction_ids'] ?? [];

    if (empty($txIds) || !is_array($txIds)) {
        apiError('MISSING_DATA', '분류할 거래 ID가 없습니다.');
    }

    $pdo = getDBConnection();

    $patterns = $pdo->query("
        SELECT * FROM classification_patterns WHERE is_active = 1 ORDER BY priority DESC, confidence DESC
    ")->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($patterns)) {
        apiOk(['results' => [], 'matched' => 0, 'unmatched' => count($txIds), 'unmatched_ids' => $txIds]);
        return;
    }

    // 충돌 키워드 맵: 같은 적요(keyword)가 여러 계정과목에 매핑되면 신뢰도 하향용
    $kwConflicts = [];
    foreach ($patterns as $p) {
        $kwConflicts[$p['keyword']][$p['account_code']] = true;
    }

    $placeholders = implode(',', array_fill(0, count($txIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, transaction_date, description, counterparty, amount, tx_type
        FROM bank_transactions WHERE id IN ($placeholders)
    ");
    $stmt->execute(array_values(array_map('strval', $txIds)));
    $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $results = [];
    $matchedIds = [];
    $hitPatternIds = [];

    foreach ($transactions as $tx) {
        $best = matchTransactionToPattern($tx, $patterns, $kwConflicts);
        if ($best) {
            $locked = !empty($best['locked']);
            $results[] = [
                'id'           => (int)$tx['id'],
                'account_code' => $best['account_code'],
                'account_name' => $best['account_name'],
                'confidence'   => (int)$best['score'],
                'reason'       => $locked
                    ? "확정 규칙: \"{$best['keyword']}\" → 무조건 적용"
                    : "패턴매칭: \"{$best['keyword']}\" (P{$best['priority']})",
                'source'       => $locked ? 'locked' : 'pattern',
                'locked'       => $locked,
                'pattern_id'   => (int)$best['id'],
            ];
            $matchedIds[] = (int)$tx['id'];
            $hitPatternIds[] = (int)$best['id'];
        }
    }

    if (!empty($hitPatternIds)) {
        $hitUnique = array_unique($hitPatternIds);
        $hitPh = implode(',', array_fill(0, count($hitUnique), '?'));
        $pdo->prepare("UPDATE classification_patterns SET hit_count = hit_count + 1 WHERE id IN ($hitPh)")
            ->execute(array_values($hitUnique));
    }

    $unmatchedIds = array_values(array_diff(array_map('intval', $txIds), $matchedIds));

    apiOk([
        'results'       => $results,
        'matched'       => count($results),
        'unmatched'     => count($unmatchedIds),
        'unmatched_ids' => $unmatchedIds,
    ]);
}

function matchTransactionToPattern(array $tx, array $patterns, array $kwConflicts = []): ?array
{
    $bestScore = 0;
    $bestPattern = null;
    $lockedBest = null;   // 확정(user) 패턴 · 조건 충족 시 무조건 적용
    $lockedLen  = -1;     // 여러 확정 매칭 시 더 구체적인(키워드 긴) 것 우선
    $MIN_SCORE = 35;

    foreach ($patterns as $p) {
        $inDesc = mb_stripos($tx['description'], $p['keyword']) !== false;
        $inCp   = mb_stripos($tx['counterparty'] ?? '', $p['keyword']) !== false;
        if (!$inDesc && !$inCp) continue;

        $matchTarget = $inDesc ? $tx['description'] : ($tx['counterparty'] ?? '');
        $isExact = trim($matchTarget) === trim($p['keyword']);

        if ($p['tx_type'] !== '전체') {
            if ($p['tx_type'] !== $tx['tx_type']) continue;
        }

        // 금액 범위 충족 여부 (확정 판정 + 페널티 공용)
        $amtInRange = true;
        if ($p['amount_min'] !== null || $p['amount_max'] !== null) {
            $amt = (int)$tx['amount'];
            if (($p['amount_min'] !== null && $amt < (int)$p['amount_min'])
             || ($p['amount_max'] !== null && $amt > (int)$p['amount_max'])) {
                $amtInRange = false;
            }
        }

        // ★ 확정(user) 패턴: 적요에 키워드가 있고 금액 조건까지 맞으면 무조건 100% 적용.
        //    거래처에서만 매칭된 경우(적요엔 키워드 없음)는 오분류 위험이 커 확정에서 제외.
        if ($p['source'] === 'user') {
            if ($inDesc && $amtInRange) {
                $kl = mb_strlen($p['keyword']);
                if ($kl > $lockedLen) {
                    $lockedLen = $kl;
                    $lockedBest = $p;
                    $lockedBest['score']  = 100;
                    $lockedBest['locked'] = true;
                }
            }
            continue; // 확정 규칙은 조건 충족 시에만 적용, 아니면 관여하지 않음
        }

        $penalties = 0;

        // 거래처에서만 매칭(적요엔 키워드 없음) · 거래처가 같아도 거래 종류가 다를 수 있어 신뢰도 하향
        if (!$inDesc && $inCp) $penalties += 25;

        // 충돌 키워드(같은 적요가 여러 계정과목) · 패턴만으로 구분 불가하므로 신뢰도 하향
        $nAcc = isset($kwConflicts[$p['keyword']]) ? count($kwConflicts[$p['keyword']]) : 1;
        if ($nAcc >= 3) $penalties += 25;
        else if ($nAcc === 2) $penalties += 15;

        if (!$amtInRange) $penalties += 15;

        if ($p['counterparty']) {
            $cpMatch = mb_stripos($tx['counterparty'] ?? '', $p['counterparty']) !== false
                    || mb_stripos($tx['description'], $p['counterparty']) !== false;
            if (!$cpMatch) $penalties += 10;
        }

        if ($p['recurrence'] !== 'none' && $p['recurrence_day'] !== null) {
            $txDay = (int)date('j', strtotime($tx['transaction_date']));
            if (abs($txDay - (int)$p['recurrence_day']) > 2) $penalties += 5;
        }

        if (!$isExact) {
            $kwLen = mb_strlen($p['keyword']);
            $descLen = mb_strlen($matchTarget);
            if ($kwLen < 3 || $descLen > $kwLen * 3) $penalties += 15;
            else if ($descLen > $kwLen * 2) $penalties += 10;
            else $penalties += 5;
        }

        $finalScore = (int)$p['confidence'] - $penalties;
        $finalScore = max(0, min(99, $finalScore));

        if ($finalScore > $bestScore) {
            $bestScore = $finalScore;
            $bestPattern = $p;
            $bestPattern['score'] = $finalScore;
        }
    }

    // 확정 규칙이 매칭되면 추천 점수보다 항상 우선
    if ($lockedBest) return $lockedBest;

    return ($bestScore >= $MIN_SCORE && $bestPattern) ? $bestPattern : null;
}

// ─── 분류 결과 DB 저장 ───

function handleSaveClassification(): void
{
    $input = apiJsonInput();
    $items = $input['items'] ?? [];

    if (empty($items)) {
        apiError('MISSING_DATA', '저장할 분류 결과가 없습니다.');
    }

    $pdo = getDBConnection();
    $updateStmt = $pdo->prepare("
        UPDATE bank_transactions
        SET account_code = ?, account_name = ?, ai_confidence = ?, is_confirmed = ?
        WHERE id = ?
    ");

    $txInfoStmt = $pdo->prepare("
        SELECT id, description, tx_type, amount, counterparty, transaction_date,
               account_code as old_code FROM bank_transactions WHERE id = ?
    ");

    $saved = 0;
    $learnData = [];
    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0) continue;

            $txInfoStmt->execute([$id]);
            $txInfo = $txInfoStmt->fetch(\PDO::FETCH_ASSOC);

            $updateStmt->execute([
                $item['account_code'] ?? null,
                $item['account_name'] ?? null,
                (int)($item['confidence'] ?? 0),
                (int)($item['confirmed'] ?? 0),
                $id,
            ]);
            $saved++;

            if (!empty($item['account_code']) && $txInfo) {
                $histStmt = $pdo->prepare("
                    INSERT INTO classification_history
                    (transaction_id, old_account_code, new_account_code, new_account_name, action, actor)
                    VALUES (?, ?, ?, ?, 'confirm', 'user')
                ");
                $histStmt->execute([
                    $id,
                    $txInfo['old_code'],
                    $item['account_code'],
                    $item['account_name'] ?? null,
                ]);

                $learnData[] = [
                    'description'      => $txInfo['description'],
                    'tx_type'          => $txInfo['tx_type'],
                    'amount'           => (int)$txInfo['amount'],
                    'counterparty'     => $txInfo['counterparty'] ?? '',
                    'transaction_date' => $txInfo['transaction_date'],
                    'account_code'     => $item['account_code'],
                    'account_name'     => $item['account_name'] ?? '',
                ];
            }
        }

        learnClassifyPatterns($pdo, $learnData);

        $pdo->commit();
        apiOk(['saved' => $saved, 'patterns_learned' => count($learnData)]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('save_classification: ' . $e->getMessage());
        apiError('SAVE_ERROR', '분류 결과 저장 중 오류가 발생했습니다.', 500);
    }
}

function learnClassifyPatterns(PDO $pdo, array $data): void
{
    if (empty($data)) return;

    $AMOUNT_MARGIN = 0.2;
    $MIN_KEYWORD_LENGTH = 2;

    foreach ($data as $d) {
        $desc = trim($d['description']);
        if (mb_strlen($desc) < $MIN_KEYWORD_LENGTH) continue;

        $amount = (int)($d['amount'] ?? 0);
        $counterparty = trim($d['counterparty'] ?? '');
        $txDate = $d['transaction_date'] ?? null;
        $dayOfMonth = $txDate ? (int)date('j', strtotime($txDate)) : null;

        $keywords = extractKeywords($desc);

        foreach ($keywords as $idx => $kw) {
            $isFullDesc = ($idx === 0 && $kw === $desc);

            $existing = $pdo->prepare("
                SELECT id, hit_count, confidence, amount_min, amount_max, counterparty, recurrence_day
                FROM classification_patterns
                WHERE keyword = ? AND account_code = ?
            ");
            $existing->execute([$kw, $d['account_code']]);
            $row = $existing->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $newConf = min(99, (float)$row['confidence'] + 2);

                $newMin = $row['amount_min'];
                $newMax = $row['amount_max'];
                if ($amount > 0) {
                    $marginAmt = max(10000, (int)($amount * $AMOUNT_MARGIN));
                    $candidateMin = $amount - $marginAmt;
                    $candidateMax = $amount + $marginAmt;
                    $newMin = ($newMin === null) ? $candidateMin : min((int)$newMin, $candidateMin);
                    $newMax = ($newMax === null) ? $candidateMax : max((int)$newMax, $candidateMax);
                }

                $newCp = $row['counterparty'];
                if ($counterparty && !$newCp) {
                    $newCp = $counterparty;
                }

                $recDay = $row['recurrence_day'];
                $recurrence = 'none';
                if ($dayOfMonth !== null && $row['hit_count'] >= 2) {
                    $recurrence = detectRecurrence($pdo, $kw, $d['account_code'], $txDate);
                    if ($recurrence !== 'none' && $recDay === null) {
                        $recDay = $dayOfMonth;
                    }
                }

                $priority = calcPriority($d['tx_type'], $newMin, $newMax, $newCp, $recurrence);

                $pdo->prepare("
                    UPDATE classification_patterns
                    SET hit_count = hit_count + 1, confidence = ?, tx_type = ?,
                        amount_min = ?, amount_max = ?, counterparty = ?,
                        recurrence = ?, recurrence_day = ?, priority = ?
                    WHERE id = ?
                ")->execute([
                    $newConf, $d['tx_type'],
                    $newMin, $newMax, $newCp ?: null,
                    $recurrence, $recDay, $priority,
                    $row['id']
                ]);
            } else {
                $amtMin = null;
                $amtMax = null;
                if ($amount > 0) {
                    $marginAmt = max(10000, (int)($amount * $AMOUNT_MARGIN));
                    $amtMin = $amount - $marginAmt;
                    $amtMax = $amount + $marginAmt;
                }

                $priority = calcPriority($d['tx_type'], $amtMin, $amtMax, $counterparty, 'none');
                $initConf = $isFullDesc ? 75 : 60;

                $pdo->prepare("
                    INSERT INTO classification_patterns
                    (keyword, tx_type, account_code, account_name, amount_min, amount_max,
                     counterparty, recurrence, recurrence_day, priority, confidence, hit_count, source)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'none', ?, ?, ?, 1, 'user')
                ")->execute([
                    $kw, $d['tx_type'], $d['account_code'], $d['account_name'],
                    $amtMin, $amtMax, $counterparty ?: null,
                    $dayOfMonth, $priority, $initConf
                ]);
            }
        }
    }
}

function calcPriority(string $txType, ?int $amtMin, ?int $amtMax, ?string $cp, string $rec): int
{
    $p = 0;
    if ($txType !== '전체') $p += 1;
    if ($amtMin !== null || $amtMax !== null) $p += 2;
    if ($cp) $p += 2;
    if ($rec !== 'none') $p += 1;
    return $p;
}

function detectRecurrence(PDO $pdo, string $keyword, string $accountCode, string $currentDate): string
{
    $stmt = $pdo->prepare("
        SELECT bt.transaction_date
        FROM bank_transactions bt
        WHERE bt.description LIKE ? AND bt.account_code = ? AND bt.is_confirmed = 1
        ORDER BY bt.transaction_date DESC
        LIMIT 12
    ");
    $stmt->execute(['%' . $keyword . '%', $accountCode]);
    $dates = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    if (count($dates) < 2) return 'none';

    $dates[] = $currentDate;
    $dates = array_unique($dates);
    sort($dates);

    $gaps = [];
    for ($i = 1; $i < count($dates); $i++) {
        $gaps[] = (strtotime($dates[$i]) - strtotime($dates[$i - 1])) / 86400;
    }
    if (empty($gaps)) return 'none';

    $avg = array_sum($gaps) / count($gaps);

    if ($avg <= 2) return 'daily';
    if ($avg >= 5 && $avg <= 10) return 'weekly';
    if ($avg >= 25 && $avg <= 35) return 'monthly';
    if ($avg >= 80 && $avg <= 100) return 'quarterly';
    if ($avg >= 170 && $avg <= 200) return 'semi_annual';
    if ($avg >= 350 && $avg <= 380) return 'annual';

    return 'none';
}

function extractKeywords(string $desc): array
{
    $desc = preg_replace('/[0-9\-\/\.\s]+/', ' ', $desc);
    $desc = trim($desc);
    if (mb_strlen($desc) < 2) return [];

    $keywords = [];
    $keywords[] = $desc;
    $parts = preg_split('/\s+/', $desc);
    foreach ($parts as $p) {
        $p = trim($p);
        if (mb_strlen($p) >= 2) {
            $keywords[] = $p;
        }
    }
    return array_unique($keywords);
}

// ─── 분류 패턴 CRUD ───

function handleGetClassifyPatterns(): void
{
    $pdo = getDBConnection();
    $where = ['1=1'];
    $params = [];

    $keyword = $_GET['keyword'] ?? '';
    if ($keyword) {
        $where[] = 'keyword LIKE ?';
        $params[] = "%{$keyword}%";
    }
    $accountCode = $_GET['account_code'] ?? '';
    if ($accountCode) {
        $where[] = 'account_code = ?';
        $params[] = $accountCode;
    }
    $source = $_GET['source'] ?? '';
    if ($source && in_array($source, ['rule', 'user', 'ai'])) {
        $where[] = 'source = ?';
        $params[] = $source;
    }

    $sql = 'SELECT * FROM classification_patterns WHERE ' . implode(' AND ', $where) . ' ORDER BY hit_count DESC, updated_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patterns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $sumStmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(is_active) as active,
               ROUND(AVG(confidence), 1) as avg_confidence
        FROM classification_patterns
    ");
    $summary = $sumStmt->fetch(\PDO::FETCH_ASSOC);
    $summary['history_count'] = (int)$pdo->query("SELECT COUNT(*) FROM classification_history")->fetchColumn();

    apiOk(['patterns' => $patterns, 'summary' => $summary]);
}

// ─── 패턴 미리보기: 이 조건에 맞는 실제 거래가 몇 건인지 ───

function handlePreviewPatternMatch(): void
{
    $input = apiJsonInput();
    $keyword = trim($input['keyword'] ?? '');
    if (mb_strlen($keyword) < 2) { apiOk(['count' => 0, 'samples' => []]); return; }

    $txType       = $input['tx_type'] ?? '전체';
    $amountMin    = (isset($input['amount_min']) && $input['amount_min'] !== null && $input['amount_min'] !== '') ? (int)$input['amount_min'] : null;
    $amountMax    = (isset($input['amount_max']) && $input['amount_max'] !== null && $input['amount_max'] !== '') ? (int)$input['amount_max'] : null;
    $counterparty = trim($input['counterparty'] ?? '');

    $pdo = getDBConnection();
    $kw = '%' . $keyword . '%';
    $where = ['(description LIKE ? OR counterparty LIKE ?)'];
    $params = [$kw, $kw];

    if ($txType === '입금' || $txType === '출금') { $where[] = 'tx_type = ?'; $params[] = $txType; }
    if ($amountMin !== null) { $where[] = 'amount >= ?'; $params[] = $amountMin; }
    if ($amountMax !== null) { $where[] = 'amount <= ?'; $params[] = $amountMax; }
    if ($counterparty !== '') {
        $cp = '%' . $counterparty . '%';
        $where[] = '(counterparty LIKE ? OR description LIKE ?)';
        $params[] = $cp; $params[] = $cp;
    }
    $wsql = implode(' AND ', $where);

    try {
        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM bank_transactions WHERE $wsql");
        $cntStmt->execute($params);
        $count = (int)$cntStmt->fetchColumn();

        $sampleStmt = $pdo->prepare("SELECT transaction_date, description, counterparty, amount, tx_type
            FROM bank_transactions WHERE $wsql ORDER BY transaction_date DESC LIMIT 5");
        $sampleStmt->execute($params);
        $samples = $sampleStmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        apiOk(['count' => 0, 'samples' => []]);
        return;
    }

    apiOk(['count' => $count, 'samples' => $samples]);
}

function handleCreateClassifyPattern(): void
{
    $input = apiJsonInput();
    $keyword = trim($input['keyword'] ?? '');
    if (mb_strlen($keyword) < 2) apiError('MISSING_DATA', '키워드는 2자 이상이어야 합니다');

    $txType = in_array($input['tx_type'] ?? '', ['입금', '출금', '전체'], true) ? $input['tx_type'] : '전체';
    $accountCode = trim($input['account_code'] ?? '');
    $accountName = trim($input['account_name'] ?? '');
    if ($accountCode === '') apiError('MISSING_DATA', '계정과목을 선택하세요');

    $source = in_array($input['source'] ?? '', ['rule', 'user', 'ai'], true) ? $input['source'] : 'user';
    $amountMin = (isset($input['amount_min']) && $input['amount_min'] !== null && $input['amount_min'] !== '') ? (int)$input['amount_min'] : null;
    $amountMax = (isset($input['amount_max']) && $input['amount_max'] !== null && $input['amount_max'] !== '') ? (int)$input['amount_max'] : null;
    $counterparty = !empty($input['counterparty']) ? mb_substr(trim($input['counterparty']), 0, 100) : null;
    $recurrence = in_array($input['recurrence'] ?? '', ['none','daily','weekly','monthly','quarterly','semi_annual','annual'], true) ? $input['recurrence'] : 'none';
    $recurrenceDay = (isset($input['recurrence_day']) && $input['recurrence_day'] !== null && $input['recurrence_day'] !== '') ? (int)$input['recurrence_day'] : null;
    // 확정(user)은 항상 100, 추천은 입력값 또는 기본 70
    $confidence = $source === 'user' ? 100 : (isset($input['confidence']) ? max(0, min(100, (float)$input['confidence'])) : 70);
    $priority = calcPriority($txType, $amountMin, $amountMax, $counterparty, $recurrence);

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO classification_patterns
        (keyword, tx_type, account_code, account_name, amount_min, amount_max,
         counterparty, recurrence, recurrence_day, priority, confidence, hit_count, source, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 1)
        ON DUPLICATE KEY UPDATE
            account_name = VALUES(account_name), amount_min = VALUES(amount_min),
            amount_max = VALUES(amount_max), counterparty = VALUES(counterparty),
            recurrence = VALUES(recurrence), recurrence_day = VALUES(recurrence_day),
            priority = VALUES(priority), confidence = VALUES(confidence),
            source = VALUES(source), is_active = 1
    ");
    $stmt->execute([$keyword, $txType, $accountCode, $accountName, $amountMin, $amountMax,
                    $counterparty, $recurrence, $recurrenceDay, $priority, $confidence, $source]);

    apiOk(['created' => true]);
}

function handleUpdateClassifyPattern(): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if (!$id) apiError('MISSING_DATA', 'id 필수');

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM classification_patterns WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$old) apiError('NOT_FOUND', '패턴을 찾을 수 없습니다', 404);

    $keyword = trim($input['keyword'] ?? $old['keyword']);
    $txType = $input['tx_type'] ?? $old['tx_type'];
    if (!in_array($txType, ['입금', '출금', '전체'])) $txType = $old['tx_type'];
    $accountCode = trim($input['account_code'] ?? $old['account_code']);
    $accountName = trim($input['account_name'] ?? $old['account_name']);
    $confidence = isset($input['confidence']) ? max(0, min(100, (float)$input['confidence'])) : (float)$old['confidence'];
    $source = $input['source'] ?? $old['source'];
    if (!in_array($source, ['rule', 'user', 'ai'])) $source = $old['source'];

    $amountMin = array_key_exists('amount_min', $input) ? ($input['amount_min'] !== null && $input['amount_min'] !== '' ? (int)$input['amount_min'] : null) : $old['amount_min'];
    $amountMax = array_key_exists('amount_max', $input) ? ($input['amount_max'] !== null && $input['amount_max'] !== '' ? (int)$input['amount_max'] : null) : $old['amount_max'];
    $counterparty = array_key_exists('counterparty', $input) ? trim($input['counterparty'] ?? '') : ($old['counterparty'] ?? '');
    $recurrence = $input['recurrence'] ?? $old['recurrence'] ?? 'none';
    $validRec = ['none','daily','weekly','monthly','quarterly','semi_annual','annual'];
    if (!in_array($recurrence, $validRec)) $recurrence = $old['recurrence'] ?? 'none';
    $recurrenceDay = array_key_exists('recurrence_day', $input) ? ($input['recurrence_day'] !== null && $input['recurrence_day'] !== '' ? (int)$input['recurrence_day'] : null) : $old['recurrence_day'];
    $priority = calcPriority($txType, $amountMin, $amountMax, $counterparty, $recurrence);

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            UPDATE classification_patterns
            SET keyword = ?, tx_type = ?, account_code = ?, account_name = ?,
                amount_min = ?, amount_max = ?, counterparty = ?,
                recurrence = ?, recurrence_day = ?, priority = ?,
                confidence = ?, source = ?
            WHERE id = ?
        ")->execute([
            $keyword, $txType, $accountCode, $accountName,
            $amountMin, $amountMax, $counterparty ?: null,
            $recurrence, $recurrenceDay, $priority,
            $confidence, $source, $id
        ]);

        $changes = [];
        if ($keyword !== $old['keyword']) $changes[] = "키워드:{$old['keyword']}→{$keyword}";
        if ($accountCode !== $old['account_code']) $changes[] = "계정:{$old['account_code']}→{$accountCode}";
        if (abs($confidence - (float)$old['confidence']) > 0.01) $changes[] = "신뢰도:{$old['confidence']}→{$confidence}";
        if ($amountMin != $old['amount_min'] || $amountMax != $old['amount_max']) $changes[] = "금액범위 변경";
        if ($counterparty !== ($old['counterparty'] ?? '')) $changes[] = "거래처:{$old['counterparty']}→{$counterparty}";
        if ($recurrence !== ($old['recurrence'] ?? 'none')) $changes[] = "반복:{$old['recurrence']}→{$recurrence}";
        $memo = $changes ? implode(', ', $changes) : '변경 없음';

        $pdo->prepare("
            INSERT INTO classification_history
            (transaction_id, old_account_code, new_account_code, new_account_name, action, pattern_id, actor, memo)
            VALUES (NULL, ?, ?, ?, 'pattern_edit', ?, 'user', ?)
        ")->execute([$old['account_code'], $accountCode, $accountName, $id, $memo]);

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log("updateClassifyPattern: " . $e->getMessage());
        apiError('UPDATE_ERROR', '패턴 수정 실패', 500);
    }
    apiOk(['success' => true]);
}

function handleToggleClassifyPattern(): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    $active = (int)($input['is_active'] ?? 0);
    if (!$id) apiError('MISSING_DATA', 'id 필수');

    $pdo = getDBConnection();
    $pdo->prepare("UPDATE classification_patterns SET is_active = ? WHERE id = ?")->execute([$active, $id]);
    apiOk(['success' => true]);
}

function handleDeleteClassifyPattern(): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if (!$id) apiError('MISSING_DATA', 'id 필수');

    $pdo = getDBConnection();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE classification_history SET pattern_id = NULL, memo = CONCAT(IFNULL(memo,''), ' [패턴 삭제됨]') WHERE pattern_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM classification_patterns WHERE id = ?");
        $stmt->execute([$id]);
        $deleted = $stmt->rowCount();

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log("deleteClassifyPattern error: " . $e->getMessage());
        apiError('DELETE_ERROR', '패턴 삭제 실패', 500);
    }
    apiOk(['success' => true, 'deleted' => $deleted]);
}

// ─── 벌크 패턴 액션 (다건 선택 → 확정/해제/숨기기/삭제) ───

function handleBulkPatternAction(): void
{
    $input = apiJsonInput();
    $ids = $input['ids'] ?? [];
    $bulkAction = trim($input['bulk_action'] ?? '');

    if (!is_array($ids) || count($ids) === 0) apiError('MISSING_DATA', 'ids 배열 필수');
    if (count($ids) > 500) apiError('TOO_MANY', '한 번에 500개까지만 처리 가능');

    $allowed = ['lock', 'unlock', 'hide', 'show', 'delete'];
    if (!in_array($bulkAction, $allowed, true)) {
        apiError('INVALID_ACTION', "허용된 액션: " . implode(', ', $allowed));
    }

    $pdo = getDBConnection();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $intIds = array_map('intval', $ids);
    $affected = 0;

    switch ($bulkAction) {
        case 'lock':
            $stmt = $pdo->prepare("UPDATE classification_patterns SET source = 'user', confidence = 100 WHERE id IN ($placeholders)");
            $stmt->execute($intIds);
            $affected = $stmt->rowCount();
            break;
        case 'unlock':
            $stmt = $pdo->prepare("UPDATE classification_patterns SET source = 'ai' WHERE id IN ($placeholders) AND source = 'user'");
            $stmt->execute($intIds);
            $affected = $stmt->rowCount();
            break;
        case 'hide':
            $stmt = $pdo->prepare("UPDATE classification_patterns SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($intIds);
            $affected = $stmt->rowCount();
            break;
        case 'show':
            $stmt = $pdo->prepare("UPDATE classification_patterns SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($intIds);
            $affected = $stmt->rowCount();
            break;
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM classification_patterns WHERE id IN ($placeholders)");
            $stmt->execute($intIds);
            $affected = $stmt->rowCount();
            break;
    }

    apiOk(['affected' => $affected, 'action' => $bulkAction]);
}

// ─── 확정 규칙 단건 생성 (분류 화면에서 "이런 건 항상 확정") ───

function handleCreateLockRule(): void
{
    $input = apiJsonInput();
    $keyword      = trim($input['keyword'] ?? '');
    $accountCode  = trim($input['account_code'] ?? '');
    $accountName  = trim($input['account_name'] ?? '');
    $txType       = $input['tx_type'] ?? '전체';
    $counterparty = !empty($input['counterparty']) ? mb_substr(trim($input['counterparty']), 0, 100) : null;

    if (mb_strlen($keyword) < 2)  apiError('MISSING_DATA', '적요(키워드)는 2자 이상이어야 합니다');
    if ($accountCode === '')      apiError('MISSING_DATA', '계정과목을 먼저 지정하세요');
    if (!in_array($txType, ['입금', '출금', '전체'], true)) $txType = '전체';

    $pdo = getDBConnection();
    $priority = calcPriority($txType, null, null, $counterparty, 'none');

    // 확정 규칙 = source 'user', confidence 100. 같은 (keyword,tx_type,account_code)면 확정으로 승격.
    $stmt = $pdo->prepare("
        INSERT INTO classification_patterns
        (keyword, tx_type, account_code, account_name, counterparty, recurrence, priority, confidence, hit_count, source, is_active)
        VALUES (?, ?, ?, ?, ?, 'none', ?, 100, 1, 'user', 1)
        ON DUPLICATE KEY UPDATE
            source = 'user', confidence = 100, account_name = VALUES(account_name),
            counterparty = VALUES(counterparty), is_active = 1
    ");
    $stmt->execute([$keyword, $txType, $accountCode, $accountName, $counterparty, $priority]);

    apiOk(['created' => true]);
}

function handleBulkImportPatterns(): void
{
    $input = apiJsonInput();
    $patterns = $input['patterns'] ?? [];
    if (empty($patterns) || !is_array($patterns)) {
        apiError('MISSING_DATA', 'patterns 배열 필수');
    }
    $maxImport = 2000;
    if (count($patterns) > $maxImport) {
        apiError('TOO_MANY_PATTERNS', "패턴은 최대 {$maxImport}건까지 임포트 가능합니다", 400);
    }

    $pdo = getDBConnection();
    $validTxTypes = ['입금', '출금', '전체'];
    $validRecurrences = ['none', 'daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual'];

    $stmt = $pdo->prepare("
        INSERT INTO classification_patterns
        (keyword, tx_type, account_code, account_name, amount_min, amount_max,
         counterparty, recurrence, priority, confidence, hit_count, source, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rule', 1)
        ON DUPLICATE KEY UPDATE
            hit_count = VALUES(hit_count),
            confidence = VALUES(confidence),
            amount_min = VALUES(amount_min),
            amount_max = VALUES(amount_max),
            counterparty = VALUES(counterparty),
            recurrence = VALUES(recurrence),
            priority = VALUES(priority)
    ");

    $imported = 0;
    $skipped = 0;
    $errors = [];

    $pdo->beginTransaction();
    try {
        foreach ($patterns as $i => $p) {
            $keyword = trim($p['keyword'] ?? '');
            if (strlen($keyword) < 2) { $skipped++; continue; }

            $txType = in_array($p['tx_type'] ?? '', $validTxTypes) ? $p['tx_type'] : '전체';
            $accountCode = trim($p['account_code'] ?? '');
            $accountName = trim($p['account_name'] ?? '');
            if (!$accountCode) { $skipped++; continue; }

            $amountMin = isset($p['amount_min']) && $p['amount_min'] !== null ? (int)$p['amount_min'] : null;
            $amountMax = isset($p['amount_max']) && $p['amount_max'] !== null ? (int)$p['amount_max'] : null;
            $counterparty = !empty($p['counterparty']) ? mb_substr(trim($p['counterparty']), 0, 100) : null;
            $recurrence = in_array($p['recurrence'] ?? '', $validRecurrences) ? $p['recurrence'] : 'none';
            $priority = (int)($p['priority'] ?? 0);
            $confidence = min(99, max(0, (float)($p['confidence'] ?? 70)));
            $hitCount = max(0, (int)($p['hit_count'] ?? 1));

            try {
                $stmt->execute([
                    mb_substr($keyword, 0, 100), $txType, $accountCode, mb_substr($accountName, 0, 50),
                    $amountMin, $amountMax, $counterparty, $recurrence,
                    $priority, $confidence, $hitCount,
                ]);
                $imported++;
            } catch (Throwable $e) {
                error_log("bulk_import_patterns #{$i}: " . $e->getMessage());
                $errors[] = "패턴 #{$i}: 저장 실패";
                if (count($errors) >= 10) break;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('bulk_import_patterns transaction error: ' . $e->getMessage());
        apiError('IMPORT_FAILED', '패턴 임포트 중 오류가 발생했습니다', 500);
    }

    apiOk([
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
        'total_sent' => count($patterns),
    ]);
}

function handleGetClassifyHistory(): void
{
    $pdo = getDBConnection();
    $where = ['1=1'];
    $params = [];

    $filterAction = $_GET['filter_action'] ?? '';
    if ($filterAction && in_array($filterAction, ['auto_classify', 'manual_classify', 'confirm', 'modify', 'pattern_edit'])) {
        $where[] = 'ch.action = ?';
        $params[] = $filterAction;
    }

    $stmt = $pdo->prepare("
        SELECT ch.*,
               bt.description as tx_desc, bt.amount as tx_amount, bt.tx_type, bt.transaction_date as tx_date
        FROM classification_history ch
        LEFT JOIN bank_transactions bt ON bt.id = ch.transaction_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY ch.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    apiOk(['history' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
}

// ─── 세금계산서 매칭 AI 보강 ───

function handleEnhanceMatch(): void
{
    $input = apiJsonInput();
    $invoiceId = (int)($input['invoice_id'] ?? 0);

    if ($invoiceId <= 0) {
        apiError('MISSING_DATA', '세금계산서 ID가 필요합니다.');
    }

    $provider = $input['provider'] ?? ai_get_default_provider();
    $model    = $input['model']    ?? ai_get_default_model();

    if (!$provider) {
        apiError('NO_PROVIDER', 'AI 프로바이더가 설정되지 않았습니다.');
    }

    $pdo = getDBConnection();

    $inv = $pdo->prepare("SELECT * FROM tax_invoices WHERE id = ?");
    $inv->execute([$invoiceId]);
    $invoice = $inv->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        apiError('NOT_FOUND', '세금계산서를 찾을 수 없습니다.', 404);
    }

    $issueDate = $invoice['issue_date'];
    $totalAmt  = (int)$invoice['total_amount'];
    $dateFrom  = date('Y-m-d', strtotime($issueDate . ' -30 days'));
    $dateTo    = date('Y-m-d', strtotime($issueDate . ' +30 days'));
    $amtLow    = (int)($totalAmt * 0.8);
    $amtHigh   = (int)($totalAmt * 1.2);

    $expectedTxType = ($invoice['invoice_type'] === '매출') ? '입금' : '출금';

    $txStmt = $pdo->prepare("
        SELECT bt.* FROM bank_transactions bt
        LEFT JOIN invoice_bank_mappings ibm ON ibm.transaction_id = bt.id AND ibm.is_confirmed = 1
        WHERE bt.transaction_date BETWEEN ? AND ?
          AND bt.amount BETWEEN ? AND ?
          AND bt.tx_type = ?
          AND ibm.id IS NULL
        ORDER BY ABS(bt.amount - ?) ASC, ABS(DATEDIFF(bt.transaction_date, ?)) ASC
        LIMIT 10
    ");
    $txStmt->execute([$dateFrom, $dateTo, $amtLow, $amtHigh, $expectedTxType, $totalAmt, $issueDate]);
    $txList = $txStmt->fetchAll(PDO::FETCH_ASSOC);

    $reverseTxType = ($expectedTxType === '입금') ? '출금' : '입금';
    $revStmt = $pdo->prepare("
        SELECT bt.* FROM bank_transactions bt
        LEFT JOIN invoice_bank_mappings ibm ON ibm.transaction_id = bt.id AND ibm.is_confirmed = 1
        WHERE bt.transaction_date BETWEEN ? AND ?
          AND bt.amount BETWEEN ? AND ?
          AND bt.tx_type = ?
          AND ibm.id IS NULL
        ORDER BY ABS(bt.amount - ?) ASC, ABS(DATEDIFF(bt.transaction_date, ?)) ASC
        LIMIT 3
    ");
    $revStmt->execute([$dateFrom, $dateTo, $amtLow, $amtHigh, $reverseTxType, $totalAmt, $issueDate]);
    $reverseTxList = $revStmt->fetchAll(PDO::FETCH_ASSOC);

    $txList = array_merge($txList, $reverseTxList);

    if (empty($txList)) {
        apiOk([
            'best_match_id' => null,
            'confidence'    => 0,
            'reason'        => '매칭 범위(금액 ±20%, 날짜 ±30일) 내에 후보 거래가 없습니다.',
        ]);
        return;
    }

    $systemPrompt = <<<PROMPT
당신은 한국 중소기업의 세무 전문가입니다.
세금계산서와 통장 거래내역을 비교하여 가장 적합한 매칭을 찾습니다.

## 매칭 기준
1. 금액 일치: 공급가액 또는 합계금액과 거래금액이 일치하거나 유사
2. 날짜 근접: 발행일과 거래일이 가까울수록 좋음 (30일 이내)
3. 거래처 일치: 세금계산서의 거래처와 거래 적요에 같은 이름이 포함
4. 거래방향: 매출→입금, 매입→출금이 정상. 반대 방향이면 환불/반품 가능성 있으므로 별도 표기

매칭이 불확실하면 best_match_id를 null로 반환하세요.
반대 방향 거래가 가장 적합하다면, reason에 "[반대방향]"을 포함하세요.

## 응답 형식 (JSON만 출력)
{"best_match_id": 거래ID또는null, "confidence": 0-100, "reason": "매칭 근거 1-2문장"}
PROMPT;

    $candidateInfo = array_map(fn($tx) => [
        'id'          => (int)$tx['id'],
        'date'        => $tx['transaction_date'],
        'description' => $tx['description'],
        'amount'      => (int)$tx['amount'],
        'type'        => $tx['tx_type'],
    ], $txList);

    $partnerName = $invoice['invoice_type'] === '매출' ? $invoice['buyer_name'] : $invoice['supplier_name'];

    $userPrompt = "세금계산서:\n" . json_encode([
        'id'            => (int)$invoice['id'],
        'issue_date'    => $invoice['issue_date'],
        'type'          => $invoice['invoice_type'],
        'supply_amount' => (int)$invoice['supply_amount'],
        'total_amount'  => $totalAmt,
        'partner'       => $partnerName,
    ], JSON_UNESCAPED_UNICODE) . "\n\n후보 거래내역:\n" . json_encode($candidateInfo, JSON_UNESCAPED_UNICODE);

    $result = ai_request($provider, $model, $systemPrompt, $userPrompt, [
        'max_tokens'  => 500,
        'temperature' => 0.1,
        'json_mode'   => ($provider === 'openai' || $provider === 'google'),
    ]);

    if (!$result['ok']) {
        apiError('AI_ERROR', 'AI 호출 실패: ' . ($result['error'] ?? ''), 502);
    }

    $parsed = null;
    if (preg_match('/\{[\s\S]*?\}/', $result['content'], $m)) {
        $parsed = json_decode($m[0], true);
    }

    if (!$parsed) {
        apiError('PARSE_ERROR', 'AI 응답을 파싱할 수 없습니다.', 502);
    }

    $matchId = $parsed['best_match_id'] ?? null;
    $matchDesc = '';
    $matchAmt  = 0;
    if ($matchId) {
        foreach ($txList as $tx) {
            if ((int)$tx['id'] === (int)$matchId) {
                $matchDesc = $tx['description'];
                $matchAmt  = (int)$tx['amount'];
                break;
            }
        }
    }

    apiOk([
        'best_match_id'          => $matchId,
        'best_match_description' => $matchDesc,
        'best_match_amount'      => $matchAmt,
        'confidence'             => max(0, min(100, (int)($parsed['confidence'] ?? 0))),
        'reason'                 => $parsed['reason'] ?? '',
        'usage'                  => $result['usage'],
        'provider_used'          => $provider,
    ]);
}

function handleBulkEnhanceMatch(): void
{
    $input = apiJsonInput();
    $provider = $input['provider'] ?? ai_get_default_provider();
    $model    = $input['model']    ?? ai_get_default_model();

    if (!$provider) {
        apiError('NO_PROVIDER', 'AI 프로바이더가 설정되지 않았습니다.');
    }

    $pdo = getDBConnection();

    $MAX_BULK_COUNT = 10;
    $unmatchedStmt = $pdo->prepare("
        SELECT ti.* FROM tax_invoices ti
        LEFT JOIN invoice_bank_mappings ibm ON ibm.invoice_id = ti.id
        WHERE ibm.id IS NULL AND ti.invoice_status != '취소'
        ORDER BY ti.issue_date DESC
        LIMIT ?
    ");
    $unmatchedStmt->execute([$MAX_BULK_COUNT]);
    $invoices = $unmatchedStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($invoices)) {
        apiOk(['results' => [], 'message' => '미매칭 세금계산서가 없습니다.']);
        return;
    }

    $results = [];
    $totalUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0];

    foreach ($invoices as $invoice) {
        $result = enhanceMatchSingle($pdo, $invoice, $provider, $model);
        $results[] = $result;
        if (isset($result['usage'])) {
            $totalUsage['prompt_tokens'] += ($result['usage']['prompt_tokens'] ?? 0);
            $totalUsage['completion_tokens'] += ($result['usage']['completion_tokens'] ?? 0);
        }
    }

    apiOk([
        'results'       => $results,
        'total_count'   => count($invoices),
        'matched_count' => count(array_filter($results, fn($r) => !empty($r['best_match_id']))),
        'total_usage'   => $totalUsage,
        'provider_used' => $provider,
    ]);
}

function enhanceMatchSingle(PDO $pdo, array $invoice, string $provider, string $model): array
{
    $issueDate = $invoice['issue_date'];
    $totalAmt  = (int)$invoice['total_amount'];
    $dateFrom  = date('Y-m-d', strtotime($issueDate . ' -30 days'));
    $dateTo    = date('Y-m-d', strtotime($issueDate . ' +30 days'));
    $amtLow    = (int)($totalAmt * 0.8);
    $amtHigh   = (int)($totalAmt * 1.2);
    $expectedTxType = ($invoice['invoice_type'] === '매출') ? '입금' : '출금';

    $txStmt = $pdo->prepare("
        SELECT bt.* FROM bank_transactions bt
        LEFT JOIN invoice_bank_mappings ibm ON ibm.transaction_id = bt.id AND ibm.is_confirmed = 1
        WHERE bt.transaction_date BETWEEN ? AND ?
          AND bt.amount BETWEEN ? AND ?
          AND bt.tx_type = ?
          AND ibm.id IS NULL
        ORDER BY ABS(bt.amount - ?) ASC, ABS(DATEDIFF(bt.transaction_date, ?)) ASC
        LIMIT 10
    ");
    $txStmt->execute([$dateFrom, $dateTo, $amtLow, $amtHigh, $expectedTxType, $totalAmt, $issueDate]);
    $txList = $txStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($txList)) {
        return [
            'invoice_id'    => (int)$invoice['id'],
            'best_match_id' => null,
            'confidence'    => 0,
            'reason'        => '후보 거래 없음',
        ];
    }

    $partnerName = $invoice['invoice_type'] === '매출' ? $invoice['buyer_name'] : $invoice['supplier_name'];

    $systemPrompt = <<<PROMPT
당신은 한국 중소기업의 세무 전문가입니다.
세금계산서와 통장 거래내역을 비교하여 가장 적합한 매칭을 찾습니다.

## 매칭 기준
1. 금액 일치: 공급가액 또는 합계금액과 거래금액이 일치하거나 유사
2. 날짜 근접: 발행일과 거래일이 가까울수록 좋음 (30일 이내)
3. 거래처 일치: 세금계산서의 거래처와 거래 적요에 같은 이름이 포함

매칭이 불확실하면 best_match_id를 null로 반환하세요.

## 응답 형식 (JSON만 출력)
{"best_match_id": 거래ID또는null, "confidence": 0-100, "reason": "매칭 근거 1-2문장"}
PROMPT;

    $candidateInfo = array_map(fn($tx) => [
        'id'          => (int)$tx['id'],
        'date'        => $tx['transaction_date'],
        'description' => $tx['description'],
        'amount'      => (int)$tx['amount'],
        'type'        => $tx['tx_type'],
    ], $txList);

    $userPrompt = "세금계산서:\n" . json_encode([
        'id'            => (int)$invoice['id'],
        'issue_date'    => $issueDate,
        'type'          => $invoice['invoice_type'],
        'supply_amount' => (int)$invoice['supply_amount'],
        'total_amount'  => $totalAmt,
        'partner'       => $partnerName,
    ], JSON_UNESCAPED_UNICODE) . "\n\n후보 거래내역:\n" . json_encode($candidateInfo, JSON_UNESCAPED_UNICODE);

    $result = ai_request($provider, $model, $systemPrompt, $userPrompt, [
        'max_tokens'  => 500,
        'temperature' => 0.1,
        'json_mode'   => ($provider === 'openai' || $provider === 'google'),
    ]);

    if (!$result['ok']) {
        return [
            'invoice_id'    => (int)$invoice['id'],
            'best_match_id' => null,
            'confidence'    => 0,
            'reason'        => 'AI 호출 실패',
            'error'         => $result['error'] ?? '',
        ];
    }

    $parsed = null;
    if (preg_match('/\{[\s\S]*?\}/', $result['content'], $m)) {
        $parsed = json_decode($m[0], true);
    }

    if (!$parsed) {
        return [
            'invoice_id'    => (int)$invoice['id'],
            'best_match_id' => null,
            'confidence'    => 0,
            'reason'        => 'AI 응답 파싱 실패',
        ];
    }

    return [
        'invoice_id'             => (int)$invoice['id'],
        'best_match_id'          => $parsed['best_match_id'] ?? null,
        'confidence'             => max(0, min(100, (int)($parsed['confidence'] ?? 0))),
        'reason'                 => $parsed['reason'] ?? '',
        'usage'                  => $result['usage'] ?? [],
    ];
}

// ─── 세무 리포트 AI 인사이트 ───

function handleReportInsights(): void
{
    $input = apiJsonInput();
    $year    = (int)($input['year']  ?? date('Y'));
    $month   = (int)($input['month'] ?? date('m'));

    $provider = $input['provider'] ?? ai_get_default_provider();
    $model    = $input['model']    ?? ai_get_default_model();

    if (!$provider) {
        apiError('NO_PROVIDER', 'AI 프로바이더가 설정되지 않았습니다.');
    }

    $pdo = getDBConnection();
    $financialData = gatherFinancialData($pdo, $year, $month);

    $systemPrompt = <<<PROMPT
당신은 한국 중소기업 세무 분석 전문가입니다.
월별 재무 데이터를 분석하여 실용적인 인사이트를 제공합니다.

## 분석 항목
1. 매출/매입 추이 분석 (전월 대비 변화)
2. 주요 지출 항목 분석 (비정상적 증감 탐지)
3. 부가세 예상액 계산
4. 절세 제안 (적용 가능한 세액공제, 비용 처리 방법)
5. 주의사항 (마감 기한, 신고 준비 등)

## 응답 형식 (JSON 배열만 출력)
반드시 아래 형식의 JSON 배열만 반환하세요. 설명 텍스트 없이 배열만 출력합니다.
[
  {"title": "제목", "type": "trend|anomaly|saving|risk|recommendation|positive", "content": "분석 내용 2-3문장", "action": "권장 조치 (선택)"},
  ...
]

type 설명:
- trend: 매출/매입 추이, 전월 대비 변화
- anomaly: 비정상적 증감, 이상치 발견
- saving: 절세 기회, 비용 절감 제안
- risk: 세무 리스크, 마감 주의사항
- recommendation: 일반 제안/조언
- positive: 긍정적 지표, 잘 하고 있는 부분

4~6개의 인사이트를 제공하세요.
PROMPT;

    $userPrompt = "{$year}년 {$month}월 재무 데이터:\n" . json_encode($financialData, JSON_UNESCAPED_UNICODE);

    $result = ai_request($provider, $model, $systemPrompt, $userPrompt, [
        'max_tokens'  => 2048,
        'temperature' => 0.3,
        'json_mode'   => ($provider === 'openai' || $provider === 'google'),
    ]);

    if (!$result['ok']) {
        apiError('AI_ERROR', 'AI 호출 실패: ' . ($result['error'] ?? ''), 502);
    }

    $insights = ai_parse_json_array($result['content']);

    if ($insights === null) {
        if (preg_match('/\{[\s\S]*\}/', $result['content'], $m)) {
            $obj = json_decode($m[0], true);
            if (is_array($obj) && isset($obj['insights'])) {
                $insights = $obj['insights'];
            }
        }
    }

    if ($insights === null) {
        $insights = [['title' => 'AI 분석 결과', 'type' => 'recommendation', 'content' => $result['content']]];
    }

    apiOk([
        'insights'      => $insights,
        'usage'         => $result['usage'],
        'provider_used' => $provider,
        'year'          => $year,
        'month'         => $month,
    ]);
}

// ─── 헬퍼 함수 ───

function loadAccountCategories(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SELECT code, name, type, tax_type FROM account_categories WHERE is_active = 1 AND code NOT LIKE 'G\\_%' ESCAPE '\\\\' ORDER BY FIELD(type, '자산','부채','자본','매출','매입','비용','수익'), sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function loadActivePatterns(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("
            SELECT keyword, tx_type, account_code, account_name, amount_min, amount_max,
                   counterparty, confidence, hit_count, recurrence, recurrence_day
            FROM classification_patterns
            WHERE is_active = 1 AND confidence >= 50
            ORDER BY hit_count DESC, confidence DESC
            LIMIT 100
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function loadConfirmedExamples(PDO $pdo, int $limit = 50): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT description, tx_type, amount, counterparty, account_code, account_name
            FROM bank_transactions
            WHERE is_confirmed = 1 AND account_code IS NOT NULL
            ORDER BY uploaded_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function buildClassifySystemPrompt(array $categories, array $patterns = [], array $confirmedExamples = []): string
{
    $catJson = json_encode(array_map(fn($c) => [
        'code' => $c['code'],
        'name' => $c['name'],
        'type' => $c['type'],
    ], $categories), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $prompt = <<<PROMPT
당신은 한국 중소기업의 경리 담당자입니다.
통장 거래내역의 적요(description)를 보고 적절한 계정과목을 분류합니다.

## 사용 가능한 계정과목
{$catJson}

## 규칙
1. 각 거래에 대해 가장 적합한 계정과목 코드와 이름을 선택하세요.
2. 신뢰도(confidence)를 0-100 사이로 평가하세요:
   - 90-100: 확실한 분류 (예: "KT 통신비" → 통신비)
   - 70-89: 높은 확률의 분류
   - 50-69: 추정 분류 (사용자 확인 필요)
   - 50 미만: 불확실 (수동 분류 권장)
3. 입금 거래는 매출(401-404) 또는 수익(601-602) 계정을 우선 고려하세요.
4. 출금 거래는 비용(511-533) 계정을 우선 고려하세요.
5. 적요에 회사명이 포함된 입금은 서비스매출(403)로 분류합니다.
6. 급여, 세금, 보험료 등 정기적 출금은 해당 비용 계정으로 분류합니다.
7. 분류 근거를 간단히 1문장으로 설명하세요.
PROMPT;

    if (!empty($patterns)) {
        $patternLines = [];
        foreach (array_slice($patterns, 0, 50) as $p) {
            $line = "- \"{$p['keyword']}\"({$p['tx_type']}) → {$p['account_name']}({$p['account_code']})";
            if ($p['hit_count'] > 0) $line .= " [적중 {$p['hit_count']}회]";
            if ($p['counterparty']) $line .= " [거래처: {$p['counterparty']}]";
            $patternLines[] = $line;
        }
        $prompt .= "\n\n## 학습된 분류 패턴 (이 회사에서 이미 확인된 규칙)\n아래 패턴과 유사한 거래는 같은 계정과목으로 분류하세요. 적중 횟수가 많을수록 신뢰도를 높게 평가하세요.\n" . implode("\n", $patternLines);
    }

    if (!empty($confirmedExamples)) {
        $exampleLines = [];
        $seen = [];
        foreach ($confirmedExamples as $ex) {
            $key = $ex['description'] . '|' . $ex['account_code'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $typeLabel = $ex['tx_type'] === '입금' ? '입금' : '출금';
            $exampleLines[] = "- {$ex['description']}({$typeLabel}, " . number_format((int)$ex['amount']) . "원) → {$ex['account_name']}({$ex['account_code']})";
        }
        if (!empty($exampleLines)) {
            $prompt .= "\n\n## 사용자가 직접 확정한 분류 이력 (가장 신뢰할 수 있는 참고 자료)\n동일하거나 유사한 적요는 아래와 같은 방식으로 분류하세요.\n" . implode("\n", array_slice($exampleLines, 0, 30));
        }
    }

    $prompt .= "\n\n## 응답 형식\n반드시 JSON 배열만 출력하세요.";

    return $prompt;
}

function buildClassifyUserPrompt(array $transactions): string
{
    $items = array_map(fn($t) => [
        'id'          => $t['id'] ?? 0,
        'date'        => $t['date'] ?? '',
        'description' => $t['description'] ?? '',
        'amount'      => $t['amount'] ?? 0,
        'type'        => $t['type'] ?? '',
    ], $transactions);

    return "다음 거래내역을 분류해주세요:\n" . json_encode($items, JSON_UNESCAPED_UNICODE) .
           "\n\n응답: [{\"id\": 거래ID, \"account_code\": \"코드\", \"account_name\": \"계정과목명\", \"confidence\": 숫자, \"reason\": \"근거\"}]";
}

function gatherFinancialData(PDO $pdo, int $year, int $month): array
{
    $dateFrom = sprintf('%04d-%02d-01', $year, $month);
    $dateTo   = date('Y-m-t', strtotime($dateFrom));

    $prevMonth = $month > 1 ? $month - 1 : 12;
    $prevYear  = $month > 1 ? $year : $year - 1;
    $prevFrom  = sprintf('%04d-%02d-01', $prevYear, $prevMonth);
    $prevTo    = date('Y-m-t', strtotime($prevFrom));

    $data = [
        'period'   => "{$year}년 {$month}월",
        'current'  => ['sales' => 0, 'purchase' => 0, 'expense_by_category' => []],
        'previous' => ['sales' => 0, 'purchase' => 0],
    ];

    try {
        $invStmt = $pdo->prepare("
            SELECT invoice_type, SUM(total_amount) as total
            FROM tax_invoices
            WHERE issue_date BETWEEN ? AND ? AND invoice_status != '취소'
            GROUP BY invoice_type
        ");

        $invStmt->execute([$dateFrom, $dateTo]);
        foreach ($invStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['invoice_type'] === '매출') $data['current']['sales'] = (int)$row['total'];
            if ($row['invoice_type'] === '매입') $data['current']['purchase'] = (int)$row['total'];
        }

        $invStmt->execute([$prevFrom, $prevTo]);
        foreach ($invStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['invoice_type'] === '매출') $data['previous']['sales'] = (int)$row['total'];
            if ($row['invoice_type'] === '매입') $data['previous']['purchase'] = (int)$row['total'];
        }

        $catStmt = $pdo->prepare("
            SELECT account_name, SUM(amount) as total, COUNT(*) as cnt
            FROM bank_transactions
            WHERE transaction_date BETWEEN ? AND ?
              AND tx_type = '출금'
              AND account_name IS NOT NULL AND account_name != ''
            GROUP BY account_name
            ORDER BY total DESC
        ");
        $catStmt->execute([$dateFrom, $dateTo]);
        foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data['current']['expense_by_category'][$row['account_name']] = (int)$row['total'];
        }
    } catch (Throwable $e) {
        error_log('gatherFinancialData: ' . $e->getMessage());
    }

    return $data;
}
