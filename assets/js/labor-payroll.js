/**
 * labor-payroll.js — 임금대장 모달, CRUD, 급여 설정
 * LABOR_DATA.basePath, .payYear, .payMonth, .payrollStatus, .payTypes, .pyOpts, .pyOrgLabel
 */

const PAYSLIP_API = LABOR_DATA.basePath + '/api/payslip.php';
const PAY_YEAR = LABOR_DATA.payYear;
const PAY_MONTH = LABOR_DATA.payMonth;
const PAYROLL_STATUS = LABOR_DATA.payrollStatus;
const PAY_TYPES = LABOR_DATA.payTypes;

function payrollQuery() {
    const y = document.getElementById('payYearSel').value;
    const m = document.getElementById('payMonthSel').value;
    location.href = '?tab=payroll&py=' + y + '&pm=' + m;
}
function payrollExportCsv() {
    location.href = PAYSLIP_API + '?action=exportCsv&year=' + PAY_YEAR + '&month=' + PAY_MONTH;
}

let _psCurrentId = 0, _psEditable = false, _psCurrentEmpId = 0;

function elMoneyVal(el) {
    return parseInt(String(el.value).replace(/,/g, '')) || 0;
}
function formatMoney(el) {
    const raw = String(el.value).replace(/[^0-9]/g, '');
    const num = parseInt(raw) || 0;
    const pos = el.selectionStart;
    const oldLen = el.value.length;
    el.value = num ? num.toLocaleString('ko-KR') : '';
    const newLen = el.value.length;
    const newPos = Math.max(0, pos + (newLen - oldLen));
    el.setSelectionRange(newPos, newPos);
}

async function openPayslipModal(id) {
    _psCurrentId = id;
    try {
        const res = await fetch(PAYSLIP_API + '?action=get&id=' + id);
        const data = await res.json();
        if (!data.ok) { showToast(data.error?.message || '조회 실패', 'error'); return; }
        const item = data.data.item;
        const itemMap = {};
        (item.items || []).forEach(si => { itemMap[si.code] = si; });
        _psEditable = item.status === 'draft';
        _psCurrentEmpId = item.employee_id || 0;

        document.getElementById('psName').textContent = item.employee_name;
        document.getElementById('psOrg').textContent = (item.division_name || '') + (item.position ? ' ' + item.position : '');
        document.getElementById('psEmpNo').textContent = item.employee_id || '-';
        document.getElementById('psGender').textContent = item.gender === 'M' ? '남' : item.gender === 'F' ? '여' : '-';
        document.getElementById('psBirthDate').textContent = item.birth_date || '-';
        document.getElementById('psHireDate').textContent = item.hire_date || '-';
        document.getElementById('psWorkDays').textContent = item.work_days ? item.work_days + '일' : '-';
        document.getElementById('psWorkHours').textContent = item.work_hours ? item.work_hours + '시간' : '-';
        const sBadge = document.getElementById('psStatusBadge');
        const sMap = {draft:['작성중','badge-info'], confirmed:['확정','badge-warning'], paid:['지급완료','badge-success']};
        const [sLabel, sClass] = sMap[item.status] || ['','badge-gray'];
        sBadge.className = 'badge ' + sClass; sBadge.textContent = sLabel;

        document.querySelectorAll('.ps-pay-input').forEach(el => {
            const code = el.dataset.ptCode;
            const si = itemMap[code];
            el.value = (parseInt(si?.amount) || 0).toLocaleString('ko-KR');
        });
        document.querySelectorAll('.ps-hours-h').forEach(el => {
            const si = itemMap[el.dataset.ptCode];
            const totalHours = parseFloat(si?.hours) || 0;
            el.value = Math.floor(totalHours);
        });
        document.querySelectorAll('.ps-hours-m').forEach(el => {
            const si = itemMap[el.dataset.ptCode];
            const totalHours = parseFloat(si?.hours) || 0;
            el.value = Math.round((totalHours - Math.floor(totalHours)) * 60);
        });
        document.getElementById('psMemo').value = item.memo || '';
        psRecalc();
        document.querySelectorAll('#psModalBody .ps-input').forEach(el => { el.readOnly = !_psEditable; });
        document.getElementById('psSaveBtn').classList.toggle('hidden', !_psEditable);
        document.getElementById('psLoadContractBtn').classList.toggle('hidden', !_psEditable);
        document.getElementById('psModalTitle').textContent = _psEditable ? '급여 편집' : '급여 상세';
        document.getElementById('payslipModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } catch (e) { showToast('서버 오류', 'error'); }
}
function closePayslipModal() {
    document.getElementById('payslipModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function loadFromContract() {
    if (!_psCurrentEmpId) { showToast('직원 정보가 없습니다.', 'error'); return; }
    try {
        const res = await fetch(LABOR_DATA.basePath + '/api/labor_contract.php?action=getContractSalary&employee_id=' + _psCurrentEmpId);
        const data = await res.json();
        if (!data.salary) { showToast('계약서 급여 정보가 없습니다.', 'warning'); return; }
        const s = data.salary;
        const map = { BASE: s.base_pay, MEAL: s.meal_allowance, CAR: s.car_allowance, CHILD: s.child_allowance };
        document.querySelectorAll('.ps-pay-input').forEach(el => {
            const code = el.dataset.ptCode;
            if (map[code] !== undefined) {
                el.value = (parseInt(map[code]) || 0).toLocaleString('ko-KR');
            }
        });
        psRecalc();
        showToast('계약서 급여가 반영되었습니다.', 'success');
    } catch (e) { showToast('계약서 조회 실패', 'error'); }
}

async function refreshFromContracts() {
    if (!(await AppUI.confirm(PAY_YEAR + '년 ' + PAY_MONTH + '월 급여를 계약서 기준으로 재생성하시겠습니까?\n기존 수정사항이 초기화됩니다.'))) return;
    try {
        const res = await fetch(PAYSLIP_API + '?action=refreshFromContracts', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ year: PAY_YEAR, month: PAY_MONTH })
        });
        const data = await res.json();
        if (data.ok) {
            showToast(data.data.refreshed + '명 급여가 재생성되었습니다.', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error?.message || '재생성 실패', 'error');
        }
    } catch (e) { showToast('서버 오류', 'error'); }
}

