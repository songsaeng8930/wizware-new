<?php
$pageTitle = '내 기안함';
$currentPage = 'approval';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$docTypes = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM approval_documents LIMIT 1');
        $hasDB = true;
        $docTypes = $pdo->query("SELECT DISTINCT doc_type FROM approval_forms WHERE is_active = 1 ORDER BY doc_type")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) { $hasDB = false; }
}

if (!$hasDB) {
    $docTypes = ['품의서','휴가신청서','출장신청서','외근신청서','야근신청서','법인카드 지출','경비청구서'];
}

$sampleDocs = [];
if (!$hasDB) {
    $sampleDocs = [
        ['id'=>1,'doc_number'=>'Zaemit_품의서_20260610143022','title'=>'6월 팀 회식비 품의','doc_type'=>'품의서','drafter_name'=>'김대표','drafter_dept'=>'경영진','status'=>'진행','current_approver'=>'정지원','draft_date'=>'2026-06-10','complete_date'=>'','reject_reason'=>'','route'=>[['name'=>'정지원','role'=>'결재','status'=>'대기']]],
        ['id'=>2,'doc_number'=>'Zaemit_휴가신청서_20260611091505','title'=>'연차 사용 신청(6/16~6/17)','doc_type'=>'휴가신청서','drafter_name'=>'김대표','drafter_dept'=>'경영진','status'=>'대기','current_approver'=>'정지원','draft_date'=>'2026-06-11','complete_date'=>'','reject_reason'=>''],
        ['id'=>3,'doc_number'=>'Zaemit_출장신청서_20260609110030','title'=>'부산 거래처 미팅 출장','doc_type'=>'출장신청서','drafter_name'=>'김대표','drafter_dept'=>'경영진','status'=>'승인','current_approver'=>'','draft_date'=>'2026-06-09','complete_date'=>'2026-06-10','reject_reason'=>'','route'=>[['name'=>'정지원','role'=>'결재','status'=>'승인']]],
        ['id'=>4,'doc_number'=>'Zaemit_법인카드_20260608163045','title'=>'5월 법인카드 사용내역 정산','doc_type'=>'법인카드 지출','drafter_name'=>'김대표','drafter_dept'=>'경영진','status'=>'진행','current_approver'=>'이본부장','draft_date'=>'2026-06-08','complete_date'=>'','reject_reason'=>'','route'=>[['name'=>'정지원','role'=>'결재','status'=>'승인'],['name'=>'이본부장','role'=>'전결','status'=>'대기']]],
        ['id'=>5,'doc_number'=>'Zaemit_경비청구서_20260607142010','title'=>'개발장비 구매 경비 청구','doc_type'=>'경비청구서','drafter_name'=>'김대표','drafter_dept'=>'경영진','status'=>'반려','current_approver'=>'','draft_date'=>'2026-06-07','complete_date'=>'2026-06-08','reject_reason'=>'증빙서류 미첨부. 영수증 사본 첨부 후 재상신 부탁드립니다.'],
        ['id'=>6,'doc_number'=>'Zaemit_야근신청서_20260612080030','title'=>'릴리즈 대응 야근 신청(6/13)','doc_type'=>'야근신청서','drafter_name'=>'김대표','drafter_dept'=>'경영진','status'=>'임시저장','current_approver'=>'','draft_date'=>'2026-06-12','complete_date'=>'','reject_reason'=>''],
    ];
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-100">내 기안함</h2>
            <a href="<?= $basePath ?>/pages/approval_delegate.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-400 hover:text-slate-200 border border-slate-700 rounded-lg hover:border-slate-500 hover:bg-slate-800/50 transition-colors">
                <i data-lucide="user-round-check" class="w-3.5 h-3.5"></i>대결자 설정
            </a>
        </div>

        <!-- 탭 + 필터 토글 -->
        <div class="flex items-center justify-between mb-3">
            <div class="zm-tab-container">
                <button class="approval-tab active" data-filter="progress" onclick="changeTab(this, 'progress')">진행중 <span class="tab-badge"><span class="tab-count" data-tab="progress">0</span></span></button>
                <button class="approval-tab" data-filter="approved" onclick="changeTab(this, 'approved')">승인 <span class="tab-badge"><span class="tab-count" data-tab="approved">0</span></span></button>
                <button class="approval-tab" data-filter="rejected" onclick="changeTab(this, 'rejected')">반려 <span class="tab-badge"><span class="tab-count" data-tab="rejected">0</span></span></button>
                <button class="approval-tab" data-filter="temp" onclick="changeTab(this, 'temp')">임시저장 <span class="tab-badge"><span class="tab-count" data-tab="temp">0</span></span></button>
            </div>
            <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> 필터
            </button>
        </div>

        <!-- 검색 필터 (접힘) -->
        <div id="filterPanel" class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-3 hidden">
            <input type="hidden" id="filterDrafter" value="">
            <input type="hidden" id="filterDept" value="">
            <div class="approval-filter-grid grid grid-cols-2 gap-x-8 gap-y-4">
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
                <span class="info-text">내 기안 문서 <strong id="totalCount">0</strong></span>
                <select id="perPageSelect" class="list-per-page" onchange="renderDocs()">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>

            <!-- 테이블 -->
            <div class="approval-table-wrap overflow-x-auto">
                <table class="w-full emp-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-center">문서번호</th>
                            <th class="px-4 py-3 text-center">제목</th>
                            <th class="px-4 py-3 text-center">문서종류</th>
                            <th class="px-4 py-3 text-center">현재 결재자</th>
                            <th class="px-4 py-3 text-center">상태</th>
                            <th id="thReason" class="px-4 py-3 text-center hidden">사유</th>
                            <th id="thDate" class="px-4 py-3 text-center">기안일</th>
                        </tr>
                    </thead>
                    <tbody id="docTableBody"></tbody>
                </table>
            </div>
            <div id="docCardList" class="approval-mobile-list hidden"></div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>
    </main>
</div>

<!-- 결재 UI 스타일은 custom.css .apv-* 클래스로 통합됨 -->
<style>
.approver-cell { position: relative; }
.approver-trigger { cursor: default; border-bottom: 1px dashed var(--zm-text-subtle); }
.reason-cell { position: relative; cursor: default; }
.reason-full {
    display: none; position: fixed;
    background: var(--zm-surface-2); color: var(--zm-text-strong);
    border: 1px solid var(--zm-border); border-radius: 8px;
    padding: 10px 14px; font-size: 13px; line-height: 1.5;
    max-width: 300px; white-space: normal; word-break: break-word;
    z-index: 9999; box-shadow: 0 8px 24px rgba(0,0,0,0.25); pointer-events: none;
}
.reason-full.show { display: block; }
.approval-mobile-list { display: none; }
.approval-mobile-card { border: 1px solid var(--zm-border); border-radius: 14px; background: var(--zm-surface-1); padding: 14px; }
@media (max-width: 767px) {
    .approval-filter-grid { grid-template-columns: 1fr; }
    .approval-filter-grid > div { flex-direction: column; align-items: stretch; gap: 8px; }
    .approval-filter-grid label { width: auto; }
    .approval-filter-grid input, .approval-filter-grid select { width: 100%; }
    .approval-table-wrap { display: none; }
    .approval-mobile-list { display: grid !important; gap: 12px; padding: 0 16px 16px; }
}
</style>

<script>
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
let allDocs = <?= json_encode($sampleDocs, JSON_UNESCAPED_UNICODE) ?>;
let filteredDocs = [...allDocs];
let currentPage = 1;
const _urlTab = new URLSearchParams(location.search).get('tab');
let currentTab = ['progress','approved','rejected','temp'].includes(_urlTab) ? _urlTab : 'progress';

document.addEventListener('DOMContentLoaded', () => {
    // URL ?tab= 파라미터로 초기 탭 동기화
    document.querySelectorAll('.approval-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.filter === currentTab);
    });
    if (HAS_DB) loadDocs();
    else { updateCounts(); filterByTab(); renderDocs(); }
});

