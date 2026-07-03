<?php
/**
 * Zaemit 그룹웨어 - 직원 정보 변경요청 관리
 * 관리자/매니저 전용 · 직원이 제출한 개인정보 변경요청을 승인/반려.
 */
$pageTitle = '정보 변경요청';
$currentPage = 'hr';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('hr', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$_crUser = $_SESSION['user'] ?? [];
if (!in_array($_crUser['role'] ?? '', ['admin', 'manager'], true)) {
    header('Location: ' . $basePath . '/pages/dashboard.php');
    exit;
}

if (!function_exists('esc')) {
    function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6 bg-slate-950 min-h-[calc(100vh-3.5rem)]">

    <!-- 페이지 헤더 -->
    <div class="flex items-center gap-2 mb-4 text-xs text-slate-500">
        <a href="<?= $basePath ?>/pages/employees.php" class="hover:text-primary">인사관리</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-slate-300">정보 변경요청</span>
    </div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-slate-100">직원 정보 변경요청</h1>
            <p class="text-sm text-slate-400 mt-1">직원이 제출한 개인정보 변경 요청을 검토하고 처리합니다.</p>
        </div>
    </div>

    <!-- 상태 필터 탭 -->
    <div id="statusTabs" class="flex items-center gap-2 mb-5">
        <button data-status="대기" class="cr-tab cr-tab-active">
            대기 <span class="cr-badge" id="cntPending" data-count="0">0</span>
        </button>
        <button data-status="전체" class="cr-tab">전체</button>
        <button data-status="승인" class="cr-tab">승인</button>
        <button data-status="반려" class="cr-tab">반려</button>
    </div>

    <!-- 요청 목록 -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-900/60 text-slate-400 text-left">
                        <th class="px-5 py-3 font-medium">직원</th>
                        <th class="px-5 py-3 font-medium">부서</th>
                        <th class="px-5 py-3 font-medium">변경내용</th>
                        <th class="px-5 py-3 font-medium">요청일</th>
                        <th class="px-5 py-3 font-medium">상태</th>
                        <th class="px-5 py-3 font-medium text-center">처리</th>
                    </tr>
                </thead>
                <tbody id="crTableBody">
                    <tr id="crLoading"><td colspan="6" class="px-5 py-8 text-center text-slate-500">불러오는 중...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="crEmptyRow" class="hidden px-5 py-12 text-center text-slate-500">
            <i data-lucide="inbox" class="inline w-8 h-8 text-slate-600 mb-2"></i>
            <p>해당하는 변경요청이 없습니다.</p>
        </div>
    </div>

</main>
</div>

<!-- 공용 확인 모달 (승인/반려) -->
<div id="crConfirmModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" id="crModalOverlay"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-slate-900 rounded-xl border border-slate-700 shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
                <div id="crModalIcon"></div>
                <h3 id="crModalTitle" class="text-base font-bold text-slate-100"></h3>
            </div>
            <p id="crModalDesc" class="text-sm text-slate-400 mb-4"></p>
            <div id="crModalReasonWrap" class="hidden mb-4">
                <textarea id="crModalReasonInput" class="reg-input w-full" rows="3" maxlength="500" placeholder="반려 사유를 입력해주세요 (필수)"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="crModalCancelBtn" class="btn btn-secondary">취소</button>
                <button type="button" id="crModalConfirmBtn" class="btn btn-primary"></button>
            </div>
        </div>
    </div>
</div>

<!-- 토스트 컨테이너 -->
<div id="toastWrap" class="fixed bottom-6 right-6 z-[70] space-y-2"></div>

<style>
.cr-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 500;
    color: var(--zm-text-muted);
    background: transparent; border: 1px solid transparent;
    cursor: pointer; transition: all .15s;
}
.cr-tab:hover { color: var(--zm-text-default); background: var(--zm-surface-2); }
.cr-tab-active {
    color: var(--zm-text-strong) !important;
    background: var(--zm-surface-2) !important;
    border-color: var(--zm-border) !important;
}
.cr-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 18px; padding: 0 5px;
    border-radius: 9px; font-size: 11px; font-weight: 600;
    background: var(--zm-status-warn-fg);
    color: #fff;
}
.cr-badge[data-count="0"] { display: none; }
</style>

