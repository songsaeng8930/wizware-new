<!-- ===== 근로자 계약 ===== -->
<div class="grid grid-cols-4 gap-4 mb-5">
    <div class="border border-slate-800 rounded-xl p-4 text-center">
        <p class="text-sm text-slate-400 mb-1">전체 직원</p>
        <p class="text-2xl font-bold text-slate-100"><?= $totalEmp ?><span class="text-sm font-normal text-slate-500 ml-0.5">명</span></p>
    </div>
    <div class="border border-slate-800 rounded-xl p-4 text-center">
        <p class="text-sm text-slate-400 mb-1">계약 체결</p>
        <p class="text-2xl font-bold text-emerald-400"><?= $contractDone ?><span class="text-sm font-normal text-slate-500 ml-0.5">건</span></p>
    </div>
    <div class="border border-slate-800 rounded-xl p-4 text-center">
        <p class="text-sm text-slate-400 mb-1">만료 예정</p>
        <p class="text-2xl font-bold text-amber-400"><?= $contractExpiring ?><span class="text-sm font-normal text-slate-500 ml-0.5">건</span></p>
    </div>
    <div class="border border-slate-800 rounded-xl p-4 text-center">
        <p class="text-sm text-slate-400 mb-1">미체결</p>
        <p class="text-2xl font-bold text-rose-400"><?= $contractNone ?><span class="text-sm font-normal text-slate-500 ml-0.5">건</span></p>
    </div>
</div>
<div class="flex items-center justify-between mb-3">
    <p class="text-sm text-slate-400">직원별 근로계약 체결 현황을 확인하고 신규 계약을 작성합니다.</p>
    <div class="flex items-center gap-2">
        <a href="<?= $basePath ?>/pages/labor_contract_template.php" class="btn btn-secondary btn-sm flex items-center gap-1" title="회사 표준 근로계약서 조문 편집">
            <i data-lucide="file-signature" class="w-3.5 h-3.5 pointer-events-none"></i> 계약서 양식
        </a>
        <a href="<?= $basePath ?>/pages/labor_contract_form.php?mode=write" class="px-3 py-1.5 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark flex items-center gap-1">
            <i data-lucide="plus" class="w-3.5 h-3.5 pointer-events-none"></i> 신규 작성
        </a>
    </div>
</div>
<?php
    $ctOrgs = array_values(array_unique(array_filter(array_column($contractData, 'org'))));
    $ctDepts = array_values(array_unique(array_filter(array_column($contractData, 'dept'))));
    $ctRanks = array_values(array_unique(array_filter(array_column($contractData, 'rank'))));
    $ctTypes = array_values(array_unique(array_filter(array_column($contractData, 'type'))));
    $ctStatuses = array_values(array_unique(array_filter(array_column($contractData, 'contract_status'))));
    sort($ctOrgs); sort($ctDepts); sort($ctRanks); sort($ctTypes); sort($ctStatuses);
?>
<div class="bg-white rounded-xl border border-gray-200 mb-4">
    <div class="p-4 pb-3">
        <div class="relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="text" id="contractSearch" placeholder="이름으로 검색하세요..." class="w-full pl-11 pr-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 transition-colors" oninput="applyContractFilters()">
        </div>
    </div>
    <div class="px-4 pb-4 relative" id="ctPillRow">
        <div class="flex items-center gap-2 flex-wrap">
            <?php if ($showDivision): ?><div class="rf-pill" data-ct="org"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('division')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
            <?php if ($showDepartment): ?><div class="rf-pill" data-ct="dept"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('department')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
            <div class="rf-pill" data-ct="rank"><span class="rf-pill__text">직급</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
            <div class="rf-pill" data-ct="type"><span class="rf-pill__text">고용형태</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
            <div class="rf-pill" data-ct="cstatus"><span class="rf-pill__text">계약상태</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
        </div>
        <div id="ctDrop" class="hidden rf-drop"></div>
    </div>
    <div id="ctChipBar" class="hidden px-4 pb-3.5 pt-3 border-t border-gray-200">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-slate-500 shrink-0">적용 중</span>
            <div id="ctChips" class="flex items-center gap-1.5 flex-wrap"></div>
            <button onclick="resetContractFilters()" class="text-xs text-slate-500 hover:text-red-400 transition-colors shrink-0 ml-auto">모두 지우기</button>
        </div>
    </div>
