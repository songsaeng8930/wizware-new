<?php
/**
 * Zaemit 그룹웨어 - 연차관리 API
 *
 * 보안 정책:
 *  - applyLeave/cancelLeave · 본인 기록만 변경 가능 (세션 강제)
 *  - getHistory · 기본 본인, admin/manager는 타인 조회 가능
 *  - getAll/initBalance · admin/manager만
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/leave_helpers.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'initBalance'      => initBalance($pdo),
        'getAll'           => getAll($pdo),
        'getHistory'       => getHistory($pdo),
        'getPending'       => getPending($pdo),
        'applyLeave'       => applyLeave($pdo),
        'approveLeave'     => approveLeave($pdo),
        'rejectLeave'      => rejectLeave($pdo),
        'cancelLeave'      => cancelLeave($pdo),
        'adjustLeave'        => adjustLeave($pdo),
        'getAdjustments'     => getAdjustments($pdo),
        'deleteAdjustment'   => deleteAdjustment($pdo),
        // Phase 1: 공휴일
        'getHolidays'        => getHolidaysList($pdo),
        'addHoliday'         => addHoliday($pdo),
        'deleteHoliday'      => deleteHolidayAction($pdo),
        // Phase 2: 감사 로그
        'getAuditLog'        => getLeaveAuditLog($pdo),
        // Phase 3: 이월
        'requestCarryover'   => requestCarryover($pdo),
        'approveCarryover'   => approveCarryover($pdo),
        'rejectCarryover'    => rejectCarryover($pdo),
        'getCarryovers'      => getCarryovers($pdo),
        // Phase 4: 촉진
        'getPromotionTargets'=> getPromotionTargets($pdo),
        'createPromotion'    => createPromotion($pdo),
        'respondPromotion'   => respondPromotion($pdo),
        'designateDates'     => designateDates($pdo),
        'getPromotions'      => getPromotions($pdo),
        // Phase 5: 퇴사 정산
        'settleLeave'        => settleLeave($pdo),
        'getSettlements'     => getSettlements($pdo),
        // Phase 6: 대시보드
        'getDashboard'       => getDashboard($pdo),
        default              => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (PDOException $e) {
    error_log('AnnualLeave API: ' . $e->getMessage());
    respond(500, ['error' => '서버 오류가 발생했습니다.']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

/** 세션 사용자 ID · 없으면 401 */
function leaveSessionEmpId(): int
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id > 0) return $id;
    if (defined('AUTH_ENABLED') && AUTH_ENABLED === false) {
        return 0;
    }
    respond(401, ['error' => '인증된 사용자가 아닙니다.']);
}

/** admin/manager 여부 */
function leaveIsPrivileged(): bool
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    return in_array($role, ['admin', 'manager'], true);
}

/** 요청 대상 employee_id 결정 · 권한 없으면 본인으로 강제 */
function leaveResolveTargetEmpId(int $requested): int
{
    $self = leaveSessionEmpId();
    if ($requested <= 0) return $self;
    if ($requested === $self) return $self;
    if (leaveIsPrivileged()) return $requested;
    respond(403, ['error' => '타인의 연차 정보에 접근할 권한이 없습니다.']);
}

/* 테이블은 db/schema_annual_leave.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */

/* ── 근속년수 기반 연차일수 계산 ── */
function calcTotalDays(?string $hireDate, int $year): float
{
    if (!$hireDate) return 15.0;
    $hireYear = (int)substr($hireDate, 0, 4);
    $tenure = $year - $hireYear;
    if ($tenure < 1) return 11.0;
    $days = 15 + intdiv($tenure - 1, 2);
    return min($days, 25) * 1.0;
}

