<?php
$pageTitle = '지출 등록';
$currentPage = 'card';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$cards = [];
$employees = [];
$regulations = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM cards LIMIT 1');
        $hasDB = true;
        $cards = $pdo->query('SELECT id, card_alias FROM cards WHERE is_active = 1 ORDER BY card_alias')->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $hasDB = false; }

    try {
        $employees = $pdo->query("
            SELECT e.id, e.name, e.position,
                   COALESCE(d.name, '') AS dept_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.is_active = 1
            ORDER BY d.sort_order, e.name
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $employees = [];
    }

    try {
        $regulations = $pdo->query('SELECT category, sub_category, limit_amount, guide FROM card_regulations WHERE is_active = 1 AND use_in_register = 1 ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $regulations = [];
    }
}

$projects = [];
if ($pdo) {
    try {
        $projects = $pdo->query("SELECT id, name, client, status FROM biz_projects WHERE status IN ('진행중','완료') ORDER BY FIELD(status,'진행중','완료'), name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $projects = [];
    }
}
if (empty($projects)) {
    $projects = [
        ['id' => 8, 'name' => 'Zaemit 기업관리 패키지 개발', 'client' => '(주)위즈웨어', 'status' => '진행중'],
        ['id' => 7, 'name' => '스마트 물류 관리 시스템', 'client' => '(주)한국물류', 'status' => '진행중'],
        ['id' => 6, 'name' => '기업 ERP 커스터마이징', 'client' => '(주)대한제조', 'status' => '진행중'],
        ['id' => 5, 'name' => '모바일 앱 리뉴얼', 'client' => '(주)모바일코리아', 'status' => '완료'],
        ['id' => 4, 'name' => '클라우드 인프라 구축', 'client' => '(주)테크솔루션', 'status' => '완료'],
        ['id' => 3, 'name' => '고객관리 CRM 도입', 'client' => '(주)영업파트너', 'status' => '완료'],
        ['id' => 2, 'name' => '사내 그룹웨어 구축', 'client' => '자체', 'status' => '완료'],
        ['id' => 1, 'name' => '웹사이트 리뉴얼', 'client' => '(주)디지털미디어', 'status' => '완료'],
    ];
}

if (!$hasDB || empty($cards)) {
    $cards = [
        ['id' => 1, 'card_alias' => '영업팀 법인카드'],
        ['id' => 2, 'card_alias' => '개발팀 법인카드'],
        ['id' => 3, 'card_alias' => '경영지원 법인카드'],
        ['id' => 4, 'card_alias' => '대표이사 법인카드'],
    ];
}
if (empty($employees)) {
    $employees = [
        ['id'=>1,'name'=>'송승환','position'=>'이사','dept_name'=>'기획'],
        ['id'=>2,'name'=>'곽호석','position'=>'과장','dept_name'=>'기획'],
        ['id'=>3,'name'=>'서수현','position'=>'과장','dept_name'=>'기획'],
        ['id'=>4,'name'=>'조재웅','position'=>'과장','dept_name'=>'기획'],
        ['id'=>5,'name'=>'위진희','position'=>'대리','dept_name'=>'기획'],
        ['id'=>6,'name'=>'이건','position'=>'사원','dept_name'=>'기획'],
        ['id'=>7,'name'=>'변지원','position'=>'인턴','dept_name'=>'기획'],
        ['id'=>8,'name'=>'한승엽','position'=>'인턴','dept_name'=>'기획'],
    ];
}

$basePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
$loggedInEmpId = $_SESSION['user']['id'] ?? 0;
$loggedInEmpName = $_SESSION['user']['name'] ?? '';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300 min-h-screen bg-slate-950">
    <!-- 상단 헤더바 -->
    <div class="sticky top-14 z-30 bg-slate-900 border-b border-slate-800 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= $basePath ?>/pages/card_expenses.php" class="text-slate-400 hover:text-slate-200 transition-colors">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </a>
            <h1 class="text-base font-bold text-slate-100">지출 등록</h1>
        </div>
        <button type="button" class="btn btn-secondary">
            <i data-lucide="list-plus" class="w-4 h-4"></i> 일괄등록
        </button>
    </div>

    <div class="max-w-4xl mx-auto py-8 px-4">
        <form id="expenseForm" onsubmit="return submitExpense(event)">

        <!-- 카테고리 선택 카드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 mb-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-slate-100">지출 카테고리 선택 <span class="text-red-500 text-sm font-normal">*</span></h2>
                <a href="<?= $basePath ?>/pages/card_regulations.php" class="text-sm text-gray-600 hover:underline">법인카드규정보기 &gt;</a>
            </div>
            <!-- 대분류 탭 -->
            <div class="flex justify-center">
                <div class="inline-flex flex-wrap justify-center bg-slate-800/60 rounded-xl p-1 gap-1" id="catTabs"></div>
            </div>
        </div>

        <!-- 세분류 + 규정 + 폼 카드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <!-- 세분류 탭 -->
            <div class="px-6 pt-5 pb-0 flex justify-center">
                <div class="inline-flex flex-wrap justify-center bg-slate-800/60 rounded-xl p-1 gap-1" id="subCatTabs"></div>
            </div>
            <!-- 규정 텍스트 -->
            <div id="ruleText" class="mx-6 mt-3 mb-1 hidden"></div>

            <!-- 폼 필드들 -->
            <div class="px-6 pb-6 pt-2 space-y-0 divide-y divide-slate-800">
                <!-- 승인번호 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">승인번호</label>
                    <div class="flex-1 flex gap-2">
                        <input type="text" id="fApproval" class="flex-1 px-3 py-2.5 text-sm reg-input" placeholder="승인번호를 입력해주세요">
                        <button type="button" onclick="document.getElementById('fApproval').value=''" class="btn btn-secondary shrink-0">삭제</button>
                    </div>
                </div>

                <!-- 지출비용 입력 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">지출비용 입력 <span class="text-red-500">*</span></label>
                    <div class="flex-1 flex items-center gap-2">
                        <input type="text" id="fAmount" class="flex-1 px-3 py-2.5 text-sm reg-input" inputmode="numeric" oninput="fmtNum(this)" required>
                        <span class="text-sm text-slate-400 shrink-0">원</span>
                    </div>
                </div>

                <!-- 한도 초과 경고 + 예외 사유 -->
                <div id="limitWarning" class="hidden ml-40 -mt-2 mb-2">
                    <div class="flex items-start gap-2 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/30">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400 shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-sm text-red-400 font-medium" id="limitWarningText"></p>
                            <p class="text-xs text-slate-400 mt-1">한도를 초과하려면 예외 사유를 작성해주세요.</p>
                            <textarea id="fExceptionReason" rows="2" class="w-full mt-2 px-3 py-2 text-sm reg-input border-red-500/30 resize-none" placeholder="예외 사유를 입력해주세요 (예: 거래처 임원 접대로 부서장 구두 승인)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- 사용처 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">사용처 <span class="text-red-500">*</span></label>
                    <div class="flex-1">
                        <input type="text" id="fStore" class="w-full px-3 py-2.5 text-sm reg-input" placeholder="가맹점명을 입력해주세요 (예: 스타벅스 강남점)" required>
                    </div>
                </div>

                <!-- 사용자 선택 -->
                <div class="flex items-start gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300 pt-2">사용자 선택 <span class="text-red-500">*</span></label>
                    <div class="flex-1">
                        <!-- 선택된 사용자 칩 -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-3 min-h-[28px]">
                            <div class="flex flex-wrap gap-1.5 flex-1" id="empChips"></div>
                            <button type="button" id="empClearAll" onclick="clearAllEmps()" class="hidden px-2 py-1 text-xs text-slate-400 hover:text-rose-400 bg-slate-800 hover:bg-rose-400/10 rounded-lg transition-colors whitespace-nowrap">전체 해제</button>
                        </div>
                        <!-- 검색 -->
                        <input type="text" id="empSearch" class="w-full px-3 py-2 text-sm reg-input mb-2" placeholder="이름 또는 부서로 검색" oninput="filterEmps(this.value)">
                        <!-- 직원 그리드 -->
                        <div class="max-h-[220px] overflow-y-auto rounded-lg border border-slate-800 p-2 emp-grid-scroll">
                            <div class="grid grid-cols-3 gap-2" id="empGrid">
                                <?php foreach ($employees as $emp): ?>
                                <div class="emp-card<?= ($emp['id'] == $loggedInEmpId) ? ' selected' : '' ?>" data-id="<?= $emp['id'] ?>" data-name="<?= htmlspecialchars($emp['name']) ?>" data-dept="<?= htmlspecialchars($emp['dept_name'] ?? '') ?>" onclick="selectEmp(this)">
                                    <span class="emp-avatar"><i data-lucide="user" class="w-4 h-4"></i></span>
                                    <span class="emp-card-info">
                                        <span class="emp-card-name"><?= htmlspecialchars($emp['name']) ?> <span class="emp-card-pos"><?= htmlspecialchars($emp['position'] ?? '') ?></span></span>
                                        <span class="emp-card-dept"><?= htmlspecialchars($emp['dept_name'] ?? '') ?></span>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="text" id="fManualEmp" class="w-64 px-3 py-2 text-sm reg-input mt-2" placeholder="직원 직접 입력 후 등록">
                    </div>
                </div>

                <!-- 사용일 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">사용일 <span class="text-red-500">*</span></label>
                    <div class="flex-1 flex items-center gap-3">
                        <input type="date" id="fDate" class="px-3 py-2.5 text-sm reg-input" required>
                        <div class="flex gap-1.5">
                            <button type="button" onclick="setDate(0)"  class="btn btn-secondary btn-xs">오늘</button>
                            <button type="button" onclick="setDate(-1)" class="btn btn-secondary btn-xs">하루 전</button>
                            <button type="button" onclick="setDate(-2)" class="btn btn-secondary btn-xs">이틀 전</button>
                        </div>
                    </div>
                </div>

                <!-- 법인카드 선택 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">법인카드 선택 <span class="text-red-500">*</span></label>
                    <div class="flex-1">
                        <select id="fCard" class="w-full px-3 py-2.5 text-sm reg-input" required>
                            <option value="">선택</option>
                            <?php foreach ($cards as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['card_alias']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 문서번호 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">문서번호</label>
                    <div class="flex-1 flex gap-2">
                        <input type="text" id="fDocNum" class="flex-1 px-3 py-2.5 text-sm reg-input" placeholder="문서번호를 입력해주세요">
                        <button type="button" onclick="openDocListModal()" class="btn btn-secondary shrink-0">문서 리스트</button>
                    </div>
                </div>

                <!-- 비용구분 -->
                <div class="flex items-center gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">비용구분</label>
                    <div class="flex-1">
                        <div class="zm-radio-group biz-segment-group" role="radiogroup" aria-label="비용구분">
                            <label class="cursor-pointer">
                                <input type="radio" name="bizType" value="사업수행비" checked class="sr-only peer biz-radio-input" onchange="onBizTypeChange()">
                                <span class="zm-radio">사업수행비</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="bizType" value="영업비" class="sr-only peer biz-radio-input" onchange="onBizTypeChange()">
                                <span class="zm-radio">영업비</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="bizType" value="공통비" class="sr-only peer biz-radio-input" onchange="onBizTypeChange()">
                                <span class="zm-radio">공통비</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 사업선택 -->
                <div class="flex items-center gap-4 py-4" id="bizSelectRow">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300">사업선택</label>
                    <div class="flex-1">
                        <select id="fBizName" class="w-full px-3 py-2.5 text-sm reg-input" style="max-width:420px">
                            <option value="">해당없음</option>
                            <?php
                            $ongoing = array_filter($projects, fn($p) => $p['status'] === '진행중');
                            $completed = array_filter($projects, fn($p) => $p['status'] === '완료');
                            if ($ongoing): ?>
                            <optgroup label="진행중">
                                <?php foreach ($ongoing as $p): ?>
                                <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?> · <?= htmlspecialchars($p['client']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif;
                            if ($completed): ?>
                            <optgroup label="완료">
                                <?php foreach ($completed as $p): ?>
                                <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?> · <?= htmlspecialchars($p['client']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- 적요 -->
                <div class="flex items-start gap-4 py-4">
                    <label class="w-36 shrink-0 text-sm font-medium text-slate-300 pt-2">적요</label>
                    <div class="flex-1">
                        <textarea id="fMemo" rows="4" class="w-full px-3 py-2.5 text-sm reg-input resize-none" placeholder="텍스트를 입력해주세요"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 하단 액션 -->
        <div class="flex items-center justify-center gap-6 pb-10">
            <a href="<?= $basePath ?>/pages/card_expenses.php" class="text-sm text-slate-400 hover:text-slate-200 underline">목록으로</a>
            <button type="submit" class="px-8 py-3 text-sm font-bold rounded-full bg-primary text-white hover:bg-primary/90 transition-colors">등록하기</button>
        </div>

        </form>
    </div>
</div>

<style>
/* ── 폼 입력 공통 ── */
.reg-input {
    border: 1px solid var(--zm-border);
    border-radius: 0.5rem;
    outline: none;
    background: var(--zm-surface-2);
    color: var(--zm-text-default);
    transition: border-color .15s, box-shadow .15s;
}
.reg-input:focus {
    border-color: var(--zm-surface-3);
    box-shadow: 0 0 0 3px rgba(0,0,0,.08);
}
.reg-input::placeholder { color: var(--zm-text-subtle); }
select.reg-input option { background: var(--zm-surface-2); color: var(--zm-text-default); }

/* 비용구분 세그먼트(.biz-segment-group) 스타일은 assets/css/custom.css 로 통합됨 */

/* ── 카테고리 대분류 탭 ── */
.cat-tab {
    padding: 8px 18px;
    font-size: 14px;
    font-weight: 500;
    color: #94a3b8;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    background: none;
    transition: all .15s;
    white-space: nowrap;
}
.cat-tab:hover { color: #e2e8f0; }
.cat-tab.active {
    color: #fff;
    background: var(--zm-primary);
    font-weight: 600;
}

/* ── 세분류 탭 (세그먼트) ── */
.sub-tab {
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #94a3b8;
    cursor: pointer;
    background: none;
    border: none;
    border-radius: 7px;
    transition: all .15s;
    white-space: nowrap;
}
.sub-tab:hover { color: #e2e8f0; }
.sub-tab.active {
    color: #fff;
    font-weight: 600;
    background: var(--zm-primary);
}

/* ── 직원 카드 ── */
.emp-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 13px;
    border: 1px solid #334155;
    border-radius: 10px;
    cursor: pointer;
    transition: all .15s;
    user-select: none;
    color: #94a3b8;
    overflow: hidden;
    position: relative;
}
.emp-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #1e293b;
    border: 1px solid #475569;
    color: #94a3b8;
    flex-shrink: 0;
    overflow: hidden;
}
.emp-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.emp-card.selected .emp-avatar {
    background: rgba(0,0,0,.06);
    border-color: rgba(0,0,0,.12);
    color: #818cf8;
}
.emp-card-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 1px;
}
.emp-card-name {
    font-size: 14px;
    font-weight: 600;
    color: #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.emp-card-pos {
    font-size: 12px;
    font-weight: 500;
    color: #94a3b8;
}
.emp-card-dept {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
/* 직원 그리드 스크롤바 */
.emp-grid-scroll::-webkit-scrollbar { width: 5px; }
.emp-grid-scroll::-webkit-scrollbar-track { background: transparent; }
.emp-grid-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
.emp-grid-scroll::-webkit-scrollbar-thumb:hover { background: #475569; }
.emp-card:hover { border-color: #475569; background: rgba(255,255,255,.03); }
.emp-card.selected {
    border-color: var(--zm-text-strong);
    background: rgba(0,0,0,.04);
}
.emp-card.selected .emp-card-name { color: #e2e8f0; }

/* ── 선택된 사용자 칩 ── */
.emp-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px 3px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #c7d2fe;
    background: rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 999px;
}
.emp-chip button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border: none;
    background: none;
    color: #818cf8;
    font-size: 14px;
    cursor: pointer;
    border-radius: 50%;
    line-height: 1;
}
.emp-chip button:hover { background: rgba(0,0,0,.08); color: #e0e7ff; }

/* ── 화이트 테마: 직원 선택 영역 가시성 보강 ── */
html[data-theme="light"] #catTabs,
html[data-theme="light"] #subCatTabs {
    background: #f1f5f9;
    border: 1px solid #dbe3ef;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, .04);
}
html[data-theme="light"] .cat-tab,
html[data-theme="light"] .sub-tab {
    color: #475569;
}
html[data-theme="light"] .cat-tab:hover,
html[data-theme="light"] .sub-tab:hover {
    color: #1e293b;
    background: #e2e8f0;
}
html[data-theme="light"] .cat-tab.active,
html[data-theme="light"] .sub-tab.active {
    color: #ffffff;
    background: var(--zm-primary);
    box-shadow: 0 6px 14px rgba(0, 0, 0, .06);
}
html[data-theme="light"] #ruleText > div {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
    box-shadow: 0 1px 2px rgba(146, 64, 14, .06);
}
html[data-theme="light"] #ruleText span,
html[data-theme="light"] #ruleText .text-amber-400,
html[data-theme="light"] #ruleText .text-amber-400\/80 {
    color: #92400e !important;
}
html[data-theme="light"] #ruleText svg {
    color: #d97706 !important;
}
/* 라이트 테마 비용구분 세그먼트 스타일도 custom.css 로 통합됨 */
html[data-theme="light"] .emp-grid-scroll {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .03);
}
html[data-theme="light"] .emp-card {
    background: #ffffff;
    border-color: #94a3b8;
    color: #334155;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
}
html[data-theme="light"] .emp-card:hover {
    border-color: var(--zm-surface-3);
    background: #f8faff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .06);
}
html[data-theme="light"] .emp-card.selected {
    border-color: var(--zm-text-strong);
    background: #eef2ff;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, .06);
}
html[data-theme="light"] .emp-avatar {
    background: #e2e8f0;
    border-color: #cbd5e1;
    color: #475569;
}
html[data-theme="light"] .emp-card.selected .emp-avatar {
    background: var(--zm-primary);
    border-color: var(--zm-primary);
    color: #ffffff;
}
html[data-theme="light"] .emp-card-name,
html[data-theme="light"] .emp-card.selected .emp-card-name {
    color: #0f172a;
}
html[data-theme="light"] .emp-card-pos,
html[data-theme="light"] .emp-card-dept {
    color: #475569;
}
html[data-theme="light"] .emp-chip {
    color: #1e3a8a;
    background: #e0e7ff;
    border-color: #a5b4fc;
}
html[data-theme="light"] .emp-chip button {
    color: var(--zm-text-strong);
}
html[data-theme="light"] .emp-chip button:hover {
    background: rgba(0,0,0,.08);
    color: #1d4ed8;
}
html[data-theme="light"] #empClearAll {
    color: #475569;
    background: #f1f5f9;
}
html[data-theme="light"] #empClearAll:hover {
    color: #be123c;
    background: #ffe4e6;
}
</style>

