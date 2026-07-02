<?php
$pageTitle = '카드관리';
$currentPage = 'card';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$cards = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM cards LIMIT 1');
        $hasDB = true;
    } catch (PDOException $e) { $hasDB = false; }
}

if (!$hasDB) {
    $cards = [
        ['id'=>1,'card_alias'=>'영업팀 법인카드','card_number'=>'9410-****-****-1234','memo'=>'영업팀 업무용','manager_name'=>'김영수','affiliation'=>'위즈웨어','department'=>'영업팀','is_active'=>1,'created_at'=>'2025-01-15'],
        ['id'=>2,'card_alias'=>'개발팀 법인카드','card_number'=>'9410-****-****-5678','memo'=>'개발팀 업무용','manager_name'=>'이정민','affiliation'=>'위즈웨어','department'=>'개발팀','is_active'=>1,'created_at'=>'2025-01-15'],
        ['id'=>3,'card_alias'=>'경영지원 법인카드','card_number'=>'5412-****-****-9012','memo'=>'경영지원실 업무용','manager_name'=>'박지현','affiliation'=>'위즈웨어','department'=>'경영지원실','is_active'=>1,'created_at'=>'2025-02-01'],
        ['id'=>4,'card_alias'=>'대표이사 법인카드','card_number'=>'4532-****-****-3456','memo'=>'대표이사 전용','manager_name'=>'최민호','affiliation'=>'위즈웨어','department'=>'경영진','is_active'=>1,'created_at'=>'2025-01-10'],
        ['id'=>5,'card_alias'=>'마케팅팀 법인카드','card_number'=>'9410-****-****-7890','memo'=>'마케팅 업무용','manager_name'=>'정수진','affiliation'=>'위즈웨어','department'=>'마케팅팀','is_active'=>0,'created_at'=>'2025-03-01'],
    ];
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-100">법인카드 운영</h2>
            <button onclick="openCardModal()" class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors">
                <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 신규등록
            </button>
        </div>
        <nav class="flex items-center gap-1 border-b border-slate-800 mb-5">
            <a href="card_manage.php" class="px-4 py-2.5 text-sm font-medium text-primary border-b-2 border-primary -mb-px">카드 관리</a>
            <a href="card_settlement.php" class="px-4 py-2.5 text-sm font-medium text-slate-400 hover:text-slate-200 -mb-px">정산</a>
        </nav>

        <!-- 검색 필터 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950 flex items-center gap-2">
                <i data-lucide="sliders-horizontal" class="w-4 h-4 text-slate-400"></i>
                <span class="text-sm font-semibold text-slate-200">검색 필터</span>
            </div>
            <div class="filter-grid">
                <div class="filter-row">
                    <div class="filter-label">소속<?= isOrgLevelEnabled('department') ? '/' . htmlspecialchars(getOrgLabel('department')) : '' ?></div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="search" class="filter-icon"></i>
                            <input type="text" id="filterDept" class="filter-input" placeholder="<?= isOrgLevelEnabled('department') ? '소속 또는 ' . htmlspecialchars(getOrgLabel('department')) . ' 검색' : '소속 검색' ?>" oninput="filterCards()">
                        </div>
                    </div>
                    <div class="filter-label">카드별칭</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="search" class="filter-icon"></i>
                            <input type="text" id="filterAlias" class="filter-input" placeholder="카드별칭 검색" oninput="filterCards()">
                        </div>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-label">책임자</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="user" class="filter-icon"></i>
                            <input type="text" id="filterManager" class="filter-input" placeholder="책임자 검색" oninput="filterCards()">
                        </div>
                    </div>
                    <div class="filter-label">사용여부</div>
                    <div class="filter-value">
                        <div class="zm-radio-group zm-segmented">
                            <label class="cursor-pointer"><input type="radio" name="filterActive" value="" checked onchange="filterCards()" class="sr-only peer"><span class="zm-radio">전체</span></label>
                            <label class="cursor-pointer"><input type="radio" name="filterActive" value="1" onchange="filterCards()" class="sr-only peer"><span class="zm-radio">사용</span></label>
                            <label class="cursor-pointer"><input type="radio" name="filterActive" value="0" onchange="filterCards()" class="sr-only peer"><span class="zm-radio">미사용</span></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-5 py-3 bg-slate-950 border-t border-slate-800">
                <button onclick="resetFilters()" class="btn btn-secondary">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> 초기화
                </button>
                <button onclick="filterCards()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
                </button>
            </div>
        </div>

        <!-- 리스트 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
                <span class="text-sm text-slate-400">총 <strong id="totalCount" class="text-primary">0</strong>건</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full emp-table">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="px-4 py-3 text-left">카드별칭</th>
                            <th class="px-4 py-3 text-left">비고</th>
                            <th class="px-4 py-3 text-center">책임자</th>
                            <th class="px-4 py-3 text-center">소속</th>
                            <?php if (isOrgLevelEnabled('department')): ?><th class="px-4 py-3 text-center"><?= htmlspecialchars(getOrgLabel('department')) ?></th><?php endif; ?>
                            <th class="px-4 py-3 text-center">사용여부</th>
                            <th class="px-4 py-3 text-center">등록일</th>
                            <th class="px-4 py-3 text-center w-[100px]">관리</th>
                        </tr>
                    </thead>
                    <tbody id="cardTableBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>
    </main>

    <!-- 등록/수정 모달 -->
    <div id="cardModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeCardModal()">
        <div class="bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <!-- 헤더 -->
            <div class="bg-primary px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-5 h-5 text-white/80"></i>
                    <h3 class="text-base font-semibold text-white" id="modalTitle">카드 등록</h3>
                </div>
                <button onclick="closeCardModal()" class="text-white/60 hover:text-white transition-colors p-2 -mr-2 rounded-lg hover:bg-white/10">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <!-- 폼 바디 -->
            <form id="cardForm" onsubmit="return saveCard(event)">
                <input type="hidden" id="cardId" value="0">
                <div class="p-6 space-y-4">
                    <!-- 카드 기본정보 -->
                    <div>
                        <label class="reg-label">카드별칭 <span class="text-amber-500">*</span></label>
                        <input type="text" id="cardAlias" class="reg-input" placeholder="예: 영업팀 법인카드" required>
                    </div>
                    <div>
                        <label class="reg-label">카드번호</label>
                        <input type="text" id="cardNumber" class="reg-input font-mono tracking-widest" placeholder="예: 9410-****-****-1234">
                    </div>
                    <hr class="border-slate-800">
                    <!-- 담당자 정보 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="reg-label">책임자 <span class="text-amber-500">*</span></label>
                            <input type="text" id="cardManager" class="reg-input" placeholder="책임자명" required>
                        </div>
                        <div>
                            <label class="reg-label">소속</label>
                            <input type="text" id="cardAffiliation" class="reg-input" placeholder="소속">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <?php if (isOrgLevelEnabled('department')): ?>
                        <div>
                            <label class="reg-label"><?= htmlspecialchars(getOrgLabel('department')) ?></label>
                            <input type="text" id="cardDepartment" class="reg-input" placeholder="<?= htmlspecialchars(getOrgLabel('department')) ?>명">
                        </div>
                        <?php else: ?>
                        <input type="hidden" id="cardDepartment" value="">
                        <?php endif; ?>
                        <div>
                            <label class="reg-label">사용여부</label>
                            <select id="cardActive" class="reg-select">
                                <option value="1">사용</option>
                                <option value="0">미사용</option>
                            </select>
                        </div>
                    </div>
                    <!-- 비고 -->
                    <div>
                        <label class="reg-label">비고</label>
                        <textarea id="cardMemo" class="reg-input resize-none" rows="3" placeholder="카드 관련 비고사항을 입력하세요"></textarea>
                    </div>
                </div>
                <!-- 푸터 버튼 -->
                <div class="flex items-center justify-end gap-2 px-6 py-4 bg-slate-950 border-t border-slate-800">
                    <button type="button" onclick="closeCardModal()" class="btn btn-secondary">취소</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold rounded-lg bg-primary text-white hover:opacity-90 transition-colors">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span id="modalSubmitText">등록하기</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const SHOW_DEPARTMENT = <?= isOrgLevelEnabled('department') ? 'true' : 'false' ?>;
let allCards = <?= json_encode($cards, JSON_UNESCAPED_UNICODE) ?>;
let filteredCards = [...allCards];
let currentPage = 1;
const perPage = 10;

document.addEventListener('DOMContentLoaded', () => {
    if (HAS_DB) loadCards();
    else renderCards();
});

function loadCards() {
    const params = new URLSearchParams();
    const dept = document.getElementById('filterDept').value;
    const alias = document.getElementById('filterAlias').value;
    const manager = document.getElementById('filterManager').value;
    const active = document.querySelector('input[name="filterActive"]:checked').value;
    if (dept) params.set('department', dept);
    if (alias) params.set('card_alias', alias);
    if (manager) params.set('manager', manager);
    if (active !== '') params.set('is_active', active);

    fetch(`${API_BASE}?action=getCards&${params}`)
        .then(r => r.json())
        .then(data => { allCards = data.cards || []; filteredCards = [...allCards]; currentPage = 1; renderCards(); });
}

function filterCards() {
    if (HAS_DB) { loadCards(); return; }
    const dept = document.getElementById('filterDept').value.toLowerCase();
    const alias = document.getElementById('filterAlias').value.toLowerCase();
    const manager = document.getElementById('filterManager').value.toLowerCase();
    const active = document.querySelector('input[name="filterActive"]:checked').value;

    filteredCards = allCards.filter(c => {
        if (dept && !((c.affiliation||'')+(c.department||'')).toLowerCase().includes(dept)) return false;
        if (alias && !(c.card_alias||'').toLowerCase().includes(alias)) return false;
        if (manager && !(c.manager_name||'').toLowerCase().includes(manager)) return false;
        if (active !== '' && String(c.is_active) !== active) return false;
        return true;
    });
    currentPage = 1;
    renderCards();
}

function renderCards() {
    const tbody = document.getElementById('cardTableBody');
    document.getElementById('totalCount').textContent = filteredCards.length;
    const start = (currentPage - 1) * perPage;
    const pageData = filteredCards.slice(start, start + perPage);

    if (!pageData.length) {
        const colCount = SHOW_DEPARTMENT ? 8 : 7;
        tbody.innerHTML = '<tr><td colspan="' + colCount + '" class="text-center py-10 text-slate-400 text-sm">등록된 카드가 없습니다.</td></tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = pageData.map(c => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
            <td class="px-4 py-3 text-sm font-medium text-slate-100">${esc(c.card_alias)}</td>
            <td class="px-4 py-3 text-sm text-slate-300">${esc(c.memo || '-')}</td>
            <td class="px-4 py-3 text-sm text-slate-200 text-center">${esc(c.manager_name)}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(c.affiliation || '-')}</td>
            ${SHOW_DEPARTMENT ? `<td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(c.department || '-')}</td>` : ''}
            <td class="px-4 py-3 text-center">
                <span class="inline-block px-2 py-0.5 text-sm font-medium rounded-full cursor-pointer ${c.is_active == 1 ? 'bg-amber-50 text-amber-700' : 'bg-slate-800 text-slate-400'}" onclick="toggleCard(${c.id})">${c.is_active == 1 ? '사용' : '미사용'}</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-400 text-center">${(c.created_at||'').substring(0,10)}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="editCard(${c.id})" class="text-slate-400 hover:text-gray-900 mr-2" title="수정"><i data-lucide="pencil" class="w-3 h-3"></i></button>
                <button onclick="deleteCard(${c.id})" class="text-slate-400 hover:text-amber-500" title="삭제"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
            </td>
        </tr>
    `).join('');

    const pages = Math.ceil(filteredCards.length / perPage);
    if (pages <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let html = `<button class="pg-btn ${currentPage===1?'pg-disabled':''}" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i=1;i<=pages;i++) html += `<button class="pg-btn ${i===currentPage?'pg-active':''}" onclick="goPage(${i})">${i}</button>`;
    html += `<button class="pg-btn ${currentPage===pages?'pg-disabled':''}" onclick="goPage(${currentPage+1})" ${currentPage===pages?'disabled':''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    document.getElementById('pagination').innerHTML = html;
}

function goPage(p) { const pages = Math.ceil(filteredCards.length / perPage); if (p<1||p>pages) return; currentPage=p; renderCards(); }

function resetFilters() {
    document.getElementById('filterDept').value='';
    document.getElementById('filterAlias').value='';
    document.getElementById('filterManager').value='';
    document.querySelector('input[name="filterActive"][value=""]').checked=true;
    filterCards();
}

function openCardModal(id) {
    document.getElementById('cardId').value = '0';
    document.getElementById('cardAlias').value = '';
    document.getElementById('cardNumber').value = '';
    document.getElementById('cardManager').value = '';
    document.getElementById('cardAffiliation').value = '위즈웨어';
    document.getElementById('cardDepartment').value = '';
    document.getElementById('cardActive').value = '1';
    document.getElementById('cardMemo').value = '';
    document.getElementById('modalTitle').textContent = '카드 등록';
    document.getElementById('modalSubmitText').textContent = '등록하기';
    document.getElementById('cardModal').classList.remove('hidden');
}

function closeCardModal() { document.getElementById('cardModal').classList.add('hidden'); }

function editCard(id) {
    const c = (HAS_DB ? filteredCards : allCards).find(x => x.id == id);
    if (!c) return;
    document.getElementById('cardId').value = c.id;
    document.getElementById('cardAlias').value = c.card_alias;
    document.getElementById('cardNumber').value = c.card_number || '';
    document.getElementById('cardManager').value = c.manager_name;
    document.getElementById('cardAffiliation').value = c.affiliation || '';
    document.getElementById('cardDepartment').value = c.department || '';
    document.getElementById('cardActive').value = c.is_active;
    document.getElementById('cardMemo').value = c.memo || '';
    document.getElementById('modalTitle').textContent = '카드 수정';
    document.getElementById('modalSubmitText').textContent = '수정하기';
    document.getElementById('cardModal').classList.remove('hidden');
}

function saveCard(e) {
    e.preventDefault();
    const data = {
        id: parseInt(document.getElementById('cardId').value),
        card_alias: document.getElementById('cardAlias').value,
        card_number: document.getElementById('cardNumber').value,
        manager_name: document.getElementById('cardManager').value,
        affiliation: document.getElementById('cardAffiliation').value,
        department: document.getElementById('cardDepartment').value,
        is_active: parseInt(document.getElementById('cardActive').value),
        memo: document.getElementById('cardMemo').value,
    };

    if (HAS_DB) {
        fetch(`${API_BASE}?action=saveCard`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })
            .then(r=>r.json()).then(res => { if(res.error){alert(res.error);return;} alert('저장되었습니다.'); closeCardModal(); loadCards(); });
    } else {
        if (data.id > 0) { const idx = allCards.findIndex(c=>c.id===data.id); if(idx>=0) Object.assign(allCards[idx],data); }
        else { data.id = allCards.length+1; data.created_at = new Date().toISOString().split('T')[0]; allCards.unshift(data); }
        filteredCards=[...allCards]; closeCardModal(); renderCards(); alert('저장되었습니다.');
    }
    return false;
}

async function deleteCard(id) {
    if (!(await AppUI.confirm('이 카드를 삭제하시겠습니까?'))) return;
    if (HAS_DB) {
        fetch(`${API_BASE}?action=deleteCard`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) })
            .then(r=>r.json()).then(res => { if(res.error) { alert(res.error); return; } loadCards(); });
    } else {
        allCards = allCards.filter(c=>c.id!==id); filteredCards=[...allCards]; renderCards();
    }
}

function toggleCard(id) {
    if (HAS_DB) {
        fetch(`${API_BASE}?action=toggleCard`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) })
            .then(r=>r.json()).then(res => { if(res.error) { alert(res.error); return; } loadCards(); });
    } else {
        const c = allCards.find(x=>x.id===id); if(c) c.is_active = c.is_active == 1 ? 0 : 1;
        filteredCards=[...allCards]; renderCards();
    }
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
