/**
 * labor-annual.js — 연차관리 필터 + 연차 CRUD
 * LABOR_DATA.anLabels, .anOpts, .leaveApi, .leaveYear, .leaveTypes,
 * .deductTypes, .leaveTypeMeta, .leaveEmployees, .resignedEmployees
 */

// ===== 연차관리 필터 =====
const AN = { org:'', dept:'', remain:'' };
const anLabels = LABOR_DATA.anLabels;
const anOpts = LABOR_DATA.anOpts;
(function(){
    let curDrop=null;
    const dropEl=document.getElementById('anDrop');
    const pillRow=document.getElementById('anPillRow');
    if(!dropEl||!pillRow) return;
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function openDrop(key){
        if(curDrop===key){closeDrop();return;} closeDrop();
        const pill=document.querySelector('[data-an="'+key+'"]');if(!pill)return;
        const pr=pillRow.getBoundingClientRect(),r=pill.getBoundingClientRect();
        dropEl.style.left=(r.left-pr.left)+'px'; dropEl.style.top=(r.bottom-pr.top+6)+'px';
        let h='<div class="rf-drop__item'+(!AN[key]?' rf-drop__item--active':'')+'" data-av="" data-ak="'+key+'">전체</div>';
        (anOpts[key]||[]).forEach(o=>{h+='<div class="rf-drop__item'+(AN[key]===o?' rf-drop__item--active':'')+'" data-av="'+esc(o)+'" data-ak="'+key+'">'+esc(o)+'</div>';});
        dropEl.innerHTML=h; dropEl.classList.remove('hidden'); curDrop=key;
        pill.classList.add('rf-pill--active');
        dropEl.querySelectorAll('[data-av]').forEach(el=>el.addEventListener('click',()=>{
            AN[el.dataset.ak]=el.dataset.av; applyAnnualFilters(); renderAnChips(); renderAnPills(); closeDrop();
        }));
    }
    function closeDrop(){dropEl.classList.add('hidden');dropEl.innerHTML='';if(curDrop){const p=document.querySelector('[data-an="'+curDrop+'"]');if(p&&!AN[curDrop])p.classList.remove('rf-pill--active');}curDrop=null;}
    function renderAnPills(){document.querySelectorAll('[data-an]').forEach(p=>{const k=p.dataset.an,t=p.querySelector('.rf-pill__text');p.classList.toggle('rf-pill--active',!!AN[k]);if(t)t.textContent=AN[k]||anLabels[k];});}
    function renderAnChips(){
        const bar=document.getElementById('anChipBar'),ct=document.getElementById('anChips');
        const xSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        const chips=[];
        ['org','dept','remain'].forEach(k=>{if(AN[k])chips.push('<span class="rf-chip">'+esc(anLabels[k])+': '+esc(AN[k])+'<span class="rf-chip__x" data-ac="'+k+'">'+xSvg+'</span></span>');});
        if(chips.length){ct.innerHTML=chips.join('');bar.classList.remove('hidden');}else{ct.innerHTML='';bar.classList.add('hidden');}
        ct.querySelectorAll('[data-ac]').forEach(x=>x.addEventListener('click',()=>{AN[x.dataset.ac]='';applyAnnualFilters();renderAnChips();renderAnPills();}));
    }
    window.applyAnnualFilters = function(){
        const search=(document.getElementById('annualSearch')?.value||'').trim().toLowerCase();
        document.querySelectorAll('#annualTbl tbody tr').forEach(r=>{
            if(!r.dataset.name) return;
            let show=true;
            if(search&&!r.dataset.name.toLowerCase().includes(search)) show=false;
            if(AN.org&&r.dataset.org!==AN.org) show=false;
            if(AN.dept&&r.dataset.dept!==AN.dept) show=false;
            if(AN.remain){
                const rem=parseInt(r.dataset.remain)||0;
                if(AN.remain==='3일 이하'&&rem>3) show=false;
                else if(AN.remain==='5일 이하'&&rem>5) show=false;
                else if(AN.remain==='10일 이하'&&rem>10) show=false;
                else if(AN.remain==='10일 초과'&&rem<=10) show=false;
            }
            r.style.display=show?'':'none';
        });
    };
    window.resetAnnualFilters = function(){
        document.getElementById('annualSearch').value='';
        Object.keys(AN).forEach(k=>AN[k]='');
        applyAnnualFilters(); renderAnChips(); renderAnPills();
    };
    document.querySelectorAll('[data-an]').forEach(p=>p.addEventListener('click',e=>{e.stopPropagation();openDrop(p.dataset.an);}));
    document.addEventListener('click',e=>{if(curDrop&&!dropEl.contains(e.target))closeDrop();});
})();

