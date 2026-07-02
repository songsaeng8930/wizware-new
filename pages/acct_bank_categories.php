<?php
if (!isset($apiBasePath)) {
    require_once __DIR__ . '/../config/database.php';
    $apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
}
?>

<style>
.zm-csel { position: relative; }
.zm-csel-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: #1f2937;
    font-size: 14px;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
    min-height: 38px;
    text-align: left;
}
.zm-csel-trigger:hover { border-color: #d1d5db; }
.zm-csel.open .zm-csel-trigger { border-color: #4F6AFF; box-shadow: 0 0 0 3px rgba(79,106,255,0.12); }
.zm-csel-label { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.zm-csel-label.empty { color: #9ca3af; }
.zm-csel-arrow { width: 16px; height: 16px; color: #9ca3af; transition: transform 0.2s; flex-shrink: 0; }
.zm-csel.open .zm-csel-arrow { transform: rotate(180deg); }
.zm-csel-list {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0; right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.04);
    max-height: 240px;
    overflow-y: auto;
    z-index: 200;
    padding: 4px;
}
.zm-csel.open .zm-csel-list { display: block; }
.zm-csel-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: background 0.1s;
    user-select: none;
}
.zm-csel-item:hover { background: #f3f4f6; }
.zm-csel-item.sel { color: #4F6AFF; font-weight: 600; background: rgba(79,106,255,0.06); }
.zm-csel-item.sel:hover { background: rgba(79,106,255,0.1); }
.zm-csel-item .chk { width: 16px; height: 16px; color: #4F6AFF; flex-shrink: 0; }
</style>

<!-- 상단 요약 + 추가 버튼 -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-4">
        <p class="text-sm text-slate-400">총 <span id="catTotalCount" class="font-bold text-slate-200">0</span>개</p>
        <p class="text-sm text-slate-400">활성 <span id="catActiveCount" class="font-bold text-emerald-400">0</span></p>
        <p class="text-sm text-slate-400">비활성 <span id="catInactiveCount" class="font-bold text-slate-500">0</span></p>
    </div>
    <button onclick="openCatModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
        <i data-lucide="plus" class="w-4 h-4"></i>계정과목 추가
    </button>
</div>

<!-- 필터 -->
<div class="flex items-center gap-3 mb-4">
    <div class="inline-flex rounded-lg border border-slate-800 overflow-hidden flex-shrink-0" id="catFilterGroup">
        <button type="button" onclick="setCatTypeFilter('all')" data-filter="all" class="cat-filter-btn zm-tab zm-tab-active px-3 py-1.5 text-sm transition-colors">전체</button>
        <button type="button" onclick="setCatTypeFilter('자산')" data-filter="자산" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">자산</button>
        <button type="button" onclick="setCatTypeFilter('부채')" data-filter="부채" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">부채</button>
        <button type="button" onclick="setCatTypeFilter('자본')" data-filter="자본" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">자본</button>
        <button type="button" onclick="setCatTypeFilter('매출')" data-filter="매출" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">매출</button>
        <button type="button" onclick="setCatTypeFilter('매입')" data-filter="매입" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">매입</button>
        <button type="button" onclick="setCatTypeFilter('비용')" data-filter="비용" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">비용</button>
        <button type="button" onclick="setCatTypeFilter('수익')" data-filter="수익" class="cat-filter-btn zm-tab px-3 py-1.5 text-sm transition-colors">수익</button>
    </div>
    <div class="relative flex-1 max-w-xs">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none"></i>
        <input type="text" id="catSearchInput" placeholder="코드 또는 이름 검색" oninput="renderCatTable()" class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
    </div>
    <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
        <input type="checkbox" id="catShowInactive" onchange="renderCatTable()" class="rounded border-slate-700 bg-slate-950 text-primary focus:ring-gray-300/30 w-3.5 h-3.5">
        <span class="text-xs text-slate-400">비활성 포함</span>
    </label>
</div>

<!-- 테이블 -->
<div class="overflow-x-auto bg-slate-900 rounded-xl border border-slate-800">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b-2 border-slate-800">
                <th class="py-3 px-4 text-left font-medium text-slate-400 w-24">코드</th>
                <th class="py-3 px-4 text-left font-medium text-slate-400">계정과목명</th>
                <th class="py-3 px-4 text-left font-medium text-slate-400 w-20">분류</th>
                <th class="py-3 px-4 text-left font-medium text-slate-400 w-24">과세구분</th>
                <th class="py-3 px-4 text-center font-medium text-slate-400 w-20">상태</th>
                <th class="py-3 px-4 text-right font-medium text-slate-400 w-20">사용</th>
                <th class="py-3 px-4 text-center font-medium text-slate-400 w-28">관리</th>
            </tr>
        </thead>
        <tbody id="catTableBody"></tbody>
    </table>
</div>
<p class="text-xs text-slate-600 mt-2" id="catFilterInfo"></p>

<!-- 추가/수정 모달 -->
<div id="catModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCatModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md border border-slate-800">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100" id="catModalTitle">계정과목 추가</h3>
            <button onclick="closeCatModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="catEditId" value="0">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">코드 <span class="text-rose-400">*</span></label>
                    <input type="text" id="catCode" placeholder="예: 81100" maxlength="10" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">정렬순서</label>
                    <input type="number" id="catSortOrder" value="0" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">계정과목명 <span class="text-rose-400">*</span></label>
                <input type="text" id="catName" placeholder="예: 복리후생비" maxlength="100" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">분류 <span class="text-rose-400">*</span></label>
                    <select id="catType" onchange="updateParentCodeOptions()" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                        <option value="">선택</option>
                        <option value="자산">자산</option>
                        <option value="부채">부채</option>
                        <option value="자본">자본</option>
                        <option value="매출">매출</option>
                        <option value="매입">매입</option>
                        <option value="비용">비용</option>
                        <option value="수익">수익</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">과세구분</label>
                    <select id="catTaxType" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                        <option value="과세">과세</option>
                        <option value="면세">면세</option>
                        <option value="영세율">영세율</option>
                        <option value="불공제">불공제</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">상위 분류</label>
                <select id="catParentCode" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <option value="">없음 (최상위)</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">계정과목을 묶어줄 중간 분류를 선택하세요.</p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-800">
            <button onclick="closeCatModal()" class="btn btn-secondary">취소</button>
            <button onclick="saveCategory()" id="catSaveBtn" class="px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">저장</button>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div id="catDeleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCatDeleteModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm border border-slate-800">
        <div class="p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-500/10 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="trash-2" class="w-6 h-6 text-rose-400"></i>
            </div>
            <p class="text-base font-bold text-slate-100 mb-2">계정과목 삭제</p>
            <p class="text-sm text-slate-400 mb-6" id="catDeleteMsg">이 계정과목을 삭제하시겠습니까?</p>
            <div class="flex items-center justify-center gap-3">
                <button onclick="closeCatDeleteModal()" class="btn btn-secondary">취소</button>
                <button onclick="confirmDeleteCategory()" class="px-5 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition-colors">삭제</button>
            </div>
        </div>
    </div>
</div>

<script>
var CAT_API = '<?= htmlspecialchars($apiBasePath) ?>/api/bankapi.php';
var allCategories = [];
var catTypeFilter = 'all';
var pendingDeleteId = 0;

document.addEventListener('DOMContentLoaded', function() {
    loadAllCategories();
});

async function loadAllCategories() {
    try {
        var res = await fetch(CAT_API + '?action=list_categories_full');
        var json = await res.json();
        if (json.success && json.data) {
            allCategories = json.data.categories || [];
            renderCatTable();
        }
    } catch (e) {
        console.error('loadAllCategories:', e);
    }
}

var TYPE_ORDER = {'자산':1,'부채':2,'자본':3,'매출':4,'매입':5,'비용':6,'수익':7};
var TYPE_COLORS = {
    '자산':'text-blue-400 bg-blue-500/10','부채':'text-rose-400 bg-rose-500/10',
    '자본':'text-purple-400 bg-purple-500/10','매출':'text-emerald-400 bg-emerald-500/10',
    '매입':'text-orange-400 bg-orange-500/10','비용':'text-amber-400 bg-amber-500/10',
    '수익':'text-cyan-400 bg-cyan-500/10'
};
var TAX_BADGE = {
    '과세':'bg-emerald-500/10 text-emerald-400','면세':'bg-slate-700/50 text-slate-400',
    '영세율':'bg-blue-500/10 text-blue-400','불공제':'bg-rose-500/10 text-rose-400'
};

function isGroupRecord(c) { return c.code && c.code.indexOf('G_') === 0; }

function getGroupMap() {
    var map = {};
    allCategories.forEach(function(c) {
        if (isGroupRecord(c)) map[c.code] = c;
    });
    return map;
}

function renderCatTable() {
    var keyword = (document.getElementById('catSearchInput').value || '').trim().toLowerCase();
    var showInactive = document.getElementById('catShowInactive').checked;
    var groupMap = getGroupMap();

    var leafCategories = allCategories.filter(function(c) { return !isGroupRecord(c); });

    var filtered = leafCategories.filter(function(c) {
        if (catTypeFilter !== 'all' && c.type !== catTypeFilter) return false;
        if (!showInactive && parseInt(c.is_active) === 0) return false;
        if (keyword) {
            var haystack = (c.code + ' ' + c.name).toLowerCase();
            if (haystack.indexOf(keyword) < 0) return false;
        }
        return true;
    });

    filtered.sort(function(a, b) {
        var ta = TYPE_ORDER[a.type] || 99, tb = TYPE_ORDER[b.type] || 99;
        if (ta !== tb) return ta - tb;
        var ga = a.parent_code ? (groupMap[a.parent_code] || {}).sort_order : null;
        var gb = b.parent_code ? (groupMap[b.parent_code] || {}).sort_order : null;
        var gaNum = ga !== null && ga !== undefined ? parseInt(ga) : -1;
        var gbNum = gb !== null && gb !== undefined ? parseInt(gb) : -1;
        if (gaNum !== gbNum) return gaNum - gbNum;
        var sa = parseInt(a.sort_order) || 0, sb = parseInt(b.sort_order) || 0;
        if (sa !== sb) return sa - sb;
        return a.code.localeCompare(b.code, 'ko', { numeric: true });
    });

    var totalActive = leafCategories.filter(function(c) { return parseInt(c.is_active) === 1; }).length;
    document.getElementById('catTotalCount').textContent = leafCategories.length;
    document.getElementById('catActiveCount').textContent = totalActive;
    document.getElementById('catInactiveCount').textContent = leafCategories.length - totalActive;

    var typeCounts = {};
    leafCategories.forEach(function(c) {
        if (parseInt(c.is_active) === 1) typeCounts[c.type] = (typeCounts[c.type] || 0) + 1;
    });
    document.querySelectorAll('.cat-filter-btn').forEach(function(btn) {
        var f = btn.getAttribute('data-filter');
        if (f !== 'all') {
            var cnt = typeCounts[f] || 0;
            btn.textContent = f + ' ' + cnt;
        }
    });

    var tbody = document.getElementById('catTableBody');
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-12 text-center text-slate-500">계정과목이 없습니다.</td></tr>';
        document.getElementById('catFilterInfo').textContent = '';
        return;
    }

    var html = '';
    var lastType = '';
    var lastParent = '';
    var showGroupHeaders = !keyword;

    filtered.forEach(function(c) {
        if (showGroupHeaders && c.type !== lastType) {
            lastType = c.type;
            lastParent = '';
            var tc = TYPE_COLORS[c.type] || 'text-slate-400 bg-slate-800/50';
            var groupCount = filtered.filter(function(x) { return x.type === c.type; }).length;
            html += '<tr class="bg-slate-800/40"><td colspan="7" class="py-2 px-4">' +
                '<span class="inline-block px-2 py-0.5 rounded text-xs font-bold ' + tc + '">' + escH(c.type) + '</span>' +
                '<span class="text-xs text-slate-500 ml-2">' + groupCount + '개</span></td></tr>';
        }

        if (showGroupHeaders && c.parent_code && c.parent_code !== lastParent) {
            lastParent = c.parent_code;
            var parentName = groupMap[c.parent_code] ? groupMap[c.parent_code].name : c.parent_code;
            var subCount = filtered.filter(function(x) { return x.parent_code === c.parent_code; }).length;
            html += '<tr class="bg-slate-800/20"><td colspan="7" class="py-1.5 px-4 pl-8">' +
                '<span class="text-xs font-semibold text-slate-400">┗ ' + escH(parentName) + '</span>' +
                '<span class="text-xs text-slate-600 ml-2">' + subCount + '개</span></td></tr>';
        }

        var isActive = parseInt(c.is_active) === 1;
        var rowClass = isActive ? '' : 'opacity-40';
        var usage = parseInt(c.usage_count || 0);
        var tc2 = (TYPE_COLORS[c.type] || '').split(' ')[0] || 'text-slate-400';
        var tb2 = TAX_BADGE[c.tax_type] || 'bg-slate-700/50 text-slate-400';
        var indent = (showGroupHeaders && c.parent_code) ? 'pl-8' : 'px-4';

        html += '<tr class="border-b border-slate-800/50 hover:bg-slate-800/30 ' + rowClass + '">' +
            '<td class="py-3 px-4 text-slate-200 font-mono text-xs">' + escH(c.code) + '</td>' +
            '<td class="py-3 ' + indent + ' text-slate-100 font-medium">' + escH(c.name) + '</td>' +
            '<td class="py-3 px-4"><span class="' + tc2 + ' text-xs font-medium">' + escH(c.type) + '</span></td>' +
            '<td class="py-3 px-4"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium ' + tb2 + '">' + escH(c.tax_type) + '</span></td>' +
            '<td class="py-3 px-4 text-center">' + (isActive
                ? '<span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>'
                : '<span class="inline-block w-2 h-2 rounded-full bg-slate-600"></span>') + '</td>' +
            '<td class="py-3 px-4 text-right text-slate-400 text-xs tabular-nums">' + (usage > 0 ? usage + '건' : '-') + '</td>' +
            '<td class="py-3 px-4 text-center">' +
                '<div class="inline-flex items-center gap-1">' +
                    '<button onclick="editCategory(' + c.id + ')" class="p-1 text-slate-500 hover:text-gray-900 transition-colors" title="수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>' +
                    '<button onclick="toggleCategory(' + c.id + ')" class="p-1 text-slate-500 hover:text-amber-400 transition-colors" title="' + (isActive ? '비활성화' : '활성화') + '"><i data-lucide="' + (isActive ? 'eye-off' : 'eye') + '" class="w-3.5 h-3.5"></i></button>' +
                    '<button onclick="deleteCategory(' + c.id + ',\'' + escH(c.name).replace(/'/g, "\\'") + '\',' + usage + ')" class="p-1 text-slate-500 hover:text-rose-400 transition-colors" title="삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>' +
                '</div>' +
            '</td></tr>';
    });

    tbody.innerHTML = html;
    var leafTotal = leafCategories.length;
    document.getElementById('catFilterInfo').textContent =
        filtered.length < leafTotal ? filtered.length + '/' + leafTotal + '개 표시' : '';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function setCatTypeFilter(type) {
    catTypeFilter = type;
    document.querySelectorAll('.cat-filter-btn').forEach(function(btn) {
        btn.classList.toggle('zm-tab-active', btn.getAttribute('data-filter') === type);
    });
    renderCatTable();
}

function openCatModal(cat) {
    document.getElementById('catEditId').value = cat ? cat.id : 0;
    document.getElementById('catCode').value = cat ? cat.code : '';
    document.getElementById('catName').value = cat ? cat.name : '';
    document.getElementById('catType').value = cat ? cat.type : '';
    document.getElementById('catTaxType').value = cat ? cat.tax_type : '과세';
    document.getElementById('catSortOrder').value = cat ? (cat.sort_order || 0) : 0;
    document.getElementById('catModalTitle').textContent = cat ? '계정과목 수정' : '계정과목 추가';
    updateParentCodeOptions(cat ? cat.parent_code : null);
    document.getElementById('catModal').classList.remove('hidden');
    setTimeout(_zmCselInit, 0);
}

function updateParentCodeOptions(selectedParent) {
    var type = document.getElementById('catType').value;
    var sel = document.getElementById('catParentCode');
    var prevVal = selectedParent !== undefined ? selectedParent : sel.value;
    sel.innerHTML = '<option value="">없음 (최상위)</option>';

    var groups = allCategories.filter(function(c) {
        return isGroupRecord(c) && (!type || c.type === type);
    });
    groups.sort(function(a, b) { return (parseInt(a.sort_order) || 0) - (parseInt(b.sort_order) || 0); });

    groups.forEach(function(g) {
        var opt = document.createElement('option');
        opt.value = g.code;
        opt.textContent = g.name;
        if (prevVal === g.code) opt.selected = true;
        sel.appendChild(opt);
    });
}
function closeCatModal() { document.getElementById('catModal').classList.add('hidden'); }

function editCategory(id) {
    var cat = allCategories.find(function(c) { return parseInt(c.id) === id; });
    if (cat) openCatModal(cat);
}

async function saveCategory() {
    var btn = document.getElementById('catSaveBtn');
    var origText = btn.textContent;
    btn.textContent = '저장 중...';
    btn.disabled = true;

    var payload = {
        id: parseInt(document.getElementById('catEditId').value) || 0,
        code: document.getElementById('catCode').value.trim(),
        name: document.getElementById('catName').value.trim(),
        type: document.getElementById('catType').value,
        tax_type: document.getElementById('catTaxType').value,
        sort_order: parseInt(document.getElementById('catSortOrder').value) || 0,
        parent_code: document.getElementById('catParentCode').value || null
    };

    if (!payload.code || !payload.name || !payload.type) {
        alert('코드, 계정과목명, 분류는 필수입니다.');
        btn.textContent = origText;
        btn.disabled = false;
        return;
    }

    try {
        var res = await fetch(CAT_API + '?action=save_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        var json = await res.json();
        if (json.success) {
            closeCatModal();
            await loadAllCategories();
        } else {
            alert(json.message || '저장 실패');
        }
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
    }
    btn.textContent = origText;
    btn.disabled = false;
}

async function toggleCategory(id) {
    try {
        var res = await fetch(CAT_API + '?action=toggle_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        var json = await res.json();
        if (json.success) {
            await loadAllCategories();
        } else {
            alert(json.message || '변경 실패');
        }
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
    }
}

function deleteCategory(id, name, usage) {
    if (usage > 0) {
        alert(name + '은(는) ' + usage + '건의 거래에서 사용 중이라 삭제할 수 없습니다.\n비활성화를 이용하세요.');
        return;
    }
    pendingDeleteId = id;
    document.getElementById('catDeleteMsg').textContent = '"' + name + '" 계정과목을 삭제하시겠습니까?';
    document.getElementById('catDeleteModal').classList.remove('hidden');
}
function closeCatDeleteModal() {
    document.getElementById('catDeleteModal').classList.add('hidden');
    pendingDeleteId = 0;
}

async function confirmDeleteCategory() {
    if (!pendingDeleteId) return;
    try {
        var res = await fetch(CAT_API + '?action=delete_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pendingDeleteId })
        });
        var json = await res.json();
        if (json.success) {
            closeCatDeleteModal();
            await loadAllCategories();
        } else {
            alert(json.message || '삭제 실패');
        }
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
    }
}

function escH(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

// ── Custom Select Dropdown ──
(function() {
    var ARROW = '<svg class="zm-csel-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>';
    var CHECK = '<svg class="chk" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>';
    var all = [];

    function build(sel) {
        if (sel._zmc) return;
        sel.style.display = 'none';

        var w = document.createElement('div');
        w.className = 'zm-csel';
        sel.parentNode.insertBefore(w, sel);
        w.appendChild(sel);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'zm-csel-trigger';
        w.appendChild(btn);

        var list = document.createElement('div');
        list.className = 'zm-csel-list';
        w.appendChild(list);

        function label() {
            var o = sel.options[sel.selectedIndex];
            var t = o ? o.text : '';
            var empty = !o || !o.value;
            btn.innerHTML = '<span class="zm-csel-label' + (empty ? ' empty' : '') + '">' + escH(t || '선택') + '</span>' + ARROW;
        }

        function items() {
            var h = '';
            for (var i = 0; i < sel.options.length; i++) {
                var o = sel.options[i], on = i === sel.selectedIndex;
                h += '<div class="zm-csel-item' + (on ? ' sel' : '') + '" data-i="' + i + '"><span>' + escH(o.text) + '</span>' + (on ? CHECK : '') + '</div>';
            }
            list.innerHTML = h;
        }

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = w.classList.contains('open');
            closeAll();
            if (!open) { items(); w.classList.add('open'); }
        });

        list.addEventListener('click', function(e) {
            var it = e.target.closest('.zm-csel-item');
            if (!it) return;
            sel.selectedIndex = parseInt(it.dataset.i);
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            label();
            w.classList.remove('open');
        });

        new MutationObserver(label).observe(sel, { childList: true, subtree: true });

        label();
        sel._zmc = { refresh: label };
        all.push({ w: w });
    }

    function closeAll() { document.querySelectorAll('.zm-csel.open').forEach(function(el) { el.classList.remove('open'); }); }
    document.addEventListener('click', closeAll);

    window._zmCselInit = function() {
        document.querySelectorAll('#catModal select').forEach(build);
        all.forEach(function(inst) {
            var sel = inst.w.querySelector('select');
            if (sel && sel._zmc) sel._zmc.refresh();
        });
    };
})();
</script>
