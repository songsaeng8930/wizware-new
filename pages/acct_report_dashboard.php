<?php
/**
 * 세무리포트 > 대시보드 탭 콘텐츠
 * 포함: acct_report.php 쉘에서 include
 * 데이터: bank_transactions, tax_invoices, closing_locks 실 데이터
 */
$pdo = getDBConnection();
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

$monthlyBank = [];
try {
    $rows = $pdo->query("
        SELECT YEAR(transaction_date) as y, MONTH(transaction_date) as m,
               SUM(CASE WHEN tx_type='입금' THEN amount ELSE 0 END) as rev,
               SUM(CASE WHEN tx_type='출금' THEN amount ELSE 0 END) as cost
        FROM bank_transactions
        GROUP BY YEAR(transaction_date), MONTH(transaction_date)
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $monthlyBank[(int)$r['y']][(int)$r['m']] = [
            'rev' => (float)$r['rev'],
            'cost' => (float)$r['cost'],
        ];
    }
} catch (\Throwable $e) {
    error_log('dashboard: bank query failed: ' . $e->getMessage());
}

$monthlyVat = [];
try {
    $rows = $pdo->query("
        SELECT YEAR(issue_date) as y, MONTH(issue_date) as m,
               SUM(CASE WHEN invoice_type='매출' THEN tax_amount ELSE 0 END) as sales_vat,
               SUM(CASE WHEN invoice_type='매입' THEN tax_amount ELSE 0 END) as purchase_vat,
               SUM(CASE WHEN invoice_type='매출' THEN supply_amount ELSE 0 END) as sales_supply,
               SUM(CASE WHEN invoice_type='매입' THEN supply_amount ELSE 0 END) as purchase_supply
        FROM tax_invoices
        WHERE invoice_status != '취소'
        GROUP BY YEAR(issue_date), MONTH(issue_date)
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $monthlyVat[(int)$r['y']][(int)$r['m']] = [
            'sales_vat'      => (float)$r['sales_vat'],
            'purchase_vat'   => (float)$r['purchase_vat'],
            'net_vat'        => (float)$r['sales_vat'] - (float)$r['purchase_vat'],
            'sales_supply'   => (float)$r['sales_supply'],
            'purchase_supply'=> (float)$r['purchase_supply'],
        ];
    }
} catch (\Throwable $e) {
    error_log('dashboard: vat query failed: ' . $e->getMessage());
}

$dbConfirmed = [];
try {
    $rows = $pdo->query("
        SELECT year, MAX(month) as last_month
        FROM closing_locks WHERE is_locked = 1
        GROUP BY year
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $dbConfirmed[(int)$r['year']] = (int)$r['last_month'];
    }
} catch (\Throwable $e) {
    // closing_locks 테이블 없을 수 있음
}

$bankYears = [];
foreach ($monthlyBank as $y => $months) {
    foreach ($months as $values) {
        if (($values['rev'] ?? 0) + ($values['cost'] ?? 0) > 0) {
            $bankYears[(int)$y] = true;
            break;
        }
    }
}
$vatYears = [];
foreach ($monthlyVat as $y => $months) {
    foreach ($months as $vatData) {
        if (($vatData['sales_vat'] ?? 0) + ($vatData['purchase_vat'] ?? 0) > 0) {
            $vatYears[(int)$y] = true;
            break;
        }
    }
}
$dataYears = $bankYears + $vatYears;
$displayYears = range($currentYear - 2, $currentYear);
$allYears = array_unique(array_merge(array_keys($dataYears), $displayYears));
if (empty($allYears)) {
    $allYears = [$currentYear];
}
sort($allYears);

$dbRaw = new stdClass();
foreach ($allYears as $y) {
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $rev  = ($monthlyBank[$y][$m]['rev']  ?? 0) / 1000000;
        $cost = ($monthlyBank[$y][$m]['cost'] ?? 0) / 1000000;
        $vatData = $monthlyVat[$y][$m] ?? [];
        $netVat  = ($vatData['net_vat'] ?? 0) / 1000000;
        $salesVat    = ($vatData['sales_vat'] ?? 0) / 1000000;
        $purchaseVat = ($vatData['purchase_vat'] ?? 0) / 1000000;
        $months[] = [
            'rev'         => round($rev, 2),
            'cost'        => round($cost, 2),
            'vat'         => round($netVat, 2),
            'salesVat'    => round($salesVat, 2),
            'purchaseVat' => round($purchaseVat, 2),
            'whTax'       => 0,
            'corpTax'     => 0,
        ];
    }
    $dbRaw->$y = $months;
}

foreach ($allYears as $y) {
    if (!isset($dbConfirmed[$y])) {
        $lastDataMonth = 0;
        for ($m = 12; $m >= 1; $m--) {
            if (isset($monthlyBank[$y][$m]) && $monthlyBank[$y][$m]['rev'] + $monthlyBank[$y][$m]['cost'] > 0) {
                $lastDataMonth = $m;
                break;
            }
        }
        if ($lastDataMonth > 0) $dbConfirmed[$y] = $lastDataMonth;
    }
}

$defaultYear = max($allYears);
$availableYears = array_reverse($allYears);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- 필터 바 -->
<div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-3">
    <div class="flex rounded-lg overflow-hidden border border-slate-700" id="dbPeriodGroup">
        <button class="px-3 py-2 text-sm font-medium bg-primary text-white" data-period="month" onclick="dbSetPeriod('month')">월별</button>
        <button class="px-3 py-2 text-sm font-medium bg-slate-800 text-slate-400 hover:bg-slate-700" data-period="quarter" onclick="dbSetPeriod('quarter')">분기</button>
        <button class="px-3 py-2 text-sm font-medium bg-slate-800 text-slate-400 hover:bg-slate-700" data-period="half" onclick="dbSetPeriod('half')">반기</button>
        <button class="px-3 py-2 text-sm font-medium bg-slate-800 text-slate-400 hover:bg-slate-700" data-period="year" onclick="dbSetPeriod('year')">연간</button>
    </div>
    <select id="dbYearSelect" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="dbRender()">
        <?php foreach ($availableYears as $y): ?>
        <option value="<?= $y ?>" <?= $y === $defaultYear ? 'selected' : '' ?>><?= $y ?>년<?= isset($dataYears[$y]) ? '' : ' (자료 필요)' ?></option>
        <?php endforeach; ?>
    </select>
    <select id="dbSubSelect" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="dbRender()"></select>
    <button id="dbYtdBtn" onclick="dbToggleYtd()" class="px-3 py-2 text-sm font-medium rounded-lg bg-primary/10 text-primary hover:bg-gray-100">당기누적</button>
    <span class="ml-2 text-sm font-medium text-slate-200" id="dbPeriodLabel"></span>
    <span class="text-xs text-slate-500" id="dbDateRange"></span>
    <div class="ml-auto flex items-center gap-2">
        <button onclick="BmsExport.print()" class="btn btn-secondary" title="브라우저 인쇄 대화창에서 'PDF로 저장' 선택">
            <i data-lucide="file-text" class="w-4 h-4"></i>PDF 출력
        </button>
        <button onclick="dbExportCsv()" class="btn btn-secondary">
            <i data-lucide="download" class="w-4 h-4"></i>엑셀 내보내기
        </button>
    </div>
</div>

<!-- 대시보드 콘텐츠 (JS 렌더) -->
<div id="dbContent"></div>

<script>
/* ══════════════════════════════════
   DATA LAYER (실 데이터)
══════════════════════════════════ */
const DB_RAW = <?= json_encode($dbRaw, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE) ?>;
const DB_CONFIRMED_UNTIL = <?= json_encode((object)$dbConfirmed, JSON_NUMERIC_CHECK) ?>;
const DB_DATA_YEARS = <?= json_encode(array_map('intval', array_keys($dataYears)), JSON_NUMERIC_CHECK) ?>;
const DB_BANK_YEARS = <?= json_encode(array_map('intval', array_keys($bankYears)), JSON_NUMERIC_CHECK) ?>;
const DB_VAT_YEARS = <?= json_encode(array_map('intval', array_keys($vatYears)), JSON_NUMERIC_CHECK) ?>;

const DB_MONTH_NAMES = ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'];
const DB_QUARTER_NAMES = ['1분기','2분기','3분기','4분기'];
const DB_HALF_NAMES = ['상반기','하반기'];

