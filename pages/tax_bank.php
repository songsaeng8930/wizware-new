<?php
$pageTitle = '통장 AI 분류';
$currentPage = 'tax';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// 더미 계정과목 (5자리 한국 표준 코드)
$accountCategories = [
    '40100' => '상품매출', '40200' => '제품매출', '41200' => '서비스매출',
    '45100' => '상품매입', '80200' => '직원급여',  '81100' => '복리후생비',
    '81900' => '지급임차료','83100' => '지급수수료','83300' => '광고선전비',
    '81300' => '접대비',   '81400' => '통신비',     '83000' => '소모품비',
    '82700' => '차량유지비','81200' => '여비교통비', '82500' => '교육훈련비',
    '80900' => '감가상각비','82100' => '보험료',     '81700' => '세금과공과금',
];

// 더미 분류 결과 데이터
$classifyResults = [
    ['date'=>'2026-03-04','desc'=>'쿠팡 사무용품',           'amount'=>87000,  'type'=>'출금','code'=>'83000','aname'=>'소모품비','confidence'=>95,'confirmed'=>false],
    ['date'=>'2026-03-04','desc'=>'GS25 편의점 23-025',      'amount'=>15200,  'type'=>'출금','code'=>'81100','aname'=>'복리후생비','confidence'=>82,'confirmed'=>false],
    ['date'=>'2026-03-03','desc'=>'㈜ABC 서비스 용역비 입금','amount'=>3200000,'type'=>'입금','code'=>'41200','aname'=>'서비스매출','confidence'=>91,'confirmed'=>false],
    ['date'=>'2026-03-03','desc'=>'KT 통신비 자동이체',      'amount'=>110000, 'type'=>'출금','code'=>'81400','aname'=>'통신비','confidence'=>98,'confirmed'=>false],
    ['date'=>'2026-03-02','desc'=>'NAVER 검색 광고',         'amount'=>550000, 'type'=>'출금','code'=>'83300','aname'=>'광고선전비','confidence'=>97,'confirmed'=>false],
    ['date'=>'2026-03-01','desc'=>'종로5가 임대료',           'amount'=>1500000,'type'=>'출금','code'=>'81900','aname'=>'지급임차료','confidence'=>88,'confirmed'=>false],
    ['date'=>'2026-02-28','desc'=>'출장경비 환불',            'amount'=>59600,  'type'=>'입금','code'=>'81200','aname'=>'여비교통비','confidence'=>61,'confirmed'=>false],
    ['date'=>'2026-02-27','desc'=>'강남 식당 법인카드',       'amount'=>145000, 'type'=>'출금','code'=>'81300','aname'=>'접대비','confidence'=>55,'confirmed'=>false],
    ['date'=>'2026-02-26','desc'=>'프리랜서 개발 용역',      'amount'=>3300000,'type'=>'출금','code'=>'83100','aname'=>'지급수수료','confidence'=>78,'confirmed'=>false],
    ['date'=>'2026-02-26','desc'=>'법인세 납부',              'amount'=>1200000,'type'=>'출금','code'=>'81700','aname'=>'세금과공과금','confidence'=>99,'confirmed'=>false],
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
            <h2 class="text-lg font-bold text-slate-100">통장 AI 분류</h2>
        </div>

        <!-- 업로드 & 분류 영역 -->
        <div class="grid grid-cols-5 gap-5 mb-5">

            <!-- 파일 업로드 -->
            <div class="col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-3 flex items-center gap-2">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-primary"></i>
                    거래내역 업로드
                </h3>
                <div id="dropZone"
                     class="border-2 border-dashed border-slate-700 rounded-xl p-8 text-center cursor-pointer hover:border-gray-400 hover:bg-gray-100 transition-colors"
                     onclick="document.getElementById('fileInput').click()"
                     ondragover="event.preventDefault(); this.classList.add('border-gray-400','bg-gray-100')"
                     ondragleave="this.classList.remove('border-gray-400','bg-gray-100')"
                     ondrop="handleDrop(event)">
                    <i data-lucide="file-spreadsheet" class="w-10 h-10 text-slate-600 mx-auto mb-3"></i>
                    <p class="text-sm font-medium text-slate-300">파일을 드래그하거나 클릭하여 업로드</p>
                    <p class="text-sm text-slate-500 mt-1">.xlsx, .xls, .csv 지원</p>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" class="hidden" onchange="handleFileSelect(this)">
                </div>
                <div id="fileInfo" class="hidden mt-3 flex items-center gap-2 p-3 bg-amber-50 rounded-lg">
                    <i data-lucide="file-check" class="w-4 h-4 text-amber-500"></i>
                    <span id="fileName" class="text-sm text-amber-700 flex-1 truncate"></span>
                    <button onclick="clearFile()" class="text-amber-500 hover:text-amber-700 p-1.5 rounded-lg hover:bg-amber-100">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- 컬럼 매핑 -->
                <div class="mt-4 space-y-3">
                    <p class="text-sm font-medium text-slate-300">컬럼 매핑 (엑셀 열 번호 또는 헤더명)</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">거래일자</label>
                            <input type="text" value="A" class="w-full border border-slate-800 rounded-lg px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">거래 적요</label>
                            <input type="text" value="B" class="w-full border border-slate-800 rounded-lg px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">입금</label>
                            <input type="text" value="C" class="w-full border border-slate-800 rounded-lg px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">출금</label>
                            <input type="text" value="D" class="w-full border border-slate-800 rounded-lg px-2 py-1.5 text-sm">
                        </div>
                    </div>
                </div>

                <button onclick="startClassify()" class="mt-4 w-full py-2.5 text-sm font-medium text-white bg-gradient-to-r from-primary to-primary rounded-xl hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    AI 분류 시작
                </button>

                <!-- AI 처리 상태 -->
                <div id="classifyProgress" class="hidden mt-3 p-3 bg-primary-light rounded-xl">
                    <div class="flex items-center gap-2 text-sm text-primary mb-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="classifyStatusText">AI가 계정과목을 분류하고 있습니다...</span>
                    </div>
                    <div class="w-full bg-primary-light rounded-full h-1.5">
                        <div id="classifyBar" class="bg-primary h-1.5 rounded-full transition-all duration-500" style="width:0%"></div>
                    </div>
                </div>
            </div>

            <!-- 분류 통계 -->
            <div class="col-span-3 bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-primary"></i>
                    분류 현황
                </h3>
                <div class="grid grid-cols-4 gap-3 mb-4">
                    <div class="text-center p-3 bg-slate-950 rounded-xl">
                        <p class="text-xl font-bold text-slate-100"><?= count($classifyResults) ?></p>
                        <p class="text-sm text-slate-400 mt-0.5">전체 건수</p>
                    </div>
                    <div class="text-center p-3 bg-amber-50 rounded-xl">
                        <p class="text-xl font-bold text-amber-700"><?= count(array_filter($classifyResults, fn($r)=>$r['confidence']>=80)) ?></p>
                        <p class="text-sm text-slate-400 mt-0.5">고신뢰 분류</p>
                    </div>
                    <div class="text-center p-3 bg-amber-50 rounded-xl">
                        <p class="text-xl font-bold text-amber-700"><?= count(array_filter($classifyResults, fn($r)=>$r['confidence']<80)) ?></p>
                        <p class="text-sm text-slate-400 mt-0.5">수동 확인 필요</p>
                    </div>
                    <div class="text-center p-3 bg-primary-light rounded-xl">
                        <p class="text-xl font-bold text-primary"><?= number_format(array_sum(array_map(fn($r)=>$r['type']==='출금'?$r['amount']:0, $classifyResults))) ?></p>
                        <p class="text-sm text-slate-400 mt-0.5">총 출금액</p>
                    </div>
                </div>

                <!-- 계정과목별 집계 -->
                <div class="overflow-y-auto max-h-48">
                    <?php
                    $grouped = [];
                    foreach ($classifyResults as $r) {
                        if ($r['type'] !== '출금') continue;
                        $key = $r['aname'];
                        if (!isset($grouped[$key])) $grouped[$key] = ['count'=>0,'total'=>0];
                        $grouped[$key]['count']++;
                        $grouped[$key]['total'] += $r['amount'];
                    }
                    arsort($grouped);
                    $maxTotal = max(array_column($grouped, 'total'));
                    foreach ($grouped as $name => $g):
                    ?>
                    <div class="flex items-center gap-3 py-1.5">
                        <span class="text-sm text-slate-300 w-24 shrink-0"><?= $name ?></span>
                        <div class="flex-1 bg-slate-800 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width:<?= round($g['total']/$maxTotal*100) ?>%"></div>
                        </div>
                        <span class="text-sm text-slate-400 tabular-nums w-24 text-right"><?= number_format($g['total']) ?>원</span>
                        <span class="text-sm text-slate-500 w-8 text-right"><?= $g['count'] ?>건</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 분류 결과 테이블 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-200 flex items-center gap-2">
                    <i data-lucide="table" class="w-4 h-4 text-primary"></i>
                    AI 분류 결과
                    <span class="text-sm font-normal text-slate-500">신뢰도 80% 미만은 수동 확인이 필요합니다</span>
                </h3>
                <div class="flex items-center gap-2">
                    <button onclick="confirmAll()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors">
                        <i data-lucide="check-check" class="w-4 h-4"></i>전체 확정
                    </button>
                    <button onclick="saveResults()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white bg-primary rounded-lg hover:opacity-90">
                        <i data-lucide="save" class="w-4 h-4"></i>저장
                    </button>
                    <button class="btn btn-secondary btn-sm">
                        <i data-lucide="download" class="w-4 h-4"></i>엑셀 다운
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm emp-table" id="classifyTable">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-3 px-3 text-center font-medium text-slate-300 w-10">
                                <input type="checkbox" class="emp-checkbox" onchange="toggleAll(this)">
                            </th>
                            <th class="py-3 px-3 text-left font-medium text-slate-300 whitespace-nowrap">거래일</th>
                            <th class="py-3 px-3 text-left font-medium text-slate-300">적요</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300">금액</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">구분</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">AI 계정과목</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">신뢰도</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">수정</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classifyResults as $i => $r):
                            $isLowConf = $r['confidence'] < 80;
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-950 <?= $isLowConf ? 'bg-amber-50/40' : '' ?>" data-row="<?= $i ?>">
                            <td class="py-3 px-3 text-center">
                                <input type="checkbox" class="emp-checkbox row-check">
                            </td>
                            <td class="py-3 px-3 text-slate-300 tabular-nums whitespace-nowrap"><?= $r['date'] ?></td>
                            <td class="py-3 px-3 text-slate-200"><?= $r['desc'] ?></td>
                            <td class="py-3 px-3 text-right font-medium tabular-nums" style="color:var(<?= $r['type']==='입금' ? '--zm-deposit-fg' : '--zm-withdraw-fg' ?>)"><?= number_format($r['amount']) ?>원</td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 text-sm rounded-full" style="background:var(<?= $r['type']==='입금' ? '--zm-deposit-bg' : '--zm-withdraw-bg' ?>);color:var(<?= $r['type']==='입금' ? '--zm-deposit-fg' : '--zm-withdraw-fg' ?>)"><?= $r['type'] ?></span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="px-2 py-0.5 text-sm rounded-full bg-primary/10 text-primary result-code" data-code="<?= $r['code'] ?>"><?= $r['aname'] ?></span>
                                    <?php if ($isLowConf): ?>
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500" title="신뢰도 낮음, 확인 필요"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <div class="w-16 bg-slate-700 rounded-full h-1.5">
                                        <div class="<?= $r['confidence'] >= 80 ? 'bg-amber-500' : 'bg-amber-100' ?> h-1.5 rounded-full" style="width:<?= $r['confidence'] ?>%"></div>
                                    </div>
                                    <span class="text-sm tabular-nums <?= $r['confidence'] >= 80 ? 'text-amber-700' : 'text-amber-500' ?>"><?= $r['confidence'] ?>%</span>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <select class="border border-slate-800 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-300/30 acc-select"
                                        data-row="<?= $i ?>"
                                        onchange="updateCategory(<?= $i ?>, this)">
                                    <?php foreach ($accountCategories as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= $r['code']===$code ? 'selected' : '' ?>><?= $code ?> <?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
function handleDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-primary','bg-primary/5');
    const file = event.dataTransfer.files[0];
    if (file) showFileInfo(file);
}
function handleFileSelect(input) {
    if (input.files[0]) showFileInfo(input.files[0]);
}
function showFileInfo(file) {
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileInfo').classList.remove('hidden');
    document.getElementById('dropZone').style.display = 'none';
}
function clearFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInfo').classList.add('hidden');
    document.getElementById('dropZone').style.display = '';
}

