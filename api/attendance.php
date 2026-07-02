<?php
/**
 * Zaemit 그룹웨어 - 출퇴근 API
 * 액션: getToday, clockIn, clockOut, getRecords, registerOutsideWork
 *
 * 보안 정책:
 *  - clockIn/clockOut/registerOutsideWork · 본인만 기록 가능 (세션 강제)
 *  - getToday/getRecords · 기본 본인, admin/manager는 ?employee_id=N 쿼리 허용
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getToday':            getToday();            break;
    case 'clockIn':             clockIn();             break;
    case 'clockOut':            clockOut();            break;
    case 'getRecords':          getRecords();          break;
    case 'registerOutsideWork': registerOutsideWork(); break;
    case 'getMonthlyNotes':     getMonthlyNotes();     break;
    case 'updateDayDetail':     updateDayDetail();     break;
    case 'recordOther':         recordOther();         break;
    default:
        respond(400, ['error' => '알 수 없는 액션']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 세션 로그인 사용자의 employee_id 반환.
 * 인증 미구현 환경(AUTH_ENABLED=false)에서만 쿼리 파라미터 fallback 허용.
 */
function getSessionEmpId(): int
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id > 0) return $id;

    // AUTH_ENABLED=false(로컬 개발)일 때만 파라미터 허용
    if (defined('AUTH_ENABLED') && AUTH_ENABLED === false) {
        $q = (int)($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
        if ($q <= 0) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $q = (int)($input['employee_id'] ?? 0);
        }
        if ($q > 0) return $q;
    }

    respond(401, ['error' => '인증된 사용자가 아닙니다.']);
}

/**
 * 본인만 수정 가능한 액션에서 사용 · 항상 세션 ID 반환.
 * 요청에 다른 employee_id가 있어도 무시한다 (IDOR 방지).
 */
function requireSelfEmpId(): int
{
    return getSessionEmpId();
}

/**
 * 조회용 · 기본 본인, admin/manager는 ?employee_id로 타인 조회 가능.
 */
function resolveQueryEmpId(): int
{
    $self = getSessionEmpId();
    $role = (string)($_SESSION['user']['role'] ?? '');
    $requested = (int)($_GET['employee_id'] ?? 0);
    if ($requested > 0 && $requested !== $self && in_array($role, ['admin', 'manager'], true)) {
        return $requested;
    }
    return $self;
}

