(function () {
    'use strict';

    var TYPE_LABELS = {
        '신규입사':     { color: 'bg-blue-100 text-blue-700',      icon: 'user-plus',        bar: '#3b82f6' },
        '전보':         { color: 'bg-indigo-100 text-indigo-700',   icon: 'arrow-right-left', bar: '#6366f1' },
        '승진':         { color: 'bg-emerald-100 text-emerald-700', icon: 'trending-up',      bar: '#10b981' },
        '직급변경':     { color: 'bg-sky-100 text-sky-700',        icon: 'arrow-up-down',    bar: '#0ea5e9' },
        '직책변경':     { color: 'bg-amber-100 text-amber-700',    icon: 'crown',            bar: '#f59e0b' },
        '고용형태변경': { color: 'bg-violet-100 text-violet-700',   icon: 'file-pen',         bar: '#8b5cf6' },
        '파견':         { color: 'bg-cyan-100 text-cyan-700',      icon: 'map-pin',          bar: '#06b6d4' },
        '전출':         { color: 'bg-teal-100 text-teal-700',      icon: 'log-out',          bar: '#14b8a6' },
        '전입':         { color: 'bg-teal-100 text-teal-700',      icon: 'log-in',           bar: '#14b8a6' },
        '휴직':         { color: 'bg-gray-100 text-gray-600',      icon: 'pause-circle',     bar: '#9ca3af' },
        '복직':         { color: 'bg-lime-100 text-lime-700',      icon: 'play-circle',      bar: '#84cc16' },
        '상태변경':     { color: 'bg-orange-100 text-orange-700',   icon: 'toggle-right',     bar: '#f97316' },
        '복합발령':     { color: 'bg-purple-100 text-purple-700',   icon: 'layers',           bar: '#a855f7' },
        '퇴사':         { color: 'bg-rose-100 text-rose-700',      icon: 'user-x',           bar: '#f43f5e' }
    };

    var APPT_TYPES_FALLBACK = [
        '전보', '승진', '직급변경', '직책변경', '고용형태변경',
        '파견', '전출', '전입', '휴직', '복직',
        '상태변경', '복합발령', '퇴사'
    ];
    var APPT_TYPES = APPT_TYPES_FALLBACK;

    function orgDepartmentLabel() {
        return ((window.ORG_LABELS || {}).department || {}).label || '부서';
    }

    var TYPE_FIELDS = {
        '전보':         ['department_id'],
        '승진':         ['position'],
        '직급변경':     ['position'],
        '직책변경':     ['title'],
        '고용형태변경': ['employment_type'],
        '파견':         ['_destination'],
        '전출':         ['department_id', '_destination'],
        '전입':         ['department_id'],
        '휴직':         ['_leave_type'],
        '복직':         ['department_id'],
        '상태변경':     ['employment_status'],
        '퇴사':         ['employment_status'],
        '복합발령':     ['department_id', 'position', 'title', 'employment_type', 'employment_status']
    };

    var FIELD_LABELS = {
        department_id:     orgDepartmentLabel(),
        position:          '직급',
        title:             '직책',
        employment_type:   '고용형태',
        employment_status: '고용상태',
        _destination:      '파견/전출처',
        _leave_type:       '휴직유형'
    };

    var container, basePath, empId;
    var formWrap, timeline, emptyEl, addBtn, filterChipsEl;
    var departments = null;
    var positions, titles, empTypes, empStatuses;
    var currentEmployee = {};
    var allItems = [];
    var activeFilter = null;

    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    function fmtDate(d) {
        if (!d) return '';
        return d.substring(0, 10);
    }

    function init() {
        container = document.getElementById('appointmentSection');
        if (!container) return;

        empId    = container.dataset.employeeId;
        basePath = container.dataset.basePath || '';
        if (!empId) return;

        positions   = parseJson(container.dataset.positions);
        titles      = parseJson(container.dataset.titles);
        empTypes    = parseJson(container.dataset.empTypes);
        empStatuses = parseJson(container.dataset.empStatuses);
        currentEmployee = parseJson(container.dataset.current) || {};

        formWrap     = document.getElementById('apptFormWrap');
        timeline     = document.getElementById('apptTimeline');
        emptyEl      = document.getElementById('apptEmpty');
        addBtn       = document.getElementById('apptAddBtn');
        filterChipsEl = document.getElementById('apptFilterChips');

        if (addBtn) {
            addBtn.addEventListener('click', function () { showForm(null); });
        }

        loadAppointmentTypes(load);
    }

    function parseJson(s) {
        try { return JSON.parse(s || '{}'); } catch (e) { return {}; }
    }

    function loadAppointmentTypes(cb) {
        fetch(basePath + '/api/employee_appointment.php?action=getAppointmentTypes')
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok && Array.isArray(res.data.types) && res.data.types.length > 0) {
                    APPT_TYPES = res.data.types.filter(function (t) { return t !== '신규입사'; });
                }
                if (cb) cb();
            })
            .catch(function () { if (cb) cb(); });
    }

    function load() {
        fetch(basePath + '/api/employee_appointment.php?action=getAppointments&employee_id=' + empId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) {
                    allItems = res.data.items || [];
                    activeFilter = null;
                    updateMeta(allItems);
                    renderFilterChips(allItems);
                    renderTimeline(allItems);
                }
            });
    }

    function updateMeta(items) {
        var countEl = document.getElementById('apptTotalCount');
        if (countEl) {
            countEl.textContent = items.length > 0 ? '총 ' + items.length + '건' : '';
        }

        var lastDateEl = document.getElementById('apptLastDate');
        if (lastDateEl && items.length > 0) {
            lastDateEl.textContent = fmtDate(items[0].appointment_date);
        }
    }

    function renderFilterChips(items) {
        if (!filterChipsEl) return;
        var typeCounts = {};
        items.forEach(function (item) {
            var t = item.appointment_type;
            typeCounts[t] = (typeCounts[t] || 0) + 1;
        });

        var types = Object.keys(typeCounts);
        if (types.length < 2) {
            filterChipsEl.classList.add('hidden');
            return;
        }

        filterChipsEl.classList.remove('hidden');
        var html = '<button type="button" data-filter="" class="appt-filter-chip active">전체 ' + items.length + '</button>';
        types.forEach(function (t) {
            html += '<button type="button" data-filter="' + esc(t) + '" class="appt-filter-chip">' + esc(t) + ' ' + typeCounts[t] + '</button>';
        });
        filterChipsEl.innerHTML = html;

        filterChipsEl.querySelectorAll('.appt-filter-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var filter = btn.dataset.filter || null;
                activeFilter = filter;
                filterChipsEl.querySelectorAll('.appt-filter-chip').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                var filtered = filter ? allItems.filter(function (i) { return i.appointment_type === filter; }) : allItems;
                renderTimeline(filtered);
            });
        });
    }

    function renderTimeline(items) {
        if (!items.length) {
            timeline.innerHTML = '';
            emptyEl.classList.remove('hidden');
            return;
        }
        emptyEl.classList.add('hidden');
        timeline.innerHTML = items.map(function (item, idx) {
            return renderItem(item, idx === 0 && !activeFilter, idx === items.length - 1);
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function renderItem(item, isLatest, isLast) {
        var style = TYPE_LABELS[item.appointment_type] || { color: 'bg-gray-100 text-gray-700', icon: 'circle', bar: '#9ca3af' };
        var isHire = item.appointment_type === '신규입사';
        var isResign = item.appointment_type === '퇴사';
        var isManual = item.source === 'manual';

        var actions = '';
        if (isManual) {
            actions = '<div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">' +
                '<button type="button" onclick="window.__apptEdit(' + item.id + ')" class="p-1 text-[var(--zm-text-subtle)] hover:text-[var(--zm-primary)]" title="수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>' +
                '<button type="button" onclick="window.__apptDel(' + item.id + ')" class="p-1 text-[var(--zm-text-subtle)] hover:text-rose-500" title="삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>' +
                '</div>';
        }

        var detailParts = [];
        if (isHire) {
            if (item.new_department_name) detailParts.push(esc(item.new_department_name));
            if (item.new_position)        detailParts.push(esc(item.new_position));
            if (item.new_title)           detailParts.push(esc(item.new_title));
            if (item.new_employment_type) detailParts.push(esc(item.new_employment_type));
        } else if (!isResign) {
            var changes = buildChangeSummary(item);
            changes.forEach(function (c) {
                detailParts.push(esc(c.label) + ' ' + esc(c.prev) + ' → ' + esc(c.next));
            });
        }

        var detailHtml = detailParts.length
            ? '<p class="text-xs text-[var(--zm-text-muted)] mt-0.5">' + detailParts.join(' · ') + '</p>'
            : '';

        var reasonHtml = item.reason
            ? '<p class="text-[11px] text-[var(--zm-text-subtle)] mt-0.5">' + esc(item.reason) + '</p>'
            : '';

        var metaParts = [];
        if (item.appointment_no) metaParts.push('#' + esc(item.appointment_no));
        if (isManual) metaParts.push('수동');
        var metaHtml = metaParts.length
            ? ' <span class="text-[11px] text-[var(--zm-text-subtle)]">' + metaParts.join(' · ') + '</span>'
            : '';

        var lineHtml = isLast ? '' : '<div class="absolute left-[5px] top-[16px] bottom-0 w-px bg-[var(--zm-border)]"></div>';
        var dotHtml = isLatest
            ? '<div class="absolute top-[2px] w-3 h-3 rounded-full bg-[var(--zm-primary)] ring-3 ring-[var(--zm-primary-tint-12)] z-10" style="left:-1px"></div>'
            : '<div class="absolute top-[3px] w-[10px] h-[10px] rounded-full border-2 border-[var(--zm-text-subtle)] z-10" style="left:1px;background:var(--zm-surface-1)"></div>';

        return '<div class="relative pb-4 group' + (isLast ? ' pb-0' : '') + '" data-appt-id="' + item.id + '">' +
                dotHtml +
                lineHtml +
                '<div class="ml-6">' +
                    '<div class="flex items-center gap-2 flex-wrap">' +
                        '<span class="text-sm font-medium tabular-nums text-[var(--zm-text-default)]">' + esc(fmtDate(item.appointment_date)) + '</span>' +
                        '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium ' + style.color + '">' +
                            '<i data-lucide="' + style.icon + '" class="w-3 h-3"></i>' +
                            esc(item.appointment_type) +
                        '</span>' +
                        (isLatest ? '<span class="text-[10px] font-semibold text-[var(--zm-primary)] bg-[var(--zm-primary-tint-12)] px-1.5 py-0.5 rounded">현재</span>' : '') +
                        metaHtml +
                        actions +
                    '</div>' +
                    detailHtml +
                    reasonHtml +
                '</div>' +
        '</div>';
    }

    function buildChangeSummary(item) {
        var parts = [];
        var fields = [
            { key: 'department', label: orgDepartmentLabel(), prev: item.prev_department_name, next: item.new_department_name },
            { key: 'position',   label: '직급', prev: item.prev_position,        next: item.new_position },
            { key: 'title',      label: '직책', prev: item.prev_title,           next: item.new_title },
            { key: 'emp_type',   label: '고용형태', prev: item.prev_employment_type,   next: item.new_employment_type },
            { key: 'emp_status', label: '고용상태', prev: item.prev_employment_status, next: item.new_employment_status }
        ];
        fields.forEach(function (f) {
            if (f.prev || f.next) {
                parts.push({ label: f.label, prev: f.prev || '-', next: f.next || '-' });
            }
        });
        return parts;
    }

    // ─── 수동 입력 폼 ───

    function showForm(editItem) {
        loadDepartments(function () {
            renderForm(editItem);
        });
    }

    function loadDepartments(cb) {
        if (departments !== null) return cb();
        fetch(basePath + '/api/organization.php?action=getDepartments')
            .then(function (r) { return r.json(); })
            .then(function (res) {
                departments = (res.data || res.departments || res || []);
                if (Array.isArray(departments)) cb();
                else { departments = []; cb(); }
            })
            .catch(function () { departments = []; cb(); });
    }

    function selectOptions(arr, selected, placeholder) {
        var html = '<option value="">' + esc(placeholder || '선택') + '</option>';
        arr.forEach(function (v) {
            var val = typeof v === 'object' ? (v.name || v) : v;
            var sel = val === selected ? ' selected' : '';
            html += '<option value="' + esc(val) + '"' + sel + '>' + esc(val) + '</option>';
        });
        return html;
    }

    function deptOptions(selected) {
        var html = '<option value="">선택</option>';
        (departments || []).forEach(function (d) {
            var id = d.id || d.department_id;
            var name = d.name || d.department_name || '';
            var sel = String(id) === String(selected) ? ' selected' : '';
            html += '<option value="' + id + '"' + sel + '>' + esc(name) + '</option>';
        });
        return html;
    }

    function getDeptName(id) {
        if (!id) return '-';
        var found = (departments || []).find(function (d) {
            return String(d.id || d.department_id) === String(id);
        });
        return found ? (found.name || found.department_name || '-') : (currentEmployee.department_name || '-');
    }

    function getCurrentVal(field) {
        if (field === 'department_id') return currentEmployee.department_name || '-';
        return currentEmployee[field] || '-';
    }

    function getFieldInput(field, selectedVal) {
        if (field === 'department_id') {
            return '<select id="apptNew_' + field + '" class="reg-select" style="min-height:34px;font-size:14px">' + deptOptions(selectedVal || '') + '</select>';
        }
        var arr = { position: positions, title: titles, employment_type: empTypes, employment_status: empStatuses }[field] || [];
        return '<select id="apptNew_' + field + '" class="reg-select" style="min-height:34px;font-size:14px">' + selectOptions(arr, selectedVal || '', '선택') + '</select>';
    }

    function renderForm(item) {
        var isEdit = item && item.id;
        var selectedType = isEdit ? item.appointment_type : '';

        var html = '<div class="bg-[var(--zm-surface-2)] rounded-lg p-4">' +
            '<div class="grid grid-cols-3 gap-3">' +
                fieldHtml('발령유형', '<select id="apptType" class="reg-select" style="min-height:34px;font-size:13px">' + selectOptions(APPT_TYPES, selectedType, '선택') + '</select>') +
                fieldHtml('발령일', '<input type="date" id="apptDate" class="reg-input-sm" style="font-size:13px" value="' + (isEdit ? fmtDate(item.appointment_date) : new Date().toISOString().substring(0, 10)) + '">') +
                fieldHtml('발령번호', '<input type="text" id="apptNo" class="reg-input-sm" style="font-size:13px" placeholder="선택사항" value="' + esc(isEdit ? item.appointment_no || '' : '') + '">') +
            '</div>' +
            '<div id="apptChangeFields" class="mt-3"></div>' +
            '<div class="mt-3">' +
                '<label class="block text-[11px] font-medium text-[var(--zm-text-subtle)] mb-1">사유</label>' +
                '<textarea id="apptReason" rows="1" class="reg-input-sm" style="min-height:auto;font-size:13px" placeholder="선택 사항">' + esc(isEdit ? item.reason || '' : '') + '</textarea>' +
            '</div>' +
            '<div class="flex items-center gap-2 mt-3 justify-end">' +
                '<button type="button" onclick="window.__apptCancel()" class="px-3 py-1.5 text-xs font-medium text-[var(--zm-text-muted)] hover:text-[var(--zm-text-default)] transition-colors">취소</button>' +
                '<button type="button" onclick="window.__apptSave(' + (isEdit ? item.id : 0) + ')" class="px-3 py-1.5 text-xs font-medium text-white bg-[var(--zm-primary)] rounded-md hover:opacity-90 transition-opacity">저장</button>' +
            '</div>' +
        '</div>';

        formWrap.innerHTML = html;
        formWrap.classList.remove('hidden');
        if (addBtn) addBtn.classList.add('hidden');

        var typeEl = document.getElementById('apptType');
        typeEl.addEventListener('change', function () {
            renderChangeFields(typeEl.value, isEdit ? item : null);
        });
        renderChangeFields(selectedType, isEdit ? item : null);

        if (typeof lucide !== 'undefined') lucide.createIcons();
        typeEl.focus();
    }

    var LEAVE_TYPES = ['육아휴직', '병가', '개인사유', '학업', '기타'];

    function renderChangeFields(type, item) {
        var box = document.getElementById('apptChangeFields');
        if (!box) return;
        var fields = TYPE_FIELDS[type];
        if (!fields || !fields.length) {
            box.innerHTML = '';
            return;
        }
        var isEdit = item && item.id;
        var html = '<div class="border-t border-[var(--zm-border)]/50 pt-3 space-y-2">';
        fields.forEach(function (f) {
            var label = FIELD_LABELS[f] || f;
            if (f === '_destination') {
                html += '<div class="flex items-center gap-2">' +
                    '<span class="text-[11px] text-[var(--zm-text-subtle)] w-14 shrink-0">' + esc(label) + '</span>' +
                    '<div class="flex-1"><input type="text" id="apptDestination" class="reg-input-sm" style="font-size:13px" placeholder="회사명, 사업부, 지역 등" value="' + esc(isEdit && item._destination ? item._destination : '') + '"></div>' +
                '</div>';
                return;
            }
            if (f === '_leave_type') {
                html += '<div class="flex items-center gap-2">' +
                    '<span class="text-[11px] text-[var(--zm-text-subtle)] w-14 shrink-0">' + esc(label) + '</span>' +
                    '<div class="flex-1"><select id="apptLeaveType" class="reg-select" style="min-height:34px;font-size:13px">' + selectOptions(LEAVE_TYPES, isEdit && item._leave_type ? item._leave_type : '', '선택') + '</select></div>' +
                '</div>';
                return;
            }
            var curVal = getCurrentVal(f);
            var editVal = '';
            if (isEdit) {
                if (f === 'department_id') editVal = item.new_department_id || '';
                else editVal = item['new_' + f] || '';
            }
            html += '<div class="flex items-center gap-2">' +
                '<span class="text-[11px] text-[var(--zm-text-subtle)] w-14 shrink-0">' + esc(label) + '</span>' +
                '<span class="text-xs text-[var(--zm-text-muted)] truncate" title="' + esc(curVal) + '">' + esc(curVal) + '</span>' +
                '<i data-lucide="chevron-right" class="w-3 h-3 text-[var(--zm-text-subtle)] shrink-0"></i>' +
                '<div class="flex-1">' + getFieldInput(f, editVal) + '</div>' +
            '</div>';
        });
        html += '</div>';
        box.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function fieldHtml(label, input, cls) {
        return '<div class="' + (cls || '') + '">' +
            '<label class="block text-[11px] font-medium text-[var(--zm-text-subtle)] mb-1">' + esc(label) + '</label>' +
            input +
        '</div>';
    }

    function hideForm() {
        formWrap.innerHTML = '';
        formWrap.classList.add('hidden');
        if (addBtn) addBtn.classList.remove('hidden');
    }

    function saveAppt(editId) {
        var type = document.getElementById('apptType').value;
        if (!type) { alert('발령유형을 선택해주세요.'); return; }
        var date = document.getElementById('apptDate').value;
        if (!date) { alert('발령일을 입력해주세요.'); return; }

        var data = {
            employee_id: parseInt(empId),
            appointment_type: type,
            appointment_date: date,
            appointment_no: document.getElementById('apptNo').value || null,
            reason: null,
            prev_department_id: null, new_department_id: null,
            prev_position: null, new_position: null,
            prev_title: null, new_title: null,
            prev_employment_type: null, new_employment_type: null,
            prev_employment_status: null, new_employment_status: null
        };

        var reasonParts = [];
        var destEl = document.getElementById('apptDestination');
        if (destEl && destEl.value.trim()) {
            reasonParts.push('[' + (type === '파견' ? '파견처' : '전출처') + '] ' + destEl.value.trim());
        }
        var leaveEl = document.getElementById('apptLeaveType');
        if (leaveEl && leaveEl.value) {
            reasonParts.push('[휴직유형] ' + leaveEl.value);
        }
        var userReason = document.getElementById('apptReason').value || '';
        if (userReason.trim()) reasonParts.push(userReason.trim());
        data.reason = reasonParts.length ? reasonParts.join(' / ') : null;

        if (type === '휴직') {
            data.prev_employment_status = currentEmployee.employment_status || '재직';
            data.new_employment_status = '휴직';
        } else if (type === '퇴사') {
            data.prev_employment_status = currentEmployee.employment_status || '재직';
            data.new_employment_status = '퇴사';
        }

        var fields = TYPE_FIELDS[type] || [];
        fields.forEach(function (f) {
            if (f.charAt(0) === '_') return;
            var el = document.getElementById('apptNew_' + f);
            if (!el) return;
            var newVal = el.value || null;
            if (f === 'department_id') {
                data.prev_department_id = currentEmployee.department_id || null;
                data.new_department_id = newVal;
            } else {
                data['prev_' + f] = currentEmployee[f] || null;
                data['new_' + f] = newVal;
            }
        });

        if (editId) data.id = editId;

        fetch(basePath + '/api/employee_appointment.php?action=saveAppointment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.ok) { hideForm(); load(); }
            else alert(res.error ? res.error.message : '저장 실패');
        });
    }

    function editAppt(id) {
        fetch(basePath + '/api/employee_appointment.php?action=getAppointments&employee_id=' + empId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) return;
                var item = (res.data.items || []).find(function (i) { return parseInt(i.id) === id; });
                if (item) showForm(item);
            });
    }

    async function deleteAppt(id) {
        if (!(await AppUI.confirm('이 발령 이력을 삭제하시겠습니까?'))) return;
        fetch(basePath + '/api/employee_appointment.php?action=deleteAppointment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.ok) load();
            else alert(res.error ? res.error.message : '삭제 실패');
        });
    }

    window.__apptSave   = saveAppt;
    window.__apptCancel = hideForm;
    window.__apptEdit   = editAppt;
    window.__apptDel    = deleteAppt;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