// ===== 연차관리 =====
const LEAVE_API = LABOR_DATA.leaveApi;
const LEAVE_YEAR = LABOR_DATA.leaveYear;
const LEAVE_TYPES = LABOR_DATA.leaveTypes;
const DEDUCT_TYPES = LABOR_DATA.deductTypes;
const LEAVE_TYPE_META = LABOR_DATA.leaveTypeMeta;
const LEAVE_EMPLOYEES = LABOR_DATA.leaveEmployees;
const RESIGNED_EMPLOYEES = LABOR_DATA.resignedEmployees;

let _selectedEmp = null;

function openLeaveModal() {
    document.getElementById('leaveModal').classList.remove('hidden');
    clearEmpSelection();
    updateLeavePreview();
    document.getElementById('empDropdown')?.classList.add('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeLeaveModal() {
    document.getElementById('leaveModal').classList.add('hidden');
    _selectedEmp = null;
}

// -- 직원 검색 Combobox --
(function() {
    const inp = document.getElementById('empSearchInput');
    const dd = document.getElementById('empDropdown');
    if (!inp || !dd) return;
    let hlIdx = -1;

    function renderList(q) {
        const lower = (q || '').toLowerCase().trim();
        const filtered = lower
            ? LEAVE_EMPLOYEES.filter(e => e.name.toLowerCase().includes(lower) || (e.dept || '').toLowerCase().includes(lower))
            : LEAVE_EMPLOYEES;
        hlIdx = -1;
        if (!filtered.length) {
            dd.innerHTML = '<li class="px-3 py-2 text-sm text-gray-400">검색 결과 없음</li>';
            dd.classList.remove('hidden');
            return;
        }
        dd.innerHTML = filtered.map((e, i) => {
            const remColor = e.remaining <= 2 ? 'text-red-500' : e.remaining <= 5 ? 'text-amber-500' : 'text-emerald-600';
            return `<li data-idx="${i}" data-id="${e.id}" class="px-3 py-2 cursor-pointer hover:bg-gray-50 flex items-center justify-between">`
                + `<span class="text-sm"><span class="font-medium text-gray-900">${escHtml(e.name)}</span>`
                + (e.dept ? ` <span class="text-gray-400 text-xs">${escHtml(e.dept)}</span>` : '') + `</span>`
                + `<span class="text-xs ${remColor} font-medium">${e.remaining}일</span></li>`;
        }).join('');
        dd.classList.remove('hidden');
        dd._filtered = filtered;
    }

    inp.addEventListener('focus', () => renderList(inp.value));
    inp.addEventListener('input', () => renderList(inp.value));

    inp.addEventListener('keydown', (ev) => {
        const items = dd.querySelectorAll('li[data-id]');
        if (!items.length) return;
        if (ev.key === 'ArrowDown') { ev.preventDefault(); hlIdx = Math.min(hlIdx + 1, items.length - 1); highlightItem(items); }
        else if (ev.key === 'ArrowUp') { ev.preventDefault(); hlIdx = Math.max(hlIdx - 1, 0); highlightItem(items); }
        else if (ev.key === 'Enter' && hlIdx >= 0) { ev.preventDefault(); selectEmp(dd._filtered[hlIdx]); }
        else if (ev.key === 'Escape') { dd.classList.add('hidden'); }
    });

    function highlightItem(items) {
        items.forEach((li, i) => li.classList.toggle('bg-primary/10', i === hlIdx));
        if (items[hlIdx]) items[hlIdx].scrollIntoView({ block: 'nearest' });
    }

    dd.addEventListener('click', (ev) => {
        const li = ev.target.closest('li[data-id]');
        if (!li || !dd._filtered) return;
        const idx = parseInt(li.dataset.idx);
        selectEmp(dd._filtered[idx]);
    });

    document.addEventListener('click', (ev) => {
        if (!ev.target.closest('#empComboWrap')) dd.classList.add('hidden');
    });
})();

function selectEmp(emp) {
    _selectedEmp = emp;
    document.getElementById('leaveEmployee').value = emp.id;
    document.getElementById('empSearchInput').value = emp.name;
    document.getElementById('empDropdown').classList.add('hidden');

    const card = document.getElementById('empCard');
    card.classList.remove('hidden');
    document.getElementById('empCardName').textContent = emp.name;
    document.getElementById('empCardDept').textContent = emp.dept ? emp.dept + (emp.rank ? ' ' + emp.rank : '') : '';

    const pct = emp.total > 0 ? Math.round((emp.used / emp.total) * 100) : 0;
    const bar = document.getElementById('empCardBar');
    bar.style.width = pct + '%';
    bar.className = 'h-1.5 rounded-full transition-all ' + (pct >= 80 ? 'bg-red-400' : pct >= 50 ? 'bg-amber-400' : 'bg-emerald-400');

    const remEl = document.getElementById('empCardRemain');
    const remColor = emp.remaining <= 2 ? 'text-red-600' : emp.remaining <= 5 ? 'text-amber-600' : 'text-emerald-600';
    remEl.textContent = '잔여 ' + emp.remaining + '일 / ' + emp.total + '일';
    remEl.className = 'text-xs font-medium whitespace-nowrap ' + remColor;

    updateLeavePreview();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function clearEmpSelection() {
    _selectedEmp = null;
    document.getElementById('leaveEmployee').value = '';
    document.getElementById('empSearchInput').value = '';
    document.getElementById('empCard').classList.add('hidden');
    updateLeavePreview();
}

// -- 휴가 유형 커스텀 드롭다운 --
let _selectedLt = LEAVE_TYPE_META[0] || null;
(function() {
    const trigger = document.getElementById('ltTrigger');
    const dd = document.getElementById('ltDropdown');
    if (!trigger || !dd) return;

    trigger.addEventListener('click', () => dd.classList.toggle('hidden'));

    dd.querySelectorAll('.lt-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const code = btn.dataset.code;
            const meta = LEAVE_TYPE_META.find(m => m.code === code);
            if (!meta) return;
            _selectedLt = meta;
            document.getElementById('leaveType').value = code;
            document.getElementById('ltLabel').textContent = meta.name;
            document.getElementById('ltDot').className = 'w-2 h-2 rounded-full ' + meta.color;
            const badge = document.getElementById('ltBadge');
            if (meta.deduct) {
                badge.textContent = meta.half ? '0.5일' : '1일';
                badge.className = 'text-xs px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-medium';
            } else {
                badge.textContent = '차감 안 함';
                badge.className = 'text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-medium';
            }
            dd.classList.add('hidden');
            updateLeavePreview();
        });
    });

    document.addEventListener('click', (ev) => {
        if (!ev.target.closest('#ltComboWrap')) dd.classList.add('hidden');
    });
})();

