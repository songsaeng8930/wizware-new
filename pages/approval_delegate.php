<?php
$isAdminMode = ($_GET['mode'] ?? '') === 'admin';
$pageTitle = $isAdminMode ? '대결 현황' : '대결자 설정';
$currentPage = $isAdminMode ? 'approval_admin' : 'approval';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('approval_admin', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
if ($pdo) {
    try { $pdo->query('SELECT 1 FROM approval_delegates LIMIT 0'); $hasDB = true; }
    catch (PDOException $e) { $hasDB = false; }
}

$currentUser = getCurrentUser() ?? [];
$userName = $currentUser['name'] ?? '';
$userId = (int)($currentUser['id'] ?? 0);
$userRole = getCurrentUserRole();
$canAdmin = in_array($userRole, ['admin', 'manager'], true);

if ($isAdminMode && !$canAdmin) {
    header('Location: ' . ($basePath ?? '') . '/pages/approval_delegate.php');
    exit;
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6 bg-[var(--zm-surface-0)] min-h-screen">

<div class="max-w-3xl mx-auto">
    <?php if (!$isAdminMode): ?>
    <a href="<?= $basePath ?>/pages/approval_pending.php" class="inline-flex items-center gap-1 text-sm text-[var(--zm-text-muted)] hover:text-[var(--zm-primary)] transition-colors mb-3">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>결재함으로 돌아가기
    </a>
    <?php endif; ?>
    <h2 class="zm-heading-page text-lg mb-5"><?= $isAdminMode ? '대결 현황' : '대결자 설정' ?></h2>

    <?php if (!$hasDB): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-700">
        approval_delegates 테이블이 없습니다. <code>db/migrate_approval_delegates.sql</code>을 실행하세요.
    </div>
    <?php else: ?>

    <!-- 현재 대결 상태 -->
    <div id="currentDelegation" class="mb-5"></div>

    <!-- 대결 설정 폼 -->
    <?php if (!$isAdminMode): ?>
    <div id="delegateForm" class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl shadow-sm p-5 mb-5" style="display:none;">
        <h3 class="zm-heading-section text-[15px] mb-4">새 대결 지정</h3>
        <div class="space-y-3">
            <div>
                <label class="zm-label block text-sm mb-1">대결자</label>
                <div class="relative">
                    <div class="relative flex items-center border border-transparent rounded-lg bg-[var(--zm-surface-1)] focus-within:border-[var(--zm-border)]">
                        <i data-lucide="search" class="ml-3 w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                        <input type="text" id="delegateSearch" placeholder="이름 또는 부서 검색" autocomplete="off"
                               class="flex-1 px-2 py-2.5 text-sm bg-transparent text-[var(--zm-text-default)] outline-none border-none focus:ring-0">
                        <div id="delegateFilterChips" class="flex items-center gap-1 pr-2.5 shrink-0">
                            <button type="button" data-filter="all" class="delegate-filter-chip active text-[11px] px-2.5 py-1 rounded-md transition-colors">전체</button>
                            <button type="button" data-filter="dept" class="delegate-filter-chip text-[11px] px-2.5 py-1 rounded-md transition-colors">우리 부서</button>
                        </div>
                    </div>
                    <div id="delegateDropdown" class="absolute z-10 w-full mt-1 bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-lg shadow-lg max-h-56 overflow-y-auto hidden"></div>
                    <input type="hidden" id="selectedDelegateId">
                    <div id="selectedDelegateBadge" class="mt-1.5 hidden">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[var(--zm-primary-tint-12)] text-[var(--zm-primary)] rounded-full text-sm font-medium">
                            <span id="selectedDelegateName"></span>
                            <button onclick="clearDelegate()" class="opacity-60 hover:opacity-100">&times;</button>
                        </span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="zm-label block text-sm mb-1">시작일</label>
                    <input type="date" id="startDate" min="<?= date('Y-m-d') ?>" class="reg-input">
                </div>
                <div>
                    <label class="zm-label block text-sm mb-1">종료일</label>
                    <input type="date" id="endDate" min="<?= date('Y-m-d') ?>" class="reg-input">
                </div>
            </div>
            <div>
                <label class="zm-label block text-sm mb-1">사유 <span class="text-[var(--zm-text-subtle)]">(선택)</span></label>
                <input type="text" id="reason" placeholder="예: 연차 휴가" maxlength="200" class="reg-input">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="hideForm()" class="zm-btn zm-btn-utility">취소</button>
            <button onclick="submitDelegation()" class="zm-btn zm-btn-cta">대결 지정</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- 관리자 모드: 설정 폼 -->
    <?php if ($isAdminMode): ?>
    <div id="adminForm" class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl shadow-sm p-5 mb-5" style="display:none;">
        <h3 class="zm-heading-section text-[15px] mb-4">대결 지정 (관리자)</h3>
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="zm-label block text-sm mb-1">원결재자</label>
                    <div class="relative">
                        <input type="text" id="adminDelegatorSearch" placeholder="이름 검색" autocomplete="off" class="reg-input">
                        <div id="adminDelegatorDropdown" class="absolute z-10 w-full mt-1 bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></div>
                        <input type="hidden" id="adminSelectedDelegatorId">
                        <div id="adminSelectedDelegatorBadge" class="mt-1.5 hidden">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[var(--zm-surface-2)] text-[var(--zm-text-default)] rounded-full text-sm font-medium">
                                <span id="adminSelectedDelegatorName"></span>
                                <button onclick="clearAdminDelegator()" class="opacity-60 hover:opacity-100">&times;</button>
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="zm-label block text-sm mb-1">대결자</label>
                    <div class="relative">
                        <input type="text" id="adminDelegateSearch" placeholder="이름 검색" autocomplete="off" class="reg-input">
                        <div id="adminDelegateDropdown" class="absolute z-10 w-full mt-1 bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></div>
                        <input type="hidden" id="adminSelectedDelegateId">
                        <div id="adminSelectedDelegateBadge" class="mt-1.5 hidden">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[var(--zm-primary-tint-12)] text-[var(--zm-primary)] rounded-full text-sm font-medium">
                                <span id="adminSelectedDelegateName"></span>
                                <button onclick="clearAdminDelegate()" class="opacity-60 hover:opacity-100">&times;</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="zm-label block text-sm mb-1">시작일</label>
                    <input type="date" id="adminStartDate" class="reg-input">
                </div>
                <div>
                    <label class="zm-label block text-sm mb-1">종료일</label>
                    <input type="date" id="adminEndDate" class="reg-input">
                </div>
            </div>
            <div>
                <label class="zm-label block text-sm mb-1">사유 <span class="text-[var(--zm-text-subtle)]">(선택)</span></label>
                <input type="text" id="adminReason" placeholder="예: 해외출장" maxlength="200" class="reg-input">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="hideAdminForm()" class="zm-btn zm-btn-utility">취소</button>
            <button onclick="submitAdminDelegation()" class="zm-btn zm-btn-cta">대결 지정</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- 대결 이력 / 전사 현황 -->
    <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-[var(--zm-border)]">
            <span class="zm-heading-section text-sm"><?= $isAdminMode ? '전사 대결 현황' : '대결 이력' ?></span>
            <?php if ($isAdminMode): ?>
            <div class="flex items-center gap-2">
                <select id="adminStatusFilter" onchange="loadAdminList()" class="reg-select text-sm" style="min-height:32px">
                    <option value="active">활성</option>
                    <option value="all">전체</option>
                    <option value="expired">만료</option>
                    <option value="cancelled">해제</option>
                </select>
                <button onclick="showAdminForm()" class="zm-btn zm-btn-cta text-xs px-3 py-1.5">대결 지정</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="emp-table w-full">
                <thead>
                    <tr class="border-b border-[var(--zm-border)] bg-[var(--zm-surface-2)]">
                        <?php if ($isAdminMode): ?><th class="px-4 py-3 text-left text-xs font-medium text-[var(--zm-text-subtle)]">원결재자</th><?php endif; ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-[var(--zm-text-subtle)]">대결자</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-[var(--zm-text-subtle)]">기간</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-[var(--zm-text-subtle)]">상태</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-[var(--zm-text-subtle)]">사유</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-[var(--zm-text-subtle)]">설정</th>
                        <?php if ($isAdminMode): ?><th class="px-4 py-3 text-center text-xs font-medium text-[var(--zm-text-subtle)]">관리</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="<?= $isAdminMode ? 7 : 5 ?>" class="text-center py-10 text-[var(--zm-text-subtle)] text-sm">로딩 중...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>

</main>
</div>

<style>
.delegate-filter-chip { color: var(--zm-text-muted); cursor: pointer; background: var(--zm-surface-2); border: none; font-weight: 500; }
.delegate-filter-chip:hover { color: var(--zm-text-default); background: var(--zm-surface-3); }
.delegate-filter-chip.active { color: var(--zm-primary); background: var(--zm-primary-tint-12); }
#delegateSearch:focus, #delegateSearch:focus-visible { border-color: transparent !important; box-shadow: none !important; outline: none !important; }
</style>
<script>
const API = '<?= $basePath ?? '' ?>/api/approval_delegate.php';
const IS_ADMIN = <?= $isAdminMode ? 'true' : 'false' ?>;
const CURRENT_USER_ID = <?= $userId ?>;

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function statusBadge(status) {
    const map = {
        active: '<span class="badge-success">활성</span>',
        expired: '<span class="badge-muted">만료</span>',
        cancelled: '<span class="badge-danger">해제</span>',
    };
    return map[status] || esc(status);
}

function createdByLabel(type) {
    return type === 'admin' ? '관리자' : '본인';
}

// ── 현재 대결 상태 로드 ──
function loadCurrentDelegation() {
    if (IS_ADMIN) return;
    fetch(API + '?action=getMyDelegate')
        .then(r => r.json())
        .then(res => {
            var el = document.getElementById('currentDelegation');
            var d = res.data && res.data.delegation;
            if (d) {
                el.innerHTML =
                    '<div class="bg-[var(--zm-st-info-bg)] border border-[var(--zm-st-info-bd)] rounded-xl shadow-sm p-4 flex items-center justify-between">' +
                        '<div class="flex items-center gap-3">' +
                            '<div class="w-9 h-9 rounded-full bg-[var(--zm-primary-tint-12)] flex items-center justify-center"><i data-lucide="user-check" class="w-4 h-4 text-[var(--zm-primary)]"></i></div>' +
                            '<div>' +
                                '<div class="text-sm font-semibold text-[var(--zm-text-strong)]">' + esc(d.delegate_name) + '님에게 대결 위임 중</div>' +
                                '<div class="text-xs text-[var(--zm-text-muted)] mt-0.5">' + esc(d.start_date) + ' ~ ' + esc(d.end_date) + (d.reason ? ' · ' + esc(d.reason) : '') + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<button id="cancelDelegationBtn" data-id="' + d.id + '" class="zm-btn zm-btn-utility text-xs text-[var(--zm-st-danger-fg)]" style="border-color:var(--zm-st-danger-bd)">해제</button>' +
                    '</div>';
                document.getElementById('delegateForm').style.display = 'none';
                var cancelBtn = document.getElementById('cancelDelegationBtn');
                if (cancelBtn) cancelBtn.addEventListener('click', function() { cancelMyDelegation(parseInt(cancelBtn.dataset.id)); });
                lucide.createIcons();
            } else {
                el.innerHTML =
                    '<div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl shadow-sm p-4 flex items-center justify-between">' +
                        '<div class="flex items-center gap-3">' +
                            '<div class="w-9 h-9 rounded-full bg-[var(--zm-surface-2)] flex items-center justify-center"><i data-lucide="user-round-x" class="w-4 h-4 text-[var(--zm-text-subtle)]"></i></div>' +
                            '<div class="text-sm text-[var(--zm-text-muted)]">활성 대결이 없습니다. 휴가·출장 시 대결자를 지정하면 대리 결재를 받을 수 있어요.</div>' +
                        '</div>' +
                        '<button onclick="showForm()" class="zm-btn zm-btn-cta text-xs whitespace-nowrap">대결 지정</button>' +
                    '</div>';
                lucide.createIcons();
            }
        });
}

async function cancelMyDelegation(id) {
    if (!(await AppUI.confirm('대결을 해제하시겠습니까?'))) return;
    fetch(API + '?action=cancelDelegate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id}),
    }).then(r => r.json()).then(res => {
        if (res.ok) { loadCurrentDelegation(); loadHistory(); AppUI?.toast?.('대결이 해제되었습니다.', 'success'); }
        else { AppUI?.toast?.(res.error?.message || '오류', 'error'); }
    });
}

// ── 대결 설정 폼 ──
function showForm() { document.getElementById('delegateForm').style.display = ''; }
function hideForm() { document.getElementById('delegateForm').style.display = 'none'; }

var searchTimer = null;
var delegateFilter = 'all';
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('delegateSearch');
    if (input) {
        input.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() { searchDelegates(input.value); }, 200);
        });
        input.addEventListener('focus', function() { searchDelegates(input.value); });
    }
    document.querySelectorAll('.delegate-filter-chip').forEach(function(btn) {
        btn.addEventListener('click', function() {
            delegateFilter = btn.dataset.filter;
            document.querySelectorAll('.delegate-filter-chip').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var searchInput = document.getElementById('delegateSearch');
            if (searchInput) searchDelegates(searchInput.value);
        });
    });
    loadCurrentDelegation();
    loadHistory();
    if (IS_ADMIN) loadAdminList();
});