let dbPeriod = 'month';
let dbYear = <?= $defaultYear ?>;
let dbSub = <?= $currentMonth ?>;
let dbYtdMode = false;
let dbViewMode = 'monthly';
let dbBarChartInstance = null;
let dbLineChartInstance = null;
let dbForecastChartInstance = null;

const DB_HAS_DATA = Object.keys(DB_RAW).some(y => DB_RAW[y].some(m => m.rev > 0 || m.cost > 0));

/* ── DATA HELPERS ── */
const ZERO_MONTH = {rev:0,cost:0,vat:0,salesVat:0,purchaseVat:0,whTax:0,corpTax:0};
function dbGetMonthsRange(year, months) {
  const d = DB_RAW[year];
  if (!d) return months.map(() => ({...ZERO_MONTH}));
  return months.map(m => d[m-1] || {...ZERO_MONTH});
}
function dbSumFields(arr) {
  return arr.reduce((a, d) => { a.rev+=d.rev; a.cost+=d.cost; a.vat+=d.vat; a.salesVat+=(d.salesVat||0); a.purchaseVat+=(d.purchaseVat||0); a.whTax+=d.whTax; a.corpTax+=d.corpTax; return a; }, {...ZERO_MONTH});
}
function dbProfit(d) { return d.rev - d.cost; }
function dbProfitRate(d) { return d.rev > 0 ? ((d.rev - d.cost) / d.rev * 100).toFixed(1) : '0.0'; }
function dbPctChg(cur, prev) { return prev ? Math.abs((cur - prev) / prev * 100).toFixed(1) : '0.0'; }

function dbGetRange(year, period, sub) {
  if (period === 'month')   return [sub];
  if (period === 'quarter') return [sub*3-2, sub*3-1, sub*3];
  if (period === 'half')    return sub === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
  return [1,2,3,4,5,6,7,8,9,10,11,12];
}
function dbGetYtdRange(year, period, sub) {
  const range = dbGetRange(year, period, sub);
  let endMonth = Math.max(...range);
  const now = new Date();
  if (year === now.getFullYear()) {
    endMonth = Math.min(endMonth, now.getMonth() + 1);
  }
  return Array.from({length: endMonth}, (_, i) => i + 1);
}
function dbGetCurrentData() {
  const months = dbYtdMode ? dbGetYtdRange(dbYear, dbPeriod, dbSub) : dbGetRange(dbYear, dbPeriod, dbSub);
  return dbSumFields(dbGetMonthsRange(dbYear, months));
}
function dbGetPrevYearData() {
  const py = dbYear-1;
  if (!DB_RAW[py]) return null;
  const months = dbYtdMode ? dbGetYtdRange(dbYear, dbPeriod, dbSub) : dbGetRange(dbYear, dbPeriod, dbSub);
  return dbSumFields(dbGetMonthsRange(py, months));
}
function dbHasPeriodData(d) { return !!d && (d.rev > 0 || d.cost > 0 || d.vat !== 0 || (d.salesVat||0) > 0 || (d.purchaseVat||0) > 0 || d.whTax > 0 || d.corpTax > 0); }
function dbHasYearData(year) { return DB_DATA_YEARS.includes(parseInt(year)); }
function dbIsConfirmed(year, month) { const c = DB_CONFIRMED_UNTIL[year]; return !c || month <= c; }
function dbIsPeriodConfirmed() { const m = dbGetRange(dbYear, dbPeriod, dbSub); return m[m.length-1] <= (DB_CONFIRMED_UNTIL[dbYear]||0); }

function dbGetForecastData() {
  const year = dbYear, confirmed = DB_CONFIRMED_UNTIL[year] || 0;
  if (confirmed >= 12 || confirmed === 0) return null;
  const cMonths = Array.from({length: confirmed}, (_, i) => i+1);
  const rMonths = Array.from({length: 12-confirmed}, (_, i) => confirmed+i+1);
  const confirmedData = cMonths.length > 0 ? dbSumFields(dbGetMonthsRange(year, cMonths)) : {...ZERO_MONTH};
  const forecastRemain = dbSumFields(dbGetMonthsRange(year, rMonths));
  const total = Object.fromEntries(Object.keys(ZERO_MONTH).map(k => [k, confirmedData[k]+forecastRemain[k]]));
  const py = year-1, prevTotal = DB_RAW[py] ? dbSumFields(DB_RAW[py]) : null;
  const growthRate = prevTotal && prevTotal.rev > 0 ? ((total.rev - prevTotal.rev) / prevTotal.rev * 100).toFixed(1) : null;
  return { confirmedData, forecastRemain, total, prevTotal, growthRate, confirmed, year };
}

/* ── AGGREGATE HELPERS ── */
function dbAggregateQuarters(yearData) {
  return [0,1,2,3].map(q => {
    const months = [q*3, q*3+1, q*3+2];
    const agg = {...ZERO_MONTH};
    months.forEach(mi => { const d = yearData[mi]; if(d){agg.rev+=d.rev;agg.cost+=d.cost;agg.vat+=d.vat;agg.salesVat+=(d.salesVat||0);agg.purchaseVat+=(d.purchaseVat||0);} });
    return agg;
  });
}
function dbAggregateHalves(yearData) {
  return [0,1].map(h => {
    const start = h*6;
    const agg = {...ZERO_MONTH};
    for(let i=start;i<start+6;i++){const d=yearData[i];if(d){agg.rev+=d.rev;agg.cost+=d.cost;agg.vat+=d.vat;agg.salesVat+=(d.salesVat||0);agg.purchaseVat+=(d.purchaseVat||0);}}
    return agg;
  });
}

/* ── FORMAT HELPERS ── */
function dbFmtM(val) {
  if (val === 0) return '0';
  if (Math.abs(val) >= 1000) return (val/1000).toFixed(1)+'억';
  if (Math.abs(val) >= 1) return Math.round(val)+'백만';
  return '';
}
function dbFmtMwon(val) {
  if (val === 0) return '0원';
  return Math.abs(val) >= 1000 ? (val/1000).toFixed(2)+'억원' : val.toFixed(0)+'백만원';
}
function dbCmpBadge(cur, prev) {
  if (!prev || prev === 0) return '';
  const pct = ((cur-prev)/Math.abs(prev)*100).toFixed(1);
  const up = pct >= 0;
  return `<span class="inline-flex items-center gap-0.5 text-sm font-bold ${up ? 'text-emerald-600':'text-rose-500'}">${up?'▲':'▼'} ${up?'+':''}${pct}%</span>`;
}
function dbDiffStr(cur, prev) {
  if (!prev) return '';
  const d = cur - prev;
  return `<span class="text-sm ${d>=0?'text-emerald-600':'text-rose-500'}">${d>=0?'+':''}${dbFmtMwon(d)}</span>`;
}

/* ── SUB SELECT ── */
function dbPopulateSub() {
  const sel = document.getElementById('dbSubSelect');
  sel.innerHTML = '';
  const names = dbPeriod==='month' ? DB_MONTH_NAMES : dbPeriod==='quarter' ? DB_QUARTER_NAMES : dbPeriod==='half' ? DB_HALF_NAMES : [];
  if (!names.length) { sel.style.display = 'none'; return; }
  sel.style.display = '';
  names.forEach((n,i) => { const o = document.createElement('option'); o.value=i+1; o.textContent=n; if(i+1===dbSub) o.selected=true; sel.appendChild(o); });
}
function dbGetPeriodLabel() {
  let label;
  if (dbPeriod==='month')   label = `${dbYear}년 ${DB_MONTH_NAMES[dbSub-1]}`;
  else if (dbPeriod==='quarter') label = `${dbYear}년 ${DB_QUARTER_NAMES[dbSub-1]}`;
  else if (dbPeriod==='half')    label = `${dbYear}년 ${DB_HALF_NAMES[dbSub-1]}`;
  else label = `${dbYear}년 연간`;
  if (dbYtdMode) {
    const ytdMonths = dbGetYtdRange(dbYear, dbPeriod, dbSub);
    const endMonth = Math.max(...ytdMonths);
    label = `${dbYear}년 당기누적 (1~${endMonth}월)`;
  }
  return label;
}
function dbGetDateRangeText() {
  const months = dbYtdMode ? dbGetYtdRange(dbYear, dbPeriod, dbSub) : dbGetRange(dbYear, dbPeriod, dbSub);
  const startM = Math.min(...months), endM = Math.max(...months);
  const pad = n => String(n).padStart(2,'0');
  const now = new Date();
  const endDay = (dbYtdMode && dbYear === now.getFullYear() && endM === now.getMonth() + 1)
    ? now.getDate()
    : new Date(dbYear, endM, 0).getDate();
  return `${dbYear}.${pad(startM)}.01 ~ ${dbYear}.${pad(endM)}.${pad(endDay)}`;
}
function dbToggleYtd() {
  dbYtdMode = !dbYtdMode;
  const btn = document.getElementById('dbYtdBtn');
  btn.className = `px-3 py-2 text-sm font-medium rounded-lg ${dbYtdMode ? 'bg-primary text-white' : 'bg-primary/10 text-primary hover:bg-gray-100'}`;
  dbRender();
}

