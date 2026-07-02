/**
 * 대시보드 JS — dashboard.php에서 추출
 * DASHBOARD_CONFIG 전역 객체를 통해 PHP 데이터를 수신한다.
 */

var ATT_API       = DASHBOARD_CONFIG.attApi;
var SCHEDULE_API  = DASHBOARD_CONFIG.scheduleApi;
var CURRENT_EMP_ID = DASHBOARD_CONFIG.currentEmpId;
var categories    = DASHBOARD_CONFIG.categories;
var employees     = DASHBOARD_CONFIG.employees;
var hasDB         = DASHBOARD_CONFIG.hasDB;
var selectedAttendees = { c: [], e: [] };
var saving = false;

// 게시판 탭
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.board-tab-btn');
    if (!btn) return;
    var tab = btn.dataset.tab;
    document.querySelectorAll('.board-tab-btn').forEach(function(b) {
        b.classList.toggle('zm-tab-active', b.dataset.tab === tab);
    });
    document.querySelectorAll('.board-tab-panel').forEach(function(p) {
        p.classList.toggle('hidden', p.dataset.tab !== tab);
    });
});

// 출퇴근
function fmtTime(t) { return t ? t.substring(0, 5) : '--:--'; }
function todayPrefix() { var d = new Date(); return (d.getMonth()+1) + '/' + d.getDate(); }

function fmtElapsed(clockInISO) {
    var diff = Math.max(0, Math.floor((Date.now() - new Date(clockInISO).getTime()) / 60000));
    var h = Math.floor(diff / 60), m = diff % 60;
    return h > 0 ? (h + '시간 ' + m + '분째') : (m + '분째');
}

function startElapsedTicker() {
    var el = document.getElementById('clockElapsed');
    if (!el) return;
    var iso = el.dataset.clockIn;
    if (!iso) return;
    el.textContent = fmtElapsed(iso);
    setInterval(function() { el.textContent = fmtElapsed(iso); }, 60000);
}

function handleClock() {
    var btn = document.getElementById('clockBtn');
    if (btn.disabled) return;
    if (!CURRENT_EMP_ID) { showToast('로그인이 필요합니다', 'error'); return; }
    btn.disabled = true;

    var elapsedEl = document.getElementById('clockElapsed');
    var action = elapsedEl ? 'clockOut' : 'clockIn';

    fetch(ATT_API + '?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({employee_id: CURRENT_EMP_ID})
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.success) {
            btn.disabled = false;
            showToast(res.error || '처리 중 오류가 발생했습니다', 'error');
            return;
        }
        var area = document.getElementById('clockArea');
        if (action === 'clockIn') {
            var tIn = fmtTime(res.clock_in);
            var iso = new Date().toISOString();
            area.className = 'clock-widget';
            area.innerHTML =
                '<span class="clock-pulse"></span>' +
                '<span class="clock-time">' + tIn + '</span>' +
                '<span id="clockElapsed" class="clock-dur" data-clock-in="' + iso + '"></span>' +
                '<button id="clockBtn" onclick="handleClock()" class="clock-btn">퇴근</button>';
            startElapsedTicker();
            showToast('출근이 기록되었습니다 (' + tIn + ')');
        } else {
            var tOut = fmtTime(res.clock_out);
            var tIn = fmtTime(res.clock_in);
            var wLabel = '';
            if (res.clock_in && res.clock_out) {
                var diffMin = Math.max(0, Math.floor(
                    (new Date('2000-01-01T' + res.clock_out) - new Date('2000-01-01T' + res.clock_in)) / 60000
                ));
                var wHrs = Math.floor(diffMin / 60);
                var wMin = diffMin % 60;
                wLabel = wHrs > 0 ? (wHrs + '시간' + (wMin > 0 ? ' ' + wMin + '분' : '')) : (wMin + '분');
            }
            area.className = 'clock-widget';
            area.innerHTML =
                '<span class="clock-time">' + tIn + '</span>' +
                '<span class="clock-sep">~</span>' +
                '<span class="clock-time">' + tOut + '</span>' +
                (wLabel ? '<span class="clock-dur">' + wLabel + '</span>' : '');
            showToast('퇴근이 기록되었습니다 (' + tOut + ')');
        }
    })
    .catch(function() {
        btn.disabled = false;
        showToast('서버 연결에 실패했습니다', 'error');
    });
}