function searchDelegates(q) {
    var url = API + '?action=getEligibleDelegates&filter=' + delegateFilter;
    if (q && q.trim()) url += '&q=' + encodeURIComponent(q.trim());
    fetch(url)
        .then(r => r.json())
        .then(res => {
            var dd = document.getElementById('delegateDropdown');
            var list = res.data?.employees || [];
            if (!list.length) {
                dd.innerHTML = '<div class="px-3 py-3 text-sm text-[var(--zm-text-subtle)] text-center">' + (q ? '검색 결과 없음' : '대결 가능한 직원이 없습니다') + '</div>';
                dd.classList.remove('hidden');
                return;
            }
            dd.innerHTML = list.map(function(e) {
                return '<div class="px-3 py-2.5 text-sm hover:bg-[var(--zm-surface-2)] cursor-pointer flex items-center justify-between delegate-option transition-colors" data-id="' + e.id + '" data-name="' + esc(e.name) + '">' +
                    '<div class="flex items-center gap-2">' +
                        '<div class="w-7 h-7 rounded-full bg-[var(--zm-primary-tint-12)] flex items-center justify-center text-xs font-medium text-[var(--zm-primary)]">' + esc(e.name.charAt(0)) + '</div>' +
                        '<div><div class="font-medium text-[var(--zm-text-default)]">' + esc(e.name) + '</div>' +
                        '<div class="text-[11px] text-[var(--zm-text-subtle)]">' + esc(e.rank_name || '') + '</div></div>' +
                    '</div>' +
                    '<span class="text-xs text-[var(--zm-text-subtle)]">' + esc(e.dept_name) + '</span></div>';
            }).join('');
            dd.querySelectorAll('.delegate-option').forEach(function(el) {
                el.addEventListener('click', function() { selectDelegate(parseInt(el.dataset.id), el.dataset.name); });
            });
            dd.classList.remove('hidden');
        });
}

