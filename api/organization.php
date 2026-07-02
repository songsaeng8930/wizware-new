<?php
/**
 * Zaemit 그룹웨어 - 조직도 API
 * 부서 및 직원 CRUD 엔드포인트
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/appointment_helper.php';
require_once __DIR__ . '/../includes/hr_codes.php';
require_once __DIR__ . '/../includes/org_path.php';
require_once __DIR__ . '/../includes/org_hierarchy.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    match ($action) {
        // 부서
        'getDepartments' => getDepartments($pdo),
        'getDepartmentTree' => getDepartmentTree($pdo),
        'createDepartment' => createDepartment($pdo),
        'updateDepartment' => updateDepartment($pdo),
        'deleteDepartment' => deleteDepartment($pdo),
        // 직원
        'getEmployees' => getEmployees($pdo),
        'getEmployeesByDept' => getEmployeesByDept($pdo),
        'createEmployee' => createEmployee($pdo),
        'updateEmployee' => updateEmployee($pdo),
        'moveEmployee' => moveEmployee($pdo),
        'reorderEmployees' => reorderEmployees($pdo),
        'checkEmployeeLinks' => checkEmployeeLinks($pdo),
        'deleteEmployee' => deleteEmployee($pdo),
        'bulkCreateEmployees' => bulkCreateEmployees($pdo),
        // 내 정보 (본인만 수정 가능)
        'getMyProfile' => getMyProfile($pdo),
        'updateMyProfile' => updateMyProfile($pdo),
        'uploadMyProfilePhoto' => uploadMyProfilePhoto($pdo),
        'uploadEmployeePhoto' => uploadEmployeePhoto($pdo),
        'deleteProfilePhoto' => deleteProfilePhoto($pdo),
        'changeMyPassword' => changeMyPassword($pdo),
        // 조직도 트리
        'getOrgTree' => getOrgTree($pdo),
        default => respond(400, ['error' => '알 수 없는 액션: ' . $action]),
    };
} catch (PDOException $e) {
    error_log('Organization API Error: ' . $e->getMessage());
    respond(500, ['error' => '서버 오류가 발생했습니다.']);
}

// === 응답 헬퍼 ===
function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

function employeeSelectColumns(string $role): string
{
    $public = 'e.id, e.employee_no, e.department_id, e.affiliation, e.name, e.position, e.rank_id, e.title, e.duty_id,
               e.email, e.phone, e.user_role, e.employment_type, e.employment_status,
               e.is_dept_head, e.profile_image, e.hire_date, e.is_active, e.created_at, e.updated_at';
    if (in_array($role, ['admin', 'manager'], true)) {
        return $public . ', e.birth_date, e.gender, e.zipcode, e.address1, e.address2';
    }
    return $public;
}

// === 부서 API ===

function getDepartments(PDO $pdo): void
{
    $stmt = $pdo->query('
        SELECT d.*, e.name AS head_name, e.position AS head_position,
               (SELECT COUNT(*) FROM employees WHERE department_id = d.id AND is_active = 1) AS employee_count
        FROM departments d
        LEFT JOIN employees e ON d.head_employee_id = e.id
        WHERE d.is_active = 1
        ORDER BY d.sort_order, d.name
    ');
    respond(200, ['departments' => $stmt->fetchAll()]);
}

function getDepartmentTree(PDO $pdo): void
{
    $stmt = $pdo->query('
        SELECT d.id, d.parent_id, d.name, d.code, d.sort_order,
               (SELECT COUNT(*) FROM employees WHERE department_id = d.id AND is_active = 1) AS employee_count
        FROM departments d
        WHERE d.is_active = 1
        ORDER BY d.sort_order, d.name
    ');
    $departments = $stmt->fetchAll();
    respond(200, ['tree' => buildTree($departments)]);
}

function buildTree(array $items, ?int $parentId = null): array
{
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = buildTree($items, (int)$item['id']);
            $item['children'] = $children;
            $tree[] = $item;
        }
    }
    return $tree;
}

function createDepartment(PDO $pdo): void
{
    $data = getJsonInput();
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        respond(400, ['error' => '부서명을 입력해주세요.']);
    }

    // ── 깊이 제한 검증 ──
    $parentId = $data['parent_id'] ?: null;
    if ($parentId !== null) {
        $enabledLevels = getOrgLevels(true);
        $maxAllowedDepth = count($enabledLevels) - 1;
        $newParentDepth = 0;
        $cur = (int)$parentId;
        while ($cur !== null) {
            $newParentDepth++;
            $st = $pdo->prepare('SELECT parent_id FROM departments WHERE id = :id');
            $st->execute(['id' => $cur]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $cur = $row ? ($row['parent_id'] ? (int)$row['parent_id'] : null) : null;
        }
        if ($newParentDepth > $maxAllowedDepth) {
            respond(400, ['error' => '최대 ' . count($enabledLevels) . '단계까지만 설정할 수 있습니다.']);
        }
    }

    $stmt = $pdo->prepare('
        INSERT INTO departments (parent_id, name, code, sort_order)
        VALUES (:parent_id, :name, :code, :sort_order)
    ');
    $stmt->execute([
        'parent_id' => $data['parent_id'] ?: null,
        'name' => $name,
        'code' => $data['code'] ?? null,
        'sort_order' => (int)($data['sort_order'] ?? 0),
    ]);

    respond(201, ['id' => (int)$pdo->lastInsertId(), 'message' => '부서가 등록되었습니다.']);
}

function updateDepartment(PDO $pdo): void
{
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        respond(400, ['error' => '유효하지 않은 부서 ID입니다.']);
    }

    $parentId = $data['parent_id'] ?: null;
    if ($parentId !== null) {
        $parentId = (int)$parentId;
        if ($parentId === $id) {
            respond(400, ['error' => '자기 자신을 상위 부서로 지정할 수 없습니다.']);
        }
        $check = $parentId;
        $visited = [$id];
        while ($check) {
            if (in_array($check, $visited)) {
                respond(400, ['error' => '하위 부서를 상위 부서로 지정할 수 없습니다. (순환 구조)']);
            }
            $visited[] = $check;
            $st = $pdo->prepare('SELECT parent_id FROM departments WHERE id = :id');
            $st->execute(['id' => $check]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $check = $row ? ($row['parent_id'] ? (int)$row['parent_id'] : null) : null;
        }
    }

    // ── 깊이 제한 검증 (하위 트리 깊이 포함) ──
    $enabledLevels = getOrgLevels(true);
    $maxAllowedDepth = count($enabledLevels) - 1;
    $newParentDepth = 0;
    $cur = $parentId !== null ? (int)$parentId : null;
    while ($cur !== null) {
        $newParentDepth++;
        $st = $pdo->prepare('SELECT parent_id FROM departments WHERE id = :id');
        $st->execute(['id' => $cur]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $cur = $row ? ($row['parent_id'] ? (int)$row['parent_id'] : null) : null;
    }
    function getSubtreeMaxDepth(PDO $pdo, int $deptId): int {
        $st = $pdo->prepare('SELECT id FROM departments WHERE parent_id = :pid AND is_active = 1');
        $st->execute(['pid' => $deptId]);
        $children = $st->fetchAll(PDO::FETCH_COLUMN);
        if (empty($children)) return 0;
        $max = 0;
        foreach ($children as $childId) {
            $max = max($max, 1 + getSubtreeMaxDepth($pdo, (int)$childId));
        }
        return $max;
    }
    $subtreeDepth = getSubtreeMaxDepth($pdo, $id);
    if ($newParentDepth + $subtreeDepth > $maxAllowedDepth) {
        respond(400, ['error' => '최대 ' . count($enabledLevels) . '단계까지만 설정할 수 있습니다.']);
    }

    $stmt = $pdo->prepare('
        UPDATE departments SET name = :name, code = :code, parent_id = :parent_id,
               head_employee_id = :head_employee_id, sort_order = :sort_order
        WHERE id = :id
    ');
    $pdo->beginTransaction();
    try {
        $stmt->execute([
            'id' => $id,
            'name' => trim($data['name'] ?? ''),
            'code' => $data['code'] ?? null,
            'parent_id' => $parentId,
            'head_employee_id' => $data['head_employee_id'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);

        $headIds = $data['head_employee_ids'] ?? [];
        if (is_array($headIds)) {
            $pdo->prepare('UPDATE employees SET is_dept_head = 0 WHERE department_id = ?')->execute([$id]);
            if (!empty($headIds)) {
                $placeholders = implode(',', array_fill(0, count($headIds), '?'));
                $params = array_map('intval', $headIds);
                $params[] = $id;
                $pdo->prepare("UPDATE employees SET is_dept_head = 1 WHERE id IN ({$placeholders}) AND department_id = ?")->execute($params);
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('updateDepartment 실패: ' . $e->getMessage());
        respond(500, ['error' => '부서 정보 수정 중 오류가 발생했습니다.']);
    }

    respond(200, ['message' => '부서 정보가 수정되었습니다.']);
}

function deleteDepartment(PDO $pdo): void
{
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        respond(400, ['error' => '유효하지 않은 부서 ID입니다.']);
    }

    // 하위 부서 확인
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE parent_id = ? AND is_active = 1');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        respond(400, ['error' => '하위 부서가 존재합니다. 먼저 하위 부서를 삭제해주세요.']);
    }

    // 소속 직원 확인
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE department_id = ? AND is_active = 1');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        respond(400, ['error' => '소속 직원이 존재합니다. 먼저 직원을 이동하거나 삭제해주세요.']);
    }

    $stmt = $pdo->prepare('UPDATE departments SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);

    respond(200, ['message' => '부서가 삭제되었습니다.']);
}

// === 직원 API ===

function getEmployees(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    $cols = employeeSelectColumns($role);

    $stmt = $pdo->query("
        SELECT {$cols}, d.name AS department_name
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE e.is_active = 1
        ORDER BY d.sort_order, e.position, e.name
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    attachOrgPathToEmployeeRows($pdo, $employees);
    respond(200, ['employees' => $employees]);
}

function getEmployeesByDept(PDO $pdo): void
{
    $deptId = (int)($_GET['department_id'] ?? 0);
    if ($deptId <= 0) {
        respond(400, ['error' => '유효하지 않은 부서 ID입니다.']);
    }

    $role = (string)($_SESSION['user']['role'] ?? '');
    $cols = employeeSelectColumns($role);

    $stmt = $pdo->prepare("
        SELECT {$cols}, d.name AS department_name
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN hr_ranks _tr ON _tr.id = e.rank_id
        WHERE e.department_id = ? AND e.is_active = 1
        ORDER BY COALESCE(_tr.sort_order, 999), e.name
    ");
    $stmt->execute([$deptId]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    attachOrgPathToEmployeeRows($pdo, $employees);
    respond(200, ['employees' => $employees]);
}

function createEmployee(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = getJsonInput();
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        respond(400, ['error' => '이름을 입력해주세요.']);
    }

    $employeeNo = trim($data['employee_no'] ?? '');
    if ($employeeNo === '') {
        $year = date('Y');
        $maxStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(employee_no, '-', -1) AS UNSIGNED)) FROM employees WHERE employee_no LIKE ?");
        $maxStmt->execute([$year . '-%']);
        $nextSeq = ((int)$maxStmt->fetchColumn()) + 1;
        $employeeNo = $year . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    } else {
        $dupCheck = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE employee_no = ?');
        $dupCheck->execute([$employeeNo]);
        if ((int)$dupCheck->fetchColumn() > 0) {
            respond(400, ['error' => "사번 '{$employeeNo}'이(가) 이미 사용 중입니다."]);
        }
    }

    $rankId  = !empty($data['rank_id'])  ? (int)$data['rank_id']  : null;
    $dutyId  = !empty($data['duty_id'])  ? (int)$data['duty_id']  : null;
    $position = $data['position'] ?? null;
    $title    = $data['title'] ?? null;

    if ($rankId) {
        $position = resolveRankName($pdo, $rankId) ?? $position;
    } elseif ($position) {
        $rankId = resolveRankId($pdo, $position);
    }
    if ($dutyId) {
        $title = resolveDutyName($pdo, $dutyId) ?? $title;
    } elseif ($title) {
        $dutyId = resolveDutyId($pdo, $title);
    }

    $stmt = $pdo->prepare('
        INSERT INTO employees (employee_no, department_id, affiliation, name, position, rank_id, title, duty_id,
            email, phone, birth_date, gender, zipcode, address1, address2,
            hire_date, resign_date, employment_type, employment_status, memo)
        VALUES (:employee_no, :department_id, :affiliation, :name, :position, :rank_id, :title, :duty_id,
            :email, :phone, :birth_date, :gender, :zipcode, :address1, :address2,
            :hire_date, :resign_date, :employment_type, :employment_status, :memo)
    ');
    $stmt->execute([
        'employee_no' => $employeeNo,
        'department_id' => $data['department_id'] ?: null,
        'affiliation' => $data['affiliation'] ?? null,
        'name' => $name,
        'position' => $position,
        'rank_id' => $rankId,
        'title' => $title,
        'duty_id' => $dutyId,
        'email' => $data['email'] ?? null,
        'phone' => $data['phone'] ?? null,
        'birth_date' => $data['birth_date'] ?: null,
        'gender' => $data['gender'] ?? null,
        'zipcode' => $data['zipcode'] ?? null,
        'address1' => $data['address1'] ?? null,
        'address2' => $data['address2'] ?? null,
        'hire_date' => $data['hire_date'] ?: null,
        'resign_date' => $data['resign_date'] ?: null,
        'employment_type' => $data['employment_type'] ?? '정규직',
        'employment_status' => $data['employment_status'] ?? '재직',
        'memo' => $data['memo'] ?? null,
    ]);

    $newId = (int)$pdo->lastInsertId();

    $rawPw = trim((string)($data['password'] ?? ''));
    if ($rawPw !== '') {
        $pwHash = password_hash($rawPw, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE employees SET password_hash = ? WHERE id = ?')->execute([$pwHash, $newId]);
    }

    // 신규입사 발령 자동 기록
    try {
        $hireDate = $data['hire_date'] ?: date('Y-m-d');
        $changes = [
            'department_id'     => ['prev' => null, 'new' => $data['department_id'] ?: null],
            'position'          => ['prev' => null, 'new' => $position],
            'title'             => ['prev' => null, 'new' => $title],
            'employment_type'   => ['prev' => null, 'new' => $data['employment_type'] ?? '정규직'],
            'employment_status' => ['prev' => null, 'new' => $data['employment_status'] ?? '재직'],
        ];
        recordAppointment($pdo, $newId, $changes, '신규입사', $hireDate, 'auto', null, $_SESSION['user_id'] ?? null);
    } catch (\Throwable $e) {
        error_log('createEmployee: 신규입사 발령 자동 기록 실패 - ' . $e->getMessage());
    }

    respond(201, ['id' => $newId, 'employee_no' => $employeeNo, 'message' => '직원이 등록되었습니다.']);
}

function updateEmployee(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        respond(400, ['error' => '유효하지 않은 직원 ID입니다.']);
    }

    $oldStmt = $pdo->prepare('SELECT department_id, position, title, employment_type, employment_status FROM employees WHERE id = ?');
    $oldStmt->execute([$id]);
    $oldEmployee = $oldStmt->fetch(PDO::FETCH_ASSOC);

    $rankId  = !empty($data['rank_id'])  ? (int)$data['rank_id']  : null;
    $dutyId  = !empty($data['duty_id'])  ? (int)$data['duty_id']  : null;
    $position = $data['position'] ?? null;
    $title    = $data['title'] ?? null;

    if ($rankId) {
        $position = resolveRankName($pdo, $rankId) ?? $position;
    } elseif ($position) {
        $rankId = resolveRankId($pdo, $position);
    }
    if ($dutyId) {
        $title = resolveDutyName($pdo, $dutyId) ?? $title;
    } elseif ($title) {
        $dutyId = resolveDutyId($pdo, $title);
    }

    $data['position'] = $position;
    $data['title']    = $title;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            UPDATE employees SET
                employee_no = :employee_no, department_id = :department_id, affiliation = :affiliation,
                name = :name, position = :position, rank_id = :rank_id,
                title = :title, duty_id = :duty_id,
                email = :email, phone = :phone,
                birth_date = :birth_date, gender = :gender,
                zipcode = :zipcode, address1 = :address1, address2 = :address2,
                hire_date = :hire_date, resign_date = :resign_date,
                employment_type = :employment_type, employment_status = :employment_status,
                memo = :memo
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'employee_no' => $data['employee_no'] ?? null,
            'department_id' => $data['department_id'] ?: null,
            'affiliation' => $data['affiliation'] ?? null,
            'name' => trim($data['name'] ?? ''),
            'position' => $position,
            'rank_id' => $rankId,
            'title' => $title,
            'duty_id' => $dutyId,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?: null,
            'gender' => $data['gender'] ?? null,
            'zipcode' => $data['zipcode'] ?? null,
            'address1' => $data['address1'] ?? null,
            'address2' => $data['address2'] ?? null,
            'hire_date' => $data['hire_date'] ?: null,
            'resign_date' => $data['resign_date'] ?: null,
            'employment_type' => $data['employment_type'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
            'memo' => $data['memo'] ?? null,
        ]);

        if ($oldEmployee) {
            $changes = detectAppointmentChanges($oldEmployee, $data);
            if (!empty($changes)) {
                $apptType = determineAppointmentType($pdo, $changes);
                recordAppointment($pdo, $id, $changes, $apptType, date('Y-m-d'), 'auto', null, (int)($_SESSION['user_id'] ?? 0));
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    respond(200, ['message' => '직원 정보가 수정되었습니다.']);
}

/**
 * 직원을 다른 부서로 이동 (드래그 앤 드롭용)
 * - admin/manager 전용
 * - 대상 부서가 존재(활성) 하는지 검증
 * - 원 소속 부서의 부서장이었다면 head_employee_id 를 null 로 해제
 */
