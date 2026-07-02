<?php
$pageTitle = '직원 상세';
$currentPage = 'hr';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/hr_codes.php';

$hrPositions = getHrCodeItems('직급');
$hrTitles = getHrCodeItems('직책');
$hrEmpTypes = getHrCodeItems('고용형태');
$hrEmpStatuses = getHrCodeItems('고용상태');

// $basePath 는 includes/header.php 에서 프로젝트 루트(/zaemit_plugin)로 이미 설정됨.
// 여기서 dirname(SCRIPT_NAME) 로 덮어쓰면 `$basePath . '/pages/...'` 가 `/pages/pages/...`
// 로 중복되어 링크가 깨진다.
$editMode = false;
$employee = null;

if (!empty($_GET['id'])) {
    $editMode = true;
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ? AND is_active = 1');
            $stmt->execute([(int)$_GET['id']]);
            $employee = $stmt->fetch();
        } catch (PDOException $e) {}
    }
}

$departments = [];
$pdo = getDBConnection();
if ($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'departments'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->query('SELECT id, name FROM departments WHERE is_active = 1 ORDER BY sort_order, name');
            $departments = $stmt->fetchAll();
        }
    } catch (PDOException $e) {}
}
if (empty($departments)) {
    $departments = [
        ['id'=>1,'name'=>'기획'],['id'=>2,'name'=>'개발'],['id'=>3,'name'=>'디자인'],
        ['id'=>4,'name'=>'경영지원'],['id'=>5,'name'=>'영업'],
    ];
}

$contract = null;
if ($editMode && $pdo && !empty($employee['id'])) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'labor_contracts'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare('SELECT * FROM labor_contracts WHERE employee_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([(int)$employee['id']]);
            $contract = $stmt->fetch();
        }
    } catch (PDOException $e) {}
}

// 계약 상태 파생값
$cStatus = '미체결';
$cStatusCls = 'zm-pill-muted';
if ($contract) {
    $raw = $contract['contract_status'] ?? '';
    $endDate = $contract['contract_end'] ?? null;
    $isExpiring = $endDate
        && ($ts = strtotime($endDate)) !== false
        && $ts >= time()
        && ($ts - time()) / 86400 <= 30;
    if ($raw === 'signed' && $isExpiring) { $cStatus = '만료예정'; $cStatusCls = 'zm-pill-warn'; }
    elseif ($raw === 'signed')             { $cStatus = '체결완료'; $cStatusCls = 'zm-pill-ok'; }
    elseif ($raw === 'draft')              { $cStatus = '작성중';   $cStatusCls = 'zm-pill-muted'; }
    elseif ($raw === 'expiring')           { $cStatus = '만료예정'; $cStatusCls = 'zm-pill-warn'; }
}

// 좌측 패널용 파생값
$empName    = $employee['name']     ?? '';
$empInitial = $empName !== '' ? mb_substr($empName, 0, 1) : '?';
$empProfileImage = $employee['profile_image'] ?? '';
$empProfileImageSrc = '';
if ($empProfileImage !== '' && str_starts_with($empProfileImage, 'uploads/profiles/')) {
    $empProfileImageSrc = rtrim($basePath, '/') . '/' . ltrim($empProfileImage, '/');
}
$empPos     = $employee['position']   ?? '';
$empDeptId  = (int)($employee['department_id'] ?? 0);
$empDeptName = '';
foreach ($departments as $d) { if ((int)$d['id'] === $empDeptId) { $empDeptName = $d['name']; break; } }
$empStatus  = $employee['employment_status'] ?? '';
$statusCls  = match ($empStatus) {
    '재직'            => 'zm-pill-ok',
    '휴직','육아휴직'  => 'zm-pill-warn',
    '퇴사'            => 'zm-pill-danger',
    default           => 'zm-pill-muted',
};
$tenureText = '';
if (!empty($employee['hire_date'])) {
    $hireTs = strtotime($employee['hire_date']);
    if ($hireTs !== false) {
        $diffDays = max(0, floor((time() - $hireTs) / 86400));
        $y = (int)floor($diffDays / 365);
        $m = (int)floor(($diffDays % 365) / 30);
        $tenureText = ($y > 0 ? $y . '년 ' : '') . $m . '개월';
    }
}

