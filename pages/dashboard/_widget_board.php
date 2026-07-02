<!-- ===== F. 게시판 탭 ===== -->
<?php
function dashRelativeTime(string $dateStr): string {
    $ts = strtotime($dateStr);
    if ($ts === false) return '';
    $diff = time() - $ts;
    if ($diff < 0) return date('n.j', $ts);
    if ($diff < 3600) return max(1, (int)($diff / 60)) . '분 전';
    if ($diff < 86400) return (int)($diff / 3600) . '시간 전';
    if ($diff < 604800) return (int)($diff / 86400) . '일 전';
    return date('n.j', $ts);
}
?>
<div class="<?= $bottomSpan ?>">
    <div class="<?= $cardClsT3 ?> h-full flex flex-col">

        <!-- 헤더 + 탭 -->
        <div class="pb-3 mb-1 border-b border-slate-800/60">
            <h3 class="text-sm font-bold text-slate-200 mb-3">게시판</h3>
            <div class="flex gap-1 p-1 bg-slate-800/80 rounded-lg">
                <button class="board-tab-btn flex-1 zm-tab-active" data-tab="notice">공지</button>
                <button class="board-tab-btn flex-1" data-tab="free">자유</button>
                <button class="board-tab-btn flex-1" data-tab="archive">자료실</button>
            </div>
        </div>

        <!-- 탭 컨텐츠 -->
        <?php
        $tabData = [
            'notice'  => ['posts' => $notices,      'type' => 'notice'],
            'free'    => ['posts' => $freePosts,    'type' => 'free'],
            'archive' => ['posts' => $archivePosts, 'type' => 'archive'],
        ];
        foreach ($tabData as $key => $tab):
            $isFirst = ($key === 'notice');
        ?>
        <div class="board-tab-panel flex-1 <?= $isFirst ? '' : 'hidden' ?>" data-tab="<?= $key ?>">
            <?php if (empty($tab['posts'])): ?>
                <div class="py-8 text-center">
                    <p class="text-sm text-slate-500">게시글이 없습니다</p>
                </div>
            <?php else: ?>
                <div>
                    <?php foreach ($tab['posts'] as $p):
                        $isPinned = !empty($p['is_pinned']);
                        $timeLabel = dashRelativeTime($p['created_at']);
                        $author = $p['author_name'] ?? '';
                    ?>
                    <a href="<?= $basePath ?>/pages/board.php?type=<?= $tab['type'] ?>" class="flex items-center gap-2 py-2.5 hover:bg-slate-800/30 -mx-1 px-1 rounded-lg transition-colors">
                        <?php if ($isPinned): ?>
                            <span class="text-[11px] font-bold text-amber-400/80 flex-shrink-0 w-6 text-center">필독</span>
                        <?php else: ?>
                            <span class="w-6 flex-shrink-0"></span>
                        <?php endif; ?>
                        <span class="text-[13px] text-slate-200 flex-1 truncate"><?= esc($p['title']) ?></span>
                        <span class="text-[11px] text-slate-600 flex-shrink-0"><?= esc($author) ?></span>
                        <span class="text-[11px] text-slate-600 flex-shrink-0 w-10 text-right tabular-nums"><?= $timeLabel ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- 전체보기 -->
        <div class="mt-auto pt-3 border-t border-slate-800/60">
            <a href="<?= $basePath ?>/pages/board.php?type=notice" class="block text-center py-1 text-xs font-semibold text-slate-500 hover:text-slate-300 hover:underline transition-colors">전체보기 →</a>
        </div>
    </div>
</div>
