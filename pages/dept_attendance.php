<?php
$pageTitle = '부서 근태현황';
$currentPage = 'attendance';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/permissions.php';
// 부서 근태현황은 로그인한 전 직원이 열람 가능 (로그인 강제는 header.php의 requireLogin).
// 관리자 전용 '근태 관리'(att_manage.php)와 구분 — 관리자 가드 제거.

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDayOfMonth);
$startDayOfWeek = (int)date('w', $firstDayOfMonth);
$today = (int)date('j');
$currentMonth = (int)date('m');
$currentYear = (int)date('Y');
$monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// DB에서 직원 데이터 가져오기 (직원관리와 동일한 소스)
require_once __DIR__ . '/../config/database.php';
$employees = [];
$timeEmployees = [];

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT e.name, e.position, e.employment_status,
                   d.name AS department_name,
                   CASE WHEN pd.parent_id IS NULL AND pd.id IS NOT NULL THEN pd.name
                        WHEN pd.parent_id IS NOT NULL THEN
                            (SELECT a.name FROM departments a WHERE a.id = pd.parent_id AND a.parent_id IS NULL)
                        ELSE COALESCE(d.name, '')
                   END AS division_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE e.is_active = 1
            ORDER BY FIELD(e.position, '대표이사','이사','부장','차장','과장','대리','주임','사원','인턴'), e.name
        ");
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $timeEmployees[] = [
                'name'     => $row['name'],
                'dept'     => $row['department_name'] ?: '',
                'division' => $row['division_name'] ?: '',
                'position' => $row['position'] ?: '',
                'status'   => $row['employment_status'] ?: '재직',
                'normal'   => true,
            ];
        }
    }
} catch (PDOException $e) {
    // DB 연결 실패 시 빈 배열 유지
}

// 이름 기반 결정론적 출퇴근 시간 생성
function genDayRecord(string $name, int $year, int $month, int $day, bool $normal): array {
    $seed = crc32($name . $year . $month . $day);
    $lateRand = abs($seed + 4) % 12;
    $isLate = ($lateRand === 0);
    if ($isLate) {
        $inH = 9; $inM = 1 + abs($seed) % 45;
    } else {
        $inH = 8; $inM = 30 + (abs($seed) % 30);
    }
    $outH = 18 + (abs($seed + 1) % 4);
    $outM = abs($seed + 2) % 60;
    $suffixRand = abs($seed + 3) % 25;
    $suffix = '';
    if ($suffixRand >= 23) $suffix = '야근';
    elseif ($suffixRand >= 21) $suffix = '휴가';
    elseif ($suffixRand >= 19) $suffix = '재택';
    elseif ($suffixRand >= 18) $suffix = '출장';
    elseif ($suffixRand >= 17) $suffix = '결근';

    $isAbsent = ($suffix === '결근');
    if ($isLate && $suffix) $isLate = false;
    return [
        'in'     => $isAbsent ? '' : sprintf('%02d:%02d', $inH, $inM),
        'out'    => $isAbsent ? '' : sprintf('%02d:%02d', $outH, $outM),
        'late'   => $isLate && !$isAbsent,
        'suffix' => $suffix,
    ];
}

// 전체 직원 정보 맵 및 월 데이터 생성
$empInfoMap = [];
$empMonthData = [];
$allEmployees = array_merge($employees, $timeEmployees);
foreach ($allEmployees as $emp) {
    $name = $emp['name'];
    if (!isset($empInfoMap[$name])) {
        $empInfoMap[$name] = $emp;
    }
    if (!isset($empMonthData[$name])) {
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow = (int)date('w', mktime(0, 0, 0, $month, $d, $year));
            if ($dow === 0 || $dow === 6) {
                $days[$d] = null;
            } else {
                $days[$d] = genDayRecord($name, $year, $month, $d, $emp['normal']);
            }
        }
        $empMonthData[$name] = $days;
    }
}
?>

<style>