const LEGAL_MONTHLY_HOURS = 209;
const OT_MULTIPLIER = 1.5;
let _otManualOverride = false;

function getOtHourlyRate(baseSalary) {
    const otPt = PAY_TYPES.find(t => t.code === 'OT');
    const custom = otPt?.custom_hourly_rate;
    if (custom && parseInt(custom) > 0) return { rate: parseInt(custom), mode: 'custom' };
    if (baseSalary > 0) return { rate: Math.round(baseSalary / LEGAL_MONTHLY_HOURS * OT_MULTIPLIER), mode: 'legal' };
    return { rate: 0, mode: 'legal' };
}

function updateOtRateGlobal() {
    if (!_rtOtType) return;
    const otPt = PAY_TYPES.find(t => t.code === 'OT');
    if (otPt) otPt.custom_hourly_rate = _rtOtType.custom_hourly_rate;
}

function calcOvertimePay() {
    const hEl = document.querySelector('.ps-hours-h[data-pt-code="OT"]');
    const mEl = document.querySelector('.ps-hours-m[data-pt-code="OT"]');
    const otInput = document.querySelector('.ps-pay-input[data-pt-code="OT"]');
    if (!hEl || !mEl || !otInput) return;
    const totalHours = (parseInt(hEl.value) || 0) + (parseInt(mEl.value) || 0) / 60;
    if (totalHours <= 0) { otInput.value = '0'; _otManualOverride = false; psRecalc(); return; }
    const baseEl = document.querySelector('.ps-pay-input[data-pt-code="BASE"]');
    const baseSalary = baseEl ? elMoneyVal(baseEl) : 0;
    const { rate: hourlyRate } = getOtHourlyRate(baseSalary);
    if (hourlyRate <= 0) { psRecalc(); return; }
    const otPay = Math.round(hourlyRate * totalHours);
    otInput.value = otPay.toLocaleString('ko-KR');
    _otManualOverride = false;
    psRecalc();
}

