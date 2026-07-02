<?php
$pageTitle = '결재함';
$currentPage = 'approval';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$docTypes = [];

if ($pdo) {
    try { $pdo->query('SELECT 1 FROM approval_documents LIMIT 1'); $hasDB = true;
        $docTypes = $pdo->query("SELECT DISTINCT doc_type FROM approval_forms WHERE is_active = 1 ORDER BY doc_type")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) { $hasDB = false; }
}
if (!$hasDB) $docTypes = ['품의서','휴가신청서','출장신청서','외근신청서','야근신청서','법인카드 지출','경비청구서'];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-100">결재함</h2>
            <a href="<?= $basePath ?>/pages/approval_delegate.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-400 hover:text-slate-200 border border-slate-700 rounded-lg hover:border-slate-500 hover:bg-slate-800/50 transition-colors">
                <i data-lucide="user-round-check" class="w-3.5 h-3.5"></i>대결자 설정
            </a>
        </div>

        <!-- 탭 + 필터 토글 -->
        <div class="flex items-center justify-between mb-3">
            <div class="zm-tab-container">
                <button class="approval-tab active" data-filter="requested" onclick="switchView('requested')">결재 요청 <span class="tab-badge"><span class="tab-count" data-tab="requested">0</span></span></button>
                <button class="approval-tab" data-filter="completed" onclick="switchView('completed')">처리 완료 <span class="tab-badge"><span class="tab-count" data-tab="completed">0</span></span></button>
            </div>
            <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> 필터
            </button>
        </div>

        <!-- 검색 필터 (접힘) -->
        <div id="filterPanel" class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-3 hidden">
            <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">제목</label>
                    <input type="text" id="filterTitle" class="reg-input" placeholder="제목 검색">
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">문서번호</label>
                    <input type="text" id="filterDocNum" class="reg-input" placeholder="문서번호 검색">
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">문서종류</label>
                    <select id="filterDocType" class="reg-select">
                        <option value="">전체</option>
                        <?php foreach ($docTypes as $dt): ?>
                        <option value="<?= htmlspecialchars($dt) ?>"><?= htmlspecialchars($dt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">기안일</label>
                    <div class="flex items-center gap-2 flex-1">
                        <input type="date" id="filterDateFrom" class="reg-input">
                        <span class="text-gray-400">~</span>
                        <input type="date" id="filterDateTo" class="reg-input">
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">기안자</label>
                    <input type="text" id="filterDrafter" class="reg-input" placeholder="기안자 검색">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button onclick="searchDocs()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">검색 <i data-lucide="search" class="w-4 h-4"></i></button>
                <button onclick="resetFilters()" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- 리스트 카드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <!-- 리스트 정보바 -->
            <div class="list-info-bar">
                <span class="info-text">문서 <strong id="totalCount">0</strong></span>
                <select id="perPageSelect" class="list-per-page" onchange="renderDocs()">
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
                            <th class="px-4 py-3 text-center">문서번호</th>
                            <th class="px-4 py-3 text-center">제목</th>
                            <th class="px-4 py-3 text-center">문서종류</th>
                            <th class="px-4 py-3 text-center">기안자</th>
                            <th class="px-4 py-3 text-center">상태</th>
                            <th class="px-4 py-3 text-center">기안일</th>
                            <th class="px-4 py-3 text-center">결재경로</th>
                        </tr>
                    </thead>
                    <tbody id="docTableBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>
    </main>
</div>

<script>
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
let allDocs = [];
let filteredDocs = [];
let currentPage = 1;
let currentView = 'requested';

const SAMPLE_REQUESTED = [
    {id:301, doc_number:'Zaemit_품의서_20260620143022', title:'7월 팀빌딩 예산 품의', doc_type:'품의서', drafter_name:'정지원', drafter_dept:'경영지원팀', status:'대기', draft_date:'2026-06-20', route:[{name:'김대표',role:'결재',status:'대기'}]},
    {id:302, doc_number:'Zaemit_휴가신청서_20260619091505', title:'연차 사용 신청(6/25~6/26)', doc_type:'휴가신청서', drafter_name:'강개발', drafter_dept:'개발1팀', status:'진행', draft_date:'2026-06-19', route:[{name:'박기술',role:'결재',status:'승인'},{name:'김대표',role:'전결',status:'대기'}]},
    {id:303, doc_number:'Zaemit_경비청구서_20260618142010', title:'서버 장비 구매 경비 청구', doc_type:'경비청구서', drafter_name:'윤개발', drafter_dept:'개발2팀', status:'대기', draft_date:'2026-06-18', route:[{name:'김대표',role:'결재',status:'대기'}]},
];

const SAMPLE_COMPLETED = [
    {id:201, doc_number:'Zaemit_품의서_20260601100000', title:'5월 팀빌딩 비용 품의', doc_type:'품의서', drafter_name:'정지원', drafter_dept:'경영지원팀', status:'승인', my_action:'승인', draft_date:'2026-06-01', route:[{name:'정지원',role:'결재',status:'승인'},{name:'이본부장',role:'전결',status:'승인'}]},
    {id:202, doc_number:'Zaemit_휴가신청서_20260530091000', title:'연차 사용 신청(6/5~6/6)', doc_type:'휴가신청서', drafter_name:'한인사', drafter_dept:'인사팀', status:'승인', my_action:'승인', draft_date:'2026-05-30', route:[{name:'정지원',role:'결재',status:'승인'},{name:'이본부장',role:'전결',status:'승인'}]},
    {id:203, doc_number:'Zaemit_출장신청서_20260528140000', title:'대전 공공기관 입찰 출장', doc_type:'출장신청서', drafter_name:'서영업', drafter_dept:'국내영업팀', status:'승인', my_action:'승인', draft_date:'2026-05-28', route:[{name:'최영업',role:'결재',status:'승인'},{name:'이본부장',role:'전결',status:'승인'}]},
    {id:204, doc_number:'Zaemit_법인카드_20260525163000', title:'4월 법인카드 사용내역 정산', doc_type:'법인카드 지출', drafter_name:'오재무', drafter_dept:'재무회계팀', status:'승인', my_action:'승인', draft_date:'2026-05-25', route:[{name:'정지원',role:'결재',status:'승인'},{name:'이본부장',role:'전결',status:'승인'}]},
    {id:205, doc_number:'Zaemit_경비청구서_20260520110000', title:'클라우드 서버 이전 비용 청구', doc_type:'경비청구서', drafter_name:'강개발', drafter_dept:'개발1팀', status:'반려', my_action:'반려', draft_date:'2026-05-20', route:[{name:'박기술',role:'결재',status:'반려'}]},
    {id:206, doc_number:'Zaemit_야근신청서_20260518080000', title:'핫픽스 배포 대응 야근', doc_type:'야근신청서', drafter_name:'엄품질', drafter_dept:'QA팀', status:'승인', my_action:'승인', draft_date:'2026-05-18', route:[{name:'박기술',role:'결재',status:'승인'},{name:'이본부장',role:'전결',status:'승인'}]},
];

document.addEventListener('DOMContentLoaded', () => {
    const urlView = new URLSearchParams(location.search).get('tab');
    if (urlView === 'completed') currentView = 'completed';
    syncTabUI();
    if (HAS_DB) loadDocs();
    else loadSampleDocs();
});

function syncTabUI() {
    document.querySelectorAll('.approval-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.filter === currentView);
    });
    document.querySelectorAll('[data-summary-card]').forEach(c => {
        c.classList.toggle('border-primary', c.dataset.summaryCard === currentView);
        c.classList.toggle('border-slate-800', c.dataset.summaryCard !== currentView);
    });
}

