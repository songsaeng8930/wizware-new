/**
 * Zaemit 그룹웨어 — 일정 관리 (FullCalendar v6 기반)
 */
(function () {
'use strict';

const { basePath, categories, employees, hasDB } = window.__scheduleConfig;

const COLOR_HEX = {
    blue: '#3b82f6', green: '#22c55e', red: '#ef4444', purple: '#8b5cf6',
    yellow: '#eab308', orange: '#f97316', teal: '#14b8a6', pink: '#ec4899', gray: '#94a3b8',
};
const COLOR_MAP = {};
Object.keys(COLOR_HEX).forEach(k => {
    COLOR_MAP[k] = { bar: 'cal-bar-' + k, dot: 'cal-dot-' + k, hex: COLOR_HEX[k] };
});

const CE_COLOR_MAP = {
    tax:       { bar: 'cal-bar-rose',   dot: 'cal-dot-rose',   label: '세무',     hex: '#f43f5e' },
    insurance: { bar: 'cal-bar-cyan',   dot: 'cal-dot-cyan',   label: '4대보험',  hex: '#06b6d4' },
    labor:     { bar: 'cal-bar-amber',  dot: 'cal-dot-amber',  label: '노무',     hex: '#f59e0b' },
    company:   { bar: 'cal-bar-indigo', dot: 'cal-dot-indigo', label: '회사행사', hex: '#6366f1' },
};
const CE_SOURCE_COLORS = { tax: '#f43f5e', insurance: '#06b6d4', labor: '#f59e0b', company: '#6366f1' };

// 색상 팔레트 (Google Calendar 스타일)
const COLOR_PALETTE = [
    '#d50000','#e67c73','#f4511e','#f6bf26','#33b679',
    '#0b8043','#039be5','#3f51b5','#7986cb','#8e24aa',
    '#616161','#a79b8e',
];

// ── State ──
let calendar = null;
let curYear  = new Date().getFullYear();
let curMonth = new Date().getMonth();
let events = [];
let holidays = [];
let calendarEvents = [];
let overlayFilters = { schedules: true, holidays: true, tax: true, insurance: true, labor: true, company: true };
let categoryFilters = {};
let colorOverrides = {};
let selectedAttendees = { c: [], e: [] };
let saving = false;
let categoryExpanded = false;
let activeColorPicker = null;
let customCalendars = [];

categories.forEach(c => { categoryFilters[c.item_id] = true; });

// ═══════════════════════════════════════
//  Init
// ═══════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    Promise.all([loadColorOverrides(), loadCustomCalendars()]).then(() => {
        initCalendar();
        renderCalendarList();
    });
    populateCalendarSelect('cCalendar');
    populateCalendarSelect('eCalendar');
    initAttendeeInput('c');
    initAttendeeInput('e');
    initTaskPanel();
    initSearchBar();
    lucide.createIcons();
});

// ═══════════════════════════════════════
//  일정 검색
// ═══════════════════════════════════════
let searchPeriod = 'month';
let searchSource = 'all';

function initSearchBar() {
    const input = document.getElementById('scheduleSearch');
    const box = document.getElementById('searchResults');
    if (!input || !box) return;
    let timer = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => runSearch(input.value.trim(), box), 300);
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { input.value = ''; box.classList.add('hidden'); }
    });

    document.querySelectorAll('#searchPeriodChips .search-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#searchPeriodChips .search-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            searchPeriod = btn.dataset.period;
            const q = input.value.trim();
            if (q) runSearch(q, box);
        });
    });
}

function runSearch(q, box) {
    if (!q || q.length < 1) { box.classList.add('hidden'); return; }

    const params = new URLSearchParams({ action: 'searchEvents', q, period: searchPeriod, source: searchSource });
    if (searchPeriod === 'month' || searchPeriod === 'year') {
        params.set('year', curYear);
        if (searchPeriod === 'month') params.set('month', curMonth + 1);
    }

    box.innerHTML = '<p class="text-xs text-slate-500 text-center py-2">검색 중...</p>';
    box.classList.remove('hidden');

    fetch(`${basePath}/api/schedule.php?${params}`)
        .then(r => r.json())
        .then(res => {
            const results = res.data?.results || [];
            if (!results.length) {
                box.innerHTML = '<p class="text-xs text-slate-500 text-center py-2">결과 없음</p>';
                return;
            }
            const SOURCE_COLORS = { schedule: '#4F6AFF', tax: '#f43f5e', insurance: '#06b6d4', labor: '#f59e0b', company: '#6366f1' };
            box.innerHTML = results.map(r => {
                const color = r._source === 'schedule'
                    ? (COLOR_HEX[r.color_code] || SOURCE_COLORS.schedule)
                    : (SOURCE_COLORS[r.category] || SOURCE_COLORS.schedule);
                const click = r._source === 'schedule' ? `openDetailModal(${r.id})` : `openCEDetail(${r.id})`;
                return `<div class="flex items-center gap-2 p-1.5 rounded hover:bg-slate-800 cursor-pointer text-sm" onclick="${click}">
                    <span class="w-2 h-2 rounded-full shrink-0" style="background:${color}"></span>
                    <span class="truncate text-slate-200">${esc(r.title)}</span>
                    <span class="text-xs text-slate-500 ml-auto shrink-0">${r.start_date}</span>
                </div>`;
            }).join('');
        })
        .catch(() => { box.innerHTML = '<p class="text-xs text-slate-500 text-center py-2">검색 오류</p>'; });
}

// ═══════════════════════════════════════
//  FullCalendar
// ═══════════════════════════════════════
function initCalendar() {
    const calEl = document.getElementById('fcCalendar');
    calendar = new FullCalendar.Calendar(calEl, {
        initialView: 'dayGridMonth',
        locale: 'ko',
        height: '100%',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        buttonText: { today: '오늘', month: '월', week: '주', day: '일', list: '목록' },
        titleFormat: { year: 'numeric', month: 'long' },
        selectable: true,
        editable: true,
        dayMaxEvents: 3,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        nowIndicator: true,
        firstDay: 0,
        fixedWeekCount: false,
        dayCellContent: function(arg) { return { html: String(arg.date.getDate()) }; },

        events: fetchFcEvents,

        eventClick: function (info) {
            info.jsEvent.preventDefault();
            const p = info.event.extendedProps;
            if (p._source === 'schedule') openDetailModal(p._id);
            else if (p._source === 'calendar_event') openCEDetail(p._id);
        },

        select: function (info) {
            if (!hasDB) { showToast('DB 미연결 상태에서는 등록할 수 없습니다', 'error'); return; }
            const dateStr = info.startStr.split('T')[0];
            openCreateModal(dateStr);
            calendar.unselect();
        },

        eventDrop: function (info) {
            const p = info.event.extendedProps;
            if (p._source !== 'schedule') { info.revert(); return; }
            handleEventMove(p._id, info.event, info.revert);
        },

        eventResize: function (info) {
            const p = info.event.extendedProps;
            if (p._source !== 'schedule') { info.revert(); return; }
            handleEventMove(p._id, info.event, info.revert);
        },

        datesSet: function (info) {
            const mid = new Date((info.start.getTime() + info.end.getTime()) / 2);
            curYear = mid.getFullYear();
            curMonth = mid.getMonth();
            loadSideData();
        },

        eventDidMount: function (info) {
            info.el.removeAttribute('title');
            if (info.event.extendedProps._source === 'holiday') {
                info.el.style.cursor = 'default';
            }
            if (info.event.extendedProps.isDeadline) {
                info.el.style.fontWeight = '600';
            }
        },

        viewDidMount: function () {
            stripToolbarTitles();
        },
    });
    calendar.render();
    stripToolbarTitles();
}