function getSelectedLeaveType() {
    return _selectedLt || LEAVE_TYPE_META[0] || { code:'AL', half:false, deduct:true };
}

function updateLeavePreview() {
    const lt = getSelectedLeaveType();
    const remaining = _selectedEmp ? _selectedEmp.remaining : 0;
    const endWrap = document.getElementById('leaveEndWrap');

    let daysUsed = 1;
    if (lt.half) {
        daysUsed = 0.5;
        endWrap.style.display = 'none';
    } else {
        endWrap.style.display = '';
        if (lt.deduct) {
            const s = document.getElementById('leaveStart').value;
            const e = document.getElementById('leaveEnd').value;
            if (s && e) {
                const diff = Math.ceil((new Date(e) - new Date(s)) / 86400000) + 1;
                daysUsed = Math.max(1, diff);
            }
        }
    }
    document.getElementById('previewDays').textContent = daysUsed + '일' + (lt.deduct ? '' : ' (차감 안 함)');
    const newRemaining = lt.deduct ? remaining - daysUsed : remaining;
    const remEl = document.getElementById('previewRemaining');
    remEl.textContent = (_selectedEmp ? newRemaining : '-') + '일';
    remEl.className = 'font-bold ' + (newRemaining <= 3 ? 'text-red-600' : newRemaining <= 5 ? 'text-amber-600' : 'text-emerald-600');
}

