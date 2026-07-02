/**
 * labor.js — 노무관리 공통 유틸 (탭 전환, 테이블 필터, 공용 헬퍼)
 * PHP 변수는 labor.php의 인라인 <script> 블록에서 LABOR_DATA 객체로 전달됨.
 */

// ============================================================
// 탭 전환 (새로고침 없이)
// ============================================================
function switchLaborTab(tab) {
    document.querySelectorAll('[data-labor-tab]').forEach(function(p) {
        p.hidden = p.dataset.laborTab !== tab;
    });
    document.querySelectorAll('[data-labor-tab-btn]').forEach(function(b) {
        b.classList.toggle('active', b.dataset.laborTabBtn === tab);
    });
    var url = new URL(window.location);
    url.searchParams.set('tab', tab);
    history.replaceState(null, '', url);

    // 사이드바 active 상태 동기화
    var sidebar = document.getElementById('submenu-labor');
    if (sidebar) {
        var activeClasses = ['text-primary', 'bg-primary-light', 'font-medium'];
        var inactiveClasses = ['text-slate-400', 'hover:text-slate-100', 'hover:bg-slate-950'];
        sidebar.querySelectorAll('a').forEach(function(a) {
            var href = a.getAttribute('href') || '';
            var match = href.match(/[?&]tab=([^&]*)/);
            var linkTab = match ? match[1] : 'contract';
            a.classList.remove(...activeClasses, ...inactiveClasses);
            if (linkTab === tab) {
                a.classList.add(...activeClasses);
            } else {
                a.classList.add(...inactiveClasses);
            }
        });
    }
    lucide.createIcons();
}

// 사이드바 노무관리 링크 → JS 탭 전환으로 가로채기
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('submenu-labor');
    if (!sidebar) return;
    sidebar.querySelectorAll('a').forEach(function(a) {
        var href = a.getAttribute('href') || '';
        var match = href.match(/[?&]tab=([^&]*)/);
        if (match) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                switchLaborTab(match[1]);
            });
        }
    });
});

// 테이블 이름 검색
function filterTable(inputId, tableId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    document.getElementById(tableId).querySelectorAll('tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// HTML 이스케이프
function escHtml(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// 토스트 메시지
function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-20 right-6 z-[100] px-4 py-3 rounded-lg shadow-lg text-sm text-white transition-all '
        + (type === 'error' ? 'bg-rose-500' : 'bg-emerald-500');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2500);
}

// 날짜 포맷 (16자리까지)
function fmtDt(s) {
    if (!s) return '';
    var t = String(s);
    return t.length >= 16 ? t.slice(0, 16) : t;
}
