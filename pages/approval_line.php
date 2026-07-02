<?php
$pageTitle = '결재라인 설정';
$currentPage = 'approval_admin';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$docTypes = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM approval_lines LIMIT 1');
        $hasDB = true;
        $dbDocTypes = $pdo->query("SELECT doc_type, COUNT(*) AS cnt FROM approval_forms WHERE is_active = 1 GROUP BY doc_type")->fetchAll(PDO::FETCH_ASSOC);
        $docTypeMap = [];
        foreach ($dbDocTypes as $dt) $docTypeMap[$dt['doc_type']] = (int)$dt['cnt'];
        require_once __DIR__ . '/../includes/approval_form_templates.php';
        foreach (approval_form_seed_templates() as $tpl) {
            $d = trim($tpl['doc_type'] ?? '');
            if ($d !== '' && !isset($docTypeMap[$d])) $docTypeMap[$d] = 0;
        }
        ksort($docTypeMap);
        foreach ($docTypeMap as $name => $cnt) {
            $docTypes[] = ['doc_type' => $name, 'cnt' => $cnt];
        }
    } catch (PDOException $e) {
        $hasDB = false;
    }
}
if (!$docTypes) {
    require_once __DIR__ . '/../includes/approval_form_templates.php';
    foreach (approval_form_seed_templates() as $tpl) {
        $d = trim($tpl['doc_type'] ?? '');
        if ($d !== '') $docTypes[] = ['doc_type' => $d, 'cnt' => 0];
    }
}

