/**
 * labor-contract.js — 근로자 계약 필터 + 근로자명부 필터/인쇄
 * LABOR_DATA.rosterData, .rosterMeta, .rfLabels, .rfOpts, .cfLabels, .cfOpts
 */

// ===== 근로자명부 인쇄 =====
const _rosterData = LABOR_DATA.rosterData;
const _rosterMeta = LABOR_DATA.rosterMeta;

function printRoster() {
    const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const m = _rosterMeta;
    const today = new Date();
    const dateStr = today.getFullYear() + '.' + String(today.getMonth()+1).padStart(2,'0') + '.' + String(today.getDate()).padStart(2,'0');

    const activeFilter = document.querySelector('.roster-filter.bg-primary');
    const filterStatus = activeFilter ? activeFilter.dataset.status : 'all';
    const data = filterStatus === 'all' ? _rosterData : _rosterData.filter(e => e.status === filterStatus);
    const filterLabel = filterStatus === 'all' ? '전체' : filterStatus;

    let cols = '<th>No.</th><th>사번</th><th>이름</th><th>생년월일</th><th>성별</th>';
    if (m.showDiv) cols += '<th>' + esc(m.divLabel) + '</th>';
    if (m.showDept) cols += '<th>' + esc(m.deptLabel) + '</th>';
    cols += '<th>직급</th><th>고용형태</th><th>재직상태</th><th>입사일</th><th>주소</th>';

    let rows = '';
    data.forEach((e, i) => {
        const gender = e.gender === 'M' ? '남' : (e.gender === 'F' ? '여' : '-');
        rows += '<tr' + (e.status === '퇴사' ? ' class="resigned"' : '') + '>';
        rows += '<td class="center">' + (i+1) + '</td>';
        rows += '<td class="center">' + esc(e.empNo) + '</td>';
        rows += '<td>' + esc(e.name) + '</td>';
        rows += '<td class="center">' + esc(e.birth) + '</td>';
        rows += '<td class="center">' + gender + '</td>';
        if (m.showDiv) rows += '<td class="center">' + esc(e.org) + '</td>';
        if (m.showDept) rows += '<td class="center">' + esc(e.dept) + '</td>';
        rows += '<td class="center">' + esc(e.rank) + '</td>';
        rows += '<td class="center">' + esc(e.type) + '</td>';
        rows += '<td class="center">' + esc(e.status) + '</td>';
        rows += '<td class="center">' + esc(e.date) + '</td>';
        rows += '<td>' + esc(e.address || '-') + '</td>';
        rows += '</tr>';
    });

    const html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>근로자명부</title>'
    + '<style>'
    + '@page{size:A4 landscape;margin:0}'
    + 'body{font-family:"Pretendard","Malgun Gothic",sans-serif;color:#111;margin:0;padding:10mm 12mm 14mm;font-size:12px}'
    + 'html,body{height:auto;overflow:visible}'
    + '.doc-header{text-align:center;margin-bottom:20px}'
    + '.doc-header h1{font-size:22px;font-weight:700;letter-spacing:4px;margin:0 0 6px}'
    + '.doc-header .sub{font-size:12px;color:#666}'
    + '.summary{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px;font-size:11px;color:#333}'
    + '.summary .counts span{margin-right:12px}'
    + '.summary .counts strong{font-weight:600}'
    + 'table{width:100%;border-collapse:collapse;font-size:10px}'
    + 'thead th{background:#f1f3f5;border:1px solid #ccc;padding:5px 4px;font-weight:600;text-align:center;white-space:nowrap}'
    + 'tbody td{border:1px solid #ddd;padding:4px 5px;vertical-align:middle}'
    + 'tbody td.center{text-align:center}'
    + 'tbody tr:nth-child(even){background:#fafbfc}'
    + 'tbody tr.resigned{color:#999}'
    + 'tbody tr.resigned td:nth-child(3){text-decoration:line-through}'
    + '.doc-footer{position:fixed;bottom:4mm;left:12mm;right:12mm;display:flex;justify-content:space-between;font-size:8px;color:#aaa;border-top:1px solid #e5e5e5;padding-top:2px}'
    + '</style></head><body>'
    + '<div class="doc-header">'
    + '<h1>근 로 자 명 부</h1>'
    + '<p class="sub">작성일: ' + dateStr + '</p>'
    + '</div>'
    + '<div class="summary">'
    + '<div class="counts">'
    + '<span>전체 <strong>' + m.total + '</strong>명</span>'
    + '<span>재직 <strong>' + m.active + '</strong></span>'
    + '<span>휴직 <strong>' + m.leave + '</strong></span>'
    + '<span>퇴사 <strong>' + m.resigned + '</strong></span>'
    + (filterStatus !== 'all' ? '<span style="color:#4F6AFF;font-weight:600">(' + filterLabel + ' 필터 적용)</span>' : '')
    + '</div>'
    + '<div>' + data.length + '명 출력</div>'
    + '</div>'
    + '<table><thead><tr>' + cols + '</tr></thead>'
    + '<tbody>' + rows + '</tbody></table>'
    + '<div class="doc-footer">'
    + '<span>근로기준법 제41조에 의한 근로자명부</span>'
    + '<span>출력일시: ' + dateStr + ' ' + String(today.getHours()).padStart(2,'0') + ':' + String(today.getMinutes()).padStart(2,'0') + '</span>'
    + '</div>'
    + '<script>window.onload=function(){window.print();window.onafterprint=function(){window.close()}}<\/script>'
    + '</body></html>';

    const w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
}

