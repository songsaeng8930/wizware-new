<?php
/**
 * 재무관리 > 카드관리 > 정산 탭
 * acct_card.php 에서 include 되는 서브 뷰
 */
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!function_exists('hasMenuPermission')) {
    require_once __DIR__ . '/../includes/permissions.php';
}
if (!function_exists('getOrgLabel')) {
    require_once __DIR__ . '/../includes/org_hierarchy.php';
}

if (!hasMenuPermission('accounting.settle', 'view')) {
    echo '<div class="text-center py-16 text-slate-400">접근 권한이 없습니다.</div>';
    return;
}

$pdo = $pdo ?? getDBConnection();
$hasDB = false;
if ($pdo) {
    try { $pdo->query('SELECT 1 FROM card_expenses LIMIT 1'); $hasDB = true; } catch (PDOException $e) { $hasDB = false; }
}

$currentUserName = $_SESSION['user']['name'] ?? '관리자';

$divisions = [
    ['key' => 'sales',   'label' => '영업본부',     'match' => ['영업', '국내영업', '해외영업']],
    ['key' => 'tech',    'label' => '기술개발본부', 'match' => ['기술', '개발', 'QA']],
    ['key' => 'mgmt',    'label' => '경영지원본부', 'match' => ['경영', '인사', '재무', '회계']],
];
?>

<!-- 3본부 정산 요약 -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5" id="divSummaryCards">
    <?php foreach ($divisions as $d): ?>
    <button type="button" class="div-card text-left bg-slate-900 rounded-xl border border-slate-800 p-5 hover:border-gray-400 transition-colors"
            data-div="<?= htmlspecialchars($d['key']) ?>">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary-light text-primary">
                <i data-lucide="building-2" class="w-4 h-4"></i>
            </span>
            <h3 class="text-sm font-bold text-slate-100"><?= htmlspecialchars($d['label']) ?></h3>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
            <div>
                <p class="text-[10px] text-slate-500">미정산</p>
                <p class="text-lg font-bold tabular-nums text-amber-500" data-stat="unsettled-<?= $d['key'] ?>">0</p>
            </div>
            <div>
                <p class="text-[10px] text-slate-500">정산완료</p>
                <p class="text-lg font-bold tabular-nums text-emerald-600" data-stat="settled-<?= $d['key'] ?>">0</p>
            </div>
            <div>
                <p class="text-[10px] text-slate-500">총 지출</p>
                <p class="text-lg font-bold tabular-nums text-primary" data-stat="total-<?= $d['key'] ?>">&#8361;0</p>
            </div>
        </div>
    </button>
    <?php endforeach; ?>
</div>

<!-- 본부 필터 칩 -->
<div class="flex items-center gap-2 flex-wrap mb-5">
    <span class="text-xs text-slate-500"><?= htmlspecialchars(isOrgLevelEnabled('division') ? getOrgLabel('division') : '소속') ?></span>
    <button type="button" class="settle-div-chip active" data-div="">전체</button>
    <?php foreach ($divisions as $d): ?>
    <button type="button" class="settle-div-chip" data-div="<?= htmlspecialchars($d['key']) ?>"><?= htmlspecialchars($d['label']) ?></button>
    <?php endforeach; ?>
</div>

