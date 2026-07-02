<?php
$pageTitle = '카드지출내역';
$currentPage = 'card';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$cards = [];
$expenses = [];
$editExpense = null;
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

if (!$hasDB) {
    $cards = [
        ['id' => 1, 'card_alias' => '영업팀 법인카드'],
        ['id' => 2, 'card_alias' => '개발팀 법인카드'],
        ['id' => 3, 'card_alias' => '경영지원 법인카드'],
        ['id' => 4, 'card_alias' => '대표이사 법인카드'],
    ];
    $expenses = [
        ['id'=>1,'card_alias'=>'영업팀 법인카드','registrant_name'=>'김영수','card_manager'=>'김영수','affiliation'=>'위즈웨어','department'=>'영업팀','usage_type'=>'법인','category'=>'식대','sub_category'=>'식사','amount'=>45000,'description'=>'거래처 미팅 식사','business_name'=>'A프로젝트','document_number'=>'DOC-2026-0101','user_name'=>'김영수','usage_date'=>'2026-02-20','approval_number'=>'AP-2026-001'],
        ['id'=>2,'card_alias'=>'영업팀 법인카드','registrant_name'=>'김영수','card_manager'=>'김영수','affiliation'=>'위즈웨어','department'=>'영업팀','usage_type'=>'법인','category'=>'교통비','sub_category'=>'여비교통비','amount'=>32000,'description'=>'거래처 방문 택시비','business_name'=>'A프로젝트','document_number'=>'DOC-2026-0102','user_name'=>'김영수','usage_date'=>'2026-02-21','approval_number'=>'AP-2026-002'],
        ['id'=>3,'card_alias'=>'개발팀 법인카드','registrant_name'=>'이정민','card_manager'=>'이정민','affiliation'=>'위즈웨어','department'=>'개발팀','usage_type'=>'법인','category'=>'소모품','sub_category'=>'구입비','amount'=>128000,'description'=>'개발용 장비 구입','business_name'=>'','document_number'=>'','user_name'=>'이정민','usage_date'=>'2026-02-19','approval_number'=>'AP-2026-003'],
        ['id'=>4,'card_alias'=>'경영지원 법인카드','registrant_name'=>'박지현','card_manager'=>'박지현','affiliation'=>'위즈웨어','department'=>'경영지원실','usage_type'=>'법인','category'=>'접대비','sub_category'=>'영업사업비','amount'=>85000,'description'=>'고객사 접대','business_name'=>'B프로젝트','document_number'=>'DOC-2026-0201','user_name'=>'박지현','usage_date'=>'2026-02-18','approval_number'=>'AP-2026-004'],
        ['id'=>5,'card_alias'=>'영업팀 법인카드','registrant_name'=>'홍길동','card_manager'=>'김영수','affiliation'=>'위즈웨어','department'=>'영업팀','usage_type'=>'개인','category'=>'교통비','sub_category'=>'여비교통비','amount'=>15000,'description'=>'외근 교통비','business_name'=>'','document_number'=>'','user_name'=>'홍길동','usage_date'=>'2026-02-22','approval_number'=>'AP-2026-005'],
        ['id'=>6,'card_alias'=>'개발팀 법인카드','registrant_name'=>'이정민','card_manager'=>'이정민','affiliation'=>'위즈웨어','department'=>'개발팀','usage_type'=>'법인','category'=>'식대','sub_category'=>'식사','amount'=>62000,'description'=>'팀 회식','business_name'=>'','document_number'=>'','user_name'=>'이정민','usage_date'=>'2026-02-17','approval_number'=>'AP-2026-006'],
        ['id'=>7,'card_alias'=>'대표이사 법인카드','registrant_name'=>'최민호','card_manager'=>'최민호','affiliation'=>'위즈웨어','department'=>'경영진','usage_type'=>'법인','category'=>'접대비','sub_category'=>'영업사업비','amount'=>250000,'description'=>'VIP 고객 미팅','business_name'=>'C프로젝트','document_number'=>'DOC-2026-0301','user_name'=>'최민호','usage_date'=>'2026-02-15','approval_number'=>'AP-2026-007'],
        ['id'=>8,'card_alias'=>'경영지원 법인카드','registrant_name'=>'박지현','card_manager'=>'박지현','affiliation'=>'위즈웨어','department'=>'경영지원실','usage_type'=>'법인','category'=>'기타','sub_category'=>'구입비','amount'=>35000,'description'=>'사무용품 구입','business_name'=>'','document_number'=>'','user_name'=>'박지현','usage_date'=>'2026-02-16','approval_number'=>'AP-2026-008'],
        ['id'=>9,'card_alias'=>'영업팀 법인카드','registrant_name'=>'김영수','card_manager'=>'김영수','affiliation'=>'위즈웨어','department'=>'영업팀','usage_type'=>'법인','category'=>'식대','sub_category'=>'식사','amount'=>55000,'description'=>'프로젝트 킥오프 식사','business_name'=>'D프로젝트','document_number'=>'DOC-2026-0401','user_name'=>'김영수','usage_date'=>'2026-02-23','approval_number'=>'AP-2026-009'],
        ['id'=>10,'card_alias'=>'개발팀 법인카드','registrant_name'=>'서지우','card_manager'=>'이정민','affiliation'=>'위즈웨어','department'=>'개발팀','usage_type'=>'개인','category'=>'교통비','sub_category'=>'여비교통비','amount'=>28000,'description'=>'출장 교통비','business_name'=>'A프로젝트','document_number'=>'','user_name'=>'서지우','usage_date'=>'2026-02-24','approval_number'=>'AP-2026-010'],
    ];
}