function getCurrentApprover(route) {
    if (!route || !route.length) return null;
    return route.find(s => s.status === '대기') || null;
}

function loadDocs() {
    const p = new URLSearchParams();
    p.set('scope', 'my');
    p.set('status_filter', currentTab);
    addFilterParams(p);
    fetch(`${API_BASE}?action=getDrafts&${p}`)
        .then(r => r.json())
        .then(data => {
            allDocs = data.documents || [];
            filteredDocs = [...allDocs];
            if (data.counts) {
                Object.entries(data.counts).forEach(([k, v]) => {
                    setApprovalCount(k, v);
                });
            }
            currentPage = 1;
            renderDocs();
        });
}

function addFilterParams(p) {
    const title = document.getElementById('filterTitle').value;
    const drafter = document.getElementById('filterDrafter').value;
    const dept = document.getElementById('filterDept').value;
    const docNum = document.getElementById('filterDocNum').value;
    const docType = document.getElementById('filterDocType').value;
    const df = document.getElementById('filterDateFrom').value;
    const dt = document.getElementById('filterDateTo').value;
    if (title) p.set('title', title);
    if (drafter) p.set('drafter', drafter);
    if (dept) p.set('drafter_dept', dept);
    if (docNum) p.set('doc_number', docNum);
    if (docType) p.set('doc_type', docType);
    if (df) p.set('date_from', df);
    if (dt) p.set('date_to', dt);
}

