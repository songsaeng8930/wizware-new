<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const APPOINTMENT_TRACKED_FIELDS = [
    'department_id', 'position', 'title', 'employment_type', 'employment_status',
];

function detectAppointmentChanges(array $oldEmployee, array $newData): array
{
    $changes = [];
    foreach (APPOINTMENT_TRACKED_FIELDS as $field) {
        $prev = $oldEmployee[$field] ?? null;
        $next = $newData[$field] ?? null;
        if ($prev === '' || $prev === null) $prev = null;
        if ($next === '' || $next === null) $next = null;
        if ((string)$prev !== (string)$next) {
            $changes[$field] = ['prev' => $prev, 'new' => $next];
        }
    }
    return $changes;
}

function determineAppointmentType(PDO $pdo, array $changes): string
{
    $keys = array_keys($changes);

    if (count($keys) >= 2) {
        if (isset($changes['employment_status']) && ($changes['employment_status']['new'] ?? '') === '퇴사') {
            return '퇴사';
        }
        return '복합발령';
    }

    $field = $keys[0];

    switch ($field) {
        case 'department_id':
            return '전보';

        case 'position':
            $prevRank = getPositionRank($pdo, (string)($changes['position']['prev'] ?? ''));
            $newRank  = getPositionRank($pdo, (string)($changes['position']['new'] ?? ''));
            if ($newRank < $prevRank) return '승진';
            return '직급변경';

        case 'title':
            return '직책변경';

        case 'employment_type':
            return '고용형태변경';

        case 'employment_status':
            $newStatus = $changes['employment_status']['new'] ?? '';
            $prevStatus = $changes['employment_status']['prev'] ?? '';
            if ($newStatus === '퇴사') return '퇴사';
            if ($newStatus === '휴직') return '휴직';
            if ($prevStatus === '휴직' && $newStatus === '재직') return '복직';
            return '상태변경';

        default:
            return '복합발령';
    }
}

function getPositionRank(PDO $pdo, string $positionName): int
{
    if ($positionName === '') return 999;
    try {
        $stmt = $pdo->prepare(
            "SELECT sort_order FROM hr_ranks WHERE name = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$positionName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['sort_order'] : 999;
    } catch (PDOException $e) {
        error_log('getPositionRank error: ' . $e->getMessage());
        return 999;
    }
}

function getApptDepartmentName(PDO $pdo, ?int $deptId): ?string
{
    if (!$deptId) return null;
    try {
        $stmt = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
        $stmt->execute([$deptId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['name'] : null;
    } catch (PDOException $e) {
        error_log('getApptDepartmentName error: ' . $e->getMessage());
        return null;
    }
}

function recordAppointment(
    PDO $pdo,
    int $employeeId,
    array $changes,
    string $appointmentType,
    string $appointmentDate,
    string $source = 'auto',
    ?string $reason = null,
    ?int $createdBy = null,
    ?string $appointmentNo = null
): int {
    $prevDeptId   = isset($changes['department_id']) ? (int)$changes['department_id']['prev'] ?: null : null;
    $newDeptId    = isset($changes['department_id']) ? (int)$changes['department_id']['new'] ?: null : null;
    $prevDeptName = getApptDepartmentName($pdo, $prevDeptId);
    $newDeptName  = getApptDepartmentName($pdo, $newDeptId);

    $stmt = $pdo->prepare('
        INSERT INTO employee_appointments (
            employee_id, appointment_type, appointment_date, appointment_no, source,
            prev_department_id, prev_department_name, prev_position, prev_title,
            prev_employment_type, prev_employment_status,
            new_department_id, new_department_name, new_position, new_title,
            new_employment_type, new_employment_status,
            reason, created_by
        ) VALUES (
            :employee_id, :appointment_type, :appointment_date, :appointment_no, :source,
            :prev_department_id, :prev_department_name, :prev_position, :prev_title,
            :prev_employment_type, :prev_employment_status,
            :new_department_id, :new_department_name, :new_position, :new_title,
            :new_employment_type, :new_employment_status,
            :reason, :created_by
        )
    ');

    $stmt->execute([
        'employee_id'          => $employeeId,
        'appointment_type'     => $appointmentType,
        'appointment_date'     => $appointmentDate,
        'appointment_no'       => $appointmentNo,
        'source'               => $source,
        'prev_department_id'   => $prevDeptId,
        'prev_department_name' => $prevDeptName,
        'prev_position'        => $changes['position']['prev'] ?? null,
        'prev_title'           => $changes['title']['prev'] ?? null,
        'prev_employment_type' => $changes['employment_type']['prev'] ?? null,
        'prev_employment_status' => $changes['employment_status']['prev'] ?? null,
        'new_department_id'    => $newDeptId,
        'new_department_name'  => $newDeptName,
        'new_position'         => $changes['position']['new'] ?? null,
        'new_title'            => $changes['title']['new'] ?? null,
        'new_employment_type'  => $changes['employment_type']['new'] ?? null,
        'new_employment_status' => $changes['employment_status']['new'] ?? null,
        'reason'               => $reason,
        'created_by'           => $createdBy,
    ]);

    return (int)$pdo->lastInsertId();
}
