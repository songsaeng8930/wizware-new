<!-- ===== G. 근태 & 연차 ===== -->
<div class="<?= $bottomSpan ?>">
    <div class="<?= $cardCls ?> h-full">
        <div class="flex items-baseline justify-between mb-4">
            <h3 class="text-base font-bold text-slate-100">근태 & 연차</h3>
            <a href="<?= $basePath ?>/pages/attendance.php" class="text-sm font-semibold text-slate-400 hover:text-slate-200 hover:underline">기록 →</a>
        </div>

        <div class="mb-3 p-3.5 bg-slate-950 rounded-xl">
            <div class="flex items-baseline justify-between mb-2">
                <p class="text-sm font-bold text-slate-200">이번 주 근무</p>
                <span class="text-sm font-bold text-slate-100"><?= $fmtNum($weekHours) ?>h / <?= $weekTarget ?>h</span>
            </div>
            <div class="h-2 bg-slate-900 rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: <?= $weekPct ?>%"></div>
            </div>
            <p class="text-xs text-slate-400 mt-1.5"><?= $weekPct >= 100 ? '목표 달성' : '남은 근무 ' . $fmtNum(max(0, $weekTarget - $weekHours)) . 'h' ?></p>
        </div>

        <div class="mb-4 p-3.5 bg-slate-950 rounded-xl">
            <div class="flex items-baseline justify-between mb-2">
                <p class="text-sm font-bold text-slate-200">연차 사용</p>
                <span class="text-sm font-bold text-slate-100"><?= $fmtNum($annualUsed) ?>일 / <?= $fmtNum($annualTotal) ?>일</span>
            </div>
            <div class="h-2 bg-slate-900 rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: <?= $annualPct ?>%"></div>
            </div>
            <p class="text-xs text-slate-400 mt-1.5">잔여 <?= $fmtNum($annualRemain) ?>일</p>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <a href="<?= $basePath ?>/pages/approval_register.php?type=휴가신청서" class="zm-btn-widget group">
                <span class="zm-btn-widget-icon bg-violet-100 text-violet-600 group-hover:bg-violet-200"><i data-lucide="palmtree" class="w-3.5 h-3.5"></i></span>
                <span>휴가 신청</span>
            </a>
            <a href="<?= $basePath ?>/pages/approval_register.php?type=출장신청서" class="zm-btn-widget group">
                <span class="zm-btn-widget-icon bg-sky-100 text-sky-600 group-hover:bg-sky-200"><i data-lucide="plane" class="w-3.5 h-3.5"></i></span>
                <span>출장 신청</span>
            </a>
            <button onclick="openOutsideWorkModal()" class="zm-btn-widget group">
                <span class="zm-btn-widget-icon bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i></span>
                <span>외근 등록</span>
            </button>
            <a href="<?= $basePath ?>/pages/approval_register.php?type=야근신청서" class="zm-btn-widget group">
                <span class="zm-btn-widget-icon bg-amber-100 text-amber-600 group-hover:bg-amber-200"><i data-lucide="moon" class="w-3.5 h-3.5"></i></span>
                <span>야근 신청</span>
            </a>
        </div>
    </div>
</div>