<script>
const API = '<?= $basePath ?>/api/card.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;

const CATS = <?php
$cats = [];
if (!empty($regulations)) {
    foreach ($regulations as $r) {
        $cat = $r['category'];
        $sub = $r['sub_category'];
        if (!isset($cats[$cat])) $cats[$cat] = ['items' => []];
        $limit = $r['limit_amount'] > 0 ? number_format($r['limit_amount']) : null;
        $cats[$cat]['items'][$sub] = [
            'rule' => $r['guide'] ?? '',
            'limit' => $limit,
        ];
    }
} else {
    $cats = [
        '식사' => ['items' => [
            '중식/석식' => ['rule' => '1인당 15,000원 이내. 4인 이상 시 사전 승인 필요', 'limit' => '15,000'],
            '회식' => ['rule' => '1인당 50,000원 이내. 팀장 사전승인 필수', 'limit' => '50,000'],
            '간식/음료' => ['rule' => '1인당 10,000원 이내', 'limit' => '10,000'],
        ]],
        '여비교통비' => ['items' => [
            '시내교통' => ['rule' => '실비 정산. 택시 이용 시 사유 기재 필수', 'limit' => null],
            '출장교통' => ['rule' => '실비 정산. KTX 이상 시 사전승인 필요', 'limit' => null],
            '주차비/톨비' => ['rule' => '실비 정산', 'limit' => null],
        ]],
        '영업사업비' => ['items' => [
            '거래처 접대' => ['rule' => '1건당 100,000원 이내. 초과 시 부서장 승인', 'limit' => '100,000'],
            '경조사비' => ['rule' => '건당 50,000원. 경조사 규정 참조', 'limit' => '50,000'],
            '선물/기념품' => ['rule' => '1건당 30,000원 이내', 'limit' => '30,000'],
        ]],
        '구입비' => ['items' => [
            '사무용품' => ['rule' => '건당 50,000원 이내. 초과 시 구매부서 경유', 'limit' => '50,000'],
            '소프트웨어' => ['rule' => 'IT부서 사전 승인 필수', 'limit' => null],
            '장비/비품' => ['rule' => '50만원 이상 시 자산등록 필수', 'limit' => null],
        ]],
    ];
}
echo json_encode($cats, JSON_UNESCAPED_UNICODE);
?>;

