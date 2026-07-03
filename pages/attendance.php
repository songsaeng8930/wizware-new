<?php
$pageTitle = '근태 현황';
$currentPage = 'attendance';
$suppressAutoTitle = true; // 페이지 안에서 이미 h1 제목을 렌더하므로 헤더의 자동주입을 막는다
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/hr_codes.php';

if (!function_exists('esc')) {
    function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

// 현재 연/월 (쿼리 파라미터 또는 기본값)
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$activeTab = $_GET['tab'] ?? 'calendar';

// ── 이번 달 본인 근태 기록을 PHP 렌더 시점에 직접 조회 ────────────────────
//    서버 측 fetch 후 렌더 깜빡임을 피하기 위해, 캘린더에 표시할 요약은
//    페이지 초기 로딩에 함께 쓸 수 있도록 PHP 배열로 준비한다.
$myEmpId      = (int)($_SESSION['user_id'] ?? 0);
$monthRecs    = []; // day => ['clock_in','clock_out','work_plan','leave_note','work_type','note']
$monthOutside = []; // day => [{destination, purpose}, ...]
$monthLeaves  = []; // day => [leave_type, ...]
$monthNotes   = []; // 특이사항 카드에 나열할 [{record_date, leave_note}]
$attSchema    = ['work_plan' => false, 'leave_note' => false];

try {
    $_pdo = getDBConnection();
    if ($_pdo && $myEmpId > 0) {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        try { $_pdo->query('SELECT work_plan FROM attendance_records LIMIT 1'); $attSchema['work_plan'] = true; } catch (PDOException $e) {}
        try { $_pdo->query('SELECT leave_note FROM attendance_records LIMIT 1'); $attSchema['leave_note'] = true; } catch (PDOException $e) {}

        $cols = 'record_date, clock_in, clock_out, work_type, note'
              . ($attSchema['work_plan'] ? ', work_plan' : '')
              . ($attSchema['leave_note'] ? ', leave_note' : '');
        try {
            $q = $_pdo->prepare("SELECT {$cols} FROM attendance_records
                                  WHERE employee_id = ? AND record_date BETWEEN ? AND ?");
            $q->execute([$myEmpId, $start, $end]);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $d = (int)substr($r['record_date'], 8, 2);
                $monthRecs[$d] = $r;
                if ($attSchema['leave_note'] && !empty($r['leave_note'])) {
                    $monthNotes[] = $r;
                }
            }
        } catch (PDOException $e) { /* 테이블 없음 허용 */ }

        // 외근/출장 기록
        try {
            $q2 = $_pdo->prepare("SELECT work_date, destination, purpose FROM outside_work_records
                                   WHERE employee_id = ? AND work_date BETWEEN ? AND ?");
            $q2->execute([$myEmpId, $start, $end]);
            foreach ($q2->fetchAll(PDO::FETCH_ASSOC) as $o) {
                $d = (int)substr($o['work_date'], 8, 2);
                $monthOutside[$d][] = $o;
            }
        } catch (PDOException $e) {}

        // 승인된 휴가(각 날짜 단위로 펼침)
        try {
            $q3 = $_pdo->prepare("SELECT start_date, end_date, leave_type FROM leave_requests
                                   WHERE employee_id = ? AND status = '승인'
                                     AND NOT (end_date < ? OR start_date > ?)");
            $q3->execute([$myEmpId, $start, $end]);
            foreach ($q3->fetchAll(PDO::FETCH_ASSOC) as $lr) {
                $sTs = strtotime($lr['start_date']);
                $eTs = strtotime($lr['end_date']);
                $ym  = sprintf('%04d-%02d', $year, $month);
                for ($t = $sTs; $t <= $eTs; $t += 86400) {
                    if (date('Y-m', $t) !== $ym) continue;
                    $d = (int)date('j', $t);
                    $monthLeaves[$d][] = $lr['leave_type'] ?: 'AL';
                }
            }
        } catch (PDOException $e) {}
    }
} catch (PDOException $e) { error_log('[attendance page] ' . $e->getMessage()); }

if (!function_exists('attLeaveLabel')) {
    function attLeaveLabel(string $t): string {
        return match ($t) {
            'AL'  => '연차',   'HAM' => '오전반차', 'HAP' => '오후반차',
            'SL'  => '병가',   'FL'  => '경조사',
            default => $t ?: '휴가',
        };
    }
}

// ── 근무 신청 내역 (approval_documents) 조회 ────────────────────────────
//    본인 기안 문서 중 근태 관련 doc_type 만. 필터는 쿼리 파라미터 ?st= / ?ty=
$reqFilterStatus = $_GET['st'] ?? '';       // '', 'open', 'approved', 'rejected'
$reqFilterType   = $_GET['ty'] ?? '';       // '', '야근' ...

$workTypes = [];
try {
    if (isset($_pdo) && $_pdo) {
        $workTypes = $_pdo->query("
            SELECT ci.code, ci.name FROM common_code_items ci
            JOIN common_code_groups cg ON ci.group_id = cg.id
            WHERE cg.module = 'attendance' AND cg.name = '근무유형' AND ci.is_active = 1 AND ci.code != 'NRM'
            ORDER BY ci.sort_order
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {}
if (empty($workTypes)) {
    $workTypes = [
        ['code'=>'WFH','name'=>'재택근무'],['code'=>'OUT','name'=>'외근'],
        ['code'=>'BIZ','name'=>'출장'],['code'=>'OT','name'=>'야근'],['code'=>'HOL','name'=>'휴일근무'],
    ];
}
$workTypeNames = array_column($workTypes, 'name');

// 근무유형 코드 → 한글 이름 (배지 표시용, NRM 포함)
$workTypeNameMap = ['NRM' => '정상근무'];
foreach ($workTypes as $wt) { $workTypeNameMap[$wt['code']] = $wt['name']; }
$workTypeNameMap += ['WFH' => '재택근무', 'OUT' => '외근', 'BIZ' => '출장', 'OT' => '야근', 'HOL' => '휴일근무'];

// 휴가유형 (공통코드 → fallback)
$leaveTypeOptions = getCommonCodeOptions('attendance', '휴가유형');
if ($leaveTypeOptions === '') {
    $leaveTypeOptions = '<option>연차</option><option>반차(오전)</option><option>반차(오후)</option><option>병가</option><option>경조사</option><option>공가</option>';
}
$docTypeMap = [
    '재택근무' => '재택근무신청서',
    '외근' => '외근신청서',
    '출장' => '출장신청서',
    '야근' => '야근신청서',
    '휴일근무' => '휴일근무신청서',
];
$workDocTypes = array_values(array_map(fn($n) => $docTypeMap[$n] ?? $n.'신청서', $workTypeNames));
$workDocTypes[] = '휴가신청서';
$reqDocs = [];
$reqCounts = ['total' => 0, 'open' => 0, 'approved' => 0, 'rejected' => 0];
try {
    if (isset($_pdo) && $_pdo && $myEmpId > 0) {
        $typeMarkers = implode(',', array_fill(0, count($workDocTypes), '?'));
        $params = $workDocTypes;
        array_unshift($params, $myEmpId);
        $sql = "SELECT id, doc_number, title, doc_type, status, draft_date, complete_date, created_at
                FROM approval_documents
                WHERE drafter_id = ? AND doc_type IN ({$typeMarkers})
                ORDER BY draft_date DESC, id DESC LIMIT 100";
        $q = $_pdo->prepare($sql);
        $q->execute($params);
        $reqDocs = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reqDocs as $r) {
            $reqCounts['total']++;
            $st = $r['status'] ?? '';
            if (in_array($st, ['대기','진행','기안'], true)) $reqCounts['open']++;
            elseif ($st === '승인')                          $reqCounts['approved']++;
            elseif (in_array($st, ['반려','회수'], true))     $reqCounts['rejected']++;
        }
    }
} catch (PDOException $e) { /* approval 미설치 환경 */ }

// 샘플 폴백
if (empty($reqDocs)) {
    $reqDocs = [
        ['id'=>0, 'doc_number'=>'데이터 없음', 'title'=>'샘플 · 2026-04-15 야근 신청', 'doc_type'=>'야근신청서', 'status'=>'대기', 'draft_date'=>date('Y-m-d'), 'complete_date'=>null, 'created_at'=>date('Y-m-d H:i:s')],
    ];
    $reqCounts = ['total' => 1, 'open' => 1, 'approved' => 0, 'rejected' => 0];
}

// 상태별 색상 (뱃지)
function reqStatusClass(string $s): array {
    return match ($s) {
        '승인'     => ['label'=>'승인', 'bg'=>'bg-emerald-100', 'fg'=>'text-emerald-700'],
        '반려'     => ['label'=>'반려', 'bg'=>'bg-rose-100',    'fg'=>'text-rose-700'],
        '회수'     => ['label'=>'회수', 'bg'=>'bg-slate-700',   'fg'=>'text-slate-200'],
        '진행'     => ['label'=>'진행', 'bg'=>'bg-blue-100',    'fg'=>'text-blue-700'],
        '대기'     => ['label'=>'대기', 'bg'=>'bg-amber-100',   'fg'=>'text-amber-700'],
        '기안'     => ['label'=>'기안', 'bg'=>'bg-amber-100',   'fg'=>'text-amber-700'],
        default    => ['label'=> $s ?: '?', 'bg'=>'bg-slate-800','fg'=>'text-slate-300'],
    };
}
function reqTypeShort(string $t): string {
    return str_replace(['신청서'], '', $t) ?: $t;
}

// 필터 적용 (클라이언트 측에 데이터 모두 내려주고 JS로 필터링도 가능하지만, 현재는 PHP 사이드)
$reqDocsView = array_values(array_filter($reqDocs, function ($r) use ($reqFilterStatus, $reqFilterType) {
    $s = $r['status'] ?? '';
    if ($reqFilterStatus === 'open'     && !in_array($s, ['대기','진행','기안'], true)) return false;
    if ($reqFilterStatus === 'approved' && $s !== '승인')                                 return false;
    if ($reqFilterStatus === 'rejected' && !in_array($s, ['반려','회수'], true))          return false;
    if ($reqFilterType !== '' && mb_strpos($r['doc_type'] ?? '', $reqFilterType) === false) return false;
    return true;
}));

// ── 휴가 요약(annual_leave) + 사용 내역(leave_requests) 조회 ──────────────
$thisYear   = (int)date('Y');
$leaveBalance = ['total_days' => 15.0, 'used_days' => 0.0]; // 기본값
$leaveRequests = [];
$leaveByMonth  = array_fill(1, 12, 0.0); // 1~12 월별 사용일 합
$leaveByType   = ['AL'=>0, 'HAM'=>0, 'HAP'=>0, 'SL'=>0, 'FL'=>0];
try {
    if (isset($_pdo) && $_pdo && $myEmpId > 0) {
        try {
            $q = $_pdo->prepare("SELECT total_days, used_days FROM annual_leave WHERE employee_id=? AND year=?");
            $q->execute([$myEmpId, $thisYear]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row) $leaveBalance = $row;
        } catch (PDOException $e) {}

        try {
            // 신규 컬럼이 아직 없는 레거시 DB도 지원
            $extraCols = '';
            $hasExtra = false;
            try {
                $chk = $_pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'approved_at'");
                if ($chk && $chk->fetch()) {
                    $extraCols = ', lr.approved_at, lr.approver_id, lr.penalty_flag, lr.penalty_reason, appr.name AS approver_name';
                    $hasExtra = true;
                }
            } catch (PDOException $e) {}

            $sql = "SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.days_used, lr.reason, lr.status, lr.created_at{$extraCols}
                    FROM leave_requests lr"
                    . ($hasExtra ? " LEFT JOIN employees appr ON appr.id = lr.approver_id" : '') .
                   " WHERE lr.employee_id=? AND YEAR(lr.start_date)=?
                    ORDER BY lr.start_date DESC, lr.id DESC LIMIT 100";
            $q = $_pdo->prepare($sql);
            $q->execute([$myEmpId, $thisYear]);
            $leaveRequests = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($leaveRequests as $lr) {
                if ($lr['status'] !== '승인') continue;
                $m = (int)substr($lr['start_date'], 5, 2);
                $d = (float)$lr['days_used'];
                if (isset($leaveByMonth[$m])) $leaveByMonth[$m] += $d;
                $t = $lr['leave_type'] ?? 'AL';
                if (isset($leaveByType[$t])) $leaveByType[$t] += $d;
            }
        } catch (PDOException $e) {}
    }
} catch (PDOException $e) {}

$leaveTotal  = (float)$leaveBalance['total_days'];
$leaveUsed   = (float)$leaveBalance['used_days'];
$leaveRemain = max(0, $leaveTotal - $leaveUsed);
$leaveRate   = $leaveTotal > 0 ? min(100, round($leaveUsed / $leaveTotal * 100)) : 0;

// 캘린더 상세 모달용으로 day => 기록 블록을 만들어 JS 전역으로 내려준다.
$monthDetailJs = [];
$_daysInCur = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
for ($d = 1; $d <= $_daysInCur; $d++) {
    $r = $monthRecs[$d] ?? null;
    $o = $monthOutside[$d] ?? [];
    $l = $monthLeaves[$d] ?? [];
    if (!$r && !$o && !$l) continue;
    $monthDetailJs[$d] = [
        'date'       => sprintf('%04d-%02d-%02d', $year, $month, $d),
        'clock_in'   => $r['clock_in']  ?? null,
        'clock_out'  => $r['clock_out'] ?? null,
        'work_plan'  => $r['work_plan'] ?? null,
        'leave_note' => $r['leave_note'] ?? null,
        'work_type'  => $r['work_type'] ?? null,
        'note'       => $r['note'] ?? null,
        'outside'    => array_values(array_map(fn($x) => [
            'destination' => $x['destination'] ?? '',
            'purpose'     => $x['purpose']     ?? '',
        ], $o)),
        'leaves'     => array_values(array_map(fn($t) => [
            'type'  => $t,
            'label' => attLeaveLabel($t),
        ], $l)),
    ];
}

// 이전/다음 달 계산
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// 달력 정보
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDayOfMonth);
$startDayOfWeek = (int)date('w', $firstDayOfMonth); // 0=Sun
$today = (int)date('j');
$currentMonth = (int)date('m');
$currentYear = (int)date('Y');
$currentDow  = (int)date('w'); // 0=Sun
$monthNames = ['', '1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'];
$dowKor     = ['일','월','화','수','목','금','토'];

// 더미 데이터: 휴가 생성 내역
$vacationCreated = [
    ['date' => '2025-09-16', 'change' => '1일', 'accumulated' => '1일', 'content' => '테스트용 연차추가입니다.', 'note' => 'o'],
];
?>

<!-- 메인 컨텐츠 (다크 테마 · 브랜드 보라가 배경에 스며든 딥 네이비) -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="relative text-slate-100 antialiased overflow-hidden" style="min-height: calc(100vh - 3.5rem); background: radial-gradient(ellipse 80% 50% at 50% -10%, rgba(79, 106, 255, 0.15), transparent 60%), var(--zm-surface-0);">
<!-- 그리드 패턴 오버레이 (subtle) -->
<div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(var(--zm-text-subtle) 1px, transparent 1px), linear-gradient(90deg, var(--zm-text-subtle) 1px, transparent 1px); background-size: 60px 60px;"></div>
<div class="relative px-8 py-8 space-y-6">

<!-- Page Header -->
<div class="flex items-start justify-between gap-4">
    <div class="space-y-1">
        <h2 class="text-lg font-bold text-slate-100">근태 현황</h2>
        <p class="text-sm text-slate-500"><?= $year ?>년 <?= $monthNames[$month] ?> · 나의 출퇴근 및 휴가 기록</p>
    </div>
    <?php if (in_array(getCurrentUserRole(), ['admin', 'manager'], true)): ?>
    <div class="flex items-center gap-2">
        <a href="dept_attendance.php" class="btn btn-secondary">
            <i data-lucide="users" class="w-4 h-4"></i>부서 근태
        </a>
        <a href="att_manage.php" class="btn btn-secondary">
            <i data-lucide="settings" class="w-4 h-4"></i>근태 관리
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Clock Card (shadcn Card) -->
<div class="rounded-xl border border-slate-800/80 bg-gradient-to-b from-slate-900/60 to-slate-900/30 backdrop-blur-xl shadow-lg shadow-black/20">
    <div class="flex items-center justify-between p-6 pb-4">
        <div class="flex items-center gap-2">
            <i data-lucide="calendar-check" class="w-4 h-4 text-slate-400"></i>
            <h2 class="text-lg font-semibold tracking-tight tabular-nums">
                <?= $currentYear ?>년 <?= $currentMonth ?>월 <?= $today ?>일
                <span class="text-slate-500 font-normal ml-1">(<?= $dowKor[$currentDow] ?>)</span>
            </h2>
        </div>
        <span class="text-sm text-slate-500">오늘의 근태</span>
    </div>
    <div class="px-6 pb-6">
        <div class="flex items-center justify-center gap-6 py-6 bg-slate-900 rounded-md">
            <button id="attClockInBtn" onclick="openClockInModal()"
                    class="inline-flex items-center justify-center rounded-md text-base font-medium transition-colors h-12 px-12 bg-primary text-white hover:bg-primary/90 shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 disabled:opacity-50 disabled:pointer-events-none disabled:shadow-none">
                출근
            </button>
            <div class="flex flex-col gap-1.5 min-w-[140px] text-center">
                <div class="text-sm">
                    <span class="text-slate-500">출근</span>
                    <span id="attClockInTime" class="ml-2 font-mono font-semibold text-slate-400 tabular-nums">--:--</span>
                </div>
                <div class="text-sm">
                    <span class="text-slate-500">퇴근</span>
                    <span id="attClockOutTime" class="ml-2 font-mono font-semibold text-slate-400 tabular-nums">--:--</span>
                </div>
            </div>
            <button id="attClockOutBtn" onclick="openClockOutModal()" disabled
                    class="inline-flex items-center justify-center rounded-md text-base font-medium transition-colors h-12 px-12 bg-primary text-white hover:bg-primary/90 shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 disabled:opacity-50 disabled:pointer-events-none disabled:shadow-none">
                퇴근
            </button>
        </div>
    </div>
</div>

<!-- KPI Grid (shadcn Cards) -->
<div class="grid grid-cols-4 gap-4">
    <div class="rounded-xl border border-slate-800/80 bg-gradient-to-b from-slate-900/60 to-slate-900/30 backdrop-blur-xl shadow-lg shadow-black/20">
        <div class="flex flex-row items-center justify-between p-6 pb-2">
            <p class="text-sm font-medium text-slate-500">이번 달 근무</p>
            <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
        </div>
        <div class="px-6 pb-6">
            <div class="text-2xl zm-stat">0<span class="text-sm font-normal text-slate-500 ml-1">시간</span></div>
            <div class="mt-2 h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-primary" style="width: 0%"></div>
            </div>
            <p class="text-sm text-slate-500 mt-2">목표 160h · 0% 달성</p>
        </div>
    </div>
    <div class="rounded-xl border border-slate-800/80 bg-gradient-to-b from-slate-900/60 to-slate-900/30 backdrop-blur-xl shadow-lg shadow-black/20">
        <div class="flex flex-row items-center justify-between p-6 pb-2">
            <p class="text-sm font-medium text-slate-500">잔여 연차</p>
            <i data-lucide="palmtree" class="w-4 h-4 text-slate-400"></i>
        </div>
        <div class="px-6 pb-6">
            <div class="text-2xl zm-stat">17<span class="text-sm font-normal text-slate-500 ml-1">일</span></div>
            <div class="mt-2 h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-primary" style="width: 0%"></div>
            </div>
            <p class="text-sm text-slate-500 mt-2">17일 중 0일 사용</p>
        </div>
    </div>
    <div class="rounded-xl border border-slate-800/80 bg-gradient-to-b from-slate-900/60 to-slate-900/30 backdrop-blur-xl shadow-lg shadow-black/20">
        <div class="flex flex-row items-center justify-between p-6 pb-2">
            <p class="text-sm font-medium text-slate-500">이번 주 근무</p>
            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400"></i>
        </div>
        <div class="px-6 pb-6">
            <div class="text-2xl zm-stat">0<span class="text-sm font-normal text-slate-500 ml-1">시간</span></div>
            <div class="mt-2 h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-primary" style="width: 0%"></div>
            </div>
            <p class="text-sm text-slate-500 mt-2">목표 40h · 0% 달성</p>
        </div>
    </div>
    <div class="rounded-xl border border-slate-800/80 bg-gradient-to-b from-slate-900/60 to-slate-900/30 backdrop-blur-xl shadow-lg shadow-black/20">
        <div class="flex flex-row items-center justify-between p-6 pb-2">
            <p class="text-sm font-medium text-slate-500">이번 달 특이사항</p>
            <i data-lucide="message-square-text" class="w-4 h-4 text-slate-400"></i>
        </div>
        <div class="px-6 pb-6">
            <div class="text-2xl zm-stat"><?= count($monthNotes) ?><span class="text-sm font-normal text-slate-500 ml-1">건</span></div>
            <?php if (empty($monthNotes)): ?>
                <p class="text-sm text-slate-500 mt-3">퇴근 시 입력한 특이사항이 없습니다.</p>
            <?php else: ?>
                <ul class="mt-3 space-y-1.5 max-h-[88px] overflow-y-auto pr-1">
                    <?php foreach (array_slice($monthNotes, 0, 5) as $n): ?>
                    <li class="flex gap-2 text-xs">
                        <span class="text-slate-500 font-mono flex-shrink-0 tabular-nums"><?= esc(substr((string)$n['record_date'], 5)) ?></span>
                        <span class="text-slate-300 line-clamp-1" title="<?= esc((string)$n['leave_note']) ?>"><?= esc(mb_substr((string)$n['leave_note'], 0, 40)) ?><?= mb_strlen((string)$n['leave_note']) > 40 ? '…' : '' ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($monthNotes) > 5): ?>
                    <p class="text-[11px] text-slate-500 mt-2">+ <?= count($monthNotes) - 5 ?>건 더</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabs (shadcn Tabs) -->
<div class="space-y-4">
<div role="tablist" class="inline-flex h-10 items-center justify-center rounded-md bg-slate-800 p-1 text-slate-500">
    <a href="?tab=calendar&year=<?= $year ?>&month=<?= $month ?>" role="tab"
       class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2 <?= $activeTab === 'calendar' ? 'bg-slate-800 text-slate-100 shadow-sm' : 'hover:text-slate-300' ?>">
        월간 캘린더
    </a>
    <a href="?tab=request&year=<?= $year ?>&month=<?= $month ?>" role="tab"
       class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2 <?= $activeTab === 'request' ? 'bg-slate-800 text-slate-100 shadow-sm' : 'hover:text-slate-300' ?>">
        근무 신청 내역
    </a>
    <a href="?tab=vacation&year=<?= $year ?>&month=<?= $month ?>" role="tab"
       class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2 <?= $activeTab === 'vacation' ? 'bg-slate-800 text-slate-100 shadow-sm' : 'hover:text-slate-300' ?>">
        나의 휴가 내역
    </a>
</div>

<!-- Tab Content (shadcn Card) -->
<div class="rounded-xl border border-slate-800/80 bg-gradient-to-b from-slate-900/60 to-slate-900/30 backdrop-blur-xl shadow-lg shadow-black/20 p-6">

<?php if ($activeTab === 'calendar'): ?>
<!-- ===== Calendar · gap 기반 그리드 (단면 border 제거, 중복 해소) ===== -->
<?php
$totalCells = $startDayOfWeek + $daysInMonth;
$totalRows = (int)ceil($totalCells / 7);
?>
<!-- Calendar Month Navigator -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-1">
        <a href="?tab=<?= $activeTab ?>&year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="inline-flex items-center justify-center h-8 w-8 rounded-md transition-colors hover:bg-slate-800 text-slate-400" aria-label="이전 달"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
        <h3 class="text-base font-semibold tracking-tight tabular-nums px-2"><?= $year ?>년 <?= $monthNames[$month] ?></h3>
        <a href="?tab=<?= $activeTab ?>&year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="inline-flex items-center justify-center h-8 w-8 rounded-md transition-colors hover:bg-slate-800 text-slate-400" aria-label="다음 달"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
    </div>
    <a href="?tab=<?= $activeTab ?>&year=<?= $currentYear ?>&month=<?= $currentMonth ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-8 px-3 hover:bg-slate-800 text-slate-300">
        오늘
    </a>
</div>
<div class="rounded-xl overflow-hidden bg-slate-800">
    <!-- Weekday header -->
    <div class="grid grid-cols-7 gap-px bg-slate-800">
        <?php foreach (['일','월','화','수','목','금','토'] as $i => $dn): ?>
            <div class="py-3 text-center text-sm font-semibold bg-slate-900 <?= ($i === 0 || $i === 6) ? 'text-slate-500' : 'text-slate-300' ?>"><?= $dn ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Days grid · gap-px + 셀 배경으로 구분선 효과 -->
    <div class="grid grid-cols-7 gap-px bg-slate-800">
        <?php // 월초 빈 셀
        for ($i = 0; $i < $startDayOfWeek; $i++): ?>
            <div class="min-h-[120px] p-2 bg-slate-950/80"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++):
            $cellIdx = $startDayOfWeek + $day - 1;
            $dow = $cellIdx % 7;
            $isWeekend = ($dow === 0 || $dow === 6);
            $isToday = ($day === $today && $month === $currentMonth && $year === $currentYear);
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $rec    = $monthRecs[$day]    ?? null;
            $outs   = $monthOutside[$day] ?? [];
            $leaves = $monthLeaves[$day]  ?? [];
            $hasAny = $rec || $outs || $leaves;
            $noteText = $rec['leave_note'] ?? '';
            $planText = $rec['work_plan']  ?? '';
        ?>
        <div class="att-day-cell min-h-[120px] p-2 <?= $isWeekend ? 'bg-slate-950/60' : 'bg-slate-950/40' ?> hover:bg-slate-900/70 transition-colors" data-date="<?= $dateStr ?>">
            <div class="mb-1.5 flex items-center justify-between">
                <?php if ($isToday): ?>
                    <span class="inline-flex items-center justify-center h-6 min-w-[24px] px-1.5 rounded-full bg-primary text-white text-xs font-semibold tabular-nums shadow-lg shadow-primary/40"><?= $day ?></span>
                <?php else: ?>
                    <span class="inline-block px-1.5 text-sm font-medium tabular-nums <?= $isWeekend ? 'text-slate-400' : 'text-slate-300' ?>"><?= $day ?></span>
                <?php endif; ?>
                <button type="button"
                        class="att-more inline-flex items-center justify-center w-5 h-5 rounded text-slate-500 hover:text-gray-900 hover:bg-gray-100"
                        data-date="<?= $dateStr ?>" title="근무 유형 신청">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>

            <?php if ($hasAny): ?>
            <?php /* 클릭 시 일자 상세 모달을 여는 영역. 버블링으로 + 버튼 / 드롭다운과 충돌하지 않도록 data 속성으로 분리. */ ?>
            <div class="att-day-summary cursor-pointer rounded -mx-0.5 px-0.5 py-0.5 hover:bg-slate-800/50 transition-colors"
                 data-day-detail="<?= $day ?>" role="button" tabindex="0"
                 title="클릭하여 상세 보기">

                <?php if ($rec && ($rec['clock_in'] || $rec['clock_out'])): ?>
                    <div class="flex items-center gap-1 text-[11px] font-mono text-slate-300 mb-1 tabular-nums">
                        <?php if (!empty($rec['clock_in'])): ?>
                            <span class="text-emerald-400">↑<?= esc(substr($rec['clock_in'], 0, 5)) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($rec['clock_out'])): ?>
                            <span class="text-primary">↓<?= esc(substr($rec['clock_out'], 0, 5)) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap gap-1">
                    <?php if (!empty($rec['work_type'])): ?>
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium bg-blue-100 text-blue-700"><?= esc($workTypeNameMap[$rec['work_type']] ?? $rec['work_type']) ?></span>
                    <?php endif; ?>
                    <?php foreach ($outs as $o): ?>
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium bg-amber-100 text-amber-700"
                              title="<?= esc(($o['destination'] ?? '') . ($o['purpose'] ? ' · '.$o['purpose'] : '')) ?>">외근</span>
                    <?php endforeach; ?>
                    <?php foreach ($leaves as $lt): ?>
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium bg-violet-100 text-violet-700"><?= esc(attLeaveLabel($lt)) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($noteText !== ''): ?>
                    <p class="text-[10px] text-slate-400 mt-1 line-clamp-2">📝 <?= esc(mb_substr($noteText, 0, 40)) ?><?= mb_strlen($noteText) > 40 ? '…' : '' ?></p>
                <?php elseif ($planText !== ''): ?>
                    <p class="text-[10px] text-slate-500 mt-1 line-clamp-2">🗒 <?= esc(mb_substr($planText, 0, 40)) ?><?= mb_strlen($planText) > 40 ? '…' : '' ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php // + 버튼 클릭 시 열리는 빠른 신청 드롭다운 ?>
            <div class="att-more-menu hidden mt-1 flex flex-wrap gap-1" data-for="<?= $dateStr ?>">
                <?php
                    $weekdayTypes = array_values(array_filter($workTypeNames, fn($n) => $n !== '휴일근무'));
                    $weekdayTypes[] = '휴가';
                    $types = $isWeekend ? ['휴일근무'] : $weekdayTypes;
                ?>
                <?php foreach ($types as $t): ?>
                    <button type="button" class="att-btn inline-flex items-center justify-center rounded px-1.5 py-0.5 text-[11px] font-medium transition-colors bg-slate-800/80 hover:bg-gray-100 hover:border-gray-400 text-slate-300 hover:text-gray-900"
                            data-type="<?= $t ?>" data-date="<?= $dateStr ?>"><?= $t ?></button>
                <?php endforeach; ?>
                <?php /* 기타 · 출퇴근 미기록/철회 등의 특이사항만 단독 저장 (결재 절차 없음) */ ?>
                <button type="button"
                        class="att-other-btn inline-flex items-center justify-center rounded px-1.5 py-0.5 text-[11px] font-medium transition-colors bg-slate-800/80 hover:bg-amber-500 text-slate-300 hover:text-white"
                        data-date="<?= $dateStr ?>"
                        title="출퇴근 미기록·철회 등 특이사항 입력">기타</button>
            </div>
        </div>
        <?php endfor; ?>

        <?php // 월말 빈 셀
        $lastDow = ($startDayOfWeek + $daysInMonth - 1) % 7;
        for ($i = $lastDow + 1; $i < 7; $i++): ?>
            <div class="min-h-[120px] p-2 bg-slate-950/80"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- Legend -->
<div class="mt-4 flex items-center gap-4 text-sm">
    <span class="text-slate-500">상태</span>
    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-slate-400"></span><span class="text-slate-400">대기</span></span>
    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-primary"></span><span class="text-slate-400">승인</span></span>
    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-red-500"></span><span class="text-slate-400">반려</span></span>
    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span><span class="text-slate-400">협의 필요</span></span>
</div>

<?php elseif ($activeTab === 'request'): ?>
<!-- ===== 근무 신청 내역 · 재기획 ===== -->
<div class="space-y-5">

    <!-- KPI 3카드 -->
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl border border-slate-800/80 bg-slate-900/40 p-4">
            <p class="text-xs font-medium text-slate-400">진행 중</p>
            <p class="mt-1 text-2xl zm-stat text-primary"><?= $reqCounts['open'] ?><span class="ml-1 text-xs font-normal text-slate-500">건</span></p>
            <p class="text-[11px] text-slate-500 mt-0.5">대기 · 진행</p>
        </div>
        <div class="rounded-xl border border-slate-800/80 bg-slate-900/40 p-4">
            <p class="text-xs font-medium text-slate-400">승인됨</p>
            <p class="mt-1 text-2xl zm-stat text-emerald-400"><?= $reqCounts['approved'] ?><span class="ml-1 text-xs font-normal text-slate-500">건</span></p>
            <p class="text-[11px] text-slate-500 mt-0.5">이번 달 · 누적</p>
        </div>
        <div class="rounded-xl border border-slate-800/80 bg-slate-900/40 p-4">
            <p class="text-xs font-medium text-slate-400">반려 · 회수</p>
            <p class="mt-1 text-2xl zm-stat text-rose-400"><?= $reqCounts['rejected'] ?><span class="ml-1 text-xs font-normal text-slate-500">건</span></p>
            <p class="text-[11px] text-slate-500 mt-0.5">재작성 권장</p>
        </div>
    </div>

    <!-- 필터 바 -->
    <?php $stBase = '?tab=request&year=' . $year . '&month=' . $month; ?>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-slate-500 mr-1">상태</span>
        <?php foreach ([['','전체'], ['open','진행 중'], ['approved','승인'], ['rejected','반려·회수']] as $opt):
            $active = $reqFilterStatus === $opt[0]; ?>
            <a href="<?= $stBase ?>&st=<?= urlencode($opt[0]) ?>&ty=<?= urlencode($reqFilterType) ?>"
               class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors <?= $active ? 'bg-primary text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>"><?= esc($opt[1]) ?></a>
        <?php endforeach; ?>

        <span class="mx-2 h-4 w-px bg-slate-800"></span>

        <span class="text-xs text-slate-500 mr-1">유형</span>
        <?php
            $filterChips = [['','전체']];
            foreach ($workTypeNames as $n) { $filterChips[] = [$n, $n]; }
            $filterChips[] = ['휴가', '휴가'];
        ?>
        <?php foreach ($filterChips as $opt):
            $active = $reqFilterType === $opt[0]; ?>
            <a href="<?= $stBase ?>&st=<?= urlencode($reqFilterStatus) ?>&ty=<?= urlencode($opt[0]) ?>"
               class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors <?= $active ? 'bg-primary text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>"><?= esc($opt[1] ?: '전체') ?></a>
        <?php endforeach; ?>
    </div>

    <!-- 리스트 -->
    <?php if (empty($reqDocsView)): ?>
        <div class="rounded-xl border border-slate-800/80 bg-slate-900/40 p-10 text-center">
            <i data-lucide="inbox" class="mx-auto w-10 h-10 text-slate-600 mb-3"></i>
            <p class="text-sm font-medium text-slate-200">해당 조건의 신청 내역이 없습니다</p>
            <p class="text-xs text-slate-500 mt-1 mb-4">캘린더에서 날짜를 선택하고 근무 유형을 신청할 수 있어요.</p>
            <a href="?tab=calendar&year=<?= $year ?>&month=<?= $month ?>"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary/90">
                <i data-lucide="calendar" class="w-4 h-4"></i>캘린더로 이동
            </a>
        </div>
    <?php else: ?>
        <ul class="divide-y divide-slate-800/80 rounded-xl border border-slate-800/80 overflow-hidden">
            <?php foreach ($reqDocsView as $r):
                $stcls = reqStatusClass($r['status'] ?? '');
                $typeShort = reqTypeShort($r['doc_type'] ?? '');
                $isSample = (int)($r['id'] ?? 0) === 0;
            ?>
            <li class="group flex items-center gap-4 p-4 bg-slate-900/30 hover:bg-slate-900/60 transition-colors">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-50 text-gray-600 text-xs font-semibold flex-shrink-0"><?= esc($typeShort) ?></span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-semibold text-slate-100 truncate"><?= esc($r['title'] ?? '데이터 없음') ?></h4>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium <?= $stcls['bg'] ?> <?= $stcls['fg'] ?>"><?= esc($stcls['label']) ?></span>
                    </div>
                    <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-slate-500">
                        <span>기안일 <span class="text-slate-300 tabular-nums"><?= esc(substr((string)$r['draft_date'], 0, 10) ?: '데이터 없음') ?></span></span>
                        <?php if (!empty($r['complete_date'])): ?>
                            <span>완료일 <span class="text-slate-300 tabular-nums"><?= esc(substr((string)$r['complete_date'], 0, 10)) ?></span></span>
                        <?php endif; ?>
                        <span>문서번호 <span class="text-slate-400 font-mono"><?= esc($r['doc_number'] ?? '데이터 없음') ?></span></span>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <?php if ($isSample): ?>
                        <span class="text-xs text-slate-500">샘플</span>
                    <?php else: ?>
                        <a href="approval_view.php?id=<?= (int)$r['id'] ?>"
                           class="inline-flex items-center gap-1 text-xs text-gray-600 hover:underline opacity-80 group-hover:opacity-100">
                            상세 <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php elseif ($activeTab === 'vacation'): ?>
<!-- ===== 나의 휴가 내역 · 재기획 ===== -->
<div class="space-y-6">

    <!-- 히어로 카드 + 유형별 KPI -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- 잔여 연차 히어로 -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-800/80 bg-gradient-to-br from-primary/10 via-slate-900/40 to-slate-900/20 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium text-slate-400"><?= $thisYear ?>년 연차 잔액</p>
                    <div class="mt-2 flex items-end gap-2">
                        <span class="text-4xl font-bold tabular-nums text-primary"><?= number_format($leaveRemain, 1) ?></span>
                        <span class="text-sm text-slate-400 pb-1.5">일 남음</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 tabular-nums">부여 <?= number_format($leaveTotal, 1) ?>일 · 사용 <?= number_format($leaveUsed, 1) ?>일 · 사용률 <?= $leaveRate ?>%</p>
                </div>
                <i data-lucide="palmtree" class="w-8 h-8 text-primary/70"></i>
            </div>
            <!-- 프로그레스 바 -->
            <div class="mt-5">
                <div class="h-2 w-full rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary to-primary-dark" style="width: <?= $leaveRate ?>%"></div>
                </div>
                <div class="mt-1.5 flex justify-between text-[11px] text-slate-500 tabular-nums">
                    <span>0</span>
                    <span><?= number_format($leaveTotal, 1) ?>일</span>
                </div>
            </div>
        </div>

        <!-- 유형별 KPI -->
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5">
            <p class="text-xs font-medium text-slate-400 mb-3">유형별 사용 일수</p>
            <ul class="space-y-2.5">
                <?php
                $leaveTypes = [
                    ['AL',  '연차',     'bg-violet-100', 'text-violet-700'],
                    ['HAM', '오전반차',  'bg-indigo-100', 'text-indigo-700'],
                    ['HAP', '오후반차',  'bg-blue-100',   'text-blue-700'],
                    ['SL',  '병가',     'bg-rose-100',   'text-rose-700'],
                    ['FL',  '경조사',    'bg-amber-100',  'text-amber-700'],
                ];
                foreach ($leaveTypes as $lt): ?>
                <li class="flex items-center justify-between">
                    <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium <?= $lt[2] ?> <?= $lt[3] ?>"><?= esc($lt[1]) ?></span>
                    <span class="text-sm font-medium text-slate-200 tabular-nums"><?= number_format($leaveByType[$lt[0]] ?? 0, 1) ?>일</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- 월별 타임라인 -->
    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-100">월별 사용 현황</h3>
                <p class="text-[11px] text-slate-500 mt-0.5"><?= $thisYear ?>년 · 승인된 휴가 기준</p>
            </div>
            <span class="text-[11px] text-slate-500">총 <span class="text-slate-200 tabular-nums"><?= number_format($leaveUsed, 1) ?></span>일</span>
        </div>
        <?php
            $maxMonthUse = max($leaveByMonth) ?: 1;
            $curMonthNum = (int)date('n');
        ?>
        <div class="grid grid-cols-12 gap-2 items-end">
            <?php for ($m = 1; $m <= 12; $m++):
                $use = $leaveByMonth[$m];
                $height = $use > 0 ? max(10, (int)round($use / $maxMonthUse * 100)) : 6;
                $isCur = ($m === $curMonthNum && $thisYear === (int)date('Y'));
            ?>
            <div class="flex flex-col items-center gap-1.5">
                <div class="w-full h-20 flex items-end">
                    <div class="w-full rounded-t <?= $use > 0 ? 'bg-primary' : 'bg-slate-800' ?> <?= $isCur ? 'ring-2 ring-primary/40' : '' ?>"
                         style="height: <?= $height ?>%;" title="<?= $m ?>월 · <?= number_format($use, 1) ?>일"></div>
                </div>
                <span class="text-[10px] tabular-nums <?= $isCur ? 'text-primary font-semibold' : 'text-slate-500' ?>"><?= $m ?></span>
                <?php if ($use > 0): ?>
                    <span class="text-[10px] text-slate-400 tabular-nums"><?= number_format($use, $use == (int)$use ? 0 : 1) ?>일</span>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- 사용 내역 리스트 -->
    <div class="space-y-3">
        <div class="flex items-end justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-100">사용 내역</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">연도 내 신청·사용 이력 전체</p>
            </div>
            <a href="?tab=calendar&year=<?= $year ?>&month=<?= $month ?>" class="text-xs text-gray-600 hover:underline inline-flex items-center gap-1">
                <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>새 휴가 신청
            </a>
        </div>

        <?php if (empty($leaveRequests)): ?>
            <div class="rounded-xl border border-slate-800/80 bg-slate-900/40 p-10 text-center">
                <i data-lucide="palmtree" class="mx-auto w-10 h-10 text-slate-600 mb-3"></i>
                <p class="text-sm font-medium text-slate-200">올해 사용한 휴가가 없습니다</p>
                <p class="text-xs text-slate-500 mt-1">연차 <?= number_format($leaveRemain, 1) ?>일이 남아 있습니다.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-800/80 rounded-xl border border-slate-800/80 overflow-hidden">
                <?php foreach ($leaveRequests as $lr):
                    $type = $lr['leave_type'] ?? 'AL';
                    $typeLabel = attLeaveLabel($type);
                    $status = $lr['status'] ?? '';
                    $stBadge = match($status) {
                        '승인' => ['bg-emerald-100','text-emerald-700'],
                        '반려' => ['bg-rose-100','text-rose-700'],
                        '취소' => ['bg-slate-700','text-slate-300'],
                        '대기' => ['bg-amber-100','text-amber-700'],
                        default=> ['bg-amber-100','text-amber-700'],
                    };
                    $typeBadge = match($type) {
                        'AL'  => ['bg-violet-100','text-violet-700'],
                        'HAM' => ['bg-indigo-100','text-indigo-700'],
                        'HAP' => ['bg-blue-100','text-blue-700'],
                        'SL'  => ['bg-rose-100','text-rose-700'],
                        'FL'  => ['bg-amber-100','text-amber-700'],
                        default=>['bg-slate-700','text-slate-300'],
                    };
                    $isPenalty    = !empty($lr['penalty_flag']);
                    $penaltyNote  = $lr['penalty_reason'] ?? '';
                    $appliedAt    = (string)($lr['created_at'] ?? '');
                    $approvedAt   = (string)($lr['approved_at'] ?? '');
                    $approverName = (string)($lr['approver_name'] ?? '');
                ?>
                <li class="flex items-start gap-4 p-4 bg-slate-900/30 hover:bg-slate-900/60 transition-colors">
                    <div class="flex-shrink-0 w-20 text-center">
                        <div class="text-[10px] uppercase tracking-wider text-slate-500">
                            <?= esc(substr((string)$lr['start_date'], 5, 2)) ?>월
                        </div>
                        <div class="text-lg font-bold tabular-nums text-slate-100">
                            <?= esc(substr((string)$lr['start_date'], 8, 2)) ?>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium <?= $typeBadge[0] ?> <?= $typeBadge[1] ?>"><?= esc($typeLabel) ?></span>
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium <?= $stBadge[0] ?> <?= $stBadge[1] ?>"><?= esc($status) ?></span>
                            <?php if ($isPenalty): ?>
                            <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[11px] font-medium bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/40"
                                  title="<?= esc($penaltyNote ?: '페널티') ?>">
                                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                <?= esc($penaltyNote ?: '페널티') ?>
                            </span>
                            <?php endif; ?>
                            <span class="text-[11px] text-slate-500 tabular-nums">
                                <?= esc($lr['start_date']) ?><?php if ($lr['end_date'] !== $lr['start_date']): ?> ~ <?= esc($lr['end_date']) ?><?php endif; ?>
                            </span>
                        </div>
                        <?php if (!empty($lr['reason'])): ?>
                        <p class="mt-1 text-sm text-slate-300 line-clamp-2"><?= esc($lr['reason']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex-shrink-0 text-right min-w-[140px]">
                        <div class="text-sm font-semibold text-slate-100 tabular-nums"><?= number_format((float)$lr['days_used'], 1) ?><span class="text-[11px] text-slate-500 ml-0.5">일</span></div>
                        <?php if ($appliedAt !== ''): ?>
                        <div class="text-[10px] text-slate-500 mt-1 tabular-nums">
                            신청 <?= esc(substr($appliedAt, 0, 16)) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($status === '승인' && $approvedAt !== ''): ?>
                        <div class="text-[10px] text-emerald-500/80 mt-0.5 tabular-nums">
                            승인 <?= esc(substr($approvedAt, 0, 16)) ?><?php if ($approverName !== ''): ?> · <?= esc($approverName) ?><?php endif; ?>
                        </div>
                        <?php elseif ($status === '반려' && $approvedAt !== ''): ?>
                        <div class="text-[10px] text-rose-400/80 mt-0.5 tabular-nums">
                            반려 <?= esc(substr($approvedAt, 0, 16)) ?>
                        </div>
                        <?php elseif ($status === '대기'): ?>
                        <div class="text-[10px] text-amber-400/80 mt-0.5">승인 대기 중</div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div>
</div>

</div>
</main>
</div>

<!-- Dialog (shadcn Dialog style) -->
<div id="attModal" class="fixed inset-0 z-[100] hidden">
    <div id="attModalOverlay" class="fixed inset-0 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out" onclick="closeModal()"></div>
    <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-2xl translate-x-[-50%] translate-y-[-50%] gap-0 border border-slate-800 bg-slate-900 shadow-lg shadow-black/30 rounded-lg max-h-[90vh] overflow-hidden flex flex-col">

        <!-- Dialog Header -->
        <div class="flex flex-col space-y-1.5 px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 id="modalTitle" class="text-lg font-semibold leading-none tracking-tight">외근</h2>
                    <p class="text-sm text-slate-500 mt-1.5">신청 내용을 확인하고 결재를 요청합니다</p>
                </div>
                <button id="modalCloseX" class="inline-flex items-center justify-center h-8 w-8 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-slate-50 focus:ring-offset-2" aria-label="닫기">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Dialog Content -->
        <div class="overflow-y-auto flex-1 px-6 py-2 grid gap-4">
            <!-- 결재자 -->
            <div class="grid gap-2">
                <label for="inputApprover" class="text-sm font-medium leading-none">결재자 <span class="text-red-500">*</span></label>
                <input type="text" id="inputApprover" class="flex h-10 w-full rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm placeholder:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2" placeholder="결재자 이름">
                <div class="inline-flex w-fit items-center gap-1 rounded-full border border-transparent bg-slate-800 px-2.5 py-0.5 text-sm font-medium text-slate-100">김정환 대표</div>
            </div>
            <!-- 참조자 -->
            <div class="grid gap-2">
                <label for="inputReference" class="text-sm font-medium leading-none">참조자</label>
                <input type="text" id="inputReference" class="flex h-10 w-full rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm placeholder:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2">
            </div>
            <!-- 결재선 -->
            <div class="grid gap-2">
                <label for="selectApprovalLine" class="text-sm font-medium leading-none">결재선</label>
                <select id="selectApprovalLine" class="flex h-10 w-full items-center justify-between rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-50 focus:ring-offset-2">
                    <option>기본 결재선</option>
                </select>
            </div>
            <!-- 목적지 -->
            <div id="rowDestination" class="hidden grid gap-2">
                <label for="inputDestination" class="text-sm font-medium leading-none">목적지 <span class="text-red-500">*</span></label>
                <input type="text" id="inputDestination" class="flex h-10 w-full rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm placeholder:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2" placeholder="방문처 / 출장지">
            </div>
            <!-- 시간 -->
            <div id="rowTime" class="grid gap-2">
                <label class="text-sm font-medium leading-none">시간</label>
                <div class="flex items-center gap-2 flex-wrap">
                    <select id="timeStart" class="flex h-10 w-28 items-center rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-50 focus:ring-offset-2">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                            <?php for ($m = 0; $m < 60; $m += 30): ?>
                                <option value="<?= sprintf('%02d:%02d', $h, $m) ?>" <?= ($h === 9 && $m === 0) ? 'selected' : '' ?>><?= sprintf('%02d:%02d', $h, $m) ?></option>
                            <?php endfor; ?>
                        <?php endfor; ?>
                    </select>
                    <span class="text-sm text-slate-400">~</span>
                    <select id="timeEnd" class="flex h-10 w-28 items-center rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-50 focus:ring-offset-2">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                            <?php for ($m = 0; $m < 60; $m += 30): ?>
                                <option value="<?= sprintf('%02d:%02d', $h, $m) ?>" <?= ($h === 18 && $m === 0) ? 'selected' : '' ?>><?= sprintf('%02d:%02d', $h, $m) ?></option>
                            <?php endfor; ?>
                        <?php endfor; ?>
                    </select>
                    <div id="checkCommute" class="hidden ml-4 flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                            <input type="checkbox" id="chkDirectIn" class="h-4 w-4 rounded border border-slate-600 bg-slate-900 accent-primary"> 직출
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                            <input type="checkbox" id="chkDirectOut" class="h-4 w-4 rounded border border-slate-600 bg-slate-900 accent-primary"> 직퇴
                        </label>
                    </div>
                </div>
            </div>
            <!-- 휴가 유형 -->
            <div id="rowVacationType" class="hidden grid gap-2">
                <label for="selectVacationType" class="text-sm font-medium leading-none">휴가 유형</label>
                <select id="selectVacationType" class="flex h-10 w-full items-center rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-50 focus:ring-offset-2">
                    <?= $leaveTypeOptions ?>
                </select>
            </div>
            <!-- 휴가 기간 -->
            <div id="rowVacationDate" class="hidden grid gap-2">
                <label class="text-sm font-medium leading-none">기간</label>
                <div class="flex items-center gap-2">
                    <input type="date" id="vacStart" class="flex h-10 rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2">
                    <span class="text-sm text-slate-400">~</span>
                    <input type="date" id="vacEnd" class="flex h-10 rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2">
                </div>
            </div>
            <!-- 일정에 추가 -->
            <div class="flex items-center space-x-2 rounded-md border border-slate-800 p-4">
                <input type="checkbox" id="chkAddSchedule" class="h-4 w-4 rounded border border-slate-600 bg-slate-900 accent-primary">
                <div class="grid gap-1 leading-none">
                    <label for="chkAddSchedule" class="text-sm font-medium leading-none cursor-pointer">일정에 함께 추가</label>
                    <p class="text-sm text-slate-500">승인 후 일정 캘린더에 자동 등록됩니다</p>
                </div>
            </div>
            <!-- 제목 -->
            <div class="grid gap-2">
                <label for="modalSubject" class="text-sm font-medium leading-none">제목</label>
                <input type="text" id="modalSubject" class="flex h-10 w-full rounded-md border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-300" readonly>
            </div>
            <!-- 내용 -->
            <div class="grid gap-2">
                <label for="modalContent" class="text-sm font-medium leading-none">내용</label>
                <textarea id="modalContent" rows="5" class="flex w-full rounded-md border border-slate-800/80 bg-slate-900/40 backdrop-blur-xl px-3 py-2 text-sm placeholder:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2 resize-y" placeholder="상세 내용을 입력해주세요"></textarea>
            </div>
            <!-- 첨부 -->
            <div class="grid gap-2">
                <label class="text-sm font-medium leading-none">첨부 파일</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="fileDisplayName" class="flex-1 flex h-10 rounded-md border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-300" placeholder="선택된 파일 없음" readonly>
                    <button id="btnFileSelect" type="button" class="btn btn-secondary">파일 선택</button>
                    <button id="btnFileDelete" type="button" class="btn btn-secondary">삭제</button>
                    <input type="file" id="hiddenFileInput" class="hidden">
                </div>
            </div>
        </div>

        <!-- Dialog Footer -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 px-6 py-4 border-t border-slate-800">
            <button id="modalClose" class="btn btn-secondary">취소</button>
            <button id="modalSubmit" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-10 px-4 bg-primary text-white hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-50 focus-visible:ring-offset-2 disabled:opacity-50">신청</button>
        </div>
    </div>
</div>

<!-- 모달 제어 스크립트 -->
<script>
(function() {
    const modal = document.getElementById('attModal');
    const overlay = document.getElementById('attModalOverlay');
    const closeX = document.getElementById('modalCloseX');
    const closeBtn = document.getElementById('modalClose');
    const titleEl = document.getElementById('modalTitle');
    const subjectEl = document.getElementById('modalSubject');
    const rowDest = document.getElementById('rowDestination');
    const rowTime = document.getElementById('rowTime');
    const checkCommute = document.getElementById('checkCommute');
    const rowVacType = document.getElementById('rowVacationType');
    const rowVacDate = document.getElementById('rowVacationDate');
    const timeStart = document.getElementById('timeStart');
    const timeEnd = document.getElementById('timeEnd');
    const vacStart = document.getElementById('vacStart');
    const vacEnd = document.getElementById('vacEnd');

    // 현재 모달 상태 저장용
    let currentType = '';
    let currentDate = '';
    let currentCfg = null;

    // 유형별 설정
    const typeConfig = {
        '야근':   { title: '야근',         docName: '야근 신청서',       startTime: '18:00', endTime: '21:00', showDest: false, showCommute: false, showVacation: false },
        '외근':   { title: '외근',         docName: '외근 신청서',       startTime: '09:00', endTime: '09:00', showDest: true,  showCommute: true,  showVacation: false },
        '재택':   { title: '재택근무신청',  docName: '재택근무 신청서',   startTime: '09:00', endTime: '18:00', showDest: false, showCommute: false, showVacation: false },
        '출장':   { title: '출장',         docName: '출장 신청서',       startTime: '09:00', endTime: '18:00', showDest: true,  showCommute: true,  showVacation: false },
        '휴가':   { title: '휴가신청',     docName: '휴가 신청서',       startTime: null,    endTime: null,    showDest: false, showCommute: false, showVacation: true  },
        '휴일근무': { title: '휴일근무',   docName: '휴일근무 신청서',   startTime: '09:00', endTime: '18:00', showDest: false, showCommute: false, showVacation: false },
    };

    function openModal(type, date) {
        const cfg = typeConfig[type];
        if (!cfg) return;

        // 현재 상태 저장
        currentType = type;
        currentDate = date;
        currentCfg = cfg;

        // 모달 폼 초기화
        resetModalForm();

        // 제목
        titleEl.textContent = cfg.title;

        // 필드 표시/숨김
        rowDest.classList.toggle('hidden', !cfg.showDest);
        checkCommute.classList.toggle('hidden', !cfg.showCommute);
        rowTime.classList.toggle('hidden', cfg.showVacation);
        rowVacType.classList.toggle('hidden', !cfg.showVacation);
        rowVacDate.classList.toggle('hidden', !cfg.showVacation);

        // 시간 기본값
        if (cfg.startTime) timeStart.value = cfg.startTime;
        if (cfg.endTime) timeEnd.value = cfg.endTime;

        // 휴가 기간 기본값
        if (cfg.showVacation) {
            vacStart.value = date;
            vacEnd.value = date;
        }

        // 자동 제목 생성
        updateSubject(cfg, date);

        // 시간 변경 시 제목 자동 업데이트
        timeStart.onchange = function() { updateSubject(cfg, date); };
        timeEnd.onchange = function() { updateSubject(cfg, date); };

        // 모달 표시
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function updateSubject(cfg, date) {
        if (cfg.showVacation) {
            subjectEl.value = '[' + cfg.docName + '] WEVEN 김정환 - ' + date;
        } else {
            subjectEl.value = '[' + cfg.docName + '] WEVEN 김정환 - ' + date + ' ' + timeStart.value + ' ~  ' + timeEnd.value;
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // 버튼 클릭 이벤트 (이벤트 위임)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.att-btn');
        if (btn) {
            const type = btn.getAttribute('data-type');
            const date = btn.getAttribute('data-date');
            if (type && date) openModal(type, date);
        }
    });

    overlay.addEventListener('click', closeModal);
    closeX.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    // ===== 폼 초기화 =====
    function resetModalForm() {
        document.getElementById('inputApprover').value = '';
        document.getElementById('inputReference').value = '';
        document.getElementById('inputDestination').value = '';
        document.getElementById('modalContent').value = '';
        document.getElementById('fileDisplayName').value = '';
        document.getElementById('hiddenFileInput').value = '';
        document.getElementById('chkAddSchedule').checked = false;
        document.getElementById('chkDirectIn').checked = false;
        document.getElementById('chkDirectOut').checked = false;
    }

    // ===== 파일선택 버튼 =====
    var hiddenFileInput = document.getElementById('hiddenFileInput');
    var fileDisplayName = document.getElementById('fileDisplayName');

    document.getElementById('btnFileSelect').addEventListener('click', function() {
        hiddenFileInput.click();
    });

    hiddenFileInput.addEventListener('change', function() {
        if (hiddenFileInput.files.length > 0) {
            fileDisplayName.value = hiddenFileInput.files[0].name;
        }
    });

    // ===== 파일삭제 버튼 =====
    document.getElementById('btnFileDelete').addEventListener('click', function() {
        hiddenFileInput.value = '';
        fileDisplayName.value = '';
    });

    // ===== 신청 버튼 =====
    var APPROVAL_API = '<?= $basePath ?>/api/approval.php';

    document.getElementById('modalSubmit').addEventListener('click', function() {
        if (!currentCfg) return;

        var title = subjectEl.value;
        if (!title) {
            alert('제목이 비어있습니다.');
            return;
        }

        // 결재자 수집 (입력된 이름 또는 기본 결재자)
        var approverInput = document.getElementById('inputApprover').value.trim();
        var approverName = approverInput || '김정환';
        var approvalRoute = [{ name: approverName }];

        // 내용 조합
        var contentParts = [];
        contentParts.push('신청유형: ' + currentType);
        contentParts.push('신청일자: ' + currentDate);

        if (currentCfg.showVacation) {
            var vType = document.getElementById('selectVacationType').value;
            var vStart = document.getElementById('vacStart').value;
            var vEnd = document.getElementById('vacEnd').value;
            contentParts.push('휴가유형: ' + vType);
            contentParts.push('기간: ' + vStart + ' ~ ' + vEnd);
        } else {
            contentParts.push('시간: ' + timeStart.value + ' ~ ' + timeEnd.value);
        }

        if (currentCfg.showDest) {
            var dest = document.getElementById('inputDestination').value.trim();
            if (dest) contentParts.push('목적지: ' + dest);
        }

        if (currentCfg.showCommute) {
            var directIn = document.getElementById('chkDirectIn').checked;
            var directOut = document.getElementById('chkDirectOut').checked;
            if (directIn) contentParts.push('직출: 예');
            if (directOut) contentParts.push('직퇴: 예');
        }

        var userContent = document.getElementById('modalContent').value.trim();
        if (userContent) contentParts.push('\n' + userContent);

        var refPerson = document.getElementById('inputReference').value.trim();
        if (refPerson) contentParts.push('참조자: ' + refPerson);

        var payload = {
            title: title,
            content: contentParts.join('\n'),
            doc_type: currentCfg.docName,
            status: '진행',
            drafter_name: '김정환',
            drafter_dept: '',
            draft_date: currentDate,
            approval_route: approvalRoute
        };

        var submitBtn = document.getElementById('modalSubmit');
        submitBtn.disabled = true;
        submitBtn.textContent = '처리중...';

        fetch(APPROVAL_API + '?action=saveDocument', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            submitBtn.disabled = false;
            submitBtn.textContent = '신청';
            if (res.error) {
                alert('신청 실패: ' + res.error);
                return;
            }
            alert('신청이 완료되었습니다.');
            closeModal();
        })
        .catch(function(err) {
            submitBtn.disabled = false;
            submitBtn.textContent = '신청';
            alert('서버 연결 실패: ' + err.message);
        });
    });
})();
</script>

<!-- 출퇴근 API 연동 -->
<script>
(function() {
    var API = '<?= $basePath ?>/api/attendance.php';
    var WORK_TYPE_NAMES = <?= json_encode($workTypeNameMap, JSON_UNESCAPED_UNICODE) ?>;
    // 서버 측 attendance API는 이미 세션에서 employee_id를 강제하므로, 프론트에서 넘기는 값은
    // admin/manager의 경우를 제외하면 무시된다. 그래도 UI 표시용으로 세션 사용자 id를 주입한다.
    var EMP_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

    function fmtTime(t) { return t ? t.substring(0, 5) : '--:--'; }

    function showAttToast(msg) {
        var el = document.createElement('div');
        el.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 bg-slate-800 text-slate-100 border border-slate-700 px-5 py-3 rounded-xl text-sm shadow-lg z-50';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function() { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(function() { el.remove(); }, 300); }, 2500);
    }

    function loadAttStatus() {
        fetch(API + '?action=getToday&employee_id=' + EMP_ID)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) return;
                var d = res.data;
                if (d.clock_in) {
                    document.getElementById('attClockInTime').textContent = fmtTime(d.clock_in);
                    document.getElementById('attClockInTime').classList.replace('text-slate-400', 'text-white');
                    document.getElementById('attClockInBtn').disabled = true;
                    document.getElementById('attClockOutBtn').disabled = !!d.clock_out;
                    if (!d.clock_out) document.getElementById('attClockOutBtn').disabled = false;
                }
                if (d.clock_out) {
                    document.getElementById('attClockOutTime').textContent = fmtTime(d.clock_out);
                    document.getElementById('attClockOutTime').classList.replace('text-slate-400', 'text-primary');
                    document.getElementById('attClockOutBtn').disabled = true;
                }
            }).catch(function() {});
    }

    /**
     * attClock(action, extraPayload)
     *   - action: 'clockIn' | 'clockOut'
     *   - extraPayload: { work_plan?: string, leave_note?: string }
     * 모달에서 입력받은 업무 계획/특이사항을 함께 전달한다.
     */
    window.attClock = function(action, extraPayload) {
        var btn = document.getElementById(action === 'clockIn' ? 'attClockInBtn' : 'attClockOutBtn');
        btn.disabled = true;
        var payload = Object.assign({ employee_id: EMP_ID }, extraPayload || {});

        return fetch(API + '?action=' + action, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) {
                btn.disabled = false;
                showAttToast(res.error || '처리 실패');
                return res;
            }
            if (action === 'clockIn') {
                document.getElementById('attClockInTime').textContent = fmtTime(res.clock_in);
                document.getElementById('attClockInTime').classList.replace('text-slate-400', 'text-white');
                document.getElementById('attClockOutBtn').disabled = false;
                showAttToast('출근이 기록되었습니다 (' + fmtTime(res.clock_in) + ')');
            } else {
                document.getElementById('attClockOutTime').textContent = fmtTime(res.clock_out);
                document.getElementById('attClockOutTime').classList.replace('text-slate-400', 'text-primary');
                showAttToast('퇴근이 기록되었습니다 (' + fmtTime(res.clock_out) + ')');
            }
            return res;
        })
        .catch(function() {
            btn.disabled = false;
            showAttToast('서버 연결 실패');
        });
    };

    // ── 출근/퇴근 모달 제어 ──
    window.openClockInModal = function () {
        var m = document.getElementById('clockInModal');
        if (!m) return;
        m.classList.remove('hidden');
        setTimeout(function(){ var ta = document.getElementById('clockInPlan'); if (ta) ta.focus(); }, 20);
    };
    window.closeClockInModal = function () {
        var m = document.getElementById('clockInModal'); if (m) m.classList.add('hidden');
    };
    window.submitClockIn = function () {
        var plan = (document.getElementById('clockInPlan').value || '').trim();
        attClock('clockIn', { work_plan: plan }).then(function(res){
            if (res && res.success) {
                closeClockInModal();
                // 캘린더에 방금 작성한 업무가 반영되도록 다시 렌더가 필요하면 페이지 리로드
                setTimeout(function(){ location.reload(); }, 400);
            }
        });
    };

    window.openClockOutModal = function () {
        var m = document.getElementById('clockOutModal');
        if (!m) return;
        m.classList.remove('hidden');
        setTimeout(function(){ var ta = document.getElementById('clockOutNote'); if (ta) ta.focus(); }, 20);
    };
    window.closeClockOutModal = function () {
        var m = document.getElementById('clockOutModal'); if (m) m.classList.add('hidden');
    };
    window.submitClockOut = function () {
        var note = (document.getElementById('clockOutNote').value || '').trim();
        attClock('clockOut', { leave_note: note }).then(function(res){
            if (res && res.success) {
                closeClockOutModal();
                setTimeout(function(){ location.reload(); }, 400);
            }
        });
    };

    // ── "기타" 버튼 : 특이사항 단독 입력 모달 ──
    //   결재 플로우 없이 leave_note 만 저장. 출퇴근 미기록/철회 등 사후 보정용.
    window.openOtherModal = function (date, presetNote) {
        document.getElementById('otherModalDate').textContent = date || '';
        document.getElementById('otherModalDateInput').value = date || '';
        document.getElementById('otherReasonSel').value = '출퇴근 미기록';
        document.getElementById('otherNoteTa').value = presetNote || '';
        document.getElementById('otherModal').classList.remove('hidden');
        setTimeout(function () { document.getElementById('otherNoteTa').focus(); }, 20);
    };
    window.closeOtherModal = function () {
        document.getElementById('otherModal').classList.add('hidden');
    };
    window.submitOtherNote = function () {
        var date   = document.getElementById('otherModalDateInput').value;
        var reason = document.getElementById('otherReasonSel').value;
        var note   = (document.getElementById('otherNoteTa').value || '').trim();
        if (!note) { alert('내용을 입력해주세요.'); return; }
        var btn = document.getElementById('otherSaveBtn');
        btn.disabled = true; btn.textContent = '저장 중…';
        fetch(API + '?action=recordOther', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ date: date, reason: reason, note: note })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            btn.disabled = false; btn.textContent = '저장';
            if (!res || !res.success) { alert((res && res.error) || '저장 실패'); return; }
            closeOtherModal();
            setTimeout(function () { location.reload(); }, 300);
        })
        .catch(function () { btn.disabled = false; btn.textContent = '저장'; alert('서버 연결 실패'); });
    };
    // 캘린더 셀의 "기타" 버튼 클릭 위임
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.att-other-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        // 드롭다운 닫기
        document.querySelectorAll('.att-more-menu:not(.hidden)').forEach(function (el) { el.classList.add('hidden'); });
        openOtherModal(btn.getAttribute('data-date'), '');
    });

    // ── 캘린더 "+" 버튼 : data-date / data-for 매칭으로 해당 셀 메뉴 토글 ──
    //    이전 구현은 closest('div') 로 잡아 바로 바깥 래퍼를 찾지 못해 메뉴가 열리지 않는 버그가 있었다.
    document.addEventListener('click', function (e) {
        var more = e.target.closest('.att-more');
        if (more) {
            e.preventDefault();
            e.stopPropagation();
            var date = more.getAttribute('data-date');
            var menu = document.querySelector('.att-more-menu[data-for="' + date + '"]');
            document.querySelectorAll('.att-more-menu:not(.hidden)').forEach(function (el) {
                if (el !== menu) el.classList.add('hidden');
            });
            if (menu) menu.classList.toggle('hidden');
            return;
        }
        if (!e.target.closest('.att-more-menu') && !e.target.closest('.att-btn')) {
            document.querySelectorAll('.att-more-menu:not(.hidden)').forEach(function (el) { el.classList.add('hidden'); });
        }
    });

    // ── 캘린더 셀 기록 영역 클릭 → 일자 상세 모달 ──
    var ATT_MONTH = <?= json_encode($monthDetailJs, JSON_UNESCAPED_UNICODE) ?>;
    window.__attMonth = ATT_MONTH;

    function fmtHm(t) { return t ? String(t).substring(0, 5) : null; }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
        });
    }
    function leaveKor(t) {
        return ({ AL: '연차', HAM: '오전반차', HAP: '오후반차', SL: '병가', FL: '경조사' })[t] || (t || '휴가');
    }

    // 편집 가능한 섹션 HTML 빌더 · 값 없어도 "작성" 버튼 표시
    function renderEditableSection(field, label, value) {
        var hasValue = value !== null && value !== undefined && String(value).trim() !== '';
        var placeholder = (field === 'work_plan')
            ? '오늘 처리할 업무 계획…'
            : '근무 중 발생한 특이사항 · 출퇴근을 놓친 사유 · 인수인계 등';
        var viewInner = hasValue
            ? '<p class="text-sm text-slate-200 whitespace-pre-wrap leading-relaxed bg-slate-950/40 rounded-lg p-3 border border-slate-800/80">' + esc(value) + '</p>'
            : '<p class="text-sm text-slate-500 italic bg-slate-950/30 rounded-lg p-3 border border-dashed border-slate-800/80">작성된 내용이 없습니다.</p>';

        return ''
            + '<section data-section="' + field + '">'
            +   '<div class="flex items-center justify-between mb-1.5">'
            +     '<h4 class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">' + esc(label) + '</h4>'
            +     '<button type="button" class="det-edit-btn inline-flex items-center gap-1 text-[11px] text-gray-600 hover:underline" data-field="' + field + '">'
            +       '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
            +       (hasValue ? '수정' : '작성')
            +     '</button>'
            +   '</div>'
            +   '<div class="det-view" data-view="' + field + '">' + viewInner + '</div>'
            +   '<div class="det-editor hidden" data-editor="' + field + '">'
            +     '<textarea id="det_' + field + '_ta" rows="5" maxlength="2000" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200" placeholder="' + placeholder + '">' + esc(value || '') + '</textarea>'
            +     '<div class="mt-2 flex justify-end gap-2">'
            +       '<button type="button" class="det-cancel btn btn-secondary btn-xs" data-field="' + field + '">취소</button>'
            +       '<button type="button" class="det-save px-3 py-1.5 text-xs bg-primary text-white rounded-lg hover:bg-primary-dark" data-field="' + field + '">저장</button>'
            +     '</div>'
            +   '</div>'
            + '</section>';
    }

    window.openDayDetail = function (day) {
        var d = ATT_MONTH[day];
        if (!d) return;
        var title = document.getElementById('dayDetailTitle');
        var body  = document.getElementById('dayDetailBody');
        var dateLabel = d.date + (function(){
            var wd = ['일','월','화','수','목','금','토'];
            var dt = new Date(d.date);
            return ' (' + wd[dt.getDay()] + ')';
        })();
        title.textContent = dateLabel + ' 근무 상세';
        body.dataset.date = d.date;
        body.dataset.day  = String(day);

        var rows = [];

        // 출퇴근 요약 (항상 표시, 값 없으면 "데이터 없음")
        rows.push(
            '<div class="flex items-center gap-4 p-3 rounded-lg bg-slate-950/60">' +
                '<div class="flex items-center gap-2">' +
                    '<span class="text-[11px] text-slate-500">출근</span>' +
                    '<span class="font-mono text-lg font-semibold text-emerald-400 tabular-nums">' + (fmtHm(d.clock_in) || '데이터 없음') + '</span>' +
                '</div>' +
                '<div class="w-px h-6 bg-slate-800"></div>' +
                '<div class="flex items-center gap-2">' +
                    '<span class="text-[11px] text-slate-500">퇴근</span>' +
                    '<span class="font-mono text-lg font-semibold text-primary tabular-nums">' + (fmtHm(d.clock_out) || '데이터 없음') + '</span>' +
                '</div>' +
            '</div>'
        );

        // 오늘의 업무 / 특이사항 · 값 없어도 섹션 항상 렌더 (수정/작성 버튼 노출)
        rows.push(renderEditableSection('work_plan',  '오늘의 업무', d.work_plan));
        rows.push(renderEditableSection('leave_note', '특이사항',    d.leave_note));

        // 근무유형
        if (d.work_type) {
            rows.push(
                '<div class="flex items-center gap-2 text-sm">' +
                    '<span class="text-slate-500">근무 유형</span>' +
                    '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium bg-blue-100 text-blue-700">' + esc(WORK_TYPE_NAMES[d.work_type] || d.work_type) + '</span>' +
                    (d.note ? '<span class="text-slate-400">· ' + esc(d.note) + '</span>' : '') +
                '</div>'
            );
        }

        // 외근
        if (d.outside && d.outside.length) {
            var outHtml = d.outside.map(function (o) {
                return '<li class="flex items-start gap-2 text-sm text-slate-200">' +
                    '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium bg-amber-100 text-amber-700 flex-shrink-0 mt-0.5">외근</span>' +
                    '<div><div>' + esc(o.destination || '') + '</div>' +
                    (o.purpose ? '<div class="text-xs text-slate-400 mt-0.5">' + esc(o.purpose) + '</div>' : '') +
                    '</div></li>';
            }).join('');
            rows.push(
                '<section>' +
                    '<h4 class="text-xs font-semibold text-slate-500 mb-1.5">외근 / 출장</h4>' +
                    '<ul class="space-y-2">' + outHtml + '</ul>' +
                '</section>'
            );
        }

        // 휴가
        if (d.leaves && d.leaves.length) {
            var lv = d.leaves.map(function (l) {
                return '<span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-medium bg-violet-100 text-violet-700">' + esc(l.label || leaveKor(l.type)) + '</span>';
            }).join(' ');
            rows.push(
                '<section>' +
                    '<h4 class="text-xs font-semibold text-slate-500 mb-1.5">승인된 휴가</h4>' +
                    '<div class="flex flex-wrap gap-1.5">' + lv + '</div>' +
                '</section>'
            );
        }

        body.innerHTML = rows.join('');
        document.getElementById('dayDetailModal').classList.remove('hidden');
    };

    // ── 상세 모달 내부 버튼 위임 · 수정/저장/취소 ──
    function toggleSectionMode(field, editing) {
        var view   = document.querySelector('[data-view="'   + field + '"]');
        var editor = document.querySelector('[data-editor="' + field + '"]');
        if (!view || !editor) return;
        view.classList.toggle('hidden',   editing);
        editor.classList.toggle('hidden', !editing);
        if (editing) {
            var ta = editor.querySelector('textarea');
            if (ta) { ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); }
        }
    }
    function saveSectionEdit(field) {
        var body = document.getElementById('dayDetailBody');
        var date = body.dataset.date;
        var day  = parseInt(body.dataset.day, 10);
        var ta   = document.getElementById('det_' + field + '_ta');
        if (!date || !ta) return;
        var btn  = document.querySelector('.det-save[data-field="' + field + '"]');
        var value = ta.value;
        var payload = { date: date };
        payload[field] = value;
        if (btn) { btn.disabled = true; btn.textContent = '저장 중…'; }

        fetch(API + '?action=updateDayDetail', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(async function (res) {
            if (btn) { btn.disabled = false; btn.textContent = '저장'; }
            if (!res || !res.success) {
                // 기록이 없으면(404) → '기타' 모달로 유도
                if (res && res.error && /기록이 없어/.test(res.error) && field === 'leave_note') {
                    if ((await AppUI.confirm('해당 날짜에 출근 기록이 없습니다. "기타" 입력으로 저장할까요?'))) {
                        openOtherModal(date, value);
                    }
                } else {
                    showAttToast((res && res.error) || '저장 실패');
                }
                return;
            }
            if (ATT_MONTH[day]) {
                ATT_MONTH[day][field] = (value.trim() === '') ? null : value;
            }
            window.openDayDetail(day);
            showAttToast('저장되었습니다');
        })
        .catch(function () {
            if (btn) { btn.disabled = false; btn.textContent = '저장'; }
            showAttToast('서버 연결 실패');
        });
    }
    document.addEventListener('click', function (e) {
        var editBtn = e.target.closest('.det-edit-btn');
        if (editBtn) { e.preventDefault(); toggleSectionMode(editBtn.getAttribute('data-field'), true); return; }
        var cancelBtn = e.target.closest('.det-cancel');
        if (cancelBtn) {
            e.preventDefault();
            var f = cancelBtn.getAttribute('data-field');
            var body = document.getElementById('dayDetailBody');
            var day  = parseInt(body.dataset.day, 10);
            var orig = (ATT_MONTH[day] && ATT_MONTH[day][f]) || '';
            var ta = document.getElementById('det_' + f + '_ta');
            if (ta) ta.value = orig;
            toggleSectionMode(f, false);
            return;
        }
        var saveBtn = e.target.closest('.det-save');
        if (saveBtn) { e.preventDefault(); saveSectionEdit(saveBtn.getAttribute('data-field')); return; }
    });
    window.closeDayDetail = function () {
        document.getElementById('dayDetailModal').classList.add('hidden');
    };

    // 클릭(또는 Enter) 이벤트로 상세 영역 연결
    document.addEventListener('click', function (e) {
        var area = e.target.closest('[data-day-detail]');
        if (!area) return;
        // + 버튼·드롭다운·att-btn 클릭은 이 핸들러로 들어오지 않도록 이전 핸들러에서 stopPropagation 처리됨
        if (e.target.closest('.att-more, .att-more-menu, .att-btn')) return;
        var day = parseInt(area.getAttribute('data-day-detail'), 10);
        if (day) window.openDayDetail(day);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var area = e.target.closest('[data-day-detail]');
        if (!area) return;
        e.preventDefault();
        var day = parseInt(area.getAttribute('data-day-detail'), 10);
        if (day) window.openDayDetail(day);
    });
    // ESC 로 상세 모달 닫기
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDayDetail();
        }
    });

    document.addEventListener('DOMContentLoaded', loadAttStatus);
})();
</script>

