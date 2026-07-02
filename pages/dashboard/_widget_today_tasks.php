<!-- ===== 오늘 할 일 ===== -->
<section class="mb-4">
    <div class="<?= $cardClsT1 ?>">
        <div class="flex items-baseline justify-between pb-3 mb-3 border-b border-slate-700/40">
            <h3 class="text-base font-bold text-slate-100">오늘 할 일</h3>
            <span class="text-xs text-slate-500 tabular-nums"><?= count($todayTasks) ?>건</span>
        </div>

        <?php if (empty($todayTasks)): ?>
            <div class="py-8 text-center">
                <p class="text-sm text-slate-400">처리할 일이 없어요</p>
                <p class="text-xs text-slate-600 mt-1">결재 요청이나 일정이 생기면 여기에 표시됩니다</p>
            </div>
        <?php else: ?>
            <?php
            $rejectedTasks  = array_filter($todayTasks, fn($t) => $t['type'] === 'rejected');
            $approvalTasks  = array_filter($todayTasks, fn($t) => $t['type'] === 'approval');
            $scheduleTasks  = array_filter($todayTasks, fn($t) => $t['type'] === 'schedule');
            ?>
            <div class="space-y-4">
                <?php if (!empty($rejectedTasks)): ?>
                <div>
                    <p class="text-[11px] font-bold text-rose-300/60 mb-2 uppercase tracking-wide">반려된 결재</p>
                    <div class="space-y-1.5">
                        <?php foreach ($rejectedTasks as $t): ?>
                        <a href="<?= $basePath ?>/pages/approval_view.php?id=<?= $t['id'] ?>"
                           class="flex items-center gap-3 px-3 py-2.5 bg-rose-500/[0.04] border border-rose-400/[0.08] rounded-lg hover:bg-rose-500/[0.08] transition-colors">
                            <span class="text-[11px] font-bold px-1.5 py-0.5 rounded flex-shrink-0 bg-rose-500/[0.12] text-rose-300/80"><?= esc($t['label']) ?></span>
                            <span class="text-[13px] font-medium text-slate-100 flex-1 truncate"><?= esc($t['title']) ?></span>
                            <span class="text-[11px] text-slate-500 flex-shrink-0"><?= esc($t['subtitle']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($approvalTasks)): ?>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 mb-2 uppercase tracking-wide">결재 대기</p>
                    <div class="space-y-1.5">
                        <?php foreach ($approvalTasks as $t): ?>
                        <a href="<?= $basePath ?>/pages/approval_view.php?id=<?= $t['id'] ?>"
                           class="flex items-center gap-3 px-3 py-2.5 bg-slate-800/40 rounded-lg hover:bg-slate-800/70 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-medium text-slate-100 truncate"><?= esc($t['title']) ?></p>
                                <p class="text-[11px] text-slate-500 mt-0.5"><?= esc($t['subtitle']) ?></p>
                            </div>
                            <span class="text-[11px] <?= !empty($t['urgent']) ? 'text-slate-200 font-bold' : 'text-slate-600' ?> flex-shrink-0"><?= esc($t['label']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($scheduleTasks)): ?>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 mb-2 uppercase tracking-wide">오늘 일정</p>
                    <div class="space-y-1.5">
                        <?php foreach ($scheduleTasks as $t): ?>
                        <button onclick="openDetailModal(<?= $t['id'] ?>)"
                                class="w-full flex items-center gap-3 px-3 py-2.5 bg-slate-800/40 rounded-lg hover:bg-slate-800/70 text-left transition-colors">
                            <span class="text-[11px] font-mono text-slate-400 w-10 flex-shrink-0 tabular-nums"><?= esc($t['subtitle']) ?></span>
                            <span class="text-[13px] font-medium text-slate-100 flex-1 truncate"><?= esc($t['title']) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-700/40">
                <a href="<?= $basePath ?>/pages/approval_pending.php" class="block text-center py-1 text-xs font-semibold text-slate-500 hover:text-slate-300 hover:underline transition-colors">전체 결재함 →</a>
            </div>
        <?php endif; ?>
    </div>
</section>
