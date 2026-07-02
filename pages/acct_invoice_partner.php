<?php
/**
 * 거래처 관리 - acct_invoice.php?tab=partner 에서 include
 * 셸(acct_invoice.php)이 $pdo, $basePath 를 제공
 */

// 더미 거래처 데이터
$partners = [
    ['id'=>1, 'name'=>'(주)테크솔루션',   'bizno'=>'234-56-78901', 'ceo'=>'김대표', 'type'=>'매출', 'biz_type'=>'정보통신업', 'biz_item'=>'소프트웨어 개발', 'email'=>'info@techsolution.co.kr', 'phone'=>'02-1234-5678', 'address'=>'서울시 강남구 테헤란로 123', 'invoice_count'=>8,  'last_invoice'=>'2026-02-25', 'total_supply'=>36550000, 'memo'=>''],
    ['id'=>2, 'name'=>'디자인웍스',        'bizno'=>'345-67-89012', 'ceo'=>'박디자',  'type'=>'매출', 'biz_type'=>'서비스업',   'biz_item'=>'디자인 서비스',   'email'=>'biz@designworks.kr',      'phone'=>'02-2345-6789', 'address'=>'서울시 마포구 양화로 45',    'invoice_count'=>3,  'last_invoice'=>'2026-02-05', 'total_supply'=>8500000,  'memo'=>''],
    ['id'=>3, 'name'=>'(주)스마트커머스',  'bizno'=>'456-78-90123', 'ceo'=>'이커머',  'type'=>'매출', 'biz_type'=>'도소매업',   'biz_item'=>'전자상거래',      'email'=>'partner@smartcommerce.kr', 'phone'=>'031-345-6789','address'=>'경기도 성남시 분당구 판교로', 'invoice_count'=>2,  'last_invoice'=>'2026-02-10', 'total_supply'=>3200000,  'memo'=>''],
    ['id'=>4, 'name'=>'NHN클라우드(주)',   'bizno'=>'890-12-34567', 'ceo'=>'최클라',  'type'=>'매입', 'biz_type'=>'정보통신업', 'biz_item'=>'클라우드 서비스', 'email'=>'biz@nhncloud.com',         'phone'=>'1544-0000',   'address'=>'경기도 성남시 분당구 대왕판교로', 'invoice_count'=>5, 'last_invoice'=>'2026-02-12', 'total_supply'=>8000000,  'memo'=>'서버 호스팅'],
    ['id'=>5, 'name'=>'(주)오피스허브',    'bizno'=>'901-23-45678', 'ceo'=>'정오피',  'type'=>'매입', 'biz_type'=>'도소매업',   'biz_item'=>'사무용품',        'email'=>'sales@officehub.co.kr',    'phone'=>'02-3456-7890','address'=>'서울시 영등포구 여의대방로',   'invoice_count'=>2,  'last_invoice'=>'2026-02-05', 'total_supply'=>1800000,  'memo'=>''],
];

$salesPartners = array_filter($partners, fn($p) => $p['type'] === '매출');
$purchasePartners = array_filter($partners, fn($p) => $p['type'] === '매입');
?>

<!-- 상단 액션 -->
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <div class="flex rounded-lg border border-slate-800 overflow-hidden" id="partnerTypeFilter">
            <button class="zm-tab px-3 py-1.5 text-sm zm-tab-active" onclick="filterPartners('all', this)">전체 <span class="ml-1 opacity-70"><?= count($partners) ?></span></button>
            <button class="zm-tab px-3 py-1.5 text-sm" onclick="filterPartners('매출', this)">매출처 <span class="ml-1 opacity-70"><?= count($salesPartners) ?></span></button>
            <button class="zm-tab px-3 py-1.5 text-sm" onclick="filterPartners('매입', this)">매입처 <span class="ml-1 opacity-70"><?= count(array_values($purchasePartners)) ?></span></button>
        </div>
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="partnerSearch" placeholder="거래처명 또는 사업자번호 검색"
                   class="pl-9 pr-3 py-1.5 text-sm border border-slate-800 rounded-lg w-64 outline-none focus:border-gray-300 transition-colors"
                   oninput="searchPartners()">
        </div>
    </div>
    <button onclick="openPartnerModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i> 거래처 등록
    </button>
</div>