function psRecalc() {
    const fmt = n => n.toLocaleString('ko-KR') + '원';
    let baseSalary = 0, grossPay = 0;

    document.querySelectorAll('.ps-pay-input').forEach(el => {
        const amt = elMoneyVal(el);
        grossPay += amt;
        if (el.dataset.ptCode === 'BASE') baseSalary = amt;
    });

    const otInput = document.querySelector('.ps-pay-input[data-pt-code="OT"]');
    const hEl = document.querySelector('.ps-hours-h[data-pt-code="OT"]');
    const mEl = document.querySelector('.ps-hours-m[data-pt-code="OT"]');
    if (otInput && hEl && mEl) {
        const totalHours = (parseInt(hEl.value) || 0) + (parseInt(mEl.value) || 0) / 60;
        const { rate: hourlyRate, mode } = getOtHourlyRate(baseSalary);
        const rateLabel = document.getElementById('psOtRateLabel');
        if (rateLabel) {
            if (totalHours > 0 && hourlyRate > 0) {
                const modeTag = mode === 'custom' ? ' (직접설정)' : ' (법정)';
                rateLabel.textContent = '시급 ' + hourlyRate.toLocaleString('ko-KR') + '원 × ' + totalHours.toFixed(1) + 'h' + modeTag;
            } else {
                rateLabel.textContent = '';
            }
        }
    }

    document.getElementById('psGross').textContent = fmt(grossPay);

    let totalDeduct = 0;
    document.querySelectorAll('[data-deduct-code]').forEach(el => {
        const dt = PAY_TYPES.find(t => t.code === el.dataset.deductCode);
        if (!dt || dt.calc_type !== 'rate' || !dt.calc_rate) return;
        const base = (dt.calc_base === 'gross_pay') ? grossPay : baseSalary;
        const amt = Math.round(base * parseFloat(dt.calc_rate));
        el.textContent = fmt(amt);
        totalDeduct += amt;
    });
    document.getElementById('psDeduction').textContent = fmt(totalDeduct);
    document.getElementById('psNet').textContent = fmt(grossPay - totalDeduct);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#psModalBody .ps-input').forEach(el => el.addEventListener('input', psRecalc));
    document.querySelectorAll('#psModalBody .ps-money').forEach(el => {
        el.addEventListener('input', () => formatMoney(el));
    });
    document.querySelectorAll('.ps-hours-h, .ps-hours-m').forEach(el => {
        el.addEventListener('input', () => { if (!_otManualOverride) calcOvertimePay(); });
    });
    const otInput = document.querySelector('.ps-pay-input[data-pt-code="OT"]');
    if (otInput) otInput.addEventListener('input', () => { _otManualOverride = true; });
});