function moveEmployee(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = getJsonInput();
    $empId  = (int)($data['employee_id'] ?? 0);
    $deptId = (int)($data['department_id'] ?? 0);
    if ($empId <= 0 || $deptId <= 0) {
        respond(400, ['error' => 'employee_id와 department_id가 필요합니다.']);
    }

    // 대상 부서 존재/활성 확인
    $dstChk = $pdo->prepare('SELECT id FROM departments WHERE id = ? AND is_active = 1');
    $dstChk->execute([$deptId]);
    if (!$dstChk->fetchColumn()) {
        respond(404, ['error' => '이동할 부서를 찾을 수 없습니다.']);
    }

    // 현재 직원 정보 조회
    $empStmt = $pdo->prepare('SELECT department_id FROM employees WHERE id = ? AND is_active = 1');
    $empStmt->execute([$empId]);
    $currentDeptId = $empStmt->fetchColumn();
    if ($currentDeptId === false) {
        respond(404, ['error' => '직원을 찾을 수 없습니다.']);
    }
    if ((int)$currentDeptId === $deptId) {
        respond(200, ['message' => '이미 해당 부서에 속해 있습니다.', 'employee_id' => $empId, 'department_id' => $deptId]);
    }

    $pdo->beginTransaction();
    try {
        // 원 소속의 부서장이었다면 해제
        $pdo->prepare('UPDATE departments SET head_employee_id = NULL WHERE head_employee_id = ?')
            ->execute([$empId]);
        $pdo->prepare('UPDATE employees SET is_dept_head = 0 WHERE id = ?')
            ->execute([$empId]);

        // 소속 이동
        $pdo->prepare('UPDATE employees SET department_id = ? WHERE id = ?')
            ->execute([$deptId, $empId]);

        $changes = ['department_id' => ['prev' => (int)$currentDeptId, 'new' => $deptId]];
        recordAppointment($pdo, $empId, $changes, '전보', date('Y-m-d'), 'auto', null, (int)($_SESSION['user_id'] ?? 0));

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    respond(200, [
        'success' => true,
        'employee_id' => $empId,
        'department_id' => $deptId,
        'message' => '직원이 이동되었습니다.',
    ]);
}

/**
 * 같은 부서 내 직원 순서 재정렬 (드래그앤드롭 상하 이동용)
 * - admin/manager 전용
 * - body: { department_id, employee_ids: [id1, id2, ...] }
 *   employee_ids 순서대로 sort_order 를 10, 20, 30 ... 으로 재할당
 *   전달된 직원들이 모두 해당 department_id 소속인지 검증
 */
function reorderEmployees(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = getJsonInput();
    $deptId = (int)($data['department_id'] ?? 0);
    $ids    = is_array($data['employee_ids'] ?? null) ? $data['employee_ids'] : [];
    $ids    = array_values(array_filter(array_map('intval', $ids), fn($x) => $x > 0));

    if ($deptId <= 0 || empty($ids)) {
        respond(400, ['error' => 'department_id와 employee_ids가 필요합니다.']);
    }

    // 부서 존재 확인
    $dstChk = $pdo->prepare('SELECT id FROM departments WHERE id = ? AND is_active = 1');
    $dstChk->execute([$deptId]);
    if (!$dstChk->fetchColumn()) {
        respond(404, ['error' => '부서를 찾을 수 없습니다.']);
    }

    // 모든 id 가 해당 부서 소속(활성)인지 한 번에 확인
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$deptId], $ids);
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE department_id = ? AND id IN ($placeholders) AND is_active = 1");
    $cntStmt->execute($params);
    if ((int)$cntStmt->fetchColumn() !== count($ids)) {
        respond(400, ['error' => '지정 부서에 속하지 않은 직원이 포함되어 있습니다.']);
    }

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE employees SET sort_order = ? WHERE id = ? AND department_id = ?');
        $i = 10;
        foreach ($ids as $empId) {
            $upd->execute([$i, $empId, $deptId]);
            $i += 10;
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    respond(200, [
        'success' => true,
        'department_id' => $deptId,
        'count' => count($ids),
        'message' => '직원 순서가 저장되었습니다.',
    ]);
}