/* ══════════════════════════════════
   MAIN RENDER
══════════════════════════════════ */
function dbRender() {
  dbYear = parseInt(document.getElementById('dbYearSelect').value);
  dbSub = parseInt(document.getElementById('dbSubSelect').value) || 1;
  if (dbPeriod === 'year') dbSub = 0;
  dbPopulateSub();
  dbSub = parseInt(document.getElementById('dbSubSelect').value) || 1;
  if (dbPeriod === 'year') dbSub = 0;
  document.getElementById('dbPeriodLabel').textContent = dbGetPeriodLabel();
  document.getElementById('dbDateRange').textContent = dbGetDateRangeText();

  if (!DB_HAS_DATA) {
    document.getElementById('dbContent').innerHTML = dbBuildEmptyState();
    if (typeof lucide !== 'undefined') lucide.createIcons();
    return;
  }

  const cur = dbGetCurrentData(), prev = dbGetPrevYearData(), confirmed = dbIsPeriodConfirmed();
  document.getElementById('dbContent').innerHTML = dbBuildHTML(cur, prev, confirmed);

  if (typeof lucide !== 'undefined') lucide.createIcons();
  setTimeout(() => { dbBuildMainCharts(cur, prev); dbBuildForecastChart(); }, 50);

  if (window.AcctReportPeriod) AcctReportPeriod.save(dbPeriod, dbYear, dbSub, dbYtdMode ? 1 : 0);
}

function dbBuildEmptyState() {
  return `
  <div class="bg-gradient-to-r from-primary/10 via-white to-amber-50 rounded-2xl border border-primary/20 p-10 text-center shadow-sm">
    <span class="w-12 h-12 rounded-xl bg-primary text-white inline-flex items-center justify-center mb-4"><i data-lucide="download-cloud" class="w-6 h-6"></i></span>
    <h3 class="text-lg font-bold text-slate-900 mb-2">세무자료를 먼저 가져와야 합니다</h3>
    <p class="text-sm text-slate-600 mb-5">통장 거래내역이나 세금계산서를 가져오면 연도별 리포트가 자동으로 채워집니다.</p>
    <div class="flex flex-wrap justify-center gap-2">
      <a href="acct_bank.php?tab=history" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white font-bold shadow-sm hover:bg-primary/90 transition-colors"><i data-lucide="download" class="w-4 h-4"></i>자료 가져오기</a>
      <a href="acct_invoice.php?tab=issue" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition-colors"><i data-lucide="receipt" class="w-4 h-4"></i>세금계산서 등록</a>
    </div>
  </div>`;
}