async function savePayslipItem() {
    if (!_psEditable || !_psCurrentId) return;
    try {
        const items = [];
        document.querySelectorAll('.ps-pay-input').forEach(el => {
            const entry = { pay_type_id: parseInt(el.dataset.ptId), amount: elMoneyVal(el) };
            const hEl = document.querySelector(`.ps-hours-h[data-pt-code="${el.dataset.ptCode}"]`);
            const mEl = document.querySelector(`.ps-hours-m[data-pt-code="${el.dataset.ptCode}"]`);
            if (hEl && mEl) entry.hours = (parseInt(hEl.value) || 0) + (parseInt(mEl.value) || 0) / 60;
            items.push(entry);
        });
        const res = await fetch(PAYSLIP_API + '?action=updateItem', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id: _psCurrentId,
                items: items,
                memo: document.getElementById('psMemo').value.trim(),
            })
        });
        const data = await res.json();
        if (data.ok) { showToast('저장 완료', 'success'); closePayslipModal(); setTimeout(() => location.reload(), 600); }
        else showToast(data.error?.message || '저장 실패', 'error');
    } catch (e) { showToast('서버 오류', 'error'); }
}
async function payrollConfirm() {
    if (!(await AppUI.confirm(PAY_YEAR + '년 ' + PAY_MONTH + '월 급여를 확정하시겠습니까?\n확정 후에는 편집할 수 없습니다.'))) return;
    try {
        const res = await fetch(PAYSLIP_API + '?action=confirm', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({year: PAY_YEAR, month: PAY_MONTH})
        });
        const data = await res.json();
        if (data.ok) { showToast(data.data.confirmed + '명 급여 확정 완료', 'success'); setTimeout(() => location.reload(), 600); }
        else showToast(data.error?.message || '확정 실패', 'error');
    } catch (e) { showToast('서버 오류', 'error'); }
}
async function payrollUnconfirm() {
    if (!(await AppUI.confirm('확정을 해제하시겠습니까? 급여가 다시 편집 가능해집니다.'))) return;
    try {
        const res = await fetch(PAYSLIP_API + '?action=unconfirm', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({year: PAY_YEAR, month: PAY_MONTH})
        });
        const data = await res.json();
        if (data.ok) { showToast('확정 해제 완료', 'success'); setTimeout(() => location.reload(), 600); }
        else showToast(data.error?.message || '해제 실패', 'error');
    } catch (e) { showToast('서버 오류', 'error'); }
}
async function payrollPay() {
    if (!(await AppUI.confirm(PAY_YEAR + '년 ' + PAY_MONTH + '월 급여를 지급완료 처리하시겠습니까?\n이후 수정이 불가능합니다.'))) return;
    try {
        const res = await fetch(PAYSLIP_API + '?action=pay', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({year: PAY_YEAR, month: PAY_MONTH})
        });
        const data = await res.json();
        if (data.ok) { showToast('지급완료 처리됨', 'success'); setTimeout(() => location.reload(), 600); }
        else showToast(data.error?.message || '처리 실패', 'error');
    } catch (e) { showToast('서버 오류', 'error'); }
}

// ===== 임금대장 검색 필터 =====
(function(){
    let pyOrg = '';
    let pyDrop = null;
    const dropEl = document.getElementById('pyDrop');
    const pillRow = document.getElementById('pyPillRow');
    const pyOpts = LABOR_DATA.pyOpts;
    const pyOrgLabel = LABOR_DATA.pyOrgLabel;
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    window.applyPayrollFilter = function(){
        const search=(document.getElementById('payrollSearch')?.value||'').trim().toLowerCase();
        document.querySelectorAll('#payrollTable .payroll-row').forEach(r=>{
            let show=true;
            if(search&&!(r.dataset.name||'').toLowerCase().includes(search)) show=false;
            if(pyOrg&&r.dataset.org!==pyOrg) show=false;
            r.style.display=show?'':'none';
        });
    };
    if(!dropEl||!pillRow||!pyOpts.length) return;
    function openDrop(){
        if(pyDrop){closeDrop();return;} closeDrop();
        const pill=document.querySelector('[data-py="org"]');if(!pill)return;
        const pr=pillRow.getBoundingClientRect(),r=pill.getBoundingClientRect();
        dropEl.style.left=(r.left-pr.left)+'px'; dropEl.style.top=(r.bottom-pr.top+6)+'px';
        let h='<div class="rf-drop__item'+(!pyOrg?' rf-drop__item--active':'')+'" data-pv="">전체</div>';
        pyOpts.forEach(o=>{h+='<div class="rf-drop__item'+(pyOrg===o?' rf-drop__item--active':'')+'" data-pv="'+esc(o)+'">'+esc(o)+'</div>';});
        dropEl.innerHTML=h; dropEl.classList.remove('hidden'); pyDrop=true;
        pill.classList.add('rf-pill--active');
        dropEl.querySelectorAll('[data-pv]').forEach(el=>el.addEventListener('click',()=>{
            pyOrg=el.dataset.pv; applyPayrollFilter(); closeDrop();
            const t=pill.querySelector('.rf-pill__text');if(t)t.textContent=pyOrg||pyOrgLabel;
            pill.classList.toggle('rf-pill--active',!!pyOrg);
        }));
    }
    function closeDrop(){dropEl.classList.add('hidden');dropEl.innerHTML='';pyDrop=null;const p=document.querySelector('[data-py="org"]');if(p&&!pyOrg)p.classList.remove('rf-pill--active');}
    document.querySelector('[data-py="org"]')?.addEventListener('click',e=>{e.stopPropagation();openDrop();});
    document.addEventListener('click',e=>{if(pyDrop&&!dropEl.contains(e.target))closeDrop();});
})();