function selectDelegate(id, name) {
    document.getElementById('selectedDelegateId').value = id;
    document.getElementById('selectedDelegateName').textContent = name;
    document.getElementById('selectedDelegateBadge').classList.remove('hidden');
    document.getElementById('delegateSearch').value = '';
    document.getElementById('delegateDropdown').classList.add('hidden');
}

function clearDelegate() {
    document.getElementById('selectedDelegateId').value = '';
    document.getElementById('selectedDelegateBadge').classList.add('hidden');
}

function submitDelegation() {
    var delegateId = parseInt(document.getElementById('selectedDelegateId').value);
    var startDate = document.getElementById('startDate').value;
    var endDate = document.getElementById('endDate').value;
    var reason = document.getElementById('reason').value;
    if (!delegateId) { AppUI?.toast?.('대결자를 선택하세요.', 'error'); return; }
    if (!startDate || !endDate) { AppUI?.toast?.('기간을 입력하세요.', 'error'); return; }
    fetch(API + '?action=setDelegate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({delegate_id: delegateId, start_date: startDate, end_date: endDate, reason: reason}),
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            AppUI?.toast?.('대결자가 지정되었습니다.', 'success');
            hideForm(); clearDelegate();
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('reason').value = '';
            loadCurrentDelegation(); loadHistory();
        } else { AppUI?.toast?.(res.error?.message || '오류', 'error'); }
    });
}