<script>
(function() {
    var basePath = '<?= esc($basePath) ?>';
    var API = basePath + '/api/employee_change_request.php';
    var FIELD_LABELS = { birth_date:'생년월일', gender:'성별', zipcode:'우편번호', address1:'기본주소', address2:'상세주소' };
    var GENDER_LABELS = { M:'남', F:'여' };
    var currentStatus = '대기';
    var modalState = { type: null, targetId: null };

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function toast(msg, type) {
        var wrap = document.getElementById('toastWrap');
        var el = document.createElement('div');
        var bg = type === 'error' ? 'bg-amber-500' : 'bg-primary';
        el.className = 'text-white text-sm px-4 py-2.5 rounded-lg shadow-lg ' + bg;
        el.setAttribute('role', 'status');
        el.textContent = msg;
        wrap.appendChild(el);
        setTimeout(function() {
            el.style.transition = 'opacity .3s';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        }, 2500);
    }

    // 공용 모달
    var modal = document.getElementById('crConfirmModal');
    var modalIcon = document.getElementById('crModalIcon');
    var modalTitle = document.getElementById('crModalTitle');
    var modalDesc = document.getElementById('crModalDesc');
    var modalReasonWrap = document.getElementById('crModalReasonWrap');
    var modalReasonInput = document.getElementById('crModalReasonInput');
    var modalConfirmBtn = document.getElementById('crModalConfirmBtn');

    function openModal(type, id) {
        modalState.type = type;
        modalState.targetId = id;

        if (type === 'approve') {
            modalIcon.innerHTML = '<div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center"><i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i></div>';
            modalTitle.textContent = '변경요청 승인';
            modalDesc.textContent = '이 변경요청을 승인하시겠습니까? 승인 시 즉시 직원 정보에 반영됩니다.';
            modalReasonWrap.classList.add('hidden');
            modalConfirmBtn.className = 'btn btn-primary bg-emerald-600 hover:bg-emerald-700';
            modalConfirmBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i>승인';
        } else {
            modalIcon.innerHTML = '<div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center"><i data-lucide="x-circle" class="w-5 h-5 text-rose-400"></i></div>';
            modalTitle.textContent = '변경요청 반려';
            modalDesc.textContent = '이 변경요청을 반려합니다. 반려 사유를 입력해주세요.';
            modalReasonWrap.classList.remove('hidden');
            modalReasonInput.value = '';
            modalConfirmBtn.className = 'btn btn-primary bg-rose-600 hover:bg-rose-700';
            modalConfirmBtn.innerHTML = '<i data-lucide="x-circle" class="w-4 h-4"></i>반려';
        }

        modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
        if (type === 'reject') setTimeout(function() { modalReasonInput.focus(); }, 100);
    }

    function closeModal() { modal.classList.add('hidden'); }

    function formatChanges(changesJson) {
        var changes = [];
        try { changes = Object.entries(JSON.parse(changesJson)); } catch(e) {}
        return changes.map(function(pair) {
            var f = pair[0], v = pair[1];
            var label = FIELD_LABELS[f] || f;
            var oldV = v.old || '-';
            var newV = v.new || '-';
            if (f === 'gender') {
                oldV = GENDER_LABELS[oldV] || oldV || '-';
                newV = GENDER_LABELS[newV] || newV || '-';
            }
            return '<span class="text-slate-500">' + escHtml(label) + ':</span> ' + escHtml(oldV) + ' → <span class="text-slate-100">' + escHtml(newV) + '</span>';
        }).join('<br>');
    }

    function load() {
        var tbody = document.getElementById('crTableBody');
        var emptyEl = document.getElementById('crEmptyRow');
        tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">불러오는 중...</td></tr>';
        emptyEl.classList.add('hidden');

        var url = API + '?action=getPendingRequests&status=' + encodeURIComponent(currentStatus);
        fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-8 text-center text-rose-400">' + escHtml((res.error && res.error.message) || '조회 실패') + '</td></tr>';
                return;
            }
            var items = res.data.items || [];
            var counts = res.data.counts || {};

            var pending = counts['대기'] || 0;
            var badgeEl = document.getElementById('cntPending');
            badgeEl.textContent = pending;
            badgeEl.setAttribute('data-count', pending);

            if (items.length === 0) {
                tbody.innerHTML = '';
                emptyEl.classList.remove('hidden');
                return;
            }

            emptyEl.classList.add('hidden');
            var html = '';
            items.forEach(function(item) {
                var statusCls = item.status === '대기' ? 'zm-pill-warn'
                              : item.status === '승인' ? 'zm-pill-ok'
                              : 'zm-pill-danger';

                html += '<tr class="border-t border-slate-800 hover:bg-slate-800/30">';
                html += '<td class="px-5 py-4">';
                html += '<p class="font-medium text-slate-100">' + escHtml(item.employee_name) + '</p>';
                if (item.employee_no) html += '<p class="text-xs text-slate-500">' + escHtml(item.employee_no) + '</p>';
                html += '</td>';
                html += '<td class="px-5 py-4 text-slate-300">' + escHtml(item.department_name || '-') + '</td>';
                html += '<td class="px-5 py-4 text-sm">' + formatChanges(item.changes_json);
                if (item.reason) html += '<p class="text-xs text-slate-500 mt-1">사유: ' + escHtml(item.reason) + '</p>';
                if (item.reject_reason) html += '<p class="text-xs text-rose-400 mt-1">반려 사유: ' + escHtml(item.reject_reason) + '</p>';
                html += '</td>';
                html += '<td class="px-5 py-4 text-slate-400 text-xs whitespace-nowrap">' + escHtml(item.created_at) + '</td>';
                html += '<td class="px-5 py-4"><span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full ' + statusCls + '">' + escHtml(item.status) + '</span></td>';
                html += '<td class="px-5 py-4 text-center whitespace-nowrap">';
                if (item.status === '대기') {
                    html += '<button class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white mr-1" onclick="window.__crApprove(' + item.id + ')">승인</button>';
                    html += '<button class="btn btn-sm bg-rose-600 hover:bg-rose-700 text-white" onclick="window.__crReject(' + item.id + ')">반려</button>';
                } else if (item.reviewed_by_name) {
                    html += '<span class="text-xs text-slate-500">' + escHtml(item.reviewed_by_name) + '</span>';
                }
                html += '</td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
        })
        .catch(function() {
            tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-8 text-center text-rose-400">서버 연결 실패</td></tr>';
        });
    }

    // 탭 전환
    document.getElementById('statusTabs').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-status]');
        if (!btn) return;
        document.querySelectorAll('.cr-tab').forEach(function(t) { t.classList.remove('cr-tab-active'); });
        btn.classList.add('cr-tab-active');
        currentStatus = btn.getAttribute('data-status');
        load();
    });

    // 승인/반려 버튼 진입점
    window.__crApprove = function(id) { openModal('approve', id); };
    window.__crReject = function(id) { openModal('reject', id); };

    // 모달 닫기
    document.getElementById('crModalOverlay').addEventListener('click', closeModal);
    document.getElementById('crModalCancelBtn').addEventListener('click', closeModal);

    // 모달 확인 (승인/반려 공용)
    modalConfirmBtn.addEventListener('click', function() {
        var id = modalState.targetId;
        var isApprove = modalState.type === 'approve';
        var body = { id: id, decision: isApprove ? '승인' : '반려' };

        if (!isApprove) {
            var reason = modalReasonInput.value.trim();
            if (!reason) { toast('반려 사유를 입력해주세요.', 'error'); return; }
            body.reject_reason = reason;
        }

        modalConfirmBtn.disabled = true;
        fetch(API + '?action=reviewRequest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            modalConfirmBtn.disabled = false;
            if (!res.ok) {
                var msg = (res.error && res.error.message) || (typeof res.error === 'string' ? res.error : '처리 실패');
                toast(msg, 'error');
                return;
            }
            toast(isApprove ? '승인 완료 · 직원 정보가 갱신되었습니다.' : '반려 처리되었습니다.');
            closeModal();
            load();
        })
        .catch(function() {
            modalConfirmBtn.disabled = false;
            toast('서버 연결 실패', 'error');
        });
    });

    load();
    if (window.lucide) lucide.createIcons();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