<!-- ── 기타 특이사항 모달 (출퇴근 미기록·철회 등) ── -->
<div id="otherModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeOtherModal()"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">기타 특이사항</h3>
            <button type="button" onclick="closeOtherModal()" class="text-slate-400 hover:text-slate-200" aria-label="닫기">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <p class="text-xs text-slate-400">출퇴근을 기록하지 못했거나 철회한 경우, 결재 없이 특이사항만 남겨둡니다.</p>

            <input type="hidden" id="otherModalDateInput" value="">
            <div>
                <label class="block text-sm text-slate-300 mb-1">대상 날짜</label>
                <p id="otherModalDate" class="text-sm text-slate-200 font-mono tabular-nums px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg"></p>
            </div>

            <div>
                <label class="block text-sm text-slate-300 mb-1">사유 구분</label>
                <select id="otherReasonSel" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200">
                    <option value="출퇴근 미기록">출퇴근 미기록</option>
                    <option value="출근 미기록">출근만 미기록</option>
                    <option value="퇴근 미기록">퇴근만 미기록</option>
                    <option value="기록 철회">기록 철회</option>
                    <option value="시스템 장애">시스템 장애 / 오류</option>
                    <option value="기타">기타</option>
                </select>
            </div>

            <div>
                <label class="block text-sm text-slate-300 mb-1">내용 <span class="text-rose-400">*</span></label>
                <textarea id="otherNoteTa" rows="5" maxlength="2000"
                          class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200"
                          placeholder="예) 외부 출장 중 네트워크 문제로 출근 기록 실패 · 오전 10:30 업무 시작"></textarea>
            </div>
        </div>
        <div class="flex gap-2 justify-end px-5 pb-5">
            <button type="button" onclick="closeOtherModal()" class="btn btn-secondary">취소</button>
            <button type="button" id="otherSaveBtn" onclick="submitOtherNote()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">저장</button>
        </div>
    </div>
