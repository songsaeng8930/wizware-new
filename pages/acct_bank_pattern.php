<?php
/**
 * 계좌관리 > 분류패턴 탭
 * 복합 조건 (키워드 + 거래구분 + 금액범위 + 거래처 + 반복주기) → 계정과목 학습 패턴
 */
require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$accountCategories = [];
$accountCategoriesFull = [];
try {
    $catStmt = $pdo->query("SELECT code, name, type, tax_type FROM account_categories WHERE is_active = 1 AND code NOT LIKE 'G\\_%' ESCAPE '\\\\' ORDER BY FIELD(type, '자산','부채','자본','매출','매입','비용','수익'), sort_order");
    foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $accountCategories[$row['code']] = $row['name'];
        $accountCategoriesFull[] = $row;
    }
} catch (Throwable $e) {}
?>

<div class="space-y-5">
    <!-- 필터 바 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-slate-400 mb-1">키워드</label>
                <input type="text" id="cpFilterKeyword" placeholder="적요 키워드 검색" oninput="applyLocalFilters()"
                       class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm w-48">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">계정과목</label>
                <input type="hidden" id="cpFilterAccount" value="">
                <button type="button" id="cpFilterAccountBtn" onclick="openPtPicker('filter', this)"
                    class="flex items-center justify-between gap-1 border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm hover:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-gray-300/30 transition-colors"
                    style="min-width:160px" data-code="">
                    <span class="truncate">전체</span>
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">상태</label>
                <select id="cpFilterStatus" onchange="applyLocalFilters()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">전체</option>
                    <option value="active" selected>사용중</option>
                    <option value="inactive">중지됨</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">거래구분</label>
                <select id="cpFilterTxType" onchange="applyLocalFilters()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">전체</option>
                    <option value="입금">입금</option>
                    <option value="출금">출금</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">적용 방식</label>
                <select id="cpFilterSource" onchange="applyLocalFilters()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">전체</option>
                    <option value="user">확정 (무조건 적용)</option>
                    <option value="recommend">추천 (검토용)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">정렬</label>
                <select id="cpSortBy" onchange="applyLocalFilters()" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="confidence_desc">정확도 높은 순</option>
                    <option value="confidence_asc">정확도 낮은 순</option>
                    <option value="hit_desc">적중 많은 순</option>
                    <option value="priority_asc">우선순위 높은 순</option>
                    <option value="keyword_asc">키워드 가나다순</option>
                </select>
            </div>
            <button onclick="loadCPatterns()" class="px-4 py-2 bg-slate-700 text-slate-200 text-sm rounded-lg hover:bg-slate-600">
                <i data-lucide="search" class="w-4 h-4 inline"></i> 새로고침
            </button>
            <div class="flex-1"></div>
            <button onclick="openCNewModal()" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90">
                <i data-lucide="plus" class="w-4 h-4 inline"></i> 새 패턴
            </button>
            <button onclick="openLedgerImportModal()" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-500">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 inline"></i> 원장 임포트
            </button>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span id="cpSummary"></span>
            <span id="cpFilterInfo"></span>
        </div>
    </div>

    <!-- 요약 카드 -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">전체 패턴</p>
            <p class="text-xl font-bold text-slate-100" id="cpTotal">0</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">활성 패턴</p>
            <p class="text-xl font-bold text-green-400" id="cpActive">0</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">평균 신뢰도</p>
            <p class="text-xl font-bold text-blue-400" id="cpAvgConf">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">분류 이력</p>
            <p class="text-xl font-bold text-slate-100" id="cpHistoryCount">0</p>
        </div>
    </div>

    <!-- 패턴 카드 그리드 -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="list-info-bar flex items-center justify-between">
            <span class="info-text">학습된 분류 패턴 <strong id="cpListCount">0</strong>건</span>
            <button type="button" id="cpSelectAllBtn" onclick="toggleSelectAllBtn()" class="text-xs text-slate-400 hover:text-gray-900 transition-colors">전체선택</button>
        </div>
        <!-- 선택 액션바 (선택 시에만 표시) -->
        <div id="cpActionBar" class="hidden px-4 py-2.5 bg-primary/10 border-b border-primary/20 flex items-center gap-3 flex-wrap">
            <span class="text-sm font-medium text-primary" id="cpSelectedCount">0개 선택</span>
            <button onclick="clearSelection()" class="text-xs text-slate-400 hover:text-slate-200 transition-colors">선택 해제</button>
            <div class="flex items-center gap-1.5 ml-auto">
                <button id="cpBulkEditBtn" onclick="bulkEdit()" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-700 transition-colors">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> 수정
                </button>
                <button onclick="bulkAction('lock')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    <i data-lucide="lock" class="w-3.5 h-3.5"></i> 확정
                </button>
                <button onclick="bulkAction('unlock')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-700 transition-colors">
                    <i data-lucide="lock-open" class="w-3.5 h-3.5"></i> 확정 해제
                </button>
                <button onclick="bulkAction('hide')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-700 transition-colors">
                    <i data-lucide="eye-off" class="w-3.5 h-3.5"></i> 숨기기
                </button>
                <button onclick="bulkAction('show')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-700 transition-colors">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> 보이기
                </button>
                <button onclick="bulkAction('delete')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-rose-600 text-white hover:bg-rose-500 transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> 삭제
                </button>
            </div>
        </div>
        <div id="cpBody" class="p-4">
            <div class="text-center text-slate-500 text-sm py-12">
                아직 학습된 분류 패턴이 없습니다.<br>
                <span class="text-xs text-slate-600 mt-1 block">분류 탭에서 거래를 분류하고 저장하면 패턴이 자동으로 생성됩니다.</span>
            </div>
        </div>
    </div>

    <!-- 분류 이력 (기본 접힘) -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 cursor-pointer hover:bg-slate-800/50 transition-colors" onclick="toggleCHistorySection()">
            <div class="flex items-center gap-2">
                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500 transition-transform" id="cHistoryChevron"></i>
                <h3 class="text-sm font-bold text-slate-200">최근 분류 활동</h3>
                <span class="text-xs text-slate-500" id="cHistoryBadge"></span>
            </div>
            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                <select id="cHistoryFilter" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-2 py-1 text-xs" onchange="loadCHistory()">
                    <option value="">전체</option>
                    <option value="confirm">확정</option>
                    <option value="modify">수정</option>
                    <option value="auto_classify">자동분류</option>
                    <option value="pattern_edit">패턴수정</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto hidden" id="cHistoryContent">
            <table class="w-full emp-table text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-center">일시</th>
                        <th class="px-4 py-2 text-center">액션</th>
                        <th class="px-4 py-2 text-left">거래 적요</th>
                        <th class="px-4 py-2 text-right">금액</th>
                        <th class="px-4 py-2 text-center">이전</th>
                        <th class="px-4 py-2 text-center">→</th>
                        <th class="px-4 py-2 text-center">변경 후</th>
                        <th class="px-4 py-2 text-center">수행자</th>
                    </tr>
                </thead>
                <tbody id="cHistoryBody">
                    <tr><td colspan="8" class="px-3 py-8 text-center text-slate-500 text-sm">분류 이력이 없습니다.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 패턴 수정 모달 -->
