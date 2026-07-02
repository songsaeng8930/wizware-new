<?php
$pageTitle = '법인카드 정산';
$currentPage = 'card';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;

if ($pdo) {
    try { $pdo->query('SELECT 1 FROM card_expenses LIMIT 1'); $hasDB = true; } catch (PDOException $e) { $hasDB = false; }
}

$sampleExpenses = [
    ['id'=>1,'card_alias'=>'영업팀 법인카드','approval_number'=>'AP-2026-001','usage_date'=>'2026-02-20','registrant_name'=>'김영수','department'=>'영업팀','usage_type'=>'법인','category'=>'식대','amount'=>45000,'description'=>'거래처 미팅 식사','user_name'=>'김영수','business_code'=>'PRJ-A','document_number'=>'DOC-2026-0101','settlement_date'=>null,'settlement_updater'=>'','compliance_status'=>'미확인','is_settled'=>0],
    ['id'=>2,'card_alias'=>'영업팀 법인카드','approval_number'=>'AP-2026-002','usage_date'=>'2026-02-21','registrant_name'=>'김영수','department'=>'영업팀','usage_type'=>'법인','category'=>'교통비','amount'=>32000,'description'=>'거래처 방문 택시비','user_name'=>'김영수','business_code'=>'PRJ-A','document_number'=>'DOC-2026-0102','settlement_date'=>null,'settlement_updater'=>'','compliance_status'=>'미확인','is_settled'=>0],
    ['id'=>3,'card_alias'=>'개발팀 법인카드','approval_number'=>'AP-2026-003','usage_date'=>'2026-02-19','registrant_name'=>'이정민','department'=>'개발팀','usage_type'=>'법인','category'=>'소모품','amount'=>128000,'description'=>'개발용 장비 구입','user_name'=>'이정민','business_code'=>'','document_number'=>'','settlement_date'=>'2026-02-25','settlement_updater'=>'관리자','compliance_status'=>'준수','is_settled'=>1],
    ['id'=>4,'card_alias'=>'경영지원 법인카드','approval_number'=>'AP-2026-004','usage_date'=>'2026-02-18','registrant_name'=>'박지현','department'=>'경영지원실','usage_type'=>'법인','category'=>'접대비','amount'=>85000,'description'=>'고객사 접대','user_name'=>'박지현','business_code'=>'PRJ-B','document_number'=>'DOC-2026-0201','settlement_date'=>'2026-02-25','settlement_updater'=>'관리자','compliance_status'=>'준수','is_settled'=>1],
    ['id'=>5,'card_alias'=>'영업팀 법인카드','approval_number'=>'AP-2026-005','usage_date'=>'2026-02-22','registrant_name'=>'홍길동','department'=>'영업팀','usage_type'=>'개인','category'=>'교통비','amount'=>15000,'description'=>'외근 교통비','user_name'=>'홍길동','business_code'=>'','document_number'=>'','settlement_date'=>null,'settlement_updater'=>'','compliance_status'=>'미확인','is_settled'=>0],
    ['id'=>6,'card_alias'=>'개발팀 법인카드','approval_number'=>'AP-2026-006','usage_date'=>'2026-02-17','registrant_name'=>'이정민','department'=>'개발팀','usage_type'=>'법인','category'=>'식대','amount'=>62000,'description'=>'팀 회식','user_name'=>'이정민','business_code'=>'','document_number'=>'','settlement_date'=>null,'settlement_updater'=>'','compliance_status'=>'미준수','is_settled'=>0],
    ['id'=>7,'card_alias'=>'대표이사 법인카드','approval_number'=>'AP-2026-007','usage_date'=>'2026-02-15','registrant_name'=>'최민호','department'=>'경영진','usage_type'=>'법인','category'=>'접대비','amount'=>250000,'description'=>'VIP 고객 미팅','user_name'=>'최민호','business_code'=>'PRJ-C','document_number'=>'DOC-2026-0301','settlement_date'=>'2026-02-24','settlement_updater'=>'관리자','compliance_status'=>'준수','is_settled'=>1],
];

