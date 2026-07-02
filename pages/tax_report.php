<?php
$pageTitle = '세무 리포트';
$currentPage = 'tax';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$period = $_GET['period'] ?? 'month'; // month | quarter | year

// 더미 월별 매출/매입 데이터
$monthlyData = [
    1  => ['sales'=>18500000, 'purchase'=>12300000],
    2  => ['sales'=>21200000, 'purchase'=>14800000],
    3  => ['sales'=>19800000, 'purchase'=>11200000],
    4  => ['sales'=>23400000, 'purchase'=>15600000],
    5  => ['sales'=>22100000, 'purchase'=>13400000],
    6  => ['sales'=>25600000, 'purchase'=>16200000],
    7  => ['sales'=>20300000, 'purchase'=>12800000],
    8  => ['sales'=>19700000, 'purchase'=>11500000],
    9  => ['sales'=>24500000, 'purchase'=>15100000],
    10 => ['sales'=>27800000, 'purchase'=>17300000],
    11 => ['sales'=>26200000, 'purchase'=>16700000],
    12 => ['sales'=>31500000, 'purchase'=>19800000],
];

// 현재 선택 기간 데이터
$curSales    = $monthlyData[$month]['sales'];
$curPurchase = $monthlyData[$month]['purchase'];
$vatPayable  = ($curSales - $curPurchase) * 0.1;
$prevMonth   = $month > 1 ? $month - 1 : 12;
$prevSales   = $monthlyData[$prevMonth]['sales'];
$salesGrowth = round(($curSales - $prevSales) / $prevSales * 100, 1);

// 계정과목별 지출 더미 데이터
$expenseByCategory = [
    '급여'       => 8500000,
    '임차료'     => 1500000,
    '광고선전비' => 2200000,
    '지급수수료' => 1800000,
    '복리후생비' => 680000,
    '통신비'     => 320000,
    '소모품비'   => 280000,
    '여비교통비' => 420000,
    '기타'       => 300000,
];
$totalExpense = array_sum($expenseByCategory);