const LOGGED_IN_ID = '<?= $loggedInEmpId ?>';
const catKeys = Object.keys(CATS);
let curCat = catKeys[0] || '';
let curSub = '';
let selectedEmps = new Map();

document.addEventListener('DOMContentLoaded', () => {
    const catWrap = document.getElementById('catTabs');
    catWrap.innerHTML = catKeys.map(c => `<button type="button" onclick="selectCat('${c}')" class="cat-tab">${c}</button>`).join('');
    setDate(0);
    selectCat(catKeys[0]);
    if (LOGGED_IN_ID && LOGGED_IN_ID !== '0') {
        const card = document.querySelector(`.emp-card[data-id="${LOGGED_IN_ID}"]`);
        if (card) {
            selectedEmps.set(LOGGED_IN_ID, card.dataset.name);
            card.classList.add('selected');
        }
    }
    renderChips();
    lucide.createIcons();
});

function selectCat(cat) {
    curCat = cat;
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.toggle('active', t.textContent.trim() === cat));

    const subs = Object.keys(CATS[cat].items);
    const wrap = document.getElementById('subCatTabs');
    wrap.innerHTML = subs.map(s => `<button type="button" class="sub-tab" onclick="selectSub('${s}')">${s}</button>`).join('');

    selectSub(subs[0]);
}