<!-- 거래처 테이블 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm emp-table">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-950/50">
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-16">구분</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300">거래처명</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300">사업자번호</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300">대표자</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300">업종</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300">거래건수</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-300">공급가액 합계</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300">최근 거래일</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-20">액션</th>
                </tr>
            </thead>
            <tbody id="partnerTableBody">
                <?php foreach ($partners as $p):
                    $typeBadge = $p['type'] === '매출'
                        ? '<span class="inline-flex items-center px-2 py-0.5 text-sm font-medium rounded-full bg-gray-50 text-gray-600 whitespace-nowrap">매출</span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-700 whitespace-nowrap">매입</span>';
                ?>
                <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors partner-row" data-type="<?= $p['type'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-bizno="<?= $p['bizno'] ?>">
                    <td class="px-4 py-3 text-center"><?= $typeBadge ?></td>
                    <td class="px-4 py-3 font-medium text-slate-100 text-center"><?= htmlspecialchars($p['name']) ?></td>
                    <td class="px-4 py-3 text-slate-400 tabular-nums text-center"><?= $p['bizno'] ?></td>
                    <td class="px-4 py-3 text-slate-300 text-center"><?= htmlspecialchars($p['ceo']) ?></td>
                    <td class="px-4 py-3 text-slate-400 text-sm text-center"><?= htmlspecialchars($p['biz_type']) ?></td>
                    <td class="px-4 py-3 text-center text-slate-200 font-medium tabular-nums"><?= $p['invoice_count'] ?>건</td>
                    <td class="px-4 py-3 text-right text-slate-200 tabular-nums"><?= number_format($p['total_supply']) ?>원</td>
                    <td class="px-4 py-3 text-center text-slate-400 tabular-nums"><?= str_replace('-', '.', $p['last_invoice']) ?></td>
                    <td class="px-4 py-3 text-center">
                        <button onclick='openPartnerDetail(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)' class="text-slate-500 hover:text-gray-900 transition-colors p-1 rounded hover:bg-gray-100">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-800 bg-slate-950/50 text-sm text-slate-500">
        총 <?= count($partners) ?>개 거래처 · 매출처 <?= count($salesPartners) ?> · 매입처 <?= count(array_values($purchasePartners)) ?>
    </div>
</div>

<!-- 거래처 등록 모달 -->
<div id="partnerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closePartnerModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100" id="partnerModalTitle">거래처 등록</h3>
            <button onclick="closePartnerModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">거래처명 <span class="text-amber-500">*</span></label>
                    <input type="text" id="pName" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="(주)거래처명">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">사업자번호 <span class="text-amber-500">*</span></label>
                    <input type="text" id="pBizno" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="000-00-00000" maxlength="12">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">대표자명</label>
                    <input type="text" id="pCeo" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="홍길동">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">구분 <span class="text-amber-500">*</span></label>
                    <select id="pType" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                        <option value="매출">매출처</option>
                        <option value="매입">매입처</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">업종</label>
                    <input type="text" id="pBizType" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="정보통신업">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">업태</label>
                    <input type="text" id="pBizItem" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="소프트웨어 개발">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">이메일</label>
                    <input type="email" id="pEmail" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="info@company.co.kr">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">전화번호</label>
                    <input type="text" id="pPhone" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="02-0000-0000">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">주소</label>
                <input type="text" id="pAddress" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="서울시 강남구...">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">메모</label>
                <textarea id="pMemo" rows="2" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30 resize-none" placeholder="참고사항 입력"></textarea>
            </div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end">
            <button onclick="closePartnerModal()" class="btn btn-secondary">취소</button>
            <button onclick="savePartner()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i> 저장
            </button>
        </div>
    </div>
</div>

<!-- 거래처 상세 모달 -->
<div id="partnerDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closePartnerDetail()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100" id="detailTitle">거래처 상세</h3>
            <button onclick="closePartnerDetail()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6" id="detailBody"></div>
        <div class="flex gap-2 px-6 pb-5 justify-end">
            <button onclick="closePartnerDetail()" class="btn btn-secondary">닫기</button>
            <button onclick="editPartner()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 flex items-center gap-1.5">
                <i data-lucide="pencil" class="w-4 h-4"></i> 수정
            </button>
        </div>
    </div>
</div>

<script>
function filterPartners(type, btn) {
    document.querySelectorAll('#partnerTypeFilter button').forEach(b => {
        b.classList.toggle('zm-tab-active', b === btn);
    });

    document.querySelectorAll('.partner-row').forEach(row => {
        row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
    });
}

function searchPartners() {
    const q = document.getElementById('partnerSearch').value.toLowerCase().replace(/-/g, '');
    document.querySelectorAll('.partner-row').forEach(row => {
        const name = row.dataset.name.toLowerCase();
        const bizno = row.dataset.bizno.replace(/-/g, '');
        row.style.display = (name.includes(q) || bizno.includes(q)) ? '' : 'none';
    });
}

function openPartnerModal() {
    document.getElementById('partnerModalTitle').textContent = '거래처 등록';
    document.getElementById('partnerModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closePartnerModal() {
    document.getElementById('partnerModal').classList.add('hidden');
    document.body.style.overflow = '';
}
function savePartner() {
    const name = document.getElementById('pName').value.trim();
    const bizno = document.getElementById('pBizno').value.trim();
    if (!name) { alert('거래처명을 입력하세요.'); return; }
    if (!bizno) { alert('사업자번호를 입력하세요.'); return; }
    alert('거래처가 등록되었습니다.');
    closePartnerModal();
}

function openPartnerDetail(p) {
    document.getElementById('detailTitle').textContent = p.name;
    const typeBadge = p.type === '매출'
        ? '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-gray-50 text-gray-600 whitespace-nowrap">매출처</span>'
        : '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-700 whitespace-nowrap">매입처</span>';
    document.getElementById('detailBody').innerHTML =
        '<div class="space-y-3">' +
        '<div class="flex items-center gap-2">' + typeBadge + '<span class="text-sm text-slate-400">' + esc(p.bizno) + '</span></div>' +
        row('대표자', p.ceo) + row('업종 / 업태', p.biz_type + ' / ' + p.biz_item) +
        row('이메일', p.email) + row('전화번호', p.phone) + row('주소', p.address) +
        '<div class="pt-3 mt-3 border-t border-slate-800">' +
        row('거래 건수', p.invoice_count + '건') +
        row('공급가액 합계', Number(p.total_supply).toLocaleString() + '원') +
        row('최근 거래일', p.last_invoice.replace(/-/g, '.')) +
        '</div>' +
        (p.memo ? '<div class="pt-3 mt-3 border-t border-slate-800">' + row('메모', p.memo) + '</div>' : '') +
        '</div>';
    document.getElementById('partnerDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
}
function closePartnerDetail() {
    document.getElementById('partnerDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
}
function editPartner() { alert('거래처 수정 기능은 준비 중입니다.'); }

function row(label, value) {
    return '<div class="flex"><span class="text-sm text-slate-500 w-24 shrink-0">' + label + '</span><span class="text-sm text-slate-200">' + esc(value || '-') + '</span></div>';
}
function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closePartnerModal(); closePartnerDetail(); }
});
</script>
