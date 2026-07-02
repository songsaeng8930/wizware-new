<?php
$pageTitle = '법인카드규정';
$currentPage = 'card';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$regulations = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM card_regulations LIMIT 1');
        $hasDB = true;
        $regulations = $pdo->query('SELECT *, IFNULL(use_in_register, 1) AS use_in_register FROM card_regulations WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
    } catch (PDOException $e) { $hasDB = false; }
}

if (!$hasDB) {
    $regulations = [
        ['id'=>1,'category'=>'식사','sub_category'=>'중식/석식','limit_amount'=>15000,'required_fields'=>'영수증, 참석자명단','guide'=>'1인당 15,000원 이내. 4인 이상 시 사전 승인 필요'],
        ['id'=>2,'category'=>'식사','sub_category'=>'회식','limit_amount'=>50000,'required_fields'=>'영수증, 참석자명단, 사유서','guide'=>'1인당 50,000원 이내. 팀장 사전승인 필수'],
        ['id'=>3,'category'=>'식사','sub_category'=>'간식/음료','limit_amount'=>10000,'required_fields'=>'영수증','guide'=>'1인당 10,000원 이내'],
        ['id'=>4,'category'=>'여비교통비','sub_category'=>'시내교통','limit_amount'=>0,'required_fields'=>'영수증, 출발/도착지','guide'=>'실비 정산. 택시 이용 시 사유 기재 필수'],
        ['id'=>5,'category'=>'여비교통비','sub_category'=>'출장교통','limit_amount'=>0,'required_fields'=>'영수증, 출장보고서','guide'=>'실비 정산. KTX 이상 시 사전승인 필요'],
        ['id'=>6,'category'=>'여비교통비','sub_category'=>'주차비/톨비','limit_amount'=>0,'required_fields'=>'영수증','guide'=>'실비 정산'],
        ['id'=>7,'category'=>'영업사업비','sub_category'=>'거래처 접대','limit_amount'=>100000,'required_fields'=>'영수증, 접대보고서','guide'=>'1건당 100,000원 이내. 초과 시 부서장 승인'],
        ['id'=>8,'category'=>'영업사업비','sub_category'=>'경조사비','limit_amount'=>50000,'required_fields'=>'경조사 증빙','guide'=>'건당 50,000원. 경조사 규정 참조'],
        ['id'=>9,'category'=>'영업사업비','sub_category'=>'선물/기념품','limit_amount'=>30000,'required_fields'=>'영수증, 사유서','guide'=>'1건당 30,000원 이내'],
        ['id'=>10,'category'=>'구입비','sub_category'=>'사무용품','limit_amount'=>50000,'required_fields'=>'영수증, 구매요청서','guide'=>'건당 50,000원 이내. 초과 시 구매부서 경유'],
        ['id'=>11,'category'=>'구입비','sub_category'=>'소프트웨어','limit_amount'=>0,'required_fields'=>'영수증, 구매요청서, 라이선스 정보','guide'=>'IT부서 사전 승인 필수'],
        ['id'=>12,'category'=>'구입비','sub_category'=>'장비/비품','limit_amount'=>0,'required_fields'=>'영수증, 구매요청서, 자산등록','guide'=>'50만원 이상 시 자산등록 필수'],
    ];
}

