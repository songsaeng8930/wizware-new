<?php
if (!isset($banks)) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/bankapi.php';
    $banks = BANKAPI_BANKS;
    $apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
}
?>

<!-- ===== 온보딩 (계좌 0개) ===== -->
<div id="onboarding" class="hidden">
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-5">
            <i data-lucide="landmark" class="w-8 h-8 text-gray-600"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-100 mb-2">계좌를 연결하세요</h3>
        <p class="text-sm text-slate-400 mb-6 leading-relaxed">
            은행 계좌를 연결하면<br>거래내역을 실시간으로 조회할 수 있습니다.
        </p>
        <button onclick="openRegisterModal()" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-white bg-primary rounded-xl hover:opacity-90 transition-opacity">
            <i data-lucide="plus" class="w-4 h-4"></i>계좌 연결하기
        </button>
    </div>
</div>

<!-- ===== 에러 배너 (실제 API 오류 전용) ===== -->
<div id="apiError" class="hidden"></div>

<!-- ===== 사업자번호 미등록 알림 ===== -->
<div id="residentAlert" class="hidden mb-4 p-4 bg-amber-950/40 border border-amber-800/40 rounded-xl">
    <div class="flex items-center gap-3">
        <i data-lucide="info" class="w-5 h-5 text-amber-400 flex-shrink-0"></i>
        <p class="text-sm text-amber-300 flex-1">거래내역 조회에 필요한 사업자(주민)번호가 등록되지 않았습니다.</p>
        <button onclick="openResidentModal()" class="text-sm text-amber-400 hover:text-amber-300 font-medium whitespace-nowrap">등록하기</button>
    </div>
</div>

<!-- ===== 로딩 ===== -->
<div id="pageLoading" class="py-16 text-center">
    <div class="inline-flex flex-col items-center gap-4">
        <div class="relative w-12 h-12">
            <div class="absolute inset-0 rounded-full border-2 border-slate-700"></div>
            <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-primary animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <i data-lucide="landmark" class="w-4 h-4 text-slate-400"></i>
            </div>
        </div>
        <div class="space-y-1">
            <p class="text-sm font-medium text-slate-300">계좌 정보를 불러오는 중</p>
            <p class="text-xs text-slate-500">잠시만 기다려 주세요</p>
        </div>
    </div>
</div>

<!-- ===== 메인 조회 폼 ===== -->
<div id="mainView" class="hidden space-y-4">
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
        <p class="text-sm font-semibold text-slate-200">거래내역 조회</p>

        <!-- 1행: 계좌 드롭다운 + 추가 버튼 -->
        <div>
            <label class="block text-sm text-slate-400 mb-1.5">계좌</label>
            <div class="flex items-center gap-2">
                <div class="relative flex-1 min-w-0" id="customSelectWrap">
                    <input type="hidden" id="accountSelect" value="">
                    <button type="button" onclick="toggleDropdown()" id="dropdownTrigger"
                        class="w-full flex items-center justify-between bg-slate-950 border border-slate-800 rounded-lg px-3 py-2.5 text-sm text-slate-100 hover:border-slate-700 focus:outline-none focus:ring-2 focus:ring-gray-300/30 transition-colors">
                        <span id="dropdownLabel" class="truncate">계좌를 선택하세요</span>
                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0 ml-2 transition-transform" id="dropdownArrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="dropdownPanel" class="hidden absolute z-30 left-0 right-0 mt-1 bg-slate-950 border border-slate-800 rounded-lg shadow-xl overflow-hidden">
                        <div id="dropdownList" class="max-h-60 overflow-y-auto py-1"></div>
                    </div>
                </div>
                <button onclick="openRegisterModal()" class="btn btn-secondary whitespace-nowrap flex-shrink-0">
                    <i data-lucide="plus" class="w-4 h-4"></i>계좌 추가
                </button>
            </div>
        </div>

        <!-- 2행: 기간 칩 + 날짜 + 조회 (한 흐름) -->
        <div class="flex items-end gap-3">
            <div>
                <label class="block text-sm text-slate-400 mb-1.5">기간</label>
                <div class="flex items-center gap-2">
                    <div class="inline-flex rounded-lg border border-slate-800 overflow-hidden flex-shrink-0">
                        <button type="button" onclick="setPeriod('week')" id="periodWeek" class="zm-tab px-3 py-2 text-sm transition-colors">1주일</button>
                        <button type="button" onclick="setPeriod('month')" id="periodMonth" class="zm-tab zm-tab-active px-3 py-2 text-sm transition-colors">이번 달</button>
                        <button type="button" onclick="setPeriod('prev')" id="periodPrev" class="zm-tab px-3 py-2 text-sm transition-colors">전월</button>
                        <button type="button" onclick="setPeriod('3month')" id="period3month" class="zm-tab px-3 py-2 text-sm transition-colors">3개월</button>
                        <button type="button" onclick="setPeriod('year')" id="periodYear" class="zm-tab px-3 py-2 text-sm transition-colors">올해</button>
                        <button type="button" onclick="setPeriod('custom')" id="periodCustom" class="zm-tab px-3 py-2 text-sm transition-colors">직접</button>
                    </div>
                    <input type="date" id="txStartDate" value="<?= date('Y-m-01') ?>" class="w-36 bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <span class="text-slate-600 text-sm flex-shrink-0">~</span>
                    <input type="date" id="txEndDate" value="<?= date('Y-m-d') ?>" class="w-36 bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                </div>
            </div>
            <div class="ml-auto">
                <label class="block text-sm text-slate-400 mb-1.5 invisible">.</label>
                <button onclick="requestFetch()" id="btnFetch" class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-opacity whitespace-nowrap cursor-pointer">
                    <i data-lucide="search" class="w-4 h-4" id="btnFetchIcon"></i><span id="btnFetchText">거래내역 조회</span>
                </button>
            </div>
        </div>

        <!-- 정보 라인 -->
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span id="ownerInfo" class="hidden inline-flex items-center gap-1">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span id="ownerInfoText">예금주 확인됨</span>
            </span>
            <span id="selectedAccountActions" class="hidden inline-flex items-center gap-1">
                <span class="text-slate-700">·</span>
                <button onclick="openAliasModalForSelected()" class="text-slate-400 hover:text-slate-200 hover:underline">별칭 수정</button>
                <span class="text-slate-700">·</span>
                <button onclick="openDeleteModalForSelected()" class="text-slate-400 hover:text-slate-200 hover:underline">연결 해제</button>
            </span>
            <span id="infoSep1" class="hidden text-slate-700">|</span>
            <span id="residentStatus" class="inline-flex items-center gap-1">
                <span id="residentStatusText">사업자번호: 미등록</span>
                <button onclick="openResidentModal()" id="residentActionBtn" class="text-slate-400 hover:text-slate-200 hover:underline">등록</button>
            </span>
            <span class="text-slate-700">|</span>
            <span id="pwStatusText" class="inline-flex items-center gap-1">
                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                비밀번호 미저장
            </span>
            <span id="syncWarningWrap" class="hidden inline-flex items-center gap-1">
                <span class="text-slate-700">|</span>
                <span class="inline-flex items-center gap-1 text-amber-400">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                    <span id="syncWarningText"></span>
                </span>
            </span>
        </div>
    </div>

    <!-- 조회 에러 -->
    <div id="txError" class="hidden p-4 bg-rose-950/50 border border-rose-800/50 rounded-xl">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-400 flex-shrink-0 mt-0.5"></i>
            <div>
                <p class="text-sm font-medium text-rose-300" id="txErrorTitle">조회 실패</p>
                <p class="text-sm text-rose-400/80 mt-0.5" id="txErrorMsg"></p>
            </div>
        </div>
    </div>

    <!-- 요약 카드 -->
    <div id="txSummary" class="hidden grid grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1 uppercase tracking-wide">현재 잔액</p>
                <p class="text-xl font-bold text-slate-100" id="sumBalance">0원</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center">
                <i data-lucide="wallet" class="w-5 h-5 text-slate-400"></i>
            </div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1 uppercase tracking-wide">거래 건수</p>
                <p class="text-xl font-bold text-slate-100" id="sumCount">0건</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center">
                <i data-lucide="receipt-text" class="w-5 h-5 text-slate-400"></i>
            </div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1 uppercase tracking-wide">기간내 총 출금</p>
                <p class="text-xl font-bold text-rose-400" id="sumWithdraw">0원</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center">
                <i data-lucide="upload" class="w-5 h-5 text-rose-400"></i>
            </div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium mb-1 uppercase tracking-wide">기간내 총 입금</p>
                <p class="text-xl font-bold text-emerald-500" id="sumDeposit">0원</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                <i data-lucide="download" class="w-5 h-5 text-emerald-500"></i>
            </div>
        </div>
    </div>

    <!-- 조회 로딩 (첫 데이터 전) -->
    <div id="txLoading" class="hidden py-10">
        <div class="max-w-sm mx-auto">
            <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative w-10 h-10 flex-shrink-0">
                        <div class="absolute inset-0 rounded-full border-2 border-slate-700"></div>
                        <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-primary animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i data-lucide="download" class="w-4 h-4 text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-200" id="txLoadingMsg">거래내역을 가져오는 중...</p>
                        <p class="text-xs text-slate-500 mt-0.5">은행 서버에서 데이터를 수신하고 있어요</p>
                    </div>
                </div>
                <div id="txProgressWrap" class="hidden">
                    <div class="w-full bg-slate-800 rounded-full h-1.5 mb-2 overflow-hidden">
                        <div id="txProgressBar" class="h-1.5 rounded-full transition-all duration-500 ease-out bg-gradient-to-r from-blue-500 to-primary" style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-xs text-slate-500" id="txProgressText">0 / 0</p>
                        <p class="text-xs text-slate-500" id="txProgressPct"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 인라인 프로그레스 (데이터 표시 중 + 추가 로딩) -->
    <div id="txInlineProgress" class="hidden mb-3">
        <div class="bg-gradient-to-r from-slate-900/90 to-slate-800/90 border border-slate-700 rounded-xl px-4 py-3 flex items-center gap-3 backdrop-blur-sm">
            <div class="relative w-6 h-6 flex-shrink-0">
                <div class="absolute inset-0 rounded-full border-2 border-slate-700"></div>
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-primary animate-spin" style="animation-duration:0.8s"></div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1.5">
                    <p class="text-xs font-medium text-slate-300" id="txInlineText">추가 데이터 조회 중...</p>
                    <p class="text-xs text-slate-200 font-medium" id="txInlinePct"></p>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-1 overflow-hidden">
                    <div id="txInlineBar" class="h-1 rounded-full transition-all duration-500 ease-out bg-gradient-to-r from-blue-500 to-primary" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 거래내역 테이블 -->
    <div id="txTableWrap" class="hidden">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <p class="text-sm font-semibold text-slate-200" id="txTableTitle">거래내역</p>
                <span class="text-sm text-slate-500" id="txTablePeriod"></span>
                <span class="text-xs text-slate-600" id="txFilterCount"></span>
            </div>
            <div class="flex items-center gap-2">
                <a href="?tab=classify" class="btn btn-secondary btn-sm">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>AI 분류
                </a>
                <button onclick="exportCsv()" class="btn btn-secondary btn-sm">
                    <i data-lucide="download" class="w-4 h-4"></i>CSV 다운로드
                </button>
            </div>
        </div>
        <!-- 필터 바 -->
        <div class="flex items-center gap-3 mb-3">
            <div class="inline-flex rounded-lg border border-slate-800 overflow-hidden flex-shrink-0">
                <button type="button" onclick="setTxFilter('all')" id="filterAll" class="zm-tab zm-tab-active px-3 py-1.5 text-sm transition-colors">전체</button>
                <button type="button" onclick="setTxFilter('deposit')" id="filterDeposit" class="zm-tab px-3 py-1.5 text-sm transition-colors">입금</button>
                <button type="button" onclick="setTxFilter('withdraw')" id="filterWithdraw" class="zm-tab px-3 py-1.5 text-sm transition-colors">출금</button>
            </div>
            <div class="relative flex-1 max-w-xs">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none"></i>
                <input type="text" id="txKeyword" placeholder="적요 검색" oninput="applyTxFilters()" class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <select id="txCatFilter" onchange="applyTxFilters()" class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                <option value="">계정과목 전체</option>
                <option value="_none">미지정만</option>
                <option value="_assigned">지정됨</option>
                <optgroup label="분류별">
                    <option value="자산">자산</option>
                    <option value="부채">부채</option>
                    <option value="자본">자본</option>
                    <option value="매출">매출</option>
                    <option value="매입">매입</option>
                    <option value="비용">비용</option>
                    <option value="수익">수익</option>
                </optgroup>
            </select>
        </div>
        <!-- 기간 필터 + 누적 토글 -->
        <div class="flex items-center gap-2.5 mb-3">
            <span class="text-xs text-slate-500 font-medium whitespace-nowrap">기간 필터</span>
            <input type="date" id="subStartDate" class="w-32 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            <span class="text-slate-600 text-xs">~</span>
            <input type="date" id="subEndDate" class="w-32 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            <button onclick="applySubDateFilter()" class="btn btn-secondary btn-xs whitespace-nowrap">적용</button>
            <button onclick="clearSubDateFilter()" class="btn btn-secondary btn-xs whitespace-nowrap">초기화</button>
            <span class="text-slate-800 mx-0.5">|</span>
            <label class="inline-flex items-center gap-1.5 select-none">
                <span class="text-xs text-slate-500">소계</span>
                <select id="subtotalMode" onchange="applyTxFilters()" class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
                    <option value="none">없음</option>
                    <option value="day">일별</option>
                    <option value="week">주별</option>
                    <option value="month">월별</option>
                    <option value="quarter">분기별</option>
                    <option value="half">반기별</option>
                    <option value="year">연별</option>
                </select>
            </label>
        </div>
        <div class="overflow-x-auto bg-slate-900 rounded-xl border border-slate-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-4 text-left font-medium text-slate-400 whitespace-nowrap cursor-pointer hover:text-slate-200 select-none" onclick="toggleSort('date')">
                            <span class="inline-flex items-center gap-1">일시 <span id="sortIcon_date" class="sort-icon"></span></span>
                        </th>
                        <th class="py-3 px-4 text-left font-medium text-slate-400">적요</th>
                        <th class="py-3 px-4 text-left font-medium text-slate-400">거래처</th>
                        <th class="py-3 px-4 text-left font-medium text-slate-400">메모</th>
                        <th class="py-3 px-4 text-right font-medium text-slate-400 cursor-pointer hover:text-slate-200 select-none whitespace-nowrap" onclick="toggleSort('withdraw')">
                            <span class="inline-flex items-center justify-end gap-1 w-full">출금 <span id="sortIcon_withdraw" class="sort-icon"></span></span>
                        </th>
                        <th class="py-3 px-4 text-right font-medium text-slate-400 cursor-pointer hover:text-slate-200 select-none whitespace-nowrap" onclick="toggleSort('deposit')">
                            <span class="inline-flex items-center justify-end gap-1 w-full">입금 <span id="sortIcon_deposit" class="sort-icon"></span></span>
                        </th>
                        <th class="py-3 px-4 text-right font-medium text-slate-400">잔액</th>
                        <th class="py-3 px-4 text-left font-medium text-slate-400 whitespace-nowrap" style="min-width:140px">
                            계정과목
                            <a href="?tab=categories" class="ml-1 text-slate-500 hover:text-slate-300" title="계정과목 관리"><i data-lucide="settings" class="w-3 h-3 inline-block"></i></a>
                        </th>
                        <th class="py-3 px-1 w-8"></th>
                    </tr>
                </thead>
                <tbody id="txTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== 계정과목 검색 드롭다운 (공유) ===== -->
