        <!-- ===== 연차관리 ===== -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <button onclick="openLeaveModal()" class="btn btn-primary btn-sm flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> 연차 등록
                </button>
                <button onclick="openAdjustModal()" class="btn btn-secondary btn-sm flex items-center gap-1">
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> 조정
                </button>
                <?php if (!$annualFromDB): ?>
                <span class="text-sm text-amber-500 bg-amber-50 px-2 py-1 rounded">(시범 데이터)</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-1.5">
                <button id="dashToggleBtn" onclick="toggleDashboard()" class="btn btn-secondary btn-xs flex items-center gap-1">
                    <i id="dashToggleIcon" data-lucide="bar-chart-3" class="w-3.5 h-3.5"></i>
                    <span id="dashToggleLabel">대시보드</span>
                </button>
                <button class="btn btn-secondary btn-xs flex items-center gap-1">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> 다운로드
                </button>
                <div class="relative">
                    <button id="annualMoreBtn" class="btn btn-secondary btn-xs flex items-center gap-1">
                        <i data-lucide="ellipsis" class="w-3.5 h-3.5"></i> 더보기
                    </button>
                    <div id="annualMorePopover" class="hidden absolute right-0 top-full mt-1 bg-white rounded-lg border border-gray-200 shadow-lg py-1 z-50 min-w-[160px]">
                        <button data-more-action="openCarryoverModal" class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <i data-lucide="arrow-right-left" class="w-4 h-4 text-gray-400"></i> 연차 이월
                        </button>
                        <button data-more-action="openPromotionModal" class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <i data-lucide="megaphone" class="w-4 h-4 text-gray-400"></i> 사용 촉진
                        </button>
                        <button data-more-action="openSettlementModal" class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <i data-lucide="calculator" class="w-4 h-4 text-gray-400"></i> 퇴사 정산
                        </button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <button data-more-action="openHolidayModal" class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <i data-lucide="calendar-off" class="w-4 h-4 text-gray-400"></i> 공휴일 관리
                        </button>
                        <button data-more-action="openAuditLogModal" class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <i data-lucide="file-clock" class="w-4 h-4 text-gray-400"></i> 변경 이력
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- 통합 KPI (양쪽 뷰 공용) -->
        <div class="grid grid-cols-4 gap-3 mb-4">
            <div class="bg-white rounded-xl border border-gray-200 p-3">
                <p class="text-xs text-gray-500 mb-0.5">총 부여일</p>
                <p class="text-xl font-bold text-gray-900 tabular-nums"><?= $totalAnnual ?><span class="text-sm font-normal text-gray-400 ml-0.5">일</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-3">
                <p class="text-xs text-gray-500 mb-0.5">총 사용일</p>
                <p class="text-xl font-bold text-primary tabular-nums"><?= $usedAnnual ?><span class="text-sm font-normal text-gray-400 ml-0.5">일</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-3">
                <p class="text-xs text-gray-500 mb-0.5">총 잔여일</p>
                <p class="text-xl font-bold text-amber-600 tabular-nums"><?= $remainAnnual ?><span class="text-sm font-normal text-gray-400 ml-0.5">일</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-3">
                <p class="text-xs text-gray-500 mb-0.5">전사 사용률</p>
                <p class="text-xl font-bold text-gray-900 tabular-nums"><?= $annualRate ?><span class="text-sm font-normal text-gray-400 ml-0.5">%</span></p>
            </div>
        </div>
        <!-- 대시보드 (토글) -->
        <div id="annualDash" class="hidden space-y-6 mb-6">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 text-primary"></i>
                        부서별 연차 사용률
                    </h4>
                    <div id="dashDeptChart" class="space-y-3">
                        <p class="text-gray-400 text-center py-6 text-sm">로딩 중...</p>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
                            월별 연차 사용 추이
                        </h4>
                        <div id="dashMonthChart">
                            <p class="text-gray-400 text-center py-6 text-sm">로딩 중...</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
                            장기 미사용 경고 <span class="text-xs text-gray-400 font-normal">(잔여 70% 이상)</span>
                        </h4>
                        <div id="dashWarnings">
                            <p class="text-gray-400 text-center py-4 text-sm">로딩 중...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            $anOrgs = array_values(array_unique(array_filter(array_column($annualData, 'org'))));
            $anDepts = array_values(array_unique(array_filter(array_column($annualData, 'dept'))));
            sort($anOrgs); sort($anDepts);
        ?>
        <div id="annualFilterBox" class="bg-white rounded-xl border border-gray-200 mb-4">
            <div class="p-4 pb-3">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
                        <input type="text" id="annualSearch" placeholder="이름으로 검색하세요..." class="w-full pl-11 pr-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 transition-colors" oninput="applyAnnualFilters()">
                    </div>
                    <select onchange="location.href='?tab=annual&year='+this.value"
                            class="border border-gray-200 rounded-xl px-3 py-3 text-sm bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 shrink-0">
                        <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 2; $y--): ?>
                        <option value="<?= $y ?>" <?= $annualYear === $y ? 'selected' : '' ?>><?= $y ?>년</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="px-4 pb-4 relative" id="anPillRow">
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($showDivision): ?><div class="rf-pill" data-an="org"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('division')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
                    <?php if ($showDepartment): ?><div class="rf-pill" data-an="dept"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('department')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
                    <div class="rf-pill" data-an="remain"><span class="rf-pill__text">잔여일수</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
                </div>
                <div id="anDrop" class="hidden rf-drop"></div>
            </div>
            <div id="anChipBar" class="hidden px-4 pb-3.5 pt-3 border-t border-gray-200">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-slate-500 shrink-0">적용 중</span>
                    <div id="anChips" class="flex items-center gap-1.5 flex-wrap"></div>
                    <button onclick="resetAnnualFilters()" class="text-xs text-slate-500 hover:text-red-400 transition-colors shrink-0 ml-auto">모두 지우기</button>
                </div>
            </div>
        </div>

        <div id="annualTable">
        <table class="w-full text-sm emp-table" id="annualTbl">
            <thead>
                <tr class="border-b-2 border-slate-800">
                    <th class="py-2.5 px-3 text-left font-medium text-slate-300">이름</th>
                    <?php if ($showAnyOrg): ?><th class="py-2.5 px-3 text-center font-medium text-slate-300"><?= htmlspecialchars($orgHeaderLabel) ?></th><?php endif; ?>
                    <th class="py-2.5 px-3 text-center font-medium text-slate-300">부여</th>
                    <th class="py-2.5 px-3 text-center font-medium text-slate-300">사용</th>
                    <th class="py-2.5 px-3 text-center font-medium text-slate-300">잔여</th>
                    <th class="py-2.5 px-3 font-medium text-slate-300 w-40">사용 현황</th>
                    <th class="py-2.5 px-3 text-right font-medium text-slate-300">일급(원)</th>
                    <th class="py-2.5 px-3 text-right font-medium text-slate-300">보상비(원)</th>
                    <th class="py-2.5 px-3 text-center font-medium text-slate-300 w-20">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($annualData as $a):
                    $pct = $a['total'] > 0 ? round($a['used'] / $a['total'] * 100) : 0;
                    $barColor = $a['remaining'] <= 3 ? 'bg-amber-100' : ($a['remaining'] <= 5 ? 'bg-amber-400' : 'bg-primary');
                    $remainCls = $a['remaining'] <= 3 ? 'text-amber-700 font-bold' : ($a['remaining'] <= 5 ? 'text-amber-600 font-medium' : 'text-slate-100');
                ?>
                <tr class="border-b border-slate-800 hover:bg-slate-950" data-name="<?= htmlspecialchars($a['name']) ?>" data-org="<?= htmlspecialchars($a['org'] ?? '') ?>" data-dept="<?= htmlspecialchars($a['dept'] ?? '') ?>" data-remain="<?= (int)$a['remaining'] ?>">
                    <td class="py-2.5 px-3 font-medium text-slate-100"><?= htmlspecialchars($a['name']) ?></td>
                    <?php if ($showAnyOrg): ?><td class="py-2.5 px-3 text-center text-slate-400 text-sm"><?= $showDivision ? htmlspecialchars($a['org']) : '' ?><?= $showDivision && $showDepartment && $a['dept'] ? ' · ' : '' ?><?= $showDepartment ? htmlspecialchars($a['dept']) : '' ?></td><?php endif; ?>
                    <td class="py-2.5 px-3 text-center text-slate-300"><?= (int)$a['total'] ?>일</td>
                    <td class="py-2.5 px-3 text-center text-primary"><?= (int)$a['used'] ?>일</td>
                    <td class="py-2.5 px-3 text-center <?= $remainCls ?>"><?= (int)$a['remaining'] ?>일</td>
                    <td class="py-2.5 px-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-slate-800 rounded-full h-2">
                                <div class="<?= $barColor ?> h-2 rounded-full transition-all" style="width:<?= min($pct, 100) ?>%"></div>
                            </div>
                            <span class="text-sm text-slate-400 w-8 text-right"><?= $pct ?>%</span>
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-right text-slate-300 tabular-nums"><?= number_format($a['daily']) ?></td>
                    <td class="py-2.5 px-3 text-right font-medium text-slate-100 tabular-nums"><?= number_format($a['compensation']) ?></td>
                    <td class="py-2.5 px-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="showHistory(<?= $a['no'] ?>, '<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>')"
                                    class="btn btn-secondary btn-xs" title="사용 내역">내역</button>
                            <button onclick="openAdjustFor(<?= $a['no'] ?>, '<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['dept'] ?? '', ENT_QUOTES) ?>', <?= $a['total'] ?>, <?= $a['used'] ?>, <?= $a['remaining'] ?>)"
                                    class="btn btn-secondary btn-xs" title="연차 조정">조정</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div><!-- /annualTable -->

        <!-- 연차 등록 모달 -->
        <?php
        $defaultLeaveTypes = [
            ['code'=>'AL','name'=>'연차'],['code'=>'HAM','name'=>'반차(오전)'],
            ['code'=>'HAP','name'=>'반차(오후)'],['code'=>'SL','name'=>'병가'],
            ['code'=>'FL','name'=>'경조사'],['code'=>'OL','name'=>'공가'],
        ];
        $leaveTypes = !empty($leaveTypeItems) ? $leaveTypeItems : $defaultLeaveTypes;
        $halfDayCodes = ['HAM','HAP'];
        $deductCodes  = ['AL','HAM','HAP'];
        $leaveEmployees = [];
        foreach ($annualData as $a) {
            $leaveEmployees[] = [
                'id' => $a['no'], 'name' => $a['name'],
                'dept' => $a['dept'] ?? '', 'rank' => $a['rank'] ?? '',
                'remaining' => $a['remaining'], 'total' => $a['total'],
                'used' => $a['used'],
            ];
        }
        ?>
        <div id="leaveModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)closeLeaveModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-visible">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">연차 등록</h3>
                    <button onclick="closeLeaveModal()" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-400"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <!-- 직원 검색 Combobox -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">직원</label>
                        <div class="relative" id="empComboWrap">
                            <input type="hidden" id="leaveEmployee" value="">
                            <input type="text" id="empSearchInput" autocomplete="off"
                                   placeholder="이름 또는 부서로 검색..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            <ul id="empDropdown" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto z-50 hidden"></ul>
                        </div>
                        <!-- 선택된 직원 카드 -->
                        <div id="empCard" class="mt-2 hidden border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span id="empCardName" class="font-medium text-gray-900 text-sm"></span>
                                    <span id="empCardDept" class="text-xs text-gray-500 ml-1.5"></span>
                                </div>
                                <button onclick="clearEmpSelection()" class="text-gray-400 hover:text-gray-600 p-0.5">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-3 mt-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                    <div id="empCardBar" class="h-1.5 rounded-full transition-all"></div>
                                </div>
                                <span id="empCardRemain" class="text-xs font-medium whitespace-nowrap"></span>
                            </div>
                        </div>
                    </div>
                    <!-- 휴가 유형 (커스텀 드롭다운) -->
                    <?php
                    $leaveTypeMeta = [];
                    $ltIcons = ['AL'=>'calendar','HAM'=>'sunrise','HAP'=>'sunset','SL'=>'thermometer','FL'=>'heart','OL'=>'building-2'];
                    $ltColors = ['AL'=>'bg-blue-500','HAM'=>'bg-amber-400','HAP'=>'bg-orange-400','SL'=>'bg-rose-400','FL'=>'bg-pink-400','OL'=>'bg-slate-400'];
                    foreach ($leaveTypes as $lt) {
                        $c = $lt['code'];
                        $leaveTypeMeta[] = [
                            'code' => $c, 'name' => $lt['name'],
                            'half' => in_array($c, $halfDayCodes),
                            'deduct' => in_array($c, $deductCodes),
                            'days' => in_array($c, $halfDayCodes) ? 0.5 : 1,
                            'icon' => $ltIcons[$c] ?? 'calendar-days',
                            'color' => $ltColors[$c] ?? 'bg-gray-400',
                        ];
                    }
                    $firstLt = $leaveTypeMeta[0] ?? null;
                    ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">휴가 유형</label>
                        <input type="hidden" id="leaveType" value="<?= $firstLt ? htmlspecialchars($firstLt['code']) : 'AL' ?>">
                        <div class="relative" id="ltComboWrap">
                            <button type="button" id="ltTrigger"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-left flex items-center justify-between hover:border-gray-400 focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                                <span class="flex items-center gap-2">
                                    <span id="ltDot" class="w-2 h-2 rounded-full <?= $firstLt['color'] ?? 'bg-blue-500' ?>"></span>
                                    <span id="ltLabel" class="text-gray-900"><?= $firstLt ? htmlspecialchars($firstLt['name']) : '연차' ?></span>
                                    <span id="ltBadge" class="text-xs px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-medium"><?= $firstLt ? ($firstLt['deduct'] ? ($firstLt['half'] ? '0.5일' : '1일') : '차감 안 함') : '1일' ?></span>
                                </span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 pointer-events-none"></i>
                            </button>
                            <div id="ltDropdown" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden overflow-hidden">
                                <div class="py-1">
                                    <div class="px-3 py-1.5 text-xs font-medium text-gray-400 uppercase tracking-wider">연차 차감</div>
                                    <?php foreach ($leaveTypeMeta as $lt): if ($lt['deduct']): ?>
                                    <button type="button" data-code="<?= htmlspecialchars($lt['code']) ?>"
                                            class="lt-option w-full px-3 py-2 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                                        <span class="flex items-center gap-2.5">
                                            <span class="w-2 h-2 rounded-full <?= $lt['color'] ?>"></span>
                                            <span class="text-sm text-gray-900"><?= htmlspecialchars($lt['name']) ?></span>
                                        </span>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-medium"><?= $lt['half'] ? '0.5일' : '1일' ?></span>
                                    </button>
                                    <?php endif; endforeach; ?>
                                    <div class="border-t border-gray-100 mt-1 mb-1"></div>
                                    <div class="px-3 py-1.5 text-xs font-medium text-gray-400 uppercase tracking-wider">차감 안 함</div>
                                    <?php foreach ($leaveTypeMeta as $lt): if (!$lt['deduct']): ?>
                                    <button type="button" data-code="<?= htmlspecialchars($lt['code']) ?>"
                                            class="lt-option w-full px-3 py-2 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                                        <span class="flex items-center gap-2.5">
                                            <span class="w-2 h-2 rounded-full <?= $lt['color'] ?>"></span>
                                            <span class="text-sm text-gray-900"><?= htmlspecialchars($lt['name']) ?></span>
                                        </span>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-medium">차감 안 함</span>
                                    </button>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 기간 -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">시작일</label>
                            <input type="date" id="leaveStart" onchange="updateLeavePreview()"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="leaveEndWrap">
                            <label class="block text-sm font-medium text-gray-700 mb-1">종료일</label>
                            <input type="date" id="leaveEnd" onchange="updateLeavePreview()"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <!-- 사유 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">사유 <span class="text-gray-400 font-normal">(선택)</span></label>
                        <input type="text" id="leaveReason" placeholder="사유를 입력하세요"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <!-- 미리보기 -->
                    <div id="leavePreview" class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">사용일수</span>
                            <span id="previewDays" class="font-bold text-primary">1일</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-gray-600">등록 후 잔여</span>
                            <span id="previewRemaining" class="font-bold text-amber-600">0일</span>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-5 flex justify-end gap-3">
                    <button onclick="closeLeaveModal()" class="btn btn-secondary">취소</button>
                    <button onclick="submitLeave()" class="btn btn-primary">등록</button>
                </div>
            </div>
        </div>

        <!-- 사용 내역 모달 -->
        <div id="historyModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)closeHistoryModal()">
            <div class="bg-slate-900 rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-100"><span id="historyName"></span> 연차 사용 내역</h3>
                    <button onclick="closeHistoryModal()" class="p-1 hover:bg-slate-800 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-slate-500"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div id="historyContent" class="text-sm">
                        <p class="text-slate-500 text-center py-8">불러오는 중...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 연차 조정 모달 -->
        <div id="adjustModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)closeAdjustModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4 text-primary"></i>
                        연차 조정
                    </h3>
                    <button onclick="closeAdjustModal()" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <!-- 직원 선택 (combobox) -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">대상 직원 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="hidden" id="adjEmployee" value="">
                            <input type="text" id="adjEmpSearch" autocomplete="off" placeholder="이름 또는 부서로 검색..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            <ul id="adjEmpDropdown" class="absolute left-0 right-0 top-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-52 overflow-y-auto z-50 hidden"></ul>
                        </div>
                        <div id="adjEmpCard" class="mt-2 hidden border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span id="adjEmpName" class="font-medium text-sm text-gray-900"></span>
                                    <span id="adjEmpDept" class="text-xs px-1.5 py-0.5 rounded bg-gray-200 text-gray-600"></span>
                                </div>
                                <button type="button" onclick="clearAdjEmp()" class="text-gray-400 hover:text-red-500">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                잔여 연차: <span id="adjEmpRemain" class="font-bold text-primary"></span>일
                                (<span id="adjEmpTotal" class="text-gray-600"></span>일 중 <span id="adjEmpUsed" class="text-gray-600"></span>일 사용)
                            </div>
                        </div>
                    </div>

                    <!-- 조정 유형 -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">조정 유형 <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="adjType" value="add" class="peer sr-only" checked>
                                    <div class="peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border border-gray-300 rounded-lg py-2 text-center text-sm font-medium transition-colors hover:bg-gray-50">
                                        + 추가
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="adjType" value="deduct" class="peer sr-only">
                                    <div class="peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 border border-gray-300 rounded-lg py-2 text-center text-sm font-medium transition-colors hover:bg-gray-50">
                                        - 차감
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">조정일수 <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="adjDaysDelta(-0.5)" class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-100 text-gray-600 font-bold">-</button>
                                <input type="number" id="adjDays" value="1" min="0.5" max="30" step="0.5"
                                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-center font-medium">
                                <button type="button" onclick="adjDaysDelta(0.5)" class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-100 text-gray-600 font-bold">+</button>
                                <span class="text-sm text-gray-500">일</span>
                            </div>
                        </div>
                    </div>

                    <!-- 분류 -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1.5">분류</label>
                        <div class="adj-cat-chips" id="adjCatWrap">
                            <label class="adj-chip" data-color="amber"><input type="radio" name="adjCat" value="포상"><span>포상</span></label>
                            <label class="adj-chip" data-color="blue"><input type="radio" name="adjCat" value="이월"><span>이월</span></label>
                            <label class="adj-chip" data-color="emerald"><input type="radio" name="adjCat" value="보정"><span>보정</span></label>
                            <label class="adj-chip" data-color="slate"><input type="radio" name="adjCat" value="기타" checked><span>기타</span></label>
                        </div>
                    </div>

                    <!-- 사유 -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">조정 사유 <span class="text-red-500">*</span></label>
                        <textarea id="adjReason" rows="2" maxlength="200" placeholder="예: 포상 연차 2일 부여 (사내 공모전 수상)"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary resize-none"></textarea>
                        <p class="text-xs text-gray-400 mt-0.5 text-right"><span id="adjReasonLen">0</span>/200</p>
                    </div>

                    <!-- 미리보기 -->
                    <div id="adjPreview" class="hidden border border-dashed border-gray-300 rounded-lg p-3 bg-gray-50 text-sm">
                        <div class="flex items-center gap-2 text-gray-600">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            <span>변경 미리보기</span>
                        </div>
                        <div class="mt-1.5 flex items-center gap-3">
                            <span class="text-gray-500">현재 <span id="adjPrevBefore" class="font-medium text-gray-900">-</span>일</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-400"></i>
                            <span id="adjPrevAfter" class="font-bold text-primary">-</span>
                            <span class="text-gray-500">일</span>
                            <span id="adjPrevDelta" class="text-xs px-1.5 py-0.5 rounded font-medium"></span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                    <button onclick="closeAdjustModal()" class="btn btn-secondary btn-sm">취소</button>
                    <button onclick="submitAdjust()" id="adjSubmitBtn" class="btn btn-primary btn-sm flex items-center gap-1">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> 적용
                    </button>
                </div>
            </div>
        </div>

        <!-- 연차 조정 이력 모달 -->
        <div id="adjHistoryModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900"><span id="adjHistName"></span> 연차 조정 이력</h3>
                    <button onclick="document.getElementById('adjHistoryModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div id="adjHistContent" class="text-sm">
                        <p class="text-gray-500 text-center py-8">불러오는 중...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ 공휴일 관리 모달 ═══ -->
        <div id="holidayModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar-off" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">공휴일 관리</h3>
                        <span class="text-xs text-gray-400 ml-1"><?= date('Y') ?>년</span>
                    </div>
                    <button onclick="document.getElementById('holidayModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="bg-blue-50 rounded-lg p-3 flex items-start gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 mt-0.5 shrink-0"></i>
                        <p class="text-xs text-blue-700">연차 일수 계산 시 이 목록에 등록된 공휴일은 자동으로 제외됩니다. 법정·대체 공휴일은 시드 데이터로 미리 등록되어 있어요.</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">공휴일 추가</label>
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">날짜 <span class="text-red-500">*</span></label>
                                <input type="date" id="newHolidayDate" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">명칭 <span class="text-red-500">*</span></label>
                                <input type="text" id="newHolidayName" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary" placeholder="예: 임시공휴일">
                            </div>
                            <div class="w-24">
                                <label class="block text-xs text-gray-500 mb-1">유형</label>
                                <select id="newHolidayType" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                                    <option value="법정">법정</option>
                                    <option value="대체">대체</option>
                                    <option value="임시" selected>임시</option>
                                </select>
                            </div>
                            <button onclick="addHolidayRow()" class="btn btn-primary btn-sm flex items-center gap-1 h-[42px] px-4">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> 추가
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">등록된 공휴일</label>
                            <div class="flex border border-gray-300 rounded-lg overflow-hidden">
                                <button onclick="switchHolidayView('list')" id="holViewList" class="px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 rounded">목록</button>
                                <button onclick="switchHolidayView('cal')" id="holViewCal" class="px-3 py-1 text-xs font-medium bg-primary text-white rounded">달력</button>
                            </div>
                        </div>
                        <div id="holidayList" class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg">
                            <p class="text-gray-400 text-center py-8 text-sm">불러오는 중...</p>
                        </div>
                        <div id="holidayCalendar" class="hidden border border-gray-200 rounded-lg overflow-hidden"></div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="document.getElementById('holidayModal').classList.add('hidden')" class="btn btn-secondary btn-sm">닫기</button>
                </div>
            </div>
        </div>

        <!-- ═══ 감사 로그 모달 ═══ -->
        <div id="auditLogModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="file-clock" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">연차 변경 이력</h3>
                        <span class="text-xs text-gray-400 ml-1">최근 50건</span>
                    </div>
                    <button onclick="document.getElementById('auditLogModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div id="auditLogContent" class="text-sm border border-gray-200 rounded-lg overflow-hidden">
                        <p class="text-gray-400 text-center py-10 text-sm">불러오는 중...</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="document.getElementById('auditLogModal').classList.add('hidden')" class="btn btn-secondary btn-sm">닫기</button>
                </div>
            </div>
        </div>

        <!-- ═══ 이월 관리 모달 ═══ -->
        <div id="carryoverModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="arrow-right-left" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">연차 이월 관리</h3>
                    </div>
                    <button onclick="document.getElementById('carryoverModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="bg-amber-50 rounded-lg p-3 flex items-start gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-amber-500 mt-0.5 shrink-0"></i>
                        <p class="text-xs text-amber-700"><?= date('Y')-1 ?>년 미사용 연차를 <?= date('Y') ?>년으로 이월합니다. 승인 시 전년도 부여일에서 차감되고 올해 부여일에 가산됩니다.</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">대상 직원 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="hidden" id="coEmployee" value="">
                            <input type="text" id="coEmpSearch" autocomplete="off" placeholder="이름 또는 부서로 검색..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            <ul id="coEmpDropdown" class="absolute left-0 right-0 top-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-52 overflow-y-auto z-50 hidden"></ul>
                        </div>
                        <div id="coEmpCard" class="mt-2 hidden border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span id="coEmpName" class="font-medium text-sm text-gray-900"></span>
                                    <span id="coEmpDept" class="text-xs px-1.5 py-0.5 rounded bg-gray-200 text-gray-600"></span>
                                </div>
                                <button type="button" onclick="clearCoEmp()" class="text-gray-400 hover:text-red-500">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                잔여 연차: <span id="coEmpRemain" class="font-bold text-primary"></span>일
                                (<span id="coEmpTotal" class="text-gray-600"></span>일 중 <span id="coEmpUsed" class="text-gray-600"></span>일 사용)
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">이월 일수 <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <button onclick="coAdjDays(-0.5)" class="w-9 h-9 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 font-medium">-</button>
                                <input type="number" id="coDays" min="0.5" max="25" step="0.5" value="1" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-center tabular-nums focus:border-primary focus:ring-1 focus:ring-primary">
                                <button onclick="coAdjDays(0.5)" class="w-9 h-9 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 font-medium">+</button>
                                <span class="text-sm text-gray-500">일</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">이월 연도</label>
                            <div class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-600 bg-gray-50">
                                <?= date('Y')-1 ?>년 → <?= date('Y') ?>년
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">이월 사유 <span class="text-red-500">*</span></label>
                        <textarea id="coReason" rows="2" maxlength="200" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary resize-none" placeholder="예: 프로젝트 사정으로 연차 사용 불가"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <button onclick="loadCarryoverList()" class="text-sm text-primary hover:underline flex items-center gap-1">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i> 이월 현황 보기
                    </button>
                    <div class="flex gap-2">
                        <button onclick="document.getElementById('carryoverModal').classList.add('hidden')" class="btn btn-secondary btn-sm">취소</button>
                        <button onclick="submitCarryover()" class="btn btn-primary btn-sm flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> 이월 등록
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ 이월 현황 모달 ═══ -->
        <div id="carryoverListModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="arrow-right-left" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">이월 현황</h3>
                    </div>
                    <button onclick="document.getElementById('carryoverListModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div id="carryoverList" class="border border-gray-200 rounded-lg overflow-hidden">
                        <p class="text-gray-400 text-center py-10 text-sm">불러오는 중...</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="document.getElementById('carryoverListModal').classList.add('hidden')" class="btn btn-secondary btn-sm">닫기</button>
                </div>
            </div>
        </div>

        <!-- ═══ 촉진 관리 모달 ═══ -->
        <div id="promotionModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="megaphone" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">연차 사용 촉진</h3>
                        <span class="text-xs text-gray-400 ml-1">근로기준법 제61조</span>
                    </div>
                    <button onclick="document.getElementById('promotionModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="bg-blue-50 rounded-lg p-3 flex items-start gap-2">
                        <i data-lucide="scale" class="w-3.5 h-3.5 text-blue-500 mt-0.5 shrink-0"></i>
                        <div class="text-xs text-blue-700 space-y-1">
                            <p class="font-medium">연차 사용 촉진 절차</p>
                            <p><strong>1차 (7월~)</strong>: 미사용 연차가 있는 직원에게 사용 계획 제출을 요청. 10일 내 계획 제출 의무.</p>
                            <p><strong>2차 (11월~)</strong>: 미제출 또는 미이행 시, 회사가 직접 사용 시기를 지정 통보.</p>
                            <p class="text-blue-500">양 단계 모두 이행하면 미사용 연차에 대한 보상 의무가 면제됩니다.</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">촉진 대상자 조회</label>
                        <div class="flex items-center gap-2">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="promoStage" value="1" class="peer sr-only" checked>
                                <div class="peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border border-gray-300 rounded-lg py-2.5 text-center text-sm font-medium transition-colors hover:bg-gray-50 cursor-pointer" onclick="loadPromotionTargets(1)">
                                    1차 촉진 (7월~)
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="promoStage" value="2" class="peer sr-only">
                                <div class="peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 border border-gray-300 rounded-lg py-2.5 text-center text-sm font-medium transition-colors hover:bg-gray-50 cursor-pointer" onclick="loadPromotionTargets(2)">
                                    2차 촉진 (11월~)
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="promotionTargets"></div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">촉진 이력</label>
                        <div id="promotionList" class="border border-gray-200 rounded-lg overflow-hidden max-h-56 overflow-y-auto">
                            <p class="text-gray-400 text-center py-8 text-sm">촉진 단계를 선택하면 대상자가 표시됩니다.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="document.getElementById('promotionModal').classList.add('hidden')" class="btn btn-secondary btn-sm">닫기</button>
                </div>
            </div>
        </div>

        <!-- ═══ 퇴사 정산 모달 ═══ -->
        <div id="settlementModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="calculator" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">퇴사자 연차 정산</h3>
                    </div>
                    <button onclick="document.getElementById('settlementModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="bg-amber-50 rounded-lg p-3 flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500 mt-0.5 shrink-0"></i>
                        <p class="text-xs text-amber-700">퇴사자의 미사용 연차를 일할 계산하여 보상액을 산정합니다. 정산 실행 시 대기 중인 휴가 신청이 자동 취소됩니다.</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">퇴사 직원 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="hidden" id="stEmployee" value="">
                            <input type="text" id="stEmpSearch" autocomplete="off" placeholder="이름으로 검색..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            <ul id="stEmpDropdown" class="absolute left-0 right-0 top-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-52 overflow-y-auto z-50 hidden"></ul>
                        </div>
                        <div id="stEmpCard" class="mt-2 hidden border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span id="stEmpName" class="font-medium text-sm text-gray-900"></span>
                                    <span id="stEmpDate" class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-600"></span>
                                </div>
                                <button type="button" onclick="clearStEmp()" class="text-gray-400 hover:text-red-500">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">기본급 (월) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" id="stBaseSalary" value="3,000,000" class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2.5 text-sm text-right tabular-nums focus:border-primary focus:ring-1 focus:ring-primary"
                                       oninput="this.value=this.value.replace(/[^\d]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,','); updateStPreview()">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">원</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">일급 = <span id="stDailyWage">138,439</span>원 (기본급 ÷ 21.67)</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">메모</label>
                            <input type="text" id="stMemo" maxlength="200" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-primary focus:ring-1 focus:ring-primary" placeholder="비고 (선택)">
                        </div>
                    </div>

                    <!-- 정산 결과 미리보기 / 실행 결과 -->
                    <div id="stResult" class="hidden">
                        <label class="text-sm font-medium text-gray-700 block mb-2">정산 결과</label>
                        <div id="stResultCards" class="grid grid-cols-3 gap-3"></div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <button onclick="loadSettlementList()" class="text-sm text-primary hover:underline flex items-center gap-1">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i> 정산 이력 보기
                    </button>
                    <div class="flex gap-2">
                        <button onclick="document.getElementById('settlementModal').classList.add('hidden')" class="btn btn-secondary btn-sm">취소</button>
                        <button onclick="submitSettlement()" class="btn btn-primary btn-sm flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> 정산 실행
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ 정산 이력 모달 ═══ -->
        <div id="settlementListModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="calculator" class="w-4 h-4 text-primary"></i>
                        <h3 class="text-base font-bold text-gray-900">정산 이력</h3>
                    </div>
                    <button onclick="document.getElementById('settlementListModal').classList.add('hidden')" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div id="settlementList" class="border border-gray-200 rounded-lg overflow-hidden">
                        <p class="text-gray-400 text-center py-10 text-sm">불러오는 중...</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="document.getElementById('settlementListModal').classList.add('hidden')" class="btn btn-secondary btn-sm">닫기</button>
                </div>
            </div>
        </div>