.att-day-table { width: 100%; border-collapse: collapse; font-size: 12px !important; line-height: 1 !important; }
.att-day-table thead th {
    text-align: center; font-weight: 500; color: var(--zm-text-muted, #64748b);
    padding: 0 2px 2px !important; border-bottom: 1px solid var(--zm-border, rgba(51,65,85,0.5));
    font-size: 11px !important; white-space: nowrap;
}
.att-day-table thead th:first-child { text-align: left; }
.att-day-table tbody td {
    padding: 2.5px 2px !important; text-align: center; white-space: nowrap; vertical-align: middle;
    font-size: 12px !important; line-height: 1.15 !important;
}
.att-day-table tbody tr {
    border-bottom: none !important;
}
.att-day-table tbody td:first-child {
    text-align: left; max-width: 5em; overflow: hidden; text-overflow: ellipsis;
}
.att-day-table tbody td:first-child span {
    max-width: 100%; overflow: hidden; text-overflow: ellipsis; display: block; text-align: left;
    line-height: inherit;
}
.att-day-table tbody tr { cursor: pointer; transition: background 0.1s; }
.att-day-table tbody tr:hover { background: rgba(0,0,0,0.08); }
.att-day-table tbody tr:hover td:first-child span { color: var(--zm-text-default); }
.att-day-table tbody tr.emp-late { background: rgba(234,88,12,0.04); }
.att-day-table tbody tr.emp-late td:first-child span { color: #c2410c; }
.att-day-table tbody tr.emp-late:hover td:first-child span { color: var(--zm-text-default); }
.att-day-table tbody tr.emp-absent { background: #fef2f2; }
.att-day-table tbody tr.emp-absent td { color: #b91c1c !important; }
.att-day-table tbody tr.emp-absent:hover { background: #fee2e2; }
.att-day-table tbody tr.emp-absent:hover td:first-child span { color: var(--zm-text-default); }
.att-day-table tbody .emp-late .att-clock-in { color: #c2410c !important; font-weight: 600; }
.att-tag {
    display: inline-block; padding: 1px 6px !important; border-radius: 4px;
    font-size: 10px !important; font-weight: 600; white-space: nowrap;
    line-height: 1.5; letter-spacing: 0.03em;
}
.att-tag-overtime { background: #dbeafe; color: #1d4ed8; }
.att-tag-leave    { background: #ede9fe; color: #6d28d9; }
.att-tag-remote   { background: #d1fae5; color: #047857; }
.att-tag-trip     { background: #fef3c7; color: #b45309; }
.att-tag-late     { background: #fee2e2; color: #dc2626; }
.att-tag-absent   { background: #fecaca; color: #b91c1c; }

.status-chip { background: rgba(51,65,85,0.6); color: #94a3b8; border: 1px solid transparent; }
.status-chip:hover { background: rgba(51,65,85,0.8); color: #e2e8f0; }
.status-chip.active { background: var(--zm-primary); color: #fff; border-color: var(--zm-primary); }

/* 다크 테마 오버라이드 */
html[data-theme="dark"] .att-day-table tbody tr.emp-absent { background: rgba(127,29,29,0.15); }
html[data-theme="dark"] .att-day-table tbody tr.emp-absent:hover { background: rgba(127,29,29,0.25); }
html[data-theme="dark"] .att-tag-overtime { background: rgba(30,58,138,0.3); color: #93c5fd; }
html[data-theme="dark"] .att-tag-leave    { background: rgba(91,33,182,0.3); color: #c4b5fd; }
html[data-theme="dark"] .att-tag-remote   { background: rgba(6,78,59,0.3); color: #6ee7b7; }
html[data-theme="dark"] .att-tag-trip     { background: rgba(120,53,15,0.3); color: #fcd34d; }
html[data-theme="dark"] .att-tag-late     { background: rgba(127,29,29,0.3); color: #fca5a5; }
html[data-theme="dark"] .att-tag-absent   { background: rgba(127,29,29,0.3); color: #fca5a5; }
</style>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 제목 -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-lg font-bold text-slate-100">부서 근태현황</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="attendance.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary border border-primary rounded-lg transition-colors">
                    <i data-lucide="calendar-check" class="w-3 h-3"></i>
                    근태 현황
                </a>
                <?php if (in_array(getCurrentUserRole(), ['admin', 'manager'], true)): ?>
                <a href="att_manage.php" class="btn btn-secondary">
                    <i data-lucide="settings" class="w-3 h-3"></i>
                    근태관리
                </a>
                <?php endif; ?>
            </div>
        </div>



        <!-- 캘린더 영역 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

            <!-- 월 네비게이션 -->
            <div class="flex items-center justify-center gap-4 mb-4">
                <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white hover:bg-primary-dark transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h3 class="text-2xl font-bold text-slate-100">
                    <?= $year ?>.<span class="text-primary"><?= $monthNames[$month] ?></span>
                </h3>
                <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white hover:bg-primary-dark transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- 필터 바 -->
            <div class="flex flex-wrap items-center gap-3 mb-4" id="filterBar">
                <!-- 직원 검색 -->
                <div class="relative">
                    <svg class="w-4 h-4 text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="empSearchInput" placeholder="직원 검색"
                        class="pl-8 pr-3 py-1.5 text-sm bg-slate-800 border border-slate-700 rounded-lg text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-primary w-36"
                        oninput="applyFilter()">
                </div>

                <!-- 부서 필터 -->
                <select id="deptFilter" onchange="applyFilter()"
                    class="px-3 py-1.5 text-sm bg-slate-800 border border-slate-700 rounded-lg text-slate-200 focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">전체 부서</option>
                    <?php
                    $depts = array_values(array_unique(array_filter(array_column($timeEmployees, 'dept'))));
                    sort($depts);
                    foreach ($depts as $dept):
                    ?>
                    <option value="<?= htmlspecialchars($dept, ENT_QUOTES) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 상태 필터 -->
                <div class="flex items-center gap-1.5" id="statusFilterWrap">
                    <span class="text-xs text-slate-500 mr-0.5">상태:</span>
                    <button class="status-chip active px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="">전체</button>
                    <button class="status-chip px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="지각">지각</button>
                    <button class="status-chip px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="결근">결근</button>
                    <button class="status-chip px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="야근">야근</button>
                    <button class="status-chip px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="휴가">휴가</button>
                    <button class="status-chip px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="재택">재택</button>
                    <button class="status-chip px-2 py-1 text-xs font-medium rounded-md transition-colors" data-status="출장">출장</button>
                </div>

                <!-- 필터 초기화 -->
                <button id="filterResetBtn" class="hidden px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:text-slate-200 bg-slate-800 border border-slate-700 rounded-lg transition-colors"
                    onclick="resetAllFilters()">
                    <svg class="w-3.5 h-3.5 inline -mt-px mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5 19a9 9 0 0115-6.7M19 5a9 9 0 01-15 6.7"/>
                    </svg>
                    초기화
                </button>
            </div>

            <!-- 직원 필터 배지 -->
            <div id="empFilterBadge" class="hidden mb-4 flex items-center gap-2">
                <span class="text-sm text-slate-400">직원 필터:</span>
                <span class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1 text-sm font-medium text-white bg-primary rounded-full">
                    <span id="empFilterName"></span>
                    <button onclick="clearEmpFilter()" class="ml-0.5 hover:opacity-80 transition-opacity" title="필터 해제">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
            </div>

            <!-- 캘린더 테이블 -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Sun</th>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Mon</th>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Tue</th>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Wed</th>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Thu</th>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Fri</th>
                            <th class="border border-slate-800 bg-primary text-white font-medium py-2 px-2">Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day = 1;
                        $totalCells = $startDayOfWeek + $daysInMonth;
                        $totalRows = ceil($totalCells / 7);
                        for ($row = 0; $row < $totalRows; $row++):
                        ?>
                        <tr>
                            <?php for ($col = 0; $col < 7; $col++):
                                $cellIndex = $row * 7 + $col;
                                $hasDay = ($cellIndex >= $startDayOfWeek && $day <= $daysInMonth);
                                $currentDay = $hasDay ? $day : 0;
                                $isSunday   = ($col === 0);
                                $isSaturday = ($col === 6);
                                $isToday    = ($hasDay && $day === $today && $month === $currentMonth && $year === $currentYear);
                                $isWeekend  = ($isSunday || $isSaturday);
                                if ($hasDay) $day++;
                            ?>
                            <td class="border border-slate-800 align-top px-1.5 py-1 <?= $isWeekend ? 'bg-slate-950' : '' ?>" style="min-width:140px;">
                                <?php if ($hasDay): ?>
                                    <!-- 날짜 -->
                                    <div class="flex items-center gap-1 mb-1 pb-0.5 border-b border-slate-800">
                                        <?php if ($isToday): ?>
                                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                                        <?php endif; ?>
                                        <span class="text-sm font-semibold <?= $isToday ? 'text-primary' : ($isSunday ? 'text-amber-500' : ($isSaturday ? 'text-primary' : 'text-slate-200')) ?>">
                                            <?= $currentDay ?>
                                        </span>
                                    </div>

                                    <?php if (!$isWeekend): ?>
                                    <!-- 출퇴근 테이블 -->
                                    <table class="att-day-table">
                                        <thead>
                                            <tr>
                                                <th>이름</th>
                                                <th>출근</th>
                                                <th>퇴근</th>
                                                <th>상태</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($timeEmployees as $te):
                                            $rec = $empMonthData[$te['name']][$currentDay] ?? null;
                                            if (!$rec) continue;
                                            $isAbsent = ($rec['suffix'] === '결근');
                                            $isLate = $rec['late'];
                                            $displaySuffix = $rec['suffix'];
                                            if ($isLate && !$displaySuffix) $displaySuffix = '지각';
                                            $suffixCls = match($displaySuffix) {
                                                '야근' => 'att-tag-overtime',
                                                '휴가' => 'att-tag-leave',
                                                '재택' => 'att-tag-remote',
                                                '출장' => 'att-tag-trip',
                                                '지각' => 'att-tag-late',
                                                '결근' => 'att-tag-absent',
                                                default => '',
                                            };
                                            $rowCls = $isAbsent ? 'emp-absent' : ($isLate ? 'emp-late' : '');
                                        ?>
                                        <tr class="emp-row <?= $rowCls ?>" data-emp="<?= htmlspecialchars($te['name']) ?>" data-division="<?= htmlspecialchars($te['division']) ?>" data-dept="<?= htmlspecialchars($te['dept']) ?>" data-status="<?= htmlspecialchars($displaySuffix) ?>"
                                            onclick="openEmpModal('<?= htmlspecialchars($te['name'], ENT_QUOTES) ?>')">
                                            <td><span class="text-slate-300 font-medium truncate"><?= htmlspecialchars($te['name']) ?></span></td>
                                            <td class="<?= $isLate ? 'att-clock-in' : 'text-slate-400' ?> tabular-nums"><?= $rec['in'] ?: '-' ?></td>
                                            <td class="text-slate-500 tabular-nums"><?= $rec['out'] ?: '-' ?></td>
                                            <td><?php if ($displaySuffix): ?><span class="att-tag <?= $suffixCls ?>"><?= $displaySuffix ?></span><?php endif; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- 직원 상세 모달 -->
<div id="empModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEmpModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col">

        <!-- 모달 헤더 -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-base" id="modalEmpInitial"></div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-slate-100" id="modalEmpName"></h3>
                        <span class="px-2 py-0.5 text-sm rounded-full bg-amber-100 text-amber-700 font-medium" id="modalEmpStatus"></span>
                    </div>
                    <p class="text-sm text-slate-400 mt-0.5" id="modalEmpInfo"></p>
                </div>
            </div>
            <button onclick="closeEmpModal()" class="text-slate-500 hover:text-slate-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- 요약 통계 -->
        <div class="grid grid-cols-3 divide-x divide-slate-800 border-b border-slate-800 bg-slate-950">
            <div class="text-center py-4">
                <div class="text-xl font-bold text-slate-100" id="modalStatWork">-</div>
                <div class="text-sm text-slate-400 mt-0.5">출근일수</div>
            </div>
            <div class="text-center py-4">
                <div class="text-xl font-bold text-amber-500" id="modalStatLate">-</div>
                <div class="text-sm text-slate-400 mt-0.5">지각횟수</div>
            </div>
            <div class="text-center py-4">
                <div class="text-xl font-bold text-primary" id="modalStatHoliday">-</div>
                <div class="text-sm text-slate-400 mt-0.5">휴가일수</div>
            </div>
        </div>

        <!-- 상세 테이블 -->
        <div class="overflow-y-auto flex-1 px-5 py-4">
            <table class="w-full text-sm emp-table border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="text-left pb-2 text-slate-300 font-medium w-16">날짜</th>
                        <th class="text-left pb-2 text-slate-300 font-medium w-8">요일</th>
                        <th class="text-left pb-2 text-slate-300 font-medium">출근</th>
                        <th class="text-left pb-2 text-slate-300 font-medium">퇴근</th>
                        <th class="text-left pb-2 text-slate-300 font-medium">상태</th>
                    </tr>
                </thead>
                <tbody id="modalEmpTable"></tbody>
            </table>
        </div>

        <!-- 모달 푸터 -->
        <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-800">
            <button id="btnFilterEmp" onclick="applyEmpFilter()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                이 직원만 보기
            </button>
            <button onclick="closeEmpModal()" class="btn btn-secondary">닫기</button>
        </div>
    </div>
</div>

<script>
const empMonthData = <?= json_encode($empMonthData, JSON_UNESCAPED_UNICODE) ?>;
const empInfoMap   = <?= json_encode($empInfoMap,   JSON_UNESCAPED_UNICODE) ?>;
const YEAR = <?= $year ?>, MONTH = <?= $month ?>, DAYS_IN_MONTH = <?= $daysInMonth ?>;
const DAY_NAMES = ['일', '월', '화', '수', '목', '금', '토'];

let currentModalEmp = null;
let activeFilterEmp = null;
let activeFilterDivision = '';
let activeFilterStatus = '';

// 부서-본부 매핑 데이터
const deptDivisionMap = <?php
    $map = [];
    foreach ($timeEmployees as $te) {
        if ($te['dept']) $map[$te['dept']] = $te['division'] ?: '';
    }
    echo json_encode($map, JSON_UNESCAPED_UNICODE);
?>;

function openEmpModal(name) {
    currentModalEmp = name;
    const info = empInfoMap[name] || {};
    const data = empMonthData[name] || {};

    document.getElementById('modalEmpInitial').textContent = name.charAt(0);
    document.getElementById('modalEmpName').textContent = name;
    document.getElementById('modalEmpStatus').textContent = info.status || '재직';
    document.getElementById('modalEmpInfo').textContent = [info.dept, info.position].filter(Boolean).join(' · ');

    // 통계 계산
    let workCount = 0, lateCount = 0, holidayCount = 0;
    for (let d = 1; d <= DAYS_IN_MONTH; d++) {
        const rec = data[d];
        if (!rec) continue;
        workCount++;
        if (rec.late) lateCount++;
        if (rec.suffix === '휴') holidayCount++;
    }
    document.getElementById('modalStatWork').textContent    = workCount + '일';
    document.getElementById('modalStatLate').textContent    = lateCount + '회';
    document.getElementById('modalStatHoliday').textContent = holidayCount + '일';

    // 상세 테이블 생성
    const tbody = document.getElementById('modalEmpTable');
    tbody.innerHTML = '';
    for (let d = 1; d <= DAYS_IN_MONTH; d++) {
        const dow = new Date(YEAR, MONTH - 1, d).getDay();
        if (dow === 0 || dow === 6) continue;

        const rec = data[d];
        const tr  = document.createElement('tr');
        tr.className = 'border-b border-slate-800 hover:bg-slate-950 transition-colors';

        let statusHtml = '<span class="text-slate-500">-</span>';
        if (rec) {
            if (rec.suffix === '휴')       statusHtml = '<span class="px-1.5 py-0.5 text-sm rounded bg-primary-light text-primary">휴가</span>';
            else if (rec.suffix === '야')  statusHtml = '<span class="px-1.5 py-0.5 text-sm rounded bg-primary-light text-primary">야근</span>';
            else if (rec.late)             statusHtml = '<span class="px-1.5 py-0.5 text-sm rounded bg-amber-100 text-amber-700">지각</span>';
            else                           statusHtml = '<span class="px-1.5 py-0.5 text-sm rounded bg-amber-100 text-amber-700">정상</span>';
        }

        tr.innerHTML = `
            <td class="py-2 pr-3 text-slate-200 tabular-nums">${MONTH}/${String(d).padStart(2,'0')}</td>
            <td class="py-2 pr-3 font-medium ${dow === 0 ? 'text-amber-500' : 'text-slate-300'}">${DAY_NAMES[dow]}</td>
            <td class="py-2 pr-3 tabular-nums ${rec && rec.late ? 'text-amber-500 font-medium' : 'text-slate-200'}">${rec ? rec.in : '-'}</td>
            <td class="py-2 pr-3 tabular-nums text-slate-200">${rec ? rec.out : '-'}</td>
            <td class="py-2">${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    }

    // 필터 버튼 상태
    updateFilterButton();

    document.getElementById('empModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function updateFilterButton() {
    const btn = document.getElementById('btnFilterEmp');
    if (activeFilterEmp && activeFilterEmp === currentModalEmp) {
        btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>필터 해제`;
        btn.className = 'btn btn-secondary';
    } else {
        btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>이 직원만 보기`;
        btn.className = 'inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors';
    }
}

function closeEmpModal() {
    document.getElementById('empModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentModalEmp = null;
}

function applyEmpFilter() {
    if (activeFilterEmp === currentModalEmp) {
        clearEmpFilter();
    } else {
        activeFilterEmp = currentModalEmp;
        applyFilter();
    }
    closeEmpModal();
}

function clearEmpFilter() {
    activeFilterEmp = null;
    applyFilter();
}

// 상태 필터 칩 이벤트
document.getElementById('statusFilterWrap').addEventListener('click', function(e) {
    const chip = e.target.closest('.status-chip');
    if (!chip) return;
    activeFilterStatus = chip.dataset.status;
    document.querySelectorAll('.status-chip').forEach(c => {
        c.classList.toggle('active', c.dataset.status === activeFilterStatus);
    });
    applyFilter();
});

function updateDeptOptions() {
    const sel = document.getElementById('deptFilter');
    const opts = sel.querySelectorAll('option');
    opts.forEach(opt => {
        if (!opt.value) return;
        const div = deptDivisionMap[opt.value] || '';
        opt.hidden = activeFilterDivision && div !== activeFilterDivision;
    });
    const selected = sel.querySelector('option:checked');
    if (selected && selected.hidden) sel.value = '';
}

function applyFilter() {
    const searchQ = (document.getElementById('empSearchInput').value || '').trim().toLowerCase();
    const deptVal = document.getElementById('deptFilter').value;

    document.querySelectorAll('.emp-row').forEach(row => {
        const empMatch  = !activeFilterEmp || row.dataset.emp === activeFilterEmp;
        const divMatch  = !activeFilterDivision || row.dataset.division === activeFilterDivision;
        const deptMatch = !deptVal || row.dataset.dept === deptVal;
        const nameMatch = !searchQ || row.dataset.emp.toLowerCase().includes(searchQ);
        const statusMatch = !activeFilterStatus || row.dataset.status === activeFilterStatus;
        row.style.display = (empMatch && divMatch && deptMatch && nameMatch && statusMatch) ? '' : 'none';
    });

    const badge    = document.getElementById('empFilterBadge');
    const nameSpan = document.getElementById('empFilterName');
    if (activeFilterEmp) {
        nameSpan.textContent = activeFilterEmp;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }

    const hasFilter = activeFilterEmp || activeFilterDivision || activeFilterStatus || searchQ || deptVal;
    document.getElementById('filterResetBtn').classList.toggle('hidden', !hasFilter);
}

function resetAllFilters() {
    activeFilterEmp = null;
    activeFilterDivision = '';
    activeFilterStatus = '';
    document.getElementById('empSearchInput').value = '';
    document.getElementById('deptFilter').value = '';
    document.querySelectorAll('.status-chip').forEach(c => c.classList.toggle('active', c.dataset.status === ''));
    updateDeptOptions();
    applyFilter();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEmpModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