// ── 이력 로드 ──
function loadHistory() {
    if (IS_ADMIN) return;
    fetch(API + '?action=getMyHistory')
        .then(r => r.json())
        .then(res => {
            var list = res.data?.history || [];
            var tbody = document.getElementById('historyTableBody');
            if (!list.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-[var(--zm-text-subtle)] text-sm">대결 이력이 없습니다.</td></tr>';
                return;
            }
            tbody.innerHTML = list.map(function(d) {
                return '<tr class="border-b border-[var(--zm-border)] hover:bg-[var(--zm-surface-2)] transition-colors">' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-default)] font-medium">' + esc(d.delegate_name) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-muted)] text-center zm-numeric">' + esc(d.start_date) + ' ~ ' + esc(d.end_date) + '</td>' +
                    '<td class="px-4 py-3 text-center">' + statusBadge(d.status) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-muted)]">' + esc(d.reason || '-') + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-subtle)] text-center">' + createdByLabel(d.created_by_type) + '</td>' +
                '</tr>';
            }).join('');
        });
}

// ── 관리자 모드 ──
function loadAdminList() {
    if (!IS_ADMIN) return;
    var status = document.getElementById('adminStatusFilter')?.value || 'active';
    fetch(API + '?action=getDelegatesAll&status=' + status)
        .then(r => r.json())
        .then(res => {
            var list = res.data?.delegations || [];
            var tbody = document.getElementById('historyTableBody');
            if (!list.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-[var(--zm-text-subtle)] text-sm">대결 내역이 없습니다.</td></tr>';
                return;
            }
            tbody.innerHTML = list.map(function(d) {
                var cancelBtn = d.status === 'active'
                    ? '<button class="text-xs text-[var(--zm-st-danger-fg)] hover:underline admin-cancel-btn" data-id="' + d.id + '">해제</button>'
                    : '<span class="text-[var(--zm-text-subtle)]">-</span>';
                return '<tr class="border-b border-[var(--zm-border)] hover:bg-[var(--zm-surface-2)] transition-colors">' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-default)] font-medium">' + esc(d.delegator_name) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-default)] font-medium">' + esc(d.delegate_name) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-muted)] text-center zm-numeric">' + esc(d.start_date) + ' ~ ' + esc(d.end_date) + '</td>' +
                    '<td class="px-4 py-3 text-center">' + statusBadge(d.status) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-muted)]">' + esc(d.reason || '-') + '</td>' +
                    '<td class="px-4 py-3 text-sm text-[var(--zm-text-subtle)] text-center">' + createdByLabel(d.created_by_type) + '</td>' +
                    '<td class="px-4 py-3 text-center">' + cancelBtn + '</td>' +
                '</tr>';
            }).join('');
            tbody.querySelectorAll('.admin-cancel-btn').forEach(function(btn) {
                btn.addEventListener('click', function() { cancelAdminDelegation(parseInt(btn.dataset.id)); });
            });
        });
}