function checkEmployeeLinks(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 직원 ID입니다.']);

    $links = [];
    $tables = [
        ['payslips', 'employee_id', '급여 명세'],
        ['approval_documents', 'employee_id', '결재 문서'],
        ['attendance_records', 'employee_id', '근태 기록'],
        ['card_expenses', 'employee_id', '카드 지출'],
        ['employee_careers', 'employee_id', '경력 이력'],
        ['employee_appointments', 'employee_id', '인사발령'],
    ];

    // $table/$col은 위 하드코딩 배열에서만 공급 — 외부 입력 아님
    foreach ($tables as [$table, $col, $label]) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$col} = ?");
            $stmt->execute([$id]);
            $cnt = (int)$stmt->fetchColumn();
            if ($cnt > 0) $links[] = ['label' => $label, 'count' => $cnt];
        } catch (\PDOException $e) {
            // 테이블 없으면 스킵
        }
    }

    $isDeptHead = false;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE head_employee_id = ?");
        $stmt->execute([$id]);
        $isDeptHead = (int)$stmt->fetchColumn() > 0;
    } catch (\PDOException $e) {}

    respond(200, [
        'links' => $links,
        'hasLinks' => !empty($links),
        'isDeptHead' => $isDeptHead,
    ]);
}

function deleteEmployee(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        respond(400, ['error' => '유효하지 않은 직원 ID입니다.']);
    }

    $pdo->beginTransaction();
    try {
        // 부서장인 경우 해제
        $pdo->prepare('UPDATE departments SET head_employee_id = NULL WHERE head_employee_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE employees SET is_dept_head = 0 WHERE id = ?')->execute([$id]);

        $pdo->prepare('UPDATE employees SET is_active = 0 WHERE id = ?')->execute([$id]);

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('deleteEmployee 실패: ' . $e->getMessage());
        respond(500, ['error' => '직원 삭제 중 오류가 발생했습니다.']);
    }

    respond(200, ['message' => '직원이 삭제되었습니다.']);
}