$sampleApprovals = [
    ['id'=>1,'card_alias'=>'영업팀 법인카드','approval_number'=>'AP-2026-001','merchant_name'=>'한우마을 강남점','approval_amount'=>45000,'approval_date'=>'2026-02-20 12:30:00','approval_status'=>'승인'],
    ['id'=>2,'card_alias'=>'영업팀 법인카드','approval_number'=>'AP-2026-002','merchant_name'=>'카카오택시','approval_amount'=>32000,'approval_date'=>'2026-02-21 09:15:00','approval_status'=>'승인'],
    ['id'=>3,'card_alias'=>'개발팀 법인카드','approval_number'=>'AP-2026-003','merchant_name'=>'쿠팡','approval_amount'=>128000,'approval_date'=>'2026-02-19 14:20:00','approval_status'=>'승인'],
    ['id'=>4,'card_alias'=>'경영지원 법인카드','approval_number'=>'AP-2026-004','merchant_name'=>'스시오마카세','approval_amount'=>85000,'approval_date'=>'2026-02-18 18:45:00','approval_status'=>'승인'],
    ['id'=>11,'card_alias'=>'영업팀 법인카드','approval_number'=>'AP-2026-011','merchant_name'=>'GS25 역삼점','approval_amount'=>8500,'approval_date'=>'2026-02-25 15:30:00','approval_status'=>'승인'],
    ['id'=>12,'card_alias'=>'경영지원 법인카드','approval_number'=>'AP-2026-012','merchant_name'=>'네이버페이','approval_amount'=>42000,'approval_date'=>'2026-02-25 16:00:00','approval_status'=>'취소'],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-100">법인카드 운영</h2>
        </div>
        <nav class="flex items-center gap-1 border-b border-slate-800 mb-5">
            <a href="card_manage.php" class="px-4 py-2.5 text-sm font-medium text-slate-400 hover:text-slate-200 -mb-px">카드 관리</a>
            <a href="card_settlement.php" class="px-4 py-2.5 text-sm font-medium text-primary border-b-2 border-primary -mb-px">정산</a>
        </nav>

        <!-- 검색 필터 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950 flex items-center gap-2">
                <i data-lucide="sliders-horizontal" class="w-4 h-4 text-slate-400"></i>
                <span class="text-sm font-semibold text-slate-200">검색 필터</span>
            </div>
            <div class="filter-grid">
                <div class="filter-row">
                    <div class="filter-label">카드별칭</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="search" class="filter-icon"></i>
                            <input type="text" id="sFilterCard" class="filter-input" placeholder="카드별칭 검색">
                        </div>
                    </div>
                    <div class="filter-label">사용일</div>
                    <div class="filter-value">
                        <div class="flex items-center gap-2">
                            <input type="date" id="sFilterDateFrom" class="filter-input" style="flex:1">
                            <span class="text-slate-500">~</span>
                            <input type="date" id="sFilterDateTo" class="filter-input" style="flex:1">
                        </div>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-label">정산여부</div>
                    <div class="filter-value">
                        <div class="zm-radio-group zm-segmented">
                            <label class="cursor-pointer"><input type="radio" name="sFilterSettled" value="" checked class="sr-only peer"><span class="zm-radio">전체</span></label>
                            <label class="cursor-pointer"><input type="radio" name="sFilterSettled" value="0" class="sr-only peer"><span class="zm-radio">미정산</span></label>
                            <label class="cursor-pointer"><input type="radio" name="sFilterSettled" value="1" class="sr-only peer"><span class="zm-radio">정산완료</span></label>
                        </div>
                    </div>
                    <div class="filter-label">규정준수</div>
                    <div class="filter-value">
                        <select id="sFilterCompliance" class="filter-input">
                            <option value="">전체</option>
                            <option value="준수">준수</option>
                            <option value="미준수">미준수</option>
                            <option value="미확인">미확인</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-5 py-3 bg-slate-950 border-t border-slate-800">
                <button onclick="resetSettlementFilters()" class="btn btn-secondary">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> 초기화
                </button>
                <button onclick="searchSettlements()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
                </button>
            </div>
        </div>

        <!-- 지출내역 섹션 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-bold text-slate-200">지출내역</h2>
                    <span class="text-sm text-slate-400">총 <strong id="expTotalCount" class="text-primary">0</strong>건</span>
                </div>
                <button onclick="batchSettle()" class="px-3 py-1.5 text-sm rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                    <i data-lucide="check-check" class="mr-1 w-4 h-4"></i> 일괄 정산처리
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full emp-table text-sm">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="px-3 py-2.5 w-8"><input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)" class="emp-checkbox"></th>
                            <th class="px-3 py-2.5 text-left">카드별칭</th>
                            <th class="px-3 py-2.5 text-left">승인번호</th>
                            <th class="px-3 py-2.5 text-center">사용일</th>
                            <th class="px-3 py-2.5 text-left">등록자</th>
                            <th class="px-3 py-2.5 text-left">등록자부서</th>
                            <th class="px-3 py-2.5 text-center">사용구분</th>
                            <th class="px-3 py-2.5 text-left">항목</th>
                            <th class="px-3 py-2.5 text-right">사용금액</th>
                            <th class="px-3 py-2.5 text-left">적요</th>
                            <th class="px-3 py-2.5 text-left">사용자</th>
                            <th class="px-3 py-2.5 text-left">사업코드</th>
                            <th class="px-3 py-2.5 text-left">문서번호</th>
                            <th class="px-3 py-2.5 text-center">최종업데이트</th>
                            <th class="px-3 py-2.5 text-left">업데이트작성자</th>
                            <th class="px-3 py-2.5 text-center">규정준수</th>
                            <th class="px-3 py-2.5 text-center">정산여부</th>
                        </tr>
                    </thead>
                    <tbody id="settlementBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="expPagination"></div>
        </div>

        <!-- 승인내역 섹션 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-bold text-slate-200">승인내역</h2>
                    <span class="text-sm text-slate-400">총 <strong id="aprTotalCount" class="text-primary">0</strong>건</span>
                </div>
            </div>
            <div class="px-5 py-3 border-b border-slate-800 flex gap-3 items-center">
                <div class="filter-input-wrap" style="width:12rem">
                    <i data-lucide="search" class="filter-icon"></i>
                    <input type="text" id="aprFilterCard" class="filter-input" placeholder="카드별칭 검색">
                </div>
                <div class="filter-input-wrap" style="width:12rem">
                    <i data-lucide="search" class="filter-icon"></i>
                    <input type="text" id="aprFilterNumber" class="filter-input" placeholder="승인번호 검색">
                </div>
                <button onclick="searchApprovals()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full emp-table text-sm">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="px-4 py-3 text-left">카드별칭</th>
                            <th class="px-4 py-3 text-left">승인번호</th>
                            <th class="px-4 py-3 text-left">가맹점명</th>
                            <th class="px-4 py-3 text-right">승인금액</th>
                            <th class="px-4 py-3 text-center">승인일시</th>
                            <th class="px-4 py-3 text-center">상태</th>
                        </tr>
                    </thead>
                    <tbody id="approvalBody"></tbody>
                </table>
            </div>
            <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="aprPagination"></div>
        </div>
    </main>
</div>

<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
let allSettlements = <?= json_encode($sampleExpenses, JSON_UNESCAPED_UNICODE) ?>;
let filteredSettlements = [...allSettlements];
let allApprovals = <?= json_encode($sampleApprovals, JSON_UNESCAPED_UNICODE) ?>;
let filteredApprovals = [...allApprovals];
let expPage = 1, aprPage = 1;
const perPage = 15;

document.addEventListener('DOMContentLoaded', () => {
    if (HAS_DB) { loadSettlements(); loadApprovals(); }
    else { renderSettlements(); renderApprovals(); }
});

function loadSettlements() {
    const p = new URLSearchParams();
    const card = document.getElementById('sFilterCard').value;
    const df = document.getElementById('sFilterDateFrom').value;
    const dt = document.getElementById('sFilterDateTo').value;
    const settled = document.querySelector('input[name="sFilterSettled"]:checked').value;
    const comp = document.getElementById('sFilterCompliance').value;
    if(card) p.set('card_alias',card);
    if(df) p.set('date_from',df);
    if(dt) p.set('date_to',dt);
    if(settled!=='') p.set('is_settled',settled);
    if(comp) p.set('compliance_status',comp);
    fetch(`${API_BASE}?action=getSettlements&${p}`)
        .then(r=>r.json()).then(d=>{ allSettlements=d.settlements||[]; filteredSettlements=[...allSettlements]; expPage=1; renderSettlements(); });
}

function loadApprovals() {
    const p = new URLSearchParams();
    const card = document.getElementById('aprFilterCard').value;
    const num = document.getElementById('aprFilterNumber').value;
    if(card) p.set('card_alias',card);
    if(num) p.set('approval_number',num);
    fetch(`${API_BASE}?action=getApprovals&${p}`)
        .then(r=>r.json()).then(d=>{ allApprovals=d.approvals||[]; filteredApprovals=[...allApprovals]; aprPage=1; renderApprovals(); });
}

function searchSettlements() { if(HAS_DB) loadSettlements(); else clientFilterSettlements(); }
function searchApprovals() { if(HAS_DB) loadApprovals(); else clientFilterApprovals(); }

function clientFilterSettlements() {
    const card = document.getElementById('sFilterCard').value.toLowerCase();
    const settled = document.querySelector('input[name="sFilterSettled"]:checked').value;
    const comp = document.getElementById('sFilterCompliance').value;
    filteredSettlements = allSettlements.filter(e => {
        if(card && !(e.card_alias||'').toLowerCase().includes(card)) return false;
        if(settled!=='' && String(e.is_settled)!==settled) return false;
        if(comp && e.compliance_status!==comp) return false;
        return true;
    });
    expPage=1; renderSettlements();
}

function clientFilterApprovals() {
    const card = document.getElementById('aprFilterCard').value.toLowerCase();
    const num = document.getElementById('aprFilterNumber').value.toLowerCase();
    filteredApprovals = allApprovals.filter(a => {
        if(card && !(a.card_alias||'').toLowerCase().includes(card)) return false;
        if(num && !(a.approval_number||'').toLowerCase().includes(num)) return false;
        return true;
    });
    aprPage=1; renderApprovals();
}

function resetSettlementFilters() {
    document.getElementById('sFilterCard').value='';
    document.getElementById('sFilterDateFrom').value='';
    document.getElementById('sFilterDateTo').value='';
    document.querySelector('input[name="sFilterSettled"][value=""]').checked=true;
    document.getElementById('sFilterCompliance').value='';
    searchSettlements();
}

function renderSettlements() {
    const tbody = document.getElementById('settlementBody');
    document.getElementById('expTotalCount').textContent = filteredSettlements.length;
    const start = (expPage-1)*perPage;
    const pageData = filteredSettlements.slice(start, start+perPage);

    if(!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="17" class="text-center py-8 text-slate-400 text-sm">데이터가 없습니다.</td></tr>';
        document.getElementById('expPagination').innerHTML=''; return;
    }

    tbody.innerHTML = pageData.map(e => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
            <td class="px-3 py-2.5 text-center"><input type="checkbox" class="emp-checkbox settle-check" value="${e.id}" ${e.is_settled==1?'disabled':''}></td>
            <td class="px-3 py-2.5 text-slate-200 font-medium">${esc(e.card_alias)}</td>
            <td class="px-3 py-2.5 text-slate-300">${esc(e.approval_number)}</td>
            <td class="px-3 py-2.5 text-slate-400 text-center">${esc(e.usage_date)}</td>
            <td class="px-3 py-2.5 text-slate-300">${esc(e.registrant_name)}</td>
            <td class="px-3 py-2.5 text-slate-300">${esc(e.department)}</td>
            <td class="px-3 py-2.5 text-center"><span class="px-1.5 py-0.5 rounded-full ${e.usage_type==='법인'?'bg-primary-light text-primary':'bg-amber-50 text-amber-700'}">${esc(e.usage_type)}</span></td>
            <td class="px-3 py-2.5 text-slate-300">${esc(e.category)}</td>
            <td class="px-3 py-2.5 text-right font-medium text-slate-100">${Number(e.amount).toLocaleString()}</td>
            <td class="px-3 py-2.5 text-slate-400 max-w-[120px] truncate">${esc(e.description)}</td>
            <td class="px-3 py-2.5 text-slate-300">${esc(e.user_name)}</td>
            <td class="px-3 py-2.5 text-slate-400">${esc(e.business_code||'-')}</td>
            <td class="px-3 py-2.5 text-slate-400">${esc(e.document_number||'-')}</td>
            <td class="px-3 py-2.5 text-slate-400 text-center">${e.settlement_date ? e.settlement_date.substring(0,10) : '-'}</td>
            <td class="px-3 py-2.5 text-slate-400">${esc(e.settlement_updater||'-')}</td>
            <td class="px-3 py-2.5 text-center">
                <select class="text-sm border rounded px-1 py-0.5 ${complianceColor(e.compliance_status)}" onchange="updateCompliance(${e.id}, this.value)" ${e.is_settled==1?'disabled':''}>
                    <option value="미확인" ${e.compliance_status==='미확인'?'selected':''}>미확인</option>
                    <option value="준수" ${e.compliance_status==='준수'?'selected':''}>준수</option>
                    <option value="미준수" ${e.compliance_status==='미준수'?'selected':''}>미준수</option>
                </select>
            </td>
            <td class="px-3 py-2.5 text-center">
                <span class="badge ${e.is_settled==1?'badge-success':'badge-warning'}">${e.is_settled==1?'완료':'미정산'}</span>
            </td>
        </tr>
    `).join('');

    renderPg('expPagination', filteredSettlements.length, expPage, p => { expPage=p; renderSettlements(); });
}

function renderApprovals() {
    const tbody = document.getElementById('approvalBody');
    document.getElementById('aprTotalCount').textContent = filteredApprovals.length;
    const start = (aprPage-1)*perPage;
    const pageData = filteredApprovals.slice(start, start+perPage);

    if(!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-400 text-sm">데이터가 없습니다.</td></tr>';
        document.getElementById('aprPagination').innerHTML=''; return;
    }

    tbody.innerHTML = pageData.map(a => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
            <td class="px-4 py-3 text-slate-200 font-medium">${esc(a.card_alias)}</td>
            <td class="px-4 py-3 text-slate-300">${esc(a.approval_number)}</td>
            <td class="px-4 py-3 text-slate-300">${esc(a.merchant_name)}</td>
            <td class="px-4 py-3 text-right font-medium text-slate-100">${Number(a.approval_amount).toLocaleString()}원</td>
            <td class="px-4 py-3 text-slate-400 text-center">${esc((a.approval_date||'').substring(0,16))}</td>
            <td class="px-4 py-3 text-center">
                <span class="badge ${a.approval_status==='승인'?'badge-success':'badge-warning'}">${esc(a.approval_status)}</span>
            </td>
        </tr>
    `).join('');

    renderPg('aprPagination', filteredApprovals.length, aprPage, p => { aprPage=p; renderApprovals(); });
}

function renderPg(elId, total, cur, cb) {
    const pages = Math.ceil(total / perPage);
    if(pages<=1) { document.getElementById(elId).innerHTML=''; return; }
    let h = `<button class="pg-btn ${cur===1?'pg-disabled':''}" onclick="void(0)" ${cur===1?'disabled':''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for(let i=1;i<=pages;i++) h += `<button class="pg-btn ${i===cur?'pg-active':''}">${i}</button>`;
    h += `<button class="pg-btn ${cur===pages?'pg-disabled':''}" onclick="void(0)" ${cur===pages?'disabled':''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    const el = document.getElementById(elId);
    el.innerHTML = h;
    el.querySelectorAll('.pg-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const p = parseInt(btn.textContent); if(isNaN(p)) return;
            cb(p);
        });
    });
}