<div id="catPickerPanel" class="hidden fixed z-50 w-72 bg-slate-950 border border-slate-700 rounded-xl shadow-2xl overflow-hidden" style="max-height:360px">
    <div class="p-2 space-y-1.5 border-b border-slate-800">
        <div class="relative">
            <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-500 pointer-events-none"></i>
            <input type="text" id="catPickerSearch" placeholder="코드 또는 이름 검색"
                oninput="renderCatPickerList()"
                class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-7 pr-2 py-1.5 text-xs text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
        </div>
        <div class="flex flex-nowrap gap-0.5" id="catPickerFilters">
            <button type="button" onclick="setPickerTypeFilter('all')" data-pf="all" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-primary text-primary font-medium whitespace-nowrap">전체</button>
            <button type="button" onclick="setPickerTypeFilter('자산')" data-pf="자산" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">자산</button>
            <button type="button" onclick="setPickerTypeFilter('부채')" data-pf="부채" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">부채</button>
            <button type="button" onclick="setPickerTypeFilter('매출')" data-pf="매출" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">매출</button>
            <button type="button" onclick="setPickerTypeFilter('매입')" data-pf="매입" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">매입</button>
            <button type="button" onclick="setPickerTypeFilter('비용')" data-pf="비용" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">비용</button>
            <button type="button" onclick="setPickerTypeFilter('수익')" data-pf="수익" class="cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">수익</button>
        </div>
    </div>
    <div id="catPickerList" class="overflow-y-auto" style="max-height:280px"></div>
</div>

<!-- ===== 계좌 등록 모달 ===== -->
<div id="registerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRegisterModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md border border-slate-800">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">계좌 연결</h3>
            <button onclick="closeRegisterModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">은행 선택 <span class="text-rose-400">*</span></label>
                <select id="regBankCode" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <option value="">은행 선택</option>
                    <?php foreach ($banks as $code => $name): ?>
                    <option value="<?= $code ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">계좌번호 <span class="text-rose-400">*</span></label>
                <input type="text" id="regAccountNo" placeholder="숫자만 입력" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">별칭 <span class="text-slate-600 text-xs">(선택)</span></label>
                <input type="text" id="regAlias" placeholder="예: 운영계좌, 급여계좌" maxlength="20" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div id="regResultBox" class="hidden p-3 rounded-xl text-sm"></div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end border-t border-slate-800 pt-4">
            <button onclick="closeRegisterModal()" class="btn btn-secondary">취소</button>
            <button onclick="registerAccount()" id="btnRegisterAcc" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i>연결
            </button>
        </div>
    </div>
</div>

<!-- ===== 별칭 수정 모달 ===== -->
<div id="aliasModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAliasModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm border border-slate-800 p-6">
        <h3 class="text-base font-bold text-slate-100 mb-4">별칭 수정</h3>
        <input type="text" id="aliasInput" placeholder="예: 운영계좌" maxlength="20" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30 mb-4">
        <div class="flex gap-2 justify-end">
            <button onclick="closeAliasModal()" class="btn btn-secondary">취소</button>
            <button onclick="saveAlias()" id="btnSaveAlias" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">저장</button>
        </div>
    </div>
</div>

<!-- ===== 사업자번호 모달 ===== -->
<div id="residentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeResidentModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm border border-slate-800 p-6">
        <h3 class="text-base font-bold text-slate-100 mb-1" id="residentModalTitle">사업자(주민)번호</h3>
        <p class="text-sm text-slate-500 mb-4">거래내역 조회 시 자동으로 사용됩니다.</p>
        <!-- 확인 모드 -->
        <div id="residentViewMode" class="hidden">
            <div class="bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 mb-4">
                <p class="text-xs text-slate-500 mb-1">등록된 번호</p>
                <p class="text-sm text-slate-100 font-mono tracking-wider" id="residentMasked"></p>
            </div>
            <div class="flex gap-2 justify-end">
                <button onclick="closeResidentModal()" class="btn btn-secondary">닫기</button>
                <button onclick="switchToResidentEdit()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">변경</button>
            </div>
        </div>
        <!-- 입력 모드 -->
        <div id="residentEditMode" class="hidden">
            <input type="text" id="residentInput" placeholder="- 없이 숫자만" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-gray-300/30 mb-4">
            <div class="flex gap-2 justify-end">
                <button onclick="closeResidentModal()" class="btn btn-secondary">취소</button>
                <button onclick="saveResidentFromModal()" id="btnSaveResident" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 거래 분할 모달 ===== -->
<div id="splitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSplitModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg border border-slate-800">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">거래 분할</h3>
            <button onclick="closeSplitModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400">원거래 금액</span>
                <span class="text-slate-100 font-bold tabular-nums" id="splitOrigAmount">0원</span>
            </div>
            <div class="flex items-center justify-between text-sm mt-1">
                <span class="text-slate-400">분할 합계</span>
                <span class="tabular-nums font-bold" id="splitSumDisplay">0원</span>
            </div>
            <div id="splitDiffRow" class="hidden flex items-center justify-between text-sm mt-1">
                <span class="text-slate-400">차이</span>
                <span class="text-rose-400 tabular-nums font-bold" id="splitDiffDisplay">0원</span>
            </div>
        </div>
        <div class="px-6 py-4 space-y-2 overflow-y-auto" style="max-height:320px" id="splitRowsContainer">
        </div>
        <div class="px-6 pb-2">
            <button onclick="addSplitRow()" class="w-full py-2 text-sm text-gray-600 border border-dashed border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-colors flex items-center justify-center gap-1.5">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>항목 추가
            </button>
        </div>
        <div class="flex items-center gap-2 px-6 pb-5 pt-3 border-t border-slate-800">
            <button onclick="deleteSplits()" id="btnDeleteSplits" class="hidden px-3 py-2 text-sm text-rose-400 border border-rose-800/50 rounded-lg hover:bg-rose-950/30">분할 해제</button>
            <div class="flex-1"></div>
            <button onclick="closeSplitModal()" class="btn btn-secondary">취소</button>
            <button onclick="saveSplits()" id="btnSaveSplits" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 disabled:opacity-40" disabled>저장</button>
        </div>
    </div>