/* ─── 급여 설정 모달 ─── */
let _rtData = [];
let _rtOtType = null;
const PREVIEW_BASE = 3000000, PREVIEW_GROSS = 3400000;
function fmtMoney(n) { return (n || 0).toLocaleString('ko-KR') + '원'; }

function openRatesModal() {
    const modal = document.getElementById('ratesModal');
    modal.classList.remove('hidden');
    fetchRatesData();
}

function closeRatesModal() {
    document.getElementById('ratesModal').classList.add('hidden');
}

async function fetchRatesData() {
    const list = document.getElementById('rtList');
    list.innerHTML = '<tr><td colspan="4" class="text-center text-gray-400 py-4">로딩 중...</td></tr>';
    try {
        const res = await fetch(PAYSLIP_API + '?action=getRates');
        const json = await res.json();
        if (!json.ok && !json.success) throw new Error(json.error?.message || json.message || '요율 조회 실패');
        const data = json.data || json;
        _rtData = data.deductTypes || [];
        _rtOtType = data.otType || null;
        renderOtSection();
        renderRateInputs();
        updateRatePreview();
    } catch (e) {
        list.innerHTML = '<tr><td colspan="4" class="text-red-500 text-center py-4">' + escHtml(e.message) + '</td></tr>';
    }
}

function renderOtSection() {
    const rate = _rtOtType?.custom_hourly_rate;
    const hasCustom = rate !== null && rate !== undefined && parseInt(rate) > 0;
    const legalRadio = document.querySelector('input[name="otRateMode"][value="legal"]');
    const customRadio = document.querySelector('input[name="otRateMode"][value="custom"]');
    const customDiv = document.getElementById('rtOtCustom');
    const rateInput = document.getElementById('rtOtHourlyRate');
    const legalDesc = document.getElementById('rtOtLegalDesc');
    if (hasCustom) {
        customRadio.checked = true;
        customDiv.classList.remove('hidden'); customDiv.classList.add('flex');
        legalDesc.classList.add('hidden');
        rateInput.value = parseInt(rate).toLocaleString('ko-KR');
    } else {
        legalRadio.checked = true;
        customDiv.classList.add('hidden'); customDiv.classList.remove('flex');
        legalDesc.classList.remove('hidden');
        rateInput.value = '';
    }
    rateInput.addEventListener('input', () => {
        const raw = rateInput.value.replace(/[^0-9]/g, '');
        rateInput.value = raw ? parseInt(raw).toLocaleString('ko-KR') : '';
    });
}

function toggleOtMode() {
    const isCustom = document.querySelector('input[name="otRateMode"][value="custom"]').checked;
    const customEl = document.getElementById('rtOtCustom');
    const legalEl = document.getElementById('rtOtLegalDesc');
    if (isCustom) { customEl.classList.remove('hidden'); customEl.classList.add('flex'); legalEl.classList.add('hidden'); document.getElementById('rtOtHourlyRate').focus(); }
    else { customEl.classList.add('hidden'); customEl.classList.remove('flex'); legalEl.classList.remove('hidden'); }
    if (isCustom) document.getElementById('rtOtHourlyRate').focus();
}