function stripToolbarTitles() {
    const el = document.getElementById('fcCalendar');
    if (!el) return;
    el.querySelectorAll('.fc-header-toolbar button[title]').forEach(btn => btn.removeAttribute('title'));
    el.querySelectorAll('.fc-header-toolbar [title]').forEach(node => node.removeAttribute('title'));
}

function fetchFcEvents(fetchInfo, successCallback, failureCallback) {
    const start = fetchInfo.startStr.split('T')[0];
    const end   = fetchInfo.endStr.split('T')[0];

    fetch(`${basePath}/api/schedule.php?action=getEventsForCalendar&start=${start}&end=${end}`)
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                successCallback(applyFilters(data.data.events));
            } else {
                failureCallback(new Error(data.error?.message || '데이터 로드 실패'));
            }
        })
        .catch(err => failureCallback(err));
}

function applyFilters(fcEvents) {
    return fcEvents.filter(ev => {
        const p = ev.extendedProps;
        if (p._source === 'schedule') {
            if (p.customCalendarId) {
                const ccKey = 'custom_' + p.customCalendarId;
                if (overlayFilters[ccKey] === false) return false;
            } else {
                if (!overlayFilters.schedules) return false;
            }
            if (p.categoryItemId && !categoryFilters[p.categoryItemId]) return false;
        } else if (p._source === 'holiday') {
            if (!overlayFilters.holidays) return false;
        } else if (p._source === 'calendar_event') {
            if (!overlayFilters[p.category]) return false;
        }
        return true;
    }).map(ev => applyColorOverride(ev));
}

function applyColorOverride(ev) {
    const p = ev.extendedProps;
    let overrideHex = null;

    if (p._source === 'schedule' && p.customCalendarId) {
        const ccKey = 'custom_' + p.customCalendarId;
        overrideHex = colorOverrides[ccKey] || p.customCalendarColor;
    } else if (p._source === 'schedule') {
        overrideHex = colorOverrides.schedules;
    } else if (p._source === 'holiday') {
        overrideHex = colorOverrides.holidays;
    } else if (p._source === 'calendar_event' && p.category) {
        overrideHex = colorOverrides[p.category];
    }

    if (!overrideHex) return ev;

    const copy = Object.assign({}, ev);
    copy.backgroundColor = overrideHex + '1f';
    copy.textColor = overrideHex;
    copy.borderColor = 'transparent';
    if (copy.extendedProps) {
        copy.extendedProps = Object.assign({}, copy.extendedProps, { colorHex: overrideHex });
    }
    return copy;
}

function handleEventMove(scheduleId, fcEvent, revert) {
    const allDay = fcEvent.allDay;
    const startDate = fcEvent.startStr.split('T')[0];
    let endDate, startTime = null, endTime = null;

    if (allDay) {
        if (fcEvent.end) {
            const end = new Date(fcEvent.end);
            end.setDate(end.getDate() - 1);
            endDate = fmtDate(end);
        } else {
            endDate = startDate;
        }
    } else {
        startTime = fcEvent.startStr.split('T')[1]?.substring(0, 5) || null;
        if (fcEvent.end) {
            endDate = fcEvent.endStr.split('T')[0];
            endTime = fcEvent.endStr.split('T')[1]?.substring(0, 5) || null;
        } else {
            endDate = startDate;
        }
    }

    fetch(`${basePath}/api/schedule.php?action=moveEvent`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: scheduleId, start_date: startDate, end_date: endDate, start_time: startTime, end_time: endTime, is_all_day: allDay }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast('일정이 이동되었습니다'); loadSideData(); }
        else { showToast(res.error || '이동 실패', 'error'); revert(); }
    })
    .catch(() => { showToast('서버 오류', 'error'); revert(); });
}