</div>

<!-- ===== 비밀번호 입력 모달 ===== -->
<div id="passwordModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePasswordModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm border border-slate-800 p-6">
        <h3 class="text-base font-bold text-slate-100 mb-1">계좌 비밀번호 입력</h3>
        <p class="text-sm text-slate-400 mb-3">비밀번호 4자리를 입력해주세요.</p>
        <input type="password" id="txAccountPw" placeholder="" maxlength="4" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-lg text-slate-100 text-center tracking-[0.3em] focus:outline-none focus:ring-2 focus:ring-gray-300/30 mb-3">
        <label class="flex items-center gap-2 text-xs text-slate-400 mb-4 cursor-pointer select-none">
            <input type="checkbox" id="txSavePw" class="accent-primary w-3.5 h-3.5">
            비밀번호 저장 (다음부터 자동 조회)
        </label>
        <div class="flex gap-2 justify-end">
            <button onclick="closePasswordModal()" class="btn btn-secondary">취소</button>
            <button onclick="submitWithPassword()" id="btnSubmitPw" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">조회</button>
        </div>
    </div>
</div>

<!-- ===== 계좌 삭제 확인 모달 ===== -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center border border-slate-800">
        <i data-lucide="alert-triangle" class="w-12 h-12 text-rose-400 mx-auto mb-3"></i>
        <h3 class="text-base font-bold text-slate-100 mb-1">계좌 연결 해제</h3>
        <p class="text-sm text-slate-400 mb-4" id="deleteMsg">이 계좌를 해제하시겠습니까?</p>
        <div class="flex gap-2 justify-center">
            <button onclick="closeDeleteModal()" class="btn btn-secondary">취소</button>
            <button onclick="confirmDelete()" id="btnDelete" class="px-4 py-2 text-sm text-white bg-rose-500 rounded-lg hover:bg-rose-600">연결 해제</button>
        </div>
    </div>
</div>

<script>
var API_URL = '<?= $apiBasePath ?>/api/bankapi.php';
var BANKS = <?= json_encode($banks, JSON_UNESCAPED_UNICODE) ?>;

var accountsList = [];
var savedResidentNo = '';
var cachedTransactions = [];
var currentBalance = null;
var accountCategories = [];
var pendingAliasBank = '';
var pendingAliasAccNo = '';
var pendingDeleteBank = '';
var pendingDeleteAccNo = '';
var activePeriod = 'month';

['regAccountNo', 'residentInput'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});

// ═══════════════════════════════════════
//  초기화
// ═══════════════════════════════════════

document.addEventListener('DOMContentLoaded', async function() {
    await loadCategories();
    await loadResidentNumber();
    await loadAccounts();
});

async function loadCategories() {
    try {
        var res = await fetch(API_URL + '?action=get_categories');
        var json = await res.json();
        if (json.success && json.data && json.data.categories) {
            accountCategories = json.data.categories;
        }
    } catch (e) {}
}

async function loadResidentNumber() {
    try {
        var res = await fetch(API_URL + '?action=resident_number');
        var json = await res.json();
        if (json.success && json.data && json.data.residentNumber) {
            savedResidentNo = json.data.residentNumber;
        }
    } catch (e) {}
    updateResidentStatus();
}

function updateResidentStatus() {
    var textEl = document.getElementById('residentStatusText');
    var btnEl = document.getElementById('residentActionBtn');
    if (savedResidentNo) {
        textEl.textContent = '사업자번호 등록됨';
        btnEl.textContent = '확인';
    } else {
        textEl.textContent = '사업자번호: 미등록';
        btnEl.textContent = '등록';
    }
}

function showApiError(msg) {
    var el = document.getElementById('apiError');
    var safe = String(msg || '관리자에게 문의하세요.').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    el.innerHTML = '<div class="p-4 bg-rose-950/50 border border-rose-800/50 rounded-xl mb-4"><div class="flex items-start gap-3"><i data-lucide="alert-circle" class="w-5 h-5 text-rose-400 flex-shrink-0 mt-0.5"></i><p class="text-sm text-rose-300">' + safe + '</p></div></div>';
    el.classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

async function loadAccounts() {
    var loading = document.getElementById('pageLoading');
    var onboarding = document.getElementById('onboarding');
    var mainView = document.getElementById('mainView');
    var apiError = document.getElementById('apiError');

    loading.classList.remove('hidden');
    onboarding.classList.add('hidden');
    mainView.classList.add('hidden');
    apiError.classList.add('hidden');

    try {
        var res = await fetch(API_URL + '?action=list_accounts');
        var json = await res.json();
        loading.classList.add('hidden');

        if (!json.success) {
            showApiError(json.message || '관리자에게 문의하세요.');
            onboarding.classList.remove('hidden');
            return;
        }

        accountsList = (json.data && json.data.accounts) || [];

        if (accountsList.length === 0) {
            onboarding.classList.remove('hidden');
            return;
        }

        var syncWrap = document.getElementById('syncWarningWrap');
        if (json.data.syncWarning) {
            document.getElementById('syncWarningText').textContent = json.data.syncWarning;
            syncWrap.classList.remove('hidden');
        } else {
            syncWrap.classList.add('hidden');
        }

        buildAccountSelect(accountsList);
        mainView.classList.remove('hidden');

        if (!savedResidentNo) {
            document.getElementById('residentAlert').classList.remove('hidden');
        } else {
            document.getElementById('residentAlert').classList.add('hidden');
        }

        if (window.lucide) lucide.createIcons();
        loadFromDb();
    } catch (e) {
        loading.classList.add('hidden');
        showApiError(e.message);
        onboarding.classList.remove('hidden');
    }
}

// ═══════════════════════════════════════
//  커스텀 드롭다운
// ═══════════════════════════════════════

var dropdownOpen = false;

function toggleDropdown() { dropdownOpen ? closeDropdown() : openDropdown(); }

function openDropdown() {
    document.getElementById('dropdownPanel').classList.remove('hidden');
    document.getElementById('dropdownArrow').style.transform = 'rotate(180deg)';
    document.getElementById('dropdownTrigger').classList.add('ring-2', 'ring-gray-300/30');
    dropdownOpen = true;
}

function closeDropdown() {
    document.getElementById('dropdownPanel').classList.add('hidden');
    document.getElementById('dropdownArrow').style.transform = '';
    document.getElementById('dropdownTrigger').classList.remove('ring-2', 'ring-gray-300/30');
    dropdownOpen = false;
}

document.addEventListener('click', function(e) {
    if (dropdownOpen && !document.getElementById('customSelectWrap').contains(e.target)) closeDropdown();
});

function selectDropdownItem(value, label) {
    document.getElementById('accountSelect').value = value;
    document.getElementById('dropdownLabel').textContent = label;
    closeDropdown();
    onAccountChange();
    highlightSelected();
}

function highlightSelected() {
    var val = document.getElementById('accountSelect').value;
    document.querySelectorAll('#dropdownList [data-value]').forEach(function(item) {
        var isActive = item.getAttribute('data-value') === val;
        item.classList.toggle('bg-slate-800', isActive);
        item.classList.toggle('text-slate-50', isActive);
        item.classList.toggle('font-bold', isActive);
        item.classList.toggle('text-slate-100', !isActive);
        item.classList.toggle('font-normal', !isActive);
    });
}

function buildAccountSelect(accounts) {
    var list = document.getElementById('dropdownList');
    var prevVal = document.getElementById('accountSelect').value;
    list.innerHTML = '';

    var firstVal = '', firstLabel = '';
    accounts.forEach(function(acc) {
        var bankCode = (acc.bankCode || '').replace(/[^A-Za-z0-9]/g, '');
        var bankName = acc.bankName || BANKS[bankCode] || bankCode;
        var accNo = (acc.accountNumber || '').replace(/[^0-9]/g, '');
        var alias = acc.alias || '';
        var masked = accNo.length > 4 ? accNo.slice(0, 4) + '****' + accNo.slice(-4) : accNo;
        var value = bankCode + '|' + accNo;
        var label = alias ? alias + ' (' + bankName + ' ' + masked + ')' : bankName + ' ' + masked;
        if (!firstVal) { firstVal = value; firstLabel = label; }

        var item = document.createElement('button');
        item.type = 'button';
        item.setAttribute('data-value', value);
        item.className = 'w-full text-left px-3 py-2.5 text-sm text-slate-100 hover:bg-slate-800/80 transition-colors cursor-pointer';
        item.textContent = label;
        item.onclick = (function(v, l) { return function() { selectDropdownItem(v, l); }; })(value, label);
        list.appendChild(item);
    });

    var hasMatch = false;
    if (prevVal) list.querySelectorAll('[data-value]').forEach(function(it) { if (it.getAttribute('data-value') === prevVal) hasMatch = true; });

    if (hasMatch) {
        var ml = ''; list.querySelectorAll('[data-value]').forEach(function(it) { if (it.getAttribute('data-value') === prevVal) ml = it.textContent; });
        selectDropdownItem(prevVal, ml);
    } else if (accounts.length >= 1) {
        selectDropdownItem(firstVal, firstLabel);
    }
}

function onAccountChange() {
    var val = document.getElementById('accountSelect').value;
    var ownerEl = document.getElementById('ownerInfo');
    var actionsEl = document.getElementById('selectedAccountActions');
    var sep1 = document.getElementById('infoSep1');

    if (val) {
        var parts = val.split('|');
        var found = accountsList.find(function(a) { return a.bankCode === parts[0] && a.accountNumber === parts[1]; });
        if (found) {
            var bankName = found.bankName || BANKS[found.bankCode] || found.bankCode;
            var owner = found.ownerName || '';
            document.getElementById('ownerInfoText').textContent = owner ? '예금주 ' + owner : bankName + ' 연결됨';
            ownerEl.classList.remove('hidden');
            actionsEl.classList.remove('hidden');
            sep1.classList.remove('hidden');
            updatePwStatus(!!found.hasPassword);
        } else {
            ownerEl.classList.add('hidden');
            actionsEl.classList.add('hidden');
            sep1.classList.add('hidden');
        }
    } else {
        ownerEl.classList.add('hidden');
        actionsEl.classList.add('hidden');
        sep1.classList.add('hidden');
    }

    if (window.lucide) lucide.createIcons();
}

function getSelectedAccount() {
    var val = document.getElementById('accountSelect').value;
    if (!val) return null;
    var parts = val.split('|');
    var acct = accountsList.find(function(a) { return a.bankCode === parts[0] && a.accountNumber === parts[1]; });
    return { bankCode: parts[0], accountNumber: parts[1], id: acct ? acct.id : null };
}

function updatePwStatus(hasPw) {
    var el = document.getElementById('pwStatusText');
    if (!el) return;
    if (hasPw) {
        el.innerHTML = '<i data-lucide="lock" class="w-3.5 h-3.5"></i>비밀번호 저장됨';
        el.className = 'inline-flex items-center gap-1 text-emerald-400';
    } else {
        el.innerHTML = '<i data-lucide="lock" class="w-3.5 h-3.5"></i>비밀번호 미저장';
        el.className = 'inline-flex items-center gap-1';
    }
    if (window.lucide) lucide.createIcons();
}

// ═══════════════════════════════════════
//  기간 퀵버튼
// ═══════════════════════════════════════

function setPeriod(type) {
    activePeriod = type;
    var now = new Date();
    var y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    var startEl = document.getElementById('txStartDate');
    var endEl = document.getElementById('txEndDate');
    var todayStr = now.toISOString().slice(0, 10);

    function toDateStr(dt) { return dt.toISOString().slice(0, 10); }

    if (type === 'week') {
        var weekAgo = new Date(y, m, d - 7);
        startEl.value = toDateStr(weekAgo);
        endEl.value = todayStr;
    } else if (type === 'month') {
        startEl.value = y + '-' + String(m + 1).padStart(2, '0') + '-01';
        endEl.value = todayStr;
    } else if (type === 'prev') {
        var pm = m === 0 ? 11 : m - 1;
        var py = m === 0 ? y - 1 : y;
        startEl.value = py + '-' + String(pm + 1).padStart(2, '0') + '-01';
        var lastDay = new Date(py, pm + 1, 0).getDate();
        endEl.value = py + '-' + String(pm + 1).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
    } else if (type === '3month') {
        var threeAgo = new Date(y, m - 3, d);
        startEl.value = toDateStr(threeAgo);
        endEl.value = todayStr;
    } else if (type === 'year') {
        startEl.value = y + '-01-01';
        endEl.value = todayStr;
    }

    var PERIOD_IDS = ['periodWeek', 'periodMonth', 'periodPrev', 'period3month', 'periodYear', 'periodCustom'];
    var ID_MAP = { week: 'periodWeek', month: 'periodMonth', prev: 'periodPrev', '3month': 'period3month', year: 'periodYear', custom: 'periodCustom' };
    PERIOD_IDS.forEach(function(id) {
        var el = document.getElementById(id);
        var isActive = id === ID_MAP[type];
        el.classList.toggle('zm-tab-active', isActive);
    });

    if (type !== 'custom') loadFromDb();
}

// ═══════════════════════════════════════
//  거래내역 조회
// ═══════════════════════════════════════

function requestFetch() {
    var val = document.getElementById('accountSelect').value;
    if (!val) return;
    if (!savedResidentNo) { openResidentModal(); return; }

    var parts = val.split('|');
    var acct = accountsList.find(function(a) { return a.bankCode === parts[0] && a.accountNumber === parts[1]; });
    if (acct && acct.hasPassword) {
        fetchTransactions('__saved__');
        return;
    }
    openPasswordModal();
}

function openPasswordModal() {
    document.getElementById('txAccountPw').value = '';
    document.getElementById('passwordModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('txAccountPw').focus(); }, 100);
    if (window.lucide) lucide.createIcons();
}
function closePasswordModal() { document.getElementById('passwordModal').classList.add('hidden'); document.body.style.overflow = ''; }

async function submitWithPassword() {
    var pw = document.getElementById('txAccountPw').value;
    if (!pw || pw.length < 4) return;
    var savePw = document.getElementById('txSavePw').checked;
    closePasswordModal();

    if (savePw) {
        var sel = getSelectedAccount();
        if (sel) {
            try {
                await fetch(API_URL + '?action=update_account', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accountId: sel.id, accountPassword: pw })
                });
                var acct = accountsList.find(function(a) { return a.bankCode === sel.bankCode && a.accountNumber === sel.accountNumber; });
                if (acct) acct.hasPassword = true;
                updatePwStatus(true);
            } catch (e) { /* 저장 실패해도 조회는 진행 */ }
        }
    }
    fetchTransactions(pw);
}