function renderRateInputs() {
    const list = document.getElementById('rtList');
    list.innerHTML = '';
    const icons = {NP:'landmark', HI:'heart-pulse', LC:'shield-plus', EI:'briefcase', IT:'receipt-text', LT:'building-2'};
    const colors = {NP:'#3b82f6', HI:'#ef4444', LC:'#f59e0b', EI:'#8b5cf6', IT:'#6366f1', LT:'#64748b'};
    _rtData.forEach((dt, i) => {
        const pct = ((parseFloat(dt.calc_rate) || 0) * 100).toFixed(3);
        const baseLabel = dt.calc_base === 'gross_pay' ? '총지급액' : '기본급';
        const icon = icons[dt.code] || 'percent';
        const color = colors[dt.code] || '#6366f1';
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100 last:border-b-0';
        tr.innerHTML =
            '<td class="py-1.5 pl-1">'
            + '<div class="flex items-center gap-1.5">'
            + '<div class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" style="background:' + color + '15">'
            + '<i data-lucide="' + icon + '" class="w-2.5 h-2.5" style="color:' + color + '"></i></div>'
            + '<span class="text-[13px] font-medium text-gray-800">' + escHtml(dt.name) + '</span></div></td>'
            + '<td class="py-1.5 text-xs text-gray-400">' + baseLabel + '</td>'
            + '<td class="py-1.5 text-right">'
            + '<div class="rt-unit-wrap rt-unit-sm">'
            + '<input type="number" step="0.001" min="0" max="50" data-rt-idx="' + i + '"'
            + ' value="' + pct + '"'
            + ' class="rt-input" />'
            + '<span class="rt-unit-suffix">%</span></div></td>'
            + '<td class="py-1.5 pr-1 text-right tabular-nums text-[13px] text-gray-600 rt-amount" data-rt-idx="' + i + '">—</td>';
        list.appendChild(tr);
    });
    lucide.createIcons({nodes: list.querySelectorAll('[data-lucide]')});
    list.querySelectorAll('.rt-input').forEach(inp => {
        inp.addEventListener('input', updateRatePreview);
    });
}

function updateRatePreview() {
    let total = 0;
    document.querySelectorAll('.rt-input').forEach(inp => {
        const idx = parseInt(inp.dataset.rtIdx);
        const dt = _rtData[idx];
        if (!dt) return;
        const rate = parseFloat(inp.value) / 100 || 0;
        const base = dt.calc_base === 'gross_pay' ? PREVIEW_GROSS : PREVIEW_BASE;
        const amount = Math.round(base * rate);
        total += amount;
        const amountCell = document.querySelector('.rt-amount[data-rt-idx="' + idx + '"]');
        if (amountCell) amountCell.textContent = fmtMoney(amount);
    });
    const foot = document.getElementById('rtFoot');
    foot.innerHTML = '<tr class="border-t border-gray-300">'
        + '<td colspan="3" class="py-1.5 pl-1 text-[13px] font-semibold text-gray-700">공제 합계</td>'
        + '<td class="py-1.5 pr-1 text-right tabular-nums text-[13px] font-semibold text-gray-900">' + fmtMoney(total) + '</td></tr>';
}

async function saveRates() {
    const btn = document.getElementById('rtSaveBtn');
    btn.disabled = true;
    btn.textContent = '저장 중...';
    try {
        const updates = [];
        document.querySelectorAll('.rt-input').forEach(inp => {
            const idx = parseInt(inp.dataset.rtIdx);
            const dt = _rtData[idx];
            if (!dt) return;
            const rate = parseFloat(inp.value) / 100;
            if (isNaN(rate) || rate < 0 || rate > 0.5) throw new Error(dt.name + ' 요율이 범위(0~50%)를 벗어납니다.');
            updates.push({ id: dt.id, calc_rate: rate });
        });
        const isCustomOt = document.querySelector('input[name="otRateMode"][value="custom"]')?.checked;
        let otHourlyRate = '';
        if (isCustomOt) {
            const raw = document.getElementById('rtOtHourlyRate').value.replace(/,/g, '');
            const val = parseInt(raw) || 0;
            if (val <= 0) throw new Error('커스텀 시급을 입력해주세요.');
            if (val > 500000) throw new Error('시급은 500,000원 이하로 입력해주세요.');
            otHourlyRate = val;
        }
        const res = await fetch(PAYSLIP_API + '?action=saveRates', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ rates: updates, otHourlyRate: otHourlyRate })
        });
        const json = await res.json();
        if (!json.ok && !json.success) throw new Error(json.error?.message || json.message || '저장 실패');
        _rtData = json.data?.deductTypes || _rtData;
        _rtOtType = json.data?.otType || _rtOtType;
        updateOtRateGlobal();
        renderRateInputs();
        updateRatePreview();
        alert('설정이 저장되었습니다. 신규 생성되는 급여부터 적용됩니다.');
        closeRatesModal();
    } catch (e) {
        alert(e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5"></i> 저장';
        lucide.createIcons();
    }
}