function startClassify() {
    const prog = document.getElementById('classifyProgress');
    const bar  = document.getElementById('classifyBar');
    const txt  = document.getElementById('classifyStatusText');
    prog.classList.remove('hidden');
    let pct = 0;
    const steps = ['파일 파싱 중...','거래 적요 분석 중...','계정과목 매핑 중...','결과 정리 중...'];
    let step = 0;
    const iv = setInterval(() => {
        pct = Math.min(pct + Math.random() * 20, 95);
        bar.style.width = pct + '%';
        if (pct > 25 * (step + 1) && step < steps.length - 1) {
            txt.textContent = steps[++step];
        }
    }, 400);
    setTimeout(() => {
        clearInterval(iv);
        bar.style.width = '100%';
        txt.textContent = '분류 완료! 아래에서 결과를 확인하세요.';
    }, 2500);
}

function updateCategory(rowIdx, sel) {
    const row = document.querySelector(`tr[data-row="${rowIdx}"]`);
    const codeSpan = row.querySelector('.result-code');
    const selectedOpt = sel.options[sel.selectedIndex];
    const codePart = selectedOpt.value;
    const namePart = selectedOpt.text.split(' ').slice(1).join(' ');
    codeSpan.textContent = namePart;
    codeSpan.dataset.code = codePart;
}

function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
}

function confirmAll() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = true);
    alert('전체 항목이 확정 처리됩니다. 저장 버튼을 눌러 완료하세요.');
}

function saveResults() {
    // 확정된 행의 계정코드 수집
    const payload = [];
    document.querySelectorAll('tr[data-row]').forEach(tr => {
        const checked = tr.querySelector('.row-check');
        const codeEl  = tr.querySelector('.result-code');
        if (checked && checked.checked && codeEl) {
            payload.push({
                row:  tr.getAttribute('data-row'),
                code: codeEl.getAttribute('data-code')
            });
        }
    });
    if (!payload.length) { alert('확정할 항목을 선택해주세요.'); return; }

    // TODO(실 DB 연동): 현재 화면 데이터는 샘플이며, bank_transactions.is_confirmed 및 계정과목 매핑을
    //                  서버에 기록하려면 api/closing.php 등에 confirmTransactions 액션 추가 필요.
    //                  지금은 확정 건수와 내용을 요약으로 알려 확인만 종료한다.
    alert(payload.length + '건의 분류가 확정 처리되었습니다.');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
