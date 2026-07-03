<?php
$pageTitle = '근로계약서';
$currentPage = 'labor';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('labor', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

$empId      = (int)($_GET['id'] ?? 0);
$contractId = (int)($_GET['contract_id'] ?? 0);
$mode       = $_GET['mode'] ?? 'write'; // write | view

// 직급별 기본급
$basePay = [
    '대표이사'=>5000000,'이사'=>4500000,'부장'=>4000000,'차장'=>3500000,
    '과장'=>3000000,'대리'=>2500000,'주임'=>2200000,'사원'=>2000000,'인턴'=>1800000,
];

// 직원 정보 로드
$emp = null;
$contract = null;
$ceoName = '';
$pdo = getDBConnection();

// 대표이사 이름 조회
if ($pdo) {
    try {
        $stmtCeo = $pdo->query("SELECT name FROM employees WHERE position = '대표이사' AND employment_status = '재직' ORDER BY id ASC LIMIT 1");
        $ceoRow = $stmtCeo->fetch();
        if ($ceoRow) {
            $ceoName = $ceoRow['name'];
        } else {
            $stmtAdmin = $pdo->query("SELECT name FROM employees WHERE role = 'admin' AND employment_status = '재직' ORDER BY id ASC LIMIT 1");
            $adminRow = $stmtAdmin->fetch();
            $ceoName = $adminRow ? $adminRow['name'] : '(대표자 미등록)';
        }
    } catch (PDOException $e) { error_log('[LaborContractForm] CEO lookup: ' . $e->getMessage()); $ceoName = '(대표자 미등록)'; }
}

/* 테이블은 db/schema_labor_contract.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */

if ($pdo && $empId) {
    try {
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, e.position, e.employment_type, e.hire_date, e.email, e.phone,
                   COALESCE(d.name, '') AS department_name,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name, '') END AS division_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE e.id = ?
        ");
        $stmt->execute([$empId]);
        $emp = $stmt->fetch() ?: null;   // fetch() 가 false 반환 시 null 로 정규화 (PHP 8 offset 경고 방지)
    } catch (PDOException $e) { error_log('[LaborContractForm] ' . $e->getMessage()); }
}

// 기존 계약 로드
if ($pdo && $contractId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM labor_contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch() ?: null;
    } catch (PDOException $e) { error_log('[LaborContractForm] ' . $e->getMessage()); }
} elseif ($pdo && $empId && !$contractId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM labor_contracts WHERE employee_id = ? ORDER BY version DESC LIMIT 1");
        $stmt->execute([$empId]);
        $contract = $stmt->fetch() ?: null;
    } catch (PDOException $e) { error_log('[LaborContractForm] ' . $e->getMessage()); }
}

// 기본 급여 계산
$position = $emp['position'] ?? '사원';
$defaultBase = $basePay[$position] ?? 2000000;
$defaultMeal = 200000;
$defaultCar = in_array($position, ['대표이사','이사','부장','과장']) ? 200000 : 0;

// 계약 데이터 (기존 or 기본값)
$c = $contract ?: [];
$isView = ($mode === 'view');
$isSigned = (($c['contract_status'] ?? '') === 'signed');
$ro = ($isView || $isSigned) ? 'readonly' : '';
$dis = ($isView || $isSigned) ? 'disabled' : '';

