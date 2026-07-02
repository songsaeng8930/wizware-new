<?php
$pageTitle = '세금계산서';
$currentPage = 'tax';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM tax_invoices LIMIT 1');
        $hasDB = true;
    } catch (PDOException $e) { $hasDB = false; }
}

// 기간 파라미터
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$period = $_GET['period'] ?? 'month'; // month | quarter

if ($period === 'quarter') {
    $quarter = ceil($month / 3);
    $dateFrom = sprintf('%04d-%02d-01', $year, ($quarter - 1) * 3 + 1);
    $dateTo   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $quarter * 3)));
    $periodLabel = "{$year}년 {$quarter}분기";
} else {
    $dateFrom = sprintf('%04d-%02d-01', $year, $month);
    $dateTo   = date('Y-m-t', strtotime($dateFrom));
    $periodLabel = "{$year}년 {$month}월";
}

// 샘플 데이터 (DB 없을 때)
$sampleInvoices = [
    // 매출
    ['id'=>1,'invoice_type'=>'매출','invoice_number'=>'20260201-41000001-00000001','issue_date'=>'2026-02-01','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'234-56-78901','buyer_name'=>'(주)테크솔루션','supply_amount'=>15000000,'tax_amount'=>1500000,'total_amount'=>16500000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>2,'invoice_type'=>'매출','invoice_number'=>'20260205-41000001-00000002','issue_date'=>'2026-02-05','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'345-67-89012','buyer_name'=>'디자인웍스','supply_amount'=>8500000,'tax_amount'=>850000,'total_amount'=>9350000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>3,'invoice_type'=>'매출','invoice_number'=>'20260210-41000001-00000003','issue_date'=>'2026-02-10','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'456-78-90123','buyer_name'=>'(주)스마트커머스','supply_amount'=>3200000,'tax_amount'=>320000,'total_amount'=>3520000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>4,'invoice_type'=>'매출','invoice_number'=>'20260215-41000001-00000004','issue_date'=>'2026-02-15','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'567-89-01234','buyer_name'=>'한국데이터','supply_amount'=>12000000,'tax_amount'=>1200000,'total_amount'=>13200000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>5,'invoice_type'=>'매출','invoice_number'=>'20260218-41000001-00000005','issue_date'=>'2026-02-18','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'234-56-78901','buyer_name'=>'(주)테크솔루션','supply_amount'=>5500000,'tax_amount'=>550000,'total_amount'=>6050000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>6,'invoice_type'=>'매출','invoice_number'=>'20260220-41000001-00000006','issue_date'=>'2026-02-20','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'678-90-12345','buyer_name'=>'그린에너지(주)','supply_amount'=>2000000,'tax_amount'=>0,'total_amount'=>2000000,'tax_type'=>'영세율','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>7,'invoice_type'=>'매출','invoice_number'=>'20260225-41000001-00000007','issue_date'=>'2026-02-25','supplier_bizno'=>'123-45-67890','supplier_name'=>'주식회사 재밋','buyer_bizno'=>'789-01-23456','buyer_name'=>'(주)미래건설','supply_amount'=>9800000,'tax_amount'=>980000,'total_amount'=>10780000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    // 매입
    ['id'=>8,'invoice_type'=>'매입','invoice_number'=>'20260203-52000001-00000001','issue_date'=>'2026-02-03','supplier_bizno'=>'890-12-34567','supplier_name'=>'NHN클라우드(주)','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>4200000,'tax_amount'=>420000,'total_amount'=>4620000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>9,'invoice_type'=>'매입','invoice_number'=>'20260205-52000001-00000002','issue_date'=>'2026-02-05','supplier_bizno'=>'901-23-45678','supplier_name'=>'(주)오피스허브','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>1800000,'tax_amount'=>180000,'total_amount'=>1980000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>10,'invoice_type'=>'매입','invoice_number'=>'20260210-52000001-00000003','issue_date'=>'2026-02-10','supplier_bizno'=>'012-34-56789','supplier_name'=>'세종사무기기','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>650000,'tax_amount'=>65000,'total_amount'=>715000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>11,'invoice_type'=>'매입','invoice_number'=>'20260212-52000001-00000004','issue_date'=>'2026-02-12','supplier_bizno'=>'890-12-34567','supplier_name'=>'NHN클라우드(주)','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>3800000,'tax_amount'=>380000,'total_amount'=>4180000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>12,'invoice_type'=>'매입','invoice_number'=>'20260215-52000001-00000005','issue_date'=>'2026-02-15','supplier_bizno'=>'234-56-00001','supplier_name'=>'(주)디지털마케팅','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>3500000,'tax_amount'=>350000,'total_amount'=>3850000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>13,'invoice_type'=>'매입','invoice_number'=>'20260220-52000001-00000006','issue_date'=>'2026-02-20','supplier_bizno'=>'345-67-00002','supplier_name'=>'코리아호스팅','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>980000,'tax_amount'=>98000,'total_amount'=>1078000,'tax_type'=>'과세','invoice_status'=>'정상','hometax_sync'=>1],
    ['id'=>14,'invoice_type'=>'매입','invoice_number'=>'20260222-52000001-00000007','issue_date'=>'2026-02-22','supplier_bizno'=>'456-78-00003','supplier_name'=>'인테리어플러스','buyer_bizno'=>'123-45-67890','buyer_name'=>'주식회사 재밋','supply_amount'=>2500000,'tax_amount'=>250000,'total_amount'=>2750000,'tax_type'=>'과세','invoice_status'=>'수정','hometax_sync'=>0],
];

// 요약 계산
$salesInvoices = array_filter($sampleInvoices, fn($i) => $i['invoice_type'] === '매출');
$purchaseInvoices = array_filter($sampleInvoices, fn($i) => $i['invoice_type'] === '매입');
$salesTax = array_sum(array_column($salesInvoices, 'tax_amount'));
$purchaseTax = array_sum(array_column($purchaseInvoices, 'tax_amount'));
$salesSupply = array_sum(array_column($salesInvoices, 'supply_amount'));
$purchaseSupply = array_sum(array_column($purchaseInvoices, 'supply_amount'));
$netVat = $salesTax - $purchaseTax;
$partnerNames = array_unique(array_merge(
    array_column($salesInvoices, 'buyer_name'),
    array_column(array_values($purchaseInvoices), 'supplier_name')
));

// 거래처별 집계
$partnerAgg = [];
foreach ($sampleInvoices as $inv) {
    $pName = $inv['invoice_type'] === '매출' ? $inv['buyer_name'] : $inv['supplier_name'];
    $pBizno = $inv['invoice_type'] === '매출' ? $inv['buyer_bizno'] : $inv['supplier_bizno'];
    $key = $pName . '|' . $inv['invoice_type'];
    if (!isset($partnerAgg[$key])) {
        $partnerAgg[$key] = ['name' => $pName, 'bizno' => $pBizno, 'type' => $inv['invoice_type'], 'cnt' => 0, 'supply' => 0, 'tax' => 0];
    }
    $partnerAgg[$key]['cnt']++;
    $partnerAgg[$key]['supply'] += $inv['supply_amount'];
    $partnerAgg[$key]['tax'] += $inv['tax_amount'];
}
usort($partnerAgg, fn($a, $b) => $b['supply'] - $a['supply']);
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 헤더 -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold text-slate-100">세금계산서</h2>
                <span class="text-sm text-slate-500 font-medium bg-slate-800 px-2 py-0.5 rounded-full">홈택스 연동</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500" id="lastSyncTime">마지막 동기화: 2026.02.28 09:00</span>
                <button onclick="syncHometax()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors" id="syncBtn">
                    <i data-lucide="refresh-cw" class="w-4 h-4" id="syncIcon"></i> 홈택스 동기화
                </button>
            </div>
        </div>

        <!-- 기간 선택 필터 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-3">
            <div class="flex rounded-lg border border-slate-800 overflow-hidden">
                <?php foreach(['month'=>'월별','quarter'=>'분기별'] as $k=>$v): ?>
                <a href="?period=<?= $k ?>&year=<?= $year ?>&month=<?= $month ?>"
                   class="px-4 py-2 text-sm transition-colors <?= $period===$k ? 'bg-primary text-white' : 'text-slate-300 hover:bg-slate-950' ?>"><?= $v ?></a>
                <?php endforeach; ?>
            </div>
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm" onchange="location.href='?period=<?= $period ?>&year='+this.value+'&month=<?= $month ?>'">
                <?php for($y=2024;$y<=2026;$y++): ?>
                <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?>년</option>
                <?php endfor; ?>
            </select>
            <?php if ($period === 'month'): ?>
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm" onchange="location.href='?period=<?= $period ?>&year=<?= $year ?>&month='+this.value">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>><?= $m ?>월</option>
                <?php endfor; ?>
            </select>
            <?php else: ?>
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm" onchange="location.href='?period=quarter&year=<?= $year ?>&month='+(this.value*3)">
                <?php for($q=1;$q<=4;$q++): ?>
                <option value="<?= $q ?>" <?= ceil($month/3)===$q?'selected':'' ?>><?= $q ?>분기</option>
                <?php endfor; ?>
            </select>
            <?php endif; ?>
            <span class="ml-2 text-sm font-semibold text-slate-200"><?= $periodLabel ?> 세금계산서</span>
        </div>

        <!-- 요약 카드 -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <!-- 매출세액 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">매출세액</p>
                    <div class="w-8 h-8 rounded-full bg-primary-light flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-100"><?= number_format($salesTax) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 · <?= count($salesInvoices) ?>건</p>
                <p class="text-sm text-slate-500 mt-0.5">공급가액 <?= number_format($salesSupply) ?>원</p>
            </div>
            <!-- 매입세액 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">매입세액</p>
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                        <i data-lucide="trending-down" class="w-4 h-4 text-amber-500"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-100"><?= number_format($purchaseTax) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 · <?= count($purchaseInvoices) ?>건</p>
                <p class="text-sm text-slate-500 mt-0.5">공급가액 <?= number_format($purchaseSupply) ?>원</p>
            </div>
            <!-- 예상 납부/환급 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">예상 <?= $netVat >= 0 ? '납부세액' : '환급세액' ?></p>
                    <div class="w-8 h-8 rounded-full <?= $netVat >= 0 ? 'bg-amber-100' : 'bg-emerald-100' ?> flex items-center justify-center">
                        <i data-lucide="receipt" class="w-4 h-4 <?= $netVat >= 0 ? 'text-amber-500' : 'text-emerald-500' ?>"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold <?= $netVat >= 0 ? 'text-amber-500' : 'text-emerald-500' ?>"><?= number_format(abs($netVat)) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 <?= $netVat >= 0 ? '납부 예정' : '환급 예정' ?></p>
                <p class="text-sm text-slate-500 mt-0.5">매출세액 - 매입세액</p>
            </div>
            <!-- 거래처 수 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">거래처</p>
                    <div class="w-8 h-8 rounded-full bg-primary-light flex items-center justify-center">
                        <i data-lucide="building-2" class="w-4 h-4 text-primary"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-100"><?= count($partnerNames) ?></p>
                <p class="text-sm text-slate-500 mt-1">개사</p>
                <p class="text-sm text-slate-500 mt-0.5">매출 <?= count(array_unique(array_column($salesInvoices, 'buyer_name'))) ?> · 매입 <?= count(array_unique(array_column(array_values($purchaseInvoices), 'supplier_name'))) ?></p>
            </div>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-3">
            <button class="approval-tab active" onclick="changeTab(this, 'all')">전체 <span class="tab-badge"><span class="tab-count" data-tab="all"><?= count($sampleInvoices) ?></span></span></button>
            <button class="approval-tab" onclick="changeTab(this, 'sales')">매출 세금계산서 <span class="tab-badge"><span class="tab-count" data-tab="sales"><?= count($salesInvoices) ?></span></span></button>
            <button class="approval-tab" onclick="changeTab(this, 'purchase')">매입 세금계산서 <span class="tab-badge"><span class="tab-count" data-tab="purchase"><?= count(array_values($purchaseInvoices)) ?></span></span></button>
        </div>

        <!-- 세금계산서 리스트 카드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <!-- 검색 필터 -->
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950/50">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 flex-1">
                        <label class="text-sm font-medium text-slate-400 shrink-0">거래처</label>
                        <input type="text" id="filterPartner" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm flex-1 outline-none focus:border-gray-300 transition-colors" placeholder="거래처명 검색">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-slate-400 shrink-0">사업자번호</label>
                        <input type="text" id="filterBizno" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm w-40 outline-none focus:border-gray-300 transition-colors" placeholder="000-00-00000">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-slate-400 shrink-0">상태</label>
                        <select id="filterStatus" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-gray-300 transition-colors">
                            <option value="">전체</option>
                            <option value="정상">정상</option>
                            <option value="수정">수정</option>
                            <option value="취소">취소</option>
                        </select>
                    </div>
                    <button onclick="searchInvoices()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
                    </button>
                    <button onclick="resetFilters()" class="btn btn-secondary btn-sm">
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> 초기화
                    </button>
                </div>
            </div>

            <!-- 리스트 정보바 -->
            <div class="list-info-bar">
                <span class="info-text">세금계산서 <strong id="totalCount"><?= count($sampleInvoices) ?></strong>건</span>
                <select id="perPageSelect" class="list-per-page" onchange="renderInvoices()">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>

            <!-- 테이블 -->
            <div class="overflow-x-auto">
                <table class="w-full emp-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-center w-16">구분</th>
                            <th class="px-4 py-3 text-center">작성일자</th>
                            <th class="px-4 py-3 text-center">거래처</th>
                            <th class="px-4 py-3 text-center">사업자번호</th>
                            <th class="px-4 py-3 text-right">공급가액</th>
                            <th class="px-4 py-3 text-right">세액</th>
                            <th class="px-4 py-3 text-right">합계</th>
                            <th class="px-4 py-3 text-center">과세유형</th>
                            <th class="px-4 py-3 text-center">상태</th>
                            <th class="px-4 py-3 text-center w-10">동기화</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>

        <!-- 거래처별 집계 -->
        <div class="grid grid-cols-2 gap-5">
            <!-- 매출 거래처 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
                    <i data-lucide="building-2" class="w-4 h-4 text-primary"></i>
                    매출 거래처별 집계
                </h3>
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-2 px-3 text-left font-medium text-slate-300">거래처</th>
                            <th class="py-2 px-3 text-center font-medium text-slate-300">건수</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">공급가액</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">세액</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $salesPartners = array_filter($partnerAgg, fn($p) => $p['type'] === '매출');
                        $spSupplyTotal = 0; $spTaxTotal = 0; $spCntTotal = 0;
                        foreach ($salesPartners as $p):
                            $spSupplyTotal += $p['supply'];
                            $spTaxTotal += $p['tax'];
                            $spCntTotal += $p['cnt'];
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-950">
                            <td class="py-2.5 px-3 text-slate-200">
                                <div><?= htmlspecialchars($p['name']) ?></div>
                                <div class="text-sm text-slate-500"><?= htmlspecialchars($p['bizno']) ?></div>
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-300 tabular-nums"><?= $p['cnt'] ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums"><?= number_format($p['supply']) ?></td>
                            <td class="py-2.5 px-3 text-right text-primary tabular-nums"><?= number_format($p['tax']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="border-t-2 border-slate-700 bg-slate-950">
                            <td class="py-2.5 px-3 font-semibold text-slate-100">합계</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-300 tabular-nums"><?= $spCntTotal ?></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-100 tabular-nums"><?= number_format($spSupplyTotal) ?></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-primary tabular-nums"><?= number_format($spTaxTotal) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 매입 거래처 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
                    <i data-lucide="building-2" class="w-4 h-4 text-amber-500"></i>
                    매입 거래처별 집계
                </h3>
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-2 px-3 text-left font-medium text-slate-300">거래처</th>
                            <th class="py-2 px-3 text-center font-medium text-slate-300">건수</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">공급가액</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">세액</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $purchasePartners = array_filter($partnerAgg, fn($p) => $p['type'] === '매입');
                        $ppSupplyTotal = 0; $ppTaxTotal = 0; $ppCntTotal = 0;
                        foreach ($purchasePartners as $p):
                            $ppSupplyTotal += $p['supply'];
                            $ppTaxTotal += $p['tax'];
                            $ppCntTotal += $p['cnt'];
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-950">
                            <td class="py-2.5 px-3 text-slate-200">
                                <div><?= htmlspecialchars($p['name']) ?></div>
                                <div class="text-sm text-slate-500"><?= htmlspecialchars($p['bizno']) ?></div>
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-300 tabular-nums"><?= $p['cnt'] ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums"><?= number_format($p['supply']) ?></td>
                            <td class="py-2.5 px-3 text-right text-amber-700 tabular-nums"><?= number_format($p['tax']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="border-t-2 border-slate-700 bg-slate-950">
                            <td class="py-2.5 px-3 font-semibold text-slate-100">합계</td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-300 tabular-nums"><?= $ppCntTotal ?></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-100 tabular-nums"><?= number_format($ppSupplyTotal) ?></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-amber-700 tabular-nums"><?= number_format($ppTaxTotal) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 예상 납부세액 요약 박스 -->
        <div class="mt-5 p-4 <?= $netVat >= 0 ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200' ?> border rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full <?= $netVat >= 0 ? 'bg-amber-100' : 'bg-emerald-100' ?> flex items-center justify-center">
                    <i data-lucide="calculator" class="w-5 h-5 <?= $netVat >= 0 ? 'text-amber-500' : 'text-emerald-500' ?>"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold <?= $netVat >= 0 ? 'text-amber-700' : 'text-emerald-700' ?>">
                        <?= $periodLabel ?> 부가세 <?= $netVat >= 0 ? '예상 납부세액' : '예상 환급세액' ?>
                    </p>
                    <p class="text-sm text-slate-400 mt-0.5">매출세액 <?= number_format($salesTax) ?>원 - 매입세액 <?= number_format($purchaseTax) ?>원</p>
                </div>
            </div>
            <p class="text-2xl font-bold <?= $netVat >= 0 ? 'text-amber-700' : 'text-emerald-700' ?>"><?= number_format(abs($netVat)) ?>원</p>
        </div>

    </main>
</div>

<script>
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const API_BASE = '<?= $basePath ?>/api/tax_invoice.php';
let allInvoices = <?= json_encode(array_values($sampleInvoices), JSON_UNESCAPED_UNICODE) ?>;
let filteredInvoices = [...allInvoices];
let currentPage = 1;
let currentTab = 'all';

document.addEventListener('DOMContentLoaded', () => {
    renderInvoices();
});

function changeTab(el, tab) {
    document.querySelectorAll('.approval-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    currentTab = tab;
    searchInvoices();
}

function searchInvoices() {
    const partner = document.getElementById('filterPartner').value.toLowerCase();
    const bizno = document.getElementById('filterBizno').value.replace(/-/g, '');
    const status = document.getElementById('filterStatus').value;

    filteredInvoices = allInvoices.filter(inv => {
        // 탭 필터
        if (currentTab === 'sales' && inv.invoice_type !== '매출') return false;
        if (currentTab === 'purchase' && inv.invoice_type !== '매입') return false;

        // 거래처 검색
        if (partner) {
            const partnerName = inv.invoice_type === '매출' ? inv.buyer_name : inv.supplier_name;
            if (!partnerName.toLowerCase().includes(partner)) return false;
        }

        // 사업자번호 검색
        if (bizno) {
            const b1 = inv.supplier_bizno.replace(/-/g, '');
            const b2 = inv.buyer_bizno.replace(/-/g, '');
            if (!b1.includes(bizno) && !b2.includes(bizno)) return false;
        }

        // 상태 필터
        if (status && inv.invoice_status !== status) return false;

        return true;
    });

    currentPage = 1;
    renderInvoices();
}

function renderInvoices() {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const tbody = document.getElementById('invoiceTableBody');
    document.getElementById('totalCount').textContent = filteredInvoices.length;

    const start = (currentPage - 1) * perPage;
    const pageData = filteredInvoices.slice(start, start + perPage);

    if (!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-16 text-slate-500 text-sm">조회된 세금계산서가 없습니다</td></tr>';
        document.getElementById('pagination').innerHTML = '';
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = pageData.map(inv => {
        const isS = inv.invoice_type === '매출';
        const partnerName = isS ? inv.buyer_name : inv.supplier_name;
        const partnerBizno = isS ? inv.buyer_bizno : inv.supplier_bizno;
        const typeBadge = isS
            ? '<span class="inline-flex items-center px-2 py-0.5 text-sm font-medium rounded-full bg-primary-light text-primary whitespace-nowrap">매출</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-700 whitespace-nowrap">매입</span>';
        const statusClass = inv.invoice_status === '정상' ? 'bg-slate-800 text-slate-300'
            : inv.invoice_status === '수정' ? 'bg-amber-50 text-amber-700'
            : 'bg-amber-50 text-amber-500';
        const taxTypeCls = inv.tax_type === '영세율' ? 'text-primary' : inv.tax_type === '면세' ? 'text-amber-500' : 'text-slate-300';
        const syncIcon = inv.hometax_sync
            ? '<i data-lucide="check-circle-2" class="w-4 h-4 text-amber-500"></i>'
            : '<i data-lucide="circle-dashed" class="w-4 h-4 text-slate-600"></i>';

        return '<tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">' +
            '<td class="px-4 py-3 text-center">' + typeBadge + '</td>' +
            '<td class="px-4 py-3 text-center text-sm text-slate-300 tabular-nums">' + formatDate(inv.issue_date) + '</td>' +
            '<td class="px-4 py-3 text-sm text-slate-100 font-medium text-center">' + esc(partnerName) + '</td>' +
            '<td class="px-4 py-3 text-sm text-slate-400 tabular-nums text-center">' + esc(partnerBizno) + '</td>' +
            '<td class="px-4 py-3 text-sm text-right text-slate-200 tabular-nums">' + formatNum(inv.supply_amount) + '</td>' +
            '<td class="px-4 py-3 text-sm text-right ' + (isS ? 'text-primary' : 'text-amber-700') + ' tabular-nums font-medium">' + formatNum(inv.tax_amount) + '</td>' +
            '<td class="px-4 py-3 text-sm text-right text-slate-100 font-semibold tabular-nums">' + formatNum(inv.total_amount) + '</td>' +
            '<td class="px-4 py-3 text-center text-sm ' + taxTypeCls + '">' + esc(inv.tax_type) + '</td>' +
            '<td class="px-4 py-3 text-center"><span class="px-2 py-0.5 text-sm font-medium rounded-full whitespace-nowrap ' + statusClass + '">' + esc(inv.invoice_status) + '</span></td>' +
            '<td class="px-4 py-3 text-center">' + syncIcon + '</td>' +
            '</tr>';
    }).join('');

    renderPagination(filteredInvoices.length, perPage);
    lucide.createIcons();
}

function renderPagination(total, perPage) {
    const pages = Math.ceil(total / perPage);
    const el = document.getElementById('pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }

    let h = '';
    h += '<button class="pg-btn ' + (currentPage <= 1 ? 'pg-disabled' : '') + '" onclick="goPage(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>';
    h += '<button class="pg-btn ' + (currentPage <= 1 ? 'pg-disabled' : '') + '" onclick="goPage(' + (currentPage-1) + ')"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>';
    for (let i = 1; i <= pages; i++) {
        if (pages > 7 && Math.abs(i - currentPage) > 2 && i > 2 && i < pages - 1) {
            if (i === 3 || i === pages - 2) h += '<span class="px-1 text-slate-500">...</span>';
            continue;
        }
        h += '<button class="pg-btn ' + (i === currentPage ? 'pg-active' : '') + '" onclick="goPage(' + i + ')">' + i + '</button>';
    }
    h += '<button class="pg-btn ' + (currentPage >= pages ? 'pg-disabled' : '') + '" onclick="goPage(' + (currentPage+1) + ')"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>';
    h += '<button class="pg-btn ' + (currentPage >= pages ? 'pg-disabled' : '') + '" onclick="goPage(' + pages + ')"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>';
    el.innerHTML = h;
}

function goPage(p) {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const pages = Math.ceil(filteredInvoices.length / perPage);
    if (p < 1 || p > pages) return;
    currentPage = p;
    renderInvoices();
}

function resetFilters() {
    document.getElementById('filterPartner').value = '';
    document.getElementById('filterBizno').value = '';
    document.getElementById('filterStatus').value = '';
    searchInvoices();
}

function syncHometax() {
    const btn = document.getElementById('syncBtn');
    const icon = document.getElementById('syncIcon');
    btn.disabled = true;
    btn.classList.add('opacity-60');
    icon.style.animation = 'spin 1s linear infinite';

    setTimeout(() => {
        btn.disabled = false;
        btn.classList.remove('opacity-60');
        icon.style.animation = '';
        document.getElementById('lastSyncTime').textContent = '마지막 동기화: ' + new Date().toLocaleString('ko-KR', {year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'}).replace(/\./g, '.').replace(',', '');
        alert('홈택스 동기화가 완료되었습니다.\n매출 7건, 매입 7건 동기화됨');
    }, 2000);
}

function formatDate(d) { return d ? d.replace(/-/g, '.') : '-'; }
function formatNum(n) { return (n || 0).toLocaleString('ko-KR'); }
function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
