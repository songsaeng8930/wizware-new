<!-- ===== 근로자명부 ===== -->
<div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
        <button class="roster-filter px-3 py-1 text-sm rounded-full bg-primary text-white" data-status="all">전체 <?= $totalEmp ?></button>
        <button class="roster-filter px-3 py-1 text-sm rounded-full bg-slate-800 text-slate-300 hover:bg-slate-700" data-status="재직">재직 <?= $activeCount ?></button>
        <button class="roster-filter px-3 py-1 text-sm rounded-full bg-slate-800 text-slate-300 hover:bg-slate-700" data-status="휴직">휴직 <?= $leaveCount ?></button>
        <button class="roster-filter px-3 py-1 text-sm rounded-full bg-slate-800 text-slate-300 hover:bg-slate-700" data-status="퇴사">퇴사 <?= $resignedCount ?></button>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="printRoster()" class="btn btn-secondary btn-sm flex items-center gap-1">
            <i data-lucide="printer" class="w-3.5 h-3.5"></i> 출력
        </button>
    </div>
</div>
<div class="bg-white rounded-xl border border-gray-200 mb-4">
    <div class="p-4 pb-3">
        <div class="relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="text" id="rosterSearch" placeholder="이름, 사번으로 검색하세요..." class="w-full pl-11 pr-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 transition-colors" oninput="applyRosterFilters()">
        </div>
    </div>
    <div class="px-4 pb-4 relative" id="rfPillRow">
        <div class="flex items-center gap-2 flex-wrap">
            <?php if ($showDivision): ?><div class="rf-pill" data-rf="org"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('division')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
            <?php if ($showDepartment): ?><div class="rf-pill" data-rf="dept"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('department')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
            <div class="rf-pill" data-rf="rank"><span class="rf-pill__text">직급</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
            <div class="rf-pill" data-rf="type"><span class="rf-pill__text">고용형태</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
            <div class="rf-pill" data-rf="gender"><span class="rf-pill__text">성별</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
        </div>
        <div id="rfDrop" class="hidden rf-drop"></div>
    </div>
    <div id="rfChipBar" class="hidden px-4 pb-3.5 pt-3 border-t border-gray-200">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-slate-500 shrink-0">적용 중</span>
            <div id="rfChips" class="flex items-center gap-1.5 flex-wrap"></div>
            <button onclick="resetRosterFilters()" class="text-xs text-slate-500 hover:text-red-400 transition-colors shrink-0 ml-auto">모두 지우기</button>
        </div>
    </div>
</div>
<table class="w-full text-sm emp-table" id="rosterTbl">
    <thead>
        <tr class="border-b-2 border-slate-800">
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">사번</th>
            <th class="py-2.5 px-3 text-left font-medium text-slate-300">이름</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">생년월일</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">성별</th>
            <?php if ($showDivision): ?><th class="py-2.5 px-3 text-center font-medium text-slate-300"><?= htmlspecialchars(getOrgLabel('division')) ?></th><?php endif; ?>
            <?php if ($showDepartment): ?><th class="py-2.5 px-3 text-center font-medium text-slate-300"><?= htmlspecialchars(getOrgLabel('department')) ?></th><?php endif; ?>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">직급</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">고용형태</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">상태</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">입사일</th>
            <th class="py-2.5 px-3 text-left font-medium text-slate-300">주소</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $e):
            $sCls = match($e['status']) {
                '재직' => 'badge-success',
                '휴직' => 'badge-warning',
                '퇴사' => 'badge-danger',
                default => 'badge-neutral',
            };
            $isResigned = $e['status'] === '퇴사';
        ?>
        <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer <?= $isResigned ? 'opacity-50' : '' ?>" data-status="<?= htmlspecialchars($e['status']) ?>" data-name="<?= htmlspecialchars($e['name']) ?>" data-empno="<?= htmlspecialchars($e['empNo']) ?>" data-org="<?= htmlspecialchars($e['org']) ?>" data-dept="<?= htmlspecialchars($e['dept']) ?>" data-rank="<?= htmlspecialchars($e['rank']) ?>" data-type="<?= htmlspecialchars($e['type']) ?>" data-gender="<?= $e['gender'] === 'M' ? '남' : ($e['gender'] === 'F' ? '여' : '') ?>" onclick="location.href='<?= $basePath ?>/pages/employee_register.php?id=<?= $e['no'] ?>'">
            <td class="py-2.5 px-3 text-center text-slate-400 text-xs tabular-nums"><?= htmlspecialchars($e['empNo']) ?></td>
            <td class="py-2.5 px-3 font-medium <?= $isResigned ? 'text-slate-400 line-through' : 'text-slate-100' ?>"><?= htmlspecialchars($e['name']) ?></td>
            <td class="py-2.5 px-3 text-center text-slate-300 tabular-nums"><?= $e['birth'] ? date('Y-m-d', strtotime($e['birth'])) : '-' ?></td>
            <td class="py-2.5 px-3 text-center text-slate-300"><?= $e['gender'] === 'M' ? '남' : ($e['gender'] === 'F' ? '여' : '-') ?></td>
            <?php if ($showDivision): ?><td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($e['org']) ?></td><?php endif; ?>
            <?php if ($showDepartment): ?><td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($e['dept']) ?></td><?php endif; ?>
            <td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($e['rank']) ?></td>
            <td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($e['type']) ?></td>
            <td class="py-2.5 px-3 text-center"><span class="px-2 py-0.5 text-sm rounded-full <?= $sCls ?>"><?= htmlspecialchars($e['status']) ?></span></td>
            <td class="py-2.5 px-3 text-center text-slate-300 tabular-nums"><?= htmlspecialchars($e['date']) ?></td>
            <td class="py-2.5 px-3 text-slate-400 text-xs"><?= htmlspecialchars($e['address']) ?: '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
