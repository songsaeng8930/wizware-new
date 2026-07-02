<?php
/**
 * 시스템 관리 > 접근권한 관리
 * - 메뉴(행) × 역할(열) 매트릭스에서 셀 클릭 시 레벨 토글
 * - 레벨: 없음(−) / 보기(view) / 편집(edit) / 관리(admin)
 * - admin 역할은 '*' 와일드카드로 전체 접근
 */
$pageTitle = '접근권한 관리';
$currentPage = 'groupware';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/permissions.php';

// admin 만 접근
requireMenuPermission('groupware.permissions', 'admin');

if (!function_exists('esc')) {
    function esc($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$pdo = getDBConnection();
ensureMenuPermissionsTable($pdo);
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6 min-h-screen bg-slate-950">

    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h2 class="text-lg font-bold text-slate-100">접근권한 관리</h2>
            <p class="text-xs text-slate-500 mt-0.5">역할(Role) × 메뉴(Menu) 매트릭스로 전 사용자의 접근 범위를 관리합니다.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="resetPermissions()" class="btn btn-secondary btn-sm">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i> 기본값으로 복원
            </button>
        </div>
    </div>

    <!-- 범례 -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 mb-5">
        <p class="text-xs font-semibold text-slate-300 mb-2">접근 레벨</p>
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block w-5 h-5 rounded bg-slate-800 border border-slate-700 text-center text-slate-500">−</span> 없음 (메뉴 표시 안 됨)
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block w-5 h-5 rounded bg-sky-500/20 text-sky-300 text-[10px] font-bold text-center leading-5">V</span> 보기 (view)
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block w-5 h-5 rounded bg-primary/20 text-primary text-[10px] font-bold text-center leading-5">E</span> 편집 (edit)
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block w-5 h-5 rounded bg-amber-500/20 text-amber-400 text-[10px] font-bold text-center leading-5">A</span> 관리 (admin)
            </span>
            <span class="ml-auto text-[11px] text-slate-500">클릭하여 레벨 순환 · admin 역할은 항상 모든 메뉴에 접근합니다.</span>
        </div>
    </div>

    <!-- 매트릭스 -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" class="w-4 h-4 text-primary"></i>
                <p class="text-sm font-semibold text-slate-200">권한 매트릭스</p>
                <span id="permSaveStatus" class="hidden inline-flex items-center gap-1 text-[11px] text-emerald-400">
                    <i data-lucide="check" class="w-3 h-3"></i> 저장됨
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="permMatrix" class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/40 border-b border-slate-800">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider w-[50%]">메뉴 / 기능</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400" id="hdrAdmin">관리자</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400" id="hdrManager"><?= htmlspecialchars(getOrgHeadTitle('department')) ?></th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400" id="hdrUser">일반</th>
                    </tr>
                </thead>
                <tbody id="permTbody"></tbody>
            </table>
        </div>
    </div>

    <p class="text-[11px] text-slate-500 mt-3">
        <i data-lucide="info" class="inline w-3 h-3 -mt-0.5"></i>
        권한 변경은 즉시 DB 에 반영됩니다. 사용자는 다음 페이지 이동 시부터 새 권한이 적용됩니다.
    </p>

</main>
</div>

<script>
const PERM_API = '<?= $basePath ?>/api/permissions.php';
const LEVEL_CYCLE = ['none', 'view', 'edit', 'admin']; // 클릭 시 순환
const LEVEL_LABEL = { none: '−', view: 'V', edit: 'E', admin: 'A' };
const LEVEL_CLASS = {
    none:  'bg-slate-800 border border-slate-700 text-slate-500',
    view:  'bg-sky-500/20 text-sky-300',
    edit:  'bg-primary/20 text-primary',
    admin: 'bg-amber-500/20 text-amber-400',
};

let MENUS = [];
let ROLES = [];
let PERM_MAP = {}; // key "menu|role" → level

document.addEventListener('DOMContentLoaded', loadAll);

async function loadAll() {
    try {
        const [m, l] = await Promise.all([
            fetch(PERM_API + '?action=menus').then(r => r.json()),
            fetch(PERM_API + '?action=list').then(r => r.json()),
        ]);
        MENUS = (m.menus || []);
        ROLES = (m.roles || []);
        PERM_MAP = {};
        (l.rows || []).forEach(r => {
            // '*' 행은 해당 역할의 모든 메뉴에 적용 · UI에선 admin 행에 그림자처럼 처리
            if (r.menu_key === '*') {
                MENUS.forEach(mn => { PERM_MAP[mn.key + '|' + r.role_key] = r.access_level; });
            } else {
                PERM_MAP[r.menu_key + '|' + r.role_key] = r.access_level;
            }
        });
        render();
    } catch (e) {
        console.error(e);
        alert('권한 데이터를 불러오지 못했습니다.');
    }
}

function render() {
    const tbody = document.getElementById('permTbody');
    if (!MENUS.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-slate-500">메뉴 데이터가 없습니다.</td></tr>';
        return;
    }
    tbody.innerHTML = MENUS.map(m => {
        const depth = m.key.includes('.') ? 'pl-8' : 'pl-4';
        const icon  = m.key.includes('.') ? 'corner-down-right' : 'folder';
        return '<tr class="border-b border-slate-800 hover:bg-slate-950/40">'
             + '<td class="' + depth + ' pr-4 py-2.5">'
             +   '<div class="flex items-center gap-2">'
             +     '<i data-lucide="' + icon + '" class="w-3.5 h-3.5 text-slate-500"></i>'
             +     '<span class="text-sm text-slate-200">' + escapeHtml(m.label) + '</span>'
             +     '<span class="text-[10px] text-slate-500 font-mono">' + escapeHtml(m.key) + '</span>'
             +   '</div>'
             + '</td>'
             + ROLES.map(r => {
                 const key = m.key + '|' + r.key;
                 const level = PERM_MAP[key] || (r.key === 'admin' ? 'admin' : 'none');
                 return '<td class="px-4 py-2.5 text-center">'
                      + '<button type="button" onclick="cyclePerm(\'' + m.key + '\', \'' + r.key + '\')"'
                      + ' class="inline-flex items-center justify-center w-8 h-8 rounded text-xs font-bold transition-colors ' + LEVEL_CLASS[level] + '"'
                      + ' data-key="' + key + '" title="클릭하여 레벨 변경">' + LEVEL_LABEL[level] + '</button>'
                      + '</td>';
             }).join('')
             + '</tr>';
    }).join('');
    if (window.lucide) lucide.createIcons();
}

async function cyclePerm(menuKey, roleKey) {
    const key = menuKey + '|' + roleKey;
    const cur = PERM_MAP[key] || 'none';
    const idx = LEVEL_CYCLE.indexOf(cur);
    const next = LEVEL_CYCLE[(idx + 1) % LEVEL_CYCLE.length];

    // admin 역할은 항상 admin 고정 (정책)
    if (roleKey === 'admin' && next !== 'admin') {
        showStatus('관리자 역할은 항상 전체 접근이 허용됩니다.');
        return;
    }

    const btn = document.querySelector('button[data-key="' + CSS.escape(key) + '"]');
    if (btn) {
        btn.className = 'inline-flex items-center justify-center w-8 h-8 rounded text-xs font-bold transition-colors opacity-60 ' + LEVEL_CLASS[next];
        btn.textContent = LEVEL_LABEL[next];
    }
    try {
        const res = await fetch(PERM_API + '?action=setCell', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ menu_key: menuKey, role_key: roleKey, access_level: next }),
        });
        const data = await res.json();
        if (!data || !data.success) throw new Error((data && data.error) || '저장 실패');
        PERM_MAP[key] = next;
        if (btn) btn.className = 'inline-flex items-center justify-center w-8 h-8 rounded text-xs font-bold transition-colors ' + LEVEL_CLASS[next];
        showStatus();
    } catch (e) {
        alert(e.message || '저장 실패');
    }
}

function showStatus(msg) {
    const el = document.getElementById('permSaveStatus');
    if (msg) {
        el.innerHTML = '<i data-lucide="alert-triangle" class="w-3 h-3"></i> ' + escapeHtml(msg);
        el.className = 'inline-flex items-center gap-1 text-[11px] text-amber-400';
    } else {
        el.innerHTML = '<i data-lucide="check" class="w-3 h-3"></i> 저장됨';
        el.className = 'inline-flex items-center gap-1 text-[11px] text-emerald-400';
    }
    el.classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
    clearTimeout(window.__permStatusTimer);
    window.__permStatusTimer = setTimeout(() => el.classList.add('hidden'), 2000);
}

async function resetPermissions() {
    if (!(await AppUI.confirm('권한 매트릭스를 기본값으로 복원합니다. 현재 설정이 모두 초기화됩니다. 진행할까요?'))) return;
    const res = await fetch(PERM_API + '?action=reset', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
    const data = await res.json();
    if (!data || !data.success) { alert((data && data.error) || '복원 실패'); return; }
    await loadAll();
    showStatus();
}

function escapeHtml(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