/* ── 전 직원 연차 잔액 초기화 (admin/manager 전용) ── */
function initBalance(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year = intval($_GET['year'] ?? date('Y'));

    $rows = $pdo->query("SELECT id, hire_date FROM employees WHERE employment_status != '퇴사' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare("INSERT INTO annual_leave (employee_id, year, total_days)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE total_days = VALUES(total_days)");

    $count = 0;
    foreach ($rows as $r) {
        $total = calcTotalDays($r['hire_date'], $year);
        $st->execute([$r['id'], $year, $total]);
        $count++;
    }
    respond(200, ['success' => true, 'initialized' => $count]);
}

/* ── 전 직원 연차 현황 조회 (admin/manager 전용) ── */
function getAll(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year = intval($_GET['year'] ?? date('Y'));

    $basePay = ['대표이사'=>5000000,'이사'=>4500000,'부장'=>4000000,'차장'=>3500000,
                 '과장'=>3000000,'대리'=>2500000,'주임'=>2200000,'사원'=>2000000,'인턴'=>1800000];

    // 데이터 없으면 자동 초기화
    $chk = $pdo->prepare("SELECT COUNT(*) FROM annual_leave WHERE year = ?");
    $chk->execute([$year]);
    if ((int)$chk->fetchColumn() === 0) {
        $rows = $pdo->query("SELECT id, hire_date FROM employees WHERE employment_status != '퇴사' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        $ins = $pdo->prepare("INSERT IGNORE INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)");
        foreach ($rows as $r) {
            $ins->execute([$r['id'], $year, calcTotalDays($r['hire_date'], $year)]);
        }
    }

    $st = $pdo->prepare("
        SELECT e.id, e.name, e.position,
               COALESCE(d.name, '') AS department_name,
               COALESCE(pd.name, d.name, '') AS division_name,
               COALESCE(al.total_days, 15.0) AS total_days,
               COALESCE(al.used_days, 0.0) AS used_days
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN departments pd ON d.parent_id = pd.id
        LEFT JOIN annual_leave al ON e.id = al.employee_id AND al.year = ?
        WHERE e.employment_status != '퇴사' AND e.is_active = 1
        ORDER BY e.id ASC
    ");
    $st->execute([$year]);
    $employees = [];
    $totalAll = 0; $usedAll = 0;

    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $total = floatval($r['total_days']);
        $used = floatval($r['used_days']);
        $remaining = $total - $used;
        $pos = $r['position'] ?: '사원';
        $daily = round(($basePay[$pos] ?? 2000000) / 209);

        $employees[] = [
            'no' => (int)$r['id'],
            'name' => $r['name'],
            'org' => $r['division_name'],
            'dept' => $r['department_name'],
            'total' => $total,
            'used' => $used,
            'remaining' => $remaining,
            'daily' => $daily,
            'compensation' => round($remaining * $daily),
        ];
        $totalAll += $total;
        $usedAll += $used;
    }

    respond(200, [
        'employees' => $employees,
        'stats' => [
            'total' => $totalAll,
            'used' => $usedAll,
            'remaining' => $totalAll - $usedAll,
            'rate' => $totalAll > 0 ? round($usedAll / $totalAll * 100) : 0,
        ],
    ]);
}

/* ── 특정 직원 사용 내역 (본인 기본, admin/manager는 타인 조회 가능) ── */
function getHistory(PDO $pdo): void
{
    $empId = leaveResolveTargetEmpId(intval($_GET['employee_id'] ?? 0));
    $year = intval($_GET['year'] ?? date('Y'));
    if (!$empId) respond(400, ['error' => '직원 ID가 필요합니다.']);

    $st = $pdo->prepare("
        SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.days_used, lr.reason, lr.status,
               lr.created_at, lr.approved_at, lr.approver_id,
               lr.penalty_flag, lr.penalty_reason,
               appr.name AS approver_name
        FROM leave_requests lr
        LEFT JOIN employees appr ON appr.id = lr.approver_id
        WHERE lr.employee_id = ? AND YEAR(lr.start_date) = ?
        ORDER BY lr.start_date DESC, lr.id DESC
    ");
    $st->execute([$empId, $year]);
    respond(200, ['history' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ── 결재 대기 중인 휴가 목록 (admin/manager 전용) ── */
function getPending(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $st = $pdo->query("
        SELECT lr.id, lr.employee_id, e.name AS employee_name,
               COALESCE(d.name, '') AS department_name,
               lr.leave_type, lr.start_date, lr.end_date, lr.days_used, lr.reason,
               lr.created_at, lr.penalty_flag, lr.penalty_reason
        FROM leave_requests lr
        LEFT JOIN employees e ON e.id = lr.employee_id
        LEFT JOIN departments d ON d.id = e.department_id
        WHERE lr.status = '대기'
        ORDER BY lr.created_at ASC
    ");
    respond(200, ['pending' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ── 연차 신청 (본인 기본, admin/manager는 타인 대신 신청 가능) ──
 *  - 신청 직후 status='대기', approved_at=NULL. 승인 전에는 annual_leave.used_days 차감하지 않음
 *  - 잔액 검증은 (승인분 + 대기분) 합산으로 수행하여 초과 신청 방지
 *  - 페널티: HAM(오전반차) && start_date==오늘 && 신청 시각 >= 09:00 → penalty_flag=1
 */
function applyLeave(PDO $pdo): void
{
    $input = getJsonInput();
    $empId = leaveResolveTargetEmpId(intval($input['employee_id'] ?? 0));
    $type = trim($input['leave_type'] ?? 'AL');
    $startDate = trim($input['start_date'] ?? '');
    $endDate = trim($input['end_date'] ?? $startDate);
    $reason = trim($input['reason'] ?? '');

    if (!$empId || !$startDate) {
        respond(400, ['error' => '직원과 날짜를 선택해주세요.']);
    }
    if (!$endDate) $endDate = $startDate;

    // 사용일수 계산 (주말+공휴일 자동 제외)
    $deductTypes = ['AL', 'HAM', 'HAP'];
    if (in_array($type, ['HAM', 'HAP'])) {
        $daysUsed = 0.5;
        $endDate = $startDate;
    } else {
        $daysUsed = calcBusinessDays($pdo, $startDate, $endDate);
    }

    $year = (int)substr($startDate, 0, 4);

    // 연차 차감 대상이면 잔여 체크 (승인분 + 대기분 합산)
    if (in_array($type, $deductTypes)) {
        $bal = $pdo->prepare("SELECT total_days, used_days FROM annual_leave WHERE employee_id = ? AND year = ?");
        $bal->execute([$empId, $year]);
        $b = $bal->fetch(PDO::FETCH_ASSOC);
        if (!$b) {
            $emp = $pdo->prepare("SELECT hire_date FROM employees WHERE id = ?");
            $emp->execute([$empId]);
            $hireDate = $emp->fetchColumn();
            $totalDays = calcTotalDays($hireDate ?: null, $year);
            $pdo->prepare("INSERT IGNORE INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)")
                ->execute([$empId, $year, $totalDays]);
            $total = $totalDays; $used = 0.0;
        } else {
            $total = floatval($b['total_days']); $used = floatval($b['used_days']);
        }

        $pending = $pdo->prepare("SELECT COALESCE(SUM(days_used), 0) FROM leave_requests
                                  WHERE employee_id = ? AND YEAR(start_date) = ?
                                    AND status = '대기' AND leave_type IN ('AL','HAM','HAP')");
        $pending->execute([$empId, $year]);
        $pendingDays = floatval($pending->fetchColumn());
        $remaining = $total - $used - $pendingDays;

        if ($remaining < $daysUsed) {
            respond(400, ['error' => sprintf('잔여 연차가 부족합니다. (가용 %.1f일, 대기 %.1f일 포함)', $remaining, $pendingDays)]);
        }
    }

    // 페널티 판정: 오전반차 + 당일 + 신청 시각 09:00 이후
    $penaltyFlag = 0; $penaltyReason = null;
    $today = date('Y-m-d');
    $nowTime = date('H:i:s');
    if ($type === 'HAM' && $startDate === $today && $nowTime >= '09:00:00') {
        $penaltyFlag = 1;
        $penaltyReason = '지각 반차 (09:00 이후 신청)';
    }

    // 트랜잭션: 대기 상태로 기록 삽입 (차감은 승인 시점에 수행)
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("INSERT INTO leave_requests
            (employee_id, leave_type, start_date, end_date, days_used, reason, status, penalty_flag, penalty_reason)
            VALUES (?, ?, ?, ?, ?, ?, '대기', ?, ?)");
        $st->execute([$empId, $type, $startDate, $endDate, $daysUsed, $reason, $penaltyFlag, $penaltyReason]);
        $leaveId = (int)$pdo->lastInsertId();

        $pdo->commit();

        logLeaveAudit($pdo, 'leave_applied', 'leave_request', $leaveId, null, null,
            ['employee_id' => $empId, 'type' => $type, 'start' => $startDate, 'end' => $endDate, 'days' => $daysUsed]);

        respond(200, [
            'success' => true,
            'leave_id' => $leaveId,
            'status' => '대기',
            'penalty_flag' => $penaltyFlag,
            'penalty_reason' => $penaltyReason,
            'message' => '휴가 신청이 접수되었습니다. 승인 대기 중입니다.',
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ── 연차 승인 (admin/manager 전용) ── */
function approveLeave(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $leaveId = intval($input['leave_id'] ?? 0);
    if (!$leaveId) respond(400, ['error' => 'leave_id가 필요합니다.']);

    $st = $pdo->prepare("SELECT employee_id, leave_type, days_used, status, YEAR(start_date) AS yr FROM leave_requests WHERE id = ?");
    $st->execute([$leaveId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(404, ['error' => '해당 기록을 찾을 수 없습니다.']);
    if ($row['status'] !== '대기') respond(400, ['error' => '대기 상태인 건만 승인할 수 있습니다.']);

    $deductTypes = ['AL', 'HAM', 'HAP'];
    $approverId = leaveSessionEmpId();

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE leave_requests SET status='승인', approved_at=NOW(), approver_id=? WHERE id=?")
            ->execute([$approverId, $leaveId]);

        if (in_array($row['leave_type'], $deductTypes)) {
            // 잔액 레코드 보장
            $chk = $pdo->prepare("SELECT id FROM annual_leave WHERE employee_id=? AND year=?");
            $chk->execute([$row['employee_id'], $row['yr']]);
            if (!$chk->fetchColumn()) {
                $emp = $pdo->prepare("SELECT hire_date FROM employees WHERE id = ?");
                $emp->execute([$row['employee_id']]);
                $hireDate = $emp->fetchColumn();
                $totalDays = calcTotalDays($hireDate ?: null, (int)$row['yr']);
                $pdo->prepare("INSERT IGNORE INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)")
                    ->execute([$row['employee_id'], $row['yr'], $totalDays]);
            }
            $pdo->prepare("UPDATE annual_leave SET used_days = used_days + ? WHERE employee_id = ? AND year = ?")
                ->execute([$row['days_used'], $row['employee_id'], $row['yr']]);
        }

        $pdo->commit();

        logLeaveAudit($pdo, 'leave_approved', 'leave_request', $leaveId, null,
            ['status' => '대기'], ['status' => '승인', 'approver_id' => $approverId]);

        $bal = $pdo->prepare("SELECT (total_days - used_days) AS remaining FROM annual_leave WHERE employee_id = ? AND year = ?");
        $bal->execute([$row['employee_id'], $row['yr']]);
        $remaining = floatval($bal->fetchColumn());

        respond(200, ['success' => true, 'remaining' => $remaining, 'message' => '휴가가 승인되었습니다.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ── 연차 반려 (admin/manager 전용) ── */
function rejectLeave(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $leaveId = intval($input['leave_id'] ?? 0);
    if (!$leaveId) respond(400, ['error' => 'leave_id가 필요합니다.']);

    $st = $pdo->prepare("SELECT status FROM leave_requests WHERE id = ?");
    $st->execute([$leaveId]);
    $status = $st->fetchColumn();
    if ($status === false) respond(404, ['error' => '해당 기록을 찾을 수 없습니다.']);
    if ($status !== '대기') respond(400, ['error' => '대기 상태인 건만 반려할 수 있습니다.']);

    $approverId = leaveSessionEmpId();
    $pdo->prepare("UPDATE leave_requests SET status='반려', approved_at=NOW(), approver_id=? WHERE id=?")
        ->execute([$approverId, $leaveId]);

    logLeaveAudit($pdo, 'leave_rejected', 'leave_request', $leaveId, null,
        ['status' => '대기'], ['status' => '반려', 'approver_id' => $approverId]);

    respond(200, ['success' => true, 'message' => '휴가가 반려되었습니다.']);
}

/* ── 연차 취소 (본인 건만, admin/manager는 예외) ──
 *  - 대기 상태: 그대로 취소 처리 (차감 전이므로 복원 불필요)
 *  - 승인 상태: 차감분 복원
 *  - 반려/취소 상태: 재취소 불가
 */
function cancelLeave(PDO $pdo): void
{
    $input = getJsonInput();
    $leaveId = intval($input['leave_id'] ?? 0);
    if (!$leaveId) respond(400, ['error' => 'leave_id가 필요합니다.']);

    $st = $pdo->prepare("SELECT employee_id, leave_type, days_used, status, YEAR(start_date) AS yr FROM leave_requests WHERE id = ?");
    $st->execute([$leaveId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(404, ['error' => '해당 기록을 찾을 수 없습니다.']);
    if (!in_array($row['status'], ['대기', '승인'], true)) {
        respond(400, ['error' => '대기 또는 승인 상태인 건만 취소할 수 있습니다.']);
    }

    // 소유자 검증 · 관리자 아닌 경우 본인 기록만 취소 가능
    $self = leaveSessionEmpId();
    if ((int)$row['employee_id'] !== $self && !leaveIsPrivileged()) {
        respond(403, ['error' => '본인 건만 취소할 수 있습니다.']);
    }

    $deductTypes = ['AL', 'HAM', 'HAP'];
    $wasApproved = ($row['status'] === '승인');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE leave_requests SET status = '취소' WHERE id = ?")->execute([$leaveId]);

        if ($wasApproved && in_array($row['leave_type'], $deductTypes)) {
            $pdo->prepare("UPDATE annual_leave SET used_days = GREATEST(used_days - ?, 0) WHERE employee_id = ? AND year = ?")
                ->execute([$row['days_used'], $row['employee_id'], $row['yr']]);
        }
        $pdo->commit();

        logLeaveAudit($pdo, 'leave_cancelled', 'leave_request', $leaveId, null,
            ['status' => $row['status']], ['status' => '취소']);

        $bal = $pdo->prepare("SELECT (total_days - used_days) AS remaining FROM annual_leave WHERE employee_id = ? AND year = ?");
        $bal->execute([$row['employee_id'], $row['yr']]);
        $remaining = floatval($bal->fetchColumn());

        respond(200, ['success' => true, 'remaining' => $remaining, 'message' => '휴가가 취소되었습니다.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ── 연차 수동 조정 (admin/manager 전용) ──
 *  - 포상연차, 이월, 착오보정 등
 *  - annual_leave.total_days를 직접 가감하고 이력을 leave_adjustments에 기록
 */
function adjustLeave(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $empId      = intval($input['employee_id'] ?? 0);
    $year       = intval($input['year'] ?? date('Y'));
    $adjustType = trim($input['adjust_type'] ?? '');
    $adjustDays = floatval($input['adjust_days'] ?? 0);
    $reason     = trim($input['reason'] ?? '');
    $category   = trim($input['category'] ?? '기타');

    if (!$empId) respond(400, ['error' => '직원을 선택해주세요.']);
    if (!in_array($adjustType, ['add', 'deduct'], true)) respond(400, ['error' => '조정 유형(add/deduct)을 선택해주세요.']);
    if ($adjustDays <= 0 || $adjustDays > 30) respond(400, ['error' => '조정일수는 0.5~30일 사이여야 합니다.']);
    if (fmod($adjustDays * 2, 1) != 0) respond(400, ['error' => '조정일수는 0.5 단위여야 합니다.']);
    if (!$reason) respond(400, ['error' => '조정 사유를 입력해주세요.']);

    $createdBy = leaveSessionEmpId();

    $pdo->beginTransaction();
    try {
        // annual_leave 레코드 보장
        $chk = $pdo->prepare("SELECT id, total_days FROM annual_leave WHERE employee_id = ? AND year = ?");
        $chk->execute([$empId, $year]);
        $bal = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$bal) {
            $emp = $pdo->prepare("SELECT hire_date FROM employees WHERE id = ?");
            $emp->execute([$empId]);
            $hireDate = $emp->fetchColumn();
            $totalDays = calcTotalDays($hireDate ?: null, $year);
            $pdo->prepare("INSERT INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)")
                ->execute([$empId, $year, $totalDays]);
            $currentTotal = $totalDays;
        } else {
            $currentTotal = floatval($bal['total_days']);
        }

        // 차감 시 total이 음수되지 않도록 검증
        if ($adjustType === 'deduct') {
            $usedSt = $pdo->prepare("SELECT used_days FROM annual_leave WHERE employee_id = ? AND year = ?");
            $usedSt->execute([$empId, $year]);
            $used = floatval($usedSt->fetchColumn());
            if ($currentTotal - $adjustDays < $used) {
                $pdo->rollBack();
                respond(400, ['error' => sprintf('이미 %.1f일 사용 중이라 %.1f일을 차감할 수 없습니다.', $used, $adjustDays)]);
            }
        }

        // total_days 가감
        $delta = ($adjustType === 'add') ? $adjustDays : -$adjustDays;
        $pdo->prepare("UPDATE annual_leave SET total_days = total_days + ? WHERE employee_id = ? AND year = ?")
            ->execute([$delta, $empId, $year]);

        // 이력 기록
        $pdo->prepare("INSERT INTO leave_adjustments (employee_id, year, adjust_type, adjust_days, reason, category, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$empId, $year, $adjustType, $adjustDays, $reason, $category, $createdBy]);

        $pdo->commit();

        // 갱신된 잔액 조회
        $newBal = $pdo->prepare("SELECT total_days, used_days FROM annual_leave WHERE employee_id = ? AND year = ?");
        $newBal->execute([$empId, $year]);
        $nb = $newBal->fetch(PDO::FETCH_ASSOC);

        logLeaveAudit($pdo, 'leave_adjusted', 'annual_leave', $empId, null,
            ['total' => $currentTotal],
            ['total' => floatval($nb['total_days']), 'delta' => $delta, 'category' => $category, 'reason' => $reason]);

        $label = $adjustType === 'add' ? '추가' : '차감';
        respond(200, [
            'success' => true,
            'total' => floatval($nb['total_days']),
            'used' => floatval($nb['used_days']),
            'remaining' => floatval($nb['total_days']) - floatval($nb['used_days']),
            'message' => sprintf('연차 %.1f일 %s 완료', $adjustDays, $label),
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ── 연차 조정 이력 조회 (admin/manager 전용) ── */
function getAdjustments(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $empId = intval($_GET['employee_id'] ?? 0);
    $year  = intval($_GET['year'] ?? date('Y'));
    if (!$empId) respond(400, ['error' => '직원 ID가 필요합니다.']);

    try {
        $pdo->query("SELECT 1 FROM leave_adjustments LIMIT 0");
    } catch (PDOException $e) {
        respond(200, ['adjustments' => []]);
    }

    $st = $pdo->prepare("
        SELECT la.id, la.adjust_type, la.adjust_days, la.reason, la.category, la.created_at,
               e.name AS created_by_name
        FROM leave_adjustments la
        LEFT JOIN employees e ON e.id = la.created_by
        WHERE la.employee_id = ? AND la.year = ?
        ORDER BY la.created_at DESC
    ");
    $st->execute([$empId, $year]);
    respond(200, ['adjustments' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ── 연차 조정 삭제 (admin/manager 전용, 되돌리기) ── */
function deleteAdjustment(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $adjId = intval($input['id'] ?? 0);
    if (!$adjId) respond(400, ['error' => 'ID가 필요합니다.']);

    $st = $pdo->prepare("SELECT employee_id, year, adjust_type, adjust_days FROM leave_adjustments WHERE id = ?");
    $st->execute([$adjId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(404, ['error' => '해당 조정 기록을 찾을 수 없습니다.']);

    $pdo->beginTransaction();
    try {
        $reverseDelta = ($row['adjust_type'] === 'add') ? -$row['adjust_days'] : $row['adjust_days'];

        $bal = $pdo->prepare("SELECT total_days, used_days FROM annual_leave WHERE employee_id = ? AND year = ?");
        $bal->execute([$row['employee_id'], $row['year']]);
        $cur = $bal->fetch(PDO::FETCH_ASSOC);
        if ($cur) {
            $newTotal = floatval($cur['total_days']) + $reverseDelta;
            $used = floatval($cur['used_days']);
            if ($newTotal < $used) {
                $pdo->rollBack();
                respond(400, ['error' => sprintf('이미 %.1f일 사용 중이라 이 조정을 되돌릴 수 없습니다.', $used)]);
            }
        }

        $pdo->prepare("UPDATE annual_leave SET total_days = GREATEST(total_days + ?, 0) WHERE employee_id = ? AND year = ?")
            ->execute([$reverseDelta, $row['employee_id'], $row['year']]);

        $pdo->prepare("DELETE FROM leave_adjustments WHERE id = ?")->execute([$adjId]);
        $pdo->commit();

        respond(200, ['success' => true, 'message' => '조정이 취소되었습니다.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ═══════════════════════════════════════════════════════════════════
 *  공통: 날짜 유효성 검증
 * ═══════════════════════════════════════════════════════════════════ */

function isValidDate(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function ensureHolidaysTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY, year SMALLINT NOT NULL, holiday_date DATE NOT NULL,
        name VARCHAR(50) NOT NULL, type ENUM('법정','대체','임시') NOT NULL DEFAULT '법정',
        created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_date (holiday_date), INDEX idx_year (year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/* ═══════════════════════════════════════════════════════════════════
 *  Phase 1: 공휴일 관리
 * ═══════════════════════════════════════════════════════════════════ */

function getHolidaysList(PDO $pdo): void
{
    $year = intval($_GET['year'] ?? date('Y'));
    ensureHolidaysTable($pdo);
    try {
        $st = $pdo->prepare("SELECT id, holiday_date, name, type FROM holidays WHERE year = ? ORDER BY holiday_date");
        $st->execute([$year]);
        respond(200, ['holidays' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        respond(200, ['holidays' => []]);
    }
}

function addHoliday(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $date = trim($input['holiday_date'] ?? '');
    $name = trim($input['name'] ?? '');
    $type = trim($input['type'] ?? '임시');

    if (!$date || !$name) respond(400, ['error' => '날짜와 명칭을 입력해주세요.']);
    if (!isValidDate($date)) respond(400, ['error' => '날짜 형식이 올바르지 않습니다. (YYYY-MM-DD)']);
    if (!in_array($type, ['법정', '대체', '임시'], true)) $type = '임시';

    $year = (int)substr($date, 0, 4);
    $createdBy = leaveSessionEmpId();

    ensureHolidaysTable($pdo);

    try {
        $st = $pdo->prepare("INSERT INTO holidays (year, holiday_date, name, type, created_by) VALUES (?, ?, ?, ?, ?)");
        $st->execute([$year, $date, $name, $type, $createdBy]);
        respond(200, ['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => '공휴일이 등록되었습니다.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            respond(400, ['error' => '이미 등록된 날짜입니다.']);
        }
        throw $e;
    }
}

function deleteHolidayAction(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    if (!$id) respond(400, ['error' => 'ID가 필요합니다.']);

    ensureHolidaysTable($pdo);
    $pdo->prepare("DELETE FROM holidays WHERE id = ?")->execute([$id]);
    respond(200, ['success' => true, 'message' => '공휴일이 삭제되었습니다.']);
}

/* ═══════════════════════════════════════════════════════════════════
 *  Phase 2: 감사 로그 조회
 * ═══════════════════════════════════════════════════════════════════ */

function getLeaveAuditLog(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $limit = min(intval($_GET['limit'] ?? 50), 200);
    $offset = max(intval($_GET['offset'] ?? 0), 0);

    try {
        $st = $pdo->prepare("
            SELECT event_type, actor_name, target_type, target_id, target_label,
                   old_value, new_value, comment, created_at
            FROM approval_audit_log
            WHERE event_category = 'leave'
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $st->execute([$limit, $offset]);
        respond(200, ['logs' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        respond(200, ['logs' => []]);
    }
}

/* ═══════════════════════════════════════════════════════════════════
 *  Phase 3: 연차 이월 관리
 * ═══════════════════════════════════════════════════════════════════ */

function ensureCarryoverTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_carryovers (
        id INT AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL, from_year SMALLINT NOT NULL,
        to_year SMALLINT NOT NULL, days DECIMAL(4,1) NOT NULL, reason VARCHAR(200) NULL,
        status ENUM('신청','승인','반려') NOT NULL DEFAULT '신청',
        agreement_date DATE NULL, approved_by INT NULL, approved_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_emp_year (employee_id, from_year),
        UNIQUE KEY uq_emp_from_to (employee_id, from_year, to_year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function requestCarryover(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $empId    = intval($input['employee_id'] ?? 0);
    $fromYear = intval($input['from_year'] ?? (int)date('Y') - 1);
    $toYear   = $fromYear + 1;
    $days     = floatval($input['days'] ?? 0);
    $reason   = trim($input['reason'] ?? '');

    if (!$empId) respond(400, ['error' => '직원을 선택해주세요.']);
    if ($days <= 0 || $days > 25) respond(400, ['error' => '이월 일수는 0.5~25일이어야 합니다.']);
    if (!$reason) respond(400, ['error' => '이월 사유를 입력해주세요.']);

    $bal = $pdo->prepare("SELECT total_days, used_days FROM annual_leave WHERE employee_id = ? AND year = ?");
    $bal->execute([$empId, $fromYear]);
    $b = $bal->fetch(PDO::FETCH_ASSOC);
    $remaining = $b ? floatval($b['total_days']) - floatval($b['used_days']) : 0;
    if ($days > $remaining) respond(400, ['error' => sprintf('잔여일(%.1f)을 초과하는 이월은 불가합니다.', $remaining)]);

    ensureCarryoverTable($pdo);

    try {
        $pdo->prepare("INSERT INTO leave_carryovers (employee_id, from_year, to_year, days, reason) VALUES (?, ?, ?, ?, ?)")
            ->execute([$empId, $fromYear, $toYear, $days, $reason]);
        respond(200, ['success' => true, 'message' => '이월 신청이 등록되었습니다.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) respond(400, ['error' => '이미 동일 연도 이월 신청이 있습니다.']);
        throw $e;
    }
}

function approveCarryover(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $agreementDate = trim($input['agreement_date'] ?? date('Y-m-d'));
    if (!$id) respond(400, ['error' => 'ID가 필요합니다.']);

    $st = $pdo->prepare("SELECT * FROM leave_carryovers WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(404, ['error' => '해당 이월 기록을 찾을 수 없습니다.']);
    if ($row['status'] !== '신청') respond(400, ['error' => '신청 상태만 승인 가능합니다.']);

    $approverId = leaveSessionEmpId();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE leave_carryovers SET status='승인', approved_by=?, approved_at=NOW(), agreement_date=? WHERE id=?")
            ->execute([$approverId, $agreementDate, $id]);

        // to_year 잔액 가산 (annual_leave 레코드 보장)
        $chk = $pdo->prepare("SELECT id FROM annual_leave WHERE employee_id = ? AND year = ?");
        $chk->execute([$row['employee_id'], $row['to_year']]);
        if (!$chk->fetchColumn()) {
            $emp = $pdo->prepare("SELECT hire_date FROM employees WHERE id = ?");
            $emp->execute([$row['employee_id']]);
            $totalDays = calcTotalDays($emp->fetchColumn() ?: null, (int)$row['to_year']);
            $pdo->prepare("INSERT IGNORE INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)")
                ->execute([$row['employee_id'], $row['to_year'], $totalDays]);
        }
        $pdo->prepare("UPDATE annual_leave SET total_days = total_days + ? WHERE employee_id = ? AND year = ?")
            ->execute([$row['days'], $row['employee_id'], $row['to_year']]);

        // from_year 잔액 차감 (이중 부여 방지)
        $pdo->prepare("UPDATE annual_leave SET total_days = GREATEST(total_days - ?, 0) WHERE employee_id = ? AND year = ?")
            ->execute([$row['days'], $row['employee_id'], $row['from_year']]);

        $pdo->commit();

        logLeaveAudit($pdo, 'carryover_approved', 'leave_carryover', $id, null,
            ['from_year' => $row['from_year'], 'days' => $row['days'], 'status' => '신청'],
            ['status' => '승인', 'agreement_date' => $agreementDate]);

        respond(200, ['success' => true, 'message' => sprintf('%.1f일 이월이 승인되었습니다.', $row['days'])]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function rejectCarryover(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    if (!$id) respond(400, ['error' => 'ID가 필요합니다.']);

    $stmt = $pdo->prepare("UPDATE leave_carryovers SET status='반려', approved_by=?, approved_at=NOW() WHERE id=? AND status='신청'");
    $stmt->execute([leaveSessionEmpId(), $id]);
    if ($stmt->rowCount() === 0) respond(400, ['error' => '이미 처리된 건입니다.']);
    respond(200, ['success' => true, 'message' => '이월이 반려되었습니다.']);
}

function getCarryovers(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year = intval($_GET['year'] ?? date('Y'));

    ensureCarryoverTable($pdo);

    $st = $pdo->prepare("
        SELECT c.*, e.name AS employee_name, COALESCE(d.name,'') AS dept_name, appr.name AS approver_name
        FROM leave_carryovers c
        JOIN employees e ON e.id = c.employee_id
        LEFT JOIN departments d ON d.id = e.department_id
        LEFT JOIN employees appr ON appr.id = c.approved_by
        WHERE c.from_year = ? OR c.to_year = ?
        ORDER BY c.created_at DESC
    ");
    $st->execute([$year, $year]);
    respond(200, ['carryovers' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ═══════════════════════════════════════════════════════════════════
 *  Phase 4: 연차 촉진 제도
 * ═══════════════════════════════════════════════════════════════════ */

function ensurePromotionTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_promotions (
        id INT AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL, year SMALLINT NOT NULL,
        stage TINYINT NOT NULL, notified_at DATETIME NULL, deadline DATE NOT NULL,
        response_status ENUM('미응답','계획제출','지정통보') NOT NULL DEFAULT '미응답',
        use_plan_dates JSON NULL, designated_dates JSON NULL, responded_at DATETIME NULL,
        created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_emp_year_stage (employee_id, year, stage),
        INDEX idx_year_stage (year, stage)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getPromotionTargets(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year  = intval($_GET['year'] ?? date('Y'));
    $stage = intval($_GET['stage'] ?? 1);

    ensurePromotionTable($pdo);

    $st = $pdo->prepare("
        SELECT e.id, e.name, COALESCE(d.name,'') AS dept, al.total_days, al.used_days,
               (al.total_days - al.used_days) AS remaining
        FROM annual_leave al
        JOIN employees e ON e.id = al.employee_id AND e.is_active = 1
        LEFT JOIN departments d ON d.id = e.department_id
        LEFT JOIN leave_promotions lp ON lp.employee_id = al.employee_id AND lp.year = al.year AND lp.stage = ?
        WHERE al.year = ? AND (al.total_days - al.used_days) > 0 AND lp.id IS NULL
        ORDER BY (al.total_days - al.used_days) DESC
    ");
    $st->execute([$stage, $year]);
    respond(200, ['targets' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

function createPromotion(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $empIds = $input['employee_ids'] ?? [];
    $year   = intval($input['year'] ?? date('Y'));
    $stage  = intval($input['stage'] ?? 1);

    if (empty($empIds)) respond(400, ['error' => '대상 직원을 선택해주세요.']);
    if (!in_array($stage, [1, 2], true)) respond(400, ['error' => '1차 또는 2차만 가능합니다.']);

    // 타이밍 검증
    $month = (int)date('n');
    if ($stage === 1 && $month < 7) respond(400, ['error' => '1차 촉진은 7월 이후부터 가능합니다.']);
    if ($stage === 2 && $month < 11) respond(400, ['error' => '2차 촉진은 11월 이후부터 가능합니다.']);

    $deadline = ($stage === 1)
        ? date('Y-m-d', strtotime('+10 days'))
        : date('Y-12-31');

    ensurePromotionTable($pdo);
    $createdBy = leaveSessionEmpId();
    $count = 0;

    foreach ($empIds as $eid) {
        $eid = intval($eid);
        try {
            $pdo->prepare("INSERT INTO leave_promotions (employee_id, year, stage, notified_at, deadline, created_by)
                            VALUES (?, ?, ?, NOW(), ?, ?)")
                ->execute([$eid, $year, $stage, $deadline, $createdBy]);

            $bal = $pdo->prepare("SELECT (total_days - used_days) AS remaining FROM annual_leave WHERE employee_id = ? AND year = ?");
            $bal->execute([$eid, $year]);
            $rem = floatval($bal->fetchColumn());

            $stageLabel = $stage === 1 ? '1차' : '2차';
            $msg = $stage === 1
                ? sprintf('미사용 연차 %.1f일에 대한 사용 계획을 %s까지 제출해주세요.', $rem, $deadline)
                : sprintf('미사용 연차 %.1f일에 대해 회사가 사용 시기를 지정했습니다.', $rem);

            createNotification($pdo, $eid, 'leave_promotion',
                "연차 사용 촉진 ({$stageLabel} 통보)", $msg, '?page=labor&tab=annual');
            $count++;
        } catch (PDOException $e) {
            if ($e->getCode() != 23000) throw $e;
        }
    }

    logLeaveAudit($pdo, 'promotion_created', 'leave_promotion', null, null, null,
        ['year' => $year, 'stage' => $stage, 'count' => $count]);

    respond(200, ['success' => true, 'count' => $count, 'message' => "{$count}명에게 촉진 통보를 발송했습니다."]);
}

function respondPromotion(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);

    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $planDates = $input['plan_dates'] ?? [];

    if (!$id) respond(400, ['error' => 'ID가 필요합니다.']);
    if (empty($planDates)) respond(400, ['error' => '사용 계획 날짜를 입력해주세요.']);

    $st = $pdo->prepare("SELECT employee_id, stage, response_status FROM leave_promotions WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(404, ['error' => '해당 촉진 기록을 찾을 수 없습니다.']);
    if ($row['stage'] != 1) respond(400, ['error' => '1차 촉진에만 사용 계획을 제출할 수 있습니다.']);
    if ($row['response_status'] !== '미응답') respond(400, ['error' => '이미 계획을 제출한 건입니다.']);

    $self = leaveSessionEmpId();
    if ((int)$row['employee_id'] !== $self && !leaveIsPrivileged()) {
        respond(403, ['error' => '본인 건만 응답할 수 있습니다.']);
    }

    $pdo->prepare("UPDATE leave_promotions SET response_status='계획제출', use_plan_dates=?, responded_at=NOW() WHERE id=?")
        ->execute([json_encode($planDates, JSON_UNESCAPED_UNICODE), $id]);

    respond(200, ['success' => true, 'message' => '사용 계획이 제출되었습니다.']);
}

function designateDates(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $dates = $input['designated_dates'] ?? [];

    if (!$id) respond(400, ['error' => 'ID가 필요합니다.']);
    if (empty($dates)) respond(400, ['error' => '지정 날짜를 입력해주세요.']);

    $stmt = $pdo->prepare("UPDATE leave_promotions SET response_status='지정통보', designated_dates=?, responded_at=NOW() WHERE id=? AND response_status != '지정통보'");
    $stmt->execute([json_encode($dates, JSON_UNESCAPED_UNICODE), $id]);
    if ($stmt->rowCount() === 0) respond(400, ['error' => '이미 지정 완료된 건입니다.']);

    respond(200, ['success' => true, 'message' => '사용 날짜가 지정되었습니다.']);
}

function getPromotions(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year = intval($_GET['year'] ?? date('Y'));

    ensurePromotionTable($pdo);

    $st = $pdo->prepare("
        SELECT lp.*, e.name AS employee_name, COALESCE(d.name,'') AS dept_name,
               (al.total_days - al.used_days) AS remaining
        FROM leave_promotions lp
        JOIN employees e ON e.id = lp.employee_id
        LEFT JOIN departments d ON d.id = e.department_id
        LEFT JOIN annual_leave al ON al.employee_id = lp.employee_id AND al.year = lp.year
        WHERE lp.year = ?
        ORDER BY lp.stage, lp.response_status, lp.notified_at DESC
    ");
    $st->execute([$year]);
    respond(200, ['promotions' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ═══════════════════════════════════════════════════════════════════
 *  Phase 5: 퇴사자 연차 정산
 * ═══════════════════════════════════════════════════════════════════ */

function ensureSettlementTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_settlements (
        id INT AUTO_INCREMENT PRIMARY KEY, employee_id INT NOT NULL, year SMALLINT NOT NULL,
        resign_date DATE NOT NULL, hire_date DATE NOT NULL, worked_months DECIMAL(4,1) NOT NULL,
        prorated_days DECIMAL(4,1) NOT NULL, used_days DECIMAL(4,1) NOT NULL,
        remaining_days DECIMAL(4,1) NOT NULL, base_salary BIGINT NOT NULL DEFAULT 0,
        daily_wage BIGINT NOT NULL DEFAULT 0, settlement_amount BIGINT NOT NULL DEFAULT 0,
        settled_by INT NULL, settled_at DATETIME DEFAULT CURRENT_TIMESTAMP, memo VARCHAR(200) NULL,
        INDEX idx_emp (employee_id), UNIQUE KEY uq_emp_year (employee_id, year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function settleLeave(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'POST만 허용']);
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);

    $input = getJsonInput();
    $empId = intval($input['employee_id'] ?? 0);
    $year  = intval($input['year'] ?? date('Y'));
    $baseSalary = intval($input['base_salary'] ?? 0);
    $memo  = trim($input['memo'] ?? '');

    if (!$empId) respond(400, ['error' => '직원을 선택해주세요.']);

    $emp = $pdo->prepare("SELECT hire_date, resign_date, name FROM employees WHERE id = ?");
    $emp->execute([$empId]);
    $e = $emp->fetch(PDO::FETCH_ASSOC);
    if (!$e) respond(404, ['error' => '직원을 찾을 수 없습니다.']);
    if (!$e['resign_date']) respond(400, ['error' => '퇴사일이 설정되지 않은 직원입니다.']);

    $hireDate   = $e['hire_date'];
    $resignDate = $e['resign_date'];

    // 해당 연도 근무 월수
    $yearStart = new DateTime("{$year}-01-01");
    $startDate = new DateTime(max($hireDate, "{$year}-01-01"));
    $endDate   = new DateTime(min($resignDate, "{$year}-12-31"));
    if ($startDate > $endDate) respond(400, ['error' => '해당 연도에 근무 기록이 없습니다.']);
    $workedMonths = round($startDate->diff($endDate)->days / 30.44, 1);

    // 연차 잔액
    $bal = $pdo->prepare("SELECT total_days, used_days FROM annual_leave WHERE employee_id = ? AND year = ?");
    $bal->execute([$empId, $year]);
    $b = $bal->fetch(PDO::FETCH_ASSOC);
    $totalDays = $b ? floatval($b['total_days']) : calcTotalDays($hireDate, $year);
    $usedDays  = $b ? floatval($b['used_days']) : 0;

    // 일할 계산
    $proratedDays = round($totalDays * ($workedMonths / 12), 1);
    $remainingDays = max(0, $proratedDays - $usedDays);

    // 일급 = 기본급 / 21.67
    if ($baseSalary <= 0) respond(400, ['error' => '기본급을 입력해주세요.']);
    $dailyWage = (int)round($baseSalary / MONTHLY_WORKING_DAYS);
    $settlementAmount = (int)round($remainingDays * $dailyWage);

    ensureSettlementTable($pdo);
    $settledBy = leaveSessionEmpId();

    $pdo->beginTransaction();
    try {
        // 대기 중 신청 자동 취소
        $cancelled = $pdo->prepare("UPDATE leave_requests SET status='취소' WHERE employee_id=? AND status='대기'");
        $cancelled->execute([$empId]);
        $cancelledCount = $cancelled->rowCount();

        $pdo->prepare("INSERT INTO leave_settlements
            (employee_id, year, resign_date, hire_date, worked_months, prorated_days, used_days,
             remaining_days, base_salary, daily_wage, settlement_amount, settled_by, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
             resign_date=VALUES(resign_date), worked_months=VALUES(worked_months), prorated_days=VALUES(prorated_days),
             used_days=VALUES(used_days), remaining_days=VALUES(remaining_days), base_salary=VALUES(base_salary),
             daily_wage=VALUES(daily_wage), settlement_amount=VALUES(settlement_amount), settled_by=VALUES(settled_by),
             settled_at=NOW(), memo=VALUES(memo)")
            ->execute([$empId, $year, $resignDate, $hireDate, $workedMonths, $proratedDays,
                       $usedDays, $remainingDays, $baseSalary, $dailyWage, $settlementAmount, $settledBy, $memo]);

        $pdo->commit();

        logLeaveAudit($pdo, 'leave_settled', 'leave_settlement', $empId, $e['name'], null,
            ['remaining' => $remainingDays, 'daily_wage' => $dailyWage, 'amount' => $settlementAmount, 'cancelled' => $cancelledCount]);

        respond(200, [
            'success' => true,
            'settlement' => [
                'worked_months' => $workedMonths,
                'prorated_days' => $proratedDays,
                'used_days' => $usedDays,
                'remaining_days' => $remainingDays,
                'daily_wage' => $dailyWage,
                'settlement_amount' => $settlementAmount,
                'cancelled_requests' => $cancelledCount,
            ],
            'message' => sprintf('%s님의 연차 정산이 완료되었습니다. (미사용 %.1f일, 보상액 %s원)',
                $e['name'], $remainingDays, number_format($settlementAmount)),
        ]);
    } catch (PDOException $e2) {
        $pdo->rollBack();
        throw $e2;
    }
}

function getSettlements(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year = intval($_GET['year'] ?? date('Y'));

    ensureSettlementTable($pdo);

    $st = $pdo->prepare("
        SELECT ls.*, e.name AS employee_name, COALESCE(d.name,'') AS dept_name, settler.name AS settled_by_name
        FROM leave_settlements ls
        JOIN employees e ON e.id = ls.employee_id
        LEFT JOIN departments d ON d.id = e.department_id
        LEFT JOIN employees settler ON settler.id = ls.settled_by
        WHERE ls.year = ?
        ORDER BY ls.settled_at DESC
    ");
    $st->execute([$year]);
    respond(200, ['settlements' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ═══════════════════════════════════════════════════════════════════
 *  Phase 6: 사용 현황 대시보드
 * ═══════════════════════════════════════════════════════════════════ */

function getDashboard(PDO $pdo): void
{
    if (!leaveIsPrivileged()) respond(403, ['error' => '관리자 권한이 필요합니다.']);
    $year = intval($_GET['year'] ?? date('Y'));

    // 부서별 사용률 (계층 정보 포함)
    $deptSt = $pdo->prepare("
        SELECT d.id AS dept_id, d.name AS dept, d.parent_id,
               COALESCE(pd.name, '') AS parent_name,
               d.sort_order AS dept_sort,
               COALESCE(pd.sort_order, d.sort_order) AS parent_sort,
               COUNT(al.id) AS emp_count,
               SUM(al.total_days) AS total, SUM(al.used_days) AS used,
               ROUND(SUM(al.used_days)/NULLIF(SUM(al.total_days),0)*100) AS rate
        FROM annual_leave al
        JOIN employees e ON e.id = al.employee_id AND e.is_active = 1
        JOIN departments d ON d.id = e.department_id
        LEFT JOIN departments pd ON pd.id = d.parent_id
        WHERE al.year = ?
        GROUP BY d.id, d.name, d.parent_id, pd.name, d.sort_order, pd.sort_order
        ORDER BY parent_sort, d.parent_id IS NULL DESC, d.sort_order
    ");
    $deptSt->execute([$year]);
    $deptStats = $deptSt->fetchAll(PDO::FETCH_ASSOC);

    // 월별 사용 추이
    $monthlySt = $pdo->prepare("
        SELECT MONTH(lr.start_date) AS month, SUM(lr.days_used) AS days, COUNT(*) AS count
        FROM leave_requests lr
        WHERE YEAR(lr.start_date) = ? AND lr.status = '승인'
        GROUP BY MONTH(lr.start_date)
        ORDER BY month
    ");
    $monthlySt->execute([$year]);
    $monthlyRaw = $monthlySt->fetchAll(PDO::FETCH_ASSOC);
    $monthlyStats = [];
    for ($m = 1; $m <= 12; $m++) {
        $found = array_filter($monthlyRaw, fn($r) => (int)$r['month'] === $m);
        $f = $found ? array_values($found)[0] : null;
        $monthlyStats[] = ['month' => $m, 'days' => $f ? floatval($f['days']) : 0, 'count' => $f ? (int)$f['count'] : 0];
    }

    // 장기 미사용 경고 (잔여 > 총일의 70%)
    $warnSt = $pdo->prepare("
        SELECT e.id, e.name, COALESCE(d.name,'') AS dept,
               al.total_days, al.used_days,
               (al.total_days - al.used_days) AS remaining,
               ROUND((al.total_days - al.used_days)/al.total_days*100) AS remain_pct
        FROM annual_leave al
        JOIN employees e ON e.id = al.employee_id AND e.is_active = 1
        LEFT JOIN departments d ON d.id = e.department_id
        WHERE al.year = ? AND al.total_days > 0
          AND (al.total_days - al.used_days) / al.total_days > 0.7
        ORDER BY remain_pct DESC
    ");
    $warnSt->execute([$year]);
    $warnings = $warnSt->fetchAll(PDO::FETCH_ASSOC);

    // 전사 요약
    $sumSt = $pdo->prepare("
        SELECT COUNT(*) AS emp_count, SUM(total_days) AS total, SUM(used_days) AS used,
               SUM(total_days - used_days) AS remaining,
               ROUND(SUM(used_days)/NULLIF(SUM(total_days),0)*100) AS rate
        FROM annual_leave al
        JOIN employees e ON e.id = al.employee_id AND e.is_active = 1
        WHERE al.year = ?
    ");
    $sumSt->execute([$year]);
    $summary = $sumSt->fetch(PDO::FETCH_ASSOC);

    respond(200, [
        'deptStats' => $deptStats,
        'monthlyStats' => $monthlyStats,
        'warnings' => $warnings,
        'summary' => $summary,
    ]);
}
