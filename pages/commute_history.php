<?php
$pageTitle = '출/퇴근이력';
$currentPage = 'attendance';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 제목 -->
        <div class="flex items-center gap-3 mb-6">
            <button onclick="history.back()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-400 hover:text-slate-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <div>
                <h2 class="text-lg font-bold text-slate-100">출/퇴근이력</h2>
                <p class="text-sm text-slate-400 mt-0.5">직원별 출퇴근 기록을 조회합니다</p>
            </div>
        </div>

        <!-- 검색 필터 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
            <!-- 필터 헤더 -->
            <div class="flex items-center gap-2 px-5 py-3 bg-slate-950 border-b border-slate-800">
                <i data-lucide="sliders-horizontal" class="w-4 h-4 text-primary"></i>
                <span class="text-sm font-semibold text-slate-200">검색 조건</span>
            </div>

            <!-- 필터 그리드 -->
            <div class="filter-grid">
                <div class="filter-row">
                    <div class="filter-label">소속</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="building-2" class="filter-icon"></i>
                            <input type="text" id="filterOrg" class="filter-input" placeholder="소속을 입력해주세요">
                        </div>
                    </div>
                    <div class="filter-label">부서</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="layout-grid" class="filter-icon"></i>
                            <input type="text" id="filterDept" class="filter-input" placeholder="부서를 입력해주세요">
                        </div>
                    </div>
                </div>
                <div class="filter-row" style="border-bottom:none">
                    <div class="filter-label">이름</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="user" class="filter-icon"></i>
                            <input type="text" id="filterName" class="filter-input" placeholder="이름을 입력해주세요">
                        </div>
                    </div>
                    <div class="filter-label">조회 기간</div>
                    <div class="filter-value">
                        <div class="flex items-center gap-2">
                            <div class="filter-input-wrap flex-1">
                                <i data-lucide="calendar" class="filter-icon"></i>
                                <input type="date" id="filterDateFrom" class="filter-input">
                            </div>
                            <span class="text-slate-500 font-medium text-sm px-1">~</span>
                            <div class="filter-input-wrap flex-1">
                                <i data-lucide="calendar" class="filter-icon"></i>
                                <input type="date" id="filterDateTo" class="filter-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 버튼 영역 -->
            <div class="flex justify-end gap-2 px-5 py-3 bg-slate-950 border-t border-slate-800">
                <button id="btnReset" class="btn btn-secondary">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                    초기화
                </button>
                <button id="btnSearch" class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    검색
                </button>
            </div>
        </div>

        <!-- 결과 테이블 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
            <!-- 테이블 상단 -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800">
                <p class="text-sm text-slate-300">
                    총 <span id="resultCount" class="text-primary font-bold text-base">0</span>건의 출/퇴근이력
                </p>
                <select class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm text-slate-300 focus:outline-none focus:border-gray-300 cursor-pointer">
                    <option>10</option>
                    <option>20</option>
                    <option selected>50</option>
                    <option>100</option>
                </select>
            </div>

            <table id="commuteTable" class="w-full text-sm emp-table">
                <thead>
                    <tr>
                        <th class="py-3.5 px-4 text-center">이름</th>
                        <th class="py-3.5 px-4 text-center">소속</th>
                        <th class="py-3.5 px-4 text-center">부서</th>
                        <th class="py-3.5 px-4 text-center">출근시간</th>
                        <th class="py-3.5 px-4 text-center">퇴근시간</th>
                        <th class="py-3.5 px-4 text-center">연장근무신청여부</th>
                        <th class="py-3.5 px-4 text-center">일자</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="py-20 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-14 h-14 rounded-full bg-slate-800 flex items-center justify-center mb-1">
                                    <svg class="w-7 h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-slate-400">조회된 출/퇴근이력이 없습니다</p>
                                <p class="text-sm text-slate-500">검색 조건을 입력하고 검색 버튼을 눌러주세요</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <div class="flex justify-center items-center gap-1 py-4 border-t border-slate-800">
                <button class="pg-btn pg-disabled" disabled>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
                <button class="pg-btn pg-disabled" disabled>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button class="pg-btn pg-active">1</button>
                <button class="pg-btn pg-disabled" disabled>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button class="pg-btn pg-disabled" disabled>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    const filterOrg = document.getElementById('filterOrg');
    const filterDept = document.getElementById('filterDept');
    const filterName = document.getElementById('filterName');
    const filterDateFrom = document.getElementById('filterDateFrom');
    const filterDateTo = document.getElementById('filterDateTo');
    const resultCount = document.getElementById('resultCount');
    const table = document.getElementById('commuteTable');
    const tbody = table.querySelector('tbody');

    // 검색 버튼
    btnSearch.addEventListener('click', function() {
        const org = filterOrg.value.trim().toLowerCase();
        const dept = filterDept.value.trim().toLowerCase();
        const name = filterName.value.trim().toLowerCase();
        const dateFrom = filterDateFrom.value;
        const dateTo = filterDateTo.value;

        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;

        rows.forEach(function(row) {
            const cells = row.querySelectorAll('td');
            // 데이터 행이 아닌 경우 (빈 상태 메시지 등) 건너뜀
            if (cells.length < 7) return;

            const rowName = (cells[0].textContent || '').trim().toLowerCase();
            const rowOrg = (cells[1].textContent || '').trim().toLowerCase();
            const rowDept = (cells[2].textContent || '').trim().toLowerCase();
            // 일자 컬럼 (7번째, index 6)
            const rowDate = (cells[6].textContent || '').trim();

            let show = true;

            if (org && !rowOrg.includes(org)) show = false;
            if (dept && !rowDept.includes(dept)) show = false;
            if (name && !rowName.includes(name)) show = false;

            // 날짜 필터: rowDate가 YYYY-MM-DD 형식인 경우 비교
            if (dateFrom && rowDate) {
                if (rowDate < dateFrom) show = false;
            }
            if (dateTo && rowDate) {
                if (rowDate > dateTo) show = false;
            }

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        resultCount.textContent = visibleCount;
    });

    // 초기화 버튼
    btnReset.addEventListener('click', function() {
        filterOrg.value = '';
        filterDept.value = '';
        filterName.value = '';
        filterDateFrom.value = '';
        filterDateTo.value = '';

        const rows = tbody.querySelectorAll('tr');
        let totalCount = 0;
        rows.forEach(function(row) {
            row.style.display = '';
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) totalCount++;
        });

        resultCount.textContent = totalCount;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
