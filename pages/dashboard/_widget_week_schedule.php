<!-- ===== D. 이번 주 일정 (통합 뷰) ===== -->
<?php
$eventTypeConfig = [
    'schedule'    => ['dot' => 'bg-violet-400', 'label' => '일정'],
    'approval'    => ['dot' => 'bg-blue-400',   'label' => '결재'],
    'reservation' => ['dot' => 'bg-emerald-400','label' => '예약'],
];
$maxPerDay   = 2;
$maxLines    = 8;
$lineCount   = 0;
$hiddenCount = 0;
$totalEvents = array_sum(array_map(fn($d) => count($d['events']), $weekDays));
?>
<div class="<?= $bottomSpan ?>">
    <div class="<?= $cardClsT3 ?> h-full flex flex-col">

        <!-- 헤더 -->
        <div class="flex items-baseline justify-between pb-3 mb-1 border-b border-slate-800/60">
            <div>
                <h3 class="text-sm font-bold text-slate-200">이번 주</h3>
                <p class="text-xs text-slate-500 mt-0.5"><?= date('n.j', strtotime($weekStart)) ?> ~ <?= date('n.j', strtotime($weekEnd)) ?></p>
            </div>
            <a href="<?= $basePath ?>/pages/schedule.php" class="text-xs font-semibold text-slate-400 hover:text-slate-200 hover:underline">월간 보기 →</a>
        </div>

        <!-- 주간 리스트 -->
        <div class="flex-1 space-y-0.5">
            <?php foreach ($weekDays as $day):
                if ($lineCount >= $maxLines) {
                    $hiddenCount += count($day['events']);
                    continue;
                }
                $hasEvents = !empty($day['events']);
                $isToday = $day['isToday'];
                $dayEvents = $day['events'];
                $showCount = min(count($dayEvents), $maxPerDay, $maxLines - $lineCount);
                $dayHidden = count($dayEvents) - $showCount;
            ?>
            <div class="flex items-center gap-3 py-1.5 rounded-lg min-h-[36px] <?= $isToday ? 'bg-slate-800/40 -mx-1 px-1' : '' ?>">
                <!-- 날짜 -->
                <div class="w-12 flex-shrink-0 flex items-center gap-1.5">
                    <span class="text-xs text-slate-500 w-3 text-right"><?= $day['dayName'] ?></span>
                    <?php if ($isToday): ?>
                        <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center"><?= $day['dayNum'] ?></span>
                    <?php else: ?>
                        <span class="text-sm font-semibold <?= $day['isWeekend'] ? 'text-slate-600' : 'text-slate-300' ?> w-7 h-7 flex items-center justify-center"><?= $day['dayNum'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- 이벤트 -->
                <div class="flex-1 min-w-0">
                    <?php if ($hasEvents): ?>
                        <?php foreach (array_slice($dayEvents, 0, $showCount) as $ev):
                            $type = $ev['event_type'] ?? 'schedule';
                            $cfg  = $eventTypeConfig[$type] ?? $eventTypeConfig['schedule'];

                            if ($type === 'schedule') {
                                $timeStr = (!$ev['is_all_day'] && $ev['start_time']) ? substr($ev['start_time'], 0, 5) : '종일';
                            } elseif ($type === 'reservation') {
                                $timeStr = $ev['start_time'] ? substr($ev['start_time'], 0, 5) : '';
                            } else {
                                $timeStr = ($ev['subtitle'] === '결재대기') ? '대기' : ($ev['subtitle'] ?? '');
                            }

                            $clickAction = match($type) {
                                'schedule'    => "openDetailModal({$ev['id']})",
                                'approval'    => "location.href='{$basePath}/pages/approval_draft.php'",
                                'reservation' => "location.href='{$basePath}/pages/reservation.php'",
                            };
                            $lineCount++;
                        ?>
                        <button onclick="<?= $clickAction ?>" class="w-full flex items-center gap-3 py-1 rounded hover:bg-slate-800/60 transition-colors text-left group">
                            <span class="w-1.5 h-1.5 rounded-full <?= $cfg['dot'] ?> flex-shrink-0 opacity-80 ml-0.5"></span>
                            <span class="text-xs text-slate-500 w-9 flex-shrink-0 tabular-nums"><?= esc($timeStr) ?></span>
                            <span class="text-[13px] text-slate-200 truncate group-hover:text-slate-100"><?= esc($ev['title']) ?></span>
                        </button>
                        <?php endforeach; ?>
                        <?php if ($dayHidden > 0): $hiddenCount += $dayHidden; ?>
                            <a href="<?= $basePath ?>/pages/schedule.php?date=<?= $day['date'] ?>" class="w-full flex items-center gap-3 py-1 rounded hover:bg-slate-800/60 transition-colors text-left">
                                <span class="w-1.5 ml-0.5 flex-shrink-0"></span>
                                <span class="text-xs text-slate-500 w-9 flex-shrink-0"></span>
                                <span class="text-xs text-slate-500 hover:text-slate-300">+<?= $dayHidden ?>건</span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-xs text-slate-700 py-0.5 inline-block">&mdash;</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($hiddenCount > 0): ?>
                <a href="<?= $basePath ?>/pages/schedule.php" class="block text-xs text-slate-500 hover:text-slate-300 text-center py-1.5">+<?= $hiddenCount ?>건 더보기</a>
            <?php endif; ?>
        </div>

        <!-- 범례 + 액션 -->
        <div class="mt-auto pt-3 border-t border-slate-800/60">
            <div class="flex items-center gap-3 mb-3">
                <?php foreach ($eventTypeConfig as $cfg): ?>
                <span class="flex items-center gap-1 text-[11px] text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full <?= $cfg['dot'] ?> opacity-80"></span><?= $cfg['label'] ?>
                </span>
                <?php endforeach; ?>
            </div>
            <button onclick="openCreateModal()" class="zm-btn-bottom-action">
                <i data-lucide="plus" class="w-4 h-4"></i>일정 추가
            </button>
        </div>
    </div>
</div>