/* ══════════════════════════════════
   HTML BUILDER
══════════════════════════════════ */
function dbBuildHTML(cur, prev, confirmed) {
  const pnl = dbProfit(cur), prevPnl = prev ? dbProfit(prev) : null;
  const pRate = dbProfitRate(cur);
  const curHasData = dbHasPeriodData(cur);
  const yearHasData = dbHasYearData(dbYear);
  const mutedClass = curHasData ? '' : 'db-no-data-muted';
  const statusBadge = confirmed
    ? '<span class="px-1.5 py-0.5 text-sm rounded bg-amber-50 text-amber-700 font-medium">확정</span>'
    : '<span class="px-1.5 py-0.5 text-sm rounded bg-amber-50 text-amber-600 font-medium">예측 포함</span>';

  const kpiCards = [
    { label:'매출액', value: dbFmtM(cur.rev), icon:'trending-up', iconBg:'bg-primary/10', iconColor:'text-primary', valueColor:'text-primary',
      change: prev?.rev ? dbCmpBadge(cur.rev, prev.rev) : '', sub:`전년 ${prev?dbFmtM(prev.rev):'0'}`, diff: dbDiffStr(cur.rev, prev?.rev) },
    { label:'비용', value: dbFmtM(cur.cost), icon:'trending-down', iconBg:'bg-amber-50', iconColor:'text-amber-500', valueColor:'text-amber-500',
      change: prev?.cost ? dbCmpBadge(cur.cost, prev.cost) : '', sub:`전년 ${prev?dbFmtM(prev.cost):'0'}` },
    { label:'순이익', value: dbFmtM(pnl), icon:'wallet', iconBg:'bg-amber-50', iconColor:'text-amber-500', valueColor:'text-amber-700',
      change: prevPnl ? dbCmpBadge(pnl, prevPnl) : '', sub:`전년 ${prevPnl?dbFmtM(prevPnl):'0'}`, diff: dbDiffStr(pnl, prevPnl) },
    { label:'순이익률', value: pRate+'%', icon:'percent', iconBg:'bg-amber-50', iconColor:'text-amber-500', valueColor:'text-amber-600',
      change: prev ? `<span class="inline-flex items-center gap-0.5 text-sm font-bold ${parseFloat(pRate)>=parseFloat(dbProfitRate(prev))?'text-emerald-600':'text-rose-500'}">${parseFloat(pRate)>=parseFloat(dbProfitRate(prev))?'▲':'▼'} ${Math.abs(parseFloat(pRate)-parseFloat(dbProfitRate(prev))).toFixed(1)}%p</span>` : '',
      sub: prev ? `전년 ${dbProfitRate(prev)}%` : '' },
    { label:'부가세 (납부)', value: dbFmtM(cur.vat), icon:'receipt', iconBg:'bg-primary-light', iconColor:'text-primary', valueColor: cur.vat >= 0 ? 'text-primary' : 'text-emerald-600',
      change: '', sub: (cur.salesVat || cur.purchaseVat) ? `매출세액 ${dbFmtM(cur.salesVat)} − 매입세액 ${dbFmtM(cur.purchaseVat)}` : '세금계산서 데이터 없음', diff: statusBadge },
  ];

  const kpiHTML = `<div class="grid grid-cols-5 gap-4 mb-5 ${mutedClass}">${kpiCards.map(k => `
    <div class="bg-slate-900 rounded-xl p-5 border border-slate-800 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
      <div class="flex items-center justify-between mb-3">
        <span class="text-sm font-medium text-slate-400">${k.label}</span>
        <span class="w-8 h-8 rounded-lg ${k.iconBg} flex items-center justify-center"><i data-lucide="${k.icon}" class="w-4 h-4 ${k.iconColor}"></i></span>
      </div>
      <div class="text-2xl zm-stat ${k.valueColor}">${k.value}</div>
      <div class="mt-1 flex items-center gap-2">${k.change} <span class="text-sm text-slate-400">${k.sub||''}</span></div>
      ${k.diff ? `<div class="mt-0.5">${k.diff}</div>` : ''}
    </div>`).join('')}</div>`;

  const hasBank = DB_BANK_YEARS.includes(dbYear);
  const hasVat  = DB_VAT_YEARS.includes(dbYear);
  const doneIcon = '<i data-lucide="check" class="w-5 h-5"></i>';
  const bankBtn = hasBank
    ? `<span class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 font-bold text-base cursor-default">${doneIcon}통장 자료 완료</span>`
    : `<a href="acct_bank.php?tab=history" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-primary text-white font-bold shadow-md hover:shadow-lg hover:bg-primary/90 transition-all text-base"><i data-lucide="download" class="w-5 h-5"></i>통장 자료 가져오기</a>`;
  const vatBtn = hasVat
    ? `<span class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 font-bold text-base cursor-default">${doneIcon}세금계산서 완료</span>`
    : `<a href="acct_invoice.php?tab=issue&upload=1" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white border-2 border-slate-200 text-slate-700 font-bold hover:border-gray-400 hover:bg-slate-50 transition-all text-base"><i data-lucide="receipt" class="w-5 h-5"></i>세금계산서 등록</a>`;

  const missingParts = [];
  if (!hasBank) missingParts.push('통장 거래내역');
  if (!hasVat) missingParts.push('세금계산서');
  const heroDesc = missingParts.length === 2
    ? '통장 거래내역과 세금계산서를 가져오면<br>매출·비용·이익 분석이 자동으로 생성됩니다.'
    : `${missingParts[0]}을 추가하면 리포트가 완성됩니다.`;

  const periodNoticeHTML = curHasData ? '' : `
  <div class="db-no-data-hero">
    <div class="db-hero-inner">
      <div class="w-14 h-14 rounded-2xl bg-primary text-white flex items-center justify-center shadow-lg mb-4">
        <i data-lucide="database" class="w-7 h-7"></i>
      </div>
      <h3 class="text-xl font-bold text-slate-900 mb-2">${yearHasData ? dbGetPeriodLabel() : dbYear + '년'} 자료가 필요합니다</h3>
      <p class="text-slate-500 mb-6 max-w-md text-center">${heroDesc}</p>
      <div class="flex gap-3">${bankBtn}${vatBtn}</div>
    </div>
  </div>`;

  const periodTag = (dbPeriod !== 'year' || dbYtdMode) ? ` <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-primary/10 text-primary font-medium">${dbGetPeriodLabel().replace(dbYear+'년 ','')}</span>` : '';
  const canToggle = dbPeriod !== 'month';
  const toggleHTML = canToggle ? `
    <div class="inline-flex rounded-lg border border-slate-700 overflow-hidden ml-3">
      <button onclick="dbSetViewMode('monthly')" class="px-2.5 py-1 text-xs transition-colors ${dbViewMode==='monthly'?'bg-primary text-white':'text-slate-400 hover:bg-slate-800'}">월별</button>
      <button onclick="dbSetViewMode('aggregate')" class="px-2.5 py-1 text-xs transition-colors ${dbViewMode==='aggregate'?'bg-primary text-white':'text-slate-400 hover:bg-slate-800'}">집계</button>
    </div>` : '';
  const chartTitle = dbViewMode==='aggregate' && canToggle
    ? (dbPeriod==='year'||dbPeriod==='quarter' ? '분기별' : '반기별') + ' 매출/비용/순이익 비교'
    : '월별 매출/비용/순이익 비교';
  const lineTitle = dbViewMode==='aggregate' && canToggle
    ? (dbPeriod==='year'||dbPeriod==='quarter' ? '분기별' : '반기별') + ' 순이익률'
    : '순이익률 추이';

  const chartHTML = `
  <div class="grid grid-cols-3 gap-5 mb-5 ${mutedClass}">
    <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-5">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <h3 class="text-sm font-bold text-slate-100">${chartTitle}${periodTag}</h3>${toggleHTML}
        </div>
        <span class="text-sm text-slate-400">${dbYear}년${prev && dbPeriod !== 'month' ? ' vs '+(dbYear-1)+'년' : ''}</span>
      </div>
      <div style="position:relative;height:240px"><canvas id="dbBarCanvas"></canvas></div>
    </div>
    <div class="col-span-1 bg-slate-900 rounded-xl border border-slate-800 p-5">
      <h3 class="text-sm font-bold text-slate-100 mb-4">${lineTitle}${periodTag}</h3>
      <div style="position:relative;height:240px"><canvas id="dbLineCanvas"></canvas></div>
    </div>
  </div>`;

  const months = dbGetRange(dbYear, dbPeriod, dbSub);
  const prevYear = dbYear - 1;
  const rowsHTML = months.map(m => {
    const d = DB_RAW[dbYear] ? DB_RAW[dbYear][m-1] : {...ZERO_MONTH};
    const pd = DB_RAW[prevYear] ? DB_RAW[prevYear][m-1] : null;
    const mp = dbProfit(d), isConf = dbIsConfirmed(dbYear, m);
    return `<tr class="border-b border-slate-800 ${isConf?'':'bg-amber-50/30'} hover:bg-slate-950">
      <td class="py-2.5 px-3 text-slate-200 tabular-nums">${DB_MONTH_NAMES[m-1]}</td>
      <td class="py-2.5 px-3 text-right text-slate-100 tabular-nums">${dbFmtMwon(d.rev)}</td>
      <td class="py-2.5 px-3 text-right">${pd?`<span class="text-slate-300 tabular-nums">${dbFmtMwon(pd.rev)}</span> ${dbCmpBadge(d.rev,pd.rev)}`:'--'}</td>
      <td class="py-2.5 px-3 text-right text-amber-500 tabular-nums">${dbFmtMwon(d.cost)}</td>
      <td class="py-2.5 px-3 text-right ${mp>=0?'text-emerald-600':'text-rose-500'} tabular-nums">${dbFmtMwon(mp)}</td>
      <td class="py-2.5 px-3 text-right text-amber-600 tabular-nums">${dbProfitRate(d)}%</td>
      <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums">${dbFmtMwon(d.vat)}</td>
      <td class="py-2.5 px-3 text-center">${isConf?'<span class="px-1.5 py-0.5 text-sm rounded bg-emerald-50 text-emerald-700 font-medium">확정</span>':'<span class="px-1.5 py-0.5 text-sm rounded bg-amber-50 text-amber-600 font-medium">미확정</span>'}</td>
    </tr>`;
  }).join('');

  const tp = dbProfit(cur);
  const tableHTML = `
  <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-5 ${mutedClass}">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-100">기간별 손익 명세</h3>
      <span class="text-sm text-slate-400">단위: 백만원</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b-2 border-slate-800">
          <th class="py-2 px-3 text-left font-medium text-slate-300">기간</th>
          <th class="py-2 px-3 text-right font-medium text-slate-300">매출액</th>
          <th class="py-2 px-3 text-right font-medium text-slate-300">전년대비</th>
          <th class="py-2 px-3 text-right font-medium text-slate-300">비용</th>
          <th class="py-2 px-3 text-right font-medium text-slate-300">순이익</th>
          <th class="py-2 px-3 text-right font-medium text-slate-300">순이익률</th>
          <th class="py-2 px-3 text-right font-medium text-slate-300">부가세</th>
          <th class="py-2 px-3 text-center font-medium text-slate-300">상태</th>
        </tr></thead>
        <tbody>${rowsHTML}</tbody>
        <tfoot><tr class="border-t-2 border-slate-700 bg-slate-950">
          <td class="py-2.5 px-3 font-semibold text-slate-100">합계</td>
          <td class="py-2.5 px-3 text-right font-semibold text-slate-100 tabular-nums">${dbFmtMwon(cur.rev)}</td>
          <td class="py-2.5 px-3 text-right">${prev?dbCmpBadge(cur.rev,prev.rev):'--'}</td>
          <td class="py-2.5 px-3 text-right font-semibold text-amber-500 tabular-nums">${dbFmtMwon(cur.cost)}</td>
          <td class="py-2.5 px-3 text-right font-semibold ${tp>=0?'text-emerald-600':'text-rose-500'} tabular-nums">${dbFmtMwon(tp)}</td>
          <td class="py-2.5 px-3 text-right font-semibold text-amber-600 tabular-nums">${dbProfitRate(cur)}%</td>
          <td class="py-2.5 px-3 text-right font-semibold text-slate-300 tabular-nums">${dbFmtMwon(cur.vat)}</td>
          <td class="py-2.5 px-3 text-center">--</td>
        </tr></tfoot>
      </table>
    </div>
  </div>`;

  const bodyHTML = kpiHTML + chartHTML + tableHTML
    + `<div class="grid grid-cols-2 gap-5 mb-5 ${mutedClass}">${dbBuildWaterfallHTML(cur,prev)}${dbBuildTaxDetail(cur,prev)}</div>`
    + dbBuildForecastHTML() + dbBuildScheduleHTML();

  if (!curHasData) {
    return `<div class="db-no-data-wrapper">
      ${periodNoticeHTML}
      <div class="db-no-data-behind">${bodyHTML}</div>
    </div>`;
  }
  return bodyHTML;
}