function clientSplitDateRange(startStr, endStr, maxDays) {
    maxDays = maxDays || 14;
    var s = new Date(startStr.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3'));
    var e = new Date(endStr.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3'));
    if (isNaN(s) || isNaN(e) || s > e) return [{ start: startStr, end: endStr }];
    var chunks = [];
    var cur = new Date(s);
    while (cur <= e) {
        var chunkEnd = new Date(cur);
        chunkEnd.setDate(chunkEnd.getDate() + maxDays - 1);
        if (chunkEnd > e) chunkEnd = new Date(e);
        var cs = cur.toISOString().slice(0,10).replace(/-/g,'');
        var ce = chunkEnd.toISOString().slice(0,10).replace(/-/g,'');
        chunks.push({ start: cs, end: ce });
        cur = new Date(chunkEnd);
        cur.setDate(cur.getDate() + 1);
    }
    return chunks;
}

function setBtnFetchLoading(loading, text) {
    var btn = document.getElementById('btnFetch');
    var icon = document.getElementById('btnFetchIcon');
    var label = document.getElementById('btnFetchText');
    btn.disabled = loading;
    if (loading) {
        icon.setAttribute('data-lucide', 'loader-2');
        icon.classList.add('animate-spin');
        label.textContent = text || '조회 중...';
        btn.classList.add('opacity-80');
    } else {
        icon.setAttribute('data-lucide', 'search');
        icon.classList.remove('animate-spin');
        label.textContent = '거래내역 조회';
        btn.classList.remove('opacity-80');
    }
    if (window.lucide) lucide.createIcons();
}

function updateProgress(done, total, failCount, isInline) {
    var pct = total > 0 ? Math.round((done / total) * 100) : 0;
    var failText = failCount > 0 ? ' (실패 ' + failCount + '건)' : '';
    var statusText = done + ' / ' + total + ' 구간 완료' + failText;

    setBtnFetchLoading(true, '조회 중 ' + pct + '%');

    if (isInline) {
        document.getElementById('txInlineBar').style.width = pct + '%';
        document.getElementById('txInlineText').textContent = statusText;
        var inlinePct = document.getElementById('txInlinePct');
        if (inlinePct) inlinePct.textContent = pct + '%';
    } else {
        document.getElementById('txProgressBar').style.width = pct + '%';
        document.getElementById('txProgressText').textContent = statusText;
        var progressPct = document.getElementById('txProgressPct');
        if (progressPct) progressPct.textContent = pct + '%';
        document.getElementById('txLoadingMsg').textContent = '거래내역을 가져오는 중...';
    }
}

async function loadFromDb() {
    var sel = getSelectedAccount();
    if (!sel) return;
    var startDate = document.getElementById('txStartDate').value.replace(/-/g, '');
    var endDate = document.getElementById('txEndDate').value.replace(/-/g, '');
    if (!startDate || !endDate) return;

    try {
        var url = API_URL + '?action=db_transactions' +
            '&bankCode=' + encodeURIComponent(sel.bankCode) +
            '&accountNumber=' + encodeURIComponent(sel.accountNumber) +
            '&startDate=' + startDate + '&endDate=' + endDate;
        var res = await fetch(url);
        var json = await res.json();
        if (json.success && json.data && json.data.dbTransactions && json.data.dbTransactions.length > 0) {
            var dbTx = json.data.dbTransactions;
            dbTx.forEach(function(tx) { tx._bankCode = sel.bankCode; });
            cachedTransactions = dbTx;

            var accLabel = document.getElementById('dropdownLabel').textContent;
            var periodStr = document.getElementById('txStartDate').value + ' ~ ' + document.getElementById('txEndDate').value;
            document.getElementById('txTableTitle').textContent = accLabel.split('(')[0].trim() + ' 거래내역';
            document.getElementById('txTablePeriod').textContent = periodStr;

            hideTxError();
            if (json.data.latestBalance !== null && json.data.latestBalance !== undefined) {
                currentBalance = json.data.latestBalance;
            }
            renderTransactions(cachedTransactions);

            if (json.data.lastSync) {
                var syncDate = new Date(json.data.lastSync);
                var now = new Date();
                var diffMin = Math.floor((now - syncDate) / 60000);
                var agoText;
                if (diffMin < 1) agoText = '방금 전';
                else if (diffMin < 60) agoText = diffMin + '분 전';
                else if (diffMin < 1440) agoText = Math.floor(diffMin / 60) + '시간 전';
                else agoText = Math.floor(diffMin / 1440) + '일 전';
                document.getElementById('txFilterCount').textContent = '마지막 동기화: ' + agoText;
            }
        }
    } catch (e) {}
}

async function fetchTransactions(accountPw) {
    var val = document.getElementById('accountSelect').value;
    if (!val || !accountPw) return;
    if (!savedResidentNo) { openResidentModal(); return; }

    var startDate = document.getElementById('txStartDate').value.replace(/-/g, '');
    var endDate = document.getElementById('txEndDate').value.replace(/-/g, '');

    hideTxError();
    document.getElementById('txSummary').classList.add('hidden');
    document.getElementById('txTableWrap').classList.add('hidden');
    document.getElementById('txInlineProgress').classList.add('hidden');
    document.getElementById('txLoading').classList.remove('hidden');
    setBtnFetchLoading(true, '조회 중...');

    var chunks = clientSplitDateRange(startDate, endDate, 30);
    var isMultiChunk = chunks.length > 1;
    var progressWrap = document.getElementById('txProgressWrap');

    if (isMultiChunk) {
        progressWrap.classList.remove('hidden');
        updateProgress(0, chunks.length, 0, false);
    } else {
        progressWrap.classList.add('hidden');
        document.getElementById('txLoadingMsg').textContent = '거래내역을 가져오는 중...';
    }

    var sel = getSelectedAccount();
    if (!sel) { document.getElementById('btnFetch').disabled = false; return; }

    var accLabel = document.getElementById('dropdownLabel').textContent;
    var periodStr = document.getElementById('txStartDate').value + ' ~ ' + document.getElementById('txEndDate').value;

    cachedTransactions = [];
    var failCount = 0;
    var lastError = '';
    var firstDataShown = false;

    for (var ci = 0; ci < chunks.length; ci++) {
        var chunk = chunks[ci];
        try {
            var res = await fetch(API_URL + '?action=transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bankCode: sel.bankCode, accountNumber: sel.accountNumber,
                    accountPassword: accountPw, residentNumber: savedResidentNo,
                    startDate: chunk.start, endDate: chunk.end
                })
            });
            var json = await res.json();
            if (!json.success) {
                failCount++;
                lastError = json.message || '조회 실패';
            } else {
                var newTx = [];
                var dbTx = (json.data && json.data.dbTransactions) || [];
                var txList = (json.data && json.data.transactions) || json.data || [];
                if (dbTx.length > 0) {
                    dbTx.forEach(function(tx) { tx._bankCode = sel.bankCode; });
                    newTx = dbTx;
                } else if (Array.isArray(txList)) {
                    txList.forEach(function(tx) { tx._bankCode = sel.bankCode; });
                    newTx = txList;
                }

                if (newTx.length > 0) {
                    cachedTransactions = cachedTransactions.concat(newTx);

                    if (!firstDataShown && isMultiChunk) {
                        firstDataShown = true;
                        document.getElementById('txLoading').classList.add('hidden');
                        document.getElementById('txInlineProgress').classList.remove('hidden');
                        document.getElementById('txTableTitle').textContent = accLabel.split('(')[0].trim() + ' 거래내역';
                        document.getElementById('txTablePeriod').textContent = periodStr;
                    }

                    if (firstDataShown) {
                        renderTransactions(cachedTransactions, true);
                    }
                }
            }
        } catch (e) {
            failCount++;
            lastError = e.message;
        }

        if (isMultiChunk) {
            updateProgress(ci + 1, chunks.length, failCount, firstDataShown);
        }
    }

    document.getElementById('txLoading').classList.add('hidden');
    document.getElementById('txInlineProgress').classList.add('hidden');
    progressWrap.classList.add('hidden');
    setBtnFetchLoading(false);

    if (cachedTransactions.length === 0 && failCount > 0) {
        showTxError('조회 실패', lastError);
        return;
    }

    if (failCount > 0 && cachedTransactions.length > 0) {
        showTxError('일부 구간 실패', chunks.length + '개 구간 중 ' + failCount + '개 실패. 성공한 구간의 데이터만 표시합니다.');
    }

    if (!firstDataShown) {
        document.getElementById('txTableTitle').textContent = accLabel.split('(')[0].trim() + ' 거래내역';
        document.getElementById('txTablePeriod').textContent = periodStr;
        renderTransactions(cachedTransactions);
    }
}