<div id="editCPatternModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center">
    <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
            <h3 class="text-sm font-bold text-slate-200" id="cpModalTitle">분류 패턴 수정</h3>
            <button onclick="closeCEditModal()" class="text-slate-400 hover:text-slate-200 cursor-pointer text-lg">&times;</button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <input type="hidden" id="editCPId">
            <div>
                <label class="block text-xs text-slate-400 mb-1">키워드 · 거래 적요/거래처에 이 단어가 들어가면 매칭돼요</label>
                <input type="text" id="editCPKeyword" placeholder="예: 나이스페이먼츠, 근로소득세, 세무법인함께" oninput="schedulePreview()" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">거래 구분</label>
                    <select id="editCPTxType" onchange="schedulePreview()" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="전체">전체</option>
                        <option value="입금">입금</option>
                        <option value="출금">출금</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">적용 방식</label>
                    <select id="editCPSource" onchange="onCpSourceChange()" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="user">확정 (무조건 적용)</option>
                        <option value="ai">추천 (검토용)</option>
                    </select>
                </div>
            </div>
            <p id="cpSourceHint" class="text-xs text-slate-500 -mt-1"></p>

            <div>
                <label class="block text-xs text-slate-400 mb-1">계정과목</label>
                <input type="hidden" id="editCPAccountCode" value="">
                <input type="hidden" id="editCPAccountName" value="">
                <button type="button" id="editCPAccountBtn" onclick="openPtPicker('modal', this)"
                    class="w-full flex items-center justify-between gap-1 border border-dashed border-slate-700 bg-slate-800 text-slate-400 rounded-lg px-3 py-2 text-sm hover:border-gray-400 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-300/30 transition-colors"
                    data-code="">
                    <span class="truncate">계정과목 선택</span>
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            <!-- 미리보기: 이 조건에 맞는 실제 거래 -->
            <div id="cpPreview" class="hidden rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3">
                <div class="text-xs text-slate-300 mb-2">이 조건에 맞는 실제 거래 <span id="cpPreviewCount" class="text-emerald-400 font-bold">0</span>건</div>
                <div id="cpPreviewSamples" class="space-y-1"></div>
            </div>

            <div class="border-t border-slate-800 pt-3">
                <button type="button" onclick="toggleCpAdvanced()" class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-200 transition-colors">
                    <i data-lucide="chevron-right" id="cpAdvChevron" class="w-3.5 h-3.5 transition-transform"></i>
                    고급 조건 (금액·거래처·반복·신뢰도) · 더 정밀하게 맞추고 싶을 때만
                </button>
            </div>

            <div id="cpAdvanced" class="hidden space-y-4 rounded-lg bg-slate-950/50 border border-slate-800 p-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">최소 금액</label>
                        <input type="number" id="editCPAmountMin" placeholder="미설정" oninput="schedulePreview()"
                               class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">최대 금액</label>
                        <input type="number" id="editCPAmountMax" placeholder="미설정" oninput="schedulePreview()"
                               class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">거래처 (적요 또는 거래처에서 매칭)</label>
                    <input type="text" id="editCPCounterparty" placeholder="미설정" oninput="schedulePreview()"
                           class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">반복 주기</label>
                        <select id="editCPRecurrence" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" onchange="toggleRecDay()">
                            <option value="none">없음</option>
                            <option value="daily">매일</option>
                            <option value="weekly">매주</option>
                            <option value="monthly">매월</option>
                            <option value="quarterly">분기</option>
                            <option value="semi_annual">반기</option>
                            <option value="annual">매년</option>
                        </select>
                    </div>
                    <div id="recDayWrap" class="hidden">
                        <label class="block text-xs text-slate-400 mb-1" id="recDayLabel">반복일</label>
                        <input type="number" id="editCPRecDay" min="1" max="31" placeholder="예: 25"
                               class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div id="cpConfWrap">
                    <label class="block text-xs text-slate-400 mb-1">신뢰도 (%)</label>
                    <div class="flex items-center gap-3">
                        <input type="range" id="editCPConfRange" min="0" max="100" step="1" class="flex-1 accent-blue-500"
                               oninput="document.getElementById('editCPConf').value = this.value">
                        <input type="number" id="editCPConf" min="0" max="100" class="w-20 border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm text-center"
                               oninput="document.getElementById('editCPConfRange').value = this.value">
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-5 py-3 border-t border-slate-800 sticky bottom-0 bg-slate-900">
            <button onclick="closeCEditModal()" class="btn btn-secondary cursor-pointer">취소</button>
            <button onclick="saveCPattern()" class="px-4 py-2 text-sm text-white bg-primary hover:opacity-90 rounded-lg cursor-pointer">저장</button>
        </div>
    </div>
</div>

<!-- 원장 임포트 모달 -->
<div id="ledgerImportModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center">
    <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-2xl mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
            <h3 class="text-sm font-bold text-slate-200">계정별원장 임포트</h3>
            <button onclick="closeLedgerImportModal()" class="text-slate-400 hover:text-slate-200 cursor-pointer text-lg">&times;</button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <p class="text-sm text-slate-400">더존 비즈온 등에서 내보낸 <strong class="text-slate-200">계정별원장 .xls/.xlsx</strong> 파일을 업로드하면, 보통예금 시트와 다른 계정 시트를 교차 대조해 분류 패턴을 자동 추출합니다.</p>
            <div id="ledgerDropZone" class="border-2 border-dashed border-slate-700 rounded-xl p-8 text-center cursor-pointer hover:border-slate-500 transition-colors"
                 onclick="document.getElementById('ledgerFileInput').click()">
                <i data-lucide="upload" class="w-8 h-8 mx-auto text-slate-500 mb-2"></i>
                <p class="text-sm text-slate-400">클릭하거나 파일을 드래그하세요</p>
                <p class="text-xs text-slate-600 mt-1">.xls, .xlsx 형식</p>
                <input type="file" id="ledgerFileInput" accept=".xls,.xlsx" class="hidden" onchange="handleLedgerFile(this)">
            </div>
            <div id="ledgerProgress" class="hidden space-y-2">
                <div class="flex items-center gap-2">
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div id="ledgerProgressBar" class="bg-emerald-500 h-2 rounded-full transition-all" style="width:0%"></div>
                    </div>
                    <span id="ledgerProgressPct" class="text-xs text-slate-400 tabular-nums w-10">0%</span>
                </div>
                <p id="ledgerProgressMsg" class="text-xs text-slate-500"></p>
            </div>
            <div id="ledgerResult" class="hidden space-y-3">
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-slate-800 rounded-lg p-3 text-center">
                        <p class="text-xs text-slate-400">분석 시트</p>
                        <p class="text-lg font-bold text-slate-200" id="ledgerSheetCount">0</p>
                    </div>
                    <div class="bg-slate-800 rounded-lg p-3 text-center">
                        <p class="text-xs text-slate-400">교차 매칭</p>
                        <p class="text-lg font-bold text-emerald-400" id="ledgerMatchCount">0</p>
                    </div>
                    <div class="bg-slate-800 rounded-lg p-3 text-center">
                        <p class="text-xs text-slate-400">추출 패턴</p>
                        <p class="text-lg font-bold text-blue-400" id="ledgerPatternCount">0</p>
                    </div>
                </div>
                <div class="bg-slate-800 rounded-lg p-3 max-h-48 overflow-y-auto">
                    <p class="text-xs text-slate-400 mb-2">추출된 패턴 미리보기 (상위 20건)</p>
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-500">
                                <th class="text-left py-1">키워드</th>
                                <th class="text-center py-1">구분</th>
                                <th class="text-left py-1">계정과목</th>
                                <th class="text-right py-1">횟수</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerPreviewBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-5 py-3 border-t border-slate-800 sticky bottom-0 bg-slate-900">
            <button onclick="closeLedgerImportModal()" class="btn btn-secondary cursor-pointer">취소</button>
            <button id="ledgerImportBtn" onclick="submitLedgerImport()" disabled class="px-4 py-2 text-sm text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                <i data-lucide="database" class="w-4 h-4 inline"></i> DB에 저장
            </button>
        </div>
    </div>
</div>

<!-- ===== 계정과목 검색 드롭다운 (공유) ===== -->
<div id="ptPickerPanel" class="hidden fixed z-[60] w-72 bg-slate-950 border border-slate-700 rounded-xl shadow-2xl overflow-hidden" style="max-height:360px">
    <div class="p-2 space-y-1.5 border-b border-slate-800">
        <div class="relative">
            <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-500 pointer-events-none"></i>
            <input type="text" id="ptPickerSearch" placeholder="코드 또는 이름 검색"
                oninput="renderPtPickerList()"
                class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-7 pr-2 py-1.5 text-xs text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
        </div>
        <div class="flex flex-nowrap gap-0.5">
            <button type="button" onclick="setPtTypeFilter('all')" data-ptf="all" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-primary text-primary font-medium whitespace-nowrap">전체</button>
            <button type="button" onclick="setPtTypeFilter('자산')" data-ptf="자산" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">자산</button>
            <button type="button" onclick="setPtTypeFilter('부채')" data-ptf="부채" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">부채</button>
            <button type="button" onclick="setPtTypeFilter('매출')" data-ptf="매출" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">매출</button>
            <button type="button" onclick="setPtTypeFilter('매입')" data-ptf="매입" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">매입</button>
            <button type="button" onclick="setPtTypeFilter('비용')" data-ptf="비용" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">비용</button>
            <button type="button" onclick="setPtTypeFilter('수익')" data-ptf="수익" class="ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">수익</button>
        </div>
    </div>
    <div id="ptPickerList" class="overflow-y-auto" style="max-height:280px"></div>