function showAdminForm() { document.getElementById('adminForm').style.display = ''; }
function hideAdminForm() { document.getElementById('adminForm').style.display = 'none'; }

async function cancelAdminDelegation(id) {
    if (!(await AppUI.confirm('이 대결을 해제하시겠습니까?'))) return;
    fetch(API + '?action=cancelDelegateAdmin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id}),
    }).then(r => r.json()).then(res => {
        if (res.ok) { loadAdminList(); AppUI?.toast?.('대결이 해제되었습니다.', 'success'); }
        else { AppUI?.toast?.(res.error?.message || '오류', 'error'); }
    });
}

// ── 관리자 직원 검색 (원결재자/대결자) ──
function setupAdminSearch(inputId, dropdownId, selectCallback) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var timer = null;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            var q = input.value.trim();
            if (q.length < 1) { document.getElementById(dropdownId).classList.add('hidden'); return; }
            fetch('<?= $basePath ?? '' ?>/api/organization.php?action=getEmployees&search=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(res => {
                    var list = (res.employees || res.data?.employees || []).slice(0, 20);
                    var dd = document.getElementById(dropdownId);
                    if (!list.length) { dd.innerHTML = '<div class="px-3 py-2 text-sm text-[var(--zm-text-subtle)]">결과 없음</div>'; dd.classList.remove('hidden'); return; }
                    dd.innerHTML = list.map(function(e) {
                        return '<div class="px-3 py-2 text-sm hover:bg-[var(--zm-surface-2)] cursor-pointer admin-search-option transition-colors" data-id="' + e.id + '" data-name="' + esc(e.name) + '"><span class="font-medium text-[var(--zm-text-default)]">' + esc(e.name) + '</span> <span class="text-[var(--zm-text-subtle)]">' + esc(e.department_name || e.dept_name || '') + '</span></div>';
                    }).join('');
                    dd.querySelectorAll('.admin-search-option').forEach(function(el) {
                        el.addEventListener('click', function() { selectCallback(parseInt(el.dataset.id), el.dataset.name); });
                    });
                    dd.classList.remove('hidden');
                });
        }, 300);
    });
}

