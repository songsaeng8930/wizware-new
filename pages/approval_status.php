<?php
$pageTitle = '전체 결재 현황';
$currentPage = 'approval_admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('approval_admin', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$userRole = $_SESSION['user']['role'] ?? '';
if ($userRole !== 'admin') {
    echo '<div class="ml-60 mt-14 p-8"><p class="text-slate-400">관리자만 접근할 수 있습니다.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

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
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <!-- 헤더 -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 mb-4">
            <div class="flex items-center gap-3 mb-3">
                <h2 class="text-lg font-bold text-slate-100">전체 결재 현황</h2>
                <span class="text-xs text-slate-500">관리자 전용 · 전사 결재 문서를 통합 관리</span>
            </div>
            <!-- KPI 카드 -->
            <div class="grid grid-cols-4 gap-3">
                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3">
                    <i data-lucide="loader" class="w-4 h-4 text-primary shrink-0"></i>
                    <span class="text-sm text-slate-400">진행중</span>
                    <span class="ml-auto text-lg font-bold text-slate-100"><span id="kpiInProgress">-</span><span class="text-xs font-normal text-slate-500 ml-0.5">건</span></span>
                </div>
                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                    <span class="text-sm text-slate-400">이번달 완료</span>
                    <span class="ml-auto text-lg font-bold text-slate-100"><span id="kpiMonthDone">-</span><span class="text-xs font-normal text-slate-500 ml-0.5">건</span></span>
                </div>
                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3">
                    <i data-lucide="timer" class="w-4 h-4 text-sky-400 shrink-0"></i>
                    <span class="text-sm text-slate-400">평균 처리일</span>
                    <span class="ml-auto text-lg font-bold text-slate-100"><span id="kpiAvgDays">-</span><span class="text-xs font-normal text-slate-500 ml-0.5">일</span></span>
                </div>
                <button onclick="showExceptions()" class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3 hover:border-rose-500/60 transition-colors text-left">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                    <span class="text-sm text-slate-400">주의 문서</span>
                    <span class="ml-auto text-lg font-bold text-rose-400"><span id="kpiExceptions">-</span><span class="text-xs font-normal text-slate-500 ml-0.5">건</span></span>
                </button>
            </div>
        </div>

        <!-- 필터 토글 -->
        <div class="flex justify-end mb-3">
            <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> 필터
            </button>
        </div>

        <!-- 필터 (접힘) -->
        <div id="filterPanel" class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-3 hidden">
            <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">검색</label>
                    <input type="text" id="filterSearch" class="reg-input" placeholder="제목, 문서번호, 기안자">
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">상태</label>
                    <select id="filterStatus" class="reg-select">
                        <option value="">전체</option>
                        <option value="대기">대기</option>
                        <option value="진행">진행</option>
                        <option value="승인">승인</option>
                        <option value="반려">반려</option>
                        <option value="임시저장">임시저장</option>
                        <option value="회수">회수</option>
                    </select>
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
                <button onclick="loadDocs()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">검색 <i data-lucide="search" class="w-4 h-4"></i></button>
                <button onclick="resetFilters()" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- 문서 목록 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="list-info-bar">
                <span class="info-text">전체 결재 문서 <strong id="totalCount">0</strong></span>
                <div class="flex items-center gap-2">
                    <button onclick="downloadCsv()" class="btn btn-secondary btn-xs">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i> CSV
                    </button>
                    <select id="perPageSelect" class="list-per-page" onchange="loadDocs()">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full emp-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left" style="width:190px">문서번호</th>
                            <th class="px-4 py-3 text-left">제목</th>
                            <th class="px-4 py-3 text-center" style="width:90px">양식</th>
                            <th class="px-4 py-3 text-center" style="width:76px">상태</th>
                            <th class="px-4 py-3 text-left" style="width:120px">기안</th>
                            <th class="px-4 py-3 text-center" style="width:90px">결재자</th>
                            <th class="px-4 py-3 text-center" style="width:90px">기안일</th>
                            <th class="px-4 py-3 text-center" style="width:60px">경과</th>
                            <th class="px-4 py-3 text-center" style="width:180px">관리</th>
                        </tr>
                    </thead>
                    <tbody id="docTableBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>
    </main>
</div>

<!-- 모달: 관리자 액션 -->
<div id="adminModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between p-5 border-b border-slate-800">
            <h3 id="modalTitle" class="text-base font-bold text-slate-100"></h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-200"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div id="modalBody" class="p-5"></div>
        <div class="flex justify-end gap-2 p-5 border-t border-slate-800">
            <button onclick="closeModal()" class="btn btn-secondary">취소</button>
            <button id="modalConfirm" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90">확인</button>
        </div>
    </div>
</div>

<!-- 모달: 예외 문서 -->
<div id="exceptionModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeExceptionModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-3xl max-h-[80vh] bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">주의 문서</h3>
            <button onclick="closeExceptionModal()" class="text-slate-400 hover:text-slate-200"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div id="exceptionBody" class="p-5 overflow-y-auto"></div>
    </div>
</div>

<script>
const API = '<?= $basePath ?>/api/approval_admin.php';
let currentPage = 1;
let totalPages = 1;
let allDocs = [];

document.addEventListener('DOMContentLoaded', loadDocs);

function loadDocs() {
    const p = new URLSearchParams();
    p.set('action', 'getStatus');
    p.set('page', currentPage);
    p.set('per_page', document.getElementById('perPageSelect').value);

    const search = document.getElementById('filterSearch').value;
    const status = document.getElementById('filterStatus').value;
    const docType = document.getElementById('filterDocType').value;
    const df = document.getElementById('filterDateFrom').value;
    const dt = document.getElementById('filterDateTo').value;
    if (search) p.set('search', search);
    if (status) p.set('status', status);
    if (docType) p.set('doc_type', docType);
    if (df) p.set('date_from', df);
    if (dt) p.set('date_to', dt);

    fetch(`${API}?${p}`)
        .then(r => r.json())
        .then(res => {
            if (!res.ok) {
                console.error(res.error);
                document.getElementById('docTableBody').innerHTML = `<tr><td colspan="9" class="py-12 text-center text-sm text-rose-400">데이터를 불러오지 못했습니다. 새로고침해 주세요.</td></tr>`;
                return;
            }
            const d = res.data;
            document.getElementById('kpiInProgress').textContent = d.kpi.in_progress;
            document.getElementById('kpiMonthDone').textContent = d.kpi.month_completed;
            document.getElementById('kpiAvgDays').textContent = d.kpi.avg_days ?? '-';
            document.getElementById('kpiExceptions').textContent = d.kpi.exceptions;

            allDocs = d.documents;
            document.getElementById('totalCount').textContent = d.pagination.total;
            totalPages = d.pagination.pages;
            currentPage = d.pagination.page;
            renderTable();
            renderPagination();
        })
        .catch(() => {
            document.getElementById('docTableBody').innerHTML = `<tr><td colspan="9" class="py-12 text-center text-sm text-rose-400">서버 연결에 실패했습니다. 잠시 후 다시 시도해 주세요.</td></tr>`;
        });
}

function renderTable() {
    const tbody = document.getElementById('docTableBody');
    if (!allDocs.length) {
        const hasFilter = document.getElementById('filterSearch').value || document.getElementById('filterStatus').value || document.getElementById('filterDocType').value || document.getElementById('filterDateFrom').value;
        const msg = hasFilter ? '검색 결과가 없습니다. 필터 조건을 조정해 보세요.' : '결재 문서가 없습니다';
        tbody.innerHTML = `<tr><td colspan="9" class="py-12 text-center">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mb-3"><i data-lucide="${hasFilter ? 'search-x' : 'inbox'}" class="w-5 h-5 text-slate-400"></i></div>
                <p class="text-sm font-semibold text-slate-200">${msg}</p>
            </div>
        </td></tr>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    tbody.innerHTML = allDocs.map(d => {
        const elapsed = d.elapsed_days ?? '';
        let elapsedCls = 'text-slate-400';
        if (elapsed > 30) elapsedCls = 'text-rose-500 font-bold';
        else if (elapsed > 7) elapsedCls = 'text-amber-500 font-medium';
        const approverInner = d.current_approver ? esc(d.current_approver) : '-';
        const approverHtml = `<span class="text-sm ${d.current_approver ? 'text-slate-200' : 'text-slate-500'}" style="border-bottom:1px dashed var(--zm-border);padding-bottom:1px">${approverInner}</span>`;
        return `<tr class="border-b border-slate-800 hover:bg-slate-950/50 transition-colors cursor-pointer" onclick="viewDoc(${d.id})">
            <td class="px-4 py-3.5 text-left"><div class="max-w-[190px] truncate text-xs text-slate-400 font-mono" title="${esc(d.doc_number)}">${esc(d.doc_number)}</div></td>
            <td class="px-4 py-3.5 text-left"><span class="text-sm text-slate-200 font-medium">${esc(d.title)}</span></td>
            <td class="px-4 py-3.5 text-center"><span class="text-xs text-slate-400">${esc(d.form_title || d.doc_type)}</span></td>
            <td class="px-4 py-3.5 text-center">${statusBadgeHtml(d.status)}</td>
            <td class="px-4 py-3.5 text-left">
                <p class="text-sm font-medium text-slate-200 leading-tight">${esc(d.drafter_name)}</p>
                <p class="text-xs text-slate-500 leading-tight mt-0.5">${esc(d.drafter_dept)}</p>
            </td>
            <td class="px-4 py-3.5 text-center approver-cell" data-doc-id="${d.id}" style="cursor:help">${approverHtml}</td>
            <td class="px-4 py-3.5 text-sm text-slate-400 text-center whitespace-nowrap">${fmtDate(d.draft_date)}</td>
            <td class="px-4 py-3.5 text-center whitespace-nowrap"><span class="text-sm ${elapsedCls}">${elapsed !== '' ? elapsed + '일' : '-'}</span></td>
            <td class="px-4 py-3.5 text-center">${buildActions(d)}</td>
        </tr>`;
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function getDocNum(id) {
    const d = allDocs.find(x => x.id === id);
    return d ? d.doc_number : '';
}

function buildActions(d) {
    if (['승인','반려','임시저장','회수'].includes(d.status)) {
        return `<button onclick="event.stopPropagation();openSoftDelete(${d.id})" class="inline-flex items-center gap-1 px-2 py-1 text-xs text-slate-400 hover:text-rose-400 hover:bg-rose-400/10 rounded-md transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>삭제</button>`;
    }
    const elapsed = d.elapsed_days ?? 0;
    const isOverdue = elapsed > 7;
    const reminderCls = isOverdue
        ? 'text-amber-400 bg-amber-400/10 hover:bg-amber-400/20'
        : 'text-slate-400 hover:text-amber-400 hover:bg-amber-400/10';
    return `<div class="flex items-center justify-center gap-1">
        <button onclick="event.stopPropagation();doReminder(${d.id})" title="결재 재알림" class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md transition-colors ${reminderCls}"><i data-lucide="bell-ring" class="w-3.5 h-3.5"></i>재알림</button>
        <button onclick="event.stopPropagation();openAdminWithdraw(${d.id})" title="문서 회수" class="inline-flex items-center gap-1 px-2 py-1 text-xs text-slate-400 hover:text-sky-400 hover:bg-sky-400/10 rounded-md transition-colors"><i data-lucide="undo-2" class="w-3.5 h-3.5"></i>회수</button>
        <div class="relative inline-block">
            <button onclick="event.stopPropagation();toggleActionMenu(this)" class="inline-flex items-center gap-1 px-1.5 py-1 text-xs rounded-md transition-colors" style="color:var(--zm-text-muted)" onmouseover="this.style.background='var(--zm-surface-2)'" onmouseout="this.style.background=''"><i data-lucide="more-horizontal" class="w-4 h-4"></i></button>
            <div class="action-menu hidden absolute right-0 w-44 rounded-lg border shadow-xl z-50 py-1" style="background:var(--zm-surface-1);border-color:var(--zm-border)">
                <button onclick="event.stopPropagation();closeMenus();openChangeApprover(${d.id})" class="w-full text-left px-3 py-2 text-xs flex items-center gap-2 transition-colors" style="color:var(--zm-text-strong)" onmouseover="this.style.background='var(--zm-surface-2)'" onmouseout="this.style.background=''"><i data-lucide="user-round-pen" class="w-3.5 h-3.5 text-primary"></i>결재자 변경</button>
                <div class="mx-2 my-1" style="border-top:1px solid var(--zm-border)"></div>
                <div class="px-3 pt-1 pb-0.5"><span class="text-[10px] font-medium" style="color:var(--zm-text-muted)">관리자 전용</span></div>
                <button onclick="event.stopPropagation();closeMenus();openForceComplete(${d.id})" class="w-full text-left px-3 py-2 text-xs flex items-center gap-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i>강제 완료</button>
                <button onclick="event.stopPropagation();closeMenus();openForceReject(${d.id})" class="w-full text-left px-3 py-2 text-xs flex items-center gap-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"><i data-lucide="x-circle" class="w-3.5 h-3.5"></i>강제 반려</button>
            </div>
        </div>
    </div>`;
}

// ─── 관리자 액션 모달 ───

function openForceComplete(docId) {
    const docNum = esc(getDocNum(docId));
    document.getElementById('modalTitle').textContent = '강제 완료';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-sm text-slate-300 mb-3">문서 <span class="text-primary font-medium">${docNum}</span>을 강제 완료합니다.</p>
        <p class="text-sm text-slate-400 mb-2">남은 결재 단계는 모두 건너뜀 처리되고 문서 상태가 승인으로 변경됩니다.</p>
        <label class="text-sm font-medium text-slate-200">사유 (필수)</label>
        <textarea id="actionComment" rows="3" class="reg-input mt-1 w-full" placeholder="강제 완료 사유를 입력해 주세요"></textarea>`;
    document.getElementById('modalConfirm').onclick = () => {
        const comment = document.getElementById('actionComment').value.trim();
        if (!comment) { alert('사유를 입력해 주세요.'); return; }
        adminAction('forceComplete', { document_id: docId, comment });
    };
    openModal();
}

function openForceReject(docId) {
    const docNum = esc(getDocNum(docId));
    document.getElementById('modalTitle').textContent = '강제 반려';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-sm text-slate-300 mb-3">문서 <span class="text-primary font-medium">${docNum}</span>을 강제 반려합니다.</p>
        <label class="text-sm font-medium text-slate-200">사유 (필수)</label>
        <textarea id="actionComment" rows="3" class="reg-input mt-1 w-full" placeholder="강제 반려 사유를 입력해 주세요"></textarea>`;
    document.getElementById('modalConfirm').onclick = () => {
        const comment = document.getElementById('actionComment').value.trim();
        if (!comment) { alert('사유를 입력해 주세요.'); return; }
        adminAction('forceReject', { document_id: docId, comment });
    };
    openModal();
}

function openSoftDelete(docId) {
    const docNum = esc(getDocNum(docId));
    document.getElementById('modalTitle').textContent = '문서 삭제';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-sm text-slate-300 mb-3">문서 <span class="text-primary font-medium">${docNum}</span>을 삭제합니다.</p>
        <p class="text-xs text-slate-500 mb-3">삭제된 문서는 결재 현황에서 제외되며, 감사 로그에 이력이 남습니다.</p>
        <label class="text-sm font-medium text-slate-200">사유 (필수)</label>
        <textarea id="actionComment" rows="3" class="reg-input mt-1 w-full" placeholder="삭제 사유를 입력해 주세요"></textarea>`;
    document.getElementById('modalConfirm').onclick = () => {
        const comment = document.getElementById('actionComment').value.trim();
        if (!comment) { alert('사유를 입력해 주세요.'); return; }
        adminAction('softDelete', { document_id: docId, comment });
    };
    openModal();
}

function openChangeApprover(docId) {
    const docNum = esc(getDocNum(docId));
    document.getElementById('modalTitle').textContent = '결재자 변경';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-sm text-slate-300 mb-3">문서 <span class="text-primary font-medium">${docNum}</span>의 결재자를 변경합니다.</p>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-slate-200">변경할 결재 단계</label>
                <select id="stepOrder" class="reg-select mt-1 w-full">
                    <option value="">불러오는 중...</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-200">새 결재자</label>
                <div class="relative mt-1">
                    <input type="text" id="approverSearch" class="reg-input w-full" placeholder="이름 또는 부서로 검색" autocomplete="off">
                    <input type="hidden" id="newApproverId">
                    <div id="approverDropdown" class="absolute z-50 left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-700 bg-slate-900 shadow-xl hidden"></div>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-200">사유 (필수)</label>
                <textarea id="actionComment" rows="2" class="reg-input mt-1 w-full" placeholder="변경 사유를 입력해 주세요"></textarea>
            </div>
        </div>`;

    fetch(`${API}?action=getDocSteps&document_id=${docId}`)
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('stepOrder');
            if (!res.ok || !res.data.steps.length) {
                sel.innerHTML = '<option value="">대기 중인 단계 없음</option>';
                return;
            }
            const pending = res.data.steps.filter(s => s.action === '대기');
            if (!pending.length) {
                sel.innerHTML = '<option value="">대기 중인 단계 없음</option>';
                return;
            }
            sel.innerHTML = pending.map(s =>
                `<option value="${s.step_order}">${s.step_order}단계 — ${esc(s.approver_name)} (${esc(s.approver_dept || '부서 미지정')})</option>`
            ).join('');
        });

    const searchInput = document.getElementById('approverSearch');
    const dropdown = document.getElementById('approverDropdown');
    let searchTimer = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const kw = searchInput.value.trim();
        if (kw.length < 1) { dropdown.classList.add('hidden'); return; }
        searchTimer = setTimeout(() => {
            fetch(`${API}?action=getEmployeeList&keyword=${encodeURIComponent(kw)}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.ok || !res.data.employees.length) {
                        dropdown.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">검색 결과 없음</div>';
                        dropdown.classList.remove('hidden');
                        return;
                    }
                    dropdown.innerHTML = res.data.employees.map(e =>
                        `<div class="px-4 py-2.5 text-sm cursor-pointer hover:bg-slate-800 transition-colors" data-id="${e.id}" data-name="${esc(e.name)}" data-dept="${esc(e.dept_name)}">
                            <span class="text-slate-200 font-medium">${esc(e.name)}</span>
                            <span class="text-slate-500 ml-2">${esc(e.dept_name || '부서 미지정')}</span>
                        </div>`
                    ).join('');
                    dropdown.classList.remove('hidden');
                    dropdown.querySelectorAll('[data-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            document.getElementById('newApproverId').value = el.dataset.id;
                            searchInput.value = `${el.dataset.name} (${el.dataset.dept || '부서 미지정'})`;
                            dropdown.classList.add('hidden');
                        });
                    });
                });
        }, 250);
    });

    document.getElementById('modalConfirm').onclick = () => {
        const stepOrder = parseInt(document.getElementById('stepOrder').value);
        const newId = parseInt(document.getElementById('newApproverId').value);
        const comment = document.getElementById('actionComment').value.trim();
        if (!stepOrder) { alert('변경할 결재 단계를 선택해 주세요.'); return; }
        if (!newId) { alert('새 결재자를 검색해서 선택해 주세요.'); return; }
        if (!comment) { alert('변경 사유를 입력해 주세요.'); return; }
        adminAction('changeApprover', {
            document_id: docId,
            step_order: stepOrder,
            new_approver_id: newId,
            comment
        });
    };
    openModal();
}

async function doReminder(docId) {
    if (!(await AppUI.confirm('현재 결재자에게 재알림을 보내시겠습니까?'))) return;
    adminAction('sendReminder', { document_id: docId });
}

function openAdminWithdraw(docId) {
    const docNum = esc(getDocNum(docId));
    document.getElementById('modalTitle').textContent = '문서 회수';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-sm text-slate-300 mb-3">문서 <span class="text-primary font-medium">${docNum}</span>을 회수합니다.</p>
        <p class="text-sm text-slate-400 mb-3">회수하면 결재가 중단되고 기안자에게 회수 알림이 전송됩니다.</p>
        <label class="text-sm font-medium text-slate-200">사유 (필수)</label>
        <textarea id="actionComment" rows="3" class="reg-input mt-1 w-full" placeholder="회수 사유를 입력해 주세요"></textarea>`;
    document.getElementById('modalConfirm').onclick = () => {
        const comment = document.getElementById('actionComment').value.trim();
        if (!comment) { alert('사유를 입력해 주세요.'); return; }
        adminAction('adminWithdraw', { document_id: docId, comment });
    };
    openModal();
}

function adminAction(action, body) {
    fetch(`${API}?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.__csrfToken || '' },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(res => {
        closeModal();
        if (res.ok) {
            alert(res.data.message || '처리되었습니다.');
            loadDocs();
        } else {
            alert(res.error?.message || '오류가 발생했습니다.');
        }
    });
}

// ─── 예외 문서 모달 ───

function showExceptions() {
    fetch(`${API}?action=getExceptions`)
        .then(r => r.json())
        .then(res => {
            if (!res.ok) return;
            const { resigned, delayed } = res.data;
            let html = '';

            if (resigned.length) {
                html += `<h4 class="text-sm font-bold text-rose-400 mb-2"><i data-lucide="user-x" class="w-4 h-4 inline-block mr-1"></i>퇴사자 결재 대기 (${resigned.length}건)</h4>`;
                html += `<div class="space-y-2 mb-5">${resigned.map(d => `
                    <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3 cursor-pointer hover:border-gray-400" onclick="viewDoc(${d.id})">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-200 font-medium truncate">${esc(d.title)}</p>
                            <p class="text-xs text-slate-500 mt-0.5">${esc(d.doc_number)} · ${esc(d.drafter_name)}</p>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-rose-900/40 text-rose-300">${esc(d.approver_name)} [퇴사]</span>
                        <span class="shrink-0 text-xs text-slate-500">${d.elapsed_days}일 경과</span>
                    </div>`).join('')}</div>`;
            }

            if (delayed.length) {
                html += `<h4 class="text-sm font-bold text-amber-400 mb-2"><i data-lucide="clock-alert" class="w-4 h-4 inline-block mr-1"></i>7일 초과 지연 (${delayed.length}건)</h4>`;
                html += `<div class="space-y-2">${delayed.map(d => `
                    <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3 cursor-pointer hover:border-gray-400" onclick="viewDoc(${d.id})">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-200 font-medium truncate">${esc(d.title)}</p>
                            <p class="text-xs text-slate-500 mt-0.5">${esc(d.doc_number)} · ${esc(d.drafter_name)}</p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-500">현재: ${esc(d.current_approver || '-')}</span>
                        <span class="shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-amber-900/40 text-amber-300">${d.elapsed_days}일</span>
                    </div>`).join('')}</div>`;
            }

            if (!resigned.length && !delayed.length) {
                html = '<div class="text-center py-8"><p class="text-sm text-slate-400">주의가 필요한 문서가 없습니다.</p></div>';
            }

            document.getElementById('exceptionBody').innerHTML = html;
            document.getElementById('exceptionModal').classList.remove('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
}

// ─── CSV 다운로드 ───

function csvSafe(v) { const s = String(v ?? ''); return /^[=+\-@\t\r]/.test(s) ? "'" + s.replace(/"/g, '""') : s.replace(/"/g, '""'); }

function downloadCsv() {
    if (!allDocs.length) { alert('다운로드할 데이터가 없습니다.'); return; }
    const BOM = '﻿';
    const header = ['문서번호','제목','양식','상태','기안자','기안부서','현재결재자','기안일','경과일'];
    const rows = allDocs.map(d => [
        d.doc_number, d.title, d.form_title || d.doc_type, d.status,
        d.drafter_name, d.drafter_dept, d.current_approver || '',
        d.draft_date || '', (d.elapsed_days ?? '') + ''
    ].map(v => '"' + csvSafe(v) + '"').join(','));
    const csv = BOM + header.join(',') + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '결재현황_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

// ─── 페이지네이션 ───

function renderPagination() {
    const el = document.getElementById('pagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }
    let h = `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(${currentPage-1})"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
        if (totalPages > 7 && Math.abs(i - currentPage) > 2 && i > 2 && i < totalPages - 1) {
            if (i === 3 || i === totalPages - 2) h += '<span class="px-1 text-slate-400">...</span>';
            continue;
        }
        h += `<button class="pg-btn ${i===currentPage?'pg-active':''}" onclick="goPage(${i})">${i}</button>`;
    }
    h += `<button class="pg-btn ${currentPage>=totalPages?'pg-disabled':''}" onclick="goPage(${currentPage+1})"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage>=totalPages?'pg-disabled':''}" onclick="goPage(${totalPages})"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>`;
    el.innerHTML = h;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function goPage(p) { if (p < 1 || p > totalPages) return; currentPage = p; loadDocs(); }

// ─── 유틸 ───

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDocType').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    currentPage = 1;
    loadDocs();
}

function viewDoc(id) { location.href = 'approval_view.php?id=' + id; }

function toggleActionMenu(btn) {
    closeMenus();
    const menu = btn.nextElementSibling;
    menu.classList.remove('top-full', 'mt-1', 'bottom-full', 'mb-1');
    const rect = btn.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    if (spaceBelow < 200) {
        menu.classList.add('bottom-full', 'mb-1');
    } else {
        menu.classList.add('top-full', 'mt-1');
    }
    menu.classList.toggle('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeMenus() { document.querySelectorAll('.action-menu').forEach(m => m.classList.add('hidden')); }
document.addEventListener('click', (e) => {
    closeMenus();
    const dd = document.getElementById('approverDropdown');
    const si = document.getElementById('approverSearch');
    if (dd && si && !dd.contains(e.target) && e.target !== si) dd.classList.add('hidden');
});

function openModal() { document.getElementById('adminModal').classList.remove('hidden'); if (typeof lucide !== 'undefined') lucide.createIcons(); }
function closeModal() { document.getElementById('adminModal').classList.add('hidden'); }
function closeExceptionModal() { document.getElementById('exceptionModal').classList.add('hidden'); }

function statusBadgeHtml(s) {
    const map = {
        '대기':     'badge-warning',
        '진행':     'badge-info',
        '승인':     'badge-success',
        '반려':     'badge-danger',
        '임시저장': 'badge-neutral',
        '회수':     'badge-neutral',
    };
    const cls = map[s] || 'badge-neutral';
    return `<span class="badge ${cls}">${esc(s)}</span>`;
}

function fmtDate(d) { return d ? d.replace(/-/g, '.') : '-'; }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ─── 결재자 hover 말풍선 (결재선 진행 현황) ───

const stepCache = {};
let approverTip = null;
let currentHoverId = null;

function ensureTip() {
    if (approverTip) return approverTip;
    approverTip = document.createElement('div');
    approverTip.style.cssText = 'position:fixed;z-index:60;display:none;min-width:220px;max-width:320px;padding:10px 12px;border-radius:10px;font-size:12px;line-height:1.5;background:var(--zm-surface-1);border:1px solid var(--zm-border);box-shadow:0 8px 24px rgba(0,0,0,.18);color:var(--zm-text-default);pointer-events:none';
    document.body.appendChild(approverTip);
    return approverTip;
}

function renderSteps(steps) {
    if (!steps || !steps.length) return '<div style="color:var(--zm-text-subtle)">결재 이력이 없습니다</div>';
    const total = steps.length;
    const rejected = steps.some(s => s.action === '반려');
    const curIdx = steps.findIndex(s => s.action === '대기');
    let head;
    if (rejected) head = '<span style="color:var(--zm-st-danger-fg);font-weight:700">반려됨</span>';
    else if (curIdx === -1) head = '<span style="color:var(--zm-st-success-fg);font-weight:700">결재 완료</span>';
    else head = `결재 진행 <b style="color:var(--zm-text-strong)">${curIdx + 1}</b> / ${total} 단계`;

    const rows = steps.map((s, i) => {
        const cur = (i === curIdx);
        let icon, color, extra = '';
        switch (s.action) {
            case '승인': icon = '✓'; color = 'var(--zm-st-success-fg)'; break;
            case '반려': icon = '✕'; color = 'var(--zm-st-danger-fg)'; break;
            case '건너뜀': icon = '–'; color = 'var(--zm-text-subtle)'; extra = 'text-decoration:line-through;opacity:.55'; break;
            default: icon = cur ? '●' : '○'; color = cur ? 'var(--zm-primary)' : 'var(--zm-text-subtle)';
        }
        const roleBadge = (s.role === '전결' || s.role === '협조')
            ? ` <span style="font-size:10px;padding:1px 5px;border-radius:6px;background:var(--zm-primary-tint-12);color:var(--zm-primary);font-weight:600">${esc(s.role)}</span>` : '';
        const nameStyle = cur ? 'font-weight:700;color:var(--zm-text-strong)' : '';
        return `<div style="display:flex;align-items:center;gap:6px;padding:3px 0;${extra}">
            <span style="width:14px;text-align:center;flex-shrink:0;color:${color}">${icon}</span>
            <span style="flex-shrink:0;color:var(--zm-text-subtle)">${s.step_order}</span>
            <span style="${nameStyle}">${esc(s.approver_name)}</span>
            <span style="color:var(--zm-text-subtle);font-size:11px">${esc(s.approver_dept || '')}</span>${roleBadge}
        </div>`;
    }).join('');

    return `<div style="font-weight:600;margin-bottom:6px;padding-bottom:6px;border-bottom:1px solid var(--zm-border)">${head}</div>${rows}`;
}

function positionTip(el) {
    const tip = approverTip;
    tip.style.display = 'block';
    const r = el.getBoundingClientRect();
    const tw = tip.offsetWidth, th = tip.offsetHeight;
    let left = r.left + r.width / 2 - tw / 2;
    left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));
    let top = r.bottom + 6;
    if (top + th > window.innerHeight - 8) top = r.top - th - 6;
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
}

function showApproverTip(el, docId) {
    const tip = ensureTip();
    const paint = (steps) => { tip.innerHTML = renderSteps(steps); positionTip(el); };
    if (stepCache[docId]) { paint(stepCache[docId]); return; }
    tip.innerHTML = '<div style="color:var(--zm-text-subtle)">불러오는 중…</div>';
    positionTip(el);
    fetch(`${API}?action=getDocSteps&document_id=${docId}`)
        .then(r => r.json())
        .then(res => {
            const steps = (res.ok && res.data && res.data.steps) ? res.data.steps : [];
            stepCache[docId] = steps;
            if (currentHoverId === docId) paint(steps);
        })
        .catch(() => {
            if (currentHoverId === docId) { tip.innerHTML = '<div style="color:var(--zm-st-danger-fg)">불러오지 못했습니다</div>'; }
        });
}

document.getElementById('docTableBody').addEventListener('mouseover', (e) => {
    const cell = e.target.closest('.approver-cell');
    if (!cell) return;
    const id = cell.dataset.docId;
    if (currentHoverId === id) return;
    currentHoverId = id;
    showApproverTip(cell, id);
});
document.getElementById('docTableBody').addEventListener('mouseout', (e) => {
    const cell = e.target.closest('.approver-cell');
    if (!cell) return;
    if (cell.contains(e.relatedTarget)) return;
    currentHoverId = null;
    if (approverTip) approverTip.style.display = 'none';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
