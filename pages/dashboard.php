<?php
/**
 * Zaemit 그룹웨어 - 대시보드 (컨트롤러)
 *
 * 디자인 규칙:
 *  - 포인트 컬러: 보라빛 블루 (primary #4F6AFF) 단 하나
 *  - 그 외 전부 블랙/그레이 계열
 *  - 페이지 bg-slate-950 / 카드 bg-slate-900 + shadow-sm 로 경계 형성 (보더 금지)
 *  - 최소 폰트 text-sm (14px), KPI 숫자 text-4xl, 섹션 제목 text-lg
 *  - 패딩 p-8, 간격 gap-6, 섹션 mb-8, 모서리 rounded-2xl
 *  - 아이콘 사용 최소화 (모달 닫기 X, 캘린더 좌우 chevron 외 사용 금지)
 */
$pageTitle = '대시보드';
$currentPage = 'dashboard';
$suppressAutoTitle = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/dashboard/_data.php';
require_once __DIR__ . '/dashboard/_widgets.php';

if (!function_exists('esc')) {
    function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

// ── 사용자 / 역할 ──
$currentUser   = getCurrentUser() ?? [];
$userName      = $currentUser['name'] ?? '사용자';
$userId        = (int)($currentUser['id'] ?? 0);
$userDeptId    = isset($currentUser['department_id']) ? (int)$currentUser['department_id'] : null;
$userRole      = getCurrentUserRole();
$isAdminRole   = $userRole === 'admin';
$isManagerRole = $userRole === 'manager';



// ── DB 연결 ──
$dbOk = false; $pdo = null;
try {
    $pdo = getDBConnection();
    $dbOk = ($pdo !== null);
} catch (Exception $e) {
    error_log('[Dashboard] DB 연결 실패: ' . $e->getMessage());
}

// ── 데이터 로딩 (_data.php 함수 사용) ──
$annualTotal = 0; $annualUsed = 0; $annualRemain = 0;
$todayClockIn = null; $todayClockOut = null; $todayWorkType = null;
$weekHours = 0; $weekTarget = 40;
$myApprovalStatus = ['진행'=>0,'완료'=>0,'반려'=>0,'기안'=>0];
$pendingForMe = [];
$kpiComp = [];
$scheduleCategories = [];
$scheduleEmployees  = [];
$orgStats = ['total'=>0, 'inOffice'=>0, 'outside'=>0];
$deptName = null;
$hasDept  = ($userDeptId !== null && $userDeptId > 0);
$monthAnniversaries = [];

// ── 위젯 설정 로딩 ──
$widgetSettings = dashLoadWidgetSettings($dbOk ? $pdo : null, $userId, $userRole);
$wVisible = fn(string $id): bool => ($widgetSettings[$id]['visible'] ?? false);

if ($dbOk && $userId > 0) {
    $annualData   = dashGetAnnualLeave($pdo, $userId);
    $annualTotal  = $annualData['total'];
    $annualUsed   = $annualData['used'];
    $annualRemain = $annualData['remain'];

    $attData      = dashGetTodayAttendance($pdo, $userId);
    $todayClockIn  = $attData['clock_in'];
    $todayClockOut = $attData['clock_out'];
    $todayWorkType = $attData['work_type'];

    $weekData  = dashGetWeekHours($pdo, $userId, $weekTarget);
    $weekHours = $weekData['hours'];

    $approvalData     = dashGetApprovalSummary($pdo, $userId);
    $myApprovalStatus = $approvalData['status'];
    $pendingForMe     = $approvalData['pending'];

    $kpiComp = dashGetKpiComparisons($pdo, $userId);
}
$pendingForMeCount = count($pendingForMe);
$lastWeekHours   = $kpiComp['lastWeekHours'] ?? 0;
$yesterdayEvents = $kpiComp['yesterdayEvents'] ?? 0;

$today = date('Y-m-d');
$weekStart = ''; $weekEnd = ''; $weekDays = [];
$todayEvents = []; $todayEventCount = 0;
$todayTasks = [];
if ($dbOk) {
    $weekEvtData     = dashGetWeekEvents($pdo);
    $weekStart       = $weekEvtData['weekStart'];
    $weekEnd         = $weekEvtData['weekEnd'];
    $today           = $weekEvtData['today'];
    $weekDays        = $weekEvtData['days'];
    $todayEvents     = $weekEvtData['todayEvents'];
    $todayEventCount = $weekEvtData['todayEventCount'];

    if ($userId > 0) {
        $todayTasks = dashGetTodayTasks($pdo, $userId);
    }

    $modalData          = dashGetScheduleModalData($pdo);
    $scheduleCategories = $modalData['categories'];
    $scheduleEmployees  = $modalData['employees'];
}

$notices = []; $freePosts = []; $archivePosts = [];
if ($wVisible('board') && $dbOk) {
    $notices      = dashGetBoardPosts($pdo, 'notice', 5);
    $freePosts    = dashGetBoardPosts($pdo, 'free', 5);
    $archivePosts = dashGetBoardPosts($pdo, 'archive', 5);
}

if ($dbOk && $hasDept && $wVisible('dept_status')) {
    $orgStats           = dashGetDeptStatus($pdo, $userDeptId);
    $deptName           = $orgStats['deptName'];
    $monthAnniversaries = dashGetAnniversaries($pdo, $userDeptId);
}

// ── 파생 변수 ──
$roleLabel = $isAdminRole ? '관리자' : ($isManagerRole ? getOrgHeadTitle('department') : '구성원');
$weekPct   = $weekTarget > 0 ? min(100, (int)round($weekHours / $weekTarget * 100)) : 0;
$annualPct = $annualTotal > 0 ? min(100, (int)round($annualUsed / $annualTotal * 100)) : 0;
$fmtNum = function (float $n): string {
    return rtrim(rtrim(number_format($n, 1), '0'), '.');
};
// Tier별 카드 스타일 — 크기 차이 = 중요도 차이
$cardClsT1 = 'rounded-xl border border-slate-800/60 bg-gradient-to-b from-slate-900/50 to-slate-900/30 backdrop-blur-xl shadow-sm p-4';
$cardClsT2 = 'rounded-lg border border-slate-800/60 bg-slate-900/30 backdrop-blur-sm shadow-sm px-3.5 py-3';
$cardClsT2Interactive = 'rounded-lg border border-slate-800/60 bg-slate-900/30 backdrop-blur-sm shadow-sm px-3.5 py-3 hover:border-slate-700 hover:bg-slate-900/50 transition-colors';
$cardClsT3 = 'rounded-lg border border-slate-800/60 bg-slate-900/20 p-3.5';
$cardCls = $cardClsT3;

// ── 동적 그리드 span 계산 ──
$midVisible = array_filter(['today_tasks', 'kpi'], $wVisible);

$bottomVisible = array_filter(['week_schedule', 'board', 'dept_status'], $wVisible);
$bottomCount   = count($bottomVisible);
$bottomSpan    = match($bottomCount) {
    1       => 'col-span-12',
    2       => 'col-span-6',
    default => 'col-span-4',
};

// 위젯 설정 정보 (JS에서 모달 렌더링용)
$widgetListForJs = [];
foreach ($widgetSettings as $id => $w) {
    $widgetListForJs[] = [
        'id'      => $id,
        'label'   => $w['label'],
        'fixed'   => $w['fixed'],
        'visible' => $w['visible'],
    ];
}
?>

<!-- 메인 컨텐츠 영역 -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="relative text-base text-slate-100 antialiased overflow-hidden" style="min-height: calc(100vh - 3.5rem); background: radial-gradient(ellipse 80% 50% at 50% -10%, rgba(79, 106, 255, 0.15), transparent 60%), var(--zm-surface-0);">
<div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(var(--zm-text-subtle) 1px, transparent 1px), linear-gradient(90deg, var(--zm-text-subtle) 1px, transparent 1px); background-size: 60px 60px;"></div>
<div class="relative px-6 py-5">

<?php require __DIR__ . '/dashboard/_widget_welcome.php'; ?>

<?php if ($wVisible('kpi')): ?>
<!-- ===== KPI 가로 4단 ===== -->
<?php require __DIR__ . '/dashboard/_widget_kpi.php'; ?>
<?php endif; ?>

<?php if ($wVisible('today_tasks')): ?>
<!-- ===== 오늘 할 일 (전체 폭) ===== -->
<?php require __DIR__ . '/dashboard/_widget_today_tasks.php'; ?>
<?php endif; ?>

<?php if (!empty($bottomVisible)): ?>
<!-- ===== D+E+F+G. 주간 일정 + 게시판 + 근태 + 부서현황 ===== -->
<section class="grid grid-cols-12 gap-4">
    <?php if ($wVisible('week_schedule')): ?>
        <?php require __DIR__ . '/dashboard/_widget_week_schedule.php'; ?>
    <?php endif; ?>
    <?php if ($wVisible('board')): ?>
        <?php require __DIR__ . '/dashboard/_widget_board.php'; ?>
    <?php endif; ?>
    <?php if ($wVisible('dept_status')): ?>
        <?php require __DIR__ . '/dashboard/_widget_dept_status.php'; ?>
    <?php endif; ?>
</section>
<?php endif; ?>

</div>
</main>
</div>

<?php require __DIR__ . '/dashboard/_modals.php'; ?>

<!-- JS: PHP→JS 데이터 브릿지 + 외부 스크립트 -->
<script>
var DASHBOARD_CONFIG = <?= json_encode([
    'attApi'       => $basePath . '/api/attendance.php',
    'scheduleApi'  => $basePath . '/api/schedule.php',
    'dashApi'      => $basePath . '/api/dashboard.php',
    'currentEmpId' => $userId,
    'categories'   => $scheduleCategories,
    'employees'    => $scheduleEmployees,
    'hasDB'        => $dbOk,
    'widgets'      => $widgetListForJs,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="<?= $basePath ?>/assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
