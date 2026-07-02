<?php
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!function_exists('getOrgLabel')) {
    require_once __DIR__ . '/../includes/org_hierarchy.php';
}
$pdo = getDBConnection();
$hasDB = false;
$cards = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM cards LIMIT 1');
        $hasDB = true;
    } catch (PDOException $e) { $hasDB = false; }
}
?>

<?php
$isSettingsPage = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'acct_settings.php');
if (!$isSettingsPage): ?>
<p class="mb-4 text-xs text-gray-400">조회 전용. 카드 등록·수정은 <a href="acct_settings.php?tab=card" class="text-primary hover:underline">환경설정 &gt; 카드등록</a>에서 관리합니다.</p>
<?php endif; ?>

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
                        <div class="zm-radio-group">
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
                        </tr>
                    </thead>
                    <tbody id="cardTableBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
        </div>
    </div>

<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const SHOW_DEPARTMENT = <?= isOrgLevelEnabled('department') ? 'true' : 'false' ?>;
let allCards = [];
let filteredCards = [];
let currentPage = 1;
const perPage = 10;

document.addEventListener('DOMContentLoaded', () => {
    if (HAS_DB) loadCards();
    else renderCards(); // DB 없으면 빈 테이블 표시
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
    renderCards();
}

function renderCards() {
    const tbody = document.getElementById('cardTableBody');
    document.getElementById('totalCount').textContent = filteredCards.length;
    const start = (currentPage - 1) * perPage;
    const pageData = filteredCards.slice(start, start + perPage);

    if (!pageData.length) {
        const colCount = SHOW_DEPARTMENT ? 7 : 6;
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
                <span class="inline-block px-2 py-0.5 text-sm font-medium rounded-full ${c.is_active == 1 ? 'bg-amber-50 text-amber-700' : 'bg-slate-800 text-slate-400'}">${c.is_active == 1 ? '사용' : '미사용'}</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-400 text-center">${(c.created_at||'').substring(0,10)}</td>
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

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>