// 계약서 양식 목록 로드 (선택 드롭다운 + 저장된 양식 이름 표기용)
$contractTemplates = [];
$activeTplId = (int)($c['template_id'] ?? 0);
if ($pdo) {
    try {
        $contractTemplates = $pdo->query("SELECT id, name, version_label, is_default
                                          FROM contract_templates
                                          WHERE is_active = 1
                                          ORDER BY is_default DESC, updated_at DESC, id DESC")->fetchAll();
        if (!$activeTplId && !empty($contractTemplates)) {
            // 신규 작성 · 기본 양식 자동 선택
            foreach ($contractTemplates as $t) {
                if ((int)$t['is_default'] === 1) { $activeTplId = (int)$t['id']; break; }
            }
            if (!$activeTplId) $activeTplId = (int)$contractTemplates[0]['id'];
        }
    } catch (PDOException $e) { $contractTemplates = []; }
}

// 값 헬퍼
if (!function_exists('cv')) {
    function cv($contract, string $key, $default = '') {
        return $contract[$key] ?? $default;
    }
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 헤더 (화면용) -->
        <div class="flex items-center justify-between mb-5 no-print">
            <div class="flex items-center gap-2">
                <a href="<?= $basePath ?>/pages/labor.php?tab=contract" class="text-slate-400 hover:text-slate-200">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h2 class="text-lg font-bold text-slate-100">근로계약서</h2>
                <?php if ($isSigned): ?>
                <span class="badge badge-success">체결완료</span>
                <?php elseif ($contract): ?>
                <span class="badge badge-neutral">임시저장</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="btn btn-secondary flex items-center gap-1">
                    <i data-lucide="printer" class="w-3.5 h-3.5 pointer-events-none"></i> 인쇄
                </button>
                <?php if (!$isView && !$isSigned): ?>
                <button onclick="saveContract()" id="btnSave" class="px-4 py-2 text-sm border border-primary text-primary rounded-lg hover:bg-gray-100 flex items-center gap-1">
                    <i data-lucide="save" class="w-3.5 h-3.5 pointer-events-none"></i> 임시저장
                </button>
                <button onclick="signContract()" id="btnSign" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark flex items-center gap-1">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 pointer-events-none"></i> 체결완료
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 사용 양식 선택/표시 -->
        <div class="max-w-[800px] mx-auto mb-4 no-print">
            <div class="flex items-center gap-3 px-4 py-3 bg-slate-900 border border-slate-800 rounded-lg">
                <i data-lucide="file-signature" class="w-4 h-4 text-primary flex-shrink-0"></i>
                <span class="text-sm text-slate-400 flex-shrink-0">사용 양식</span>
                <?php if ($isView || $isSigned): ?>
                    <?php
                    $savedName = $c['template_name'] ?? null;
                    $savedVer  = $c['template_version'] ?? null;
                    if (!$savedName && $activeTplId) {
                        foreach ($contractTemplates as $t) {
                            if ((int)$t['id'] === $activeTplId) { $savedName = $t['name']; $savedVer = $t['version_label']; break; }
                        }
                    }
                    ?>
                    <span class="text-sm font-medium text-slate-200"><?= htmlspecialchars($savedName ?: '양식 정보 없음') ?></span>
                    <?php if ($savedVer): ?>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono"><?= htmlspecialchars($savedVer) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <select id="templateSelect" class="flex-1 bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-sm text-slate-200">
                        <?php foreach ($contractTemplates as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (int)$t['id'] === $activeTplId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?><?= $t['version_label'] ? ' · ' . htmlspecialchars($t['version_label']) : '' ?><?= (int)$t['is_default'] === 1 ? ' (기본)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (empty($contractTemplates)): ?>
                        <option value="0">등록된 양식이 없습니다 · 계약서 양식 페이지에서 먼저 등록해주세요</option>
                        <?php endif; ?>
                    </select>
                    <a href="<?= $basePath ?>/pages/labor_contract_template.php" target="_blank" class="text-xs text-slate-400 hover:text-primary flex items-center gap-1" title="계약서 양식 관리">
                        <i data-lucide="settings" class="w-3.5 h-3.5"></i> 양식 관리
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 직원 검색 (신규 작성 시) -->
        <?php if (!$emp && !$isView && !$isSigned): ?>
        <div class="max-w-[800px] mx-auto mb-4 no-print" id="empSearchBox">
            <div class="relative">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="empSearchInput"
                           class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-gray-300 rounded-xl shadow-sm
                                  placeholder:text-gray-400 text-gray-700
                                  focus:border-gray-300 focus:ring-2 focus:ring-gray-300/20 outline-none transition-all"
                           placeholder="계약할 근로자 이름을 입력하세요"
                           oninput="debouncedSearch()" onkeydown="empSearchKeydown(event)" autocomplete="off">
                    <button id="empSearchClear" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            onclick="clearEmpSearch()">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div id="empSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto z-50"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== 계약서 본문 (법률 문서 형식) ===== -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-[800px] mx-auto" id="contractBody">
            <div class="px-12 py-10 contract-doc zm-document">

                <!-- 타이틀 -->
                <h1 class="text-center text-2xl font-bold tracking-[0.6em] mb-10 contract-title">근 로 계 약 서</h1>

                <!-- ===== 제1조 (당사자) ===== -->
                <div class="mb-6">
                    <p class="font-bold text-sm mb-3">제1조 (당사자)</p>
                    <table class="w-full border-collapse contract-table text-sm">
                        <tr>
                            <td rowspan="3" class="border border-slate-700 px-3 py-2 bg-slate-950 text-center font-medium w-[80px]">사용자<br>(갑)</td>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center w-[70px]">상호</td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" name="company_name" class="doc-input w-full" value="<?= htmlspecialchars(cv($c, 'company_name', '주식회사 재밋')) ?>" <?= $ro ?>>
                            </td>
                            <td rowspan="3" class="border border-slate-700 px-3 py-2 bg-slate-950 text-center font-medium w-[80px]">근로자<br>(을)</td>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center w-[70px]"><?= htmlspecialchars(getOrgLabel('division')) ?></td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" id="empDivision" class="doc-input w-full bg-transparent" value="<?= htmlspecialchars($emp['division_name'] ?? '') ?>" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center">소재지</td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" name="company_address" class="doc-input w-full" value="<?= htmlspecialchars(cv($c, 'company_address', '')) ?>" placeholder="사업장 주소" <?= $ro ?>>
                            </td>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center"><?= htmlspecialchars(getOrgLabel('department')) ?></td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" id="empDept" class="doc-input w-full bg-transparent" value="<?= htmlspecialchars($emp['department_name'] ?? '') ?>" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center">대표자</td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" name="company_ceo" class="doc-input" value="<?= htmlspecialchars(cv($c, 'company_ceo', $ceoName)) ?>" <?= $ro ?>>
                                <span class="text-slate-400 ml-1">( 서명 )</span>
                            </td>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center">성명</td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" id="empName" class="doc-input bg-transparent" value="<?= htmlspecialchars($emp['name'] ?? '') ?>" readonly>
                                <span class="text-slate-400 ml-1">( 서명 )</span>
                            </td>
                        </tr>
                    </table>
                    <!-- 숨김: 사업자번호 (DB 저장용) -->
                    <input type="hidden" name="company_bizno" value="<?= htmlspecialchars(cv($c, 'company_bizno', '')) ?>">
                </div>

                <!-- ===== 제2조 (담당업무 등) ===== -->
                <div class="mb-6">
                    <p class="text-sm mb-2">
                        <span class="font-bold">제2조 (담당업무 등)</span>
                        (을)의 담당업무 및 근무장소는 아래와 같으며, (갑)은 업무상 필요한 경우 이를 변경할 수 있고, (을)은 이에 동의한다.
                        [동의인 : <span id="agree2Name" class="font-medium"><?= htmlspecialchars($emp['name'] ?? '') ?></span> (서명)]
                    </p>
                    <table class="w-full border-collapse contract-table text-sm">
                        <tr>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center w-[80px]">담당<br>업무</td>
                            <td class="border border-slate-700 px-3 py-1.5">
                                <input type="text" name="job_description" class="doc-input w-full" value="<?= htmlspecialchars(cv($c, 'job_description', '')) ?>" placeholder="(갑)이 부여하는 업무" <?= $ro ?>>
                            </td>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center w-[55px]">장소</td>
                            <td class="border border-slate-700 px-3 py-1.5 w-[120px]">
                                <input type="text" name="workplace" class="doc-input w-full" value="<?= htmlspecialchars(cv($c, 'workplace', '(갑)사업장')) ?>" <?= $ro ?>>
                            </td>
                            <td class="border border-slate-700 px-3 py-2 bg-slate-950 text-center w-[55px]">직급</td>
                            <td class="border border-slate-700 px-3 py-1.5 w-[80px]">
                                <input type="text" id="empPosition" class="doc-input w-full bg-transparent" value="<?= htmlspecialchars($emp['position'] ?? '') ?>" readonly>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- ===== 제3조 (계약기간 및 수습기간) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제3조 (계약기간 및 수습기간)</span>
                        ① (갑)과 (을)은
                        <input type="date" name="contract_start" class="doc-input-inline" value="<?= htmlspecialchars(cv($c, 'contract_start', $emp['hire_date'] ?? '')) ?>" <?= $ro ?>>부터
                        <select name="contract_type" id="contractType" class="doc-input-inline" <?= $dis ?> onchange="toggleContractEnd()">
                            <option value="permanent" <?= cv($c, 'contract_type', 'permanent') === 'permanent' ? 'selected' : '' ?>>기간의 정함이 없는</option>
                            <option value="fixed" <?= cv($c, 'contract_type') === 'fixed' ? 'selected' : '' ?>>기간제</option>
                            <option value="parttime" <?= cv($c, 'contract_type') === 'parttime' ? 'selected' : '' ?>>단시간</option>
                        </select>
                        근로계약을 체결한다.
                        <span id="contractEndWrap" class="<?= cv($c, 'contract_type', 'permanent') === 'permanent' ? 'hidden' : '' ?>">
                            (계약 종료일:
                            <input type="date" name="contract_end" id="contractEnd" class="doc-input-inline" value="<?= htmlspecialchars(cv($c, 'contract_end', '')) ?>" <?= $ro ?>>)
                        </span>
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ② 입사일로부터
                        <select name="probation" class="doc-input-inline" <?= $dis ?>>
                            <option value="0" <?= cv($c, 'probation', '3') === '0' ? 'selected' : '' ?>>수습기간 없음</option>
                            <option value="1" <?= cv($c, 'probation') === '1' ? 'selected' : '' ?>>1개월</option>
                            <option value="3" <?= cv($c, 'probation', '3') === '3' ? 'selected' : '' ?>>3개월</option>
                            <option value="6" <?= cv($c, 'probation') === '6' ? 'selected' : '' ?>>6개월</option>
                        </select>을
                        수습기간으로 하며, 수습기간이 만료하기 전이라도 업무적격성이 입증되지 않으면 본채용을 하지 않을 수 있다.
                    </p>
                </div>

                <!-- ===== 제4조 (근로일과 휴일) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제4조 (근로일과 휴일)</span>
                        ① 근로일은 매주
                        <input type="text" name="work_days" class="doc-input-inline w-[100px]" value="<?= htmlspecialchars(cv($c, 'work_days', '월요일부터 금요일')) ?>" <?= $ro ?>>까지로 한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ② 주휴일은
                        <input type="text" name="weekly_holiday" class="doc-input-inline w-[180px]" value="<?= htmlspecialchars(cv($c, 'weekly_holiday', '일요일')) ?>" <?= $ro ?>>로 하며,
                        토요일은 무급휴무일로 한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ③ 근로자의 날과 공휴일은 유급휴일로 하며, 공휴일이 중복될 때에는 1일로 한다.
                    </p>
                </div>

                <!-- ===== 제5조 (휴가) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제5조 (휴가)</span>
                        ① 연차휴가는 근로기준법에서 정하는 바에 따라 발생하며, (을)이 자유로이 사용할 수 있다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ② 연차휴가 외의 휴가는 근로관계법률이 정하는 바에 따른다.
                    </p>
                    <textarea name="annual_leave" class="hidden"><?= htmlspecialchars(cv($c, 'annual_leave', '근로기준법에 의한 연차유급휴가')) ?></textarea>
                </div>

                <!-- ===== 제6조 (근로시간과 휴게시간) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제6조 (근로시간과 휴게시간)</span>
                        ① (을)의 소정근로시간은 1주 40시간, 1일 8시간으로 한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ② 시업시각은
                        <input type="time" name="work_start" class="doc-input-inline" value="<?= htmlspecialchars(substr(cv($c, 'work_start', '09:00'), 0, 5)) ?>" <?= $ro ?>>,
                        종업시각은
                        <input type="time" name="work_end" class="doc-input-inline" value="<?= htmlspecialchars(substr(cv($c, 'work_end', '18:00'), 0, 5)) ?>" <?= $ro ?>>으로 하며,
                        업무량에 따라 변경할 수 있다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ③ 휴게시간은
                        <input type="time" name="break_start" class="doc-input-inline" value="<?= htmlspecialchars(substr(cv($c, 'break_start', '12:00'), 0, 5)) ?>" <?= $ro ?>>부터
                        <input type="time" name="break_end" class="doc-input-inline" value="<?= htmlspecialchars(substr(cv($c, 'break_end', '13:00'), 0, 5)) ?>" <?= $ro ?>>까지로 하며,
                        휴게시간은 (을)이 자유로이 사용할 수 있다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ④ (을)은 (갑)의 업무상황 등에 따른 연장, 야간, 휴일근무를 하는 것에 동의한다.
                        [동의인 : <span id="agree6Name" class="font-medium"><?= htmlspecialchars($emp['name'] ?? '') ?></span> (서명)]
                    </p>
                </div>

                <!-- ===== 제7조 (임금) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제7조 (임금)</span>
                        ① 월 급여는 <span id="monthlyTotalText" class="font-medium">0</span>원으로 한다.
                        월 급여는 기본급
                        <input type="text" name="base_pay" id="basePay" class="doc-input-inline w-[110px] text-right salary-input" value="<?= number_format(cv($c, 'base_pay', $defaultBase)) ?>" <?= ($isView || $isSigned) ? 'readonly' : '' ?> oninput="calcSalary()">원,
                        고정연장(휴일)근로수당
                        <input type="text" name="extra_pay_1" class="doc-input-inline w-[110px] text-right salary-input" value="<?= number_format(cv($c, 'extra_pay_1', 0)) ?>" <?= $ro ?> oninput="calcSalary()">원,
                        식비
                        <input type="text" name="meal_allowance" id="mealAllowance" class="doc-input-inline w-[90px] text-right salary-input" value="<?= number_format(cv($c, 'meal_allowance', $defaultMeal)) ?>" <?= ($isView || $isSigned) ? 'readonly' : '' ?> oninput="calcSalary()">원으로 구성된다.
                        (을)의 시간당 통상임금은 <span id="hourlyWageText" class="font-medium">0</span>원이다.
                    </p>

                    <!-- 추가 수당 (차량지원, 육아, 수당2, 수당3) -->
                    <div class="mt-2 ml-4 text-sm text-slate-300" id="extraPayWrap">
                        <p class="mb-1">※ 추가 수당 항목:</p>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1">
                            <div class="flex items-center gap-2">
                                <span class="w-[70px] shrink-0">차량지원비</span>
                                <input type="text" name="car_allowance" id="carAllowance" class="doc-input-inline w-[100px] text-right salary-input" value="<?= number_format(cv($c, 'car_allowance', $defaultCar)) ?>" <?= ($isView || $isSigned) ? 'readonly' : '' ?> oninput="calcSalary()">원
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-[70px] shrink-0">육아수당</span>
                                <input type="text" name="child_allowance" id="childAllowance" class="doc-input-inline w-[100px] text-right salary-input" value="<?= number_format(cv($c, 'child_allowance', 0)) ?>" <?= ($isView || $isSigned) ? 'readonly' : '' ?> oninput="calcSalary()">원
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-[70px] shrink-0">수당2</span>
                                <input type="text" name="extra_pay_2" class="doc-input-inline w-[100px] text-right salary-input" value="<?= number_format(cv($c, 'extra_pay_2', 0)) ?>" <?= $ro ?> oninput="calcSalary()">원
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-[70px] shrink-0">수당3</span>
                                <input type="text" name="extra_pay_3" class="doc-input-inline w-[100px] text-right salary-input" value="<?= number_format(cv($c, 'extra_pay_3', 0)) ?>" <?= $ro ?> oninput="calcSalary()">원
                            </div>
                        </div>
                    </div>

                    <!-- 비과세 한도 초과 경고 -->
                    <div id="taxFreeWarning" class="hidden mt-2 ml-4 p-2 bg-amber-900/30 border border-amber-700/50 rounded text-xs text-amber-300">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline -mt-0.5"></i>
                        <span id="taxFreeWarningText"></span>
                    </div>

                    <p class="text-sm leading-relaxed mt-3">
                        ② 월 급여는 1주 12시간의 연장(휴일)근로에 대한 임금이 포함되어 있다.
                        [확인 : <span id="confirm7Name" class="font-medium"><?= htmlspecialchars($emp['name'] ?? '') ?></span> (서명)]
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ③ (갑)의 경영상황과 (을)의 업무성과에 따라 성과급을 지급할 수 있다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ④ 월 급여는 세전금액으로 원천징수세액 및 각종 공적보험료 근로자부담분은 (을)이 부담한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ⑤ 급여 산정기간은 매월 1일부터 말일까지이며 익월
                        <input type="number" name="pay_day" class="doc-input-inline w-[50px] text-center" value="<?= cv($c, 'pay_day', 25) ?>" min="1" max="31" <?= $ro ?>>일에
                        (을)이 지정한
                        <select name="pay_method" class="doc-input-inline" <?= $dis ?>>
                            <option value="transfer" <?= cv($c, 'pay_method', 'transfer') === 'transfer' ? 'selected' : '' ?>>계좌</option>
                            <option value="cash" <?= cv($c, 'pay_method') === 'cash' ? 'selected' : '' ?>>현금</option>
                            <option value="other" <?= cv($c, 'pay_method') === 'other' ? 'selected' : '' ?>>기타</option>
                        </select>로 지급한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ⑥ (을)이 1년 이상 계속근로한 경우 계속근로연수 1년에 대하여 평균임금 30일분을
                        <label class="inline-flex items-center gap-1 mx-1">
                            <input type="radio" name="retirement_pay" value="1" class="border-slate-700 text-primary" <?= cv($c, 'retirement_pay', 1) ? 'checked' : '' ?> <?= $dis ?>>
                            <span>퇴직금으로 하며</span>
                        </label>,
                        (갑)과 (을)의 합의에 의해 퇴직연금(DC형)에 가입할 수 있다.
                        <label class="inline-flex items-center gap-1 ml-2 text-slate-400">
                            <input type="radio" name="retirement_pay" value="0" class="border-slate-700 text-primary" <?= !cv($c, 'retirement_pay', 1) ? 'checked' : '' ?> <?= $dis ?>>
                            <span>미적용</span>
                        </label>
                    </p>

                    <!-- 연봉 ↔ 월급 변환 + 합계 -->
                    <div class="mt-3 p-3 bg-primary-light/60 rounded text-sm salary-summary">
                        <?php if (!$isView && !$isSigned): ?>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-slate-300 shrink-0">연봉 입력:</span>
                            <input type="text" id="annualInput" class="doc-input-inline w-[140px] text-right salary-input" placeholder="예: 48,000,000" oninput="distributeAnnual()">원
                            <button type="button" onclick="distributeAnnual()" class="px-2 py-0.5 text-xs bg-primary/20 text-primary border border-primary/30 rounded hover:bg-primary/30">자동 분배</button>
                            <span class="text-slate-500 text-xs">(비과세 우선 배분 후 나머지 → 기본급)</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-center gap-6">
                            <span class="text-slate-300">월 급여 합계:</span>
                            <span class="font-bold text-primary" id="monthlyTotal">0</span>원
                            <span class="text-slate-500 mx-1">|</span>
                            <span class="text-slate-300">연봉:</span>
                            <span class="font-bold text-primary" id="annualTotal">0</span>원
                        </div>
                    </div>
                </div>

                <!-- ===== 제8조 (사회보험) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제8조 (사회보험)</span>
                        다음 각 호의 사회보험에 관하여는 관계법령에 의한다.
                    </p>
                    <div class="mt-2 ml-4 flex items-center gap-5 text-sm">
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="ins_pension" value="1" class="rounded border-slate-700 text-primary" <?= cv($c, 'ins_pension', 1) ? 'checked' : '' ?> <?= $dis ?>>
                            국민연금
                        </label>
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="ins_health" value="1" class="rounded border-slate-700 text-primary" <?= cv($c, 'ins_health', 1) ? 'checked' : '' ?> <?= $dis ?>>
                            건강보험
                        </label>
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="ins_employment" value="1" class="rounded border-slate-700 text-primary" <?= cv($c, 'ins_employment', 1) ? 'checked' : '' ?> <?= $dis ?>>
                            고용보험
                        </label>
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="ins_industrial" value="1" class="rounded border-slate-700 text-primary" <?= cv($c, 'ins_industrial', 1) ? 'checked' : '' ?> <?= $dis ?>>
                            산재보험
                        </label>
                    </div>
                </div>

                <!-- ===== 제9조 (준수사항 등) ===== -->
                <div class="mb-6">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">제9조 (준수사항 등)</span>
                        ① (을)은 업무와 관련된 비밀 및 (갑)의 거래처에 대한 정보를 재직 중은 물론 퇴사 후에도 제3자에게 절대 누설해서는 아니되며, 업무와 관련된 비리, 부정행위를 하지 않는다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ② (을)이 지각, 조퇴 또는 결근한 경우 그 시간만큼 급여를 지급하지 아니한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ③ 근로기준법 제17조제2항에 따라 본 근로계약서를 교부받은 사실을 확인한다.
                        [확인 : <span id="confirm9Name" class="font-medium"><?= htmlspecialchars($emp['name'] ?? '') ?></span> (서명)]
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ④ (을)이 퇴사하고자 하는 경우 30일 전에 미리 (갑)에게 통보한 후 후임자에 대해 업무인수인계를 하여야 하며, 이러한 절차를 이행치 않아 (갑)에게 손해가 발생한 경우 이를 배상해야 한다.
                    </p>
                    <p class="text-sm leading-relaxed mt-1">
                        ⑤ 업무수행과정 중에 (을)이 타인 또는 (갑)에게 고의 또는 중과실로 손해를 끼친 경우 그 손해액의 전액을 배상해야 한다.
                    </p>
                </div>

                <!-- ===== 기타 근로조건 ===== -->
                <div class="mb-8">
                    <p class="text-sm leading-relaxed">
                        <span class="font-bold">기타 근로조건</span>
                    </p>
                    <textarea name="additional_terms" rows="3" class="w-full border-b border-slate-700 text-sm mt-1 px-1 py-1 resize-none focus:outline-none focus:border-gray-300" placeholder="이 계약에 정하지 않은 사항은 근로기준법령에 의함" <?= $ro ?>><?= htmlspecialchars(cv($c, 'additional_terms', '')) ?></textarea>
                </div>

                <!-- ===== 서약문 + 날짜 + 서명 ===== -->
                <div class="mt-10 pt-6 border-t border-slate-700 signature-area">
                    <p class="text-sm text-center leading-relaxed mb-8">
                        계약당사자인 (갑)과 (을)은 자유로이 위와 같이 근로계약을 체결하며,<br>
                        상호 성실히 준수할 것을 서약한다.
                    </p>
                    <p class="text-sm text-center mb-8" id="signDateDisplay">
                        <?php if ($isSigned && !empty($c['signed_at'])): ?>
                            <?= date('Y', strtotime($c['signed_at'])) ?>년 <?= date('n', strtotime($c['signed_at'])) ?>월 <?= date('j', strtotime($c['signed_at'])) ?>일
                        <?php else: ?>
                            <?= date('Y') ?>년 ____월 ____일
                        <?php endif; ?>
                    </p>

                    <!-- 서명란: 갑/을 양쪽 -->
                    <div class="grid grid-cols-2 gap-12 mt-6">
                        <div class="text-center space-y-3">
                            <p class="text-sm font-medium text-slate-400 mb-4">( 갑 )</p>
                            <p class="text-sm">상 호 : <span class="font-medium border-b border-slate-700 px-4 inline-block min-w-[150px]"><?= htmlspecialchars(cv($c, 'company_name', '주식회사 재밋')) ?></span></p>
                            <p class="text-sm">대표자 : <span class="font-medium border-b border-slate-700 px-4 inline-block min-w-[150px]"><?= htmlspecialchars(cv($c, 'company_ceo', $ceoName)) ?></span> (인)</p>
                        </div>
                        <div class="text-center space-y-3">
                            <p class="text-sm font-medium text-slate-400 mb-4">( 을 )</p>
                            <p class="text-sm">소 속 : <span class="font-medium border-b border-slate-700 px-4 inline-block min-w-[150px]" id="signEmpDept"><?= htmlspecialchars(($emp['division_name'] ?? '') . ' ' . ($emp['department_name'] ?? '')) ?></span></p>
                            <p class="text-sm">성 명 : <span class="font-medium border-b border-slate-700 px-4 inline-block min-w-[150px]" id="signEmpName"><?= htmlspecialchars($emp['name'] ?? '') ?></span> (인)</p>
                        </div>
                    </div>
                </div>

                <!-- 숨김 필드: 고용형태 -->
                <input type="hidden" id="empType" value="<?= htmlspecialchars($emp['employment_type'] ?? '') ?>">

            </div>
        </div>

        <!-- 하단 버튼 -->
        <div class="flex justify-center gap-3 mt-6 no-print max-w-[800px] mx-auto">
            <a href="<?= $basePath ?>/pages/labor.php?tab=contract" class="btn btn-secondary btn-lg">목록으로</a>
            <?php if (!$isView && !$isSigned): ?>
            <button onclick="saveContract()" class="px-6 py-2.5 text-sm border border-primary text-primary rounded-lg hover:bg-gray-100">임시저장</button>
            <button onclick="signContract()" class="px-6 py-2.5 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">체결완료</button>
            <?php endif; ?>
        </div>

        <?php if ($contract && $empId): ?>
        <div class="max-w-[800px] mx-auto mt-8 no-print">
            <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-[var(--zm-text-strong)] flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-primary"></i> 급여 변경 이력
                    </h3>
                    <button onclick="loadSalaryHistory()" id="btnLoadHistory" class="text-xs text-primary hover:underline">이력 조회</button>
                </div>
                <div id="salaryHistoryTable"></div>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- 토스트 -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div class="bg-slate-800 text-white px-5 py-3 rounded-lg shadow-lg text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 pointer-events-none"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<!-- 문서 스타일 + 인쇄 CSS -->
<style>
/* 법률 문서 스타일 인라인 input */
.doc-input {
    border: none;
    border-bottom: 1px solid #cbd5e1;
    outline: none;
    padding: 2px 4px;
    font-size: 0.875rem;
    background: transparent;
}
.doc-input:focus {
    border-bottom-color: #3b82f6;
}
.doc-input[readonly] {
    border-bottom-color: transparent;
    cursor: default;
}
.doc-input-inline {
    border: none;
    border-bottom: 1px solid #cbd5e1;
    outline: none;
    padding: 1px 4px;
    font-size: 0.875rem;
    background: transparent;
    vertical-align: baseline;
}
.doc-input-inline:focus {
    border-bottom-color: #3b82f6;
}
.doc-input-inline[readonly],
.doc-input-inline[disabled] {
    border-bottom-color: transparent;
}
html[data-theme="dark"] .doc-input,
html[data-theme="dark"] .doc-input-inline {
    border-bottom-color: #475569;
}
html[data-theme="dark"] .doc-input:focus,
html[data-theme="dark"] .doc-input-inline:focus {
    border-bottom-color: #60a5fa;
}

/* 테이블 */
.contract-table td {
    vertical-align: middle;
}

/* 인쇄 CSS */
@media print {
    #sidebar, header, .no-print, #toast { display: none !important; }
    body { padding: 0 !important; }
    #mainContent { margin: 0 !important; padding: 0 !important; }
    main { padding: 0 !important; }
    @page { size: A4; margin: 0; }
    .contract-title { font-size: 22px !important; }
    .signature-area { page-break-inside: avoid; }
    .bg-slate-900 { border: none !important; box-shadow: none !important; border-radius: 0 !important; }
    .contract-doc { padding: 10mm 15mm !important; }
    .doc-input, .doc-input-inline {
        border-bottom: none !important;
        padding: 0 !important;
    }
    input, select, textarea {
        -webkit-appearance: none;
        appearance: none;
    }
    input[type="checkbox"], input[type="radio"] {
        -webkit-appearance: auto;
        appearance: auto;
    }
    select {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
    }
    .salary-summary { background: transparent !important; border: none !important; padding: 0 !important; }
    #extraPayWrap { display: none !important; }
    #empSearchBox { display: none !important; }
}
</style>

<script>
const API_URL = '<?= $basePath ?>/api/labor_contract.php';
let selectedEmployeeId = <?= $empId ?: 'null' ?>;
let currentContractId = <?= $contract ? (int)$contract['id'] : 'null' ?>;
let currentContractVersion = <?= $contract ? (int)$contract['version'] : 'null' ?>;

// ========== 급여 계산 ==========
function parseNum(str) {
    return parseInt(String(str).replace(/[^\d-]/g, ''), 10) || 0;
}

const TAX_FREE_MEAL = 200000;
const TAX_FREE_CAR  = 200000;

function calcSalary() {
    const basePay = parseNum(document.getElementById('basePay')?.value || '0');
    const meal = parseNum(document.getElementById('mealAllowance')?.value || '0');
    const car = parseNum(document.getElementById('carAllowance')?.value || '0');
    const child = parseNum(document.getElementById('childAllowance')?.value || '0');

    let extra = 0;
    document.querySelectorAll('[name^="extra_pay_"]').forEach(el => {
        extra += parseNum(el.value);
    });

    const monthly = basePay + meal + car + child + extra;
    const annual = monthly * 12;

    document.getElementById('monthlyTotal').textContent = monthly.toLocaleString();
    document.getElementById('annualTotal').textContent = annual.toLocaleString();
    document.getElementById('monthlyTotalText').textContent = monthly.toLocaleString();

    const annualInput = document.getElementById('annualInput');
    if (annualInput && !annualInput._distributing) {
        annualInput.value = annual.toLocaleString();
    }

    const hourly = basePay > 0 ? Math.round((basePay / 209) * 10) / 10 : 0;
    document.getElementById('hourlyWageText').textContent = hourly.toLocaleString();

    const warnings = [];
    if (meal > TAX_FREE_MEAL) warnings.push(`식대 ${(meal - TAX_FREE_MEAL).toLocaleString()}원 초과 → 초과분 과세`);
    if (car > TAX_FREE_CAR) warnings.push(`차량유지비 ${(car - TAX_FREE_CAR).toLocaleString()}원 초과 → 초과분 과세 (본인 차량 업무 사용 시에만 비과세)`);
    const warnEl = document.getElementById('taxFreeWarning');
    const warnText = document.getElementById('taxFreeWarningText');
    if (warnEl && warnText) {
        if (warnings.length) {
            warnText.textContent = warnings.join(' / ');
            warnEl.classList.remove('hidden');
        } else {
            warnEl.classList.add('hidden');
        }
    }
}

function distributeAnnual() {
    const annualInput = document.getElementById('annualInput');
    if (!annualInput) return;

    const annual = parseNum(annualInput.value);
    if (annual <= 0) return;

    const monthly = Math.floor(annual / 12);

    const car = parseNum(document.getElementById('carAllowance')?.value || '0');
    const child = parseNum(document.getElementById('childAllowance')?.value || '0');
    let extraTotal = 0;
    document.querySelectorAll('[name^="extra_pay_"]').forEach(el => {
        extraTotal += parseNum(el.value);
    });

    const meal = Math.min(TAX_FREE_MEAL, monthly);
    const basePay = Math.max(0, monthly - meal - car - child - extraTotal);

    annualInput._distributing = true;
    document.getElementById('basePay').value = basePay.toLocaleString();
    document.getElementById('mealAllowance').value = meal.toLocaleString();
    annualInput._distributing = false;

    calcSalary();
}

// ========== 직원 검색 (실시간) ==========
let searchTimer = null;
let empSearchIdx = -1;
let lastSearchKeyword = '';

function debouncedSearch() {
    clearTimeout(searchTimer);
    const input = document.getElementById('empSearchInput');
    const keyword = input?.value.trim();
    const clearBtn = document.getElementById('empSearchClear');
    const box = document.getElementById('empSearchResults');

    if (clearBtn) clearBtn.classList.toggle('hidden', !input?.value);

    if (!keyword) {
        box?.classList.add('hidden');
        lastSearchKeyword = '';
        empSearchIdx = -1;
        return;
    }
    if (keyword === lastSearchKeyword) return;
    searchTimer = setTimeout(() => doSearch(), 120);
}

function clearEmpSearch() {
    const input = document.getElementById('empSearchInput');
    if (input) { input.value = ''; input.focus(); }
    document.getElementById('empSearchClear')?.classList.add('hidden');
    document.getElementById('empSearchResults')?.classList.add('hidden');
    lastSearchKeyword = '';
    empSearchIdx = -1;
}

function empSearchKeydown(e) {
    const box = document.getElementById('empSearchResults');
    if (e.key === 'Enter') {
        e.preventDefault();
        if (box && !box.classList.contains('hidden')) {
            const items = box.querySelectorAll('[data-emp-id]');
            if (items.length && empSearchIdx >= 0) {
                items[empSearchIdx]?.click();
            }
        }
        return;
    }
    if (!box || box.classList.contains('hidden')) return;
    const items = box.querySelectorAll('[data-emp-id]');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        empSearchIdx = Math.min(empSearchIdx + 1, items.length - 1);
        highlightSearchItem(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        empSearchIdx = Math.max(empSearchIdx - 1, 0);
        highlightSearchItem(items);
    } else if (e.key === 'Escape') {
        box.classList.add('hidden');
        empSearchIdx = -1;
    }
}

function highlightSearchItem(items) {
    items.forEach((el, i) => {
        el.classList.toggle('bg-primary/8', i === empSearchIdx);
    });
    if (empSearchIdx >= 0 && items[empSearchIdx]) {
        items[empSearchIdx].scrollIntoView({ block: 'nearest' });
    }
}

function highlightMatch(text, keyword) {
    if (!keyword) return esc(text);
    const escaped = esc(text);
    const kw = esc(keyword);
    const re = new RegExp('(' + kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return escaped.replace(re, '<mark class="bg-primary/15 text-primary font-semibold rounded px-0.5">$1</mark>');
}

async function doSearch() {
    const keyword = document.getElementById('empSearchInput')?.value.trim();
    if (!keyword) return;
    lastSearchKeyword = keyword;
    empSearchIdx = -1;

    try {
        const res = await fetch(API_URL + '?action=searchEmployee&keyword=' + encodeURIComponent(keyword));
        const data = await res.json();
        const box = document.getElementById('empSearchResults');

        if (!data.employees || data.employees.length === 0) {
            box.innerHTML = '<div class="flex items-center gap-2 px-4 py-3 text-sm text-gray-400"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>검색 결과가 없습니다</div>';
            box.classList.remove('hidden');
            return;
        }

        box.innerHTML = data.employees.map((e, i) => `
            <button data-emp-id="${e.id}"
                    class="w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-gray-50 ${i > 0 ? 'border-t border-gray-100' : ''}"
                    onclick="selectEmployee(${e.id})">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-xs font-bold shrink-0">${esc(e.name.charAt(0))}</span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-semibold text-gray-800">${highlightMatch(e.name, keyword)}</span>
                    <span class="block text-xs text-gray-400 truncate mt-0.5">${esc(e.division_name)}${e.department_name ? ' · ' + esc(e.department_name) : ''}${e.position ? ' · ' + esc(e.position) : ''}</span>
                </span>
            </button>
        `).join('');
        box.classList.remove('hidden');
    } catch (e) {
        console.error(e);
    }
}

document.addEventListener('click', function(e) {
    const searchBox = document.getElementById('empSearchBox');
    if (searchBox && !searchBox.contains(e.target)) {
        document.getElementById('empSearchResults')?.classList.add('hidden');
        empSearchIdx = -1;
    }
});

async function selectEmployee(id) {
    try {
        const res = await fetch(API_URL + '?action=getEmployee&id=' + id);
        const data = await res.json();
        if (!data.employee) return;

        const e = data.employee;
        const s = data.salary;
        selectedEmployeeId = e.id;

        // 제1조 테이블 필드 채우기
        document.getElementById('empName').value = e.name;
        document.getElementById('empDivision').value = e.division_name || '';
        document.getElementById('empDept').value = e.department_name || '';
        document.getElementById('empPosition').value = e.position || '';
        document.getElementById('empType').value = e.employment_type || '';

        // 서명란 채우기
        document.getElementById('signEmpName').textContent = e.name;
        document.getElementById('signEmpDept').textContent = (e.division_name || '') + ' ' + (e.department_name || '');

        // 동의인/확인인 이름 채우기
        ['agree2Name', 'agree6Name', 'confirm7Name', 'confirm9Name'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = e.name;
        });

        // 급여 자동 채움
        document.getElementById('basePay').value = (s.base_pay || 0).toLocaleString();
        document.getElementById('mealAllowance').value = (s.meal_allowance || 0).toLocaleString();
        document.getElementById('carAllowance').value = (s.car_allowance || 0).toLocaleString();
        document.getElementById('childAllowance').value = (s.child_allowance || 0).toLocaleString();

        // 계약시작일 = 입사일
        const startInput = document.querySelector('[name="contract_start"]');
        if (startInput && !startInput.value && e.hire_date) {
            startInput.value = e.hire_date;
        }

        calcSalary();

        document.getElementById('empSearchResults')?.classList.add('hidden');
        empSearchIdx = -1;

        // 기존 계약 확인
        const cRes = await fetch(API_URL + '?action=getContract&employee_id=' + id);
        const cData = await cRes.json();
        if (cData.contract) {
            fillContractData(cData.contract);
            if (cData.contract.contract_status === 'signed') {
                currentContractId = null;
                currentContractVersion = null;
                showToast('체결된 계약서를 바탕으로 새 계약서를 작성합니다.');
            } else {
                currentContractId = cData.contract.id;
                currentContractVersion = parseInt(cData.contract.version) || null;
                showToast('이 직원의 임시저장된 계약서를 불러왔습니다.');
            }
        }
    } catch (e) {
        console.error(e);
        alert('직원 정보 조회 중 오류가 발생했습니다.');
    }
}

function fillContractData(c) {
    const map = {
        'company_name': c.company_name, 'company_ceo': c.company_ceo,
        'company_bizno': c.company_bizno, 'company_address': c.company_address,
        'contract_type': c.contract_type, 'contract_start': c.contract_start,
        'contract_end': c.contract_end, 'workplace': c.workplace,
        'job_description': c.job_description,
        'work_start': (c.work_start || '09:00').substring(0, 5),
        'work_end': (c.work_end || '18:00').substring(0, 5),
        'break_start': (c.break_start || '12:00').substring(0, 5),
        'break_end': (c.break_end || '13:00').substring(0, 5),
        'work_days': c.work_days, 'weekly_holiday': c.weekly_holiday,
        'annual_leave': c.annual_leave, 'pay_day': c.pay_day,
        'pay_method': c.pay_method, 'probation': c.probation,
        'additional_terms': c.additional_terms,
    };

    for (const [name, val] of Object.entries(map)) {
        const el = document.querySelector(`[name="${name}"]`);
        if (el && val != null) el.value = val;
    }

    // 급여 필드
    document.getElementById('basePay').value = Number(c.base_pay || 0).toLocaleString();
    document.getElementById('mealAllowance').value = Number(c.meal_allowance || 0).toLocaleString();
    document.getElementById('carAllowance').value = Number(c.car_allowance || 0).toLocaleString();
    document.getElementById('childAllowance').value = Number(c.child_allowance || 0).toLocaleString();

    ['extra_pay_1', 'extra_pay_2', 'extra_pay_3'].forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el) el.value = Number(c[name] || 0).toLocaleString();
    });

    // 체크박스
    ['ins_pension', 'ins_health', 'ins_employment', 'ins_industrial'].forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el) el.checked = !!Number(c[name]);
    });

    // 라디오
    const retEl = document.querySelector(`[name="retirement_pay"][value="${c.retirement_pay ?? 1}"]`);
    if (retEl) retEl.checked = true;

    calcSalary();
    toggleContractEnd();
}

