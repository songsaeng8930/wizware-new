<?php
$pageTitle = '결재문서 상세';
$currentPage = 'approval';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$docId = (int)($_GET['id'] ?? 0);

if ($pdo) {
    try { $pdo->query('SELECT 1 FROM approval_documents LIMIT 1'); $hasDB = true; }
    catch (PDOException $e) { $hasDB = false; }
}

// 대결 위임자 ID (결재 버튼 노출용)
$delegateForIds = [];
if ($pdo && $hasDB) {
    try {
        require_once __DIR__ . '/../includes/approval_delegate_helper.php';
        $currentUser = getCurrentUser();
        $myId = (int)($currentUser['id'] ?? 0);
        if ($myId > 0) {
            expireDelegations($pdo);
            $delegateForIds = getDelegatorIdsFor($pdo, $myId);
        }
    } catch (\Throwable $e) { error_log('[approval_view] delegate lookup: ' . $e->getMessage()); }
}

// DB 없을 때 폴백용 샘플
$sampleDoc = null;
if (!$hasDB) {
    $samples = [
        1 => ['id'=>1,'doc_number'=>'Zaemit_개발1_휴가_20260320100000','title'=>'연차 사용 신청 (3/28~3/29)','drafter_dept'=>'개발1팀','doc_type'=>'휴가신청서','drafter_name'=>'박프론트','status'=>'대기','draft_date'=>'2026-03-20','content'=>'<p>연차 사용 신청 (3/28~3/29) 내용입니다.</p>',
         'history'=>[['approver_name'=>'강개발','approver_dept'=>'개발1팀','step_order'=>1,'action'=>'대기','comment'=>null,'action_date'=>null],['approver_name'=>'박기술','approver_dept'=>'기술개발본부','step_order'=>2,'action'=>'대기','comment'=>null,'action_date'=>null]]],
    ];
    $sampleDoc = $samples[$docId] ?? null;
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950 max-w-[1100px]">
        <!-- 헤더 -->
        <div class="flex items-center gap-3 mb-6">
            <button onclick="history.back()" class="p-1.5 rounded-lg hover:bg-slate-700 transition-colors" title="목록으로">
                <i data-lucide="arrow-left" class="w-5 h-5 text-slate-300"></i>
            </button>
            <h2 class="text-lg font-bold text-slate-100" id="pageTitle">결재문서 상세</h2>
            <span id="statusBadge" class="px-2.5 py-0.5 text-sm font-semibold rounded-full"></span>
        </div>

        <!-- 로딩 / 에러 -->
        <div id="loadingState" class="bg-slate-900 rounded-xl border border-slate-800 p-16 text-center text-slate-500">
            <i data-lucide="loader-2" class="w-6 h-6 mx-auto mb-2 animate-spin"></i> 문서를 불러오는 중...
        </div>
        <div id="errorState" class="bg-slate-900 rounded-xl border border-slate-800 p-16 text-center text-slate-400 hidden">
            <i data-lucide="alert-circle" class="w-6 h-6 mx-auto mb-2"></i> <span id="errorMsg">문서를 찾을 수 없습니다.</span>
        </div>

        <!-- 본문 (JS로 표시) -->
        <div id="docContent" class="hidden">
            <!-- 문서정보 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                    <span class="form-section-title">문서정보</span>
                </div>
                <table class="form-table">
                    <tr>
                        <th class="form-th w-[120px]">문서종류</th>
                        <td class="form-td" id="infoDocType"></td>
                        <th class="form-th w-[120px]">기안자</th>
                        <td class="form-td" id="infoDrafter"></td>
                    </tr>
                    <tr>
                        <th class="form-th">기안부서</th>
                        <td class="form-td" id="infoDept"></td>
                        <th class="form-th">기안일</th>
                        <td class="form-td" id="infoDraftDate"></td>
                    </tr>
                    <tr>
                        <th class="form-th">문서번호</th>
                        <td class="form-td" id="infoDocNum" colspan="3"></td>
                    </tr>
                </table>
            </div>

            <!-- 결재선 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                    <span class="form-section-title">결재선</span>
                </div>
                <div class="p-5">
                    <div id="approvalFlow"></div>
                </div>
            </div>

            <!-- 규정 준수 정보 (경비청구서만 표시) -->
            <div id="regulationSection" class="mb-5 hidden"></div>

            <!-- 문서내용 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                    <span class="form-section-title">결재내용</span>
                </div>
                <div class="p-5">
                    <h2 id="docTitle" class="text-base font-bold text-slate-100 mb-4"></h2>
                    <div id="docBody" class="text-sm text-slate-200 leading-relaxed"></div>
                </div>
            </div>

            <!-- 반려 사유 -->
            <div id="rejectSection" class="rounded-xl mb-5 overflow-hidden hidden" style="border:1px solid var(--zm-st-danger-bd); background:var(--zm-st-danger-bg)">
                <div class="px-5 py-3" style="border-bottom:1px solid var(--zm-st-danger-bd)">
                    <span class="form-section-title" style="color:var(--zm-st-danger-fg)">반려 사유</span>
                </div>
                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0" style="background:var(--zm-st-danger-bg);color:var(--zm-st-danger-fg);border:1px solid var(--zm-st-danger-bd)" id="rejectAvatar"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-slate-100" id="rejectName"></span>
                                <span class="text-sm text-slate-500" id="rejectDept"></span>
                                <span class="text-sm text-slate-500" id="rejectDate"></span>
                            </div>
                            <p class="text-sm text-amber-700 leading-relaxed" id="rejectReason"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 결재의견 -->
            <div id="commentsSection" class="bg-slate-900 rounded-xl border border-slate-800 mb-6 overflow-hidden hidden">
                <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                    <span class="form-section-title">결재의견</span>
                </div>
                <div id="commentsList" class="p-5 space-y-4"></div>
            </div>

            <!-- 하단 버튼 -->
            <div class="flex items-center justify-end gap-2">
                <button onclick="history.back()" class="btn btn-secondary">목록으로</button>
                <button id="btnReject" onclick="rejectDoc()" class="hidden px-5 py-2.5 text-sm font-medium text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-50 transition-colors">반려</button>
                <button id="btnApprove" onclick="approveDoc()" class="hidden px-5 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition-colors">승인</button>
                <button id="btnCoopComment" onclick="coopComment()" class="hidden btn btn-secondary">의견 남기기</button>
                <button id="btnCoopAgree" onclick="coopAgree()" class="hidden px-5 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">합의</button>
            </div>
        </div>
    </main>
</div>

<!-- 결재 UI 스타일은 custom.css .apv-* 클래스로 통합됨 -->

<script>
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const DOC_ID = <?= $docId ?>;
const CURRENT_USER_ID = <?= (int)($_SESSION['user']['id'] ?? 0) ?>;
window.DELEGATE_FOR_IDS = <?= json_encode($delegateForIds) ?>;
const CURRENT_USER_NAME = '<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name'] ?? '', ENT_QUOTES) : '' ?>';
const SAMPLE_DOC = <?= $sampleDoc ? json_encode($sampleDoc, JSON_UNESCAPED_UNICODE) : 'null' ?>;

