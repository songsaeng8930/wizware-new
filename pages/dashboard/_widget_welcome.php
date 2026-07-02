<!-- ===== A. 환영 + 액션 바 (컴팩트 1줄) ===== -->
<section class="mb-4 flex flex-wrap items-center gap-3">
    <!-- 인사말 -->
    <div class="flex items-center gap-2.5 flex-1 min-w-[240px]">
        <h2 class="text-xl font-bold text-slate-100 whitespace-nowrap">안녕하세요, <?= esc($userName) ?>님</h2>
        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-800 text-slate-200"><?= esc($roleLabel) ?></span>
        <span class="text-sm text-slate-500 hidden lg:inline">일정 <?= (int)$todayEventCount ?> · 결재 <?= (int)$pendingForMeCount ?></span>
    </div>

    <!-- 출퇴근 -->
    <?php if ($todayClockIn && $todayClockOut):
        $inTs  = strtotime($todayClockIn);
        $outTs = strtotime($todayClockOut);
        $diffMin = max(0, (int)(($outTs - $inTs) / 60));
        $wHrs = intdiv($diffMin, 60);
        $wMin = $diffMin % 60;
        $wLabel = $wHrs > 0 ? ($wHrs . '시간' . ($wMin > 0 ? ' ' . $wMin . '분' : '')) : ($wMin . '분');
    ?>
    <div id="clockArea" class="clock-widget">
        <span class="clock-time"><?= substr($todayClockIn, 0, 5) ?></span>
        <span class="clock-sep">~</span>
        <span class="clock-time"><?= substr($todayClockOut, 0, 5) ?></span>
        <span class="clock-dur"><?= $wLabel ?></span>
    </div>
    <?php elseif ($todayClockIn): ?>
    <div id="clockArea" class="clock-widget">
        <span class="clock-pulse"></span>
        <span class="clock-time"><?= substr($todayClockIn, 0, 5) ?></span>
        <span id="clockElapsed" class="clock-dur" data-clock-in="<?= date('Y-m-d\TH:i:s', strtotime($todayClockIn)) ?>"></span>
        <button id="clockBtn" onclick="handleClock()" class="clock-btn">퇴근</button>
    </div>
    <?php else: ?>
    <div id="clockArea" class="clock-widget">
        <button id="clockBtn" onclick="handleClock()" class="clock-btn-primary">출근</button>
    </div>
    <?php endif; ?>

    <!-- 빠른 액션 -->
    <div class="flex items-center gap-1.5">
        <button onclick="openCreateModal()" class="zm-btn-glass">
            <i data-lucide="calendar-plus" class="w-3 h-3"></i>일정
        </button>
        <a href="<?= $basePath ?>/pages/approval_register.php" class="zm-btn-glass">
            <i data-lucide="file-plus" class="w-3 h-3"></i>결재
        </a>
        <button onclick="openOutsideWorkModal()" class="zm-btn-glass">
            <i data-lucide="map-pin" class="w-3 h-3"></i>외근
        </button>
        <a href="<?= $basePath ?>/pages/approval_register.php?type=휴가신청서" class="zm-btn-glass">
            <i data-lucide="palmtree" class="w-3 h-3"></i>휴가
        </a>
        <a href="<?= $basePath ?>/pages/approval_register.php?type=출장신청서" class="zm-btn-glass">
            <i data-lucide="plane" class="w-3 h-3"></i>출장
        </a>
        <a href="<?= $basePath ?>/pages/approval_register.php?type=야근신청서" class="zm-btn-glass">
            <i data-lucide="moon" class="w-3 h-3"></i>야근
        </a>
        <button onclick="openWidgetSettings()" class="zm-btn-glass !px-2">
            <i data-lucide="settings" class="w-3.5 h-3.5"></i>
        </button>
    </div>
</section>