/* ── WATERFALL ── */
function dbBuildWaterfallHTML(cur, prev) {
  if (!prev) return `<div class="bg-slate-900 rounded-xl border border-slate-800 p-5"><h3 class="text-sm font-bold text-slate-100 mb-4">전년 대비 비교</h3><p class="text-sm text-slate-400 py-8 text-center">전년 데이터 없음</p></div>`;
  const maxVal = Math.max(cur.rev, prev.rev, cur.cost, prev.cost, 1) * 1.05;
  const items = [
    {label:'전년 매출',val:prev.rev,color:'bg-slate-700'},
    {label:'당기 매출',val:cur.rev,color:'bg-primary'},
    {label:'전년 비용',val:prev.cost,color:'bg-slate-700'},
    {label:'당기 비용',val:cur.cost,color:'bg-amber-100'},
    {label:'전년 순이익',val:dbProfit(prev),color:'bg-slate-700'},
    {label:'당기 순이익',val:dbProfit(cur),color:'bg-amber-500'},
  ];
  const rows = items.map(it => {
    const w = Math.max(2, (it.val/maxVal)*100);
    return `<div class="flex items-center gap-3">
      <span class="text-sm text-slate-300 w-16 shrink-0">${it.label}</span>
      <div class="flex-1 h-6 bg-slate-950 rounded overflow-hidden"><div class="${it.color} h-full rounded flex items-center pl-2 text-sm text-white font-medium transition-all" style="width:${w}%">${w>25?dbFmtM(it.val):''}</div></div>
      <span class="text-sm text-slate-200 tabular-nums w-20 text-right">${dbFmtMwon(it.val)}</span>
    </div>`;
  }).join('');
  return `<div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-100">전년 대비 비교 (${dbYear-1}→${dbYear})</h3>
      ${dbCmpBadge(cur.rev, prev.rev)}
    </div>
    <div class="space-y-2">${rows}</div>
  </div>`;
}

/* ── TAX DETAIL ── */
function dbBuildTaxDetail(cur, prev) {
  const items = [
    {label:'매출세액', cur:cur.salesVat||0, prev:prev?.salesVat||0, note:'매출 세금계산서 세액', icon:'trending-up'},
    {label:'매입세액', cur:cur.purchaseVat||0, prev:prev?.purchaseVat||0, note:'매입 세금계산서 세액 (공제)', icon:'trending-down'},
    {label:'납부할 부가세', cur:cur.vat, prev:prev?.vat, note:'매출세액 − 매입세액', icon:'receipt'},
    {label:'원천세 (갑종)', cur:cur.whTax, prev:prev?.whTax, note:'급여 모듈 연동 시 반영', icon:'file-text'},
    {label:'법인세 충당', cur:cur.corpTax, prev:prev?.corpTax, note:'결산 시 반영', icon:'building-2'},
  ];
  const rows = items.map(it => `
    <div class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0">
      <div class="flex items-center gap-3">
        <span class="w-8 h-8 rounded-lg bg-slate-950 flex items-center justify-center"><i data-lucide="${it.icon}" class="w-4 h-4 text-slate-400"></i></span>
        <div><p class="text-sm text-slate-200">${it.label}</p><p class="text-sm text-slate-400">${it.note}</p></div>
      </div>
      <div class="text-right"><p class="text-sm font-semibold text-slate-100 tabular-nums">${it.cur > 0 ? dbFmtMwon(it.cur) : '미연동'}</p>${it.prev && it.cur > 0 ?`<p class="mt-0.5">${dbCmpBadge(it.cur,it.prev)}</p>`:''}</div>
    </div>`).join('');
  return `<div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
    <h3 class="text-sm font-bold text-slate-100 mb-3 flex items-center gap-2"><i data-lucide="calculator" class="w-4 h-4 text-primary"></i>세금 상세 내역</h3>
    <div>${rows}</div>
  </div>`;
}

/* ── FORECAST ── */
function dbBuildForecastHTML() {
  const fc = dbGetForecastData();
  if (!fc) return '';

  const { confirmedData, forecastRemain, total, prevTotal, growthRate, confirmed } = fc;
  const pnlTotal = dbProfit(total), progress = Math.round((confirmed/12)*100);
  const fcCards = [
    {label:'연간 예상 매출', val:dbFmtM(total.rev), color:'text-primary', note:`확정 ${dbFmtM(confirmedData.rev)} + 예측 ${dbFmtM(forecastRemain.rev)}`, badge:prevTotal?dbCmpBadge(total.rev,prevTotal.rev):''},
    {label:'연간 예상 비용', val:dbFmtM(total.cost), color:'text-amber-500', note:`확정 ${dbFmtM(confirmedData.cost)} + 예측 ${dbFmtM(forecastRemain.cost)}`, badge:''},
    {label:'연간 예상 순이익', val:dbFmtM(pnlTotal), color:'text-amber-700', note:`순이익률 예상 ${dbProfitRate(total)}%`, badge:prevTotal?dbCmpBadge(pnlTotal,dbProfit(prevTotal)):''},
    {label:'연간 예상 부가세', val:dbFmtM(total.vat), color:'text-amber-600', note:`매출세액 ${dbFmtM(total.salesVat||0)} − 매입세액 ${dbFmtM(total.purchaseVat||0)}`, badge:''},
  ];

  return `
  <div class="bg-primary-light/30 border border-primary/20 rounded-xl p-5 mb-5">
    <div class="flex items-center gap-2 mb-1">
      <h3 class="text-sm font-bold text-slate-100">${dbYear}년 연말 예측</h3>
      <span class="px-1.5 py-0.5 text-sm rounded bg-amber-50 text-amber-600 font-medium">예측</span>
    </div>
    <p class="text-sm text-slate-400 mb-2">확정 ${confirmed}개월 실적 기반${growthRate?` / 전년 대비 예상 성장률 <strong class="text-primary">${growthRate>=0?'+':''}${growthRate}%</strong>`:''}</p>
    <div class="mb-4 rounded-lg border border-primary/10 bg-slate-900/50 px-3 py-2 text-sm text-slate-400">
      <span class="font-semibold text-slate-200">예측 산식:</span>
      연간 예상 = 확정 ${confirmed}개월 실적 + ${confirmed + 1}~12월 입력/예정 데이터 합계입니다.
      현재는 추세 extrapolation이 아니라, 아직 마감되지 않은 월의 등록 데이터를 그대로 더합니다.
    </div>
    <div class="grid grid-cols-4 gap-4 mb-4">${fcCards.map(c=>`
      <div class="bg-slate-900/70 rounded-lg p-4">
        <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-1">${c.label}</p>
        <p class="text-xl font-bold ${c.color} tabular-nums">${c.val}</p>
        <p class="text-sm text-slate-400 mt-1">${c.note}</p>
        ${c.badge?`<div class="mt-1">${c.badge}</div>`:''}
      </div>`).join('')}</div>
    <div class="flex items-center gap-3 pt-3 border-t border-primary/10 text-sm text-slate-400">
      <span>데이터 기반 신뢰도 (${confirmed}/12개월 확정)</span>
      <div class="flex-1 h-1.5 bg-slate-900/50 rounded-full overflow-hidden"><div class="h-full bg-primary rounded-full" style="width:${progress}%"></div></div>
      <span class="font-bold text-primary tabular-nums">${progress}%</span>
    </div>
  </div>
  <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-100">월별 실적 & 예측 추이 (${dbYear}년 전체)</h3>
      <div class="flex gap-3 text-sm text-slate-400">
        <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-primary rounded inline-block"></span>확정 매출</span>
        <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-primary/30 rounded inline-block" style="border-top:1px dashed var(--zm-primary, #4F6AFF)"></span>예측 매출</span>
        <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-amber-500 rounded inline-block"></span>확정 순이익</span>
      </div>
    </div>
    <div style="position:relative;height:220px"><canvas id="dbForecastCanvas"></canvas></div>
  </div>`;
}

