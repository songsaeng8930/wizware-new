<?php
$pageTitle = '재무관리';
$currentPage = 'accounting';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();

// ── 날짜 범위 ──
$thisMonthStart = date('Y-m-01');
$thisMonthEnd   = date('Y-m-t');
$prevMonthStart = date('Y-m-01', strtotime('-1 month'));
$prevMonthEnd   = date('Y-m-t', strtotime('-1 month'));

// ── KPI 데이터 조회 ──
$kpi = ['income' => 0, 'expense' => 0, 'card' => 0, 'balance' => 0,
        'prev_income' => 0, 'prev_expense' => 0, 'prev_card' => 0];
$hasData = false;

if ($pdo) {
    try {
        // 이번 달 입금 합계
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE tx_type = '입금' AND transaction_date BETWEEN ? AND ?");
        $st->execute([$thisMonthStart, $thisMonthEnd]);
        $kpi['income'] = (int) $st->fetchColumn();

        // 전월 입금
        $st->execute([$prevMonthStart, $prevMonthEnd]);
        $kpi['prev_income'] = (int) $st->fetchColumn();

        // 이번 달 지출 (카드 + 통장 출금)
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM card_expenses WHERE usage_date BETWEEN ? AND ?");
        $st->execute([$thisMonthStart, $thisMonthEnd]);
        $cardExpThisMonth = (int) $st->fetchColumn();

        $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE tx_type = '출금' AND transaction_date BETWEEN ? AND ?");
        $st->execute([$thisMonthStart, $thisMonthEnd]);
        $bankOutThisMonth = (int) $st->fetchColumn();
        $kpi['expense'] = $cardExpThisMonth + $bankOutThisMonth;

        // 전월 지출
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM card_expenses WHERE usage_date BETWEEN ? AND ?");
        $st->execute([$prevMonthStart, $prevMonthEnd]);
        $prevCardExp = (int) $st->fetchColumn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE tx_type = '출금' AND transaction_date BETWEEN ? AND ?");
        $st->execute([$prevMonthStart, $prevMonthEnd]);
        $prevBankOut = (int) $st->fetchColumn();
        $kpi['prev_expense'] = $prevCardExp + $prevBankOut;

        // 이번 달 카드 사용액
        $kpi['card'] = $cardExpThisMonth;
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM card_expenses WHERE usage_date BETWEEN ? AND ?");
        $st->execute([$prevMonthStart, $prevMonthEnd]);
        $kpi['prev_card'] = (int) $st->fetchColumn();

        // 계좌 잔액 (최신 bank_transactions의 balance, 없으면 0)
        $st = $pdo->query("SELECT ba.id, ba.bank_name, ba.account_alias,
            COALESCE((SELECT bt.balance FROM bank_transactions bt WHERE bt.account_id = ba.id ORDER BY bt.transaction_date DESC, bt.id DESC LIMIT 1), 0) AS last_balance
            FROM bank_accounts ba WHERE ba.is_active = 1");
        $accounts = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($accounts as $acc) {
            $kpi['balance'] += (int) $acc['last_balance'];
        }

        $hasData = ($kpi['income'] > 0 || $kpi['expense'] > 0 || $kpi['card'] > 0 || $kpi['balance'] > 0);

        // ── 6개월 월별 집계 (차트) ──
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = date('Y-m-01', strtotime("-{$i} months"));
            $mEnd   = date('Y-m-t', strtotime("-{$i} months"));
            $label  = date('n월', strtotime($mStart));

            $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE tx_type = '입금' AND transaction_date BETWEEN ? AND ?");
            $st->execute([$mStart, $mEnd]);
            $mIncome = (int) $st->fetchColumn();

            $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM card_expenses WHERE usage_date BETWEEN ? AND ?");
            $st->execute([$mStart, $mEnd]);
            $mCardExp = (int) $st->fetchColumn();

            $st = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE tx_type = '출금' AND transaction_date BETWEEN ? AND ?");
            $st->execute([$mStart, $mEnd]);
            $mBankOut = (int) $st->fetchColumn();

            $monthlyData[] = ['label' => $label, 'income' => $mIncome, 'expense' => $mCardExp + $mBankOut];
        }

        // ── 처리 필요 건수 ──
        $unsettledCount = (int) $pdo->query("SELECT COUNT(*) FROM card_expenses WHERE is_settled = 0")->fetchColumn();
        $violationCount = (int) $pdo->query("SELECT COUNT(*) FROM card_expenses WHERE compliance_status = '미준수'")->fetchColumn();

        // ── 카드별 당월 사용현황 ──
        $st = $pdo->prepare("SELECT c.card_alias, COALESCE(SUM(ce.amount), 0) AS total
            FROM cards c LEFT JOIN card_expenses ce ON c.id = ce.card_id AND ce.usage_date BETWEEN ? AND ?
            WHERE c.is_active = 1 GROUP BY c.id, c.card_alias ORDER BY total DESC");
        $st->execute([$thisMonthStart, $thisMonthEnd]);
        $cardUsage = $st->fetchAll(PDO::FETCH_ASSOC);

        // ── 최근 거래 10건 ──
        $recentTx = [];
        // 카드 지출
        $st = $pdo->query("SELECT usage_date AS tx_date, CONCAT(user_name, ' - ', category) AS tx_desc, amount, '카드' AS tx_source FROM card_expenses ORDER BY usage_date DESC, id DESC LIMIT 10");
        $cardTx = $st->fetchAll(PDO::FETCH_ASSOC);
        // 통장 거래
        $st = $pdo->query("SELECT transaction_date AS tx_date, description AS tx_desc, amount, CASE tx_type WHEN '입금' THEN '입금' ELSE '출금' END AS tx_source FROM bank_transactions ORDER BY transaction_date DESC, id DESC LIMIT 10");
        $bankTx = $st->fetchAll(PDO::FETCH_ASSOC);
        $recentTx = array_merge($cardTx, $bankTx);
        usort($recentTx, fn($a, $b) => strcmp($b['tx_date'], $a['tx_date']));
        $recentTx = array_slice($recentTx, 0, 10);

        // ── 항목별 지출 비중 ──
        $st = $pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) AS total FROM card_expenses WHERE usage_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
        $st->execute([$thisMonthStart, $thisMonthEnd]);
        $categoryBreakdown = $st->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('[AcctDashboard] DB 오류: ' . $e->getMessage());
    }
}