async function submitLeave() {
    const empId = document.getElementById('leaveEmployee').value;
    if (!empId || !_selectedEmp) { showToast('직원을 선택해주세요.', 'error'); return; }
    const lt = getSelectedLeaveType();
    const type = lt.code;
    const start = document.getElementById('leaveStart').value;
    const end = lt.half ? start : document.getElementById('leaveEnd').value;
    const reason = document.getElementById('leaveReason').value;

    if (!start) { showToast('날짜를 선택해주세요.', 'error'); return; }
    if (lt.deduct && !lt.half && end < start) { showToast('종료일이 시작일보다 빠릅니다.', 'error'); return; }

    try {
        const res = await fetch(LEAVE_API + '?action=applyLeave', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({employee_id: parseInt(empId), leave_type: type, start_date: start, end_date: end, reason})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || '연차 등록 완료', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.error || '등록 실패', 'error');
        }
    } catch (e) {
        showToast('서버 오류가 발생했습니다.', 'error');
    }
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

let _currentHistoryEmp = null;

async function showHistory(empId, empName) {
    _currentHistoryEmp = { id: empId, name: empName };
    document.getElementById('historyName').textContent = empName;
    document.getElementById('historyContent').innerHTML = '<p class="text-slate-500 text-center py-8">불러오는 중...</p>';
    document.getElementById('historyModal').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
        const res = await fetch(LEAVE_API + '?action=getHistory&employee_id=' + empId + '&year=' + LEAVE_YEAR);
        const data = await res.json();
        const list = data.history || [];
        if (list.length === 0) {
            document.getElementById('historyContent').innerHTML = '<p class="text-slate-500 text-center py-8">사용 내역이 없습니다.</p>';
            return;
        }
        let html = '<table class="w-full text-sm"><thead><tr class="border-b-2 border-slate-800">'
            + '<th class="py-2 px-4 text-left text-slate-300">사용일</th>'
            + '<th class="py-2 px-4 text-center text-slate-300">유형</th>'
            + '<th class="py-2 px-4 text-center text-slate-300">일수</th>'
            + '<th class="py-2 px-4 text-center text-slate-300">상태</th>'
            + '<th class="py-2 px-4 text-left text-slate-300">신청 일시</th>'
            + '<th class="py-2 px-4 text-left text-slate-300">승인 일시</th>'
            + '<th class="py-2 px-4 text-center text-slate-300">작업</th>'
            + '</tr></thead><tbody>';
        list.forEach(r => {
            const statusCls = r.status === '승인' ? 'text-emerald-400'
                            : r.status === '반려' ? 'text-rose-400'
                            : r.status === '취소' ? 'text-slate-500 line-through'
                            : 'text-amber-400';
            const dateStr = r.start_date === r.end_date ? r.start_date : r.start_date + ' ~ ' + r.end_date;
            const penaltyBadge = r.penalty_flag == 1
                ? ' <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/40" title="'
                  + escHtml(r.penalty_reason || '페널티') + '">⚠ ' + escHtml(r.penalty_reason || '페널티') + '</span>'
                : '';
            const approvedCell = r.approved_at
                ? fmtDt(r.approved_at) + (r.approver_name ? '<span class="text-slate-500"> · ' + escHtml(r.approver_name) + '</span>' : '')
                : (r.status === '대기' ? '<span class="text-slate-500">대기 중</span>' : '<span class="text-slate-600">-</span>');

            let actions = '';
            if (r.status === '대기') {
                actions = '<button onclick="approveLeaveReq(' + r.id + ')" class="text-xs text-emerald-400 hover:underline mr-2">승인</button>'
                        + '<button onclick="rejectLeaveReq(' + r.id + ')" class="text-xs text-rose-400 hover:underline">반려</button>';
            } else if (r.status === '승인') {
                actions = '<button onclick="cancelLeaveReq(' + r.id + ')" class="text-xs text-slate-400 hover:underline">취소</button>';
            }

            html += '<tr class="border-b border-slate-800">'
                + '<td class="py-2 px-4 text-slate-100">' + escHtml(dateStr) + penaltyBadge + '</td>'
                + '<td class="py-2 px-4 text-center text-slate-200">' + escHtml(LEAVE_TYPES[r.leave_type] || r.leave_type) + '</td>'
                + '<td class="py-2 px-4 text-center text-slate-200 tabular-nums">' + r.days_used + '일</td>'
                + '<td class="py-2 px-4 text-center ' + statusCls + '">' + escHtml(r.status) + '</td>'
                + '<td class="py-2 px-4 text-slate-400 tabular-nums">' + escHtml(fmtDt(r.created_at)) + '</td>'
                + '<td class="py-2 px-4 text-slate-400 tabular-nums">' + approvedCell + '</td>'
                + '<td class="py-2 px-4 text-center">' + actions + '</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        if (list[0]?.reason) html += '<p class="text-sm text-slate-500 mt-2">최근 사유: ' + escHtml(list[0].reason) + '</p>';
        document.getElementById('historyContent').innerHTML = html;
    } catch (e) {
        document.getElementById('historyContent').innerHTML = '<p class="text-amber-500 text-center py-8">데이터를 불러올 수 없습니다.</p>';
    }
}

async function approveLeaveReq(leaveId) {
    if (!(await AppUI.confirm('이 휴가 신청을 승인하시겠습니까?'))) return;
    try {
        const res = await fetch(LEAVE_API + '?action=approveLeave', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({leave_id: leaveId})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || '승인 완료', 'success');
            if (_currentHistoryEmp) showHistory(_currentHistoryEmp.id, _currentHistoryEmp.name);
        } else {
            showToast(data.error || '승인 실패', 'error');
        }
    } catch (e) {
        showToast('서버 오류가 발생했습니다.', 'error');
    }
}