// 수정 모드인 경우
$editId = (int)($_GET['edit'] ?? 0);
$viewMode = $_GET['view'] ?? 'list'; // list or register
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300 min-h-screen bg-slate-950">
    <div class="p-4 md:p-6">
        <!-- 헤더 -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-100">카드지출내역</h2>
                <p class="text-sm text-slate-300 mt-1">법인카드 지출내역을 조회하고 등록할 수 있습니다.</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <button onclick="showView('list')" id="btnList" class="card-view-btn card-view-btn-active">
                    <i data-lucide="list" class="mr-1 w-4 h-4"></i> 지출내역
                </button>
                <button onclick="showView('register')" id="btnRegister" class="card-view-btn card-view-btn-inactive">
                    <i data-lucide="plus" class="mr-1 w-4 h-4"></i> 지출등록
                </button>
            </div>
        </div>

        <!-- ===== 지출내역 리스트 뷰 ===== -->
        <div id="listView">
            <!-- 검색 필터 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4 md:p-5 mb-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-slate-200 w-20 shrink-0">카드별칭</label>
                        <select id="filterCard" class="reg-select" onchange="filterExpenses()">
                            <option value="">전체</option>
                            <?php foreach ($cards as $c): ?>
                            <option value="<?= htmlspecialchars($c['card_alias']) ?>"><?= htmlspecialchars($c['card_alias']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-slate-200 w-20 shrink-0">책임자</label>
                        <input type="text" id="filterManager" class="reg-input" placeholder="책임자 검색" oninput="filterExpenses()">
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-slate-200 w-20 shrink-0">소속<?= isOrgLevelEnabled('department') ? '/' . htmlspecialchars(getOrgLabel('department')) : '' ?></label>
                        <input type="text" id="filterDept" class="reg-input" placeholder="<?= isOrgLevelEnabled('department') ? '소속 또는 ' . htmlspecialchars(getOrgLabel('department')) . ' 검색' : '소속 검색' ?>" oninput="filterExpenses()">
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-slate-200 w-20 shrink-0">승인번호</label>
                        <input type="text" id="filterApproval" class="reg-input" placeholder="승인번호 검색" oninput="filterExpenses()">
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-slate-200 w-20 shrink-0">사용구분</label>
                        <input type="hidden" id="filterUsageType" value="">
                        <div class="usage-segment-group" role="group" aria-label="사용구분 필터">
                            <button type="button" class="usage-segment active" data-value="" onclick="setFilterUsageType(this)">전체</button>
                            <button type="button" class="usage-segment" data-value="법인" onclick="setFilterUsageType(this)">법인</button>
                            <button type="button" class="usage-segment" data-value="개인" onclick="setFilterUsageType(this)">개인</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-slate-200 w-20 shrink-0">사용일</label>
                        <div class="flex items-center gap-2 flex-1">
                            <input type="date" id="filterDateFrom" class="reg-input" onchange="filterExpenses()">
                            <span class="text-slate-500">~</span>
                            <input type="date" id="filterDateTo" class="reg-input" onchange="filterExpenses()">
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-4">
                    <label class="text-sm font-medium text-slate-400">빠른선택:</label>
                    <button onclick="setDateRange('today')" class="btn btn-secondary btn-xs rounded-full">오늘</button>
                    <button onclick="setDateRange('week')" class="btn btn-secondary btn-xs rounded-full">이번주</button>
                    <button onclick="setDateRange('month')" class="btn btn-secondary btn-xs rounded-full">이번달</button>
                    <button onclick="setDateRange('3month')" class="btn btn-secondary btn-xs rounded-full">3개월</button>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button onclick="resetFilters()" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
                    <button onclick="filterExpenses()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">검색 <i data-lucide="search" class="w-4 h-4"></i></button>
                </div>
            </div>

            <!-- 리스트 테이블 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
                    <span class="text-sm text-slate-400">총 <strong id="totalCount" class="text-gray-700">0</strong>건</span>
                    <button onclick="exportExcel()" class="btn btn-secondary btn-sm">
                        <i data-lucide="file-spreadsheet" class="mr-1 text-amber-700 w-4 h-4"></i> 엑셀 다운로드
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full emp-table">
                        <thead>
                            <tr class="border-b border-slate-800">
                                <th class="px-4 py-3 text-center">카드별칭</th>
                                <th class="px-4 py-3 text-center">등록자</th>
                                <th class="px-4 py-3 text-center">책임자</th>
                                <th class="px-4 py-3 text-center">소속<?= isOrgLevelEnabled('department') ? '/' . htmlspecialchars(getOrgLabel('department')) : '' ?></th>
                                <th class="px-4 py-3 text-center">사용구분</th>
                                <th class="px-4 py-3 text-center">항목</th>
                                <th class="px-4 py-3 text-right">사용금액</th>
                                <th class="px-4 py-3 text-left">적요</th>
                                <th class="px-4 py-3 text-center">사업명</th>
                                <th class="px-4 py-3 text-center">문서번호</th>
                                <th class="px-4 py-3 text-center">사용자</th>
                                <th class="px-4 py-3 text-center">사용일</th>
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
                                <button type="button" onclick="selectCategory('식사')" class="card-cat-tab active px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="utensils" class="w-4 h-4"></i> 식사
                                </button>
                                <button type="button" onclick="selectCategory('여비교통비')" class="card-cat-tab px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="bus" class="w-4 h-4"></i> 여비교통비
                                </button>
                                <button type="button" onclick="selectCategory('영업사업비')" class="card-cat-tab px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="handshake" class="w-4 h-4"></i> 영업사업비
                                </button>
                                <button type="button" onclick="selectCategory('구입비')" class="card-cat-tab px-4 py-2.5 text-sm font-medium rounded-lg border transition-colors whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
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
                                        <button type="button" onclick="setExpDate('today')" class="btn btn-secondary btn-sm rounded-full">오늘</button>
                                        <button type="button" onclick="setExpDate('yesterday')" class="btn btn-secondary btn-sm rounded-full">어제</button>
                                        <button type="button" onclick="setExpDate('2days')" class="btn btn-secondary btn-sm rounded-full">2일전</button>
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
                                    <div class="zm-radio-group mb-2">
                                        <label class="cursor-pointer"><input type="radio" name="expUserType" value="self" checked onchange="toggleUserField()" class="sr-only peer"><span class="zm-radio">본인</span></label>
                                        <label class="cursor-pointer"><input type="radio" name="expUserType" value="other" onchange="toggleUserField()" class="sr-only peer"><span class="zm-radio">타인</span></label>
                                    </div>
                                    <input type="text" id="expUserName" class="reg-input" value="김영수" placeholder="사용자명" disabled>
                                </div>
                                <!-- 등록자 -->
                                <div>
                                    <label class="reg-label">등록자</label>
                                    <input type="text" id="expRegistrant" class="reg-input bg-slate-950" value="김영수" readonly>
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
                    <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-200 p-2 -mr-2 rounded-lg hover:bg-slate-800">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6" id="detailContent"></div>
                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-800">
                    <button onclick="closeDetailModal()" class="btn btn-secondary">닫기</button>
                    <button onclick="editFromDetail()" class="px-4 py-2 text-sm rounded-lg bg-primary text-white hover:bg-primary-dark">
                        <i data-lucide="pencil" class="mr-1 w-4 h-4"></i> 수정
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 40px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 700;
    border-radius: 10px;
    border: 1px solid transparent;
    transition: background-color .15s, color .15s, border-color .15s, box-shadow .15s;
}
.card-view-btn svg,
.card-view-btn i { width: 16px; height: 16px; }
.card-view-btn-active {
    color: #fff !important;
    background: var(--zm-primary) !important;
    border-color: var(--zm-primary) !important;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.10);
}
.card-view-btn-active:hover {
    background: #3f56e8 !important;
    border-color: #3f56e8 !important;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.12);
}
.card-view-btn-inactive {
    color: #334155 !important;
    background: #fff !important;
    border-color: #cbd5e1 !important;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
}
.card-view-btn-inactive:hover {
    color: #0f172a !important;
    background: #f1f5f9 !important;
    border-color: #94a3b8 !important;
}
html:not([data-theme="light"]) .card-view-btn-inactive {
    color: #cbd5e1 !important;
    background: #0f172a !important;
    border-color: #334155 !important;
}
html:not([data-theme="light"]) .card-view-btn-inactive:hover {
    color: #fff !important;
    background: #1e293b !important;
    border-color: #475569 !important;
}
.usage-segment-group {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    background: #f3f4f6;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, .04);
}
.usage-segment {
    min-width: 62px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 16px;
    border: 0;
    border-radius: 14px;
    background: transparent;
    color: #6b7280;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color .15s, color .15s, box-shadow .15s;
}
.usage-segment:hover {
    background: rgba(255, 255, 255, .8);
    color: #374151;
}
.usage-segment.active {
    color: #ffffff;
    background: var(--zm-primary, #4F6AFF);
    box-shadow: 0 2px 6px rgba(79, 106, 255, 0.25);
}
html:not([data-theme="light"]) .usage-segment-group {
    background: #0f172a;
    border-color: #334155;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, .24);
}
html:not([data-theme="light"]) .usage-segment {
    color: #cbd5e1;
}
html:not([data-theme="light"]) .usage-segment:hover {
    background: rgba(30, 41, 59, .85);
    color: #ffffff;
}
html:not([data-theme="light"]) .usage-segment.active {
    color: #ffffff;
    background: var(--zm-primary, #4F6AFF);
    box-shadow: 0 2px 6px rgba(79, 106, 255, 0.35);
}
</style>

<script>
const API_BASE = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const SHOW_DEPARTMENT = <?= isOrgLevelEnabled('department') ? 'true' : 'false' ?>;
let allExpenses = <?= json_encode($expenses, JSON_UNESCAPED_UNICODE) ?>;
let filteredExpenses = [...allExpenses];
let currentPage = 1;
const perPage = 15;
let currentDetailId = null;

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('expUsageDate').value = today;

    if (HAS_DB) {
        loadExpenses();
    } else {
        renderExpenses();
    }

    // URL 파라미터 처리
    const params = new URLSearchParams(location.search);
    if (params.get('view') === 'register') showView('register');
    if (params.get('edit')) {
        loadExpenseForEdit(parseInt(params.get('edit')));
    }
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
        btnList.className = 'card-view-btn card-view-btn-active';
        btnRegister.className = 'card-view-btn card-view-btn-inactive';
        resetForm();
    } else {
        listView.classList.add('hidden');
        registerView.classList.remove('hidden');
        btnList.className = 'card-view-btn card-view-btn-inactive';
        btnRegister.className = 'card-view-btn card-view-btn-active';
    }
}

