<?php
/**
 * Zaemit 그룹웨어 - 세금계산서 API
 * 홈택스 연동 세금계산서 매출/매입 통합 관리
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'getInvoices'            => getInvoices($pdo),
        'getSummary'             => getSummary($pdo),
        'getPartners'            => getPartners($pdo),
        'getSyncLog'             => getSyncLog($pdo),
        'uploadInvoices'         => uploadInvoices($pdo),
        'autoMatch'              => autoMatch($pdo),
        'getMatchCandidates'     => getMatchCandidates($pdo),
        'getMatchStatus'         => getMatchStatus($pdo),
        'saveMatch'              => saveMatch($pdo),
        'removeMatch'            => removeMatch($pdo),
        'unconfirmMatch'         => unconfirmMatch($pdo),
        'confirmMatches'         => confirmMatches($pdo),
        'getPatterns'            => getPatterns($pdo),
        'togglePattern'          => togglePattern($pdo),
        'deletePattern'          => deletePattern($pdo),
        'updatePattern'          => updatePattern($pdo),
        'getPatternHistory'      => getPatternHistory($pdo),
        'getMatchHistory'        => getMatchHistory($pdo),
        default                  => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (PDOException $e) {
    error_log('TaxInvoice API: ' . $e->getMessage());
    respond(500, ['error' => '서버 오류가 발생했습니다.']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 세금계산서 목록 조회
 */
function getInvoices(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    // 매출/매입 필터
    $type = $_GET['invoice_type'] ?? '';
    if ($type && in_array($type, ['매출', '매입'])) {
        $where[] = 'invoice_type = ?';
        $params[] = $type;
    }

    // 기간 필터
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    if ($dateFrom) { $where[] = 'issue_date >= ?'; $params[] = $dateFrom; }
    if ($dateTo)   { $where[] = 'issue_date <= ?'; $params[] = $dateTo; }

    // 거래처 검색
    $partner = $_GET['partner'] ?? '';
    if ($partner) {
        $where[] = '(supplier_name LIKE ? OR buyer_name LIKE ?)';
        $params[] = "%{$partner}%";
        $params[] = "%{$partner}%";
    }

    // 사업자번호 검색
    $bizno = $_GET['bizno'] ?? '';
    if ($bizno) {
        $where[] = '(supplier_bizno LIKE ? OR buyer_bizno LIKE ?)';
        $params[] = "%{$bizno}%";
        $params[] = "%{$bizno}%";
    }

    // 상태 필터
    $status = $_GET['invoice_status'] ?? '';
    if ($status && in_array($status, ['정상', '수정', '취소'])) {
        $where[] = 'invoice_status = ?';
        $params[] = $status;
    }

    $sql = 'SELECT * FROM tax_invoices WHERE ' . implode(' AND ', $where) . ' ORDER BY issue_date DESC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 탭별 카운트
    $countSql = 'SELECT invoice_type, COUNT(*) as cnt FROM tax_invoices WHERE ' . implode(' AND ', array_filter($where, fn($w) => !str_contains($w, 'invoice_type'))) . ' GROUP BY invoice_type';
    $countParams = array_values(array_filter($params, fn($k) => $k !== $type, ARRAY_FILTER_USE_BOTH));
    // 간단하게 전체 카운트
    $allCount = count($invoices);
    $salesCount = count(array_filter($invoices, fn($i) => $i['invoice_type'] === '매출'));
    $purchaseCount = count(array_filter($invoices, fn($i) => $i['invoice_type'] === '매입'));

    respond(200, [
        'invoices' => $invoices,
        'counts' => [
            'all' => $allCount,
            'sales' => $salesCount,
            'purchase' => $purchaseCount,
        ],
    ]);
}

/**
 * 요약 통계
 */
function getSummary(PDO $pdo): void
{
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';

    $where = ["invoice_status != '취소'"];
    $params = [];
    if ($dateFrom) { $where[] = 'issue_date >= ?'; $params[] = $dateFrom; }
    if ($dateTo)   { $where[] = 'issue_date <= ?'; $params[] = $dateTo; }

    $sql = "SELECT
                invoice_type,
                COUNT(*) as cnt,
                COALESCE(SUM(supply_amount), 0) as supply_total,
                COALESCE(SUM(tax_amount), 0) as tax_total,
                COALESCE(SUM(total_amount), 0) as total
            FROM tax_invoices
            WHERE " . implode(' AND ', $where) . "
            GROUP BY invoice_type";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = ['sales' => null, 'purchase' => null];
    foreach ($rows as $row) {
        $key = $row['invoice_type'] === '매출' ? 'sales' : 'purchase';
        $summary[$key] = $row;
    }

    respond(200, ['summary' => $summary]);
}

/**
 * 거래처별 집계
 */