// 일정 모달
function openCreateModal(dateStr) {
    if (!hasDB) { showToast('DB 미연결 상태에서는 등록할 수 없습니다', 'error'); return; }
    var d = dateStr || new Date().toISOString().split('T')[0];
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
    showModal('createModal');
}
function closeCreateModal() { hideModal('createModal'); }

function saveEvent() {
    if (saving) return;
    var data = {
        title: document.getElementById('cTitle').value.trim(),
        start_date: document.getElementById('cStartDate').value,
        end_date: document.getElementById('cEndDate').value,
        start_time: document.getElementById('cStartTime').value || null,
        end_time: document.getElementById('cEndTime').value || null,
        is_all_day: document.getElementById('cAllDay').checked,
        category_item_id: document.getElementById('cCategory').value || null,
        creator_id: document.getElementById('cCreator').value,
        description: document.getElementById('cDesc').value.trim(),
        attendee_ids: selectedAttendees.c.map(function(a) { return a.id; }),
    };
    if (!data.title) { showToast('일정 제목을 입력해주세요', 'error'); return; }
    if (!data.start_date || !data.end_date) { showToast('날짜를 입력해주세요', 'error'); return; }
    if (!data.creator_id) { showToast('작성자를 선택해주세요', 'error'); return; }

    saving = true;
    fetch(SCHEDULE_API + '?action=createEvent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) { showToast('일정이 등록되었습니다'); closeCreateModal(); location.reload(); }
        else { showToast(res.error || '등록 실패', 'error'); }
    })
    .catch(function() { showToast('서버 오류', 'error'); })
    .finally(function() { saving = false; });
}

function openDetailModal(id) {
    fetch(SCHEDULE_API + '?action=getEvent&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.event) { showToast('일정을 찾을 수 없습니다', 'error'); return; }
            showDetailView(data.event);
            showModal('detailModal');
        })
        .catch(function() { showToast('서버 오류', 'error'); });
}