var txTypeFilter = 'all';
var txSortKey = 'date';
var txSortDir = 'desc';

function toggleSort(key) {
    if (txSortKey === key) {
        txSortDir = txSortDir === 'desc' ? 'asc' : 'desc';
    } else {
        txSortKey = key;
        txSortDir = key === 'date' ? 'desc' : 'asc';
    }
    updateSortIcons();
    applyTxFilters();
}

function updateSortIcons() {
    ['date', 'withdraw', 'deposit'].forEach(function(k) {
        var el = document.getElementById('sortIcon_' + k);
        if (!el) return;
        var isActive = txSortKey === k;
        var upClass = 'sort-up ' + (isActive && txSortDir === 'asc' ? 'sort-active' : 'sort-dim');
        var downClass = 'sort-down ' + (isActive && txSortDir === 'desc' ? 'sort-active' : 'sort-dim');
        el.innerHTML = '<span class="sort-arrows">' +
            '<span class="' + upClass + '"></span>' +
            '<span class="' + downClass + '"></span>' +
            '</span>';
    });
}

function sortTransactions(list) {
    return list.slice().sort(function(a, b) {
        var va, vb;
        if (txSortKey === 'date') {
            var da = (a.transaction_date || a.date || '') + ' ' + (a.transaction_time || a.time || '');
            var db = (b.transaction_date || b.date || '') + ' ' + (b.transaction_time || b.time || '');
            if (da !== db) return txSortDir === 'desc' ? (db > da ? 1 : -1) : (da > db ? 1 : -1);
            var balA = parseInt(a.balance || 0), balB = parseInt(b.balance || 0);
            return txSortDir === 'desc' ? (balB - balA) : (balA - balB);
        }
        var typeA = (a.tx_type || a.type || '').toLowerCase();
        var typeB = (b.tx_type || b.type || '').toLowerCase();
        var isDepA = typeA.indexOf('deposit') >= 0 || typeA.indexOf('입금') >= 0 || typeA === 'in';
        var isDepB = typeB.indexOf('deposit') >= 0 || typeB.indexOf('입금') >= 0 || typeB === 'in';
        var amtA = parseInt(a.amount || 0);
        var amtB = parseInt(b.amount || 0);

        if (txSortKey === 'deposit') {
            va = isDepA ? amtA : 0;
            vb = isDepB ? amtB : 0;
        } else {
            va = !isDepA ? amtA : 0;
            vb = !isDepB ? amtB : 0;
        }
        return txSortDir === 'desc' ? (vb - va) : (va - vb);
    });
}

function setTxFilter(type) {
    txTypeFilter = type;
    var FILTER_IDS = ['filterAll', 'filterDeposit', 'filterWithdraw'];
    var ID_MAP = { all: 'filterAll', deposit: 'filterDeposit', withdraw: 'filterWithdraw' };
    FILTER_IDS.forEach(function(id) {
        var el = document.getElementById(id);
        var isActive = id === ID_MAP[type];
        el.classList.toggle('zm-tab-active', isActive);
    });
    applyTxFilters();
}

function applyTxFilters() {
    var keyword = (document.getElementById('txKeyword').value || '').trim().toLowerCase();
    var subStart = (document.getElementById('subStartDate') || {}).value || '';
    var subEnd = (document.getElementById('subEndDate') || {}).value || '';
    var filtered = cachedTransactions.filter(function(tx) {
        if (subStart || subEnd) {
            var txDate = formatDate(tx.transaction_date || tx.date || tx.transactionDate || '');
            if (subStart && txDate < subStart) return false;
            if (subEnd && txDate > subEnd) return false;
        }
        var type = (tx.tx_type || tx.type || tx.transactionType || '').toLowerCase();
        var isDeposit = type.indexOf('deposit') >= 0 || type.indexOf('입금') >= 0 || type === 'in';
        if (txTypeFilter === 'deposit' && !isDeposit) return false;
        if (txTypeFilter === 'withdraw' && isDeposit) return false;
        if (keyword) {
            var searchStr = [tx.description, tx.counterparty, tx.displayName, tx.memo, tx.content].filter(Boolean).join(' ').toLowerCase();
            if (searchStr.indexOf(keyword) < 0) return false;
        }
        var catFilter = (document.getElementById('txCatFilter') || {}).value || '';
        if (catFilter) {
            var txCode = tx.account_code || '';
            if (catFilter === '_none') { if (txCode) return false; }
            else if (catFilter === '_assigned') { if (!txCode) return false; }
            else {
                if (!txCode) return false;
                var catObj = accountCategories.find(function(c) { return c.code === txCode; });
                if (!catObj || catObj.type !== catFilter) return false;
            }
        }
        return true;
    });
    var sorted = sortTransactions(filtered);
    updateFilteredSummary(sorted);
    renderTable(sorted);
    var countEl = document.getElementById('txFilterCount');
    if (filtered.length < cachedTransactions.length) {
        countEl.textContent = filtered.length + '건 / 전체 ' + cachedTransactions.length + '건';
    } else {
        countEl.textContent = '';
    }
}

function updateFilteredSummary(txList) {
    var totalDeposit = 0, totalWithdraw = 0;
    txList.forEach(function(tx) {
        var type = (tx.tx_type || tx.type || tx.transactionType || '').toLowerCase();
        var amount = parseInt(tx.amount || 0);
        var isDeposit = type.indexOf('deposit') >= 0 || type.indexOf('입금') >= 0 || type === 'in';
        if (isDeposit) totalDeposit += amount; else totalWithdraw += amount;
    });
    document.getElementById('sumDeposit').textContent = totalDeposit.toLocaleString() + '원';
    document.getElementById('sumWithdraw').textContent = totalWithdraw.toLocaleString() + '원';
    document.getElementById('sumCount').textContent = txList.length + '건';
}

function applySubDateFilter() { applyTxFilters(); }

function clearSubDateFilter() {
    document.getElementById('subStartDate').value = '';
    document.getElementById('subEndDate').value = '';
    applyTxFilters();
}

function renderTransactions(txList, isStreaming) {
    if (!isStreaming) {
        txTypeFilter = 'all';
        txSortKey = 'date';
        txSortDir = 'desc';
        updateSortIcons();
        var kwEl = document.getElementById('txKeyword');
        if (kwEl) kwEl.value = '';
        var subS = document.getElementById('subStartDate');
        var subE = document.getElementById('subEndDate');
        if (subS) subS.value = '';
        if (subE) subE.value = '';
        var subM = document.getElementById('subtotalMode');
        if (subM) subM.value = 'none';
    }
    setTxFilter('all');

    var tbody = document.getElementById('txTableBody');
    tbody.innerHTML = '';

    if (txList.length === 0) {
        showTxError('조회 결과', '해당 기간에 거래내역이 없습니다.');
        return;
    }

    var totalDeposit = 0, totalWithdraw = 0;
    txList.forEach(function(tx) {
        var type = (tx.tx_type || tx.type || tx.transactionType || '').toLowerCase();
        var amount = parseInt(tx.amount || 0);
        var isDeposit = type.indexOf('deposit') >= 0 || type.indexOf('입금') >= 0 || type === 'in';
        if (isDeposit) totalDeposit += amount; else totalWithdraw += amount;
    });
    var dateSorted = txList.slice().sort(function(a, b) {
        var da = (a.transaction_date || a.date || '') + ' ' + (a.transaction_time || a.time || '');
        var db2 = (b.transaction_date || b.date || '') + ' ' + (b.transaction_time || b.time || '');
        if (da !== db2) return db2 > da ? 1 : db2 < da ? -1 : 0;
        return (parseInt(b.balance || 0)) - (parseInt(a.balance || 0));
    });
    var lastBalance = dateSorted.length > 0 ? parseInt(dateSorted[0].balance || 0) : 0;

    var displayBalance = currentBalance !== null ? currentBalance : lastBalance;
    document.getElementById('sumDeposit').textContent = totalDeposit.toLocaleString() + '원';
    document.getElementById('sumWithdraw').textContent = totalWithdraw.toLocaleString() + '원';
    document.getElementById('sumBalance').textContent = displayBalance.toLocaleString() + '원';
    document.getElementById('sumCount').textContent = txList.length + '건';

    document.getElementById('txSummary').classList.remove('hidden');
    document.getElementById('txTableWrap').classList.remove('hidden');
    renderTable(txList);
    if (window.lucide) lucide.createIcons();
}

function getSubtotalMode() {
    var el = document.getElementById('subtotalMode');
    return el ? el.value : 'none';
}