/** 오늘 출퇴근 상태 조회 (조회만, admin/manager는 타인 조회 가능) */
function getToday(): void
{
    $empId = resolveQueryEmpId();
    try {
        $pdo = getDBConnection();
        $st = $pdo->prepare("SELECT clock_in, clock_out, work_type
            FROM attendance_records WHERE employee_id = ? AND record_date = CURDATE()");
        $st->execute([$empId]);
        $row = $st->fetch();

        respond(200, [
            'success' => true,
            'data' => $row ?: ['clock_in' => null, 'clock_out' => null, 'work_type' => null]
        ]);
    } catch (Exception $e) {
        error_log('[Attendance] getToday 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/** 출근 기록 · 본인만. JSON 바디 {work_plan}(선택) 을 함께 저장. */
function clockIn(): void
{
    $empId = requireSelfEmpId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $plan  = trim((string)($input['work_plan'] ?? ''));
    if (mb_strlen($plan) > 2000) $plan = mb_substr($plan, 0, 2000);

    try {
        $pdo = getDBConnection();

        // 이미 출근했는지 확인
        $st = $pdo->prepare("SELECT clock_in FROM attendance_records WHERE employee_id = ? AND record_date = CURDATE()");
        $st->execute([$empId]);
        $existing = $st->fetch();
        if ($existing && $existing['clock_in']) {
            respond(409, ['error' => '이미 출근 기록이 있습니다', 'clock_in' => $existing['clock_in']]);
        }

        // work_plan 컬럼 존재 여부 감지 (마이그레이션 미실행 환경 호환)
        $hasPlan = true;
        try { $pdo->query('SELECT work_plan FROM attendance_records LIMIT 1'); }
        catch (PDOException $e) { $hasPlan = false; }

        if ($hasPlan) {
            $st = $pdo->prepare("INSERT INTO attendance_records (employee_id, record_date, clock_in, work_plan)
                VALUES (?, CURDATE(), CURTIME(), ?)
                ON DUPLICATE KEY UPDATE clock_in = CURTIME(), work_plan = VALUES(work_plan), updated_at = CURRENT_TIMESTAMP");
            $st->execute([$empId, $plan !== '' ? $plan : null]);
        } else {
            $st = $pdo->prepare("INSERT INTO attendance_records (employee_id, record_date, clock_in)
                VALUES (?, CURDATE(), CURTIME())
                ON DUPLICATE KEY UPDATE clock_in = CURTIME(), updated_at = CURRENT_TIMESTAMP");
            $st->execute([$empId]);
        }

        $st = $pdo->prepare("SELECT clock_in FROM attendance_records WHERE employee_id = ? AND record_date = CURDATE()");
        $st->execute([$empId]);
        $row = $st->fetch();

        respond(200, ['success' => true, 'clock_in' => $row['clock_in'], 'work_plan_saved' => $hasPlan && $plan !== '']);
    } catch (Exception $e) {
        error_log('[Attendance] clockIn 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/** 퇴근 기록 · 본인만. JSON 바디 {leave_note}(선택) 을 함께 저장. */
function clockOut(): void
{
    $empId = requireSelfEmpId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $note  = trim((string)($input['leave_note'] ?? ''));
    if (mb_strlen($note) > 2000) $note = mb_substr($note, 0, 2000);

    try {
        $pdo = getDBConnection();

        $st = $pdo->prepare("SELECT clock_in, clock_out FROM attendance_records WHERE employee_id = ? AND record_date = CURDATE()");
        $st->execute([$empId]);
        $existing = $st->fetch();
        if (!$existing || !$existing['clock_in']) {
            respond(400, ['error' => '출근 기록이 없습니다. 먼저 출근해주세요.']);
        }
        if ($existing['clock_out']) {
            respond(409, ['error' => '이미 퇴근 기록이 있습니다', 'clock_out' => $existing['clock_out']]);
        }

        // leave_note 컬럼 존재 여부 감지 (마이그레이션 미실행 환경 호환)
        $hasNote = true;
        try { $pdo->query('SELECT leave_note FROM attendance_records LIMIT 1'); }
        catch (PDOException $e) { $hasNote = false; }

        if ($hasNote) {
            $st = $pdo->prepare("UPDATE attendance_records
                SET clock_out = CURTIME(), leave_note = ?, updated_at = CURRENT_TIMESTAMP
                WHERE employee_id = ? AND record_date = CURDATE()");
            $st->execute([$note !== '' ? $note : null, $empId]);
        } else {
            $st = $pdo->prepare("UPDATE attendance_records SET clock_out = CURTIME(), updated_at = CURRENT_TIMESTAMP
                WHERE employee_id = ? AND record_date = CURDATE()");
            $st->execute([$empId]);
        }

        $st = $pdo->prepare("SELECT clock_in, clock_out FROM attendance_records WHERE employee_id = ? AND record_date = CURDATE()");
        $st->execute([$empId]);
        $row = $st->fetch();

        respond(200, ['success' => true, 'clock_in' => $row['clock_in'], 'clock_out' => $row['clock_out'], 'leave_note_saved' => $hasNote && $note !== '']);
    } catch (Exception $e) {
        error_log('[Attendance] clockOut 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/**
 * 과거 날짜의 work_plan / leave_note 수정 · 본인만.
 * 입력: { date: YYYY-MM-DD, work_plan?: string, leave_note?: string }
 *   - 필드는 부분 업데이트(전달된 키만 반영). 빈 문자열은 NULL로 저장.
 *   - 출퇴근 시각(clock_in/out) 은 이 액션에서 수정하지 않는다.
 */
function updateDayDetail(): void
{
    $empId = requireSelfEmpId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $date  = trim((string)($input['date'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        respond(400, ['error' => '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD)']);
    }

    try {
        $pdo = getDBConnection();

        // 해당 날짜 기록 존재 확인 · 본인 것만
        $chk = $pdo->prepare("SELECT id FROM attendance_records WHERE employee_id = ? AND record_date = ?");
        $chk->execute([$empId, $date]);
        if (!$chk->fetchColumn()) respond(404, ['error' => '해당 날짜의 출근 기록이 없어 수정할 수 없습니다']);

        // 컬럼 존재 감지
        $hasPlan = true; try { $pdo->query('SELECT work_plan FROM attendance_records LIMIT 1'); } catch (PDOException $e) { $hasPlan = false; }
        $hasNote = true; try { $pdo->query('SELECT leave_note FROM attendance_records LIMIT 1'); } catch (PDOException $e) { $hasNote = false; }
        if (!$hasPlan && !$hasNote) {
            respond(500, ['error' => 'work_plan/leave_note 컬럼이 없습니다. db/migrate_attendance_plan_note.sql 실행이 필요합니다.']);
        }

        $sets   = [];
        $params = [];
        if ($hasPlan && array_key_exists('work_plan', $input)) {
            $v = trim((string)$input['work_plan']);
            if (mb_strlen($v) > 2000) $v = mb_substr($v, 0, 2000);
            $sets[]   = 'work_plan = ?';
            $params[] = $v === '' ? null : $v;
        }
        if ($hasNote && array_key_exists('leave_note', $input)) {
            $v = trim((string)$input['leave_note']);
            if (mb_strlen($v) > 2000) $v = mb_substr($v, 0, 2000);
            $sets[]   = 'leave_note = ?';
            $params[] = $v === '' ? null : $v;
        }
        if (!$sets) respond(400, ['error' => '수정할 필드가 없습니다']);

        $params[] = $empId;
        $params[] = $date;
        $sql = "UPDATE attendance_records SET " . implode(', ', $sets) . ", updated_at = CURRENT_TIMESTAMP
                WHERE employee_id = ? AND record_date = ?";
        $pdo->prepare($sql)->execute($params);

        // 최신 값 반환
        $cols = 'clock_in, clock_out' . ($hasPlan ? ', work_plan' : '') . ($hasNote ? ', leave_note' : '');
        $q = $pdo->prepare("SELECT {$cols} FROM attendance_records WHERE employee_id = ? AND record_date = ?");
        $q->execute([$empId, $date]);
        respond(200, ['success' => true, 'record' => $q->fetch(PDO::FETCH_ASSOC) ?: []]);
    } catch (Exception $e) {
        error_log('[Attendance] updateDayDetail 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/**
 * 기타 특이사항 기록 · 본인만.
 *   입력: { date: YYYY-MM-DD, reason: string, note: string }
 *   - 출퇴근을 기록하지 못했거나 철회한 경우 등 결재 절차 없는 메모를 바로 저장.
 *   - 해당 날짜에 레코드가 없으면 INSERT, 있으면 leave_note 덮어쓰기.
 *   - reason 은 attendance_records.note (짧은 분류 메모) 로, 본문은 leave_note 로 저장.
 */
function recordOther(): void
{
    $empId = requireSelfEmpId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $date   = trim((string)($input['date']   ?? ''));
    $reason = trim((string)($input['reason'] ?? ''));
    $note   = trim((string)($input['note']   ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) respond(400, ['error' => '날짜 형식이 올바르지 않습니다']);
    if ($note === '' && $reason === '') respond(400, ['error' => '사유 또는 내용을 입력해주세요']);
    if (mb_strlen($note) > 2000) $note = mb_substr($note, 0, 2000);
    if (mb_strlen($reason) > 100) $reason = mb_substr($reason, 0, 100);

    try {
        $pdo = getDBConnection();
        $hasNote = true; try { $pdo->query('SELECT leave_note FROM attendance_records LIMIT 1'); } catch (PDOException $e) { $hasNote = false; }
        if (!$hasNote) respond(500, ['error' => 'leave_note 컬럼이 없습니다. db/migrate_attendance_plan_note.sql 실행이 필요합니다.']);

        // reason 은 짧은 태그로 note 컬럼에 넣고, 본문은 leave_note 로.
        // 병기 표시: reason 이 있으면 leave_note 앞에 "[사유] " 프리픽스로 가독성 확보.
        $combined = ($reason !== '' ? '[' . $reason . '] ' : '') . $note;

        $sql = "INSERT INTO attendance_records (employee_id, record_date, leave_note, note)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    leave_note = VALUES(leave_note),
                    note       = COALESCE(VALUES(note), note),
                    updated_at = CURRENT_TIMESTAMP";
        $pdo->prepare($sql)->execute([$empId, $date, $combined, $reason !== '' ? $reason : null]);
        respond(200, ['success' => true, 'record_date' => $date, 'leave_note' => $combined]);
    } catch (Exception $e) {
        error_log('[Attendance] recordOther 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/** 이번 달 특이사항(leave_note) 목록 조회 · 본인만. 달력 밖 요약 카드용. */
function getMonthlyNotes(): void
{
    $empId = resolveQueryEmpId();
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));
    if ($year < 2000 || $month < 1 || $month > 12) respond(400, ['error' => '기간이 올바르지 않습니다']);

    $start = sprintf('%04d-%02d-01', $year, $month);
    $end   = date('Y-m-t', strtotime($start));

    try {
        $pdo = getDBConnection();
        $hasNote = true;
        try { $pdo->query('SELECT leave_note FROM attendance_records LIMIT 1'); }
        catch (PDOException $e) { $hasNote = false; }

        $notes = [];
        if ($hasNote) {
            $st = $pdo->prepare("SELECT record_date, clock_out, leave_note
                FROM attendance_records
                WHERE employee_id = ? AND record_date BETWEEN ? AND ? AND leave_note IS NOT NULL AND leave_note <> ''
                ORDER BY record_date DESC");
            $st->execute([$empId, $start, $end]);
            $notes = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        respond(200, ['success' => true, 'notes' => $notes, 'schema_ready' => $hasNote]);
    } catch (Exception $e) {
        error_log('[Attendance] getMonthlyNotes 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/** 외근 등록 · 본인만 */
function registerOutsideWork(): void
{
    $empId = requireSelfEmpId();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $workDate      = trim($input['work_date'] ?? '');
    $departureTime = trim($input['departure_time'] ?? '') ?: null;
    $returnTime    = trim($input['return_time'] ?? '') ?: null;
    $destination   = trim($input['destination'] ?? '');
    $purpose       = trim($input['purpose'] ?? '') ?: null;

    if (!$workDate) respond(400, ['error' => '날짜를 입력해주세요']);
    if (!$destination) respond(400, ['error' => '방문처/장소를 입력해주세요']);

    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // outside_work_records에 외근 기록 저장
        $st = $pdo->prepare("INSERT INTO outside_work_records
            (employee_id, work_date, departure_time, return_time, destination, purpose)
            VALUES (?, ?, ?, ?, ?, ?)");
        $st->execute([$empId, $workDate, $departureTime, $returnTime, $destination, $purpose]);

        // attendance_records에도 work_type='외근' 마킹 (출퇴근 현황에 반영)
        $st = $pdo->prepare("INSERT INTO attendance_records (employee_id, record_date, work_type, note)
            VALUES (?, ?, '외근', ?)
            ON DUPLICATE KEY UPDATE work_type = '외근', note = VALUES(note)");
        $st->execute([$empId, $workDate, $destination]);

        $pdo->commit();

        respond(200, ['success' => true, 'message' => '외근이 등록되었습니다']);
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[Attendance] registerOutsideWork 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}

/** 기간별 출퇴근 기록 조회 (기본 본인, admin/manager는 타인 조회 가능) */
function getRecords(): void
{
    $empId = resolveQueryEmpId();
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');

    try {
        $pdo = getDBConnection();
        $st = $pdo->prepare("SELECT record_date, clock_in, clock_out, work_type, note
            FROM attendance_records
            WHERE employee_id = ? AND record_date BETWEEN ? AND ?
            ORDER BY record_date DESC");
        $st->execute([$empId, $start, $end]);

        respond(200, ['success' => true, 'data' => $st->fetchAll()]);
    } catch (Exception $e) {
        error_log('[Attendance] getRecords 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류']);
    }
}
