<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/ui_settings.php';
require_once __DIR__ . '/org_hierarchy.php';
require_once __DIR__ . '/terminology.php';
require_once __DIR__ . '/../config/database.php';
$_currentFile = basename($_SERVER['SCRIPT_FILENAME']);
if ($_currentFile !== 'login.php') { requireLogin(); }

$pageTitle = $pageTitle ?? 'BMS';
$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRoot = realpath(__DIR__ . '/..');
$basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');
$_csrfToken = csrfToken();

// 알림 배지 초기값 (서버사이드)
$__notifCount = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        $_nPdo = getDBConnection();
        if ($_nPdo) {
            $_nStmt = $_nPdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
            $_nStmt->execute([(int)$_SESSION['user_id']]);
            $__notifCount = (int)$_nStmt->fetchColumn();
        }
    } catch (\Throwable $e) {
        $__notifCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="ko" class="bms-preload" data-theme="<?= htmlspecialchars(getUiTheme(), ENT_QUOTES) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_csrfToken, ENT_QUOTES) ?>">
    <title>Zaemit - <?= htmlspecialchars($pageTitle) ?></title>
    <!-- 페이지 로드 중 트랜지션 전면 차단 · Tailwind CDN 이 런타임에 스타일을 주입하므로,
         로드 직후 transition-* 클래스가 "무스타일 → 적용" 변화를 애니메이션으로 재생하는 것을 막는다.
         window load 후 클래스 제거 (footer.php) -->
    <style>
        html.bms-preload *, html.bms-preload *::before, html.bms-preload *::after {
            transition: none !important;
            animation-duration: 0s !important;
        }
    </style>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F6AFF',
                        'primary-dark': '#3B54D4',
                        'primary-light': '#E8ECFF',
                        sidebar: '#F8F9FA',
                        'sidebar-active': '#EEF1FF',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/custom.css?v=<?= @filemtime(__DIR__ . '/../assets/css/custom.css') ?: time() ?>">
    <script defer src="<?= $basePath ?>/assets/js/export.js"></script>
    <script src="<?= $basePath ?>/assets/js/approval-ui.js"></script>
    <script>
    window.__pageTitle = <?= json_encode($pageTitle, JSON_UNESCAPED_UNICODE) ?>;
    window.__suppressAutoTitle = <?= json_encode(!empty($suppressAutoTitle)) ?>;
    window.ORG_LABELS = <?= getOrgLabelsForJS() ?>;
    window.TERMINOLOGY = <?= json_encode(getTerminologyForJS(), JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen antialiased">

<!-- 상단 헤더바 -->
<?php
$__headerUser = getCurrentUser();
$__headerProfileImage = '';
if ($__headerUser && !empty($__headerUser['profile_image'])) {
    $__rawProfileImage = trim((string)$__headerUser['profile_image']);
    if (str_starts_with($__rawProfileImage, 'uploads/profiles/')) {
        $__headerProfileImage = rtrim($basePath, '/') . '/' . ltrim($__rawProfileImage, '/');
    } elseif (str_starts_with($__rawProfileImage, '/uploads/profiles/')) {
        $__headerProfileImage = $__rawProfileImage;
    }
}
?>
<header class="fixed top-0 left-0 right-0 h-14 bg-slate-900 border-b border-slate-800 z-50 flex items-center justify-between px-4">
    <div class="flex items-center gap-3 min-w-0 pl-2">
        <button id="sidebarToggle" type="button"
                class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-slate-700 text-slate-200 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/50"
                aria-label="주 메뉴 열기" aria-controls="sidebar" aria-expanded="false">
            <i data-lucide="panel-left-open" class="h-5 w-5"></i>
        </button>
        <a href="<?= $basePath ?>/pages/dashboard.php" class="flex items-center gap-2 hover:opacity-80 transition-opacity flex-shrink-0">
            <div class="w-7 h-7 bg-primary rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13 3L4 14h7l-2 7 9-11h-7l2-7z"/>
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-100">Zaemit</span>
        </a>
        <!-- 페이지 브레드크럼 -->
        <nav aria-label="브레드크럼" class="hidden md:flex items-center gap-2 min-w-0 pl-3 ml-1 border-l border-slate-800">
            <span class="text-sm text-slate-200 font-medium truncate"><?= htmlspecialchars($pageTitle) ?></span>
        </nav>
    </div>
    <div class="flex items-center gap-2">
        <!-- 알림 벨 -->
        <div class="relative" id="notifWrap">
            <button id="notifBtn" onclick="toggleNotifDropdown()" aria-label="알림" aria-haspopup="true" aria-expanded="false" class="relative p-2 hover:bg-slate-800 rounded-lg transition-colors">
                <i data-lucide="bell" class="w-5 h-5 text-slate-300"></i>
                <span id="notifBadge" <?php if ($__notifCount <= 0): ?>class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 bg-rose-500 text-white text-[11px] font-bold rounded-full flex items-center justify-center"<?php else: ?>aria-label="읽지 않은 알림 <?= $__notifCount ?>건" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 bg-rose-500 text-white text-[11px] font-bold rounded-full flex items-center justify-center"<?php endif; ?>><?= $__notifCount > 0 ? $__notifCount : '' ?></span>
            </button>
            <!-- 알림 드롭다운 -->
            <div id="notifDropdown" role="menu" class="hidden absolute right-0 top-10 w-80 bg-slate-900 rounded-2xl shadow-xl border border-slate-800 z-50 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                    <p class="text-sm font-semibold text-slate-100">알림</p>
                    <button onclick="markAllNotifRead()" class="text-xs text-primary hover:underline">전체 읽음</button>
                </div>
                <div class="max-h-72 overflow-y-auto" id="notifList">
                    <div class="px-4 py-8 text-center text-sm text-slate-500">불러오는 중...</div>
                </div>
            </div>
        </div>

        <!-- 도움말 (아이콘) -->
        <a href="<?= $basePath ?>/pages/manual.php"
           aria-label="사용자 매뉴얼"
           class="p-2 text-slate-300 hover:bg-slate-800 hover:text-primary rounded-lg transition-colors">
            <i data-lucide="help-circle" class="w-5 h-5"></i>
        </a>

        <!-- 사용자 프로필 -->
        <?php if ($__headerUser): ?>
        <div class="relative" id="userWrap">
            <button id="userBtn" onclick="toggleUserDropdown()" aria-label="사용자 메뉴" aria-haspopup="true" aria-expanded="false"
                    class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-slate-800 transition-colors">
                <div id="headerAvatar" class="w-7 h-7 bg-primary/10 rounded-full flex items-center justify-center overflow-hidden">
                    <?php if ($__headerProfileImage !== ''): ?>
                        <img src="<?= htmlspecialchars($__headerProfileImage, ENT_QUOTES) ?>" alt="프로필 사진" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i data-lucide="user" class="w-3.5 h-3.5 text-primary"></i>
                    <?php endif; ?>
                </div>
                <span class="hidden sm:inline text-sm text-slate-200 font-medium truncate max-w-[120px]"><?= htmlspecialchars($__headerUser['name'] ?? '사용자') ?></span>
            </button>
            <div id="userDropdown" role="menu" class="hidden absolute right-0 top-11 w-56 bg-slate-900 rounded-xl shadow-xl border border-slate-800 z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <p class="text-sm font-semibold text-slate-100 truncate"><?= htmlspecialchars($__headerUser['name'] ?? '사용자') ?></p>
                    <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($__headerUser['email'] ?? '') ?></p>
                </div>
                <a href="<?= $basePath ?>/pages/my_profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-200 hover:bg-slate-950">
                    <i data-lucide="user-cog" class="w-4 h-4 text-slate-400"></i>내 정보
                </a>
                <a href="<?= $basePath ?>/pages/manual.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-200 hover:bg-slate-950">
                    <i data-lucide="book-open" class="w-4 h-4 text-slate-400"></i>사용자 매뉴얼
                </a>
                <a href="<?= $basePath ?>/pages/login.php?action=logout" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 border-t border-slate-800">
                    <i data-lucide="log-out" class="w-4 h-4"></i>로그아웃
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>
<script>
var __notifBasePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;
var __NOTIF_TYPE_MAP = {
    approval_pending:    { icon: 'file-check',    bg: 'zm-notif-info',      iconColor: 'zm-notif-info-icon' },
    approval_approved:   { icon: 'check-circle',  bg: 'zm-notif-success',   iconColor: 'zm-notif-success-icon' },
    approval_rejected:   { icon: 'x-circle',      bg: 'zm-notif-danger',    iconColor: 'zm-notif-danger-icon' },
    approval_cooperated: { icon: 'handshake',      bg: 'zm-notif-info',      iconColor: 'zm-notif-info-icon' },
    approval_referenced: { icon: 'eye',            bg: 'zm-notif-neutral',   iconColor: 'zm-notif-neutral-icon' },
    approval_progress:   { icon: 'arrow-right',    bg: 'zm-notif-info',      iconColor: 'zm-notif-info-icon' },
    doc_request:         { icon: 'send',           bg: 'zm-notif-warning',   iconColor: 'zm-notif-warning-icon' },
    doc_upload:          { icon: 'upload',         bg: 'zm-notif-info',      iconColor: 'zm-notif-info-icon' },
    doc_confirmed:       { icon: 'check-circle',   bg: 'zm-notif-success',   iconColor: 'zm-notif-success-icon' },
    approval_line_changed: { icon: 'git-branch',   bg: 'zm-notif-info',      iconColor: 'zm-notif-info-icon' }
};

function __notifTimeAgo(dateStr) {
    var now = Date.now();
    var then = new Date(dateStr.replace(' ', 'T')).getTime();
    var diff = Math.floor((now - then) / 1000);
    if (diff < 60) return '방금';
    if (diff < 3600) return Math.floor(diff / 60) + '분 전';
    if (diff < 86400) return Math.floor(diff / 3600) + '시간 전';
    if (diff < 604800) return Math.floor(diff / 86400) + '일 전';
    return dateStr.substring(0, 10);
}

function __notifEsc(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function __updateNotifBadge(count) {
    var b = document.getElementById('notifBadge');
    if (!b) return;
    if (count > 0) {
        b.textContent = count > 99 ? '99+' : count;
        b.setAttribute('aria-label', '읽지 않은 알림 ' + count + '건');
        b.classList.remove('hidden');
    } else {
        b.textContent = '';
        b.classList.add('hidden');
    }
}

function __renderNotifList(items) {
    var list = document.getElementById('notifList');
    if (!list) return;
    if (!items || items.length === 0) {
        list.innerHTML = '<div class="px-4 py-8 text-center text-sm text-slate-500"><i data-lucide="bell-off" class="inline w-6 h-6 text-slate-600 mb-1"></i><p>알림이 없습니다</p></div>';
        if (window.lucide) lucide.createIcons({ nodes: [list] });
        return;
    }
    var html = '';
    items.forEach(function(n) {
        var meta = __NOTIF_TYPE_MAP[n.type] || { icon: 'bell', bg: 'bg-slate-800', iconColor: 'text-slate-400' };
        var isUnread = n.is_read === '0' || n.is_read === 0;
        var rowBg = isUnread ? 'bg-primary-light/50' : '';
        var rawLink = n.link_url || '';
        var href = (rawLink && /^\/pages\//.test(rawLink)) ? (__notifBasePath + rawLink) : '#';

        html += '<a href="' + __notifEsc(href) + '" data-notif-id="' + n.id + '" onclick="__notifClick(event,' + n.id + ')" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-800/60 transition-colors border-b border-slate-800 ' + rowBg + '">';
        html += '<div class="w-8 h-8 rounded-full ' + meta.bg + ' flex items-center justify-center flex-shrink-0 mt-0.5"><i data-lucide="' + __notifEsc(meta.icon) + '" class="w-3.5 h-3.5 ' + meta.iconColor + '"></i></div>';
        html += '<div class="flex-1 min-w-0">';
        html += '<p class="text-sm font-medium text-slate-100">' + __notifEsc(n.title) + '</p>';
        if (n.message) html += '<p class="text-xs text-slate-300 mt-0.5 line-clamp-2">' + __notifEsc(n.message) + '</p>';
        html += '<p class="text-xs text-slate-400 mt-1">' + __notifTimeAgo(n.created_at) + '</p>';
        html += '</div>';
        if (isUnread) html += '<span class="w-2 h-2 rounded-full bg-primary flex-shrink-0 mt-1.5" aria-label="읽지 않음"></span>';
        html += '</a>';
    });
    list.innerHTML = html;
    if (window.lucide) lucide.createIcons({ nodes: [list] });
}

function __notifClick(e, id) {
    fetch(__notifBasePath + '/api/notifications.php?action=markRead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    }).catch(function() {});
}

function __loadNotifications() {
    fetch(__notifBasePath + '/api/notifications.php?action=getNotifications')
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.ok) return;
        __renderNotifList(res.data.items);
        __updateNotifBadge(res.data.unread_count);
    })
    .catch(function() {});
}

function __pollUnreadCount() {
    fetch(__notifBasePath + '/api/notifications.php?action=getUnreadCount')
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) __updateNotifBadge(res.data.count);
    })
    .catch(function() {});
}

