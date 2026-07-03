<?php
$pageTitle = '결재양식 등록';
$currentPage = 'approval_admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_form_templates.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('approval_admin', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = [];

if ($pdo) {
    try { $pdo->query('SELECT 1 FROM approval_forms LIMIT 1'); $hasDB = true; }
    catch (PDOException $e) { $hasDB = false; }
}

if ($hasDB && $formId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT id, doc_type, title, content_template, is_active, description, allowed_departments, allowed_positions, retention_days FROM approval_forms WHERE id = ?');
        $stmt->execute([$formId]);
        $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) { $editing = []; }
}

$departments = [];
if ($pdo) {
    try {
        $departments = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$isEdit = !empty($editing);
$pageLabel = $isEdit ? '결재양식 수정' : '결재양식 등록';
$formTemplates = approval_form_templates();
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950 approval-form-register-page">

        <!-- 페이지 헤더 -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <a href="approval_forms.php" class="p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="목록으로">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-slate-300"></i>
                </a>
                <h2 class="text-lg font-bold text-slate-100"><?= htmlspecialchars($pageLabel) ?></h2>
                <?php if ($isEdit): ?>
                <span class="text-sm text-slate-500">· ID <?= (int)$editing['id'] ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <a href="approval_forms.php" class="btn btn-secondary">취소</a>
                <button type="button" onclick="saveForm()" class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors">
                    <i data-lucide="check" class="w-4 h-4"></i> 저장
                </button>
            </div>
        </div>

        <!-- 2단 레이아웃: 템플릿 + 편집 -->
        <div class="grid grid-cols-[280px_1fr] gap-5">

            <!-- 좌측: 기본 템플릿 / 외부 양식 가져오기 -->
            <aside class="bg-slate-900 rounded-xl border border-slate-800 p-4 self-start sticky top-20 approval-template-sidebar h-[calc(100vh-10.5rem)] overflow-hidden flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="layout-template" class="w-5 h-5 text-primary"></i>
                    <h3 class="text-base font-semibold text-slate-100">실무 템플릿</h3>
                </div>
                <p class="text-xs text-slate-500 mb-3">템플릿 선택 또는 파일 업로드</p>

                <div class="mb-3 rounded-xl border border-dashed border-slate-700 bg-slate-950/60 p-2.5 approval-import-box shrink-0">
                    <input type="file" id="formImportFile" class="hidden" accept=".html,.htm,.txt,.md,.csv,.tsv,.docx" onchange="importExternalTemplate(this)">
                    <button type="button" onclick="document.getElementById('formImportFile').click()" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg border border-primary/30 text-primary hover:bg-gray-100 transition-colors">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i> 외부 양식 업로드
                    </button>
                    <p class="mt-1.5 text-xs text-slate-500 leading-snug">HTML/TXT/MD/CSV/DOCX 지원</p>
                    <p id="importStatus" class="mt-1.5 text-xs text-slate-500"></p>
                </div>

                <div class="flex items-center justify-between mb-2 shrink-0">
                    <span class="text-xs font-semibold text-slate-500">기본 제공 <?= count(approval_form_seed_templates()) ?>종</span>
                </div>
                <ul id="templateList" class="space-y-1.5 overflow-y-auto pr-1 flex-1 min-h-0"></ul>
            </aside>

            <!-- 우측: 입력 영역 -->
            <section class="h-[calc(100vh-10.5rem)] flex flex-col gap-5 min-h-0">

                <!-- 기본 정보 카드 -->
                <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                        <span class="text-base font-semibold text-slate-100">기본 정보</span>
                    </div>
                    <div class="p-6 grid grid-cols-3 gap-5">
                        <div>
                            <label class="reg-label">문서종류 <span class="text-amber-500">*</span></label>
                            <input type="text" id="formDocType" class="reg-input" placeholder="예: 품의서" value="<?= htmlspecialchars($editing['doc_type'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label class="reg-label">제목</label>
                            <input type="text" id="formTitle" class="reg-input" placeholder="양식 제목 (미입력시 문서종류와 동일)" value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="reg-label">보존기간 (일)</label>
                            <input type="number" id="formRetention" class="reg-input" placeholder="미입력시 무제한" min="1" value="<?= htmlspecialchars($editing['retention_days'] ?? '') ?>">
                        </div>
                        <div class="col-span-3">
                            <label class="reg-label">양식 설명</label>
                            <input type="text" id="formDescription" class="reg-input" placeholder="이 양식의 용도를 설명해 주세요" value="<?= htmlspecialchars($editing['description'] ?? '') ?>">
                        </div>
                        <div class="col-span-3">
                            <label class="reg-label">사용 가능 부서</label>
                            <p class="text-xs text-slate-500 mb-2">선택하지 않으면 전체 부서에서 사용 가능합니다.</p>
                            <div id="deptCheckboxes" class="flex flex-wrap gap-2">
                                <?php
                                $allowedDepts = [];
                                if (!empty($editing['allowed_departments'])) {
                                    $decoded = json_decode($editing['allowed_departments'], true);
                                    if (is_array($decoded)) $allowedDepts = $decoded;
                                }
                                foreach ($departments as $dept):
                                    $checked = in_array($dept['id'], $allowedDepts) ? 'checked' : '';
                                ?>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-950/60 text-sm text-slate-300 cursor-pointer hover:border-gray-400 transition-colors has-[:checked]:border-primary/60 has-[:checked]:bg-primary/10 has-[:checked]:text-primary">
                                    <input type="checkbox" name="allowed_dept" value="<?= (int)$dept['id'] ?>" class="sr-only" <?= $checked ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 양식 본문 카드 -->
                <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden flex-1 min-h-0 flex flex-col">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800 bg-slate-950 shrink-0">
                        <span class="text-base font-semibold text-slate-100">양식 본문</span>
                        <span class="text-sm text-slate-500">기안 작성 시 이 내용이 자동으로 불러와집니다</span>
                    </div>

                    <!-- 에디터 (자체 TFEditor · 표·정렬 지원) -->
                    <div class="p-5 flex-1 min-h-0">
                        <div id="formEditorMount" class="h-full"></div>
                        <textarea id="formContentInitial" class="hidden"><?= htmlspecialchars($editing['content_template'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

            </section>
        </div>
    </main>
</div>

<link rel="stylesheet" href="<?= $basePath ?>/assets/editor/editor.css">
<script src="<?= $basePath ?>/assets/editor/editor.js"></script>
<style>
/* 사용 가능 부서 칩 선택 상태 (CDN Tailwind가 has-[:checked] 변형을 컴파일하지 않는 경우 대비) */
#deptCheckboxes label.is-selected {
    border-color: var(--zm-primary);
    background: var(--zm-primary-tint-12);
    color: var(--zm-primary);
}
/* 템플릿 카드 / 외부 업로드 */
.approval-template-sidebar { box-shadow: var(--zm-card-shadow); }
.approval-import-box { transition: border-color .15s, background-color .15s; }
.approval-import-box:hover { border-color: rgba(0,0,0,0.12); background: rgba(0,0,0,0.04); }
.tpl-item { display:flex; align-items:flex-start; gap:12px; padding:12px 12px; border-radius:12px; border:1px solid transparent; cursor:pointer; color:var(--zm-text-default); transition:background-color .15s, color .15s, border-color .15s; font-size:14px; font-weight:600; }
.tpl-item:hover { background-color:var(--zm-surface-2); color:var(--zm-text-strong); border-color:transparent; }
.tpl-item.active { background-color:rgba(79,106,255,0.18); border-color:transparent; color:var(--zm-primary-fg); }
.tpl-item.active:hover { background-color:rgba(0,0,0,0.04); border-color:transparent; color:var(--zm-text-strong); }
.tpl-item .tpl-ico { width:34px; height:34px; display:flex; align-items:center; justify-content:center; border-radius:10px; background-color:var(--zm-surface-2); color:var(--zm-text-muted); flex-shrink:0; }
.tpl-item.active .tpl-ico { background-color:rgba(79,106,255,0.24); color:var(--zm-primary-fg); }
.tpl-item.active .tpl-category { background:rgba(79,106,255,0.24); color:var(--zm-primary-fg); }
.tpl-item.active .tpl-desc { color:var(--zm-primary-fg); opacity:.76; }
.tpl-meta { min-width:0; flex:1; }
.tpl-name { display:flex; align-items:center; gap:6px; min-width:0; }
.tpl-category { display:inline-flex; align-items:center; padding:1px 6px; border-radius:999px; background:var(--zm-surface-2); color:var(--zm-text-muted); font-size:10px; font-weight:700; white-space:nowrap; }
.tpl-desc { margin-top:3px; color:var(--zm-text-muted); font-size:12px; font-weight:500; line-height:1.35; }
.approval-form-register-page #formEditorMount .tf-editor-container {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.approval-form-register-page #formEditorMount .tf-editor-content {
    flex: 1 1 auto;
    min-height: 0;
    height: auto;
    max-height: none;
}
html[data-theme="light"] .approval-template-sidebar,
html[data-theme="light"] .approval-template-sidebar + section .bg-slate-900 { background:#fff !important; border-color:#e5e7eb !important; }
html[data-theme="light"] .approval-template-sidebar + section .bg-slate-950 { background:#f8fafc !important; border-color:#e5e7eb !important; }
html[data-theme="light"] .tf-editor-container { --tf-bg-1:#ffffff; --tf-bg-2:#f8fafc; --tf-bg-3:#eef2ff; --tf-border:#dbe3ef; --tf-text-1:#111827; --tf-text-2:#374151; --tf-text-3:#94a3b8; }
html[data-theme="light"] .tf-editor-content { background:#ffffff; color:#111827; }
html[data-theme="light"] .tf-editor-content tr,
html[data-theme="light"] .tf-editor-content th:not(.tf-cell-selected),
html[data-theme="light"] .tf-editor-content td:not(.tf-cell-selected) { background:transparent !important; background-color:transparent !important; }
html[data-theme="light"] .tf-editor-content th { color:#374151; border-color:#d1d5db; }
html[data-theme="light"] .tf-editor-content td { color:#111827; border-color:#d1d5db; }
html[data-theme="light"] .tf-editor-content th.tf-cell-selected,
html[data-theme="light"] .tf-editor-content td.tf-cell-selected { color:#1d3fce !important; }
</style>

<script>
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const FORM_ID = <?= (int)($editing['id'] ?? 0) ?>;
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ── 기본 양식 템플릿 ────────────────────────────────────────
const FORM_TEMPLATES = <?= json_encode($formTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function renderTemplateList() {
    const ul = document.getElementById('templateList');
    ul.innerHTML = FORM_TEMPLATES.map(t => `
        <li class="tpl-item" data-tpl-id="${t.id}" onclick="applyTemplate('${t.id}')" title="${esc(t.desc || '')}">
            <span class="tpl-ico"><i data-lucide="${t.icon}" class="w-4 h-4"></i></span>
            <span class="tpl-meta">
                <span class="tpl-name"><span class="truncate">${esc(t.name)}</span>${t.category ? `<span class="tpl-category">${esc(t.category)}</span>` : ''}</span>
                ${t.desc ? `<span class="tpl-desc">${esc(t.desc)}</span>` : ''}
            </span>
        </li>
    `).join('');
    if (window.lucide) lucide.createIcons();
}

/* TFEditor 인스턴스 */
let __formEditor = null;
let __formDirty = false;

function markFormDirty() {
    __formDirty = true;
}

function resetFormDirty() {
    __formDirty = false;
}

function initFormEditor() {
    const initial = document.getElementById('formContentInitial')?.value || '';
    __formEditor = new TFEditor({
        container: '#formEditorMount',
        initialContent: initial,
        placeholder: '양식 본문을 입력하세요...',
        showHint: false,
        showVariableButton: false,
        onChange: markFormDirty,
    });

    document.getElementById('formDocType')?.addEventListener('input', markFormDirty);
    document.getElementById('formTitle')?.addEventListener('input', markFormDirty);
}

async function applyTemplate(id) {
    const tpl = FORM_TEMPLATES.find(t => t.id === id);
    if (!tpl) return;
    const docTypeEl = document.getElementById('formDocType');
    const titleEl = document.getElementById('formTitle');

    if (__formDirty) {
        const msg = tpl.id === 'blank' ? '입력한 내용을 모두 비울까요?' : '입력한 내용을 템플릿으로 덮어쓸까요?';
        if (!(await AppUI.confirm(msg))) return;
    }

    if (__formEditor) __formEditor.setContent(tpl.html || '');
    if (tpl.doc_type) docTypeEl.value = tpl.doc_type;
    if (tpl.title)    titleEl.value = tpl.title;
    if (tpl.id === 'blank' && !IS_EDIT) {
        docTypeEl.value = '';
        titleEl.value = '';
    }

    document.querySelectorAll('#templateList .tpl-item').forEach(el => {
        el.classList.toggle('active', el.dataset.tplId === id);
    });
    resetFormDirty();
}

function setImportStatus(message, tone = 'muted') {
    const el = document.getElementById('importStatus');
    if (!el) return;
    el.textContent = message || '';
    el.className = 'mt-1.5 text-xs ' + (tone === 'ok' ? 'text-emerald-600' : (tone === 'error' ? 'text-red-600' : 'text-slate-500'));
}

async function importExternalTemplate(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    if (__formDirty && !(await AppUI.confirm('현재 입력한 내용을 업로드한 양식으로 덮어쓸까요?'))) {
        input.value = '';
        return;
    }

    const fd = new FormData();
    fd.append('template_file', file);
    fd.append('_csrf', CSRF_TOKEN);
    setImportStatus('업로드한 양식을 읽는 중입니다...');

    fetch(`${API_BASE}?action=importFormTemplate`, { method: 'POST', body: fd })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok || data.error) throw new Error(data.error || '양식을 읽지 못했습니다.');
            return data;
        })
        .then(data => {
            if (__formEditor) __formEditor.setContent(data.html || '');
            const docType = (data.doc_type || '').trim();
            const title = (data.title || '').trim();
            if (docType) document.getElementById('formDocType').value = docType;
            if (title) document.getElementById('formTitle').value = title;
            document.querySelectorAll('#templateList .tpl-item').forEach(el => el.classList.remove('active'));
            resetFormDirty();
            setImportStatus(`${file.name} 양식을 불러왔습니다. 저장하면 결재양식으로 등록됩니다.`, 'ok');
        })
        .catch(err => {
            console.error(err);
            setImportStatus(err.message || '업로드 실패', 'error');
        })
        .finally(() => { input.value = ''; });
}

function saveForm() {
    const docType = document.getElementById('formDocType').value.trim();
    if (!docType) {
        alert('문서종류를 입력해주세요.');
        document.getElementById('formDocType').focus();
        return;
    }
    const allowedDepts = [...document.querySelectorAll('#deptCheckboxes input[name="allowed_dept"]:checked')]
        .map(cb => parseInt(cb.value));
    const retentionVal = document.getElementById('formRetention').value.trim();

    const data = {
        id: FORM_ID,
        doc_type: docType,
        title: document.getElementById('formTitle').value.trim(),
        is_active: <?= $isEdit ? (int)($editing['is_active'] ?? 1) : 1 ?>,
        content_template: __formEditor ? __formEditor.getContent() : '',
        description: document.getElementById('formDescription').value.trim() || null,
        allowed_departments: allowedDepts.length ? allowedDepts : null,
        retention_days: retentionVal ? parseInt(retentionVal) : null,
    };

    if (!HAS_DB) {
        alert('저장되었습니다. (샘플 모드)');
        location.href = 'approval_forms.php?saved=1';
        return;
    }

    fetch(`${API_BASE}?action=saveForm`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) { alert(res.error); return; }
        location.href = 'approval_forms.php?saved=1';
    })
    .catch(err => { alert('저장 중 오류가 발생했습니다.'); console.error(err); });
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

document.addEventListener('DOMContentLoaded', () => {
    renderTemplateList();
    initFormEditor();

    // 사용 가능 부서 칩: 체크 상태를 라벨 색으로 표시 (초기값 + 클릭 토글)
    const deptWrap = document.getElementById('deptCheckboxes');
    if (deptWrap) {
        const syncChip = (cb) => {
            const label = cb.closest('label');
            if (label) label.classList.toggle('is-selected', cb.checked);
        };
        deptWrap.querySelectorAll('input[name="allowed_dept"]').forEach(syncChip);
        deptWrap.addEventListener('change', (e) => {
            if (e.target && e.target.name === 'allowed_dept') syncChip(e.target);
        });
    }

    const imported = sessionStorage.getItem('importedForm');
    if (imported) {
        sessionStorage.removeItem('importedForm');
        try {
            const data = JSON.parse(imported);
            setTimeout(() => {
                if (__formEditor && data.html) __formEditor.setContent(data.html);
                if (data.doc_type) document.getElementById('formDocType').value = data.doc_type;
                if (data.title) document.getElementById('formTitle').value = data.title;
                document.querySelectorAll('#templateList .tpl-item').forEach(el => el.classList.remove('active'));
                resetFormDirty();
            }, 200);
        } catch(e) { console.error('imported form parse error', e); }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
