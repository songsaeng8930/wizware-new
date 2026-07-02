/**
 * Zaemit 커스텀 데이트피커
 * 모든 <input type="date">에 자동 부착.
 * 연도 드롭다운 + 월 그리드 + 일 그리드.
 */
(function() {
    var DAYS = ['일','월','화','수','목','금','토'];
    var MONTHS_SHORT = ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'];

    var activeInput = null;
    var panel = null;
    var viewYear, viewMonth, selYear, selMonth, selDay;
    var mode = 'day'; // 'day' | 'month' | 'year'

    function create() {
        panel = document.createElement('div');
        panel.className = 'zm-dp';
        panel.innerHTML = '<div class="zm-dp-head"></div><div class="zm-dp-body"></div>';
        document.body.appendChild(panel);
        panel.addEventListener('mousedown', function(e) { e.preventDefault(); });
        panel.addEventListener('click', handleClick);
    }

    function open(input) {
        if (!panel) create();
        activeInput = input;
        mode = 'day';
        var v = input.value;
        var today = new Date();
        if (v && /^\d{4}-\d{2}-\d{2}$/.test(v)) {
            var parts = v.split('-');
            selYear = +parts[0]; selMonth = +parts[1]; selDay = +parts[2];
        } else {
            selYear = today.getFullYear(); selMonth = today.getMonth() + 1; selDay = today.getDate();
        }
        viewYear = selYear; viewMonth = selMonth;
        render();
        position(input);
        panel.classList.add('zm-dp-open');
    }

    function close() {
        if (panel) panel.classList.remove('zm-dp-open');
        activeInput = null;
    }

    function position(input) {
        var r = input.getBoundingClientRect();
        var st = window.scrollY || document.documentElement.scrollTop;
        var sl = window.scrollX || document.documentElement.scrollLeft;
        var top = r.bottom + st + 4;
        var left = r.left + sl;
        if (top + 320 > st + window.innerHeight) top = r.top + st - 324;
        if (left + 280 > sl + window.innerWidth) left = sl + window.innerWidth - 284;
        panel.style.top = top + 'px';
        panel.style.left = Math.max(4, left) + 'px';
    }

    function render() {
        if (mode === 'day') renderDays();
        else if (mode === 'month') renderMonths();
        else renderYears();
    }

    function renderDays() {
        var head = panel.querySelector('.zm-dp-head');
        var body = panel.querySelector('.zm-dp-body');
        head.innerHTML =
            '<button class="zm-dp-nav" data-act="prev-m">&lsaquo;</button>' +
            '<button class="zm-dp-title" data-act="to-month">' + viewYear + '년 ' + viewMonth + '월</button>' +
            '<button class="zm-dp-nav" data-act="next-m">&rsaquo;</button>';

        var html = '<div class="zm-dp-dow">';
        for (var i = 0; i < 7; i++) html += '<span>' + DAYS[i] + '</span>';
        html += '</div><div class="zm-dp-grid">';

        var first = new Date(viewYear, viewMonth - 1, 1);
        var startDow = first.getDay();
        var daysInMonth = new Date(viewYear, viewMonth, 0).getDate();
        var prevDays = new Date(viewYear, viewMonth - 1, 0).getDate();
        var today = new Date();
        var tY = today.getFullYear(), tM = today.getMonth() + 1, tD = today.getDate();

        for (var d = startDow - 1; d >= 0; d--) {
            html += '<button class="zm-dp-d other" data-act="prev-d" data-d="' + (prevDays - d) + '">' + (prevDays - d) + '</button>';
        }
        for (var d = 1; d <= daysInMonth; d++) {
            var cls = 'zm-dp-d';
            if (d === tD && viewMonth === tM && viewYear === tY) cls += ' today';
            if (d === selDay && viewMonth === selMonth && viewYear === selYear) cls += ' sel';
            html += '<button class="' + cls + '" data-act="pick" data-d="' + d + '">' + d + '</button>';
        }
        var total = startDow + daysInMonth;
        var rem = total % 7 === 0 ? 0 : 7 - (total % 7);
        for (var d = 1; d <= rem; d++) {
            html += '<button class="zm-dp-d other" data-act="next-d" data-d="' + d + '">' + d + '</button>';
        }
        html += '</div><div class="zm-dp-foot">' +
            '<button data-act="clear">삭제</button>' +
            '<button data-act="today">오늘</button></div>';
        body.innerHTML = html;
    }

    function renderMonths() {
        var head = panel.querySelector('.zm-dp-head');
        var body = panel.querySelector('.zm-dp-body');
        head.innerHTML =
            '<button class="zm-dp-nav" data-act="prev-y">&lsaquo;</button>' +
            '<button class="zm-dp-title" data-act="to-year">' + viewYear + '년</button>' +
            '<button class="zm-dp-nav" data-act="next-y">&rsaquo;</button>';

        var html = '<div class="zm-dp-mgrid">';
        var today = new Date();
        for (var m = 0; m < 12; m++) {
            var cls = 'zm-dp-m';
            if (m + 1 === selMonth && viewYear === selYear) cls += ' sel';
            if (m + 1 === today.getMonth() + 1 && viewYear === today.getFullYear()) cls += ' today';
            html += '<button class="' + cls + '" data-act="pick-m" data-m="' + (m + 1) + '">' + MONTHS_SHORT[m] + '</button>';
        }
        html += '</div>';
        body.innerHTML = html;
    }

    function renderYears() {
        var head = panel.querySelector('.zm-dp-head');
        var body = panel.querySelector('.zm-dp-body');
        var startY = viewYear - viewYear % 10 - 1;
        head.innerHTML =
            '<button class="zm-dp-nav" data-act="prev-dec">&lsaquo;</button>' +
            '<button class="zm-dp-title">' + (startY + 1) + ' ~ ' + (startY + 10) + '</button>' +
            '<button class="zm-dp-nav" data-act="next-dec">&rsaquo;</button>';

        var html = '<div class="zm-dp-ygrid">';
        var today = new Date();
        for (var i = 0; i < 12; i++) {
            var y = startY + i;
            var cls = 'zm-dp-y';
            if (i === 0 || i === 11) cls += ' other';
            if (y === selYear) cls += ' sel';
            if (y === today.getFullYear()) cls += ' today';
            html += '<button class="' + cls + '" data-act="pick-y" data-y="' + y + '">' + y + '</button>';
        }
        html += '</div>';
        body.innerHTML = html;
    }

    function handleClick(e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) return;
        var act = btn.dataset.act;
        switch (act) {
            case 'prev-m': viewMonth--; if (viewMonth < 1) { viewMonth = 12; viewYear--; } render(); break;
            case 'next-m': viewMonth++; if (viewMonth > 12) { viewMonth = 1; viewYear++; } render(); break;
            case 'prev-y': viewYear--; render(); break;
            case 'next-y': viewYear++; render(); break;
            case 'prev-dec': viewYear -= 10; render(); break;
            case 'next-dec': viewYear += 10; render(); break;
            case 'to-month': mode = 'month'; render(); break;
            case 'to-year': mode = 'year'; render(); break;
            case 'pick':
                selYear = viewYear; selMonth = viewMonth; selDay = +btn.dataset.d;
                setValue(); close(); break;
            case 'prev-d':
                viewMonth--; if (viewMonth < 1) { viewMonth = 12; viewYear--; }
                selYear = viewYear; selMonth = viewMonth; selDay = +btn.dataset.d;
                setValue(); close(); break;
            case 'next-d':
                viewMonth++; if (viewMonth > 12) { viewMonth = 1; viewYear++; }
                selYear = viewYear; selMonth = viewMonth; selDay = +btn.dataset.d;
                setValue(); close(); break;
            case 'pick-m':
                viewMonth = +btn.dataset.m; mode = 'day'; render(); break;
            case 'pick-y':
                viewYear = +btn.dataset.y; mode = 'month'; render(); break;
            case 'today':
                var t = new Date();
                selYear = t.getFullYear(); selMonth = t.getMonth() + 1; selDay = t.getDate();
                setValue(); close(); break;
            case 'clear':
                if (activeInput) {
                    activeInput.value = '';
                    activeInput.dispatchEvent(new Event('change', {bubbles: true}));
                    activeInput.dispatchEvent(new Event('input', {bubbles: true}));
                }
                close(); break;
        }
    }

    function setValue() {
        if (!activeInput) return;
        var v = selYear + '-' + pad(selMonth) + '-' + pad(selDay);
        activeInput.value = v;
        activeInput.dispatchEvent(new Event('change', {bubbles: true}));
        activeInput.dispatchEvent(new Event('input', {bubbles: true}));
    }

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function attach(input) {
        if (input._zmDp) return;
        input._zmDp = true;
        input.addEventListener('click', function(e) {
            e.preventDefault();
            if (activeInput === this && panel && panel.classList.contains('zm-dp-open')) {
                close();
            } else {
                open(this);
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') close();
        });
    }

    function init() {
        document.querySelectorAll('input[type="date"]').forEach(attach);
        new MutationObserver(function() {
            document.querySelectorAll('input[type="date"]:not([data-zm-dp])').forEach(function(el) {
                el.setAttribute('data-zm-dp', '1');
                attach(el);
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener('mousedown', function(e) {
        if (panel && !panel.contains(e.target) && e.target !== activeInput) close();
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