</div>
<div class="overflow-x-auto">
<table class="w-full text-sm emp-table" id="contractTbl">
    <thead>
        <tr class="border-b-2 border-slate-800">
            <th class="py-2.5 px-3 text-left font-medium text-slate-300">이름</th>
            <?php if ($showAnyOrg): ?><th class="py-2.5 px-3 text-center font-medium text-slate-300"><?= htmlspecialchars($orgHeaderLabel) ?></th><?php endif; ?>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">직급</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">고용형태</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">계약일</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">만료일</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">상태</th>
            <th class="py-2.5 px-3 text-center font-medium text-slate-300">관리</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contractData as $c):
            $stCls = match($c['contract_status']) {
                '체결완료' => 'badge-success',
                '만료예정' => 'badge-warning',
                '미체결'   => 'badge-danger',
                default    => 'badge-neutral',
            };
            $isExpiring = $c['contract_status'] === '만료예정';
            $endDate = $c['contract_end'] ?? null;
            $daysLeft = null;
            if ($endDate && strtotime($endDate) !== false) {
                $daysLeft = (int)floor((strtotime($endDate) - time()) / 86400);
            }
            // 이름/버튼 공용 링크 — 체결완료는 열람(view), 그 외는 작성(write)
            if ($c['contract_status'] === '체결완료') {
                $contractHref = $basePath . '/pages/labor_contract_form.php?id=' . $c['no'] . '&mode=view&contract_id=' . $c['contract_id'];
            } else {
                $contractHref = $basePath . '/pages/labor_contract_form.php?id=' . $c['no'] . '&mode=write' . (!empty($c['contract_id']) ? '&contract_id=' . $c['contract_id'] : '');
            }
        ?>
        <tr class="border-b border-gray-100 hover:bg-gray-50" data-name="<?= htmlspecialchars($c['name']) ?>" data-org="<?= htmlspecialchars($c['org']) ?>" data-dept="<?= htmlspecialchars($c['dept']) ?>" data-rank="<?= htmlspecialchars($c['rank']) ?>" data-type="<?= htmlspecialchars($c['type']) ?>" data-cstatus="<?= htmlspecialchars($c['contract_status']) ?>">
            <td class="py-2.5 px-3 font-medium text-slate-100">
                <a href="<?= $contractHref ?>" class="hover:underline cursor-pointer" title="계약서 열기"><?= htmlspecialchars($c['name']) ?></a>
                <?php if ($isExpiring): ?>
                <span class="ml-1 inline-flex items-center text-[10px] text-amber-400" title="만료 예정"><i data-lucide="clock" class="w-3 h-3 inline"></i></span>
                <?php elseif ($c['contract_status'] === '미체결'): ?>
                <span class="ml-1 inline-flex items-center text-[10px] text-rose-400" title="미체결"><i data-lucide="alert-circle" class="w-3 h-3 inline"></i></span>
                <?php endif; ?>
            </td>
            <?php if ($showAnyOrg): ?><td class="py-2.5 px-3 text-center text-slate-400 text-sm"><?= $showDivision ? htmlspecialchars($c['org']) : '' ?><?= $showDivision && $showDepartment && $c['dept'] ? ' · ' : '' ?><?= $showDepartment ? htmlspecialchars($c['dept']) : '' ?></td><?php endif; ?>
            <td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($c['rank']) ?></td>
            <td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($c['type']) ?></td>
            <td class="py-2.5 px-3 text-center text-slate-300"><?= htmlspecialchars($c['date']) ?></td>
            <td class="py-2.5 px-3 text-center">
                <?php if ($endDate): ?>
                    <?php if ($daysLeft !== null && $daysLeft < 0): ?>
                    <span class="text-rose-400 font-medium"><?= htmlspecialchars($endDate) ?></span>
                    <span class="block text-xs text-rose-400">만료됨</span>
                    <?php elseif ($daysLeft !== null && $daysLeft <= 30): ?>
                    <span class="text-amber-400 font-medium"><?= htmlspecialchars($endDate) ?></span>
                    <span class="block text-xs text-amber-400">D-<?= $daysLeft ?></span>
                    <?php else: ?>
                    <span class="text-slate-300"><?= htmlspecialchars($endDate) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-slate-500 text-xs">정규직</span>
                <?php endif; ?>
            </td>
            <td class="py-2.5 px-3 text-center"><span class="px-2 py-0.5 text-sm rounded-full <?= $stCls ?>"><?= htmlspecialchars($c['contract_status']) ?></span></td>
            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                    <?php if ($c['contract_status'] === '체결완료'): ?>
                    <span class="inline-block px-3 py-1 text-sm bg-gray-100 text-gray-400 rounded-lg cursor-default">작성</span>
                    <a href="<?= $basePath ?>/pages/labor_contract_form.php?id=<?= $c['no'] ?>&mode=view&contract_id=<?= $c['contract_id'] ?>" class="inline-block px-3 py-1 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100">열람</a>
                    <?php else: ?>
                    <a href="<?= $basePath ?>/pages/labor_contract_form.php?id=<?= $c['no'] ?>&mode=write<?= !empty($c['contract_id']) ? '&contract_id='.$c['contract_id'] : '' ?>" class="inline-block px-3 py-1 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">작성</a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