function filterByTab() {
    filteredDocs = allDocs.filter(d => {
        if (currentTab === 'progress') return d.status === '대기' || d.status === '진행';
        if (currentTab === 'approved') return d.status === '승인';
        if (currentTab === 'rejected') return d.status === '반려';
        if (currentTab === 'temp') return d.status === '임시저장';
        return true;
    });
}

function searchDocs() {
    if (HAS_DB) { loadDocs(); return; }
    const title = document.getElementById('filterTitle').value.toLowerCase();
    const docNum = document.getElementById('filterDocNum').value.toLowerCase();
    const docType = document.getElementById('filterDocType').value;

    filteredDocs = allDocs.filter(d => {
        if (currentTab === 'progress' && d.status !== '대기' && d.status !== '진행') return false;
        if (currentTab === 'approved' && d.status !== '승인') return false;
        if (currentTab === 'rejected' && d.status !== '반려') return false;
        if (currentTab === 'temp' && d.status !== '임시저장') return false;
        if (title && !d.title.toLowerCase().includes(title)) return false;
        if (docNum && !d.doc_number.toLowerCase().includes(docNum)) return false;
        if (docType && d.doc_type !== docType) return false;
        return true;
    });
    currentPage = 1;
    renderDocs();
}

function updateCounts() {
    const progress = allDocs.filter(d => d.status === '대기' || d.status === '진행').length;
    const approved = allDocs.filter(d => d.status === '승인').length;
    const rejected = allDocs.filter(d => d.status === '반려').length;
    const temp = allDocs.filter(d => d.status === '임시저장').length;
    setApprovalCount('progress', progress);
    setApprovalCount('approved', approved);
    setApprovalCount('rejected', rejected);
    setApprovalCount('temp', temp);
}

function setApprovalCount(tab, value) {
    document.querySelectorAll(`.tab-count[data-tab="${tab}"], [data-summary-tab="${tab}"]`).forEach(el => {
        el.textContent = value;
    });
}

function activateTab(tab) {
    const el = document.querySelector(`.approval-tab[data-filter="${tab}"]`);
    if (el) changeTab(el, tab);
}

