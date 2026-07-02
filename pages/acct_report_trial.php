<?php
/**
 * 세무리포트 > 시산표 탭
 * 포함: acct_report.php 쉘에서 include
 */
$pdo = getDBConnection();
require_once __DIR__ . '/../includes/accounting_helpers.php';

$_ptRaw = $_GET['tb_pt'] ?? $_GET['rpt_pt'] ?? 'month';
$periodType = in_array($_ptRaw, ['month','quarter','half','year'], true) ? $_ptRaw : 'month';
$year  = isset($_GET['tb_y']) ? (int)$_GET['tb_y'] : (isset($_GET['rpt_y']) ? (int)$_GET['rpt_y'] : (int)date('Y'));
$month = isset($_GET['tb_m']) ? (int)$_GET['tb_m'] : (isset($_GET['rpt_sub']) && ($periodType === 'month') ? (int)$_GET['rpt_sub'] : (int)date('m'));
$ytdMode = ($_GET['rpt_ytd'] ?? '0') === '1';

switch ($periodType) {
    case 'quarter':
        $q = isset($_GET['tb_q']) ? (int)$_GET['tb_q'] : (isset($_GET['rpt_sub']) ? (int)$_GET['rpt_sub'] : (int)ceil(date('n') / 3));
        $startMonth = ($q - 1) * 3 + 1;
        $endMonth = $q * 3;
        $periodLabel = "{$year}년 {$q}분기";
        break;
    case 'half':
        $h = isset($_GET['tb_h']) ? (int)$_GET['tb_h'] : (isset($_GET['rpt_sub']) ? (int)$_GET['rpt_sub'] : (date('n') <= 6 ? 1 : 2));
        $startMonth = $h === 1 ? 1 : 7;
        $endMonth = $h === 1 ? 6 : 12;
        $periodLabel = "{$year}년 " . ($h === 1 ? '상반기' : '하반기');
        break;
    case 'year':
        $startMonth = 1;
        $endMonth = 12;
        $periodLabel = "{$year}년 연간";
        break;
    default:
        $startMonth = $month;
        $endMonth = $month;
        $periodLabel = "{$year}년 {$month}월";
}

if ($ytdMode && $periodType !== 'year') {
    $dateFrom = sprintf('%04d-01-01', $year);
    $periodLabel = "{$year}년 당기누적 (1~{$endMonth}월)";
} else {
    $dateFrom = sprintf('%04d-%02d-01', $year, $startMonth);
}
$dateTo = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));

// 시산표 데이터 조회
$trialData = [];
$groupTotals = [];
$grandDebit = 0;
$grandCredit = 0;

$grandDrBal = 0;   // 차변잔액 합계
$grandCrBal = 0;   // 대변잔액 합계

if ($pdo) {
    try {
        // 보통예금 반대편 레그를 합성해야 복식부기 대차평균(차변합계=대변합계)이 성립한다.
        $trialData = getUnifiedAccountSummary($pdo, $dateFrom, $dateTo, null, true);

        foreach ($trialData as &$row) {
            $row['group_sort'] = $row['grp_sort'] ?? 0;
            $row['total_debit'] = (int)$row['total_debit'];
            $row['total_credit'] = (int)$row['total_credit'];
            $row['tx_count'] = (int)$row['tx_count'];

            // 잔액은 차변·대변 합계 중 큰 쪽에 표시한다 (한쪽은 항상 0).
            // 이렇게 하면 Σ차변잔액 = Σ대변잔액 이 합계 균형과 동시에 성립한다.
            $net = $row['total_debit'] - $row['total_credit'];
            $row['debit_balance']  = $net > 0 ? $net : 0;
            $row['credit_balance'] = $net < 0 ? -$net : 0;

            $grandDebit += $row['total_debit'];
            $grandCredit += $row['total_credit'];
            $grandDrBal += $row['debit_balance'];
            $grandCrBal += $row['credit_balance'];

            $gKey = $row['parent_code'] ?: '_' . $row['type'];
            if (!isset($groupTotals[$gKey])) {
                $groupTotals[$gKey] = [
                    'name' => $row['group_name'] ?: $row['type'],
                    'type' => $row['type'],
                    'debit' => 0,
                    'credit' => 0,
                    'debit_balance' => 0,
                    'credit_balance' => 0,
                    'sort' => (int)($row['group_sort'] ?? 0),
                ];
            }
            $groupTotals[$gKey]['debit'] += $row['total_debit'];
            $groupTotals[$gKey]['credit'] += $row['total_credit'];
            $groupTotals[$gKey]['debit_balance'] += $row['debit_balance'];
            $groupTotals[$gKey]['credit_balance'] += $row['credit_balance'];
        }
        unset($row);
    } catch (Throwable $e) {
        error_log('trial: query failed: ' . $e->getMessage());
    }
}