$departments = [];
$employees = [];
if ($pdo) {
    try {
        $departments = $pdo->query("SELECT id, parent_id, name, sort_order, head_employee_id FROM departments WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $departments = $pdo->query("SELECT id, parent_id, name, sort_order, NULL as head_employee_id FROM departments WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {}
    }
    try {
        $employees = $pdo->query("SELECT e.id, e.name, e.position, e.title, e.department_id, d.name AS dept_name
                                    FROM employees e
                                    LEFT JOIN departments d ON e.department_id = d.id
                                    WHERE e.is_active = 1 AND (e.employment_status IS NULL OR e.employment_status <> '퇴사')
                                    ORDER BY d.sort_order, d.name, e.position, e.name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
if (!$departments) {
    $departments = [
        ['id'=>1,'parent_id'=>null,'name'=>'(주)재밋','sort_order'=>0,'head_employee_id'=>1],
        ['id'=>2,'parent_id'=>1,'name'=>'경영지원본부','sort_order'=>1,'head_employee_id'=>2],
        ['id'=>3,'parent_id'=>1,'name'=>'기술개발본부','sort_order'=>2,'head_employee_id'=>3],
        ['id'=>4,'parent_id'=>1,'name'=>'영업본부','sort_order'=>3,'head_employee_id'=>null],
        ['id'=>5,'parent_id'=>2,'name'=>'경영지원팀','sort_order'=>1,'head_employee_id'=>5],
        ['id'=>6,'parent_id'=>2,'name'=>'인사팀','sort_order'=>2,'head_employee_id'=>6],
        ['id'=>7,'parent_id'=>2,'name'=>'재무회계팀','sort_order'=>3,'head_employee_id'=>7],
        ['id'=>8,'parent_id'=>3,'name'=>'개발1팀','sort_order'=>1,'head_employee_id'=>8],
        ['id'=>9,'parent_id'=>3,'name'=>'개발2팀','sort_order'=>2,'head_employee_id'=>null],
        ['id'=>10,'parent_id'=>3,'name'=>'QA팀','sort_order'=>3,'head_employee_id'=>null],
        ['id'=>11,'parent_id'=>4,'name'=>'국내영업팀','sort_order'=>1,'head_employee_id'=>11],
        ['id'=>12,'parent_id'=>4,'name'=>'해외영업팀','sort_order'=>2,'head_employee_id'=>null],
    ];
}
if (!$employees) {
    $employees = [
        ['id'=>1,'name'=>'김대표','position'=>'대표이사','title'=>'CEO','department_id'=>1,'dept_name'=>'(주)재밋'],
        ['id'=>2,'name'=>'이본부장','position'=>'이사','title'=>'경영지원본부장','department_id'=>2,'dept_name'=>'경영지원본부'],
        ['id'=>3,'name'=>'박기술','position'=>'이사','title'=>'CTO','department_id'=>3,'dept_name'=>'기술개발본부'],
        ['id'=>5,'name'=>'정지원','position'=>'부장','title'=>'경영지원팀장','department_id'=>5,'dept_name'=>'경영지원팀'],
        ['id'=>6,'name'=>'한인사','position'=>'부장','title'=>'인사팀장','department_id'=>6,'dept_name'=>'인사팀'],
        ['id'=>7,'name'=>'오재무','position'=>'부장','title'=>'재무회계팀장','department_id'=>7,'dept_name'=>'재무회계팀'],
        ['id'=>8,'name'=>'강개발','position'=>'부장','title'=>'개발1팀장','department_id'=>8,'dept_name'=>'개발1팀'],
        ['id'=>11,'name'=>'서영업','position'=>'과장','title'=>'','department_id'=>11,'dept_name'=>'국내영업팀'],
    ];
}
$basePath = rtrim(str_replace('\\','/',str_replace(realpath($_SERVER['DOCUMENT_ROOT']),'',realpath(__DIR__.'/..'))),'/');
?>

<style>
#empDropdown::-webkit-scrollbar { width: 6px; }
#empDropdown::-webkit-scrollbar-track { background: transparent; }
#empDropdown::-webkit-scrollbar-thumb { background: var(--zm-text-muted, #475569); border-radius: 3px; }
</style>
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6 min-h-screen bg-slate-950">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-lg font-bold text-slate-100">결재라인 설정</h2>
            <p class="text-xs text-slate-500 mt-0.5">기본 결재 경로와 문서별 적용 경로를 관리하는 곳이에요.</p>
        </div>
    </div>

    <input type="hidden" id="exId" value="0">

    <!-- ① 문서 선택 + 기본 설정 (상단 전체) -->
    <section class="bg-slate-900 rounded-xl border border-slate-800 p-4 mb-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <select id="exDocType" class="reg-select max-w-[220px]" onchange="onEditorDocChange()">
                        <option value="">문서 종류 선택</option>
                        <?php foreach ($docTypes as $dt): ?>
                        <option value="<?= htmlspecialchars($dt['doc_type']) ?>"><?= htmlspecialchars($dt['doc_type']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-sm text-slate-500">의 결재 경로</span>
                    <div class="ml-auto flex items-center gap-1.5 text-xs text-slate-400">
                        <span>기본:</span>
                        <span id="depthDesc" class="text-slate-200 font-medium"></span>
                        <button type="button" onclick="toggleDepthPanel()" class="text-gray-600 hover:underline ml-1">변경</button>
                    </div>
                </div>
                <div id="amountThresholdRow" class="hidden mt-3 pt-3 border-t border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-400 shrink-0">금액 기준</span>
                        <div class="relative w-[200px]">
                            <input type="number" id="exAmountThreshold" class="reg-input w-full pr-8 text-right" value="0" min="0" step="10000" placeholder="0">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-500">원</span>
                        </div>
                        <span class="text-xs text-slate-500">이상일 때 이 경로 적용 (0 = 금액 무관)</span>
                    </div>
                </div>
                <!-- depth 패널 (숨김) -->
                <div id="depthPanel" class="hidden mt-3 pt-3 border-t border-slate-800">
                    <div class="text-[11px] text-slate-500 mb-2">직접 설정하지 않은 모든 문서는 조직도를 따라 이 단계까지 올라가요</div>
                    <div id="depthButtons" class="flex gap-2"></div>
                </div>
    </section>

    <!-- 2단 레이아웃: 경로 편집기(메인) | 미리보기(사이드) -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-5 items-start">

        <!-- ===== 왼쪽: 편집기 + 예외 목록 ===== -->
        <div class="space-y-4">
            <!-- ② 경로 편집 -->
            <section id="editorSection" class="bg-slate-900 rounded-xl border border-slate-800 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span id="editorTitle" class="text-sm font-semibold text-slate-200">경로</span>
                    <span id="editorBadge" class="hidden text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-semibold">직접 설정</span>
                    <div class="relative group ml-1">
                        <i data-lucide="help-circle" class="w-3.5 h-3.5 text-slate-500 cursor-help"></i>
                        <div class="hidden group-hover:block absolute left-0 bottom-full mb-1.5 z-50 w-[300px] p-3 rounded-lg bg-slate-800 border border-slate-600 shadow-xl text-xs text-slate-300 leading-relaxed">
                            <p class="font-semibold text-slate-200 mb-1.5">결재 경로란?</p>
                            <p class="mb-1.5">직원이 문서를 작성·상신하면 여기에 설정된 순서대로 결재자에게 전달돼요.</p>
                            <p class="mb-1"><strong class="text-slate-200" id="helpSlotText"></strong> · 문서를 올리는 사람의 조직도 기준으로 해당 직급자를 찾아 넣어요. <?= htmlspecialchars(getOrgLabel('department')) ?>마다 다른 사람이 채워져요.</p>
                            <p class="mb-1"><strong class="text-gray-600">직원 검색</strong> · 특정 사람을 지정하면 누가 올리든 항상 그 사람에게 가요.</p>
                            <p><strong>결재/전결/협조/참조</strong> · 오른쪽 드롭다운으로 역할을 바꿀 수 있어요. 결재=승인 권한, 전결=최종 승인, 협조=의견 제공, 참조=열람만.</p>
                        </div>
                    </div>
                </div>

                <!-- 경로 스텝 -->
                <div id="exStepList" class="flex flex-col gap-1.5 mb-4 min-h-[60px]"></div>

                <!-- 추가 도구 -->
                <div class="flex items-center gap-2 flex-wrap mb-3">
                    <span class="text-[12px] font-semibold text-slate-400 shrink-0 mr-0.5">결재자 추가</span>
                    <div class="relative w-[300px]" id="empSearchWrap">
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                        <i data-lucide="chevron-down" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-300 pointer-events-none"></i>
                        <input type="text" id="exEmpSearch" class="w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-800 placeholder-gray-400 pl-8 pr-8 py-2 focus:outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-300/10" placeholder="직원 이름으로 검색" autocomplete="off" onfocus="openEmpDropdown()" oninput="openEmpDropdown()">
                        <div id="empDropdownWrap" class="hidden absolute left-0 right-0 top-full mt-1.5 z-50 max-h-[280px] rounded-xl border border-gray-300 bg-white overflow-hidden" style="box-shadow:0 4px 24px rgba(0,0,0,.12), 0 0 0 1px rgba(0,0,0,.04)">
                            <div id="empDropdown" class="max-h-[278px] overflow-y-auto" style="scrollbar-gutter:stable"></div>
                        </div>
                    </div>
                    <span id="slotButtons"></span>
                </div>

                <p class="text-[11px] text-slate-400 mb-4"><i data-lucide="info" class="inline w-3 h-3 -mt-0.5 text-slate-500"></i> <span id="slotHintText"></span></p>

                <!-- 저장 바 -->
                <div id="editorActions" class="flex items-center justify-between pt-3 border-t border-slate-800">
                    <button type="button" id="exDeleteBtn" onclick="deleteException()" class="hidden inline-flex items-center gap-1.5 text-sm text-rose-400 hover:text-rose-300 font-medium">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> 기본 경로로
                    </button>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" onclick="resetEditor()" class="btn btn-secondary">초기화</button>
                        <button type="button" onclick="saveException()" class="px-5 py-2 text-sm font-medium bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors shadow-sm">저장</button>
                    </div>
                </div>
            </section>

            <!-- 문서별 목록은 메인 편집 흐름에서 숨김: 필요 시 별도 탭/화면으로 분리 -->
            <section class="hidden" aria-hidden="true">
                <span id="exceptionCount"></span>
                <div id="exceptionList"></div>
                <div id="exceptionEmpty" class="hidden"></div>
            </section>

        </div>

        <!-- ===== 오른쪽: 실제 결재 경로 확인 ===== -->
        <div class="lg:sticky lg:top-20">
            <section class="bg-slate-900 rounded-xl border border-slate-800 p-5">
                <h3 id="simTitle" class="text-sm font-bold text-slate-200 mb-1">이 문서를 올리면 누가 결재하나?</h3>
                <p id="simDesc" class="text-xs text-slate-500 mb-3">직원을 바꿔보면 <?= htmlspecialchars(getOrgLabel('department')) ?>에 따라 경로가 달라지는 걸 확인할 수 있어요.</p>
                <select id="simEmp" class="reg-select text-sm py-2 w-full mb-4" onchange="simulate()">
                    <option value="">직원을 골라보세요</option>
                    <?php foreach ($employees as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['name']) ?> (<?= htmlspecialchars($e['dept_name'] ?: '-') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div id="simResult" class="min-h-[60px] rounded-xl bg-slate-950/50 p-4"></div>
            </section>
        </div>

    </div>

</main>
</div>

<script>
const esc = ApvUI.esc;
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const DEPTS = <?= json_encode($departments, JSON_UNESCAPED_UNICODE) ?>;
const EMPLOYEES = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
const DOC_TYPES = <?= json_encode(array_values(array_map(fn($d) => $d['doc_type'], $docTypes)), JSON_UNESCAPED_UNICODE) ?>;

const deptById = {};
DEPTS.forEach(d => { d.id = parseInt(d.id); d.parent_id = d.parent_id ? parseInt(d.parent_id) : null; d.head_employee_id = d.head_employee_id ? parseInt(d.head_employee_id) : null; deptById[d.id] = d; });
const empById = {};
EMPLOYEES.forEach(e => { e.id = parseInt(e.id); e.department_id = parseInt(e.department_id); empById[e.id] = e; });

let currentDepth = 'division_head';
let loadedExceptions = [];
let editing = false;

function isSlot(s) { return (s.type || '') === 'slot' || (!!s.slot && !s.name); }
function stepLabel(s) {
    if (isSlot(s)) return '<span class="font-medium">' + esc(s.slot) + '</span>';
    return '<span class="font-medium">' + esc(s.name || '-') + '</span>' + (s.position ? ' <span class="text-[11px] opacity-70">' + esc(s.position) + '</span>' : '');
}
function routeArrow() {
    return `<div class="flex justify-center py-0.5"><svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m5 12 7 7 7-7"/></svg></div>`;
}
function drafterStartCard() {
    return `<div class="grid grid-cols-[16px_30px_minmax(0,1fr)_76px_24px_22px] items-center gap-2 px-3 py-2.5 rounded-lg border border-gray-200 bg-gray-50 shadow-sm">
        <span></span>
        <span class="w-6 h-6 rounded-full bg-gray-400 text-white flex items-center justify-center shrink-0"><i data-lucide="pen-line" class="w-3 h-3"></i></span>
        <div class="min-w-0">
            <div class="text-sm font-semibold text-gray-800 truncate">기안자</div>
            <div class="text-[11px] text-gray-400 truncate">상신자 기준</div>
        </div>
        <span class="inline-flex h-7 w-[76px] items-center justify-center rounded-md bg-gray-200 text-[12px] font-semibold text-gray-600">기안</span>
        <span></span>
        <span></span>
    </div>`;
}
function parseLineData(l) {
    try { return typeof l.line_data === 'string' ? JSON.parse(l.line_data) : (l.line_data || []); }
    catch (e) { return []; }
}

let _configReady = false, _exceptionsReady = false;
function _tryAutoSelect() {
    if (!_configReady || !_exceptionsReady) return;
    const docSel = document.getElementById('exDocType');
    if (docSel.options.length > 1 && !docSel.value) {
        docSel.selectedIndex = 1;
        onEditorDocChange();
    }
}
function initOrgLabelsUI() {
    const L = window.ORG_LABELS || {};
    const enabled = Object.entries(L).filter(([k,v]) => !k.startsWith('_') && v && v.enabled).sort((a,b) => a[1].depth - b[1].depth);
    const nonCompany = enabled.filter(([k]) => k !== 'company');
    const company = enabled.find(([k]) => k === 'company');

    const depthBtnContainer = document.getElementById('depthButtons');
    if (depthBtnContainer) {
        const depthMap = buildDepthMap();
        depthBtnContainer.innerHTML = '';
        Object.keys(depthMap).forEach((key, i) => {
            const btn = document.createElement('button');
            btn.className = 'depth-btn flex-1 px-3 py-2.5 rounded-lg border border-slate-700 text-center transition-colors hover:border-gray-300/60';
            btn.dataset.depth = key;
            btn.addEventListener('click', () => setDepth(key));
            const title = document.createElement('div');
            title.className = 'text-sm font-semibold text-slate-200';
            title.textContent = (i+1) + '단계';
            const desc = document.createElement('div');
            desc.className = 'text-[11px] text-slate-500 mt-0.5';
            desc.textContent = depthMap[key];
            btn.appendChild(title);
            btn.appendChild(desc);
            depthBtnContainer.appendChild(btn);
        });
    }

    const slotBtnContainer = document.getElementById('slotButtons');
    if (slotBtnContainer) {
        const allLevels = nonCompany.slice();
        if (company) allLevels.push(company);
        slotBtnContainer.innerHTML = '';
        slotBtnContainer.style.display = 'inline-flex';
        slotBtnContainer.style.gap = '6px';
        allLevels.forEach(([k, v]) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-2.5 py-1.5 text-xs rounded-lg border border-slate-700 text-slate-400 hover:border-gray-300/50 hover:text-gray-200 transition-colors';
            btn.textContent = '+' + v.head;
            btn.addEventListener('click', () => addSlot(v.head, k + '.head'));
            slotBtnContainer.appendChild(btn);
        });
    }

    const hintEl = document.getElementById('slotHintText');
    if (hintEl) {
        const allHeads = enabled.map(([,v]) => v.head);
        hintEl.textContent = allHeads.join('/') + ' 항목은 작성자의 조직도 기준으로 실제 담당자가 채워져요';
    }

    const helpEl = document.getElementById('helpSlotText');
    if (helpEl) {
        const allHeads = enabled.map(([,v]) => '+' + v.head);
        helpEl.textContent = allHeads.join(', ');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    initOrgLabelsUI();
    loadConfig();
    loadExceptions();
    renderEmpBrowser('');
    const empSel = document.getElementById('simEmp');
    if (empSel.options.length > 1) empSel.selectedIndex = 1;
    if (window.lucide) lucide.createIcons();
});

/* ───── 설정 로드 + depth 토글 ───── */
function loadConfig() {
    fetch(`${API_BASE}?action=getApprovalConfig`)
        .then(r => r.json())
        .then(data => {
            currentDepth = data.default_depth || 'division_head';
            markDepthToggle();
            updateDepthDesc();
            _configReady = true; _tryAutoSelect();
        })
        .catch(() => { markDepthToggle(); updateDepthDesc(); _configReady = true; _tryAutoSelect(); });
}

function buildDepthMap() {
    const map = {};
    buildDepthEntries().forEach(entry => {
        map[entry.key] = '기안자 → ' + entry.slots.map(s => s.slot).join(' → ');
    });
    return map;
}

function buildDepthEntries() {
    const L = window.ORG_LABELS || {};
    const enabled = Object.entries(L).filter(([k,v]) => !k.startsWith('_') && v && v.enabled).sort((a,b) => a[1].depth - b[1].depth);
    const nonCompany = enabled.filter(([k]) => k !== 'company');
    const company = enabled.find(([k]) => k === 'company');
    const reversed = [...nonCompany].reverse();
    const entries = [];
    for (let i = 1; i <= reversed.length; i++) {
        const key = i === 1 ? 'team_lead' : (i === 2 ? 'division_head' : 'level_' + i);
        entries.push({ key, slots: reversed.slice(0, i).map(([k,v]) => ({ slot: v.head, slot_key: k + '.head' })).filter(s => s.slot) });
    }
    if (company) {
        const slots = reversed.map(([k,v]) => ({ slot: v.head, slot_key: k + '.head' })).filter(s => s.slot);
        slots.push({ slot: company[1].head, slot_key: 'company.head' });
        entries.push({ key: 'ceo', slots });
    }
    return entries;
}

function updateDepthDesc() {
    const map = buildDepthMap();
    const el = document.getElementById('depthDesc');
    if (el) el.textContent = map[currentDepth] || Object.values(map)[1] || '';
}

function markDepthToggle() {
    document.querySelectorAll('.depth-btn').forEach(btn => {
        const active = btn.dataset.depth === currentDepth;
        btn.style.borderColor = active ? 'rgba(0,0,0,0.12)' : '';
        btn.style.background = active ? 'rgba(79, 106, 255, 0.08)' : '';
    });
}

function toggleDepthPanel() {
    const panel = document.getElementById('depthPanel');
    panel.classList.toggle('hidden');
}

function openEmpDropdown() {
    const wrap = document.getElementById('empDropdownWrap');
    wrap.classList.remove('hidden');
    renderEmpBrowser(document.getElementById('exEmpSearch').value);
}
function closeEmpDropdown() {
    document.getElementById('empDropdownWrap').classList.add('hidden');
}
document.addEventListener('click', e => {
    const wrap = document.getElementById('empSearchWrap');
    if (wrap && !wrap.contains(e.target)) closeEmpDropdown();
});

function setDepth(value) {
    currentDepth = value;
    markDepthToggle();
    updateDepthDesc();
    const docType = document.getElementById('exDocType').value;
    const existing = loadedExceptions.find(l => l.doc_type === docType);
    if (docType && !existing) {
        fillDefaultSteps();
        renderExSteps();
    }
    renderExceptionList();
    simulate();
    fetch(`${API_BASE}?action=saveApprovalConfig`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ default_depth: value })
    }).catch(() => {});
}

/* ───── 시뮬레이터 ───── */
function simulate() {
    const emp = document.getElementById('simEmp').value;
    const doc = document.getElementById('exDocType').value;
    const box = document.getElementById('simResult');
    const titleEl = document.getElementById('simTitle');
    const descEl = document.getElementById('simDesc');

    if (!doc) {
        titleEl.textContent = '이 문서를 올리면 누가 결재하나?';
        descEl.textContent = '왼쪽에서 문서 종류를 선택하면 경로를 확인할 수 있어요.';
        box.innerHTML = '';
        return;
    }
    if (!emp) {
        titleEl.textContent = doc + ' 결재 경로';
        descEl.textContent = '직원을 골라보면 실제로 누구에게 가는지 볼 수 있어요.';
        box.innerHTML = '';
        return;
    }

    const empData = empById[parseInt(emp)];
    titleEl.textContent = (empData ? empData.name + '님이 ' : '') + doc + '를 올리면';
    descEl.textContent = '직원을 바꿔보면 ' + (((window.ORG_LABELS || {}).department || {}).label || '부서') + '에 따라 경로가 달라지는 걸 확인할 수 있어요.';

    box.innerHTML = '<div class="text-sm text-slate-500 py-3 text-center">계산 중…</div>';

    const isException = loadedExceptions.some(l => l.doc_type === doc);

    fetch(`${API_BASE}?action=getResolvedRoute&employee_id=${encodeURIComponent(emp)}&doc_type=${encodeURIComponent(doc)}`)
        .then(r => r.json())
        .then(data => {
            const route = data.route || [];
            if (!route.length) {
                box.innerHTML = '<div class="text-sm text-amber-400 py-3 text-center"><i data-lucide="alert-triangle" class="inline w-4 h-4 mr-1"></i>결재자를 찾지 못했어요.</div>';
                if (window.lucide) lucide.createIcons();
                return;
            }
            const empData = empById[parseInt(emp)];
            function roleText(role) {
                return `<span class="justify-self-end w-[54px] text-center rounded-md bg-gray-100 border border-gray-200 py-0.5 text-[11px] font-bold text-gray-600 whitespace-nowrap">${esc(role)}</span>`;
            }
            const simArrow = '<div class="flex justify-center py-0.5"><svg class="w-3.5 h-3.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m5 12 7 7 7-7"/></svg></div>';
            let html = '<div class="flex flex-col">';
            if (empData) {
                const drafterInfo = [empData.dept_name, empData.position, empData.title].filter(Boolean).join(' · ');
                html += `<div class="grid grid-cols-[26px_minmax(0,1fr)_54px] items-center gap-2 px-2.5 py-2 rounded-lg border border-gray-200 bg-gray-50">
                    <span class="w-6 h-6 rounded-full bg-gray-400 text-white flex items-center justify-center text-[11px] font-bold shrink-0">${esc(empData.name.charAt(0))}</span>
                    <div class="min-w-0">
                        <div class="text-[13px] font-semibold text-gray-800 truncate">${esc(empData.name)}</div>
                        <div class="text-[11px] text-gray-400 truncate">${esc(drafterInfo)}</div>
                    </div>
                    ${roleText('기안')}
                </div>`;
            }
            route.forEach((s, i) => {
                const role = s.role || '결재';
                const posText = [s.dept, s.position, s.title].filter(Boolean).join(' · ');
                html += `${simArrow}<div class="grid grid-cols-[26px_minmax(0,1fr)_54px] items-center gap-2 px-2.5 py-2 rounded-lg border border-gray-200 bg-white">
                    <span class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-[11px] font-bold shrink-0">${esc(s.name.charAt(0))}</span>
                    <div class="min-w-0">
                        <div class="text-[13px] font-semibold text-gray-800 truncate">${esc(s.name)}</div>
                        <div class="text-[11px] text-gray-400 truncate">${esc(posText)}</div>
                    </div>
                    ${roleText(role)}
                </div>`;
            });
            html += '</div>';
            if (isException) html += '<div class="text-[11px] text-gray-600 mt-2 pt-2 border-t border-gray-200 font-medium">직접 설정 경로 적용 중</div>';
            box.innerHTML = html;
            if (window.lucide) lucide.createIcons();
        })
        .catch(() => {
            box.innerHTML = '<div class="text-sm text-rose-400 py-3 text-center">서버 연결에 실패했어요.</div>';
        });
}

/* ───── 예외 데이터 ───── */
function loadExceptions() {
    fetch(`${API_BASE}?action=getLines`)
        .then(r => r.json())
        .then(data => {
            loadedExceptions = (data.lines || []).filter(l => (l.doc_type || '').trim() !== '');
            renderExceptionList();
            if (!editing) simulate();
            _exceptionsReady = true; _tryAutoSelect();
        })
        .catch(() => { loadedExceptions = []; renderExceptionList(); _exceptionsReady = true; _tryAutoSelect(); });
}

function renderExceptionList() {
    const list = document.getElementById('exceptionList');
    const empty = document.getElementById('exceptionEmpty');
    const count = document.getElementById('exceptionCount');
    const exByDoc = {};
    loadedExceptions.forEach(ex => {
        const doc = (ex.doc_type || '').trim();
        if (!doc) return;
        if (!exByDoc[doc]) exByDoc[doc] = [];
        exByDoc[doc].push(ex);
    });
    const docs = Array.from(new Set([...(DOC_TYPES || []), ...loadedExceptions.map(ex => ex.doc_type).filter(Boolean)]));

    if (!docs.length) {
        list.innerHTML = '';
        empty.classList.remove('hidden');
        count.textContent = '';
        return;
    }

    empty.classList.add('hidden');
    const directCount = docs.filter(doc => exByDoc[doc]).length;
    count.textContent = `${docs.length}개 문서 · 직접 설정 ${directCount}개`;

    function routePreview(steps) {
        const routeSep = '<span class="text-gray-300 text-[11px] mx-0.5">→</span>';
        const draftStep = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 border border-gray-200">` +
            `<span class="text-[11px] text-gray-700 font-semibold">기안자</span>` +
            `<span class="text-[10px] text-gray-400 font-semibold">기안</span>` +
            `</span>`;
        const stepsHtml = steps.map(s => {
            const role = s.role || '결재';
            const label = isSlot(s) ? s.slot : (s.name || '-');
            const isS = isSlot(s);
            const bgCls = isS ? 'bg-amber-50 border-amber-200' : 'bg-gray-50 border-gray-200';
            const labelCls = isS ? 'text-amber-700' : 'text-gray-700';
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full ${bgCls} border">` +
                `<span class="text-[11px] ${labelCls} font-semibold">${esc(label)}</span>` +
                `<span class="text-[10px] text-gray-400 font-semibold">${esc(role)}</span>` +
                `</span>`;
        }).join(routeSep);
        return draftStep + (stepsHtml ? routeSep + stepsHtml : '');
    }

    list.innerHTML = docs.map(doc => {
        const exArr = exByDoc[doc];
        const ex = exArr ? exArr[0] : null;
        const steps = ex ? parseLineData(ex) : getDefaultSteps();
        const isActive = document.getElementById('exDocType').value === doc;
        const isDirect = !!ex;
        const activeCls = isActive ? 'border-gray-300/60 bg-gray-50 ring-1 ring-gray-300/30' : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm';
        const badgeCls = isDirect ? 'bg-gray-100 text-gray-600 border-gray-300/40' : 'bg-gray-100 text-gray-500 border-gray-200';
        const badgeText = isDirect ? '직접 설정' : '기본 경로';
        const amountLines = exArr ? exArr.filter(e => parseInt(e.amount_threshold || 0) > 0) : [];
        const amountBadge = amountLines.length ? `<span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-bold bg-blue-50 text-blue-600 border-blue-200">금액별 ${amountLines.length + 1}단계</span>` : '';
        return `
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border ${activeCls} cursor-pointer transition-all group"
             onclick="selectDocType(this.dataset.doc)" data-doc="${esc(doc)}">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="text-sm font-bold text-gray-800 truncate">${esc(doc)}</div>
                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-bold ${badgeCls}">${badgeText}</span>
                    ${amountBadge}
                </div>
                <div class="flex items-center flex-wrap gap-1">${routePreview(steps)}</div>
            </div>
            ${isDirect ? `<button onclick="event.stopPropagation(); deleteException(${ex.id|0})" class="text-[11px] text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all shrink-0 px-2 py-1 rounded-md hover:bg-red-50" title="기본 경로로 되돌리기">기본으로</button>` : ''}
        </div>`;
    }).join('');
    if (window.lucide) lucide.createIcons();
}

function applyPreset(key) {
    const docType = document.getElementById('exDocType').value;
    if (!docType) { alert('먼저 문서 종류를 선택하세요.'); return; }
    const L = window.ORG_LABELS || {};
    const deptHead = (L.department || {}).head || '부서장';
    const ceoHead = (L.company || {}).head || '대표';
    const presets = {
        'dept_specific': [
            { type:'slot', slot: deptHead, slot_key:'department.head', role:'결재' }
        ],
        'dept_finance': [
            { type:'slot', slot: deptHead, slot_key:'department.head', role:'결재' },
            { type:'person', name:'오재무', id:7, dept:'재무회계팀', position:'부장', role:'전결' }
        ],
        'direct_ceo': [
            { type:'slot', slot: deptHead, slot_key:'department.head', role:'결재' },
            { type:'slot', slot: ceoHead, slot_key:'company.head', role:'전결' }
        ],
        'dept_only': [
            { type:'slot', slot: deptHead, slot_key:'department.head', role:'전결' }
        ],
    };
    exSteps = JSON.parse(JSON.stringify(presets[key] || []));
    if (key === 'dept_specific') {
        alert(deptHead + ' 슬롯을 넣었어요. 오른쪽 직원 목록에서 경유할 팀장을 추가하세요.');
    }
    editing = true;
    renderExSteps();
    simulate();
    if (window.lucide) lucide.createIcons();
}

function getDefaultSteps() {
    const depthSlots = {};
    buildDepthEntries().forEach(entry => {
        depthSlots[entry.key] = entry.slots.map(s => ({ type:'slot', slot: s.slot, slot_key: s.slot_key, role:'결재' }));
    });
    const fallback = depthSlots['division_head'] || Object.values(depthSlots)[0] || [];
    const steps = JSON.parse(JSON.stringify(depthSlots[currentDepth] || fallback));
    if (steps.length >= 2) steps[steps.length - 1].role = '전결';
    return steps;
}

function fillDefaultSteps() {
    exSteps = getDefaultSteps();
}

function onEditorDocChange() {
    const docType = document.getElementById('exDocType').value;
    const amountRow = document.getElementById('amountThresholdRow');
    const amountInput = document.getElementById('exAmountThreshold');
    if (!docType) {
        exSteps = [];
        document.getElementById('exId').value = 0;
        document.getElementById('exDeleteBtn').classList.add('hidden');
        document.getElementById('editorTitle').textContent = '문서별 경로';
        amountRow.classList.add('hidden');
        amountInput.value = 0;
        renderExSteps();
        renderExceptionList();
        simulate();
        return;
    }
    amountRow.classList.remove('hidden');
    const existing = loadedExceptions.find(l => l.doc_type === docType && parseInt(l.amount_threshold || 0) === parseInt(amountInput.value || 0));
    const existingAny = loadedExceptions.find(l => l.doc_type === docType);
    const target = existing || existingAny;
    if (target) {
        document.getElementById('exId').value = target.id | 0;
        document.getElementById('exDeleteBtn').classList.remove('hidden');
        document.getElementById('editorTitle').textContent = docType;
        document.getElementById('editorBadge').classList.remove('hidden');
        amountInput.value = parseInt(target.amount_threshold || 0);
        exSteps = parseLineData(target).map(s => isSlot(s)
            ? { type: 'slot', slot: s.slot, slot_key: s.slot_key || '', role: s.role || '결재' }
            : { type: 'person', id: s.id || null, name: s.name || '', position: s.position || '', dept: s.dept || '', role: s.role || '결재' });
    } else {
        document.getElementById('exId').value = 0;
        document.getElementById('exDeleteBtn').classList.add('hidden');
        document.getElementById('editorTitle').textContent = docType;
        document.getElementById('editorBadge').classList.add('hidden');
        amountInput.value = 0;
        fillDefaultSteps();
    }
    editing = true;
    renderExSteps();
    renderExceptionList();
    document.getElementById('exEmpSearch').value = '';
    renderEmpBrowser('');
    simulate();
    if (window.lucide) lucide.createIcons();
}

function selectDocType(docType) {
    document.getElementById('exDocType').value = docType || '';
    onEditorDocChange();
    document.getElementById('editorSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function editException(row) {
    selectDocType(row.doc_type || '');
}

function resetEditor() {
    exSteps = [];
    document.getElementById('exId').value = 0;
    document.getElementById('exAmountThreshold').value = 0;
    document.getElementById('exDeleteBtn').classList.add('hidden');
    document.getElementById('editorBadge').classList.add('hidden');
    closeEmpDropdown();
    document.getElementById('exEmpSearch').value = '';
    renderEmpBrowser('');
    const docType = document.getElementById('exDocType').value;
    if (docType) {
        onEditorDocChange();
    } else {
        editing = false;
        document.getElementById('editorTitle').textContent = '경로';
        renderExSteps();
        renderExceptionList();
    }
}

/* ───── 편집 ───── */
let exSteps = [];

function renderExSteps() {
    const box = document.getElementById('exStepList');
    const hasDoc = !!document.getElementById('exDocType').value;
    const emptyGuide = !hasDoc
        ? '<div class="flex flex-col items-center justify-center py-6 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50">' +
            '<i data-lucide="file-text" class="w-6 h-6 text-gray-300 mb-1.5"></i>' +
            '<p class="text-[12px] text-gray-400">위에서 문서 종류를 선택하세요</p></div>'
        : '<div class="flex flex-col items-center justify-center py-5 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50">' +
            '<i data-lucide="git-branch" class="w-6 h-6 text-gray-300 mb-1.5"></i>' +
            '<p class="text-[12px] text-gray-400">슬롯이나 직원을 추가하세요</p>' +
            '<p class="text-[11px] text-gray-300">비워두면 기본 경로가 적용돼요</p></div>';

    if (!exSteps.length) {
        box.innerHTML = drafterStartCard() + routeArrow() + emptyGuide;
        if (window.lucide) lucide.createIcons();
        return;
    }
    // 각 step이 자기 앞에 routeArrow()를 포함하므로 drafterStartCard 뒤에는 arrow 안 붙임

    const slotColorPalette = [
        { avatar:'bg-slate-500 text-white',  name:'text-slate-600',  tag:'badge badge-neutral' },
        { avatar:'bg-slate-600 text-white',  name:'text-slate-600',  tag:'badge badge-neutral' },
        { avatar:'bg-slate-500 text-white',  name:'text-slate-600',  tag:'badge badge-neutral' },
        { avatar:'bg-slate-600 text-white',  name:'text-slate-600',  tag:'badge badge-neutral' },
        { avatar:'bg-slate-500 text-white',  name:'text-slate-600',  tag:'badge badge-neutral' },
    ];
    const L = window.ORG_LABELS || {};
    const slotColors = {};
    const enabledLevels = Object.entries(L).filter(([k,v]) => !k.startsWith('_') && v && v.enabled).sort((a,b) => a[1].depth - b[1].depth);
    enabledLevels.forEach(([k, v], i) => { slotColors[v.head] = slotColorPalette[i] || slotColorPalette[slotColorPalette.length - 1]; });
    const defaultSlotColor = { avatar:'bg-slate-500 text-white', name:'text-slate-600', tag:'badge badge-neutral' };
    const len = exSteps.length;
    const stepHtml = exSteps.map((s, i) => {
        const isS = isSlot(s);
        const initial = isS ? (s.slot || '?').charAt(0) : (s.name || '?').charAt(0);
        const sc = isS ? (slotColors[s.slot] || defaultSlotColor) : null;
        const avatarCls = isS ? sc.avatar : 'bg-primary text-white';
        const nameCls = isS ? sc.name : 'text-gray-800';
        const role = s.role || '결재';
        const roleOptions = ['결재','전결','협조','참조'].map(r => `<option value="${r}" ${r === role ? 'selected' : ''}>${r}</option>`).join('');
        const upDisabled = i === 0;
        const downDisabled = i === len - 1;
        const label = isS ? esc(s.slot) : esc(s.name || '-');
        const meta = isS ? '작성자 조직도 기준' : esc([s.dept, s.position].filter(Boolean).join(' · '));
        const tagHtml = isS ? `<span class="ml-1 px-1.5 py-0 text-[10px] font-medium rounded border ${sc.tag}">자동</span>` : '';
        return `<div class="step-unit" data-step="${i}">${routeArrow()}<div class="step-card grid grid-cols-[16px_30px_minmax(0,1fr)_76px_24px_22px] items-center gap-2 px-3 py-2.5 rounded-lg border border-gray-200 bg-white shadow-sm">
            <span class="line-grip cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 flex-shrink-0" title="드래그하여 순서 변경"><i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i></span>
            <span class="w-6 h-6 rounded-full ${avatarCls} flex items-center justify-center text-[11px] font-bold shrink-0">${esc(initial)}</span>
            <div class="min-w-0">
                <div class="flex items-center"><span class="text-sm font-semibold ${nameCls} truncate">${label}</span>${tagHtml}</div>
                ${meta ? `<div class="text-[11px] text-gray-400 truncate">${meta}</div>` : ''}
            </div>
            <select onchange="changeRole(${i}, this.value)" class="h-7 w-[76px] rounded-md border border-gray-300 bg-gray-50 px-2 text-[12px] font-semibold text-gray-700 focus:outline-none focus:border-gray-300">
                ${roleOptions}
            </select>
            <div class="grid grid-rows-2 gap-0.5 justify-items-center">
                <button type="button" onclick="moveStep(${i},-1)" class="inline-flex w-5 h-3.5 items-center justify-center rounded ${upDisabled ? 'text-gray-200 cursor-default' : 'text-gray-400 hover:text-gray-700 hover:bg-gray-100'}" ${upDisabled ? 'disabled' : ''} title="위로"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                <button type="button" onclick="moveStep(${i},1)" class="inline-flex w-5 h-3.5 items-center justify-center rounded ${downDisabled ? 'text-gray-200 cursor-default' : 'text-gray-400 hover:text-gray-700 hover:bg-gray-100'}" ${downDisabled ? 'disabled' : ''} title="아래로"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
            </div>
            <button type="button" onclick="removeExStep(${i})" class="inline-flex w-5 h-5 items-center justify-center text-gray-300 hover:text-red-500 transition-colors" title="삭제"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div></div>`;
    }).join('');

    box.innerHTML = drafterStartCard() + stepHtml;
    if (window.lucide) lucide.createIcons();
    initLineDrag();
}
function addSlot(slot, slotKey) { exSteps.push({ type: 'slot', slot, slot_key: slotKey || '', role: '결재' }); autoSetFinalApproval(); renderExSteps(); }
function autoSetFinalApproval() {
    if (exSteps.length < 2) return;
    exSteps.forEach((s, i) => { if (s.role === '전결') s.role = '결재'; });
    exSteps[exSteps.length - 1].role = '전결';
}
function changeRole(i, value) {
    exSteps[i].role = value;
    renderExSteps();
}
function moveStep(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= exSteps.length) return;
    const oldCardI = document.querySelector(`.step-unit[data-step="${i}"]`);
    const oldCardJ = document.querySelector(`.step-unit[data-step="${j}"]`);
    const rectI = oldCardI?.getBoundingClientRect();
    const rectJ = oldCardJ?.getBoundingClientRect();
    [exSteps[i], exSteps[j]] = [exSteps[j], exSteps[i]];
    renderExSteps();
    requestAnimationFrame(() => {
        const newCardAtI = document.querySelector(`.step-unit[data-step="${i}"]`);
        const newCardAtJ = document.querySelector(`.step-unit[data-step="${j}"]`);
        if (!rectI || !rectJ || !newCardAtI || !newCardAtJ) return;
        const newRectI = newCardAtI.getBoundingClientRect();
        const newRectJ = newCardAtJ.getBoundingClientRect();
        [newCardAtI, newCardAtJ].forEach((card, idx) => {
            const oldTop = idx === 0 ? rectJ.top : rectI.top;
            const newTop = idx === 0 ? newRectI.top : newRectJ.top;
            const dy = oldTop - newTop;
            card.style.transition = 'none';
            card.style.transform = `translateY(${dy}px)`;
            card.style.zIndex = '10';
        });
        requestAnimationFrame(() => {
            [newCardAtI, newCardAtJ].forEach(card => {
                card.style.transition = 'transform .3s cubic-bezier(.22,1,.36,1)';
                card.style.transform = '';
                const cleanup = () => { card.style.zIndex = ''; card.style.transition = ''; };
                card.addEventListener('transitionend', cleanup, { once: true });
                setTimeout(cleanup, 400);
            });
        });
    });
}
function removeExStep(i) { exSteps.splice(i, 1); renderExSteps(); renderEmpBrowser(document.getElementById('exEmpSearch')?.value || ''); }

function renderEmpBrowser(filter) {
    const box = document.getElementById('empDropdown');
    if (!box) return;
    const q = (filter || '').toLowerCase().trim();
    const grouped = {};
    const divOrder = [];
    DEPTS.filter(d => d.parent_id !== null).forEach(d => {
        const emps = EMPLOYEES.filter(e => e.department_id === d.id && (
            !q || (e.name || '').toLowerCase().includes(q) || (d.name || '').toLowerCase().includes(q)
        ));
        if (emps.length) { grouped[d.name] = emps; divOrder.push(d.name); }
    });
    if (!divOrder.length) {
        box.innerHTML = '<div class="flex flex-col items-center py-6 text-center">' +
            '<i data-lucide="search-x" class="w-5 h-5 text-gray-300 mb-1"></i>' +
            '<p class="text-[12px] text-gray-400">' + (q ? '검색 결과가 없어요' : '직원 데이터가 없어요') + '</p></div>';
        if (window.lucide) lucide.createIcons();
        return;
    }
    box.innerHTML = divOrder.map(dept => {
        const emps = grouped[dept];
        return '<div class="border-b border-gray-100 last:border-b-0">' +
            '<div class="px-3 py-1.5 text-[11px] font-semibold text-gray-500 bg-gray-50 sticky top-0 z-10 flex items-center justify-between border-b border-gray-100">' +
                '<span>' + esc(dept) + '</span>' +
                '<span class="text-[10px] text-gray-400 font-normal">' + emps.length + '명</span>' +
            '</div>' +
            emps.map(e => {
                const already = exSteps.some(s => !isSlot(s) && s.id == e.id);
                const initial = (e.name || '?').charAt(0);
                return '<div class="px-3 py-2 flex items-center gap-2.5 text-sm border-b border-gray-50 last:border-b-0 ' +
                    (already ? 'opacity-40' : 'hover:bg-gray-50 cursor-pointer') + '"' +
                    (already ? '' : ' onclick="addExPerson(' + e.id + ')"') + '>' +
                    '<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-200 text-gray-600 text-xs font-bold shrink-0">' + esc(initial) + '</span>' +
                    '<div class="flex-1 min-w-0">' +
                        '<div class="text-sm font-medium text-gray-800 truncate">' + esc(e.name) + '</div>' +
                        '<div class="text-[11px] text-gray-400 truncate">' + esc(e.position || '') + '</div>' +
                    '</div>' +
                    (already ? '<span class="text-[10px] text-gray-400 shrink-0">추가됨</span>' : '<i data-lucide="plus-circle" class="w-4 h-4 text-gray-300 hover:text-gray-600 shrink-0"></i>') +
                '</div>';
            }).join('') + '</div>';
    }).join('');
    if (window.lucide) lucide.createIcons();
}
function addExPerson(id) {
    const e = EMPLOYEES.find(x => x.id == id);
    if (!e) return;
    if (exSteps.some(s => !isSlot(s) && s.id == id)) { alert(e.name + '님은 이미 있어요.'); return; }
    exSteps.push({ type: 'person', id: e.id, name: e.name, position: e.position || '', dept: e.dept_name || '', role: '결재' });
    autoSetFinalApproval();
    renderExSteps();
    document.getElementById('exEmpSearch').value = '';
    closeEmpDropdown();
}

function initLineDrag() {
    const box = document.getElementById('exStepList');
    if (!box) return;

    const OLD_KEY = '__lineDragHandler';
    if (box[OLD_KEY]) box.removeEventListener('pointerdown', box[OLD_KEY]);

    let ds = null;

    const handler = e => {
        const grip = e.target.closest('.line-grip');
        if (!grip) return;
        const unit = grip.closest('.step-unit');
        if (!unit) return;
        const freshUnits = [...box.querySelectorAll('.step-unit')];
        const idx = freshUnits.indexOf(unit);
        if (idx < 0) return;
        e.preventDefault();

        const card = unit.querySelector('.step-card');
        if (!card) return;
        const rect = card.getBoundingClientRect();
        const ox = e.clientX - rect.left, oy = e.clientY - rect.top;
        const origTops = freshUnits.map(u => u.getBoundingClientRect().top);
        const itemH = freshUnits.length > 1 ? origTops[1] - origTops[0] : rect.height;

        const ghost = card.cloneNode(true);
        ghost.className = 'apv-line-drag-ghost';
        ghost.style.cssText = `position:fixed;z-index:9999;pointer-events:none;opacity:.92;
            left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;
            border-radius:8px;border:2px solid var(--zm-primary,#4F6AFF);background:var(--zm-surface-1,#fff);box-shadow:0 8px 24px rgba(0,0,0,.18);`;
        document.body.appendChild(ghost);
        unit.style.opacity = '0';
        box.classList.add('zm-drag-active');

        ds = { fromIdx: idx, gapIdx: idx, origTops, itemH, ghost, unit, ox, oy, units: freshUnits };

        function onMove(ev) {
            if (!ds) return;
            ghost.style.left = (ev.clientX - ox) + 'px';
            ghost.style.top = (ev.clientY - oy) + 'px';
            let newGap = ds.units.length;
            for (let i = 0; i < ds.units.length; i++) {
                if (ev.clientY < ds.origTops[i] + itemH / 2) { newGap = i; break; }
            }
            if (newGap === ds.gapIdx) return;
            ds.gapIdx = newGap;
            ds.units.forEach((u, i) => {
                if (i === idx) return;
                let shift = 0;
                if (idx < newGap && i > idx && i < newGap) shift = -itemH;
                else if (idx > newGap && i >= newGap && i < idx) shift = itemH;
                u.style.transform = shift ? `translateY(${shift}px)` : '';
            });
        }
        function onUp() {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            document.removeEventListener('pointercancel', onUp);
            if (!ds) return;
            if (ghost.isConnected) ghost.remove();
            unit.style.opacity = '';
            box.classList.remove('zm-drag-active');
            ds.units.forEach(u => u.style.transform = '');
            const { fromIdx, gapIdx } = ds;
            ds = null;
            if (gapIdx === fromIdx || gapIdx === fromIdx + 1) return;
            const toIdx = gapIdx > fromIdx ? gapIdx - 1 : gapIdx;
            const [moved] = exSteps.splice(fromIdx, 1);
            exSteps.splice(toIdx, 0, moved);
            autoSetFinalApproval();
            renderExSteps();
        }
        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
        document.addEventListener('pointercancel', onUp);
    };
    box[OLD_KEY] = handler;
    box.addEventListener('pointerdown', handler);
}

function saveException() {
    const id = parseInt(document.getElementById('exId').value) || 0;
    let docType = '';
    if (id) {
        const existing = loadedExceptions.find(l => l.id === id);
        docType = existing ? existing.doc_type : '';
    } else {
        docType = document.getElementById('exDocType').value;
    }
    if (!docType) { alert('문서 종류를 선택하세요.'); return; }
    if (!exSteps.length) { alert('결재 경로에 최소 한 명(또는 슬롯)을 추가하세요.'); return; }
    const amountThreshold = Math.max(0, parseInt(document.getElementById('exAmountThreshold').value) || 0);
    const lineData = exSteps.map(s => isSlot(s)
        ? { type: 'slot', slot: s.slot, slot_key: s.slot_key || '', role: s.role || '결재' }
        : { type: 'person', id: s.id, name: s.name, position: s.position, dept: s.dept, role: s.role || '결재' });
    const nameSuffix = amountThreshold > 0 ? ` (${amountThreshold.toLocaleString()}원~)` : '';
    const payload = { id, name: docType + ' 결재경로' + nameSuffix, department: '', doc_type: docType, line_data: lineData, amount_threshold: amountThreshold };
    fetch(`${API_BASE}?action=saveLine`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    }).then(r => r.json()).then(() => {
        editing = false;
        loadExceptions();
    }).catch(() => alert('저장에 실패했어요.'));
}

async function deleteException(id) {
    const targetId = id || (parseInt(document.getElementById('exId').value) || 0);
    if (!targetId) return;
    if (!(await AppUI.confirm('직접 설정한 경로를 해제하고 기본 경로로 되돌릴까요?'))) return;
    fetch(`${API_BASE}?action=deleteLine`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: targetId })
    }).then(r => r.json()).then(() => {
        editing = false;
        resetEditor();
        loadExceptions();
    }).catch(() => {});
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