function toggleCheckAll(el) {
    document.querySelectorAll('.settle-check:not(:disabled)').forEach(c => c.checked = el.checked);
}

async function batchSettle() {
    const ids = [...document.querySelectorAll('.settle-check:checked')].map(c => parseInt(c.value));
    if(!ids.length) { alert('정산 처리할 항목을 선택해주세요.'); return; }
    if(!(await AppUI.confirm(`${ids.length}건을 정산 처리하시겠습니까?`))) return;

    if(HAS_DB) {
        fetch(`${API_BASE}?action=batchSettle`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ids, settlement_updater:'관리자'}) })
            .then(r=>r.json()).then(res => { alert(res.message); loadSettlements(); });
    } else {
        ids.forEach(id => {
            const e = allSettlements.find(x=>x.id===id);
            if(e) { e.is_settled=1; e.compliance_status='준수'; e.settlement_updater='관리자'; e.settlement_date=new Date().toISOString(); }
        });
        filteredSettlements=[...allSettlements]; renderSettlements();
        alert(`${ids.length}건이 정산 처리되었습니다.`);
    }
}

function updateCompliance(id, status) {
    if(HAS_DB) {
        fetch(`${API_BASE}?action=updateSettlement`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id, compliance_status:status, is_settled:0, settlement_updater:'관리자'}) })
            .then(r=>r.json()).then(() => loadSettlements());
    } else {
        const e = allSettlements.find(x=>x.id===id);
        if(e) { e.compliance_status=status; e.settlement_updater='관리자'; e.settlement_date=new Date().toISOString(); }
        filteredSettlements=[...allSettlements]; renderSettlements();
    }
}

function complianceColor(s) {
    if(s==='준수') return 'border-emerald-200 text-emerald-700 bg-emerald-50';
    if(s==='미준수') return 'border-rose-200 text-rose-700 bg-rose-50';
    return 'border-slate-700 text-slate-400';
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