// ═══════════════════════════════════════
//  Side Data (기존 API → 미니캘린더 + 사이드패널)
// ═══════════════════════════════════════
function loadSideData() {
    const start = `${curYear}-${String(curMonth + 1).padStart(2, '0')}-01`;
    const lastDay = new Date(curYear, curMonth + 1, 0).getDate();
    const end = `${curYear}-${String(curMonth + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;

    fetch(`${basePath}/api/schedule.php?action=getEvents&start=${start}&end=${end}`)
        .then(r => r.json()).catch(() => ({ events: [] }))
        .then(data => {
            events = (data.events || []).map(e => ({ ...e, _source: 'schedule' }));
            holidays = (data.holidays || []).map(h => ({
                _source: 'holiday', _id: 'h_' + h.id,
                title: h.name, start_date: h.holiday_date, end_date: h.holiday_date,
                is_all_day: 1, _type: h.type,
            }));
            calendarEvents = (data.calendarEvents || []).map(ce => ({
                _source: 'calendar_event', _id: 'ce_' + ce.id, id: ce.id,
                title: ce.title, description: ce.description,
                start_date: ce.event_date, end_date: ce.end_date || ce.event_date,
                is_all_day: 1, category: ce.category,
                is_deadline: +ce.is_deadline, is_system: +ce.is_system,
                source_ref: ce.source_ref,
            }));
            renderSidePanel();
            renderCalendarList();
            renderMiniCalendar();
        });
}

// ═══════════════════════════════════════
//  Side Panel (다가오는 일정)
// ═══════════════════════════════════════
function renderSidePanel() {
    const panel = document.getElementById('sidePanel');
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    const items = [];

    events.forEach(ev => {
        if (!overlayFilters.schedules) return;
        if (ev.category_item_id && !categoryFilters[ev.category_item_id]) return;
        if (ev.end_date < todayStr) return;
        const c = COLOR_MAP[ev.color_code] || COLOR_MAP.blue;
        items.push({
            date: ev.start_date, dotCls: c.dot, title: ev.title,
            sub: ev.is_all_day == 1 ? '종일' : (ev.start_time ? ev.start_time.substring(0, 5) : ''),
            click: `openDetailModal(${ev.id})`, bold: false, sourceLabel: null,
        });
    });

    if (overlayFilters.holidays) {
        holidays.forEach(h => {
            if (h.start_date < todayStr) return;
            items.push({
                date: h.start_date, dotCls: 'cal-dot-red', title: h.title,
                sub: h.start_date, click: '', bold: false,
                sourceLabel: '공휴일', sourceColor: '#ef4444',
            });
        });
    }

    calendarEvents.forEach(ce => {
        if (!overlayFilters[ce.category]) return;
        if (ce.end_date < todayStr) return;
        const cc = CE_COLOR_MAP[ce.category] || CE_COLOR_MAP.tax;
        items.push({
            date: ce.start_date, dotCls: cc.dot, title: ce.title,
            sub: ce.start_date, click: `openCEDetail(${ce.id})`,
            bold: !!ce.is_deadline,
            sourceLabel: cc.label, sourceColor: CE_SOURCE_COLORS[ce.category],
        });
    });

    items.sort((a, b) => a.date.localeCompare(b.date));
    const upcoming = items.slice(0, 10);

    if (!upcoming.length) {
        panel.innerHTML = '<p class="text-sm text-slate-400 text-center py-6">등록된 일정이 없습니다</p>';
        return;
    }

    panel.innerHTML = upcoming.map(it => {
        const boldCls = it.bold ? ' font-semibold' : '';
        const badge = it.sourceLabel
            ? ` <span class="cal-source-badge" style="background:${it.sourceColor}15;color:${it.sourceColor}">${it.sourceLabel}</span>` : '';
        return `<div class="flex items-start gap-3 p-2 rounded-lg hover:bg-slate-950 cursor-pointer" ${it.click ? `onclick="${it.click}"` : ''}>
            <div class="w-1.5 h-1.5 mt-1.5 rounded-full ${it.dotCls} shrink-0"></div>
            <div class="min-w-0">
                <p class="text-sm text-slate-100 font-medium truncate${boldCls}">${esc(it.title)}${badge}</p>
                <p class="text-sm text-slate-400">${it.sub || it.date}</p>
            </div>
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════
//  Mini Calendar
// ═══════════════════════════════════════
function renderMiniCalendar() {
    const grid = document.getElementById('miniCalGrid');
    const titleEl = document.getElementById('miniCalTitle');
    if (!grid || !titleEl) return;

    titleEl.textContent = `${curYear}년 ${curMonth + 1}월`;

    const firstDay = new Date(curYear, curMonth, 1).getDay();
    const daysInMonth = new Date(curYear, curMonth + 1, 0).getDate();
    const today = new Date();

    const eventDates = new Set();
    if (overlayFilters.schedules) events.forEach(ev => {
        for (let d = new Date(ev.start_date); d <= new Date(ev.end_date); d.setDate(d.getDate() + 1)) {
            eventDates.add(d.toISOString().split('T')[0]);
        }
    });
    if (overlayFilters.holidays) holidays.forEach(h => eventDates.add(h.start_date));
    calendarEvents.forEach(ce => { if (overlayFilters[ce.category]) eventDates.add(ce.start_date); });

    let html = '';
    for (let i = 0; i < firstDay; i++) html += '<div class="mini-cal-cell"></div>';

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${curYear}-${String(curMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isToday = curYear === today.getFullYear() && curMonth === today.getMonth() && d === today.getDate();
        const weekday = (firstDay + d - 1) % 7;
        const hasEvent = eventDates.has(dateStr);

        let cls = 'mini-cal-cell mini-cal-day';
        if (isToday) cls += ' mini-cal-today';
        else if (weekday === 0) cls += ' mini-cal-sun';
        else if (weekday === 6) cls += ' mini-cal-sat';

        html += `<div class="${cls}" data-date="${dateStr}" onclick="miniCalClick('${dateStr}')">
            ${d}${hasEvent ? '<span class="mini-cal-dot"></span>' : ''}
        </div>`;
    }

    const remaining = (7 - (firstDay + daysInMonth) % 7) % 7;
    for (let i = 0; i < remaining; i++) html += '<div class="mini-cal-cell"></div>';
    grid.innerHTML = html;
}

// mini calendar navigation → drives FullCalendar
window.miniPrev = function () {
    curMonth--;
    if (curMonth < 0) { curMonth = 11; curYear--; }
    calendar.gotoDate(new Date(curYear, curMonth, 1));
};
window.miniNext = function () {
    curMonth++;
    if (curMonth > 11) { curMonth = 0; curYear++; }
    calendar.gotoDate(new Date(curYear, curMonth, 1));
};

window.miniCalClick = function (dateStr) {
    calendar.gotoDate(dateStr);

    const allItems = [];
    if (overlayFilters.holidays) holidays.filter(h => h.start_date === dateStr).forEach(h =>
        allItems.push(`<div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full cal-dot-red"></span><span class="text-xs text-red-400">${esc(h.title)}</span></div>`)
    );
    calendarEvents.filter(ce => overlayFilters[ce.category] && dateStr >= ce.start_date && dateStr <= ce.end_date).forEach(ce => {
        const cc = CE_COLOR_MAP[ce.category] || CE_COLOR_MAP.tax;
        allItems.push(`<div class="flex items-center gap-1.5 cursor-pointer hover:opacity-75" onclick="openCEDetail(${ce.id})"><span class="w-1.5 h-1.5 rounded-full ${cc.dot}"></span><span class="text-xs">${esc(ce.title)}</span></div>`);
    });
    if (overlayFilters.schedules) events.filter(ev => dateStr >= ev.start_date && dateStr <= ev.end_date).forEach(ev => {
        const c = COLOR_MAP[ev.color_code] || COLOR_MAP.blue;
        allItems.push(`<div class="flex items-center gap-1.5 cursor-pointer hover:opacity-75" onclick="openDetailModal(${ev.id})"><span class="w-1.5 h-1.5 rounded-full ${c.dot}"></span><span class="text-xs">${esc(ev.title)}</span></div>`);
    });

    const existing = document.getElementById('miniCalPopover');
    if (existing) existing.remove();
    if (!allItems.length) return;

    const cell = document.querySelector(`[data-date="${dateStr}"]`);
    if (!cell) return;
    const rect = cell.getBoundingClientRect();

    const pop = document.createElement('div');
    pop.id = 'miniCalPopover';
    pop.className = 'mini-cal-popover';
    pop.innerHTML = `<div class="text-xs font-semibold text-slate-300 mb-1.5">${dateStr}</div>${allItems.join('')}`;
    pop.style.top = (rect.bottom + 4) + 'px';
    pop.style.left = Math.max(rect.left, 8) + 'px';
    document.body.appendChild(pop);

    const closePopover = (e) => { if (!pop.contains(e.target) && !cell.contains(e.target)) { pop.remove(); document.removeEventListener('click', closePopover, true); } };
    setTimeout(() => document.addEventListener('click', closePopover, true), 0);
};

// ═══════════════════════════════════════
//  Calendar List (왼쪽 패널 체크박스)
// ═══════════════════════════════════════
const CAL_LIST_DEFAULT = {
    schedules: '#4F6AFF', holidays: '#ef4444', tax: '#f43f5e',
    insurance: '#06b6d4', labor: '#f59e0b', company: '#6366f1',
};
const CAL_LIST = [
    { group: '내 캘린더', items: [
        { key: 'schedules', label: '내 일정' },
    ]},
    { group: '법정 캘린더', items: [
        { key: 'holidays',  label: '공휴일' },
        { key: 'tax',       label: '세무' },
        { key: 'insurance', label: '4대보험' },
        { key: 'labor',     label: '노무' },
        { key: 'company',   label: '회사행사' },
    ]},
];

function calColor(key) {
    if (colorOverrides[key]) return colorOverrides[key];
    if (CAL_LIST_DEFAULT[key]) return CAL_LIST_DEFAULT[key];
    if (key.startsWith('custom_')) {
        const ccId = parseInt(key.replace('custom_', ''), 10);
        const cc = customCalendars.find(c => c.id === ccId || c.id === String(ccId));
        if (cc) return cc.color_code;
    }
    return '#4F6AFF';
}

function loadColorOverrides() {
    if (!hasDB) return Promise.resolve();
    return fetch(`${basePath}/api/schedule.php?action=getColorOverrides`)
        .then(r => r.json()).catch(() => ({ ok: false }))
        .then(res => {
            if (res.ok && res.data) colorOverrides = res.data.overrides || {};
        });
}

function loadCustomCalendars() {
    if (!hasDB) return Promise.resolve();
    return fetch(`${basePath}/api/schedule.php?action=getCustomCalendars`)
        .then(r => r.json()).catch(() => ({ ok: false }))
        .then(res => {
            if (res.ok && res.data) {
                customCalendars = res.data.calendars || [];
                customCalendars.forEach(cc => {
                    const key = 'custom_' + cc.id;
                    if (!(key in overlayFilters)) overlayFilters[key] = true;
                    if (!CAL_LIST_DEFAULT[key]) CAL_LIST_DEFAULT[key] = cc.color_code;
                });
            }
        });
}

function saveCalColor(calKey, hex) {
    colorOverrides[calKey] = hex;
    renderCalendarList();
    calendar.refetchEvents();
    renderSidePanel();
    renderMiniCalendar();
    fetch(`${basePath}/api/schedule.php?action=saveColorOverride`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ calendar_key: calKey, color_code: hex }),
    }).catch(() => showToast('색상 저장 실패', 'error'));
}