require_once __DIR__ . '/../includes/card_helpers.php';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-gray-50">

        <!-- 상단 경로 -->
        <div class="flex items-center gap-2 mb-4 text-xs text-[var(--zm-text-subtle)]">
            <a href="<?= $basePath ?>/pages/employees.php" class="hover:text-primary">직원관리</a>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span class="text-[var(--zm-text-default)]"><?= $editMode ? htmlspecialchars($empName ?: '직원 상세') : '신규 직원 등록' ?></span>
        </div>

        <?php if ($editMode && $empStatus === '퇴사'): ?>
        <div class="mb-4 p-4 rounded-xl border flex items-center gap-3 emp-resigned-banner">
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 emp-resigned-icon">
                <i data-lucide="user-x" class="w-4 h-4"></i>
            </div>
            <div>
                <p class="text-sm font-bold">퇴사 처리된 직원입니다</p>
                <p class="text-xs mt-0.5">퇴사일: <?= htmlspecialchars($employee['resign_date'] ?? '미입력') ?> · 기록 보존을 위해 데이터가 유지됩니다.</p>
            </div>
        </div>
        <?php endif; ?>

        <form id="registerForm" autocomplete="off">
            <input type="hidden" id="empId" value="<?= $employee['id'] ?? '' ?>">

            <div class="flex gap-6 items-start">

                <!-- ==================== LEFT PANEL ==================== -->
                <aside class="w-72 shrink-0">
                    <div class="sticky top-20 space-y-4">

                        <!-- Identity Card -->
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-5 text-center shadow-sm">
                            <?php if ($editMode): ?>
                            <div id="photoEditWrapper" class="relative mx-auto w-fit group">
                                <div id="photoUploadArea" class="w-20 h-20 rounded-2xl bg-primary/20 text-primary text-3xl font-bold flex items-center justify-center cursor-pointer overflow-hidden ring-2 ring-primary/20 group-hover:ring-primary/50 transition-all relative">
                                    <?php if ($empProfileImageSrc !== ''): ?>
                                        <img src="<?= htmlspecialchars($empProfileImageSrc) ?>" alt="프로필" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?= htmlspecialchars($empInitial) ?>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                                    </div>
                                </div>
                                <?php if ($empProfileImageSrc !== ''): ?>
                                <button type="button" id="btnDeletePhoto" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white/90 hover:bg-rose-500 text-slate-500 hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-10 shadow-md ring-1 ring-black/10" title="사진 삭제">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" class="hidden">
                            <p class="text-[10px] text-[var(--zm-text-subtle)] opacity-40 mt-1.5 tracking-wider">JPG · PNG · WEBP · 5MB</p>
                            <h2 id="sidebarName" class="text-lg font-bold text-[var(--zm-text-strong)] mt-3"><?= htmlspecialchars($empName) ?: '이름 미입력' ?></h2>
                            <p id="sidebarPosition" class="text-sm text-[var(--zm-text-muted)] mt-0.5"><?= htmlspecialchars($empPos) ?></p>
                            <div class="flex items-center justify-center gap-1.5 mt-2 flex-wrap">
                                <?php if ($empStatus): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $statusCls ?>"><?= htmlspecialchars($empStatus) ?></span>
                                <?php endif; ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $cStatusCls ?>">계약 <?= $cStatus ?></span>
                            </div>
                            <?php else: ?>
                            <div id="photoUploadAreaNew" class="w-20 h-20 mx-auto border-2 border-dashed border-[var(--zm-border)] rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-gray-400 hover:bg-gray-100 transition-all">
                                <i data-lucide="image-plus" class="w-6 h-6 text-[var(--zm-text-subtle)] mb-1"></i>
                                <span class="text-[10px] text-[var(--zm-text-subtle)]">사진</span>
                            </div>
                            <input type="file" id="photoInputNew" accept="image/*" class="hidden">
                            <h2 class="text-base font-bold text-[var(--zm-text-strong)] mt-3">신규 직원 등록</h2>
                            <p class="text-xs text-[var(--zm-text-subtle)] mt-1"><span class="text-amber-500">*</span> 필수 입력 항목</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($editMode): ?>
                        <!-- Key Info -->
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-4 space-y-2.5 shadow-sm">
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="building" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span id="sidebarDept" class="text-[var(--zm-text-default)] truncate"><?= htmlspecialchars($empDeptName ?: '-') ?></span>
                            </div>
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="mail" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span id="sidebarEmail" class="text-[var(--zm-text-default)] truncate"><?= htmlspecialchars($employee['email'] ?? '-') ?></span>
                            </div>
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="phone" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span id="sidebarPhone" class="text-[var(--zm-text-default)] truncate"><?= htmlspecialchars($employee['phone'] ?? '-') ?></span>
                            </div>
                            <?php if (!empty($employee['hire_date'])): ?>
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="calendar-check" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span class="text-[var(--zm-text-default)]">입사 <?= htmlspecialchars($employee['hire_date']) ?> · <?= $tenureText ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($contract): ?>
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-4 space-y-2.5 shadow-sm">
                            <h4 class="text-[11px] font-semibold text-[var(--zm-text-subtle)] uppercase tracking-wider mb-1">급여 정보</h4>
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="wallet" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span class="text-[var(--zm-text-muted)] shrink-0">월급여</span>
                                <span class="text-[var(--zm-text-default)] ml-auto tabular-nums"><?= number_format((int)($contract['monthly_total'] ?? 0)) ?>원</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="landmark" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span class="text-[var(--zm-text-muted)] shrink-0">연봉</span>
                                <span class="text-[var(--zm-text-default)] ml-auto tabular-nums"><?= number_format((int)($contract['annual_total'] ?? 0)) ?>원</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-sm">
                                <i data-lucide="banknote" class="w-4 h-4 text-[var(--zm-text-subtle)] shrink-0"></i>
                                <span class="text-[var(--zm-text-muted)] shrink-0">기본급</span>
                                <span class="text-[var(--zm-text-default)] ml-auto tabular-nums"><?= number_format((int)($contract['base_pay'] ?? 0)) ?>원</span>
                            </div>
                            <a href="<?= $basePath ?>/pages/labor_contract_form.php?id=<?= (int)$employee['id'] ?>&mode=view<?= $contract ? '&contract_id=' . (int)$contract['id'] : '' ?>" class="text-xs text-primary hover:underline flex items-center gap-1 pt-1">
                                계약서 상세 <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <div class="space-y-2">
                            <button type="button" id="btnPwChange" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-[var(--zm-text-default)] bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">
                                <i data-lucide="key" class="w-4 h-4 text-[var(--zm-text-subtle)]"></i>비밀번호 변경
                            </button>
                            <div id="pwChangeForm" class="hidden bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-4 space-y-3">
                                <div class="emp-field">
                                    <label for="regPassword" class="emp-field-label">새 비밀번호</label>
                                    <input type="password" id="regPassword" class="reg-input" placeholder="변경 시에만 입력">
                                </div>
                                <div class="emp-field">
                                    <label for="regPasswordConfirm" class="emp-field-label">비밀번호 확인</label>
                                    <input type="password" id="regPasswordConfirm" class="reg-input" placeholder="재입력">
                                </div>
                                <button type="button" id="pwChangeCancel" class="text-xs text-[var(--zm-text-subtle)] hover:text-[var(--zm-text-default)]">닫기</button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Memo -->
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-4 shadow-sm">
                            <label for="regMemo" class="emp-field-label">메모</label>
                            <textarea id="regMemo" rows="3" class="reg-input resize-none mt-1" placeholder="특이사항·비고"><?= htmlspecialchars($employee['memo'] ?? '') ?></textarea>
                        </div>

                    </div>
                </aside>

                <!-- ==================== RIGHT PANEL ==================== -->
                <div class="flex-1 min-w-0 max-w-4xl">

                    <!-- Main Tabs -->
                    <nav class="flex items-center gap-1 border-b border-[var(--zm-border)] mb-5">
                        <button type="button" data-main-tab="basic" class="main-tab active">기본정보</button>
                        <?php if ($editMode && $employee): ?>
                        <button type="button" data-main-tab="profile" class="main-tab">
                            프로필
                            <span id="profileTotalBadge" class="tab-badge hidden"></span>
                        </button>
                        <button type="button" data-main-tab="appointment" class="main-tab">발령</button>
                        <button type="button" data-main-tab="docs" class="main-tab">문서</button>
                        <?php endif; ?>
                    </nav>

                    <!-- TAB: 기본정보 (인적사항 + 조직·고용 통합) -->
                    <div data-main-panel="basic">
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-[var(--zm-text-default)] mb-3 flex items-center gap-1.5"><i data-lucide="user" class="w-4 h-4 text-[var(--zm-text-subtle)]"></i>인적사항</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-3">
                                <?php if (!$editMode): ?>
                                <div class="emp-field">
                                    <label for="regLoginId" class="emp-field-label">아이디 <span class="text-rose-400">*</span></label>
                                    <input type="text" id="regLoginId" required class="reg-input" placeholder="이메일 형식">
                                </div>
                                <div class="emp-field">
                                    <label for="regPassword" class="emp-field-label">비밀번호 <span class="text-rose-400">*</span></label>
                                    <input type="password" id="regPassword" required class="reg-input" placeholder="초기 비밀번호">
                                    <p class="text-[10px] text-[var(--zm-text-subtle)] mt-1">최초 로그인 후 사용자가 직접 변경 가능합니다.</p>
                                </div>
                                <div class="emp-field">
                                    <label for="regPasswordConfirm" class="emp-field-label">비밀번호 확인 <span class="text-rose-400">*</span></label>
                                    <input type="password" id="regPasswordConfirm" required class="reg-input" placeholder="재입력">
                                </div>
                                <?php else: ?>
                                <div class="emp-field">
                                    <label class="emp-field-label">아이디</label>
                                    <input type="text" id="regLoginId" value="<?= htmlspecialchars($employee['login_id'] ?? $employee['email'] ?? '') ?>" readonly tabindex="-1" class="reg-input cursor-not-allowed opacity-60">
                                </div>
                                <?php endif; ?>
                                <div class="emp-field">
                                    <label for="regName" class="emp-field-label">이름 <span class="text-rose-400">*</span></label>
                                    <input type="text" id="regName" value="<?= htmlspecialchars($employee['name'] ?? '') ?>" required class="reg-input" placeholder="이름">
                                </div>
                                <div class="emp-field">
                                    <label for="regEmail" class="emp-field-label">이메일 <span class="text-rose-400">*</span></label>
                                    <input type="email" id="regEmail" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" class="reg-input" placeholder="알림 수신 이메일">
                                </div>
                                <div class="emp-field">
                                    <label class="emp-field-label">휴대전화 <span class="text-rose-400">*</span></label>
                                    <div class="flex items-center gap-1.5">
                                        <select id="regPhonePrefix" class="reg-select" style="width:76px;flex-shrink:0">
                                            <option>010</option><option>011</option><option>016</option>
                                            <option>017</option><option>018</option><option>019</option>
                                        </select>
                                        <span class="text-[var(--zm-text-subtle)] text-sm">-</span>
                                        <input type="text" id="regPhoneMid" maxlength="4" class="reg-input text-center" placeholder="0000">
                                        <span class="text-[var(--zm-text-subtle)] text-sm">-</span>
                                        <input type="text" id="regPhoneLast" maxlength="4" class="reg-input text-center" placeholder="0000">
                                    </div>
                                </div>
                                <div class="emp-field">
                                    <label for="regBirthdate" class="emp-field-label">생년월일</label>
                                    <input type="date" id="regBirthdate" value="<?= htmlspecialchars($employee['birth_date'] ?? '') ?>" class="reg-input">
                                </div>
                                <div class="emp-field">
                                    <label class="emp-field-label">성별</label>
                                    <div class="flex items-center gap-2 pt-2">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="gender" value="M" <?= ($employee['gender'] ?? '') === 'M' ? 'checked' : '' ?> class="peer hidden">
                                            <span class="gender-chip">남</span>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="gender" value="F" <?= ($employee['gender'] ?? '') === 'F' ? 'checked' : '' ?> class="peer hidden">
                                            <span class="gender-chip">여</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="emp-field md:col-span-2 xl:col-span-3">
                                    <label class="emp-field-label">주소</label>
                                    <div class="flex gap-2 mb-2">
                                        <input type="text" id="regZipcode" value="<?= htmlspecialchars($employee['zipcode'] ?? '') ?>" class="reg-input cursor-pointer hover:border-gray-400" style="width:120px;flex-shrink:0" placeholder="우편번호" readonly>
                                        <input type="text" id="regAddress1" value="<?= htmlspecialchars($employee['address1'] ?? '') ?>" class="reg-input flex-1 cursor-pointer hover:border-gray-400" placeholder="기본주소" readonly>
                                        <button type="button" id="btnAddrSearch" class="px-4 py-2 text-sm font-medium text-[var(--zm-text-default)] bg-[var(--zm-surface-2)] border border-[var(--zm-border)] rounded-lg hover:bg-[var(--zm-surface-3)] whitespace-nowrap">검색</button>
                                    </div>
                                    <input type="text" id="regAddress2" value="<?= htmlspecialchars($employee['address2'] ?? '') ?>" class="reg-input" placeholder="상세주소">
                                </div>
                            </div>
                        </div>

                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-4 mt-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-[var(--zm-text-default)] mb-3 flex items-center gap-1.5"><i data-lucide="building-2" class="w-4 h-4 text-[var(--zm-text-subtle)]"></i>조직·고용</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-3">
                                <div class="emp-field">
                                    <label for="regHireDate" class="emp-field-label">입사일 <span class="text-rose-400">*</span></label>
                                    <input type="date" id="regHireDate" value="<?= htmlspecialchars($employee['hire_date'] ?? '') ?>" required class="reg-input">
                                </div>
                                <div class="emp-field">
                                    <label for="regDept" class="emp-field-label"><?= htmlspecialchars(getOrgLabel('department')) ?> <span class="text-rose-400">*</span></label>
                                    <select id="regDept" required class="reg-select">
                                        <option value="">선택</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>" <?= ($employee['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="emp-field">
                                    <label for="regPosition" class="emp-field-label">직급 <span class="text-rose-400">*</span></label>
                                    <select id="regPosition" required class="reg-select">
                                        <option value="">선택</option>
                                        <?php foreach ($hrPositions as $pos): ?>
                                            <option value="<?= (int)$pos['id'] ?>" data-name="<?= htmlspecialchars($pos['name']) ?>" <?= (int)($employee['rank_id'] ?? 0) === (int)$pos['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pos['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="emp-field">
                                    <label for="regTitle" class="emp-field-label">직책</label>
                                    <select id="regTitle" class="reg-select">
                                        <option value="">선택</option>
                                        <?php foreach ($hrTitles as $t): ?>
                                            <option value="<?= (int)$t['id'] ?>" data-name="<?= htmlspecialchars($t['name']) ?>" <?= (int)($employee['duty_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="emp-field">
                                    <label for="regEmpType" class="emp-field-label">고용형태 <span class="text-rose-400">*</span></label>
                                    <select id="regEmpType" required class="reg-select">
                                        <option value="">선택</option>
                                        <?php foreach ($hrEmpTypes as $et): ?>
                                            <option value="<?= htmlspecialchars($et['name']) ?>" <?= ($employee['employment_type'] ?? '') === $et['name'] ? 'selected' : '' ?>><?= htmlspecialchars($et['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="emp-field">
                                    <label for="regEmpStatus" class="emp-field-label">고용상태 <span class="text-rose-400">*</span></label>
                                    <select id="regEmpStatus" required class="reg-select">
                                        <option value="">선택</option>
                                        <?php foreach ($hrEmpStatuses as $es): ?>
                                            <option value="<?= htmlspecialchars($es['name']) ?>" <?= ($employee['employment_status'] ?? '') === $es['name'] ? 'selected' : '' ?>><?= htmlspecialchars($es['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="emp-field" id="regResignDateWrap">
                                    <label for="regResignDate" class="emp-field-label">퇴사일</label>
                                    <input type="date" id="regResignDate" value="<?= htmlspecialchars($employee['resign_date'] ?? '') ?>" class="reg-input">
                                    <p class="text-[10px] text-[var(--zm-text-subtle)] mt-1">고용상태가 '퇴사'일 때만 활성</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: 프로필 (편집 모드 전용) -->
                    <?php if ($editMode && $employee): ?>
                    <div data-main-panel="profile" class="hidden">
                        <?php $pfEmpId = (int)$employee['id']; $pfBasePath = $basePath; include __DIR__ . '/../includes/profile_sections.php'; ?>
                    </div>

                    <!-- TAB: 발령 -->
                    <div data-main-panel="appointment" class="hidden">
                        <?php
                        $deptName = '';
                        if (!empty($employee['department_id'])) {
                            foreach ($departments as $d) {
                                if ((int)$d['id'] === (int)$employee['department_id']) { $deptName = $d['name']; break; }
                            }
                        }
                        $curEmp = [
                            'department_id'     => $employee['department_id'] ?? null,
                            'department_name'   => $deptName,
                            'position'          => $employee['position'] ?? null,
                            'title'             => $employee['title'] ?? null,
                            'employment_type'   => $employee['employment_type'] ?? null,
                            'employment_status' => $employee['employment_status'] ?? null,
                        ];
                        $summaryParts = array_filter([
                            $deptName,
                            $employee['position'] ?? null,
                            $employee['title'] ?? null,
                            $employee['employment_type'] ?? null,
                        ]);
                        ?>
                        <div id="appointmentSection"
                             data-employee-id="<?= (int)$employee['id'] ?>"
                             data-base-path="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>"
                             data-hire-date="<?= htmlspecialchars($employee['hire_date'] ?? '', ENT_QUOTES) ?>"
                             data-tenure="<?= htmlspecialchars($tenureText, ENT_QUOTES) ?>"
                             data-positions='<?= htmlspecialchars(json_encode(array_column($hrPositions, "name")), ENT_QUOTES) ?>'
                             data-titles='<?= htmlspecialchars(json_encode(array_column($hrTitles, "name")), ENT_QUOTES) ?>'
                             data-emp-types='<?= htmlspecialchars(json_encode(array_column($hrEmpTypes, "name")), ENT_QUOTES) ?>'
                             data-emp-statuses='<?= htmlspecialchars(json_encode(array_column($hrEmpStatuses, "name")), ENT_QUOTES) ?>'
                             data-current='<?= htmlspecialchars(json_encode($curEmp), ENT_QUOTES) ?>'
                             class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl shadow-sm">

                            <!-- 상단: 현재 직무 요약 -->
                            <div class="px-5 pt-5 pb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-[var(--zm-text-subtle)] uppercase tracking-wider">현재 직무</span>
                                    <span class="text-xs text-[var(--zm-text-subtle)]"><?= htmlspecialchars($employee['employment_status'] ?? '재직') ?></span>
                                </div>
                                <p class="text-[15px] font-semibold text-[var(--zm-text-strong)]"><?= htmlspecialchars(implode(' · ', $summaryParts) ?: '-') ?></p>
                                <p class="text-xs text-[var(--zm-text-muted)] mt-1">입사 <?= htmlspecialchars($employee['hire_date'] ?? '-') ?> · <?= $tenureText ?: '-' ?> · 최근 발령 <span id="apptLastDate">-</span></p>
                            </div>

                            <!-- 구분선 + 발령 이력 -->
                            <div class="border-t border-[var(--zm-border)]">
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm font-semibold text-[var(--zm-text-default)]">발령 이력 <span id="apptTotalCount" class="text-xs font-normal text-[var(--zm-text-subtle)]"></span></span>
                                    <button type="button" id="apptAddBtn" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-[var(--zm-primary)] rounded-md hover:bg-[var(--zm-surface-2)] transition-colors">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>추가
                                    </button>
                                </div>
                                <div id="apptFilterChips" class="hidden px-5 pb-2 flex items-center gap-1.5 flex-wrap"></div>
                                <div class="px-5 pb-5">
                                    <div id="apptFormWrap" class="hidden mb-3"></div>
                                    <div id="apptTimeline" class="relative pl-5"></div>
                                    <div id="apptEmpty" class="hidden text-center py-8">
                                        <p class="text-sm text-[var(--zm-text-subtle)]">발령 이력이 없습니다</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: 문서 -->
                    <div data-main-panel="docs" class="hidden">
                        <?php
                        $contractHref = $basePath . '/pages/labor_contract_form.php?id=' . (int)$employee['id']
                            . '&mode=write' . ($contract ? '&contract_id=' . (int)$contract['id'] : '');
                        ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- 근로계약 -->
                            <a href="<?= $contractHref ?>" class="group block rounded-xl border border-[var(--zm-border)] bg-[var(--zm-surface-1)] p-5 hover:border-gray-400 hover:shadow-lg transition-all">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-11 h-11 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-signature" class="w-5 h-5 text-blue-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-semibold text-[var(--zm-text-default)]">근로계약</h4>
                                            <?php if ($contract): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $cStatusCls ?>"><?= $cStatus ?></span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700">미체결</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-[var(--zm-text-subtle)] mt-1">근로계약서 작성 및 관리</p>
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-t border-[var(--zm-border)] flex items-center justify-end">
                                    <span class="text-xs font-medium text-primary flex items-center gap-1 group-hover:gap-2 transition-all">
                                        <?= $contract ? '계약서 보기' : '계약서 작성' ?> <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </span>
                                </div>
                            </a>

                            <!-- 이력서 -->
                            <div class="rounded-xl border border-[var(--zm-border)] bg-[var(--zm-surface-1)] p-5">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-11 h-11 rounded-xl bg-violet-500/10 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-text" class="w-5 h-5 text-violet-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-semibold text-[var(--zm-text-default)]">이력서</h4>
                                            <span id="resumeCount" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[var(--zm-chip-bg)] text-[var(--zm-text-muted)]">0건</span>
                                        </div>
                                        <p class="text-xs text-[var(--zm-text-subtle)] mt-1">이력서 업로드 및 관리</p>
                                    </div>
                                </div>
                                <div id="resumeList" class="mt-3 space-y-1.5"></div>
                                <label class="mt-4 flex items-center justify-center gap-2 w-full py-3 rounded-lg border-2 border-dashed border-[var(--zm-border)] text-xs font-medium text-[var(--zm-text-muted)] hover:border-gray-400 hover:text-gray-900 cursor-pointer transition-colors">
                                    <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                    파일 업로드 (PDF, HWP, DOC)
                                    <input type="file" id="resumeUploadInput" class="hidden" accept=".pdf,.doc,.docx,.hwp,.hwpx,.txt">
                                </label>
                                <div id="resumeEmpty" class="hidden"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /right panel -->
            </div><!-- /flex -->

            <!-- Floating Footer -->
            <div class="h-16"></div>
            <div id="floatingFooter" class="fixed bottom-0 left-60 right-0 z-30 bg-[var(--zm-surface-1)]/95 backdrop-blur-sm border-t border-[var(--zm-border)] px-6 py-3 transition-all duration-300 shadow-[0_-2px_8px_rgba(0,0,0,0.05)]">
                <div class="flex items-center justify-between gap-3">
                    <a href="<?= $basePath ?>/pages/employees.php"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-[var(--zm-text-default)] border border-[var(--zm-border)] rounded-lg hover:bg-[var(--zm-surface-2)]">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i> 목록으로
                    </a>
                    <div class="flex items-center gap-2">
                        <?php if ($editMode): ?>
                        <button type="button" id="btnDelete"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-rose-300 border border-rose-500/40 rounded-lg hover:bg-rose-500/10">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> 삭제
                        </button>
                        <?php endif; ?>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> <?= $editMode ? '변경사항 저장' : '등록' ?>
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </main>
</div>

<!-- 직원 삭제 확인 모달 -->
<div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-[var(--zm-surface-1)] rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
            </div>
            <h3 class="text-lg font-bold text-[var(--zm-text-default)]">직원 삭제</h3>
        </div>
        <div id="deleteCheckLoading" class="py-4 text-center text-sm text-[var(--zm-text-muted)]">연관 데이터 확인 중...</div>
        <div id="deleteCheckResult" class="hidden">
            <p id="deleteTargetName" class="text-sm font-medium text-[var(--zm-text-default)] mb-3"></p>
            <div id="deleteLinksList" class="hidden mb-4">
                <p class="text-sm font-medium text-rose-500 mb-2">이 직원과 연결된 데이터가 있습니다:</p>
                <ul id="deleteLinksUl" class="space-y-1 text-sm text-[var(--zm-text-sub)]"></ul>
                <div class="mt-3 p-3 rounded-lg border emp-delete-warn">
                    <p class="text-sm">
                        <strong>삭제할 수 없습니다.</strong> 연결된 데이터가 있는 직원은 기본정보 탭에서 재직상태를 "퇴사"로 변경해주세요. 퇴사 처리하면 목록에서 숨겨지지만 기록은 보존됩니다.
                    </p>
                </div>
            </div>
            <div id="deleteNoLinks" class="hidden mb-4">
                <p class="text-sm text-[var(--zm-text-sub)]">연결된 급여·결재·근태 데이터가 없습니다. 삭제하면 직원 목록에서 제거됩니다.</p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm rounded-lg border border-[var(--zm-border)] text-[var(--zm-text-default)] hover:bg-[var(--zm-surface-2)]">취소</button>
            <button type="button" id="deleteConfirmBtn" onclick="executeDelete()" class="px-4 py-2 text-sm rounded-lg bg-rose-500 text-white hover:bg-rose-600 hidden">삭제</button>
        </div>
    </div>
</div>

<style>
.emp-field-label { display: block; font-size: 11px; color: var(--zm-text-muted); margin-bottom: 4px; font-weight: 500; }
.emp-resigned-banner { background: #fff1f2; border-color: #fecdd3; color: #be123c; }
.emp-resigned-icon { background: #ffe4e6; color: #e11d48; }
.emp-delete-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
html[data-theme="dark"] .emp-resigned-banner { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.25); color: #fca5a5; }
html[data-theme="dark"] .emp-resigned-icon { background: rgba(239,68,68,0.2); color: #fb7185; }
html[data-theme="dark"] .emp-delete-warn { background: rgba(245,158,11,0.12); border-color: rgba(245,158,11,0.25); color: #fcd34d; }
.emp-field .reg-input, .emp-field .reg-select {
    min-height: 36px; height: auto; font-size: 13px; line-height: 1.5;
    padding-top: 6px; padding-bottom: 6px;
}
.emp-field textarea.reg-input { min-height: 0; height: auto; }

/* 라이트 테마 한정: 입력칸을 흰 배경 + 또렷한 테두리로.
   custom.css 의 `.emp-field > .reg-input{surface-2}` 보다 specificity 를 높여 확실히 우선.
   다크 테마는 custom.css 의 다크 매핑이 그대로 처리하도록 건드리지 않는다. */
html:not([data-theme="dark"]) .emp-field .reg-input,
html:not([data-theme="dark"]) .emp-field .reg-select {
    background-color: #fff;
    border-color: #d1d5db;
}
/* 읽기전용 중 진짜 비활성(cursor-not-allowed)만 회색 */
html:not([data-theme="dark"]) .emp-field .reg-input.cursor-not-allowed {
    background-color: #f1f5f9;
    border-color: #e5e7eb;
}

/* main-tab / profile-tab / tab-badge → custom.css 로 이동 완료 */
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>

<div id="toastWrap" class="fixed bottom-6 right-6 z-[70] space-y-2"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = '<?= $basePath ?>';
    const editMode = <?= $editMode ? 'true' : 'false' ?>;

    function toast(msg, type) {
        const wrap = document.getElementById('toastWrap');
        const bg = type === 'error' ? 'bg-amber-500' : (type === 'info' ? 'bg-slate-300' : 'bg-primary');
        const el = document.createElement('div');
        el.className = 'text-white text-sm px-4 py-2.5 rounded-lg shadow-lg ' + bg;
        el.setAttribute('role', 'status');
        el.textContent = msg;
        wrap.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 2500);
    }

    // === Photo upload ===
    const ORG_API = basePath + '/api/organization.php';
    function setPhotoAreaImage(area, src) {
        area.innerHTML = '<img src="' + src.replace(/"/g, '&quot;') + '" class="w-full h-full object-cover">'
            + '<div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">'
            + '<i data-lucide="camera" class="w-5 h-5 text-white"></i></div>';
        if (window.lucide) lucide.createIcons();
    }
    function setPhotoAreaInitial(area, initial) {
        area.innerHTML = initial
            + '<div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">'
            + '<i data-lucide="camera" class="w-5 h-5 text-white"></i></div>';
        if (window.lucide) lucide.createIcons();
    }

    // 편집 모드: 서버 업로드
    const editPhotoArea = document.getElementById('photoUploadArea');
    const editPhotoInput = document.getElementById('photoInput');
    if (editPhotoArea && editPhotoInput && editMode) {
        editPhotoArea.addEventListener('click', () => editPhotoInput.click());
        editPhotoInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) return;
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type)) { toast('JPG, PNG, WEBP 형식만 가능합니다.', 'error'); this.value = ''; return; }
            if (file.size > 5 * 1024 * 1024) { toast('5MB 이하만 가능합니다.', 'error'); this.value = ''; return; }
            const empId = document.getElementById('empId').value;
            const form = new FormData();
            form.append('profile_photo', file);
            editPhotoInput.disabled = true;
            editPhotoArea.classList.add('photo-uploading');
            fetch(ORG_API + '?action=uploadEmployeePhoto&employee_id=' + empId, { method: 'POST', body: form })
            .then(r => r.text().then(text => {
                let data = {};
                try { data = text ? JSON.parse(text) : {}; } catch(e) { console.error('Photo upload parse error:', text); data = {error:'서버 응답 오류'}; }
                return { ok: r.ok, data };
            }))
            .then(res => {
                if (!res.ok || res.data.error) { toast(res.data.error || '업로드 실패', 'error'); return; }
                const imgPath = res.data.profile_image || '';
                const src = imgPath ? basePath.replace(/\/$/, '') + '/' + imgPath : '';
                if (src) {
                    setPhotoAreaImage(editPhotoArea, src + '?v=' + Date.now());
                    editPhotoArea.classList.add('photo-upload-ok');
                    setTimeout(() => editPhotoArea.classList.remove('photo-upload-ok'), 800);
                    toast('프로필 사진이 저장되었습니다.');
                    ensureDeleteBadge();
                }
            })
            .catch(() => toast('서버 연결 실패', 'error'))
            .finally(() => { editPhotoInput.disabled = false; editPhotoInput.value = ''; editPhotoArea.classList.remove('photo-uploading'); });
        });
    }

    // 신규 모드: 로컬 프리뷰 (저장 시 폼에 포함)
    const newPhotoArea = document.getElementById('photoUploadAreaNew');
    const newPhotoInput = document.getElementById('photoInputNew');
    if (newPhotoArea && newPhotoInput) {
        newPhotoArea.addEventListener('click', () => newPhotoInput.click());
        newPhotoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { newPhotoArea.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`; };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // 사진 삭제
    async function handleDeleteEmpPhoto(btn) {
        if (!(await AppUI.confirm('프로필 사진을 삭제하시겠습니까?'))) return;
        const empId = document.getElementById('empId').value;
        fetch(ORG_API + '?action=deleteProfilePhoto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ employee_id: parseInt(empId) }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { toast(res.error, 'error'); return; }
            const nameEl = document.getElementById('sidebarName');
            const initial = nameEl ? (nameEl.textContent || '?').trim().charAt(0) : '?';
            setPhotoAreaInitial(editPhotoArea, initial);
            btn.remove();
            toast('프로필 사진이 삭제되었습니다.');
        })
        .catch(() => toast('서버 연결 실패', 'error'));
    }
    function ensureDeleteBadge() {
        if (document.getElementById('btnDeletePhoto')) return;
        const wrapper = document.getElementById('photoEditWrapper');
        if (!wrapper) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'btnDeletePhoto';
        btn.className = 'absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white/90 hover:bg-rose-500 text-slate-500 hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-10 shadow-md ring-1 ring-black/10';
        btn.title = '사진 삭제';
        btn.innerHTML = '<i data-lucide="x" class="w-3 h-3"></i>';
        btn.addEventListener('click', e => { e.stopPropagation(); handleDeleteEmpPhoto(btn); });
        wrapper.appendChild(btn);
        if (window.lucide) lucide.createIcons();
    }
    const existingDelBtn = document.getElementById('btnDeletePhoto');
    if (existingDelBtn) existingDelBtn.addEventListener('click', e => { e.stopPropagation(); handleDeleteEmpPhoto(existingDelBtn); });

    // === Phone auto-focus ===
    const phoneMid = document.getElementById('regPhoneMid');
    if (phoneMid) phoneMid.addEventListener('input', function() {
        if (this.value.length >= 4) document.getElementById('regPhoneLast').focus();
    });

    // === Address search ===
    function openRegAddrSearch() {
        if (typeof daum === 'undefined' || !daum.Postcode) { toast('주소 검색 서비스를 불러오지 못했습니다.', 'error'); return; }
        new daum.Postcode({
            oncomplete: function(data) {
                document.getElementById('regZipcode').value = data.zonecode;
                document.getElementById('regAddress1').value = data.roadAddress || data.jibunAddress;
                document.getElementById('regAddress2').focus();
            }
        }).open();
    }
    ['btnAddrSearch', 'regZipcode', 'regAddress1'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', openRegAddrSearch);
    });

    // === Resign date toggle ===
    const statusSelect = document.getElementById('regEmpStatus');
    const resignWrap = document.getElementById('regResignDateWrap');
    if (statusSelect && resignWrap) {
        function toggleResignDate() {
            const isResigned = statusSelect.value === '퇴사';
            resignWrap.style.opacity = isResigned ? '1' : '0.4';
            resignWrap.style.pointerEvents = isResigned ? '' : 'none';
        }
        statusSelect.addEventListener('change', toggleResignDate);
        toggleResignDate();
    }

    // === Phone parsing (edit mode) ===
    <?php if ($employee && !empty($employee['phone'])): ?>
    (function() {
        const parts = '<?= htmlspecialchars($employee['phone'], ENT_QUOTES) ?>'.split('-');
        if (parts.length >= 3) {
            document.getElementById('regPhonePrefix').value = parts[0];
            document.getElementById('regPhoneMid').value    = parts[1];
            document.getElementById('regPhoneLast').value   = parts[2];
        }
    })();
    <?php endif; ?>

    // === Resume (edit mode) ===
    if (editMode) {
        const empId = document.getElementById('empId').value;
        const listEl  = document.getElementById('resumeList');
        const emptyEl = document.getElementById('resumeEmpty');
        const uploadInput = document.getElementById('resumeUploadInput');

        function fmtSize(b) {
            if (!b) return '0 KB';
            if (b < 1024) return b + ' B';
            if (b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
            return (b/(1024*1024)).toFixed(1) + ' MB';
        }
        function escHtml(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

        function renderResumes(list) {
            const countEl = document.getElementById('resumeCount');
            if (countEl) countEl.textContent = (list ? list.length : 0) + '건';
            if (!list || !list.length) {
                if (listEl) listEl.innerHTML = '';
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }
            if (emptyEl) emptyEl.classList.add('hidden');
            if (!listEl) return;
            listEl.innerHTML = list.map(r => `
                <div class="flex items-center gap-2.5 px-3 py-2 border-b border-slate-800 last:border-b-0 hover:bg-slate-900/50 group">
                    <a href="${basePath}/api/employee_resume.php?action=download&id=${r.id}" target="_blank"
                       class="text-sm text-slate-300 hover:text-primary truncate flex-1 min-w-0" title="${escHtml(r.file_name)}">
                        ${escHtml(r.file_name)}
                    </a>
                    <span class="text-[10px] text-slate-600 shrink-0">${fmtSize(r.file_size)}</span>
                    <a href="${basePath}/api/employee_resume.php?action=download&id=${r.id}" target="_blank"
                       class="p-1 text-slate-500 hover:text-primary rounded shrink-0" title="다운로드">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    </a>
                    <button type="button" class="resume-del-btn p-1 text-slate-600 hover:text-rose-400 rounded opacity-0 group-hover:opacity-100 shrink-0" data-id="${r.id}" title="삭제">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            `).join('');
            if (window.lucide) lucide.createIcons();
            listEl.querySelectorAll('.resume-del-btn').forEach(btn => {
                btn.addEventListener('click', () => deleteResume(Number(btn.dataset.id)));
            });
        }

        function loadResumes() {
            fetch(`${basePath}/api/employee_resume.php?action=list&employee_id=${empId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.ok) renderResumes(res.data?.resumes || []);
                    else if (listEl) listEl.innerHTML = `<p class="text-sm text-amber-500">${escHtml(res.error?.message || '조회 실패')}</p>`;
                })
                .catch(() => { if (listEl) listEl.innerHTML = '<p class="text-sm text-amber-500">서버 오류</p>'; });
        }

        async function deleteResume(id) {
            if (!(await AppUI.confirm('이 이력서를 삭제하시겠습니까?'))) return;
            fetch(`${basePath}/api/employee_resume.php?action=delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
            .then(r => r.json())
            .then(res => res.ok ? loadResumes() : alert(res.error?.message || '삭제 실패'))
            .catch(() => alert('서버 오류'));
        }

        if (uploadInput) {
            uploadInput.addEventListener('change', () => {
                if (!uploadInput.files || !uploadInput.files[0]) return;
                const f = uploadInput.files[0];
                if (f.size > 10 * 1024 * 1024) { alert('10MB를 초과할 수 없습니다.'); uploadInput.value = ''; return; }
                const fd = new FormData();
                fd.append('employee_id', empId);
                fd.append('file', f);
                fetch(`${basePath}/api/employee_resume.php?action=upload`, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.ok) { uploadInput.value = ''; loadResumes(); }
                        else alert(res.error?.message || '업로드 실패');
                    })
                    .catch(() => alert('서버 오류'));
            });
        }

        loadResumes();
    }

    // === Form submit (cross-tab validation) ===
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const allRequired = this.querySelectorAll('input[required], select[required]');
        for (const el of allRequired) {
            if (!el.value || !el.value.trim()) {
                const panel = el.closest('[data-main-panel]');
                if (panel && panel.classList.contains('hidden')) {
                    const tabName = panel.dataset.mainPanel;
                    document.querySelectorAll('.main-tab').forEach(t => t.classList.toggle('active', t.dataset.mainTab === tabName));
                    document.querySelectorAll('[data-main-panel]').forEach(p => p.classList.toggle('hidden', p.dataset.mainPanel !== tabName));
                }
                el.focus();
                el.reportValidity();
                return;
            }
        }

        const pw  = document.getElementById('regPassword')?.value || '';
        const pwc = document.getElementById('regPasswordConfirm')?.value || '';
        if (pw && pw !== pwc) {
            alert('비밀번호가 일치하지 않습니다.');
            const pwcEl = document.getElementById('regPasswordConfirm');
            if (pwcEl) pwcEl.focus();
            return;
        }
        if (!editMode && !pw) {
            alert('비밀번호를 입력해주세요.');
            document.getElementById('regPassword')?.focus();
            return;
        }

        const phone = [
            document.getElementById('regPhonePrefix').value,
            document.getElementById('regPhoneMid').value,
            document.getElementById('regPhoneLast').value
        ].filter(Boolean).join('-');

        const data = {
            id: document.getElementById('empId').value || null,
            login_id: document.getElementById('regLoginId').value.trim(),
            password: pw || undefined,
            name: document.getElementById('regName').value.trim(),
            email: document.getElementById('regEmail').value.trim(),
            phone: phone,
            birth_date: document.getElementById('regBirthdate').value || null,
            gender: document.querySelector('input[name="gender"]:checked')?.value || null,
            address1: document.getElementById('regAddress1').value,
            address2: document.getElementById('regAddress2').value,
            zipcode: document.getElementById('regZipcode').value,
            hire_date: document.getElementById('regHireDate')?.value || null,
            department_id: document.getElementById('regDept')?.value || null,
            rank_id: document.getElementById('regPosition')?.value || null,
            duty_id: document.getElementById('regTitle')?.value || null,
            employment_type: document.getElementById('regEmpType')?.value || null,
            employment_status: document.getElementById('regEmpStatus')?.value || null,
            resign_date: document.getElementById('regResignDate')?.value || null,
            memo: document.getElementById('regMemo').value,
        };
        const action = editMode ? 'updateEmployee' : 'createEmployee';
        fetch(`${basePath}/api/organization.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            if (result.error) { alert(result.error); return; }
            if (editMode) {
                alert('수정되었습니다.');
                location.href = `${basePath}/pages/employees.php`;
            } else {
                const newId = result.data?.id || result.id;
                if (newId) {
                    alert('등록되었습니다. 프로필 정보(경력/학력/자격증 등)를 이어서 입력할 수 있습니다.');
                    location.href = `${basePath}/pages/employee_register.php?id=${newId}&tab=profile`;
                } else {
                    alert('등록되었습니다.');
                    location.href = `${basePath}/pages/employees.php`;
                }
            }
        })
        .catch(() => {
            alert(editMode ? '수정되었습니다. (샘플모드)' : '등록되었습니다. (샘플모드)');
            location.href = `${basePath}/pages/employees.php`;
        });
    });

    // === Delete ===
    const deleteBtn = document.getElementById('btnDelete');
    if (deleteBtn) deleteBtn.addEventListener('click', function() {
        openDeleteModal();
    });

    function openDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        const loading = document.getElementById('deleteCheckLoading');
        const result = document.getElementById('deleteCheckResult');
        const confirmBtn = document.getElementById('deleteConfirmBtn');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loading.classList.remove('hidden');
        result.classList.add('hidden');
        confirmBtn.classList.add('hidden');

        const id = document.getElementById('empId').value;
        const name = document.getElementById('sidebarName')?.textContent?.trim() || '이 직원';

        fetch(`${basePath}/api/organization.php?action=checkEmployeeLinks&id=${id}`)
        .then(r => r.json())
        .then(data => {
            loading.classList.add('hidden');
            result.classList.remove('hidden');
            document.getElementById('deleteTargetName').textContent = `대상: ${name}`;

            const linksList = document.getElementById('deleteLinksList');
            const noLinks = document.getElementById('deleteNoLinks');
            const ul = document.getElementById('deleteLinksUl');

            if (data.hasLinks || data.isDeptHead) {
                linksList.classList.remove('hidden');
                noLinks.classList.add('hidden');
                confirmBtn.classList.add('hidden');
                ul.innerHTML = '';
                if (data.isDeptHead) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-2';
                    li.innerHTML = '<i data-lucide="user-check" class="w-3.5 h-3.5 text-rose-400"></i>';
                    li.appendChild(document.createTextNode('부서장으로 지정되어 있음'));
                    ul.appendChild(li);
                }
                (data.links || []).forEach(l => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-2';
                    li.innerHTML = '<i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-400"></i>';
                    li.appendChild(document.createTextNode(l.label + ': ' + l.count + '건'));
                    ul.appendChild(li);
                });
                lucide.createIcons({nodes: [ul]});
            } else {
                linksList.classList.add('hidden');
                noLinks.classList.remove('hidden');
                confirmBtn.classList.remove('hidden');
            }
        })
        .catch(() => {
            loading.classList.add('hidden');
            result.classList.remove('hidden');
            document.getElementById('deleteNoLinks').classList.add('hidden');
            document.getElementById('deleteLinksList').classList.remove('hidden');
            document.getElementById('deleteLinksUl').innerHTML = '';
            const errDiv = document.getElementById('deleteLinksList');
            errDiv.querySelector('p.text-rose-500').textContent = '연관 데이터를 확인할 수 없습니다.';
            errDiv.querySelector('.emp-delete-warn p').textContent = '서버 연결에 실패했습니다. 잠시 후 다시 시도해주세요.';
            confirmBtn.classList.add('hidden');
        });
    }

    window.closeDeleteModal = function() {
        const modal = document.getElementById('deleteConfirmModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.executeDelete = function() {
        const id = document.getElementById('empId').value;
        fetch(`${basePath}/api/organization.php?action=deleteEmployee`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            alert('삭제되었습니다.');
            location.href = `${basePath}/pages/employees.php`;
        })
        .catch(() => {
            alert('삭제되었습니다. (샘플모드)');
            location.href = `${basePath}/pages/employees.php`;
        });
    };

    // === Main tab switching ===
    document.querySelectorAll('.main-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.mainTab;
            document.querySelectorAll('.main-tab').forEach(t => t.classList.toggle('active', t.dataset.mainTab === target));
            document.querySelectorAll('[data-main-panel]').forEach(p => p.classList.toggle('hidden', p.dataset.mainPanel !== target));
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });


    // === URL ?tab=profile 파라미터로 프로필 탭 자동 오픈 ===
    const urlTab = new URLSearchParams(location.search).get('tab');
    if (urlTab && editMode) {
        const mainTab = document.querySelector(`.main-tab[data-main-tab="${urlTab}"]`);
        if (mainTab) mainTab.click();
    }

    // === Password change toggle (edit mode) ===
    if (editMode) {
        const pwBtn = document.getElementById('btnPwChange');
        const pwForm = document.getElementById('pwChangeForm');
        const pwCancel = document.getElementById('pwChangeCancel');
        if (pwBtn && pwForm) {
            pwBtn.addEventListener('click', () => { pwForm.classList.remove('hidden'); pwBtn.classList.add('hidden'); });
            if (pwCancel) pwCancel.addEventListener('click', () => {
                pwForm.classList.add('hidden');
                pwBtn.classList.remove('hidden');
                document.getElementById('regPassword').value = '';
                document.getElementById('regPasswordConfirm').value = '';
            });
        }
    }

    // === Left panel sync (edit mode) ===
    if (editMode) {
        const nameInput = document.getElementById('regName');
        const sName = document.getElementById('sidebarName');
        if (nameInput && sName) nameInput.addEventListener('input', () => { sName.textContent = nameInput.value || '이름 미입력'; });

        const deptSel = document.getElementById('regDept');
        const sDept = document.getElementById('sidebarDept');
        if (deptSel && sDept) deptSel.addEventListener('change', () => { sDept.textContent = deptSel.options[deptSel.selectedIndex]?.text || '-'; });

        const emailInput = document.getElementById('regEmail');
        const sEmail = document.getElementById('sidebarEmail');
        if (emailInput && sEmail) emailInput.addEventListener('input', () => { sEmail.textContent = emailInput.value || '-'; });

        const posSelect = document.getElementById('regPosition');
        const sPos = document.getElementById('sidebarPosition');
        if (posSelect && sPos) posSelect.addEventListener('change', () => { sPos.textContent = posSelect.options[posSelect.selectedIndex]?.text || '-'; });

        ['regPhoneMid', 'regPhoneLast'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', () => {
                const p = [document.getElementById('regPhonePrefix')?.value, document.getElementById('regPhoneMid')?.value, document.getElementById('regPhoneLast')?.value].filter(Boolean).join('-');
                const sPhone = document.getElementById('sidebarPhone');
                if (sPhone) sPhone.textContent = p || '-';
            });
        });
    }

    // === Footer sidebar sync ===
    const footer = document.getElementById('floatingFooter');
    const mainContent = document.getElementById('mainContent');
    if (footer && mainContent) {
        const syncLeft = () => { footer.style.left = getComputedStyle(mainContent).marginLeft; };
        syncLeft();
        new MutationObserver(syncLeft).observe(mainContent, { attributes: true, attributeFilter: ['class','style'] });
    }
});
</script>
<?php if ($editMode && $employee): ?>
<script src="<?= $basePath ?>/assets/js/employee-profile.js"></script>

<script src="<?= $basePath ?>/assets/js/employee-appointment.js"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
