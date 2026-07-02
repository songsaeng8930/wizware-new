<?php
$pageTitle = '결재양식관리';
$currentPage = 'approval_admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_form_templates.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$docTypes = [];

if ($pdo) {
    try { $pdo->query('SELECT 1 FROM approval_forms LIMIT 1'); $hasDB = true;
        $docTypes = $pdo->query("SELECT DISTINCT doc_type FROM approval_forms ORDER BY doc_type")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) { $hasDB = false; }
}

$formTemplateCatalog = approval_form_seed_templates();
$sampleForms = array_map(static function (array $tpl, int $idx): array {
    return [
        'id' => $idx + 1,
        'doc_type' => $tpl['doc_type'],
        'title' => $tpl['title'] ?: $tpl['doc_type'],
        'category' => $tpl['category'] ?? '기본',
        'desc' => $tpl['desc'] ?? '',
        'created_at' => '2026-06-17',
        'is_active' => 1,
    ];
}, $formTemplateCatalog, array_keys($formTemplateCatalog));
if (!$hasDB) $docTypes = array_values(array_unique(array_column($sampleForms, 'doc_type')));

$saved = isset($_GET['saved']);
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950 approval-forms-page">
        <div class="approval-forms-hero rounded-2xl border border-slate-800 bg-slate-900 px-6 py-4 mb-5">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-100">결재양식관리</h2>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full border border-slate-700 bg-slate-800 text-slate-300">기본 양식 <strong class="text-slate-100"><?= count($formTemplateCatalog) ?>종</strong></span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full border border-slate-700 bg-slate-800 text-slate-300">카테고리 <strong class="text-slate-100"><?= count(array_unique(array_column($formTemplateCatalog, 'category'))) ?>개</strong></span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="approval_form_register.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i> 신규등록
                    </a>
                    <button type="button" onclick="openUploadModal()" class="btn btn-secondary">
                        <i data-lucide="upload" class="w-4 h-4"></i> 외부 업로드
                    </button>
                </div>
            </div>
            <p class="text-sm text-slate-500 mt-2">품의·지출·구매·휴가·출장 등 실무에서 바로 쓰는 결재 양식을 관리합니다.</p>
        </div>

        <?php if ($saved): ?>
        <div class="mb-5 flex items-center gap-2 px-4 py-3 rounded-lg border border-emerald-800 bg-emerald-900/30 text-emerald-200 text-sm">
            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
            <span>양식이 저장되었습니다.</span>
        </div>
        <?php endif; ?>

        <!-- 필터 토글 -->
        <div class="flex justify-end mb-3">
            <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> 필터
            </button>
        </div>

        <!-- 검색 필터 (접힘) -->
        <div id="filterPanel" class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-3 hidden approval-filter-card">
            <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">제목</label>
                    <input type="text" id="filterTitle" class="reg-input" placeholder="양식명, 문서종류 검색">
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
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button onclick="searchForms()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">검색 <i data-lucide="search" class="w-4 h-4"></i></button>
                <button onclick="resetFilters()" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- 리스트 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden approval-list-card">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
                <span class="text-sm text-slate-200">결재양식 총 등록건수 <strong id="totalCount" class="text-primary ml-1">0</strong></span>
                <select id="perPageSelect" class="reg-select w-20" onchange="renderForms()">
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full emp-table approval-forms-table">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="px-4 py-3 text-left">양식명</th>
                            <th class="px-4 py-3 text-left">실무 용도</th>
                            <th class="px-4 py-3 text-center">분류</th>
                            <th class="px-4 py-3 text-center">사용여부</th>
                            <th class="px-4 py-3 text-center">등록일</th>
                            <th class="px-4 py-3 text-center w-[110px]">관리</th>
                        </tr>
                    </thead>
                    <tbody id="formTableBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>
    </main>
</div>