// ── 샘플 데이터 fallback (DB 빈 경우) ──
if (!$hasData) {
    $hasData = true;
    $kpi = ['income' => 42800000, 'expense' => 31200000, 'card' => 8950000, 'balance' => 128500000,
            'prev_income' => 38500000, 'prev_expense' => 29800000, 'prev_card' => 7200000];
    $monthlyData = [
        ['label' => '1월', 'income' => 35200000, 'expense' => 28100000],
        ['label' => '2월', 'income' => 31800000, 'expense' => 26500000],
        ['label' => '3월', 'income' => 44100000, 'expense' => 32800000],
        ['label' => '4월', 'income' => 38500000, 'expense' => 29800000],
        ['label' => '5월', 'income' => 41200000, 'expense' => 30500000],
        ['label' => '6월', 'income' => 42800000, 'expense' => 31200000],
    ];
    $unsettledCount = 12;
    $violationCount = 2;
    $cardUsage = [
        ['card_alias' => '영업팀 공용카드', 'total' => 3200000],
        ['card_alias' => '개발팀 카드',     'total' => 2800000],
        ['card_alias' => '김대표 법인카드', 'total' => 1950000],
        ['card_alias' => '경영지원 카드',   'total' => 1000000],
    ];
    $recentTx = [
        ['tx_date' => '2026-06-18', 'tx_desc' => '스타벅스 강남점', 'amount' => 32000, 'tx_source' => '카드'],
        ['tx_date' => '2026-06-17', 'tx_desc' => '6월 매출 입금 - A사', 'amount' => 15000000, 'tx_source' => '입금'],
        ['tx_date' => '2026-06-16', 'tx_desc' => 'AWS 클라우드 비용', 'amount' => 890000, 'tx_source' => '카드'],
        ['tx_date' => '2026-06-15', 'tx_desc' => 'KTX 예매 - 부산 출장', 'amount' => 118000, 'tx_source' => '카드'],
        ['tx_date' => '2026-06-14', 'tx_desc' => '6월 급여 이체', 'amount' => 22000000, 'tx_source' => '출금'],
        ['tx_date' => '2026-06-13', 'tx_desc' => '사무용품 구매', 'amount' => 124500, 'tx_source' => '카드'],
        ['tx_date' => '2026-06-12', 'tx_desc' => '6월 매출 입금 - B사', 'amount' => 8500000, 'tx_source' => '입금'],
        ['tx_date' => '2026-06-11', 'tx_desc' => '한솥도시락 야근 석식', 'amount' => 45000, 'tx_source' => '카드'],
    ];
    $categoryBreakdown = [
        ['category' => '접대비', 'total' => 2850000],
        ['category' => '클라우드 비용', 'total' => 1780000],
        ['category' => '출장비', 'total' => 1450000],
        ['category' => '복리후생비', 'total' => 1200000],
        ['category' => '회의비', 'total' => 850000],
        ['category' => '소모품비', 'total' => 620000],
        ['category' => '도서구입비', 'total' => 200000],
    ];
    $accounts = [
        ['bank_name' => '신한은행', 'account_alias' => '(주)재밋 운영계좌', 'last_balance' => 85200000],
        ['bank_name' => '국민은행', 'account_alias' => '급여 계좌', 'last_balance' => 32800000],
        ['bank_name' => '우리은행', 'account_alias' => '세금 적립 계좌', 'last_balance' => 10500000],
    ];
}