/* ── SCHEDULE ── */
function dbBuildScheduleHTML() {
  const y = dbYear;
  const schedules = [
    {mon:'01월',day:'10',title:'원천세 신고/납부',sub:`${y-1}년 12월분`,due:false},
    {mon:'01월',day:'25',title:'부가세 2기 확정신고',sub:`${y-1}년 7~12월`,due:false},
    {mon:'03월',day:'31',title:'법인세 신고',sub:`${y-1}년 귀속 법인세`,due:false},
    {mon:'04월',day:'25',title:'부가세 1기 예정신고',sub:`${y}년 1~3월`,due:false},
    {mon:'05월',day:'31',title:'종합소득세 신고',sub:`${y-1}년 귀속 종합소득세`,due:false},
    {mon:'07월',day:'25',title:'부가세 1기 확정신고',sub:`${y}년 1~6월`,due:false},
    {mon:'08월',day:'31',title:'법인세 중간예납',sub:'상반기 실적 기준',due:false},
    {mon:'10월',day:'25',title:'부가세 2기 예정신고',sub:`${y}년 7~9월`,due:false},
  ];
  const now = new Date();
  const curY = now.getFullYear(), curM = now.getMonth()+1, curD = now.getDate();
  schedules.forEach(s => {
    if (y === curY) {
      const sm = parseInt(s.mon), sd = parseInt(s.day);
      if (sm === curM || (sm === curM + 1 && sd <= 25)) s.due = true;
      if (sm < curM || (sm === curM && sd < curD)) s.due = false;
    }
  });

  const rows = schedules.map(s=>`
    <div class="flex items-start gap-3 py-3">
      <div class="w-10 text-center shrink-0">
        <div class="text-lg font-bold ${s.due?'text-amber-600':'text-slate-400'}">${s.day}</div>
        <div class="text-sm text-slate-400">${s.mon}</div>
      </div>
      <div class="flex-1">
        <p class="text-sm font-semibold text-slate-200">${s.title}</p>
        <p class="text-sm text-slate-400">${s.sub}</p>
      </div>
      <span class="px-1.5 py-0.5 text-sm rounded font-medium ${s.due?'bg-amber-50 text-amber-600':'bg-slate-800 text-slate-400'}">${s.due?'임박':'예정'}</span>
    </div>`).join('');
  return `<div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
    <h3 class="text-sm font-bold text-slate-100 mb-3 flex items-center gap-2"><i data-lucide="calendar-days" class="w-4 h-4 text-primary"></i>주요 세무 신고/납부 일정 <span class="text-sm font-normal text-slate-400">${dbYear}년</span></h3>
    <div class="divide-y divide-slate-800">${rows}</div>
  </div>`;
}

/* ══════════════════════════════════
   CHART.JS BUILDERS
══════════════════════════════════ */
const _cs = getComputedStyle(document.documentElement);
const _isDark = document.documentElement.dataset.theme === 'dark';
const DB_COLORS = {
  primary: _cs.getPropertyValue('--zm-primary').trim() || '#4F6AFF', red:'#EF4444', green:'#10B981', amber:'#F59E0B',
  gray: _cs.getPropertyValue('--zm-text-muted').trim() || '#9CA3AF',
  gridLine: _cs.getPropertyValue('--zm-border').trim() || '#e5e7eb',
  tickText: _cs.getPropertyValue('--zm-text-subtle').trim() || '#94a3b8',
  tickActive: _cs.getPropertyValue('--zm-text-strong').trim() || '#64748b',
  surface: _cs.getPropertyValue('--zm-surface-1').trim() || '#ffffff',
  barAlpha: _isDark ? 0.85 : 0.6,
  subAlpha: _isDark ? 0.4 : 0.25,
  prevLine: _isDark ? 'rgba(156,163,175,0.55)' : 'rgba(156,163,175,0.4)',
};
function _hexToRgba(hex, alpha) {
  const h = hex.replace('#','');
  const r = parseInt(h.substring(0,2),16), g = parseInt(h.substring(2,4),16), b = parseInt(h.substring(4,6),16);
  return `rgba(${r},${g},${b},${alpha})`;
}

function dbSetViewMode(mode) {
  dbViewMode = mode;
  dbRender();
}

function dbBuildMainCharts(cur, prev) {
  const useAggregate = dbViewMode === 'aggregate' && dbPeriod !== 'month';

  if (useAggregate) {
    dbBuildAggregateCharts(cur, prev);
  } else {
    dbBuildMonthlyCharts(cur, prev);
  }
}

function dbBuildAggregateCharts(cur, prev) {
  const prevYear = dbYear - 1;
  const yearData = DB_RAW[dbYear] || [];
  const prevData = DB_RAW[prevYear] || [];
  const isQuarterLevel = dbPeriod === 'year' || dbPeriod === 'quarter';

  let labels, aggData, prevAggData, selectedIdx;

  if (isQuarterLevel) {
    labels = DB_QUARTER_NAMES;
    aggData = dbAggregateQuarters(yearData);
    prevAggData = prevData.length ? dbAggregateQuarters(prevData) : null;
    selectedIdx = dbPeriod === 'quarter' ? dbSub - 1 : -1;
  } else {
    labels = DB_HALF_NAMES;
    aggData = dbAggregateHalves(yearData);
    prevAggData = prevData.length ? dbAggregateHalves(prevData) : null;
    selectedIdx = dbPeriod === 'half' ? dbSub - 1 : -1;
  }

  const revData = aggData.map(d => d.rev);
  const costData = aggData.map(d => d.cost);
  const pnlData = aggData.map(d => dbProfit(d));
  const prevRevData = prevAggData ? prevAggData.map(d => d.rev) : [];

  const hlPlugin = {
    id:'periodHL',
    beforeDraw(chart) {
      if (selectedIdx < 0) return;
      const {ctx, chartArea:ca, scales:{x}} = chart;
      const catWidth = ca.width / labels.length;
      const bandHalf = catWidth * 0.4;
      const xc = x.getPixelForValue(selectedIdx);
      const x1 = xc - bandHalf, x2 = xc + bandHalf;
      ctx.save();
      ctx.fillStyle = 'rgba(0,0,0,0.04)';
      ctx.fillRect(x1, ca.top, x2-x1, ca.bottom-ca.top);
      ctx.strokeStyle = 'rgba(0,0,0,0.08)';
      ctx.lineWidth = 1; ctx.setLineDash([4,3]);
      ctx.beginPath();
      ctx.moveTo(x1, ca.top); ctx.lineTo(x1, ca.bottom);
      ctx.moveTo(x2, ca.top); ctx.lineTo(x2, ca.bottom);
      ctx.stroke(); ctx.restore();
    }
  };

  const a = i => selectedIdx < 0 || i === selectedIdx ? 1 : 0.3;

  if (dbBarChartInstance) dbBarChartInstance.destroy();
  const barCtx = document.getElementById('dbBarCanvas');
  if (!barCtx) return;

  const showPrev = prevRevData.length > 0 && prevRevData.some(v => v > 0);
  const aggRevLabel = showPrev ? `${dbYear}년 매출` : '매출';
  const bA = DB_COLORS.barAlpha, sA = DB_COLORS.subAlpha;
  const barDatasets = [
    {label:aggRevLabel, data:revData, backgroundColor:revData.map((_,i)=>_hexToRgba(DB_COLORS.primary,a(i))), borderRadius:6, barPercentage:0.7},
    ...(showPrev ? [{label:`${prevYear}년 매출`, data:prevRevData, backgroundColor:prevRevData.map((_,i)=>`rgba(156,163,175,${sA*a(i)})`), borderRadius:6, barPercentage:0.7}] : []),
    {label:'비용', data:costData, backgroundColor:costData.map((_,i)=>`rgba(239,68,68,${bA*a(i)})`), borderRadius:6, barPercentage:0.7},
    {label:'순이익', data:pnlData, backgroundColor:pnlData.map((_,i)=>`rgba(16,185,129,${bA*a(i)})`), borderRadius:6, barPercentage:0.7},
  ];
  const barLegendColors = [_hexToRgba(DB_COLORS.primary,1)];
  if (showPrev) barLegendColors.push(`rgba(156,163,175,${sA+0.15})`);
  barLegendColors.push(`rgba(239,68,68,${bA})`, `rgba(16,185,129,${bA})`);

  dbBarChartInstance = new Chart(barCtx, {
    type:'bar', data:{labels, datasets:barDatasets}, plugins:[hlPlugin],
    options:{responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11},padding:12,
        generateLabels(chart){return chart.data.datasets.map((ds,i)=>({text:ds.label,fillStyle:barLegendColors[i],strokeStyle:'transparent',hidden:!chart.isDatasetVisible(i),datasetIndex:i}));}}},
        tooltip:{callbacks:{label:ctx=>`${ctx.dataset.label}: ${dbFmtMwon(ctx.raw)}`}}},
      scales:{y:{beginAtZero:true,suggestedMax:10,ticks:{font:{size:11},callback:v=>dbFmtM(v)},grid:{color:DB_COLORS.gridLine+'18'}},
        x:{ticks:{font:ctx=>({size:12,weight:selectedIdx>=0&&ctx.index===selectedIdx?'bold':'normal'}),
          color:ctx=>selectedIdx>=0&&ctx.index===selectedIdx?DB_COLORS.tickActive:DB_COLORS.tickText},grid:{display:false}}}}
  });

  if (dbLineChartInstance) dbLineChartInstance.destroy();
  const lineCtx = document.getElementById('dbLineCanvas');
  if (!lineCtx) return;

  const rateData = aggData.map(d => d.rev > 0 ? parseFloat(dbProfitRate(d)) : null);
  const prevRateData = prevAggData ? prevAggData.map(d => d.rev > 0 ? parseFloat(dbProfitRate(d)) : null) : [];
  const validRates = rateData.filter(v=>v!==null).concat(prevRateData.filter(v=>v!==null));
  const rMin = validRates.length ? Math.min(0,...validRates) : -10;
  const rMax = validRates.length ? Math.max(0,...validRates) : 10;
  const rPad = Math.max(5,(rMax-rMin)*0.15);

  const showPrevLine = prevRateData.length > 0 && prevRateData.some(v => v !== null);
  const aggLineLabel = showPrevLine ? `${dbYear}년` : '이익률';
  dbLineChartInstance = new Chart(lineCtx, {
    type:'line', data:{labels, datasets:[
      {label:aggLineLabel, data:rateData, borderColor:DB_COLORS.green, backgroundColor:DB_COLORS.green+'20', fill:true, tension:0.4, spanGaps:false,
       pointRadius:rateData.map((v,i)=>v===null?0:(selectedIdx<0||i===selectedIdx?6:3)),
       pointBackgroundColor:rateData.map((v,i)=>v===null?'transparent':(selectedIdx>=0&&i!==selectedIdx?DB_COLORS.green+'40':DB_COLORS.green)),
       pointBorderColor:rateData.map((_,i)=>selectedIdx<0||i===selectedIdx?DB_COLORS.green:DB_COLORS.green+'40'),
       pointBorderWidth:2},
      ...(showPrevLine ? [{label:`${prevYear}년`, data:prevRateData, borderColor:DB_COLORS.prevLine, backgroundColor:'transparent', fill:false, tension:0.4, pointRadius:3, borderWidth:1.5, spanGaps:false}] : []),
    ]}, plugins:[hlPlugin],
    options:{responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11},padding:12}},
        tooltip:{filter:item=>item.raw!==null,callbacks:{label:ctx=>`${ctx.dataset.label}: ${ctx.raw}%`}}},
      scales:{y:{suggestedMin:rMin-rPad,suggestedMax:rMax+rPad,ticks:{font:{size:10},callback:v=>v+'%'},grid:{color:ctx=>ctx.tick?.value===0?'rgba(107,114,128,0.3)':'rgba(0,0,0,0.04)'}},
        x:{ticks:{font:ctx=>({size:12,weight:selectedIdx>=0&&ctx.index===selectedIdx?'bold':'normal'}),
          color:ctx=>selectedIdx>=0&&ctx.index===selectedIdx?DB_COLORS.tickActive:DB_COLORS.tickText},grid:{display:false}}}}
  });
}

