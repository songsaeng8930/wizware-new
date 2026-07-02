(function() {
'use strict';

const API = window.LEAVE_API_URL || '/api/annual_leave.php';
const YEAR = window.LEAVE_YEAR || new Date().getFullYear();

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function escAttr(s) { return String(s==null?'':s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtNum(n) { return Number(n).toLocaleString('ko-KR'); }
function fmtDate(d) { if (!d) return '-'; return d.replace(/T.*/, '').replace(/(\d{4})-(\d{2})-(\d{2})/, '$1.$2.$3'); }

async function api(action, params, postBody) {
    let url = API + '?action=' + action;
    if (params) Object.entries(params).forEach(([k,v]) => url += '&' + k + '=' + encodeURIComponent(v));
    const opts = {};
    if (postBody) {
        opts.method = 'POST';
        opts.headers = {'Content-Type': 'application/json'};
        opts.body = JSON.stringify(postBody);
    }
    const res = await fetch(url, opts);
    return res.json();
}

function toast(msg, type) {
    const t = document.createElement('div');
    t.className = 'fixed top-20 right-6 z-[100] px-4 py-3 rounded-lg shadow-lg text-sm text-white transition-all '
        + (type === 'error' ? 'bg-rose-500' : 'bg-emerald-500');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2500);
}

/* ═══ 공휴일 관리 ═══ */

let _holidayCache = [];

window.openHolidayModal = async function() {
    const m = document.getElementById('holidayModal');
    if (!m) return;
    m.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    await loadHolidays();
    switchHolidayView('cal');
};

async function loadHolidays() {
    const data = await api('getHolidays', {year: YEAR});
    const list = data.holidays || [];
    const el = document.getElementById('holidayList');
    if (!el) return;
    if (list.length === 0) {
        el.innerHTML = '<p class="text-gray-500 text-center py-6">등록된 공휴일이 없습니다.</p>';
        return;
    }
    let html = '<table class="w-full text-sm"><thead><tr class="border-b-2 border-gray-200">'
        + '<th class="py-2 px-3 text-left text-gray-600">날짜</th>'
        + '<th class="py-2 px-3 text-left text-gray-600">명칭</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">유형</th>'
        + '<th class="py-2 px-3 text-center text-gray-600 w-16">삭제</th>'
        + '</tr></thead><tbody>';
    list.forEach(h => {
        const dow = ['일','월','화','수','목','금','토'][new Date(h.holiday_date).getDay()];
        const typeCls = h.type === '법정' ? 'bg-red-50 text-red-600' : h.type === '대체' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-600';
        html += '<tr class="border-b border-gray-100">'
            + '<td class="py-2 px-3 text-gray-900 tabular-nums">' + fmtDate(h.holiday_date) + ' <span class="text-gray-400">(' + dow + ')</span></td>'
            + '<td class="py-2 px-3 text-gray-700">' + escHtml(h.name) + '</td>'
            + '<td class="py-2 px-3 text-center"><span class="text-xs px-1.5 py-0.5 rounded ' + typeCls + '">' + escHtml(h.type) + '</span></td>'
            + '<td class="py-2 px-3 text-center"><button onclick="deleteHolidayRow(' + h.id + ')" class="text-gray-400 hover:text-red-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>'
            + '</tr>';
    });
    html += '</tbody></table>';
    el.innerHTML = html;
    _holidayCache = list;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

let _holViewMode = 'cal';
let _holCalSub = 'month';
window.switchHolidayView = function(view) {
    _holViewMode = view;
    const listEl = document.getElementById('holidayList');
    const calEl = document.getElementById('holidayCalendar');
    const btnList = document.getElementById('holViewList');
    const btnCal = document.getElementById('holViewCal');
    if (!listEl || !calEl) return;
    const off = 'px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 rounded';
    const on = 'px-3 py-1 text-xs font-medium bg-primary text-white rounded';
    if (btnList) btnList.className = view === 'list' ? on : off;
    if (btnCal) btnCal.className = (view === 'cal') ? on : off;
    if (view === 'cal') {
        listEl.classList.add('hidden');
        calEl.classList.remove('hidden');
        if (_holCalSub === 'year') renderHolidayYearView();
        else renderHolidayCalendar();
    } else {
        calEl.classList.add('hidden');
        listEl.classList.remove('hidden');
    }
};
window._setHolCalSub = function(sub) {
    _holCalSub = sub;
    if (sub === 'year') renderHolidayYearView();
    else renderHolidayCalendar();
};

let _calMonth = new Date().getMonth();
let _calYear = YEAR;

function buildCalSubToggle() {
    const onStyle = 'padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;border:1px solid #4F6AFF;background:#eef1ff;color:#4F6AFF;cursor:pointer';
    const offStyle = 'padding:3px 10px;border-radius:6px;font-size:11px;font-weight:400;border:1px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer';
    return '<div style="display:flex;gap:4px;justify-content:center;margin-bottom:10px">'
        + '<button onclick="window._setHolCalSub(\'month\')" style="' + (_holCalSub === 'month' ? onStyle : offStyle) + '">월간</button>'
        + '<button onclick="window._setHolCalSub(\'year\')" style="' + (_holCalSub === 'year' ? onStyle : offStyle) + '">연간</button>'
        + '</div>';
}

function renderHolidayCalendar() {
    const calEl = document.getElementById('holidayCalendar');
    if (!calEl) return;
    const holDates = {};
    _holidayCache.forEach(h => {
        holDates[h.holiday_date] = { name: h.name, type: h.type };
    });
    const DAYS = ['일','월','화','수','목','금','토'];
    const y = _calYear;
    const m = _calMonth;
    const first = new Date(y, m, 1);
    const last = new Date(y, m + 1, 0).getDate();
    const startDay = first.getDay();

    const holCount = _holidayCache.filter(h => {
        const parts = h.holiday_date.split('-');
        return parseInt(parts[0], 10) === y && parseInt(parts[1], 10) - 1 === m;
    }).length;

    let html = '<div style="padding:16px">';
    html += buildCalSubToggle();
    html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">';
    html += '<button onclick="window._holCalNav(-1)" style="border:none;background:none;cursor:pointer;padding:4px 8px;font-size:18px;color:#6b7280;border-radius:6px" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">&lt;</button>';
    html += '<span style="font-weight:700;font-size:15px;color:#1f2937">' + y + '년 ' + (m + 1) + '월';
    if (holCount > 0) html += ' <span style="font-size:12px;font-weight:500;color:#6366f1;margin-left:4px">공휴일 ' + holCount + '일</span>';
    html += '</span>';
    html += '<button onclick="window._holCalNav(1)" style="border:none;background:none;cursor:pointer;padding:4px 8px;font-size:18px;color:#6b7280;border-radius:6px" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">&gt;</button>';
    html += '</div>';

    html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:13px">';
    DAYS.forEach((d, i) => {
        const c = i === 0 ? '#ef4444' : i === 6 ? '#3b82f6' : '#9ca3af';
        html += '<span style="color:' + c + ';font-weight:600;padding:6px 0;font-size:12px">' + d + '</span>';
    });
    for (let i = 0; i < startDay; i++) html += '<span></span>';
    for (let d = 1; d <= last; d++) {
        const ds = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        const hol = holDates[ds];
        const dow = new Date(y, m, d).getDay();
        let bg = 'transparent', color = '#374151', fw = '400', title = '', extra = '';
        if (hol) {
            bg = hol.type === '법정' ? '#fef2f2' : hol.type === '대체' ? '#eff6ff' : '#f0fdf4';
            color = hol.type === '법정' ? '#dc2626' : hol.type === '대체' ? '#2563eb' : '#16a34a';
            fw = '700';
            title = ' title="' + escAttr(hol.name) + '"';
            extra = '<span style="display:block;font-size:9px;font-weight:500;line-height:1;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:52px">' + escHtml(hol.name) + '</span>';
        } else if (dow === 0) {
            color = '#ef4444';
        } else if (dow === 6) {
            color = '#3b82f6';
        }
        html += '<span style="padding:6px 2px;border-radius:6px;background:' + bg + ';color:' + color + ';font-weight:' + fw + ';cursor:' + (hol ? 'help' : 'default') + ';line-height:1.2"' + title + '>' + d + extra + '</span>';
    }
    html += '</div>';

    const monthHols = _holidayCache.filter(h => {
        const parts = h.holiday_date.split('-');
        return parseInt(parts[0], 10) === y && parseInt(parts[1], 10) - 1 === m;
    });
    if (monthHols.length > 0) {
        html += '<div style="margin-top:12px;border-top:1px solid #e5e7eb;padding-top:10px">';
        monthHols.forEach(h => {
            const typeColor = h.type === '법정' ? '#dc2626' : h.type === '대체' ? '#2563eb' : '#16a34a';
            const typeBg = h.type === '법정' ? '#fef2f2' : h.type === '대체' ? '#eff6ff' : '#f0fdf4';
            html += '<div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px">';
            html += '<span style="color:#9ca3af;min-width:44px">' + h.holiday_date.slice(5) + '</span>';
            html += '<span style="background:' + typeBg + ';color:' + typeColor + ';padding:1px 6px;border-radius:4px;font-size:11px;font-weight:500">' + escHtml(h.type) + '</span>';
            html += '<span style="color:#374151">' + escHtml(h.name) + '</span>';
            html += '</div>';
        });
        html += '</div>';
    }

    html += '</div>';
    calEl.innerHTML = html;
}

window._holCalNav = function(dir) {
    _calMonth += dir;
    if (_calMonth < 0) { _calMonth = 11; _calYear--; }
    if (_calMonth > 11) { _calMonth = 0; _calYear++; }
    renderHolidayCalendar();
};

window._holYearNav = function(dir) {
    _calYear += dir;
    renderHolidayYearView();
};

function renderHolidayYearView() {
    const calEl = document.getElementById('holidayCalendar');
    if (!calEl) return;
    const holDates = {};
    const y = _calYear;
    _holidayCache.forEach(h => { holDates[h.holiday_date] = { name: h.name, type: h.type }; });
    const DAYS = ['일','월','화','수','목','금','토'];
    const yearHols = _holidayCache.filter(h => parseInt(h.holiday_date.split('-')[0], 10) === y);

    let html = '<div style="padding:12px">';
    html += buildCalSubToggle();
    html += '<div style="display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:12px">';
    html += '<button onclick="window._holYearNav(-1)" style="border:none;background:none;cursor:pointer;padding:4px 8px;font-size:18px;color:#6b7280;border-radius:6px" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">&lt;</button>';
    html += '<span style="font-weight:700;font-size:15px;color:#1f2937">' + y + '년';
    html += ' <span style="font-size:12px;color:#6366f1;font-weight:500">' + yearHols.length + '일</span></span>';
    html += '<button onclick="window._holYearNav(1)" style="border:none;background:none;cursor:pointer;padding:4px 8px;font-size:18px;color:#6b7280;border-radius:6px" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">&gt;</button>';
    html += '</div>';
    html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-height:460px;overflow-y:auto">';

    for (let m = 0; m < 12; m++) {
        const first = new Date(y, m, 1);
        const last = new Date(y, m + 1, 0).getDate();
        const startDay = first.getDay();
        const mHols = yearHols.filter(h => parseInt(h.holiday_date.split('-')[1], 10) - 1 === m);

        html += '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:8px;cursor:pointer" onclick="window._calYear=' + y + ';window._calMonth=' + m + ';window._setHolCalSub(\'month\')">';
        html += '<div style="font-weight:600;font-size:12px;color:#374151;text-align:center;margin-bottom:4px">' + (m + 1) + '월';
        if (mHols.length > 0) html += ' <span style="color:#6366f1;font-weight:500;font-size:10px">' + mHols.length + '일</span>';
        html += '</div>';
        html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;text-align:center;font-size:10px">';
        DAYS.forEach((d, i) => {
            const c = i === 0 ? '#ef4444' : i === 6 ? '#3b82f6' : '#d1d5db';
            html += '<span style="color:' + c + ';font-weight:600;font-size:9px">' + d + '</span>';
        });
        for (let i = 0; i < startDay; i++) html += '<span></span>';
        for (let d = 1; d <= last; d++) {
            const ds = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            const hol = holDates[ds];
            const dow = new Date(y, m, d).getDay();
            let bg = 'transparent', color = '#6b7280', fw = '400', title = '';
            if (hol) {
                bg = hol.type === '법정' ? '#fef2f2' : hol.type === '대체' ? '#eff6ff' : '#f0fdf4';
                color = hol.type === '법정' ? '#dc2626' : hol.type === '대체' ? '#2563eb' : '#16a34a';
                fw = '700';
                title = ' title="' + escAttr(hol.name) + '"';
            } else if (dow === 0) { color = '#ef4444'; }
            else if (dow === 6) { color = '#3b82f6'; }
            html += '<span style="padding:2px 0;border-radius:3px;background:' + bg + ';color:' + color + ';font-weight:' + fw + ';cursor:' + (hol ? 'help' : 'default') + ';line-height:1.4"' + title + '>' + d + '</span>';
        }
        html += '</div></div>';
    }
    html += '</div></div>';
    calEl.innerHTML = html;
}

document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('holidayModal');
    if (!modal || modal.classList.contains('hidden')) return;
    if (_holViewMode !== 'cal') return;
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        if (_holCalSub === 'year') window._holYearNav(-1); else window._holCalNav(-1);
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        if (_holCalSub === 'year') window._holYearNav(1); else window._holCalNav(1);
    }
});

window.addHolidayRow = async function() {
    const date = document.getElementById('newHolidayDate')?.value;
    const name = document.getElementById('newHolidayName')?.value?.trim();
    const type = document.getElementById('newHolidayType')?.value || '임시';
    if (!date || !name) { toast('날짜와 명칭을 입력해주세요.', 'error'); return; }
    const data = await api('addHoliday', null, {holiday_date: date, name, type});
    if (data.success) {
        toast(data.message, 'success');
        document.getElementById('newHolidayDate').value = '';
        document.getElementById('newHolidayName').value = '';
        await loadHolidays();
    } else {
        toast(data.error || '등록 실패', 'error');
    }
};

window.deleteHolidayRow = async function(id) {
    if (!(await AppUI.confirm('이 공휴일을 삭제하시겠습니까?'))) return;
    const data = await api('deleteHoliday', null, {id});
    if (data.success) { toast(data.message, 'success'); await loadHolidays(); }
    else toast(data.error || '삭제 실패', 'error');
};

/* ═══ 프론트 영업일 계산 (미리보기용) ═══ */

window.calcBusinessDaysFront = function(start, end) {
    const holidays = window.HOLIDAYS_DATA || [];
    const s = new Date(start), e = new Date(end);
    if (s > e) return 0;
    let days = 0;
    const cur = new Date(s);
    while (cur <= e) {
        const dow = cur.getDay();
        const dateStr = cur.toISOString().slice(0, 10);
        if (dow !== 0 && dow !== 6 && !holidays.includes(dateStr)) days++;
        cur.setDate(cur.getDate() + 1);
    }
    return Math.max(1, days);
};

/* ═══ 감사 로그 ═══ */

window.openAuditLogModal = async function() {
    const m = document.getElementById('auditLogModal');
    if (!m) return;
    m.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const el = document.getElementById('auditLogContent');
    el.innerHTML = '<p class="text-gray-500 text-center py-8">불러오는 중...</p>';

    const data = await api('getAuditLog', {limit: 50});
    const list = data.logs || [];
    if (list.length === 0) { el.innerHTML = '<p class="text-gray-500 text-center py-8">변경 이력이 없습니다.</p>'; return; }

    const typeLabels = {
        leave_applied: '신청', leave_approved: '승인', leave_rejected: '반려',
        leave_cancelled: '취소', leave_adjusted: '조정', carryover_approved: '이월 승인',
        promotion_created: '촉진 통보', leave_settled: '퇴사 정산'
    };
    let html = '<div class="max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="sticky top-0 bg-white"><tr class="border-b-2 border-gray-200">'
        + '<th class="py-2 px-3 text-left text-gray-600">일시</th>'
        + '<th class="py-2 px-3 text-left text-gray-600">행위자</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">유형</th>'
        + '<th class="py-2 px-3 text-left text-gray-600">상세</th>'
        + '</tr></thead><tbody>';
    list.forEach(log => {
        const label = typeLabels[log.event_type] || log.event_type;
        let detail = '';
        try {
            const nv = log.new_value ? JSON.parse(log.new_value) : {};
            if (nv.delta) detail = (nv.delta > 0 ? '+' : '') + nv.delta + '일';
            else if (nv.status) detail = '→ ' + nv.status;
            else if (nv.count) detail = nv.count + '명';
            else if (nv.amount) detail = fmtNum(nv.amount) + '원';
        } catch(e) {}
        html += '<tr class="border-b border-gray-100">'
            + '<td class="py-2 px-3 text-gray-500 tabular-nums">' + fmtDate(log.created_at) + '</td>'
            + '<td class="py-2 px-3 text-gray-700">' + escHtml(log.actor_name || '-') + '</td>'
            + '<td class="py-2 px-3 text-center"><span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">' + escHtml(label) + '</span></td>'
            + '<td class="py-2 px-3 text-gray-600">' + escHtml(detail || log.comment || '-') + '</td>'
            + '</tr>';
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
};

/* ═══ 이월 관리 ═══ */

window.coAdjDays = function(delta) {
    const el = document.getElementById('coDays');
    if (!el) return;
    const v = Math.max(0.5, Math.min(25, (parseFloat(el.value) || 0) + delta));
    el.value = v;
};

window.openCarryoverModal = function() {
    const m = document.getElementById('carryoverModal');
    if (!m) return;
    m.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
};

window.loadCarryoverList = async function() {
    document.getElementById('carryoverListModal')?.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    await loadCarryovers();
};

async function loadCarryovers() {
    const data = await api('getCarryovers', {year: YEAR});
    const list = data.carryovers || [];
    const el = document.getElementById('carryoverList');
    if (!el) return;
    if (list.length === 0) {
        el.innerHTML = '<p class="text-gray-500 text-center py-6">이월 내역이 없습니다.</p>';
        return;
    }
    let html = '<table class="w-full text-sm"><thead><tr class="border-b-2 border-gray-200">'
        + '<th class="py-2 px-3 text-left text-gray-600">직원</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">연도</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">일수</th>'
        + '<th class="py-2 px-3 text-left text-gray-600">사유</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">상태</th>'
        + '<th class="py-2 px-3 text-center text-gray-600 w-24">작업</th>'
        + '</tr></thead><tbody>';
    list.forEach(c => {
        const statusCls = c.status === '승인' ? 'bg-emerald-50 text-emerald-600' : c.status === '반려' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600';
        let actions = '';
        if (c.status === '신청') {
            actions = '<button onclick="approveCarryoverRow(' + c.id + ')" class="text-xs text-emerald-600 hover:underline mr-2">승인</button>'
                    + '<button onclick="rejectCarryoverRow(' + c.id + ')" class="text-xs text-red-500 hover:underline">반려</button>';
        }
        html += '<tr class="border-b border-gray-100">'
            + '<td class="py-2 px-3 text-gray-900">' + escHtml(c.employee_name) + ' <span class="text-xs text-gray-400">' + escHtml(c.dept_name) + '</span></td>'
            + '<td class="py-2 px-3 text-center text-gray-600">' + c.from_year + ' → ' + c.to_year + '</td>'
            + '<td class="py-2 px-3 text-center font-medium tabular-nums">' + c.days + '일</td>'
            + '<td class="py-2 px-3 text-gray-600">' + escHtml(c.reason || '-') + '</td>'
            + '<td class="py-2 px-3 text-center"><span class="text-xs px-1.5 py-0.5 rounded ' + statusCls + '">' + escHtml(c.status) + '</span></td>'
            + '<td class="py-2 px-3 text-center">' + actions + '</td>'
            + '</tr>';
    });
    html += '</tbody></table>';
    el.innerHTML = html;
};

window.submitCarryover = async function() {
    const empId = document.getElementById('coEmployee')?.value;
    const days = parseFloat(document.getElementById('coDays')?.value) || 0;
    const reason = document.getElementById('coReason')?.value?.trim();
    if (!empId) { toast('직원을 선택해주세요.', 'error'); return; }
    if (days <= 0) { toast('이월 일수를 입력해주세요.', 'error'); return; }
    if (!reason) { toast('이월 사유를 입력해주세요.', 'error'); return; }
    const data = await api('requestCarryover', null, {employee_id: parseInt(empId), from_year: YEAR, days, reason});
    if (data.success) {
        toast(data.message, 'success');
        document.getElementById('carryoverModal')?.classList.add('hidden');
        await loadCarryoverList();
    }
    else toast(data.error || '등록 실패', 'error');
};

window.approveCarryoverRow = async function(id) {
    if (!(await AppUI.confirm('이월을 승인하시겠습니까?'))) return;
    const data = await api('approveCarryover', null, {id});
    if (data.success) { toast(data.message, 'success'); await loadCarryovers(); }
    else toast(data.error || '승인 실패', 'error');
};

window.rejectCarryoverRow = async function(id) {
    if (!(await AppUI.confirm('이월을 반려하시겠습니까?'))) return;
    const data = await api('rejectCarryover', null, {id});
    if (data.success) { toast(data.message, 'success'); await loadCarryovers(); }
    else toast(data.error || '반려 실패', 'error');
};

/* ═══ 촉진 관리 ═══ */

window.openPromotionModal = function() {
    const m = document.getElementById('promotionModal');
    if (!m) return;
    m.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    loadPromotions();
};

async function loadPromotions() {
    const data = await api('getPromotions', {year: YEAR});
    const list = data.promotions || [];
    const el = document.getElementById('promotionList');
    if (!el) return;
    if (list.length === 0) {
        el.innerHTML = '<p class="text-gray-500 text-center py-6">촉진 이력이 없습니다. "촉진 대상자 조회" 버튼으로 대상자를 확인하세요.</p>';
        return;
    }
    const statusCls = {미응답: 'bg-amber-50 text-amber-600', 계획제출: 'bg-blue-50 text-blue-600', 지정통보: 'bg-emerald-50 text-emerald-600'};
    let html = '<table class="w-full text-sm"><thead><tr class="border-b-2 border-gray-200">'
        + '<th class="py-2 px-3 text-left text-gray-600">직원</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">단계</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">잔여</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">상태</th>'
        + '<th class="py-2 px-3 text-left text-gray-600">기한</th>'
        + '<th class="py-2 px-3 text-left text-gray-600">통보일</th>'
        + '</tr></thead><tbody>';
    list.forEach(p => {
        const cls = statusCls[p.response_status] || 'bg-gray-100 text-gray-600';
        html += '<tr class="border-b border-gray-100">'
            + '<td class="py-2 px-3 text-gray-900">' + escHtml(p.employee_name) + ' <span class="text-xs text-gray-400">' + escHtml(p.dept_name) + '</span></td>'
            + '<td class="py-2 px-3 text-center font-medium">' + p.stage + '차</td>'
            + '<td class="py-2 px-3 text-center tabular-nums">' + (p.remaining ?? '-') + '일</td>'
            + '<td class="py-2 px-3 text-center"><span class="text-xs px-1.5 py-0.5 rounded ' + cls + '">' + escHtml(p.response_status) + '</span></td>'
            + '<td class="py-2 px-3 text-gray-600 tabular-nums">' + fmtDate(p.deadline) + '</td>'
            + '<td class="py-2 px-3 text-gray-500 tabular-nums">' + fmtDate(p.notified_at) + '</td>'
            + '</tr>';
    });
    html += '</tbody></table>';
    el.innerHTML = html;
}

window.loadPromotionTargets = async function(stage) {
    const data = await api('getPromotionTargets', {year: YEAR, stage});
    const list = data.targets || [];
    const el = document.getElementById('promotionTargets');
    if (!el) return;
    if (list.length === 0) {
        el.innerHTML = '<p class="text-gray-500 text-center py-4">' + stage + '차 촉진 대상자가 없습니다.</p>';
        return;
    }
    let html = '<div class="flex items-center justify-between mb-3">'
        + '<span class="text-sm text-gray-600">' + list.length + '명 대상</span>'
        + '<button onclick="sendPromotionBatch(' + stage + ')" class="btn btn-primary btn-sm">일괄 통보</button></div>';
    html += '<div class="max-h-48 overflow-y-auto space-y-1">';
    list.forEach(t => {
        html += '<label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer">'
            + '<input type="checkbox" class="promoTarget rounded" value="' + t.id + '" checked>'
            + '<span class="text-sm text-gray-900">' + escHtml(t.name) + '</span>'
            + '<span class="text-xs text-gray-400">' + escHtml(t.dept) + '</span>'
            + '<span class="text-xs text-primary ml-auto">잔여 ' + t.remaining + '일</span>'
            + '</label>';
    });
    html += '</div>';
    el.innerHTML = html;
};

window.sendPromotionBatch = async function(stage) {
    const checks = document.querySelectorAll('.promoTarget:checked');
    const ids = Array.from(checks).map(c => parseInt(c.value));
    if (ids.length === 0) { toast('대상자를 선택해주세요.', 'error'); return; }
    if (!(await AppUI.confirm(ids.length + '명에게 ' + stage + '차 촉진 통보를 발송하시겠습니까?'))) return;
    const data = await api('createPromotion', null, {employee_ids: ids, year: YEAR, stage});
    if (data.success) { toast(data.message, 'success'); await loadPromotions(); document.getElementById('promotionTargets').innerHTML = ''; }
    else toast(data.error || '발송 실패', 'error');
};

/* ═══ 퇴사자 정산 ═══ */

window.updateStPreview = function() {
    const raw = (document.getElementById('stBaseSalary')?.value || '').replace(/,/g, '');
    const salary = parseInt(raw) || 0;
    const daily = salary > 0 ? Math.round(salary / 21.67) : 0;
    const el = document.getElementById('stDailyWage');
    if (el) el.textContent = daily.toLocaleString('ko-KR');
};

window.openSettlementModal = function() {
    const m = document.getElementById('settlementModal');
    if (!m) return;
    m.classList.remove('hidden');
    document.getElementById('stResult')?.classList.add('hidden');
    updateStPreview();
    if (typeof lucide !== 'undefined') lucide.createIcons();
};

window.loadSettlementList = async function() {
    document.getElementById('settlementListModal')?.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    await loadSettlements();
};

async function loadSettlements() {
    const data = await api('getSettlements', {year: YEAR});
    const list = data.settlements || [];
    const el = document.getElementById('settlementList');
    if (!el) return;
    if (list.length === 0) {
        el.innerHTML = '<p class="text-gray-500 text-center py-6">정산 내역이 없습니다.</p>';
        return;
    }
    let html = '<table class="w-full text-sm"><thead><tr class="border-b-2 border-gray-200">'
        + '<th class="py-2 px-3 text-left text-gray-600">직원</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">퇴사일</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">근무월수</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">일할부여</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">사용</th>'
        + '<th class="py-2 px-3 text-center text-gray-600">미사용</th>'
        + '<th class="py-2 px-3 text-right text-gray-600">보상액</th>'
        + '</tr></thead><tbody>';
    list.forEach(s => {
        html += '<tr class="border-b border-gray-100">'
            + '<td class="py-2 px-3 text-gray-900">' + escHtml(s.employee_name) + '</td>'
            + '<td class="py-2 px-3 text-center text-gray-600 tabular-nums">' + fmtDate(s.resign_date) + '</td>'
            + '<td class="py-2 px-3 text-center tabular-nums">' + s.worked_months + '개월</td>'
            + '<td class="py-2 px-3 text-center tabular-nums">' + s.prorated_days + '일</td>'
            + '<td class="py-2 px-3 text-center tabular-nums">' + s.used_days + '일</td>'
            + '<td class="py-2 px-3 text-center font-medium text-primary tabular-nums">' + s.remaining_days + '일</td>'
            + '<td class="py-2 px-3 text-right font-medium tabular-nums">' + fmtNum(s.settlement_amount) + '원</td>'
            + '</tr>';
    });
    html += '</tbody></table>';
    el.innerHTML = html;
}

window.submitSettlement = async function() {
    const empId = document.getElementById('stEmployee')?.value;
    const baseSalary = parseInt((document.getElementById('stBaseSalary')?.value || '').replace(/,/g, '')) || 0;
    const memo = document.getElementById('stMemo')?.value?.trim();
    if (!empId) { toast('직원을 선택해주세요.', 'error'); return; }
    if (!baseSalary) { toast('기본급을 입력해주세요.', 'error'); return; }
    if (!(await AppUI.confirm('정산을 실행하시겠습니까? 대기 중인 휴가 신청이 자동 취소됩니다.'))) return;
    const data = await api('settleLeave', null, {employee_id: parseInt(empId), year: YEAR, base_salary: baseSalary, memo});
    if (data.success) {
        toast(data.message, 'success');
        if (data.settlement) {
            const s = data.settlement;
            function card(label, value, color) {
                return '<div style="background:#f9fafb;border-radius:8px;padding:10px 12px">'
                    + '<p style="font-size:12px;color:#6b7280;margin-bottom:2px">' + label + '</p>'
                    + '<p style="font-size:16px;font-weight:700;color:' + color + ';font-variant-numeric:tabular-nums">' + value + '</p></div>';
            }
            document.getElementById('stResultCards').innerHTML =
                card('근무 월수', s.worked_months + '개월', '#111827')
                + card('일할 부여', s.prorated_days + '일', '#111827')
                + card('사용일', s.used_days + '일', '#6b7280')
                + card('미사용 잔여', s.remaining_days + '일', '#4F6AFF')
                + card('일급', fmtNum(s.daily_wage) + '원', '#111827')
                + card('보상액', fmtNum(s.settlement_amount) + '원', '#dc2626');
            document.getElementById('stResult').classList.remove('hidden');
        }
    } else toast(data.error || '정산 실패', 'error');
};

/* ═══ 대시보드 ═══ */

let _dashLoaded = false;

window.toggleDashboard = async function() {
    const table = document.getElementById('annualTable');
    const dash = document.getElementById('annualDash');
    const filterBox = document.getElementById('annualFilterBox');
    const btn = document.getElementById('dashToggleBtn');
    if (!table || !dash) return;

    const icon = document.getElementById('dashToggleIcon');
    const label = document.getElementById('dashToggleLabel');
    if (dash.classList.contains('hidden')) {
        table.classList.add('hidden');
        if (filterBox) filterBox.classList.add('hidden');
        dash.classList.remove('hidden');
        if (icon) icon.setAttribute('data-lucide', 'list');
        if (label) label.textContent = '목록';
        lucide.createIcons({attrs:{class:'w-3.5 h-3.5'},nameAttr:'data-lucide'});
        if (!_dashLoaded) { await loadDashboard(); _dashLoaded = true; }
    } else {
        dash.classList.add('hidden');
        table.classList.remove('hidden');
        if (filterBox) filterBox.classList.remove('hidden');
        if (icon) icon.setAttribute('data-lucide', 'bar-chart-3');
        if (label) label.textContent = '대시보드';
        lucide.createIcons({attrs:{class:'w-3.5 h-3.5'},nameAttr:'data-lucide'});
    }
};

async function loadDashboard() {
    const data = await api('getDashboard', {year: YEAR});

    // 부서별 사용률 (계층 구분)
    const deptEl = document.getElementById('dashDeptChart');
    if (deptEl && data.deptStats) {
        window._deptStatsRaw = data.deptStats;
        renderDeptChart();
    }

    // 월별 추이 (기간 필터 포함)
    const monthEl = document.getElementById('dashMonthChart');
    if (monthEl && Array.isArray(data.monthlyStats)) {
        window._monthlyStats = data.monthlyStats;
        renderMonthlyChart('all');
    }

    // 장기 미사용 경고
    const warnEl = document.getElementById('dashWarnings');
    if (warnEl && data.warnings) {
        if (data.warnings.length === 0) {
            warnEl.innerHTML = '<p style="text-align:center;color:#9ca3af;padding:24px 0;font-size:14px">장기 미사용자 없음</p>';
        } else {
            let html = '<table style="width:100%;font-size:14px;border-collapse:collapse"><thead>'
                + '<tr style="border-bottom:2px solid #e5e7eb">'
                + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#6b7280;font-size:13px">이름</th>'
                + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#6b7280;font-size:13px">부서</th>'
                + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#6b7280;font-size:13px">잔여/부여</th>'
                + '<th style="text-align:center;padding:8px 12px;font-weight:600;color:#6b7280;font-size:13px;width:60px">잔여율</th>'
                + '</tr></thead><tbody>';
            data.warnings.forEach(w => {
                const pctColor = w.remain_pct >= 80 ? '#dc2626' : '#f59e0b';
                html += '<tr style="border-bottom:1px solid #f3f4f6">'
                    + '<td style="padding:10px 12px;font-weight:600;color:#111827">' + escHtml(w.name) + '</td>'
                    + '<td style="padding:10px 12px;color:#6b7280">' + escHtml(w.dept) + '</td>'
                    + '<td style="padding:10px 12px;text-align:right;font-variant-numeric:tabular-nums;font-weight:600;color:' + pctColor + '">' + w.remaining + '/' + w.total_days + '일</td>'
                    + '<td style="padding:10px 12px;text-align:center"><span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;background:' + (w.remain_pct >= 80 ? '#fef2f2' : '#fffbeb') + ';color:' + pctColor + '">' + w.remain_pct + '%</span></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            warnEl.innerHTML = html;
        }
    }
}

/* ═══ 월별 추이 차트 렌더러 ═══ */

const MONTH_FILTERS = {
    all:  {label: '전체',   months: [1,2,3,4,5,6,7,8,9,10,11,12]},
    h1:   {label: '상반기', months: [1,2,3,4,5,6]},
    h2:   {label: '하반기', months: [7,8,9,10,11,12]},
    q1:   {label: 'Q1',     months: [1,2,3]},
    q2:   {label: 'Q2',     months: [4,5,6]},
    q3:   {label: 'Q3',     months: [7,8,9]},
    q4:   {label: 'Q4',     months: [10,11,12]},
};
let _monthFilter = 'all';

function renderMonthlyChart(filterKey) {
    const monthEl = document.getElementById('dashMonthChart');
    if (!monthEl || !window._monthlyStats) return;
    _monthFilter = filterKey || 'all';
    const filter = MONTH_FILTERS[_monthFilter] || MONTH_FILTERS.all;
    const curMonth = new Date().getMonth() + 1;

    const allStats = window._monthlyStats;
    const stats = allStats.filter(m => filter.months.includes(m.month));
    const maxDays = Math.max(...stats.map(m => Number(m.days) || 0), 0.1);
    const cumDays = stats.reduce((s, m) => s + (Number(m.days) || 0), 0);
    const activeCnt = stats.filter(m => m.month <= curMonth && Number(m.days) > 0).length;
    const BAR_MAX_H = 100;

    let chipHtml = '<div style="display:flex;gap:4px;margin-bottom:12px;flex-wrap:wrap">';
    Object.entries(MONTH_FILTERS).forEach(([k, v]) => {
        const active = k === _monthFilter;
        chipHtml += '<button onclick="window._setMonthFilter(\'' + k + '\')" style="padding:3px 10px;border-radius:6px;font-size:11px;font-weight:' + (active ? '600' : '400')
            + ';border:1px solid ' + (active ? '#4F6AFF' : '#e5e7eb')
            + ';background:' + (active ? '#eef1ff' : '#fff')
            + ';color:' + (active ? '#4F6AFF' : '#6b7280')
            + ';cursor:pointer">' + v.label + '</button>';
    });
    chipHtml += '</div>';

    let barsHtml = '';
    stats.forEach(m => {
        const days = Number(m.days) || 0;
        const count = Number(m.count) || 0;
        const h = days > 0 ? Math.max(Math.round((days / maxDays) * BAR_MAX_H), 8) : 4;
        const isNow = m.month === curMonth;
        const isFuture = m.month > curMonth;
        const barBg = isFuture ? '#f3f4f6' : days === 0 ? '#e5e7eb' : isNow ? '#4F6AFF' : '#93a3fc';
        const labelColor = isFuture ? '#d1d5db' : days > 0 ? '#374151' : '#d1d5db';
        barsHtml += '<div style="width:42px;display:flex;flex-direction:column;align-items:center;gap:3px">'
            + '<span style="font-size:11px;color:' + labelColor + ';font-variant-numeric:tabular-nums;font-weight:500">' + (days > 0 ? days + '일' : isFuture ? '' : '0') + '</span>'
            + '<div style="width:28px;height:' + h + 'px;background:' + barBg + ';border-radius:4px 4px 0 0" title="' + m.month + '월: ' + days + '일 / ' + count + '건"></div>'
            + '<span style="font-size:11px;color:' + (isNow ? '#4F6AFF' : isFuture ? '#d1d5db' : '#9ca3af') + ';font-weight:' + (isNow ? '700' : '400') + '">' + m.month + '월</span>'
            + '</div>';
    });

    const avg = activeCnt > 0 ? (cumDays / activeCnt).toFixed(1) : '0';
    let summaryHtml = '<div style="display:flex;gap:16px;margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;font-size:12px;color:#9ca3af">'
        + '<span>합계 <strong style="color:#374151">' + cumDays + '일</strong></span>'
        + '<span>월 평균 <strong style="color:#374151">' + avg + '일</strong></span>'
        + '</div>';

    monthEl.innerHTML = chipHtml
        + '<div style="display:flex;align-items:flex-end;gap:6px;justify-content:center">' + barsHtml + '</div>'
        + summaryHtml;
}

window._setMonthFilter = function(key) {
    renderMonthlyChart(key);
};

/* ═══ 부서별 사용률 차트 렌더러 ═══ */

let _deptView = 'team';
let _deptFilter = 'all';

function renderDeptChart() {
    const deptEl = document.getElementById('dashDeptChart');
    if (!deptEl || !window._deptStatsRaw) return;
    const all = window._deptStatsRaw;

    const parentNames = new Set(all.map(d => d.parent_name).filter(Boolean));
    const teams = all.filter(d => !parentNames.has(d.dept));
    const divDirect = all.filter(d => parentNames.has(d.dept));
    const divNames = [...new Set(all.map(d => d.parent_name || d.dept).filter(Boolean))].filter(n => parentNames.has(n) || divDirect.some(dd => dd.dept === n));
    const uniqueDivs = [...new Set([...divDirect.map(d => d.dept), ...teams.map(d => d.parent_name).filter(Boolean)])].sort();

    const groups = {};
    teams.forEach(d => {
        const grp = d.parent_name || d.dept;
        if (!groups[grp]) groups[grp] = [];
        groups[grp].push(d);
    });
    divDirect.forEach(d => {
        if (!groups[d.dept]) groups[d.dept] = [];
        groups[d.dept].push({...d, dept: '본부 소속', _isDirect: true});
    });

    const filteredAll = _deptFilter === 'all' ? all : all.filter(d => {
        const grp = parentNames.has(d.dept) ? d.dept : (d.parent_name || d.dept);
        return grp === _deptFilter;
    });
    const totalEmp = filteredAll.reduce((s, d) => s + (Number(d.emp_count) || 0), 0);
    const totalUsed = filteredAll.reduce((s, d) => s + (Number(d.used) || 0), 0);
    const totalDays = filteredAll.reduce((s, d) => s + (Number(d.total) || 0), 0);
    const totalRemain = totalDays - totalUsed;
    const totalRate = totalDays > 0 ? Math.round(totalUsed / totalDays * 100) : 0;

    let html = '';

    html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">';
    html += '<div style="display:flex;gap:4px;flex-wrap:wrap">';
    const viewOpts = [{k:'team',l:'팀별'},{k:'div',l:'본부별'}];
    viewOpts.forEach(v => {
        const active = _deptView === v.k;
        html += '<button data-dept-view="' + escAttr(v.k) + '" style="padding:3px 10px;border-radius:6px;font-size:11px;font-weight:' + (active ? '600' : '400')
            + ';border:1px solid ' + (active ? '#4F6AFF' : '#e5e7eb')
            + ';background:' + (active ? '#eef1ff' : '#fff')
            + ';color:' + (active ? '#4F6AFF' : '#6b7280')
            + ';cursor:pointer">' + escHtml(v.l) + '</button>';
    });
    html += '</div>';
    html += '<div style="display:flex;gap:3px;flex-wrap:wrap">';
    [{k:'all',l:'전체'}].concat(uniqueDivs.map(n => ({k:n,l:n.replace(/본부$/,'')}))).forEach(f => {
        const active = _deptFilter === f.k;
        html += '<button data-dept-filter="' + escAttr(f.k) + '" style="padding:2px 8px;border-radius:5px;font-size:10px;font-weight:' + (active ? '600' : '400')
            + ';border:1px solid ' + (active ? '#374151' : '#e5e7eb')
            + ';background:' + (active ? '#f3f4f6' : '#fff')
            + ';color:' + (active ? '#374151' : '#9ca3af')
            + ';cursor:pointer">' + escHtml(f.l) + '</button>';
    });
    html += '</div></div>';

    html += '<div style="display:flex;gap:16px;padding-bottom:6px;margin-bottom:4px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#9ca3af">'
        + '<span>' + (_deptFilter === 'all' ? '전체' : escHtml(_deptFilter)) + ' <strong style="color:#374151">' + totalEmp + '명</strong></span>'
        + '<span>부여 <strong style="color:#374151">' + totalDays + '일</strong></span>'
        + '<span>사용 <strong style="color:#4F6AFF">' + totalUsed + '일</strong> <span style="color:#d1d5db">' + totalRate + '%</span></span>'
        + '<span>잔여 <strong style="color:#f59e0b">' + totalRemain + '일</strong></span>'
        + '</div>';

    html += '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">'
        + '<span style="width:80px;text-align:right;font-size:10px;color:#d1d5db">' + (_deptView === 'div' ? '본부' : '부서') + '</span>'
        + '<span style="flex:1;font-size:10px;color:#d1d5db;text-align:center">사용률</span>'
        + '<span style="flex-shrink:0;font-size:10px;color:#d1d5db;white-space:nowrap">사용/부여 · 인원</span>'
        + '</div>';

    html += '<div style="max-height:520px;overflow-y:auto">';

    if (_deptView === 'div') {
        const divAgg = {};
        all.forEach(d => {
            const grp = parentNames.has(d.dept) ? d.dept : (d.parent_name || d.dept);
            if (_deptFilter !== 'all' && grp !== _deptFilter) return;
            if (!divAgg[grp]) divAgg[grp] = {emp: 0, used: 0, total: 0};
            divAgg[grp].emp += Number(d.emp_count) || 0;
            divAgg[grp].used += Number(d.used) || 0;
            divAgg[grp].total += Number(d.total) || 0;
        });
        const sorted = Object.entries(divAgg).sort((a, b) => {
            const ra = a[1].total > 0 ? a[1].used / a[1].total : 0;
            const rb = b[1].total > 0 ? b[1].used / b[1].total : 0;
            return rb - ra;
        });
        sorted.forEach(([name, ag]) => {
            html += renderBar(name, ag.used, ag.total, ag.emp, '#4b5563');
        });
    } else {
        let first = true;
        Object.entries(groups).forEach(([grpName, items]) => {
            if (_deptFilter !== 'all' && grpName !== _deptFilter) return;
            html += '<div style="font-size:11px;color:#9ca3af;font-weight:600;margin:' + (first ? '0' : '8px') + ' 0 3px 0;' + (first ? '' : 'padding-top:5px;border-top:1px solid #f3f4f6') + '">' + escHtml(grpName) + '</div>';
            first = false;
            items.forEach(d => {
                const nameColor = d._isDirect ? '#9ca3af' : '#4b5563';
                html += renderBar(d.dept, Number(d.used)||0, Number(d.total)||0, Number(d.emp_count)||0, nameColor);
            });
        });
    }

    html += '</div>';
    deptEl.innerHTML = html || '<p style="text-align:center;color:#9ca3af;padding:24px 0;font-size:14px">데이터 없음</p>';

    deptEl.querySelectorAll('[data-dept-view]').forEach(btn => {
        btn.addEventListener('click', () => window._setDeptView(btn.dataset.deptView));
    });
    deptEl.querySelectorAll('[data-dept-filter]').forEach(btn => {
        btn.addEventListener('click', () => window._setDeptFilter(btn.dataset.deptFilter));
    });
}

function renderBar(name, used, total, empCount, nameColor) {
    const pct = total > 0 ? Math.round(used / total * 100) : 0;
    const barColor = pct >= 70 ? '#10b981' : pct >= 40 ? '#4F6AFF' : '#f59e0b';
    return '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">'
        + '<span style="width:80px;text-align:right;font-size:13px;color:' + nameColor + ';white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="' + escAttr(name) + '">' + escHtml(name) + '</span>'
        + '<div style="flex:1;min-width:0;height:22px;background:#f3f4f6;border-radius:11px;position:relative;overflow:hidden">'
        + '<div style="height:100%;width:' + Math.max(pct, 3) + '%;background:' + barColor + ';border-radius:11px;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;transition:width .4s">'
        + (pct >= 22 ? '<span style="font-size:11px;color:#fff;font-weight:600">' + pct + '%</span>' : '')
        + '</div>'
        + (pct < 22 ? '<span style="position:absolute;left:' + Math.max(pct, 3) + '%;margin-left:6px;top:50%;transform:translateY(-50%);font-size:11px;color:#6b7280;font-weight:500">' + pct + '%</span>' : '')
        + '</div>'
        + '<span style="flex-shrink:0;font-size:11px;color:#9ca3af;white-space:nowrap">'
        + '<span style="color:#6b7280;font-weight:500">' + used + '</span>/' + total + '일'
        + ' <span style="color:#d1d5db">·</span> ' + empCount + '명</span>'
        + '</div>';
}

window._setDeptView = function(v) { _deptView = v; renderDeptChart(); };
window._setDeptFilter = function(f) { _deptFilter = f; renderDeptChart(); };

/* ═══ 더보기 팝오버 ═══ */

window.closeAnnualMore = function() {
    const pop = document.getElementById('annualMorePopover');
    if (pop) pop.classList.add('hidden');
};

(function initMorePopover() {
    document.addEventListener('click', function(e) {
        const btn = document.getElementById('annualMoreBtn');
        const pop = document.getElementById('annualMorePopover');
        if (!btn || !pop) return;

        const actionBtn = e.target.closest('[data-more-action]');
        if (actionBtn && pop.contains(actionBtn)) {
            e.stopPropagation();
            const fn = actionBtn.dataset.moreAction;
            pop.classList.add('hidden');
            if (typeof window[fn] === 'function') window[fn]();
            return;
        }

        if (btn.contains(e.target)) {
            pop.classList.toggle('hidden');
            if (!pop.classList.contains('hidden')) lucide.createIcons({attrs:{class:'w-4 h-4 text-gray-400'},nameAttr:'data-lucide'});
        } else if (!pop.contains(e.target)) {
            pop.classList.add('hidden');
        }
    });
})();

})();
