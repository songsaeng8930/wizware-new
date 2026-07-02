<?php
/**
 * 세무리포트 > 계정별원장 탭
 * 포함: acct_report.php 쉘에서 include
 */
$pdo = getDBConnection();

$categories = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT ac.code, ac.name, ac.type, ac.parent_code,
                   pg.name AS group_name
            FROM account_categories ac
            LEFT JOIN account_categories pg ON ac.parent_code = pg.code
            WHERE ac.is_active = 1
              AND ac.code NOT LIKE 'G\\_%' ESCAPE '\\\\'
            ORDER BY FIELD(ac.type, '자산','부채','자본','매출','매입','비용','수익'), ac.sort_order, ac.code
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('ledger: category load failed: ' . $e->getMessage());
    }
}

$basePath = $apiBasePath ?? rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');
?>

<!-- 기간 + 계정과목 선택 -->
<div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex rounded-lg overflow-hidden border border-slate-700">
            <button onclick="setLedgerPeriod('week')" data-pt="week" class="ledger-pt-btn zm-tab px-3 py-2 text-sm">주별</button>
            <button onclick="setLedgerPeriod('month')" data-pt="month" class="ledger-pt-btn zm-tab zm-tab-active px-3 py-2 text-sm">월별</button>
            <button onclick="setLedgerPeriod('quarter')" data-pt="quarter" class="ledger-pt-btn zm-tab px-3 py-2 text-sm">분기</button>
            <button onclick="setLedgerPeriod('half')" data-pt="half" class="ledger-pt-btn zm-tab px-3 py-2 text-sm">반기</button>
            <button onclick="setLedgerPeriod('year')" data-pt="year" class="ledger-pt-btn zm-tab px-3 py-2 text-sm">연간</button>
        </div>

        <select id="ledgerYear" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="loadLedger()">
            <?php for ($y = $currentYear; $y >= 2024; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?>년</option>
            <?php endfor; ?>
        </select>

        <select id="ledgerSub" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="loadLedger()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $currentMonth ? 'selected' : '' ?>><?= $m ?>월</option>
            <?php endfor; ?>
        </select>

        <div class="relative flex-1 min-w-[260px]">
            <input type="text" id="ledgerAccountSearch" placeholder="계정과목 검색 (코드 또는 이름)"
                   class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-gray-300/30"
                   onfocus="showAccountDropdown()" oninput="filterAccountDropdown()">
            <div id="accountDropdown" class="hidden absolute z-30 mt-1 w-full max-h-64 overflow-y-auto bg-slate-900 border border-slate-700 rounded-lg shadow-xl">
                <?php
                $currentType = '';
                foreach ($categories as $cat):
                    if ($cat['type'] !== $currentType):
                        $currentType = $cat['type'];
                ?>
                <div class="px-3 py-1.5 text-xs font-bold text-slate-500 bg-slate-950 sticky top-0"><?= $currentType ?></div>
                <?php endif; ?>
                <button type="button"
                        class="account-option w-full text-left px-3 py-2 text-sm hover:bg-slate-800 transition-colors flex items-center gap-2"
                        data-code="<?= htmlspecialchars($cat['code']) ?>"
                        data-name="<?= htmlspecialchars($cat['name']) ?>"
                        data-type="<?= htmlspecialchars($cat['type']) ?>"
                        data-group="<?= htmlspecialchars($cat['group_name'] ?? '') ?>"
                        onclick="selectAccount(this)">
                    <span class="text-slate-500 tabular-nums w-14 shrink-0"><?= htmlspecialchars($cat['code']) ?></span>
                    <span class="text-slate-200"><?= htmlspecialchars($cat['name']) ?></span>
                    <?php if ($cat['group_name']): ?>
                    <span class="text-xs text-slate-600 ml-auto"><?= htmlspecialchars($cat['group_name']) ?></span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center gap-2 ml-auto">
            <label class="text-xs text-slate-500">소계</label>
            <select id="subtotalUnit" onchange="reRenderLedger()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-2 py-1.5 text-xs">
                <option value="">없음</option>
                <option value="week">주별</option>
                <option value="month" selected>월별</option>
                <option value="quarter">분기별</option>
                <option value="half">반기별</option>
            </select>
            <button onclick="exportLedgerCSV()" class="btn btn-secondary btn-sm">
                <i data-lucide="download" class="w-4 h-4"></i>CSV
            </button>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>출력
            </button>
        </div>
    </div>
</div>

<!-- 2열 레이아웃: 좌측 계정목록 + 우측 원장 -->
<div class="flex gap-5">
    <!-- 좌측: 계정과목 목록 패널 -->
    <div class="w-60 shrink-0 hidden lg:block">
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden sticky top-20">
            <div class="px-4 py-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-slate-200 flex items-center gap-2">
                    <i data-lucide="folder-tree" class="w-4 h-4 text-primary"></i>
                    계정과목
                </h3>
                <input type="text" id="acctListFilter" placeholder="검색..."
                       class="mt-2 w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1.5 text-xs text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-1 focus:ring-gray-300/30"
                       oninput="filterAcctList()">
            </div>
            <button type="button" id="acctAllBtn" onclick="backToSummary()"
                    class="w-full text-left px-4 py-2.5 text-xs font-medium text-slate-300 hover:bg-slate-800/60 border-b border-slate-800 flex items-center gap-2 transition-colors">
                <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-primary"></i>
                전체 계정 보기
            </button>
            <div class="overflow-y-auto max-h-[calc(100vh-300px)]" id="acctListBody">
                <?php
                $prevType = '';
                foreach ($categories as $cat):
                    if ($cat['type'] !== $prevType):
                        $prevType = $cat['type'];
                ?>
                <div class="acct-type-hdr px-3 py-2 text-xs font-bold text-slate-500 bg-slate-950/80 sticky top-0 backdrop-blur-sm">
                    <?= htmlspecialchars($prevType) ?>
                </div>
                <?php endif; ?>
                <button type="button"
                        class="acct-list-item w-full text-left px-3 py-2 text-xs hover:bg-slate-800/60 transition-colors flex items-center gap-1.5 border-l-2 border-transparent"
                        data-code="<?= htmlspecialchars($cat['code']) ?>"
                        data-name="<?= htmlspecialchars($cat['name']) ?>"
                        onclick="selectFromList(this)">
                    <span class="tabular-nums w-11 shrink-0 text-slate-500"><?= htmlspecialchars($cat['code']) ?></span>
                    <span class="text-slate-300 truncate"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="acct-count ml-auto text-slate-600 tabular-nums hidden"></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 우측: 원장 콘텐츠 -->
    <div class="flex-1 min-w-0">
        <!-- 계정 요약 카드 (전체/개별 공통) -->
        <div id="ledgerSummary" class="mb-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <p class="text-xs text-slate-400 mb-1" id="sumLabel1">계정과목</p>
                    <p class="text-base font-bold text-slate-100" id="sumAccountName">-</p>
                    <p class="text-xs text-slate-500" id="sumAccountMeta">-</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <p class="text-xs text-slate-400 mb-1" id="sumLabel2">기초잔액</p>
                    <p class="text-lg font-bold tabular-nums text-slate-100" id="sumOpening">0</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <p class="text-xs text-slate-400 mb-1" id="sumLabel3">기간 증감</p>
                    <p class="text-lg font-bold tabular-nums" id="sumChange">0</p>
                    <p class="text-xs text-slate-500" id="sumChangeDetail">차변 0 / 대변 0</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <p class="text-xs text-slate-400 mb-1" id="sumLabel4">기말잔액</p>
                    <p class="text-lg font-bold tabular-nums text-primary" id="sumClosing">0</p>
                </div>
            </div>
        </div>

        <!-- 전체 계정 요약 (계정 미선택 시) -->
        <div id="ledgerPlaceholder">
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                        <i data-lucide="list" class="w-4 h-4 text-primary"></i>
                        전체 계정별 요약
                    </h3>
                    <span class="text-xs text-slate-500" id="summaryPeriodLabel"></span>
                </div>
                <div id="summaryLoading" class="py-12 text-center">
                    <div class="inline-flex flex-col items-center gap-3">
                        <div class="w-8 h-8 rounded-full border-2 border-slate-700 border-t-primary animate-spin"></div>
                        <p class="text-sm text-slate-400">계정 요약을 불러오는 중</p>
                    </div>
                </div>
                <div id="summaryTableWrap" class="hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-800/50 text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium w-20">코드</th>
                                    <th class="px-4 py-3 text-left font-medium">계정과목</th>
                                    <th class="px-4 py-3 text-left font-medium w-20">유형</th>
                                    <th class="px-4 py-3 text-right font-medium w-32">차변</th>
                                    <th class="px-4 py-3 text-right font-medium w-32">대변</th>
                                    <th class="px-4 py-3 text-right font-medium w-36">잔액</th>
                                    <th class="px-4 py-3 text-right font-medium w-16">건수</th>
                                </tr>
                            </thead>
                            <tbody id="summaryBody" class="text-slate-300"></tbody>
                        </table>
                    </div>
                </div>
                <div id="summaryEmpty" class="hidden py-12 text-center">
                    <i data-lucide="inbox" class="w-10 h-10 text-slate-600 mx-auto mb-3"></i>
                    <p class="text-sm text-slate-400">선택한 기간에 거래 내역이 없어요</p>
                </div>
            </div>
        </div>

        <!-- 원장 테이블 -->
        <div id="ledgerTableWrap" class="hidden">
            <div class="mb-3">
                <button onclick="backToSummary()" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-200 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>전체 계정 요약으로
                </button>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="ledgerTable">
                        <thead class="bg-slate-800/50 text-slate-300">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium w-24">일자</th>
                                <th class="px-3 py-3 text-left font-medium w-24">전표번호</th>
                                <th class="px-3 py-3 text-left font-medium">적요</th>
                                <th class="px-3 py-3 text-left font-medium w-28">거래처</th>
                                <th class="px-3 py-3 text-left font-medium w-28">상대계정</th>
                                <th class="px-3 py-3 text-right font-medium w-28">차변</th>
                                <th class="px-3 py-3 text-right font-medium w-28">대변</th>
                                <th class="px-3 py-3 text-right font-medium w-32">잔액</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerBody" class="text-slate-300"></tbody>
                    </table>
                </div>
            </div>
            <p class="text-xs text-slate-600 mt-2" id="ledgerInfo"></p>
        </div>

        <!-- 로딩 -->
        <div id="ledgerLoading" class="hidden py-16 text-center">
            <div class="inline-flex flex-col items-center gap-3">
                <div class="w-10 h-10 rounded-full border-2 border-slate-700 border-t-primary animate-spin"></div>
                <p class="text-sm text-slate-400">원장 데이터를 불러오는 중</p>
            </div>
        </div>
    </div>
</div>

<script>
const LEDGER_API = '<?= $basePath ?>/api/bankapi.php';
let ledgerPeriodType = 'month';
let selectedAccountCode = '';
let selectedAccountName = '';

function fmt(n) {
    return Math.abs(n).toLocaleString('ko-KR');
}

// ─── 기간 유형 ───
function setLedgerPeriod(type) {
    ledgerPeriodType = type;
    document.querySelectorAll('.ledger-pt-btn').forEach(b => {
        b.classList.toggle('zm-tab-active', b.dataset.pt === type);
    });
    updateSubSelect();
    _syncLedgerPeriod();
    loadLedger();
}
function _syncLedgerPeriod() {
    if (!window.AcctReportPeriod) return;
    const y = parseInt(document.getElementById('ledgerYear').value);
    const sub = parseInt(document.getElementById('ledgerSub').value) || 0;
    AcctReportPeriod.save(ledgerPeriodType, y, sub);
}

function updateSubSelect() {
    const sel = document.getElementById('ledgerSub');
    const year = parseInt(document.getElementById('ledgerYear').value);
    sel.innerHTML = '';
    if (ledgerPeriodType === 'week') {
        for (let m = 1; m <= 12; m++) {
            sel.innerHTML += `<option value="${m}" ${m === <?= $currentMonth ?> ? 'selected' : ''}>${m}월</option>`;
        }
        sel.classList.remove('hidden');
    } else if (ledgerPeriodType === 'month') {
        for (let m = 1; m <= 12; m++) {
            sel.innerHTML += `<option value="${m}" ${m === <?= $currentMonth ?> ? 'selected' : ''}>${m}월</option>`;
        }
        sel.classList.remove('hidden');
    } else if (ledgerPeriodType === 'quarter') {
        for (let q = 1; q <= 4; q++) sel.innerHTML += `<option value="${q}">${q}분기</option>`;
        sel.classList.remove('hidden');
    } else if (ledgerPeriodType === 'half') {
        sel.innerHTML = '<option value="1">상반기</option><option value="2">하반기</option>';
        sel.classList.remove('hidden');
    } else {
        sel.classList.add('hidden');
    }
}

function getDateRange() {
    const year = parseInt(document.getElementById('ledgerYear').value);
    const sub = parseInt(document.getElementById('ledgerSub').value) || 1;
    let startMonth, endMonth;

    if (ledgerPeriodType === 'week') {
        startMonth = sub;
        endMonth = sub;
    } else if (ledgerPeriodType === 'quarter') {
        startMonth = (sub - 1) * 3 + 1;
        endMonth = sub * 3;
    } else if (ledgerPeriodType === 'half') {
        startMonth = sub === 1 ? 1 : 7;
        endMonth = sub === 1 ? 6 : 12;
    } else if (ledgerPeriodType === 'year') {
        startMonth = 1;
        endMonth = 12;
    } else {
        startMonth = sub;
        endMonth = sub;
    }

    const dateFrom = `${year}-${String(startMonth).padStart(2,'0')}-01`;
    const lastDay = new Date(year, endMonth, 0).getDate();
    const dateTo = `${year}-${String(endMonth).padStart(2,'0')}-${String(lastDay).padStart(2,'0')}`;
    return { dateFrom, dateTo };
}

// ─── 소계/누계 기간 키 계산 ───
function getSubtotalKey(dateStr, unit) {
    const d = new Date(dateStr);
    const y = d.getFullYear();
    const m = d.getMonth() + 1;
    if (unit === 'week') {
        const jan1 = new Date(y, 0, 1);
        const dayOfYear = Math.floor((d - jan1) / 86400000) + 1;
        const weekNum = Math.ceil((dayOfYear + jan1.getDay()) / 7);
        return { key: `${y}-W${weekNum}`, label: `${m}월 ${weekNum}주차` };
    }
    if (unit === 'month') return { key: `${y}-${m}`, label: `${m}월계` };
    if (unit === 'quarter') {
        const q = Math.ceil(m / 3);
        return { key: `${y}-Q${q}`, label: `${q}분기계` };
    }
    if (unit === 'half') {
        const h = m <= 6 ? 1 : 2;
        return { key: `${y}-H${h}`, label: h === 1 ? '상반기계' : '하반기계' };
    }
    return { key: `${y}`, label: `${y}년계` };
}

// ─── 계정과목 드롭다운 (상단 검색바) ───
function showAccountDropdown() {
    document.getElementById('accountDropdown').classList.remove('hidden');
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('ledgerAccountSearch');
    const dd = document.getElementById('accountDropdown');
    if (wrap && dd && !wrap.contains(e.target) && !dd.contains(e.target)) {
        dd.classList.add('hidden');
    }
});