function getPartners(PDO $pdo): void
{
    $type = $_GET['invoice_type'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';

    $where = ["invoice_status != '취소'"];
    $params = [];
    if ($type && in_array($type, ['매출', '매입'])) {
        $where[] = 'invoice_type = ?';
        $params[] = $type;
    }
    if ($dateFrom) { $where[] = 'issue_date >= ?'; $params[] = $dateFrom; }
    if ($dateTo)   { $where[] = 'issue_date <= ?'; $params[] = $dateTo; }

    $sql = "SELECT
                CASE WHEN invoice_type = '매출' THEN buyer_name ELSE supplier_name END as partner_name,
                CASE WHEN invoice_type = '매출' THEN buyer_bizno ELSE supplier_bizno END as partner_bizno,
                invoice_type,
                COUNT(*) as cnt,
                SUM(supply_amount) as supply_total,
                SUM(tax_amount) as tax_total
            FROM tax_invoices
            WHERE " . implode(' AND ', $where) . "
            GROUP BY partner_name, partner_bizno, invoice_type
            ORDER BY supply_total DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    respond(200, ['partners' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

/**
 * 동기화 이력 조회
 */
function getSyncLog(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT * FROM hometax_sync_log ORDER BY started_at DESC LIMIT 20');
    respond(200, ['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// =============================================================
// 홈택스 업로드
// =============================================================

function uploadInvoices(PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!$input) respond(400, ['error' => '요청 데이터가 없습니다.']);

    $invoiceType = $input['invoice_type'] ?? '';
    $year  = (int)($input['year'] ?? 0);
    $month = (int)($input['month'] ?? 0);
    $rows  = $input['invoices'] ?? [];

    if (!in_array($invoiceType, ['매출', '매입'])) {
        respond(400, ['error' => '매출 또는 매입을 선택해주세요.']);
    }
    if ($year < 2020 || $year > 2099 || $month < 1 || $month > 12) {
        respond(400, ['error' => '올바른 연도/월을 선택해주세요.']);
    }
    if (empty($rows) || !is_array($rows)) {
        respond(400, ['error' => '업로드할 데이터가 없습니다.']);
    }

    $companyId = 1;
    $inserted = 0;
    $updated  = 0;
    $skipped  = 0;
    $errors   = [];

    $sql = "INSERT INTO tax_invoices
        (company_id, invoice_type, invoice_number, issue_date, send_date,
         supplier_bizno, supplier_name, supplier_ceo,
         buyer_bizno, buyer_name, buyer_ceo,
         supply_amount, tax_amount, total_amount, tax_type,
         invoice_status, hometax_sync, synced_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '정상', 1, NOW())
        ON DUPLICATE KEY UPDATE
            issue_date     = VALUES(issue_date),
            send_date      = VALUES(send_date),
            supplier_name  = VALUES(supplier_name),
            supplier_ceo   = VALUES(supplier_ceo),
            buyer_name     = VALUES(buyer_name),
            buyer_ceo      = VALUES(buyer_ceo),
            supply_amount  = VALUES(supply_amount),
            tax_amount     = VALUES(tax_amount),
            total_amount   = VALUES(total_amount),
            tax_type       = VALUES(tax_type),
            hometax_sync   = 1,
            synced_at      = NOW()";

    $stmt = $pdo->prepare($sql);
    $pdo->beginTransaction();

    try {
        foreach ($rows as $i => $row) {
            $invoiceNumber = trim($row['invoice_number'] ?? '');
            if (!$invoiceNumber) {
                $skipped++;
                continue;
            }

            $issueDate  = normalizeDate($row['issue_date'] ?? '');
            $sendDate   = normalizeDate($row['send_date'] ?? '') ?: null;
            if (!$issueDate) {
                $errors[] = ['row' => $i + 1, 'reason' => '작성일자 형식 오류'];
                $skipped++;
                continue;
            }

            $supplyAmount = normalizeAmount($row['supply_amount'] ?? 0);
            $taxAmount    = normalizeAmount($row['tax_amount'] ?? 0);
            $totalAmount  = normalizeAmount($row['total_amount'] ?? 0);
            if ($totalAmount === 0 && $supplyAmount > 0) {
                $totalAmount = $supplyAmount + $taxAmount;
            }

            $taxType = resolveTaxType($row);

            $stmt->execute([
                $companyId,
                $invoiceType,
                $invoiceNumber,
                $issueDate,
                $sendDate,
                trim($row['supplier_bizno'] ?? ''),
                trim($row['supplier_name'] ?? ''),
                trim($row['supplier_ceo'] ?? '') ?: null,
                trim($row['buyer_bizno'] ?? ''),
                trim($row['buyer_name'] ?? ''),
                trim($row['buyer_ceo'] ?? '') ?: null,
                $supplyAmount,
                $taxAmount,
                $totalAmount,
                $taxType,
            ]);

            $affected = $stmt->rowCount();
            if ($affected === 1) $inserted++;
            elseif ($affected === 2) $updated++;
            else $skipped++;
        }

        $syncType = $invoiceType === '매출' ? 'upload_sales' : 'upload_purchase';
        $syncMsg  = sprintf('%04d년 %02d월 %s %d건 업로드 (신규 %d, 갱신 %d, 건너뜀 %d)',
            $year, $month, $invoiceType, $inserted + $updated, $inserted, $updated, $skipped);

        $logStmt = $pdo->prepare("INSERT INTO hometax_sync_log (company_id, sync_type, sync_count, status, message, started_at, finished_at)
            VALUES (?, ?, ?, '성공', ?, NOW(), NOW())");
        $logStmt->execute([$companyId, $syncType, $inserted + $updated, $syncMsg]);

        $pdo->commit();

        respond(200, [
            'success'  => true,
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'message'  => $syncMsg,
        ]);
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('uploadInvoices: ' . $e->getMessage());
        respond(500, ['error' => '업로드 중 오류가 발생했습니다.']);
    }
}

/**
 * 과세유형 판정 · 명시 '과세유형' 우선, 없으면 홈택스 분류/종류로 추론.
 * 전자세금계산서분류: 세금계산서(과세)/계산서(면세), 전자세금계산서종류: 일반/영세율.
 */
function resolveTaxType(array $row): string
{
    $explicit = trim($row['tax_type'] ?? '');
    if ($explicit !== '') {
        return match (true) {
            str_contains($explicit, '영세') => '영세율',
            str_contains($explicit, '면세') => '면세',
            default                          => '과세',
        };
    }
    $cls  = trim($row['invoice_class'] ?? '');   // 세금계산서 / 계산서 / 수정세금계산서 ...
    $kind = trim($row['invoice_kind'] ?? '');    // 일반 / 영세율 ...
    if ($cls !== '' && !str_contains($cls, '세금계산서') && str_contains($cls, '계산서')) {
        return '면세';
    }
    if (str_contains($kind, '영세')) {
        return '영세율';
    }
    return '과세';
}

function normalizeDate(string $val): string
{
    $val = trim($val);
    if (!$val) return '';
    $val = str_replace(['.', '/'], '-', $val);
    if (preg_match('/^\d{8}$/', $val)) {
        $val = substr($val, 0, 4) . '-' . substr($val, 4, 2) . '-' . substr($val, 6, 2);
    }
    if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $val)) {
        $parts = explode('-', $val);
        return sprintf('%04d-%02d-%02d', (int)$parts[0], (int)$parts[1], (int)$parts[2]);
    }
    return '';
}

function normalizeAmount($val): int
{
    if (is_int($val) || is_float($val)) return (int)$val;
    $val = preg_replace('/[^0-9\-]/', '', (string)$val);
    return (int)$val;
}

// =============================================================
// 세금계산서 ↔ 통장 매핑
// =============================================================

function autoMatch(PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];

    $dateFrom = $input['date_from'] ?? '';
    $dateTo   = $input['date_to'] ?? '';
    if (!$dateFrom || !$dateTo) respond(400, ['error' => '기간을 지정해주세요.']);

    $txCheck = $pdo->prepare("
        SELECT COUNT(*) FROM bank_transactions
        WHERE transaction_date BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND DATE_ADD(?, INTERVAL 30 DAY)
    ");
    $txCheck->execute([$dateFrom, $dateTo]);
    $txCount = (int)$txCheck->fetchColumn();

    if ($txCount === 0) {
        respond(200, [
            'success'      => false,
            'no_bank_data' => true,
            'message'      => '해당 기간의 통장 거래내역이 없습니다. 통장내역을 먼저 조회해주세요.',
        ]);
    }

    $invoices = $pdo->prepare("
        SELECT ti.* FROM tax_invoices ti
        WHERE ti.issue_date BETWEEN ? AND ?
          AND ti.invoice_status != '취소'
          AND ti.id NOT IN (
              SELECT invoice_id FROM invoice_bank_mappings WHERE is_confirmed = 1
          )
        ORDER BY ti.issue_date
    ");
    $invoices->execute([$dateFrom, $dateTo]);
    $invoiceList = $invoices->fetchAll(PDO::FETCH_ASSOC);

    $activePatterns = $pdo->query("SELECT * FROM match_patterns WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

    $txStmt = $pdo->prepare("
        SELECT bt.* FROM bank_transactions bt
        WHERE bt.tx_type = ?
          AND bt.transaction_date BETWEEN ? AND ?
          AND bt.id NOT IN (
              SELECT transaction_id FROM invoice_bank_mappings WHERE is_confirmed = 1
          )
        ORDER BY bt.transaction_date
    ");
    $reverseTxStmt = $pdo->prepare("
        SELECT bt.* FROM bank_transactions bt
        WHERE bt.tx_type = ?
          AND bt.transaction_date BETWEEN ? AND ?
          AND bt.id NOT IN (
              SELECT transaction_id FROM invoice_bank_mappings WHERE is_confirmed = 1
          )
        ORDER BY bt.transaction_date
        LIMIT 5
    ");
    $saveStmt = $pdo->prepare("
        INSERT INTO invoice_bank_mappings (invoice_id, transaction_id, match_type, confidence, match_reason, name_warning)
        VALUES (?, ?, 'auto', ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            confidence   = IF(is_confirmed = 0, VALUES(confidence), confidence),
            match_reason = IF(is_confirmed = 0, VALUES(match_reason), match_reason),
            match_type   = IF(is_confirmed = 0, 'auto', match_type),
            name_warning = IF(is_confirmed = 0, VALUES(name_warning), name_warning)
    ");
    $histStmt = $pdo->prepare("
        INSERT INTO match_history (invoice_id, transaction_id, action, actor, memo)
        VALUES (?, ?, 'auto_match', 'system', ?)
    ");

    $MIN_AUTO_SCORE = 60;
    $MIN_GAP = 10;
    $REVERSE_PENALTY = 10;

    $pdo->beginTransaction();
    try {
        $scoreMatrix = [];

        foreach ($invoiceList as $inv) {
            $issueTs = strtotime($inv['issue_date']);
            if (!$issueTs) continue;
            if ($inv['invoice_status'] === '수정') continue;

            $direction = $inv['invoice_type'] === '매출' ? '입금' : '출금';
            $partnerName = $inv['invoice_type'] === '매출' ? $inv['buyer_name'] : $inv['supplier_name'];
            $dateMin = date('Y-m-d', strtotime('-30 days', $issueTs));
            $dateMax = date('Y-m-d', strtotime('+30 days', $issueTs));

            $txStmt->execute([$direction, $dateMin, $dateMax]);
            $candidates = $txStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($candidates as $tx) {
                $score = calcMatchScore($inv, $tx, $partnerName, $activePatterns);
                if ($score >= $MIN_AUTO_SCORE) {
                    $scoreMatrix[] = [
                        'inv' => $inv, 'tx' => $tx,
                        'score' => $score, 'partnerName' => $partnerName,
                        'reversed' => false,
                    ];
                }
            }

            $reverseDir = $direction === '입금' ? '출금' : '입금';
            $reverseTxStmt->execute([$reverseDir, $dateMin, $dateMax]);
            $reverseCandidates = $reverseTxStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($reverseCandidates as $tx) {
                $score = calcMatchScore($inv, $tx, $partnerName, $activePatterns) - $REVERSE_PENALTY;
                if ($score >= $MIN_AUTO_SCORE) {
                    $scoreMatrix[] = [
                        'inv' => $inv, 'tx' => $tx,
                        'score' => $score, 'partnerName' => $partnerName,
                        'reversed' => true,
                    ];
                }
            }
        }

        usort($scoreMatrix, fn($a, $b) => $b['score'] - $a['score']);

        $assignedInvoices = [];
        $assignedTxs = [];
        $matched = 0;

        foreach ($scoreMatrix as $entry) {
            $invId = $entry['inv']['id'];
            $txId  = $entry['tx']['id'];
            if (isset($assignedInvoices[$invId]) || isset($assignedTxs[$txId])) continue;

            $secondScore = 0;
            foreach ($scoreMatrix as $other) {
                if ($other['inv']['id'] === $invId && $other['tx']['id'] !== $txId
                    && !isset($assignedTxs[$other['tx']['id']])) {
                    $secondScore = $other['score'];
                    break;
                }
            }
            $gap = $entry['score'] - $secondScore;
            if ($gap < $MIN_GAP) continue;

            $partnerBizno = $entry['inv']['invoice_type'] === '매출'
                ? ($entry['inv']['buyer_bizno'] ?? '') : ($entry['inv']['supplier_bizno'] ?? '');
            $nameScore = nameMatchScore($entry['partnerName'], $entry['tx']['description'], $partnerBizno);
            $nameWarning = ($nameScore < 20 && $nameScore > 0) ? 1 : 0;

            $reason = buildMatchReason($entry['inv'], $entry['tx'], $entry['partnerName'], $entry['score']);
            if ($gap < 20) $reason .= ' [유사 후보 있음]';
            if ($entry['reversed']) $reason .= ' [반대방향]';
            if ($nameWarning) $reason .= ' [이름 유사]';

            $saveStmt->execute([$invId, $txId, $entry['score'], $reason, $nameWarning]);
            $histStmt->execute([$invId, $txId, $reason]);
            $assignedInvoices[$invId] = true;
            $assignedTxs[$txId] = true;
            $matched++;
        }

        $unmatched = count($invoiceList) - $matched;
        $pdo->commit();

        $unmatchedInvoices = array_filter($invoiceList, fn($inv) => !isset($assignedInvoices[$inv['id']]));
        $aggregateHints = detectAggregateMatches($pdo, $unmatchedInvoices);

        respond(200, [
            'success'         => true,
            'matched'         => $matched,
            'unmatched'       => $unmatched,
            'total'           => count($invoiceList),
            'aggregate_hints' => $aggregateHints,
        ]);
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('autoMatch: ' . $e->getMessage());
        respond(500, ['error' => '자동 매칭 중 오류가 발생했습니다.']);
    }
}

function calcMatchScore(array $inv, array $tx, string $partnerName, array $patterns = []): int
{
    $score = 0;

    $totalAmount  = abs((int)$inv['total_amount']);
    $supplyAmount = abs((int)$inv['supply_amount']);
    $txAmount     = (int)$tx['amount'];

    if ($txAmount === $totalAmount && $totalAmount > 0) {
        $score += 60;
    } elseif ($txAmount === $supplyAmount && $supplyAmount > 0) {
        $score += 45;
    } elseif ($totalAmount > 0) {
        $diff = abs($txAmount - $totalAmount);
        $ratio = $diff / $totalAmount;
        if ($ratio <= 0.005)     $score += 50;
        elseif ($ratio <= 0.01)  $score += 40;
        elseif ($ratio <= 0.02)  $score += 30;
        elseif ($ratio <= 0.05)  $score += 20;
    }

    $invTs = strtotime($inv['issue_date']);
    $txTs  = strtotime($tx['transaction_date']);
    if (!$invTs || !$txTs) return $score;
    $daysDiff = (int)abs(($invTs - $txTs) / 86400);

    if ($daysDiff === 0)       $score += 20;
    elseif ($daysDiff === 1)   $score += 18;
    elseif ($daysDiff === 2)   $score += 16;
    elseif ($daysDiff === 3)   $score += 15;
    elseif ($daysDiff <= 5)    $score += 13;
    elseif ($daysDiff <= 7)    $score += 11;
    elseif ($daysDiff <= 14)   $score += 8;
    elseif ($daysDiff <= 21)   $score += 5;
    elseif ($daysDiff <= 30)   $score += 3;

    $partnerBizno = $inv['invoice_type'] === '매출'
        ? ($inv['buyer_bizno'] ?? '') : ($inv['supplier_bizno'] ?? '');
    $nameScore = nameMatchScore($partnerName, $tx['description'], $partnerBizno);
    $score += $nameScore;

    $score += calcPatternBonus($inv, $tx, $partnerBizno, $patterns);

    return min($score, 100);
}

function calcPatternBonus(array $inv, array $tx, string $partnerBizno, array $patterns): int
{
    if (empty($patterns)) return 0;

    $txAmount = (int)$tx['amount'];
    $bonus = 0;

    foreach ($patterns as $p) {
        if ($p['partner_bizno'] && $p['partner_bizno'] !== $partnerBizno) continue;

        $rule = json_decode($p['pattern_rule'], true) ?? [];
        $pConf = (float)$p['confidence'] / 100;

        switch ($p['pattern_type']) {
            case 'amount_exact':
                if (isset($rule['amount']) && $txAmount === (int)$rule['amount']) {
                    $bonus = max($bonus, (int)(15 * $pConf));
                }
                break;
            case 'date_offset':
                if (isset($rule['offset_days'])) {
                    $invTs = strtotime($inv['issue_date']);
                    $txTs  = strtotime($tx['transaction_date']);
                    if ($invTs && $txTs) {
                        $actualOffset = (int)round(($txTs - $invTs) / 86400);
                        if ($actualOffset === (int)$rule['offset_days']) {
                            $bonus = max($bonus, (int)(12 * $pConf));
                        } elseif (abs($actualOffset - (int)$rule['offset_days']) <= 2) {
                            $bonus = max($bonus, (int)(8 * $pConf));
                        }
                    }
                }
                break;
            case 'description_keyword':
                if (isset($rule['keyword']) && mb_stripos($tx['description'], $rule['keyword']) !== false) {
                    $bonus = max($bonus, (int)(10 * $pConf));
                }
                break;
        }
    }
    return min($bonus, 15);
}

function nameMatchScore(string $partnerName, string $description, string $partnerBizno = ''): int
{
    if ($partnerBizno) {
        $cleanBizno = preg_replace('/[^0-9]/', '', $partnerBizno);
        $cleanDesc = preg_replace('/[^0-9a-zA-Z가-힣]/', '', $description);
        if ($cleanBizno && mb_strlen($cleanBizno) >= 10 && str_contains($cleanDesc, $cleanBizno)) {
            return 20;
        }
    }

    $clean = function(string $s): string {
        $s = mb_strtolower($s);
        $s = preg_replace('/\(주\)|주식회사|㈜|유한회사|합자회사|합명회사|사단법인|재단법인|사회적협동조합|협동조합|\(유\)|㈲/u', '', $s);
        $s = preg_replace('/\s+/u', '', $s);
        return trim($s);
    };

    $partner = $clean($partnerName);
    $desc = $clean($description);
    if (!$partner || !$desc) return 0;

    if (mb_strpos($desc, $partner) !== false) return 20;
    if (mb_strpos($partner, $desc) !== false && mb_strlen($desc) >= 2) return 18;

    $lcsLen = longestCommonSubstring($partner, $desc);
    $partnerLen = mb_strlen($partner);
    if ($lcsLen >= 2 && $partnerLen > 0) {
        $ratio = $lcsLen / $partnerLen;
        if ($ratio >= 0.8) return 18;
        if ($ratio >= 0.6) return 14;
        if ($ratio >= 0.4) return 10;
        if ($ratio >= 0.3 && $lcsLen >= 3) return 6;
    }

    return 0;
}

function longestCommonSubstring(string $a, string $b): int
{
    $aLen = mb_strlen($a);
    $bLen = mb_strlen($b);
    if ($aLen === 0 || $bLen === 0) return 0;

    $maxLen = 0;
    $prev = array_fill(0, $bLen + 1, 0);
    for ($i = 1; $i <= $aLen; $i++) {
        $curr = array_fill(0, $bLen + 1, 0);
        $aChar = mb_substr($a, $i - 1, 1);
        for ($j = 1; $j <= $bLen; $j++) {
            if ($aChar === mb_substr($b, $j - 1, 1)) {
                $curr[$j] = $prev[$j - 1] + 1;
                if ($curr[$j] > $maxLen) $maxLen = $curr[$j];
            }
        }
        $prev = $curr;
    }
    return $maxLen;
}

function detectAggregateMatches(PDO $pdo, array $invoices): array
{
    $hints = [];
    $byPartner = [];
    foreach ($invoices as $inv) {
        $bizno = $inv['invoice_type'] === '매출' ? $inv['buyer_bizno'] : $inv['supplier_bizno'];
        if ($bizno) $byPartner[$bizno][] = $inv;
    }

    foreach ($byPartner as $bizno => $group) {
        if (count($group) < 2) continue;
        $totalSum = array_sum(array_map(fn($i) => abs((int)$i['total_amount']), $group));
        $direction = $group[0]['invoice_type'] === '매출' ? '입금' : '출금';
        $dates = array_map(fn($i) => $i['issue_date'], $group);
        $dateMin = date('Y-m-d', strtotime(min($dates) . ' -30 days'));
        $dateMax = date('Y-m-d', strtotime(max($dates) . ' +30 days'));
        $margin = max(1, (int)($totalSum * 0.02));

        $txStmt = $pdo->prepare("
            SELECT id, amount, transaction_date, description FROM bank_transactions
            WHERE tx_type = ? AND transaction_date BETWEEN ? AND ?
              AND amount BETWEEN ? AND ?
              AND id NOT IN (SELECT transaction_id FROM invoice_bank_mappings WHERE is_confirmed = 1)
            LIMIT 3
        ");
        $txStmt->execute([$direction, $dateMin, $dateMax, $totalSum - $margin, $totalSum + $margin]);
        $matchingTxs = $txStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($matchingTxs)) {
            $partnerName = $group[0]['invoice_type'] === '매출'
                ? $group[0]['buyer_name'] : $group[0]['supplier_name'];
            $hints[] = [
                'type'          => 'potential_n1',
                'partner_name'  => $partnerName,
                'invoice_ids'   => array_map(fn($i) => $i['id'], $group),
                'invoice_count' => count($group),
                'sum_amount'    => $totalSum,
                'candidate_txs' => $matchingTxs,
            ];
        }
    }
    return $hints;
}

function buildMatchReason(array $inv, array $tx, string $partnerName, int $score): string
{
    $parts = [];
    $totalAmount  = abs((int)$inv['total_amount']);
    $supplyAmount = abs((int)$inv['supply_amount']);
    $txAmount     = (int)$tx['amount'];

    if ($txAmount === $totalAmount) $parts[] = '합계금액 일치';
    elseif ($txAmount === $supplyAmount) $parts[] = '공급가액 일치';
    else {
        $diff = $totalAmount > 0 ? abs($txAmount - $totalAmount) / $totalAmount : 1;
        if ($diff <= 0.05) $parts[] = '금액 유사(' . round($diff * 100, 1) . '% 차이)';
    }

    $daysDiff = (int)abs((strtotime($inv['issue_date']) - strtotime($tx['transaction_date'])) / 86400);
    if ($daysDiff === 0) $parts[] = '동일 날짜';
    elseif ($daysDiff <= 7) $parts[] = $daysDiff . '일 차이';

    $partnerBizno = $inv['invoice_type'] === '매출'
        ? ($inv['buyer_bizno'] ?? '') : ($inv['supplier_bizno'] ?? '');
    $ns = nameMatchScore($partnerName, $tx['description'], $partnerBizno);
    if ($ns >= 20) $parts[] = '거래처 일치';
    elseif ($ns >= 10) $parts[] = '거래처 유사';

    return implode(', ', $parts) . " ({$score}점)";
}

function getMatchCandidates(PDO $pdo): void
{
    $invoiceId = (int)($_GET['invoice_id'] ?? 0);
    if (!$invoiceId) respond(400, ['error' => 'invoice_id 필수']);

    $inv = $pdo->prepare("SELECT * FROM tax_invoices WHERE id = ?");
    $inv->execute([$invoiceId]);
    $invoice = $inv->fetch(PDO::FETCH_ASSOC);
    if (!$invoice) respond(404, ['error' => '세금계산서를 찾을 수 없습니다.']);

    $direction = $invoice['invoice_type'] === '매출' ? '입금' : '출금';
    $partnerName = $invoice['invoice_type'] === '매출' ? $invoice['buyer_name'] : $invoice['supplier_name'];
    $issueTs = strtotime($invoice['issue_date']);
    if (!$issueTs) respond(400, ['error' => '세금계산서 작성일자가 잘못되었습니다.']);
    $dateMin = date('Y-m-d', strtotime('-30 days', $issueTs));
    $dateMax = date('Y-m-d', strtotime('+30 days', $issueTs));

    $activePatterns = $pdo->query("SELECT * FROM match_patterns WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

    $txStmt = $pdo->prepare("
        SELECT bt.*,
               ibm.id as mapping_id, ibm.is_confirmed as mapping_confirmed
        FROM bank_transactions bt
        LEFT JOIN invoice_bank_mappings ibm ON ibm.transaction_id = bt.id AND ibm.is_confirmed = 1
        WHERE bt.tx_type = ?
          AND bt.transaction_date BETWEEN ? AND ?
        ORDER BY bt.transaction_date
    ");
    $txStmt->execute([$direction, $dateMin, $dateMax]);
    $candidates = $txStmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($candidates as $tx) {
        $score = calcMatchScore($invoice, $tx, $partnerName, $activePatterns);
        $tx['confidence'] = $score;
        $tx['match_reason'] = buildMatchReason($invoice, $tx, $partnerName, $score);
        $tx['already_confirmed'] = (bool)$tx['mapping_confirmed'];
        $partnerBizno = $invoice['invoice_type'] === '매출'
            ? ($invoice['buyer_bizno'] ?? '') : ($invoice['supplier_bizno'] ?? '');
        $ns = nameMatchScore($partnerName, $tx['description'], $partnerBizno);
        $tx['name_warning'] = ($ns < 20 && $ns > 0) ? 1 : 0;
        unset($tx['mapping_id'], $tx['mapping_confirmed']);
        $result[] = $tx;
    }

    usort($result, fn($a, $b) => $b['confidence'] - $a['confidence']);
    $result = array_slice($result, 0, 20);

    respond(200, ['invoice' => $invoice, 'candidates' => $result]);
}

function getMatchStatus(PDO $pdo): void
{
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo   = $_GET['date_to'] ?? '';
    $status   = $_GET['status'] ?? 'all';

    $allTablesExist = true;
    foreach (['invoice_bank_mappings', 'bank_transactions', 'bank_accounts'] as $tbl) {
        $chk = $pdo->prepare("SHOW TABLES LIKE ?");
        $chk->execute([$tbl]);
        if (!$chk->rowCount()) {
            $allTablesExist = false;
            break;
        }
    }

    if (!$allTablesExist) {
        $where = ["invoice_status != '취소'"];
        $params = [];
        if ($dateFrom) { $where[] = 'issue_date >= ?'; $params[] = $dateFrom; }
        if ($dateTo)   { $where[] = 'issue_date <= ?'; $params[] = $dateTo; }

        if (in_array($status, ['matched', 'confirmed', 'name_warning'])) {
            respond(200, ['items' => [], 'summary' => ['total' => 0, 'matched' => 0, 'confirmed' => 0, 'unmatched' => 0, 'name_warning' => 0]]);
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $pdo->prepare("SELECT * FROM tax_invoices WHERE {$whereClause} ORDER BY issue_date DESC, id DESC");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($rows);

        respond(200, [
            'items' => $rows,
            'summary' => ['total' => $total, 'matched' => 0, 'confirmed' => 0, 'unmatched' => $total, 'name_warning' => 0],
        ]);
    }

    $where = ["ti.invoice_status != '취소'"];
    $params = [];
    if ($dateFrom) { $where[] = 'ti.issue_date >= ?'; $params[] = $dateFrom; }
    if ($dateTo)   { $where[] = 'ti.issue_date <= ?'; $params[] = $dateTo; }

    if ($status === 'matched') {
        $where[] = 'ibm.id IS NOT NULL';
    } elseif ($status === 'unmatched') {
        $where[] = 'ibm.id IS NULL';
    } elseif ($status === 'confirmed') {
        $where[] = 'ibm.is_confirmed = 1';
    } elseif ($status === 'name_warning') {
        $where[] = 'ibm.name_warning = 1';
    }

    $whereClause = implode(' AND ', $where);

    $sql = "
        SELECT ti.*,
               ibm.id as mapping_id, ibm.transaction_id, ibm.match_type, ibm.confidence,
               ibm.match_reason, ibm.name_warning, ibm.aggregate_flag,
               ibm.is_confirmed, ibm.confirmed_at,
               bt.transaction_date as tx_date, bt.description as tx_desc,
               bt.amount as tx_amount, bt.tx_type, bt.balance as tx_balance,
               ba.bank_name, ba.account_alias
        FROM tax_invoices ti
        LEFT JOIN invoice_bank_mappings ibm ON ibm.invoice_id = ti.id
        LEFT JOIN bank_transactions bt ON bt.id = ibm.transaction_id
        LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
        WHERE {$whereClause}
        ORDER BY ti.issue_date DESC, ti.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sumParams = [];
    $sumWhere = ["ti.invoice_status != '취소'"];
    if ($dateFrom) { $sumWhere[] = 'ti.issue_date >= ?'; $sumParams[] = $dateFrom; }
    if ($dateTo)   { $sumWhere[] = 'ti.issue_date <= ?'; $sumParams[] = $dateTo; }
    $sumSql = "
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN ibm.id IS NOT NULL THEN 1 ELSE 0 END) as matched_ct,
            SUM(CASE WHEN ibm.is_confirmed = 1 THEN 1 ELSE 0 END) as confirmed_ct,
            SUM(CASE WHEN ibm.name_warning = 1 THEN 1 ELSE 0 END) as name_warning_ct
        FROM tax_invoices ti
        LEFT JOIN invoice_bank_mappings ibm ON ibm.invoice_id = ti.id
        WHERE " . implode(' AND ', $sumWhere) . "
    ";
    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute($sumParams);
    $sum = $sumStmt->fetch(PDO::FETCH_ASSOC);

    $total     = (int)$sum['total'];
    $matchedCt = (int)$sum['matched_ct'];
    $confirmedCt = (int)$sum['confirmed_ct'];

    respond(200, [
        'items' => $rows,
        'summary' => [
            'total'        => $total,
            'matched'      => $matchedCt,
            'confirmed'    => $confirmedCt,
            'unmatched'    => $total - $matchedCt,
            'name_warning' => (int)$sum['name_warning_ct'],
        ],
    ]);
}

function saveMatch(PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];

    $invoiceId     = (int)($input['invoice_id'] ?? 0);
    $transactionId = (int)($input['transaction_id'] ?? 0);
    $matchType     = in_array($input['match_type'] ?? '', ['auto', 'manual']) ? $input['match_type'] : 'manual';
    $confidence    = (int)($input['confidence'] ?? 0);

    if (!$invoiceId || !$transactionId) respond(400, ['error' => 'invoice_id, transaction_id 필수']);

    $dupCheck = $pdo->prepare("
        SELECT ibm.id, ti.invoice_number
        FROM invoice_bank_mappings ibm
        JOIN tax_invoices ti ON ti.id = ibm.invoice_id
        WHERE ibm.transaction_id = ? AND ibm.is_confirmed = 1 AND ibm.invoice_id != ?
    ");
    $dupCheck->execute([$transactionId, $invoiceId]);
    $dup = $dupCheck->fetch(PDO::FETCH_ASSOC);
    if ($dup) {
        respond(400, ['error' => "이 통장 거래는 이미 세금계산서({$dup['invoice_number']})에 확정 매핑되어 있습니다."]);
    }

    $inv = $pdo->prepare("SELECT * FROM tax_invoices WHERE id = ?");
    $inv->execute([$invoiceId]);
    $invoice = $inv->fetch(PDO::FETCH_ASSOC);
    $tx = $pdo->prepare("SELECT * FROM bank_transactions WHERE id = ?");
    $tx->execute([$transactionId]);
    $transaction = $tx->fetch(PDO::FETCH_ASSOC);

    $nameWarning = 0;
    $reason = '수동 매칭';
    if ($invoice && $transaction) {
        $partnerName = $invoice['invoice_type'] === '매출' ? $invoice['buyer_name'] : $invoice['supplier_name'];
        $partnerBizno = $invoice['invoice_type'] === '매출'
            ? ($invoice['buyer_bizno'] ?? '') : ($invoice['supplier_bizno'] ?? '');
        $ns = nameMatchScore($partnerName, $transaction['description'], $partnerBizno);
        $nameWarning = ($ns < 20 && $ns > 0) ? 1 : 0;
        if ($nameWarning) $reason = '수동 매칭 [이름 유사]';
    }

    $stmt = $pdo->prepare("
        INSERT INTO invoice_bank_mappings (invoice_id, transaction_id, match_type, confidence, match_reason, name_warning)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            match_type   = IF(is_confirmed = 0, VALUES(match_type), match_type),
            confidence   = IF(is_confirmed = 0, VALUES(confidence), confidence),
            match_reason = IF(is_confirmed = 0, VALUES(match_reason), match_reason),
            name_warning = IF(is_confirmed = 0, VALUES(name_warning), name_warning)
    ");
    $stmt->execute([$invoiceId, $transactionId, $matchType, $confidence, $reason, $nameWarning]);

    $histStmt = $pdo->prepare("
        INSERT INTO match_history (invoice_id, transaction_id, action, actor, memo)
        VALUES (?, ?, 'manual_match', 'user', ?)
    ");
    $histStmt->execute([$invoiceId, $transactionId, $reason]);

    respond(200, ['success' => true, 'message' => '매칭이 저장되었습니다.', 'name_warning' => $nameWarning]);
}

function removeMatch(PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];

    $mappingId = (int)($input['mapping_id'] ?? 0);
    if (!$mappingId) respond(400, ['error' => 'mapping_id 필수']);

    $mapInfo = $pdo->prepare("SELECT invoice_id, transaction_id FROM invoice_bank_mappings WHERE id = ?");
    $mapInfo->execute([$mappingId]);
    $mapping = $mapInfo->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("DELETE FROM invoice_bank_mappings WHERE id = ? AND is_confirmed = 0");
    $stmt->execute([$mappingId]);

    if ($stmt->rowCount() > 0) {
        if ($mapping) {
            $histStmt = $pdo->prepare("
                INSERT INTO match_history (invoice_id, transaction_id, action, actor, memo)
                VALUES (?, ?, 'remove', 'user', '매칭 해제')
            ");
            $histStmt->execute([$mapping['invoice_id'], $mapping['transaction_id']]);
        }
        respond(200, ['success' => true, 'message' => '매칭이 해제되었습니다.']);
    } else {
        respond(400, ['error' => '이미 확정된 매칭은 해제할 수 없습니다.']);
    }
}

function unconfirmMatch(PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];

    $mappingIds = $input['mapping_ids'] ?? [];
    if (empty($mappingIds) || !is_array($mappingIds)) {
        respond(400, ['error' => '해제할 매칭을 선택해주세요.']);
    }
    $MAX_UNCONFIRM_BATCH = 200;
    if (count($mappingIds) > $MAX_UNCONFIRM_BATCH) {
        respond(400, ['error' => "한 번에 최대 {$MAX_UNCONFIRM_BATCH}건까지 해제 가능합니다."]);
    }

    $placeholders = implode(',', array_fill(0, count($mappingIds), '?'));
    $intIds = array_map('intval', $mappingIds);

    $mapStmt = $pdo->prepare("SELECT id, invoice_id, transaction_id FROM invoice_bank_mappings WHERE id IN ({$placeholders}) AND is_confirmed = 1");
    $mapStmt->execute($intIds);
    $mappings = $mapStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($mappings)) {
        respond(400, ['error' => '해제 가능한 확정 매칭이 없습니다.']);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE invoice_bank_mappings SET is_confirmed = 0, confirmed_at = NULL WHERE id IN ({$placeholders}) AND is_confirmed = 1");
        $stmt->execute($intIds);
        $unconfirmedCount = $stmt->rowCount();

        $histStmt = $pdo->prepare("
            INSERT INTO match_history (mapping_id, invoice_id, transaction_id, action, actor, memo)
            VALUES (?, ?, ?, 'unconfirm', 'user', '확정 해제')
        ");
        foreach ($mappings as $m) {
            $histStmt->execute([$m['id'], $m['invoice_id'], $m['transaction_id']]);
        }
        $pdo->commit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        error_log("unconfirmMatch error: " . $e->getMessage());
        respond(500, ['error' => '확정 해제 처리 중 오류가 발생했습니다.']);
    }

    respond(200, [
        'success' => true,
        'message' => "{$unconfirmedCount}건의 매칭 확정이 해제되었습니다.",
        'unconfirmed_count' => $unconfirmedCount
    ]);
}

function confirmMatches(PDO $pdo): void
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];

    $mappingIds = $input['mapping_ids'] ?? [];
    if (empty($mappingIds) || !is_array($mappingIds)) {
        respond(400, ['error' => '확정할 매칭을 선택해주세요.']);
    }
    $MAX_CONFIRM_BATCH = 200;
    if (count($mappingIds) > $MAX_CONFIRM_BATCH) {
        respond(400, ['error' => "한 번에 최대 {$MAX_CONFIRM_BATCH}건까지 확정 가능합니다."]);
    }

    $placeholders = implode(',', array_fill(0, count($mappingIds), '?'));
    $intIds = array_map('intval', $mappingIds);

    $pdo->beginTransaction();
    try {
        $mapStmt = $pdo->prepare("SELECT id, invoice_id, transaction_id FROM invoice_bank_mappings WHERE id IN ({$placeholders}) AND is_confirmed = 0");
        $mapStmt->execute($intIds);
        $mappings = $mapStmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE invoice_bank_mappings SET is_confirmed = 1, confirmed_at = NOW() WHERE id IN ({$placeholders}) AND is_confirmed = 0");
        $stmt->execute($intIds);
        $confirmedCount = $stmt->rowCount();

        if ($confirmedCount > 0) {
            $histStmt = $pdo->prepare("
                INSERT INTO match_history (mapping_id, invoice_id, transaction_id, action, actor, memo)
                VALUES (?, ?, ?, 'confirm', 'user', '매칭 확정')
            ");
            foreach ($mappings as $m) {
                $histStmt->execute([$m['id'], $m['invoice_id'], $m['transaction_id']]);
            }

            learnPatternsFromConfirmed($pdo, $mappings);
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('confirmMatches 트랜잭션 실패: ' . $e->getMessage());
        respond(500, ['error' => '매칭 확정 처리 중 오류가 발생했습니다.']);
    }

    respond(200, [
        'success'   => true,
        'confirmed' => $confirmedCount,
        'message'   => $confirmedCount . '건이 확정되었습니다.',
    ]);
}

/**
 * 확정된 매칭에서 패턴을 추출하여 match_patterns에 저장/갱신
 */
function learnPatternsFromConfirmed(PDO $pdo, array $mappings): void
{
    $invStmt = $pdo->prepare("SELECT * FROM tax_invoices WHERE id = ?");
    $txStmt  = $pdo->prepare("SELECT * FROM bank_transactions WHERE id = ?");

    $findStmt = $pdo->prepare("
        SELECT id FROM match_patterns
        WHERE partner_bizno = ? AND pattern_type = ?
        LIMIT 1
    ");
    $insertStmt = $pdo->prepare("
        INSERT INTO match_patterns (partner_name, partner_bizno, pattern_type, pattern_rule, confidence, hit_count, source, last_matched_at)
        VALUES (?, ?, ?, ?, 70, 1, 'user', NOW())
    ");
    $updateStmt = $pdo->prepare("
        UPDATE match_patterns
        SET hit_count = hit_count + 1,
            confidence = LEAST(99, confidence + 2),
            last_matched_at = NOW(),
            previous_rules = CASE
                WHEN previous_rules IS NULL THEN JSON_ARRAY(pattern_rule)
                ELSE JSON_ARRAY_APPEND(previous_rules, '$', pattern_rule)
            END,
            pattern_rule = ?
        WHERE id = ?
    ");

    foreach ($mappings as $m) {
        $invStmt->execute([$m['invoice_id']]);
        $inv = $invStmt->fetch(PDO::FETCH_ASSOC);
        if (!$inv) continue;

        $txStmt->execute([$m['transaction_id']]);
        $tx = $txStmt->fetch(PDO::FETCH_ASSOC);
        if (!$tx) continue;

        $partnerName  = $inv['invoice_type'] === '매출' ? $inv['buyer_name'] : $inv['supplier_name'];
        $partnerBizno = $inv['invoice_type'] === '매출' ? $inv['buyer_bizno'] : $inv['supplier_bizno'];
        $totalAmount  = abs((int)$inv['total_amount']);
        $supplyAmount = abs((int)$inv['supply_amount']);
        $txAmount     = (int)$tx['amount'];

        if ($txAmount === $totalAmount && $totalAmount > 0) {
            $patternType = 'amount_exact';
            $rule = json_encode(['amount' => $totalAmount, 'field' => 'total_amount'], JSON_UNESCAPED_UNICODE);
        } elseif ($txAmount === $supplyAmount && $supplyAmount > 0) {
            $patternType = 'amount_exact';
            $rule = json_encode(['amount' => $supplyAmount, 'field' => 'supply_amount'], JSON_UNESCAPED_UNICODE);
        } else {
            $invTs = strtotime($inv['issue_date']);
            $txTs  = strtotime($tx['transaction_date']);
            $daysDiff = ($invTs && $txTs) ? (int)round(($txTs - $invTs) / 86400) : 0;
            $patternType = 'date_offset';
            $rule = json_encode(['offset_days' => $daysDiff, 'approx_amount' => $txAmount], JSON_UNESCAPED_UNICODE);
        }

        $findStmt->execute([$partnerBizno, $patternType]);
        $existingId = $findStmt->fetchColumn();

        if ($existingId) {
            $updateStmt->execute([$rule, $existingId]);
        } else {
            $insertStmt->execute([$partnerName, $partnerBizno, $patternType, $rule]);
        }
    }
}

// =============================================================
// 패턴 관리 + 매칭 이력
// =============================================================

function getPatterns(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    $partner = $_GET['partner'] ?? '';
    if ($partner) {
        $where[] = 'partner_name LIKE ?';
        $params[] = "%{$partner}%";
    }
    $ALLOWED_PATTERN_TYPES = ['amount_exact', 'date_offset', 'description_keyword', 'aggregate'];
    $type = $_GET['pattern_type'] ?? '';
    if ($type && in_array($type, $ALLOWED_PATTERN_TYPES)) {
        $where[] = 'pattern_type = ?';
        $params[] = $type;
    }
    // 적용 방식 필터: user=확정(사람이 정함), recommend=추천(rule/ai 통합)
    $source = $_GET['source'] ?? '';
    if ($source === 'user') {
        $where[] = 'source = ?';
        $params[] = 'user';
    } elseif ($source === 'recommend') {
        $where[] = "source IN ('rule', 'ai')";
    }

    $sql = 'SELECT * FROM match_patterns WHERE ' . implode(' AND ', $where) . ' ORDER BY updated_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sumStmt = $pdo->query("
        SELECT
            COUNT(*) as total,
            SUM(is_active) as active,
            ROUND(AVG(confidence), 1) as avg_confidence
        FROM match_patterns
    ");
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    $histCnt = (int)$pdo->query("SELECT COUNT(*) FROM match_history")->fetchColumn();
    $summary['history_count'] = $histCnt;

    respond(200, ['patterns' => $patterns, 'summary' => $summary]);
}

function togglePattern(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    $active = (int)($input['is_active'] ?? 0);
    if (!$id) respond(400, ['error' => 'id 필수']);

    $stmt = $pdo->prepare("UPDATE match_patterns SET is_active = ? WHERE id = ?");
    $stmt->execute([$active, $id]);
    respond(200, ['success' => true]);
}

function deletePattern(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    if (!$id) respond(400, ['error' => 'id 필수']);

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE match_history SET pattern_id = NULL, memo = CONCAT(IFNULL(memo,''), ' [패턴 삭제됨]') WHERE pattern_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM match_patterns WHERE id = ?");
        $stmt->execute([$id]);
        $deleted = $stmt->rowCount();

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log("deletePattern error: " . $e->getMessage());
        respond(500, ['error' => '패턴 삭제 실패']);
    }
    respond(200, ['success' => true, 'deleted' => $deleted]);
}

function updatePattern(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    if (!$id) respond(400, ['error' => 'id 필수']);

    $stmt = $pdo->prepare("SELECT * FROM match_patterns WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) respond(404, ['error' => '패턴을 찾을 수 없습니다']);

    $ALLOWED_TYPES = ['amount_exact', 'date_offset', 'description_keyword', 'aggregate'];

    $partnerName = trim($input['partner_name'] ?? $old['partner_name']);
    $partnerBizno = trim($input['partner_bizno'] ?? $old['partner_bizno'] ?? '');
    $patternType = $input['pattern_type'] ?? $old['pattern_type'];
    if (!in_array($patternType, $ALLOWED_TYPES)) respond(400, ['error' => '유효하지 않은 패턴 유형']);

    $patternRule = $input['pattern_rule'] ?? null;
    if ($patternRule !== null) {
        if (is_string($patternRule)) {
            $decoded = json_decode($patternRule, true);
            if ($decoded === null) respond(400, ['error' => '패턴 규칙이 유효한 JSON이 아닙니다']);
            $patternRule = $decoded;
        }
        $ruleJson = json_encode($patternRule, JSON_UNESCAPED_UNICODE);
    } else {
        $ruleJson = $old['pattern_rule'];
    }

    $confidence = isset($input['confidence']) ? max(0, min(100, (float)$input['confidence'])) : (float)$old['confidence'];
    $source = $input['source'] ?? $old['source'];
    if (!in_array($source, ['rule', 'user', 'ai'])) $source = $old['source'];

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare("
            UPDATE match_patterns
            SET partner_name = ?, partner_bizno = ?, pattern_type = ?,
                pattern_rule = ?, confidence = ?, source = ?
            WHERE id = ?
        ");
        $upd->execute([$partnerName, $partnerBizno, $patternType, $ruleJson, $confidence, $source, $id]);

        $changes = [];
        if ($partnerName !== $old['partner_name']) $changes[] = "거래처:{$old['partner_name']}→{$partnerName}";
        if ($patternType !== $old['pattern_type']) $changes[] = "유형:{$old['pattern_type']}→{$patternType}";
        if (abs($confidence - (float)$old['confidence']) > 0.01) $changes[] = "신뢰도:{$old['confidence']}→{$confidence}";
        if ($ruleJson !== $old['pattern_rule']) $changes[] = "규칙 변경";
        if ($source !== $old['source']) $changes[] = "출처:{$old['source']}→{$source}";

        $memo = $changes ? implode(', ', $changes) : '변경 없음';

        $hist = $pdo->prepare("
            INSERT INTO match_history (mapping_id, invoice_id, transaction_id, action, pattern_id, actor, memo)
            VALUES (NULL, NULL, NULL, 'pattern_edit', ?, 'user', ?)
        ");
        $hist->execute([$id, $memo]);

        $pdo->commit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        error_log("updatePattern error: " . $e->getMessage());
        respond(500, ['error' => '패턴 수정 실패']);
    }

    respond(200, ['success' => true]);
}

function getPatternHistory(PDO $pdo): void
{
    $patternId = (int)($_GET['pattern_id'] ?? 0);
    if (!$patternId) respond(400, ['error' => 'pattern_id 필수']);

    $pat = $pdo->prepare("SELECT partner_bizno FROM match_patterns WHERE id = ?");
    $pat->execute([$patternId]);
    $pattern = $pat->fetch(PDO::FETCH_ASSOC);
    if (!$pattern) respond(404, ['error' => '패턴 없음']);

    $bizno = $pattern['partner_bizno'] ?? '';

    $stmt = $pdo->prepare("
        SELECT mh.*,
               ti.invoice_number, ti.issue_date, ti.total_amount, ti.supply_amount, ti.invoice_type,
               CASE WHEN ti.invoice_type = '매출' THEN ti.buyer_name ELSE ti.supplier_name END as partner_name,
               bt.transaction_date as tx_date, bt.description as tx_desc, bt.amount as tx_amount,
               ba.bank_name, ba.account_no
        FROM match_history mh
        LEFT JOIN tax_invoices ti ON ti.id = mh.invoice_id
        LEFT JOIN bank_transactions bt ON bt.id = mh.transaction_id
        LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
        WHERE mh.pattern_id = ?
           OR (mh.pattern_id IS NULL AND ? != '' AND mh.invoice_id IN (
               SELECT id FROM tax_invoices
               WHERE supplier_bizno = ? OR buyer_bizno = ?
           ))
        ORDER BY mh.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$patternId, $bizno, $bizno, $bizno]);
    respond(200, ['history' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getMatchHistory(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    $ALLOWED_HISTORY_ACTIONS = ['confirm', 'modify', 'remove', 'auto_match', 'manual_match', 'pattern_edit', 'unconfirm'];
    $filterAction = $_GET['filter_action'] ?? '';
    if ($filterAction && in_array($filterAction, $ALLOWED_HISTORY_ACTIONS)) {
        $where[] = 'mh.action = ?';
        $params[] = $filterAction;
    }

    $dateFrom = $_GET['date_from'] ?? '';
    if ($dateFrom && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'mh.created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }

    $dateTo = $_GET['date_to'] ?? '';
    if ($dateTo && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'mh.created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }

    $stmt = $pdo->prepare("
        SELECT mh.*,
               ti.total_amount,
               CASE WHEN ti.invoice_type = '매출' THEN ti.buyer_name ELSE ti.supplier_name END as partner_name,
               bt.transaction_date as tx_date, bt.description as tx_desc, bt.amount as tx_amount
        FROM match_history mh
        LEFT JOIN tax_invoices ti ON ti.id = mh.invoice_id
        LEFT JOIN bank_transactions bt ON bt.id = mh.transaction_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY mh.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    respond(200, ['history' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