const statusMap = {
    '대기': { text: '결재대기', class: 'badge-warning' },
    '진행': { text: '결재진행', class: 'badge-info' },
    '승인': { text: '승인완료', class: 'badge-success' },
    '결재완료': { text: '결재완료', class: 'badge-success' },
    '반려': { text: '반려', class: 'badge-danger' },
    '임시저장': { text: '임시저장', class: 'badge-neutral' },
};

// role은 DB에서 직접 가져옴 (결재/전결)

document.addEventListener('DOMContentLoaded', () => {
    if (!DOC_ID) { showError('유효하지 않은 문서 ID입니다.'); return; }
    if (HAS_DB) loadDocument();
    else if (SAMPLE_DOC) renderDocument(SAMPLE_DOC);
    else showError('문서를 찾을 수 없습니다.');
});

function loadDocument() {
    fetch(`${API_BASE}?action=getDocument&id=${DOC_ID}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) { showError(data.error); return; }
            renderDocument(data.document);
        })
        .catch(() => showError('문서를 불러오는 데 실패했습니다.'));
}

function renderDocument(doc) {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('docContent').classList.remove('hidden');

    // 상태 배지
    const si = statusMap[doc.status] || statusMap['대기'];
    const badge = document.getElementById('statusBadge');
    badge.textContent = si.text;
    badge.className = `px-2.5 py-0.5 text-sm font-semibold rounded-full ${si.class}`;

    // 문서정보
    document.getElementById('infoDocType').textContent = doc.doc_type || '-';
    document.getElementById('infoDrafter').textContent = doc.drafter_name || '-';
    document.getElementById('infoDept').textContent = doc.drafter_dept || '-';
    document.getElementById('infoDraftDate').textContent = (doc.draft_date || '').replace(/-/g, '.');
    document.getElementById('infoDocNum').textContent = doc.doc_number || '-';

    // 규정 준수 정보 (경비청구서)
    renderRegulationInfo(doc);

    // 문서내용
    document.getElementById('docTitle').textContent = doc.title || '';
    document.getElementById('docBody').innerHTML = doc.content || '<p class="text-slate-500">내용이 없습니다.</p>';

    // 결재선 + 참조자
    renderApprovalFlow(doc.history || [], doc.references || []);

    // 반려 사유
    if (doc.status === '반려') {
        const rejected = (doc.history || []).find(h => h.action === '반려');
        if (rejected && rejected.comment) {
            document.getElementById('rejectSection').classList.remove('hidden');
            document.getElementById('rejectAvatar').textContent = (rejected.approver_name || '?').charAt(0);
            document.getElementById('rejectName').textContent = rejected.approver_name || '';
            document.getElementById('rejectDept').textContent = rejected.approver_dept || '';
            document.getElementById('rejectDate').textContent = rejected.action_date ? rejected.action_date.substring(0, 10).replace(/-/g, '.') : '';
            document.getElementById('rejectReason').textContent = rejected.comment;
        }
    }

    // 결재의견
    renderComments(doc.history || []);

    // 버튼 노출: 현재 결재 차례가 로그인 사용자이거나 대결자일 때, 역할에 따라 다른 버튼
    const currentStep = (doc.history || []).find(h => h.action === '대기');
    const isOriginalApprover = currentStep && currentStep.approver_id == CURRENT_USER_ID;
    const isDelegateForStep = currentStep && (window.DELEGATE_FOR_IDS || []).includes(currentStep.approver_id);
    const isMyTurn = (isOriginalApprover || isDelegateForStep) && ['대기','진행'].includes(doc.status);
    if (isMyTurn) {
        const role = currentStep.role || '결재';
        if (role === '협조') {
            document.getElementById('btnCoopAgree').classList.remove('hidden');
            document.getElementById('btnCoopComment').classList.remove('hidden');
        } else {
            document.getElementById('btnApprove').classList.remove('hidden');
            document.getElementById('btnReject').classList.remove('hidden');
        }
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// 승인 처리 (Phase 1 워크플로우 통합 훅 트리거)
async function approveDoc() {
    if (!(await AppUI.confirm('이 문서를 승인하시겠습니까?\n\n최종 승인 시 연동된 원본 데이터에도 자동으로 반영됩니다.'))) return;
    fetch(`${API_BASE}?action=approveDocument`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_id: DOC_ID, action: '승인', comment: '' }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) { alert(data.error || '처리에 실패했습니다.'); return; }
        alert(data.message || '승인되었습니다.');
        location.reload();
    })
    .catch(() => alert('네트워크 오류가 발생했습니다.'));
}

// 반려 처리
async function rejectDoc() {
    const reason = await AppUI.prompt('반려 사유를 입력하세요:');
    if (reason === null) return;
    if (!reason.trim()) { alert('반려 사유는 필수입니다.'); return; }
    fetch(`${API_BASE}?action=approveDocument`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_id: DOC_ID, action: '반려', comment: reason.trim() }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) { alert(data.error || '처리에 실패했습니다.'); return; }
        alert(data.message || '반려되었습니다.');
        location.reload();
    })
    .catch(() => alert('네트워크 오류가 발생했습니다.'));
}

// 협조 · 합의 처리 (반려 권한 없이 동의만)
async function coopAgree() {
    if (!(await AppUI.confirm('이 문서에 합의하시겠습니까?'))) return;
    fetch(`${API_BASE}?action=approveDocument`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_id: DOC_ID, action: '합의', comment: '' }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) { alert(data.error || '처리에 실패했습니다.'); return; }
        alert(data.message || '합의되었습니다.');
        location.reload();
    })
    .catch(() => alert('네트워크 오류가 발생했습니다.'));
}

// 협조 · 의견 남기기 (코멘트 남기고 흐름은 계속 진행)
async function coopComment() {
    const comment = await AppUI.prompt('의견을 남겨주세요 (흐름은 계속 진행됩니다):');
    if (comment === null) return;
    fetch(`${API_BASE}?action=approveDocument`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_id: DOC_ID, action: '의견', comment: (comment || '').trim() }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) { alert(data.error || '처리에 실패했습니다.'); return; }
        alert(data.message || '의견이 등록되었습니다.');
        location.reload();
    })
    .catch(() => alert('네트워크 오류가 발생했습니다.'));
}

function renderApprovalFlow(history, references) {
    const container = document.getElementById('approvalFlow');
    container.innerHTML = ApvUI.renderFlow(history, { references: references });
}

function renderComments(history) {
    const comments = history.filter(h => h.comment && h.comment.trim());
    if (!comments.length) return;

    document.getElementById('commentsSection').classList.remove('hidden');
    const container = document.getElementById('commentsList');
    const colors = ['#64748b', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];

    container.innerHTML = comments.map((c, i) => {
        const bgColor = colors[i % colors.length];
        const initial = (c.approver_name || '?').charAt(0);
        const actionBadge = c.action === '승인'
            ? '<span class="text-sm text-amber-700 font-medium">승인</span>'
            : c.action === '반려'
            ? '<span class="text-sm text-amber-500 font-medium">반려</span>'
            : c.action === '합의'
            ? '<span class="text-sm text-emerald-400 font-medium">합의</span>'
            : c.action === '의견'
            ? '<span class="text-sm text-slate-400 font-medium">의견</span>'
            : '';
        const dateStr = c.action_date ? c.action_date.substring(0, 16).replace('T', ' ') : '';

        return `
        <div class="apv-comment">
            <div class="apv-comment__avatar" style="background:${bgColor}">${esc(initial)}</div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-semibold text-slate-100">${esc(c.approver_name)}</span>
                    <span class="text-sm text-slate-500">${esc(c.approver_dept || '')}</span>
                    ${actionBadge}
                </div>
                <p class="text-sm text-slate-200">${esc(c.comment)}</p>
                <span class="text-sm text-slate-500 mt-1 block">${dateStr}</span>
            </div>
        </div>`;
    }).join('');
}

function showError(msg) {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('errorMsg').textContent = msg;
    document.getElementById('errorState').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderRegulationInfo(doc) {
    const section = document.getElementById('regulationSection');
    if (!doc.metadata) return;
    let meta;
    try { meta = typeof doc.metadata === 'string' ? JSON.parse(doc.metadata) : doc.metadata; }
    catch { return; }
    if (meta.source !== 'card_expense') return;

    const status = meta.compliance_status;
    if (!status) return;

    section.classList.remove('hidden');

    if (status === '예외신청') {
        const limit = meta.regulation_limit || 0;
        const reason = meta.exception_reason || '';
        section.innerHTML = `
        <div class="rounded-xl overflow-hidden" style="border:1px solid var(--zm-st-warning-bd); background:var(--zm-st-warning-bg)">
            <div class="px-5 py-3 flex items-center gap-2" style="border-bottom:1px solid var(--zm-st-warning-bd)">
                <i data-lucide="alert-triangle" class="w-4 h-4" style="color:var(--zm-st-warning-fg)"></i>
                <span class="form-section-title" style="color:var(--zm-st-warning-fg)">규정 한도 초과</span>
            </div>
            <div class="px-5 py-4">
                <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm mb-3">
                    <div><span class="text-slate-400">규정 한도:</span> <span class="font-semibold" style="color:var(--zm-st-warning-fg)">${limit ? limit.toLocaleString() + '원' : '-'}</span></div>
                    <div><span class="text-slate-400">초과 상태:</span> <span class="badge badge-warning">예외신청</span></div>
                </div>
                ${reason ? `<div class="text-sm"><span class="text-slate-400">예외 사유:</span> <span class="text-slate-200 ml-1">${esc(reason)}</span></div>` : ''}
            </div>
        </div>`;
    } else if (status === '준수') {
        section.innerHTML = `
        <div class="flex items-center gap-2 px-4 py-2.5 rounded-lg" style="background:var(--zm-st-success-bg); border:1px solid var(--zm-st-success-bd)">
            <i data-lucide="check-circle-2" class="w-4 h-4" style="color:var(--zm-st-success-fg)"></i>
            <span class="text-sm font-medium" style="color:var(--zm-st-success-fg)">카드 사용 규정 준수</span>
        </div>`;
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

const esc = ApvUI.esc;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
