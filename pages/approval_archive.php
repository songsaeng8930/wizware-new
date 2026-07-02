<?php
$pageTitle = '문서보관함';
$currentPage = 'approval';
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
require_once __DIR__ . '/../config/database.php';
if ($isEmbed) {
    require_once __DIR__ . '/../includes/auth.php';
    requireLogin();
    $basePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{primary:'#4F6AFF','primary-dark':'#3B54D4','primary-light':'#E8ECFF'}}}}</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/custom.css">
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
<?php
} else {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
}

$pdo = getDBConnection();
$hasDB = false;
$docTypes = [];

if ($pdo) {
    try { $pdo->query('SELECT 1 FROM approval_documents LIMIT 1'); $hasDB = true;
        $docTypes = $pdo->query("SELECT DISTINCT doc_type FROM approval_forms WHERE is_active = 1 ORDER BY doc_type")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) { $hasDB = false; }
}
if (!$hasDB) $docTypes = ['품의서','휴가신청서','출장신청서','외근신청서','야근신청서','법인카드 지출','경비청구서'];

$sampleDocs = [];
if (!$hasDB) {
    $sampleDocs = [
        ['id'=>101,'tab'=>'recalled','doc_number'=>'Zaemit_품의서_20260601100000','title'=>'6월 워크숍 비용 품의 (회수)','doc_type'=>'품의서','drafter_name'=>'정지원','drafter_dept'=>'경영지원팀','result'=>'회수','draft_date'=>'2026-06-01','complete_date'=>'2026-06-02'],
        ['id'=>102,'tab'=>'recalled','doc_number'=>'Zaemit_출장신청서_20260528140000','title'=>'대전 공공기관 입찰 출장 (일정 변경)','doc_type'=>'출장신청서','drafter_name'=>'서영업','drafter_dept'=>'국내영업팀','result'=>'회수','draft_date'=>'2026-05-28','complete_date'=>'2026-05-29'],
        ['id'=>103,'tab'=>'recalled','doc_number'=>'Zaemit_경비청구서_20260520110000','title'=>'클라우드 서버 비용 청구 (금액 수정)','doc_type'=>'경비청구서','drafter_name'=>'강개발','drafter_dept'=>'개발1팀','result'=>'회수','draft_date'=>'2026-05-20','complete_date'=>'2026-05-21'],
        ['id'=>104,'tab'=>'temp','doc_number'=>'Zaemit_품의서_20260615143000','title'=>'하반기 마케팅 예산 품의','doc_type'=>'품의서','drafter_name'=>'류해외','drafter_dept'=>'해외영업팀','result'=>'임시저장','draft_date'=>'2026-06-15','complete_date'=>''],
        ['id'=>105,'tab'=>'temp','doc_number'=>'Zaemit_야근신청서_20260614080000','title'=>'긴급 패치 대응 야근 신청','doc_type'=>'야근신청서','drafter_name'=>'엄품질','drafter_dept'=>'QA팀','result'=>'임시저장','draft_date'=>'2026-06-14','complete_date'=>''],
        ['id'=>106,'tab'=>'reference','doc_number'=>'Zaemit_품의서_20260501100000','title'=>'5월 팀빌딩 비용 품의','doc_type'=>'품의서','drafter_name'=>'정지원','drafter_dept'=>'경영지원팀','result'=>'승인','draft_date'=>'2026-05-01','complete_date'=>'2026-05-02'],
        ['id'=>107,'tab'=>'reference','doc_number'=>'Zaemit_법인카드_20260520163000','title'=>'4월 법인카드 사용내역 정산','doc_type'=>'법인카드 지출','drafter_name'=>'오재무','drafter_dept'=>'재무회계팀','result'=>'승인','draft_date'=>'2026-05-20','complete_date'=>'2026-05-21'],
        ['id'=>108,'tab'=>'reference','doc_number'=>'Zaemit_휴가신청서_20260503091000','title'=>'연차 사용 신청(5/12~5/13)','doc_type'=>'휴가신청서','drafter_name'=>'한인사','drafter_dept'=>'인사팀','result'=>'승인','draft_date'=>'2026-05-03','complete_date'=>'2026-05-04'],
    ];
}
?>