function renderCalendarList() {
    const wrap = document.getElementById('calendarList');
    if (!wrap) return;

    wrap.innerHTML = CAL_LIST.map(g => {
        const itemsHtml = g.items.map(it => {
            const color = calColor(it.key);
            const checked = overlayFilters[it.key] ? 'checked' : '';
            const hasSubItems = it.key === 'schedules' && categories.length > 0;
            const chevron = hasSubItems
                ? `<button type="button" class="cal-list-toggle" data-toggle="categories">
                    <i data-lucide="${categoryExpanded ? 'chevron-down' : 'chevron-right'}" class="w-3 h-3"></i>
                   </button>` : '';

            let subHtml = '';
            if (hasSubItems && categoryExpanded) {
                subHtml = categories.map(c => {
                    const hex = catColorToHex(c.color_code);
                    const subChecked = categoryFilters[c.item_id] ? 'checked' : '';
                    return `<label class="cal-list-sub-item" data-catid="${c.item_id}">
                        <input type="checkbox" ${subChecked} class="cal-list-sub-cb" style="accent-color:${hex}">
                        <span class="cal-list-sub-dot" style="background:${hex}"></span>
                        <span class="cal-list-label">${esc(c.name)}</span>
                    </label>`;
                }).join('');
            }

            return `<div class="cal-list-item" data-calkey="${it.key}">
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" ${checked} class="cal-list-cb" style="accent-color:${color}">
                </label>
                <span class="cal-list-dot" style="background:${color}"></span>
                <span class="cal-list-label flex-1 min-w-0">${it.label}</span>
                ${chevron}
                <button type="button" class="cal-list-menu" data-calkey="${it.key}">⋮</button>
            </div>${subHtml}`;
        }).join('');

        // 커스텀 캘린더: '내 캘린더' 그룹에 추가
        let customHtml = '';
        if (g.group === '내 캘린더') {
            customHtml = customCalendars.map(cc => {
                const key = 'custom_' + cc.id;
                const color = calColor(key);
                const checked = overlayFilters[key] ? 'checked' : '';
                return `<div class="cal-list-item" data-calkey="${key}" data-custom-id="${cc.id}">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" ${checked} class="cal-list-cb" style="accent-color:${color}">
                    </label>
                    <span class="cal-list-dot" style="background:${color}"></span>
                    <span class="cal-list-label flex-1 min-w-0">${esc(cc.name)}</span>
                    <button type="button" class="cal-list-menu" data-calkey="${key}" data-custom-id="${cc.id}">⋮</button>
                </div>`;
            }).join('');
        }

        const addBtn = g.group === '내 캘린더'
            ? `<button type="button" id="btnAddCustomCal" class="cal-list-add-btn">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>캘린더 추가
               </button>` : '';

        return `<div class="cal-list-group">
            <div class="cal-list-header">${g.group}</div>
            ${itemsHtml}${customHtml}${addBtn}
        </div>`;
    }).join('');

    wrap.querySelectorAll('.cal-list-cb').forEach(cb => {
        cb.addEventListener('change', () => {
            const key = cb.closest('[data-calkey]').dataset.calkey;
            overlayFilters[key] = cb.checked;
            if (calendar) calendar.refetchEvents();
            renderSidePanel();
            renderMiniCalendar();
        });
    });

    wrap.querySelectorAll('.cal-list-menu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openCalMenu(btn.dataset.calkey, btn);
        });
    });

    wrap.querySelectorAll('.cal-list-sub-cb').forEach(cb => {
        cb.addEventListener('change', () => {
            const catId = cb.closest('[data-catid]').dataset.catid;
            categoryFilters[catId] = cb.checked;
            if (calendar) calendar.refetchEvents();
            renderSidePanel();
        });
    });

    wrap.querySelectorAll('[data-toggle="categories"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            categoryExpanded = !categoryExpanded;
            renderCalendarList();
            lucide.createIcons();
        });
    });

    const addBtn = document.getElementById('btnAddCustomCal');
    if (addBtn) {
        addBtn.addEventListener('click', () => openCustomCalForm());
    }

    lucide.createIcons();
}

