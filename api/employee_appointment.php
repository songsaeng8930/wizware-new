<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/appointment_helper.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    apiError('DB_ERROR', '데이터베이스 연결 실패', 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAppointments':
        getAppointments($pdo);
        break;
    case 'saveAppointment':
        saveAppointment($pdo);
        break;
    case 'deleteAppointment':
        deleteAppointment($pdo);
        break;
    case 'getAppointmentTypes':
        getAppointmentTypes($pdo);
        break;
    default:
        apiError('BAD_ACTION', '알 수 없는 액션입니다.', 400);
}

// ─── 조회 ───

function getAppointments(PDO $pdo): void
{
    $empId = (int)($_GET['employee_id'] ?? 0);
    if ($empId <= 0) {
        apiError('BAD_INPUT', 'employee_id가 필요합니다.');
    }

    $role = apiSessionRole();
    $sessionUserId = apiSessionUserId();
    if (!in_array($role, ['admin', 'manager'], true)) {
        $chk = $pdo->prepare('SELECT id FROM employees WHERE id = ? AND id = ?');
        $chk->execute([$empId, $sessionUserId]);
        if (!$chk->fetchColumn()) {
            apiError('FORBIDDEN', '본인의 발령 이력만 조회할 수 있습니다.', 403);
        }
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM employee_appointments WHERE employee_id = ?');
    $countStmt->execute([$empId]);
    if ((int)$countStmt->fetchColumn() === 0) {
        seedInitialAppointment($pdo, $empId);
    }

    $stmt = $pdo->prepare('
        SELECT a.*,
               creator.name AS created_by_name
        FROM employee_appointments a
        LEFT JOIN employees creator ON a.created_by = creator.id
        WHERE a.employee_id = ?
        ORDER BY a.appointment_date DESC, a.created_at DESC
    ');
    $stmt->execute([$empId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk(['items' => $items]);
}

// ─── 수동 등록/수정 ───

function saveAppointment(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $input = apiJsonInput();
    $id    = (int)($input['id'] ?? 0);
    $empId = (int)($input['employee_id'] ?? 0);

    if ($empId <= 0) {
        apiError('BAD_INPUT', 'employee_id가 필요합니다.');
    }
    if (empty($input['appointment_type'])) {
        apiError('BAD_INPUT', '발령유형을 선택해주세요.');
    }
    if (empty($input['appointment_date'])) {
        apiError('BAD_INPUT', '발령일을 입력해주세요.');
    }

    $trackedFields = ['department_id', 'position', 'title', 'employment_type', 'employment_status'];
    $changes = [];
    foreach ($trackedFields as $field) {
        $prev = $input['prev_' . $field] ?? null;
        $next = $input['new_' . $field] ?? null;
        if ($prev === '') $prev = null;
        if ($next === '') $next = null;
        if ($prev !== null || $next !== null) {
            $changes[$field] = ['prev' => $prev, 'new' => $next];
        }
    }

    if ($id > 0) {
        $existing = $pdo->prepare('SELECT id, source FROM employee_appointments WHERE id = ?');
        $existing->execute([$id]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            apiError('NOT_FOUND', '발령 이력을 찾을 수 없습니다.', 404);
        }
        if ($row['source'] !== 'manual') {
            apiError('FORBIDDEN', '자동 기록된 발령 이력은 수정할 수 없습니다.', 403);
        }

        $prevDeptId   = isset($changes['department_id']) ? (int)$changes['department_id']['prev'] ?: null : null;
        $newDeptId    = isset($changes['department_id']) ? (int)$changes['department_id']['new'] ?: null : null;
        $prevDeptName = getApptDepartmentName($pdo, $prevDeptId);
        $newDeptName  = getApptDepartmentName($pdo, $newDeptId);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                UPDATE employee_appointments SET
                    appointment_type = :appointment_type,
                    appointment_date = :appointment_date,
                    appointment_no = :appointment_no,
                    prev_department_id = :prev_department_id,
                    prev_department_name = :prev_department_name,
                    prev_position = :prev_position,
                    prev_title = :prev_title,
                    prev_employment_type = :prev_employment_type,
                    prev_employment_status = :prev_employment_status,
                    new_department_id = :new_department_id,
                    new_department_name = :new_department_name,
                    new_position = :new_position,
                    new_title = :new_title,
                    new_employment_type = :new_employment_type,
                    new_employment_status = :new_employment_status,
                    reason = :reason
                WHERE id = :id
            ');
            $stmt->execute([
                'id'                     => $id,
                'appointment_type'       => $input['appointment_type'],
                'appointment_date'       => $input['appointment_date'],
                'appointment_no'         => $input['appointment_no'] ?? null,
                'prev_department_id'     => $prevDeptId,
                'prev_department_name'   => $prevDeptName,
                'prev_position'          => $changes['position']['prev'] ?? null,
                'prev_title'             => $changes['title']['prev'] ?? null,
                'prev_employment_type'   => $changes['employment_type']['prev'] ?? null,
                'prev_employment_status' => $changes['employment_status']['prev'] ?? null,
                'new_department_id'      => $newDeptId,
                'new_department_name'    => $newDeptName,
                'new_position'           => $changes['position']['new'] ?? null,
                'new_title'              => $changes['title']['new'] ?? null,
                'new_employment_type'    => $changes['employment_type']['new'] ?? null,
                'new_employment_status'  => $changes['employment_status']['new'] ?? null,
                'reason'                 => $input['reason'] ?? null,
            ]);

            syncEmployeeFromAppointment($pdo, $empId, $changes);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('saveAppointment UPDATE 실패: ' . $e->getMessage());
            apiError('SERVER', '발령 수정 중 오류가 발생했습니다.', 500);
        }

        apiOk(['id' => $id]);
    }

    $pdo->beginTransaction();
    try {
        $newId = recordAppointment(
            $pdo,
            $empId,
            $changes,
            $input['appointment_type'],
            $input['appointment_date'],
            'manual',
            $input['reason'] ?? null,
            apiSessionUserId(),
            $input['appointment_no'] ?? null
        );

        syncEmployeeFromAppointment($pdo, $empId, $changes);

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('saveAppointment 실패: ' . $e->getMessage());
        apiError('SERVER', '발령 저장 중 오류가 발생했습니다.', 500);
    }

    apiOk(['id' => $newId], 201);
}

// ─── 삭제 ───

function deleteAppointment(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        apiError('BAD_INPUT', 'id가 필요합니다.');
    }

    $chk = $pdo->prepare('SELECT id, source FROM employee_appointments WHERE id = ?');
    $chk->execute([$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        apiError('NOT_FOUND', '발령 이력을 찾을 수 없습니다.', 404);
    }
    if ($row['source'] !== 'manual') {
        apiError('FORBIDDEN', '자동 기록된 발령 이력은 삭제할 수 없습니다.', 403);
    }

    $pdo->prepare('DELETE FROM employee_appointments WHERE id = ?')->execute([$id]);

    apiOk();
}

// ─── 기존 직원 신규입사 자동 시드 ───

function seedInitialAppointment(PDO $pdo, int $empId): void
{
    try {
        $emp = $pdo->prepare('SELECT department_id, position, title, employment_type, employment_status, hire_date FROM employees WHERE id = ?');
        $emp->execute([$empId]);
        $row = $emp->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;

        $hireDate = $row['hire_date'] ?: date('Y-m-d');
        $changes = [
            'department_id'     => ['prev' => null, 'new' => $row['department_id']],
            'position'          => ['prev' => null, 'new' => $row['position']],
            'title'             => ['prev' => null, 'new' => $row['title']],
            'employment_type'   => ['prev' => null, 'new' => $row['employment_type']],
            'employment_status' => ['prev' => null, 'new' => $row['employment_status']],
        ];
        recordAppointment($pdo, $empId, $changes, '신규입사', $hireDate, 'auto', '시스템 자동 생성 (현재 기준 값)');
    } catch (\Throwable $e) {
        error_log('seedInitialAppointment: ' . $e->getMessage());
    }
}

// ─── 수동 발령 → employees 테이블 동기화 ───

function syncEmployeeFromAppointment(PDO $pdo, int $empId, array $changes): void
{
    $fieldMap = [
        'department_id'     => 'department_id',
        'position'          => 'position',
        'title'             => 'title',
        'employment_type'   => 'employment_type',
        'employment_status' => 'employment_status',
    ];

    $setClauses = [];
    $params = [];
    foreach ($fieldMap as $changeKey => $column) {
        if (isset($changes[$changeKey]) && $changes[$changeKey]['new'] !== null) {
            $setClauses[] = "{$column} = ?";
            $params[] = $changes[$changeKey]['new'];
        }
    }

    if (empty($setClauses)) return;

    $params[] = $empId;
    $sql = 'UPDATE employees SET ' . implode(', ', $setClauses) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute($params);
}

// ─── 발령유형 동적 조회 (공통코드 연동) ───

function getAppointmentTypes(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare("
            SELECT ci.name
            FROM common_code_items ci
            INNER JOIN common_code_groups cg ON ci.group_id = cg.id
            WHERE cg.module = 'hr' AND cg.name = '발령유형' AND ci.is_active = 1
            ORDER BY ci.sort_order ASC, ci.name ASC
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
        apiOk(['types' => $items]);
    } catch (\Throwable $e) {
        error_log('getAppointmentTypes: ' . $e->getMessage());
        apiError('SERVER', '발령유형 조회 실패', 500);
    }
}
