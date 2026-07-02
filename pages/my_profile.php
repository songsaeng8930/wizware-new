<?php
/**
 * Zaemit 그룹웨어 - 내 정보
 * 로그인한 본인만 접근/수정 가능. (사용자 ID는 세션에서 확정)
 */
$pageTitle = '내 정보';
$currentPage = '';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/card_helpers.php';

$user = getCurrentUser() ?? [];
$userId = (int)($user['id'] ?? 0);

$profile = [
    'name'  => $user['name']  ?? '',
    'email' => $user['email'] ?? '',
    'phone' => '',
    'position' => $user['position'] ?? '',
    'title'    => $user['title']    ?? '',
    'department_name' => '',
    'hire_date' => '',
    'employment_type'   => '',
    'employment_status' => '',
    'user_role'         => $user['role'] ?? 'user',
    'profile_image'     => $user['profile_image'] ?? '',
    'birth_date' => '',
    'gender'     => '',
    'zipcode'    => '',
    'address1'   => '',
    'address2'   => '',
];

try {
    $pdo = getDBConnection();
    if ($pdo && $userId > 0) {
        $extra = [];
        foreach (['employment_type','employment_status','user_role','birth_date','gender','zipcode','address1','address2'] as $col) {
            $c = $pdo->prepare('SHOW COLUMNS FROM employees LIKE ?');
            $c->execute([$col]);
            if ($c->fetchColumn()) $extra[] = "e.$col";
        }
        $extraSql = $extra ? (', ' . implode(', ', $extra)) : '';

        $sql = "SELECT e.name, e.email, e.phone, e.position, e.title, e.hire_date,
                       e.profile_image, d.name AS department_name $extraSql
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.id = ? AND e.is_active = 1
                LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach ($row as $k => $v) { $profile[$k] = $v ?? ($profile[$k] ?? ''); }
        }
    }
} catch (Exception $e) {
    error_log('[MyProfile] 프로필 조회 실패: ' . $e->getMessage());
}