function toggleNotifDropdown() {
    var dd = document.getElementById('notifDropdown');
    var btn = document.getElementById('notifBtn');
    var wasHidden = dd.classList.contains('hidden');
    dd.classList.toggle('hidden');
    btn.setAttribute('aria-expanded', wasHidden ? 'true' : 'false');
    if (wasHidden) __loadNotifications();
}

function markAllNotifRead() {
    fetch(__notifBasePath + '/api/notifications.php?action=markAllRead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: '{}'
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.ok) return;
        __updateNotifBadge(0);
        __loadNotifications();
    })
    .catch(function() {});
}

setInterval(__pollUnreadCount, 30000);
function toggleUserDropdown() {
    var dd = document.getElementById('userDropdown');
    var btn = document.getElementById('userBtn');
    if (!dd || !btn) return;
    dd.classList.toggle('hidden');
    btn.setAttribute('aria-expanded', dd.classList.contains('hidden') ? 'false' : 'true');
}
document.addEventListener('click', function(e) {
    var nw = document.getElementById('notifWrap');
    if (nw && !nw.contains(e.target)) {
        var dd = document.getElementById('notifDropdown');
        if (dd) dd.classList.add('hidden');
        var nb = document.getElementById('notifBtn');
        if (nb) nb.setAttribute('aria-expanded', 'false');
    }
    var uw = document.getElementById('userWrap');
    if (uw && !uw.contains(e.target)) {
        var ud = document.getElementById('userDropdown');
        if (ud) ud.classList.add('hidden');
        var ub = document.getElementById('userBtn');
        if (ub) ub.setAttribute('aria-expanded', 'false');
    }
});
// ESC 키로 드롭다운/모달 일괄 닫기
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    ['notifDropdown','userDropdown'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && !el.classList.contains('hidden')) el.classList.add('hidden');
    });
});

// ─────────────────────────────────────────────────────────────
// CSRF 토큰 자동 주입: 동일 출처 대상 상태변경 요청에 X-CSRF-Token 추가
// ─────────────────────────────────────────────────────────────
(function() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return;
    var token = meta.getAttribute('content') || '';
    if (!token) return;
    var _fetch = window.fetch;
    window.fetch = function(input, init) {
        init = init || {};
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();
        var isState = method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS';
        // 외부 도메인 요청이면 토큰 주입하지 않음
        var url = typeof input === 'string' ? input : (input && input.url) || '';
        var isSameOrigin = !/^https?:\/\//i.test(url) || url.indexOf(location.origin) === 0;
        if (isState && isSameOrigin) {
            var headers = new Headers(init.headers || (typeof input !== 'string' && input.headers) || {});
            if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', token);
            init.headers = headers;
        }
        return _fetch.call(this, input, init);
    };
    // 전역 헬퍼: form에 <input name="_csrf"> 자동 삽입 가능
    window.__csrfToken = token;
})();
</script>