// ── 커스텀 캘린더 생성/수정 폼 ──
function openCustomCalForm(editCal) {
    closeCalMenu();
    const existing = document.getElementById('customCalFormOverlay');
    if (existing) existing.remove();

    const name = editCal ? editCal.name : '';
    const color = editCal ? editCal.color_code : '#4F6AFF';
    const title = editCal ? '캘린더 수정' : '캘린더 추가';
    const btnLabel = editCal ? '수정' : '추가';

    const swatches = COLOR_PALETTE.map(hex => {
        const sel = hex === color ? ' cal-color-selected' : '';
        return `<button type="button" class="cal-color-swatch${sel}" style="background:${hex}" data-hex="${hex}"></button>`;
    }).join('');

    const overlay = document.createElement('div');
    overlay.id = 'customCalFormOverlay';
    overlay.className = 'fixed inset-0 z-[60] flex items-center justify-center bg-black/50';
    overlay.innerHTML = `
        <div class="bg-slate-800 rounded-lg shadow-xl p-5 w-80 border border-slate-700">
            <h3 class="text-sm font-semibold text-slate-200 mb-3">${title}</h3>
            <input type="text" id="ccalName" value="${esc(name)}" placeholder="캘린더 이름"
                class="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-600 rounded text-slate-200 mb-3 focus:outline-none focus:border-blue-500" maxlength="100">
            <div class="mb-3">
                <label class="text-xs text-slate-400 mb-1 block">색상</label>
                <div class="flex flex-wrap gap-1.5" id="ccalColors">${swatches}</div>
                <input type="hidden" id="ccalColor" value="${color}">
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" id="ccalCancel" class="px-3 py-1.5 text-xs rounded bg-slate-700 text-slate-300 hover:bg-slate-600">취소</button>
                <button type="button" id="ccalSubmit" class="px-3 py-1.5 text-xs rounded bg-blue-600 text-white hover:bg-blue-500">${btnLabel}</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);

    overlay.querySelectorAll('.cal-color-swatch').forEach(btn => {
        btn.addEventListener('click', () => {
            overlay.querySelectorAll('.cal-color-swatch').forEach(b => b.classList.remove('cal-color-selected'));
            btn.classList.add('cal-color-selected');
            document.getElementById('ccalColor').value = btn.dataset.hex;
        });
    });

    overlay.querySelector('#ccalCancel').addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

    overlay.querySelector('#ccalSubmit').addEventListener('click', () => {
        const n = document.getElementById('ccalName').value.trim();
        const c = document.getElementById('ccalColor').value;
        if (!n) { showToast('캘린더 이름을 입력해주세요', 'error'); return; }

        const action = editCal ? 'updateCustomCalendar' : 'createCustomCalendar';
        const body = editCal ? { id: editCal.id, name: n, color_code: c } : { name: n, color_code: c };

        fetch(`${basePath}/api/schedule.php?action=${action}`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        }).then(r => r.json()).then(res => {
            if (res.ok) {
                overlay.remove();
                loadCustomCalendars().then(() => {
                    renderCalendarList();
                    if (calendar) calendar.refetchEvents();
                });
                showToast(editCal ? '캘린더가 수정되었습니다' : '캘린더가 추가되었습니다', 'success');
            } else {
                showToast(res.error || '저장 실패', 'error');
            }
        }).catch(() => showToast('서버 오류', 'error'));
    });

    document.getElementById('ccalName').focus();
}

function deleteCustomCalendar(ccId) {
    if (!confirm('이 캘린더를 삭제하시겠습니까?\n연결된 일정은 "내 일정"으로 이동됩니다.')) return;

    fetch(`${basePath}/api/schedule.php?action=deleteCustomCalendar`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: ccId }),
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            const key = 'custom_' + ccId;
            delete overlayFilters[key];
            delete colorOverrides[key];
            loadCustomCalendars().then(() => {
                renderCalendarList();
                if (calendar) calendar.refetchEvents();
                renderSidePanel();
            });
            showToast('캘린더가 삭제되었습니다', 'success');
        } else {
            showToast(res.error || '삭제 실패', 'error');
        }
    }).catch(() => showToast('서버 오류', 'error'));
}

let activePickerCloseHandler = null;

function openCalMenu(calKey, anchorEl) {
    closeCalMenu();
    const rect = anchorEl.getBoundingClientRect();
    const pop = document.createElement('div');
    pop.id = 'calContextMenu';
    pop.className = 'cal-context-menu';
    pop.style.top = (rect.bottom + 4) + 'px';
    pop.style.left = Math.max(rect.right - 180, 8) + 'px';

    const current = calColor(calKey);
    const swatches = COLOR_PALETTE.map(hex => {
        const sel = hex === current ? ' cal-color-selected' : '';
        return `<button type="button" class="cal-color-swatch${sel}" style="background:${hex}" data-hex="${hex}"></button>`;
    }).join('');

    const isCustom = calKey.startsWith('custom_');
    const customEditHtml = isCustom ? `
        <button type="button" class="cal-menu-item" data-action="edit">
            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>캘린더 수정
        </button>
        <button type="button" class="cal-menu-item cal-menu-danger" data-action="delete">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>캘린더 삭제
        </button>` : '';

    pop.innerHTML = `
        <button type="button" class="cal-menu-item" data-action="only">
            <i data-lucide="eye" class="w-3.5 h-3.5"></i>이 캘린더만 보기
        </button>
        <button type="button" class="cal-menu-item" data-action="all">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>전체 캘린더 보기
        </button>
        ${customEditHtml}
        <div class="cal-menu-divider"></div>
        <div class="cal-menu-colors">${swatches}</div>`;

    document.body.appendChild(pop);
    activeColorPicker = pop;
    lucide.createIcons({ nodes: [pop] });

    pop.querySelectorAll('.cal-color-swatch').forEach(btn => {
        btn.addEventListener('click', () => {
            saveCalColor(calKey, btn.dataset.hex);
            closeCalMenu();
        });
    });

    if (isCustom) {
        const ccId = parseInt(calKey.replace('custom_', ''), 10);
        const editBtn = pop.querySelector('[data-action="edit"]');
        if (editBtn) editBtn.addEventListener('click', () => {
            closeCalMenu();
            const cc = customCalendars.find(c => c.id === ccId || c.id === String(ccId));
            if (cc) openCustomCalForm(cc);
        });
        const delBtn = pop.querySelector('[data-action="delete"]');
        if (delBtn) delBtn.addEventListener('click', () => {
            closeCalMenu();
            deleteCustomCalendar(ccId);
        });
    }

    pop.querySelector('[data-action="only"]').addEventListener('click', () => {
        Object.keys(overlayFilters).forEach(k => { overlayFilters[k] = (k === calKey); });
        renderCalendarList();
        if (calendar) calendar.refetchEvents();
        renderSidePanel();
        renderMiniCalendar();
        closeCalMenu();
    });

    pop.querySelector('[data-action="all"]').addEventListener('click', () => {
        Object.keys(overlayFilters).forEach(k => { overlayFilters[k] = true; });
        renderCalendarList();
        if (calendar) calendar.refetchEvents();
        renderSidePanel();
        renderMiniCalendar();
        closeCalMenu();
    });

    activePickerCloseHandler = (e) => {
        if (!pop.contains(e.target) && e.target !== anchorEl) {
            closeCalMenu();
        }
    };
    setTimeout(() => document.addEventListener('click', activePickerCloseHandler, true), 0);
}

function closeCalMenu() {
    if (activePickerCloseHandler) {
        document.removeEventListener('click', activePickerCloseHandler, true);
        activePickerCloseHandler = null;
    }
    if (activeColorPicker) { activeColorPicker.remove(); activeColorPicker = null; }
}

function catColorToHex(code) {
    return COLOR_HEX[code] || '#3b82f6';
}

// ═══════════════════════════════════════
//  Create Modal
// ═══════════════════════════════════════
window.openCreateModal = function (dateStr) {
    if (!hasDB) { showToast('DB 미연결 상태에서는 등록할 수 없습니다', 'error'); return; }
    const d = dateStr || new Date().toISOString().split('T')[0];
    document.getElementById('cTitle').value = '';
    document.getElementById('cStartDate').value = d;
    document.getElementById('cEndDate').value = d;
    document.getElementById('cStartTime').value = '09:00';
    document.getElementById('cEndTime').value = '10:00';
    document.getElementById('cAllDay').checked = false;
    document.getElementById('cDesc').value = '';
    toggleAllDay('c');
    selectedAttendees.c = [];
    renderAttendeeTags('c');
    populateCalendarSelect('cCalendar');
    document.getElementById('cCalendar').value = '';
    showModal('createModal');
};
window.closeCreateModal = function () { hideModal('createModal'); };

window.saveEvent = function () {
    if (saving) return;
    const data = {
        title: document.getElementById('cTitle').value.trim(),
        start_date: document.getElementById('cStartDate').value,
        end_date: document.getElementById('cEndDate').value,
        start_time: document.getElementById('cStartTime').value || null,
        end_time: document.getElementById('cEndTime').value || null,
        is_all_day: document.getElementById('cAllDay').checked,
        custom_calendar_id: document.getElementById('cCalendar').value || null,
        description: document.getElementById('cDesc').value.trim(),
        attendee_ids: selectedAttendees.c.map(a => a.id),
    };
    if (!data.title) { showToast('일정 제목을 입력해주세요', 'error'); return; }
    if (!data.start_date || !data.end_date) { showToast('날짜를 입력해주세요', 'error'); return; }

    saving = true;
    fetch(`${basePath}/api/schedule.php?action=createEvent`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast('일정이 등록되었습니다'); closeCreateModal(); calendar.refetchEvents(); loadSideData(); }
        else { showToast(res.error || '등록 실패', 'error'); }
    })
    .catch(() => showToast('서버 오류', 'error'))
    .finally(() => { saving = false; });
};

// ═══════════════════════════════════════
//  Detail / Edit Modal
// ═══════════════════════════════════════
window.openDetailModal = function (id) {
    fetch(`${basePath}/api/schedule.php?action=getEvent&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.event) { showToast('일정을 찾을 수 없습니다', 'error'); return; }
            showDetailView(data.event);
            showModal('detailModal');
        })
        .catch(() => showToast('서버 오류', 'error'));
};