// ===== 근로자명부 필터 시스템 =====
const RF = { search:'', org:'', dept:'', rank:'', type:'', gender:'' };
const rfLabels = LABOR_DATA.rfLabels;
const rfOpts = LABOR_DATA.rfOpts;
let rfCurrentDrop = null;
const rfDropEl = document.getElementById('rfDrop');
const rfPillRow = document.getElementById('rfPillRow');

function rfEsc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function rfOpenDrop(key) {
    if (rfCurrentDrop === key) { rfCloseDrop(); return; }
    rfCloseDrop();
    const pill = document.querySelector('[data-rf="'+key+'"]');
    if (!pill) return;
    const pr = rfPillRow.getBoundingClientRect();
    const r = pill.getBoundingClientRect();
    rfDropEl.style.left = (r.left - pr.left) + 'px';
    rfDropEl.style.top = (r.bottom - pr.top + 6) + 'px';
    let h = '<div class="rf-drop__item'+(!RF[key]?' rf-drop__item--active':'')+'" data-rv="" data-rk="'+key+'">전체</div>';
    (rfOpts[key]||[]).forEach(o => {
        h += '<div class="rf-drop__item'+(RF[key]===o?' rf-drop__item--active':'')+'" data-rv="'+rfEsc(o)+'" data-rk="'+key+'">'+rfEsc(o)+'</div>';
    });
    rfDropEl.innerHTML = h;
    rfDropEl.classList.remove('hidden');
    rfCurrentDrop = key;
    pill.classList.add('rf-pill--active');
    rfDropEl.querySelectorAll('[data-rv]').forEach(el => el.addEventListener('click', () => {
        RF[el.dataset.rk] = el.dataset.rv;
        applyRosterFilters(); rfRenderChips(); rfRenderPills(); rfCloseDrop();
    }));
}
function rfCloseDrop() {
    rfDropEl.classList.add('hidden'); rfDropEl.innerHTML = '';
    if (rfCurrentDrop) { const p = document.querySelector('[data-rf="'+rfCurrentDrop+'"]'); if (p && !RF[rfCurrentDrop]) p.classList.remove('rf-pill--active'); }
    rfCurrentDrop = null;
}
function rfRenderPills() {
    document.querySelectorAll('[data-rf]').forEach(p => {
        const k = p.dataset.rf;
        const txt = p.querySelector('.rf-pill__text');
        p.classList.toggle('rf-pill--active', !!RF[k]);
        if (txt) txt.textContent = RF[k] ? RF[k] : rfLabels[k];
    });
}
function rfRenderChips() {
    const bar = document.getElementById('rfChipBar');
    const ct = document.getElementById('rfChips');
    const xSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    const chips = [];
    ['org','dept','rank','type','gender'].forEach(k => {
        if (RF[k]) chips.push('<span class="rf-chip">'+rfEsc(rfLabels[k])+': '+rfEsc(RF[k])+'<span class="rf-chip__x" data-rc="'+k+'">'+xSvg+'</span></span>');
    });
    if (chips.length) { ct.innerHTML = chips.join(''); bar.classList.remove('hidden'); }
    else { ct.innerHTML = ''; bar.classList.add('hidden'); }
    ct.querySelectorAll('[data-rc]').forEach(x => x.addEventListener('click', () => {
        RF[x.dataset.rc] = ''; applyRosterFilters(); rfRenderChips(); rfRenderPills();
    }));
}
function applyRosterFilters() {
    const search = (document.getElementById('rosterSearch')?.value || '').trim().toLowerCase();
    const activeBtn = document.querySelector('.roster-filter.bg-primary');
    const status = activeBtn ? activeBtn.dataset.status : 'all';
    let count = 0;
    document.querySelectorAll('#rosterTbl tbody tr').forEach(r => {
        let show = true;
        if (status !== 'all' && r.dataset.status !== status) show = false;
        if (search && !r.dataset.name.toLowerCase().includes(search) && !(r.dataset.empno||'').toLowerCase().includes(search)) show = false;
        if (RF.org && r.dataset.org !== RF.org) show = false;
        if (RF.dept && r.dataset.dept !== RF.dept) show = false;
        if (RF.rank && r.dataset.rank !== RF.rank) show = false;
        if (RF.type && r.dataset.type !== RF.type) show = false;
        if (RF.gender && r.dataset.gender !== RF.gender) show = false;
        r.style.display = show ? '' : 'none';
        if (show) count++;
    });
}
function resetRosterFilters() {
    document.getElementById('rosterSearch').value = '';
    Object.keys(RF).forEach(k => RF[k] = '');
    document.querySelectorAll('.roster-filter').forEach((b, i) => {
        b.className = 'roster-filter px-3 py-1 text-sm rounded-full ' + (i === 0 ? 'bg-primary text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700');
    });
    applyRosterFilters(); rfRenderChips(); rfRenderPills();
}
document.querySelectorAll('.roster-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.roster-filter').forEach(b => {
            b.className = 'roster-filter px-3 py-1 text-sm rounded-full bg-slate-800 text-slate-300 hover:bg-slate-700';
        });
        this.className = 'roster-filter px-3 py-1 text-sm rounded-full bg-primary text-white';
        applyRosterFilters();
    });
});
document.querySelectorAll('[data-rf]').forEach(p => p.addEventListener('click', e => { e.stopPropagation(); rfOpenDrop(p.dataset.rf); }));
document.addEventListener('click', e => { if (rfCurrentDrop && !rfDropEl.contains(e.target)) rfCloseDrop(); });