// 부가세 상세 더미
$vatDetail = [
    ['type'=>'과세', 'sales'=>16800000, 'purchase'=>9200000, 'vat_sales'=>1680000, 'vat_purchase'=>920000],
    ['type'=>'면세', 'sales'=>1700000,  'purchase'=>1500000, 'vat_sales'=>0,       'vat_purchase'=>0],
    ['type'=>'영세율','sales'=>0,       'purchase'=>1500000, 'vat_sales'=>0,       'vat_purchase'=>0],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-lg font-bold text-slate-100">세무 리포트</h2>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="runAiInsight()" id="btnAiInsight" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>AI 분석
                </button>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i data-lucide="printer" class="w-4 h-4"></i>PDF 출력
                </button>
            </div>
        </div>

        <!-- 기간 선택 필터 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-3">
            <div class="flex rounded-lg border border-slate-800 overflow-hidden">
                <?php foreach(['month'=>'월별','quarter'=>'분기별','year'=>'연간'] as $k=>$v): ?>
                <a href="?period=<?= $k ?>&year=<?= $year ?>&month=<?= $month ?>"
                   class="px-4 py-2 text-sm transition-colors <?= $period===$k ? 'bg-primary text-white' : 'text-slate-300 hover:bg-slate-950' ?>"><?= $v ?></a>
                <?php endforeach; ?>
            </div>
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm" onchange="location.href='?period=<?= $period ?>&year='+this.value+'&month=<?= $month ?>'">
                <?php for($y=2024;$y<=2026;$y++): ?>
                <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?>년</option>
                <?php endfor; ?>
            </select>
            <?php if ($period === 'month'): ?>
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm" onchange="location.href='?period=<?= $period ?>&year=<?= $year ?>&month='+this.value">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>><?= $m ?>월</option>
                <?php endfor; ?>
            </select>
            <?php endif; ?>
            <span class="ml-2 text-sm font-medium text-slate-200">
                <?= $year ?>년 <?= $month ?>월 리포트
            </span>
        </div>

        <!-- 요약 카드 -->
        <div class="grid grid-cols-4 gap-4 mb-5 print-section">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">매출 합계</p>
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4 text-slate-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-100"><?= number_format($curSales) ?></p>
                <p class="text-sm text-slate-500 mt-1">원</p>
                <p class="text-sm mt-2 <?= $salesGrowth>=0?'text-emerald-500':'text-rose-500' ?>">
                    전월 대비 <?= $salesGrowth>=0?'+':'' ?><?= $salesGrowth ?>%
                </p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">매입 합계</p>
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                        <i data-lucide="trending-down" class="w-4 h-4 text-slate-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-100"><?= number_format($curPurchase) ?></p>
                <p class="text-sm text-slate-500 mt-1">원</p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">부가세 예상 납부액</p>
                    <div class="w-8 h-8 rounded-full <?= $vatPayable>=0?'bg-amber-100':'bg-emerald-100' ?> flex items-center justify-center">
                        <i data-lucide="receipt" class="w-4 h-4 <?= $vatPayable>=0?'text-amber-500':'text-emerald-500' ?>"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold <?= $vatPayable>=0?'text-amber-500':'text-emerald-500' ?>"><?= number_format(abs($vatPayable)) ?></p>
                <p class="text-sm text-slate-500 mt-1">원 <?= $vatPayable>=0?'납부 예정':'환급 예정' ?></p>
                <p class="text-sm text-slate-500 mt-1">(매출-매입) × 10%</p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-400">영업이익률</p>
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                        <i data-lucide="percent" class="w-4 h-4 text-slate-400"></i>
                    </div>
                </div>
                <?php $margin = round(($curSales-$curPurchase)/$curSales*100, 1); ?>
                <p class="text-2xl font-bold text-slate-100"><?= $margin ?>%</p>
                <p class="text-sm text-slate-500 mt-1">(매출 - 매입) / 매출</p>
            </div>
        </div>

        <!-- 차트 영역 -->
        <div class="grid grid-cols-5 gap-5 mb-5">

            <!-- 월별 매출/매입 막대 차트 (SVG) -->
            <div class="col-span-3 bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4">월별 매출 / 매입 추이</h3>
                <div class="flex items-end gap-1.5 h-48" id="barChart">
                    <?php
                    $maxVal = max(array_merge(
                        array_column($monthlyData, 'sales'),
                        array_column($monthlyData, 'purchase')
                    ));
                    $monthNames = ['','1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'];
                    foreach ($monthlyData as $m => $d):
                        $sH = round($d['sales']    / $maxVal * 160);
                        $pH = round($d['purchase'] / $maxVal * 160);
                        $isActive = ($m === $month);
                    ?>
                    <div class="flex flex-col items-center gap-0.5 flex-1 group cursor-pointer" title="<?= $monthNames[$m] ?> 매출: <?= number_format($d['sales']) ?>원 / 매입: <?= number_format($d['purchase']) ?>원">
                        <div class="flex items-end gap-0.5 w-full">
                            <div class="flex-1 rounded-t <?= $isActive ? 'bg-primary' : 'bg-primary/30 group-hover:bg-primary/50' ?> transition-colors" style="height:<?= $sH ?>px"></div>
                            <div class="flex-1 rounded-t <?= $isActive ? 'bg-slate-500' : 'bg-slate-700 group-hover:bg-slate-700' ?> transition-colors" style="height:<?= $pH ?>px"></div>
                        </div>
                        <span class="text-[9px] text-slate-500 <?= $isActive ? 'font-bold text-slate-100' : '' ?>"><?= $m ?>월</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center gap-4 mt-3 justify-center">
                    <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-sm bg-primary"></div><span class="text-sm text-slate-400">매출</span></div>
                    <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-sm bg-slate-600"></div><span class="text-sm text-slate-400">매입</span></div>
                </div>
            </div>

            <!-- 계정과목별 도넛 차트 -->
            <div class="col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4">지출 계정과목별 분포</h3>
                <?php
                $colors = ['#4F6AFF','#6B7FFF','#818CF8','#A5B4FC','#C7D2FE','#E0E7FF','#F0F4FF','#94A3B8','#CBD5E1'];
                $sortedExp = $expenseByCategory;
                arsort($sortedExp);
                $colorIdx = 0;
                $cumAngle = -90;
                $r = 60; $cx = 80; $cy = 80;
                $svgPaths = '';
                $legendItems = [];
                foreach ($sortedExp as $name => $amount) {
                    $pct = $amount / $totalExpense;
                    $angle = $pct * 360;
                    $startRad = deg2rad($cumAngle);
                    $endRad = deg2rad($cumAngle + $angle);
                    $x1 = $cx + $r * cos($startRad);
                    $y1 = $cy + $r * sin($startRad);
                    $x2 = $cx + $r * cos($endRad);
                    $y2 = $cy + $r * sin($endRad);
                    $large = $angle > 180 ? 1 : 0;
                    $color = $colors[$colorIdx % count($colors)];
                    $svgPaths .= "<path d=\"M{$cx},{$cy} L{$x1},{$y1} A{$r},{$r} 0 {$large},1 {$x2},{$y2} Z\" fill=\"{$color}\" opacity=\"0.85\"/>";
                    $legendItems[] = ['name'=>$name,'pct'=>round($pct*100,1),'color'=>$color,'amount'=>$amount];
                    $cumAngle += $angle;
                    $colorIdx++;
                }
                ?>
                <div class="flex items-center gap-4">
                    <svg width="160" height="160" viewBox="0 0 160 160" class="shrink-0">
                        <?= $svgPaths ?>
                        <circle cx="80" cy="80" r="35" fill="var(--zm-surface-1)"/>
                        <text x="80" y="77" text-anchor="middle" font-size="9" fill="var(--zm-text-subtle)">총지출</text>
                        <text x="80" y="90" text-anchor="middle" font-size="8" fill="var(--zm-text-strong)" font-weight="bold"><?= number_format(round($totalExpense/10000)) ?>만원</text>
                    </svg>
                    <div class="flex-1 space-y-1 overflow-y-auto max-h-36">
                        <?php foreach ($legendItems as $item): ?>
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:<?= $item['color'] ?>"></div>
                            <span class="text-sm text-slate-300 flex-1 truncate"><?= $item['name'] ?></span>
                            <span class="text-sm text-slate-500 tabular-nums"><?= $item['pct'] ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 부가세 상세 & 계정과목 합계 -->
        <div class="grid grid-cols-2 gap-5">
            <!-- 부가세 예상 상세 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-slate-400"></i>
                    부가세 예상 납부액 상세
                </h3>
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-2 px-3 text-left font-medium text-slate-300">구분</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">매출</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">매입</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">부가세(매출)</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">부가세(매입)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vatDetail as $v): ?>
                        <tr class="border-b border-slate-800">
                            <td class="py-2.5 px-3 text-slate-200"><?= $v['type'] ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums"><?= $v['sales']>0?number_format($v['sales']):'-' ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums"><?= $v['purchase']>0?number_format($v['purchase']):'-' ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-200 tabular-nums"><?= $v['vat_sales']>0?number_format($v['vat_sales']):'-' ?></td>
                            <td class="py-2.5 px-3 text-right text-amber-700 tabular-nums"><?= $v['vat_purchase']>0?number_format($v['vat_purchase']):'-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php
                        $sumVatSales = array_sum(array_column($vatDetail,'vat_sales'));
                        $sumVatPurch = array_sum(array_column($vatDetail,'vat_purchase'));
                        $netVat = $sumVatSales - $sumVatPurch;
                        ?>
                        <tr class="border-t-2 border-slate-700 bg-slate-950">
                            <td class="py-2.5 px-3 font-semibold text-slate-100">합계</td>
                            <td colspan="2" class="py-2.5 px-3"></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-100 tabular-nums"><?= number_format($sumVatSales) ?></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-amber-700 tabular-nums"><?= number_format($sumVatPurch) ?></td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 p-3 <?= $netVat>=0?'bg-amber-50 border-amber-200':'bg-emerald-50 border-emerald-200' ?> border rounded-xl flex items-center justify-between">
                    <span class="text-sm font-medium <?= $netVat>=0?'text-amber-700':'text-emerald-700' ?>">
                        <?= $netVat>=0 ? '예상 납부세액' : '예상 환급세액' ?>
                    </span>
                    <span class="text-lg font-bold <?= $netVat>=0?'text-amber-700':'text-emerald-700' ?>">
                        <?= number_format(abs($netVat)) ?>원
                    </span>
                </div>
            </div>

            <!-- 계정과목별 지출 상세 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="w-4 h-4 text-slate-400"></i>
                    계정과목별 지출 상세
                </h3>
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-2 px-3 text-left font-medium text-slate-300">계정과목</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">금액</th>
                            <th class="py-2 px-3 text-right font-medium text-slate-300">비중</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php arsort($expenseByCategory); foreach ($expenseByCategory as $name => $amt): ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-950">
                            <td class="py-2.5 px-3 text-slate-200"><?= $name ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums"><?= number_format($amt) ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-500 tabular-nums"><?= round($amt/$totalExpense*100,1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="border-t-2 border-slate-700 bg-slate-950">
                            <td class="py-2.5 px-3 font-semibold text-slate-100">합계</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-100 tabular-nums"><?= number_format($totalExpense) ?></td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-500">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AI 인사이트 영역 -->
        <div id="aiInsightSection" class="hidden mt-5">
            <div class="bg-slate-900 border border-violet-800/30 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-200 flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-violet-400"></i>
                        AI 재무 인사이트
                    </h3>
                    <span class="text-xs text-slate-500" id="aiInsightTime"></span>
                </div>
                <div id="aiInsightLoading" class="hidden flex items-center gap-3 py-8 justify-center">
                    <svg class="animate-spin w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-sm text-violet-300">AI가 재무 데이터를 분석하고 있습니다...</span>
                </div>
                <div id="aiInsightError" class="hidden p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-300"></div>
                <div id="aiInsightCards" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
            </div>
        </div>

    </main>
</div>

<script>
const AI_REPORT_API = '<?= rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/') ?>/api/ai.php';

async function runAiInsight() {
    const btn = document.getElementById('btnAiInsight');
    const section = document.getElementById('aiInsightSection');
    const loading = document.getElementById('aiInsightLoading');
    const cards = document.getElementById('aiInsightCards');
    const errEl = document.getElementById('aiInsightError');

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> 분석 중...';

    section.classList.remove('hidden');
    loading.classList.remove('hidden');
    cards.innerHTML = '';
    errEl.classList.add('hidden');

    try {
        const res = await fetch(`${AI_REPORT_API}?action=report_insights`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                year: <?= $year ?>,
                month: <?= $month ?>,
                period: '<?= $period ?>'
            })
        });
        const data = await res.json();

        if (data.ok && data.data?.insights) {
            const insights = data.data.insights;
            const icons = {
                'anomaly': 'alert-triangle',
                'trend': 'trending-up',
                'saving': 'piggy-bank',
                'risk': 'shield-alert',
                'recommendation': 'lightbulb',
                'positive': 'thumbs-up',
            };
            const colors = {
                'anomaly': 'text-amber-400 bg-amber-500/10',
                'trend': 'text-blue-400 bg-blue-500/10',
                'saving': 'text-green-400 bg-green-500/10',
                'risk': 'text-red-400 bg-red-500/10',
                'recommendation': 'text-violet-400 bg-violet-500/10',
                'positive': 'text-emerald-400 bg-emerald-500/10',
            };

            cards.innerHTML = insights.map(item => {
                const type = item.type || 'recommendation';
                const icon = icons[type] || 'info';
                const color = colors[type] || 'text-slate-400 bg-slate-500/10';
                const [textColor, bgColor] = color.split(' ');

                return `
                    <div class="p-4 ${bgColor} border border-slate-800 rounded-xl">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full ${bgColor} flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="${icon}" class="w-4 h-4 ${textColor}"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-slate-200 mb-1">${escHtml(item.title || '')}</h4>
                                <p class="text-sm text-slate-400 leading-relaxed">${escHtml(item.content || '')}</p>
                                ${item.action ? `<p class="text-xs ${textColor} mt-2 font-medium">${escHtml(item.action)}</p>` : ''}
                            </div>
                        </div>
                    </div>`;
            }).join('');

            document.getElementById('aiInsightTime').textContent =
                new Date().toLocaleString('ko-KR') + ' 기준';

            lucide.createIcons();
        } else {
            errEl.textContent = 'AI 분석 실패: ' + (data.error?.message || data.message || '알 수 없는 오류');
            errEl.classList.remove('hidden');
        }
    } catch (e) {
        errEl.textContent = '네트워크 오류: ' + e.message;
        errEl.classList.remove('hidden');
    } finally {
        loading.classList.add('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i>AI 분석';
        lucide.createIcons();
    }
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>

<style>
@media print {
    #sidebar, .ml-60 > header, button { display: none !important; }
    #mainContent { margin-left: 0 !important; margin-top: 0 !important; }
    main { padding: 16px !important; }
    .bg-slate-900 { box-shadow: none !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
