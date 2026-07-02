<?php
$pageTitle = '세액공제 계산';
$currentPage = 'tax';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$tab = $_GET['tab'] ?? 'sim';

// 고용증대 세액공제 기준 금액 (2024년 기준, 3년 적용)
$creditTable = [
    'youth' => [
        'metro'    => 11000000, // 수도권 청년 1,100만원
        'province' => 12000000, // 비수도권 청년 1,200만원
    ],
    'elder' => [
        'metro'    => 7700000,  // 수도권 장년 770만원
        'province' => 7700000,  // 비수도권 장년 770만원
    ],
];

// 현재 재직 직원 수 (근태 연동 - 더미)
$currentEmpCount = 20;

// 더미 시뮬레이션 이력
$simHistory = [
    ['id'=>1,'year'=>2026,'base'=>15,'current'=>20,'youth'=>3,'elder'=>2,'region'=>'수도권','total'=>36400000,'created_at'=>'2026-03-01'],
    ['id'=>2,'year'=>2025,'base'=>12,'current'=>15,'youth'=>2,'elder'=>1,'region'=>'수도권','total'=>29700000,'created_at'=>'2025-12-15'],
    ['id'=>3,'year'=>2024,'base'=>10,'current'=>12,'youth'=>1,'elder'=>1,'region'=>'비수도권','total'=>19700000,'created_at'=>'2025-01-10'],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <div class="flex items-center gap-2 mb-5">
            <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h2 class="text-lg font-bold text-slate-100">세액공제 계산</h2>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-5">
            <a href="?tab=sim"     class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='sim'     ? 'approval-tab active' : 'approval-tab' ?>">고용증대 시뮬레이션</a>
            <a href="?tab=history" class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='history' ? 'approval-tab active' : 'approval-tab' ?>">시뮬레이션 이력</a>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

        <?php if ($tab === 'sim'): ?>
        <!-- ===== 시뮬레이션 ===== -->
        <div class="grid grid-cols-5 gap-6">
            <!-- 입력 폼 -->
            <div class="col-span-2 space-y-5">
                <div>
                    <p class="text-sm font-semibold text-slate-200 mb-3">기본 정보</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-slate-300 mb-1.5">적용 연도</label>
                            <select id="simYear" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" onchange="calcCredit()">
                                <option value="2026">2026년</option>
                                <option value="2025">2025년</option>
                                <option value="2024">2024년</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-1.5">지역 구분</label>
                            <div class="flex rounded-lg border border-slate-800 overflow-hidden">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="region" value="metro" class="sr-only" checked onchange="calcCredit()">
                                    <span class="block text-center py-2 text-sm zm-tab region-opt metro-opt zm-tab-active">수도권</span>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="region" value="province" class="sr-only" onchange="calcCredit()">
                                    <span class="block text-center py-2 text-sm zm-tab region-opt province-opt">비수도권</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-slate-200 mb-3">근로자 수</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-slate-300 mb-1.5">
                                기준년도 상시근로자 수
                                <span class="text-sm text-slate-500 ml-1">(전년도)</span>
                            </label>
                            <input type="number" id="baseCount" value="15" min="0" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" oninput="calcCredit()">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-1.5 flex items-center justify-between">
                                <span>당해년도 상시근로자 수</span>
                                <button onclick="loadCurrentEmp()" class="text-sm text-primary hover:underline flex items-center gap-0.5">
                                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>근태 연동
                                </button>
                            </label>
                            <input type="number" id="currentCount" value="<?= $currentEmpCount ?>" min="0" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" oninput="calcCredit()">
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-slate-200 mb-3">증가 인원 구분 <span class="text-sm font-normal text-slate-500">(자동 계산 후 수동 조정 가능)</span></p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-slate-300 mb-1.5">
                                청년 증가 인원
                                <span class="text-sm text-slate-500 ml-1">(29세 이하)</span>
                            </label>
                            <input type="number" id="youthCount" value="3" min="0" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" oninput="calcCredit()">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300 mb-1.5">
                                장년 증가 인원
                                <span class="text-sm text-slate-500 ml-1">(30세 이상)</span>
                            </label>
                            <input type="number" id="elderCount" value="2" min="0" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30" oninput="calcCredit()">
                        </div>
                    </div>
                </div>

                <button onclick="saveSim()" class="w-full py-2.5 text-sm font-medium text-white bg-primary rounded-xl hover:opacity-90 flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>시뮬레이션 저장
                </button>
            </div>

            <!-- 계산 결과 -->
            <div class="col-span-3 space-y-4">
                <!-- 증가 인원 요약 -->
                <div class="bg-slate-950 rounded-xl p-4 flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-sm text-slate-400">기준 인원</p>
                        <p class="text-2xl font-bold text-slate-200" id="resBase">15</p>
                        <p class="text-sm text-slate-500">명</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center">
                        <svg class="w-8 h-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-slate-400">당해 인원</p>
                        <p class="text-2xl font-bold text-slate-200" id="resCurrent"><?= $currentEmpCount ?></p>
                        <p class="text-sm text-slate-500">명</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center">
                        <span class="text-2xl text-slate-500">=</span>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-primary font-medium">총 증가 인원</p>
                        <p class="text-3xl font-bold text-primary" id="resIncrease">5</p>
                        <p class="text-sm text-slate-500">명</p>
                    </div>
                </div>

                <!-- 세액공제 계산표 -->
                <table class="w-full text-sm emp-table border-collapse">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-3 px-4 text-left font-medium text-slate-300">구분</th>
                            <th class="py-3 px-4 text-center font-medium text-slate-300">인원</th>
                            <th class="py-3 px-4 text-right font-medium text-slate-300">인당 공제액</th>
                            <th class="py-3 px-4 text-right font-medium text-slate-300">소계</th>
                        </tr>
                    </thead>
                    <tbody id="creditTable">
                        <!-- JS로 채움 -->
                    </tbody>
                </table>

                <!-- 환급 예상액 강조 -->
                <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5 text-center">
                    <p class="text-sm text-amber-700 font-medium mb-2">고용증대 세액공제 예상 환급액</p>
                    <p class="text-4xl font-bold text-amber-700" id="totalCreditDisplay">36,400,000</p>
                    <p class="text-sm text-amber-700 mt-1">원</p>
                    <div class="mt-3 p-3 bg-amber-100/50 rounded-xl text-sm text-amber-700">
                        <p>* 3년간 공제 적용 (중소기업 기준) · 고용 유지 조건 충족 시</p>
                        <p class="mt-0.5">* 청년 근로자 1인당 1,100만원 (수도권), 장년 1인당 770만원</p>
                        <p class="mt-0.5 font-medium text-amber-700">실제 공제액은 세무사 확인 후 신고하세요</p>
                    </div>
                </div>

                <!-- 안내 정보 -->
                <div class="p-4 bg-primary-light rounded-xl">
                    <p class="text-sm font-medium text-primary mb-2 flex items-center gap-1">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>고용증대 세액공제 안내
                    </p>
                    <ul class="text-sm text-primary space-y-1 list-disc list-inside">
                        <li>중소기업이 상시근로자를 증가시킨 경우 법인세/소득세에서 공제</li>
                        <li>청년(29세 이하): 수도권 1,100만원 / 비수도권 1,200만원 / 인원</li>
                        <li>장년(30세 이상): 수도권·비수도권 공통 770만원 / 인원</li>
                        <li>공제 기간: 증가년도 포함 3년간 (고용 유지 조건 있음)</li>
                        <li>이 계산기는 참고용이며, 정확한 공제액은 세무사 확인이 필요합니다</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ===== 시뮬레이션 이력 ===== -->
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-slate-400">저장된 세액공제 시뮬레이션 이력을 확인합니다.</p>
            <a href="?tab=sim" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">
                <i data-lucide="plus" class="w-4 h-4"></i>새 시뮬레이션
            </a>
        </div>
        <table class="w-full text-sm emp-table">
            <thead>
                <tr class="border-b-2 border-slate-800">
                    <th class="py-3 px-3 text-left font-medium text-slate-300">적용연도</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">기준인원</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">당해인원</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">청년증가</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">장년증가</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">지역</th>
                    <th class="py-3 px-3 text-right font-medium text-slate-300">예상 공제액</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">생성일</th>
                    <th class="py-3 px-3 text-center font-medium text-slate-300">액션</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($simHistory as $s): ?>
                <tr class="border-b border-slate-800 hover:bg-slate-950">
                    <td class="py-3 px-3 font-medium text-slate-100"><?= $s['year'] ?>년</td>
                    <td class="py-3 px-3 text-center text-slate-300"><?= $s['base'] ?>명</td>
                    <td class="py-3 px-3 text-center text-slate-300"><?= $s['current'] ?>명</td>
                    <td class="py-3 px-3 text-center text-primary font-medium"><?= $s['youth'] ?>명</td>
                    <td class="py-3 px-3 text-center text-slate-300"><?= $s['elder'] ?>명</td>
                    <td class="py-3 px-3 text-center"><span class="px-2 py-0.5 text-sm rounded-full bg-slate-800 text-slate-400"><?= $s['region'] ?></span></td>
                    <td class="py-3 px-3 text-right font-bold text-amber-700 tabular-nums"><?= number_format($s['total']) ?>원</td>
                    <td class="py-3 px-3 text-center text-slate-500 text-sm"><?= $s['created_at'] ?></td>
                    <td class="py-3 px-3 text-center">
                        <button class="btn btn-secondary btn-xs">불러오기</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        </div>
    </main>
</div>

<script>
const creditRates = <?= json_encode($creditTable, JSON_UNESCAPED_UNICODE) ?>;

// 지역 라디오 버튼 스타일
document.querySelectorAll('input[name="region"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.region-opt').forEach(el => {
            el.classList.toggle('zm-tab-active', el.previousElementSibling === radio && radio.checked);
        });
        calcCredit();
    });
});