// ===== 근로자 계약 필터 =====
const CF = { org:'', dept:'', rank:'', type:'', cstatus:'' };
const cfLabels = LABOR_DATA.cfLabels;
const cfOpts = LABOR_DATA.cfOpts;
(function(){
    let curDrop = null;
    const dropEl = document.getElementById('ctDrop');
    const pillRow = document.getElementById('ctPillRow');
    if (!dropEl || !pillRow) return;
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function openDrop(key){
        if(curDrop===key){closeDrop();return;} closeDrop();
        const pill=document.querySelector('[data-ct="'+key+'"]');if(!pill)return;
        const pr=pillRow.getBoundingClientRect(),r=pill.getBoundingClientRect();
        dropEl.style.left=(r.left-pr.left)+'px'; dropEl.style.top=(r.bottom-pr.top+6)+'px';
        let h='<div class="rf-drop__item'+(!CF[key]?' rf-drop__item--active':'')+'" data-cv="" data-ck="'+key+'">전체</div>';
        (cfOpts[key]||[]).forEach(o=>{h+='<div class="rf-drop__item'+(CF[key]===o?' rf-drop__item--active':'')+'" data-cv="'+esc(o)+'" data-ck="'+key+'">'+esc(o)+'</div>';});
        dropEl.innerHTML=h; dropEl.classList.remove('hidden'); curDrop=key;
        pill.classList.add('rf-pill--active');
        dropEl.querySelectorAll('[data-cv]').forEach(el=>el.addEventListener('click',()=>{
            CF[el.dataset.ck]=el.dataset.cv; applyContractFilters(); renderCtChips(); renderCtPills(); closeDrop();
        }));
    }
    function closeDrop(){dropEl.classList.add('hidden');dropEl.innerHTML='';if(curDrop){const p=document.querySelector('[data-ct="'+curDrop+'"]');if(p&&!CF[curDrop])p.classList.remove('rf-pill--active');}curDrop=null;}
    function renderCtPills(){document.querySelectorAll('[data-ct]').forEach(p=>{const k=p.dataset.ct,t=p.querySelector('.rf-pill__text');p.classList.toggle('rf-pill--active',!!CF[k]);if(t)t.textContent=CF[k]||cfLabels[k];});}
    function renderCtChips(){
        const bar=document.getElementById('ctChipBar'),ct=document.getElementById('ctChips');
        const xSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        const chips=[];
        ['org','dept','rank','type','cstatus'].forEach(k=>{if(CF[k])chips.push('<span class="rf-chip">'+esc(cfLabels[k])+': '+esc(CF[k])+'<span class="rf-chip__x" data-cc="'+k+'">'+xSvg+'</span></span>');});
        if(chips.length){ct.innerHTML=chips.join('');bar.classList.remove('hidden');}else{ct.innerHTML='';bar.classList.add('hidden');}
        ct.querySelectorAll('[data-cc]').forEach(x=>x.addEventListener('click',()=>{CF[x.dataset.cc]='';applyContractFilters();renderCtChips();renderCtPills();}));
    }
    window.applyContractFilters = function(){
        const search=(document.getElementById('contractSearch')?.value||'').trim().toLowerCase();
        document.querySelectorAll('#contractTbl tbody tr').forEach(r=>{
            if(!r.dataset.name) return;
            let show=true;
            if(search&&!r.dataset.name.toLowerCase().includes(search)) show=false;
            if(CF.org&&r.dataset.org!==CF.org) show=false;
            if(CF.dept&&r.dataset.dept!==CF.dept) show=false;
            if(CF.rank&&r.dataset.rank!==CF.rank) show=false;
            if(CF.type&&r.dataset.type!==CF.type) show=false;
            if(CF.cstatus&&r.dataset.cstatus!==CF.cstatus) show=false;
            r.style.display=show?'':'none';
        });
    };
    window.resetContractFilters = function(){
        document.getElementById('contractSearch').value='';
        Object.keys(CF).forEach(k=>CF[k]='');
        applyContractFilters(); renderCtChips(); renderCtPills();
    };
    document.querySelectorAll('[data-ct]').forEach(p=>p.addEventListener('click',e=>{e.stopPropagation();openDrop(p.dataset.ct);}));
    document.addEventListener('click',e=>{if(curDrop&&!dropEl.contains(e.target))closeDrop();});
})();