<div id="mainContent" class="<?= $isEmbed ? '' : 'ml-60 mt-14' ?> transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <h2 class="text-lg font-bold text-slate-100 mb-4">문서보관함</h2>

        <!-- 탭 + 필터 토글 -->
        <div class="flex items-center justify-between mb-3">
            <div class="zm-tab-container">
                <button class="approval-tab" data-tab="recalled" onclick="changeTab(this, 'recalled')">회수함 <span class="tab-badge"><span class="tab-count" data-tab="recalled">0</span></span></button>
                <button class="approval-tab" data-tab="temp" onclick="changeTab(this, 'temp')">임시저장함 <span class="tab-badge"><span class="tab-count" data-tab="temp">0</span></span></button>
                <button class="approval-tab" data-tab="reference" onclick="changeTab(this, 'reference')">참조문서함 <span class="tab-badge"><span class="tab-count" data-tab="reference">0</span></span></button>
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
                <?php if (isOrgLevelEnabled('department')): ?>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">기안<?= htmlspecialchars(getOrgLabel('department')) ?></label>
                    <input type="text" id="filterDept" class="reg-input" placeholder="기안<?= htmlspecialchars(getOrgLabel('department')) ?> 검색">
                </div>
                <?php else: ?>
                <input type="hidden" id="filterDept" value="">
                <?php endif; ?>
                <div id="resultFilterRow" class="flex items-center gap-4 col-span-2 hidden">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">결과</label>
                    <div class="flex items-center gap-4">
                        <label class="emp-checkbox-label"><input type="checkbox" class="emp-checkbox" value="" checked onchange="toggleResultAll(this)"> 전체</label>
                        <label class="emp-checkbox-label"><input type="checkbox" class="emp-checkbox result-check" value="승인" checked> 승인</label>
                        <label class="emp-checkbox-label"><input type="checkbox" class="emp-checkbox result-check" value="반려" checked> 반려</label>
                        <label class="emp-checkbox-label"><input type="checkbox" class="emp-checkbox result-check" value="협의필요" checked> 협의필요</label>
                    </div>
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
                <span class="info-text"><span id="infoLabel">회수함 총 등록건수</span> <strong id="totalCount">0</strong></span>
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
                            <th class="px-4 py-3 text-center">결과</th>
                            <?php if (isOrgLevelEnabled('department')): ?><th class="px-4 py-3 text-center">기안<?= htmlspecialchars(getOrgLabel('department')) ?></th><?php endif; ?>
                            <th class="px-4 py-3 text-center">문서종류</th>
                            <th class="px-4 py-3 text-center">기안자</th>
                            <th class="px-4 py-3 text-center">기안일</th>
                            <th id="thComplete" class="px-4 py-3 text-center">회수일</th>
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
const SHOW_DRAFTER_DEPT = <?= isOrgLevelEnabled('department') ? 'true' : 'false' ?>;
let allDocs = <?= json_encode($sampleDocs, JSON_UNESCAPED_UNICODE) ?>;
let filteredDocs = [...allDocs];
let currentPage = 1;
const _urlTab = new URLSearchParams(location.search).get('tab');
let currentTab = ['recalled','temp','reference'].includes(_urlTab) ? _urlTab : 'recalled';

document.addEventListener('DOMContentLoaded', () => {
    // URL ?tab= 파라미터 → 해당 탭 활성화 + 헤더 라벨 초기화
    document.querySelectorAll('.approval-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === currentTab);
    });
    document.getElementById('resultFilterRow').classList.toggle('hidden', currentTab !== 'reference');
    document.getElementById('thComplete').textContent = currentTab === 'recalled' ? '회수일' : currentTab === 'temp' ? '저장일' : '완료일';
    const tabNames = { recalled: '회수함', temp: '임시저장함', reference: '참조문서함' };
    document.getElementById('infoLabel').textContent = (tabNames[currentTab] || '문서보관함') + ' 총 등록건수';
    document.querySelectorAll('[data-summary-card]').forEach(c => c.classList.toggle('border-primary', c.dataset.summaryCard === currentTab));

    if (HAS_DB) loadDocs();
    else { updateCounts(); searchDocs(); }
});

function updateCounts() {
    ['recalled','temp','reference'].forEach(tab => {
        const cnt = allDocs.filter(d => d.tab === tab).length;
        const tabEl = document.querySelector(`.tab-count[data-tab="${tab}"]`);
        const sumEl = document.querySelector(`[data-summary-tab="${tab}"]`);
        if (tabEl) tabEl.textContent = cnt;
        if (sumEl) sumEl.textContent = cnt;
    });
}

function loadDocs() {
    const p = new URLSearchParams();
    p.set('tab_filter', currentTab);
    const results = [...document.querySelectorAll('.result-check:checked')].map(c => c.value);
    if (results.length) p.set('result', results.join(','));
    ['filterTitle:title','filterDrafter:drafter','filterDept:drafter_dept','filterDocNum:doc_number','filterDocType:doc_type','filterDateFrom:date_from','filterDateTo:date_to'].forEach(m => {
        const [elId, key] = m.split(':');
        const v = document.getElementById(elId).value;
        if (v) p.set(key, v);
    });
    fetch(`${API_BASE}?action=getArchive&${p}`)
        .then(r => r.json())
        .then(data => {
            allDocs = data.documents || [];
            filteredDocs = [...allDocs];
            if (data.counts) {
                Object.entries(data.counts).forEach(([k, v]) => {
                    const tabEl = document.querySelector(`.tab-count[data-tab="${k}"]`);
                    const sumEl = document.querySelector(`[data-summary-tab="${k}"]`);
                    if (tabEl) tabEl.textContent = v;
                    if (sumEl) sumEl.textContent = v;
                });
            }
            currentPage = 1;
            renderDocs();
        });
}