function showDetailView(ev) {
    document.getElementById('dModalTitle').textContent = '일정 상세';
    document.getElementById('detailView').classList.remove('hidden');
    document.getElementById('editView').classList.add('hidden');
    document.getElementById('btnEdit').classList.remove('hidden');
    document.getElementById('btnSave').classList.add('hidden');

    document.getElementById('dTitle').textContent = ev.title;
    var dateStr = ev.start_date;
    if (ev.start_date !== ev.end_date) dateStr += ' ~ ' + ev.end_date;
    if (ev.is_all_day == 1) { dateStr += ' (종일)'; }
    else if (ev.start_time) { dateStr += ' ' + ev.start_time.substring(0,5) + (ev.end_time ? ' ~ ' + ev.end_time.substring(0,5) : ''); }
    document.getElementById('dDate').textContent = dateStr;

    document.getElementById('dCategory').innerHTML = '<span class="bg-slate-800 text-slate-200 text-sm px-2.5 py-1 rounded-md">' + esc(ev.category_name || '미분류') + '</span>';
    document.getElementById('dCreator').textContent = (ev.creator_name || '') + (ev.creator_position ? ' (' + ev.creator_position + ')' : '') + (ev.creator_department ? ' · ' + ev.creator_department : '');

    var attendees = ev.attendees || [];
    if (attendees.length) {
        document.getElementById('dAttendeesWrap').classList.remove('hidden');
        document.getElementById('dAttendees').innerHTML = attendees.map(function(a) { return '<span class="inline-block bg-slate-800 text-slate-200 text-sm px-2.5 py-1 rounded-md mr-1.5 mb-1.5">' + esc(a.name) + '</span>'; }).join('');
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
}

function switchToEdit() {
    var ev = JSON.parse(document.getElementById('detailModal').dataset.event || '{}');
    document.getElementById('dModalTitle').textContent = '일정 수정';
    document.getElementById('detailView').classList.add('hidden');
    document.getElementById('editView').classList.remove('hidden');
    document.getElementById('btnEdit').classList.add('hidden');
    document.getElementById('btnSave').classList.remove('hidden');

    document.getElementById('eId').value = ev.id;
    document.getElementById('eTitle').value = ev.title;
    document.getElementById('eStartDate').value = ev.start_date;
    document.getElementById('eEndDate').value = ev.end_date;
    document.getElementById('eStartTime').value = ev.start_time ? ev.start_time.substring(0,5) : '';
    document.getElementById('eEndTime').value = ev.end_time ? ev.end_time.substring(0,5) : '';
    document.getElementById('eAllDay').checked = ev.is_all_day == 1;
    document.getElementById('eCategory').value = ev.category_item_id || '';
    document.getElementById('eDesc').value = ev.description || '';
    toggleAllDay('e');

    selectedAttendees.e = (ev.attendees || []).map(function(a) { return { id: a.employee_id, name: a.name, position: a.position }; });
    renderAttendeeTags('e');
}

function saveEdit() {
    if (saving) return;
    var data = {
        id: document.getElementById('eId').value,
        title: document.getElementById('eTitle').value.trim(),
        start_date: document.getElementById('eStartDate').value,
        end_date: document.getElementById('eEndDate').value,
        start_time: document.getElementById('eStartTime').value || null,
        end_time: document.getElementById('eEndTime').value || null,
        is_all_day: document.getElementById('eAllDay').checked,
        category_item_id: document.getElementById('eCategory').value || null,
        description: document.getElementById('eDesc').value.trim(),
        attendee_ids: selectedAttendees.e.map(function(a) { return a.id; }),
    };
    if (!data.title) { showToast('일정 제목을 입력해주세요', 'error'); return; }

    saving = true;
    fetch(SCHEDULE_API + '?action=updateEvent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) { showToast('일정이 수정되었습니다'); closeDetailModal(); location.reload(); }
        else { showToast(res.error || '수정 실패', 'error'); }
    })
    .catch(function() { showToast('서버 오류', 'error'); })
    .finally(function() { saving = false; });
}

async function confirmDelete() {
    var ev = JSON.parse(document.getElementById('detailModal').dataset.event || '{}');
    if (!(await AppUI.confirm('"' + ev.title + '" 일정을 삭제하시겠습니까?'))) return;

    fetch(SCHEDULE_API + '?action=deleteEvent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: ev.id }),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) { showToast('일정이 삭제되었습니다'); closeDetailModal(); location.reload(); }
        else { showToast(res.error || '삭제 실패', 'error'); }
    })
    .catch(function() { showToast('서버 오류', 'error'); });
}

function closeDetailModal() { hideModal('detailModal'); }

// 참석자
function initAttendeeInput(prefix) {
    var input = document.getElementById(prefix + 'AttendeeInput');
    var dropdown = document.getElementById(prefix + 'AttendeeDropdown');
    if (!input || !dropdown) return;

    input.addEventListener('input', function() {
        var kw = input.value.trim().toLowerCase();
        if (!kw) { dropdown.classList.add('hidden'); return; }
        var filtered = employees.filter(function(e) {
            return e.name.toLowerCase().indexOf(kw) !== -1 &&
                !selectedAttendees[prefix].some(function(s) { return s.id == e.id; });
        });
        if (!filtered.length) { dropdown.classList.add('hidden'); return; }
        dropdown.innerHTML = filtered.slice(0, 8).map(function(e) {
            return '<div class="px-4 py-2.5 text-sm hover:bg-slate-950 cursor-pointer" data-eid="' + e.id + '">' + esc(e.name) + ' <span class="text-slate-500 text-sm">' + esc(e.position||'') + ' · ' + esc(e.department||'') + '</span></div>';
        }).join('');
        dropdown.querySelectorAll('[data-eid]').forEach(function(el) {
            el.addEventListener('mousedown', function(evt) {
                evt.preventDefault();
                var emp = employees.find(function(e) { return e.id == el.dataset.eid; });
                if (emp) addAttendee(prefix, emp.id, emp.name, emp.position || '');
            });
        });
        dropdown.classList.remove('hidden');
    });
    input.addEventListener('blur', function() { setTimeout(function() { dropdown.classList.add('hidden'); }, 200); });
    input.addEventListener('focus', function() { if (input.value.trim()) input.dispatchEvent(new Event('input')); });
}