function showDetailView(ev) {
    document.getElementById('dModalTitle').textContent = '일정 상세';
    document.getElementById('detailView').classList.remove('hidden');
    document.getElementById('editView').classList.add('hidden');
    document.getElementById('btnEdit').classList.remove('hidden');
    document.getElementById('btnSave').classList.add('hidden');

    document.getElementById('dTitle').textContent = ev.title;

    let dateStr = ev.start_date;
    if (ev.start_date !== ev.end_date) dateStr += ' ~ ' + ev.end_date;
    if (ev.is_all_day == 1) { dateStr += ' (종일)'; }
    else if (ev.start_time) { dateStr += ' ' + ev.start_time.substring(0, 5) + (ev.end_time ? ' ~ ' + ev.end_time.substring(0, 5) : ''); }
    document.getElementById('dDate').textContent = dateStr;

    const c = COLOR_MAP[ev.color_code] || COLOR_MAP.blue;
    document.getElementById('dCategory').innerHTML = `<span class="inline-flex items-center gap-1.5 text-sm text-slate-300"><span class="w-2 h-2 rounded-full ${c.dot}"></span>${esc(ev.category_name || '미분류')}</span>`;
    document.getElementById('dCreator').textContent = `${ev.creator_name || ''} ${ev.creator_position ? '(' + ev.creator_position + ')' : ''} ${ev.creator_department ? '· ' + ev.creator_department : ''}`;

    const attendees = ev.attendees || [];
    if (attendees.length) {
        document.getElementById('dAttendeesWrap').classList.remove('hidden');
        document.getElementById('dAttendees').innerHTML = attendees.map(a => `<span class="inline-block bg-slate-800 text-slate-200 text-sm px-2 py-0.5 rounded mr-1 mb-1">${esc(a.name)}</span>`).join('');
    } else {
        document.getElementById('dAttendeesWrap').classList.add('hidden');
    }

    if (ev.description) {
        document.getElementById('dDescWrap').classList.remove('hidden');
        document.getElementById('dDesc').textContent = ev.description;
    } else {
        document.getElementById('dDescWrap').classList.add('hidden');
    }

    document.getElementById('detailModal').dataset.event = JSON.stringify(ev);

    const btn = document.getElementById('btnImportant');
    if (btn) {
        btn.classList.remove('hidden');
        const icon = document.getElementById('importantIcon');
        if (ev.is_important == 1) {
            icon.style.color = '#eab308';
            icon.setAttribute('data-lucide', 'star');
        } else {
            icon.style.color = '';
            icon.setAttribute('data-lucide', 'star');
        }
        lucide.createIcons({ nodes: [icon.parentElement] });
    }
}

window.toggleImportant = function () {
    const modal = document.getElementById('detailModal');
    const ev = JSON.parse(modal.dataset.event || '{}');
    if (!ev.id) return;

    fetch(`${basePath}/api/schedule.php?action=toggleImportant`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: ev.id }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok || res.success) {
            const val = res.data ? res.data.is_important : (res.is_important ?? 0);
            ev.is_important = val;
            modal.dataset.event = JSON.stringify(ev);
            const icon = document.getElementById('importantIcon');
            icon.style.color = val ? '#eab308' : '';
            lucide.createIcons({ nodes: [icon.parentElement] });
            calendar.refetchEvents();
            showToast(val ? '중요 일정으로 지정됨' : '중요 일정 해제됨');
        } else {
            showToast(res.error?.message || res.error || '변경 실패', 'error');
        }
    })
    .catch(() => showToast('서버 오류', 'error'));
};

window.switchToEdit = function () {
    const ev = JSON.parse(document.getElementById('detailModal').dataset.event || '{}');
    document.getElementById('dModalTitle').textContent = '일정 수정';
    document.getElementById('detailView').classList.add('hidden');
    document.getElementById('editView').classList.remove('hidden');
    document.getElementById('btnEdit').classList.add('hidden');
    document.getElementById('btnSave').classList.remove('hidden');

    document.getElementById('eId').value = ev.id;
    document.getElementById('eTitle').value = ev.title;
    document.getElementById('eStartDate').value = ev.start_date;
    document.getElementById('eEndDate').value = ev.end_date;
    document.getElementById('eStartTime').value = ev.start_time ? ev.start_time.substring(0, 5) : '';
    document.getElementById('eEndTime').value = ev.end_time ? ev.end_time.substring(0, 5) : '';
    document.getElementById('eAllDay').checked = ev.is_all_day == 1;
    populateCalendarSelect('eCalendar');
    document.getElementById('eCalendar').value = ev.customCalendarId || ev.custom_calendar_id || '';
    document.getElementById('eDesc').value = ev.description || '';
    toggleAllDay('e');

    selectedAttendees.e = (ev.attendees || []).map(a => ({ id: a.employee_id, name: a.name, position: a.position }));
    renderAttendeeTags('e');
};

window.saveEdit = function () {
    if (saving) return;
    const data = {
        id: document.getElementById('eId').value,
        title: document.getElementById('eTitle').value.trim(),
        start_date: document.getElementById('eStartDate').value,
        end_date: document.getElementById('eEndDate').value,
        start_time: document.getElementById('eStartTime').value || null,
        end_time: document.getElementById('eEndTime').value || null,
        is_all_day: document.getElementById('eAllDay').checked,
        custom_calendar_id: document.getElementById('eCalendar').value || null,
        description: document.getElementById('eDesc').value.trim(),
        attendee_ids: selectedAttendees.e.map(a => a.id),
    };
    if (!data.title) { showToast('일정 제목을 입력해주세요', 'error'); return; }

    saving = true;
    fetch(`${basePath}/api/schedule.php?action=updateEvent`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast('일정이 수정되었습니다'); closeDetailModal(); calendar.refetchEvents(); loadSideData(); }
        else { showToast(res.error || '수정 실패', 'error'); }
    })
    .catch(() => showToast('서버 오류', 'error'))
    .finally(() => { saving = false; });
};

