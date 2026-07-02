<?php
/**
 * 세무리포트 > 재무상태표 탭
 * 포함: acct_report.php 쉘에서 include
 * 특정 시점 기준 자산 = 부채 + 자본 구조 표시
 */
$pdo = getDBConnection();
require_once __DIR__ . '/../includes/accounting_helpers.php';

$bsYear  = isset($_GET['bs_y']) ? (int)$_GET['bs_y'] : (isset($_GET['rpt_y']) ? (int)$_GET['rpt_y'] : (int)date('Y'));

$bsMonth = (int)date('m');
if (isset($_GET['bs_m'])) {
    $bsMonth = (int)$_GET['bs_m'];
} elseif (isset($_GET['rpt_pt']) && isset($_GET['rpt_sub'])) {
    $rptPt = $_GET['rpt_pt'];
    $rptSub = (int)$_GET['rpt_sub'];
    if ($rptPt === 'month' && $rptSub >= 1 && $rptSub <= 12) $bsMonth = $rptSub;
    elseif ($rptPt === 'quarter' && $rptSub >= 1 && $rptSub <= 4) $bsMonth = $rptSub * 3;
    elseif ($rptPt === 'half' && $rptSub >= 1 && $rptSub <= 2) $bsMonth = $rptSub * 6;
    elseif ($rptPt === 'year') $bsMonth = 12;
}
$bsDate  = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $bsYear, $bsMonth)));
$bsLabel = "{$bsYear}년 {$bsMonth}월 말 기준";

$prevYear  = $bsMonth === 12 ? $bsYear : $bsYear - 1;
$prevMonth = $bsMonth === 12 ? 11 : 12;
$prevDate  = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $prevYear, $prevMonth)));
$prevLabel = "{$prevYear}년 {$prevMonth}월 말";

$assets = [];
$liabilities = [];
$equity = [];
$totalAsset = 0;
$totalLiability = 0;
$totalEquity = 0;
$prevTotalAsset = 0;
$prevTotalLiability = 0;
$prevTotalEquity = 0;
$netIncome = 0;
$prevNetIncome = 0;

if ($pdo) {
    try {
        $epochStart = '2000-01-01';

        // 당기 데이터 (통합 쿼리: 통장 + 분할 + 조정분개 + 보통예금 반대편 레그)
        // 보통예금 레그를 포함해야 자산에 실제 현금이 잡히고 자산 = 부채 + 자본 + 순이익이 성립한다.
        $curData = getUnifiedAccountSummary($pdo, $epochStart, $bsDate, null, true);

        // 전기 데이터
        $prevData = getUnifiedAccountSummary($pdo, $epochStart, $prevDate, null, true);
        $prevMap = [];
        foreach ($prevData as $pr) {
            $prevMap[$pr['code']] = $pr;
        }

        // 손익 계산 (수익/매출 - 비용/매입)
        foreach ($curData as $row) {
            $debit  = (int)$row['total_debit'];
            $credit = (int)$row['total_credit'];
            if (in_array($row['type'], ['매출', '수익'])) {
                $netIncome += ($credit - $debit);
            } elseif (in_array($row['type'], ['매입', '비용'])) {
                $netIncome -= ($debit - $credit);
            }
        }
        foreach ($prevData as $row) {
            $debit  = (int)$row['total_debit'];
            $credit = (int)$row['total_credit'];
            if (in_array($row['type'], ['매출', '수익'])) {
                $prevNetIncome += ($credit - $debit);
            } elseif (in_array($row['type'], ['매입', '비용'])) {
                $prevNetIncome -= ($debit - $credit);
            }
        }

        foreach ($curData as $row) {
            $debit  = (int)$row['total_debit'];
            $credit = (int)$row['total_credit'];
            $isDebitNormal = in_array($row['type'], ['자산', '비용', '매입']);
            $balance = $isDebitNormal ? ($debit - $credit) : ($credit - $debit);

            $prevRow = $prevMap[$row['code']] ?? null;
            $prevBalance = 0;
            if ($prevRow) {
                $pd = (int)$prevRow['total_debit'];
                $pc = (int)$prevRow['total_credit'];
                $prevBalance = $isDebitNormal ? ($pd - $pc) : ($pc - $pd);
            }

            if ($balance == 0 && $prevBalance == 0) continue;

            $item = [
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'parent_code' => $row['parent_code'],
                'group_name' => $row['group_name'],
                'grp_sort' => (int)$row['grp_sort'],
                'balance' => $balance,
                'prev_balance' => $prevBalance,
            ];

            if ($row['type'] === '자산') {
                $assets[] = $item;
                $totalAsset += $balance;
            } elseif ($row['type'] === '부채') {
                $liabilities[] = $item;
                $totalLiability += $balance;
            } elseif ($row['type'] === '자본') {
                $equity[] = $item;
                $totalEquity += $balance;
            }
        }

        // 전기 합계 (prev에만 있는 항목 포함)
        foreach ($prevData as $row) {
            $pd = (int)$row['total_debit'];
            $pc = (int)$row['total_credit'];
            $isDebitNormal = in_array($row['type'], ['자산', '비용', '매입']);
            $prevBal = $isDebitNormal ? ($pd - $pc) : ($pc - $pd);
            if ($row['type'] === '자산') $prevTotalAsset += $prevBal;
            elseif ($row['type'] === '부채') $prevTotalLiability += $prevBal;
            elseif ($row['type'] === '자본') $prevTotalEquity += $prevBal;
        }

    } catch (\Throwable $e) {
        error_log('balance_sheet: ' . $e->getMessage());
    }
}