// ── 증감률 계산 헬퍼 ──
function calcChange(int $current, int $previous): array {
    if ($previous === 0) return ['rate' => 0, 'direction' => 'none'];
    $rate = round(($current - $previous) / $previous * 100, 1);
    return ['rate' => abs($rate), 'direction' => $rate >= 0 ? 'up' : 'down'];
}

$incomeChange  = calcChange($kpi['income'], $kpi['prev_income']);
$expenseChange = calcChange($kpi['expense'], $kpi['prev_expense']);
$cardChange    = calcChange($kpi['card'], $kpi['prev_card']);

// 금액 포맷
function fmtAmount(int $amount): string {
    if ($amount >= 100000000) return number_format($amount / 100000000, 1) . '억';
    if ($amount >= 10000) return number_format($amount / 10000, 0) . '만';
    return number_format($amount);
}

// 차트 데이터 JSON
$chartLabels  = json_encode(array_column($monthlyData ?? [], 'label'));
$chartIncome  = json_encode(array_column($monthlyData ?? [], 'income'));
$chartExpense = json_encode(array_column($monthlyData ?? [], 'expense'));

// 도넛차트 데이터
$donutLabels = json_encode(array_column($categoryBreakdown ?? [], 'category'));
$donutValues = json_encode(array_map('intval', array_column($categoryBreakdown ?? [], 'total')));
$donutTotal  = array_sum(array_column($categoryBreakdown ?? [], 'total'));
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<div class="p-6 bg-slate-950 min-h-[calc(100vh-3.5rem)]">

    <!-- 헤더 -->
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-100">재무관리 대시보드</h2>
            <p class="text-sm text-slate-500 mt-1">계좌, 카드, 세금계산서 흐름을 월 단위로 확인합니다.</p>
        </div>
        <span class="text-sm text-slate-500"><?= date('Y년 n월') ?> 기준</span>
    </div>

    <div class="grid grid-cols-4 gap-3 mb-6">
        <a href="<?= $basePath ?>/pages/acct_bank.php" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-slate-600 transition-colors">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-100">계좌 연결</p>
                    <p class="text-xs text-slate-500 mt-1">입출금 조회 준비</p>
                </div>
                <i data-lucide="landmark" class="w-5 h-5 text-slate-400"></i>
            </div>
        </a>
        <a href="<?= $basePath ?>/pages/acct_card.php" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-slate-600 transition-colors">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-100">카드 지출</p>
                    <p class="text-xs text-slate-500 mt-1">정산/규정 확인</p>
                </div>
                <i data-lucide="credit-card" class="w-5 h-5 text-slate-400"></i>
            </div>
        </a>
        <a href="<?= $basePath ?>/pages/acct_invoice.php" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-slate-600 transition-colors">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-100">세금계산서</p>
                    <p class="text-xs text-slate-500 mt-1">발행/수취 내역</p>
                </div>
                <i data-lucide="receipt-text" class="w-5 h-5 text-slate-400"></i>
            </div>
        </a>
        <a href="<?= $basePath ?>/pages/acct_report.php" class="bg-slate-900 border border-slate-800 rounded-xl p-4 hover:border-slate-600 transition-colors">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-100">세무리포트</p>
                    <p class="text-xs text-slate-500 mt-1">마감 자료 점검</p>
                </div>
                <i data-lucide="file-chart-column" class="w-5 h-5 text-slate-400"></i>
            </div>
        </a>
    </div>

    <!-- ===== 1행: KPI 카드 4개 ===== -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <!-- 입금합계 -->
        <div class="bg-slate-900 rounded-xl p-5 border border-slate-800 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-400">이번 달 입금</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><i data-lucide="trending-up" class="w-4 h-4 text-amber-500"></i></span>
            </div>
            <div class="text-2xl zm-stat text-slate-100"><?= fmtAmount($kpi['income']) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></div>
            <?php if ($incomeChange['direction'] !== 'none'): ?>
            <div class="mt-1 text-sm <?= $incomeChange['direction'] === 'up' ? 'text-emerald-500' : 'text-rose-500' ?>">
                <?= $incomeChange['direction'] === 'up' ? '▲' : '▼' ?> <?= $incomeChange['rate'] ?>% 전월비
            </div>
            <?php endif; ?>
        </div>

        <!-- 지출합계 -->
        <div class="bg-slate-900 rounded-xl p-5 border border-slate-800 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-400">이번 달 지출</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><i data-lucide="trending-down" class="w-4 h-4 text-amber-500"></i></span>
            </div>
            <div class="text-2xl zm-stat text-slate-100"><?= fmtAmount($kpi['expense']) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></div>
            <?php if ($expenseChange['direction'] !== 'none'): ?>
            <div class="mt-1 text-sm <?= $expenseChange['direction'] === 'up' ? 'text-rose-500' : 'text-emerald-500' ?>">
                <?= $expenseChange['direction'] === 'up' ? '▲' : '▼' ?> <?= $expenseChange['rate'] ?>% 전월비
            </div>
            <?php endif; ?>
        </div>

        <!-- 카드 사용액 -->
        <div class="bg-slate-900 rounded-xl p-5 border border-slate-800 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-400">카드 사용액</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><i data-lucide="credit-card" class="w-4 h-4 text-amber-500"></i></span>
            </div>
            <div class="text-2xl zm-stat text-slate-100"><?= fmtAmount($kpi['card']) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></div>
            <?php if ($cardChange['direction'] !== 'none'): ?>
            <div class="mt-1 text-sm <?= $cardChange['direction'] === 'up' ? 'text-rose-500' : 'text-emerald-500' ?>">
                <?= $cardChange['direction'] === 'up' ? '▲' : '▼' ?> <?= $cardChange['rate'] ?>% 전월비
            </div>
            <?php endif; ?>
        </div>

        <!-- 계좌 잔액 -->
        <div class="bg-slate-900 rounded-xl p-5 border border-slate-800 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-400">계좌 잔액</span>
                <span class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center"><i data-lucide="landmark" class="w-4 h-4 text-slate-400"></i></span>
            </div>
            <div class="text-2xl zm-stat text-slate-100"><?= fmtAmount($kpi['balance']) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></div>
            <div class="mt-1 text-sm text-slate-500"><?= count($accounts ?? []) ?>개 계좌 합산</div>
        </div>
    </div>

    <!-- ===== 2행: 차트 + 사이드 ===== -->
    <div class="grid grid-cols-3 gap-5 mb-6">
        <!-- 월별 추이 차트 -->
        <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h3 class="text-sm font-bold text-slate-100 mb-4">월별 입금 / 지출 추이</h3>
            <?php if ($hasData): ?>
            <div style="position: relative; height: 280px;">
                <canvas id="monthlyChart"></canvas>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center py-16 text-slate-500">
                <i data-lucide="bar-chart-3" class="w-12 h-12 mb-3 opacity-30"></i>
                <p class="text-sm font-semibold text-slate-300">아직 거래 내역이 없습니다</p>
                <p class="text-sm mt-1 text-center">계좌를 연결하거나 카드 지출을 등록하면 월별 추이가 표시됩니다.</p>
                <div class="flex flex-wrap justify-center gap-2 mt-4">
                    <a href="<?= $basePath ?>/pages/acct_bank.php" class="px-3 py-2 text-sm bg-primary text-white rounded-lg">계좌관리</a>
                    <a href="<?= $basePath ?>/pages/acct_card.php" class="btn btn-secondary btn-sm">카드관리</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 오른쪽 사이드 -->
        <div class="col-span-1 space-y-5">
            <!-- 처리 필요 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
                <h3 class="text-sm font-bold text-slate-100 mb-3">처리 필요</h3>
                <div class="space-y-2.5">
                    <a href="<?= $basePath ?>/pages/acct_card.php?tab=settle&filter=unsettled" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-slate-950 transition-colors group">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?= $unsettledCount > 0 ? 'bg-amber-400' : 'bg-slate-700' ?>"></span>
                            <span class="text-sm text-slate-300">미정산 카드 지출</span>
                        </div>
                        <span class="text-sm font-bold <?= $unsettledCount > 0 ? 'text-amber-600' : 'text-slate-600' ?>"><?= $unsettledCount ?>건</span>
                    </a>
                    <a href="<?= $basePath ?>/pages/acct_card.php?tab=expenses&filter=violation" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-slate-950 transition-colors group">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?= $violationCount > 0 ? 'bg-amber-100' : 'bg-slate-700' ?>"></span>
                            <span class="text-sm text-slate-300">규정 위반</span>
                        </div>
                        <span class="text-sm font-bold <?= $violationCount > 0 ? 'text-amber-700' : 'text-slate-600' ?>"><?= $violationCount ?>건</span>
                    </a>
                </div>
            </div>

            <!-- 카드별 사용현황 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-100">카드별 사용현황</h3>
                    <span class="text-sm text-slate-500">이번 달</span>
                </div>
                <?php if (!empty($cardUsage)): ?>
                <div class="space-y-3">
                    <?php foreach ($cardUsage as $cu): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-300 truncate max-w-[140px]"><?= htmlspecialchars($cu['card_alias']) ?></span>
                        <span class="text-sm font-semibold text-slate-100"><?= number_format($cu['total']) ?>원</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-slate-500 py-4 text-center">등록된 카드가 없습니다</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== 3행: 최근 거래 + 지출 비중 ===== -->
    <div class="grid grid-cols-3 gap-5">
        <!-- 최근 거래내역 -->
        <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-100">최근 거래내역</h3>
                <a href="<?= $basePath ?>/pages/acct_card.php" class="text-sm text-slate-500 hover:text-slate-300 transition-colors">전체보기 &rarr;</a>
            </div>
            <?php if (!empty($recentTx)): ?>
            <div class="overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="text-left text-sm font-medium text-slate-500 pb-2 w-[90px]">날짜</th>
                            <th class="text-left text-sm font-medium text-slate-500 pb-2">내용</th>
                            <th class="text-right text-sm font-medium text-slate-500 pb-2 w-[110px]">금액</th>
                            <th class="text-center text-sm font-medium text-slate-500 pb-2 w-[60px]">구분</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTx as $tx): ?>
                        <tr class="border-b border-slate-800">
                            <td class="py-2.5 text-sm text-slate-500"><?= htmlspecialchars($tx['tx_date']) ?></td>
                            <td class="py-2.5 text-sm text-slate-200 truncate max-w-[250px]"><?= htmlspecialchars($tx['tx_desc']) ?></td>
                            <td class="py-2.5 text-sm text-right font-medium <?= $tx['tx_source'] === '입금' ? 'text-amber-700' : 'text-slate-100' ?>">
                                <?= $tx['tx_source'] === '입금' ? '+' : '-' ?><?= number_format($tx['amount']) ?>원
                            </td>
                            <td class="py-2.5 text-center">
                                <span class="inline-block px-1.5 py-0.5 text-sm font-medium rounded
                                    <?= $tx['tx_source'] === '입금' ? 'bg-amber-50 text-amber-700' : ($tx['tx_source'] === '카드' ? 'bg-amber-50 text-amber-600' : 'bg-slate-800 text-slate-400') ?>">
                                    <?= htmlspecialchars($tx['tx_source']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center py-10 text-slate-500">
                <i data-lucide="receipt" class="w-10 h-10 mb-2 opacity-30"></i>
                <p class="text-sm">아직 거래 내역이 없습니다</p>
                <div class="flex gap-3 mt-3">
                    <a href="<?= $basePath ?>/pages/acct_card.php" class="text-sm text-slate-400 hover:underline">카드관리 바로가기</a>
                    <a href="<?= $basePath ?>/pages/acct_bank.php" class="text-sm text-slate-400 hover:underline">계좌관리 바로가기</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 항목별 지출 비중 -->
        <div class="col-span-1 bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h3 class="text-sm font-bold text-slate-100 mb-4">항목별 지출 비중</h3>
            <?php if (!empty($categoryBreakdown)): ?>
            <div class="flex justify-center mb-4">
                <div style="position: relative; width: 180px; height: 180px;">
                    <canvas id="donutChart"></canvas>
                </div>
            </div>
            <div class="space-y-2">
                <?php
                $donutColors = ['#4F6AFF', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280'];
                $idx = 0;
                foreach ($categoryBreakdown as $cat):
                    $pct = $donutTotal > 0 ? round($cat['total'] / $donutTotal * 100, 1) : 0;
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background: <?= $donutColors[$idx % count($donutColors)] ?>"></span>
                        <span class="text-sm text-slate-300"><?= htmlspecialchars($cat['category']) ?></span>
                    </div>
                    <span class="text-sm font-medium text-slate-400"><?= $pct ?>%</span>
                </div>
                <?php $idx++; endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center py-10 text-slate-500">
                <i data-lucide="pie-chart" class="w-10 h-10 mb-2 opacity-30"></i>
                <p class="text-sm">지출 데이터가 없습니다</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($hasData): ?>
// 월별 막대차트
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [
            {
                label: '입금',
                data: <?= $chartIncome ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderRadius: 6,
                barPercentage: 0.82,
                categoryPercentage: 0.68
            },
            {
                label: '지출',
                data: <?= $chartExpense ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderRadius: 6,
                barPercentage: 0.82,
                categoryPercentage: 0.68
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', align: 'end', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: function(ctx) { return ctx.dataset.label + ': ' + ctx.raw.toLocaleString() + '원'; }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: function(v) { return v >= 10000 ? (v / 10000) + '만' : v; }, font: { size: 11 } },
                grid: { color: getComputedStyle(document.documentElement).getPropertyValue('--zm-border').trim() + '30' }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($categoryBreakdown)): ?>
// 도넛차트
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: <?= $donutLabels ?>,
        datasets: [{
            data: <?= $donutValues ?>,
            backgroundColor: ['#4F6AFF', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) { return ctx.label + ': ' + ctx.raw.toLocaleString() + '원'; }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