$diff = $grandDebit - $grandCredit;
$balDiff = $grandDrBal - $grandCrBal;
$isBalanced = abs($diff) < 1 && abs($balDiff) < 1;

if (!function_exists('pctChange')) {
    function pctChange(int $cur, int $prev): ?float {
        return $prev != 0 ? round(($cur - $prev) / abs($prev) * 100, 1) : null;
    }
}

// 타입 순서 정의
$typeOrder = ['자산' => 1, '부채' => 2, '자본' => 3, '매출' => 4, '매입' => 5, '비용' => 6, '수익' => 7];
?>

<!-- 기간 선택 바 -->
<div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex rounded-lg overflow-hidden border border-slate-700">
            <?php
            $ptOptions = ['month' => '월별', 'quarter' => '분기', 'half' => '반기', 'year' => '연간'];
            foreach ($ptOptions as $k => $v): ?>
            <button onclick="setTbPeriod('<?= $k ?>')"
                    class="px-3 py-2 text-sm font-medium <?= $periodType === $k ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">
                <?= $v ?>
            </button>
            <?php endforeach; ?>
        </div>

        <select id="tbYear" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="goTb()">
            <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?>년</option>
            <?php endfor; ?>
        </select>

        <?php if ($periodType === 'month'): ?>
        <select id="tbMonth" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="goTb()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?>월</option>
            <?php endfor; ?>
        </select>
        <?php elseif ($periodType === 'quarter'): ?>
        <select id="tbQuarter" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="goTb()">
            <?php for ($qi = 1; $qi <= 4; $qi++): ?>
            <option value="<?= $qi ?>" <?= $qi === ($q ?? 1) ? 'selected' : '' ?>><?= $qi ?>분기</option>
            <?php endfor; ?>
        </select>
        <?php elseif ($periodType === 'half'): ?>
        <select id="tbHalf" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="goTb()">
            <option value="1" <?= ($h ?? 1) === 1 ? 'selected' : '' ?>>상반기</option>
            <option value="2" <?= ($h ?? 1) === 2 ? 'selected' : '' ?>>하반기</option>
        </select>
        <?php endif; ?>

        <button onclick="toggleTbYtd()" id="tbYtdBtn"
                class="zm-tab px-3 py-2 text-sm rounded-lg <?= $ytdMode ? 'zm-tab-active' : '' ?>">
            당기누적
        </button>
        <span class="text-sm font-medium text-slate-200 ml-2"><?= $periodLabel ?> 시산표</span>
        <span class="text-xs text-slate-500 ml-1"><?= date('Y.m.d', strtotime($dateFrom)) ?> ~ <?= date('Y.m.d', strtotime($dateTo)) ?></span>

        <div class="ml-auto flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium <?= $isBalanced ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' ?>">
                <i data-lucide="<?= $isBalanced ? 'check-circle' : 'alert-triangle' ?>" class="w-3.5 h-3.5"></i>
                <?= $isBalanced ? '차대 균형' : '차대 불일치 ₩' . number_format(abs($diff)) ?>
            </span>
            <button onclick="exportTbCsv()" class="btn btn-secondary btn-sm">
                <i data-lucide="download" class="w-4 h-4"></i>CSV
            </button>
        </div>
    </div>
</div>

<!-- 요약 카드 -->
<div class="grid grid-cols-3 gap-4 mb-5">
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <p class="text-xs text-slate-400 mb-1">차변 합계</p>
        <p class="text-xl font-bold text-blue-400 tabular-nums">₩<?= number_format($grandDebit) ?></p>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <p class="text-xs text-slate-400 mb-1">대변 합계</p>
        <p class="text-xl font-bold text-red-400 tabular-nums">₩<?= number_format($grandCredit) ?></p>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <p class="text-xs text-slate-400 mb-1">계정과목 수</p>
        <p class="text-xl font-bold text-slate-100 tabular-nums"><?= count($trialData) ?>개</p>
        <p class="text-xs text-slate-500"><?= count($groupTotals) ?>개 그룹</p>
    </div>
