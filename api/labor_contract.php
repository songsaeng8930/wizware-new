<?php
/**
 * Zaemit 그룹웨어 - 근로계약서 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

// 모든 액션 공통: labor_contracts 테이블 자동 생성 (마이그레이션 미실행 환경 보호)
try {
    $__pdo = getDBConnection();
} catch (Throwable $e) { error_log('[LaborContract API] ensure: ' . $e->getMessage()); }

switch ($action) {
    case 'searchEmployee':  searchEmployee();  break;
    case 'getEmployee':     getEmployee();     break;
    case 'getContract':     getContract();     break;
    case 'save':            saveContract();    break;
    case 'sign':            signContract();    break;
    case 'getContractSalary':  getContractSalary();  break;
    case 'getContractHistory': getContractHistory(); break;
    default:
        respond(400, ['error' => '알 수 없는 액션']);
}

/* 테이블은 db/schema_labor_contract.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */

// ========== 헬퍼 ==========

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

function requireSelfOrAdmin(int $employeeId): void
{
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $role = (string)($_SESSION['user']['role'] ?? '');
    if ($currentUserId === $employeeId) return;
    if (in_array($role, ['admin', 'manager'], true)) return;
    respond(403, ['error' => '권한이 없습니다.']);
}

/** 직급별 기본급 매핑 (labor.php, payslip.php와 동일) */
function getBasePay(): array
{
    return [
        '대표이사' => 5000000, '이사' => 4500000, '부장' => 4000000, '차장' => 3500000,
        '과장' => 3000000, '대리' => 2500000, '주임' => 2200000, '사원' => 2000000, '인턴' => 1800000,
    ];
}

/** 직급별 수당 자동계산 */
function calcAllowances(string $position): array
{
    $meal = 200000;
    $car = in_array($position, ['대표이사', '이사']) ? 300000 : (in_array($position, ['부장', '과장']) ? 200000 : 0);
    return ['meal_allowance' => $meal, 'car_allowance' => $car, 'child_allowance' => 0];
}