function addAttendee(prefix, id, name, position) {
    if (selectedAttendees[prefix].some(function(a) { return a.id === id; })) return;
    selectedAttendees[prefix].push({ id: id, name: name, position: position });
    document.getElementById(prefix + 'AttendeeInput').value = '';
    document.getElementById(prefix + 'AttendeeDropdown').classList.add('hidden');
    renderAttendeeTags(prefix);
}

function removeAttendee(prefix, id) {
    selectedAttendees[prefix] = selectedAttendees[prefix].filter(function(a) { return a.id !== id; });
    renderAttendeeTags(prefix);
}

function renderAttendeeTags(prefix) {
    var wrap = document.getElementById(prefix + 'AttendeeTags');
    if (!wrap) return;
    wrap.innerHTML = selectedAttendees[prefix].map(function(a) {
        return '<span class="inline-flex items-center gap-1.5 bg-slate-800 text-slate-200 text-sm font-medium px-2.5 py-1 rounded-full">' + esc(a.name) + '<button type="button" onclick="removeAttendee(\'' + prefix + '\',' + a.id + ')" class="text-slate-400 hover:text-slate-200">&times;</button></span>';
    }).join('');
}

function toggleAllDay(prefix) {
    var checked = document.getElementById(prefix + 'AllDay').checked;
    var stw = document.getElementById(prefix + 'StartTimeWrap');
    var etw = document.getElementById(prefix + 'EndTimeWrap');
    if (stw) stw.style.display = checked ? 'none' : '';
    if (etw) etw.style.display = checked ? 'none' : '';
}

function populateCategorySelect(selectId) {
    var sel = document.getElementById(selectId); if (!sel) return;
    sel.innerHTML = '<option value="">선택 안함</option>' +
        categories.map(function(c) { return '<option value="' + c.item_id + '">' + esc(c.name) + '</option>'; }).join('');
}

function populateCreatorSelect() {
    var sel = document.getElementById('cCreator'); if (!sel) return;
    sel.innerHTML = employees.map(function(e) {
        var selected = (e.id == CURRENT_EMP_ID) ? ' selected' : '';
        return '<option value="' + e.id + '"' + selected + '>' + esc(e.name) + ' (' + esc(e.position||'') + ')</option>';
    }).join('');
}

function showModal(id) {
    var el = document.getElementById(id);
    el.classList.remove('hidden'); el.classList.add('flex');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function hideModal(id) {
    var el = document.getElementById(id);
    el.classList.add('hidden'); el.classList.remove('flex');
}

function showToast(msg, type) {
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'px-5 py-3 rounded-xl text-sm font-semibold text-white shadow-lg transition-all duration-300 ' + (type === 'error' ? 'bg-slate-900' : 'bg-primary');
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '0'; setTimeout(function() { toast.remove(); }, 300); }, 2500);
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var om = document.getElementById('outsideWorkModal');
        var cm = document.getElementById('createModal');
        var dm = document.getElementById('detailModal');
        if (om && !om.classList.contains('hidden')) closeOutsideWorkModal();
        if (cm && !cm.classList.contains('hidden')) closeCreateModal();
        if (dm && !dm.classList.contains('hidden')) closeDetailModal();
    }
});