window.confirmDelete = async function () {
    const ev = JSON.parse(document.getElementById('detailModal').dataset.event || '{}');
    if (!(await AppUI.confirm(`"${ev.title}" 일정을 삭제하시겠습니까?`))) return;

    fetch(`${basePath}/api/schedule.php?action=deleteEvent`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: ev.id }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast('일정이 삭제되었습니다'); closeDetailModal(); calendar.refetchEvents(); loadSideData(); }
        else { showToast(res.error || '삭제 실패', 'error'); }
    })
    .catch(() => showToast('서버 오류', 'error'));
};

window.closeDetailModal = function () { hideModal('detailModal'); };

// ═══════════════════════════════════════
//  Calendar Event Detail Modal
// ═══════════════════════════════════════
window.openCEDetail = function (id) {
    const ce = calendarEvents.find(e => e.id == id);
    if (!ce) return;
    const cc = CE_COLOR_MAP[ce.category] || CE_COLOR_MAP.tax;

    document.getElementById('ceTitle').textContent = ce.title;
    document.getElementById('ceCategoryBadge').innerHTML =
        `<span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full ${cc.bar}"><span class="w-2 h-2 rounded-full ${cc.dot}"></span>${cc.label}${ce.is_deadline ? ' · 마감일' : ''}</span>`;

    const dateText = ce.start_date === ce.end_date ? ce.start_date : `${ce.start_date} ~ ${ce.end_date}`;
    document.getElementById('ceDate').textContent = dateText;

    const descWrap = document.getElementById('ceDescWrap');
    if (ce.description) {
        document.getElementById('ceDesc').textContent = ce.description;
        descWrap.classList.remove('hidden');
    } else { descWrap.classList.add('hidden'); }

    const refWrap = document.getElementById('ceRefWrap');
    if (ce.source_ref) {
        document.getElementById('ceRef').textContent = ce.source_ref;
        refWrap.classList.remove('hidden');
    } else { refWrap.classList.add('hidden'); }

    showModal('ceDetailModal');
};
window.closeCEDetail = function () { hideModal('ceDetailModal'); };

// ═══════════════════════════════════════
//  Attendee Selector
// ═══════════════════════════════════════
function initAttendeeInput(prefix) {
    const input = document.getElementById(prefix + 'AttendeeInput');
    const dropdown = document.getElementById(prefix + 'AttendeeDropdown');

    input.addEventListener('input', () => {
        const kw = input.value.trim().toLowerCase();
        if (!kw) { dropdown.classList.add('hidden'); return; }
        const filtered = employees.filter(e =>
            e.name.toLowerCase().includes(kw) &&
            !selectedAttendees[prefix].some(s => s.id == e.id)
        );
        if (!filtered.length) { dropdown.classList.add('hidden'); return; }
        dropdown.innerHTML = filtered.slice(0, 8).map(e =>
            `<div class="px-3 py-2 text-sm hover:bg-slate-950 cursor-pointer" data-eid="${e.id}">${esc(e.name)} <span class="text-slate-500 text-sm">${esc(e.position || '')} · ${esc(e.department || '')}</span></div>`
        ).join('');
        dropdown.querySelectorAll('[data-eid]').forEach(el => {
            el.addEventListener('mousedown', (evt) => {
                evt.preventDefault();
                const emp = employees.find(e => e.id == el.dataset.eid);
                if (emp) addAttendee(prefix, emp.id, emp.name, emp.position || '');
            });
        });
        dropdown.classList.remove('hidden');
    });

    input.addEventListener('blur', () => { setTimeout(() => dropdown.classList.add('hidden'), 200); });
    input.addEventListener('focus', () => { if (input.value.trim()) input.dispatchEvent(new Event('input')); });
}

function addAttendee(prefix, id, name, position) {
    if (selectedAttendees[prefix].some(a => a.id === id)) return;
    selectedAttendees[prefix].push({ id, name, position });
    document.getElementById(prefix + 'AttendeeInput').value = '';
    document.getElementById(prefix + 'AttendeeDropdown').classList.add('hidden');
    renderAttendeeTags(prefix);
}

window.removeAttendee = function (prefix, id) {
    selectedAttendees[prefix] = selectedAttendees[prefix].filter(a => a.id !== id);
    renderAttendeeTags(prefix);
};

function renderAttendeeTags(prefix) {
    const wrap = document.getElementById(prefix + 'AttendeeTags');
    wrap.innerHTML = selectedAttendees[prefix].map(a =>
        `<span class="inline-flex items-center gap-1 bg-primary-light text-primary text-sm px-2 py-1 rounded-full">
            ${esc(a.name)}
            <button type="button" onclick="removeAttendee('${prefix}',${a.id})" class="text-primary hover:text-gray-900">&times;</button>
        </span>`
    ).join('');
}

// ═══════════════════════════════════════
//  Helpers
// ═══════════════════════════════════════
function toggleAllDay(prefix) {
    const checked = document.getElementById(prefix + 'AllDay').checked;
    document.getElementById(prefix + 'StartTimeWrap').style.display = checked ? 'none' : '';
    document.getElementById(prefix + 'EndTimeWrap').style.display = checked ? 'none' : '';
}
window.toggleAllDay = toggleAllDay;

function populateCategorySelect(selectId) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">선택 안함</option>' +
        categories.map(c => `<option value="${c.item_id}">${esc(c.name)}</option>`).join('');
}

// 일정을 넣을 캘린더 선택 (내 일정 = 기본, + 사용자가 만든 커스텀 캘린더)
function populateCalendarSelect(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const cur = sel.value;
    sel.innerHTML = '<option value="">내 일정</option>' +
        customCalendars.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    sel.value = cur;
}

function showModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
    lucide.createIcons();
}
function hideModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (!document.getElementById('createModal').classList.contains('hidden')) closeCreateModal();
        if (!document.getElementById('detailModal').classList.contains('hidden')) closeDetailModal();
        if (!document.getElementById('ceDetailModal').classList.contains('hidden')) closeCEDetail();
        if (!document.getElementById('taskModal').classList.contains('hidden')) closeTaskModal();
    }
});
document.getElementById('createModal').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeCreateModal(); });
document.getElementById('detailModal').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeDetailModal(); });

function showToast(msg, type) {
    type = type || 'success';
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `px-4 py-2.5 rounded-lg text-sm text-white shadow-lg transition-all duration-300 ${type === 'success' ? 'bg-emerald-500' : 'bg-rose-500'}`;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2500);
}
window.showToast = showToast;

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function fmtDate(dt) {
    return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
}

// ════════════════════════════════════════════════════
//  내 할 일 (개인 To-do)
// ════════════════════════════════════════════════════
const TASKS_API = `${basePath}/api/tasks.php`;
const TASK_PRIORITY = {
    urgent: { label: '긴급', dot: 'bg-rose-500',   text: 'text-rose-400' },
    high:   { label: '높음', dot: 'bg-amber-500',  text: 'text-amber-400' },
    normal: { label: '보통', dot: 'bg-slate-500',  text: 'text-slate-400' },
    low:    { label: '낮음', dot: 'bg-slate-700',  text: 'text-slate-500' },
};
const TASK_TABS = [
    { key: 'today',  label: '오늘' },
    { key: 'soon',   label: '예정' },
    { key: 'nodate', label: '기한없음' },
    { key: 'done',   label: '완료' },
];
let tasks = [];
let taskTab = 'today';