// ========== 계약 종료일 토글 ==========
function toggleContractEnd() {
    const type = document.getElementById('contractType')?.value;
    const wrap = document.getElementById('contractEndWrap');
    if (type === 'permanent') {
        wrap?.classList.add('hidden');
    } else {
        wrap?.classList.remove('hidden');
    }
}

// ========== 폼 데이터 수집 ==========
function collectFormData() {
    const get = (name) => {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return '';
        if (el.type === 'checkbox') return el.checked ? 1 : 0;
        if (el.type === 'radio') {
            const checked = document.querySelector(`[name="${name}"]:checked`);
            return checked ? checked.value : '';
        }
        return el.value;
    };

    return {
        id: currentContractId,
        employee_id: selectedEmployeeId,
        contract_status: 'draft',
        company_name: get('company_name'),
        company_ceo: get('company_ceo'),
        company_address: get('company_address'),
        company_bizno: get('company_bizno'),
        contract_type: get('contract_type'),
        contract_start: get('contract_start'),
        contract_end: get('contract_end'),
        job_description: get('job_description'),
        workplace: get('workplace'),
        work_start: get('work_start'),
        work_end: get('work_end'),
        break_start: get('break_start'),
        break_end: get('break_end'),
        work_days: get('work_days'),
        weekly_holiday: get('weekly_holiday'),
        annual_leave: get('annual_leave'),
        base_pay: parseNum(document.getElementById('basePay')?.value),
        meal_allowance: parseNum(document.getElementById('mealAllowance')?.value),
        car_allowance: parseNum(document.getElementById('carAllowance')?.value),
        child_allowance: parseNum(document.getElementById('childAllowance')?.value),
        extra_pay_1: parseNum(get('extra_pay_1')),
        extra_pay_2: parseNum(get('extra_pay_2')),
        extra_pay_3: parseNum(get('extra_pay_3')),
        pay_day: get('pay_day'),
        pay_method: get('pay_method'),
        ins_pension: get('ins_pension'),
        ins_health: get('ins_health'),
        ins_employment: get('ins_employment'),
        ins_industrial: get('ins_industrial'),
        retirement_pay: get('retirement_pay'),
        probation: get('probation'),
        additional_terms: get('additional_terms'),
        template_id: parseInt(document.getElementById('templateSelect')?.value || '0', 10) || null,
    };
}