$grouped = [];
foreach ($regulations as $r) {
    $grouped[$r['category']][] = $r;
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-100">법인카드규정</h2>
                <p class="text-sm text-slate-300 mt-1">법인카드 사용 규정 및 한도를 관리합니다.</p>
            </div>
            <div class="flex gap-2">
                <button id="btnAddRule" onclick="openAddModal()" class="px-4 py-2 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                    <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 규칙 추가
                </button>
                <button id="btnEdit" onclick="toggleEditMode()" class="btn btn-secondary">
                    <i data-lucide="pencil" class="mr-1 w-4 h-4"></i> 정보수정
                </button>
                <button id="btnSave" onclick="saveRegulations()" class="hidden reg-save-btn px-4 py-2 text-sm font-semibold rounded-lg transition-colors">
                    <i data-lucide="check" class="mr-1 w-4 h-4"></i> 저장하기
                </button>
                <button id="btnCancel" onclick="cancelEdit()" class="hidden btn btn-secondary">
                    취소
                </button>
            </div>
        </div>

        <!-- 필터 -->
        <div class="flex items-center gap-3 mb-4">
            <label class="text-sm text-slate-400">항목 필터</label>
            <select id="catFilter" onchange="filterByCategory()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm min-w-[160px]">
                <option value="">전체 항목</option>
            </select>
            <span class="text-xs text-slate-500 ml-2" id="regCount"></span>
        </div>

        <!-- 규정 카드 컨테이너 -->
        <div class="space-y-4" id="regContainer"></div>

        <!-- 수정모드: 행 추가 -->
        <div id="addRowArea" class="hidden mt-3 flex gap-2">
            <select id="newCategory" class="reg-select w-40"></select>
            <button onclick="addRow()" class="px-4 py-2 text-sm font-medium rounded-lg border border-dashed border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors">
                <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 규정 추가
            </button>
        </div>
    </main>
</div>

<!-- 규칙 추가 모달 -->
<div id="addRegModal" class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeAddModal()">
    <div class="add-reg-modal-box rounded-2xl shadow-xl w-full max-w-lg border">
        <div class="px-6 py-4 border-b flex items-center justify-between add-reg-modal-header">
            <h3 class="text-base font-bold">규칙 추가</h3>
            <button onclick="closeAddModal()" class="add-reg-modal-close hover:opacity-70 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1.5 add-reg-label">항목 <span class="text-rose-400">*</span></label>
                <select id="addRegCat" class="reg-select w-full" onchange="toggleNewCatInput()"></select>
                <input id="addRegCatNew" type="text" class="reg-input-sm mt-2 hidden" placeholder="새 항목명 입력">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 add-reg-label">세부항목 <span class="text-rose-400">*</span></label>
                <input id="addRegSub" type="text" class="reg-input-sm" placeholder="예) 중식/석식">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 add-reg-label">한도 (원)</label>
                <input id="addRegLimit" type="text" class="reg-input-sm" placeholder="실비인 경우 비워두세요" oninput="formatAmountInput(this)">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 add-reg-label">필수증빙</label>
                <input id="addRegReq" type="text" class="reg-input-sm" placeholder="쉼표로 구분 (예: 영수증, 참석자명단)">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5 add-reg-label">가이드</label>
                <textarea id="addRegGuide" rows="2" class="reg-input-sm" style="resize:vertical" placeholder="사용 시 참고할 규정을 입력하세요"></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end gap-2 add-reg-modal-footer">
            <button onclick="closeAddModal()" class="btn btn-secondary">취소</button>
            <button onclick="saveNewRule()" class="px-5 py-2 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-colors">추가</button>
        </div>
    </div>
</div>

<style>
.reg-input-sm {
    width: 100%;
    padding: 6px 10px;
    font-size: 13px;
    border: 1px solid var(--zm-border);
    border-radius: 6px;
    background: var(--zm-surface-2);
    color: var(--zm-text-default);
    outline: none;
    transition: border-color .15s;
}
.reg-input-sm:focus {
    border-color: var(--zm-surface-3);
    box-shadow: 0 0 0 2px rgba(0,0,0,.08);
}
.reg-select {
    padding: 6px 10px;
    font-size: 13px;
    border: 1px solid var(--zm-border);
    border-radius: 6px;
    background: var(--zm-surface-2);
    color: var(--zm-text-default);
}
.reg-save-btn {
    color: #fff !important;
    background: var(--zm-primary) !important;
    border: 1px solid var(--zm-primary) !important;
    box-shadow: 0 8px 22px rgba(0,0,0,.06);
}
.reg-save-btn:hover {
    background: #3f56e8 !important;
    border-color: #3f56e8 !important;
    box-shadow: 0 10px 26px rgba(0,0,0,.08);
}
.reg-save-btn svg,
.reg-save-btn i {
    color: #fff !important;
    stroke: #fff !important;
}
.reg-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}
.reg-toggle input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.reg-toggle-track {
    display: block;
    width: 36px;
    height: 20px;
    background: #475569;
    border-radius: 10px;
    position: relative;
    transition: background .2s;
}
.reg-toggle-track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    transition: transform .2s;
}
.reg-toggle input:checked + .reg-toggle-track {
    background: var(--zm-primary);
}
.reg-toggle input:checked + .reg-toggle-track::after {
    transform: translateX(16px);
}
.reg-row {
    display: grid;
    grid-template-columns: 140px 90px 1fr 2fr 52px;
    align-items: center;
    gap: 16px;
    padding: 11px 20px;
    border-bottom: 1px solid rgba(51,65,85,.4);
    transition: background .1s;
}
.reg-row:last-child { border-bottom: none; }
.reg-row:hover { background: rgba(15,23,42,.5); }
.reg-row-head {
    display: grid;
    grid-template-columns: 140px 90px 1fr 2fr 52px;
    gap: 16px;
    padding: 8px 20px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
    position: relative;
    z-index: 30;
    overflow: visible;
}
.reg-help-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 15px;
    height: 15px;
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    border: 1px solid #475569;
    border-radius: 50%;
    cursor: help;
    position: relative;
    z-index: 40;
    vertical-align: middle;
    margin-left: 2px;
}
.reg-help-trigger:hover,
.reg-help-trigger:focus {
    color: #e2e8f0;
    border-color: var(--zm-surface-3);
}
.reg-help-tooltip {
    display: none;
    position: absolute;
    bottom: calc(100% + 8px);
    right: -8px;
    width: 220px;
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.6;
    color: #e2e8f0;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.4);
    text-transform: none;
    letter-spacing: 0;
    white-space: normal;
    text-align: left;
    z-index: 60;
}
.reg-help-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    right: 14px;
    border: 6px solid transparent;
    border-top-color: #334155;
}
.reg-help-trigger:hover .reg-help-tooltip,
.reg-help-trigger:focus .reg-help-tooltip {
    display: block;
}
.req-tag {
    display: inline-block;
    padding: 1px 6px;
    font-size: 11px;
    background: #1e293b;
    color: #94a3b8;
    border-radius: 4px;
    border: 1px solid #334155;
    white-space: nowrap;
}