function getRegion() {
    return document.querySelector('input[name="region"]:checked').value;
}

function calcCredit() {
    const base    = parseInt(document.getElementById('baseCount').value)    || 0;
    const current = parseInt(document.getElementById('currentCount').value) || 0;
    const youth   = parseInt(document.getElementById('youthCount').value)   || 0;
    const elder   = parseInt(document.getElementById('elderCount').value)   || 0;
    const region  = getRegion(); // 'metro' | 'province'
    const increase = Math.max(0, current - base);

    document.getElementById('resBase').textContent    = base;
    document.getElementById('resCurrent').textContent = current;
    document.getElementById('resIncrease').textContent = increase;

    const youthRate = creditRates.youth[region];
    const elderRate = creditRates.elder[region];
    const youthTotal = youth * youthRate;
    const elderTotal = elder * elderRate;
    const total = youthTotal + elderTotal;

    const regionLabel = region === 'metro' ? '수도권' : '비수도권';
    const tbody = document.getElementById('creditTable');
    tbody.innerHTML = `
        <tr class="border-b border-slate-800 hover:bg-slate-950">
            <td class="py-3 px-4 text-slate-200">청년 (29세 이하) · ${regionLabel}</td>
            <td class="py-3 px-4 text-center font-medium text-primary">${youth}명</td>
            <td class="py-3 px-4 text-right text-slate-300 tabular-nums">${youthRate.toLocaleString()}원</td>
            <td class="py-3 px-4 text-right font-medium text-slate-100 tabular-nums">${youthTotal.toLocaleString()}원</td>
        </tr>
        <tr class="border-b border-slate-800 hover:bg-slate-950">
            <td class="py-3 px-4 text-slate-200">장년 (30세 이상) · ${regionLabel}</td>
            <td class="py-3 px-4 text-center font-medium text-primary">${elder}명</td>
            <td class="py-3 px-4 text-right text-slate-300 tabular-nums">${elderRate.toLocaleString()}원</td>
            <td class="py-3 px-4 text-right font-medium text-slate-100 tabular-nums">${elderTotal.toLocaleString()}원</td>
        </tr>
        <tr class="bg-slate-950 border-t-2 border-slate-700">
            <td class="py-3 px-4 font-semibold text-slate-100">합계</td>
            <td class="py-3 px-4 text-center font-bold text-primary">${youth+elder}명</td>
            <td class="py-3 px-4"></td>
            <td class="py-3 px-4 text-right font-bold text-amber-700 tabular-nums">${total.toLocaleString()}원</td>
        </tr>
    `;
    document.getElementById('totalCreditDisplay').textContent = total.toLocaleString();

    if (window.lucide) lucide.createIcons();
}

function loadCurrentEmp() {
    document.getElementById('currentCount').value = <?= $currentEmpCount ?>;
    calcCredit();
    alert('근태 데이터에서 현재 재직 직원 수(<?= $currentEmpCount ?>명)를 불러왔습니다.');
}

function saveSim() {
    alert('시뮬레이션이 저장되었습니다.');
}

calcCredit();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