// === 직원 일괄 등록 ===

function bulkCreateEmployees(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = getJsonInput();
    $employees = $data['employees'] ?? [];
    if (!is_array($employees) || empty($employees)) {
        respond(400, ['error' => '등록할 직원 데이터가 없습니다.']);
    }
    if (count($employees) > 200) {
        respond(400, ['error' => '한 번에 최대 200명까지 등록할 수 있습니다.']);
    }

    $deptStmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? AND is_active = 1 LIMIT 1');

    $insertStmt = $pdo->prepare('
        INSERT INTO employees (employee_no, department_id, affiliation, name, position, rank_id,
            email, phone, hire_date, employment_type, employment_status)
        VALUES (:employee_no, :department_id, :affiliation, :name, :position, :rank_id,
            :email, :phone, :hire_date, :employment_type, :employment_status)
    ');

    $year = date('Y');
    $maxStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(employee_no, '-', -1) AS UNSIGNED)) FROM employees WHERE employee_no LIKE ?");
    $maxStmt->execute([$year . '-%']);
    $nextSeq = ((int)$maxStmt->fetchColumn()) + 1;

    $created = 0;
    $errors = [];

    $pdo->beginTransaction();
    try {
        foreach ($employees as $i => $emp) {
            $name = trim($emp['name'] ?? '');
            if ($name === '') {
                $errors[] = ['row' => $i + 1, 'reason' => '이름이 비어있습니다.'];
                continue;
            }

            $deptId = null;
            $deptName = trim($emp['department'] ?? '');
            if ($deptName !== '') {
                $deptStmt->execute([$deptName]);
                $deptId = $deptStmt->fetchColumn() ?: null;
            }

            $employeeNo = $year . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
            $nextSeq++;

            $empType = trim($emp['employment_type'] ?? '') ?: '정규직';
            $hireDate = trim($emp['join_date'] ?? '') ?: null;
            $bulkPosition = trim($emp['position'] ?? '') ?: null;
            $bulkRankId = $bulkPosition ? resolveRankId($pdo, $bulkPosition) : null;

            $insertStmt->execute([
                'employee_no'       => $employeeNo,
                'department_id'     => $deptId,
                'affiliation'       => trim($emp['affiliation'] ?? '') ?: null,
                'name'              => $name,
                'position'          => $bulkPosition,
                'rank_id'           => $bulkRankId,
                'email'             => trim($emp['email'] ?? '') ?: null,
                'phone'             => trim($emp['phone'] ?? '') ?: null,
                'hire_date'         => $hireDate,
                'employment_type'   => $empType,
                'employment_status' => '재직',
            ]);

            $newId = (int)$pdo->lastInsertId();

            try {
                $changes = [
                    'department_id'     => ['prev' => null, 'new' => $deptId],
                    'position'          => ['prev' => null, 'new' => trim($emp['position'] ?? '') ?: null],
                    'title'             => ['prev' => null, 'new' => null],
                    'employment_type'   => ['prev' => null, 'new' => $empType],
                    'employment_status' => ['prev' => null, 'new' => '재직'],
                ];
                recordAppointment($pdo, $newId, $changes, '신규입사', $hireDate ?: date('Y-m-d'), 'auto', null, $_SESSION['user_id'] ?? null);
            } catch (\Throwable $e) {
                error_log("bulkCreateEmployees: 발령 기록 실패 (row {$i}) - " . $e->getMessage());
                $errors[] = ['row' => $i + 1, 'reason' => '직원 등록 완료, 발령이력 기록 실패'];
            }

            $created++;
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('bulkCreateEmployees: ' . $e->getMessage());
        respond(500, ['error' => '일괄 등록 중 오류가 발생했습니다.']);
    }

    respond(200, [
        'success' => true,
        'created' => $created,
        'skipped' => count($errors),
        'errors'  => $errors,
        'message' => "{$created}명이 등록되었습니다.",
    ]);
}

