<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'acct_invoice_issue.php') {
    header('Location: acct_invoice.php?tab=issue');
    exit;
}

if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
$pdo = getDBConnection();
$hasDB = false;
if (!isset($basePath)) {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(__DIR__ . '/..');
    $basePath = ($docRoot && $projectRoot) ? rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/') : '';
}

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

$invoices = [];
$lastSyncLabel = '연동 이력 없음';
if ($hasDB) {
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM tax_invoices
            WHERE issue_date BETWEEN ? AND ?
            ORDER BY issue_date DESC, id DESC
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $log = $pdo->query("SELECT finished_at FROM hometax_sync_log ORDER BY finished_at DESC LIMIT 1")->fetch();
        if ($log && !empty($log['finished_at'])) {
            $lastSyncLabel = date('Y.m.d H:i', strtotime($log['finished_at']));
        }
    } catch (PDOException $e) {
        error_log('[Invoice Issue] DB load failed: ' . $e->getMessage());
        $hasDB = false;
    }
}

// 요약 계산
$salesInvoices = array_filter($invoices, fn($i) => $i['invoice_type'] === '매출');
$purchaseInvoices = array_filter($invoices, fn($i) => $i['invoice_type'] === '매입');
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
foreach ($invoices as $inv) {
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
$invoiceHasData = count($invoices) > 0;
$invoiceMutedClass = '';
?>

        <script>
        (function(){
            var target = document.getElementById('invoiceHeaderActions');
            if (target) target.innerHTML =
                '<span class="text-sm text-slate-500">홈택스 파일 기준 · 마지막 반영: <?= htmlspecialchars($lastSyncLabel) ?></span>' +
                '<button onclick="openInvoiceUpload()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg hover:opacity-90 transition-colors shadow-sm">' +
                    '<i data-lucide="upload" class="w-4 h-4"></i> 홈택스 파일 업로드</button>' +
                '<button onclick="syncHometax()" class="btn btn-secondary btn-sm" id="syncBtn">' +
                    '<i data-lucide="refresh-cw" class="w-4 h-4" id="syncIcon"></i> API 동기화 준비중</button>';
            if (typeof lucide !== 'undefined') lucide.createIcons({attrs:{class:'w-4 h-4'},nameAttr:'data-lucide'});
        })();
        </script>

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

        <?php if (!$invoiceHasData): ?>
        <div class="db-no-data-wrapper">
        <div class="db-no-data-hero">
            <div class="db-hero-inner">
                <div class="w-14 h-14 rounded-2xl bg-primary text-white flex items-center justify-center shadow-lg mb-4">
                    <i data-lucide="receipt" class="w-7 h-7"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2"><?= $periodLabel ?> 세금계산서 자료가 필요합니다</h3>
                <p class="text-slate-500 mb-6 max-w-md text-center">홈택스에서 내려받은 매출/매입 세금계산서 파일을 업로드하면<br>이 화면과 세무리포트가 자동으로 채워집니다.</p>
                <button onclick="openInvoiceUpload()" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-primary text-white font-bold shadow-md hover:shadow-lg hover:bg-primary/90 transition-all text-base">
                    <i data-lucide="upload" class="w-5 h-5"></i>홈택스 파일 업로드
                </button>
            </div>
        </div>
        <div class="db-no-data-behind">
        <?php endif; ?>

        <!-- 요약 카드 -->
        <div class="grid grid-cols-4 gap-4 mb-5 <?= $invoiceMutedClass ?>">
            <!-- 매출세액 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">매출세액</p>
                    <div class="w-8 h-8 rounded-full bg-primary-light flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
                    </div>
                </div>
                <?php if ($invoiceHasData): ?>
                <p class="text-2xl font-bold text-slate-100"><?= number_format($salesTax) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 · <?= count($salesInvoices) ?>건</p>
                <p class="text-sm text-slate-500 mt-0.5">공급가액 <?= number_format($salesSupply) ?>원</p>
                <?php else: ?>
                <p class="text-lg font-bold text-slate-400">데이터 없음</p>
                <p class="text-sm text-slate-500 mt-1">자료를 가져오면 계산됩니다</p>
                <?php endif; ?>
            </div>
            <!-- 매입세액 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">매입세액</p>
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                        <i data-lucide="trending-down" class="w-4 h-4 text-amber-500"></i>
                    </div>
                </div>
                <?php if ($invoiceHasData): ?>
                <p class="text-2xl font-bold text-slate-100"><?= number_format($purchaseTax) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 · <?= count($purchaseInvoices) ?>건</p>
                <p class="text-sm text-slate-500 mt-0.5">공급가액 <?= number_format($purchaseSupply) ?>원</p>
                <?php else: ?>
                <p class="text-lg font-bold text-slate-400">데이터 없음</p>
                <p class="text-sm text-slate-500 mt-1">자료를 가져오면 계산됩니다</p>
                <?php endif; ?>
            </div>
            <!-- 예상 납부/환급 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">예상 <?= $netVat >= 0 ? '납부세액' : '환급세액' ?></p>
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                        <i data-lucide="receipt" class="w-4 h-4 text-amber-500"></i>
                    </div>
                </div>
                <?php if ($invoiceHasData): ?>
                <p class="text-2xl font-bold <?= $netVat >= 0 ? 'text-amber-500' : 'text-amber-700' ?>"><?= number_format(abs($netVat)) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 <?= $netVat >= 0 ? '납부 예정' : '환급 예정' ?></p>
                <p class="text-sm text-slate-500 mt-0.5">매출세액 - 매입세액</p>
                <?php else: ?>
                <p class="text-lg font-bold text-slate-400">데이터 없음</p>
                <p class="text-sm text-slate-500 mt-1">자료를 가져오면 계산됩니다</p>
                <?php endif; ?>
            </div>
            <!-- 거래처 수 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">거래처</p>
                    <div class="w-8 h-8 rounded-full bg-primary-light flex items-center justify-center">
                        <i data-lucide="building-2" class="w-4 h-4 text-primary"></i>
                    </div>
                </div>
                <?php if ($invoiceHasData): ?>
                <p class="text-2xl font-bold text-slate-100"><?= count($partnerNames) ?></p>
                <p class="text-sm text-slate-500 mt-1">개사</p>
                <p class="text-sm text-slate-500 mt-0.5">매출 <?= count(array_unique(array_column($salesInvoices, 'buyer_name'))) ?> · 매입 <?= count(array_unique(array_column(array_values($purchaseInvoices), 'supplier_name'))) ?></p>
                <?php else: ?>
                <p class="text-lg font-bold text-slate-400">데이터 없음</p>
                <p class="text-sm text-slate-500 mt-1">자료를 가져오면 계산됩니다</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-3">
            <button class="approval-tab active" onclick="changeTab(this, 'all')">전체 <span class="tab-badge"><span class="tab-count" data-tab="all"><?= count($invoices) ?></span></span></button>
            <button class="approval-tab" onclick="changeTab(this, 'sales')">매출 세금계산서 <span class="tab-badge"><span class="tab-count" data-tab="sales"><?= count($salesInvoices) ?></span></span></button>
            <button class="approval-tab" onclick="changeTab(this, 'purchase')">매입 세금계산서 <span class="tab-badge"><span class="tab-count" data-tab="purchase"><?= count(array_values($purchaseInvoices)) ?></span></span></button>
        </div>

        <!-- 세금계산서 리스트 카드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5 <?= $invoiceMutedClass ?>">
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
                <span class="info-text">세금계산서 <strong id="totalCount"><?= count($invoices) ?></strong>건</span>
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
        <div class="grid grid-cols-2 gap-5 <?= $invoiceMutedClass ?>">
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
        <div class="mt-5 p-4 <?= $netVat >= 0 ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200' ?> border rounded-xl flex items-center justify-between <?= $invoiceMutedClass ?>">
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

<?php if (!$invoiceHasData): ?>
        </div><!-- .db-no-data-behind -->
        </div><!-- .db-no-data-wrapper -->
<?php endif; ?>

<script>
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const API_BASE = '<?= $basePath ?>/api/tax_invoice.php';
let allInvoices = <?= json_encode(array_values($invoices), JSON_UNESCAPED_UNICODE) ?>;
let filteredInvoices = [...allInvoices];
let currentPage = 1;
let currentTab = 'all';

document.addEventListener('DOMContentLoaded', () => {
    renderInvoices();
    if (new URLSearchParams(window.location.search).get('upload') === '1') {
        openInvoiceUpload();
    }
});

function openInvoiceUpload() {
    const modal = document.getElementById('uploadModal');
    if (modal) modal.classList.remove('hidden');
}

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
        if (typeof lucide !== 'undefined') lucide.createIcons();
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
    if (typeof lucide !== 'undefined') lucide.createIcons();
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
        alert('홈택스 API 자동 동기화는 아직 준비 중입니다. 지금은 홈택스에서 내려받은 엑셀/CSV 파일을 업로드해 자료를 반영해주세요.');
    }, 2000);
}

function formatDate(d) { return d ? d.replace(/-/g, '.') : '-'; }
function formatNum(n) { return (n || 0).toLocaleString('ko-KR'); }
function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<!-- 업로드 모달 -->
<div id="uploadModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/60" onclick="document.getElementById('uploadModal').classList.add('hidden')"></div>
    <div class="absolute inset-4 md:inset-10 lg:inset-16 bg-slate-950 border border-slate-800 rounded-2xl shadow-2xl overflow-y-auto">
        <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 bg-slate-950 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">홈택스 파일 업로드</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-slate-500 hover:text-slate-300 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <?php include __DIR__ . '/acct_invoice_upload.php'; ?>
        </div>
    </div>
</div>
