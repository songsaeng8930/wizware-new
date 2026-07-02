<!-- ===== Tier 3: 부서 현황 ===== -->
<div class="<?= $bottomSpan ?>">
    <div class="<?= $cardClsT3 ?> h-full flex flex-col">

        <!-- 헤더 -->
        <div class="flex items-baseline justify-between pb-3 mb-1 border-b border-slate-800/60">
            <h3 class="text-sm font-bold text-slate-200"><?= $hasDept ? esc($deptName ?? '우리 부서') : '우리 부서' ?></h3>
            <?php if ($hasDept): ?>
                <a href="<?= $basePath ?>/pages/organization.php" class="text-xs font-semibold text-slate-400 hover:text-slate-200 hover:underline">조직도 →</a>
            <?php endif; ?>
        </div>

        <?php if (!$hasDept): ?>
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-sm text-slate-300">소속된 조직이 없어요</p>
                    <p class="text-xs text-slate-500 mt-1">인사 담당자에게 부서 배정을 요청하세요</p>
                </div>
            </div>
        <?php else: ?>
            <?php
            $totalEmp   = max(1, $orgStats['total']);
            $inOffice   = $orgStats['inOffice'];
            $outside    = $orgStats['outside'];
            $unconfirmed = max(0, $totalEmp - $inOffice - $outside);
            $inPct  = round($inOffice / $totalEmp * 100);
            $outPct = round($outside  / $totalEmp * 100);
            ?>

            <!-- 출근 현황 -->
            <div class="mb-4">
                <p class="text-xs text-slate-500 mb-2">오늘 출근 현황</p>
                <div class="flex h-2 rounded-full overflow-hidden bg-slate-800 mb-2.5">
                    <?php if ($inPct > 0): ?>
                    <div class="bg-emerald-400 h-full" style="width: <?= $inPct ?>%"></div>
                    <?php endif; ?>
                    <?php if ($outPct > 0): ?>
                    <div class="bg-slate-500 h-full" style="width: <?= $outPct ?>%"></div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-4 text-[11px]">
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span class="text-slate-500">출근</span>
                        <span class="font-bold text-slate-300"><?= $inOffice ?></span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span class="text-slate-500">외근</span>
                        <span class="font-bold text-slate-300"><?= $outside ?></span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-700"></span>
                        <span class="text-slate-500">미확인</span>
                        <span class="font-bold text-slate-600"><?= $unconfirmed ?></span>
                    </span>
                </div>
            </div>

            <!-- 입사기념일 -->
            <div class="mt-auto">
                <p class="text-xs font-semibold text-slate-400 mb-2"><?= date('n') ?>월 입사기념일</p>
                <?php if (empty($monthAnniversaries)): ?>
                    <p class="text-xs text-slate-600">이달에는 없어요</p>
                <?php else: ?>
                    <div class="space-y-1.5">
                        <?php foreach ($monthAnniversaries as $a): ?>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 font-bold text-[11px]"><?= (int)$a['years'] ?>년</span>
                            <span class="text-slate-200 font-medium truncate"><?= esc($a['name']) ?></span>
                            <span class="text-slate-500"><?= esc($a['position'] ?? '') ?></span>
                            <span class="text-slate-600 ml-auto tabular-nums"><?= date('n.j', strtotime($a['hire_date'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
