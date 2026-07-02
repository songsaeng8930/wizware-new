<?php
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
$pdo = getDBConnection();
$hasDB = false;
$regulations = [];
$categories = [];

if ($pdo) {
    try {
        // 카테고리 테이블 확인
        $pdo->query('SELECT 1 FROM card_regulation_categories LIMIT 1');
        $categories = $pdo->query('SELECT * FROM card_regulation_categories WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();

        $pdo->query('SELECT 1 FROM card_regulations LIMIT 1');
        $hasDB = true;
        $regulations = $pdo->query('SELECT * FROM card_regulations WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
    } catch (PDOException $e) {
        // 카테고리 테이블 없으면 기본값
        if (empty($categories)) {
            $categories = [
                ['id' => 0, 'name' => '식사', 'color' => 'green'],
                ['id' => 0, 'name' => '여비교통비', 'color' => 'blue'],
                ['id' => 0, 'name' => '영업사업비', 'color' => 'purple'],
                ['id' => 0, 'name' => '구입비', 'color' => 'orange'],
            ];
        }
        $hasDB = false;
    }
}

if (!$hasDB) {
    $regulations = [];
}
?>

<style>
.rule-bl-primary { border-left-color: var(--zm-primary) !important; }
.rule-bl-warm { border-left-color: var(--zm-status-warn-fg) !important; }
.rule-bl-muted { border-left-color: var(--zm-text-subtle) !important; }
.rule-dot-warm-accent { background-color: var(--zm-status-warn-fg); }
.rule-dot-muted { background-color: var(--zm-text-subtle); }
.rule-group-sep { border-top: 2px solid var(--zm-border); }
.rule-del-hover:hover { background-color: var(--zm-amber-bg); }
.rule-del-hover:hover .rule-del-icon { color: var(--zm-status-warn-fg); }
</style>

<!-- 상단 액션 -->
<div class="flex items-center justify-end gap-2 mb-5">
    <button id="btnCatManage" onclick="toggleCatPanel()" class="hidden btn btn-secondary">
        <i data-lucide="settings" class="mr-1 w-4 h-4"></i> 항목 관리
    </button>
    <button id="btnEdit" onclick="toggleEditMode()" class="btn btn-secondary">
        <i data-lucide="pencil" class="mr-1 w-4 h-4"></i> 정보수정
    </button>
    <button id="btnSave" onclick="saveRegulations()" class="hidden px-4 py-2 text-sm font-medium rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors">
        <i data-lucide="check" class="mr-1 w-4 h-4"></i> 저장하기
    </button>
    <button id="btnCancel" onclick="cancelEdit()" class="hidden btn btn-secondary">
        취소
    </button>
</div>

<!-- 카테고리 관리 패널 -->
<div id="catPanel" class="hidden mb-5 bg-slate-900 rounded-xl border border-slate-800">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800 bg-slate-950/80">
        <h3 class="text-sm font-semibold text-slate-200">항목 관리</h3>
        <button onclick="toggleCatPanel()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <div class="p-5">
        <!-- 기존 카테고리 목록 -->
        <div id="catList" class="space-y-2 mb-4"></div>
        <!-- 새 카테고리 추가 -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-800">
            <input type="text" id="newCatName" placeholder="새 항목 이름" class="flex-1 px-3 py-2 text-sm border border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300">
            <div id="newCatColorPicker" class="relative">
                <button onclick="toggleColorDropdown('new')" id="newColorBtn" class="flex items-center gap-2 px-3 py-2 text-sm border border-slate-800 rounded-lg hover:bg-slate-950">
                    <span class="w-4 h-4 rounded-full rule-dot-muted" id="newColorPreview"></span>
                    <span class="text-slate-300" id="newColorLabel">회색</span>
                    <i data-lucide="chevron-down" class="w-3 h-3 text-slate-500"></i>
                </button>
                <div id="colorDropdown-new" class="hidden absolute top-full left-0 mt-1 bg-slate-900 rounded-lg border border-slate-800 shadow-lg z-50 p-2 w-44"></div>
            </div>
            <button onclick="addCategory()" class="px-4 py-2 text-sm font-medium rounded-lg border border-dashed border-primary text-primary hover:bg-gray-100 transition-colors">
                <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 추가
            </button>
        </div>
    </div>
</div>

<!-- 규정 테이블 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full emp-table" id="regTable">
            <thead>
                <tr class="border-b border-slate-800">
                    <th class="px-5 py-3 text-left w-[180px]">항목</th>
                    <th class="px-5 py-3 text-left w-[160px]">세부항목</th>
                    <th class="px-5 py-3 text-right w-[120px]">한도</th>
                    <th class="px-5 py-3 text-left w-[220px]">입력시 필수사항</th>
                    <th class="px-5 py-3 text-left">세부항목 가이드</th>
                </tr>
            </thead>
            <tbody id="regTableBody">
            </tbody>
        </table>
    </div>
</div>

<!-- 수정모드: 행 추가 -->
<div id="addRowArea" class="hidden mt-3 flex gap-2">
    <select id="newCategory" class="reg-select w-40"></select>
    <button onclick="addRow()" class="px-4 py-2 text-sm font-medium rounded-lg border border-dashed border-primary text-primary hover:bg-gray-100 transition-colors">
        <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 규정 추가
    </button>
</div>

<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
let regulations = <?= json_encode(array_values($regulations), JSON_UNESCAPED_UNICODE) ?>;
let categories = <?= json_encode(array_values($categories), JSON_UNESCAPED_UNICODE) ?>;
let isEditMode = false;
let originalData = null;
let originalCategories = null;
let selectedNewColor = 'gray';

const COLORS = [
    { key: 'green', label: '초록', dot: 'bg-amber-100', bg: 'bg-amber-50', border: 'rule-bl-warm' },
    { key: 'blue', label: '파랑', dot: 'bg-primary', bg: 'bg-primary-light', border: 'rule-bl-primary' },
    { key: 'purple', label: '보라', dot: 'bg-primary', bg: 'bg-primary-light', border: 'rule-bl-primary' },
    { key: 'orange', label: '주황', dot: 'bg-amber-100', bg: 'bg-amber-50', border: 'rule-bl-warm' },
    { key: 'red', label: '빨강', dot: 'bg-amber-100', bg: 'bg-amber-50', border: 'rule-bl-warm' },
    { key: 'pink', label: '분홍', dot: 'bg-amber-100', bg: 'bg-amber-50', border: 'rule-bl-warm' },
    { key: 'teal', label: '청록', dot: 'bg-primary', bg: 'bg-primary-light', border: 'rule-bl-primary' },
    { key: 'amber', label: '노랑', dot: 'rule-dot-warm-accent', bg: 'bg-amber-50', border: 'rule-bl-warm' },
    { key: 'indigo', label: '남색', dot: 'bg-primary', bg: 'bg-primary-light', border: 'rule-bl-primary' },
    { key: 'gray', label: '회색', dot: 'rule-dot-muted', bg: 'bg-slate-950', border: 'rule-bl-muted' },
];

document.addEventListener('DOMContentLoaded', () => {
    renderTable();
    renderCatSelect();
});

// 색상 헬퍼 (DB 카테고리 기반)
function getColorObj(cat) {
    const c = categories.find(c => c.name === cat);
    const colorKey = c ? c.color : 'gray';
    return COLORS.find(cl => cl.key === colorKey) || COLORS[9];
}
function getCatColor(cat) { return getColorObj(cat).dot; }
function getCatBg(cat) { return getColorObj(cat).bg; }
function getCatBorderColor(cat) { return getColorObj(cat).border; }

// ===== 테이블 렌더링 =====
function renderTable() {
    const tbody = document.getElementById('regTableBody');
    if (regulations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400 text-sm">등록된 규정이 없습니다.</td></tr>';
        return;
    }

    const grouped = {};
    regulations.forEach((r, idx) => {
        if (!grouped[r.category]) grouped[r.category] = [];
        grouped[r.category].push({...r, _regIdx: idx});
    });

    let html = '';
    let isFirst = true;
    for (const [cat, items] of Object.entries(grouped)) {
        items.forEach((r, i) => {
            const groupSeparator = (i === 0 && !isFirst) ? 'rule-group-sep' : '';
            html += `<tr class="border-b border-slate-800 hover:bg-slate-950/60 transition-colors ${groupSeparator}" data-id="${r.id || 0}" data-reg-idx="${r._regIdx}">`;
            if (i === 0) {
                html += `<td class="px-5 py-3.5 text-sm align-middle ${getCatBg(cat)} border-l-4 ${getCatBorderColor(cat)}" rowspan="${items.length}">
                    <span class="inline-flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full ${getCatColor(cat)} flex-shrink-0"></span>
                        <span class="font-semibold text-slate-100">${esc(cat)}</span>
                    </span>
                </td>`;
            }
            if (isEditMode) {
                html += `<td class="px-5 py-3"><input type="text" class="reg-input-sm" value="${esc(r.sub_category)}" data-field="sub_category"></td>`;
                html += `<td class="px-5 py-3"><input type="text" class="reg-input-sm text-right" value="${r.limit_amount > 0 ? Number(r.limit_amount).toLocaleString() : '실비'}" data-field="limit_amount"></td>`;
                html += `<td class="px-5 py-3"><input type="text" class="reg-input-sm" value="${esc(r.required_fields)}" data-field="required_fields"></td>`;
                html += `<td class="px-5 py-3 flex items-center gap-2">
                    <input type="text" class="reg-input-sm flex-1" value="${esc(r.guide)}" data-field="guide">
                    <button onclick="removeRow(this)" class="shrink-0 rule-del-hover rounded p-0.5" style="color:var(--zm-status-warn-fg)"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                </td>`;
            } else {
                html += `<td class="px-5 py-3 text-sm text-slate-200">${esc(r.sub_category)}</td>`;
                html += `<td class="px-5 py-3 text-sm text-right font-medium ${r.limit_amount > 0 ? 'text-slate-100' : 'text-primary'}">${r.limit_amount > 0 ? Number(r.limit_amount).toLocaleString() + '원' : '실비'}</td>`;
                html += `<td class="px-5 py-3 text-sm text-slate-300">${esc(r.required_fields)}</td>`;
                html += `<td class="px-5 py-3 text-sm text-slate-400">${esc(r.guide)}</td>`;
            }
            html += '</tr>';
        });
        isFirst = false;
    }
    tbody.innerHTML = html;
}

// ===== 수정모드 =====
function toggleEditMode() {
    isEditMode = true;
    originalData = JSON.parse(JSON.stringify(regulations));
    originalCategories = JSON.parse(JSON.stringify(categories));
    document.getElementById('btnEdit').classList.add('hidden');
    document.getElementById('btnSave').classList.remove('hidden');
    document.getElementById('btnCancel').classList.remove('hidden');
    document.getElementById('btnCatManage').classList.remove('hidden');
    document.getElementById('addRowArea').classList.remove('hidden');
    renderTable();
    renderCatSelect();
}

function cancelEdit() {
    isEditMode = false;
    regulations = originalData;
    categories = originalCategories;
    document.getElementById('btnEdit').classList.remove('hidden');
    document.getElementById('btnSave').classList.add('hidden');
    document.getElementById('btnCancel').classList.add('hidden');
    document.getElementById('btnCatManage').classList.add('hidden');
    document.getElementById('addRowArea').classList.add('hidden');
    document.getElementById('catPanel').classList.add('hidden');
    renderTable();
    renderCatSelect();
}

function addRow() {
    const cat = document.getElementById('newCategory').value;
    regulations.push({id: 0, category: cat, sub_category: '', limit_amount: 0, required_fields: '', guide: ''});
    renderTable();
}

function removeRow(btn) {
    const tr = btn.closest('tr');
    const regIdx = parseInt(tr.dataset.regIdx);
    if (!isNaN(regIdx) && regIdx >= 0 && regIdx < regulations.length) {
        regulations.splice(regIdx, 1);
    }
    renderTable();
}

function saveRegulations() {
    const rows = document.querySelectorAll('#regTableBody tr[data-id]');
    regulations.forEach((r, i) => {
        const row = rows[i];
        if (!row) return;
        const inputs = row.querySelectorAll('input[data-field]');
        inputs.forEach(inp => {
            const field = inp.dataset.field;
            if (field === 'limit_amount') {
                const val = inp.value.replace(/[^\d]/g, '');
                r[field] = val ? parseInt(val) : 0;
            } else {
                r[field] = inp.value;
            }
        });
    });

    const updated = regulations.filter(r => r.sub_category && r.sub_category.trim());

    if (HAS_DB) {
        fetch(`${API_BASE}?action=saveRegulations`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({regulations: updated})
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            alert('저장되었습니다.');
            location.reload();
        });
    } else {
        alert('DB 연결이 필요합니다.');
    }
}

// ===== 카테고리 관리 =====
function toggleCatPanel() {
    const panel = document.getElementById('catPanel');
    panel.classList.toggle('hidden');
    if (!panel.classList.contains('hidden')) {
        renderCatList();
    }
}

function renderCatList() {
    const container = document.getElementById('catList');
    if (categories.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-400">등록된 항목이 없습니다.</p>';
        return;
    }
    let html = '';
    categories.forEach((cat, idx) => {
        const colorObj = COLORS.find(c => c.key === cat.color) || COLORS[9];
        html += `<div class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-slate-950" data-cat-idx="${idx}">
            <span class="w-3 h-3 rounded-full ${colorObj.dot} flex-shrink-0"></span>
            <span class="text-sm font-medium text-slate-100 flex-1">${esc(cat.name)}</span>
            <div class="relative">
                <button onclick="toggleColorDropdown(${idx})" class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm border border-slate-800 rounded-lg hover:bg-slate-950">
                    <span class="w-3 h-3 rounded-full ${colorObj.dot}"></span>
                    <span class="text-slate-300">${colorObj.label}</span>
                    <i data-lucide="chevron-down" class="w-3 h-3 text-slate-500"></i>
                </button>
                <div id="colorDropdown-${idx}" class="hidden absolute top-full right-0 mt-1 bg-slate-900 rounded-lg border border-slate-800 shadow-lg z-50 p-2 w-44"></div>
            </div>
            <button onclick="deleteCategory(${idx})" class="p-1.5 text-slate-500 rounded-lg rule-del-hover transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5 rule-del-icon"></i>
            </button>
        </div>`;
    });
    container.innerHTML = html;
}

function renderCatSelect() {
    const sel = document.getElementById('newCategory');
    sel.innerHTML = categories.map(c => `<option value="${esc(c.name)}">${esc(c.name)}</option>`).join('');
}

// ===== 색상 드롭다운 =====
function toggleColorDropdown(target) {
    // 다른 열린 드롭다운 닫기
    document.querySelectorAll('[id^="colorDropdown-"]').forEach(dd => {
        if (dd.id !== `colorDropdown-${target}`) dd.classList.add('hidden');
    });

    const dd = document.getElementById(`colorDropdown-${target}`);
    dd.classList.toggle('hidden');

    if (!dd.classList.contains('hidden')) {
        let html = '<div class="grid grid-cols-2 gap-1">';
        COLORS.forEach(c => {
            html += `<button onclick="selectColor('${target}', '${c.key}')" class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-800 transition-colors">
                <span class="w-3.5 h-3.5 rounded-full ${c.dot}"></span>
                <span class="text-sm text-slate-200">${c.label}</span>
            </button>`;
        });
        html += '</div>';
        dd.innerHTML = html;
    }
}

function selectColor(target, colorKey) {
    document.getElementById(`colorDropdown-${target}`).classList.add('hidden');

    if (target === 'new') {
        selectedNewColor = colorKey;
        const colorObj = COLORS.find(c => c.key === colorKey);
        document.getElementById('newColorPreview').className = `w-4 h-4 rounded-full ${colorObj.dot}`;
        document.getElementById('newColorLabel').textContent = colorObj.label;
    } else {
        const idx = parseInt(target);
        const cat = categories[idx];
        if (!cat) return;

        const oldName = cat.name;
        cat.color = colorKey;

        // API 호출로 저장
        if (HAS_DB && cat.id > 0) {
            fetch(`${API_BASE}?action=saveCategory`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: cat.id, name: cat.name, color: colorKey, old_name: oldName })
            })
            .then(r => r.json())
            .then(res => {
                if (res.error) { alert(res.error); return; }
                renderCatList();
                renderTable();
            });
        } else {
            renderCatList();
            renderTable();
        }
    }
}