// === 조직도 전체 트리 (부서 + 직원) ===

function getOrgTree(PDO $pdo): void
{
    // 모든 부서
    $deptStmt = $pdo->query('
        SELECT d.id, d.parent_id, d.name, d.code, d.head_employee_id, d.sort_order
        FROM departments d WHERE d.is_active = 1
        ORDER BY d.sort_order, d.name
    ');
    $departments = $deptStmt->fetchAll();

    // 모든 직원
    $empStmt = $pdo->query('
        SELECT e.id, e.department_id, e.name, e.position, e.title, e.email, e.phone, e.profile_image, e.is_dept_head
        FROM employees e
        LEFT JOIN hr_ranks _tr ON _tr.id = e.rank_id
        WHERE e.is_active = 1
        ORDER BY COALESCE(_tr.sort_order, 999), e.name
    ');
    $employees = $empStmt->fetchAll();

    // 부서별 직원 매핑
    $empByDept = [];
    foreach ($employees as $emp) {
        $empByDept[$emp['department_id']][] = $emp;
    }

    // 트리 구성
    $tree = buildOrgTree($departments, $empByDept);

    respond(200, ['tree' => $tree]);
}

function buildOrgTree(array $depts, array $empByDept, ?int $parentId = null): array
{
    $tree = [];
    foreach ($depts as $dept) {
        if ($dept['parent_id'] == $parentId) {
            $node = [
                'id' => (int)$dept['id'],
                'name' => $dept['name'],
                'code' => $dept['code'],
                'head_employee_id' => $dept['head_employee_id'],
                'employees' => $empByDept[$dept['id']] ?? [],
                'children' => buildOrgTree($depts, $empByDept, (int)$dept['id']),
            ];
            $tree[] = $node;
        }
    }
    return $tree;
}

// ─────────────────────────────────────────────────────────────
// 내 정보 · 로그인 사용자 본인만 조회/수정 가능.
// 세션 ID로 강제 귀속해 URL 파라미터로 타인 ID 조작 불가.
// ─────────────────────────────────────────────────────────────

function currentEmployeeId(): int
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        respond(401, ['error' => '로그인이 필요합니다.']);
    }
    return $id;
}