function selectSub(sub) {
    curSub = sub;
    document.querySelectorAll('.sub-tab').forEach(t => t.classList.toggle('active', t.textContent.trim() === sub));

    const info = CATS[curCat].items[sub];
    const ruleEl = document.getElementById('ruleText');
    if (info.rule) {
        let items = info.rule.split(/\s*\d+\.\s*/).filter(Boolean);
        if (items.length <= 1) items = info.rule.split(/\.\s+/).map(s => s.replace(/\.?$/, '')).filter(Boolean);
        const list = items.length > 1
            ? items.map((t, i) => `<span class="whitespace-nowrap text-amber-400/80"><span class="text-amber-400 font-semibold mr-1">${i+1}.</span>${t.trim()}</span>`).join('<span class="text-slate-700 mx-1">·</span>')
            : `<span class="text-amber-400/80">${info.rule}</span>`;
        ruleEl.innerHTML = `<div class="flex items-start gap-2 px-4 py-2.5 rounded-lg bg-amber-500/[.05] text-[13px] leading-6"><i data-lucide="alert-circle" class="w-4 h-4 text-amber-500/60 shrink-0 mt-1"></i><div class="flex flex-wrap items-center gap-y-0.5">${list}</div></div>`;
        ruleEl.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } else {
        ruleEl.classList.add('hidden');
    }

    const amtEl = document.getElementById('fAmount');
    if (info.limit) {
        amtEl.placeholder = '한도 : ' + info.limit + '원';
    } else {
        amtEl.placeholder = '지출비용을 입력해주세요';
    }
    checkLimit();
}