function switchView(view) {
    currentView = view;
    syncTabUI();
    if (HAS_DB) loadDocs();
    else loadSampleDocs();
}

function loadSampleDocs() {
    const requested = SAMPLE_REQUESTED;
    const completed = SAMPLE_COMPLETED;
    allDocs = currentView === 'requested' ? requested : completed;
    filteredDocs = [...allDocs];
    document.querySelectorAll('.tab-count[data-tab="requested"], [data-summary-count="requested"]').forEach(el => el.textContent = requested.length);
    document.querySelectorAll('.tab-count[data-tab="completed"], [data-summary-count="completed"]').forEach(el => el.textContent = completed.length);
    currentPage = 1;
    renderDocs();
}

function loadDocs() {
    const p = new URLSearchParams();
    p.set('status_filter', currentView);
    ['filterTitle:title','filterDrafter:drafter','filterDocNum:doc_number','filterDocType:doc_type','filterDateFrom:date_from','filterDateTo:date_to'].forEach(m => {
        const [elId, key] = m.split(':');
        const v = document.getElementById(elId).value;
        if (v) p.set(key, v);
    });
    fetch(`${API_BASE}?action=getPending&${p}`)
        .then(r => r.json())
        .then(data => {
            allDocs = data.documents || [];
            filteredDocs = [...allDocs];
            if (data.counts) {
                Object.entries(data.counts).forEach(([k, v]) => {
                    document.querySelectorAll(`.tab-count[data-tab="${k}"], [data-summary-count="${k}"]`).forEach(el => el.textContent = v);
                });
            }
            currentPage = 1;
            renderDocs();
        });
}