async function rejectLeaveReq(leaveId) {
    if (!(await AppUI.confirm('이 휴가 신청을 반려하시겠습니까?'))) return;
    try {
        const res = await fetch(LEAVE_API + '?action=rejectLeave', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({leave_id: leaveId})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || '반려 완료', 'success');
            if (_currentHistoryEmp) showHistory(_currentHistoryEmp.id, _currentHistoryEmp.name);
        } else {
            showToast(data.error || '반려 실패', 'error');
        }
    } catch (e) {
        showToast('서버 오류가 발생했습니다.', 'error');
    }
}

async function cancelLeaveReq(leaveId) {
    if (!(await AppUI.confirm('이 연차를 취소하시겠습니까?'))) return;
    try {
        const res = await fetch(LEAVE_API + '?action=cancelLeave', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({leave_id: leaveId})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || '취소 완료', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.error || '취소 실패', 'error');
        }
    } catch (e) {
        showToast('서버 오류가 발생했습니다.', 'error');
    }
}

/* ── 연차 조정 모달 ── */
let _adjEmp = null;

function openAdjustModal() {
    clearAdjEmp();
    document.getElementById('adjDays').value = '1';
    document.getElementById('adjReason').value = '';
    document.getElementById('adjReasonLen').textContent = '0';
    document.querySelector('input[name="adjType"][value="add"]').checked = true;
    document.querySelector('input[name="adjCat"][value="기타"]').checked = true;
    document.getElementById('adjPreview').classList.add('hidden');
    document.getElementById('adjustModal').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function openAdjustFor(empId, name, dept, total, used, remaining) {
    openAdjustModal();
    _adjEmp = { id: empId, name, dept, total, used, remaining };
    document.getElementById('adjEmployee').value = empId;
    document.getElementById('adjEmpSearch').value = name;
    document.getElementById('adjEmpSearch').classList.add('hidden');
    document.getElementById('adjEmpName').textContent = name;
    document.getElementById('adjEmpDept').textContent = dept || '-';
    document.getElementById('adjEmpRemain').textContent = remaining;
    document.getElementById('adjEmpTotal').textContent = total;
    document.getElementById('adjEmpUsed').textContent = used;
    document.getElementById('adjEmpCard').classList.remove('hidden');
    updateAdjPreview();
}

function closeAdjustModal() {
    document.getElementById('adjustModal').classList.add('hidden');
}

function clearAdjEmp() {
    _adjEmp = null;
    document.getElementById('adjEmployee').value = '';
    document.getElementById('adjEmpSearch').value = '';
    document.getElementById('adjEmpSearch').classList.remove('hidden');
    document.getElementById('adjEmpCard').classList.add('hidden');
    document.getElementById('adjPreview').classList.add('hidden');
}

function adjDaysDelta(d) {
    const inp = document.getElementById('adjDays');
    let v = parseFloat(inp.value) || 1;
    v = Math.max(0.5, Math.min(30, v + d));
    inp.value = v;
    updateAdjPreview();
}

function updateAdjPreview() {
    if (!_adjEmp) { document.getElementById('adjPreview').classList.add('hidden'); return; }
    const days = parseFloat(document.getElementById('adjDays').value) || 0;
    const isAdd = document.querySelector('input[name="adjType"]:checked')?.value === 'add';
    const before = _adjEmp.total;
    const after = isAdd ? before + days : before - days;
    const newRemain = after - _adjEmp.used;

    document.getElementById('adjPrevBefore').textContent = before;
    document.getElementById('adjPrevAfter').textContent = after;
    const delta = document.getElementById('adjPrevDelta');
    delta.textContent = (isAdd ? '+' : '-') + days + '일';
    delta.className = 'text-xs px-1.5 py-0.5 rounded font-medium '
        + (isAdd ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600');
    document.getElementById('adjPreview').classList.remove('hidden');
}

// 조정 모달 직원 검색 combobox
(function() {
    const input = document.getElementById('adjEmpSearch');
    const dropdown = document.getElementById('adjEmpDropdown');
    if (!input || !dropdown) return;

    let debounce;
    input.addEventListener('input', function() {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            const q = this.value.trim().toLowerCase();
            if (q.length < 1) { dropdown.classList.add('hidden'); return; }
            const matches = LEAVE_EMPLOYEES.filter(e =>
                e.name.toLowerCase().includes(q) || (e.dept || '').toLowerCase().includes(q)
            );
            if (matches.length === 0) {
                dropdown.innerHTML = '<li class="px-3 py-2 text-gray-400 text-sm">결과 없음</li>';
            } else {
                dropdown.innerHTML = matches.map(e =>
                    '<li class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between" data-id="' + e.id + '">'
                    + '<span class="font-medium text-gray-900">' + escHtml(e.name) + '</span>'
                    + '<span class="text-xs text-gray-500">' + escHtml(e.dept || '') + (e.rank ? ' · ' + escHtml(e.rank) : '') + '</span>'
                    + '</li>'
                ).join('');
            }
            dropdown.classList.remove('hidden');
        }, 150);
    });

    dropdown.addEventListener('click', function(ev) {
        const li = ev.target.closest('li[data-id]');
        if (!li) return;
        const emp = LEAVE_EMPLOYEES.find(e => e.id == li.dataset.id);
        if (!emp) return;
        _adjEmp = { id: emp.id, name: emp.name, dept: emp.dept || '', total: emp.total, used: emp.used, remaining: emp.remaining };
        document.getElementById('adjEmployee').value = emp.id;
        input.classList.add('hidden');
        dropdown.classList.add('hidden');
        document.getElementById('adjEmpName').textContent = emp.name;
        document.getElementById('adjEmpDept').textContent = emp.dept || '-';
        document.getElementById('adjEmpRemain').textContent = emp.remaining;
        document.getElementById('adjEmpTotal').textContent = emp.total;
        document.getElementById('adjEmpUsed').textContent = emp.used;
        document.getElementById('adjEmpCard').classList.remove('hidden');
        updateAdjPreview();
    });

    document.addEventListener('click', function(ev) {
        if (!input.contains(ev.target) && !dropdown.contains(ev.target)) dropdown.classList.add('hidden');
    });
})();