/* ── 화이트 테마: 법인카드규정 가시성 보강 ── */
/* #btnEdit, #btnCancel 은 .btn.btn-secondary 클래스가 양 테마를 자동 처리 */
html[data-theme="light"] #btnSave {
    background: var(--zm-primary);
    color: #ffffff;
    border: 1px solid var(--zm-primary);
    box-shadow: 0 6px 14px rgba(0, 0, 0, .06);
}
html[data-theme="light"] #btnSave:hover {
    background: #3f57e6;
    border-color: #3f57e6;
}
html[data-theme="light"] #addRowArea button {
    background: #eef2ff;
    border-color: #a5b4fc;
    color: #3f57e6;
}
html[data-theme="light"] #addRowArea button:hover {
    background: #e0e7ff;
    border-color: #818cf8;
    color: #1d4ed8;
}
html[data-theme="light"] .reg-row {
    border-bottom-color: #d1d5db;
}
html[data-theme="light"] .reg-row:hover {
    background: #f8fafc;
}
html[data-theme="light"] .reg-row-head {
    color: #475569;
}
html[data-theme="light"] .reg-help-trigger {
    color: #64748b;
    border-color: #cbd5e1;
    background: #ffffff;
}
html[data-theme="light"] .reg-help-trigger:hover,
html[data-theme="light"] .reg-help-trigger:focus {
    color: var(--zm-text-default);
    border-color: var(--zm-surface-3);
}
html[data-theme="light"] .req-tag {
    background: #eef2ff;
    color: #3046c7;
    border-color: #c7d2fe;
    font-weight: 600;
}
html[data-theme="light"] .reg-input-sm,
html[data-theme="light"] .reg-select {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #111827;
}
html[data-theme="light"] .reg-input-sm:focus,
html[data-theme="light"] .reg-select:focus {
    border-color: var(--zm-surface-3);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, .08);
}
html[data-theme="light"] .reg-toggle-track {
    background: #cbd5e1;
    border: 1px solid #94a3b8;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, .08);
}
html[data-theme="light"] .reg-toggle-track::after {
    top: 1px;
    left: 1px;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .18);
}
html[data-theme="light"] .reg-toggle input:checked + .reg-toggle-track {
    background: var(--zm-primary);
    border-color: var(--zm-primary);
}
/* ── 규칙 추가 모달 ── */
.add-reg-modal-box {
    background: #0f172a;
    border-color: #1e293b;
}
.add-reg-modal-header {
    border-color: #1e293b;
    color: #f1f5f9;
}
.add-reg-modal-close { color: #94a3b8; }
.add-reg-label { color: #cbd5e1; }
.add-reg-modal-footer { border-color: #1e293b; }
html[data-theme="light"] .add-reg-modal-box {
    background: #ffffff;
    border-color: #e2e8f0;
}
html[data-theme="light"] .add-reg-modal-header {
    border-color: #e2e8f0;
    color: #1e293b;
}
html[data-theme="light"] .add-reg-modal-close { color: #64748b; }
html[data-theme="light"] .add-reg-label { color: #475569; }
html[data-theme="light"] .add-reg-modal-footer { border-color: #e2e8f0; }
</style>

<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
let regulations = <?= json_encode(array_values($regulations), JSON_UNESCAPED_UNICODE) ?>;
let isEditMode = false;
let originalData = null;
let currentFilter = '';

const CAT_COLORS = {
    '식사': '#f59e0b', '식사/회식': '#f59e0b',
    '여비교통비': '#3b82f6',
    '영업사업비': '#10b981', '접대/영업': '#10b981',
    '경조사비': '#8b5cf6',
    '구입비': '#a78bfa',
    '통신비': '#06b6d4',
    '교육훈련비': '#f472b6',
};

document.addEventListener('DOMContentLoaded', () => {
    buildFilterDropdown();
    render();
});

function buildFilterDropdown() {
    const cats = [...new Set(regulations.map(r => r.category))];
    const sel = document.getElementById('catFilter');
    const newSel = document.getElementById('newCategory');
    cats.forEach(cat => {
        sel.appendChild(new Option(cat, cat));
        newSel.appendChild(new Option(cat, cat));
    });
}

function filterByCategory() {
    currentFilter = document.getElementById('catFilter').value;
    render();
}

function getFiltered() {
    if (!currentFilter) return regulations;
    return regulations.filter(r => r.category === currentFilter);
}

function render() {
    const container = document.getElementById('regContainer');
    const filtered = getFiltered();

    if (!filtered.length) {
        container.innerHTML = '<div class="text-center py-16 text-slate-400 text-sm">등록된 규정이 없습니다.</div>';
        document.getElementById('regCount').textContent = '';
        return;
    }

    document.getElementById('regCount').textContent = filtered.length + '개 항목';

    const grouped = {};
    filtered.forEach(r => {
        if (!grouped[r.category]) grouped[r.category] = [];
        grouped[r.category].push(r);
    });

    let html = '';
    for (const [cat, items] of Object.entries(grouped)) {
        const color = CAT_COLORS[cat] || '#64748b';
        html += `<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-visible">`;
        html += `<div class="px-4 py-3 flex items-center gap-3 border-b border-slate-800/60" style="background:linear-gradient(90deg,${color}08,transparent)">
            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${color}"></span>
            <span class="text-sm font-bold text-slate-100">${esc(cat)}</span>
            <span class="text-xs text-slate-500">${items.length}건</span>
        </div>`;

        html += `<div class="reg-row-head"><span>세부항목</span><span class="text-right">한도</span><span>필수증빙</span><span>가이드</span><span class="text-center">노출 <span class="reg-help-trigger" tabindex="0">?<span class="reg-help-tooltip">이 항목이 직원의 지출 등록 화면에<br>카테고리로 노출되는지 여부입니다.<br>OFF로 변경하면 해당 항목이<br>등록 화면에서 숨겨집니다.</span></span></span></div>`;

        items.forEach(r => {
            const regOn = r.use_in_register == 1;
            const regIdx = regulations.indexOf(r);
            html += `<div class="reg-row" data-id="${r.id || 0}" data-cat="${esc(r.category)}" data-reg-idx="${regIdx}">`;
            if (isEditMode) {
                html += `<div><input type="text" class="reg-input-sm" value="${esc(r.sub_category)}" data-field="sub_category"></div>`;
                html += `<div><input type="text" class="reg-input-sm text-right tabular-nums" inputmode="numeric" value="${r.limit_amount > 0 ? Number(r.limit_amount).toLocaleString() : '실비'}" data-field="limit_amount" oninput="formatAmountInput(this)" onfocus="clearActualAmount(this)" placeholder="실비"></div>`;
                html += `<div><input type="text" class="reg-input-sm" value="${esc(r.required_fields)}" data-field="required_fields"></div>`;
                html += `<div class="flex items-center gap-2"><input type="text" class="reg-input-sm flex-1" value="${esc(r.guide)}" data-field="guide"><button onclick="removeRow(this)" class="text-rose-400 hover:text-rose-300 shrink-0" title="삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></div>`;
                html += `<div class="text-center"><label class="reg-toggle"><input type="checkbox" data-field="use_in_register" ${regOn ? 'checked' : ''}><span class="reg-toggle-track"></span></label></div>`;
            } else {
                html += `<div class="text-sm font-medium text-slate-200">${esc(r.sub_category)}</div>`;
                html += `<div class="text-sm text-right tabular-nums ${r.limit_amount > 0 ? 'font-medium text-slate-100' : 'text-slate-400'}">${r.limit_amount > 0 ? Number(r.limit_amount).toLocaleString() + '원' : '실비'}</div>`;
                html += `<div class="flex flex-wrap gap-1">${formatReq(r.required_fields)}</div>`;
                html += `<div class="text-sm text-slate-400 leading-relaxed">${esc(r.guide)}</div>`;
                html += `<div class="text-center"><span class="inline-block w-2 h-2 rounded-full ${regOn ? 'bg-emerald-400' : 'bg-slate-600'}" title="${regOn ? '노출' : '비노출'}"></span></div>`;
            }
            html += `</div>`;
        });
        html += `</div>`;
    }
    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function formatReq(str) {
    if (!str) return '';
    return str.split(',').map(s => s.trim()).filter(Boolean)
        .map(s => `<span class="req-tag">${esc(s)}</span>`).join('');
}

function toggleEditMode() {
    isEditMode = true;
    originalData = JSON.parse(JSON.stringify(regulations));
    document.getElementById('btnEdit').classList.add('hidden');
    document.getElementById('btnSave').classList.remove('hidden');
    document.getElementById('btnCancel').classList.remove('hidden');
    document.getElementById('addRowArea').classList.remove('hidden');
    render();
}

function cancelEdit() {
    isEditMode = false;
    regulations = originalData;
    document.getElementById('btnEdit').classList.remove('hidden');
    document.getElementById('btnSave').classList.add('hidden');
    document.getElementById('btnCancel').classList.add('hidden');
    document.getElementById('addRowArea').classList.add('hidden');
    render();
}

function addRow() {
    const cat = document.getElementById('newCategory').value;
    regulations.push({id: 0, category: cat, sub_category: '', limit_amount: 0, required_fields: '', guide: '', use_in_register: 1});
    render();
}

function removeRow(btn) {
    const row = btn.closest('.reg-row');
    const regIdx = parseInt(row.dataset.regIdx);
    if (!isNaN(regIdx) && regIdx >= 0 && regIdx < regulations.length) {
        regulations.splice(regIdx, 1);
    }
    render();
}

function formatAmountInput(input) {
    const digits = input.value.replace(/[^\d]/g, '');
    input.value = digits ? Number(digits).toLocaleString('ko-KR') : '';
}

function clearActualAmount(input) {
    if (input.value.trim() === '실비') input.value = '';
}

function saveRegulations() {
    const rows = document.querySelectorAll('.reg-row[data-reg-idx]');
    const updated = [];

    rows.forEach(row => {
        const r = regulations[parseInt(row.dataset.regIdx)];
        if (!r) return;
        row.querySelectorAll('[data-field]').forEach(inp => {
            const field = inp.dataset.field;
            if (field === 'limit_amount') {
                const val = inp.value.replace(/[^\d]/g, '');
                r[field] = val ? parseInt(val) : 0;
            } else if (field === 'use_in_register') {
                r[field] = inp.checked ? 1 : 0;
            } else {
                r[field] = inp.value;
            }
        });
        if (r.sub_category.trim()) updated.push(r);
    });

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
        regulations = updated;
        isEditMode = false;
        document.getElementById('btnEdit').classList.remove('hidden');
        document.getElementById('btnSave').classList.add('hidden');
        document.getElementById('btnCancel').classList.add('hidden');
        document.getElementById('addRowArea').classList.add('hidden');
        render();
        alert('저장되었습니다.');
    }
}

function openAddModal() {
    const sel = document.getElementById('addRegCat');
    sel.innerHTML = '';
    const cats = [...new Set(regulations.map(r => r.category))];
    cats.forEach(cat => sel.appendChild(new Option(cat, cat)));
    sel.appendChild(new Option('+ 새 항목 직접 입력', '__new__'));
    document.getElementById('addRegCatNew').classList.add('hidden');
    document.getElementById('addRegCatNew').value = '';
    document.getElementById('addRegSub').value = '';
    document.getElementById('addRegLimit').value = '';
    document.getElementById('addRegReq').value = '';
    document.getElementById('addRegGuide').value = '';
    document.getElementById('addRegModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addRegModal').classList.add('hidden');
}

function toggleNewCatInput() {
    const sel = document.getElementById('addRegCat');
    const inp = document.getElementById('addRegCatNew');
    if (sel.value === '__new__') {
        inp.classList.remove('hidden');
        inp.focus();
    } else {
        inp.classList.add('hidden');
        inp.value = '';
    }
}

function saveNewRule() {
    const sel = document.getElementById('addRegCat');
    let category = sel.value;
    if (category === '__new__') {
        category = document.getElementById('addRegCatNew').value.trim();
        if (!category) { alert('항목명을 입력해주세요.'); return; }
    }
    const sub = document.getElementById('addRegSub').value.trim();
    if (!sub) { alert('세부항목을 입력해주세요.'); return; }

    const limitRaw = document.getElementById('addRegLimit').value.replace(/[^\d]/g, '');
    const newRule = {
        id: 0,
        category: category,
        sub_category: sub,
        limit_amount: limitRaw ? parseInt(limitRaw) : 0,
        required_fields: document.getElementById('addRegReq').value.trim(),
        guide: document.getElementById('addRegGuide').value.trim(),
        use_in_register: 1,
        sort_order: regulations.length
    };

    regulations.push(newRule);

    if (HAS_DB) {
        fetch(`${API_BASE}?action=saveRegulations`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({regulations: regulations})
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            closeAddModal();
            location.reload();
        })
        .catch(() => {
            closeAddModal();
            render();
        });
    } else {
        closeAddModal();
        const catFilter = document.getElementById('catFilter');
        const newCatSel = document.getElementById('newCategory');
        const existing = new Set([...catFilter.options].map(o => o.value).filter(Boolean));
        if (!existing.has(category)) {
            catFilter.appendChild(new Option(category, category));
            newCatSel.appendChild(new Option(category, category));
        }
        render();
    }
}

function esc(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