function selectEmp(card) {
    const id = card.dataset.id;
    if (selectedEmps.has(id)) {
        selectedEmps.delete(id);
        card.classList.remove('selected');
    } else {
        selectedEmps.set(id, card.dataset.name);
        card.classList.add('selected');
    }
    renderChips();
}

function deselectEmp(id) {
    selectedEmps.delete(id);
    const card = document.querySelector(`.emp-card[data-id="${id}"]`);
    if (card) card.classList.remove('selected');
    renderChips();
}

function renderChips() {
    const wrap = document.getElementById('empChips');
    const clearBtn = document.getElementById('empClearAll');
    if (!selectedEmps.size) {
        wrap.innerHTML = '<span class="text-sm text-slate-500">클릭하여 사용자를 선택하세요</span>';
        clearBtn.classList.add('hidden');
        return;
    }
    wrap.innerHTML = '';
    selectedEmps.forEach(function(name, id) {
        var span = document.createElement('span');
        span.className = 'emp-chip';
        span.textContent = name;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = '×';
        btn.addEventListener('click', function() { deselectEmp(id); });
        span.appendChild(btn);
        wrap.appendChild(span);
    });
    clearBtn.classList.toggle('hidden', selectedEmps.size < 2);
}

function filterEmps(query) {
    const q = query.trim().toLowerCase();
    document.querySelectorAll('.emp-card').forEach(card => {
        const name = (card.dataset.name || '').toLowerCase();
        const dept = (card.dataset.dept || '').toLowerCase();
        card.style.display = (!q || name.includes(q) || dept.includes(q)) ? '' : 'none';
    });
}