</div>

<!-- SheetJS (원장 임포트용) -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"
        integrity="sha384-EnyY0/GSHQGSxSgMwaIPzSESbqoOLSexfnSMN2AP+39Ckmn92stwABZynq1JyzdT"
        crossorigin="anonymous"></script>

<script>
const CP_API = '<?= $apiBasePath ?>/api/ai.php';
const ptCategories = <?= json_encode($accountCategoriesFull, JSON_UNESCAPED_UNICODE) ?>;
let cPatterns = [];
let cHistory = [];

// ── 검색형 계정과목 드롭다운 ──
var ptPickerState = { context: '', triggerEl: null, typeFilter: 'all' };
var _ptScrollHandler = null;

function positionPtPanel() {
    var trigger = ptPickerState.triggerEl;
    if (!trigger) return;
    var panel = document.getElementById('ptPickerPanel');
    var rect = trigger.getBoundingClientRect();
    var panelH = 360;
    var spaceBelow = window.innerHeight - rect.bottom;
    var top = spaceBelow > panelH ? rect.bottom + 4 : rect.top - panelH - 4;
    panel.style.left = Math.min(rect.left, window.innerWidth - 296) + 'px';
    panel.style.top = Math.max(4, top) + 'px';
}

function openPtPicker(context, triggerEl) {
    var panel = document.getElementById('ptPickerPanel');
    if (ptPickerState.triggerEl === triggerEl && !panel.classList.contains('hidden')) {
        closePtPicker(); return;
    }
    ptPickerState.context = context;
    ptPickerState.triggerEl = triggerEl;
    ptPickerState.typeFilter = 'all';
    updatePtFilterBtns();
    document.getElementById('ptPickerSearch').value = '';
    renderPtPickerList();
    panel.classList.remove('hidden');
    panel.style.position = 'fixed';
    positionPtPanel();
    var _ptMouseInPanel = false;
    panel.addEventListener('mouseenter', function() { _ptMouseInPanel = true; });
    panel.addEventListener('mouseleave', function() { _ptMouseInPanel = false; });
    _ptScrollHandler = function(e) {
        if (_ptMouseInPanel) return;
        if (document.getElementById('ptPickerPanel').contains(e.target)) return;
        closePtPicker();
    };
    window.addEventListener('scroll', _ptScrollHandler, true);
    window.addEventListener('resize', _ptScrollHandler);
    setTimeout(function() { document.getElementById('ptPickerSearch').focus(); }, 50);
}

function closePtPicker() {
    document.getElementById('ptPickerPanel').classList.add('hidden');
    ptPickerState.context = '';
    ptPickerState.triggerEl = null;
    if (_ptScrollHandler) {
        window.removeEventListener('scroll', _ptScrollHandler, true);
        window.removeEventListener('resize', _ptScrollHandler);
        _ptScrollHandler = null;
    }
}

function setPtTypeFilter(type) {
    ptPickerState.typeFilter = type;
    updatePtFilterBtns();
    renderPtPickerList();
}

function updatePtFilterBtns() {
    var active = ptPickerState.typeFilter;
    document.querySelectorAll('.ptf-btn').forEach(function(btn) {
        var f = btn.getAttribute('data-ptf');
        if (f === active) {
            btn.className = 'ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-primary text-primary font-medium whitespace-nowrap';
        } else {
            btn.className = 'ptf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap';
        }
    });
}

function renderPtPickerList() {
    var keyword = (document.getElementById('ptPickerSearch').value || '').toLowerCase();
    var typeFilter = ptPickerState.typeFilter;
    var currentCode = ptPickerState.triggerEl ? (ptPickerState.triggerEl.getAttribute('data-code') || '') : '';
    var list = document.getElementById('ptPickerList');
    var typeColors = {
        '자산':'text-blue-400','부채':'text-rose-400','자본':'text-purple-400',
        '매출':'text-emerald-400','매입':'text-orange-400','비용':'text-amber-400','수익':'text-cyan-400'
    };
    var html = '';
    if (ptPickerState.context === 'filter' && typeFilter === 'all' && !keyword) {
        html += '<div class="px-2 py-1"><button type="button" onclick="selectPtItem(\'\')" ' +
            'class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-slate-800 ' +
            (!currentCode ? 'text-primary font-medium' : 'text-slate-400') + '">전체 (필터 해제)</button></div>';
    }
    var lastType = '';
    var matchCount = 0;
    ptCategories.forEach(function(cat) {
        if (typeFilter !== 'all' && cat.type !== typeFilter) return;
        if (keyword) {
            var haystack = (cat.code + ' ' + cat.name).toLowerCase();
            if (haystack.indexOf(keyword) < 0) return;
        }
        if (cat.type !== lastType) {
            lastType = cat.type;
            var tc = typeColors[cat.type] || 'text-slate-400';
            html += '<div class="px-3 pt-2 pb-0.5 text-[10px] font-bold tracking-wider ' + tc + '">' + esc(cat.type) + '</div>';
        }
        var isActive = cat.code === currentCode;
        html += '<div class="px-2"><button type="button" onclick="selectPtItem(\'' + esc(cat.code) + '\')" ' +
            'class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-slate-800 truncate ' +
            (isActive ? 'text-primary font-medium bg-primary/5' : 'text-slate-200') + '">' +
            '<span class="text-slate-500 font-mono mr-1.5">' + esc(cat.code) + '</span>' +
            esc(cat.name) + '</button></div>';
        matchCount++;
    });
    if (matchCount === 0) {
        html += '<div class="px-4 py-6 text-center text-xs text-slate-500">검색 결과 없음</div>';
    }
    list.innerHTML = html;
}

function selectPtItem(code) {
    var ctx = ptPickerState.context;
    var trigger = ptPickerState.triggerEl;
    closePtPicker();
    if (!trigger) return;

    var found = code ? ptCategories.find(function(c) { return c.code === code; }) : null;
    var label = found ? found.name : '';
    var span = trigger.querySelector('span');

    if (ctx === 'filter') {
        document.getElementById('cpFilterAccount').value = code;
        if (span) span.textContent = code ? (code + ' ' + label) : '전체';
        if (code) {
            trigger.classList.remove('text-slate-200');
            trigger.classList.add('text-primary');
        } else {
            trigger.classList.remove('text-primary');
            trigger.classList.add('text-slate-200');
        }
        trigger.setAttribute('data-code', code);
        applyLocalFilters();
    } else if (ctx === 'modal') {
        document.getElementById('editCPAccountCode').value = code;
        document.getElementById('editCPAccountName').value = label;
        if (span) span.textContent = code ? (code + ' ' + label) : '계정과목 선택';
        trigger.setAttribute('data-code', code);
        if (code) {
            trigger.classList.remove('text-slate-400', 'border-dashed');
            trigger.classList.add('text-slate-200');
            trigger.style.borderStyle = 'solid';
        } else {
            trigger.classList.remove('text-slate-200');
            trigger.classList.add('text-slate-400', 'border-dashed');
        }
    }
}

document.addEventListener('click', function(e) {
    var panel = document.getElementById('ptPickerPanel');
    if (panel && !panel.classList.contains('hidden')) {
        if (!panel.contains(e.target) && !e.target.closest('[onclick*="openPtPicker"]')) {
            closePtPicker();
        }
    }
});

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function fmtAmt(n) {
    if (n === null || n === undefined || n === '') return '';
    return Number(n).toLocaleString();
}