// 외근
function openOutsideWorkModal() {
    if (!CURRENT_EMP_ID) { showToast('로그인이 필요합니다', 'error'); return; }
    document.getElementById('owDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('owDepartureTime').value = '09:00';
    document.getElementById('owReturnTime').value = '18:00';
    document.getElementById('owDestination').value = '';
    document.getElementById('owPurpose').value = '';
    showModal('outsideWorkModal');
}
function closeOutsideWorkModal() { hideModal('outsideWorkModal'); }
function saveOutsideWork() {
    var data = {
        employee_id: CURRENT_EMP_ID,
        work_date: document.getElementById('owDate').value,
        departure_time: document.getElementById('owDepartureTime').value,
        return_time: document.getElementById('owReturnTime').value,
        destination: document.getElementById('owDestination').value.trim(),
        purpose: document.getElementById('owPurpose').value.trim()
    };
    if (!data.work_date) { showToast('날짜를 입력해주세요', 'error'); return; }
    if (!data.destination) { showToast('방문처/장소를 입력해주세요', 'error'); return; }

    fetch(ATT_API + '?action=registerOutsideWork', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) { showToast('외근이 등록되었습니다'); closeOutsideWorkModal(); }
        else { showToast(res.error || '등록 실패', 'error'); }
    })
    .catch(function() { showToast('서버 오류가 발생했습니다', 'error'); });
}

// ── 위젯 설정 모달 ──
function openWidgetSettings() {
    var modal = document.getElementById('widgetSettingsModal');
    var list = document.getElementById('widgetSettingsList');
    var widgets = DASHBOARD_CONFIG.widgets || [];
    list.innerHTML = '';
    widgets.forEach(function(w) {
        var row = document.createElement('label');
        row.className = 'flex items-center gap-3 px-4 py-3 rounded-xl ' +
            (w.fixed ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-800 cursor-pointer');
        var cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.checked = w.visible;
        cb.disabled = w.fixed;
        cb.dataset.widgetId = w.id;
        cb.className = 'w-4 h-4 accent-primary rounded';
        var span = document.createElement('span');
        span.className = 'text-sm text-slate-200';
        span.textContent = w.label + (w.fixed ? ' (고정)' : '');
        row.appendChild(cb);
        row.appendChild(span);
        list.appendChild(row);
    });
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lucide.createIcons();
}

function closeWidgetSettings() {
    var modal = document.getElementById('widgetSettingsModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function saveWidgetSettings() {
    var checkboxes = document.querySelectorAll('#widgetSettingsList input[data-widget-id]');
    var widgets = [];
    checkboxes.forEach(function(cb) {
        if (!cb.disabled) {
            widgets.push({ widget_id: cb.dataset.widgetId, is_visible: cb.checked });
        }
    });
    fetch(DASHBOARD_CONFIG.dashApi + '?action=saveWidgetSettings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ widgets: widgets })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            showToast('위젯 설정이 저장되었습니다');
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast((res.error && res.error.message) || '저장 실패', 'error');
        }
    })
    .catch(function() { showToast('서버 오류가 발생했습니다', 'error'); });
}

async function resetWidgetSettings() {
    if (!(await AppUI.confirm('위젯 설정을 기본값으로 되돌릴까요?'))) return;
    fetch(DASHBOARD_CONFIG.dashApi + '?action=resetWidgetSettings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            showToast('기본값으로 복원되었습니다');
            setTimeout(function() { location.reload(); }, 500);
        }
    })
    .catch(function() { showToast('서버 오류가 발생했습니다', 'error'); });
}

document.addEventListener('DOMContentLoaded', function() {
    populateCategorySelect('cCategory');
    populateCategorySelect('eCategory');
    populateCreatorSelect();
    initAttendeeInput('c');
    initAttendeeInput('e');
    startElapsedTicker();
});