function clearAllEmps() {
    selectedEmps.clear();
    document.querySelectorAll('.emp-card.selected').forEach(c => c.classList.remove('selected'));
    renderChips();
}

function onBizTypeChange() {
    const type = document.querySelector('input[name="bizType"]:checked').value;
    const row = document.getElementById('bizSelectRow');
    const sel = document.getElementById('fBizName');
    if (type === '공통비') {
        row.style.opacity = '0.4';
        sel.disabled = true;
        sel.value = '';
    } else {
        row.style.opacity = '1';
        sel.disabled = false;
    }
}

function setDate(offset) {
    const d = new Date();
    d.setDate(d.getDate() + offset);
    document.getElementById('fDate').value = d.toISOString().split('T')[0];
}

function fmtNum(el) {
    let v = el.value.replace(/[^\d]/g, '');
    el.value = v ? Number(v).toLocaleString() : '';
    checkLimit();
}

function getRegLimit() {
    const info = CATS[curCat]?.items?.[curSub];
    if (!info?.limit) return 0;
    return parseInt(info.limit.replace(/[^\d]/g, '')) || 0;
}

function checkLimit() {
    const amount = parseInt((document.getElementById('fAmount').value || '').replace(/[^\d]/g, '')) || 0;
    const limit = getRegLimit();
    const warn = document.getElementById('limitWarning');
    if (limit > 0 && amount > limit) {
        document.getElementById('limitWarningText').textContent =
            curSub + ' 한도 ' + limit.toLocaleString() + '원 초과 (입력: ' + amount.toLocaleString() + '원, +' + (amount - limit).toLocaleString() + '원)';
        warn.classList.remove('hidden');
    } else {
        warn.classList.add('hidden');
        document.getElementById('fExceptionReason').value = '';
    }
}