// ========== 저장 ==========
async function saveContract() {
    if (!selectedEmployeeId) {
        alert('직원을 먼저 선택해주세요.');
        return;
    }

    const data = collectFormData();
    try {
        const res = await fetch(API_URL + '?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const result = await res.json();
        if (result.success) {
            currentContractId = result.id;
            showToast(result.message || '저장되었습니다.');
        } else {
            alert(result.error || '저장에 실패했습니다.');
        }
    } catch (e) {
        alert('저장 중 오류가 발생했습니다.');
    }
}

// ========== 체결완료 ==========
async function signContract() {
    if (!selectedEmployeeId) {
        alert('직원을 먼저 선택해주세요.');
        return;
    }

    const start = document.querySelector('[name="contract_start"]')?.value;
    if (!start) {
        alert('계약 시작일을 입력해주세요.');
        return;
    }

    if (!(await AppUI.confirm('계약을 체결하시겠습니까?\n체결 후에는 수정할 수 없습니다.'))) return;

    const data = collectFormData();
    try {
        const saveRes = await fetch(API_URL + '?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const saveResult = await saveRes.json();
        if (!saveResult.success) {
            alert(saveResult.error || '저장에 실패했습니다.');
            return;
        }
        currentContractId = saveResult.id;

        const signRes = await fetch(API_URL + '?action=sign', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentContractId }),
        });
        const signResult = await signRes.json();
        if (signResult.success) {
            showToast('계약이 체결되었습니다.');
            setTimeout(() => {
                location.href = '<?= $basePath ?>/pages/labor.php?tab=contract';
            }, 1500);
        } else {
            alert(signResult.error || '체결에 실패했습니다.');
        }
    } catch (e) {
        alert('체결 처리 중 오류가 발생했습니다.');
    }
}