function dbBuildMonthlyCharts(cur, prev) {
  const selectedMonths = new Set(dbGetRange(dbYear, dbPeriod, dbSub));

  let chartMonths;
  if (dbPeriod === 'month') {
    const center = dbSub;
    const start = Math.max(1, Math.min(8, center - 2));
    chartMonths = Array.from({length: 5}, (_, i) => start + i);
  } else {
    chartMonths = [1,2,3,4,5,6,7,8,9,10,11,12];
  }

  const isAll = chartMonths.every(m => selectedMonths.has(m));
  const selIdx = chartMonths.reduce((acc, m, i) => { if (selectedMonths.has(m)) acc.push(i); return acc; }, []);
  const hlPlugin = {
    id:'periodHL',
    beforeDraw(chart) {
      if (isAll || selIdx.length < 1) return;
      const {ctx, chartArea:ca, scales:{x}} = chart;
      const step = x.getPixelForValue(1) - x.getPixelForValue(0);
      const x1 = x.getPixelForValue(Math.min(...selIdx)) - step*0.55;
      const x2 = x.getPixelForValue(Math.max(...selIdx)) + step*0.55;
      ctx.save();
      ctx.fillStyle = 'rgba(0,0,0,0.04)';
      ctx.fillRect(x1, ca.top, x2-x1, ca.bottom-ca.top);
      ctx.strokeStyle = 'rgba(0,0,0,0.08)';
      ctx.lineWidth = 1;
      ctx.setLineDash([4,3]);
      ctx.beginPath();
      ctx.moveTo(x1, ca.top); ctx.lineTo(x1, ca.bottom);
      ctx.moveTo(x2, ca.top); ctx.lineTo(x2, ca.bottom);
      ctx.stroke();
      ctx.restore();
    }
  };
  const prevYear = dbYear - 1;
  const labels = chartMonths.map(m => DB_MONTH_NAMES[m-1]);
  const yearData = DB_RAW[dbYear] || [];
  const prevData = DB_RAW[prevYear] || [];
  const revData = chartMonths.map(m => yearData[m-1]?.rev || 0);
  const costData = chartMonths.map(m => yearData[m-1]?.cost || 0);
  const pnlData = chartMonths.map(m => yearData[m-1] ? dbProfit(yearData[m-1]) : 0);
  const prevRevData = prevData.length ? chartMonths.map(m => prevData[m-1]?.rev || 0) : [];
  const a = m => isAll || selectedMonths.has(m) ? 1 : 0.15;

  if (dbBarChartInstance) dbBarChartInstance.destroy();
  const barCtx = document.getElementById('dbBarCanvas');
  if (!barCtx) return;
  const showPrev = prevRevData.length && dbPeriod !== 'month' && prevRevData.some(v => v > 0);
  const revLabel = showPrev ? `${dbYear}년 매출` : '매출';
  const bA = DB_COLORS.barAlpha, sA = DB_COLORS.subAlpha;
  const barDatasets = [
    {label:revLabel, data:revData, backgroundColor:chartMonths.map(m=>_hexToRgba(DB_COLORS.primary,a(m))), borderRadius:4},
    ...(showPrev ? [{label:`${prevYear}년 매출`, data:prevRevData, backgroundColor:chartMonths.map(m=>`rgba(156,163,175,${sA*a(m)})`), borderRadius:4}] : []),
    {label:'비용', data:costData, backgroundColor:chartMonths.map(m=>`rgba(239,68,68,${bA*a(m)})`), borderRadius:4},
    {label:'순이익', data:pnlData, backgroundColor:chartMonths.map(m=>`rgba(16,185,129,${bA*a(m)})`), borderRadius:4},
  ];
  const barLegendColors = [_hexToRgba(DB_COLORS.primary,1)];
  if (showPrev) barLegendColors.push(`rgba(156,163,175,${sA+0.15})`);
  barLegendColors.push(`rgba(239,68,68,${bA})`, `rgba(16,185,129,${bA})`);
  dbBarChartInstance = new Chart(barCtx, {
    type:'bar', data:{labels, datasets:barDatasets}, plugins:[hlPlugin],
    options:{responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11},padding:12,
        generateLabels(chart){return chart.data.datasets.map((ds,i)=>({text:ds.label,fillStyle:barLegendColors[i],strokeStyle:'transparent',hidden:!chart.isDatasetVisible(i),datasetIndex:i}));}}},tooltip:{callbacks:{label:ctx=>`${ctx.dataset.label}: ${dbFmtMwon(ctx.raw)}`}}},
      scales:{y:{beginAtZero:true,suggestedMax:10,ticks:{font:{size:10},callback:v=>dbFmtM(v)},grid:{color:'rgba(0,0,0,0.04)'}},
        x:{ticks:{font:ctx=>({size:10,weight:!isAll&&selectedMonths.has(chartMonths[ctx.index])?'bold':'normal'}),color:ctx=>isAll?DB_COLORS.tickActive:selectedMonths.has(chartMonths[ctx.index])?DB_COLORS.tickActive:'#CBD5E1'},grid:{display:false}}}}
  });

  if (dbLineChartInstance) dbLineChartInstance.destroy();
  const lineCtx = document.getElementById('dbLineCanvas');
  if (!lineCtx) return;
  const rateData = chartMonths.map(m=>{const d=yearData[m-1];return d&&d.rev>0?parseFloat(dbProfitRate(d)):null;});
  const prevRateData = prevData.length ? chartMonths.map(m=>{const d=prevData[m-1];return d&&d.rev>0?parseFloat(dbProfitRate(d)):null;}) : [];
  const confirmedMonth = DB_CONFIRMED_UNTIL[dbYear] || 12;
  const validRates = rateData.filter(v=>v!==null).concat(prevRateData.filter(v=>v!==null));
  const rMin = validRates.length ? Math.min(0,...validRates) : -10;
  const rMax = validRates.length ? Math.max(0,...validRates) : 10;
  const rPad = Math.max(5,(rMax-rMin)*0.15);

  const showPrevLine = prevRateData.length && dbPeriod !== 'month' && prevRateData.some(v => v !== null && v !== 0);
  const lineLabel = showPrevLine ? `${dbYear}년` : '이익률';
  const lineDatasets = [
    {label:lineLabel, data:rateData, borderColor:DB_COLORS.green, backgroundColor:DB_COLORS.green+'20', fill:true, tension:0.4, spanGaps:false,
     pointRadius:chartMonths.map((m,i)=>rateData[i]===null?0:(isAll||selectedMonths.has(m)?5:3)),
     pointBackgroundColor:chartMonths.map((m,i)=>{if(rateData[i]===null)return'transparent';if(!isAll&&!selectedMonths.has(m))return DB_COLORS.green+'40';return m<=confirmedMonth?DB_COLORS.green:DB_COLORS.surface;}),
     pointBorderColor:chartMonths.map(m=>isAll||selectedMonths.has(m)?DB_COLORS.green:DB_COLORS.green+'40'),
     pointBorderWidth:chartMonths.map(m=>!isAll&&selectedMonths.has(m)?2:1),
     segment:{borderDash:ctx=>(chartMonths[ctx.p0DataIndex]>confirmedMonth?[5,4]:undefined)}},
    ...(showPrevLine ? [{label:`${prevYear}년`, data:prevRateData, borderColor:DB_COLORS.prevLine, backgroundColor:'transparent', fill:false, tension:0.4, pointRadius:2, borderWidth:1.5, spanGaps:false}] : []),
  ];
  dbLineChartInstance = new Chart(lineCtx, {
    type:'line', data:{labels, datasets:lineDatasets}, plugins:[hlPlugin],
    options:{responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11},padding:12}},tooltip:{filter:item=>item.raw!==null,callbacks:{label:ctx=>`${ctx.dataset.label}: ${ctx.raw}%`}}},
      scales:{y:{suggestedMin:rMin-rPad,suggestedMax:rMax+rPad,ticks:{font:{size:10},callback:v=>v+'%'},grid:{color:ctx=>ctx.tick?.value===0?DB_COLORS.gray+'4D':DB_COLORS.gridLine+'18'}},
        x:{ticks:{font:ctx=>({size:10,weight:!isAll&&selectedMonths.has(chartMonths[ctx.index])?'bold':'normal'}),color:ctx=>isAll?DB_COLORS.tickText:selectedMonths.has(chartMonths[ctx.index])?DB_COLORS.tickActive:DB_COLORS.tickText+'80'},grid:{display:false}}}}
  });
}