function initTaskPanel() {
    loadTasks();
}

function todayStr() {
    const t = new Date();
    return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;
}

function taskBucket(t) {
    if (t.status === 'done') return 'done';
    if (!t.due_date) return 'nodate';
    return t.due_date <= todayStr() ? 'today' : 'soon';
}

function loadTasks() {
    if (!hasDB) { document.getElementById('taskList').innerHTML = noTaskMsg('DB 미연결'); renderTaskTabs(); return; }
    fetch(`${TASKS_API}?action=list`)
        .then(r => r.json()).catch(() => ({ ok: false }))
        .then(res => {
            tasks = (res.ok && res.data && res.data.tasks) ? res.data.tasks : [];
            renderTaskTabs();
            renderTaskList();
            lucide.createIcons();
        });
}

function renderTaskTabs() {
    const counts = { today: 0, soon: 0, nodate: 0, done: 0 };
    tasks.forEach(t => { counts[taskBucket(t)]++; });
    document.getElementById('taskTabs').innerHTML = TASK_TABS.map(tab => {
        const active = taskTab === tab.key;
        const cls = active ? 'bg-primary text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800';
        return `<button type="button" onclick="switchTaskTab('${tab.key}')" class="flex-1 px-1.5 py-1 text-xs rounded-md transition-colors ${cls}">
            ${tab.label}<span class="ml-0.5 ${active ? 'text-white/80' : 'text-slate-500'}">${counts[tab.key] || 0}</span>
        </button>`;
    }).join('');
}

window.switchTaskTab = function (key) { taskTab = key; renderTaskTabs(); renderTaskList(); lucide.createIcons(); };

function noTaskMsg(msg) {
    return `<p class="text-sm text-slate-500 text-center py-8">${esc(msg)}</p>`;
}

function renderTaskList() {
    const list = document.getElementById('taskList');
    const today = todayStr();
    const items = tasks.filter(t => taskBucket(t) === taskTab);

    if (!items.length) {
        const empty = { today: '오늘 할 일이 없어요', soon: '예정된 할 일이 없어요', nodate: '기한 없는 할 일이 없어요', done: '완료된 할 일이 없어요' };
        list.innerHTML = noTaskMsg(empty[taskTab] || '할 일이 없어요');
        return;
    }

    list.innerHTML = items.map(t => {
        const p = TASK_PRIORITY[t.priority] || TASK_PRIORITY.normal;
        const done = t.status === 'done';
        let dueBadge = '';
        if (t.due_date) {
            if (!done && t.due_date < today) {
                const days = Math.round((new Date(today) - new Date(t.due_date)) / 86400000);
                dueBadge = `<span class="text-[11px] text-rose-400 font-medium shrink-0">${days}일 지남</span>`;
            } else if (!done && t.due_date === today) {
                dueBadge = `<span class="text-[11px] text-primary font-medium shrink-0">오늘</span>`;
            } else {
                dueBadge = `<span class="text-[11px] text-slate-500 shrink-0">${t.due_date.substring(5)}</span>`;
            }
        }
        const titleCls = done ? 'line-through text-slate-500' : 'text-slate-100';
        const memoIcon = t.description ? `<i data-lucide="align-left" class="w-3 h-3 text-slate-600 shrink-0"></i>` : '';
        return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-950 group">
            <button type="button" onclick="event.stopPropagation();toggleTask(${t.id})" title="완료 토글"
                    class="shrink-0 w-4 h-4 rounded-full border ${done ? 'bg-emerald-500 border-emerald-500' : 'border-slate-600 hover:border-primary'} flex items-center justify-center">
                ${done ? '<i data-lucide="check" class="w-3 h-3 text-white"></i>' : `<span class="w-1.5 h-1.5 rounded-full ${p.dot}"></span>`}
            </button>
            <div class="min-w-0 flex-1 cursor-pointer flex items-center gap-1.5" onclick="openTaskModal(${t.id})">
                <span class="text-sm truncate ${titleCls}">${esc(t.title)}</span>
                ${memoIcon}
            </div>
            ${dueBadge}
        </div>`;
    }).join('');
}

window.quickAddTask = function () {
    if (!hasDB) { showToast('DB 미연결 상태에서는 추가할 수 없습니다', 'error'); return; }
    const input = document.getElementById('taskQuickInput');
    const title = input.value.trim();
    if (!title) return;
    fetch(`${TASKS_API}?action=create`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) { input.value = ''; loadTasks(); }
        else { showToast(res.error?.message || '추가 실패', 'error'); }
    })
    .catch(() => showToast('서버 오류', 'error'));
};

window.toggleTask = function (id) {
    fetch(`${TASKS_API}?action=toggle`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    })
    .then(r => r.json())
    .then(res => { if (res.ok) loadTasks(); else showToast(res.error?.message || '처리 실패', 'error'); })
    .catch(() => showToast('서버 오류', 'error'));
};

window.openTaskModal = function (id) {
    if (id) {
        const t = tasks.find(x => x.id == id);
        if (!t) return;
        document.getElementById('tId').value = t.id;
        document.getElementById('tTitle').value = t.title;
        document.getElementById('tDue').value = t.due_date || '';
        document.getElementById('tPriority').value = t.priority || 'normal';
        document.getElementById('tDesc').value = t.description || '';
    } else {
        document.getElementById('tId').value = '';
        document.getElementById('tTitle').value = '';
        document.getElementById('tDue').value = '';
        document.getElementById('tPriority').value = 'normal';
        document.getElementById('tDesc').value = '';
    }
    showModal('taskModal');
};
window.closeTaskModal = function () { hideModal('taskModal'); };

window.saveTask = function () {
    const id = document.getElementById('tId').value;
    const title = document.getElementById('tTitle').value.trim();
    if (!title) { showToast('할 일 내용을 입력해주세요', 'error'); return; }
    const data = {
        title,
        due_date: document.getElementById('tDue').value || null,
        priority: document.getElementById('tPriority').value || 'normal',
        description: document.getElementById('tDesc').value.trim(),
    };
    const action = id ? 'update' : 'create';
    if (id) data.id = id;
    fetch(`${TASKS_API}?action=${action}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) { showToast(id ? '할 일이 저장되었습니다' : '할 일이 추가되었습니다'); closeTaskModal(); loadTasks(); }
        else { showToast(res.error?.message || '저장 실패', 'error'); }
    })
    .catch(() => showToast('서버 오류', 'error'));
};

window.deleteTask = async function () {
    const id = document.getElementById('tId').value;
    if (!(await AppUI.confirm('이 할 일을 삭제하시겠습니까?'))) return;
    fetch(`${TASKS_API}?action=delete`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) { showToast('할 일이 삭제되었습니다'); closeTaskModal(); loadTasks(); }
        else { showToast(res.error?.message || '삭제 실패', 'error'); }
    })
    .catch(() => showToast('서버 오류', 'error'));
};

})();