function searchDocs() {
    if (HAS_DB) { loadDocs(); return; }
    const title = document.getElementById('filterTitle').value.toLowerCase();
    const results = [...document.querySelectorAll('.result-check:checked')].map(c => c.value);
    filteredDocs = allDocs.filter(d => {
        if (d.tab && d.tab !== currentTab) return false;
        if (title && !d.title.toLowerCase().includes(title)) return false;
        if (currentTab === 'reference' && results.length && !results.includes(d.result || d.status)) return false;
        return true;
    });
    currentPage = 1; renderDocs();
}

function toggleResultAll(el) {
    document.querySelectorAll('.result-check').forEach(c => c.checked = el.checked);
}

function activateTab(tab) {
    const el = document.querySelector(`.approval-tab[data-tab="${tab}"]`);
    if (el) changeTab(el, tab);
}

function changeTab(el, tab) {
    document.querySelectorAll('.approval-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    currentTab = tab;
    document.querySelectorAll('[data-summary-card]').forEach(c => c.classList.toggle('border-primary', c.dataset.summaryCard === tab));
    document.getElementById('resultFilterRow').classList.toggle('hidden', tab !== 'reference');
    document.getElementById('thComplete').textContent = tab === 'recalled' ? '회수일' : tab === 'temp' ? '저장일' : '완료일';
    const tabNames = { recalled: '회수함', temp: '임시저장함', reference: '참조문서함' };
    document.getElementById('infoLabel').textContent = (tabNames[tab] || '문서보관함') + ' 총 등록건수';
    if (HAS_DB) loadDocs(); else searchDocs();
}

function renderDocs() {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const tbody = document.getElementById('docTableBody');
    document.getElementById('totalCount').textContent = filteredDocs.length;
    const start = (currentPage - 1) * perPage;
    const pageData = filteredDocs.slice(start, start + perPage);

    if (!pageData.length) {
        const colCount = SHOW_DRAFTER_DEPT ? 8 : 7;
        tbody.innerHTML = '<tr><td colspan="' + colCount + '" class="text-center py-16 text-slate-400"><i data-lucide="info" class="mr-1 w-4 h-4"></i> 검색 결과가 없습니다</td></tr>';
        document.getElementById('pagination').innerHTML = ''; return;
    }

    tbody.innerHTML = pageData.map(d => {
        const result = d.result || d.status;
        const resultClass = result === '승인' ? 'badge-success' : result === '결재완료' ? 'badge-success' : result === '반려' ? 'badge-danger' : result === '회수' ? 'badge-warning' : result === '임시저장' ? 'badge-neutral' : 'badge-info';
        return `
        <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer transition-colors" onclick="viewDoc(${d.id})">
            <td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.doc_number)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-200 font-medium text-center">${esc(d.title)}</td>
            <td class="px-4 py-3.5 text-center"><span class="badge ${resultClass} whitespace-nowrap">${esc(result)}</span></td>
            ${SHOW_DRAFTER_DEPT ? `<td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.drafter_dept)}</td>` : ''}
            <td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.doc_type)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-200 text-center">${esc(d.drafter_name)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-400 text-center">${(d.draft_date||'').replace(/-/g,'.')}</td>
            <td class="px-4 py-3.5 text-sm text-slate-400 text-center">${(d.complete_date||'-').replace(/-/g,'.')}</td>
        </tr>`;
    }).join('');

    const pages = Math.ceil(filteredDocs.length / perPage);
    if (pages <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let h = `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(${currentPage-1})"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i=1;i<=pages;i++) h += `<button class="pg-btn ${i===currentPage?'pg-active':''}" onclick="goPage(${i})">${i}</button>`;
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${currentPage+1})"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${pages})"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>`;
    document.getElementById('pagination').innerHTML = h;
}

function goPage(p) { const pages = Math.ceil(filteredDocs.length / parseInt(document.getElementById('perPageSelect').value)); if(p<1||p>pages)return; currentPage=p; renderDocs(); }
function resetFilters() { ['filterTitle','filterDrafter','filterDept','filterDocNum','filterDocType','filterDateFrom','filterDateTo'].forEach(id => document.getElementById(id).value = ''); document.querySelectorAll('.result-check').forEach(c => c.checked = true); searchDocs(); }
function viewDoc(id) {
    <?php if ($isEmbed): ?>
    const row = allDocs.find(d => d.id === id || d.id === String(id));
    if (row && window.parent !== window) {
        window.parent.postMessage({ type: 'selectDoc', docNumber: row.doc_number, docTitle: row.title, docId: id }, '*');
        return;
    }
    <?php endif; ?>
    location.href = 'approval_view.php?id=' + id;
}
const esc = (typeof ApvUI !== 'undefined') ? ApvUI.esc : function(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); };
</script>

<?php if ($isEmbed): ?>
<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body></html>
<?php else: ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>