// 이월 모달 직원 검색 combobox
(function() {
    const input = document.getElementById('coEmpSearch');
    const dropdown = document.getElementById('coEmpDropdown');
    if (!input || !dropdown) return;

    let debounce;
    input.addEventListener('input', function() {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            const q = this.value.trim().toLowerCase();
            if (q.length < 1) { dropdown.classList.add('hidden'); return; }
            const matches = LEAVE_EMPLOYEES.filter(e =>
                e.name.toLowerCase().includes(q) || (e.dept || '').toLowerCase().includes(q)
            );
            if (matches.length === 0) {
                dropdown.innerHTML = '<li class="px-3 py-2 text-gray-400 text-sm">결과 없음</li>';
            } else {
                dropdown.innerHTML = matches.map(e =>
                    '<li class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between" data-id="' + e.id + '">'
                    + '<span class="font-medium text-gray-900">' + escHtml(e.name) + '</span>'
                    + '<span class="text-xs text-gray-500">' + escHtml(e.dept || '') + ' · 잔여 ' + e.remaining + '일</span>'
                    + '</li>'
                ).join('');
            }
            dropdown.classList.remove('hidden');
        }, 150);
    });

    dropdown.addEventListener('click', function(ev) {
        const li = ev.target.closest('li[data-id]');
        if (!li) return;
        const emp = LEAVE_EMPLOYEES.find(e => e.id == li.dataset.id);
        if (!emp) return;
        document.getElementById('coEmployee').value = emp.id;
        input.classList.add('hidden');
        dropdown.classList.add('hidden');
        document.getElementById('coEmpName').textContent = emp.name;
        document.getElementById('coEmpDept').textContent = emp.dept || '-';
        document.getElementById('coEmpRemain').textContent = emp.remaining;
        document.getElementById('coEmpTotal').textContent = emp.total;
        document.getElementById('coEmpUsed').textContent = emp.used;
        document.getElementById('coEmpCard').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    document.addEventListener('click', function(ev) {
        if (!input.contains(ev.target) && !dropdown.contains(ev.target)) dropdown.classList.add('hidden');
    });
})();