function getMyProfile(PDO $pdo): void
{
    $id = currentEmployeeId();

    // 기본 컬럼 + (있다면) 확장 컬럼을 선택적으로 포함.
    $extraCols = [];
    foreach (['employment_type','employment_status','user_role','birth_date','gender','zipcode','address1','address2'] as $col) {
        $check = $pdo->prepare("SHOW COLUMNS FROM employees LIKE ?");
        $check->execute([$col]);
        if ($check->fetchColumn()) $extraCols[] = "e.$col";
    }
    $selectExtra = $extraCols ? (', ' . implode(', ', $extraCols)) : '';

    $sql = "SELECT e.id, e.department_id, e.name, e.position, e.title,
                   e.email, e.phone, e.profile_image, e.hire_date,
                   d.name AS department_name $selectExtra
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.id = ? AND e.is_active = 1
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$emp) {
        respond(404, ['error' => '사용자 정보를 찾을 수 없습니다.']);
    }
    $employees = [$emp];
    attachOrgPathToEmployeeRows($pdo, $employees);
    $emp = $employees[0];
    respond(200, ['profile' => $emp]);
}

function updateMyProfile(PDO $pdo): void
{
    $id = currentEmployeeId();
    $data = getJsonInput();

    $name  = trim((string)($data['name']  ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));

    $zipcode  = trim((string)($data['zipcode']  ?? ''));
    $address1 = trim((string)($data['address1'] ?? ''));
    $address2 = trim((string)($data['address2'] ?? ''));

    if ($name === '') {
        respond(400, ['error' => '이름은 필수 입력입니다.']);
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(400, ['error' => '올바른 이메일 형식이 아닙니다.']);
    }
    if ($phone !== '' && !preg_match('/^[0-9\-+ ]{9,20}$/', $phone)) {
        respond(400, ['error' => '올바른 전화번호 형식이 아닙니다.']);
    }
    if ($zipcode !== '' && !preg_match('/^\d{5}$/', $zipcode)) {
        respond(400, ['error' => '우편번호는 5자리 숫자여야 합니다.']);
    }
    if (mb_strlen($address1) > 200) {
        respond(400, ['error' => '기본주소는 200자 이내여야 합니다.']);
    }
    if (mb_strlen($address2) > 200) {
        respond(400, ['error' => '상세주소는 200자 이내여야 합니다.']);
    }

    // 이메일은 로그인 아이디이므로 중복 검사
    $dup = $pdo->prepare('SELECT id FROM employees WHERE email = ? AND id <> ? AND is_active = 1');
    $dup->execute([$email, $id]);
    if ($dup->fetchColumn()) {
        respond(409, ['error' => '이미 사용 중인 이메일입니다.']);
    }

    $stmt = $pdo->prepare('
        UPDATE employees
           SET name = :name, email = :email, phone = :phone,
               zipcode = :zipcode, address1 = :address1, address2 = :address2
         WHERE id = :id
    ');
    $stmt->execute([
        'id'       => $id,
        'name'     => $name,
        'email'    => $email,
        'phone'    => $phone !== '' ? $phone : null,
        'zipcode'  => $zipcode !== '' ? $zipcode : null,
        'address1' => $address1 !== '' ? $address1 : null,
        'address2' => $address2 !== '' ? $address2 : null,
    ]);

    // 세션 캐시 갱신
    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['name']  = $name;
        $_SESSION['user']['email'] = $email;
    }

    respond(200, ['message' => '내 정보가 저장되었습니다.']);
}