<style>
.settle-div-chip { display:inline-flex; align-items:center; padding:4px 12px; font-size:12px; font-weight:500; border-radius:9999px; background:transparent; color:var(--zm-text-muted); border:1px solid var(--zm-border); transition:all .15s; cursor:pointer; }
.settle-div-chip:hover { border-color:var(--zm-text-subtle, #94a3b8); color:var(--zm-text-default); }
.settle-div-chip.active { background:var(--zm-primary); color:#fff; border-color:var(--zm-primary); }
.settle-badge-ok { background:var(--zm-success-bg); color:var(--zm-success-fg); }
.settle-badge-warn { background:var(--zm-amber-bg); color:var(--zm-amber-fg); }
.settle-comp-ok { border-color:var(--zm-status-ok-fg); color:var(--zm-status-ok-fg); }
.settle-comp-warn { border-color:var(--zm-status-warning-fg, #d97706); color:var(--zm-status-warning-fg, #d97706); }
.settle-comp-bad { border-color:var(--zm-status-danger-fg); color:var(--zm-status-danger-fg); }
.settle-comp-default { border-color:var(--zm-border); color:var(--zm-text-muted); }
</style>

<!-- 필터 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-800 bg-slate-950/80">
        <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">정산 필터</span>
    </div>
    <div class="px-5 py-4">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-slate-400 shrink-0">카드</label>
                <input type="text" id="sFilterCard" class="text-sm border border-slate-800 rounded-lg px-3 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none min-w-[140px]" placeholder="카드별칭 검색">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-slate-400 shrink-0">사용일</label>
                <div class="flex items-center gap-1.5">
                    <input type="date" id="sFilterDateFrom" class="text-sm border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none">
                    <span class="text-slate-600">~</span>
                    <input type="date" id="sFilterDateTo" class="text-sm border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-slate-400 shrink-0">정산</label>
                <div class="inline-flex rounded-lg border border-slate-800 overflow-hidden" id="settledToggle">
                    <label class="zm-tab settle-toggle-chip px-3 py-1.5 text-sm cursor-pointer transition-colors zm-tab-active">
                        <input type="radio" name="sFilterSettled" value="" checked class="hidden"> 전체
                    </label>
                    <label class="zm-tab settle-toggle-chip px-3 py-1.5 text-sm cursor-pointer transition-colors border-l border-slate-800">
                        <input type="radio" name="sFilterSettled" value="0" class="hidden"> 미정산
                    </label>
                    <label class="zm-tab settle-toggle-chip px-3 py-1.5 text-sm cursor-pointer transition-colors border-l border-slate-800">
                        <input type="radio" name="sFilterSettled" value="1" class="hidden"> 정산완료
                    </label>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-slate-400 shrink-0">규정</label>
                <select id="sFilterCompliance" class="text-sm border border-slate-800 rounded-lg pl-3 pr-8 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none">
                    <option value="">전체</option>
                    <option value="준수">준수</option>
                    <option value="미준수">미준수</option>
                    <option value="미확인">미확인</option>
                </select>
            </div>
            <div class="flex items-center gap-2 ml-auto">
                <button type="button" onclick="resetSettlementFilters()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-slate-400 rounded-lg hover:bg-slate-800 transition-colors">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> 초기화
                </button>
                <button type="button" onclick="searchSettlements()" class="inline-flex items-center gap-1 px-4 py-1.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 지출내역 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
        <span class="text-sm text-slate-400">총 <strong id="expTotalCount" class="text-primary">0</strong>건</span>
        <button onclick="batchSettle()" class="px-3 py-1.5 text-sm rounded-lg bg-primary text-white hover:opacity-90 transition-colors">
            <i data-lucide="check-check" class="mr-1 w-4 h-4 inline"></i> 일괄 정산처리
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full emp-table">
            <thead>
                <tr class="border-b border-slate-800">
                    <th class="px-3 py-3 w-8"><input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)" class="emp-checkbox"></th>
                    <th class="px-3 py-3 text-left">카드별칭</th>
                    <th class="px-3 py-3 text-left">승인번호</th>
                    <th class="px-3 py-3 text-center">사용일</th>
                    <th class="px-3 py-3 text-left">등록자</th>
                    <th class="px-3 py-3 text-left">부서</th>
                    <th class="px-3 py-3 text-center">사용구분</th>
                    <th class="px-3 py-3 text-left">항목</th>
                    <th class="px-3 py-3 text-right">사용금액</th>
                    <th class="px-3 py-3 text-left">적요</th>
                    <th class="px-3 py-3 text-left">사용자</th>
                    <th class="px-3 py-3 text-left">사업코드</th>
                    <th class="px-3 py-3 text-left">문서번호</th>
                    <th class="px-3 py-3 text-center">최종업데이트</th>
                    <th class="px-3 py-3 text-left">업데이트작성자</th>
                    <th class="px-3 py-3 text-center">규정준수</th>
                    <th class="px-3 py-3 text-center">정산여부</th>
                </tr>
            </thead>
            <tbody id="settlementBody"></tbody>
        </table>
    </div>
    <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="expPagination"></div>
</div>

<!-- 승인내역 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
        <span class="text-sm text-slate-400">총 <strong id="aprTotalCount" class="text-primary">0</strong>건</span>
    </div>
    <div class="px-5 py-3 border-b border-slate-800 bg-slate-950/80 flex gap-3 items-center">
        <input type="text" id="aprFilterCard" class="text-sm border border-slate-800 rounded-lg px-3 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none w-48" placeholder="카드별칭 검색">
        <input type="text" id="aprFilterNumber" class="text-sm border border-slate-800 rounded-lg px-3 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none w-48" placeholder="승인번호 검색">
        <button onclick="searchApprovals()" class="inline-flex items-center gap-1 px-4 py-1.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
            <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full emp-table">
            <thead>
                <tr class="border-b border-slate-800">
                    <th class="px-4 py-3 text-left">카드별칭</th>
                    <th class="px-4 py-3 text-left">승인번호</th>
                    <th class="px-4 py-3 text-left">가맹점명</th>
                    <th class="px-4 py-3 text-right">승인금액</th>
                    <th class="px-4 py-3 text-center">승인일시</th>
                    <th class="px-4 py-3 text-center">상태</th>
                </tr>
            </thead>
            <tbody id="approvalBody"></tbody>
        </table>
    </div>
    <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="aprPagination"></div>
</div>

<script>
(function() {
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const CURRENT_USER = <?= json_encode($currentUserName, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const DIVISIONS = <?= json_encode($divisions, JSON_UNESCAPED_UNICODE) ?>;
let currentDivision = '';

let allSettlements = [];
let filteredSettlements = [];
let allApprovals = [];
let filteredApprovals = [];
let expPage = 1, aprPage = 1;
const PER_PAGE = 15;

const SAMPLE_SETTLEMENTS = [
    {id:1, card_alias:'영업팀 공용카드', card_number:'****-5678', approval_number:'A20260618-001', usage_date:'2026-06-18', registrant_name:'서영업', department:'영업팀', usage_type:'법인', category:'접대비', amount:285000, description:'신라호텔 뷔페 · 거래처 접대', user_name:'서영업', business_code:'', document_number:'', is_settled:0, compliance_status:'준수', settlement_updater:'', settlement_date:''},
    {id:2, card_alias:'개발팀 카드', card_number:'****-9012', approval_number:'A20260617-001', usage_date:'2026-06-17', registrant_name:'강개발', department:'개발팀', usage_type:'법인', category:'도서구입비', amount:52800, description:'YES24 기술서적', user_name:'강개발', business_code:'', document_number:'', is_settled:1, compliance_status:'준수', settlement_updater:'오재무', settlement_date:'2026-06-19'},
    {id:3, card_alias:'김대표 법인카드', card_number:'****-1234', approval_number:'A20260616-001', usage_date:'2026-06-16', registrant_name:'한인사', department:'인사팀', usage_type:'법인', category:'복리후생비', amount:45000, description:'한솥도시락 야근 석식', user_name:'한인사', business_code:'', document_number:'', is_settled:0, compliance_status:'준수', settlement_updater:'', settlement_date:''},
    {id:4, card_alias:'영업팀 공용카드', card_number:'****-5678', approval_number:'A20260615-001', usage_date:'2026-06-15', registrant_name:'서영업', department:'국내영업팀', usage_type:'법인', category:'출장비', amount:118000, description:'KTX 부산 출장', user_name:'서영업', business_code:'', document_number:'', is_settled:1, compliance_status:'준수', settlement_updater:'오재무', settlement_date:'2026-06-18'},
    {id:5, card_alias:'경영지원 카드', card_number:'****-3456', approval_number:'A20260614-001', usage_date:'2026-06-14', registrant_name:'정지원', department:'경영지원실', usage_type:'법인', category:'소모품비', amount:124500, description:'오피스디포 사무용품', user_name:'정지원', business_code:'', document_number:'', is_settled:1, compliance_status:'준수', settlement_updater:'오재무', settlement_date:'2026-06-17'},
    {id:6, card_alias:'개발팀 카드', card_number:'****-9012', approval_number:'A20260613-001', usage_date:'2026-06-13', registrant_name:'박기술', department:'기술개발본부', usage_type:'법인', category:'클라우드 비용', amount:890000, description:'AWS 6월 서버 비용', user_name:'박기술', business_code:'', document_number:'', is_settled:0, compliance_status:'미확인', settlement_updater:'', settlement_date:''},
    {id:7, card_alias:'영업팀 공용카드', card_number:'****-5678', approval_number:'A20260612-001', usage_date:'2026-06-12', registrant_name:'류해외', department:'해외영업팀', usage_type:'법인', category:'차량유지비', amount:78000, description:'GS칼텍스 역삼주유소', user_name:'류해외', business_code:'', document_number:'', is_settled:0, compliance_status:'미확인', settlement_updater:'', settlement_date:''},
    {id:8, card_alias:'경영지원 카드', card_number:'****-3456', approval_number:'A20260611-001', usage_date:'2026-06-11', registrant_name:'정지원', department:'경영지원실', usage_type:'법인', category:'회의비', amount:32000, description:'스타벅스 강남점', user_name:'정지원', business_code:'', document_number:'', is_settled:0, compliance_status:'준수', settlement_updater:'', settlement_date:''},
];
const SAMPLE_APPROVALS = [
    {id:1, card_alias:'영업팀 공용카드', approval_number:'AP-20260618-001', merchant_name:'신라호텔', approval_amount:285000, approval_date:'2026-06-18 14:30:22', approval_status:'대기'},
    {id:2, card_alias:'개발팀 카드', approval_number:'AP-20260613-001', merchant_name:'AWS', approval_amount:890000, approval_date:'2026-06-13 11:00:00', approval_status:'승인'},
    {id:3, card_alias:'경영지원 카드', approval_number:'AP-20260611-001', merchant_name:'오피스디포', approval_amount:124500, approval_date:'2026-06-11 09:30:00', approval_status:'승인'},
];

let dashboardFilter = '';

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(location.search);
    dashboardFilter = urlParams.get('filter') || '';

    if (dashboardFilter === 'unsettled') {
        document.querySelector('input[name="sFilterSettled"][value="0"]').checked = true;
        updateSettledToggleStyle();
    }

    if (HAS_DB) { loadSettlements(); loadApprovals(); }
    else {
        allSettlements = SAMPLE_SETTLEMENTS;
        allApprovals = SAMPLE_APPROVALS;
        filteredApprovals = [...allApprovals];
        if (dashboardFilter === 'unsettled') {
            allSettlements = allSettlements.filter(s => Number(s.is_settled) === 0);
        }
        updateDivisionSummary();
        applyDivisionFilter('');
        renderApprovals();
    }

    document.querySelectorAll('.settle-div-chip').forEach(btn => {
        btn.addEventListener('click', () => applyDivisionFilter(btn.dataset.div));
    });
    document.querySelectorAll('.div-card').forEach(btn => {
        btn.addEventListener('click', () => applyDivisionFilter(btn.dataset.div));
    });
    document.querySelectorAll('.settle-toggle-chip').forEach(label => {
        label.querySelector('input').addEventListener('change', () => {
            updateSettledToggleStyle();
            searchSettlements();
        });
    });

    updateSettledToggleStyle();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});

function updateSettledToggleStyle() {
    document.querySelectorAll('.settle-toggle-chip').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        label.classList.toggle('zm-tab-active', radio.checked);
    });
}

function matchDivision(row, key) {
    if (!key) return true;
    const def = DIVISIONS.find(d => d.key === key);
    if (!def) return true;
    const dept = (row.department || '');
    return def.match.some(m => dept.includes(m));
}

function updateDivisionSummary() {
    DIVISIONS.forEach(d => {
        let unsettled = 0, settled = 0, total = 0;
        allSettlements.forEach(s => {
            if (!matchDivision(s, d.key)) return;
            total += Number(s.amount || 0);
            if (Number(s.is_settled) === 1) settled++; else unsettled++;
        });
        const u = document.querySelector(`[data-stat="unsettled-${d.key}"]`);
        const st = document.querySelector(`[data-stat="settled-${d.key}"]`);
        const t = document.querySelector(`[data-stat="total-${d.key}"]`);
        if (u)  u.textContent  = unsettled.toLocaleString();
        if (st) st.textContent = settled.toLocaleString();
        if (t)  t.textContent  = '₩' + total.toLocaleString();
    });
}

function applyDivisionFilter(key) {
    currentDivision = key || '';
    document.querySelectorAll('.settle-div-chip').forEach(c => {
        c.classList.toggle('active', (c.dataset.div || '') === currentDivision);
    });
    document.querySelectorAll('.div-card').forEach(c => {
        const match = (c.dataset.div || '') === currentDivision && currentDivision !== '';
        c.classList.toggle('border-primary', match);
        c.classList.toggle('border-slate-800', !match);
    });
    filteredSettlements = allSettlements.filter(s => matchDivision(s, currentDivision));
    expPage = 1;
    renderSettlements();
}

function loadSettlements() {
    const p = new URLSearchParams();
    const card = document.getElementById('sFilterCard').value;
    const df = document.getElementById('sFilterDateFrom').value;
    const dt = document.getElementById('sFilterDateTo').value;
    const settled = document.querySelector('input[name="sFilterSettled"]:checked').value;
    const comp = document.getElementById('sFilterCompliance').value;
    if (card) p.set('card_alias', card);
    if (df) p.set('date_from', df);
    if (dt) p.set('date_to', dt);
    if (settled !== '') p.set('is_settled', settled);
    if (comp) p.set('compliance_status', comp);
    fetch(`${API_BASE}?action=getSettlements&${p}`)
        .then(r => r.json())
        .then(d => {
            allSettlements = d.settlements || [];
            updateDivisionSummary();
            applyDivisionFilter(currentDivision);
        })
        .catch(() => { renderSettlements(); });
}

function loadApprovals() {
    const p = new URLSearchParams();
    const card = document.getElementById('aprFilterCard').value;
    const num = document.getElementById('aprFilterNumber').value;
    if (card) p.set('card_alias', card);
    if (num) p.set('approval_number', num);
    fetch(`${API_BASE}?action=getApprovals&${p}`)
        .then(r => r.json())
        .then(d => {
            allApprovals = d.approvals || [];
            filteredApprovals = [...allApprovals];
            aprPage = 1;
            renderApprovals();
        })
        .catch(() => { renderApprovals(); });
}

window.searchSettlements = function() {
    if (HAS_DB) loadSettlements();
    else applyDivisionFilter(currentDivision);
};
window.searchApprovals = function() {
    if (HAS_DB) { loadApprovals(); return; }
    const card = document.getElementById('aprFilterCard').value.toLowerCase();
    const num = document.getElementById('aprFilterNumber').value.toLowerCase();
    filteredApprovals = allApprovals.filter(a => {
        if (card && !(a.card_alias || '').toLowerCase().includes(card)) return false;
        if (num && !(a.approval_number || '').toLowerCase().includes(num)) return false;
        return true;
    });
    aprPage = 1;
    renderApprovals();
};

window.resetSettlementFilters = function() {
    document.getElementById('sFilterCard').value = '';
    document.getElementById('sFilterDateFrom').value = '';
    document.getElementById('sFilterDateTo').value = '';
    document.querySelector('input[name="sFilterSettled"][value=""]').checked = true;
    document.getElementById('sFilterCompliance').value = '';
    updateSettledToggleStyle();
    window.searchSettlements();
};

function renderSettlements() {
    const tbody = document.getElementById('settlementBody');
    document.getElementById('expTotalCount').textContent = filteredSettlements.length;
    const start = (expPage - 1) * PER_PAGE;
    const pageData = filteredSettlements.slice(start, start + PER_PAGE);

    if (!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="17" class="text-center py-10 text-slate-400 text-sm">데이터가 없습니다.</td></tr>';
        document.getElementById('expPagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = pageData.map(e => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
            <td class="px-3 py-3 text-center"><input type="checkbox" class="emp-checkbox settle-check" value="${e.id}" ${Number(e.is_settled) === 1 ? 'disabled' : ''}></td>
            <td class="px-3 py-3 text-sm text-slate-100 font-medium">${esc(e.card_alias)}</td>
            <td class="px-3 py-3 text-sm text-slate-400">${esc(e.approval_number)}</td>
            <td class="px-3 py-3 text-sm text-slate-400 text-center whitespace-nowrap">${esc(e.usage_date)}</td>
            <td class="px-3 py-3 text-sm text-slate-200">${esc(e.registrant_name)}</td>
            <td class="px-3 py-3 text-sm text-slate-300">${esc(e.department)}</td>
            <td class="px-3 py-3 text-center"><span class="inline-block px-1.5 py-0.5 text-sm font-medium rounded ${e.usage_type === '법인' ? 'bg-primary-light text-primary' : 'bg-amber-50 text-amber-500'}">${esc(e.usage_type)}</span></td>
            <td class="px-3 py-3 text-sm text-slate-300">${esc(e.category)}</td>
            <td class="px-3 py-3 text-sm text-right font-semibold text-slate-100">${Number(e.amount).toLocaleString()}원</td>
            <td class="px-3 py-3 text-sm text-slate-400 max-w-[120px] truncate" title="${esc(e.description)}">${esc(e.description)}</td>
            <td class="px-3 py-3 text-sm text-slate-200">${esc(e.user_name)}</td>
            <td class="px-3 py-3 text-sm text-slate-500">${esc(e.business_code || '-')}</td>
            <td class="px-3 py-3 text-sm text-slate-500">${esc(e.document_number || '-')}</td>
            <td class="px-3 py-3 text-sm text-slate-500 text-center">${e.settlement_date ? e.settlement_date.substring(0, 10) : '-'}</td>
            <td class="px-3 py-3 text-sm text-slate-500">${esc(e.settlement_updater || '-')}</td>
            <td class="px-3 py-3 text-center">
                <select class="text-sm border rounded px-1 py-0.5 bg-slate-900 ${complianceColor(e.compliance_status)}" onchange="updateCompliance(${parseInt(e.id)}, this.value)" ${Number(e.is_settled) === 1 ? 'disabled' : ''}>
                    <option value="미확인" ${e.compliance_status === '미확인' ? 'selected' : ''}>미확인</option>
                    <option value="준수" ${e.compliance_status === '준수' ? 'selected' : ''}>준수</option>
                    <option value="예외신청" ${e.compliance_status === '예외신청' ? 'selected' : ''}>예외신청</option>
                    <option value="미준수" ${e.compliance_status === '미준수' ? 'selected' : ''}>미준수</option>
                </select>
            </td>
            <td class="px-3 py-3 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full text-sm font-medium whitespace-nowrap ${Number(e.is_settled) === 1 ? 'settle-badge-ok' : 'settle-badge-warn'}">${Number(e.is_settled) === 1 ? '완료' : '미정산'}</span>
            </td>
        </tr>
    `).join('');

    renderPg('expPagination', filteredSettlements.length, expPage, p => { expPage = p; renderSettlements(); });
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderApprovals() {
    const tbody = document.getElementById('approvalBody');
    document.getElementById('aprTotalCount').textContent = filteredApprovals.length;
    const start = (aprPage - 1) * PER_PAGE;
    const pageData = filteredApprovals.slice(start, start + PER_PAGE);

    if (!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400 text-sm">데이터가 없습니다.</td></tr>';
        document.getElementById('aprPagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = pageData.map(a => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
            <td class="px-4 py-3 text-sm text-slate-100 font-medium">${esc(a.card_alias)}</td>
            <td class="px-4 py-3 text-sm text-slate-400">${esc(a.approval_number)}</td>
            <td class="px-4 py-3 text-sm text-slate-200">${esc(a.merchant_name)}</td>
            <td class="px-4 py-3 text-sm text-right font-semibold text-slate-100">${Number(a.approval_amount).toLocaleString()}원</td>
            <td class="px-4 py-3 text-sm text-slate-400 text-center">${esc((a.approval_date || '').substring(0, 16))}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full text-sm font-medium whitespace-nowrap ${a.approval_status === '승인' ? 'settle-badge-ok' : 'settle-badge-warn'}">${esc(a.approval_status)}</span>
            </td>
        </tr>
    `).join('');

    renderPg('aprPagination', filteredApprovals.length, aprPage, p => { aprPage = p; renderApprovals(); });
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderPg(elId, total, cur, cb) {
    const pages = Math.ceil(total / PER_PAGE);
    if (pages <= 1) { document.getElementById(elId).innerHTML = ''; return; }
    let h = `<button class="pg-btn ${cur === 1 ? 'pg-disabled' : ''}" data-page="${cur - 1}" ${cur === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= pages; i++) {
        if (pages > 7 && i > 3 && i < pages - 2 && Math.abs(i - cur) > 1) {
            if (i === 4 || i === pages - 3) h += '<span class="px-1 text-slate-400">...</span>';
            continue;
        }
        h += `<button class="pg-btn ${i === cur ? 'pg-active' : ''}" data-page="${i}">${i}</button>`;
    }
    h += `<button class="pg-btn ${cur === pages ? 'pg-disabled' : ''}" data-page="${cur + 1}" ${cur === pages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    const el = document.getElementById(elId);
    el.innerHTML = h;
    el.querySelectorAll('.pg-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const p = parseInt(btn.dataset.page);
            if (isNaN(p) || p < 1 || p > pages) return;
            cb(p);
        });
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

window.toggleCheckAll = function(el) {
    document.querySelectorAll('.settle-check:not(:disabled)').forEach(c => c.checked = el.checked);
};

window.batchSettle = async function() {
    const ids = [...document.querySelectorAll('.settle-check:checked')].map(c => parseInt(c.value));
    if (!ids.length) { alert('정산 처리할 항목을 선택해주세요.'); return; }
    if (!(await AppUI.confirm(`${ids.length}건을 정산 처리하시겠습니까?`))) return;
    if (!HAS_DB) { alert('DB 연결이 필요합니다.'); return; }
    fetch(`${API_BASE}?action=batchSettle`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ids, settlement_updater:CURRENT_USER}) })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            alert(res.message);
            loadSettlements();
        })
        .catch(() => alert('정산 처리 중 오류가 발생했습니다.'));
};

window.updateCompliance = function(id, status) {
    if (!HAS_DB) { alert('DB 연결이 필요합니다.'); return; }
    fetch(`${API_BASE}?action=updateSettlement`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id, compliance_status:status, settlement_updater:CURRENT_USER}) })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            loadSettlements();
        })
        .catch(() => alert('규정준수 변경 중 오류가 발생했습니다.'));
};

function complianceColor(s) {
    if (s === '준수') return 'settle-comp-ok';
    if (s === '미준수') return 'settle-comp-bad';
    if (s === '예외신청') return 'settle-comp-warn';
    return 'settle-comp-default';
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

})();
</script>