window.clearCoEmp = function() {
    document.getElementById('coEmployee').value = '';
    const search = document.getElementById('coEmpSearch');
    search.value = '';
    search.classList.remove('hidden');
    document.getElementById('coEmpCard').classList.add('hidden');
};

// 퇴사 정산 모달 직원 검색 combobox
(function() {
    const input = document.getElementById('stEmpSearch');
    const dropdown = document.getElementById('stEmpDropdown');
    if (!input || !dropdown) return;

    let debounce;
    input.addEventListener('input', function() {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            const q = this.value.trim().toLowerCase();
            if (q.length < 1) { dropdown.classList.add('hidden'); return; }
            const matches = RESIGNED_EMPLOYEES.filter(e =>
                e.name.toLowerCase().includes(q)
            );
            if (matches.length === 0) {
                dropdown.innerHTML = '<li class="px-3 py-2 text-gray-400 text-sm">결과 없음</li>';
            } else {
                dropdown.innerHTML = matches.map(e =>
                    '<li class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between" data-id="' + e.id + '">'
                    + '<span class="font-medium text-gray-900">' + escHtml(e.name) + '</span>'
                    + '<span class="text-xs text-red-500">퇴사일 ' + (e.resign_date || '-') + '</span>'
                    + '</li>'
                ).join('');
            }
            dropdown.classList.remove('hidden');
        }, 150);
    });

    dropdown.addEventListener('click', function(ev) {
        const li = ev.target.closest('li[data-id]');
        if (!li) return;
        const emp = RESIGNED_EMPLOYEES.find(e => e.id == li.dataset.id);
        if (!emp) return;
        document.getElementById('stEmployee').value = emp.id;
        input.classList.add('hidden');
        dropdown.classList.add('hidden');
        document.getElementById('stEmpName').textContent = emp.name;
        document.getElementById('stEmpDate').textContent = '퇴사일 ' + (emp.resign_date || '-');
        document.getElementById('stEmpCard').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    document.addEventListener('click', function(ev) {
        if (!input.contains(ev.target) && !dropdown.contains(ev.target)) dropdown.classList.add('hidden');
    });
})();

window.clearStEmp = function() {
    document.getElementById('stEmployee').value = '';
    const search = document.getElementById('stEmpSearch');
    search.value = '';
    search.classList.remove('hidden');
    document.getElementById('stEmpCard').classList.add('hidden');
};

