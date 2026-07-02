<!-- ===== KPI: 가로 4단 스트립 ===== -->
<?php
$weekDiff    = round($weekHours - $lastWeekHours, 1);
$weekDiffAbs = abs($weekDiff);
$weekArrow   = $weekDiff > 0 ? '↑' : ($weekDiff < 0 ? '↓' : '');
$weekDiffCls = $weekDiff > 0 ? 'text-emerald-400' : ($weekDiff < 0 ? 'text-amber-400' : 'text-slate-500');

$evtDiff    = $todayEventCount - $yesterdayEvents;
$evtArrow   = $evtDiff > 0 ? '↑' : ($evtDiff < 0 ? '↓' : '');
$evtDiffAbs = abs($evtDiff);
$evtDiffCls = 'text-slate-500';
?>
<section class="grid grid-cols-4 gap-3 mb-4">
    <!-- 결재 대기 -->
    <a href="<?= $basePath ?>/pages/approval_pending.php" class="<?= $cardClsT2Interactive ?>">
        <p class="text-[11px] font-medium text-slate-500 tracking-wide">내 결재 대기</p>
        <p class="text-2xl text-slate-100 zm-stat mt-1.5 tabular-nums"><?= $pendingForMeCount ?><span class="text-xs font-normal text-slate-500 ml-0.5">건</span></p>
        <p class="text-[11px] text-slate-600 mt-1">기안 <?= $myApprovalStatus['진행'] ?> · 반려 <?= $myApprovalStatus['반려'] ?></p>
    </a>

    <!-- 연차 잔여 -->
    <a href="<?= $basePath ?>/pages/<?= ($isAdminRole || $isManagerRole) ? 'labor.php?tab=annual' : 'attendance.php' ?>" class="<?= $cardClsT2Interactive ?>">
        <p class="text-[11px] font-medium text-slate-500 tracking-wide">연차 잔여</p>
        <div class="flex items-baseline gap-1.5 mt-1.5">
            <p class="text-2xl text-slate-100 zm-stat tabular-nums"><?= $fmtNum($annualRemain) ?><span class="text-xs font-normal text-slate-500 ml-0.5">일</span></p>
            <span class="text-[11px] text-slate-600"><?= $fmtNum($annualUsed) ?>/<?= $fmtNum($annualTotal) ?></span>
        </div>
        <div class="mt-2 h-1 bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-emerald-400 rounded-full transition-all" style="width: <?= $annualPct ?>%"></div>
        </div>
    </a>

    <!-- 이번 주 근무 -->
    <div class="<?= $cardClsT2 ?>">
        <p class="text-[11px] font-medium text-slate-500 tracking-wide">이번 주 근무</p>
        <div class="flex items-baseline gap-1.5 mt-1.5">
            <p class="text-2xl text-slate-100 zm-stat tabular-nums"><?= $fmtNum($weekHours) ?><span class="text-xs font-normal text-slate-500 ml-0.5">/ <?= $weekTarget ?>h</span></p>
        </div>
        <div class="flex items-center justify-between mt-2">
            <div class="flex-1 h-1 bg-slate-800 rounded-full overflow-hidden mr-2">
                <div class="h-full bg-primary rounded-full transition-all" style="width: <?= $weekPct ?>%"></div>
            </div>
            <span class="text-[11px] <?= $weekDiffCls ?> tabular-nums flex-shrink-0">
                <?php if ($weekDiff == 0): ?>
                    ±0
                <?php else: ?>
                    <?= $weekArrow ?><?= $fmtNum($weekDiffAbs) ?>h
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- 오늘 일정 -->
    <a href="<?= $basePath ?>/pages/schedule.php" class="<?= $cardClsT2Interactive ?>">
        <p class="text-[11px] font-medium text-slate-500 tracking-wide">오늘 일정</p>
        <p class="text-2xl text-slate-100 zm-stat mt-1.5 tabular-nums"><?= $todayEventCount ?><span class="text-xs font-normal text-slate-500 ml-0.5">건</span></p>
        <p class="text-[11px] <?= $evtDiffCls ?> mt-1 tabular-nums">
            <?php if ($evtDiff == 0): ?>
                어제와 동일
            <?php else: ?>
                전일 대비 <?= $evtArrow ?> <?= $evtDiffAbs ?>건
            <?php endif; ?>
        </p>
    </a>
</section>