// 카테고리 추가
function addCategory() {
    const nameInput = document.getElementById('newCatName');
    const name = nameInput.value.trim();
    if (!name) { alert('항목 이름을 입력해주세요.'); nameInput.focus(); return; }

    // 중복 체크 (클라이언트)
    if (categories.find(c => c.name === name)) {
        alert(`'${name}' 항목이 이미 존재합니다.`);
        return;
    }

    if (HAS_DB) {
        fetch(`${API_BASE}?action=saveCategory`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name, color: selectedNewColor })
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            categories.push(res.category);
            nameInput.value = '';
            renderCatList();
            renderCatSelect();
            renderTable();
        });
    } else {
        categories.push({ id: 0, name, color: selectedNewColor });
        nameInput.value = '';
        renderCatList();
        renderCatSelect();
        renderTable();
    }
}

// 카테고리 삭제
async function deleteCategory(idx) {
    const cat = categories[idx];
    if (!cat) return;

    // 규정에서 사용 중인지 클라이언트 체크
    const usedCount = regulations.filter(r => r.category === cat.name).length;
    if (usedCount > 0) {
        alert(`'${cat.name}' 항목에 ${usedCount}개 규정이 있어서 삭제할 수 없습니다.\n규정을 먼저 삭제해주세요.`);
        return;
    }

    if (!(await AppUI.confirm(`'${cat.name}' 항목을 삭제하시겠습니까?`))) return;

    if (HAS_DB && cat.id > 0) {
        fetch(`${API_BASE}?action=deleteCategory`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: cat.id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            categories.splice(idx, 1);
            renderCatList();
            renderCatSelect();
            renderTable();
        });
    } else {
        categories.splice(idx, 1);
        renderCatList();
        renderCatSelect();
        renderTable();
    }
}

// 바깥 클릭 시 색상 드롭다운 닫기
document.addEventListener('click', e => {
    if (!e.target.closest('[id^="colorDropdown-"]') && !e.target.closest('[onclick*="toggleColorDropdown"]')) {
        document.querySelectorAll('[id^="colorDropdown-"]').forEach(dd => dd.classList.add('hidden'));
    }
});

function esc(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>
