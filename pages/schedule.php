<?php
$pageTitle = '일정';
$currentPage = 'schedule';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// 카테고리 + 직원 목록 (JS에 전달)
$pdo = getDBConnection();
$categories = [];
$employees  = [];
$hasDB = false;

if ($pdo) {
    try {
        $hasDB = true;
        $catStmt = $pdo->query("
            SELECT ci.id AS item_id, ci.name, ci.code,
                   COALESCE(scc.color_code, 'blue') AS color_code
            FROM common_code_items ci
            JOIN common_code_groups cg ON ci.group_id = cg.id
            LEFT JOIN schedule_category_config scc ON scc.item_id = ci.id
            WHERE cg.module = 'schedule' AND cg.name = '일정유형' AND ci.is_active = 1
            ORDER BY ci.sort_order, ci.id
        ");
        $categories = $catStmt->fetchAll();

        $empStmt = $pdo->query("
            SELECT e.id, e.name, e.position, d.name AS department
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.is_active = 1 AND e.employment_status = '재직'
            ORDER BY e.name
        ");
        $employees = $empStmt->fetchAll();
    } catch (PDOException $e) {
        $hasDB = false;
    }
}

?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300 h-[calc(100vh-3.5rem)] overflow-hidden">
<main class="p-5 h-full flex flex-col overflow-hidden">

    <!-- 페이지 헤더 -->
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold text-slate-100">일정</h2>
        <button onclick="openCreateModal()" class="flex items-center gap-1.5 px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> 일정 등록
        </button>
    </div>

    <div class="flex gap-4 flex-1 min-h-0">
        <!-- 왼쪽 캘린더 패널 -->
        <div class="w-56 shrink-0 flex flex-col gap-3 min-h-0">
            <!-- 일정 검색 -->
            <div id="searchPanel" class="space-y-2">
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" id="scheduleSearch" placeholder="일정 검색"
                           class="w-full bg-transparent border border-slate-700 rounded-lg pl-8 pr-3 py-1.5 text-sm text-slate-200 placeholder-slate-500 focus:border-slate-500 outline-none transition-colors">
                </div>
                <div class="flex gap-1" id="searchPeriodChips">
                    <button data-period="month" class="search-chip active">이번 달</button>
                    <button data-period="year" class="search-chip">올해</button>
                    <button data-period="all" class="search-chip">전체</button>
                </div>
                <div id="searchResults" class="hidden bg-slate-900 border border-slate-800 rounded-xl p-2 max-h-52 overflow-y-auto"></div>
            </div>

            <!-- 미니 달력 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3">
                <div class="flex items-center justify-between mb-2.5">
                    <button onclick="miniPrev()" class="p-1 hover:bg-slate-800 rounded transition-colors">
                        <i data-lucide="chevron-left" class="w-4 h-4 text-slate-400"></i>
                    </button>
                    <span id="miniCalTitle" class="text-sm font-semibold text-slate-200"></span>
                    <button onclick="miniNext()" class="p-1 hover:bg-slate-800 rounded transition-colors">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-0 text-center mb-1.5">
                    <span class="text-[11px] text-red-400 py-1">일</span>
                    <span class="text-[11px] text-slate-500 py-1">월</span>
                    <span class="text-[11px] text-slate-500 py-1">화</span>
                    <span class="text-[11px] text-slate-500 py-1">수</span>
                    <span class="text-[11px] text-slate-500 py-1">목</span>
                    <span class="text-[11px] text-slate-500 py-1">금</span>
                    <span class="text-[11px] text-primary py-1">토</span>
                </div>
                <div id="miniCalGrid" class="grid grid-cols-7 gap-0.5"></div>
            </div>

            <!-- 캘린더 목록 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex-1 overflow-y-auto">
                <div id="calendarList"></div>
            </div>
        </div>

        <!-- FullCalendar 영역 -->
        <div class="flex-1 bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col min-h-0 overflow-hidden">
            <div id="fcCalendar" class="flex-1 min-h-0 overflow-hidden"></div>
        </div>

        <!-- 사이드 패널 -->
        <div class="w-72 shrink-0 flex flex-col gap-4 min-h-0">

            <!-- 내 할 일 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex-1 flex flex-col min-h-0">
                <h3 class="flex items-center gap-1.5 text-sm font-semibold text-slate-100 mb-2.5 shrink-0">
                    <i data-lucide="check-square" class="w-4 h-4 text-primary"></i> 내 할 일
                </h3>

                <!-- 할 일 추가 버튼 -->
                <button onclick="openTaskModal()" class="flex items-center gap-1.5 w-full px-3 py-2 mb-2.5 text-sm text-slate-400 border border-slate-800 rounded-lg hover:border-slate-600 hover:text-slate-200 transition-colors shrink-0">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> 할 일 추가
                </button>

                <!-- 탭 -->
                <div id="taskTabs" class="flex gap-1 mb-2.5 shrink-0"></div>

                <!-- 목록 -->
                <div id="taskList" class="space-y-1 flex-1 overflow-y-auto -mr-1 pr-1"></div>
            </div>

            <!-- 다가오는 일정 + 범례 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col min-h-0 shrink-0">
                <h3 class="flex items-center gap-1.5 text-sm font-semibold text-slate-100 mb-3">
                    <i data-lucide="clock" class="w-4 h-4 text-primary"></i> 다가오는 일정
                </h3>
                <div id="sidePanel" class="space-y-1.5 max-h-52 overflow-y-auto"></div>
            </div>
        </div>
    </div>

</main>
</div>

<!-- 등록 모달 -->
<div id="createModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/40" onclick="if(event.target===this)closeCreateModal()">
    <div class="bg-slate-900 rounded-xl shadow-xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-lg font-bold text-slate-100">일정 등록</h3>
            <button onclick="closeCreateModal()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">일정 제목 <span class="text-amber-500">*</span></label>
                <input type="text" id="cTitle" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 focus:ring-1 focus:ring-gray-300 outline-none" placeholder="일정 제목을 입력해주세요">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">시작일 <span class="text-amber-500">*</span></label>
                    <input type="date" id="cStartDate" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none">
                </div>
                <div id="cStartTimeWrap">
                    <label class="block text-sm font-medium text-slate-200 mb-1">시작시간</label>
                    <input type="time" id="cStartTime" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" value="09:00">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">종료일 <span class="text-amber-500">*</span></label>
                    <input type="date" id="cEndDate" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none">
                </div>
                <div id="cEndTimeWrap">
                    <label class="block text-sm font-medium text-slate-200 mb-1">종료시간</label>
                    <input type="time" id="cEndTime" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" value="18:00">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="cAllDay" class="w-4 h-4 accent-primary rounded" onchange="toggleAllDay('c')">
                <label for="cAllDay" class="text-sm text-slate-300">종일 일정</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">카테고리</label>
                <select id="cCategory" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></select>
            </div>
            <!-- 참석자 -->
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">참석자</label>
                <div class="relative">
                    <input type="text" id="cAttendeeInput" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" placeholder="이름으로 검색..." autocomplete="off">
                    <div id="cAttendeeDropdown" class="hidden absolute z-10 w-full mt-1 bg-slate-900 border border-slate-800 rounded-lg shadow-lg max-h-40 overflow-y-auto"></div>
                </div>
                <div id="cAttendeeTags" class="flex flex-wrap gap-1.5 mt-2"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">내용</label>
                <textarea id="cDesc" rows="3" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" placeholder="일정 내용을 입력해주세요"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-800">
            <button onclick="closeCreateModal()" class="btn btn-secondary">취소</button>
            <button onclick="saveEvent()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90">등록</button>
        </div>
    </div>
</div>

<!-- 상세/수정 모달 -->
<div id="detailModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/40" onclick="if(event.target===this)closeDetailModal()">
    <div class="bg-slate-900 rounded-xl shadow-xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <h3 id="dModalTitle" class="text-lg font-bold text-slate-100">일정 상세</h3>
                <button id="btnImportant" onclick="toggleImportant()" title="중요 일정" class="p-1 rounded hover:bg-slate-800 transition-colors">
                    <i id="importantIcon" data-lucide="star" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- 상세보기 모드 -->
        <div id="detailView" class="px-6 py-5 space-y-3">
            <div id="dTitle" class="text-base font-bold text-slate-100"></div>
            <div class="flex items-center gap-2 text-sm text-slate-300">
                <i data-lucide="calendar" class="w-4 h-4"></i>
                <span id="dDate"></span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-300">
                <i data-lucide="tag" class="w-4 h-4"></i>
                <span id="dCategory"></span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-300">
                <i data-lucide="user" class="w-4 h-4"></i>
                <span id="dCreator"></span>
            </div>
            <div id="dAttendeesWrap" class="flex items-start gap-2 text-sm text-slate-300">
                <i data-lucide="users" class="w-4 h-4 mt-0.5 shrink-0"></i>
                <div id="dAttendees"></div>
            </div>
            <div id="dDescWrap" class="pt-2 border-t border-slate-800">
                <p id="dDesc" class="text-sm text-slate-300 whitespace-pre-wrap"></p>
            </div>
        </div>

        <!-- 수정 모드 (등록 모달과 동일 구조) -->
        <div id="editView" class="hidden px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="eId">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">일정 제목 <span class="text-amber-500">*</span></label>
                <input type="text" id="eTitle" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-200 mb-1">시작일</label><input type="date" id="eStartDate" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></div>
                <div id="eStartTimeWrap"><label class="block text-sm font-medium text-slate-200 mb-1">시작시간</label><input type="time" id="eStartTime" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-200 mb-1">종료일</label><input type="date" id="eEndDate" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></div>
                <div id="eEndTimeWrap"><label class="block text-sm font-medium text-slate-200 mb-1">종료시간</label><input type="time" id="eEndTime" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="eAllDay" class="w-4 h-4 accent-primary rounded" onchange="toggleAllDay('e')">
                <label for="eAllDay" class="text-sm text-slate-300">종일 일정</label>
            </div>
            <div><label class="block text-sm font-medium text-slate-200 mb-1">카테고리</label><select id="eCategory" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></select></div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">참석자</label>
                <div class="relative">
                    <input type="text" id="eAttendeeInput" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" placeholder="이름으로 검색..." autocomplete="off">
                    <div id="eAttendeeDropdown" class="hidden absolute z-10 w-full mt-1 bg-slate-900 border border-slate-800 rounded-lg shadow-lg max-h-40 overflow-y-auto"></div>
                </div>
                <div id="eAttendeeTags" class="flex flex-wrap gap-1.5 mt-2"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-200 mb-1">내용</label><textarea id="eDesc" rows="3" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none"></textarea></div>
        </div>

        <!-- 하단 버튼 -->
        <div class="flex justify-between px-6 py-4 border-t border-slate-800">
            <div id="dBtnLeft">
                <button onclick="confirmDelete()" class="px-4 py-2 text-sm text-amber-500 border border-amber-200 rounded-lg hover:bg-amber-50">삭제</button>
            </div>
            <div id="dBtnRight" class="flex gap-2">
                <button onclick="closeDetailModal()" class="btn btn-secondary">닫기</button>
                <button id="btnEdit" onclick="switchToEdit()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90">수정</button>
                <button id="btnSave" onclick="saveEdit()" class="hidden px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 캘린더 이벤트 상세 모달 -->
<div id="ceDetailModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/40" onclick="if(event.target===this)closeCEDetail()">
    <div class="bg-slate-900 rounded-xl shadow-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 id="ceTitle" class="text-lg font-bold text-slate-100 truncate"></h3>
            <button onclick="closeCEDetail()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div id="ceCategoryBadge"></div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 text-slate-300">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-500"></i>
                    <span id="ceDate"></span>
                </div>
                <div id="ceDescWrap" class="hidden">
                    <p id="ceDesc" class="text-slate-300 leading-relaxed mt-2"></p>
                </div>
                <div id="ceRefWrap" class="hidden mt-2">
                    <p class="text-slate-500 text-xs flex items-center gap-1.5">
                        <i data-lucide="scale" class="w-3.5 h-3.5"></i>
                        <span id="ceRef"></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="flex justify-end px-6 py-3 border-t border-slate-800">
            <button onclick="closeCEDetail()" class="btn btn-secondary">닫기</button>
        </div>
    </div>
</div>

<!-- 할 일 상세/수정 모달 -->
<div id="taskModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/40" onclick="if(event.target===this)closeTaskModal()">
    <div class="bg-slate-900 rounded-xl shadow-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-lg font-bold text-slate-100">할 일</h3>
            <button onclick="closeTaskModal()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="px-6 py-3 space-y-3">
            <input type="hidden" id="tId">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">내용 <span class="text-amber-500">*</span></label>
                <input type="text" id="tTitle" maxlength="200" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" placeholder="할 일 내용">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">마감일</label>
                    <input type="date" id="tDue" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">우선순위</label>
                    <select id="tPriority" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none">
                        <option value="urgent">긴급</option>
                        <option value="high">높음</option>
                        <option value="normal" selected>보통</option>
                        <option value="low">낮음</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">메모</label>
                <textarea id="tDesc" rows="3" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:border-gray-300 outline-none" placeholder="상세 내용(선택)"></textarea>
            </div>
        </div>
        <div class="flex justify-between px-6 py-4 border-t border-slate-800">
            <button id="tDeleteBtn" onclick="deleteTask()" class="px-4 py-2 text-sm text-rose-400 border border-rose-900/60 rounded-lg hover:bg-rose-900/20">삭제</button>
            <div class="flex gap-2">
                <button onclick="closeTaskModal()" class="btn btn-secondary">취소</button>
                <button onclick="saveTask()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 토스트 컨테이너 -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-[70] space-y-2"></div>

<!-- FullCalendar CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js" integrity="sha384-B1OFx8Gy9GjPu8UbUyXbGQpzll9ubAUQ9agInFJ8NnD7nYG1u/CLR+Sqr5yifl4q" crossorigin="anonymous"></script>

<!-- PHP → JS 데이터 브릿지 -->
<script>
window.__scheduleConfig = {
    basePath: '<?= rtrim(str_replace("\\", "/", str_replace(realpath($_SERVER["DOCUMENT_ROOT"]), "", realpath(__DIR__ . "/.."))), "/") ?>',
    categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>,
    employees: <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>,
    hasDB: <?= $hasDB ? 'true' : 'false' ?>,
};
</script>

<!-- 일정 JS (FullCalendar 통합) -->
<script src="<?= rtrim(str_replace("\\", "/", str_replace(realpath($_SERVER["DOCUMENT_ROOT"]), "", realpath(__DIR__ . "/.."))), "/") ?>/assets/js/schedule.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