// 사유 글자수 카운트 + 미리보기 업데이트 이벤트
document.getElementById('adjReason')?.addEventListener('input', function() {
    document.getElementById('adjReasonLen').textContent = this.value.length;
});
document.getElementById('adjDays')?.addEventListener('input', updateAdjPreview);
document.querySelectorAll('input[name="adjType"]').forEach(r => r.addEventListener('change', updateAdjPreview));

async function submitAdjust() {
    if (!_adjEmp) { showToast('직원을 선택해주세요.', 'error'); return; }
    const days = parseFloat(document.getElementById('adjDays').value);
    if (!days || days <= 0) { showToast('조정일수를 입력해주세요.', 'error'); return; }
    const reason = document.getElementById('adjReason').value.trim();
    if (!reason) { showToast('조정 사유를 입력해주세요.', 'error'); return; }
    const adjustType = document.querySelector('input[name="adjType"]:checked')?.value;
    const category = document.querySelector('input[name="adjCat"]:checked')?.value || '기타';

    const label = adjustType === 'add' ? '추가' : '차감';
    if (!(await AppUI.confirm(_adjEmp.name + '의 연차를 ' + days + '일 ' + label + '하시겠습니까?\n사유: ' + reason))) return;

    const btn = document.getElementById('adjSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full"></span> 처리 중...';

    try {
        const res = await fetch(LEAVE_API + '?action=adjustLeave', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                employee_id: _adjEmp.id,
                year: LEAVE_YEAR,
                adjust_type: adjustType,
                adjust_days: days,
                reason: reason,
                category: category
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || '조정 완료', 'success');
            closeAdjustModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.error || '조정 실패', 'error');
        }
    } catch (e) {
        showToast('서버 오류가 발생했습니다.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> 적용';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

async function showAdjHistory(empId, empName) {
    document.getElementById('adjHistName').textContent = empName;
    document.getElementById('adjHistContent').innerHTML = '<p class="text-gray-500 text-center py-8">불러오는 중...</p>';
    document.getElementById('adjHistoryModal').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
        const res = await fetch(LEAVE_API + '?action=getAdjustments&employee_id=' + empId + '&year=' + LEAVE_YEAR);
        const data = await res.json();
        const list = data.adjustments || [];
        if (list.length === 0) {
            document.getElementById('adjHistContent').innerHTML = '<p class="text-gray-500 text-center py-8">조정 이력이 없습니다.</p>';
            return;
        }
        let html = '<table class="w-full text-sm"><thead><tr class="border-b-2 border-gray-200">'
            + '<th class="py-2 px-3 text-left text-gray-600">일시</th>'
            + '<th class="py-2 px-3 text-center text-gray-600">유형</th>'
            + '<th class="py-2 px-3 text-center text-gray-600">일수</th>'
            + '<th class="py-2 px-3 text-center text-gray-600">분류</th>'
            + '<th class="py-2 px-3 text-left text-gray-600">사유</th>'
            + '<th class="py-2 px-3 text-left text-gray-600">등록자</th>'
            + '</tr></thead><tbody>';
        list.forEach(r => {
            const typeCls = r.adjust_type === 'add' ? 'text-blue-600' : 'text-red-600';
            const typeLabel = r.adjust_type === 'add' ? '+추가' : '-차감';
            html += '<tr class="border-b border-gray-100">'
                + '<td class="py-2 px-3 text-gray-500 tabular-nums">' + escHtml(fmtDt(r.created_at)) + '</td>'
                + '<td class="py-2 px-3 text-center font-medium ' + typeCls + '">' + typeLabel + '</td>'
                + '<td class="py-2 px-3 text-center tabular-nums">' + r.adjust_days + '일</td>'
                + '<td class="py-2 px-3 text-center"><span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">' + escHtml(r.category || '기타') + '</span></td>'
                + '<td class="py-2 px-3 text-gray-700">' + escHtml(r.reason) + '</td>'
                + '<td class="py-2 px-3 text-gray-500">' + escHtml(r.created_by_name || '-') + '</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        document.getElementById('adjHistContent').innerHTML = html;
    } catch (e) {
        document.getElementById('adjHistContent').innerHTML = '<p class="text-red-500 text-center py-8">데이터를 불러올 수 없습니다.</p>';
    }
}
