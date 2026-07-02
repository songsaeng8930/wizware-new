<?php
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!function_exists('getOrgLabel')) {
    require_once __DIR__ . '/../includes/org_hierarchy.php';
}
$pdo = getDBConnection();
$hasDB = false;
$cards = [];
$expenseCategories = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM cards LIMIT 1');
        $hasDB = true;
        $cards = $pdo->query('SELECT id, card_alias FROM cards WHERE is_active = 1 ORDER BY card_alias')->fetchAll();
        $expenseCategories = $pdo->query("
            SELECT ci.code, ci.name FROM common_code_items ci
            JOIN common_code_groups cg ON ci.group_id = cg.id
            WHERE cg.module = 'card' AND cg.name = '비용항목' AND ci.is_active = 1
            ORDER BY ci.sort_order
        ")->fetchAll();
    } catch (PDOException $e) {
        $hasDB = false;
    }
}
if (empty($expenseCategories)) {
    $expenseCategories = [
        ['code'=>'FOOD','name'=>'식대'],['code'=>'TRANS','name'=>'교통비'],
        ['code'=>'ENT','name'=>'접대비'],['code'=>'SUP','name'=>'소모품'],['code'=>'ETC','name'=>'기타'],
    ];
}

// 직원 목록 조회
$employees = [];
$currentUserName = '관리자';
if ($pdo) {
    try {
        $employees = $pdo->query("SELECT e.id, e.name, d.name AS dept_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.is_active = 1 ORDER BY d.name, e.name")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($employees)) $currentUserName = $employees[0]['name'];
    } catch (PDOException $e) {}
}
$currentUserNameJson = json_encode($currentUserName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
$employeesJson = json_encode($employees, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>

<!-- 지출내역/등록 전환 버튼 -->
<div class="flex items-center justify-end gap-2 mb-5">
    <button onclick="showView('list')" id="btnList" class="zm-tab zm-tab-active px-4 py-2 text-sm rounded-lg border transition-colors">
        <i data-lucide="list" class="mr-1 w-4 h-4"></i> 지출내역
    </button>
    <button onclick="showView('register')" id="btnRegister" class="zm-tab px-4 py-2 text-sm rounded-lg border transition-colors">
        <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 지출등록
    </button>
</div>

        <!-- ===== 지출내역 리스트 뷰 ===== -->
        <div id="listView">
            <!-- 검색 필터 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
                <!-- 기간 퀵필터 -->
                <div class="flex items-center gap-2 px-5 py-3 bg-slate-950/80 border-b border-slate-800">
                    <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider mr-1">기간</span>
                    <button type="button" onclick="setDateRange('today')" class="zm-tab date-chip px-3 py-1 text-sm rounded-full border transition-all" data-range="today">오늘</button>
                    <button type="button" onclick="setDateRange('week')" class="zm-tab date-chip px-3 py-1 text-sm rounded-full border transition-all" data-range="week">이번주</button>
                    <button type="button" onclick="setDateRange('month')" class="zm-tab zm-tab-active date-chip px-3 py-1 text-sm rounded-full border transition-all" data-range="month">이번달</button>
                    <button type="button" onclick="setDateRange('3month')" class="zm-tab date-chip px-3 py-1 text-sm rounded-full border transition-all" data-range="3month">3개월</button>
                    <div class="flex items-center gap-1.5 ml-3 pl-3 border-l border-slate-700">
                        <input type="date" id="filterDateFrom" class="text-sm border border-slate-800 rounded-lg px-2.5 py-1 text-slate-300 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none" onchange="filterExpenses();clearDateChipActive()">
                        <span class="text-slate-600">~</span>
                        <input type="date" id="filterDateTo" class="text-sm border border-slate-800 rounded-lg px-2.5 py-1 text-slate-300 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none" onchange="filterExpenses();clearDateChipActive()">
                    </div>
                </div>
                <!-- 상세 필터 -->
                <div class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                        <!-- 카드 -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-slate-400 shrink-0">카드</label>
                            <select id="filterCard" class="text-sm border border-slate-800 rounded-lg pl-3 pr-8 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none min-w-[160px]" onchange="filterExpenses()">
                                <option value="">전체</option>
                                <?php foreach ($cards as $c): ?>
                                <option value="<?= htmlspecialchars($c['card_alias']) ?>"><?= htmlspecialchars($c['card_alias']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- 사용자 -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-slate-400 shrink-0">사용자</label>
                            <select id="filterUser" class="text-sm border border-slate-800 rounded-lg pl-3 pr-8 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none min-w-[140px]" onchange="filterExpenses()">
                                <option value="">전체</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['name']) ?>">
                                    <?= htmlspecialchars($emp['name']) ?><?= $emp['dept_name'] ? ' (' . htmlspecialchars($emp['dept_name']) . ')' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- 항목 -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-slate-400 shrink-0">항목</label>
                            <select id="filterCategory" class="text-sm border border-slate-800 rounded-lg pl-3 pr-8 py-1.5 text-slate-200 bg-slate-900 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none min-w-[110px]" onchange="filterExpenses()">
                                <option value="">전체</option>
                                <?php foreach ($expenseCategories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- 사용구분 (토글 칩) -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-slate-400 shrink-0">사용구분</label>
                            <div class="inline-flex rounded-lg border border-slate-800 overflow-hidden" id="usageTypeToggle">
                                <label class="zm-tab usage-chip zm-tab-active px-3 py-1.5 text-sm cursor-pointer transition-colors">
                                    <input type="radio" name="filterUsageType" value="" checked onchange="updateUsageChipStyle();filterExpenses()" class="hidden"> 전체
                                </label>
                                <label class="zm-tab usage-chip px-3 py-1.5 text-sm cursor-pointer transition-colors border-l border-slate-800">
                                    <input type="radio" name="filterUsageType" value="법인" onchange="updateUsageChipStyle();filterExpenses()" class="hidden"> 법인
                                </label>
                                <label class="zm-tab usage-chip px-3 py-1.5 text-sm cursor-pointer transition-colors border-l border-slate-800">
                                    <input type="radio" name="filterUsageType" value="개인" onchange="updateUsageChipStyle();filterExpenses()" class="hidden"> 개인
                                </label>
                            </div>
                        </div>
                        <!-- 버튼 (우측 밀어내기) -->
                        <div class="flex items-center gap-2 ml-auto">
                            <button type="button" onclick="resetFilters()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-slate-400 rounded-lg hover:bg-slate-800 transition-colors">
                                <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> 초기화
                            </button>
                            <button type="button" onclick="filterExpenses()" class="inline-flex items-center gap-1 px-4 py-1.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i> 검색
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 리스트 테이블 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
                    <span class="text-sm text-slate-400">총 <strong id="totalCount" class="text-primary">0</strong>건</span>
                    <button onclick="exportExcel()" class="btn btn-secondary btn-sm">
                        <i data-lucide="file-spreadsheet" class="mr-1 text-amber-700 w-4 h-4"></i> 엑셀 다운로드
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full emp-table">
                        <thead>
                            <tr class="border-b border-slate-800">
                                <th class="px-4 py-3 text-center w-[100px]">사용일</th>
                                <th class="px-4 py-3 text-center">카드명</th>
                                <th class="px-4 py-3 text-center w-[90px]">카드번호</th>
                                <th class="px-4 py-3 text-center w-[90px]">사용자</th>
                                <th class="px-4 py-3 text-center">항목</th>
                                <th class="px-4 py-3 text-center w-[70px]">구분</th>
                                <th class="px-4 py-3 text-right w-[120px]">사용금액</th>
                                <th class="px-4 py-3 text-center">적요</th>
                                <th class="px-4 py-3 text-center w-[90px]">처리상태</th>
                            </tr>
                        </thead>
                        <tbody id="expenseTableBody">
                        </tbody>
                    </table>
                </div>
                <!-- 페이지네이션 -->
                <div class="flex items-center justify-center gap-1 py-4 border-t border-slate-800" id="pagination"></div>
            </div>
        </div>

        <!-- ===== 지출등록/수정 뷰 ===== -->
        <div id="registerView" class="hidden">
            <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
                <!-- 폼 헤더 -->
                <div class="px-5 py-4 border-b border-slate-800">
                    <h2 class="text-lg font-bold text-slate-100" id="registerTitle">지출등록</h2>
                </div>

                <form id="expenseForm" onsubmit="return saveExpense(event)">
                    <input type="hidden" id="expenseId" value="0">
                    <input type="hidden" id="expenseSubCategory" value="식사">

                    <div class="p-4 md:p-6 space-y-6">

                        <!-- Section: 지출분류 -->
                        <div>
                            <div class="form-section-title mb-3">지출분류</div>
                            <div class="flex gap-2 overflow-x-auto pb-1 mobile-scroll-hide" id="categoryTabs">
                                <button type="button" data-cat="식사" onclick="selectCategory('식사')" class="card-cat-tab active px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="utensils" class="w-4 h-4"></i> 식사
                                </button>
                                <button type="button" data-cat="여비교통비" onclick="selectCategory('여비교통비')" class="card-cat-tab px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="bus" class="w-4 h-4"></i> 여비교통비
                                </button>
                                <button type="button" data-cat="영업사업비" onclick="selectCategory('영업사업비')" class="card-cat-tab px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="handshake" class="w-4 h-4"></i> 영업사업비
                                </button>
                                <button type="button" data-cat="구입비" onclick="selectCategory('구입비')" class="card-cat-tab px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="shopping-cart" class="w-4 h-4"></i> 구입비
                                </button>
                            </div>
                            <div class="mt-4">
                                <label class="reg-label">항목 <span class="text-amber-500">*</span></label>
                                <select id="expCategory" class="reg-select" required>
                                    <option value="">항목 선택</option>
                                    <?php foreach ($expenseCategories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Section: 결제정보 -->
                        <div>
                            <div class="form-section-title mb-3">결제정보</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-x-8">
                                <!-- 지출비용 -->
                                <div>
                                    <label class="reg-label">지출비용 <span class="text-amber-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" id="expAmount" class="reg-input pr-10 text-lg font-semibold" inputmode="numeric" placeholder="0" oninput="formatNumber(this)" required>
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-500 font-medium pointer-events-none">원</span>
                                    </div>
                                </div>
                                <!-- 사용일 -->
                                <div>
                                    <label class="reg-label">사용일 <span class="text-amber-500">*</span></label>
                                    <input type="date" id="expUsageDate" class="reg-input" required>
                                    <div class="flex gap-1.5 mt-2">
                                        <button type="button" onclick="setExpDate('today')" class="px-3 py-1.5 text-sm font-medium rounded-full border border-slate-800 text-slate-300 hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">오늘</button>
                                        <button type="button" onclick="setExpDate('yesterday')" class="px-3 py-1.5 text-sm font-medium rounded-full border border-slate-800 text-slate-300 hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">어제</button>
                                        <button type="button" onclick="setExpDate('2days')" class="px-3 py-1.5 text-sm font-medium rounded-full border border-slate-800 text-slate-300 hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">2일전</button>
                                    </div>
                                </div>
                                <!-- 법인카드 -->
                                <div>
                                    <label class="reg-label">법인카드 <span class="text-amber-500">*</span></label>
                                    <select id="expCardId" class="reg-select" required>
                                        <option value="">카드 선택</option>
                                        <?php foreach ($cards as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['card_alias']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- 사용구분 -->
                                <div>
                                    <label class="reg-label">사용구분</label>
                                    <div class="zm-radio-group min-h-[44px]">
                                        <label class="cursor-pointer"><input type="radio" name="expUsageType" value="법인" checked class="sr-only peer"><span class="zm-radio">법인</span></label>
                                        <label class="cursor-pointer"><input type="radio" name="expUsageType" value="개인" class="sr-only peer"><span class="zm-radio">개인</span></label>
                                    </div>
                                </div>
                                <!-- 승인번호 -->
                                <div>
                                    <label class="reg-label">승인번호</label>
                                    <input type="text" id="expApprovalNumber" class="reg-input" placeholder="승인번호 입력">
                                </div>
                            </div>
                        </div>

                        <!-- Section: 사용정보 -->
                        <div>
                            <div class="form-section-title mb-3">사용정보</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-x-8">
                                <!-- 사용자 -->
                                <div>
                                    <label class="reg-label">사용자 <span class="text-amber-500">*</span></label>
                                    <select id="expUserName" class="reg-select" required>
                                        <?php foreach ($employees as $emp): ?>
                                        <option value="<?= htmlspecialchars($emp['name']) ?>" <?= $emp['name'] === $currentUserName ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($emp['name']) ?><?= $emp['dept_name'] ? ' (' . htmlspecialchars($emp['dept_name']) . ')' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- 등록자 -->
                                <div>
                                    <label class="reg-label">등록자</label>
                                    <select id="expRegistrant" class="reg-select" disabled>
                                        <?php foreach ($employees as $emp): ?>
                                        <option value="<?= htmlspecialchars($emp['name']) ?>" <?= $emp['name'] === $currentUserName ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($emp['name']) ?><?= $emp['dept_name'] ? ' (' . htmlspecialchars($emp['dept_name']) . ')' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- 사업선택 -->
                                <div>
                                    <label class="reg-label">사업선택</label>
                                    <div class="zm-radio-group mb-2">
                                        <label class="cursor-pointer"><input type="radio" name="expBusinessType" value="none" checked onchange="toggleBusinessField()" class="sr-only peer"><span class="zm-radio">해당없음</span></label>
                                        <label class="cursor-pointer"><input type="radio" name="expBusinessType" value="select" onchange="toggleBusinessField()" class="sr-only peer"><span class="zm-radio">사업선택</span></label>
                                    </div>
                                    <input type="text" id="expBusinessName" class="reg-input" placeholder="사업명 입력" disabled>
                                </div>
                                <!-- 문서번호 -->
                                <div>
                                    <label class="reg-label">문서번호</label>
                                    <input type="text" id="expDocNumber" class="reg-input" placeholder="문서번호 입력">
                                </div>
                            </div>
                        </div>

                        <!-- Section: 상세내용 -->
                        <div>
                            <div class="form-section-title mb-3">상세내용</div>
                            <textarea id="expDescription" class="reg-input" rows="3" placeholder="지출 내용을 입력해주세요."></textarea>
                        </div>

                    </div>

                    <!-- 하단 액션 (모바일 sticky) -->
                    <div class="sticky bottom-0 bg-slate-900 border-t border-slate-800 px-4 md:px-6 py-4 flex items-center justify-end gap-2 z-10">
                        <button type="button" onclick="showView('list')" class="btn btn-secondary">
                            취소
                        </button>
                        <button type="button" id="btnDeleteExpense" onclick="deleteExpense()" class="hidden px-5 py-2.5 text-sm font-medium rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 transition-colors">
                            <i data-lucide="trash-2" class="mr-1 w-4 h-4"></i> 삭제
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-medium rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors">
                            <i data-lucide="check" class="mr-1 w-4 h-4"></i> <span id="btnSubmitText">등록하기</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 상세보기 모달 -->
        <div id="detailModal" class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center" onclick="if(event.target===this)closeDetailModal()">
            <div class="bg-slate-900 rounded-xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                    <h3 class="text-lg font-bold text-slate-100">지출내역 상세</h3>
                    <button id="btnCloseModalX" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-300 hover:bg-slate-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="p-6" id="detailContent"></div>
                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-800">
                    <button id="btnCloseModal" class="btn btn-secondary">닫기</button>
                    <button id="btnEditFromDetail" class="btn btn-secondary">수정</button>
                    <a id="linkApprovalDoc" href="#" class="hidden btn btn-secondary">
                        <i data-lucide="file-text" class="inline w-4 h-4 -mt-0.5"></i> 결재 문서 보기
                    </a>
                    <button id="btnSubmitApproval" onclick="submitApproval()" class="hidden px-4 py-2 text-sm font-medium rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors">
                        <i data-lucide="send" class="inline w-4 h-4 -mt-0.5"></i> 결재 상신
                    </button>
                </div>
            </div>
        </div>
<style>
.row-violation { background: #fef2f2; }
html[data-theme="dark"] .row-violation { background: rgba(127, 29, 29, 0.2); }
.row-violation:hover { background: #fee2e2 !important; }
html[data-theme="dark"] .row-violation:hover { background: rgba(127, 29, 29, 0.35) !important; }
.status-settled { background: #ecfdf5; color: #059669; }
html[data-theme="dark"] .status-settled { background: rgba(6, 78, 59, 0.4); color: #34d399; }
.status-violation { background: #fef2f2; color: #dc2626; font-weight: 600; }
html[data-theme="dark"] .status-violation { background: rgba(127, 29, 29, 0.4); color: #f87171; }
.status-unsettled { background: #f1f5f9; color: #64748b; }
html[data-theme="dark"] .status-unsettled { background: rgba(30, 41, 59, 0.8); color: #94a3b8; }
</style>
<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const CURRENT_USER = <?= $currentUserNameJson ?>;
const EMPLOYEES = <?= $employeesJson ?>;
const SHOW_DEPARTMENT = <?= isOrgLevelEnabled('department') ? 'true' : 'false' ?>;
let allExpenses = [];
let filteredExpenses = [];
let currentPage = 1;
const perPage = 15;
let currentDetailId = null;
let dashboardFilter = '';

const SAMPLE_EXPENSES = [
    {id:1, card_alias:'영업팀 공용카드', card_number:'****-5678', usage_date:'2026-06-18', merchant:'스타벅스 강남점', amount:32000, category:'회의비', affiliation:'영업본부', department:'국내영업팀', user_name:'서영업', usage_type:'법인', is_settled:0, compliance_status:'준수', memo:'거래처 미팅'},
    {id:2, card_alias:'개발팀 카드', card_number:'****-9012', usage_date:'2026-06-17', merchant:'YES24', amount:52800, category:'도서구입비', affiliation:'기술개발본부', department:'개발1팀', user_name:'강개발', usage_type:'법인', is_settled:0, compliance_status:'준수', memo:'기술서적'},
    {id:3, card_alias:'김대표 법인카드', card_number:'****-1234', usage_date:'2026-06-16', merchant:'한솥도시락', amount:45000, category:'복리후생비', affiliation:'경영지원본부', department:'인사팀', user_name:'한인사', usage_type:'법인', is_settled:0, compliance_status:'준수', memo:'야근 석식'},
    {id:4, card_alias:'영업팀 공용카드', card_number:'****-5678', usage_date:'2026-06-15', merchant:'KTX 예매', amount:118000, category:'출장비', affiliation:'영업본부', department:'국내영업팀', user_name:'서영업', usage_type:'법인', is_settled:1, compliance_status:'준수', memo:'부산 출장'},
    {id:5, card_alias:'경영지원 카드', card_number:'****-3456', usage_date:'2026-06-14', merchant:'오피스디포', amount:124500, category:'소모품비', affiliation:'경영지원본부', department:'경영지원팀', user_name:'정지원', usage_type:'법인', is_settled:1, compliance_status:'준수', memo:'사무용품'},
    {id:6, card_alias:'개발팀 카드', card_number:'****-9012', usage_date:'2026-06-13', merchant:'AWS', amount:890000, category:'클라우드 비용', affiliation:'기술개발본부', department:'기술개발본부', user_name:'박기술', usage_type:'법인', is_settled:0, compliance_status:'미확인', memo:'6월 서버 비용'},
    {id:7, card_alias:'영업팀 공용카드', card_number:'****-5678', usage_date:'2026-06-12', merchant:'신라호텔', amount:285000, category:'접대비', affiliation:'영업본부', department:'해외영업팀', user_name:'류해외', usage_type:'법인', is_settled:0, compliance_status:'준수', memo:'거래처 접대'},
    {id:8, card_alias:'김대표 법인카드', card_number:'****-1234', usage_date:'2026-06-11', merchant:'GS칼텍스', amount:78000, category:'차량유지비', affiliation:'경영지원본부', department:'경영지원팀', user_name:'정지원', usage_type:'법인', is_settled:0, compliance_status:'미확인', memo:'업무용 차량 주유'},
];

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('expUsageDate').value = today;

    // URL 파라미터 처리
    const params = new URLSearchParams(location.search);
    dashboardFilter = params.get('filter') || '';

    if (HAS_DB) {
        loadExpenses();
    } else {
        allExpenses = SAMPLE_EXPENSES;
        if (dashboardFilter === 'violation') {
            allExpenses = allExpenses.filter(e => e.compliance_status === '미준수');
        }
        filteredExpenses = [...allExpenses];
        renderExpenses();
    }

    if (params.get('view') === 'register') showView('register');
    if (params.get('edit')) {
        loadExpenseForEdit(parseInt(params.get('edit')));
    }

    // 모달 버튼 이벤트
    document.getElementById('btnCloseModalX').addEventListener('click', closeDetailModal);
    document.getElementById('btnCloseModal').addEventListener('click', closeDetailModal);
    document.getElementById('btnEditFromDetail').addEventListener('click', editFromDetail);
});


// 뷰 전환
function showView(view) {
    const listView = document.getElementById('listView');
    const registerView = document.getElementById('registerView');
    const btnList = document.getElementById('btnList');
    const btnRegister = document.getElementById('btnRegister');

    if (view === 'list') {
        listView.classList.remove('hidden');
        registerView.classList.add('hidden');
        btnList.classList.add('zm-tab-active');
        btnRegister.classList.remove('zm-tab-active');
        resetForm();
    } else {
        listView.classList.add('hidden');
        registerView.classList.remove('hidden');
        btnList.classList.remove('zm-tab-active');
        btnRegister.classList.add('zm-tab-active');
    }
}

// 처리상태 헬퍼
function getStatusInfo(e) {
    if (Number(e.is_settled) === 1) return {label: '정산완료', cls: 'status-settled'};
    if (e.compliance_status === '미준수') return {label: '규정위반', cls: 'status-violation'};
    return {label: '미정산', cls: 'status-unsettled'};
}

// DB에서 지출내역 로드
function loadExpenses() {
    const params = new URLSearchParams();
    const card = document.getElementById('filterCard').value;
    const userName = document.getElementById('filterUser').value;
    const category = document.getElementById('filterCategory').value;
    const usageType = document.querySelector('input[name="filterUsageType"]:checked').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    if (card) params.set('card_alias', card);
    if (userName) params.set('user_name', userName);
    if (category) params.set('category', category);
    if (usageType) params.set('usage_type', usageType);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (dashboardFilter === 'violation') params.set('compliance_status', '미준수');

    fetch(`${API_BASE}?action=getExpenses&${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            allExpenses = data.expenses || [];
            filteredExpenses = [...allExpenses];
            currentPage = 1;
            renderExpenses();
        })
        .catch(() => renderExpenses());
}

// 필터 검색
function filterExpenses() {
    if (HAS_DB) { loadExpenses(); return; }
    renderExpenses(); // DB 없으면 빈 테이블
}

// 테이블 렌더
function renderExpenses() {
    const tbody = document.getElementById('expenseTableBody');
    const total = filteredExpenses.length;
    document.getElementById('totalCount').textContent = total;

    const start = (currentPage - 1) * perPage;
    const pageData = filteredExpenses.slice(start, start + perPage);

    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-slate-400 text-sm">지출내역이 없습니다.</td></tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = pageData.map(e => {
        const cardNum = e.card_number ? e.card_number.slice(-4) : '';
        const statusInfo = getStatusInfo(e);
        const violationRow = e.compliance_status === '미준수' ? ' row-violation' : '';
        return `
        <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer transition-colors${violationRow}" onclick="showDetail(${e.id})">
            <td class="px-4 py-3 text-sm text-slate-400 text-center whitespace-nowrap">${esc(e.usage_date)}</td>
            <td class="px-4 py-3 text-sm text-slate-200 font-medium text-center">${esc(e.card_alias)}</td>
            <td class="px-4 py-3 text-sm text-slate-500 text-center">${cardNum ? esc(cardNum) : '-'}</td>
            <td class="px-4 py-3 text-sm text-slate-200 text-center">${esc(e.user_name)}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.category)}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-block px-1.5 py-0.5 text-sm font-medium rounded whitespace-nowrap ${e.usage_type === '법인' ? 'bg-gray-50 text-gray-600' : 'bg-amber-50 text-amber-500'}">${esc(e.usage_type)}</span>
            </td>
            <td class="px-4 py-3 text-sm text-right font-semibold text-slate-100">${Number(e.amount).toLocaleString()}원</td>
            <td class="px-4 py-3 text-sm text-slate-400 text-center max-w-[200px] truncate">${esc(e.description || '-')}</td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
                <span class="inline-block px-2 py-0.5 text-sm font-medium rounded-full whitespace-nowrap ${statusInfo.cls}">${statusInfo.label}</span>
            </td>
        </tr>`;
    }).join('');

    renderPagination(total);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// 페이지네이션
function renderPagination(total) {
    const pages = Math.ceil(total / perPage);
    if (pages <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let html = '';
    html += `<button class="pg-btn ${currentPage === 1 ? 'pg-disabled' : ''}" onclick="goPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= pages; i++) {
        if (pages > 7 && i > 3 && i < pages - 2 && Math.abs(i - currentPage) > 1) {
            if (i === 4 || i === pages - 3) html += '<span class="px-1 text-slate-400">...</span>';
            continue;
        }
        html += `<button class="pg-btn ${i === currentPage ? 'pg-active' : ''}" onclick="goPage(${i})">${i}</button>`;
    }
    html += `<button class="pg-btn ${currentPage === pages ? 'pg-disabled' : ''}" onclick="goPage(${currentPage + 1})" ${currentPage === pages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    document.getElementById('pagination').innerHTML = html;
}

function goPage(p) {
    const pages = Math.ceil(filteredExpenses.length / perPage);
    if (p < 1 || p > pages) return;
    currentPage = p;
    renderExpenses();
}

// 날짜 빠른선택
function setDateRange(range) {
    const today = new Date();
    let from = new Date();
    switch (range) {
        case 'today': break;
        case 'week': from.setDate(today.getDate() - today.getDay()); break;
        case 'month': from.setDate(1); break;
        case '3month': from.setMonth(today.getMonth() - 3); break;
    }
    document.getElementById('filterDateFrom').value = from.toISOString().split('T')[0];
    document.getElementById('filterDateTo').value = today.toISOString().split('T')[0];
    // 클릭한 칩 활성화
    document.querySelectorAll('.date-chip').forEach(btn => {
        btn.classList.remove('zm-tab-active');
    });
    const active = document.querySelector(`.date-chip[data-range="${range}"]`);
    if (active) active.classList.add('zm-tab-active');
    filterExpenses();
}

function clearDateChipActive() {
    document.querySelectorAll('.date-chip').forEach(btn => {
        btn.classList.remove('zm-tab-active');
    });
}

function updateUsageChipStyle() {
    document.querySelectorAll('.usage-chip').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        label.classList.toggle('zm-tab-active', radio.checked);
    });
}

function resetFilters() {
    dashboardFilter = '';
    document.getElementById('filterCard').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterCategory').value = '';
    document.querySelector('input[name="filterUsageType"][value=""]').checked = true;
    updateUsageChipStyle();
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    clearDateChipActive();
    setDateRange('month');
}

// 카테고리 탭 선택
function selectCategory(cat) {
    document.querySelectorAll('.card-cat-tab').forEach(t => t.classList.remove('active'));
    event.target.closest('.card-cat-tab').classList.add('active');
    document.getElementById('expenseSubCategory').value = cat;

    // 항목 자동 매핑
    const catMap = {'식사':'식대', '여비교통비':'교통비', '영업사업비':'접대비', '구입비':'소모품'};
    const sel = document.getElementById('expCategory');
    if (catMap[cat]) sel.value = catMap[cat];
}


// 사업 필드 토글
function toggleBusinessField() {
    const isNone = document.querySelector('input[name="expBusinessType"]:checked').value === 'none';
    const field = document.getElementById('expBusinessName');
    field.disabled = isNone;
    if (isNone) field.value = '';
}

// 사용일 빠른선택
function setExpDate(type) {
    const d = new Date();
    if (type === 'yesterday') d.setDate(d.getDate() - 1);
    if (type === '2days') d.setDate(d.getDate() - 2);
    document.getElementById('expUsageDate').value = d.toISOString().split('T')[0];
}

// 금액 포맷
function formatNumber(el) {
    let v = el.value.replace(/[^\d]/g, '');
    el.value = v ? Number(v).toLocaleString() : '';
}

// 폼 리셋
function resetForm() {
    document.getElementById('expenseId').value = '0';
    document.getElementById('expApprovalNumber').value = '';
    document.getElementById('expAmount').value = '';
    document.getElementById('expUsageDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('expCategory').value = '';
    document.getElementById('expCardId').value = '';
    document.getElementById('expDocNumber').value = '';
    document.getElementById('expBusinessName').value = '';
    document.getElementById('expDescription').value = '';
    document.getElementById('expUserName').value = CURRENT_USER;
    document.getElementById('expRegistrant').value = CURRENT_USER;
    document.querySelector('input[name="expUsageType"][value="법인"]').checked = true;
    document.querySelector('input[name="expBusinessType"][value="none"]').checked = true;
    document.getElementById('expBusinessName').disabled = true;
    document.getElementById('btnDeleteExpense').classList.add('hidden');
    document.getElementById('registerTitle').textContent = '지출등록';
    document.getElementById('btnSubmitText').textContent = '등록하기';

    // 카테고리 탭 초기화
    document.querySelectorAll('.card-cat-tab').forEach((t, i) => {
        t.classList.toggle('active', i === 0);
    });
    document.getElementById('expenseSubCategory').value = '식사';
}

// 저장
function saveExpense(e) {
    e.preventDefault();
    const id = parseInt(document.getElementById('expenseId').value);
    const amount = parseInt(document.getElementById('expAmount').value.replace(/[^\d]/g, '')) || 0;
    const data = {
        id: id,
        card_id: parseInt(document.getElementById('expCardId').value),
        registrant_name: document.getElementById('expRegistrant').value,
        approval_number: document.getElementById('expApprovalNumber').value,
        usage_type: document.querySelector('input[name="expUsageType"]:checked').value,
        category: document.getElementById('expCategory').value,
        sub_category: document.getElementById('expenseSubCategory').value,
        amount: amount,
        description: document.getElementById('expDescription').value,
        business_name: document.getElementById('expBusinessName').value,
        document_number: document.getElementById('expDocNumber').value,
        user_name: document.getElementById('expUserName').value,
        usage_date: document.getElementById('expUsageDate').value,
    };

    if (!data.card_id) { alert('법인카드를 선택해주세요.'); return false; }
    if (!data.amount) { alert('지출비용을 입력해주세요.'); return false; }
    if (!data.category) { alert('항목을 선택해주세요.'); return false; }

    if (HAS_DB) {
        fetch(`${API_BASE}?action=saveExpense`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            alert(id > 0 ? '수정되었습니다.' : '등록되었습니다.');
            showView('list');
            loadExpenses();
        })
        .catch(() => alert('저장 중 오류가 발생했습니다.'));
    } else {
        alert('DB 연결이 필요합니다.');
    }
    return false;
}

// 수정 로드
function loadExpenseForEdit(id) {
    if (!HAS_DB) { alert('DB 연결이 필요합니다.'); return; }
    fetch(`${API_BASE}?action=getExpense&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.expense) populateForm(data.expense);
        });
}

function populateForm(exp) {
    showView('register');
    document.getElementById('expenseId').value = exp.id;
    document.getElementById('expApprovalNumber').value = exp.approval_number || '';
    document.getElementById('expAmount').value = Number(exp.amount).toLocaleString();
    document.getElementById('expUsageDate').value = exp.usage_date || '';
    document.getElementById('expCategory').value = exp.category || '';
    document.getElementById('expCardId').value = exp.card_id || '';
    document.getElementById('expDocNumber').value = exp.document_number || '';
    document.getElementById('expDescription').value = exp.description || '';
    document.getElementById('expRegistrant').value = exp.registrant_name || '';
    document.getElementById('expenseSubCategory').value = exp.sub_category || '';

    // 사용자
    document.getElementById('expUserName').value = exp.user_name || '';

    // 사용구분
    const utRadio = document.querySelector(`input[name="expUsageType"][value="${exp.usage_type}"]`);
    if (utRadio) utRadio.checked = true;

    // 사업
    if (exp.business_name) {
        document.querySelector('input[name="expBusinessType"][value="select"]').checked = true;
        document.getElementById('expBusinessName').disabled = false;
        document.getElementById('expBusinessName').value = exp.business_name;
    }

    // 카테고리 탭
    const subCat = exp.sub_category || '식사';
    document.querySelectorAll('.card-cat-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.cat === subCat);
    });

    document.getElementById('btnDeleteExpense').classList.remove('hidden');
    document.getElementById('registerTitle').textContent = '지출내역 수정';
    document.getElementById('btnSubmitText').textContent = '수정하기';
}

// 삭제
async function deleteExpense() {
    const id = parseInt(document.getElementById('expenseId').value);
    if (!id || !(await AppUI.confirm('이 지출내역을 삭제하시겠습니까?'))) return;

    if (!HAS_DB) { alert('DB 연결이 필요합니다.'); return; }
    fetch(`${API_BASE}?action=deleteExpense`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id})
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) { alert(res.error); return; }
        alert('삭제되었습니다.');
        showView('list');
        loadExpenses();
    })
    .catch(() => alert('삭제 중 오류가 발생했습니다.'));
}

// 상세 모달
function showDetail(id) {
    currentDetailId = id;
    const exp = filteredExpenses.find(e => e.id === id);
    if (!exp) return;

    const detailStatus = getStatusInfo(exp);
    document.getElementById('detailContent').innerHTML = `
        <!-- 핵심 정보 -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <p class="text-2xl font-bold text-slate-100">${Number(exp.amount).toLocaleString()}원</p>
                <p class="text-sm text-slate-400 mt-1">${esc(exp.usage_date)} · ${esc(exp.category)}
                    <span class="ml-1 inline-block px-1.5 py-0.5 text-sm font-medium rounded ${exp.usage_type === '법인' ? 'bg-gray-50 text-gray-600' : 'bg-amber-50 text-amber-500'}">${esc(exp.usage_type)}</span>
                </p>
            </div>
            <span class="inline-block px-3 py-1 text-sm font-medium rounded-full ${detailStatus.cls}">${detailStatus.label}</span>
        </div>
        <!-- 상세 항목 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><span class="text-sm text-slate-400">카드</span><p class="text-sm font-medium text-slate-100 mt-1">${esc(exp.card_alias)}${exp.card_number ? ' <span class="text-slate-500">·' + esc(exp.card_number.slice(-4)) + '</span>' : ''}</p></div>
            <div><span class="text-sm text-slate-400">사용자</span><p class="text-sm text-slate-200 mt-1">${esc(exp.user_name)}</p></div>
            <div><span class="text-sm text-slate-400">카드 책임자</span><p class="text-sm text-slate-200 mt-1">${esc(exp.card_manager)}</p></div>
            <div><span class="text-sm text-slate-400">소속${SHOW_DEPARTMENT ? '/<?= htmlspecialchars(getOrgLabel('department')) ?>' : ''}</span><p class="text-sm text-slate-200 mt-1">${esc(exp.affiliation || '-')}${SHOW_DEPARTMENT && exp.department ? ' / ' + esc(exp.department) : ''}</p></div>
            <div><span class="text-sm text-slate-400">등록자</span><p class="text-sm text-slate-200 mt-1">${esc(exp.registrant_name)}</p></div>
            <div><span class="text-sm text-slate-400">승인번호</span><p class="text-sm text-slate-200 mt-1">${esc(exp.approval_number || '-')}</p></div>
            <div><span class="text-sm text-slate-400">사업명</span><p class="text-sm text-slate-200 mt-1">${esc(exp.business_name || '-')}</p></div>
            <div><span class="text-sm text-slate-400">문서번호</span><p class="text-sm text-slate-200 mt-1">${esc(exp.document_number || '-')}</p></div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-800">
            <span class="text-sm text-slate-400">적요</span>
            <p class="text-sm text-slate-200 mt-1">${esc(exp.description || '-')}</p>
        </div>
    `;

    // 결재 상신/문서보기 버튼 상태 제어
    const btnSubmit = document.getElementById('btnSubmitApproval');
    const linkDoc   = document.getElementById('linkApprovalDoc');
    btnSubmit.classList.add('hidden');
    linkDoc.classList.add('hidden');

    if (exp.document_number && exp.approval_doc_id) {
        // 이미 상신됨 → 결재 문서 보기 링크
        linkDoc.href = `<?= $basePath ?>/pages/approval_view.php?id=${exp.approval_doc_id}`;
        linkDoc.classList.remove('hidden');
    } else if (Number(exp.is_settled) !== 1) {
        // 미상신 & 미정산 → 결재 상신 버튼
        btnSubmit.classList.remove('hidden');
    }

    document.getElementById('detailModal').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// 결재 상신 (Phase 1 워크플로우 통합)
async function submitApproval() {
    if (!currentDetailId) return;
    if (!(await AppUI.confirm('이 경비를 전자결재로 상신하시겠습니까?\n\n상신 후에는 결재자가 승인해야 정산이 완료됩니다.'))) return;

    fetch(`${API_BASE}?action=submitCardExpenseApproval`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ expense_id: currentDetailId }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) { alert(data.error || '상신에 실패했습니다.'); return; }
        alert(`결재 상신이 완료되었습니다.\n\n문서번호: ${data.doc_number}`);
        closeDetailModal();
        loadExpenses();
    })
    .catch(() => alert('네트워크 오류가 발생했습니다.'));
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    currentDetailId = null;
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !document.getElementById('detailModal').classList.contains('hidden')) {
        closeDetailModal();
    }
});

function editFromDetail() {
    const id = currentDetailId;
    closeDetailModal();
    if (id) loadExpenseForEdit(id);
}

// 엑셀 export (간이)
function exportExcel() {
    let csv = '\uFEFF사용일,카드명,카드번호,사용자,항목,구분,사용금액,적요,처리상태,책임자,소속' + (SHOW_DEPARTMENT ? '/<?= htmlspecialchars(getOrgLabel('department')) ?>' : '') + ',등록자,승인번호,사업명,문서번호\n';
    filteredExpenses.forEach(e => {
        const status = getStatusInfo(e).label;
        const orgText = (e.affiliation || '') + (SHOW_DEPARTMENT && e.department ? '/' + e.department : '');
        csv += `"${e.usage_date}","${e.card_alias}","${e.card_number || ''}","${e.user_name}","${e.category}","${e.usage_type}",${e.amount},"${e.description || ''}","${status}","${e.card_manager}","${orgText}","${e.registrant_name}","${e.approval_number || ''}","${e.business_name || ''}","${e.document_number || ''}"\n`;
    });
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '카드지출내역_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}

function esc(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>
