<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/payroll_calc.php';

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

function checkClosingLock(PDO $pdo, int $year, int $month): void {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM closing_locks WHERE year = ? AND month = ? AND is_locked = 1 LIMIT 1");
        $stmt->execute([$year, $month]);
        if ($stmt->fetch()) {
            apiError('LOCKED', "{$year}년 {$month}월은 마감 상태입니다.");
        }
    } catch (\PDOException $e) {
        if ($e->getCode() !== '42S02') {
            error_log('payslip: closing_lock check failed: ' . $e->getMessage());
            apiError('DB_ERROR', '마감 상태 확인 중 오류가 발생했습니다.', 500);
        }
    }
}

function getMonthStatus(PDO $pdo, int $year, int $month): string {
    $stmt = $pdo->prepare("SELECT DISTINCT status FROM payslips WHERE year = ? AND month = ?");
    $stmt->execute([$year, $month]);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($statuses)) return 'empty';
    if (in_array('paid', $statuses)) return 'paid';
    if (in_array('confirmed', $statuses)) return 'confirmed';
    return 'draft';
}

function loadPayslipItems(PDO $pdo, int $payslipId): array {
    $stmt = $pdo->prepare("
        SELECT si.pay_type_id, si.amount, si.hours, pt.code, pt.name, pt.category
        FROM payslip_items si
        JOIN payroll_pay_types pt ON si.pay_type_id = pt.id
        WHERE si.payslip_id = ?
        ORDER BY pt.sort_order
    ");
    $stmt->execute([$payslipId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function savePayslipItemsToDB(PDO $pdo, int $payslipId, array $items): void {
    $upsert = $pdo->prepare("
        INSERT INTO payslip_items (payslip_id, pay_type_id, amount, hours)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), hours = VALUES(hours)
    ");
    foreach ($items as $item) {
        $upsert->execute([
            $payslipId,
            (int)$item['pay_type_id'],
            (int)$item['amount'],
            isset($item['hours']) ? (float)$item['hours'] : null,
        ]);
    }
}

function updatePayslipSummary(PDO $pdo, int $payslipId, int $gross, int $totalDeduct, int $net): void {
    $pdo->prepare("UPDATE payslips SET gross_pay = ?, total_deduction = ?, net_pay = ? WHERE id = ?")
        ->execute([$gross, $totalDeduct, $net, $payslipId]);
}

switch ($action) {
    case 'getPayTypes':
        apiRequireAdminOrManager();
        $types = getPayTypes($pdo);
        apiOk(['types' => $types]);

    case 'getRates':
        apiRequireAdminOrManager();
        $deductTypes = getPayrollRatesFull($pdo);
        $summary = getPayrollRates($pdo);
        $otType = null;
        try {
            $otStmt = $pdo->query("SELECT id, code, name, custom_hourly_rate FROM payroll_pay_types WHERE code = 'OT' AND is_active = 1 LIMIT 1");
            $otType = $otStmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('payslip getRates OT query: ' . $e->getMessage());
        }
        apiOk(['deductTypes' => $deductTypes, 'summary' => $summary, 'otType' => $otType]);

    case 'saveRates':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdmin();

        $input = apiJsonInput();
        $updates = $input['rates'] ?? [];
        if (empty($updates)) apiError('INVALID_PARAM', '변경할 요율 데이터가 없습니다.');

        $stmtRate = $pdo->prepare("UPDATE payroll_pay_types SET calc_rate = ? WHERE id = ? AND calc_type = 'rate'");
        $changed = 0;
        foreach ($updates as $u) {
            $id = (int)($u['id'] ?? 0);
            $rate = (float)($u['calc_rate'] ?? 0);
            if (!$id) continue;
            if ($rate < 0 || $rate > 0.5) apiError('INVALID_PARAM', '요율은 0~50% 범위여야 합니다.');
            $stmtRate->execute([$rate, $id]);
            $changed += $stmtRate->rowCount();
        }

        $otHourlyRate = $input['otHourlyRate'] ?? null;
        if ($otHourlyRate !== null) {
            $otVal = $otHourlyRate === '' || $otHourlyRate === 0 ? null : (int)$otHourlyRate;
            if ($otVal !== null && ($otVal < 0 || $otVal > 500000)) {
                apiError('INVALID_PARAM', '시급은 0~500,000원 범위여야 합니다.');
            }
            $stmtOt = $pdo->prepare("UPDATE payroll_pay_types SET custom_hourly_rate = ? WHERE code = 'OT'");
            $stmtOt->execute([$otVal]);
            $changed += $stmtOt->rowCount();
        }

        $newRates = getPayrollRatesFull($pdo);
        $otType = null;
        try {
            $otStmt = $pdo->query("SELECT id, code, name, custom_hourly_rate FROM payroll_pay_types WHERE code = 'OT' AND is_active = 1 LIMIT 1");
            $otType = $otStmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) { /* ignore */ }
        apiOk(['changed' => $changed, 'deductTypes' => $newRates, 'otType' => $otType]);

    case 'load':
        apiRequireAdminOrManager();
        $year  = (int)($_GET['year'] ?? 0);
        $month = (int)($_GET['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        $stmt = $pdo->prepare("
            SELECT p.id, p.employee_id, p.employee_name, p.year, p.month,
                   p.gross_pay, p.total_deduction, p.net_pay,
                   p.status, p.memo, p.confirmed_at, p.confirmed_by, p.paid_at,
                   e.position, e.hire_date, e.birth_date,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name,'') END AS division_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE p.year = ? AND p.month = ?
            ORDER BY p.employee_id
        ");
        $stmt->execute([$year, $month]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['items'] = loadPayslipItems($pdo, (int)$row['id']);
        }
        unset($row);

        $monthStatus = getMonthStatus($pdo, $year, $month);
        $payTypes = getPayTypes($pdo);
        $rates = getPayrollRates($pdo, $year);
        apiOk(['rows' => $rows, 'count' => count($rows), 'monthStatus' => $monthStatus, 'payTypes' => $payTypes, 'rates' => $rates]);

    case 'get':
        apiRequireAdminOrManager();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) apiError('INVALID_PARAM', 'ID를 지정해주세요.');

        $stmt = $pdo->prepare("
            SELECT p.id, p.employee_id, p.employee_name, p.year, p.month,
                   p.gross_pay, p.total_deduction, p.net_pay,
                   p.status, p.memo, p.confirmed_at, p.confirmed_by, p.paid_at,
                   e.position, e.hire_date, e.birth_date, e.gender,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name,'') END AS division_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) apiError('NOT_FOUND', '해당 급여 데이터를 찾을 수 없습니다.', 404);
        $row['items'] = loadPayslipItems($pdo, (int)$row['id']);

        $startDate = sprintf('%04d-%02d-01', $row['year'], $row['month']);
        $endDate = date('Y-m-t', strtotime($startDate));
        try {
            $attStmt = $pdo->prepare("
                SELECT COUNT(*) AS work_days,
                       COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(clock_out, clock_in))), 0) AS total_seconds
                FROM attendance_records
                WHERE employee_id = ? AND record_date BETWEEN ? AND ?
                  AND clock_in IS NOT NULL AND clock_out IS NOT NULL
            ");
            $attStmt->execute([(int)$row['employee_id'], $startDate, $endDate]);
            $att = $attStmt->fetch(PDO::FETCH_ASSOC);
            $row['work_days'] = (int)($att['work_days'] ?? 0);
            $row['work_hours'] = round(((int)($att['total_seconds'] ?? 0)) / 3600, 1);
        } catch (\PDOException $e) {
            $row['work_days'] = 0;
            $row['work_hours'] = 0;
        }

        $payTypes = getPayTypes($pdo);
        apiOk(['item' => $row, 'payTypes' => $payTypes]);

    case 'updateItem':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $id = (int)($input['id'] ?? 0);
        if (!$id) apiError('INVALID_PARAM', 'ID를 지정해주세요.');

        $stmt = $pdo->prepare("SELECT id, year, month, status FROM payslips WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) apiError('NOT_FOUND', '해당 급여 데이터를 찾을 수 없습니다.', 404);
        if ($existing['status'] !== 'draft') apiError('STATUS', '작성중 상태에서만 수정 가능합니다.');

        checkClosingLock($pdo, (int)$existing['year'], (int)$existing['month']);

        $payItems = $input['items'] ?? [];
        $memo = (string)($input['memo'] ?? '');
        if (empty($payItems)) apiError('INVALID_PARAM', '항목 데이터가 없습니다.');

        $payTypes = getPayTypes($pdo);
        $typeMap = array_column($payTypes, null, 'id');

        $pdo->beginTransaction();
        try {
            savePayslipItemsToDB($pdo, $id, $payItems);

            $baseSalary = 0;
            $grossPay = 0;
            foreach ($payItems as $pi) {
                $tid = (int)$pi['pay_type_id'];
                $t = $typeMap[$tid] ?? null;
                if (!$t || $t['category'] !== 'pay') continue;
                $grossPay += (int)$pi['amount'];
                if ($t['code'] === 'BASE') $baseSalary = (int)$pi['amount'];
            }

            $deductTypes = array_filter($payTypes, fn($t) => $t['category'] === 'deduct');
            $dedResult = calcDeductionsFromTypes($deductTypes, $baseSalary, $grossPay);
            foreach ($dedResult['items'] as $di) {
                savePayslipItemsToDB($pdo, $id, [$di]);
            }

            $net = $grossPay - $dedResult['total'];
            updatePayslipSummary($pdo, $id, $grossPay, $dedResult['total'], $net);

            $pdo->prepare("UPDATE payslips SET memo = ? WHERE id = ?")->execute([$memo, $id]);

            $pdo->commit();
            apiOk(['id' => $id, 'gross' => $grossPay, 'deduction' => $dedResult['total'], 'net' => $net]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('payslip: updateItem failed: ' . $e->getMessage());
            apiError('DB_ERROR', '저장 중 오류가 발생했습니다.', 500);
        }

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $year  = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        $items = $input['items'] ?? [];

        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');
        if (empty($items)) apiError('INVALID_PARAM', '저장할 급여 데이터가 없습니다.');

        checkClosingLock($pdo, $year, $month);

        $pdo->beginTransaction();
        try {
            $lockCheck = $pdo->prepare(
                "SELECT status FROM payslips WHERE year = ? AND month = ? AND status != 'draft' LIMIT 1 FOR UPDATE"
            );
            $lockCheck->execute([$year, $month]);
            if ($lockCheck->fetch()) {
                $pdo->rollBack();
                apiError('STATUS', '확정 또는 지급완료 상태에서는 일괄 저장할 수 없습니다.');
            }

            $del = $pdo->prepare("DELETE FROM payslips WHERE year = ? AND month = ? AND status = 'draft'");
            $del->execute([$year, $month]);

            $ins = $pdo->prepare("
                INSERT INTO payslips (
                    employee_id, employee_name, year, month,
                    gross_pay, total_deduction, net_pay, status
                ) VALUES (?,?,?,?,?,?,?,'draft')
            ");

            $saved = 0;
            foreach ($items as $item) {
                $ins->execute([
                    (int)$item['employee_id'],
                    (string)$item['employee_name'],
                    $year, $month,
                    (int)$item['gross_pay'],
                    (int)$item['total_deduction'],
                    (int)$item['net_pay'],
                ]);
                $payslipId = (int)$pdo->lastInsertId();

                if (empty($item['payItems'])) {
                    error_log("payslip save: employee {$item['employee_id']} has no payItems, skipping items save");
                } else {
                    savePayslipItemsToDB($pdo, $payslipId, $item['payItems']);
                }
                $saved++;
            }

            $pdo->commit();
            apiOk(['saved' => $saved, 'year' => $year, 'month' => $month]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('payslip: save failed: ' . $e->getMessage());
            apiError('DB_ERROR', '저장 중 오류가 발생했습니다.', 500);
        }

    case 'confirm':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $year  = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        checkClosingLock($pdo, $year, $month);

        $userId = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare("
            UPDATE payslips SET status = 'confirmed', confirmed_at = NOW(), confirmed_by = ?
            WHERE year = ? AND month = ? AND status = 'draft'
        ");
        $stmt->execute([$userId, $year, $month]);
        $cnt = $stmt->rowCount();
        if ($cnt === 0) apiError('NO_DRAFT', '확정할 작성중 급여가 없습니다.');
        apiOk(['confirmed' => $cnt, 'year' => $year, 'month' => $month]);

    case 'unconfirm':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $year  = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        $ms = getMonthStatus($pdo, $year, $month);
        if ($ms === 'paid') apiError('STATUS', '지급완료 상태에서는 확정해제할 수 없습니다.');

        $stmt = $pdo->prepare("
            UPDATE payslips SET status = 'draft', confirmed_at = NULL, confirmed_by = NULL
            WHERE year = ? AND month = ? AND status = 'confirmed'
        ");
        $stmt->execute([$year, $month]);
        $cnt = $stmt->rowCount();
        if ($cnt === 0) apiError('NO_CONFIRMED', '확정해제할 급여가 없습니다.');
        apiOk(['unconfirmed' => $cnt]);

    case 'pay':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $year  = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        $ms = getMonthStatus($pdo, $year, $month);
        if ($ms !== 'confirmed') apiError('STATUS', '확정 상태에서만 지급완료 처리 가능합니다.');

        $stmt = $pdo->prepare("
            UPDATE payslips SET status = 'paid', paid_at = NOW()
            WHERE year = ? AND month = ? AND status = 'confirmed'
        ");
        $stmt->execute([$year, $month]);
        apiOk(['paid' => $stmt->rowCount()]);

    case 'exportCsv':
        apiRequireAdminOrManager();
        $year  = (int)($_GET['year'] ?? 0);
        $month = (int)($_GET['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        $payTypes = getPayTypes($pdo);

        $stmt = $pdo->prepare("
            SELECT p.id, p.employee_name, p.gross_pay, p.total_deduction, p.net_pay, p.memo,
                   e.position,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name,'') END AS division_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE p.year = ? AND p.month = ?
            ORDER BY p.employee_id
        ");
        $stmt->execute([$year, $month]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "임금대장_{$year}년{$month}월.csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"payroll_{$year}_{$month}.csv\"; filename*=UTF-8''" . rawurlencode($filename));
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');

        $headers = ['이름', '소속', '직급'];
        foreach ($payTypes as $pt) {
            $headers[] = $pt['name'];
            if ($pt['has_hours']) $headers[] = $pt['name'] . '(h)';
        }
        $headers = array_merge($headers, ['총지급액', '총공제액', '실수령액', '비고']);
        fputcsv($out, $headers, ',', '"', '');

        foreach ($rows as $r) {
            $items = loadPayslipItems($pdo, (int)$r['id']);
            $itemMap = array_column($items, null, 'code');

            $csvRow = [$r['employee_name'], $r['division_name'], $r['position'] ?? ''];
            foreach ($payTypes as $pt) {
                $it = $itemMap[$pt['code']] ?? null;
                $csvRow[] = $it ? $it['amount'] : 0;
                if ($pt['has_hours']) $csvRow[] = $it ? ($it['hours'] ?? 0) : 0;
            }
            $csvRow[] = $r['gross_pay'];
            $csvRow[] = $r['total_deduction'];
            $csvRow[] = $r['net_pay'];
            $csvRow[] = $r['memo'];
            fputcsv($out, $csvRow, ',', '"', '');
        }
        fclose($out);
        exit;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $year  = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        checkClosingLock($pdo, $year, $month);

        $ms = getMonthStatus($pdo, $year, $month);
        if ($ms === 'confirmed' || $ms === 'paid') {
            apiError('STATUS', '확정 또는 지급완료 상태에서는 삭제할 수 없습니다. 확정해제 후 삭제하세요.');
        }

        $stmt = $pdo->prepare("DELETE FROM payslips WHERE year = ? AND month = ? AND status = 'draft'");
        $stmt->execute([$year, $month]);
        apiOk(['deleted' => $stmt->rowCount()]);

    case 'refreshFromContracts':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('METHOD', 'POST만 허용', 405);
        apiRequireAdminOrManager();

        $input = apiJsonInput();
        $year  = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        if (!$year || !$month) apiError('INVALID_PARAM', '연도와 월을 지정해주세요.');

        checkClosingLock($pdo, $year, $month);

        $ms = getMonthStatus($pdo, $year, $month);
        if ($ms === 'confirmed' || $ms === 'paid') {
            apiError('STATUS', '확정 또는 지급완료 상태에서는 재생성할 수 없습니다.');
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM payslips WHERE year = ? AND month = ? AND status = 'draft'")
                ->execute([$year, $month]);

            $payTypes = getPayTypes($pdo);
            $deductTypes = array_filter($payTypes, fn($t) => $t['category'] === 'deduct');
            $codeToId = array_column($payTypes, 'id', 'code');

            $lcStmt = $pdo->query("
                SELECT lc.employee_id, lc.base_pay, lc.meal_allowance, lc.car_allowance,
                       lc.child_allowance, e.name
                FROM labor_contracts lc
                INNER JOIN (
                    SELECT employee_id, MAX(version) AS max_ver
                    FROM labor_contracts GROUP BY employee_id
                ) latest ON lc.employee_id = latest.employee_id AND lc.version = latest.max_ver
                LEFT JOIN employees e ON lc.employee_id = e.id
                WHERE (e.employment_status IS NULL OR e.employment_status != '퇴사')
                ORDER BY e.id
            ");
            $contracts = $lcStmt->fetchAll(PDO::FETCH_ASSOC);

            $insStmt = $pdo->prepare("
                INSERT INTO payslips (employee_id, employee_name, year, month,
                    gross_pay, total_deduction, net_pay, status)
                VALUES (?,?,?,?, ?,?,?,'draft')
            ");
            $siIns = $pdo->prepare("INSERT INTO payslip_items (payslip_id, pay_type_id, amount, hours) VALUES (?,?,?,?)");

            $refreshed = 0;
            foreach ($contracts as $c) {
                $base  = (int)($c['base_pay'] ?: 0);
                $meal  = (int)($c['meal_allowance'] ?: 0);
                $car   = (int)($c['car_allowance'] ?: 0);
                $child = (int)($c['child_allowance'] ?: 0);
                $gross = $base + $meal + $car + $child;
                $dedResult = calcDeductionsFromTypes($deductTypes, $base, $gross);
                $net = $gross - $dedResult['total'];

                $insStmt->execute([
                    (int)$c['employee_id'], $c['name'], $year, $month,
                    $gross, $dedResult['total'], $net
                ]);
                $psId = (int)$pdo->lastInsertId();

                $payItems = ['BASE' => $base, 'MEAL' => $meal, 'CAR' => $car, 'CHILD' => $child, 'OT' => 0];
                foreach ($payItems as $code => $amount) {
                    if (isset($codeToId[$code])) {
                        $siIns->execute([$psId, $codeToId[$code], $amount, null]);
                    }
                }
                foreach ($dedResult['items'] as $di) {
                    $siIns->execute([$psId, $di['pay_type_id'], $di['amount'], null]);
                }
                $refreshed++;
            }

            $pdo->commit();
            apiOk(['refreshed' => $refreshed, 'year' => $year, 'month' => $month]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('payslip: refreshFromContracts failed: ' . $e->getMessage());
            apiError('DB_ERROR', '재생성 중 오류가 발생했습니다.', 500);
        }

    default:
        apiError('UNKNOWN_ACTION', "알 수 없는 액션: {$action}");
}