function selectAdminDelegator(id, name) {
    document.getElementById('adminSelectedDelegatorId').value = id;
    document.getElementById('adminSelectedDelegatorName').textContent = name;
    document.getElementById('adminSelectedDelegatorBadge').classList.remove('hidden');
    document.getElementById('adminDelegatorSearch').value = '';
    document.getElementById('adminDelegatorDropdown').classList.add('hidden');
}
function clearAdminDelegator() {
    document.getElementById('adminSelectedDelegatorId').value = '';
    document.getElementById('adminSelectedDelegatorBadge').classList.add('hidden');
}
function selectAdminDelegate(id, name) {
    document.getElementById('adminSelectedDelegateId').value = id;
    document.getElementById('adminSelectedDelegateName').textContent = name;
    document.getElementById('adminSelectedDelegateBadge').classList.remove('hidden');
    document.getElementById('adminDelegateSearch').value = '';
    document.getElementById('adminDelegateDropdown').classList.add('hidden');
}
function clearAdminDelegate() {
    document.getElementById('adminSelectedDelegateId').value = '';
    document.getElementById('adminSelectedDelegateBadge').classList.add('hidden');
}

function submitAdminDelegation() {
    var delegatorId = parseInt(document.getElementById('adminSelectedDelegatorId').value);
    var delegateId = parseInt(document.getElementById('adminSelectedDelegateId').value);
    var startDate = document.getElementById('adminStartDate').value;
    var endDate = document.getElementById('adminEndDate').value;
    var reason = document.getElementById('adminReason').value;
    if (!delegatorId) { AppUI?.toast?.('원결재자를 선택하세요.', 'error'); return; }
    if (!delegateId) { AppUI?.toast?.('대결자를 선택하세요.', 'error'); return; }
    if (!startDate || !endDate) { AppUI?.toast?.('기간을 입력하세요.', 'error'); return; }
    fetch(API + '?action=setDelegateAdmin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({delegator_id: delegatorId, delegate_id: delegateId, start_date: startDate, end_date: endDate, reason: reason}),
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            AppUI?.toast?.('대결자가 지정되었습니다.', 'success');
            hideAdminForm(); clearAdminDelegator(); clearAdminDelegate();
            loadAdminList();
        } else { AppUI?.toast?.(res.error?.message || '오류', 'error'); }
    });
}

if (IS_ADMIN) {
    document.addEventListener('DOMContentLoaded', function() {
        setupAdminSearch('adminDelegatorSearch', 'adminDelegatorDropdown', selectAdminDelegator);
        setupAdminSearch('adminDelegateSearch', 'adminDelegateDropdown', selectAdminDelegate);
    });
}

document.addEventListener('click', function(e) {
    var pairs = [
        ['delegateDropdown', 'delegateSearch'],
        ['adminDelegatorDropdown', 'adminDelegatorSearch'],
        ['adminDelegateDropdown', 'adminDelegateSearch'],
    ];
    pairs.forEach(function(pair) {
        var dd = document.getElementById(pair[0]);
        var input = document.getElementById(pair[1]);
        if (dd && !dd.contains(e.target) && e.target !== input) dd.classList.add('hidden');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