function changeTab(el, tab) {
    document.querySelectorAll('.approval-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    currentTab = tab;
    if (HAS_DB) loadDocs();
    else { filterByTab(); currentPage = 1; renderDocs(); }
}

function buildApproverCell(d) {
    if (d.status === '대기') return '<span class="text-slate-500">-</span>';

    const route = d.route || [];
    const current = getCurrentApprover(route);
    if (!route.length) return '<span class="text-slate-500">-</span>';
    if (!current && d.status !== '반려') return '<span class="text-sm text-amber-700 font-medium">결재완료</span>';

    const approverName = current ? current.name : '-';
    const refs = d.references || [];

    return `<div class="approver-cell">
        <span class="approver-trigger text-sm text-primary font-medium">${esc(approverName)}</span>
        <div class="apv-tooltip">
            <div class="text-sm text-slate-500 mb-2 font-medium">결재 경로</div>
            ${ApvUI.renderTooltip(route, refs)}
        </div>
    </div>`;
}

function renderDocs() {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const tbody = document.getElementById('docTableBody');
    document.getElementById('totalCount').textContent = filteredDocs.length;
    const isRejected = currentTab === 'rejected';
    document.getElementById('thReason').classList.toggle('hidden', !isRejected);
    document.getElementById('thDate').textContent = isRejected ? '반려일' : '기안일';
    const colCount = 6 + (isRejected ? 1 : 0);
    const start = (currentPage - 1) * perPage;
    const pageData = filteredDocs.slice(start, start + perPage);

    if (!pageData.length) {
        const tabName = {progress:'진행중', approved:'승인', rejected:'반려', temp:'임시저장'}[currentTab] || '';
        const emptyHtml = `<div class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mb-3"><i data-lucide="file-text" class="w-5 h-5 text-slate-400"></i></div>
            <p class="text-sm font-semibold text-slate-200">${tabName ? tabName + ' 문서가 없습니다' : '기안한 문서가 없습니다'}</p>
            <p class="text-sm text-slate-500 mt-1">사이드바의 "기안하기"에서 새 문서를 작성할 수 있어요.</p>
        </div>`;
        tbody.innerHTML = `<tr><td colspan="${colCount}">${emptyHtml}</td></tr>`;
        renderDocCards([], emptyHtml);
        document.getElementById('pagination').innerHTML = '';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    tbody.innerHTML = pageData.map(d => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer transition-colors" onclick="viewDoc(${d.id})">
            <td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.doc_number)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-200 font-medium text-center">${esc(d.title)}</td>
            <td class="px-4 py-3.5 text-sm text-slate-300 text-center">${esc(d.doc_type)}</td>
            <td class="px-4 py-3.5 text-center">${buildApproverCell(d)}</td>
            <td class="px-4 py-3.5 text-center"><span class="px-2 py-0.5 text-sm font-medium rounded-full ${statusBadge(d.status)}">${esc(d.status)}</span></td>
            ${isRejected ? `<td class="px-4 py-3.5 text-sm text-slate-300 text-center">${buildReasonCell(getRejectReason(d))}</td>` : ''}
            <td class="px-4 py-3.5 text-sm text-slate-400 text-center">${formatDate(isRejected ? (d.complete_date || d.draft_date) : d.draft_date)}</td>
        </tr>
    `).join('');

    renderDocCards(pageData);
    renderPagination(filteredDocs.length, perPage);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderDocCards(pageData, emptyHtml = '') {
    const wrap = document.getElementById('docCardList');
    if (!wrap) return;
    if (!pageData.length) {
        wrap.innerHTML = emptyHtml;
        return;
    }
    const isRejected = currentTab === 'rejected';
    wrap.innerHTML = pageData.map(d => {
        const current = getCurrentApprover(d.route || []);
        const approver = current ? current.name : (d.status === '진행' ? '결재완료' : '-');
        const reason = isRejected ? getRejectReason(d) : '';
        return `<article class="approval-mobile-card" onclick="viewDoc(${d.id})">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs text-slate-500">${esc(d.doc_number)} · ${esc(d.doc_type)}</p>
                    <h3 class="mt-1 text-sm font-semibold text-slate-100 leading-5">${esc(d.title)}</h3>
                </div>
                <span class="shrink-0 px-2 py-0.5 text-xs font-medium rounded-full ${statusBadge(d.status)}">${esc(d.status)}</span>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-4 text-xs">
                <div><p class="text-slate-500">현재 결재자</p><p class="mt-1 text-primary font-medium">${esc(approver)}</p></div>
                <div><p class="text-slate-500">${isRejected ? '반려일' : '기안일'}</p><p class="mt-1 text-slate-300">${formatDate(isRejected ? (d.complete_date || d.draft_date) : d.draft_date)}</p></div>
            </div>
            ${reason ? `<div class="mt-3 rounded-lg bg-slate-900 border border-slate-800 p-3 text-xs text-slate-300"><span class="text-slate-500">반려 사유</span><br>${esc(reason)}</div>` : ''}
        </article>`;
    }).join('');
}

function renderPagination(total, perPage) {
    const pages = Math.ceil(total / perPage);
    if (pages <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let h = `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(${currentPage-1})"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= pages; i++) {
        if (pages > 7 && Math.abs(i - currentPage) > 2 && i > 2 && i < pages - 1) { if (i === 3 || i === pages - 2) h += '<span class="px-1 text-slate-400">...</span>'; continue; }
        h += `<button class="pg-btn ${i===currentPage?'pg-active':''}" onclick="goPage(${i})">${i}</button>`;
    }
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${currentPage+1})"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${pages})"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>`;
    document.getElementById('pagination').innerHTML = h;
}

function goPage(p) { const pages = Math.ceil(filteredDocs.length / parseInt(document.getElementById('perPageSelect').value)); if(p<1||p>pages)return; currentPage=p; renderDocs(); }

function resetFilters() {
    document.getElementById('filterTitle').value = '';
    document.getElementById('filterDrafter').value = '';
    document.getElementById('filterDept').value = '';
    document.getElementById('filterDocNum').value = '';
    document.getElementById('filterDocType').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    searchDocs();
}

function viewDoc(id) { location.href = 'approval_view.php?id=' + id; }

function getRejectReason(doc) {
    if (!doc.route) return '';
    const rejected = doc.route.find(s => s.status === '반려');
    return rejected && rejected.comment ? rejected.comment : '';
}

function buildReasonCell(reason) {
    if (!reason) return '<span class="text-slate-500">-</span>';
    const short = reason.length > 15 ? reason.substring(0, 15) + '...' : reason;
    const needsTooltip = reason.length > 15;
    if (!needsTooltip) return `<span class="text-slate-300">${esc(reason)}</span>`;
    return `<span class="reason-cell" onmouseenter="showReasonTip(this)" onmouseleave="hideReasonTip()">
        <span class="text-slate-300">${esc(short)}</span>
        <span class="reason-full">${esc(reason)}</span>
    </span>`;
}

function showReasonTip(el) {
    const tip = el.querySelector('.reason-full');
    if (!tip) return;
    const rect = el.getBoundingClientRect();
    tip.style.top = (rect.bottom + 6) + 'px';
    tip.style.left = Math.min(rect.left, window.innerWidth - 320) + 'px';
    tip.classList.add('show');
}

function hideReasonTip() {
    document.querySelectorAll('.reason-full.show').forEach(t => t.classList.remove('show'));
}

function statusBadge(s) {
    const map = {
        '대기': 'badge-warning',
        '진행': 'badge-info',
        '승인': 'badge-success',
        '결재완료': 'badge-success',
        '반려': 'badge-danger',
        '임시저장': 'badge-neutral',
    };
    return map[s] || 'badge-neutral';
}

function formatDate(d) { return d ? d.replace(/-/g, '.') : '-'; }

const esc = ApvUI.esc;

// 툴팁 위치 계산 (fixed 방식 · overflow 영향 안 받음)
document.addEventListener('mouseover', function(e) {
    const trigger = e.target.closest('.approver-trigger');
    if (!trigger) return;
    const tooltip = trigger.closest('.approver-cell')?.querySelector('.apv-tooltip');
    if (!tooltip) return;

    const rect = trigger.getBoundingClientRect();
    tooltip.classList.add('show');

    const ttRect = tooltip.getBoundingClientRect();
    let top = rect.bottom + 8;
    if (top + ttRect.height > window.innerHeight - 10) {
        top = rect.top - ttRect.height - 8;
    }
    let left = rect.left + rect.width / 2 - ttRect.width / 2;
    if (left < 10) left = 10;
    if (left + ttRect.width > window.innerWidth - 10) left = window.innerWidth - ttRect.width - 10;

    tooltip.style.top = top + 'px';
    tooltip.style.left = left + 'px';
});
document.addEventListener('mouseout', function(e) {
    const trigger = e.target.closest('.approver-trigger');
    if (!trigger) return;
    const tooltip = trigger.closest('.approver-cell')?.querySelector('.apv-tooltip');
    if (tooltip) tooltip.classList.remove('show');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