function setFilterUsageType(button) {
    const group = button.closest('.usage-segment-group');
    document.getElementById('filterUsageType').value = button.dataset.value || '';
    if (group) {
        group.querySelectorAll('.usage-segment').forEach(btn => {
            btn.classList.toggle('active', btn === button);
        });
    }
    filterExpenses();
}

// DB에서 지출내역 로드
function loadExpenses() {
    const params = new URLSearchParams();
    const card = document.getElementById('filterCard').value;
    const manager = document.getElementById('filterManager').value;
    const dept = document.getElementById('filterDept').value;
    const approval = document.getElementById('filterApproval').value;
    const usageType = document.getElementById('filterUsageType').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    if (card) params.set('card_alias', card);
    if (manager) params.set('manager', manager);
    if (dept) params.set('department', dept);
    if (approval) params.set('approval_number', approval);
    if (usageType) params.set('usage_type', usageType);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

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

// 클라이언트 필터 (fallback)
function filterExpenses() {
    if (HAS_DB) { loadExpenses(); return; }

    const card = document.getElementById('filterCard').value;
    const manager = document.getElementById('filterManager').value.toLowerCase();
    const dept = document.getElementById('filterDept').value.toLowerCase();
    const approval = document.getElementById('filterApproval').value.toLowerCase();
    const usageType = document.getElementById('filterUsageType').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    filteredExpenses = allExpenses.filter(e => {
        if (card && e.card_alias !== card) return false;
        if (manager && !(e.card_manager || '').toLowerCase().includes(manager)) return false;
        const deptHay = SHOW_DEPARTMENT ? (e.affiliation || '') + (e.department || '') : (e.affiliation || '');
        if (dept && !deptHay.toLowerCase().includes(dept)) return false;
        if (approval && !(e.approval_number || '').toLowerCase().includes(approval)) return false;
        if (usageType && e.usage_type !== usageType) return false;
        if (dateFrom && e.usage_date < dateFrom) return false;
        if (dateTo && e.usage_date > dateTo) return false;
        return true;
    });
    currentPage = 1;
    renderExpenses();
}

// 테이블 렌더
function renderExpenses() {
    const tbody = document.getElementById('expenseTableBody');
    const total = filteredExpenses.length;
    document.getElementById('totalCount').textContent = total;

    const start = (currentPage - 1) * perPage;
    const pageData = filteredExpenses.slice(start, start + perPage);

    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center py-10 text-slate-400 text-sm">지출내역이 없습니다.</td></tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = pageData.map(e => `
        <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer transition-colors" onclick="showDetail(${e.id})">
            <td class="px-4 py-3 text-sm text-slate-200 font-medium text-center">${esc(e.card_alias)}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.registrant_name)}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.card_manager)}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.affiliation || '-')}${SHOW_DEPARTMENT && e.department ? '/' + esc(e.department) : ''}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-block px-2 py-0.5 text-sm font-medium rounded-full ${e.usage_type === '법인' ? 'bg-gray-100 text-gray-700' : 'bg-amber-50 text-amber-700'}">${esc(e.usage_type)}</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.category)}</td>
            <td class="px-4 py-3 text-sm text-right font-medium text-slate-100">${Number(e.amount).toLocaleString()}원</td>
            <td class="px-4 py-3 text-sm text-slate-300 max-w-[180px] truncate">${esc(e.description)}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.business_name || '-')}</td>
            <td class="px-4 py-3 text-sm text-slate-400 text-center">${esc(e.document_number || '-')}</td>
            <td class="px-4 py-3 text-sm text-slate-300 text-center">${esc(e.user_name)}</td>
            <td class="px-4 py-3 text-sm text-slate-400 text-center">${esc(e.usage_date)}</td>
        </tr>
    `).join('');

    renderPagination(total);
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
    filterExpenses();
}

function resetFilters() {
    document.getElementById('filterCard').value = '';
    document.getElementById('filterManager').value = '';
    document.getElementById('filterDept').value = '';
    document.getElementById('filterApproval').value = '';
    document.getElementById('filterUsageType').value = '';
    document.querySelectorAll('.usage-segment-group .usage-segment').forEach(btn => {
        btn.classList.toggle('active', (btn.dataset.value || '') === '');
    });
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    filterExpenses();
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

// 사용자 필드 토글
function toggleUserField() {
    const isSelf = document.querySelector('input[name="expUserType"]:checked').value === 'self';
    const field = document.getElementById('expUserName');
    field.disabled = isSelf;
    if (isSelf) field.value = '김영수';
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
    document.getElementById('expUserName').value = '김영수';
    document.querySelector('input[name="expUserType"][value="self"]').checked = true;
    document.querySelector('input[name="expUsageType"][value="법인"]').checked = true;
    document.querySelector('input[name="expBusinessType"][value="none"]').checked = true;
    document.getElementById('expUserName').disabled = true;
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
        // fallback: 클라이언트 추가
        const cardInfo = <?= json_encode($cards, JSON_UNESCAPED_UNICODE) ?>.find(c => c.id == data.card_id);
        if (id > 0) {
            const idx = allExpenses.findIndex(e => e.id === id);
            if (idx >= 0) Object.assign(allExpenses[idx], data, {card_alias: cardInfo?.card_alias || ''});
        } else {
            data.id = allExpenses.length + 1;
            data.card_alias = cardInfo?.card_alias || '';
            data.card_manager = data.registrant_name;
            data.affiliation = '위즈웨어';
            data.department = '';
            allExpenses.unshift(data);
        }
        alert(id > 0 ? '수정되었습니다.' : '등록되었습니다.');
        showView('list');
        filteredExpenses = [...allExpenses];
        renderExpenses();
    }
    return false;
}

// 수정 로드
function loadExpenseForEdit(id) {
    if (HAS_DB) {
        fetch(`${API_BASE}?action=getExpense&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.expense) populateForm(data.expense);
            });
    } else {
        const exp = allExpenses.find(e => e.id === id);
        if (exp) populateForm(exp);
    }
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
    if (exp.user_name && exp.user_name !== '김영수') {
        document.querySelector('input[name="expUserType"][value="other"]').checked = true;
        document.getElementById('expUserName').disabled = false;
    }
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
    const catMap = {'식사':'식사', '여비교통비':'여비교통비', '영업사업비':'영업사업비', '구입비':'구입비'};
    const subCat = exp.sub_category || '식사';
    document.querySelectorAll('.card-cat-tab').forEach(t => {
        const txt = t.textContent.trim();
        t.classList.toggle('active', txt.includes(subCat));
    });

    document.getElementById('btnDeleteExpense').classList.remove('hidden');
    document.getElementById('registerTitle').textContent = '지출내역 수정';
    document.getElementById('btnSubmitText').textContent = '수정하기';
}

