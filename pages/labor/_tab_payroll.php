        <!-- ===== 임금대장 ===== -->
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div class="flex items-center gap-2 flex-wrap">
                <select id="payYearSel" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 2; $y--): ?>
                    <option value="<?= $y ?>" <?= $payYear === $y ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
                <select id="payMonthSel" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $payMonth === $m ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
                <button onclick="payrollQuery()" class="px-3 py-1.5 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">조회</button>
                <span class="badge <?= $statusBadge[$payrollStatus] ?>"><?= $statusLabels[$payrollStatus] ?></span>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($isDraft && $payrollFromDB): ?>
                <button onclick="refreshFromContracts()" class="btn btn-secondary btn-sm flex items-center gap-1">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> 계약서 기준 재생성
                </button>
                <button onclick="payrollConfirm()" class="btn btn-primary btn-sm flex items-center gap-1">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> 확정
                </button>
                <?php elseif ($isConfirmed): ?>
                <button onclick="payrollUnconfirm()" class="btn btn-secondary btn-sm flex items-center gap-1">
                    <i data-lucide="undo-2" class="w-3.5 h-3.5"></i> 확정해제
                </button>
                <button onclick="payrollPay()" class="btn btn-primary btn-sm flex items-center gap-1">
                    <i data-lucide="banknote" class="w-3.5 h-3.5"></i> 지급완료
                </button>
                <?php endif; ?>
                <?php if ($payrollFromDB): ?>
                <button onclick="payrollExportCsv()" class="btn btn-secondary btn-sm flex items-center gap-1">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> 엑셀
                </button>
                <?php endif; ?>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <button onclick="openRatesModal()" class="btn btn-secondary btn-sm flex items-center gap-1" title="급여 설정">
                    <i data-lucide="settings" class="w-3.5 h-3.5"></i> 급여 설정
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="border border-slate-800 rounded-lg p-3">
                <p class="text-sm text-slate-400">총 지급액</p>
                <p class="text-xl font-bold text-slate-100 mt-1"><?= number_format($totalGross) ?><span class="text-sm font-normal text-slate-500 ml-0.5">원</span></p>
            </div>
            <div class="border border-slate-800 rounded-lg p-3">
                <p class="text-sm text-slate-400">총 공제액</p>
                <p class="text-xl font-bold text-amber-500 mt-1"><?= number_format($totalDeduction) ?><span class="text-sm font-normal text-slate-500 ml-0.5">원</span></p>
            </div>
            <div class="border border-slate-800 rounded-lg p-3">
                <p class="text-sm text-slate-400">총 실수령액</p>
                <p class="text-xl font-bold text-emerald-400 mt-1"><?= number_format($totalNet) ?><span class="text-sm font-normal text-slate-500 ml-0.5">원</span></p>
            </div>
        </div>
        <?php if ($payrollFromDB):
            $pyOrgs = array_values(array_unique(array_filter(array_column($payrollData, 'org'))));
            sort($pyOrgs);
        ?>
        <div class="bg-white rounded-xl border border-gray-200 mb-4">
            <div class="p-4 pb-3">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
                    <input type="text" id="payrollSearch" placeholder="이름으로 검색하세요..." class="w-full pl-11 pr-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 transition-colors" oninput="applyPayrollFilter()">
                </div>
            </div>
            <?php if ($showDivision && count($pyOrgs) > 1): ?>
            <div class="px-4 pb-4 relative" id="pyPillRow">
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="rf-pill" data-py="org"><span class="rf-pill__text"><?= htmlspecialchars(getOrgLabel('division')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
                </div>
                <div id="pyDrop" class="hidden rf-drop"></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
        <table id="payrollTable" class="w-full text-sm emp-table">
            <thead>
                <tr class="border-b-2 border-slate-800">
                    <th class="py-2.5 px-2 text-center font-medium text-slate-300">No</th>
                    <th class="py-2.5 px-2 text-left font-medium text-slate-300">이름</th>
                    <?php if ($showDivision): ?><th class="py-2.5 px-2 text-center font-medium text-slate-300"><?= htmlspecialchars(getOrgLabel('division')) ?></th><?php endif; ?>
                    <th class="py-2.5 px-2 text-center font-medium text-slate-300">입사일</th>
                    <?php foreach ($payPayTypes as $pt): ?>
                    <th class="py-2.5 px-2 text-right font-medium text-slate-300"><?= esc($pt['name']) ?></th>
                    <?php endforeach; ?>
                    <th class="py-2.5 px-2 text-right font-medium text-slate-300">지급액</th>
                    <th class="py-2.5 px-2 text-right font-medium text-slate-300">총공제액</th>
                    <th class="py-2.5 px-2 text-right font-medium text-slate-300">실수령액</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payrollData as $pi => $p): ?>
                <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer payroll-row" data-name="<?= htmlspecialchars($p['name']) ?>" data-org="<?= htmlspecialchars($p['org'] ?? '') ?>" onclick="openPayslipModal(<?= $p['id'] ?>)">
                    <td class="py-2.5 px-2 text-center text-slate-500 text-xs tabular-nums"><?= $pi + 1 ?></td>
                    <td class="py-2.5 px-2 font-medium text-slate-100"><?= esc($p['name']) ?><?php if (($p['contractStatus'] ?? '') !== 'signed'): ?> <span class="badge badge-warning text-[10px] ml-1">계약미체결</span><?php endif; ?></td>
                    <?php if ($showDivision): ?><td class="py-2.5 px-2 text-center text-slate-400 text-sm"><?= esc($p['org']) ?></td><?php endif; ?>
                    <td class="py-2.5 px-2 text-center text-slate-400 text-sm tabular-nums"><?= $p['hireDate'] ? date('Y.m.d', strtotime($p['hireDate'])) : '-' ?></td>
                    <?php foreach ($payPayTypes as $pt):
                        $amt = $p['items'][$pt['code']]['amount'] ?? 0;
                    ?>
                    <td class="py-2.5 px-2 text-right tabular-nums <?= $amt > 0 ? 'text-slate-300' : 'text-slate-600' ?>"><?= $amt > 0 ? number_format($amt) : '-' ?></td>
                    <?php endforeach; ?>
                    <td class="py-2.5 px-2 text-right font-medium text-slate-100 tabular-nums"><?= number_format($p['gross']) ?></td>
                    <td class="py-2.5 px-2 text-right font-medium text-amber-500 tabular-nums"><?= number_format($p['deduction']) ?></td>
                    <td class="py-2.5 px-2 text-right font-bold text-emerald-400 tabular-nums"><?= number_format($p['net']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php $fixedCols = 3 + ($showDivision ? 1 : 0) + count($payPayTypes); ?>
                <tr class="border-t-2 border-slate-700 bg-slate-950 font-semibold">
                    <td class="py-2.5 px-2 text-slate-100" colspan="<?= $fixedCols ?>">합계 (<?= count($payrollData) ?>명)</td>
                    <td class="py-2.5 px-2 text-right text-slate-100 tabular-nums"><span class="text-[10px] text-slate-500 block">총 지급액</span><?= number_format($totalGross) ?></td>
                    <td class="py-2.5 px-2 text-right text-amber-500 tabular-nums"><span class="text-[10px] text-slate-500 block">총공제액</span><?= number_format($totalDeduction) ?></td>
                    <td class="py-2.5 px-2 text-right font-bold text-emerald-400 tabular-nums"><span class="text-[10px] text-slate-500 block">총 실수령액</span><?= number_format($totalNet) ?></td>
                </tr>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="text-center py-16 text-slate-500">
            <i data-lucide="file-spreadsheet" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
            <p class="text-sm">해당 월의 급여 데이터가 없습니다.</p>
            <p class="text-sm mt-1 text-slate-600">근로계약이 체결된 직원이 있으면 자동으로 생성됩니다.</p>
        </div>
        <?php endif; ?>

        <!-- 급여 상세/편집 모달 -->
        <div id="payslipModal" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/50" onclick="closePayslipModal()"></div>
            <div class="absolute right-0 top-0 bottom-0 w-full max-w-lg bg-white overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                    <h3 class="text-base font-bold text-gray-800" id="psModalTitle">급여 상세</h3>
                    <button onclick="closePayslipModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="px-6 py-5 space-y-5" id="psModalBody">
                    <div class="flex items-center gap-3 mb-1">
                        <span id="psName" class="text-lg font-bold text-gray-800"></span>
                        <span id="psOrg" class="text-sm text-gray-500"></span>
                        <span id="psStatusBadge" class="badge badge-info"></span>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400 mb-2">
                        <span>사원번호 <strong id="psEmpNo" class="text-gray-600"></strong></span>
                        <span>성별 <strong id="psGender" class="text-gray-600"></strong></span>
                        <span>생년월일 <strong id="psBirthDate" class="text-gray-600"></strong></span>
                        <span>입사일 <strong id="psHireDate" class="text-gray-600"></strong></span>
                        <span>근로일수 <strong id="psWorkDays" class="text-gray-600"></strong></span>
                        <span>근로시간 <strong id="psWorkHours" class="text-gray-600"></strong></span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-2">지급 항목</h4>
                        <div id="psPayItems" class="grid grid-cols-2 gap-3">
                            <?php foreach ($payPayTypes as $pt): ?>
                            <?php if ($pt['has_hours']): ?>
                            <div class="col-span-2 bg-gray-50 rounded-lg p-3 -mx-1">
                                <label class="text-xs font-medium text-gray-600 mb-2 block"><?= esc($pt['name']) ?></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-400 mb-1 block">근무시간</label>
                                        <div class="flex items-center gap-1.5">
                                            <input data-pt-id="<?= $pt['id'] ?>" data-pt-code="<?= $pt['code'] ?>" data-pt-hours="h" type="number" min="0" step="1" class="ps-input ps-hours-h w-full text-right" value="0" />
                                            <span class="text-xs text-gray-400 shrink-0">시간</span>
                                            <input data-pt-id="<?= $pt['id'] ?>" data-pt-code="<?= $pt['code'] ?>" data-pt-hours="m" type="number" min="0" max="59" step="1" class="ps-input ps-hours-m w-full text-right" value="0" />
                                            <span class="text-xs text-gray-400 shrink-0">분</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 mb-1 block">수당 금액</label>
                                        <input data-pt-id="<?= $pt['id'] ?>" data-pt-code="<?= $pt['code'] ?>" data-pt-cat="pay" type="text" inputmode="numeric" class="ps-input ps-money ps-pay-input w-full text-right" placeholder="자동계산" />
                                    </div>
                                </div>
                                <span id="psOtRateLabel" class="text-xs text-gray-400 mt-1.5 block"></span>
                            </div>
                            <?php else: ?>
                            <div>
                                <label class="text-xs text-gray-500"><?= esc($pt['name']) ?></label>
                                <input data-pt-id="<?= $pt['id'] ?>" data-pt-code="<?= $pt['code'] ?>" data-pt-cat="pay" type="text" inputmode="numeric" class="ps-input ps-money ps-pay-input w-full text-right" />
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 flex justify-between text-sm font-semibold text-gray-700 border-t border-gray-100 pt-2">
                            <span>총 지급액</span><span id="psGross" class="tabular-nums">0</span>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-600 mb-2">공제 항목</h4>
                        <div id="psDeductItems" class="space-y-1.5 text-sm">
                            <?php foreach ($deductPayTypes as $dt): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500"><?= esc($dt['name']) ?><?php if ($dt['calc_rate']): ?> (<?= round((float)$dt['calc_rate'] * 100, 3) ?>%)<?php endif; ?></span>
                                <span data-deduct-id="<?= $dt['id'] ?>" data-deduct-code="<?= $dt['code'] ?>" class="tabular-nums text-gray-700">0</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 flex justify-between text-sm font-semibold text-amber-600 border-t border-gray-100 pt-2">
                            <span>총 공제액</span><span id="psDeduction" class="tabular-nums">0</span>
                        </div>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-800 border-t-2 border-gray-200 pt-3">
                        <span>실수령액</span><span id="psNet" class="tabular-nums">0</span>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">비고</label>
                        <input id="psMemo" type="text" maxlength="200" class="ps-input w-full" placeholder="메모 입력" />
                    </div>
                </div>
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-3 flex justify-end gap-2" id="psModalFooter">
                    <button id="psLoadContractBtn" onclick="loadFromContract()" class="btn btn-secondary btn-sm hidden">계약서에서 불러오기</button>
                    <button onclick="closePayslipModal()" class="btn btn-secondary btn-sm">닫기</button>
                    <button id="psSaveBtn" onclick="savePayslipItem()" class="btn btn-primary btn-sm hidden">저장</button>
                </div>
            </div>
        </div>
        <style>
        .ps-input{border:1px solid var(--zm-border,#e2e8f0);border-radius:6px;padding:6px 10px;font-size:14px;color:var(--zm-text-default,#1e293b);background:var(--zm-surface-0,#fff);outline:none;transition:border-color .15s}
        .ps-input:focus{border-color:#4F6AFF;box-shadow:0 0 0 2px rgba(79,106,255,.12)}
        .ps-input:read-only{background:var(--zm-surface-1,#f8fafc);color:var(--zm-text-muted,#64748b);cursor:default}
        .rt-input::-webkit-inner-spin-button,.rt-input::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
        .rt-input{-moz-appearance:textfield}
        .rt-unit-wrap{display:inline-flex;align-items:stretch;border:1px solid var(--zm-border,#e2e8f0);border-radius:8px;overflow:hidden;background:var(--zm-surface-1,#f7f8fa);transition:border-color .15s}
        .rt-unit-wrap:focus-within{border-color:var(--zm-primary,#4F6AFF);box-shadow:0 0 0 2px var(--zm-focus-ring,rgba(79,106,255,.15))}
        .rt-unit-wrap input{border:none!important;outline:none!important;box-shadow:none!important;background:#fff;padding:7px 10px;font-size:14px;text-align:right;font-variant-numeric:tabular-nums;width:110px;color:var(--zm-text-default,#1e293b)}
        .rt-unit-wrap input::placeholder{color:#b0b8c1}
        .rt-unit-suffix{display:flex;align-items:center;padding:0 10px;font-size:12px;font-weight:500;color:#8b95a1;white-space:nowrap;user-select:none;background:transparent;border:none}
        .rt-unit-sm{border-radius:7px}
        .rt-unit-sm input{width:56px;padding:5px 6px;font-size:13px}
        .rt-unit-sm .rt-unit-suffix{padding:0 7px;font-size:11px}
        #ratesModal .zm-segmented{padding:2px;border-radius:8px}
        #ratesModal .zm-segmented .zm-radio{padding:4px 12px;font-size:12px;border-radius:6px;min-width:unset;height:auto}
        .rt-tip-target{cursor:default}
        </style>

        <!-- 급여 설정 모달 -->
        <div id="ratesModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="if(event.target===this)closeRatesModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4 text-primary"></i>
                        급여 설정
                    </h3>
                    <button onclick="closeRatesModal()" class="p-1 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div>
                        <div class="flex items-center gap-3">
                            <h4 class="text-sm font-semibold text-gray-700">초과수당 시급</h4>
                            <div class="zm-radio-group zm-segmented">
                                <label class="cursor-pointer"><input type="radio" name="otRateMode" value="legal" checked class="sr-only peer" onchange="toggleOtMode()"><span class="zm-radio">법정 공식</span></label>
                                <label class="cursor-pointer"><input type="radio" name="otRateMode" value="custom" class="sr-only peer" onchange="toggleOtMode()"><span class="zm-radio">직접 설정</span></label>
                            </div>
                            <span id="rtOtLegalDesc" class="text-xs text-gray-400">기본급 &divide; 209h &times; 1.5</span>
                            <div id="rtOtCustom" class="hidden items-center">
                                <div class="rt-unit-wrap">
                                    <input type="text" inputmode="numeric" id="rtOtHourlyRate" placeholder="22,249" />
                                    <span class="rt-unit-suffix">원/시간</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between mb-2">
                            <h4 class="text-sm font-semibold text-gray-700">공제 요율</h4>
                            <span class="text-xs text-gray-500 bg-gray-100 rounded px-2 py-0.5">예상 금액은 기본급 300만원 기준</span>
                        </div>
                        <table class="w-full text-sm" id="rtTable">
                            <thead>
                                <tr class="text-xs text-gray-400 border-b border-gray-200">
                                    <th class="text-left font-medium pb-1.5 pl-1">항목</th>
                                    <th class="text-left font-medium pb-1.5">기준</th>
                                    <th class="text-right font-medium pb-1.5 w-24">요율</th>
                                    <th class="text-right font-medium pb-1.5 pr-1 w-24">예상 공제</th>
                                </tr>
                            </thead>
                            <tbody id="rtList"></tbody>
                            <tfoot id="rtFoot"></tfoot>
                        </table>
                    </div>
                </div>
                <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between gap-4">
                    <p class="text-xs text-amber-600 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        변경된 요율은 <strong>다음 달 급여</strong>부터 반영돼요. 이미 발급된 명세서는 그대로 유지됩니다.
                    </p>
                    <div class="flex gap-2 flex-shrink-0">
                        <button onclick="closeRatesModal()" class="btn btn-secondary btn-sm">취소</button>
                        <button onclick="saveRates()" id="rtSaveBtn" class="btn btn-primary btn-sm flex items-center gap-1">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i> 저장
                        </button>
                    </div>
                </div>
            </div>
        </div>