function submitExpense(e) {
    e.preventDefault();

    const amount = parseInt(document.getElementById('fAmount').value.replace(/[^\d]/g, '')) || 0;
    if (!amount) { alert('지출비용을 입력해주세요.'); return false; }

    const storeName = document.getElementById('fStore').value.trim();
    if (!storeName) { alert('사용처(가맹점명)를 입력해주세요.'); return false; }

    const names = [...selectedEmps.values()];
    const manualName = document.getElementById('fManualEmp').value.trim();
    if (manualName) names.push(manualName);
    if (!names.length) { alert('사용자를 선택해주세요.'); return false; }

    const cardId = document.getElementById('fCard').value;
    if (!cardId) { alert('법인카드를 선택해주세요.'); return false; }

    const limit = getRegLimit();
    const exceptionReason = document.getElementById('fExceptionReason').value.trim();
    if (limit > 0 && amount > limit && !exceptionReason) {
        alert('한도 초과 시 예외 사유를 반드시 입력해주세요.');
        document.getElementById('fExceptionReason').focus();
        return false;
    }

    const data = {
        card_id: parseInt(cardId),
        approval_number: document.getElementById('fApproval').value,
        store_name: storeName,
        amount: amount,
        usage_date: document.getElementById('fDate').value,
        document_number: document.getElementById('fDocNum').value,
        business_name: document.getElementById('fBizName').value,
        description: document.getElementById('fMemo').value,
        user_name: names.join(', '),
        usage_type: '법인',
        category: curCat,
        sub_category: curSub,
        registrant_name: names[0],
        business_type: document.querySelector('input[name="bizType"]:checked').value,
        exception_reason: exceptionReason || '',
    };

    if (HAS_DB) {
        fetch(`${API}?action=saveExpense`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            alert('등록되었습니다.');
            location.href = '<?= $basePath ?>/pages/card_expenses.php';
        })
        .catch(() => alert('저장 중 오류가 발생했습니다.'));
    } else {
        alert('등록되었습니다. (데모 모드)');
        location.href = '<?= $basePath ?>/pages/card_expenses.php';
    }
    return false;
}

function openDocListModal() {
    const modal = document.getElementById('docListModal');
    const iframe = document.getElementById('docListIframe');
    iframe.src = '<?= $basePath ?>/pages/approval_archive.php?embed=1';
    modal.classList.remove('hidden');
}

function closeDocListModal() {
    const modal = document.getElementById('docListModal');
    modal.classList.add('hidden');
    document.getElementById('docListIframe').src = 'about:blank';
}

window.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'selectDoc') {
        document.getElementById('fDocNum').value = e.data.docNumber || '';
        closeDocListModal();
    }
});
</script>

<!-- 문서 리스트 모달 -->
<div id="docListModal" class="fixed inset-0 z-[60] hidden" onclick="if(event.target===this) closeDocListModal()">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="relative z-10 flex items-center justify-center w-full h-full p-6">
        <div class="bg-slate-900 rounded-2xl border border-slate-700 shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800">
                <h3 class="text-base font-bold text-slate-100">문서보관함</h3>
                <button onclick="closeDocListModal()" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-slate-200 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <iframe id="docListIframe" class="flex-1 w-full border-0" src="about:blank"></iframe>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