/** 대표이사 이름 조회 */
function getCeoName(PDO $pdo): string
{
    try {
        $stmt = $pdo->query("SELECT name FROM employees WHERE position = '대표이사' AND employment_status = '재직' ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) return $row['name'];

        $stmt2 = $pdo->query("SELECT name FROM employees WHERE role = 'admin' AND employment_status = '재직' ORDER BY id ASC LIMIT 1");
        $row2 = $stmt2->fetch();
        return $row2 ? $row2['name'] : '(대표자 미등록)';
    } catch (PDOException $e) {
        error_log('[LaborContract API] CEO lookup: ' . $e->getMessage());
        return '(대표자 미등록)';
    }
}

// ========== 직원 검색 (이름 키워드) ==========
function searchEmployee(): void
{
    $keyword = trim($_GET['keyword'] ?? '');
    if ($keyword === '') {
        respond(400, ['error' => '검색어를 입력해주세요.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, e.position, e.employment_type,
                   COALESCE(d.name, '') AS department_name,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name, '') END AS division_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE e.name LIKE ? AND e.employment_status = '재직'
            ORDER BY e.name
            LIMIT 20
        ");
        $stmt->execute(['%' . $keyword . '%']);
        respond(200, ['employees' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        error_log('[LaborContract API] searchEmployee error: ' . $e->getMessage());
        respond(500, ['error' => '검색 중 오류가 발생했습니다.']);
    }
}

// ========== 직원 상세 + 기본급 ==========
function getEmployee(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        respond(400, ['error' => '직원 ID가 필요합니다.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, e.position, e.employment_type, e.hire_date,
                   e.email, e.phone,
                   COALESCE(d.name, '') AS department_name,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name, '') END AS division_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $emp = $stmt->fetch();

        if (!$emp) {
            respond(404, ['error' => '직원을 찾을 수 없습니다.']);
            return;
        }

        $basePay = getBasePay();
        $position = $emp['position'] ?: '사원';
        $base = $basePay[$position] ?? 2000000;
        $allowances = calcAllowances($position);

        respond(200, [
            'employee' => $emp,
            'salary' => array_merge(['base_pay' => $base], $allowances),
        ]);
    } catch (PDOException $e) {
        error_log('[LaborContract API] getEmployee error: ' . $e->getMessage());
        respond(500, ['error' => '직원 조회 중 오류가 발생했습니다.']);
    }
}

// ========== 계약서 조회 ==========
function getContract(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $employeeId = (int)($_GET['employee_id'] ?? 0);

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다.']);
        return;
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM labor_contracts WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($employeeId) {
            $stmt = $pdo->prepare("SELECT * FROM labor_contracts WHERE employee_id = ? ORDER BY version DESC LIMIT 1");
            $stmt->execute([$employeeId]);
        } else {
            respond(400, ['error' => 'ID 또는 employee_id가 필요합니다.']);
            return;
        }

        $contract = $stmt->fetch();
        if (!$contract) {
            respond(200, ['contract' => null]);
            return;
        }
        respond(200, ['contract' => $contract]);
    } catch (PDOException $e) {
        error_log('[LaborContract API] getContract error: ' . $e->getMessage());
        respond(500, ['error' => '계약서 조회 중 오류가 발생했습니다.']);
    }
}

// ========== 계약서 급여 조회 (payslip 연동용) ==========
function getContractSalary(): void
{
    $employeeId = (int)($_GET['employee_id'] ?? 0);
    if (!$employeeId) { respond(400, ['error' => 'employee_id가 필요합니다.']); return; }
    requireSelfOrAdmin($employeeId);

    $pdo = getDBConnection();
    if (!$pdo) { respond(503, ['error' => 'DB 연결에 실패했습니다.']); return; }

    try {
        $stmt = $pdo->prepare("
            SELECT base_pay, meal_allowance, car_allowance, child_allowance,
                   extra_pay_1, extra_pay_2, extra_pay_3,
                   monthly_total, annual_total, contract_status, version
            FROM labor_contracts
            WHERE employee_id = ?
            ORDER BY version DESC LIMIT 1
        ");
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        respond(200, ['salary' => $row ?: null]);
    } catch (PDOException $e) {
        error_log('[LaborContract API] getContractSalary error: ' . $e->getMessage());
        respond(500, ['error' => '급여 정보 조회 중 오류가 발생했습니다.']);
    }
}

// ========== 계약서 버전 이력 (급여 변경 추적) ==========
function getContractHistory(): void
{
    $employeeId = (int)($_GET['employee_id'] ?? 0);
    if (!$employeeId) { respond(400, ['error' => 'employee_id가 필요합니다.']); return; }
    requireSelfOrAdmin($employeeId);

    $pdo = getDBConnection();
    if (!$pdo) { respond(503, ['error' => 'DB 연결에 실패했습니다.']); return; }

    try {
        $stmt = $pdo->prepare("
            SELECT version, contract_status, base_pay, meal_allowance, car_allowance,
                   child_allowance, monthly_total, annual_total,
                   contract_start, signed_at, created_at
            FROM labor_contracts
            WHERE employee_id = ?
            ORDER BY version DESC
        ");
        $stmt->execute([$employeeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            if ($i < $count - 1) {
                $prev = $rows[$i + 1];
                $rows[$i]['delta_monthly'] = (int)$rows[$i]['monthly_total'] - (int)$prev['monthly_total'];
                $rows[$i]['delta_annual']  = (int)$rows[$i]['annual_total']  - (int)$prev['annual_total'];
            } else {
                $rows[$i]['delta_monthly'] = null;
                $rows[$i]['delta_annual']  = null;
            }
        }

        respond(200, ['history' => $rows]);
    } catch (PDOException $e) {
        error_log('[LaborContract API] getContractHistory error: ' . $e->getMessage());
        respond(500, ['error' => '이력 조회 중 오류가 발생했습니다.']);
    }
}

// ========== 계약서 저장 ==========
function saveContract(): void
{
    $data = getJsonInput();

    $id          = (int)($data['id'] ?? 0);
    $employeeId  = (int)($data['employee_id'] ?? 0);

    if (!$employeeId) {
        respond(400, ['error' => '직원을 선택해주세요.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다.']);
        return;
    }

    // 급여 서버 재계산
    $basePay      = (int)($data['base_pay'] ?? 0);
    $meal         = (int)($data['meal_allowance'] ?? 0);
    $car          = (int)($data['car_allowance'] ?? 0);
    $child        = (int)($data['child_allowance'] ?? 0);
    $extra1       = (int)($data['extra_pay_1'] ?? 0);
    $extra2       = (int)($data['extra_pay_2'] ?? 0);
    $extra3       = (int)($data['extra_pay_3'] ?? 0);
    $monthlyTotal = $basePay + $meal + $car + $child + $extra1 + $extra2 + $extra3;
    $annualTotal  = $monthlyTotal * 12;

    $fields = [
        'employee_id'     => $employeeId,
        'contract_status' => in_array(trim($data['contract_status'] ?? 'draft'), ['draft', 'signed'], true) ? trim($data['contract_status'] ?? 'draft') : 'draft',
        'company_name'    => trim($data['company_name'] ?? '주식회사 재밋'),
        'company_ceo'     => trim($data['company_ceo'] ?? getCeoName($pdo)),
        'company_address' => trim($data['company_address'] ?? '') ?: null,
        'company_bizno'   => trim($data['company_bizno'] ?? '') ?: null,
        'contract_type'   => trim($data['contract_type'] ?? 'permanent'),
        'contract_start'  => ($data['contract_start'] ?? '') ?: null,
        'contract_end'    => ($data['contract_end'] ?? '') ?: null,
        'job_description' => trim($data['job_description'] ?? '') ?: null,
        'workplace'       => trim($data['workplace'] ?? '') ?: null,
        'work_start'      => $data['work_start'] ?? '09:00',
        'work_end'        => $data['work_end'] ?? '18:00',
        'break_start'     => $data['break_start'] ?? '12:00',
        'break_end'       => $data['break_end'] ?? '13:00',
        'work_days'       => trim($data['work_days'] ?? '월~금'),
        'weekly_holiday'  => trim($data['weekly_holiday'] ?? '매주 토요일, 일요일'),
        'annual_leave'    => trim($data['annual_leave'] ?? '근로기준법에 의한 연차유급휴가'),
        'base_pay'        => $basePay,
        'meal_allowance'  => $meal,
        'car_allowance'   => $car,
        'child_allowance' => $child,
        'extra_pay_1'     => $extra1,
        'extra_pay_2'     => $extra2,
        'extra_pay_3'     => $extra3,
        'monthly_total'   => $monthlyTotal,
        'annual_total'    => $annualTotal,
        'pay_day'         => max(1, min(31, (int)($data['pay_day'] ?? 25))),
        'pay_method'      => trim($data['pay_method'] ?? 'transfer'),
        'ins_pension'     => (int)($data['ins_pension'] ?? 1),
        'ins_health'      => (int)($data['ins_health'] ?? 1),
        'ins_employment'  => (int)($data['ins_employment'] ?? 1),
        'ins_industrial'  => (int)($data['ins_industrial'] ?? 1),
        'retirement_pay'  => (int)($data['retirement_pay'] ?? 1),
        'probation'       => trim($data['probation'] ?? '3'),
        'additional_terms' => trim($data['additional_terms'] ?? '') ?: null,
    ];

    // 사용된 양식 기록 · template_id 가 넘어오면 서버에서 이름/버전 스냅샷을 함께 저장
    $tplId = (int)($data['template_id'] ?? 0);
    if ($tplId > 0) {
        try {
            $tplStmt = $pdo->prepare("SELECT name, version_label FROM contract_templates WHERE id = ?");
            $tplStmt->execute([$tplId]);
            $tplRow = $tplStmt->fetch();
            if ($tplRow) {
                $fields['template_id']      = $tplId;
                $fields['template_name']    = $tplRow['name'] ?? null;
                $fields['template_version'] = $tplRow['version_label'] ?? null;
            }
        } catch (PDOException $e) {
            // 컬럼 미존재 등 · 무시하고 계속 진행
            error_log('[LaborContract API] template snapshot skipped: ' . $e->getMessage());
        }
    }

    // contract_type, pay_method 화이트리스트 검증
    $allowedTypes = ['permanent', 'fixed', 'parttime'];
    $fields['contract_type'] = in_array($fields['contract_type'], $allowedTypes, true) ? $fields['contract_type'] : 'permanent';
    $allowedMethods = ['transfer', 'cash', 'other'];
    $fields['pay_method'] = in_array($fields['pay_method'], $allowedMethods, true) ? $fields['pay_method'] : 'transfer';

    try {
        if ($id) {
            // 체결된 계약서는 수정 불가
            $check = $pdo->prepare("SELECT contract_status FROM labor_contracts WHERE id = ?");
            $check->execute([$id]);
            $row = $check->fetch();
            if (!$row) {
                respond(404, ['error' => '계약서를 찾을 수 없습니다.']);
                return;
            }
            if ($row['contract_status'] === 'signed') {
                respond(403, ['error' => '체결된 계약서는 수정할 수 없습니다.']);
                return;
            }

            // UPDATE
            $sets = [];
            $params = [];
            foreach ($fields as $col => $val) {
                $sets[] = "{$col} = ?";
                $params[] = $val;
            }
            $params[] = $id;
            $pdo->prepare("UPDATE labor_contracts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            respond(200, ['success' => true, 'id' => $id, 'message' => '계약서가 저장되었습니다.']);
        } else {
            // INSERT · version 계산
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(version), 0) + 1 AS next_ver FROM labor_contracts WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            $fields['version'] = (int)$stmt->fetchColumn();

            $cols = implode(', ', array_keys($fields));
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $pdo->prepare("INSERT INTO labor_contracts ({$cols}) VALUES ({$placeholders})")->execute(array_values($fields));
            $newId = (int)$pdo->lastInsertId();
            respond(200, ['success' => true, 'id' => $newId, 'message' => '계약서가 저장되었습니다.']);
        }
    } catch (PDOException $e) {
        error_log('[LaborContract API] save error: ' . $e->getMessage());
        respond(500, ['error' => '저장 중 오류가 발생했습니다.']);
    }
}

// ========== 체결완료 ==========
function signContract(): void
{
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);

    if (!$id) {
        respond(400, ['error' => '계약서 ID가 필요합니다.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE labor_contracts SET contract_status = 'signed', signed_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            respond(404, ['error' => '계약서를 찾을 수 없습니다.']);
            return;
        }
        respond(200, ['success' => true, 'message' => '계약이 체결되었습니다.']);
    } catch (PDOException $e) {
        error_log('[LaborContract API] sign error: ' . $e->getMessage());
        respond(500, ['error' => '체결 처리 중 오류가 발생했습니다.']);
    }
}