function uploadMyProfilePhoto(PDO $pdo): void
{
    $id = currentEmployeeId();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(405, ['error' => 'POST 요청만 허용됩니다.']);
    }
    if (empty($_FILES['profile_photo']) || !is_array($_FILES['profile_photo'])) {
        respond(400, ['error' => '업로드할 사진을 선택해주세요.']);
    }

    $file = $_FILES['profile_photo'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        respond(400, ['error' => '사진 업로드에 실패했습니다. 다시 시도해주세요.']);
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 5 * 1024 * 1024) {
        respond(400, ['error' => '사진은 5MB 이하만 업로드할 수 있습니다.']);
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        respond(400, ['error' => '유효하지 않은 업로드 파일입니다.']);
    }

    $size = @getimagesize($tmp);
    if ($size === false) {
        respond(400, ['error' => '이미지 파일을 확인할 수 없습니다.']);
    }
    $mime = $size['mime'] ?? '';
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extMap[$mime])) {
        respond(400, ['error' => 'JPG, PNG, WEBP 형식의 사진만 업로드할 수 있습니다.']);
    }
    $width = (int)($size[0] ?? 0);
    $height = (int)($size[1] ?? 0);
    if ($width <= 0 || $height <= 0 || $width > 5000 || $height > 5000 || ($width * $height) > 16000000) {
        respond(400, ['error' => '사진 해상도는 최대 5000px, 1600만 픽셀 이하만 허용됩니다.']);
    }

    $oldStmt = $pdo->prepare('SELECT profile_image FROM employees WHERE id = ? AND is_active = 1');
    $oldStmt->execute([$id]);
    $oldPath = $oldStmt->fetchColumn();
    if ($oldPath === false) {
        respond(404, ['error' => '사용자 정보를 찾을 수 없습니다.']);
    }
    $oldPath = (string)($oldPath ?: '');

    $uploadDir = __DIR__ . '/../uploads/profiles';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        error_log('[Organization] profile upload mkdir failed: ' . $uploadDir);
        respond(500, ['error' => '사진 저장 경로를 만들 수 없습니다.']);
    }

    $filename = 'profile_' . $id . '_' . bin2hex(random_bytes(8)) . '.' . $extMap[$mime];
    $destPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destPath)) {
        error_log('[Organization] profile upload move failed: ' . $destPath);
        respond(500, ['error' => '사진 저장에 실패했습니다.']);
    }
    @chmod($destPath, 0644);

    $newPath = 'uploads/profiles/' . $filename;

    $stmt = $pdo->prepare('UPDATE employees SET profile_image = ? WHERE id = ? AND is_active = 1');
    $stmt->execute([$newPath, $id]);
    if ($stmt->rowCount() < 1) {
        @unlink($destPath);
        respond(404, ['error' => '사용자 정보를 찾을 수 없습니다.']);
    }

    // uploads/profiles 내부의 기존 파일만 정리한다. 외부/절대 경로는 건드리지 않는다.
    if ($oldPath !== '' && str_starts_with($oldPath, 'uploads/profiles/')) {
        $oldFile = realpath(__DIR__ . '/../' . $oldPath);
        $baseDir = realpath($uploadDir);
        if ($oldFile && $baseDir && str_starts_with($oldFile, $baseDir . DIRECTORY_SEPARATOR) && is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['profile_image'] = $newPath;
    }

    respond(200, [
        'message' => '프로필 사진이 저장되었습니다.',
        'profile_image' => $newPath,
    ]);
}

