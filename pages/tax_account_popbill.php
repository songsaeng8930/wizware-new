<?php
$pageTitle = '계좌 조회';
$currentPage = 'accounting';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/bank_brand.php';
$apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');

$tab = $_GET['tab'] ?? 'history';

// 은행 코드 목록 (팝빌 EasyFinBank 기준)
$bankCodes = [
    '0004' => '국민은행',   '0020' => '우리은행',   '0088' => '신한은행',
    '0081' => '하나은행',   '0003' => '기업은행',   '0011' => '농협은행',
    '0023' => 'SC제일은행', '0027' => '씨티은행',   '0031' => '대구은행',
    '0032' => '부산은행',   '0034' => '광주은행',   '0035' => '제주은행',
    '0037' => '전북은행',   '0039' => '경남은행',   '0045' => '새마을금고',
    '0048' => '신협',       '0050' => '상호저축',   '0071' => '우체국',
    '0089' => '케이뱅크',   '0090' => '카카오뱅크', '0092' => '토스뱅크',
];

// 더미 계좌 데이터
$accounts = [
    ['id'=>1, 'bank_code'=>'0004', 'bank'=>'국민은행',   'no'=>'123-456-789012', 'alias'=>'운영계좌',     'owner'=>'주식회사 재밋', 'consent'=>true,  'consent_date'=>'2026-01-15', 'last_sync'=>'2026-03-05 09:32', 'status'=>'정상'],
    ['id'=>2, 'bank_code'=>'0088', 'bank'=>'신한은행',   'no'=>'234-567-890123', 'alias'=>'급여계좌',     'owner'=>'주식회사 재밋', 'consent'=>true,  'consent_date'=>'2026-01-15', 'last_sync'=>'2026-03-04 18:10', 'status'=>'정상'],
    ['id'=>3, 'bank_code'=>'0003', 'bank'=>'기업은행',   'no'=>'345-678-901234', 'alias'=>'세금납부계좌', 'owner'=>'주식회사 재밋', 'consent'=>false, 'consent_date'=>null,         'last_sync'=>null,             'status'=>'미등록'],
];

// 더미 입출금 내역
$transactions = [
    ['date'=>'2026-03-04','desc'=>'사무용품 구매 (쿠팡)',    'in'=>0,        'out'=>87000,    'balance'=>24200000,'aname'=>'소모품비','confirmed'=>true ],
    ['date'=>'2026-03-04','desc'=>'GS25 편의점',             'in'=>0,        'out'=>15200,    'balance'=>24287000,'aname'=>'복리후생비','confirmed'=>true ],
    ['date'=>'2026-03-03','desc'=>'㈜ABC 서비스 매출입금',   'in'=>3200000,  'out'=>0,        'balance'=>24302200,'aname'=>'서비스매출','confirmed'=>true ],
    ['date'=>'2026-03-03','desc'=>'KT 인터넷/전화',          'in'=>0,        'out'=>110000,   'balance'=>21102200,'aname'=>'통신비','confirmed'=>false],
    ['date'=>'2026-03-02','desc'=>'네이버 광고비',           'in'=>0,        'out'=>550000,   'balance'=>21212200,'aname'=>'광고선전비','confirmed'=>true ],
    ['date'=>'2026-03-01','desc'=>'사무실 임차료',           'in'=>0,        'out'=>1500000,  'balance'=>21762200,'aname'=>'임차료','confirmed'=>true ],
    ['date'=>'2026-02-28','desc'=>'㈜XYZ 컨설팅 매출',      'in'=>5000000,  'out'=>0,        'balance'=>23262200,'aname'=>'서비스매출','confirmed'=>true ],
    ['date'=>'2026-02-28','desc'=>'직원 급여 이체',          'in'=>0,        'out'=>15000000, 'balance'=>18262200,'aname'=>'급여','confirmed'=>true ],
    ['date'=>'2026-02-27','desc'=>'출장 KTX 티켓',           'in'=>0,        'out'=>59600,    'balance'=>33262200,'aname'=>'여비교통비','confirmed'=>false],
    ['date'=>'2026-02-26','desc'=>'법인세 납부',             'in'=>0,        'out'=>1200000,  'balance'=>33321800,'aname'=>'세금과공과','confirmed'=>true ],
    ['date'=>'2026-02-25','desc'=>'식당 법인카드 결제',      'in'=>0,        'out'=>145000,   'balance'=>34521800,'aname'=>'복리후생비','confirmed'=>true ],
    ['date'=>'2026-02-24','desc'=>'외부 개발 용역비',        'in'=>0,        'out'=>3300000,  'balance'=>34666800,'aname'=>'지급수수료','confirmed'=>false],
];