</div>

<!-- ── 일자 상세 모달 (출퇴근·업무·특이사항·외근·휴가) ── -->
<div id="dayDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeDayDetail()"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 id="dayDetailTitle" class="text-base font-bold text-slate-100">일자 상세</h3>
            <button type="button" onclick="closeDayDetail()" class="text-slate-400 hover:text-slate-200" aria-label="닫기">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="dayDetailBody" class="p-5 space-y-4 text-sm text-slate-200 overflow-y-auto"></div>
        <div class="flex gap-2 justify-end px-5 pb-5">
            <button type="button" onclick="closeDayDetail()" class="btn btn-secondary">닫기</button>
        </div>
    </div>
</div>

<!-- ── 출근 모달: 오늘의 업무 ── -->
<div id="clockInModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeClockInModal()"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">출근 · 오늘의 업무</h3>
            <button type="button" onclick="closeClockInModal()" class="text-slate-400 hover:text-slate-200" aria-label="닫기">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <p class="text-xs text-slate-400">오늘 처리할 업무를 간단히 적어두면, 캘린더와 대시보드에 함께 표시됩니다.</p>
            <label class="block text-sm text-slate-300">오늘의 업무 <span class="text-slate-500">(선택)</span></label>
            <textarea id="clockInPlan" rows="5" maxlength="2000"
                      class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200"
                      placeholder="예) 전자결재 2건 처리 · 세무리포트 초안 작성 · 14시 거래처 미팅"></textarea>
        </div>
        <div class="flex gap-2 justify-end px-5 pb-5">
            <button type="button" onclick="closeClockInModal()" class="btn btn-secondary">취소</button>
            <button type="button" onclick="submitClockIn()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">출근 등록</button>
        </div>
    </div>
</div>

<!-- ── 퇴근 모달: 특이사항 ── -->
<div id="clockOutModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeClockOutModal()"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">퇴근 · 특이사항 제출</h3>
            <button type="button" onclick="closeClockOutModal()" class="text-slate-400 hover:text-slate-200" aria-label="닫기">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <p class="text-xs text-slate-400">오늘 근무 중 발생한 특이사항·인수인계·내일로 이어질 이슈를 남겨주세요. 빈 값으로 제출해도 됩니다.</p>
            <label class="block text-sm text-slate-300">특이사항 메모 <span class="text-slate-500">(선택)</span></label>
            <textarea id="clockOutNote" rows="5" maxlength="2000"
                      class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200"
                      placeholder="예) 서버 배포 지연 · 내일 아침 확인 필요 / 고객사 A 컴플레인 오전 처리"></textarea>
        </div>
        <div class="flex gap-2 justify-end px-5 pb-5">
            <button type="button" onclick="closeClockOutModal()" class="btn btn-secondary">취소</button>
            <button type="button" onclick="submitClockOut()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">퇴근 등록</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