function getTxGroupKey(dateStr, mode) {
    if (!dateStr || mode === 'none') return null;
    var normalized = formatDate(dateStr);
    if (!normalized) return null;
    var parts = normalized.split('-');
    var y = parseInt(parts[0]) || 2025;
    var m = parseInt(parts[1]) || 1;
    var d = parseInt(parts[2]) || 1;
    var dt = new Date(y, m - 1, d);

    switch (mode) {
        case 'day': return dateStr;
        case 'week':
            var day = dt.getDay();
            var monday = new Date(dt);
            monday.setDate(dt.getDate() - (day === 0 ? 6 : day - 1));
            return monday.getFullYear() + '-' + String(monday.getMonth() + 1).padStart(2, '0') + '-' + String(monday.getDate()).padStart(2, '0');
        case 'month': return y + '-' + String(m).padStart(2, '0');
        case 'quarter': return y + ' Q' + Math.ceil(m / 3);
        case 'half': return y + (m <= 6 ? ' 상반기' : ' 하반기');
        case 'year': return String(y);
        default: return null;
    }
}

function getGroupLabel(key, mode) {
    if (!key) return '';
    switch (mode) {
        case 'day': return formatDate(key) + ' 소계';
        case 'week': return formatDate(key) + ' 주간 소계';
        case 'month': return key.replace('-', '년 ') + '월 소계';
        case 'quarter': return key.replace(' ', '년 ') + ' 소계';
        case 'half': return key;
        case 'year': return key + '년 소계';
        default: return key;
    }
}

function renderTable(txList) {
    var tbody = document.getElementById('txTableBody');
    tbody.innerHTML = '';
    var mode = getSubtotalMode();

    var renderList;
    if (mode !== 'none') {
        renderList = txList.slice().sort(function(a, b) {
            var da = a.transaction_date || a.date || a.transactionDate || '';
            var db = b.transaction_date || b.date || b.transactionDate || '';
            if (da !== db) return da < db ? -1 : 1;
            return (parseInt(a.id) || 0) - (parseInt(b.id) || 0);
        });
    } else {
        renderList = txList;
    }

    var cumDeposit = 0, cumWithdraw = 0;
    var groupDeposit = 0, groupWithdraw = 0;
    var prevGroupKey = null;

    renderList.forEach(function(tx, idx) {
        var date = tx.transaction_date || tx.date || tx.transactionDate || '';
        var time = tx.transaction_time || tx.time || tx.transactionTime || '';
        var desc = tx.description || tx.content || '';
        var cp = tx.counterparty || '';
        var displayName = tx.displayName || '';
        var memo = tx.memo || '';
        var type = (tx.tx_type || tx.type || tx.transactionType || '').toLowerCase();
        var amount = parseInt(tx.amount || 0);
        var balance = parseInt(tx.balance || 0);
        var isDeposit = type.indexOf('deposit') >= 0 || type.indexOf('입금') >= 0 || type === 'in';
        var txId = parseInt(tx.id || 0);
        var accountCode = tx.account_code || '';
        var dateStr = formatDate(date);
        var timeStr = formatTime(time);

        var currentGroupKey = getTxGroupKey(date, mode);
        if (mode !== 'none' && prevGroupKey !== null && currentGroupKey !== prevGroupKey) {
            insertSubtotalRow(tbody, prevGroupKey, mode, groupDeposit, groupWithdraw, cumDeposit, cumWithdraw);
            groupDeposit = 0;
            groupWithdraw = 0;
        }
        prevGroupKey = currentGroupKey;

        if (isDeposit) { cumDeposit += amount; groupDeposit += amount; }
        else { cumWithdraw += amount; groupWithdraw += amount; }

        var withdrawHtml = '', depositHtml = '';
        if (!isDeposit && amount) withdrawHtml = amount.toLocaleString() + '원';
        if (isDeposit && amount) depositHtml = amount.toLocaleString() + '원';

        var tr = document.createElement('tr');
        tr.className = 'border-b border-slate-800 hover:bg-slate-800/50';
        var memoText = [displayName, memo].filter(Boolean).join(' ');
        var catHtml = '';
        if (txId && accountCategories.length > 0) {
            catHtml = buildCategoryPicker(txId, accountCode);
        }
        tr.innerHTML =
            '<td class="py-3 px-4 text-slate-300 tabular-nums whitespace-nowrap">' + dateStr + ' ' + timeStr + '</td>' +
            '<td class="py-3 px-4 text-slate-200 whitespace-nowrap">' + escHtml(desc) + '</td>' +
            '<td class="py-3 px-4 text-slate-200 whitespace-nowrap">' + escHtml(cp) + '</td>' +
            '<td class="py-3 px-4 text-slate-400">' + escHtml(memoText) + '</td>' +
            '<td class="py-3 px-4 text-right tabular-nums font-medium whitespace-nowrap" ' + (!isDeposit ? 'style="color:var(--zm-withdraw-fg)"' : '') + '>' + withdrawHtml + '</td>' +
            '<td class="py-3 px-4 text-right tabular-nums font-medium whitespace-nowrap" ' + (isDeposit ? 'style="color:var(--zm-deposit-fg)"' : '') + '>' + depositHtml + '</td>' +
            '<td class="py-3 px-4 text-right text-slate-200 tabular-nums whitespace-nowrap">' + balance.toLocaleString() + '원</td>' +
            '<td class="py-2 px-2">' + catHtml + '</td>' +
            '<td class="py-2 px-1 w-8">' + (txId ? '<button type="button" onclick="openSplitModal(' + txId + ',' + amount + ')" class="p-1.5 text-slate-600 hover:text-slate-200 rounded-lg hover:bg-slate-800 transition-colors" title="거래 분할"><i data-lucide="split" class="w-3.5 h-3.5"></i></button>' : '') + '</td>';
        tbody.appendChild(tr);

        if (mode !== 'none' && idx === renderList.length - 1) {
            insertSubtotalRow(tbody, currentGroupKey, mode, groupDeposit, groupWithdraw, cumDeposit, cumWithdraw);
        }
    });
}

function insertSubtotalRow(tbody, groupKey, mode, groupDep, groupWith, cumDep, cumWith) {
    var label = getGroupLabel(groupKey, mode);

    var tr1 = document.createElement('tr');
    tr1.className = 'bg-slate-800/70 border-b border-slate-700';
    tr1.innerHTML =
        '<td colspan="4" class="py-2 px-4 text-xs font-bold text-slate-300">' + escHtml(label) + '</td>' +
        '<td class="py-2 px-4 text-right text-xs font-bold tabular-nums" style="color:var(--zm-withdraw-fg)">' + (groupWith ? groupWith.toLocaleString() + '원' : '') + '</td>' +
        '<td class="py-2 px-4 text-right text-xs font-bold tabular-nums" style="color:var(--zm-deposit-fg)">' + (groupDep ? groupDep.toLocaleString() + '원' : '') + '</td>' +
        '<td colspan="2"></td>';
    tbody.appendChild(tr1);

    var tr2 = document.createElement('tr');
    tr2.className = 'bg-slate-800/40 border-b-2 border-slate-700';
    tr2.innerHTML =
        '<td colspan="4" class="py-1.5 px-4 text-[11px] text-slate-500">누적 합계</td>' +
        '<td class="py-1.5 px-4 text-right text-[11px] tabular-nums" style="color:var(--zm-withdraw-fg);opacity:.7">' + (cumWith ? cumWith.toLocaleString() + '원' : '') + '</td>' +
        '<td class="py-1.5 px-4 text-right text-[11px] tabular-nums" style="color:var(--zm-deposit-fg);opacity:.7">' + (cumDep ? cumDep.toLocaleString() + '원' : '') + '</td>' +
        '<td colspan="2"></td>';
    tbody.appendChild(tr2);
}

// ── 검색형 계정과목 드롭다운 (공유 패널 1개) ──

var catPickerState = { txId: 0, triggerEl: null, typeFilter: 'all' };
var _cpScrollHandler = null;

function buildCategoryPicker(txId, currentCode) {
    var chevron = '<svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    if (currentCode) {
        var found = accountCategories.find(function(c) { return c.code === currentCode; });
        var label = found ? found.name : currentCode;
        var tmap = {'자산':'border-blue-500/40','부채':'border-rose-500/40','자본':'border-purple-500/40',
            '매출':'border-emerald-500/40','매입':'border-orange-500/40','비용':'border-amber-500/40','수익':'border-cyan-500/40'};
        var bc = (found && tmap[found.type]) || 'border-slate-700';
        return '<button type="button" onclick="openCatPicker(' + txId + ', this)" ' +
            'class="w-full flex items-center justify-between gap-1 bg-slate-950 border ' + bc + ' rounded-lg px-2 py-1.5 text-xs text-slate-100' +
            ' hover:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-gray-300/30 transition-colors" ' +
            'style="min-width:140px" data-code="' + escHtml(currentCode) + '">' +
            '<span class="truncate">' + escHtml(label) + '</span>' + chevron + '</button>';
    }
    return '<button type="button" onclick="openCatPicker(' + txId + ', this)" ' +
        'class="w-full flex items-center justify-between gap-1 bg-slate-950 border border-dashed border-slate-700 rounded-lg px-2 py-1.5 text-xs text-slate-500' +
        ' hover:border-slate-600 hover:text-slate-300 focus:outline-none focus:ring-1 focus:ring-gray-300/30 transition-colors" ' +
        'style="min-width:140px" data-code="">' +
        '<span class="truncate">계정과목 선택</span>' + chevron + '</button>';
}

function positionCatPanel() {
    var trigger = catPickerState.triggerEl;
    if (!trigger) return;
    var panel = document.getElementById('catPickerPanel');
    var rect = trigger.getBoundingClientRect();
    var panelH = 360;
    var spaceBelow = window.innerHeight - rect.bottom;
    var top = spaceBelow > panelH ? rect.bottom + 4 : rect.top - panelH - 4;
    panel.style.left = Math.min(rect.left, window.innerWidth - 296) + 'px';
    panel.style.top = Math.max(4, top) + 'px';
}

function openCatPicker(txId, triggerEl) {
    var panel = document.getElementById('catPickerPanel');
    if (catPickerState.triggerEl === triggerEl && !panel.classList.contains('hidden')) {
        closeCatPicker(); return;
    }
    catPickerState.txId = txId;
    catPickerState.triggerEl = triggerEl;
    catPickerState.typeFilter = 'all';
    updatePickerFilterBtns();

    document.getElementById('catPickerSearch').value = '';
    renderCatPickerList();

    panel.classList.remove('hidden');
    panel.style.position = 'fixed';
    positionCatPanel();

    var _cpMouseInPanel = false;
    panel.addEventListener('mouseenter', function() { _cpMouseInPanel = true; });
    panel.addEventListener('mouseleave', function() { _cpMouseInPanel = false; });
    _cpScrollHandler = function(e) {
        if (_cpMouseInPanel) return;
        if (document.getElementById('catPickerPanel').contains(e.target)) return;
        closeCatPicker();
    };
    window.addEventListener('scroll', _cpScrollHandler, true);
    window.addEventListener('resize', _cpScrollHandler);

    setTimeout(function() { document.getElementById('catPickerSearch').focus(); }, 50);
}