// 적용 방식: 사람이 정한 규칙(user)은 '확정', 시스템이 만든 것(rule/ai)은 '추천'
const SOURCE_LABELS = {
    user: '<span class="zm-status-pill zm-status-confirmed"><i data-lucide="lock"></i>확정적용</span>',
    rule: '<span class="zm-status-pill zm-status-review"><i data-lucide="eye"></i>검토필요</span>',
    ai: '<span class="zm-status-pill zm-status-review"><i data-lucide="eye"></i>검토필요</span>',
};

const ACTION_LABELS = {
    confirm: '<span class="zm-status-pill zm-status-confirmed">확정</span>',
    modify: '<span class="zm-status-pill zm-status-review">수정</span>',
    auto_classify: '<span class="zm-status-pill zm-status-info">자동</span>',
    manual_classify: '<span class="zm-status-pill zm-status-muted">수동</span>',
    pattern_edit: '<span class="zm-status-pill zm-status-info">패턴수정</span>',
};

const REC_LABELS = {
    none: '', daily: '매일', weekly: '매주', monthly: '매월',
    quarterly: '분기', semi_annual: '반기', annual: '매년'
};

function buildConditionBadges(p) {
    const txBadge = p.tx_type === '입금' ? '<span class="px-1.5 py-0.5 rounded text-xs" style="background:var(--zm-deposit-bg);color:var(--zm-deposit-fg)">입금</span>'
        : p.tx_type === '출금' ? '<span class="px-1.5 py-0.5 rounded text-xs" style="background:var(--zm-withdraw-bg);color:var(--zm-withdraw-fg)">출금</span>'
        : '';

    let details = [];
    if (p.amount_min !== null || p.amount_max !== null) {
        const min = p.amount_min !== null ? fmtAmt(p.amount_min) : '';
        const max = p.amount_max !== null ? fmtAmt(p.amount_max) : '';
        const range = min && max ? `${min}~${max}원` : min ? `${min}원~` : `~${max}원`;
        details.push(`<span class="text-emerald-400">${range}</span>`);
    }
    if (p.counterparty) {
        details.push(`<span class="text-violet-400">${esc(p.counterparty)}</span>`);
    }
    if (p.recurrence && p.recurrence !== 'none') {
        let recText = REC_LABELS[p.recurrence] || p.recurrence;
        if (p.recurrence_day) recText += ` ${p.recurrence_day}일`;
        details.push(`<span class="text-orange-400">${recText}</span>`);
    }

    let html = `<div class="space-y-0.5">`;
    html += `<div class="flex items-center gap-2">`;
    html += `<span class="text-sm font-medium text-slate-100">${esc(p.keyword)}</span>`;
    html += txBadge;
    if (p.priority > 0) html += `<span class="text-xs text-slate-500">P${p.priority}</span>`;
    html += `</div>`;
    if (details.length) {
        html += `<div class="flex items-center gap-1.5 text-xs text-slate-500">${details.join('<span class="text-slate-700">·</span>')}</div>`;
    }
    html += `</div>`;
    return html;
}

async function loadCPatterns() {
    const params = new URLSearchParams({ action: 'get_classify_patterns' });

    try {
        const res = await fetch(`${CP_API}?${params}`);
        const data = await res.json();
        if (!data.ok) { alert(data.error?.message || '로드 실패'); return; }

        cPatterns = data.data.patterns || [];
        cpSelectedIds.clear();
        const summary = data.data.summary || {};

        document.getElementById('cpTotal').textContent = summary.total ?? 0;
        document.getElementById('cpActive').textContent = summary.active ?? 0;
        document.getElementById('cpAvgConf').textContent = summary.avg_confidence ? summary.avg_confidence + '%' : '-';
        document.getElementById('cpHistoryCount').textContent = summary.history_count ?? 0;

        applyLocalFilters();
    } catch (e) {
        console.error('분류 패턴 로드 실패:', e);
    }
}

function applyLocalFilters() {
    const keyword = document.getElementById('cpFilterKeyword').value.toLowerCase();
    const account = document.getElementById('cpFilterAccount').value;
    const status = document.getElementById('cpFilterStatus').value;
    const txType = document.getElementById('cpFilterTxType').value;
    const source = document.getElementById('cpFilterSource').value;
    const sortBy = document.getElementById('cpSortBy').value;

    let filtered = cPatterns.filter(function(p) {
        if (keyword && !p.keyword.toLowerCase().includes(keyword)
            && !(p.counterparty && p.counterparty.toLowerCase().includes(keyword))
            && !(p.account_name && p.account_name.toLowerCase().includes(keyword))) return false;
        if (account && p.account_code !== account) return false;
        if (status === 'active' && !p.is_active) return false;
        if (status === 'inactive' && p.is_active) return false;
        if (txType && p.tx_type !== txType) return false;
        if (source === 'user' && p.source !== 'user') return false;
        if (source === 'recommend' && p.source === 'user') return false;
        return true;
    });

    filtered.sort(function(a, b) {
        switch (sortBy) {
            case 'confidence_desc': return (parseFloat(b.confidence) || 0) - (parseFloat(a.confidence) || 0);
            case 'confidence_asc': return (parseFloat(a.confidence) || 0) - (parseFloat(b.confidence) || 0);
            case 'hit_desc': return (parseInt(b.hit_count) || 0) - (parseInt(a.hit_count) || 0);
            case 'priority_asc': return (parseInt(a.priority) || 99) - (parseInt(b.priority) || 99);
            case 'keyword_asc': return a.keyword.localeCompare(b.keyword, 'ko');
            default: return 0;
        }
    });

    document.getElementById('cpListCount').textContent = filtered.length;
    var info = document.getElementById('cpFilterInfo');
    if (filtered.length < cPatterns.length) {
        info.textContent = '(전체 ' + cPatterns.length + '개 중 ' + filtered.length + '개 표시)';
    } else {
        info.textContent = '';
    }

    renderCPatterns(filtered);
}

let cpSelectedIds = new Set();