function filterAccountDropdown() {
    const q = document.getElementById('ledgerAccountSearch').value.toLowerCase();
    document.querySelectorAll('.account-option').forEach(btn => {
        const code = btn.dataset.code.toLowerCase();
        const name = btn.dataset.name.toLowerCase();
        btn.classList.toggle('hidden', q && !code.includes(q) && !name.includes(q));
    });
    showAccountDropdown();
}

function selectAccount(btn) {
    selectedAccountCode = btn.dataset.code;
    selectedAccountName = btn.dataset.name;
    document.getElementById('ledgerAccountSearch').value = `${selectedAccountCode} ${selectedAccountName}`;
    document.getElementById('accountDropdown').classList.add('hidden');
    highlightListItem(selectedAccountCode);
    loadLedger();
}

// ─── 좌측 계정목록 패널 ───
function filterAcctList() {
    const q = document.getElementById('acctListFilter').value.toLowerCase();
    document.querySelectorAll('.acct-list-item').forEach(btn => {
        const code = btn.dataset.code.toLowerCase();
        const name = btn.dataset.name.toLowerCase();
        btn.classList.toggle('hidden', q && !code.includes(q) && !name.includes(q));
    });
    document.querySelectorAll('.acct-type-hdr').forEach(hdr => {
        if (!q) { hdr.classList.remove('hidden'); return; }
        let next = hdr.nextElementSibling;
        let hasVisible = false;
        while (next && !next.classList.contains('acct-type-hdr')) {
            if (!next.classList.contains('hidden')) hasVisible = true;
            next = next.nextElementSibling;
        }
        hdr.classList.toggle('hidden', !hasVisible);
    });
}