function closeCatPicker() {
    document.getElementById('catPickerPanel').classList.add('hidden');
    catPickerState.txId = 0;
    catPickerState.triggerEl = null;
    if (_cpScrollHandler) {
        window.removeEventListener('scroll', _cpScrollHandler, true);
        window.removeEventListener('resize', _cpScrollHandler);
        _cpScrollHandler = null;
    }
}

function setPickerTypeFilter(type) {
    catPickerState.typeFilter = type;
    updatePickerFilterBtns();
    renderCatPickerList();
}

function updatePickerFilterBtns() {
    var active = catPickerState.typeFilter;
    document.querySelectorAll('.cpf-btn').forEach(function(btn) {
        var f = btn.getAttribute('data-pf');
        if (f === active) {
            btn.className = 'cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-primary text-primary font-medium whitespace-nowrap';
        } else {
            btn.className = 'cpf-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap';
        }
    });
}

function renderCatPickerList() {
    var keyword = (document.getElementById('catPickerSearch').value || '').toLowerCase();
    var typeFilter = catPickerState.typeFilter;
    var currentCode = catPickerState.triggerEl ? (catPickerState.triggerEl.getAttribute('data-code') || '') : '';
    var list = document.getElementById('catPickerList');
    var typeColors = {
        '자산':'text-blue-400','부채':'text-rose-400','자본':'text-purple-400',
        '매출':'text-emerald-400','매입':'text-orange-400','비용':'text-amber-400','수익':'text-cyan-400'
    };

    var html = '';
    if (typeFilter === 'all' && !keyword) {
        html += '<div class="px-2 py-1"><button type="button" onclick="selectCatItem(\'\')" ' +
            'class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-slate-800 ' +
            (!currentCode ? 'text-slate-100 font-medium' : 'text-slate-400') + '">해제 (미지정)</button></div>';
    }

    var lastType = '';
    var matchCount = 0;
    accountCategories.forEach(function(cat) {
        if (typeFilter !== 'all' && cat.type !== typeFilter) return;
        if (keyword) {
            var haystack = (cat.code + ' ' + cat.name).toLowerCase();
            if (haystack.indexOf(keyword) < 0) return;
        }
        if (cat.type !== lastType) {
            lastType = cat.type;
            var tc = typeColors[cat.type] || 'text-slate-400';
            html += '<div class="px-3 pt-2 pb-0.5 text-[10px] font-bold tracking-wider ' + tc + '">' + escHtml(cat.type) + '</div>';
        }
        var isActive = cat.code === currentCode;
        html += '<div class="px-2"><button type="button" onclick="selectCatItem(\'' + escHtml(cat.code) + '\')" ' +
            'class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-slate-800 truncate ' +
            (isActive ? 'text-slate-50 font-medium bg-slate-800' : 'text-slate-200') + '">' +
            '<span class="text-slate-500 font-mono mr-1.5">' + escHtml(cat.code) + '</span>' +
            escHtml(cat.name) + '</button></div>';
        matchCount++;
    });

    if (matchCount === 0) {
        html += '<div class="px-4 py-6 text-center text-xs text-slate-500">검색 결과 없음</div>';
    }
    list.innerHTML = html;
}

async function selectCatItem(code) {
    var txId = catPickerState.txId;
    var trigger = catPickerState.triggerEl;
    if (!txId || !trigger) return;
    closeCatPicker();

    trigger.style.outline = '2px solid var(--zm-surface-3)';
    try {
        var res = await fetch(API_URL + '?action=update_tx_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ transactionId: txId, accountCode: code })
        });
        var json = await res.json();
        if (json.success) {
            trigger.style.outline = '2px solid #10b981';
            var span = trigger.querySelector('span');
            if (code) {
                var found = accountCategories.find(function(c) { return c.code === code; });
                if (span) span.textContent = found ? found.name : code;
                trigger.classList.remove('text-slate-500', 'border-dashed', 'border-slate-700');
                trigger.classList.add('text-slate-100');
                var tmap = {'자산':'border-blue-500/40','부채':'border-rose-500/40','자본':'border-purple-500/40',
                    '매출':'border-emerald-500/40','매입':'border-orange-500/40','비용':'border-amber-500/40','수익':'border-cyan-500/40'};
                if (found && tmap[found.type]) {
                    trigger.className = trigger.className.replace(/border-\w+-\d+\/\d+/g, '');
                    trigger.classList.add(tmap[found.type]);
                }
            } else {
                if (span) span.textContent = '계정과목 선택';
                trigger.classList.remove('text-slate-100');
                trigger.classList.add('text-slate-500', 'border-dashed', 'border-slate-700');
            }
            trigger.setAttribute('data-code', code);
            cachedTransactions.forEach(function(tx) {
                if (parseInt(tx.id) === txId) {
                    tx.account_code = code;
                    if (code) {
                        var f = accountCategories.find(function(c) { return c.code === code; });
                        tx.account_name = f ? f.name : '';
                    } else {
                        tx.account_name = '';
                    }
                }
            });
            setTimeout(function() { trigger.style.outline = ''; }, 1200);
        } else {
            trigger.style.outline = '2px solid #ef4444';
            setTimeout(function() { trigger.style.outline = ''; }, 2000);
        }
    } catch (e) {
        trigger.style.outline = '2px solid #ef4444';
        setTimeout(function() { trigger.style.outline = ''; }, 2000);
    }
}

document.addEventListener('click', function(e) {
    var panel = document.getElementById('catPickerPanel');
    if (panel && !panel.classList.contains('hidden')) {
        if (!panel.contains(e.target) && !e.target.closest('[onclick*="openCatPicker"]')) {
            closeCatPicker();
        }
    }
});

function showTxError(title, msg) {
    document.getElementById('txErrorTitle').textContent = title;
    document.getElementById('txErrorMsg').textContent = msg;
    document.getElementById('txError').classList.remove('hidden');
}
function hideTxError() { document.getElementById('txError').classList.add('hidden'); }

// ═══════════════════════════════════════
//  CSV 다운로드
// ═══════════════════════════════════════

function exportCsv() {
    if (!cachedTransactions.length) return;
    var csv = '일시,적요,거래처,메모,출금,입금,잔액,계정과목\n';
    cachedTransactions.forEach(function(tx) {
        var date = tx.transaction_date || tx.date || tx.transactionDate || '';
        var time = tx.transaction_time || tx.time || tx.transactionTime || '';
        var desc = (tx.description || tx.content || '').replace(/"/g, '""');
        var cp = (tx.counterparty || '').replace(/"/g, '""');
        var type = (tx.tx_type || tx.type || tx.transactionType || '').toLowerCase();
        var amount = parseInt(tx.amount || 0);
        var isDeposit = type.indexOf('deposit') >= 0 || type.indexOf('입금') >= 0 || type === 'in';
        var memoText = [tx.displayName, tx.memo].filter(Boolean).join(' ').replace(/"/g, '""');
        var catText = tx.account_code ? (tx.account_code + ' ' + (tx.account_name || '')).replace(/"/g, '""') : '';
        csv += '"' + date + ' ' + time + '","' + desc + '","' + cp + '","' + memoText + '",' + (!isDeposit ? amount : '') + ',' + (isDeposit ? amount : '') + ',' + (tx.balance || 0) + ',"' + catText + '"\n';
    });
    var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '거래내역_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}

// ═══════════════════════════════════════
//  사업자번호
// ═══════════════════════════════════════

function maskResident(num) {
    if (!num || num.length < 4) return num || '';
    return num.substring(0, 3) + '-' + num.substring(3, 5) + '-' + '*'.repeat(num.length - 5);
}

function openResidentModal() {
    var viewMode = document.getElementById('residentViewMode');
    var editMode = document.getElementById('residentEditMode');
    var title = document.getElementById('residentModalTitle');
    document.getElementById('residentInput').value = '';

    if (savedResidentNo) {
        title.textContent = '사업자(주민)번호 확인';
        document.getElementById('residentMasked').textContent = maskResident(savedResidentNo);
        viewMode.classList.remove('hidden');
        editMode.classList.add('hidden');
    } else {
        title.textContent = '사업자(주민)번호 등록';
        viewMode.classList.add('hidden');
        editMode.classList.remove('hidden');
        setTimeout(function() { document.getElementById('residentInput').focus(); }, 100);
    }

    document.getElementById('residentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function switchToResidentEdit() {
    document.getElementById('residentModalTitle').textContent = '사업자(주민)번호 변경';
    document.getElementById('residentViewMode').classList.add('hidden');
    document.getElementById('residentEditMode').classList.remove('hidden');
    document.getElementById('residentInput').value = '';
    setTimeout(function() { document.getElementById('residentInput').focus(); }, 100);
}

function closeResidentModal() { document.getElementById('residentModal').classList.add('hidden'); document.body.style.overflow = ''; }

async function saveResidentFromModal() {
    var num = document.getElementById('residentInput').value.replace(/[^0-9]/g, '');
    if (!num) return;
    document.getElementById('btnSaveResident').disabled = true;
    try {
        var res = await fetch(API_URL + '?action=resident_number', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ residentNumber: num })
        });
        var json = await res.json();
        if (json.success) {
            savedResidentNo = num;
            document.getElementById('residentAlert').classList.add('hidden');
            updateResidentStatus();
            closeResidentModal();
        }
    } catch (e) {}
    document.getElementById('btnSaveResident').disabled = false;
}

// ═══════════════════════════════════════
//  계좌 등록
// ═══════════════════════════════════════

function openRegisterModal() {
    document.getElementById('regBankCode').value = '';
    document.getElementById('regAccountNo').value = '';
    document.getElementById('regAlias').value = '';
    document.getElementById('regResultBox').classList.add('hidden');
    document.getElementById('registerModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeRegisterModal() { document.getElementById('registerModal').classList.add('hidden'); document.body.style.overflow = ''; }

async function registerAccount() {
    var bankCode = document.getElementById('regBankCode').value;
    var accountNo = document.getElementById('regAccountNo').value.replace(/[^0-9]/g, '');
    var alias = document.getElementById('regAlias').value.trim();
    var resultBox = document.getElementById('regResultBox');

    if (!bankCode || !accountNo) {
        resultBox.className = 'p-3 rounded-xl text-sm bg-rose-950/50 text-rose-300';
        resultBox.textContent = '은행과 계좌번호를 입력하세요.';
        resultBox.classList.remove('hidden');
        return;
    }

    document.getElementById('btnRegisterAcc').disabled = true;
    resultBox.className = 'p-3 rounded-xl text-sm bg-slate-800 text-slate-200';
    resultBox.textContent = '계좌를 등록하는 중...';
    resultBox.classList.remove('hidden');

    try {
        var res = await fetch(API_URL + '?action=register_account', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bankCode: bankCode, accountNumber: accountNo })
        });
        var json = await res.json();
        if (json.success) {
            if (alias) {
                await fetch(API_URL + '?action=update_alias', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bankCode: bankCode, accountNumber: accountNo, alias: alias })
                });
            }
            resultBox.className = 'p-3 rounded-xl text-sm bg-emerald-950/50 text-emerald-300';
            resultBox.textContent = json.message || '계좌 연결 완료!';
            setTimeout(function() { closeRegisterModal(); loadAccounts(); }, 1200);
        } else {
            resultBox.className = 'p-3 rounded-xl text-sm bg-rose-950/50 text-rose-300';
            resultBox.textContent = json.message || '등록 실패';
        }
    } catch (e) {
        resultBox.className = 'p-3 rounded-xl text-sm bg-rose-950/50 text-rose-300';
        resultBox.textContent = '네트워크 오류: ' + e.message;
    } finally { document.getElementById('btnRegisterAcc').disabled = false; }
}