</div>

<!-- 시산표 테이블 -->
<div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
            <i data-lucide="scale" class="w-4 h-4 text-primary"></i>
            합계잔액시산표
        </h3>
        <button onclick="toggleAllTbGroups()" class="text-xs text-slate-400 hover:text-slate-200 transition-colors" id="tbToggleBtn">전체 펼치기</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="tbTable">
            <thead class="bg-slate-800/50 text-slate-300">
                <tr>
                    <th class="px-4 py-2 text-center font-medium border-b border-slate-700" colspan="2">차변</th>
                    <th class="px-4 py-2 text-center font-medium border-b border-slate-700">계정과목</th>
                    <th class="px-4 py-2 text-center font-medium border-b border-slate-700" colspan="2">대변</th>
                </tr>
                <tr class="text-xs text-slate-400">
                    <th class="px-4 py-2 text-right font-medium w-36">잔액</th>
                    <th class="px-4 py-2 text-right font-medium w-36">합계</th>
                    <th class="px-4 py-2 text-left font-medium"></th>
                    <th class="px-4 py-2 text-right font-medium w-36">합계</th>
                    <th class="px-4 py-2 text-right font-medium w-36">잔액</th>
                </tr>
            </thead>
            <tbody class="text-slate-300">
                <?php
                $currentType = '';
                $currentGroup = '';

                foreach ($trialData as $row):
                    // 타입 헤더
                    if ($row['type'] !== $currentType):
                        $currentType = $row['type'];
                ?>
                <tr class="bg-slate-800/70">
                    <td class="px-4 py-2.5 font-bold text-slate-200 text-center" colspan="5"><?= $currentType ?></td>
                </tr>
                <?php
                    endif;

                    // 그룹 헤더
                    $gKey = $row['parent_code'] ?: '_' . $row['type'];
                    if ($gKey !== $currentGroup && $row['group_name']):
                        $currentGroup = $gKey;
                        $gt = $groupTotals[$gKey] ?? null;
                        if ($gt):
                ?>
                <tr class="border-b border-slate-800 cursor-pointer hover:bg-slate-800/50" onclick="toggleTbGroup('<?= $gKey ?>')">
                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-blue-300"><?= $gt['debit_balance'] > 0 ? number_format($gt['debit_balance']) : '' ?></td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-blue-400"><?= $gt['debit'] > 0 ? number_format($gt['debit']) : '' ?></td>
                    <td class="py-2.5 px-4 font-semibold text-slate-200">
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 inline-block transition-transform tb-chevron align-middle mr-1" data-group="<?= $gKey ?>"></i><?= htmlspecialchars($gt['name']) ?>
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-red-400"><?= $gt['credit'] > 0 ? number_format($gt['credit']) : '' ?></td>
                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-red-300"><?= $gt['credit_balance'] > 0 ? number_format($gt['credit_balance']) : '' ?></td>
                </tr>
                <?php
                        endif;
                    endif;
                ?>
                <tr class="border-b border-slate-800 hover:bg-slate-800/30 tb-detail-row hidden" data-tb-group="<?= $gKey ?>">
                    <td class="px-4 py-2.5 text-right tabular-nums <?= $row['debit_balance'] > 0 ? 'text-blue-300' : 'text-slate-600' ?>"><?= $row['debit_balance'] > 0 ? number_format($row['debit_balance']) : '' ?></td>
                    <td class="px-4 py-2.5 text-right tabular-nums <?= $row['total_debit'] > 0 ? 'text-blue-400' : 'text-slate-600' ?>"><?= $row['total_debit'] > 0 ? number_format($row['total_debit']) : '' ?></td>
                    <td class="py-2.5 pl-10 pr-4">
                        <span class="tabular-nums text-slate-500 text-xs mr-2"><?= htmlspecialchars($row['code']) ?></span><?= htmlspecialchars($row['name']) ?>
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums <?= $row['total_credit'] > 0 ? 'text-red-400' : 'text-slate-600' ?>"><?= $row['total_credit'] > 0 ? number_format($row['total_credit']) : '' ?></td>
                    <td class="px-4 py-2.5 text-right tabular-nums <?= $row['credit_balance'] > 0 ? 'text-red-300' : 'text-slate-600' ?>"><?= $row['credit_balance'] > 0 ? number_format($row['credit_balance']) : '' ?></td>
                </tr>
                <?php endforeach; ?>

                <!-- 합계 -->
                <tr class="border-t-2 border-slate-700 bg-slate-800/50 font-bold text-slate-100">
                    <td class="px-4 py-3 text-right tabular-nums text-blue-300"><?= number_format($grandDrBal) ?></td>
                    <td class="px-4 py-3 text-right tabular-nums text-blue-400"><?= number_format($grandDebit) ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($isBalanced): ?>
                            <span class="text-emerald-400">합계 (대차 일치)</span>
                        <?php else: ?>
                            <span class="text-red-400">합계 (불일치 <?= number_format(abs($diff ?: $balDiff)) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-red-400"><?= number_format($grandCredit) ?></td>
                    <td class="px-4 py-3 text-right tabular-nums text-red-300"><?= number_format($grandCrBal) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let tbGroupsExpanded = false;
function toggleTbGroup(groupId) {
    const rows = document.querySelectorAll(`tr[data-tb-group="${groupId}"].tb-detail-row`);
    const chevrons = document.querySelectorAll(`i.tb-chevron[data-group="${groupId}"]`);
    const isHidden = rows.length > 0 && rows[0].classList.contains('hidden');
    rows.forEach(r => r.classList.toggle('hidden', !isHidden));
    chevrons.forEach(c => c.style.transform = isHidden ? 'rotate(90deg)' : '');
}
function toggleAllTbGroups() {
    tbGroupsExpanded = !tbGroupsExpanded;
    document.querySelectorAll('.tb-detail-row').forEach(r => r.classList.toggle('hidden', !tbGroupsExpanded));
    document.querySelectorAll('i.tb-chevron').forEach(c => c.style.transform = tbGroupsExpanded ? 'rotate(90deg)' : '');
    const btn = document.getElementById('tbToggleBtn');
    if (btn) btn.textContent = tbGroupsExpanded ? '전체 접기' : '전체 펼치기';
}

function tbBuildUrl(pt, y, sub) {
    let url = '?tab=trial&tb_pt=' + pt + '&tb_y=' + y + '&rpt_pt=' + pt + '&rpt_y=' + y + '&rpt_sub=' + sub;
    if (pt === 'month') url += '&tb_m=' + sub;
    else if (pt === 'quarter') url += '&tb_q=' + sub;
    else if (pt === 'half') url += '&tb_h=' + sub;
    const ytd = document.getElementById('tbYtdBtn')?.classList.contains('zm-tab-active') ? '1' : '0';
    if (ytd === '1') url += '&rpt_ytd=1';
    return url;
}
function setTbPeriod(pt) {
    const y = document.getElementById('tbYear').value;
    let sub = 1;
    if (pt === 'month') sub = <?= $month ?>;
    else if (pt === 'year') sub = 0;
    location.href = tbBuildUrl(pt, y, sub);
}
function goTb() {
    const y = document.getElementById('tbYear').value;
    const pt = <?= json_encode($periodType, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let sub = 0;
    if (pt === 'month') sub = document.getElementById('tbMonth').value;
    else if (pt === 'quarter') sub = document.getElementById('tbQuarter').value;
    else if (pt === 'half') sub = document.getElementById('tbHalf').value;
    location.href = tbBuildUrl(pt, y, sub);
}
function toggleTbYtd() {
    const params = new URLSearchParams(window.location.search);
    const isYtd = params.get('rpt_ytd') === '1';
    if (isYtd) params.delete('rpt_ytd');
    else params.set('rpt_ytd', '1');
    window.location.search = params.toString();
}

// 탭 동기화: 현재 기간을 sessionStorage에 저장
if (window.AcctReportPeriod) {
    const sub = <?= $periodType === 'month' ? $month : ($periodType === 'quarter' ? ($q ?? 1) : ($periodType === 'half' ? ($h ?? 1) : 0)) ?>;
    AcctReportPeriod.save(<?= json_encode($periodType, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= (int)$year ?>, sub, <?= $ytdMode ? 1 : 0 ?>);
}

function exportTbCsv() {
    const table = document.getElementById('tbTable');
    if (!table) return;
    let csv = '﻿';
    table.querySelectorAll('tr').forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        cells.forEach(cell => rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"'));
        csv += rowData.join(',') + '\n';
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = '시산표_<?= $periodLabel ?>.csv';
    link.click();
}

if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