function selectFromList(btn) {
    selectedAccountCode = btn.dataset.code;
    selectedAccountName = btn.dataset.name;
    document.getElementById('ledgerAccountSearch').value = `${selectedAccountCode} ${selectedAccountName}`;
    highlightListItem(selectedAccountCode);
    loadLedger();
}

function highlightListItem(code) {
    document.querySelectorAll('.acct-list-item').forEach(btn => {
        const active = btn.dataset.code === code;
        btn.classList.toggle('border-l-primary', active);
        btn.classList.toggle('bg-slate-800/60', active);
        btn.classList.toggle('border-transparent', !active);
    });
}

function updateListCounts(summaryData) {
    const countMap = {};
    (summaryData || []).forEach(r => { countMap[r.code] = r.tx_count; });
    document.querySelectorAll('.acct-list-item').forEach(btn => {
        const cnt = countMap[btn.dataset.code];
        const span = btn.querySelector('.acct-count');
        if (span) {
            if (cnt && cnt > 0) {
                span.textContent = cnt;
                span.classList.remove('hidden');
            } else {
                span.classList.add('hidden');
            }
        }
    });
}

// ─── 전체 요약 로드 ───
async function loadSummary() {
    const { dateFrom, dateTo } = getDateRange();
    const summaryLoading = document.getElementById('summaryLoading');
    const summaryTable = document.getElementById('summaryTableWrap');
    const summaryEmpty = document.getElementById('summaryEmpty');
    const label = document.getElementById('summaryPeriodLabel');

    summaryLoading.classList.remove('hidden');
    summaryTable.classList.add('hidden');
    summaryEmpty.classList.add('hidden');
    if (label) label.textContent = `${dateFrom} ~ ${dateTo}`;

    try {
        const url = `${LEDGER_API}?action=account_summary&date_from=${dateFrom}&date_to=${dateTo}`;
        const resp = await fetch(url);
        const json = await resp.json();

        summaryLoading.classList.add('hidden');

        if (!json.success || !json.data?.length) {
            summaryEmpty.classList.remove('hidden');
            updateListCounts([]);
            return;
        }

        const totals = renderSummaryTable(json.data);
        updateListCounts(json.data);
        renderSummaryCards(json.data, totals);
        summaryTable.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (err) {
        console.error('Summary fetch error:', err);
        summaryLoading.classList.add('hidden');
        summaryEmpty.classList.remove('hidden');
    }
}

// 전체 보기 카드 (계정 미선택 시)
function renderSummaryCards(data, totals) {
    document.getElementById('sumLabel1').textContent = '조회 계정';
    document.getElementById('sumAccountName').textContent = `전체 ${data.length}개`;
    document.getElementById('sumAccountMeta').textContent = '계정과목 클릭 시 상세';

    document.getElementById('sumLabel2').textContent = '총 차변';
    document.getElementById('sumOpening').textContent = '₩' + fmt(totals.debit);
    document.getElementById('sumOpening').className = 'text-lg font-bold tabular-nums text-blue-400';

    document.getElementById('sumLabel3').textContent = '총 대변';
    const changeEl = document.getElementById('sumChange');
    changeEl.textContent = '₩' + fmt(totals.credit);
    changeEl.className = 'text-lg font-bold tabular-nums text-red-400';
    document.getElementById('sumChangeDetail').textContent = `총 ${totals.count}건`;

    document.getElementById('sumLabel4').textContent = '거래 건수';
    document.getElementById('sumClosing').textContent = totals.count + '건';
    document.getElementById('sumClosing').className = 'text-lg font-bold tabular-nums text-primary';
}

function renderSummaryTable(data) {
    const tbody = document.getElementById('summaryBody');

    let totalDebit = 0, totalCredit = 0;
    let html = '';
    let prevType = '';

    data.forEach(r => {
        if (r.type !== prevType) {
            prevType = r.type;
            html += `<tr class="bg-slate-800/30"><td colspan="7" class="px-4 py-2 text-xs font-bold text-slate-300 uppercase tracking-wider">${r.type}</td></tr>`;
        }
        totalDebit += r.debit;
        totalCredit += r.credit;
        const balColor = r.balance >= 0 ? 'text-slate-100' : 'text-red-400';
        html += `<tr class="border-b border-slate-800 hover:bg-slate-800/50 cursor-pointer transition-colors"
                     onclick="drillToAccount('${r.code}','${escHtml(r.name)}')">
            <td class="px-4 py-3 tabular-nums text-slate-500 text-xs">${r.code}</td>
            <td class="px-4 py-3 text-slate-200 font-medium">${escHtml(r.name)}</td>
            <td class="px-4 py-3"><span class="text-xs px-1.5 py-0.5 rounded text-slate-400 bg-slate-800">${r.type}</span></td>
            <td class="px-4 py-3 text-right tabular-nums ${r.debit > 0 ? 'text-blue-400' : 'text-slate-600'}">${r.debit > 0 ? '₩'+fmt(r.debit) : '-'}</td>
            <td class="px-4 py-3 text-right tabular-nums ${r.credit > 0 ? 'text-red-400' : 'text-slate-600'}">${r.credit > 0 ? '₩'+fmt(r.credit) : '-'}</td>
            <td class="px-4 py-3 text-right tabular-nums font-medium ${balColor}">${r.balance < 0 ? '-' : ''}₩${fmt(r.balance)}</td>
            <td class="px-4 py-3 text-right tabular-nums text-slate-500">${r.tx_count}</td>
        </tr>`;
    });

    html += `<tr class="border-t-2 border-slate-700 bg-slate-800/50 font-semibold">
        <td class="px-4 py-3" colspan="3">합계 (${data.length}개 계정)</td>
        <td class="px-4 py-3 text-right tabular-nums text-blue-400">₩${fmt(totalDebit)}</td>
        <td class="px-4 py-3 text-right tabular-nums text-red-400">₩${fmt(totalCredit)}</td>
        <td class="px-4 py-3 text-right tabular-nums text-primary">₩${fmt(Math.abs(totalDebit - totalCredit))}</td>
        <td class="px-4 py-3 text-right tabular-nums text-slate-400">${data.reduce((s,r) => s+r.tx_count, 0)}</td>
    </tr>`;

    tbody.innerHTML = html;
    return { debit: totalDebit, credit: totalCredit, count: data.reduce((s,r) => s+r.tx_count, 0) };
}

function drillToAccount(code, name) {
    selectedAccountCode = code;
    selectedAccountName = name;
    document.getElementById('ledgerAccountSearch').value = `${code} ${name}`;
    highlightListItem(code);
    loadLedger();
}

function backToSummary() {
    selectedAccountCode = '';
    selectedAccountName = '';
    document.getElementById('ledgerAccountSearch').value = '';
    document.getElementById('ledgerTableWrap').classList.add('hidden');
    document.getElementById('ledgerPlaceholder').classList.remove('hidden');
    highlightListItem('');
    loadSummary();
}

// ─── 데이터 로드 ───
async function loadLedger() {
    _syncLedgerPeriod();
    if (!selectedAccountCode) {
        loadSummary();
        return;
    }

    const { dateFrom, dateTo } = getDateRange();
    const placeholder = document.getElementById('ledgerPlaceholder');
    const loading = document.getElementById('ledgerLoading');
    const tableWrap = document.getElementById('ledgerTableWrap');

    placeholder.classList.add('hidden');
    tableWrap.classList.add('hidden');
    loading.classList.remove('hidden');

    try {
        const url = `${LEDGER_API}?action=account_ledger&account_code=${encodeURIComponent(selectedAccountCode)}&date_from=${dateFrom}&date_to=${dateTo}`;
        const resp = await fetch(url);
        const json = await resp.json();

        if (!json.success) {
            alert(json.message || '데이터 조회 실패');
            loading.classList.add('hidden');
            placeholder.classList.remove('hidden');
            return;
        }

        renderLedger(json.data);
    } catch (err) {
        console.error('Ledger fetch error:', err);
        alert('원장 데이터 조회 중 오류가 발생했습니다.');
        loading.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }
}

let lastLedgerData = null;

function reRenderLedger() {
    if (lastLedgerData) renderLedger(lastLedgerData);
}

function renderLedger(data) {
    lastLedgerData = data;
    const { account, is_debit_normal, opening_balance, closing_balance, period_debit, period_credit, transactions } = data;
    const loading = document.getElementById('ledgerLoading');
    const tableWrap = document.getElementById('ledgerTableWrap');
    const summary = document.getElementById('ledgerSummary');
    const subtotalUnit = document.getElementById('subtotalUnit').value;

    // 개별 계정 모드 · 카드 라벨 복원
    document.getElementById('sumLabel1').textContent = '계정과목';
    document.getElementById('sumLabel2').textContent = '기초잔액';
    document.getElementById('sumLabel3').textContent = '기간 증감';
    document.getElementById('sumLabel4').textContent = '기말잔액';

    document.getElementById('sumAccountName').textContent = `${account.code} ${account.name}`;
    document.getElementById('sumAccountMeta').textContent = `${account.type} · ${account.tax_type}`;
    document.getElementById('sumOpening').textContent = (opening_balance < 0 ? '-' : '') + '₩' + fmt(opening_balance);
    document.getElementById('sumOpening').className = 'text-lg font-bold tabular-nums text-slate-100';
    document.getElementById('sumClosing').textContent = (closing_balance < 0 ? '-' : '') + '₩' + fmt(closing_balance);
    document.getElementById('sumClosing').className = 'text-lg font-bold tabular-nums text-primary';

    const change = closing_balance - opening_balance;
    const changeEl = document.getElementById('sumChange');
    changeEl.textContent = (change >= 0 ? '+' : '-') + '₩' + fmt(change);
    changeEl.className = 'text-lg font-bold tabular-nums ' + (change >= 0 ? 'text-emerald-400' : 'text-red-400');
    document.getElementById('sumChangeDetail').textContent = `차변 ₩${fmt(period_debit)} / 대변 ₩${fmt(period_credit)}`;

    const tbody = document.getElementById('ledgerBody');

    // 거래 0건: 빈 표 대신 명확한 안내 (고장/로딩과 구분)
    if (!transactions.length) {
        const { dateFrom, dateTo } = getDateRange();
        const bal = opening_balance;
        tbody.innerHTML = `<tr><td colspan="8" class="py-16 text-center">
            <i data-lucide="inbox" class="w-10 h-10 text-slate-600 mx-auto mb-3"></i>
            <p class="text-sm text-slate-400">이 계정은 선택한 기간에 거래가 없어요</p>
            ${bal !== 0 ? `<p class="text-xs text-slate-600 mt-1">전기이월 잔액 ${bal < 0 ? '-' : ''}₩${fmt(bal)}</p>` : ''}
        </td></tr>`;
        document.getElementById('ledgerInfo').textContent = `${account.code} ${account.name} · ${dateFrom} ~ ${dateTo} · 0건`;
        loading.classList.add('hidden');
        summary.classList.remove('hidden');
        tableWrap.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    let html = '';
    let runningBalance = opening_balance;

    html += `<tr class="border-b border-slate-800 bg-slate-800/30">
        <td class="px-3 py-3 text-slate-400"></td>
        <td class="px-3 py-3 text-slate-500"></td>
        <td class="px-3 py-3 font-medium text-slate-200">전기이월</td>
        <td class="px-3 py-3"></td>
        <td class="px-3 py-3"></td>
        <td class="px-3 py-3 text-right tabular-nums"></td>
        <td class="px-3 py-3 text-right tabular-nums"></td>
        <td class="px-3 py-3 text-right tabular-nums font-medium text-slate-100">${runningBalance < 0 ? '-' : ''}₩${fmt(runningBalance)}</td>
    </tr>`;

    let grpDebit = 0, grpCredit = 0;
    let cumDebit = 0, cumCredit = 0;
    let prevKey = '';

    function subtotalRow(label, gd, gc, cd, cc) {
        return `<tr class="border-b border-slate-700 bg-slate-800/40">
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2 text-xs font-semibold text-amber-300">${label}</td>
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2 text-right tabular-nums text-xs font-semibold text-blue-300">${gd > 0 ? '₩'+fmt(gd) : ''}</td>
            <td class="px-3 py-2 text-right tabular-nums text-xs font-semibold text-red-300">${gc > 0 ? '₩'+fmt(gc) : ''}</td>
            <td class="px-3 py-2"></td>
        </tr>
        <tr class="border-b border-slate-700 bg-slate-800/20">
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2 text-xs font-semibold text-slate-400">누계</td>
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2"></td>
            <td class="px-3 py-2 text-right tabular-nums text-xs text-blue-400/70">${cd > 0 ? '₩'+fmt(cd) : ''}</td>
            <td class="px-3 py-2 text-right tabular-nums text-xs text-red-400/70">${cc > 0 ? '₩'+fmt(cc) : ''}</td>
            <td class="px-3 py-2"></td>
        </tr>`;
    }

    transactions.forEach((tx, idx) => {
        if (is_debit_normal) {
            runningBalance += tx.debit - tx.credit;
        } else {
            runningBalance += tx.credit - tx.debit;
        }

        if (subtotalUnit) {
            const { key, label } = getSubtotalKey(tx.transaction_date, subtotalUnit);
            if (prevKey && key !== prevKey) {
                cumDebit += grpDebit;
                cumCredit += grpCredit;
                const prevLabel = getSubtotalKey(transactions[idx-1].transaction_date, subtotalUnit).label;
                html += subtotalRow(prevLabel, grpDebit, grpCredit, cumDebit, cumCredit);
                grpDebit = 0;
                grpCredit = 0;
            }
            prevKey = key;
        }

        grpDebit += tx.debit;
        grpCredit += tx.credit;

        const debitStr = tx.debit > 0 ? `₩${fmt(tx.debit)}` : '';
        const creditStr = tx.credit > 0 ? `₩${fmt(tx.credit)}` : '';
        const balStr = `${runningBalance < 0 ? '-' : ''}₩${fmt(runningBalance)}`;

        html += `<tr class="border-b border-slate-800 hover:bg-slate-800/30">
            <td class="px-3 py-3 tabular-nums text-slate-400 text-xs whitespace-nowrap">${tx.transaction_date}</td>
            <td class="px-3 py-3 tabular-nums text-xs text-slate-500 whitespace-nowrap">${escHtml(tx.ref_no || '')}</td>
            <td class="px-3 py-3 text-slate-200">${escHtml(tx.description || '')}</td>
            <td class="px-3 py-3 text-xs text-slate-400 truncate max-w-[120px]" title="${escHtml(tx.counterparty || '')}">${escHtml(tx.counterparty || '')}</td>
            <td class="px-3 py-3 text-xs text-slate-400 truncate max-w-[120px]" title="${escHtml(tx.counter_name || '')}">${escHtml(tx.counter_name || '')}</td>
            <td class="px-3 py-3 text-right tabular-nums ${tx.debit > 0 ? 'text-blue-400' : ''}">${debitStr}</td>
            <td class="px-3 py-3 text-right tabular-nums ${tx.credit > 0 ? 'text-red-400' : ''}">${creditStr}</td>
            <td class="px-3 py-3 text-right tabular-nums font-medium text-slate-100">${balStr}</td>
        </tr>`;
    });

    if (subtotalUnit && transactions.length > 0) {
        cumDebit += grpDebit;
        cumCredit += grpCredit;
        const lastLabel = getSubtotalKey(transactions[transactions.length-1].transaction_date, subtotalUnit).label;
        html += subtotalRow(lastLabel, grpDebit, grpCredit, cumDebit, cumCredit);
    }

    html += `<tr class="border-t-2 border-slate-700 bg-slate-800/50 font-semibold">
        <td class="px-3 py-3"></td>
        <td class="px-3 py-3"></td>
        <td class="px-3 py-3 text-slate-100">합계</td>
        <td class="px-3 py-3 text-xs text-slate-500">${transactions.length}건</td>
        <td class="px-3 py-3"></td>
        <td class="px-3 py-3 text-right tabular-nums text-blue-400">₩${fmt(period_debit)}</td>
        <td class="px-3 py-3 text-right tabular-nums text-red-400">₩${fmt(period_credit)}</td>
        <td class="px-3 py-3 text-right tabular-nums text-primary">₩${fmt(closing_balance)}</td>
    </tr>`;

    tbody.innerHTML = html;

    const { dateFrom, dateTo } = getDateRange();
    document.getElementById('ledgerInfo').textContent =
        `${account.code} ${account.name} · ${dateFrom} ~ ${dateTo} · ${transactions.length}건`;

    loading.classList.add('hidden');
    summary.classList.remove('hidden');
    tableWrap.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ─── CSV 내보내기 ───
function exportLedgerCSV() {
    const table = document.getElementById('ledgerTable');
    if (!table) return;

    let csv = '﻿';
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        cells.forEach(cell => {
            let val = cell.textContent.trim().replace(/"/g, '""');
            rowData.push('"' + val + '"');
        });
        csv += rowData.join(',') + '\n';
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `계정별원장_${selectedAccountCode}_${selectedAccountName}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}

// 초기화 · 공유 기간 상태 복원
const _sharedPeriod = window.AcctReportPeriod?.load();
if (_sharedPeriod && _sharedPeriod.pt && ['month','quarter','half','year'].includes(_sharedPeriod.pt)) {
    ledgerPeriodType = _sharedPeriod.pt;
    document.querySelectorAll('.ledger-pt-btn').forEach(b => {
        b.classList.toggle('zm-tab-active', b.dataset.pt === ledgerPeriodType);
    });
    if (_sharedPeriod.y) document.getElementById('ledgerYear').value = _sharedPeriod.y;
}
updateSubSelect();
if (_sharedPeriod && _sharedPeriod.sub > 0) {
    const subSel = document.getElementById('ledgerSub');
    if (subSel && !subSel.classList.contains('hidden')) subSel.value = _sharedPeriod.sub;
}
loadSummary();
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