function maskNo(string $no): string {
    $parts = explode('-', $no);
    if (count($parts) === 3) return $parts[0].'-'.str_repeat('*', strlen($parts[1])).'-'.substr($parts[2],-4);
    return substr($no,0,4).str_repeat('*',strlen($no)-8).substr($no,-4);
}

$totalIn  = array_sum(array_column($transactions,'in'));
$totalOut = array_sum(array_column($transactions,'out'));
$unconfirmedCount = count(array_filter($transactions, fn($t)=>!$t['confirmed']));
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <div class="flex items-center gap-2 mb-5">
            <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h2 class="text-lg font-bold text-slate-100">계좌 조회</h2>
            <span class="ml-1 px-2 py-0.5 text-sm bg-gray-50 text-gray-600 rounded-full font-medium">빠른계좌조회 연동</span>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-5">
            <a href="?tab=history" class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='history' ? 'approval-tab active' : 'approval-tab' ?>">입출금 내역</a>
            <a href="?tab=manage"  class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='manage'  ? 'approval-tab active' : 'approval-tab' ?>">계좌 관리</a>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

        <?php if ($tab === 'history'): ?>
        <!-- ===== 입출금 내역 ===== -->
        <div class="flex flex-wrap items-end gap-3 mb-5">
            <div>
                <label class="block text-sm text-slate-400 mb-1">계좌 선택</label>
                <select id="selAccount" class="border border-slate-800 rounded-lg px-3 py-2 text-sm min-w-[180px]">
                    <option value="">전체 계좌</option>
                    <?php foreach ($accounts as $acc): if(!$acc['consent']) continue; ?>
                    <option value="<?= $acc['id'] ?>"><?= $acc['alias'] ?> (<?= $acc['bank'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">기간</label>
                <div class="flex items-center gap-1">
                    <input type="date" id="dtFrom" value="<?= date('Y-m-01') ?>" class="border border-slate-800 rounded-lg px-3 py-2 text-sm">
                    <span class="text-slate-500 text-sm">~</span>
                    <input type="date" id="dtTo" value="<?= date('Y-m-d') ?>" class="border border-slate-800 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">구분</label>
                <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm">
                    <option>전체</option><option>입금</option><option>출금</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">검색</label>
                <input type="text" placeholder="거래 적요 검색" class="border border-slate-800 rounded-lg px-3 py-2 text-sm w-40">
            </div>
            <button class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5 self-end">
                <i data-lucide="search" class="w-4 h-4"></i>조회
            </button>
            <div class="ml-auto flex items-center gap-2 self-end">
                <button onclick="openSyncModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>거래내역 수집
                </button>
                <a href="tax_bank.php" class="btn btn-secondary">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>AI 분류
                </a>
            </div>
        </div>

        <!-- 요약 카드 -->
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div class="bg-primary-light rounded-xl p-4">
                <p class="text-sm text-primary font-medium mb-1">기간 총 입금</p>
                <p class="text-xl font-bold text-primary"><?= number_format($totalIn) ?>원</p>
            </div>
            <div class="bg-amber-50 rounded-xl p-4">
                <p class="text-sm text-amber-500 font-medium mb-1">기간 총 출금</p>
                <p class="text-xl font-bold text-amber-700"><?= number_format($totalOut) ?>원</p>
            </div>
            <div class="bg-amber-50 rounded-xl p-4">
                <p class="text-sm text-amber-500 font-medium mb-1">미분류 건수</p>
                <p class="text-xl font-bold text-amber-700"><?= $unconfirmedCount ?>건 <span class="text-sm font-normal">확인 필요</span></p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-3 text-left   font-medium text-slate-300 whitespace-nowrap">거래일</th>
                        <th class="py-3 px-3 text-left   font-medium text-slate-300">적요</th>
                        <th class="py-3 px-3 text-right  font-medium text-slate-300">입금</th>
                        <th class="py-3 px-3 text-right  font-medium text-slate-300">출금</th>
                        <th class="py-3 px-3 text-right  font-medium text-slate-300">잔액</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">계정과목</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                    <tr class="border-b border-slate-800 hover:bg-slate-950 <?= !$tx['confirmed'] ? 'bg-amber-50/30' : '' ?>">
                        <td class="py-3 px-3 text-slate-300 tabular-nums whitespace-nowrap"><?= $tx['date'] ?></td>
                        <td class="py-3 px-3 text-slate-200"><?= $tx['desc'] ?></td>
                        <td class="py-3 px-3 text-right text-primary tabular-nums font-medium"><?= $tx['in']  > 0 ? number_format($tx['in'])  : '-' ?></td>
                        <td class="py-3 px-3 text-right text-amber-500  tabular-nums font-medium"><?= $tx['out'] > 0 ? number_format($tx['out']) : '-' ?></td>
                        <td class="py-3 px-3 text-right text-slate-200 tabular-nums"><?= number_format($tx['balance']) ?></td>
                        <td class="py-3 px-3 text-center">
                            <?php if ($tx['aname']): ?>
                            <span class="px-2 py-0.5 text-sm rounded-full bg-primary/10 text-primary"><?= $tx['aname'] ?></span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 text-sm rounded-full bg-slate-800 text-slate-500">미분류</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <?php if ($tx['confirmed']): ?>
                            <span class="px-2 py-0.5 text-sm rounded-full bg-amber-100 text-amber-700">확정</span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 text-sm rounded-full bg-amber-100 text-amber-700">미확정</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <!-- ===== 계좌 관리 ===== -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <p class="text-sm font-medium text-slate-200">빠른계좌조회 서비스 연동 계좌</p>
                <p class="text-sm text-slate-500 mt-0.5">공동인증서 없이 계좌를 등록하고 거래내역을 자동 수집합니다. (팝빌 EasyFinBank API 기반)</p>
            </div>
            <button onclick="openAddAccountModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">
                <i data-lucide="plus" class="w-4 h-4"></i>계좌 추가
            </button>
        </div>

        <div class="space-y-3 mb-6">
            <?php foreach ($accounts as $acc): ?>
            <div class="flex items-center gap-4 p-4 border border-slate-800 rounded-xl hover:border-gray-300/50 transition-colors group">
                <?= bankBadgeHtmlPHP($acc['bank'], $apiBasePath, 'lg') ?>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-100"><?= $acc['alias'] ?></span>
                        <span class="text-sm text-slate-500"><?= $acc['bank'] ?></span>
                        <?php if ($acc['status'] === '정상'): ?>
                        <span class="px-1.5 py-0.5 text-sm rounded-full bg-amber-100 text-amber-700">연동중</span>
                        <?php else: ?>
                        <span class="px-1.5 py-0.5 text-sm rounded-full bg-slate-800 text-slate-400">미연동</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-slate-400 mt-0.5">
                        <?= maskNo($acc['no']) ?> · 예금주: <?= $acc['owner'] ?>
                        <?php if ($acc['last_sync']): ?>
                        <span class="ml-2 text-sm text-slate-500">마지막 수집: <?= $acc['last_sync'] ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <?php if ($acc['consent']): ?>
                    <button onclick="openSyncModal(<?= $acc['id'] ?>)" class="text-sm px-3 py-1.5 bg-gray-50 text-gray-600 border border-gray-300/40 rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i>수집
                    </button>
                    <?php else: ?>
                    <button onclick="openAddAccountModal(<?= $acc['id'] ?>)" class="text-sm px-3 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors">
                        등록 완료
                    </button>
                    <?php endif; ?>
                    <button onclick="editAccount(<?= $acc['id'] ?>)" class="text-slate-500 hover:text-slate-300 transition-colors p-1.5 rounded-lg hover:bg-slate-800" title="수정">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button class="text-slate-500 hover:text-amber-500 transition-colors p-1.5 rounded-lg hover:bg-amber-50" title="삭제">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- API 안내 -->
        <div class="bg-primary-light rounded-xl p-5 border border-primary-light">
            <div class="flex items-start gap-3">
                <i data-lucide="zap" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-sm font-medium text-primary mb-2">빠른계좌조회 서비스 연동 안내</p>
                    <ul class="text-sm text-primary space-y-1 list-disc list-inside">
                        <li>공동인증서(공인인증서) 없이 계좌번호 + 인터넷뱅킹 ID/PW로 간편 등록</li>
                        <li>국민, 신한, 하나, 우리, 기업, 농협 등 19개 은행 지원</li>
                        <li>이체 기능 없이 거래내역 및 잔액 조회 전용</li>
                        <li>복수 계좌 등록 가능 (법인 계좌 포함)</li>
                    </ul>
                    <p class="text-sm text-primary mt-3">* 팝빌 EasyFinBank API 기반 · 서비스 이용 신청 후 사용 가능</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        </div>
    </main>
</div>

<!-- 계좌 추가/수정 모달 -->
<div id="addAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeAddAccountModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-100">계좌 등록</h3>
                <p class="text-sm text-slate-500 mt-0.5">빠른계좌조회 서비스에 계좌를 등록합니다</p>
            </div>
            <button onclick="closeAddAccountModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">은행 선택 <span class="text-amber-500">*</span></label>
                    <select id="modalBankCode" onchange="updateBankFields()" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                        <option value="">은행 선택</option>
                        <?php foreach ($bankCodes as $code => $name): ?>
                        <option value="<?= $code ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">계좌 별칭</label>
                    <input type="text" id="modalAlias" placeholder="예: 운영계좌, 급여계좌" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">계좌번호 <span class="text-amber-500">*</span></label>
                <input type="text" id="modalAccountNo" placeholder="숫자만 입력 (예: 1234567890123)" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">사업자번호 / 생년월일 <span class="text-amber-500">*</span></label>
                <input type="text" id="modalBizNo" placeholder="사업자번호 또는 생년월일 (YYYYMMDD)" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                <p class="text-sm text-slate-500 mt-1">법인은 사업자번호, 개인은 생년월일 입력</p>
            </div>
            <!-- 은행별 추가 인증 필드 (선택) -->
            <div id="bankExtraFields" class="hidden space-y-3 p-4 bg-primary-light rounded-xl border border-primary-light">
                <p class="text-sm font-medium text-primary flex items-center gap-1">
                    <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                    선택한 은행의 추가 인증 정보 필요
                </p>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">인터넷뱅킹 ID <span id="bankIdRequired" class="text-amber-500 hidden">*</span></label>
                    <input type="text" id="modalBankId" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="인터넷뱅킹 아이디">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">인터넷뱅킹 비밀번호</label>
                    <input type="password" id="modalBankPw" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" placeholder="인터넷뱅킹 비밀번호">
                    <p class="text-sm text-slate-500 mt-1">* 비밀번호는 암호화 저장되며 조회 전용으로만 사용됩니다</p>
                </div>
            </div>
            <div class="p-3 bg-slate-950 rounded-lg text-sm text-slate-400 flex items-start gap-2">
                <i data-lucide="lock" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5 text-slate-500"></i>
                <span>입력 정보는 팝빌 EasyFinBank API를 통해 안전하게 처리되며, 잔액조회 및 거래내역 조회에만 사용됩니다. 이체/출금은 불가합니다.</span>
            </div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end border-t border-slate-800 pt-4">
            <button onclick="closeAddAccountModal()" class="btn btn-secondary">취소</button>
            <button onclick="registerAccount()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i>등록
            </button>
        </div>
    </div>
</div>

<!-- 거래내역 수집 모달 -->
<div id="syncModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeSyncModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">거래내역 수집</h3>
            <button onclick="closeSyncModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">계좌 선택</label>
                <select id="syncAccountSel" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <option value="">전체 연동 계좌</option>
                    <?php foreach ($accounts as $acc): if(!$acc['consent']) continue; ?>
                    <option value="<?= $acc['id'] ?>"><?= $acc['alias'] ?> (<?= $acc['bank'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">시작일</label>
                    <input type="date" id="syncDateFrom" value="<?= date('Y-m-01') ?>" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">종료일</label>
                    <input type="date" id="syncDateTo" value="<?= date('Y-m-d') ?>" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                </div>
            </div>
            <div id="syncProgress" class="hidden p-4 bg-primary-light rounded-xl">
                <div class="flex items-center gap-2 text-sm text-primary">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>거래내역을 수집 중입니다...</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end">
            <button onclick="closeSyncModal()" class="btn btn-secondary">취소</button>
            <button onclick="startSync()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark flex items-center gap-1.5">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>수집 시작
            </button>
        </div>
    </div>
</div>

<script>
// 은행별 추가 인증 필요 코드 (인터넷뱅킹 ID/PW 필요 은행)
const banksNeedAuth = ['0004','0020','0088','0081','0003','0011']; // 주요 시중은행

function openAddAccountModal(accountId = null) {
    document.getElementById('addAccountModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeAddAccountModal() {
    document.getElementById('addAccountModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function updateBankFields() {
    const code = document.getElementById('modalBankCode').value;
    const extra = document.getElementById('bankExtraFields');
    if (banksNeedAuth.includes(code)) {
        extra.classList.remove('hidden');
    } else {
        extra.classList.add('hidden');
    }
}

async function registerAccount() {
    const bankCode  = document.getElementById('modalBankCode').value;
    const accountNo = document.getElementById('modalAccountNo').value.trim();
    const bizNo     = document.getElementById('modalBizNo').value.trim();
    if (!bankCode || !accountNo || !bizNo) {
        alert('은행, 계좌번호, 사업자번호(생년월일)는 필수 입력 항목입니다.');
        return;
    }
    // 실제 계좌 등록은 bankapi.co.kr 연동 페이지(pages/acct_bank_register.php)를 사용한다.
    // 팝빌 SDK 연동이 도입되기 전까지 안내 후 전용 페이지로 이동.
    if ((await AppUI.confirm('팝빌 계좌 등록은 현재 API 키 연동 대기 상태입니다. bankapi.co.kr 계좌 등록 페이지로 이동할까요?'))) {
        const basePath = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>';
        location.href = basePath + '/acct_bank_register.php';
        return;
    }
    closeAddAccountModal();
}

function editAccount(id) { openAddAccountModal(id); }

function openSyncModal(accountId = null) {
    if (accountId) document.getElementById('syncAccountSel').value = accountId;
    document.getElementById('syncModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSyncModal() {
    document.getElementById('syncModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('syncProgress').classList.add('hidden');
}

function startSync() {
    // bankapi.co.kr 거래내역 조회 페이지를 사용. 팝빌 SDK 도입 전까지 기본 경로.
    document.getElementById('syncProgress').classList.remove('hidden');
    setTimeout(async () => {
        closeSyncModal();
        if ((await AppUI.confirm('거래내역 수집은 "통장 거래내역" 페이지에서 수행합니다. 이동할까요?'))) {
            const basePath = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>';
            location.href = basePath + '/acct_bank_history.php';
        }
    }, 600);
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeAddAccountModal(); closeSyncModal(); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