function renderCPatterns(items) {
    const body = document.getElementById('cpBody');
    if (!items.length) {
        body.innerHTML = '<div class="text-center text-slate-500 text-sm py-12">아직 학습된 분류 패턴이 없습니다.<br><span class="text-xs text-slate-600 mt-1 block">분류 탭에서 거래를 분류하고 저장하면 패턴이 자동으로 생성됩니다.</span></div>';
        updateActionBar();
        return;
    }

    body.innerHTML = '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">' + items.map(p => {
        const conf = parseFloat(p.confidence) || 0;
        const confColor = conf >= 80 ? 'text-emerald-400' : conf >= 40 ? 'text-amber-400' : 'text-red-400';
        const barColor = conf >= 80 ? 'bg-emerald-500' : conf >= 40 ? 'bg-amber-500' : 'bg-red-500';
        const confLabel = conf >= 80 ? '정확도 높음' : conf >= 60 ? '보통' : conf >= 40 ? '낮음' : '부정확';

        const txBadge = p.tx_type === '입금'
            ? '<span class="px-1.5 py-0.5 rounded text-[11px] font-medium" style="background:var(--zm-deposit-bg);color:var(--zm-deposit-fg)">입금</span>'
            : p.tx_type === '출금'
            ? '<span class="px-1.5 py-0.5 rounded text-[11px] font-medium" style="background:var(--zm-withdraw-bg);color:var(--zm-withdraw-fg)">출금</span>'
            : '';

        const recLabel = (p.recurrence && p.recurrence !== 'none')
            ? `<span class="px-1.5 py-0.5 bg-orange-500/10 text-orange-400 rounded text-[11px]">${esc(REC_LABELS[p.recurrence] || p.recurrence)} 반복${p.recurrence_day ? ' (' + p.recurrence_day + '일)' : ''}</span>`
            : '';

        let meta = [];
        if (p.amount_min !== null || p.amount_max !== null) {
            const min = p.amount_min !== null ? fmtAmt(p.amount_min) : '';
            const max = p.amount_max !== null ? fmtAmt(p.amount_max) : '';
            const range = min && max ? `${min}~${max}원` : min ? `${min}원 이상` : `${max}원 이하`;
            meta.push(`<span class="text-emerald-400/70">금액 ${range}</span>`);
        }
        if (p.counterparty) {
            meta.push(`<span class="text-violet-400/70">거래처: ${esc(p.counterparty)}</span>`);
        }

        const priNames = { 1: '최우선', 2: '높음', 3: '높음', 4: '보통', 5: '보통', 6: '낮음' };
        const priName = priNames[p.priority] || (p.priority <= 3 ? '높음' : p.priority <= 5 ? '보통' : '낮음');
        const priorityDot = p.priority <= 3
            ? '<span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0" title="우선순위: ' + priName + '"></span>'
            : p.priority <= 5
            ? '<span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0" title="우선순위: ' + priName + '"></span>'
            : '<span class="w-1.5 h-1.5 rounded-full bg-slate-500 flex-shrink-0" title="우선순위: ' + priName + '"></span>';

        const statusBadge = p.is_active
            ? '<span class="px-1.5 py-0.5 bg-emerald-500/15 text-emerald-400 rounded text-[10px] font-medium">사용중</span>'
            : '<span class="px-1.5 py-0.5 bg-slate-700 text-slate-500 rounded text-[10px] font-medium">중지됨</span>';

        const checked = cpSelectedIds.has(p.id) ? 'checked' : '';
        const selectedBorder = cpSelectedIds.has(p.id) ? 'border-primary/50 ring-1 ring-primary/20' : 'border-slate-800';

        const isSelected = cpSelectedIds.has(p.id);
        const selClass = isSelected ? 'border-primary ring-1 ring-primary/30 bg-primary/5' : 'border-slate-800 bg-slate-950';

        return `<div class="cp-card ${selClass} rounded-xl p-3.5 hover:border-slate-600 transition-all cursor-pointer select-none ${!p.is_active ? 'opacity-60 grayscale-[30%]' : ''}" data-pid="${p.id}" onclick="onCardClick(event, ${p.id})" ondblclick="onCardDblClick(event, ${p.id})">
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                    ${priorityDot}
                    <span class="text-sm font-semibold text-slate-100 truncate">${esc(p.keyword)}</span>
                    ${txBadge}
                    ${statusBadge}
                </div>
            </div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-xs">
                    <span class="text-primary/60 font-mono text-[10px]">${esc(p.account_code)}</span>
                    ${esc(p.account_name)}
                </span>
                ${recLabel}
            </div>
            ${meta.length ? '<div class="flex items-center gap-1.5 text-xs mb-2">' + meta.join('<span class="text-slate-700">·</span>') + '</div>' : ''}
            <div class="flex items-center justify-between pt-2 border-t border-slate-800/50">
                <div class="flex items-center gap-2">
                    <div class="w-14 h-1 bg-slate-700 rounded-full overflow-hidden">
                        <div class="${barColor} h-full rounded-full" style="width:${conf}%"></div>
                    </div>
                    <span class="text-[11px] ${confColor} tabular-nums">${conf}% · ${confLabel}</span>
                </div>
                <div class="flex items-center gap-3 text-[11px]">
                    <span class="tabular-nums"><span class="text-green-400">${p.hit_count}맞음</span><span class="text-slate-600"> / </span><span class="text-red-400">${p.miss_count}틀림</span></span>
                    ${SOURCE_LABELS[p.source] || esc(p.source)}
                </div>
            </div>
        </div>`;
    }).join('') + '</div>';
    updateActionBar();
    if (window.lucide) lucide.createIcons();
}

function onCardClick(e, id) {
    if (cpSelectedIds.has(id)) cpSelectedIds.delete(id);
    else cpSelectedIds.add(id);
    refreshCardSelectionUI();
    updateActionBar();
}

function onCardDblClick(e, id) {
    e.preventDefault();
    openCEditModal(id);
}

function refreshCardSelectionUI() {
    document.querySelectorAll('#cpBody .cp-card').forEach(card => {
        const pid = parseInt(card.getAttribute('data-pid'));
        if (cpSelectedIds.has(pid)) {
            card.className = card.className
                .replace(/border-slate-800/g, 'border-primary')
                .replace(/bg-slate-950/g, 'bg-primary/5');
            if (!card.classList.contains('ring-1')) card.classList.add('ring-1', 'ring-primary/30');
        } else {
            card.className = card.className
                .replace(/border-primary/g, 'border-slate-800')
                .replace(/bg-primary\/5/g, 'bg-slate-950');
            card.classList.remove('ring-1', 'ring-primary/30');
        }
    });
    const btn = document.getElementById('cpSelectAllBtn');
    const total = document.querySelectorAll('#cpBody .cp-card').length;
    if (btn) btn.textContent = cpSelectedIds.size === total && total > 0 ? '전체 해제' : '전체선택';
}

function toggleSelectAllBtn() {
    const cards = document.querySelectorAll('#cpBody .cp-card');
    const total = cards.length;
    if (cpSelectedIds.size === total && total > 0) {
        cpSelectedIds.clear();
    } else {
        cards.forEach(card => {
            const id = parseInt(card.getAttribute('data-pid'));
            if (id) cpSelectedIds.add(id);
        });
    }
    refreshCardSelectionUI();
    updateActionBar();
}

function clearSelection() {
    cpSelectedIds.clear();
    refreshCardSelectionUI();
    updateActionBar();
}

function updateActionBar() {
    const bar = document.getElementById('cpActionBar');
    const count = cpSelectedIds.size;
    if (count > 0) {
        bar.classList.remove('hidden');
        document.getElementById('cpSelectedCount').textContent = count + '개 선택';
        const editBtn = document.getElementById('cpBulkEditBtn');
        if (editBtn) {
            editBtn.disabled = count !== 1;
            editBtn.classList.toggle('opacity-40', count !== 1);
            editBtn.classList.toggle('cursor-not-allowed', count !== 1);
        }
    } else {
        bar.classList.add('hidden');
    }
}

function bulkEdit() {
    if (cpSelectedIds.size !== 1) return;
    const id = Array.from(cpSelectedIds)[0];
    openCEditModal(id);
}

async function bulkAction(action) {
    const ids = Array.from(cpSelectedIds);
    if (!ids.length) return;

    const labels = { lock: '확정', unlock: '확정 해제', hide: '숨기기', show: '보이기', delete: '삭제' };
    const label = labels[action] || action;

    if (action === 'delete') {
        if (!(await AppUI.confirm(ids.length + '개 패턴을 삭제할까요? 이 작업은 되돌릴 수 없어요.'))) return;
    } else if (action === 'lock') {
        if (!(await AppUI.confirm(ids.length + '개 패턴을 「확정」으로 전환할까요?\n조건에 맞는 거래가 무조건 자동 지정됩니다.'))) return;
    } else {
        if (!(await AppUI.confirm(ids.length + '개 패턴을 ' + label + ' 처리할까요?'))) return;
    }

    try {
        const res = await fetch(`${CP_API}?action=bulk_pattern_action`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids, bulk_action: action }),
        });
        const data = await res.json();
        if (!data.ok) { alert('처리 실패: ' + (data.error?.message || '')); return; }
        cpSelectedIds.clear();
        loadCPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

async function toggleLockPattern(id, isLocked) {
    if (!isLocked) {
        if (!(await AppUI.confirm('이 패턴을 「확정」으로 바꾸면, 조건에 맞는 거래가 무조건 이 계정과목으로 자동 지정됩니다.\n\n· 적요(거래 내용)에 키워드가 들어간 거래에만 적용\n· 거래처만 같은 거래는 자동으로 제외 (사람이 검토)\n\n확정으로 전환할까요?'))) return;
    }
    const newSource = isLocked ? 'ai' : 'user';
    try {
        const res = await fetch(`${CP_API}?action=update_classify_pattern`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, source: newSource }),
        });
        const data = await res.json();
        if (!data.ok) { alert('전환 실패'); return; }
        loadCPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

async function toggleCPattern(id, currentActive) {
    try {
        const res = await fetch(`${CP_API}?action=toggle_classify_pattern`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, is_active: currentActive ? 0 : 1 }),
        });
        const data = await res.json();
        if (!data.ok) { alert('변경 실패'); return; }
        loadCPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