// ========== 유틸 ==========
function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function showToast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3000);
}

// ========== 급여 변경 이력 ==========
async function loadSalaryHistory() {
    if (!selectedEmployeeId) return;
    const container = document.getElementById('salaryHistoryTable');
    container.innerHTML = '<p class="text-xs text-[var(--zm-text-muted)]">로딩 중...</p>';

    try {
        const res = await fetch(API_URL + '?action=getContractHistory&employee_id=' + selectedEmployeeId);
        const data = await res.json();
        if (!data.history || data.history.length === 0) {
            container.innerHTML = '<p class="text-xs text-[var(--zm-text-muted)]">이력이 없습니다.</p>';
            return;
        }

        const fmt = n => (parseInt(n) || 0).toLocaleString('ko-KR');
        const statusMap = { signed: '체결', draft: '작성중', expiring: '만료예정' };
        const currentVer = currentContractVersion;

        let html = '<div class="overflow-x-auto"><table class="w-full text-xs border-collapse">';
        html += '<thead><tr class="border-b border-[var(--zm-border)]">';
        html += '<th class="text-left py-2 px-2 text-[var(--zm-text-muted)] font-medium">버전</th>';
        html += '<th class="text-left py-2 px-2 text-[var(--zm-text-muted)] font-medium">날짜</th>';
        html += '<th class="text-left py-2 px-2 text-[var(--zm-text-muted)] font-medium">상태</th>';
        html += '<th class="text-right py-2 px-2 text-[var(--zm-text-muted)] font-medium">기본급</th>';
        html += '<th class="text-right py-2 px-2 text-[var(--zm-text-muted)] font-medium">식대</th>';
        html += '<th class="text-right py-2 px-2 text-[var(--zm-text-muted)] font-medium">차량비</th>';
        html += '<th class="text-right py-2 px-2 text-[var(--zm-text-muted)] font-medium">월합계</th>';
        html += '<th class="text-right py-2 px-2 text-[var(--zm-text-muted)] font-medium">연봉</th>';
        html += '<th class="text-right py-2 px-2 text-[var(--zm-text-muted)] font-medium">변동</th>';
        html += '</tr></thead><tbody>';

        data.history.forEach(row => {
            const isCurrent = currentVer && parseInt(row.version) === currentVer;
            const rowCls = isCurrent ? 'bg-primary/10' : '';
            const date = row.signed_at ? row.signed_at.substring(0, 10) : (row.created_at ? row.created_at.substring(0, 10) : '-');
            const status = statusMap[row.contract_status] || row.contract_status || '-';

            let deltaHtml = '-';
            if (row.delta_monthly !== null && row.delta_monthly !== undefined) {
                const d = parseInt(row.delta_monthly);
                if (d > 0) deltaHtml = '<span class="text-emerald-500">+' + fmt(d) + '</span>';
                else if (d < 0) deltaHtml = '<span class="text-rose-500">' + fmt(d) + '</span>';
                else deltaHtml = '<span class="text-[var(--zm-text-muted)]">0</span>';
            }

            html += '<tr class="border-b border-[var(--zm-border)] ' + rowCls + '">';
            html += '<td class="py-2 px-2 text-[var(--zm-text-default)]">v' + row.version + (isCurrent ? ' <span class="badge badge-info text-[10px]">현재</span>' : '') + '</td>';
            html += '<td class="py-2 px-2 text-[var(--zm-text-default)]">' + date + '</td>';
            html += '<td class="py-2 px-2 text-[var(--zm-text-default)]">' + status + '</td>';
            html += '<td class="py-2 px-2 text-right text-[var(--zm-text-default)] tabular-nums">' + fmt(row.base_pay) + '</td>';
            html += '<td class="py-2 px-2 text-right text-[var(--zm-text-default)] tabular-nums">' + fmt(row.meal_allowance) + '</td>';
            html += '<td class="py-2 px-2 text-right text-[var(--zm-text-default)] tabular-nums">' + fmt(row.car_allowance) + '</td>';
            html += '<td class="py-2 px-2 text-right text-[var(--zm-text-default)] tabular-nums font-medium">' + fmt(row.monthly_total) + '</td>';
            html += '<td class="py-2 px-2 text-right text-[var(--zm-text-default)] tabular-nums">' + fmt(row.annual_total) + '</td>';
            html += '<td class="py-2 px-2 text-right">' + deltaHtml + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<p class="text-xs text-rose-500">이력 조회 실패</p>';
    }
    document.getElementById('btnLoadHistory').textContent = '새로고침';
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    calcSalary();
    toggleContractEnd();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