function dbBuildForecastChart() {
  const el = document.getElementById('dbForecastCanvas');
  if (!el) return;
  if (dbForecastChartInstance) dbForecastChartInstance.destroy();

  const year = dbYear, confirmed = DB_CONFIRMED_UNTIL[year] || 0;
  if (confirmed >= 12 || confirmed === 0) return;

  const yearData = DB_RAW[year] || [];
  if (!yearData.length) return;

  const labels = DB_MONTH_NAMES;
  const revAll = yearData.map(d => d.rev);
  const pnlAll = yearData.map(d => dbProfit(d));

  dbForecastChartInstance = new Chart(el, {
    type:'line', data:{labels, datasets:[
      {label:'매출', data:revAll, borderColor:DB_COLORS.primary, backgroundColor:DB_COLORS.primary+'15', fill:true, tension:0.4,
       pointRadius:3, pointHoverRadius:7, pointHitRadius:18,
       pointBackgroundColor:revAll.map((_,i)=>i<confirmed?DB_COLORS.primary:DB_COLORS.surface), pointBorderColor:DB_COLORS.primary,
       segment:{borderDash:ctx=>(ctx.p0DataIndex>=confirmed-1?[5,4]:undefined)}},
      {label:'순이익', data:pnlAll, borderColor:DB_COLORS.green, backgroundColor:DB_COLORS.green+'15', fill:true, tension:0.4,
       pointRadius:3, pointHoverRadius:7, pointHitRadius:18,
       pointBackgroundColor:pnlAll.map((_,i)=>i<confirmed?DB_COLORS.green:DB_COLORS.surface), pointBorderColor:DB_COLORS.green,
       segment:{borderDash:ctx=>(ctx.p0DataIndex>=confirmed-1?[5,4]:undefined)}},
    ]},
    options:{responsive:true, maintainAspectRatio:false,
      interaction:{mode:'index', intersect:false, axis:'x'},
      hover:{mode:'index', intersect:false},
      plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11},padding:12}},
        tooltip:{mode:'index', intersect:false, callbacks:{label:ctx=>`${ctx.dataset.label}: ${dbFmtMwon(ctx.raw)}`}}},
      scales:{y:{beginAtZero:true,ticks:{font:{size:10},color:DB_COLORS.tickText,callback:v=>dbFmtM(v)},grid:{color:DB_COLORS.gridLine+'18'}},
        x:{ticks:{font:{size:10},color:DB_COLORS.tickText},grid:{display:false}}}}
  });
}

/* ── PERIOD CONTROL ── */
function dbSetPeriod(p) {
  dbPeriod = p;
  const now = new Date();
  if (p==='month') dbSub = now.getMonth()+1;
  else if (p==='quarter') dbSub = Math.ceil((now.getMonth()+1)/3);
  else if (p==='half') dbSub = now.getMonth()+1 <= 6 ? 1 : 2;
  else dbSub = 0;
  document.querySelectorAll('#dbPeriodGroup button').forEach(b => {
    const isActive = b.dataset.period === p;
    b.className = `px-3 py-2 text-sm font-medium ${isActive ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'}`;
  });
  dbPopulateSub();
  dbRender();
}

/* ── EXPORT ── */
function dbExportCsv() {
  if (typeof BmsExport === 'undefined') { alert('내보내기 유틸이 로드되지 않았습니다.'); return; }
  const months = dbGetRange(dbYear, dbPeriod, dbSub);
  const yearData = DB_RAW[dbYear] || [];
  const rows = [['월', '매출(백만)', '비용(백만)', '부가세(백만)']];
  months.forEach(m => {
    const d = yearData[m-1];
    if (!d) return;
    rows.push([`${dbYear}-${String(m).padStart(2,'0')}`, d.rev, d.cost, d.vat]);
  });
  BmsExport.rows(rows, `세무리포트_${dbYear}_${dbPeriod}${dbSub?('_'+dbSub):''}.csv`);
}

/* ── INIT ── */
(function dbInit() {
  if (window.__dbInitialized) return;
  window.__dbInitialized = true;

  const shared = window.AcctReportPeriod?.load();
  if (shared && shared.pt && ['month','quarter','half','year'].includes(shared.pt)) {
    dbPeriod = shared.pt;
    if (shared.y && DB_RAW[shared.y] !== undefined) dbYear = shared.y;
    if (shared.sub > 0) dbSub = shared.sub;
    if (shared.ytd) { dbYtdMode = true; }
    document.querySelectorAll('#dbPeriodGroup button').forEach(b => {
      const isActive = b.dataset.period === dbPeriod;
      b.className = `px-3 py-2 text-sm font-medium ${isActive ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'}`;
    });
    document.getElementById('dbYearSelect').value = dbYear;
    if (dbYtdMode) {
      const btn = document.getElementById('dbYtdBtn');
      btn.className = 'px-3 py-2 text-sm font-medium rounded-lg bg-primary text-white';
    }
  }

  dbPopulateSub();
  dbRender();
})();
</script>