// ═══════════════════════════════════════
//  별칭 수정 / 계좌 삭제
// ═══════════════════════════════════════

function openAliasModalForSelected() {
    var acc = getSelectedAccount();
    if (!acc) return;
    var found = accountsList.find(function(a) { return a.bankCode === acc.bankCode && a.accountNumber === acc.accountNumber; });
    pendingAliasBank = acc.bankCode;
    pendingAliasAccNo = acc.accountNumber;
    document.getElementById('aliasInput').value = (found && found.alias) || '';
    document.getElementById('aliasModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('aliasInput').focus(); }, 100);
}
function closeAliasModal() { document.getElementById('aliasModal').classList.add('hidden'); document.body.style.overflow = ''; }

async function saveAlias() {
    var alias = document.getElementById('aliasInput').value.trim();
    document.getElementById('btnSaveAlias').disabled = true;
    try {
        await fetch(API_URL + '?action=update_alias', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bankCode: pendingAliasBank, accountNumber: pendingAliasAccNo, alias: alias })
        });
        closeAliasModal(); loadAccounts();
    } catch (e) { closeAliasModal(); }
    document.getElementById('btnSaveAlias').disabled = false;
}

function openDeleteModalForSelected() {
    var acc = getSelectedAccount();
    if (!acc) return;
    pendingDeleteBank = acc.bankCode;
    pendingDeleteAccNo = acc.accountNumber;
    var bankName = BANKS[acc.bankCode] || acc.bankCode;
    var masked = acc.accountNumber.length > 4 ? acc.accountNumber.slice(0,4) + '****' + acc.accountNumber.slice(-4) : acc.accountNumber;
    document.getElementById('deleteMsg').textContent = bankName + ' ' + masked + ' 계좌를 연결 해제하시겠습니까?';
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.add('hidden'); document.body.style.overflow = ''; }

async function confirmDelete() {
    document.getElementById('btnDelete').disabled = true;
    try {
        await fetch(API_URL + '?action=delete_account', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bankCode: pendingDeleteBank, accountNumber: pendingDeleteAccNo })
        });
        closeDeleteModal(); loadAccounts();
    } catch (e) { closeDeleteModal(); }
    document.getElementById('btnDelete').disabled = false;
}

// ═══════════════════════════════════════
//  거래 분할
// ═══════════════════════════════════════

var splitState = { txId: 0, origAmount: 0, hasExisting: false };

async function openSplitModal(txId, amount) {
    splitState.txId = txId;
    splitState.origAmount = Math.abs(amount);
    splitState.hasExisting = false;

    document.getElementById('splitOrigAmount').textContent = splitState.origAmount.toLocaleString() + '원';
    document.getElementById('splitRowsContainer').innerHTML = '';
    document.getElementById('btnDeleteSplits').classList.add('hidden');

    try {
        var res = await fetch(API_URL + '?action=get_splits&transaction_id=' + txId);
        var json = await res.json();
        if (json.success && json.data && json.data.length > 0) {
            splitState.hasExisting = true;
            document.getElementById('btnDeleteSplits').classList.remove('hidden');
            json.data.forEach(function(s) {
                addSplitRow(s.account_code, s.account_name, s.amount, s.memo);
            });
        } else {
            addSplitRow();
            addSplitRow();
        }
    } catch (e) {
        addSplitRow();
        addSplitRow();
    }

    updateSplitSum();
    document.getElementById('splitModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
}

function closeSplitModal() {
    document.getElementById('splitModal').classList.add('hidden');
    document.body.style.overflow = '';
    splitState.txId = 0;
}

function addSplitRow(code, name, amount, memo) {
    var container = document.getElementById('splitRowsContainer');
    var idx = container.children.length;
    var row = document.createElement('div');
    row.className = 'flex items-start gap-2 split-row';

    var catOptions = '<option value="">계정과목 선택</option>';
    var typeOrder = ['자산','부채','자본','매출','매입','비용','수익'];
    var lastType = '';
    accountCategories.forEach(function(c) {
        if (c.type !== lastType) {
            if (lastType) catOptions += '</optgroup>';
            catOptions += '<optgroup label="' + escHtml(c.type) + '">';
            lastType = c.type;
        }
        var sel = (code && c.code === code) ? ' selected' : '';
        catOptions += '<option value="' + escHtml(c.code) + '" data-name="' + escHtml(c.name) + '"' + sel + '>' + escHtml(c.code) + ' ' + escHtml(c.name) + '</option>';
    });
    if (lastType) catOptions += '</optgroup>';

    row.innerHTML =
        '<div class="flex-1 min-w-0 space-y-1.5">' +
            '<select onchange="updateSplitSum()" class="split-code w-full bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">' + catOptions + '</select>' +
            '<div class="flex gap-1.5">' +
                '<input type="number" min="0" placeholder="금액" value="' + (amount || '') + '" oninput="updateSplitSum()" class="split-amount w-28 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-slate-100 tabular-nums text-right focus:outline-none focus:ring-1 focus:ring-gray-300/30">' +
                '<input type="text" placeholder="메모 (선택)" maxlength="200" value="' + escHtml(memo || '') + '" class="split-memo flex-1 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">' +
            '</div>' +
        '</div>' +
        '<button type="button" onclick="removeSplitRow(this)" class="mt-1 p-1.5 text-slate-600 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition-colors flex-shrink-0" title="삭제">' +
            '<i data-lucide="x" class="w-3.5 h-3.5"></i>' +
        '</button>';

    container.appendChild(row);
    if (window.lucide) lucide.createIcons();
}

function removeSplitRow(btn) {
    var row = btn.closest('.split-row');
    if (row) row.remove();
    updateSplitSum();
}

function updateSplitSum() {
    var rows = document.querySelectorAll('#splitRowsContainer .split-row');
    var sum = 0;
    var allValid = true;
    rows.forEach(function(row) {
        var amt = parseInt(row.querySelector('.split-amount').value) || 0;
        var code = row.querySelector('.split-code').value;
        sum += amt;
        if (!code || amt <= 0) allValid = false;
    });

    var sumEl = document.getElementById('splitSumDisplay');
    var diffRow = document.getElementById('splitDiffRow');
    var diffEl = document.getElementById('splitDiffDisplay');
    var diff = splitState.origAmount - sum;

    sumEl.textContent = sum.toLocaleString() + '원';

    if (diff === 0) {
        sumEl.className = 'text-emerald-400 tabular-nums font-bold';
        diffRow.classList.add('hidden');
    } else {
        sumEl.className = 'text-rose-400 tabular-nums font-bold';
        diffRow.classList.remove('hidden');
        diffEl.textContent = (diff > 0 ? '+' : '') + diff.toLocaleString() + '원 남음';
    }

    var canSave = rows.length >= 2 && diff === 0 && allValid;
    document.getElementById('btnSaveSplits').disabled = !canSave;
}

async function saveSplits() {
    var rows = document.querySelectorAll('#splitRowsContainer .split-row');
    var splits = [];
    rows.forEach(function(row) {
        var sel = row.querySelector('.split-code');
        var opt = sel.options[sel.selectedIndex];
        splits.push({
            account_code: sel.value,
            account_name: opt && opt.dataset.name ? opt.dataset.name : sel.value,
            amount: parseInt(row.querySelector('.split-amount').value) || 0,
            memo: row.querySelector('.split-memo').value || ''
        });
    });

    var btn = document.getElementById('btnSaveSplits');
    btn.disabled = true;
    btn.textContent = '저장 중…';

    try {
        var res = await fetch(API_URL + '?action=save_splits', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ transactionId: splitState.txId, splits: splits })
        });
        var json = await res.json();
        if (json.success) {
            closeSplitModal();
            cachedTransactions.forEach(function(tx) {
                if (parseInt(tx.id) === splitState.txId) {
                    tx.account_code = null;
                    tx.account_name = '분할';
                }
            });
            renderTable(cachedTransactions);
        } else {
            alert(json.error || '저장에 실패했습니다.');
        }
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
    }
    btn.disabled = false;
    btn.textContent = '저장';
}

async function deleteSplits() {
    if (!(await AppUI.confirm('분할을 해제하시겠습니까? 원거래의 계정과목도 초기화됩니다.'))) return;
    var btn = document.getElementById('btnDeleteSplits');
    btn.disabled = true;

    try {
        var res = await fetch(API_URL + '?action=delete_splits', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ transactionId: splitState.txId })
        });
        var json = await res.json();
        if (json.success) {
            closeSplitModal();
            cachedTransactions.forEach(function(tx) {
                if (parseInt(tx.id) === splitState.txId) {
                    tx.account_code = '';
                    tx.account_name = '';
                }
            });
            renderTable(cachedTransactions);
        } else {
            alert(json.error || '해제에 실패했습니다.');
        }
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
    }
    btn.disabled = false;
}

// ═══════════════════════════════════════
//  유틸
// ═══════════════════════════════════════

function escHtml(s) { var div = document.createElement('div'); div.textContent = s; return div.innerHTML; }

function formatDate(raw) {
    if (!raw) return '';
    var digits = raw.replace(/[^0-9]/g, '');
    if (digits.length >= 8) return digits.substring(0,4) + '-' + digits.substring(4,6) + '-' + digits.substring(6,8);
    if (raw.indexOf('-') >= 0 || raw.indexOf('/') >= 0) return raw.substring(0, 10);
    return raw;
}

function formatTime(raw) {
    if (!raw) return '';
    if (raw.indexOf(':') >= 0) {
        var parts = raw.split(':');
        return parts[0].padStart(2, '0') + ':' + parts[1].padStart(2, '0');
    }
    var digits = raw.replace(/[^0-9]/g, '');
    if (digits.length >= 4) return digits.substring(0,2) + ':' + digits.substring(2,4);
    return raw;
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeRegisterModal(); closeAliasModal(); closeDeleteModal(); closeResidentModal(); closePasswordModal(); closeSplitModal(); }
});

document.getElementById('txAccountPw').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') submitWithPassword();
});
</script>