function uploadEmployeePhoto(PDO $pdo): void
{
    $currentRole = $_SESSION['user']['role'] ?? 'user';
    if (!in_array($currentRole, ['admin', 'manager'], true)) {
        respond(403, ['error' => '권한이 없습니다.']);
    }
    $empId = (int)($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
    if ($empId <= 0) {
        respond(400, ['error' => '직원 ID가 필요합니다.']);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(405, ['error' => 'POST 요청만 허용됩니다.']);
    }
    if (empty($_FILES['profile_photo'])) {
        respond(400, ['error' => '업로드할 사진을 선택해주세요.']);
    }
    $file = $_FILES['profile_photo'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        respond(400, ['error' => '사진 업로드에 실패했습니다.']);
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 5 * 1024 * 1024) {
        respond(400, ['error' => '사진은 5MB 이하만 업로드할 수 있습니다.']);
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        respond(400, ['error' => '유효하지 않은 업로드 파일입니다.']);
    }
    $size = @getimagesize($tmp);
    if ($size === false) {
        respond(400, ['error' => '이미지 파일을 확인할 수 없습니다.']);
    }
    $mime = $size['mime'] ?? '';
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extMap[$mime])) {
        respond(400, ['error' => 'JPG, PNG, WEBP 형식만 가능합니다.']);
    }
    $width = (int)($size[0] ?? 0);
    $height = (int)($size[1] ?? 0);
    if ($width <= 0 || $height <= 0 || $width > 5000 || $height > 5000) {
        respond(400, ['error' => '사진 해상도는 최대 5000px입니다.']);
    }

    $oldStmt = $pdo->prepare('SELECT profile_image FROM employees WHERE id = ? AND is_active = 1');
    $oldStmt->execute([$empId]);
    $oldPath = $oldStmt->fetchColumn();
    if ($oldPath === false) {
        respond(404, ['error' => '직원 정보를 찾을 수 없습니다.']);
    }
    $oldPath = (string)($oldPath ?: '');

    $uploadDir = __DIR__ . '/../uploads/profiles';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        respond(500, ['error' => '사진 저장 경로를 만들 수 없습니다.']);
    }
    $filename = 'profile_' . $empId . '_' . bin2hex(random_bytes(8)) . '.' . $extMap[$mime];
    $destPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destPath)) {
        respond(500, ['error' => '사진 저장에 실패했습니다.']);
    }
    @chmod($destPath, 0644);

    $newPath = 'uploads/profiles/' . $filename;
    $pdo->prepare('UPDATE employees SET profile_image = ? WHERE id = ? AND is_active = 1')
        ->execute([$newPath, $empId]);

    if ($oldPath !== '' && str_starts_with($oldPath, 'uploads/profiles/')) {
        $oldFile = realpath(__DIR__ . '/../' . $oldPath);
        $baseDir = realpath($uploadDir);
        if ($oldFile && $baseDir && str_starts_with($oldFile, $baseDir . DIRECTORY_SEPARATOR) && is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    if ($empId === (int)($_SESSION['user_id'] ?? 0) && !empty($_SESSION['user'])) {
        $_SESSION['user']['profile_image'] = $newPath;
    }

    respond(200, ['message' => '프로필 사진이 저장되었습니다.', 'profile_image' => $newPath]);
}

function deleteProfilePhoto(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(405, ['error' => 'POST 요청만 허용됩니다.']);
    }
    $data = getJsonInput();
    $empId = (int)($data['employee_id'] ?? 0);
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $currentRole = $_SESSION['user']['role'] ?? 'user';

    if ($empId <= 0) {
        $empId = $currentUserId;
    }
    if ($empId !== $currentUserId && !in_array($currentRole, ['admin', 'manager'], true)) {
        respond(403, ['error' => '권한이 없습니다.']);
    }
    if ($empId <= 0) {
        respond(401, ['error' => '로그인이 필요합니다.']);
    }

    $stmt = $pdo->prepare('SELECT profile_image FROM employees WHERE id = ? AND is_active = 1');
    $stmt->execute([$empId]);
    $oldPath = $stmt->fetchColumn();
    if ($oldPath === false) {
        respond(404, ['error' => '직원 정보를 찾을 수 없습니다.']);
    }
    $oldPath = (string)($oldPath ?: '');
    if ($oldPath === '') {
        respond(400, ['error' => '삭제할 프로필 사진이 없습니다.']);
    }

    $pdo->prepare('UPDATE employees SET profile_image = NULL WHERE id = ? AND is_active = 1')
        ->execute([$empId]);

    if ($oldPath !== '' && str_starts_with($oldPath, 'uploads/profiles/')) {
        $uploadDir = __DIR__ . '/../uploads/profiles';
        $oldFile = realpath(__DIR__ . '/../' . $oldPath);
        $baseDir = realpath($uploadDir);
        if ($oldFile && $baseDir && str_starts_with($oldFile, $baseDir . DIRECTORY_SEPARATOR) && is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    if ($empId === $currentUserId && !empty($_SESSION['user'])) {
        $_SESSION['user']['profile_image'] = '';
    }

    respond(200, ['message' => '프로필 사진이 삭제되었습니다.']);
}

function changeMyPassword(PDO $pdo): void
{
    $id = currentEmployeeId();
    $data = getJsonInput();

    $current = (string)($data['current_password'] ?? '');
    $new     = (string)($data['new_password'] ?? '');
    $confirm = (string)($data['new_password_confirm'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
        respond(400, ['error' => '비밀번호를 모두 입력해주세요.']);
    }
    if ($new !== $confirm) {
        respond(400, ['error' => '새 비밀번호 확인이 일치하지 않습니다.']);
    }
    if (strlen($new) < 8) {
        respond(400, ['error' => '새 비밀번호는 8자 이상이어야 합니다.']);
    }
    if ($new === $current) {
        respond(400, ['error' => '새 비밀번호가 현재 비밀번호와 동일합니다.']);
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM employees WHERE id = ? AND is_active = 1');
    $stmt->execute([$id]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) {
        respond(400, ['error' => '현재 비밀번호가 올바르지 않습니다.']);
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $up = $pdo->prepare('UPDATE employees SET password_hash = ? WHERE id = ?');
    $up->execute([$newHash, $id]);

    respond(200, ['message' => '비밀번호가 변경되었습니다.']);
}