function searchDocs() {
    if (HAS_DB) { loadDocs(); return; }
    currentPage = 1; renderDocs();
}

function renderDocs() {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const tbody = document.getElementById('docTableBody');
    document.getElementById('totalCount').textContent = filteredDocs.length;
    const start = (currentPage - 1) * perPage;
    const pageData = filteredDocs.slice(start, start + perPage);

    if (!pageData.length) {
        const emptyMsg = currentView === 'requested' ? '대기 중인 결재 요청이 없습니다' : '처리한 결재가 없습니다';
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-16 text-slate-500 text-sm">${emptyMsg}</td></tr>`;
        document.getElementById('pagination').innerHTML = '';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    tbody.innerHTML = pageData.map(d => {
        const statusBadge = getStatusBadge(d);
        const routeHtml = renderRoute(d.route || []);

        return `
        <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer transition-colors" onclick="viewDoc(${d.id})">
            <td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.doc_number)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-200 font-medium text-center">${esc(d.title)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.doc_type)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-200 text-center">${esc(d.drafter_name)}</td>
            <td class="px-4 py-3.5 text-center">${statusBadge}</td>
            <td class="px-4 py-3.5 text-sm text-slate-400 text-center">${(d.draft_date||'').replace(/-/g,'.')}</td>
            <td class="px-4 py-3.5 text-center">${routeHtml}</td>
        </tr>`;
    }).join('');

    renderPagination();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function getStatusBadge(d) {
    // 내가 어떤 처리를 했는지 표시 (승인/반려)
    if (d.my_action) {
        const actionCls = { '승인': 'badge-success', '반려': 'badge-danger', '합의': 'badge-info', '의견': 'badge-neutral' };
        const cls = actionCls[d.my_action] || 'badge-neutral';
        return `<span class="badge ${cls} whitespace-nowrap">${esc(d.my_action)}</span>`;
    }
    const map = {
        '승인': 'badge-success',
        '반려': 'badge-danger',
        '진행': 'badge-info',
    };
    const cls = map[d.status] || 'badge-neutral';
    return `<span class="badge ${cls} whitespace-nowrap">${esc(d.status || '-')}</span>`;
}

function renderRoute(route) {
    if (!route || !route.length) return '<span class="text-sm text-slate-500">-</span>';
    return route.map(s => {
        const cls = (s.status === '승인' || s.status === '합의') ? 'text-amber-700' : s.status === '반려' ? 'text-amber-500' : s.status === '의견' ? 'text-slate-400' : 'text-slate-500';
        const icon = (s.status === '승인' || s.status === '합의') ? '✓' : s.status === '반려' ? '✕' : s.status === '의견' ? '💬' : '○';
        return `<span class="text-sm ${cls}" title="${esc(s.name)} (${esc(s.position)})">${icon}${esc(s.name)}</span>`;
    }).join('<span class="text-sm text-slate-600 mx-0.5">→</span>');
}

function renderPagination() {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const pages = Math.ceil(filteredDocs.length / perPage);
    const el = document.getElementById('pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }
    let h = `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(${currentPage-1})"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i=1;i<=pages;i++) {
        if (pages > 7 && Math.abs(i - currentPage) > 2 && i > 2 && i < pages - 1) { if (i === 3 || i === pages - 2) h += '<span class="px-1 text-slate-400">...</span>'; continue; }
        h += `<button class="pg-btn ${i===currentPage?'pg-active':''}" onclick="goPage(${i})">${i}</button>`;
    }
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${currentPage+1})"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${pages})"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>`;
    el.innerHTML = h;
}

function goPage(p) { const pages = Math.ceil(filteredDocs.length / parseInt(document.getElementById('perPageSelect').value)); if(p<1||p>pages)return; currentPage=p; renderDocs(); }
function resetFilters() { ['filterTitle','filterDrafter','filterDocNum','filterDocType','filterDateFrom','filterDateTo'].forEach(id => document.getElementById(id).value = ''); searchDocs(); }
function viewDoc(id) { location.href = 'approval_view.php?id=' + id; }
const esc = (typeof ApvUI !== 'undefined') ? ApvUI.esc : function(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); };
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