// 삭제
async function deleteExpense() {
    const id = parseInt(document.getElementById('expenseId').value);
    if (!id || !(await AppUI.confirm('이 지출내역을 삭제하시겠습니까?'))) return;

    if (HAS_DB) {
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
        });
    } else {
        allExpenses = allExpenses.filter(e => e.id !== id);
        filteredExpenses = [...allExpenses];
        alert('삭제되었습니다.');
        showView('list');
        renderExpenses();
    }
}

// 상세 모달
function showDetail(id) {
    currentDetailId = id;
    const exp = (HAS_DB ? filteredExpenses : allExpenses).find(e => e.id === id) || filteredExpenses.find(e => e.id === id);
    if (!exp) return;

    document.getElementById('detailContent').innerHTML = `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><span class="text-sm text-slate-400">카드별칭</span><p class="text-sm font-medium text-slate-100 mt-1">${esc(exp.card_alias)}</p></div>
            <div><span class="text-sm text-slate-400">승인번호</span><p class="text-sm font-medium text-slate-100 mt-1">${esc(exp.approval_number || '-')}</p></div>
            <div><span class="text-sm text-slate-400">등록자</span><p class="text-sm text-slate-200 mt-1">${esc(exp.registrant_name)}</p></div>
            <div><span class="text-sm text-slate-400">사용자</span><p class="text-sm text-slate-200 mt-1">${esc(exp.user_name)}</p></div>
            <div><span class="text-sm text-slate-400">책임자</span><p class="text-sm text-slate-200 mt-1">${esc(exp.card_manager)}</p></div>
            <div><span class="text-sm text-slate-400">소속${SHOW_DEPARTMENT ? '/<?= htmlspecialchars(getOrgLabel('department')) ?>' : ''}</span><p class="text-sm text-slate-200 mt-1">${esc(exp.affiliation || '-')}${SHOW_DEPARTMENT && exp.department ? '/' + esc(exp.department) : ''}</p></div>
            <div><span class="text-sm text-slate-400">사용구분</span><p class="text-sm mt-1"><span class="inline-block px-2 py-0.5 text-sm font-medium rounded-full ${exp.usage_type === '법인' ? 'bg-gray-100 text-gray-700' : 'bg-amber-50 text-amber-700'}">${esc(exp.usage_type)}</span></p></div>
            <div><span class="text-sm text-slate-400">항목</span><p class="text-sm text-slate-200 mt-1">${esc(exp.category)}</p></div>
            <div><span class="text-sm text-slate-400">사용금액</span><p class="text-sm font-bold text-slate-100 mt-1">${Number(exp.amount).toLocaleString()}원</p></div>
            <div><span class="text-sm text-slate-400">사용일</span><p class="text-sm text-slate-200 mt-1">${esc(exp.usage_date)}</p></div>
            <div><span class="text-sm text-slate-400">사업명</span><p class="text-sm text-slate-200 mt-1">${esc(exp.business_name || '-')}</p></div>
            <div><span class="text-sm text-slate-400">문서번호</span><p class="text-sm text-slate-200 mt-1">${esc(exp.document_number || '-')}</p></div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-800">
            <span class="text-sm text-slate-400">적요</span>
            <p class="text-sm text-slate-200 mt-1">${esc(exp.description || '-')}</p>
        </div>
    `;
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    currentDetailId = null;
}

function editFromDetail() {
    const id = currentDetailId;
    closeDetailModal();
    if (id) loadExpenseForEdit(id);
}

// 엑셀 export (간이)
function exportExcel() {
    let csv = '\uFEFF카드별칭,등록자,책임자,소속' + (SHOW_DEPARTMENT ? '/<?= htmlspecialchars(getOrgLabel('department')) ?>' : '') + ',사용구분,항목,사용금액,적요,사업명,문서번호,사용자,사용일\n';
    filteredExpenses.forEach(e => {
        const orgText = (e.affiliation || '') + (SHOW_DEPARTMENT && e.department ? '/' + e.department : '');
        csv += `"${e.card_alias}","${e.registrant_name}","${e.card_manager}","${orgText}","${e.usage_type}","${e.category}",${e.amount},"${e.description}","${e.business_name || ''}","${e.document_number || ''}","${e.user_name}","${e.usage_date}"\n`;
    });
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '카드지출내역_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}

function esc(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