$totalLiabilityEquity = $totalLiability + $totalEquity + $netIncome;
$prevTotalLiabilityEquity = $prevTotalLiability + $prevTotalEquity + $prevNetIncome;
$isBalanced = abs($totalAsset - $totalLiabilityEquity) < 1;

function bsGroupItems(array $items): array {
    $groups = [];
    foreach ($items as $item) {
        $gKey = $item['parent_code'] ?: '_direct_' . $item['code'];
        if (!isset($groups[$gKey])) {
            $groups[$gKey] = [
                'name' => $item['group_name'] ?: $item['name'],
                'sort' => $item['grp_sort'],
                'items' => [],
                'total' => 0,
                'prev_total' => 0,
            ];
        }
        $groups[$gKey]['items'][] = $item;
        $groups[$gKey]['total'] += $item['balance'];
        $groups[$gKey]['prev_total'] += $item['prev_balance'];
    }
    uasort($groups, function($a, $b) { return $a['sort'] - $b['sort']; });
    return $groups;
}

$assetGroups = bsGroupItems($assets);
$liabilityGroups = bsGroupItems($liabilities);
$equityGroups = bsGroupItems($equity);

function bsFmt(int $val): string {
    if ($val == 0) return '-';
    return number_format(abs($val)) . '원';
}

function bsChg(int $cur, int $prev): string {
    $diff = $cur - $prev;
    if ($diff == 0) return '-';
    $cls = $diff > 0 ? 'text-emerald-400' : 'text-rose-400';
    $sign = $diff > 0 ? '+' : '';
    return '<span class="' . $cls . '">' . $sign . number_format($diff) . '</span>';
}
?>

<!-- 기간 선택 -->
<div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5">
    <div class="flex flex-wrap items-center gap-3">
        <label class="text-sm text-slate-400">기준일</label>
        <select id="bsYear" onchange="setBsPeriod()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
            <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $bsYear ? 'selected' : '' ?>><?= $y ?>년</option>
            <?php endfor; ?>
        </select>
        <select id="bsMonth" onchange="setBsPeriod()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $bsMonth ? 'selected' : '' ?>><?= $m ?>월</option>
            <?php endfor; ?>
        </select>
        <span class="text-sm text-slate-500"><?= htmlspecialchars($bsLabel) ?></span>
        <div class="flex-1"></div>
        <?php if ($isBalanced): ?>
        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-emerald-500/10 text-emerald-400 rounded-full">
            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>대차 균형
        </span>
        <?php else: ?>
        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-rose-500/10 text-rose-400 rounded-full">
            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>대차 불일치 (<?= number_format(abs($totalAsset - $totalLiabilityEquity)) ?>원)
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- 요약 카드 -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <p class="text-xs text-slate-500 mb-1">자산 총계</p>
        <p class="text-lg font-bold text-blue-400 tabular-nums"><?= bsFmt($totalAsset) ?></p>
        <p class="text-xs text-slate-500 mt-1">전기 <?= bsFmt($prevTotalAsset) ?></p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <p class="text-xs text-slate-500 mb-1">부채 총계</p>
        <p class="text-lg font-bold text-rose-400 tabular-nums"><?= bsFmt($totalLiability) ?></p>
        <p class="text-xs text-slate-500 mt-1">전기 <?= bsFmt($prevTotalLiability) ?></p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <p class="text-xs text-slate-500 mb-1">자본 총계 (이익잉여금 포함)</p>
        <p class="text-lg font-bold text-emerald-400 tabular-nums"><?= bsFmt($totalEquity + $netIncome) ?></p>
        <p class="text-xs text-slate-500 mt-1">전기 <?= bsFmt($prevTotalEquity + $prevNetIncome) ?></p>
    </div>