<style>
.approval-forms-hero,
.approval-filter-card,
.approval-list-card { box-shadow: var(--zm-card-shadow); }
.approval-forms-kpi { transition: transform .15s, border-color .15s, background-color .15s; }
.approval-forms-kpi:hover { transform: translateY(-1px); border-color: rgba(0,0,0,0.12); }
.approval-form-name { display:flex; flex-direction:column; gap:3px; min-width:0; }
.approval-form-title { color:var(--zm-text-strong); font-weight:700; }
.approval-form-sub { color:var(--zm-text-muted); font-size:12px; }
.approval-form-desc { color:var(--zm-text-default); font-size:13px; line-height:1.45; max-width:520px; }
.approval-form-category { display:inline-flex; align-items:center; justify-content:center; min-width:64px; padding:4px 9px; border-radius:999px; background:var(--zm-surface-2); color:var(--zm-text-default); font-size:12px; font-weight:700; }
.approval-form-actions { display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.approval-form-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:8px; border:1px solid var(--zm-border); color:var(--zm-text-muted); background:var(--zm-surface-1); transition:all .15s; }
.approval-form-icon-btn:hover { color:var(--zm-text-strong); border-color:#94a3b8; background:var(--zm-surface-2); }
.approval-form-icon-btn.danger:hover { color:#b91c1c; border-color:#fecaca; background:#fef2f2; }
html[data-theme="light"] .approval-forms-hero,
html[data-theme="light"] .approval-filter-card,
html[data-theme="light"] .approval-list-card { background:#ffffff !important; border-color:#e5e7eb !important; }
html[data-theme="light"] .approval-forms-kpi { background:#f8fafc !important; border-color:#e5e7eb !important; }
html[data-theme="light"] .approval-forms-table tbody tr:hover > td { background:#f8fbff !important; }
</style>

<script>
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const TEMPLATE_CATALOG = <?= json_encode($formTemplateCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const TEMPLATE_META = Object.fromEntries(TEMPLATE_CATALOG.map(t => [t.doc_type, t]));
let allForms = <?= json_encode($sampleForms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let filteredForms = [...allForms];
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    if (HAS_DB) loadForms();
    else renderForms();
});

function loadForms() {
    const p = new URLSearchParams();
    const title = document.getElementById('filterTitle').value;
    const dt = document.getElementById('filterDocType').value;
    if (title) p.set('title', title);
    if (dt) p.set('doc_type', dt);
    fetch(`${API_BASE}?action=getForms&${p}`)
        .then(r => r.json())
        .then(data => { allForms = data.forms || []; filteredForms = [...allForms]; currentPage = 1; renderForms(); });
}

function searchForms() {
    if (HAS_DB) { loadForms(); return; }
    const title = document.getElementById('filterTitle').value.toLowerCase();
    const dt = document.getElementById('filterDocType').value;
    filteredForms = allForms.filter(f => {
        if (title && !(f.title||'').toLowerCase().includes(title) && !(f.doc_type||'').toLowerCase().includes(title)) return false;
        if (dt && f.doc_type !== dt) return false;
        return true;
    });
    currentPage = 1; renderForms();
}

function renderForms() {
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const tbody = document.getElementById('formTableBody');
    document.getElementById('totalCount').textContent = filteredForms.length;
    const start = (currentPage - 1) * perPage;
    const pageData = filteredForms.slice(start, start + perPage);

    if (!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-16 text-slate-400"><i data-lucide="info" class="mr-1 w-4 h-4"></i> 검색 결과가 없습니다</td></tr>';
        document.getElementById('pagination').innerHTML = '';
        if (window.lucide) lucide.createIcons();
        return;
    }

    tbody.innerHTML = pageData.map(f => {
        const meta = TEMPLATE_META[f.doc_type] || {};
        const desc = f.description || f.desc || meta.desc || '회사 규정에 맞게 본문과 결재선을 조정해서 사용할 수 있습니다.';
        const category = f.category || meta.category || '사용자 양식';
        const title = f.title || f.doc_type;
        return `
        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
            <td class="px-4 py-4 text-sm">
                <div class="approval-form-name">
                    <span class="approval-form-title">${esc(title)}</span>
                    <span class="approval-form-sub">${esc(f.doc_type || '')}</span>
                </div>
            </td>
            <td class="px-4 py-4"><div class="approval-form-desc">${esc(desc)}</div></td>
            <td class="px-4 py-4 text-center"><span class="approval-form-category">${esc(category)}</span></td>
            <td class="px-4 py-4 text-center">
                <button type="button" onclick="toggleForm(${f.id})" class="zm-toggle ${f.is_active==1?'active':''}" aria-label="${f.is_active==1?'사용 중':'미사용'}"><span></span></button>
            </td>
            <td class="px-4 py-4 text-sm text-slate-400 text-center">${((f.created_at||'').substring(0,10)).replace(/-/g,'.')}</td>
            <td class="px-4 py-4 text-center">
                <span class="approval-form-actions">
                    <a href="approval_form_register.php?id=${f.id}" class="approval-form-icon-btn" title="수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></a>
                    <button onclick="deleteForm(${f.id})" class="approval-form-icon-btn danger" title="삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                </span>
            </td>
        </tr>`;
    }).join('');
    if (window.lucide) lucide.createIcons();

    const pages = Math.ceil(filteredForms.length / perPage);
    if (pages <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let h = `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage<=1?'pg-disabled':''}" onclick="goPage(${currentPage-1})"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i=1;i<=pages;i++) h += `<button class="pg-btn ${i===currentPage?'pg-active':''}" onclick="goPage(${i})">${i}</button>`;
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${currentPage+1})"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    h += `<button class="pg-btn ${currentPage>=pages?'pg-disabled':''}" onclick="goPage(${pages})"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>`;
    document.getElementById('pagination').innerHTML = h;
    if (window.lucide) lucide.createIcons();
}

function goPage(p) { const pages = Math.ceil(filteredForms.length / parseInt(document.getElementById('perPageSelect').value)); if(p<1||p>pages)return; currentPage=p; renderForms(); }
function resetFilters() { document.getElementById('filterTitle').value=''; document.getElementById('filterDocType').value=''; searchForms(); }


async function deleteForm(id) {
    if (!(await AppUI.confirm('이 양식을 삭제하시겠습니까?'))) return;
    if (HAS_DB) {
        fetch(`${API_BASE}?action=deleteForm`, { method:'POST', headers:{'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN}, body:JSON.stringify({id}) })
            .then(r=>r.json()).then(() => loadForms());
    } else {
        allForms = allForms.filter(f => f.id !== id); filteredForms = [...allForms]; renderForms();
    }
}

function toggleForm(id) {
    if (HAS_DB) {
        fetch(`${API_BASE}?action=toggleForm`, { method:'POST', headers:{'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN}, body:JSON.stringify({id}) })
            .then(r=>r.json()).then(() => loadForms());
    } else {
        const f = allForms.find(x=>x.id===id); if(f) f.is_active = f.is_active==1?0:1;
        filteredForms=[...allForms]; renderForms();
    }
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

/* ── 외부 업로드 모달 ──────────────────────────────── */
function openUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModalStatus').textContent = '';
    document.getElementById('uploadModalFile').value = '';
    document.getElementById('uploadModalFileName').textContent = '';
    document.getElementById('uploadModalFileName').classList.add('hidden');
    document.getElementById('uploadModalSubmit').disabled = true;
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

function handleUploadDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('ring-2', 'ring-primary');
    const file = e.dataTransfer?.files?.[0];
    if (file) setUploadFile(file);
}

function handleUploadDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('ring-2', 'ring-primary');
}

function handleUploadDragLeave(e) {
    e.currentTarget.classList.remove('ring-2', 'ring-primary');
}

function handleUploadFileChange(e) {
    const file = e.target.files?.[0];
    if (file) setUploadFile(file);
}

function setUploadFile(file) {
    const allowed = ['.html','.htm','.txt','.md','.csv','.tsv','.docx'];
    const ext = '.' + file.name.split('.').pop().toLowerCase();
    if (!allowed.includes(ext)) {
        document.getElementById('uploadModalStatus').textContent = '지원하지 않는 파일 형식입니다.';
        document.getElementById('uploadModalStatus').className = 'text-xs text-red-500 mt-2';
        return;
    }
    document.getElementById('uploadModalStatus').textContent = '';
    const nameEl = document.getElementById('uploadModalFileName');
    nameEl.textContent = file.name;
    nameEl.classList.remove('hidden');
    document.getElementById('uploadModalSubmit').disabled = false;
    document.getElementById('uploadModalSubmit')._file = file;
}

function submitUploadModal() {
    const btn = document.getElementById('uploadModalSubmit');
    const file = btn._file;
    if (!file) return;
    btn.disabled = true;
    btn.textContent = '업로드 중...';

    const fd = new FormData();
    fd.append('template_file', file);
    fd.append('_csrf', CSRF_TOKEN);

    fetch(`${API_BASE}?action=importFormTemplate`, { method: 'POST', body: fd })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok || data.error) throw new Error(data.error || '양식을 읽지 못했습니다.');
            return data;
        })
        .then(data => {
            sessionStorage.setItem('importedForm', JSON.stringify({
                html: data.html || '',
                doc_type: data.doc_type || '',
                title: data.title || '',
                fileName: file.name
            }));
            location.href = 'approval_form_register.php?imported=1';
        })
        .catch(err => {
            document.getElementById('uploadModalStatus').textContent = err.message;
            document.getElementById('uploadModalStatus').className = 'text-xs text-red-500 mt-2';
            btn.disabled = false;
            btn.textContent = '업로드';
        });
}
</script>

<!-- 외부 업로드 모달 -->
<div id="uploadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" onclick="closeUploadModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 mx-4" style="background:var(--zm-surface-1);border:1px solid var(--zm-border-default)">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold" style="color:var(--zm-text-strong)">외부 양식 업로드</h3>
            <button onclick="closeUploadModal()" class="p-1 rounded-lg hover:bg-slate-100" style="color:var(--zm-text-muted)">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background:var(--zm-chip-bg);color:var(--zm-text-default)">HTML</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background:var(--zm-chip-bg);color:var(--zm-text-default)">TXT</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background:var(--zm-chip-bg);color:var(--zm-text-default)">CSV</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background:var(--zm-chip-bg);color:var(--zm-text-default)">DOCX</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background:var(--zm-chip-bg);color:var(--zm-text-default)">MD</span>
        </div>

        <div class="rounded-xl border-2 border-dashed p-8 text-center cursor-pointer transition-colors"
             style="border-color:var(--zm-border-default);background:var(--zm-surface-2)"
             ondrop="handleUploadDrop(event)"
             ondragover="handleUploadDragOver(event)"
             ondragleave="handleUploadDragLeave(event)"
             onclick="document.getElementById('uploadModalFile').click()">
            <i data-lucide="upload-cloud" class="w-10 h-10 mx-auto mb-3" style="color:var(--zm-text-muted)"></i>
            <p class="text-sm font-medium" style="color:var(--zm-text-default)">파일을 드래그하거나 클릭하여 선택</p>
            <p class="text-xs mt-1" style="color:var(--zm-text-muted)">최대 10MB</p>
            <input type="file" id="uploadModalFile" class="hidden" accept=".html,.htm,.txt,.md,.csv,.tsv,.docx" onchange="handleUploadFileChange(event)">
        </div>

        <p id="uploadModalFileName" class="hidden text-sm font-medium mt-3 px-1" style="color:var(--zm-text-default)"></p>
        <p id="uploadModalStatus" class="text-xs mt-2"></p>

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" onclick="closeUploadModal()" class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors" style="border-color:var(--zm-border-default);color:var(--zm-text-default)">취소</button>
            <button type="button" id="uploadModalSubmit" onclick="submitUploadModal()" disabled class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed">업로드</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