$salaryInfo = null;
try {
    if ($pdo && $userId > 0) {
        $pdo->query("SELECT 1 FROM labor_contracts LIMIT 1");
        $salStmt = $pdo->prepare("
            SELECT base_pay, meal_allowance, car_allowance, child_allowance,
                   monthly_total, annual_total, contract_status
            FROM labor_contracts
            WHERE employee_id = ?
            ORDER BY version DESC LIMIT 1
        ");
        $salStmt->execute([$userId]);
        $salaryInfo = $salStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Exception $e) {
    error_log('[MyProfile] salary info: ' . $e->getMessage());
}

$pendingRequest = null;
try {
    if ($pdo && $userId > 0) {
        $pr = $pdo->prepare("SELECT id, changes_json, reason, created_at FROM employee_change_requests WHERE employee_id = ? AND status = '대기' LIMIT 1");
        $pr->execute([$userId]);
        $pendingRequest = $pr->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Exception $e) {
    error_log('[MyProfile] 변경요청 대기 조회 실패: ' . $e->getMessage());
}

$phoneParts = ['010', '', ''];
if (!empty($profile['phone']) && preg_match('/^(\d{2,3})-?(\d{3,4})-?(\d{4})$/', preg_replace('/\s+/', '', $profile['phone']), $m)) {
    $phoneParts = [$m[1], $m[2], $m[3]];
}

$roleLabels = [
    'admin'   => '시스템 관리자',
    'manager' => htmlspecialchars(getOrgLabel('department')) . ' 관리자',
    'user'    => '일반 사용자',
];
$roleLabel = $roleLabels[$profile['user_role']] ?? '일반 사용자';

if (!function_exists('esc')) {
    function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('profileImageSrc')) {
    function profileImageSrc(?string $path, string $basePath): string
    {
        $path = trim((string)$path);
        if ($path === '') return '';
        if (str_starts_with($path, 'uploads/profiles/')) {
            return rtrim($basePath, '/') . '/' . ltrim($path, '/');
        }
        if (str_starts_with($path, '/uploads/profiles/')) {
            return $path;
        }
        return '';
    }
}
$profileImageSrc = profileImageSrc($profile['profile_image'] ?? '', $basePath);

$empInitial = $profile['name'] !== '' ? mb_substr($profile['name'], 0, 1) : '?';

$tenureText = '';
if (!empty($profile['hire_date'])) {
    $hireTs = strtotime($profile['hire_date']);
    if ($hireTs !== false) {
        $diffDays = max(0, floor((time() - $hireTs) / 86400));
        $y = (int)floor($diffDays / 365);
        $m = (int)floor(($diffDays % 365) / 30);
        $tenureText = ($y > 0 ? $y . '년 ' : '') . $m . '개월';
    }
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6 bg-slate-950 min-h-[calc(100vh-3.5rem)]">

    <!-- 상단 경로 -->
    <div class="flex items-center gap-2 mb-4 text-xs text-slate-500">
        <a href="<?= $basePath ?>/pages/dashboard.php" class="hover:text-primary">대시보드</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-slate-300">내 정보</span>
    </div>

    <div class="flex gap-6 items-start">

        <!-- ==================== LEFT PANEL ==================== -->
        <aside class="w-72 shrink-0">
            <div class="sticky top-20 space-y-4">

                <!-- Identity Card -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 text-center">
                    <div id="photoWrapper" class="relative mx-auto w-fit group">
                        <div id="profileAvatar" class="w-20 h-20 rounded-2xl bg-primary/20 text-primary text-3xl font-bold flex items-center justify-center cursor-pointer overflow-hidden ring-2 ring-primary/20 group-hover:ring-primary/50 transition-all relative">
                            <?php if ($profileImageSrc !== ''): ?>
                                <img src="<?= esc($profileImageSrc) ?>" alt="프로필" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= esc($empInitial) ?>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <?php if ($profileImageSrc !== ''): ?>
                        <button type="button" id="btnDeleteMyPhoto" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white/90 hover:bg-rose-500 text-slate-500 hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-10 shadow-md ring-1 ring-black/10" title="사진 삭제">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="profilePhotoInput" class="hidden" accept="image/jpeg,image/png,image/webp">
                    <p class="text-[10px] text-slate-500/40 mt-1.5 tracking-wider">JPG · PNG · WEBP · 5MB</p>
                    <h2 id="sideName" class="text-lg font-bold text-slate-100 mt-2"><?= esc($profile['name']) ?></h2>
                    <p class="text-sm text-slate-400 mt-0.5">
                        <?= esc($profile['position']) ?><?= $profile['title'] ? ' · ' . esc($profile['title']) : '' ?>
                    </p>
                    <p class="text-sm text-slate-500 mt-0.5"><?= esc($profile['department_name'] ?: '미지정') ?></p>
                    <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium <?= $profile['user_role'] === 'admin' ? 'badge-violet' : ($profile['user_role'] === 'manager' ? 'badge-blue' : 'badge-gray') ?>">
                        <?= esc($roleLabel) ?>
                    </span>
                </div>

                <!-- Org Info (read-only) -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-2.5">
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="building" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-300 truncate"><?= esc($profile['department_name'] ?: '-') ?></span>
                    </div>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="briefcase" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-300"><?= esc($profile['position'] ?: '-') ?><?= $profile['title'] ? ' · ' . esc($profile['title']) : '' ?></span>
                    </div>
                    <?php if (!empty($profile['hire_date'])): ?>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="calendar-check" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-300">입사 <?= esc($profile['hire_date']) ?><?= $tenureText ? ' · ' . $tenureText : '' ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['employment_type'])): ?>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="id-card" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-300"><?= esc($profile['employment_type']) ?><?= !empty($profile['employment_status']) ? ' · ' . esc($profile['employment_status']) : '' ?></span>
                    </div>
                    <?php endif; ?>
                    <p class="text-[10px] text-slate-600 pt-2">조직/고용 정보는 인사 담당자만 변경할 수 있습니다.</p>
                </div>

                <?php if ($salaryInfo): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-2.5">
                    <h4 class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">급여 정보</h4>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="wallet" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-400 shrink-0">월급여</span>
                        <span class="text-slate-200 ml-auto tabular-nums"><?= number_format((int)$salaryInfo['monthly_total']) ?>원</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="landmark" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-400 shrink-0">연봉</span>
                        <span class="text-slate-200 ml-auto tabular-nums"><?= number_format((int)$salaryInfo['annual_total']) ?>원</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="banknote" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="text-slate-400 shrink-0">기본급</span>
                        <span class="text-slate-200 ml-auto tabular-nums"><?= number_format((int)$salaryInfo['base_pay']) ?>원</span>
                    </div>
                    <?php if ($salaryInfo['contract_status'] === 'signed'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium badge-success">계약 체결</span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium badge-gray">임시저장</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="space-y-2">
                    <button type="button" id="btnPwToggle" class="btn btn-secondary w-full justify-start gap-2">
                        <i data-lucide="key" class="w-4 h-4 text-slate-500"></i>비밀번호 변경
                    </button>
                    <div id="pwFormPanel" class="hidden bg-white border border-gray-200 rounded-xl p-4 space-y-3">
                        <div>
                            <label for="pwCurrent" class="text-[11px] text-slate-500 font-medium block mb-1">현재 비밀번호</label>
                            <input type="password" id="pwCurrent" class="reg-input" autocomplete="current-password">
                        </div>
                        <div>
                            <label for="pwNew" class="text-[11px] text-slate-500 font-medium block mb-1">새 비밀번호</label>
                            <input type="password" id="pwNew" class="reg-input" minlength="8" autocomplete="new-password">
                            <p class="text-[10px] text-slate-600 mt-1">8자 이상</p>
                        </div>
                        <div>
                            <label for="pwConfirm" class="text-[11px] text-slate-500 font-medium block mb-1">새 비밀번호 확인</label>
                            <input type="password" id="pwConfirm" class="reg-input" minlength="8" autocomplete="new-password">
                            <p id="pwMatch" class="text-[11px] mt-1 hidden"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="pwSave" class="flex-1 px-3 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark">변경</button>
                            <button type="button" id="pwCancel" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-300">닫기</button>
                        </div>
                    </div>
                </div>

            </div>
        </aside>

        <!-- ==================== RIGHT PANEL ==================== -->
        <div class="flex-1 min-w-0">

            <!-- Main Tabs -->
            <nav class="flex items-center gap-1 border-b border-[var(--zm-border)] mb-5">
                <button type="button" data-main-tab="basic" class="main-tab active">기본 정보</button>
                <?php if ($userId > 0): ?>
                <button type="button" data-main-tab="profile" class="main-tab">프로필<span id="profileTotalBadge" class="tab-badge hidden"></span></button>
                <?php endif; ?>
                <button type="button" data-main-tab="history" class="main-tab">변경 이력</button>
            </nav>

            <!-- TAB: 기본 정보 -->
            <div data-main-panel="basic">
                <form id="profileForm" autocomplete="off">
                    <div class="max-w-3xl space-y-4">

                        <?php if ($pendingRequest): ?>
                        <?php
                        $pendingChanges = json_decode($pendingRequest['changes_json'], true) ?: [];
                        $bannerFieldLabels = ['birth_date'=>'생년월일','gender'=>'성별'];
                        $bannerGenderLabels = ['M'=>'남','F'=>'여'];
                        ?>
                        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
                            <p class="text-sm font-medium text-amber-600 dark:text-amber-300 mb-2 flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>생년월일·성별 변경 요청이 승인 대기중입니다
                            </p>
                            <div class="space-y-1 text-sm">
                                <?php foreach ($pendingChanges as $f => $v):
                                    $label = $bannerFieldLabels[$f] ?? $f;
                                    $oldDisp = $v['old'] ?? '-';
                                    $newDisp = $v['new'] ?? '-';
                                    if ($f === 'gender') {
                                        $oldDisp = $bannerGenderLabels[$oldDisp] ?? $oldDisp ?: '-';
                                        $newDisp = $bannerGenderLabels[$newDisp] ?? $newDisp ?: '-';
                                    }
                                ?>
                                    <p class="text-[var(--zm-text-default)]"><span class="text-[var(--zm-text-subtle)]"><?= esc($label) ?>:</span> <?= esc((string)$oldDisp) ?> → <span class="text-amber-600 dark:text-amber-300"><?= esc((string)$newDisp) ?></span></p>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <button type="button" id="piCancelBtn" data-request-id="<?= (int)$pendingRequest['id'] ?>"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs text-rose-500 border border-rose-400/40 rounded-lg hover:bg-rose-500/10">
                                    <i data-lucide="x" class="w-3 h-3"></i>요청 취소
                                </button>
                                <span class="text-[10px] text-[var(--zm-text-subtle)]">취소 후 다시 수정할 수 있습니다</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 섹션 1: 인적사항 -->
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-[var(--zm-text-default)] flex items-center gap-1.5">
                                    <i data-lucide="user" class="w-4 h-4 text-[var(--zm-text-subtle)]"></i>인적사항
                                </h3>
                                <p class="text-xs text-[var(--zm-text-subtle)]"><span class="text-amber-500">*</span> 필수 항목</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
                                <div class="emp-field">
                                    <label for="pfName" class="emp-field-label">이름 <span class="text-rose-400">*</span></label>
                                    <input type="text" id="pfName" class="reg-input" value="<?= esc($profile['name']) ?>" required maxlength="50">
                                </div>
                                <div class="emp-field">
                                    <label for="pfBirth" class="emp-field-label">생년월일</label>
                                    <input type="date" id="pfBirth" class="reg-input" value="<?= esc($profile['birth_date']) ?>" <?= $pendingRequest ? 'disabled' : '' ?>>
                                    <p class="text-[10px] text-[var(--zm-text-subtle)] mt-1">변경 시 관리자 승인 후 반영</p>
                                </div>
                                <div class="emp-field">
                                    <label class="emp-field-label">성별</label>
                                    <div class="flex items-center gap-2 pt-2">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="pfGender" value="M" <?= $profile['gender'] === 'M' ? 'checked' : '' ?> <?= $pendingRequest ? 'disabled' : '' ?> class="peer hidden">
                                            <span class="gender-chip">남</span>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="pfGender" value="F" <?= $profile['gender'] === 'F' ? 'checked' : '' ?> <?= $pendingRequest ? 'disabled' : '' ?> class="peer hidden">
                                            <span class="gender-chip">여</span>
                                        </label>
                                    </div>
                                </div>
                                <?php if (!$pendingRequest): ?>
                                <div class="emp-field">
                                    <label for="pfReason" class="emp-field-label">변경 사유 <span class="text-[var(--zm-text-subtle)] font-normal">(선택)</span></label>
                                    <input type="text" id="pfReason" class="reg-input" maxlength="500" placeholder="예: 주민등록 정정">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 섹션 2: 연락처 -->
                        <div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-5 shadow-sm">
                            <h3 class="text-sm font-semibold text-[var(--zm-text-default)] mb-3 flex items-center gap-1.5">
                                <i data-lucide="mail" class="w-4 h-4 text-[var(--zm-text-subtle)]"></i>연락처
                            </h3>
                            <div class="grid grid-cols-1 gap-y-3">
                                <div class="emp-field">
                                    <label for="pfEmail" class="emp-field-label">이메일 (로그인 아이디) <span class="text-rose-400">*</span></label>
                                    <input type="email" id="pfEmail" class="reg-input" value="<?= esc($profile['email']) ?>" required maxlength="100">
                                    <p class="text-[10px] text-[var(--zm-text-subtle)] mt-1">이 이메일로 로그인하고 시스템 알림을 수신합니다.</p>
                                </div>
                                <div class="emp-field">
                                    <label class="emp-field-label">휴대전화</label>
                                    <div class="flex items-center gap-1.5">
                                        <select id="pfPhonePrefix" class="reg-select" style="width:76px;flex-shrink:0">
                                            <?php foreach (['010','011','016','017','018','019'] as $p): ?>
                                                <option value="<?= $p ?>" <?= $phoneParts[0] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="text-[var(--zm-text-subtle)] text-sm">-</span>
                                        <input type="text" id="pfPhoneMid" class="reg-input text-center" maxlength="4" inputmode="numeric" value="<?= esc($phoneParts[1]) ?>" placeholder="0000">
                                        <span class="text-[var(--zm-text-subtle)] text-sm">-</span>
                                        <input type="text" id="pfPhoneLast" class="reg-input text-center" maxlength="4" inputmode="numeric" value="<?= esc($phoneParts[2]) ?>" placeholder="0000">
                                    </div>
                                </div>
                                <div class="emp-field">
                                    <label class="emp-field-label">주소</label>
                                    <div class="flex gap-2 mb-2">
                                        <input type="text" id="pfZip" class="reg-input cursor-pointer hover:border-gray-400" style="width:120px;flex-shrink:0" maxlength="5" value="<?= esc($profile['zipcode']) ?>" placeholder="우편번호" readonly>
                                        <input type="text" id="pfAddr1" class="reg-input flex-1 cursor-pointer hover:border-gray-400" maxlength="200" value="<?= esc($profile['address1']) ?>" placeholder="기본주소" readonly>
                                        <button type="button" id="btnPfAddrSearch" class="btn btn-secondary whitespace-nowrap">검색</button>
                                    </div>
                                    <input type="text" id="pfAddr2" class="reg-input" maxlength="200" value="<?= esc($profile['address2']) ?>" placeholder="상세주소">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="pfReset" class="btn btn-secondary">되돌리기</button>
                            <button type="submit" id="pfSave" class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>저장
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <!-- TAB: 프로필 -->
            <?php if ($userId > 0): ?>
            <div data-main-panel="profile" class="hidden">
                <?php $pfEmpId = $userId; $pfBasePath = $basePath; include __DIR__ . '/../includes/profile_sections.php'; ?>
            </div>
            <?php endif; ?>

            <!-- TAB: 변경 이력 -->
            <div data-main-panel="history" class="hidden">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div id="crHistory"></div>
                    <div id="crEmpty" class="hidden text-center py-6">
                        <p class="text-sm text-slate-500">변경요청 이력이 없습니다.</p>
                    </div>
                </div>
            </div>

        </div><!-- /right panel -->
    </div><!-- /flex -->

</main>
</div>

<style>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>

<!-- 토스트 컨테이너 -->
<div id="toastWrap" class="fixed bottom-6 right-6 z-[70] space-y-2"></div>

<script>
(function() {
    var basePath = '<?= esc($basePath) ?>';
    var ORG_API = basePath + '/api/organization.php';
    var CR_API  = basePath + '/api/employee_change_request.php';
    var FIELD_LABELS = { birth_date:'생년월일', gender:'성별', zipcode:'우편번호', address1:'기본주소', address2:'상세주소' };
    var GENDER_LABELS = { M:'남', F:'여' };

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ─── 토스트 ───
    function toast(msg, type) {
        var wrap = document.getElementById('toastWrap');
        var el = document.createElement('div');
        var bg = type === 'error' ? 'bg-amber-500' : (type === 'info' ? 'bg-slate-300' : 'bg-primary');
        el.className = 'text-white text-sm px-4 py-2.5 rounded-lg shadow-lg ' + bg;
        el.setAttribute('role', 'status');
        el.textContent = msg;
        wrap.appendChild(el);
        setTimeout(function() {
            el.style.transition = 'opacity .3s';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        }, 2500);
    }

    // ─── 프로필 사진 업로드 (hover 방식) ───
    var avatar = document.getElementById('profileAvatar');
    var photoInput = document.getElementById('profilePhotoInput');
    function resolveAssetUrl(path) {
        if (!path) return '';
        if (path.indexOf('uploads/profiles/') === 0) return basePath.replace(/\/$/, '') + '/' + path;
        if (path.indexOf('/uploads/profiles/') === 0) return path;
        return '';
    }
    function setAvatarImage(src) {
        if (!avatar) return;
        avatar.innerHTML = '<img src="' + src.replace(/"/g, '&quot;') + '" alt="프로필" class="w-full h-full object-cover">'
            + '<div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">'
            + '<i data-lucide="camera" class="w-5 h-5 text-white"></i></div>';
        var headerAvatar = document.getElementById('headerAvatar');
        if (headerAvatar) headerAvatar.innerHTML = '<img src="' + src.replace(/"/g, '&quot;') + '" alt="프로필" class="w-full h-full object-cover">';
        if (window.lucide) lucide.createIcons();
    }
    if (avatar && photoInput) {
        avatar.addEventListener('click', function() { photoInput.click(); });
        var isPhotoUploading = false;
        photoInput.addEventListener('change', function() {
            if (isPhotoUploading) return;
            var file = this.files && this.files[0];
            if (!file) return;
            var allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (allowed.indexOf(file.type) === -1) {
                toast('JPG, PNG, WEBP 형식만 업로드할 수 있습니다.', 'error');
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                toast('사진은 5MB 이하만 업로드할 수 있습니다.', 'error');
                this.value = '';
                return;
            }
            var form = new FormData();
            form.append('profile_photo', file);
            isPhotoUploading = true;
            photoInput.disabled = true;
            avatar.classList.add('photo-uploading');

            fetch(ORG_API + '?action=uploadMyProfilePhoto', { method: 'POST', body: form })
            .then(function(r) {
                return r.text().then(function(text) {
                    var data = {};
                    try { data = text ? JSON.parse(text) : {}; } catch (e) {
                        console.error('[ProfilePhoto] JSON parse failed. Raw:', text);
                        data = { error: '서버 응답 형식 오류 (개발자 도구 콘솔 확인)' };
                    }
                    return { ok: r.ok, status: r.status, data: data, raw: text };
                });
            })
            .then(function(res) {
                if (!res.ok || res.data.error) {
                    console.error('[ProfilePhoto] API error:', res.status, res.data);
                    toast(res.data.error || '사진 저장 실패 (HTTP ' + res.status + ')', 'error');
                    return;
                }
                var imgPath = res.data.profile_image;
                if (!imgPath) {
                    console.error('[ProfilePhoto] No profile_image in response:', res.data);
                    toast('사진 저장은 됐지만 경로를 받지 못했습니다. 새로고침해주세요.', 'error');
                    return;
                }
                var src = resolveAssetUrl(imgPath);
                if (!src) {
                    console.error('[ProfilePhoto] resolveAssetUrl failed for:', imgPath);
                    src = basePath.replace(/\/$/, '') + '/' + imgPath;
                }
                setAvatarImage(src + '?v=' + Date.now());
                avatar.classList.add('photo-upload-ok');
                setTimeout(function() { avatar.classList.remove('photo-upload-ok'); }, 800);
                toast(res.data.message || '프로필 사진이 저장되었습니다.');
                ensureDeleteBadge();
            })
            .catch(function(err) { console.error('[ProfilePhoto] fetch error:', err); toast('서버 연결 실패', 'error'); })
            .finally(function() {
                isPhotoUploading = false;
                photoInput.disabled = false;
                photoInput.value = '';
                avatar.classList.remove('photo-uploading');
            });
        });
    }

    // ─── 사진 삭제 ───
async function handleDeletePhoto(btn) {
        if (!(await AppUI.confirm('프로필 사진을 삭제하시겠습니까?'))) return;
        fetch(ORG_API + '?action=deleteProfilePhoto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.error) { toast(res.error, 'error'); return; }
            if (!avatar) return;
            var initial = '<?= esc($empInitial) ?>';
            avatar.innerHTML = initial
                + '<div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">'
                + '<i data-lucide="camera" class="w-5 h-5 text-white"></i></div>';
            var headerAvatar = document.getElementById('headerAvatar');
            if (headerAvatar) headerAvatar.textContent = initial;
            if (window.lucide) lucide.createIcons();
            btn.remove();
            toast('프로필 사진이 삭제되었습니다.');
        })
        .catch(function() { toast('서버 연결 실패', 'error'); });
    }
    function ensureDeleteBadge() {
        if (document.getElementById('btnDeleteMyPhoto')) return;
        var wrapper = document.getElementById('photoWrapper');
        if (!wrapper) return;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'btnDeleteMyPhoto';
        btn.className = 'absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white/90 hover:bg-rose-500 text-slate-500 hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-10 shadow-md ring-1 ring-black/10';
        btn.title = '사진 삭제';
        btn.innerHTML = '<i data-lucide="x" class="w-3 h-3"></i>';
        btn.addEventListener('click', function(e) { e.stopPropagation(); handleDeletePhoto(btn); });
        wrapper.appendChild(btn);
        if (window.lucide) lucide.createIcons();
    }
    var delMyPhotoBtn = document.getElementById('btnDeleteMyPhoto');
    if (delMyPhotoBtn) {
        delMyPhotoBtn.addEventListener('click', function(e) { e.stopPropagation(); handleDeletePhoto(delMyPhotoBtn); });
    }

    // ─── 전화번호 자동 포커스 ───
    var mid = document.getElementById('pfPhoneMid');
    var last = document.getElementById('pfPhoneLast');
    if (mid && last) {
        mid.addEventListener('input', function() { if (this.value.length >= this.maxLength) last.focus(); });
        [mid, last].forEach(function(inp) {
            inp.addEventListener('input', function() { this.value = this.value.replace(/\D/g, ''); });
        });
    }

    // ─── 주소 검색 ───
    function openAddrSearch() {
        if (typeof daum === 'undefined' || !daum.Postcode) { toast('주소 검색 서비스를 불러오지 못했습니다.', 'error'); return; }
        new daum.Postcode({
            oncomplete: function(data) {
                document.getElementById('pfZip').value = data.zonecode;
                document.getElementById('pfAddr1').value = data.roadAddress || data.jibunAddress;
                document.getElementById('pfAddr2').focus();
            }
        }).open();
    }
    ['btnPfAddrSearch', 'pfZip', 'pfAddr1'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', openAddrSearch);
    });

    // ─── 되돌리기 ───
    var pfBirthEl = document.getElementById('pfBirth');
    function currentGender() { return (document.querySelector('input[name="pfGender"]:checked') || {}).value || ''; }
    var originalValues = {
        name:  document.getElementById('pfName').value,
        email: document.getElementById('pfEmail').value,
        prefix: document.getElementById('pfPhonePrefix').value,
        mid: mid ? mid.value : '',
        last: last ? last.value : '',
        zipcode: document.getElementById('pfZip').value,
        address1: document.getElementById('pfAddr1').value,
        address2: document.getElementById('pfAddr2').value,
        birth:  pfBirthEl ? pfBirthEl.value : '',
        gender: currentGender(),
    };
    document.getElementById('pfReset').addEventListener('click', function() {
        document.getElementById('pfName').value  = originalValues.name;
        document.getElementById('pfEmail').value = originalValues.email;
        document.getElementById('pfPhonePrefix').value = originalValues.prefix;
        if (mid) mid.value = originalValues.mid;
        if (last) last.value = originalValues.last;
        document.getElementById('pfZip').value = originalValues.zipcode;
        document.getElementById('pfAddr1').value = originalValues.address1;
        document.getElementById('pfAddr2').value = originalValues.address2;
        if (pfBirthEl) pfBirthEl.value = originalValues.birth;
        var gReset = document.querySelector('input[name="pfGender"][value="' + originalValues.gender + '"]');
        if (gReset) gReset.checked = true;
        var pfReasonEl = document.getElementById('pfReason');
        if (pfReasonEl) pfReasonEl.value = '';
    });

    // ─── 기본 정보 저장 ───
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var name  = document.getElementById('pfName').value.trim();
        var email = document.getElementById('pfEmail').value.trim();
        var prefix = document.getElementById('pfPhonePrefix').value;
        var phone = (mid && mid.value && last && last.value) ? (prefix + '-' + mid.value + '-' + last.value) : '';
        var zipcode  = document.getElementById('pfZip').value.trim();
        var address1 = document.getElementById('pfAddr1').value.trim();
        var address2 = document.getElementById('pfAddr2').value.trim();

        if (!name) { toast('이름을 입력해주세요', 'error'); return; }
        if (!email) { toast('이메일을 입력해주세요', 'error'); return; }

        // 승인 대상(생년월일·성별) 변경 감지 — 대기 중이면 필드가 disabled 라 자동 제외.
        // 필드가 없으면(렌더 실패 등) 안전하게 잠금 처리해 의도치 않은 변경요청을 막는다.
        var locked = !pfBirthEl || pfBirthEl.disabled;
        var crChanges = {};
        if (!locked) {
            var newBirth = pfBirthEl ? pfBirthEl.value : '';
            var newGender = currentGender();
            if (newBirth !== originalValues.birth)  crChanges.birth_date = newBirth;
            if (newGender !== originalValues.gender) crChanges.gender = newGender;
        }
        var hasCr = Object.keys(crChanges).length > 0;
        var reasonEl = document.getElementById('pfReason');
        var crReason = reasonEl ? reasonEl.value.trim() : '';

        var btn = document.getElementById('pfSave');
        btn.disabled = true; btn.classList.add('is-disabled');

        // 1) 기본정보(이름·이메일·전화·주소)는 즉시 저장
        fetch(ORG_API + '?action=updateMyProfile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, email: email, phone: phone, zipcode: zipcode, address1: address1, address2: address2 }),
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok) { toast(res.data.error || '저장 실패', 'error'); return Promise.reject('handled'); }
            document.getElementById('sideName').textContent = name;
            var headerName = document.querySelector('#userBtn span');
            if (headerName) headerName.textContent = name;
            originalValues.name  = name;
            originalValues.email = email;
            originalValues.prefix = prefix;
            originalValues.mid = mid ? mid.value : '';
            originalValues.last = last ? last.value : '';
            originalValues.zipcode = zipcode;
            originalValues.address1 = address1;
            originalValues.address2 = address2;

            // 2) 생년월일·성별이 바뀐 경우에만 자동으로 승인요청 제출
            if (!hasCr) {
                toast(res.data.message || '내 정보가 저장되었습니다');
                return;
            }
            return fetch(CR_API + '?action=submitRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ changes: crChanges, reason: crReason }),
            })
            .then(function(r) { return r.json().then(function(cr) { return { ok: r.ok, data: cr }; }); })
            .then(function(res2) {
                if (!res2.ok || !res2.data.ok) {
                    var msg = (res2.data.error && res2.data.error.message) || '승인 요청 실패';
                    toast('기본 정보는 저장됐지만 ' + msg, 'error');
                    return;
                }
                toast('저장 완료. 생년월일·성별 변경은 관리자 승인 후 반영됩니다');
                setTimeout(function() { location.reload(); }, 1200);
            });
        })
        .catch(function(err) { if (err !== 'handled') toast('서버 연결 실패', 'error'); })
        .finally(function() { btn.disabled = false; btn.classList.remove('is-disabled'); });
    });

    // ─── 변경요청 취소 (기본정보 탭의 승인 대기 배너) ───
    var piCancelBtn = document.getElementById('piCancelBtn');
    if (piCancelBtn) {
        piCancelBtn.addEventListener('click', async function() {
            if (!(await AppUI.confirm('변경 요청을 취소하시겠습니까?'))) return;
            var reqId = piCancelBtn.getAttribute('data-request-id');
            fetch(CR_API + '?action=cancelRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(reqId) }),
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.ok) {
                    toast((res.error && res.error.message) || '취소 실패', 'error');
                    return;
                }
                toast('요청이 취소되었습니다.');
                setTimeout(function() { location.reload(); }, 800);
            })
            .catch(function() { toast('서버 연결 실패', 'error'); });
        });
    }

    // ─── 비밀번호 변경 (좌측 패널 토글) ───
    var pwToggle = document.getElementById('btnPwToggle');
    var pwPanel = document.getElementById('pwFormPanel');
    var pwCancelBtn = document.getElementById('pwCancel');
    var pwNew = document.getElementById('pwNew');
    var pwConf = document.getElementById('pwConfirm');
    var pwMatch = document.getElementById('pwMatch');

    if (pwToggle && pwPanel) {
        pwToggle.addEventListener('click', function() {
            pwPanel.classList.remove('hidden');
            pwToggle.classList.add('hidden');
        });
        if (pwCancelBtn) pwCancelBtn.addEventListener('click', function() {
            pwPanel.classList.add('hidden');
            pwToggle.classList.remove('hidden');
            document.getElementById('pwCurrent').value = '';
            pwNew.value = '';
            pwConf.value = '';
            pwMatch.classList.add('hidden');
        });
    }

    function checkPwMatch() {
        if (!pwConf.value) { pwMatch.classList.add('hidden'); return; }
        if (pwNew.value === pwConf.value) {
            pwMatch.textContent = '비밀번호가 일치합니다';
            pwMatch.className = 'text-[11px] mt-1 text-emerald-400';
        } else {
            pwMatch.textContent = '비밀번호가 일치하지 않습니다';
            pwMatch.className = 'text-[11px] mt-1 text-amber-500';
        }
    }
    if (pwNew) pwNew.addEventListener('input', checkPwMatch);
    if (pwConf) pwConf.addEventListener('input', checkPwMatch);

    var pwSaveBtn = document.getElementById('pwSave');
    if (pwSaveBtn) {
        pwSaveBtn.addEventListener('click', function() {
            var cur = document.getElementById('pwCurrent').value;
            var nw = pwNew.value;
            var cf = pwConf.value;
            if (!cur || !nw || !cf) { toast('비밀번호를 모두 입력해주세요', 'error'); return; }
            if (nw.length < 8) { toast('새 비밀번호는 8자 이상이어야 합니다', 'error'); return; }
            if (nw !== cf) { toast('새 비밀번호 확인이 일치하지 않습니다', 'error'); return; }
            if (nw === cur) { toast('새 비밀번호가 현재 비밀번호와 같습니다', 'error'); return; }

            pwSaveBtn.disabled = true;
            fetch(ORG_API + '?action=changeMyPassword', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: cur, new_password: nw, new_password_confirm: cf }),
            })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(res) {
                if (!res.ok) { toast(res.data.error || '변경 실패', 'error'); return; }
                toast(res.data.message || '비밀번호가 변경되었습니다');
                document.getElementById('pwCurrent').value = '';
                pwNew.value = '';
                pwConf.value = '';
                pwMatch.classList.add('hidden');
                pwPanel.classList.add('hidden');
                pwToggle.classList.remove('hidden');
            })
            .catch(function() { toast('서버 연결 실패', 'error'); })
            .finally(function() { pwSaveBtn.disabled = false; });
        });
    }

    // ─── Main tab switching ───
    document.querySelectorAll('.main-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = this.dataset.mainTab;
            document.querySelectorAll('.main-tab').forEach(function(t) { t.classList.toggle('active', t.dataset.mainTab === target); });
            document.querySelectorAll('[data-main-panel]').forEach(function(p) { p.classList.toggle('hidden', p.dataset.mainPanel !== target); });
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });

    // ─── 변경요청 이력 로드 ───
    function loadChangeHistory() {
        var histEl = document.getElementById('crHistory');
        var emptyEl = document.getElementById('crEmpty');

        fetch(CR_API + '?action=getMyRequests')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok || !res.data || !res.data.items || res.data.items.length === 0) {
                histEl.innerHTML = '';
                emptyEl.classList.remove('hidden');
                return;
            }
            emptyEl.classList.add('hidden');
            var html = '<div class="space-y-3">';
            res.data.items.forEach(function(item) {
                var statusCls = item.status === '대기' ? 'badge-amber'
                              : item.status === '승인' ? 'badge-green'
                              : 'badge-red';
                var changes = [];
                try { changes = Object.entries(JSON.parse(item.changes_json)); } catch(e) {}

                html += '<div class="p-4 rounded-lg bg-slate-950/50 border border-slate-800">';
                html += '<div class="flex items-center justify-between mb-2">';
                html += '<span class="text-xs text-slate-500">' + escHtml(item.created_at) + '</span>';
                html += '<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full ' + statusCls + '">' + escHtml(item.status) + '</span>';
                html += '</div>';

                changes.forEach(function(pair) {
                    var f = pair[0], v = pair[1];
                    var label = FIELD_LABELS[f] || f;
                    var oldV = v.old || '-';
                    var newV = v.new || '-';
                    if (f === 'gender') {
                        oldV = GENDER_LABELS[oldV] || oldV || '-';
                        newV = GENDER_LABELS[newV] || newV || '-';
                    }
                    html += '<p class="text-sm text-slate-300"><span class="text-slate-500">' + escHtml(label) + ':</span> ' + escHtml(oldV) + ' → ' + escHtml(newV) + '</p>';
                });

                if (item.reason) {
                    html += '<p class="text-xs text-slate-500 mt-1">사유: ' + escHtml(item.reason) + '</p>';
                }
                if (item.status === '반려' && item.reject_reason) {
                    html += '<p class="text-xs text-rose-400 mt-1">반려 사유: ' + escHtml(item.reject_reason) + '</p>';
                }
                if (item.status !== '대기' && item.reviewed_by_name) {
                    html += '<p class="text-xs text-slate-600 mt-1">처리: ' + escHtml(item.reviewed_by_name) + ' (' + escHtml(item.reviewed_at) + ')</p>';
                }
                html += '</div>';
            });
            html += '</div>';
            histEl.innerHTML = html;
        })
        .catch(function() {
            histEl.innerHTML = '';
            emptyEl.classList.remove('hidden');
        });
    }

    loadChangeHistory();
})();
</script>

<?php if ($userId > 0): ?>
<script src="<?= $basePath ?>/assets/js/employee-profile.js"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