</div>

<!-- 재무상태표 본문 -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- 좌측: 자산 -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800">
            <h4 class="text-sm font-bold text-blue-400">자산</h4>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-700 text-slate-500">
                    <th class="py-2 px-4 text-left font-medium text-xs">계정과목</th>
                    <th class="py-2 px-4 text-right font-medium text-xs">당기</th>
                    <th class="py-2 px-4 text-right font-medium text-xs">전기</th>
                    <th class="py-2 px-4 text-right font-medium text-xs">증감</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assetGroups)): ?>
                <tr><td colspan="4" class="py-8 text-center text-slate-500 text-sm">자산 데이터가 없습니다.</td></tr>
                <?php else: ?>
                <?php foreach ($assetGroups as $gKey => $group): ?>
                    <?php if (count($group['items']) > 1): ?>
                    <tr class="bg-slate-800/30 cursor-pointer" onclick="toggleBsGroup('<?= htmlspecialchars($gKey) ?>')">
                        <td class="py-2.5 px-4 text-slate-200 font-medium text-xs">
                            <span class="inline-block w-3 mr-1 bs-chevron-<?= htmlspecialchars($gKey) ?> transition-transform" style="font-size:10px">&#9654;</span>
                            <?= htmlspecialchars($group['name']) ?>
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-200 font-medium text-xs"><?= bsFmt($group['total']) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($group['prev_total']) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($group['total'], $group['prev_total']) ?></td>
                    </tr>
                    <?php foreach ($group['items'] as $item): ?>
                    <tr class="border-b border-slate-800/50 bs-item-<?= htmlspecialchars($gKey) ?> hidden">
                        <td class="py-2 px-4 pl-8 text-slate-300 text-xs">
                            <span class="text-slate-600 font-mono mr-1"><?= htmlspecialchars($item['code']) ?></span>
                            <?= htmlspecialchars($item['name']) ?>
                        </td>
                        <td class="py-2 px-4 text-right tabular-nums text-slate-200 text-xs"><?= bsFmt($item['balance']) ?></td>
                        <td class="py-2 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($item['prev_balance']) ?></td>
                        <td class="py-2 px-4 text-right tabular-nums text-xs"><?= bsChg($item['balance'], $item['prev_balance']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <?php $item = $group['items'][0]; ?>
                    <tr class="border-b border-slate-800/50">
                        <td class="py-2.5 px-4 text-slate-200 text-xs">
                            <span class="text-slate-600 font-mono mr-1"><?= htmlspecialchars($item['code']) ?></span>
                            <?= htmlspecialchars($item['name']) ?>
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-200 text-xs"><?= bsFmt($item['balance']) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($item['prev_balance']) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($item['balance'], $item['prev_balance']) ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-700 bg-slate-800/50">
                    <td class="py-3 px-4 text-sm font-bold text-blue-400">자산 총계</td>
                    <td class="py-3 px-4 text-right tabular-nums text-sm font-bold text-blue-400"><?= bsFmt($totalAsset) ?></td>
                    <td class="py-3 px-4 text-right tabular-nums text-sm text-slate-400"><?= bsFmt($prevTotalAsset) ?></td>
                    <td class="py-3 px-4 text-right tabular-nums text-sm"><?= bsChg($totalAsset, $prevTotalAsset) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- 우측: 부채 + 자본 -->
    <div class="space-y-4">
        <!-- 부채 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h4 class="text-sm font-bold text-rose-400">부채</h4>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-700 text-slate-500">
                        <th class="py-2 px-4 text-left font-medium text-xs">계정과목</th>
                        <th class="py-2 px-4 text-right font-medium text-xs">당기</th>
                        <th class="py-2 px-4 text-right font-medium text-xs">전기</th>
                        <th class="py-2 px-4 text-right font-medium text-xs">증감</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($liabilityGroups)): ?>
                    <tr><td colspan="4" class="py-6 text-center text-slate-500 text-sm">부채 데이터가 없습니다.</td></tr>
                    <?php else: ?>
                    <?php foreach ($liabilityGroups as $gKey => $group): ?>
                        <?php if (count($group['items']) > 1): ?>
                        <tr class="bg-slate-800/30 cursor-pointer" onclick="toggleBsGroup('<?= htmlspecialchars($gKey) ?>')">
                            <td class="py-2.5 px-4 text-slate-200 font-medium text-xs">
                                <span class="inline-block w-3 mr-1 bs-chevron-<?= htmlspecialchars($gKey) ?> transition-transform" style="font-size:10px">&#9654;</span>
                                <?= htmlspecialchars($group['name']) ?>
                            </td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-200 font-medium text-xs"><?= bsFmt($group['total']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($group['prev_total']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($group['total'], $group['prev_total']) ?></td>
                        </tr>
                        <?php foreach ($group['items'] as $item): ?>
                        <tr class="border-b border-slate-800/50 bs-item-<?= htmlspecialchars($gKey) ?> hidden">
                            <td class="py-2 px-4 pl-8 text-slate-300 text-xs">
                                <span class="text-slate-600 font-mono mr-1"><?= htmlspecialchars($item['code']) ?></span>
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td class="py-2 px-4 text-right tabular-nums text-slate-200 text-xs"><?= bsFmt($item['balance']) ?></td>
                            <td class="py-2 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($item['prev_balance']) ?></td>
                            <td class="py-2 px-4 text-right tabular-nums text-xs"><?= bsChg($item['balance'], $item['prev_balance']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <?php $item = $group['items'][0]; ?>
                        <tr class="border-b border-slate-800/50">
                            <td class="py-2.5 px-4 text-slate-200 text-xs">
                                <span class="text-slate-600 font-mono mr-1"><?= htmlspecialchars($item['code']) ?></span>
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-200 text-xs"><?= bsFmt($item['balance']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($item['prev_balance']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($item['balance'], $item['prev_balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-800/50">
                        <td class="py-3 px-4 text-sm font-bold text-rose-400">부채 소계</td>
                        <td class="py-3 px-4 text-right tabular-nums text-sm font-bold text-rose-400"><?= bsFmt($totalLiability) ?></td>
                        <td class="py-3 px-4 text-right tabular-nums text-sm text-slate-400"><?= bsFmt($prevTotalLiability) ?></td>
                        <td class="py-3 px-4 text-right tabular-nums text-sm"><?= bsChg($totalLiability, $prevTotalLiability) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 자본 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h4 class="text-sm font-bold text-emerald-400">자본</h4>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-700 text-slate-500">
                        <th class="py-2 px-4 text-left font-medium text-xs">계정과목</th>
                        <th class="py-2 px-4 text-right font-medium text-xs">당기</th>
                        <th class="py-2 px-4 text-right font-medium text-xs">전기</th>
                        <th class="py-2 px-4 text-right font-medium text-xs">증감</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($equityGroups)): ?>
                    <tr><td colspan="4" class="py-4 text-center text-slate-500 text-sm">자본 데이터가 없습니다.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($equityGroups as $gKey => $group): ?>
                        <?php if (count($group['items']) > 1): ?>
                        <tr class="bg-slate-800/30 cursor-pointer" onclick="toggleBsGroup('<?= htmlspecialchars($gKey) ?>')">
                            <td class="py-2.5 px-4 text-slate-200 font-medium text-xs">
                                <span class="inline-block w-3 mr-1 bs-chevron-<?= htmlspecialchars($gKey) ?> transition-transform" style="font-size:10px">&#9654;</span>
                                <?= htmlspecialchars($group['name']) ?>
                            </td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-200 font-medium text-xs"><?= bsFmt($group['total']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($group['prev_total']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($group['total'], $group['prev_total']) ?></td>
                        </tr>
                        <?php foreach ($group['items'] as $item): ?>
                        <tr class="border-b border-slate-800/50 bs-item-<?= htmlspecialchars($gKey) ?> hidden">
                            <td class="py-2 px-4 pl-8 text-slate-300 text-xs">
                                <span class="text-slate-600 font-mono mr-1"><?= htmlspecialchars($item['code']) ?></span>
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td class="py-2 px-4 text-right tabular-nums text-slate-200 text-xs"><?= bsFmt($item['balance']) ?></td>
                            <td class="py-2 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($item['prev_balance']) ?></td>
                            <td class="py-2 px-4 text-right tabular-nums text-xs"><?= bsChg($item['balance'], $item['prev_balance']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <?php $item = $group['items'][0]; ?>
                        <tr class="border-b border-slate-800/50">
                            <td class="py-2.5 px-4 text-slate-200 text-xs">
                                <span class="text-slate-600 font-mono mr-1"><?= htmlspecialchars($item['code']) ?></span>
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-200 text-xs"><?= bsFmt($item['balance']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($item['prev_balance']) ?></td>
                            <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($item['balance'], $item['prev_balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <!-- 이익잉여금 (당기순이익) -->
                    <tr class="border-b border-slate-800/50 bg-amber-500/5">
                        <td class="py-2.5 px-4 text-amber-400 text-xs font-medium">당기순이익 (이익잉여금)</td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-amber-400 text-xs font-medium"><?= bsFmt($netIncome) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-400 text-xs"><?= bsFmt($prevNetIncome) ?></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-xs"><?= bsChg($netIncome, $prevNetIncome) ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-800/50">
                        <td class="py-3 px-4 text-sm font-bold text-emerald-400">자본 소계</td>
                        <td class="py-3 px-4 text-right tabular-nums text-sm font-bold text-emerald-400"><?= bsFmt($totalEquity + $netIncome) ?></td>
                        <td class="py-3 px-4 text-right tabular-nums text-sm text-slate-400"><?= bsFmt($prevTotalEquity + $prevNetIncome) ?></td>
                        <td class="py-3 px-4 text-right tabular-nums text-sm"><?= bsChg($totalEquity + $netIncome, $prevTotalEquity + $prevNetIncome) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 부채+자본 합계 -->
        <div class="bg-slate-900 rounded-xl border-2 <?= $isBalanced ? 'border-emerald-800/50' : 'border-rose-800/50' ?> p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold <?= $isBalanced ? 'text-emerald-400' : 'text-rose-400' ?>">부채 + 자본 합계</span>
                <span class="text-lg font-bold tabular-nums <?= $isBalanced ? 'text-emerald-400' : 'text-rose-400' ?>"><?= bsFmt($totalLiabilityEquity) ?></span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <span class="text-xs text-slate-500">전기</span>
                <span class="text-xs text-slate-400 tabular-nums"><?= bsFmt($prevTotalLiabilityEquity) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- 등식 검증 -->
<div class="mt-4 bg-slate-900 rounded-xl border border-slate-800 p-4">
    <div class="flex items-center justify-center gap-4 text-sm">
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-1">자산</p>
            <p class="font-bold text-blue-400 tabular-nums"><?= number_format($totalAsset) ?>원</p>
        </div>
        <span class="text-lg text-slate-600">=</span>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-1">부채</p>
            <p class="font-bold text-rose-400 tabular-nums"><?= number_format($totalLiability) ?>원</p>
        </div>
        <span class="text-lg text-slate-600">+</span>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-1">자본</p>
            <p class="font-bold text-emerald-400 tabular-nums"><?= number_format($totalEquity + $netIncome) ?>원</p>
        </div>
        <div class="ml-4">
            <?php if ($isBalanced): ?>
            <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-emerald-500/10 text-emerald-400 rounded-full">
                <i data-lucide="check-circle" class="w-4 h-4"></i>균형
            </span>
            <?php else: ?>
            <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-rose-500/10 text-rose-400 rounded-full">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>불일치 <?= number_format(abs($totalAsset - $totalLiabilityEquity)) ?>원
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function setBsPeriod() {
    var y = document.getElementById('bsYear').value;
    var m = document.getElementById('bsMonth').value;
    if (window.AcctReportPeriod) AcctReportPeriod.save('month', parseInt(y), parseInt(m));
    var params = new URLSearchParams(window.location.search);
    params.set('tab', 'balance');
    params.set('bs_y', y);
    params.set('bs_m', m);
    params.set('rpt_pt', 'month');
    params.set('rpt_y', y);
    params.set('rpt_sub', m);
    window.location.search = params.toString();
}

function toggleBsGroup(gKey) {
    var items = document.querySelectorAll('.bs-item-' + CSS.escape(gKey));
    var chevrons = document.querySelectorAll('.bs-chevron-' + CSS.escape(gKey));
    var isHidden = items.length > 0 && items[0].classList.contains('hidden');
    items.forEach(function(el) { el.classList.toggle('hidden', !isHidden); });
    chevrons.forEach(function(el) { el.style.transform = isHidden ? 'rotate(90deg)' : ''; });
}

if (window.AcctReportPeriod) AcctReportPeriod.save('month', <?= $bsYear ?>, <?= $bsMonth ?>);
</script>
