<?php
/**
 * 결산 탭 · 간이 손익 + 계좌별 집계 + 기간 확장 + 마감 + CSV + 체크리스트
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/accounting_helpers.php';
$pdo = getDBConnection();

// --- 기간 파라미터 ---
$periodType = $_GET['pt'] ?? 'month'; // month, quarter, half, year
$year  = isset($_GET['cy']) ? (int)$_GET['cy'] : (int)date('Y');
$month = isset($_GET['cm']) ? (int)$_GET['cm'] : (int)date('m');

switch ($periodType) {
    case 'quarter':
        $q = isset($_GET['cq']) ? (int)$_GET['cq'] : (int)ceil(date('n') / 3);
        $startMonth = ($q - 1) * 3 + 1;
        $endMonth   = $q * 3;
        $periodLabel = "{$year}년 {$q}분기";
        break;
    case 'half':
        $h = isset($_GET['ch']) ? (int)$_GET['ch'] : (date('n') <= 6 ? 1 : 2);
        $startMonth = $h === 1 ? 1 : 7;
        $endMonth   = $h === 1 ? 6 : 12;
        $periodLabel = "{$year}년 " . ($h === 1 ? '상반기' : '하반기');
        break;
    case 'year':
        $startMonth = 1;
        $endMonth   = 12;
        $periodLabel = "{$year}년 연간";
        break;
    default:
        $startMonth = $month;
        $endMonth   = $month;
        $periodLabel = "{$year}년 {$month}월";
}

$dateFrom = sprintf('%04d-%02d-01', $year, $startMonth);
$dateTo   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));

// 전기간 (비교용) · 같은 길이의 바로 이전 기간
$monthSpan = $endMonth - $startMonth + 1;
if ($periodType === 'year') {
    $prevFrom = sprintf('%04d-01-01', $year - 1);
    $prevTo   = sprintf('%04d-12-31', $year - 1);
} else {
    $prevEndMonth   = $startMonth - 1;
    $prevEndYear    = $year;
    if ($prevEndMonth < 1) { $prevEndMonth = 12; $prevEndYear--; }
    $prevStartMonth = $prevEndMonth - $monthSpan + 1;
    $prevStartYear  = $prevEndYear;
    if ($prevStartMonth < 1) { $prevStartMonth += 12; $prevStartYear--; }
    $prevFrom = sprintf('%04d-%02d-01', $prevStartYear, $prevStartMonth);
    $prevTo   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $prevEndYear, $prevEndMonth)));
}

// 전년 동기간
$yoyFrom = sprintf('%04d-%02d-01', $year - 1, $startMonth);
$yoyTo   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year - 1, $endMonth)));

// --- 마감 상태 확인 ---
$closedMonths = [];
try {
    $stmt = $pdo->query("SELECT year, month, closed_at FROM closing_locks WHERE is_locked = 1");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $closedMonths[$r['year'] . '-' . $r['month']] = $r['closed_at'];
    }
} catch (Throwable $e) {
    error_log('closing: lock status query failed: ' . $e->getMessage());
}

$lockedCount = 0;
$periodMonthCount = $endMonth - $startMonth + 1;
for ($m = $startMonth; $m <= $endMonth; $m++) {
    if (isset($closedMonths[$year . '-' . $m])) {
        $lockedCount++;
    }
}
$isCurrentPeriodLocked = ($lockedCount === $periodMonthCount);
$isPartiallyLocked     = ($lockedCount > 0 && $lockedCount < $periodMonthCount);

// --- 계좌 목록 ---
$accounts = [];
try {
    $accounts = $pdo->query("
        SELECT id, bank_name, account_no, account_alias, account_type, is_active
        FROM bank_accounts ORDER BY sort_order, id
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('closing: accounts query failed: ' . $e->getMessage());
}

// --- 당기 계좌별 집계 ---
$acctSummary = [];
try {
    $stmt = $pdo->prepare("
        SELECT account_id,
               SUM(CASE WHEN tx_type='입금' THEN amount ELSE 0 END) as deposit,
               SUM(CASE WHEN tx_type='출금' THEN amount ELSE 0 END) as withdraw,
               COUNT(*) as tx_count,
               SUM(CASE WHEN account_code IS NULL OR account_code = '' THEN 1 ELSE 0 END) as unclassified
        FROM bank_transactions
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY account_id
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $acctSummary[$row['account_id']] = $row;
    }
} catch (Throwable $e) {
    error_log('closing: acct summary query failed: ' . $e->getMessage());
}

// --- 전기간 계좌별 ---
$prevSummary = [];
try {
    $stmt = $pdo->prepare("
        SELECT account_id,
               SUM(CASE WHEN tx_type='입금' THEN amount ELSE 0 END) as deposit,
               SUM(CASE WHEN tx_type='출금' THEN amount ELSE 0 END) as withdraw
        FROM bank_transactions
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY account_id
    ");
    $stmt->execute([$prevFrom, $prevTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $prevSummary[$row['account_id']] = $row;
    }
} catch (Throwable $e) {
    error_log('closing: prev summary query failed: ' . $e->getMessage());
}

// --- 전년 동기간 계좌별 ---
$yoySummary = [];
try {
    $stmt = $pdo->prepare("
        SELECT account_id,
               SUM(CASE WHEN tx_type='입금' THEN amount ELSE 0 END) as deposit,
               SUM(CASE WHEN tx_type='출금' THEN amount ELSE 0 END) as withdraw
        FROM bank_transactions
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY account_id
    ");
    $stmt->execute([$yoyFrom, $yoyTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $yoySummary[$row['account_id']] = $row;
    }
} catch (Throwable $e) {
    error_log('closing: yoy summary query failed: ' . $e->getMessage());
}

// --- 간이 손익계산서 데이터 (parent_code 계층 구조) ---
$plData = ['매출' => 0, '매입' => 0, '수익' => 0, '비용' => 0];
$plPrev = ['매출' => 0, '매입' => 0, '수익' => 0, '비용' => 0];
$plYoy  = ['매출' => 0, '매입' => 0, '수익' => 0, '비용' => 0];
$plDetail = [];
$plPrevDetail = [];
$plYoyDetail = [];
$plGroups = [];

try {
    $unifiedFrom = getUnifiedTransactionSQL($pdo, 'bt');
    $stmt = $pdo->prepare("
        SELECT ac.type, ac.name as cat_name, ac.code as cat_code,
               ac.parent_code, pg.name as group_name,
               COALESCE(pg.sort_order, ac.sort_order) as group_sort,
               SUM(bt.amount) as total, COUNT(*) as cnt
        FROM {$unifiedFrom}
        JOIN account_categories ac ON bt.account_code = ac.code
        LEFT JOIN account_categories pg ON ac.parent_code = pg.code
        WHERE bt.transaction_date BETWEEN ? AND ?
          AND ac.code NOT LIKE 'G\\_%' ESCAPE '\\\\'
          AND (
            (ac.type IN ('매출','수익') AND bt.tx_type = '입금')
            OR (ac.type IN ('매입','비용') AND bt.tx_type = '출금')
          )
        GROUP BY ac.type, ac.name, ac.code, ac.parent_code, pg.name, group_sort
        ORDER BY group_sort, ac.sort_order
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $t = $r['type'];
        if (isset($plData[$t])) $plData[$t] += (int)$r['total'];
        $plDetail[$r['cat_code']] = (int)$r['total'];
        $gKey = $r['parent_code'] ?: '_' . $t;
        if (!isset($plGroups[$gKey])) {
            $plGroups[$gKey] = [
                'name' => $r['group_name'] ?: $t,
                'type' => $t,
                'sort' => (int)$r['group_sort'],
                'items' => [],
            ];
        }
        $plGroups[$gKey]['items'][$r['cat_code']] = [
            'name' => $r['cat_name'],
            'cur' => (int)$r['total'],
            'prev' => 0,
            'yoy' => 0,
        ];
    }

    // 전기간
    $stmt->execute([$prevFrom, $prevTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $t = $r['type'];
        if (isset($plPrev[$t])) $plPrev[$t] += (int)$r['total'];
        $plPrevDetail[$r['cat_code']] = (int)$r['total'];
        $gKey = $r['parent_code'] ?: '_' . $t;
        if (isset($plGroups[$gKey]['items'][$r['cat_code']])) {
            $plGroups[$gKey]['items'][$r['cat_code']]['prev'] = (int)$r['total'];
        } else {
            if (!isset($plGroups[$gKey])) {
                $plGroups[$gKey] = [
                    'name' => $r['group_name'] ?: $t,
                    'type' => $t,
                    'sort' => (int)$r['group_sort'],
                    'items' => [],
                ];
            }
            $plGroups[$gKey]['items'][$r['cat_code']] = [
                'name' => $r['cat_name'],
                'cur' => 0,
                'prev' => (int)$r['total'],
                'yoy' => 0,
            ];
        }
    }

    // 전년 동기간
    $stmt->execute([$yoyFrom, $yoyTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $t = $r['type'];
        if (isset($plYoy[$t])) $plYoy[$t] += (int)$r['total'];
        $gKey = $r['parent_code'] ?: '_' . $t;
        if (isset($plGroups[$gKey]['items'][$r['cat_code']])) {
            $plGroups[$gKey]['items'][$r['cat_code']]['yoy'] = (int)$r['total'];
        }
    }
} catch (Throwable $e) {
    error_log('closing: P&L query failed: ' . $e->getMessage());
}

// 그룹 정렬
uasort($plGroups, fn($a, $b) => $a['sort'] - $b['sort']);

// 손익 계산
$revenue      = $plData['매출'] + $plData['수익'];
$costOfSales  = $plData['매입'];
$grossProfit  = $revenue - $costOfSales;
$expenses     = $plData['비용'];
$netIncome    = $grossProfit - $expenses;

$prevRevenue  = $plPrev['매출'] + $plPrev['수익'];
$prevExpenses = $plPrev['매입'] + $plPrev['비용'];
$prevNet      = $prevRevenue - $prevExpenses;

$yoyRevenue   = $plYoy['매출'] + $plYoy['수익'];
$yoyExpenses  = $plYoy['매입'] + $plYoy['비용'];
$yoyNet       = $yoyRevenue - $yoyExpenses;

// 수익/비용 그룹 분리
$revenueGroups = array_filter($plGroups, fn($g) => in_array($g['type'], ['매출', '수익']));
$costGroups = array_filter($plGroups, fn($g) => in_array($g['type'], ['매입', '비용']));

// --- 월별 손익 추이 (라인차트용) · 당해 + 전년 1~12월 ---
$monthlyTrend = array_fill(1, 12, ['rev' => 0, 'cost' => 0, 'net' => 0]);
$monthlyTrendPrev = array_fill(1, 12, ['net' => 0]);
try {
    $trendUnified = getUnifiedTransactionSQL($pdo, 'bt');
    $trendSql = "
        SELECT MONTH(bt.transaction_date) AS m, ac.type AS t, SUM(bt.amount) AS total
        FROM {$trendUnified}
        JOIN account_categories ac ON bt.account_code = ac.code
        WHERE YEAR(bt.transaction_date) = ?
          AND ac.code NOT LIKE 'G\\_%' ESCAPE '\\\\'
          AND (
            (ac.type IN ('매출','수익') AND bt.tx_type = '입금')
            OR (ac.type IN ('매입','비용') AND bt.tx_type = '출금')
          )
        GROUP BY m, t
    ";
    $stmtTrend = $pdo->prepare($trendSql);

    $stmtTrend->execute([$year]);
    foreach ($stmtTrend->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $m = (int)$r['m']; $v = (int)$r['total'];
        if ($m < 1 || $m > 12) continue;
        if (in_array($r['t'], ['매출', '수익'])) $monthlyTrend[$m]['rev'] += $v;
        else $monthlyTrend[$m]['cost'] += $v;
    }
    foreach ($monthlyTrend as $m => &$d) { $d['net'] = $d['rev'] - $d['cost']; }
    unset($d);

    $stmtTrend->execute([$year - 1]);
    foreach ($stmtTrend->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $m = (int)$r['m']; $v = (int)$r['total'];
        if ($m < 1 || $m > 12) continue;
        if (in_array($r['t'], ['매출', '수익'])) $monthlyTrendPrev[$m]['net'] += $v;
        else $monthlyTrendPrev[$m]['net'] -= $v;
    }
} catch (Throwable $e) {
    error_log('closing: monthly trend query failed: ' . $e->getMessage());
}

// --- 계정과목별 집계 (카테고리 전월 대비 포함) ---
$categoryBreakdown = [];
try {
    $catUnified = getUnifiedTransactionSQL($pdo, 'bt');
    $stmt = $pdo->prepare("
        SELECT COALESCE(bt.account_name, '미분류') as category,
               bt.account_code,
               bt.tx_type,
               SUM(bt.amount) as total,
               COUNT(*) as cnt
        FROM {$catUnified}
        WHERE bt.transaction_date BETWEEN ? AND ?
        GROUP BY bt.account_name, bt.account_code, bt.tx_type
        ORDER BY total DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $categoryBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('closing: category breakdown query failed: ' . $e->getMessage());
}

// --- 전기간 계정과목별 집계 (입출금 계정과목별 전기 비교용) ---
// 주의: 손익 전용 $plPrevDetail 가 아니라, 모든 계정(자산/부채 포함)을 account_code+tx_type 으로 집계해야
//       외상매출금·미지급금·가수금 같은 비손익 계정의 전기 비교가 0 으로 깨지지 않는다.
$categoryPrevMap = [];
try {
    $catPrevUnified = getUnifiedTransactionSQL($pdo, 'bt');
    $stmtPrev = $pdo->prepare("
        SELECT bt.account_code, bt.tx_type, SUM(bt.amount) as total
        FROM {$catPrevUnified}
        WHERE bt.transaction_date BETWEEN ? AND ?
        GROUP BY bt.account_code, bt.tx_type
    ");
    $stmtPrev->execute([$prevFrom, $prevTo]);
    foreach ($stmtPrev->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $categoryPrevMap[($row['account_code'] ?? '') . '|' . $row['tx_type']] = (int)$row['total'];
    }
} catch (Throwable $e) {
    error_log('closing: category prev breakdown query failed: ' . $e->getMessage());
}
$hasPrevCategoryData = !empty($categoryPrevMap);
$prevColLabel = ['month' => '전월', 'quarter' => '전분기', 'half' => '전반기', 'year' => '전년'][$periodType] ?? '전기';

// 전체 합산
$totalDeposit  = array_sum(array_column($acctSummary, 'deposit'));
$totalWithdraw = array_sum(array_column($acctSummary, 'withdraw'));
$totalTxCount  = array_sum(array_column($acctSummary, 'tx_count'));
$totalUnclass  = array_sum(array_column($acctSummary, 'unclassified'));
$prevTotalDep  = array_sum(array_column($prevSummary, 'deposit'));
$prevTotalWd   = array_sum(array_column($prevSummary, 'withdraw'));
$yoyTotalDep   = array_sum(array_column($yoySummary, 'deposit'));
$yoyTotalWd    = array_sum(array_column($yoySummary, 'withdraw'));
$netFlow       = $totalDeposit - $totalWithdraw;

$catDeposit  = array_filter($categoryBreakdown, fn($r) => $r['tx_type'] === '입금');
$catWithdraw = array_filter($categoryBreakdown, fn($r) => $r['tx_type'] === '출금');

// 퍼센트 변동 헬퍼
if (!function_exists('pctChange')) {
    function pctChange(int $cur, int $prev): ?float {
        return $prev != 0 ? round(($cur - $prev) / abs($prev) * 100, 1) : null;
    }
}

$depChange  = pctChange($totalDeposit, $prevTotalDep);
$wdChange   = pctChange($totalWithdraw, $prevTotalWd);
$yoyDepChg  = pctChange($totalDeposit, $yoyTotalDep);
$yoyWdChg   = pctChange($totalWithdraw, $yoyTotalWd);
$classRate  = $totalTxCount > 0 ? round(($totalTxCount - $totalUnclass) / $totalTxCount * 100, 1) : 0;

// 세금계산서 매칭 상태
$invoiceMatchRate = null;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ti.id) as total,
               COUNT(DISTINCT m.invoice_id) as matched
        FROM tax_invoices ti
        LEFT JOIN invoice_bank_mappings m ON m.invoice_id = ti.id AND m.is_confirmed = 1
        WHERE ti.issue_date BETWEEN ? AND ? AND ti.invoice_status != '취소'
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inv && (int)$inv['total'] > 0) {
        $invoiceMatchRate = round((int)$inv['matched'] / (int)$inv['total'] * 100, 1);
    }
} catch (Throwable $e) {
    error_log('closing: invoice match rate query failed: ' . $e->getMessage());
}

// ===== 세금계산서 기준 매출/매입/부가세 =====
$vatSales = ['supply' => 0, 'tax' => 0, 'total' => 0, 'count' => 0];
$vatPurchase = ['supply' => 0, 'tax' => 0, 'total' => 0, 'count' => 0];
$vatPrevSales = ['supply' => 0, 'tax' => 0];
$vatPrevPurchase = ['supply' => 0, 'tax' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT invoice_type, tax_type,
               SUM(supply_amount) as supply, SUM(tax_amount) as tax,
               SUM(total_amount) as total, COUNT(*) as cnt
        FROM tax_invoices
        WHERE issue_date BETWEEN ? AND ? AND invoice_status != '취소'
        GROUP BY invoice_type, tax_type
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $key = $r['invoice_type'] === '매출' ? 'sales' : 'purchase';
        if ($key === 'sales') {
            $vatSales['supply'] += (int)$r['supply'];
            $vatSales['tax']    += (int)$r['tax'];
            $vatSales['total']  += (int)$r['total'];
            $vatSales['count']  += (int)$r['cnt'];
        } else {
            $vatPurchase['supply'] += (int)$r['supply'];
            $vatPurchase['tax']    += (int)$r['tax'];
            $vatPurchase['total']  += (int)$r['total'];
            $vatPurchase['count']  += (int)$r['cnt'];
        }
    }

    // 전기간 부가세
    $stmt->execute([$prevFrom, $prevTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['invoice_type'] === '매출') {
            $vatPrevSales['supply'] += (int)$r['supply'];
            $vatPrevSales['tax']    += (int)$r['tax'];
        } else {
            $vatPrevPurchase['supply'] += (int)$r['supply'];
            $vatPrevPurchase['tax']    += (int)$r['tax'];
        }
    }
} catch (Throwable $e) {
    error_log('closing: VAT query failed: ' . $e->getMessage());
}

$vatPayable = $vatSales['tax'] - $vatPurchase['tax'];
$prevVatPayable = $vatPrevSales['tax'] - $vatPrevPurchase['tax'];

// ===== 인건비 집계 (payslips → 없으면 통장 거래 fallback) =====
$payrollData = ['gross' => 0, 'deduction' => 0, 'net' => 0, 'count' => 0,
                'pension' => 0, 'health' => 0, 'employ' => 0, 'income_tax' => 0];
$payrollSource = 'none';
try {
    $stmt = $pdo->prepare("
        SELECT SUM(gross_pay) as gross, SUM(total_deduction) as deduction, SUM(net_pay) as net,
               SUM(national_pension) as pension, SUM(health_insurance) as health,
               SUM(emp_insurance) as employ, SUM(income_tax) as income_tax, COUNT(*) as cnt
        FROM payslips
        WHERE (year * 100 + month) BETWEEN ? AND ?
    ");
    $fromYM = $year * 100 + $startMonth;
    $toYM   = $year * 100 + $endMonth;
    $stmt->execute([$fromYM, $toYM]);
    $pr = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pr && (int)($pr['cnt'] ?? 0) > 0) {
        $payrollData = [
            'gross' => (int)($pr['gross'] ?? 0), 'deduction' => (int)($pr['deduction'] ?? 0),
            'net' => (int)($pr['net'] ?? 0), 'count' => (int)($pr['cnt'] ?? 0),
            'pension' => (int)($pr['pension'] ?? 0), 'health' => (int)($pr['health'] ?? 0),
            'employ' => (int)($pr['employ'] ?? 0), 'income_tax' => (int)($pr['income_tax'] ?? 0),
        ];
        $payrollSource = 'payslips';
    }
} catch (Throwable $e) {
    error_log('closing: payroll query failed: ' . $e->getMessage());
}

// payslips 없으면 통장 거래에서 급여 관련 출금 집계
$bankPayroll = ['total' => 0, 'count' => 0, 'items' => []];
if ($payrollSource === 'none') {
    try {
        $stmt = $pdo->prepare("
            SELECT ac.name as cat_name, SUM(bt.amount) as total, COUNT(*) as cnt
            FROM bank_transactions bt
            JOIN account_categories ac ON bt.account_code = ac.code
            WHERE bt.transaction_date BETWEEN ? AND ?
              AND bt.tx_type = '출금'
              AND (ac.name LIKE '%급여%' OR ac.name LIKE '%퇴직%' OR ac.name LIKE '%보험%'
                   OR ac.name LIKE '%연금%' OR ac.name LIKE '%원천%')
            GROUP BY ac.name
            ORDER BY total DESC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $bankPayroll['total'] += (int)$r['total'];
            $bankPayroll['count'] += (int)$r['cnt'];
            $bankPayroll['items'][] = $r;
        }
        if ($bankPayroll['count'] > 0) {
            $payrollSource = 'bank';
        }
    } catch (Throwable $e) {
        error_log('closing: bank payroll fallback failed: ' . $e->getMessage());
    }
}

// ===== 법인카드 지출 집계 =====
$cardTotal = 0;
$cardCount = 0;
$cardUnsettled = 0;
$cardByCategory = [];
try {
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) as total, COUNT(*) as cnt,
               SUM(CASE WHEN is_settled = 0 THEN 1 ELSE 0 END) as unsettled
        FROM card_expenses
        WHERE usage_date BETWEEN ? AND ?
        GROUP BY category ORDER BY total DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cardTotal += (int)$r['total'];
        $cardCount += (int)$r['cnt'];
        $cardUnsettled += (int)$r['unsettled'];
        $cardByCategory[] = $r;
    }
} catch (Throwable $e) {
    error_log('closing: card expenses query failed: ' . $e->getMessage());
}

// ===== 통장↔세금계산서 대사 (미수/미지급 추정) =====
$bankRevenue = $plData['매출'];
$invoiceRevenue = $vatSales['supply'];
$revenueGap = $invoiceRevenue - $bankRevenue;
$bankCost = $plData['매입'];
$invoiceCost = $vatPurchase['supply'];
$costGap = $invoiceCost - $bankCost;

// 부가세 기간 라벨 (분기 모드일 때)
$vatPeriodLabel = '';
if ($periodType === 'quarter') {
    $vatPeriods = [1 => '1기 예정 (4/25 신고)', 2 => '1기 확정 (7/25 신고)', 3 => '2기 예정 (10/25 신고)', 4 => '2기 확정 (1/25 신고)'];
    $vatPeriodLabel = $vatPeriods[$q ?? 1] ?? '';
} elseif ($periodType === 'half') {
    $vatPeriodLabel = ($h ?? 1) === 1 ? '1기 확정 (7/25 신고)' : '2기 확정 (1/25 신고)';
}

$basePath = $basePath ?? '';
?>

<div class="space-y-5" id="closingContainer">

    <!-- 기간 선택 바 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="flex flex-wrap items-center gap-3">
            <!-- 기간 유형 -->
            <div class="flex rounded-lg overflow-hidden border border-slate-700">
                <?php
                $ptOptions = ['month' => '월별', 'quarter' => '분기', 'half' => '반기', 'year' => '연간'];
                foreach ($ptOptions as $k => $v): ?>
                <button onclick="setPeriodType('<?= $k ?>')"
                        class="px-3 py-2 text-sm font-medium <?= $periodType === $k ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">
                    <?= $v ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- 연도 -->
            <select id="closingYear" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm"
                    onchange="goClosing()">
                <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?>년</option>
                <?php endfor; ?>
            </select>

            <!-- 월/분기/반기 선택 -->
            <?php if ($periodType === 'month'): ?>
            <select id="closingMonth" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm"
                    onchange="goClosing()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?>월</option>
                <?php endfor; ?>
            </select>
            <?php elseif ($periodType === 'quarter'): ?>
            <select id="closingQuarter" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm"
                    onchange="goClosing()">
                <?php for ($qi = 1; $qi <= 4; $qi++): ?>
                <option value="<?= $qi ?>" <?= $qi === ($q ?? 1) ? 'selected' : '' ?>><?= $qi ?>분기</option>
                <?php endfor; ?>
            </select>
            <?php elseif ($periodType === 'half'): ?>
            <select id="closingHalf" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm"
                    onchange="goClosing()">
                <option value="1" <?= ($h ?? 1) === 1 ? 'selected' : '' ?>>상반기</option>
                <option value="2" <?= ($h ?? 1) === 2 ? 'selected' : '' ?>>하반기</option>
            </select>
            <?php endif; ?>

            <span class="text-sm font-semibold text-slate-200"><?= $periodLabel ?> 결산</span>
            <?php if ($vatPeriodLabel): ?>
            <span class="text-xs text-slate-500">| 부가세 <?= $vatPeriodLabel ?></span>
            <?php endif; ?>

            <?php if ($isCurrentPeriodLocked): ?>
            <span class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-xs text-emerald-400">
                <i data-lucide="lock" class="w-3.5 h-3.5"></i>마감 완료
            </span>
            <?php elseif ($isPartiallyLocked): ?>
            <span class="flex items-center gap-1.5 px-2.5 py-1 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-400">
                <i data-lucide="lock" class="w-3.5 h-3.5"></i>부분 마감 (<?= $lockedCount ?>/<?= $periodMonthCount ?>월)
            </span>
            <?php endif; ?>

            <div class="flex-1"></div>

            <button onclick="downloadCSV()" class="btn btn-secondary btn-sm">
                <i data-lucide="download" class="w-4 h-4"></i>CSV
            </button>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>출력
            </button>
        </div>
    </div>

    <!-- ==================== 계층1: 마감 전 처리할 것 ==================== -->
    <?php
    $blockers = [];
    if ($totalUnclass > 0) $blockers[] = ['label' => "미분류 거래 " . number_format($totalUnclass) . "건", 'link' => $basePath . '/pages/acct_bank.php?tab=classify', 'cta' => '분류하기'];
    if ($cardUnsettled > 0) $blockers[] = ['label' => "카드 미정산 {$cardUnsettled}건", 'link' => $basePath . '/pages/card_settlement.php', 'cta' => '정산하기'];
    if ($invoiceMatchRate !== null && $invoiceMatchRate < 90) $blockers[] = ['label' => "계산서 매칭 {$invoiceMatchRate}%", 'link' => $basePath . '/pages/acct_invoice.php', 'cta' => '매칭하기'];
    ?>
    <?php if (!empty($blockers)): ?>
    <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="flex items-center gap-2 text-sm font-semibold text-amber-300">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>마감 전 처리할 것
            </span>
            <?php foreach ($blockers as $b): ?>
            <a href="<?= $b['link'] ?>" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-900 border border-amber-500/30 rounded-lg text-xs text-amber-200 hover:bg-amber-500/10 transition-colors">
                <?= $b['label'] ?> <span class="text-amber-400 font-medium"><?= $b['cta'] ?> →</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
        <span class="text-sm text-emerald-300">마감 준비가 끝났어요. 아래에서 검토 후 마감하세요.</span>
    </div>
    <?php endif; ?>

    <!-- ==================== 계층2: 핵심 3지표 ==================== -->
    <?php
    $niColorTop = $netIncome >= 0 ? 'text-emerald-400' : 'text-red-400';
    $niChg = pctChange($netIncome, $prevNet);
    $totalCostTop = $costOfSales + $expenses;
    $mxRC = max($revenue, $totalCostTop, 1);
    $mxDW = max($totalDeposit, $totalWithdraw, 1);
    ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- 순이익 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-slate-400">순이익</span>
                <?php if ($niChg !== null): ?>
                <span class="text-xs <?= $niChg >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">전기 대비 <?= $niChg >= 0 ? '+' : '' ?><?= $niChg ?>%</span>
                <?php endif; ?>
            </div>
            <p class="text-2xl zm-stat <?= $niColorTop ?>"><?= $netIncome < 0 ? '-' : '' ?>₩<?= number_format(abs($netIncome)) ?></p>
            <div class="mt-3 space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 w-8 shrink-0">수익</span>
                    <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width:<?= round($revenue / $mxRC * 100) ?>%"></div></div>
                    <span class="text-xs text-emerald-400 tabular-nums shrink-0"><?= clFmt($revenue) ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 w-8 shrink-0">비용</span>
                    <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-red-500 rounded-full" style="width:<?= round($totalCostTop / $mxRC * 100) ?>%"></div></div>
                    <span class="text-xs text-red-400 tabular-nums shrink-0"><?= clFmt($totalCostTop) ?></span>
                </div>
            </div>
        </div>
        <!-- 부가세 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-slate-400">부가세 <?= $vatPayable >= 0 ? '납부 예상' : '환급 예상' ?></span>
                <?php if ($vatPeriodLabel): ?>
                <span class="text-xs text-slate-500"><?= $vatPeriodLabel ?></span>
                <?php endif; ?>
            </div>
            <p class="text-2xl zm-stat <?= $vatPayable >= 0 ? 'text-amber-400' : 'text-emerald-400' ?>">₩<?= number_format(abs($vatPayable)) ?></p>
            <div class="mt-3 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">매출세액</span>
                    <span class="text-emerald-400 tabular-nums">₩<?= number_format($vatSales['tax']) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">매입세액</span>
                    <span class="text-red-400 tabular-nums">₩<?= number_format($vatPurchase['tax']) ?></span>
                </div>
            </div>
        </div>
        <!-- 현금흐름 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-slate-400">현금흐름</span>
                <span class="text-xs text-slate-500"><?= number_format($totalTxCount) ?>건 · 분류율 <?= $classRate ?>%</span>
            </div>
            <p class="text-2xl zm-stat <?= $netFlow >= 0 ? 'text-emerald-400' : 'text-amber-400' ?>"><?= $netFlow < 0 ? '-' : '+' ?>₩<?= number_format(abs($netFlow)) ?></p>
            <div class="mt-3 space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 w-8 shrink-0">입금</span>
                    <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width:<?= round($totalDeposit / $mxDW * 100) ?>%"></div></div>
                    <span class="text-xs text-emerald-400 tabular-nums shrink-0"><?= clFmt($totalDeposit) ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 w-8 shrink-0">출금</span>
                    <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-red-500 rounded-full" style="width:<?= round($totalWithdraw / $mxDW * 100) ?>%"></div></div>
                    <span class="text-xs text-red-400 tabular-nums shrink-0"><?= clFmt($totalWithdraw) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 계층3: 추세 · 비중 차트 ==================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2 mb-4">
                <i data-lucide="line-chart" class="w-4 h-4 text-slate-400"></i>
                월별 순이익 추이 <span class="text-xs font-normal text-slate-500">(<?= $year ?> vs <?= $year - 1 ?>)</span>
            </h3>
            <div class="h-56"><canvas id="clTrendChart"></canvas></div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2 mb-4">
                <i data-lucide="pie-chart" class="w-4 h-4 text-slate-400"></i>
                지출 구성 <span class="text-xs font-normal text-slate-500">(출금 계정과목별)</span>
            </h3>
            <div class="h-56"><canvas id="clExpenseChart"></canvas></div>
        </div>
    </div>

    <!-- 결산 체크리스트 -->
    <?php
    $hasInvoiceData = ($vatSales['count'] + $vatPurchase['count']) > 0;
    $checks = [
        ['step' => 1, 'group' => '자료', 'label' => '매출 세금계산서', 'ok' => $vatSales['count'] > 0,
         'desc' => $vatSales['count'] > 0 ? number_format($vatSales['count']).'건 · 공급가 '.number_format($vatSales['supply']).'원' : '발행 내역 미확인',
         'problem' => $vatSales['count'] > 0 ? '매출 자료는 들어왔습니다.' : '이 기간 매출 세금계산서가 아직 수집되지 않았습니다.',
         'action' => $vatSales['count'] > 0 ? '누락된 발행 건이 없는지만 최종 확인하세요.' : '매출 계산서 탭에서 발행/수집 내역을 가져오세요.',
         'link' => $basePath.'/pages/acct_invoice.php', 'cta' => '매출 확인'],
        ['step' => 2, 'group' => '자료', 'label' => '매입 세금계산서', 'ok' => $vatPurchase['count'] > 0,
         'desc' => $vatPurchase['count'] > 0 ? number_format($vatPurchase['count']).'건 · 공급가 '.number_format($vatPurchase['supply']).'원' : '수취 내역 미확인',
         'problem' => $vatPurchase['count'] > 0 ? '매입 자료는 들어왔습니다.' : '이 기간 매입 세금계산서 수취 내역이 비어 있습니다.',
         'action' => $vatPurchase['count'] > 0 ? '누락/불공제 항목이 없는지 검토하세요.' : '매입 계산서 탭에서 수취 내역을 가져오세요.',
         'link' => $basePath.'/pages/acct_invoice.php', 'cta' => '매입 확인'],
        ['step' => 3, 'group' => '대사', 'label' => '계산서↔통장 매칭', 'ok' => $invoiceMatchRate !== null && $invoiceMatchRate >= 90,
         'desc' => $invoiceMatchRate !== null ? "매칭률 {$invoiceMatchRate}%" : '매칭 데이터 없음',
         'problem' => $invoiceMatchRate !== null && $invoiceMatchRate >= 90 ? '계산서와 입출금 매칭률이 기준 이상입니다.' : '계산서 금액과 통장 입출금이 충분히 연결되지 않았습니다.',
         'action' => $invoiceMatchRate !== null && $invoiceMatchRate >= 90 ? '미수/미지급 차이만 검토하세요.' : '미매칭 계산서를 통장 입출금과 연결하세요.',
         'link' => $basePath.'/pages/acct_invoice.php', 'cta' => '미매칭 처리'],
        ['step' => 4, 'group' => '분류', 'label' => '통장 거래 분류', 'ok' => $totalUnclass === 0 && $totalTxCount > 0,
         'desc' => $totalUnclass > 0 ? '미분류 '.number_format($totalUnclass).'건' : ($totalTxCount > 0 ? '전체 분류 완료' : '거래내역 없음'),
         'problem' => $totalUnclass > 0 ? '계정과목이 없는 통장 거래가 남아 있습니다.' : ($totalTxCount > 0 ? '통장 거래가 모두 분류됐습니다.' : '이 기간 통장 거래내역이 없습니다.'),
         'action' => $totalUnclass > 0 ? 'AI 분류 후 계정과목이 맞는지 검토하세요.' : ($totalTxCount > 0 ? '분류 결과를 표본 검토하세요.' : '통장 거래내역을 먼저 불러오세요.'),
         'link' => $basePath.'/pages/acct_bank.php?tab=classify', 'cta' => 'AI 분류'],
        ['step' => 5, 'group' => '비용', 'label' => '법인카드 정산', 'ok' => $cardCount === 0 || $cardUnsettled === 0,
         'desc' => $cardCount > 0 ? ($cardUnsettled > 0 ? '미정산 '.$cardUnsettled.'건' : '전체 정산 완료') : '카드 사용 없음',
         'problem' => $cardUnsettled > 0 ? '증빙/규정 확인이 끝나지 않은 카드 사용 건이 있습니다.' : '카드 정산 상태는 문제 없습니다.',
         'action' => $cardUnsettled > 0 ? '미정산 건의 증빙을 확인하고 정산 처리하세요.' : '카드 비용이 장부에 반영됐는지만 확인하세요.',
         'link' => $basePath.'/pages/acct_card.php?tab=settle', 'cta' => '정산 처리'],
        ['step' => 6, 'group' => '비용', 'label' => '급여/4대보험', 'ok' => $payrollSource !== 'none',
         'desc' => $payrollSource === 'payslips' ? number_format($payrollData['count']).'명 · '.number_format($payrollData['gross']).'원'
            : ($payrollSource === 'bank' ? '통장 추정 '.number_format($bankPayroll['total']).'원' : '급여 자료 없음'),
         'problem' => $payrollSource !== 'none' ? '급여/보험료 자료가 감지됐습니다.' : '급여대장 또는 4대보험 납부 자료가 없습니다.',
         'action' => $payrollSource !== 'none' ? '급여와 4대보험이 비용에 반영됐는지 확인하세요.' : '급여대장 또는 4대보험 납부 자료를 등록하세요.',
         'link' => $basePath.'/pages/labor.php?tab=payroll', 'cta' => '임금대장 확인'],
        ['step' => 7, 'group' => '세무', 'label' => '부가세 예상액', 'ok' => $hasInvoiceData,
         'desc' => $hasInvoiceData ? ($vatPayable >= 0 ? '납부 '.number_format($vatPayable).'원' : '환급 '.number_format(abs($vatPayable)).'원') : '계산서 데이터 필요',
         'problem' => $hasInvoiceData ? '부가세 예상액을 계산할 수 있습니다.' : '부가세 계산에 필요한 계산서 데이터가 부족합니다.',
         'action' => $hasInvoiceData ? '매출/매입세액 차이를 신고 자료와 대조하세요.' : '세금계산서를 먼저 수집한 뒤 예상액을 다시 계산하세요.',
         'link' => $basePath.'/pages/acct_invoice.php', 'cta' => '근거 확인'],
    ];
    $readyCount = count(array_filter($checks, fn($c) => $c['ok']));
    $totalCheckCount = count($checks);
    $todoCount = $totalCheckCount - $readyCount;
    $readyPercent = $totalCheckCount > 0 ? round($readyCount / $totalCheckCount * 100) : 0;
    $canClose = $todoCount === 0 && !$isPartiallyLocked;
    $lockLabel = $periodType === 'month' ? '이 달 마감하기' : $periodLabel . ' 일괄 마감';
    $unlockLabel = $periodType === 'month' ? '마감 해제' : $periodLabel . ' 마감 해제';
    ?>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="mb-4">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-slate-400"></i>
                    <?= $periodLabel ?> 결산 체크리스트
                </h3>
                <span class="px-2 py-0.5 text-xs rounded-full <?= $todoCount === 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' ?> font-semibold">
                    <?= $readyCount ?>/<?= $totalCheckCount ?> 완료 · <?= $readyPercent ?>%
                </span>
                <?php if ($isCurrentPeriodLocked): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/10 text-emerald-400 font-semibold">마감 완료</span>
                <?php elseif ($isPartiallyLocked): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/10 text-amber-400 font-semibold">부분 마감</span>
                <?php endif; ?>
            </div>
            <div class="mt-2 h-1.5 bg-slate-800 rounded-full overflow-hidden max-w-xl">
                <div class="h-full <?= $todoCount === 0 ? 'bg-emerald-400' : 'bg-primary' ?> rounded-full" style="width: <?= $readyPercent ?>%"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <?php foreach ($checks as $c):
                $statusClass = $c['ok'] ? 'border-slate-800 bg-slate-950/70' : 'border-amber-500/30 bg-amber-500/5';
                $badgeClass = $c['ok'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400';
                $icon = $c['ok'] ? 'check-circle-2' : 'circle-alert';
                $iconColor = $c['ok'] ? 'text-emerald-400' : 'text-amber-400';
            ?>
            <div class="border <?= $statusClass ?> rounded-xl p-3 min-h-[148px] flex flex-col">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-6 h-6 rounded-md bg-slate-900 border border-slate-800 text-[11px] font-bold text-slate-300 flex items-center justify-center shrink-0"><?= $c['step'] ?></span>
                        <i data-lucide="<?= $icon ?>" class="w-4 h-4 <?= $iconColor ?> shrink-0"></i>
                        <p class="text-sm font-semibold text-slate-100 truncate"><?= $c['label'] ?></p>
                    </div>
                    <span class="text-[11px] px-1.5 py-0.5 rounded-full <?= $badgeClass ?> font-semibold shrink-0"><?= $c['ok'] ? '완료' : '필요' ?></span>
                </div>
                <p class="text-xs text-slate-400 truncate mb-1"><?= $c['desc'] ?></p>
                <p class="text-xs <?= $c['ok'] ? 'text-slate-400' : 'text-amber-300' ?> line-clamp-2">
                    <span class="font-semibold">문제:</span> <?= $c['problem'] ?>
                </p>
                <p class="text-xs <?= $c['ok'] ? 'text-slate-500' : 'text-amber-400' ?> mt-1 line-clamp-2">
                    <span class="font-semibold">다음:</span> <?= $c['action'] ?>
                </p>
                <div class="mt-auto pt-2 flex items-center justify-between gap-2">
                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400"><?= $c['group'] ?></span>
                    <?php if (!$c['ok'] && !empty($c['link'])): ?>
                    <a href="<?= $c['link'] ?>" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold text-white bg-primary rounded-md hover:bg-primary-dark transition-colors">
                        <?= $c['cta'] ?> <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="border <?= $isCurrentPeriodLocked ? 'border-emerald-500/30 bg-emerald-500/5' : ($canClose ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-slate-800 bg-slate-950/70') ?> rounded-xl p-3 min-h-[148px] flex flex-col">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-6 h-6 rounded-md bg-slate-900 border border-slate-800 text-[11px] font-bold text-slate-300 flex items-center justify-center shrink-0">8</span>
                        <i data-lucide="<?= $isCurrentPeriodLocked ? 'lock-keyhole' : 'flag' ?>" class="w-4 h-4 <?= $isCurrentPeriodLocked || $canClose ? 'text-emerald-400' : 'text-slate-500' ?> shrink-0"></i>
                        <p class="text-sm font-semibold text-slate-100 truncate">기간 마감</p>
                    </div>
                    <span class="text-[11px] px-1.5 py-0.5 rounded-full <?= $isCurrentPeriodLocked || $canClose ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400' ?> font-semibold shrink-0">
                        <?= $isCurrentPeriodLocked ? '완료' : ($canClose ? '가능' : '대기') ?>
                    </span>
                </div>
                <p class="text-xs text-slate-400 truncate mb-1"><?= $isCurrentPeriodLocked ? '마감 잠김 상태' : ($canClose ? '마감 가능' : '미완료 '.$todoCount.'건') ?></p>
                <p class="text-xs <?= $canClose || $isCurrentPeriodLocked ? 'text-emerald-400' : 'text-slate-400' ?> line-clamp-2">
                    <span class="font-semibold">문제:</span> <?= $isCurrentPeriodLocked ? '기간이 잠겨 있어 수정이 제한됩니다.' : ($canClose ? '마감 전 점검이 모두 완료됐습니다.' : '아직 처리해야 할 결산 항목이 남아 있습니다.') ?>
                </p>
                <p class="text-xs <?= $canClose || $isCurrentPeriodLocked ? 'text-emerald-400' : 'text-slate-500' ?> mt-1 line-clamp-2">
                    <span class="font-semibold">다음:</span> <?= $isCurrentPeriodLocked ? '필요 시 마감 해제 후 수정하세요.' : ($canClose ? '마감 버튼을 눌러 기간을 잠그세요.' : '위 미완료 항목을 먼저 처리하세요.') ?>
                </p>
                <div class="mt-auto pt-2 flex items-center justify-between gap-2">
                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">마감</span>
                    <?php if (!$isCurrentPeriodLocked && !$isPartiallyLocked): ?>
                    <button onclick="toggleLock()" <?= $canClose ? '' : 'disabled' ?> class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold rounded-md border <?= $canClose ? 'border-emerald-500/30 text-emerald-300 hover:bg-emerald-500/10' : 'border-slate-700 text-slate-500 cursor-not-allowed opacity-70' ?> transition-colors">
                        <i data-lucide="lock" class="w-3 h-3"></i><?= $lockLabel ?>
                    </button>
                    <?php elseif ($isPartiallyLocked): ?>
                    <button onclick="toggleLock()" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold border border-amber-500/30 rounded-md hover:bg-amber-500/10 text-amber-400 transition-colors">
                        <i data-lucide="lock" class="w-3 h-3"></i>나머지 마감
                    </button>
                    <?php else: ?>
                    <button onclick="toggleLock(true)" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold border border-emerald-500/30 rounded-md hover:bg-emerald-500/10 text-emerald-400 transition-colors">
                        <i data-lucide="unlock" class="w-3 h-3"></i><?= $unlockLabel ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 계층4: 상세 보기 (접기) ==================== -->
    <details class="closing-detail group">
        <summary class="cursor-pointer list-none bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between hover:bg-slate-800/50 transition-colors">
            <span class="text-sm font-bold text-slate-100 flex items-center gap-2">
                <i data-lucide="table-2" class="w-4 h-4 text-slate-400"></i>
                상세 내역 <span class="text-xs font-normal text-slate-500">손익계산서 · 계좌별 · 계정과목별</span>
            </span>
            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
        </summary>
        <!-- 상세 위계 순서는 order 클래스로 제어: 손익(1) → 계정과목별(2) → 계좌별(3) → 대사(4) → 인건비/카드(5) -->
        <div class="flex flex-col gap-5 mt-5">

    <!-- ==================== 통장↔세금계산서 대사 · 위계 4순위(참고) ==================== -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 order-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                <i data-lucide="git-compare" class="w-4 h-4 text-slate-400"></i>
                통장↔세금계산서 대사 <span class="text-xs font-normal text-slate-500">(미수금·미지급 추정)</span>
            </h3>
            <?php if ($vatPeriodLabel): ?>
            <span class="text-xs text-slate-400"><?= $vatPeriodLabel ?></span>
            <?php endif; ?>
        </div>
        <?php if (abs($revenueGap) > 0 || abs($costGap) > 0): ?>
        <div class="p-3 bg-slate-800/50 rounded-lg">
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-400">매출:</span>
                    <span class="text-slate-300">계산서 <?= number_format($invoiceRevenue) ?> vs 통장 <?= number_format($bankRevenue) ?></span>
                    <?php if ($revenueGap > 0): ?>
                    <span class="text-amber-400 ml-1">→ 미수금 추정 <?= number_format($revenueGap) ?></span>
                    <?php elseif ($revenueGap < 0): ?>
                    <span class="text-slate-500 ml-1">→ 선수금/기타입금 <?= number_format(abs($revenueGap)) ?></span>
                    <?php else: ?>
                    <span class="text-emerald-400 ml-1">일치</span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-slate-400">매입:</span>
                    <span class="text-slate-300">계산서 <?= number_format($invoiceCost) ?> vs 통장 <?= number_format($bankCost) ?></span>
                    <?php if ($costGap > 0): ?>
                    <span class="text-amber-400 ml-1">→ 미지급금 추정 <?= number_format($costGap) ?></span>
                    <?php elseif ($costGap < 0): ?>
                    <span class="text-slate-500 ml-1">→ 선급금/기타출금 <?= number_format(abs($costGap)) ?></span>
                    <?php else: ?>
                    <span class="text-emerald-400 ml-1">일치</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ==================== 손익계산서 (계층 구조) · 위계 1순위 ==================== -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden order-1">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                <i data-lucide="calculator" class="w-4 h-4 text-slate-400"></i>
                손익계산서
            </h3>
            <div class="flex items-center gap-3">
                <button onclick="toggleAllPlGroups()" class="text-xs text-slate-400 hover:text-slate-200 transition-colors" id="plToggleBtn">전체 펼치기</button>
                <span class="text-xs text-slate-500">분류된 거래 기준 · 미분류 <?= number_format($totalUnclass) ?>건 제외</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="plTable">
                <thead class="bg-slate-800/50 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">항목</th>
                        <th class="px-4 py-3 text-right font-medium">당기</th>
                        <th class="px-4 py-3 text-right font-medium">전기간</th>
                        <th class="px-4 py-3 text-right font-medium">증감</th>
                        <th class="px-4 py-3 text-right font-medium">전년 동기</th>
                        <th class="px-4 py-3 text-right font-medium">전년비</th>
                    </tr>
                </thead>
                <tbody class="text-slate-300">
                    <?php
                    // 헬퍼: P&L 행 렌더링
                    function renderPlRow(string $label, int $cur, int $prev, int $yoy, string $color, bool $isBold, bool $hasBorder, int $indent = 0, string $groupId = '', bool $isGroupHeader = false): void {
                        $diff = $cur - $prev;
                        $diffPct = $prev != 0 ? round(($cur - $prev) / abs($prev) * 100, 1) : null;
                        $yoyPct = $yoy != 0 ? round(($cur - $yoy) / abs($yoy) * 100, 1) : null;
                        $borderClass = $hasBorder ? 'border-b border-slate-700' : 'border-b border-slate-800';
                        $fontClass = $isBold ? 'font-semibold text-slate-100' : '';
                        $padLeft = 16 + $indent * 20;
                        $hiddenClass = $groupId ? "pl-detail-row hidden" : '';
                        $dataAttr = $groupId ? "data-pl-group=\"{$groupId}\"" : '';
                        $cursor = $isGroupHeader ? 'cursor-pointer hover:bg-slate-800/50' : 'hover:bg-slate-800/30';
                        $toggleAttr = $isGroupHeader ? "onclick=\"togglePlGroup('{$groupId}')\"" : '';
                        $chevron = $isGroupHeader ? '<i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 inline-block mr-1 transition-transform pl-chevron" data-group="' . $groupId . '"></i>' : '';
                        echo "<tr class=\"{$borderClass} {$cursor} {$hiddenClass}\" {$dataAttr} {$toggleAttr}>";
                        echo "<td class=\"py-3 {$fontClass}\" style=\"padding-left:{$padLeft}px\">{$chevron}{$label}</td>";
                        echo "<td class=\"px-4 py-3 text-right tabular-nums {$color}\">" . number_format($cur) . "</td>";
                        echo "<td class=\"px-4 py-3 text-right tabular-nums text-slate-500\">" . number_format($prev) . "</td>";
                        $diffColor = $diff >= 0 ? 'text-emerald-500' : 'text-red-400';
                        echo "<td class=\"px-4 py-3 text-right tabular-nums {$diffColor}\">";
                        echo ($diff >= 0 ? '+' : '') . number_format($diff);
                        if ($diffPct !== null) echo " <span class=\"text-xs\">(" . ($diffPct >= 0 ? '+' : '') . "{$diffPct}%)</span>";
                        echo "</td>";
                        echo "<td class=\"px-4 py-3 text-right tabular-nums text-slate-500\">" . number_format($yoy) . "</td>";
                        $yoyColor = ($yoyPct ?? 0) >= 0 ? 'text-emerald-500' : 'text-red-400';
                        echo "<td class=\"px-4 py-3 text-right tabular-nums {$yoyColor}\">";
                        if ($yoyPct !== null) echo ($yoyPct >= 0 ? '+' : '') . "{$yoyPct}%";
                        else echo '<span class="text-slate-600">-</span>';
                        echo "</td></tr>\n";
                    }

                    // 헬퍼: 그룹 렌더링
                    function renderPlGroup(array $group, string $gKey, string $color): void {
                        $gCur = $gPrev = $gYoy = 0;
                        foreach ($group['items'] as $item) {
                            $gCur += $item['cur'];
                            $gPrev += $item['prev'];
                            $gYoy += $item['yoy'];
                        }
                        $hasMultiple = count($group['items']) > 1;
                        if ($hasMultiple) {
                            renderPlRow($group['name'], $gCur, $gPrev, $gYoy, $color, true, false, 1, $gKey, true);
                            foreach ($group['items'] as $item) {
                                renderPlRow($item['name'], $item['cur'], $item['prev'], $item['yoy'], $color, false, false, 2, $gKey);
                            }
                        } else {
                            $item = reset($group['items']);
                            $name = $item ? $item['name'] : $group['name'];
                            renderPlRow($name, $gCur, $gPrev, $gYoy, $color, false, false, 1);
                        }
                    }

                    // ── 수익 섹션 ──
                    if (!empty($revenueGroups)):
                        foreach ($revenueGroups as $gKey => $group) {
                            renderPlGroup($group, $gKey, 'text-emerald-400');
                        }
                    endif;
                    renderPlRow('총수익', $revenue, $prevRevenue, $yoyRevenue, 'text-emerald-400', true, true);

                    // ── 비용 섹션 ──
                    if (!empty($costGroups)):
                        foreach ($costGroups as $gKey => $group) {
                            renderPlGroup($group, $gKey, 'text-red-400');
                        }
                    endif;
                    $totalCost = $costOfSales + $expenses;
                    $prevTotalCost = $plPrev['매입'] + $plPrev['비용'];
                    $yoyTotalCost = $plYoy['매입'] + $plYoy['비용'];
                    renderPlRow('총비용', $totalCost, $prevTotalCost, $yoyTotalCost, 'text-red-400', true, true);

                    // ── 순이익 ──
                    $niColor = $netIncome >= 0 ? 'text-emerald-400' : 'text-red-400';
                    renderPlRow('순이익', $netIncome, $prevNet, $yoyNet, $niColor, true, false);
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== 인건비 + 법인카드 요약 · 위계 5순위(참고) ==================== -->
    <div class="grid grid-cols-2 gap-5 order-5">
        <!-- 인건비 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2 mb-3">
                <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                인건비 총괄
                <?php if ($payrollSource === 'payslips'): ?>
                <span class="text-xs font-normal text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">급여명세 기준</span>
                <?php elseif ($payrollSource === 'bank'): ?>
                <span class="text-xs font-normal text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">통장 추정</span>
                <?php endif; ?>
            </h3>
            <?php if ($payrollSource === 'payslips'): ?>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">총지급액</span>
                    <span class="text-slate-200 tabular-nums font-medium"><?= number_format($payrollData['gross']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">4대보험 (회사부담분)</span>
                    <span class="text-red-400 tabular-nums"><?= number_format($payrollData['pension'] + $payrollData['health'] + $payrollData['employ']) ?></span>
                </div>
                <div class="flex justify-between text-xs text-slate-500 pl-3">
                    <span>국민연금 <?= number_format($payrollData['pension']) ?> · 건강보험 <?= number_format($payrollData['health']) ?> · 고용보험 <?= number_format($payrollData['employ']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">원천세 (소득세)</span>
                    <span class="text-red-400 tabular-nums"><?= number_format($payrollData['income_tax']) ?></span>
                </div>
                <div class="flex justify-between text-sm border-t border-slate-800 pt-2 mt-1">
                    <span class="text-slate-300 font-medium">실수령 총액</span>
                    <span class="text-slate-100 tabular-nums font-bold"><?= number_format($payrollData['net']) ?></span>
                </div>
                <p class="text-xs text-slate-500"><?= number_format($payrollData['count']) ?>명 · 원천세 신고 매월 10일</p>
            </div>
            <?php elseif ($payrollSource === 'bank'): ?>
            <div class="space-y-2">
                <?php foreach ($bankPayroll['items'] as $bp): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400"><?= htmlspecialchars($bp['cat_name']) ?></span>
                    <span class="text-red-400 tabular-nums"><?= number_format((int)$bp['total']) ?> <span class="text-xs text-slate-500">(<?= $bp['cnt'] ?>건)</span></span>
                </div>
                <?php endforeach; ?>
                <div class="flex justify-between text-sm border-t border-slate-800 pt-2 mt-1">
                    <span class="text-slate-300 font-medium">합계</span>
                    <span class="text-slate-100 tabular-nums font-bold"><?= number_format($bankPayroll['total']) ?></span>
                </div>
                <p class="text-xs text-amber-400/70">통장 거래 기준 추정치입니다. 노무관리 임금대장에서 확정하면 정확한 내역이 표시됩니다.</p>
            </div>
            <?php else: ?>
            <div class="text-center py-3">
                <p class="text-sm text-slate-500">해당 기간 인건비 데이터가 없습니다.</p>
                <p class="text-xs text-slate-600 mt-1">노무관리 임금대장에서 저장하거나, 통장 거래를 급여 계정으로 분류하세요.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- 법인카드 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2 mb-3">
                <i data-lucide="credit-card" class="w-4 h-4 text-slate-400"></i>
                법인카드 지출
            </h3>
            <?php if ($cardCount > 0): ?>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">총 사용액</span>
                    <span class="text-slate-200 tabular-nums font-medium"><?= number_format($cardTotal) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">사용 건수</span>
                    <span class="text-slate-300 tabular-nums"><?= number_format($cardCount) ?>건</span>
                </div>
                <?php if ($cardUnsettled > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-amber-400">미정산</span>
                    <span class="text-amber-400 tabular-nums"><?= number_format($cardUnsettled) ?>건</span>
                </div>
                <?php endif; ?>
                <?php if (!empty($cardByCategory)): ?>
                <div class="border-t border-slate-800 pt-2 mt-1 space-y-1">
                    <?php foreach (array_slice($cardByCategory, 0, 5) as $cc): ?>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400"><?= htmlspecialchars($cc['category']) ?></span>
                        <span class="text-slate-300 tabular-nums"><?= number_format((int)$cc['total']) ?> (<?= $cc['cnt'] ?>건)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($cardByCategory) > 5): ?>
                    <p class="text-xs text-slate-500">외 <?= count($cardByCategory) - 5 ?>개 항목</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <p class="text-xs text-slate-500">매입세액공제 대상 확인 필요</p>
            </div>
            <?php else: ?>
            <p class="text-sm text-slate-500">해당 기간 카드 사용 내역이 없습니다.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- 계좌별 결산 테이블 · 위계 3순위 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden order-3">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                <i data-lucide="landmark" class="w-4 h-4 text-slate-400"></i>
                계좌별 결산
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/50 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">계좌</th>
                        <th class="px-4 py-3 text-left font-medium">용도</th>
                        <th class="px-4 py-3 text-right font-medium">입금</th>
                        <th class="px-4 py-3 text-right font-medium">출금</th>
                        <th class="px-4 py-3 text-right font-medium">순흐름</th>
                        <th class="px-4 py-3 text-right font-medium">건수</th>
                        <th class="px-4 py-3 text-center font-medium">미분류</th>
                        <th class="px-4 py-3 text-right font-medium">전기 입금</th>
                        <th class="px-4 py-3 text-right font-medium">전기 출금</th>
                    </tr>
                </thead>
                <tbody class="text-slate-300">
                    <?php foreach ($accounts as $a):
                        $s  = $acctSummary[$a['id']] ?? ['deposit'=>0,'withdraw'=>0,'tx_count'=>0,'unclassified'=>0];
                        $ps = $prevSummary[$a['id']] ?? ['deposit'=>0,'withdraw'=>0];
                        $net = (int)$s['deposit'] - (int)$s['withdraw'];
                        $label = $a['account_alias'] ?: $a['bank_name'];
                        $maskedNo = substr($a['account_no'], 0, 6) . '****';
                        $inactive = !$a['is_active'] ? 'opacity-50' : '';
                        $typeColors = ['운영'=>'bg-sky-500/10 text-sky-400','급여'=>'bg-emerald-500/10 text-emerald-400','세금'=>'bg-amber-500/10 text-amber-400','예비'=>'bg-slate-700 text-slate-400','기타'=>'bg-slate-700 text-slate-400'];
                        $type = $a['account_type'] ?? '기타';
                        $tc = $typeColors[$type] ?? $typeColors['기타'];
                    ?>
                    <tr class="border-b border-slate-800 hover:bg-slate-800/30 <?= $inactive ?>">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-200"><?= htmlspecialchars($label) ?></p>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($a['bank_name']) ?> <?= $maskedNo ?></p>
                        </td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded-full <?= $tc ?>"><?= htmlspecialchars($type) ?></span></td>
                        <td class="px-4 py-3 text-right tabular-nums text-emerald-400"><?= number_format((int)$s['deposit']) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-red-400"><?= number_format((int)$s['withdraw']) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium <?= $net >= 0 ? 'text-emerald-400' : 'text-amber-400' ?>"><?= $net >= 0 ? '+' : '' ?><?= number_format($net) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?= number_format((int)$s['tx_count']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ((int)$s['unclassified'] > 0): ?>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/10 text-amber-400"><?= $s['unclassified'] ?>건</span>
                            <?php else: ?>
                            <span class="text-xs text-emerald-500">완료</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-500"><?= number_format((int)$ps['deposit']) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-500"><?= number_format((int)$ps['withdraw']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($accounts)): ?>
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">등록된 계좌가 없습니다.</td></tr>
                    <?php endif; ?>
                    <?php if (!empty($accounts)): ?>
                    <tr class="bg-slate-800/50 font-semibold text-slate-100">
                        <td class="px-4 py-3" colspan="2">합계</td>
                        <td class="px-4 py-3 text-right tabular-nums text-emerald-400"><?= number_format($totalDeposit) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-red-400"><?= number_format($totalWithdraw) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums <?= $netFlow >= 0 ? 'text-emerald-400' : 'text-amber-400' ?>"><?= $netFlow >= 0 ? '+' : '' ?><?= number_format($netFlow) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?= number_format($totalTxCount) ?></td>
                        <td class="px-4 py-3 text-center"><?= $totalUnclass > 0 ? '<span class="text-amber-400">'.number_format($totalUnclass).'건</span>' : '<span class="text-emerald-500">완료</span>' ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-500"><?= number_format($prevTotalDep) ?></td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-500"><?= number_format($prevTotalWd) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 입출금 계정과목별 (전기 대비) · 위계 2순위 -->
    <div class="grid grid-cols-2 gap-5 order-2">
        <!-- 수익 계정 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-emerald-400"></i>
                    입금 계정과목별 (전기 대비)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800/50 text-slate-300">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium">계정과목</th>
                            <th class="px-4 py-2.5 text-right font-medium">건수</th>
                            <th class="px-4 py-2.5 text-right font-medium">당기</th>
                            <th class="px-4 py-2.5 text-right font-medium"><?= $prevColLabel ?></th>
                            <th class="px-4 py-2.5 text-right font-medium">증감</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-300">
                        <?php foreach ($catDeposit as $r):
                            $isUnclass = $r['category'] === '미분류';
                            $prevAmt   = $categoryPrevMap[($r['account_code'] ?? '') . '|' . $r['tx_type']] ?? 0;
                            $diff      = (int)$r['total'] - $prevAmt;
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-800/30 <?= $isUnclass ? 'bg-amber-500/5' : '' ?>">
                            <td class="px-4 py-2.5 <?= $isUnclass ? 'text-amber-400' : '' ?>"><?= htmlspecialchars($r['category']) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums"><?= number_format((int)$r['cnt']) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-emerald-400"><?= number_format((int)$r['total']) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-slate-500"><?= number_format($prevAmt) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums <?= $diff >= 0 ? 'text-emerald-500' : 'text-red-400' ?>">
                                <?= $diff >= 0 ? '+' : '' ?><?= number_format($diff) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($catDeposit)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">입금 내역이 없습니다.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 출금 계정 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                    <i data-lucide="trending-down" class="w-4 h-4 text-red-400"></i>
                    출금 계정과목별 (전기 대비)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800/50 text-slate-300">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium">계정과목</th>
                            <th class="px-4 py-2.5 text-right font-medium">건수</th>
                            <th class="px-4 py-2.5 text-right font-medium">당기</th>
                            <th class="px-4 py-2.5 text-right font-medium"><?= $prevColLabel ?></th>
                            <th class="px-4 py-2.5 text-right font-medium">증감</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-300">
                        <?php foreach ($catWithdraw as $r):
                            $isUnclass = $r['category'] === '미분류';
                            $prevAmt   = $categoryPrevMap[($r['account_code'] ?? '') . '|' . $r['tx_type']] ?? 0;
                            $diff      = (int)$r['total'] - $prevAmt;
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-800/30 <?= $isUnclass ? 'bg-amber-500/5' : '' ?>">
                            <td class="px-4 py-2.5 <?= $isUnclass ? 'text-amber-400' : '' ?>"><?= htmlspecialchars($r['category']) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums"><?= number_format((int)$r['cnt']) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-red-400"><?= number_format((int)$r['total']) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-slate-500"><?= number_format($prevAmt) ?></td>
                            <td class="px-4 py-2.5 text-right tabular-nums <?= $diff <= 0 ? 'text-emerald-500' : 'text-red-400' ?>">
                                <?= $diff >= 0 ? '+' : '' ?><?= number_format($diff) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($catWithdraw)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">출금 내역이 없습니다.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        </div>
    </details>
</div>

<style>
@media print {
    #sidebar, .ml-60 > header, button, a, .zm-tab-container, select { display: none !important; }
    #mainContent { margin-left: 0 !important; margin-top: 0 !important; }
    main { padding: 16px !important; }
    .bg-slate-900 { border: 1px solid #ccc !important; }
}
</style>

<?php
// 차트 데이터 · 월별 순이익 추이 + 출금 도넛
$trendNet = []; $trendPrevNet = [];
for ($m = 1; $m <= 12; $m++) {
    $trendNet[] = $monthlyTrend[$m]['net'] ?? 0;
    $trendPrevNet[] = $monthlyTrendPrev[$m]['net'] ?? 0;
}
$donutData = [];
foreach ($catWithdraw as $r) {
    if ($r['category'] === '미분류') continue;
    $donutData[] = ['name' => $r['category'], 'value' => (int)$r['total']];
}
usort($donutData, fn($a, $b) => $b['value'] - $a['value']);
$donutTop = array_slice($donutData, 0, 6);
$donutEtc = array_sum(array_column(array_slice($donutData, 6), 'value'));
if ($donutEtc > 0) $donutTop[] = ['name' => '기타', 'value' => $donutEtc];
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const PERIOD_TYPE = '<?= $periodType ?>';
const API_BASE = '<?= $basePath ?>';
const CL_TREND_NET = <?= json_encode($trendNet) ?>;
const CL_TREND_PREV = <?= json_encode($trendPrevNet) ?>;
const CL_DONUT = <?= json_encode($donutTop, JSON_UNESCAPED_UNICODE) ?>;
const CL_YEAR = <?= $year ?>;

function clChartM(v) { const a = Math.abs(v); if (a >= 1e8) return (v/1e8).toFixed(1)+'억'; if (a >= 1e4) return Math.round(v/1e4)+'만'; return v; }
function clChartWon(v) { return '₩' + Number(v).toLocaleString('ko-KR'); }

function clBuildCharts() {
    if (typeof Chart === 'undefined') { setTimeout(clBuildCharts, 100); return; }
    const _cls = getComputedStyle(document.documentElement);
    Chart.defaults.color = _cls.getPropertyValue('--zm-text-subtle').trim() || '#94a3b8';

    const tc = document.getElementById('clTrendChart');
    if (tc && !tc.dataset.built) {
        tc.dataset.built = '1';
        new Chart(tc, {
            type: 'line',
            data: {
                labels: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
                datasets: [
                    { label: CL_YEAR+'년', data: CL_TREND_NET, borderColor: _cls.getPropertyValue('--zm-primary').trim()||'#4F6AFF', backgroundColor: 'rgba(79,106,255,0.12)', fill: true, tension: 0.35, pointRadius: 2, borderWidth: 2 },
                    { label: (CL_YEAR-1)+'년', data: CL_TREND_PREV, borderColor: document.documentElement.dataset.theme==='dark'?'rgba(148,163,184,0.6)':'rgba(148,163,184,0.45)', borderDash: [5,4], fill: false, tension: 0.35, pointRadius: 0, borderWidth: 1.5 },
                ]
            },
            options: { responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => `${c.dataset.label}: ${clChartWon(c.raw)}` } } },
                scales: { y: { ticks: { callback: v => clChartM(v) }, grid: { color: (_cls.getPropertyValue('--zm-border').trim()||'#e5e7eb') + '30' } }, x: { grid: { display: false } } } }
        });
    }

    const ec = document.getElementById('clExpenseChart');
    if (ec && !ec.dataset.built) {
        ec.dataset.built = '1';
        if (!CL_DONUT.length) {
            ec.parentElement.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-slate-500">출금 내역이 없어요</div>';
        } else {
            new Chart(ec, {
                type: 'doughnut',
                data: { labels: CL_DONUT.map(d => d.name), datasets: [{ data: CL_DONUT.map(d => d.value),
                    backgroundColor: ['#60a5fa','#EF4444','#F59E0B','#10B981','#8B5CF6','#EC4899','#64748B'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '60%',
                    plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 8, font: { size: 10 } } },
                        tooltip: { callbacks: { label: c => `${c.label}: ${clChartWon(c.raw)}` } } } }
            });
        }
    }
}
setTimeout(clBuildCharts, 60);

// 손익계산서 그룹 접기/펼치기
let plGroupsExpanded = false;
function togglePlGroup(groupId) {
    const rows = document.querySelectorAll(`tr[data-pl-group="${groupId}"].pl-detail-row`);
    const chevrons = document.querySelectorAll(`i.pl-chevron[data-group="${groupId}"]`);
    const isHidden = rows.length > 0 && rows[0].classList.contains('hidden');
    rows.forEach(r => r.classList.toggle('hidden', !isHidden));
    chevrons.forEach(c => c.style.transform = isHidden ? 'rotate(90deg)' : '');
}
function toggleAllPlGroups() {
    plGroupsExpanded = !plGroupsExpanded;
    document.querySelectorAll('.pl-detail-row').forEach(r => r.classList.toggle('hidden', !plGroupsExpanded));
    document.querySelectorAll('i.pl-chevron').forEach(c => c.style.transform = plGroupsExpanded ? 'rotate(90deg)' : '');
    const btn = document.getElementById('plToggleBtn');
    if (btn) btn.textContent = plGroupsExpanded ? '전체 접기' : '전체 펼치기';
}

function setPeriodType(pt) {
    const y = document.getElementById('closingYear').value;
    let url = '?tab=closing&pt=' + pt + '&cy=' + y;
    if (pt === 'month') url += '&cm=<?= $month ?>';
    else if (pt === 'quarter') url += '&cq=1';
    else if (pt === 'half') url += '&ch=1';
    location.href = url;
}

function goClosing() {
    const y = document.getElementById('closingYear').value;
    let url = '?tab=closing&pt=' + PERIOD_TYPE + '&cy=' + y;
    if (PERIOD_TYPE === 'month') {
        url += '&cm=' + document.getElementById('closingMonth').value;
    } else if (PERIOD_TYPE === 'quarter') {
        url += '&cq=' + document.getElementById('closingQuarter').value;
    } else if (PERIOD_TYPE === 'half') {
        url += '&ch=' + document.getElementById('closingHalf').value;
    }
    location.href = url;
}

function downloadCSV() {
    const tables = document.querySelectorAll('#closingContainer table');
    let csv = '﻿'; // BOM for Korean
    tables.forEach((table, ti) => {
        if (ti > 0) csv += '\n\n';
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('th, td');
            const line = Array.from(cells).map(c => {
                let t = c.innerText.replace(/"/g, '""').trim();
                return '"' + t + '"';
            });
            csv += line.join(',') + '\n';
        });
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '결산_<?= $periodLabel ?>.csv';
    a.click();
}

async function toggleLock(forceUnlock) {
    const isLocked = <?= $isCurrentPeriodLocked ? 'true' : 'false' ?>;
    const isPartial = <?= $isPartiallyLocked ? 'true' : 'false' ?>;
    const months = <?= json_encode(range($startMonth, $endMonth)) ?>;
    const year = <?= $year ?>;
    const closedMonths = <?= json_encode(array_map('intval', array_map(fn($k) => explode('-', $k)[1], array_filter(array_keys($closedMonths), fn($k) => str_starts_with($k, $year . '-'))))) ?>;

    let doLock;
    let targetMonths;
    let msg;

    if (forceUnlock) {
        doLock = false;
        targetMonths = months.filter(m => closedMonths.includes(m));
        const monthList = targetMonths.join(', ') + '월';
        msg = `${year}년 ${monthList} 마감을 전체 해제하시겠습니까?\n해제하면 해당 월의 데이터를 다시 수정할 수 있습니다.`;
    } else if (isPartial) {
        doLock = true;
        targetMonths = months.filter(m => !closedMonths.includes(m));
        const monthList = targetMonths.join(', ') + '월';
        msg = `${year}년 ${monthList}을 추가 마감하시겠습니까?`;
    } else if (isLocked) {
        doLock = false;
        targetMonths = months;
        const monthList = months.length === 1 ? months[0] + '월' : months.join(', ') + '월';
        msg = `${year}년 ${monthList} 마감을 해제하시겠습니까?\n해제하면 해당 월의 데이터를 다시 수정할 수 있습니다.`;
    } else {
        doLock = true;
        targetMonths = months;
        const monthList = months.length === 1 ? months[0] + '월' : months.join(', ') + '월';
        msg = `${year}년 ${monthList}을 마감하시겠습니까?\n마감하면 해당 월의 거래내역 수정이 제한됩니다.`;
    }

    if (!(await AppUI.confirm(msg))) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let failed = [];
    for (const m of targetMonths) {
        try {
            const res = await fetch(API_BASE + '/api/bankapi.php?action=closing_lock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ year, month: m, lock: doLock })
            });
            const d = await res.json();
            if (!d.success) failed.push(m + '월: ' + (d.message || '실패'));
        } catch {
            failed.push(m + '월: 서버 오류');
        }
    }

    if (failed.length > 0) {
        alert('일부 실패:\n' + failed.join('\n'));
    }
    location.reload();
}
</script>