async function deleteCPattern(id) {
    if (!(await AppUI.confirm('이 분류 패턴을 삭제할까요?'))) return;
    try {
        const res = await fetch(`${CP_API}?action=delete_classify_pattern`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (!data.ok) { alert('삭제 실패'); return; }
        loadCPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

function toggleRecDay() {
    const rec = document.getElementById('editCPRecurrence').value;
    const wrap = document.getElementById('recDayWrap');
    const label = document.getElementById('recDayLabel');
    if (rec === 'none' || rec === 'daily') {
        wrap.classList.add('hidden');
    } else {
        wrap.classList.remove('hidden');
        label.textContent = rec === 'weekly' ? '요일 (1=월~7=일)' : '반복일 (1~31)';
        document.getElementById('editCPRecDay').max = rec === 'weekly' ? 7 : 31;
    }
}

function openCEditModal(id) {
    const p = cPatterns.find(x => x.id == id);
    if (!p) return;
    document.getElementById('editCPId').value = p.id;
    document.getElementById('editCPKeyword').value = p.keyword || '';
    document.getElementById('editCPTxType').value = p.tx_type || '전체';
    document.getElementById('editCPSource').value = (p.source === 'user') ? 'user' : 'ai';
    document.getElementById('editCPAccountCode').value = p.account_code || '';
    document.getElementById('editCPAccountName').value = p.account_name || '';
    var editBtn = document.getElementById('editCPAccountBtn');
    var editSpan = editBtn.querySelector('span');
    if (p.account_code) {
        editSpan.textContent = p.account_code + ' ' + (p.account_name || '');
        editBtn.setAttribute('data-code', p.account_code);
        editBtn.classList.remove('text-slate-400', 'border-dashed');
        editBtn.classList.add('text-slate-200');
        editBtn.style.borderStyle = 'solid';
    } else {
        editSpan.textContent = '계정과목 선택';
        editBtn.setAttribute('data-code', '');
        editBtn.classList.remove('text-slate-200');
        editBtn.classList.add('text-slate-400', 'border-dashed');
    }
    document.getElementById('editCPAmountMin').value = p.amount_min ?? '';
    document.getElementById('editCPAmountMax').value = p.amount_max ?? '';
    document.getElementById('editCPCounterparty').value = p.counterparty || '';
    document.getElementById('editCPRecurrence').value = p.recurrence || 'none';
    document.getElementById('editCPRecDay').value = p.recurrence_day ?? '';
    toggleRecDay();
    const conf = parseFloat(p.confidence) || 0;
    document.getElementById('editCPConf').value = conf;
    document.getElementById('editCPConfRange').value = conf;

    document.getElementById('cpModalTitle').textContent = '분류 패턴 수정';
    const hasAdv = (p.amount_min != null) || (p.amount_max != null) || !!p.counterparty || (p.recurrence && p.recurrence !== 'none');
    setCpAdvanced(hasAdv);
    onCpSourceChange();

    const modal = document.getElementById('editCPatternModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.lucide) lucide.createIcons();
    runCpPreview();
}

// 새 패턴 · 빈 모달 열기
function openCNewModal() {
    document.getElementById('editCPId').value = '';
    document.getElementById('editCPKeyword').value = '';
    document.getElementById('editCPTxType').value = '전체';
    document.getElementById('editCPSource').value = 'user';
    document.getElementById('editCPAccountCode').value = '';
    document.getElementById('editCPAccountName').value = '';
    var btn = document.getElementById('editCPAccountBtn');
    btn.querySelector('span').textContent = '계정과목 선택';
    btn.setAttribute('data-code', '');
    btn.classList.remove('text-slate-200');
    btn.classList.add('text-slate-400', 'border-dashed');
    btn.style.borderStyle = '';
    document.getElementById('editCPAmountMin').value = '';
    document.getElementById('editCPAmountMax').value = '';
    document.getElementById('editCPCounterparty').value = '';
    document.getElementById('editCPRecurrence').value = 'none';
    document.getElementById('editCPRecDay').value = '';
    toggleRecDay();
    document.getElementById('editCPConf').value = 70;
    document.getElementById('editCPConfRange').value = 70;
    document.getElementById('cpModalTitle').textContent = '새 패턴 추가';
    setCpAdvanced(false);
    onCpSourceChange();
    document.getElementById('cpPreview').classList.add('hidden');
    const modal = document.getElementById('editCPatternModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.lucide) lucide.createIcons();
}

// 미리보기: 입력한 조건에 맞는 실제 거래를 실시간으로 보여줌
let _cpPreviewTimer = null;
function schedulePreview() {
    clearTimeout(_cpPreviewTimer);
    _cpPreviewTimer = setTimeout(runCpPreview, 350);
}

async function runCpPreview() {
    const keyword = document.getElementById('editCPKeyword').value.trim();
    const box = document.getElementById('cpPreview');
    if (keyword.length < 2) { box.classList.add('hidden'); return; }

    const amtMin = document.getElementById('editCPAmountMin').value;
    const amtMax = document.getElementById('editCPAmountMax').value;
    const body = {
        keyword: keyword,
        tx_type: document.getElementById('editCPTxType').value,
        amount_min: amtMin !== '' ? parseInt(amtMin) : null,
        amount_max: amtMax !== '' ? parseInt(amtMax) : null,
        counterparty: document.getElementById('editCPCounterparty').value.trim() || null,
    };

    try {
        const res = await fetch(`${CP_API}?action=preview_pattern_match`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!data.ok) return;
        const count = data.data.count || 0;
        const samples = data.data.samples || [];

        document.getElementById('cpPreviewCount').textContent = count;
        const wrap = document.getElementById('cpPreviewSamples');
        wrap.innerHTML = '';

        if (count === 0) {
            const warn = document.createElement('div');
            warn.className = 'text-xs text-amber-400';
            warn.textContent = '맞는 거래가 없어요. 키워드를 확인해보세요.';
            wrap.appendChild(warn);
        } else {
            samples.forEach(function(s) {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between text-xs text-slate-300';
                const left = document.createElement('span');
                left.className = 'truncate';
                left.textContent = (s.tx_type || '') + ' · ' + (s.description || '');
                const right = document.createElement('span');
                right.className = 'tabular-nums text-slate-400 flex-shrink-0 ml-2';
                right.textContent = Number(s.amount).toLocaleString() + '원';
                row.appendChild(left);
                row.appendChild(right);
                wrap.appendChild(row);
            });
            if (count > samples.length) {
                const more = document.createElement('div');
                more.className = 'text-xs text-slate-500';
                more.textContent = '외 ' + (count - samples.length) + '건';
                wrap.appendChild(more);
            }
        }
        box.classList.remove('hidden');
    } catch(e) { /* 미리보기 실패는 조용히 무시 */ }
}

// 적용 방식(확정/추천)에 따라 힌트 + 신뢰도 표시 전환
function onCpSourceChange() {
    const src = document.getElementById('editCPSource').value;
    const hint = document.getElementById('cpSourceHint');
    const confWrap = document.getElementById('cpConfWrap');
    if (src === 'user') {
        hint.textContent = '확정: 이 조건에 맞으면 무조건 이 계정과목으로 자동 지정돼요. (신뢰도 100% 고정)';
        confWrap.classList.add('hidden');
    } else {
        hint.textContent = '추천: 자동 분류 후보로만 제시되고, 사람이 검토해요.';
        confWrap.classList.remove('hidden');
    }
}

function setCpAdvanced(show) {
    const adv = document.getElementById('cpAdvanced');
    const chev = document.getElementById('cpAdvChevron');
    if (show) { adv.classList.remove('hidden'); if (chev) chev.style.transform = 'rotate(90deg)'; }
    else { adv.classList.add('hidden'); if (chev) chev.style.transform = ''; }
}

function toggleCpAdvanced() {
    setCpAdvanced(document.getElementById('cpAdvanced').classList.contains('hidden'));
}

function closeCEditModal() {
    const modal = document.getElementById('editCPatternModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function saveCPattern() {
    const id = document.getElementById('editCPId').value;
    const acctCode = document.getElementById('editCPAccountCode').value;
    const acctName = document.getElementById('editCPAccountName').value;

    const amtMin = document.getElementById('editCPAmountMin').value;
    const amtMax = document.getElementById('editCPAmountMax').value;
    const recDay = document.getElementById('editCPRecDay').value;

    const body = {
        keyword: document.getElementById('editCPKeyword').value.trim(),
        tx_type: document.getElementById('editCPTxType').value,
        account_code: acctCode,
        account_name: acctName,
        amount_min: amtMin !== '' ? parseInt(amtMin) : null,
        amount_max: amtMax !== '' ? parseInt(amtMax) : null,
        counterparty: document.getElementById('editCPCounterparty').value.trim() || null,
        recurrence: document.getElementById('editCPRecurrence').value,
        recurrence_day: recDay !== '' ? parseInt(recDay) : null,
        confidence: parseFloat(document.getElementById('editCPConf').value) || 0,
        source: document.getElementById('editCPSource').value,
    };
    if (!body.keyword) { alert('키워드(적요)를 입력하세요.'); return; }
    if (!body.account_code) { alert('계정과목을 선택하세요.'); return; }

    // id 있으면 수정, 없으면 신규 생성
    const isNew = !id;
    const action = isNew ? 'create_classify_pattern' : 'update_classify_pattern';
    if (!isNew) body.id = parseInt(id);

    try {
        const res = await fetch(`${CP_API}?action=${action}`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!data.ok) { alert((isNew ? '생성' : '수정') + ' 실패: ' + (data.error?.message || '')); return; }
        closeCEditModal();
        loadCPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

function toggleCHistorySection() {
    const content = document.getElementById('cHistoryContent');
    const chevron = document.getElementById('cHistoryChevron');
    const isHidden = content.classList.contains('hidden');
    content.classList.toggle('hidden');
    chevron.style.transform = isHidden ? 'rotate(90deg)' : '';
    if (isHidden && !cHistory.length) loadCHistory();
}

async function loadCHistory() {
    const action = document.getElementById('cHistoryFilter').value;
    const params = new URLSearchParams({ action: 'get_classify_history' });
    if (action) params.set('filter_action', action);

    try {
        const res = await fetch(`${CP_API}?${params}`);
        const data = await res.json();
        cHistory = data.data?.history || [];
        document.getElementById('cHistoryBadge').textContent = cHistory.length ? cHistory.length + '건' : '';
        renderCHistory(cHistory);
    } catch(e) {
        console.error('분류 이력 로드 실패:', e);
    }
}

function renderCHistory(items) {
    const body = document.getElementById('cHistoryBody');
    if (!items.length) {
        body.innerHTML = '<tr><td colspan="8" class="px-3 py-8 text-center text-slate-500 text-sm">분류 이력이 없습니다.</td></tr>';
        return;
    }

    body.innerHTML = items.map(h => {
        const isPE = h.action === 'pattern_edit';
        return `<tr class="border-b border-slate-800 hover:bg-slate-950">
            <td class="px-4 py-2 text-center text-xs text-slate-400 tabular-nums">${h.created_at?.slice(0,16) || '-'}</td>
            <td class="px-4 py-2 text-center">${ACTION_LABELS[h.action] || esc(h.action)}</td>
            <td class="px-4 py-2 text-sm text-slate-300">${isPE ? '<span class="text-cyan-400">패턴 #' + (h.pattern_id || '?') + '</span>' : esc(h.tx_desc || '-')}</td>
            <td class="px-4 py-2 text-right text-sm tabular-nums text-slate-300">${isPE ? '-' : (h.tx_amount ? Number(h.tx_amount).toLocaleString() + '원' : '-')}</td>
            <td class="px-4 py-2 text-center text-xs text-slate-500">${esc(h.old_account_code || '-')}</td>
            <td class="px-4 py-2 text-center text-slate-600">→</td>
            <td class="px-4 py-2 text-center"><span class="px-2 py-0.5 bg-primary/10 text-primary rounded-full text-xs">${esc(h.new_account_code || '-')} ${esc(h.new_account_name || '')}</span></td>
            <td class="px-4 py-2 text-center text-xs text-slate-400">${esc(h.actor || '-')}</td>
        </tr>`;
    }).join('');
}

// ─── 원장 임포트 ───
let ledgerPatterns = [];

function openLedgerImportModal() {
    document.getElementById('ledgerImportModal').classList.remove('hidden');
    document.getElementById('ledgerImportModal').classList.add('flex');
    document.getElementById('ledgerProgress').classList.add('hidden');
    document.getElementById('ledgerResult').classList.add('hidden');
    document.getElementById('ledgerDropZone').classList.remove('hidden');
    document.getElementById('ledgerImportBtn').disabled = true;
    document.getElementById('ledgerFileInput').value = '';
    ledgerPatterns = [];
    if (window.lucide) lucide.createIcons();
}

function closeLedgerImportModal() {
    document.getElementById('ledgerImportModal').classList.add('hidden');
    document.getElementById('ledgerImportModal').classList.remove('flex');
}

function setLedgerProgress(pct, msg) {
    document.getElementById('ledgerProgressBar').style.width = pct + '%';
    document.getElementById('ledgerProgressPct').textContent = pct + '%';
    document.getElementById('ledgerProgressMsg').textContent = msg;
}

function handleLedgerFile(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('ledgerDropZone').classList.add('hidden');
    document.getElementById('ledgerProgress').classList.remove('hidden');
    setLedgerProgress(10, '파일 읽는 중...');

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            setLedgerProgress(20, 'Excel 파싱 중...');
            const data = new Uint8Array(e.target.result);
            const wb = XLSX.read(data, { type: 'array', codepage: 949 });
            processLedgerWorkbook(wb);
        } catch (err) {
            alert('파일 파싱 실패: ' + err.message);
            document.getElementById('ledgerDropZone').classList.remove('hidden');
            document.getElementById('ledgerProgress').classList.add('hidden');
        }
    };
    reader.readAsArrayBuffer(file);
}

function processLedgerWorkbook(wb) {
    setLedgerProgress(30, '시트 분석 중...');

    const SKIP_CODES = new Set(['10300', '21300', '21900', '33100', '34100', '37600', '96200']);
    const codeRegex = /\((\d{4,5})\)/;

    let bankSheet = null;
    const otherSheets = [];

    for (const name of wb.SheetNames) {
        const m = name.match(codeRegex);
        if (!m) continue;
        const code = m[1];
        if (code === '10300') {
            bankSheet = { name, code, sheet: wb.Sheets[name] };
        } else if (!SKIP_CODES.has(code)) {
            const acctName = name.replace(/^\d+_/, '').replace(/\(\d+\)/, '').trim();
            otherSheets.push({ name, code, acctName, sheet: wb.Sheets[name] });
        }
    }

    if (!bankSheet) {
        alert('보통예금(10300) 시트를 찾을 수 없습니다.\n계정별원장 형식의 파일인지 확인하세요.');
        document.getElementById('ledgerDropZone').classList.remove('hidden');
        document.getElementById('ledgerProgress').classList.add('hidden');
        return;
    }

    setLedgerProgress(40, '다른 시트 인덱스 구축 중...');
    const otherIndex = {};

    for (const s of otherSheets) {
        const rows = XLSX.utils.sheet_to_json(s.sheet, { header: 1, defval: '' });
        for (let r = 4; r < rows.length; r++) {
            const row = rows[r];
            const dateVal = String(row[0] || '').trim();
            const desc = String(row[1] || '').trim();
            const cp = String(row[3] || '').trim();
            const debit = Number(String(row[4]).replace(/,/g, '')) || 0;
            const credit = Number(String(row[5]).replace(/,/g, '')) || 0;

            if (!dateVal || desc.includes('전기이월') || (desc.includes('월') && desc.includes('계'))
                || (desc.includes('합') && desc.includes('계')) || (desc.includes('누') && desc.includes('계'))) continue;

            if (debit !== 0) {
                const key = `${dateVal}|${Math.abs(Math.round(debit))}|credit`;
                if (!otherIndex[key]) otherIndex[key] = [];
                otherIndex[key].push({ code: s.code, name: s.acctName, desc, cp });
            }
            if (credit !== 0) {
                const key = `${dateVal}|${Math.abs(Math.round(credit))}|debit`;
                if (!otherIndex[key]) otherIndex[key] = [];
                otherIndex[key].push({ code: s.code, name: s.acctName, desc, cp });
            }
        }
    }

    setLedgerProgress(60, '보통예금 교차 매칭 중...');
    const bankRows = XLSX.utils.sheet_to_json(bankSheet.sheet, { header: 1, defval: '' });
    const matches = [];

    for (let r = 4; r < bankRows.length; r++) {
        const row = bankRows[r];
        const dateVal = String(row[0] || '').trim();
        const desc = String(row[1] || '').trim();
        const debit = parseFloat(row[4]) || 0;
        const credit = parseFloat(row[5]) || 0;

        if (!dateVal || !desc || desc.includes('전기이월') || (desc.includes('월') && desc.includes('계'))) continue;

        let txType, amt, side;
        if (debit !== 0) {
            amt = Math.abs(Math.round(debit));
            side = 'debit';
            txType = '입금';
        } else if (credit !== 0) {
            amt = Math.abs(Math.round(credit));
            side = 'credit';
            txType = '출금';
        } else continue;

        const found = otherIndex[`${dateVal}|${amt}|${side}`];
        if (found && found.length) {
            const best = found.find(f => f.code !== '12600') || found[0];
            matches.push({ bankDesc: desc, txType, amount: amt, accountCode: best.code, accountName: best.name, matchedCp: best.cp });
        }
    }

    setLedgerProgress(80, '패턴 집계 중...');
    const agg = {};
    for (const m of matches) {
        const kw = normalizeLedgerKeyword(m.bankDesc);
        if (!kw || kw.length < 2) continue;
        const key = `${kw}|${m.accountCode}`;
        if (!agg[key]) {
            agg[key] = { keyword: kw, accountCode: m.accountCode, accountName: m.accountName, txType: m.txType, amounts: [], counterparties: new Set(), count: 0 };
        }
        agg[key].amounts.push(m.amount);
        if (m.matchedCp) agg[key].counterparties.add(m.matchedCp);
        agg[key].count++;
    }

    ledgerPatterns = Object.values(agg)
        .filter(p => p.count >= 2)
        .map(p => {
            const amounts = p.amounts.filter(a => a > 0);
            const rawMin = amounts.length ? Math.min(...amounts) : null;
            const rawMax = amounts.length ? Math.max(...amounts) : null;
            const margin = rawMin ? Math.max(10000, Math.round(rawMin * 0.2)) : 10000;
            const cpArr = Array.from(p.counterparties);

            const rec = detectLedgerRecurrence(p.count, p.keyword);
            let priority = 1;
            if (rawMin !== null) priority += 2;
            if (cpArr.length) priority += 2;
            if (rec !== 'none') priority += 1;

            return {
                keyword: p.keyword.slice(0, 100),
                tx_type: p.txType,
                account_code: p.accountCode,
                account_name: p.accountName,
                amount_min: rawMin !== null ? Math.max(0, rawMin - margin) : null,
                amount_max: rawMax !== null ? rawMax + Math.max(10000, Math.round(rawMax * 0.2)) : null,
                counterparty: cpArr[0] ? cpArr[0].slice(0, 100) : null,
                recurrence: rec,
                priority,
                confidence: Math.min(95, 50 + p.count * 5),
                hit_count: p.count,
            };
        })
        .sort((a, b) => b.hit_count - a.hit_count);

    setLedgerProgress(100, '완료');
    showLedgerResult(otherSheets.length, matches.length, ledgerPatterns);
}

function normalizeLedgerKeyword(desc) {
    let s = desc.trim();
    s = s.replace(/20\d{2}년?\s*\d{1,2}월?\s*(분|달)?\s*/g, '');
    s = s.replace(/\d{1,2}월\s*(분|달)?\s*/g, '');
    s = s.replace(/\d{4}[.\-\/]\d{2}[.\-\/]\d{2}/g, '');
    s = s.replace(/\s+\d[\d,]*\.?\d*\s*$/g, '');
    s = s.replace(/\s+[Xx×]\s*\d[\d,]*/g, '');
    return s.replace(/\s+/g, ' ').trim();
}

function detectLedgerRecurrence(hitCount, keyword) {
    const hints = ['렌탈', '임차', '임대', '보험', '급여', '이자', '인건', '관리비', '보육료', '통신'];
    const hasHint = hints.some(h => keyword.includes(h));
    if (hitCount >= 10 && hasHint) return 'monthly';
    if (hitCount >= 10 && hitCount <= 14) return 'monthly';
    if (hitCount >= 3 && hitCount <= 5 && hasHint) return 'quarterly';
    return 'none';
}

function showLedgerResult(sheetCount, matchCount, patterns) {
    document.getElementById('ledgerResult').classList.remove('hidden');
    document.getElementById('ledgerSheetCount').textContent = sheetCount;
    document.getElementById('ledgerMatchCount').textContent = matchCount.toLocaleString();
    document.getElementById('ledgerPatternCount').textContent = patterns.length;
    document.getElementById('ledgerImportBtn').disabled = patterns.length === 0;

    const preview = patterns.slice(0, 20);
    document.getElementById('ledgerPreviewBody').innerHTML = preview.map(p => {
        const txBadge = p.tx_type === '입금'
            ? '<span class="px-1 py-0.5 rounded" style="background:var(--zm-deposit-bg);color:var(--zm-deposit-fg)">입금</span>'
            : '<span class="px-1 py-0.5 rounded" style="background:var(--zm-withdraw-bg);color:var(--zm-withdraw-fg)">출금</span>';
        return `<tr class="border-b border-slate-700/50">
            <td class="py-1 text-slate-300">${esc(p.keyword)}</td>
            <td class="py-1 text-center">${txBadge}</td>
            <td class="py-1 text-slate-400">${esc(p.account_code)} ${esc(p.account_name)}</td>
            <td class="py-1 text-right text-slate-400">${p.hit_count}</td>
        </tr>`;
    }).join('');
}

async function submitLedgerImport() {
    if (!ledgerPatterns.length) return;
    const btn = document.getElementById('ledgerImportBtn');
    btn.disabled = true;
    btn.textContent = '저장 중...';

    try {
        const res = await fetch(`${CP_API}?action=bulk_import_patterns`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ patterns: ledgerPatterns }),
        });
        const data = await res.json();
        if (!data.ok) {
            alert('임포트 실패: ' + (data.error?.message || ''));
            return;
        }
        alert(`임포트 완료!\n저장: ${data.data.imported}건 / 건너뜀: ${data.data.skipped}건` +
              (data.data.errors?.length ? `\n오류: ${data.data.errors.length}건` : ''));
        closeLedgerImportModal();
        loadCPatterns();
    } catch (e) {
        alert('오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="database" class="w-4 h-4 inline"></i> DB에 저장';
        if (window.lucide) lucide.createIcons();
    }
}

// 드래그앤드롭
const ledgerDZ = document.getElementById('ledgerDropZone');
if (ledgerDZ) {
    ledgerDZ.addEventListener('dragover', e => { e.preventDefault(); ledgerDZ.classList.add('border-emerald-500'); });
    ledgerDZ.addEventListener('dragleave', () => ledgerDZ.classList.remove('border-emerald-500'));
    ledgerDZ.addEventListener('drop', e => {
        e.preventDefault();
        ledgerDZ.classList.remove('border-emerald-500');
        const file = e.dataTransfer.files[0];
        if (file) {
            if (!/\.(xls|xlsx)$/i.test(file.name)) {
                alert('.xls 또는 .xlsx 파일만 지원합니다.');
                return;
            }
            const input = document.getElementById('ledgerFileInput');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            handleLedgerFile(input);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadCPatterns();
    loadCHistory();
    if (window.lucide) lucide.createIcons();
});
</script>
