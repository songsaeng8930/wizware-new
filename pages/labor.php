<?php
$pageTitle = '노무관리';
$currentPage = 'labor';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('labor', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// 안전한 HTML 이스케이프 헬퍼 · 다른 곳에서 이미 정의되어 있으면 건너뜀
if (!function_exists('esc')) {
    function esc($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$showDivision = isOrgLevelEnabled('division');
$showDepartment = isOrgLevelEnabled('department');
$showAnyOrg = $showDivision || $showDepartment;
$orgHeaderParts = [];
if ($showDivision) $orgHeaderParts[] = getOrgLabel('division');
if ($showDepartment) $orgHeaderParts[] = getOrgLabel('department');
$orgHeaderLabel = implode(' · ', $orgHeaderParts);

// $basePath는 header.php에서 이미 설정됨
$tab = $_GET['tab'] ?? 'contract';
$annualYear = intval($_GET['year'] ?? date('Y'));
$payYear  = max(2020, min(2099, (int)($_GET['py'] ?? date('Y'))));
$payMonth = max(1, min(12, (int)($_GET['pm'] ?? date('m'))));
$payrollFromDB = false;
$tabs = [
    'contract' => '근로자 계약',
    'roster'   => '근로자명부',
    'payroll'  => '임금대장',
    'annual'   => '연차관리',
    'rules'    => '취업규칙',
];

require_once __DIR__ . '/../config/database.php';
$employees = [];
$annualData = [];
$payrollData = [];
$contractData = [];
$leaveTypeItems = [];

try {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $ltStmt = $pdo->prepare("
                SELECT ci.code, ci.name
                FROM common_code_items ci
                JOIN common_code_groups cg ON ci.group_id = cg.id
                WHERE cg.module = 'attendance' AND cg.name = '휴가유형' AND ci.is_active = 1
                ORDER BY ci.sort_order
            ");
            $ltStmt->execute();
            $leaveTypeItems = $ltStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[labor.php] 휴가유형 조회 실패: ' . $e->getMessage()); }
    }
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT e.id, e.employee_no, e.name, e.position, e.employment_type, e.employment_status,
                   e.hire_date, e.birth_date, e.gender,
                   CONCAT_WS(' ', NULLIF(e.address1,''), NULLIF(e.address2,'')) AS address,
                   d.name AS department_name,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name
                        ELSE COALESCE(d.name, '')
                   END AS division_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            ORDER BY e.employment_status = '재직' DESC, e.employment_status = '휴직' DESC, e.id ASC
        ");
        $rows = $stmt->fetchAll();
        $basePay = ['대표이사'=>5000000,'이사'=>4500000,'부장'=>4000000,'차장'=>3500000,
                     '과장'=>3000000,'대리'=>2500000,'주임'=>2200000,'사원'=>2000000,'인턴'=>1800000];
        $curYear = (int)date('Y');
        $curMonth = (int)date('m');

        // 연차 DB 로드 시도
        $annualFromDB = false;
        try {
            $pdo->query('SELECT 1 FROM annual_leave LIMIT 1');
            // 해당 연도 데이터 없으면 자동 초기화
            $chk = $pdo->prepare("SELECT COUNT(*) FROM annual_leave WHERE year = ?");
            $chk->execute([$annualYear]);
            if ((int)$chk->fetchColumn() === 0) {
                foreach ($rows as $r) {
                    if (($r['employment_status'] ?: '재직') === '퇴사') continue;
                    $hd = $r['hire_date'];
                    $tenure = $hd ? ($annualYear - (int)substr($hd, 0, 4)) : 0;
                    $td = $tenure < 1 ? 11.0 : min(15 + intdiv($tenure - 1, 2), 25) * 1.0;
                    $pdo->prepare("INSERT IGNORE INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)")
                        ->execute([$r['id'], $annualYear, $td]);
                }
            }
            // 시범 데이터: used_days가 전원 0이면 월 진행률 기반 현실적 값 시드
            $zcStmt = $pdo->prepare("SELECT COUNT(*) FROM annual_leave WHERE year = ? AND used_days > 0");
            $zcStmt->execute([$annualYear]);
            if ((int)$zcStmt->fetchColumn() === 0) {
                $mFrac = (int)date('m') / 12.0;
                $seedRows = $pdo->prepare("SELECT employee_id, total_days FROM annual_leave WHERE year = ?");
                $seedRows->execute([$annualYear]);
                $seedUpd = $pdo->prepare("UPDATE annual_leave SET used_days = ? WHERE employee_id = ? AND year = ?");
                foreach ($seedRows->fetchAll() as $sr) {
                    $s = abs(crc32((string)$sr['employee_id'] . $annualYear));
                    $total = (float)$sr['total_days'];
                    $rate = max(0.05, min(0.85, $mFrac * (0.5 + ($s % 70) / 100.0)));
                    $used = round($total * $rate * 2) / 2;
                    $used = max(0.0, min($used, $total - 0.5));
                    $seedUpd->execute([$used, $sr['employee_id'], $annualYear]);
                }
            }
            // 시범 데이터: leave_requests가 비어있으면 사용 내역 시드
            try {
                $lrChk = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE YEAR(start_date) = ?");
                $lrChk->execute([$annualYear]);
                if ((int)$lrChk->fetchColumn() === 0) {
                    $leaveTypes = ['AL','AL','AL','HAM','HAP','SL','FL'];
                    $reasons = ['개인 사유','가족 행사','병원 방문','경조사 참석','개인 일정','컨디션 불량',''];
                    $lrIns = $pdo->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days_used, reason, status, approved_at, approver_id, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    foreach ($rows as $r) {
                        if (($r['employment_status'] ?: '재직') === '퇴사') continue;
                        $es = abs(crc32($r['name'] . 'leave' . $annualYear));
                        $numLeaves = 2 + ($es % 4);
                        for ($li = 0; $li < $numLeaves; $li++) {
                            $ls = abs(crc32($r['name'] . $annualYear . $li));
                            $mon = 1 + ($ls % min((int)date('m'), 6));
                            $day = 1 + ($ls % 25);
                            $sd = sprintf('%04d-%02d-%02d', $annualYear, $mon, $day);
                            $lt = $leaveTypes[$ls % count($leaveTypes)];
                            if ($lt === 'HAM' || $lt === 'HAP') {
                                $ed = $sd; $du = 0.5;
                            } elseif ($lt === 'AL' && $ls % 4 === 0) {
                                $ed = date('Y-m-d', strtotime($sd . ' +1 day')); $du = 2.0;
                            } else {
                                $ed = $sd; $du = 1.0;
                            }
                            $reason = $reasons[$ls % count($reasons)];
                            $status = ($ls % 12 === 0) ? '대기' : '승인';
                            $approvedAt = $status === '승인' ? date('Y-m-d H:i:s', strtotime($sd . ' +1 day 09:30:00')) : null;
                            $approverId = $status === '승인' ? 1 : null;
                            $createdAt = date('Y-m-d H:i:s', strtotime($sd . ' -2 days 14:00:00'));
                            $lrIns->execute([(int)$r['id'], $lt, $sd, $ed, $du, $reason, $status, $approvedAt, $approverId, $createdAt]);
                        }
                    }
                }
            } catch (PDOException $e) { error_log('[Labor] leave_requests seed: ' . $e->getMessage()); }

            // 연차 데이터 조회
            $alSt = $pdo->prepare("
                SELECT e.id, COALESCE(al.total_days, 15.0) AS total_days, COALESCE(al.used_days, 0.0) AS used_days
                FROM employees e
                LEFT JOIN annual_leave al ON e.id = al.employee_id AND al.year = ?
                WHERE e.employment_status != '퇴사' AND e.is_active = 1
            ");
            $alSt->execute([$annualYear]);
            $alMap = [];
            foreach ($alSt->fetchAll(PDO::FETCH_ASSOC) as $alr) {
                $alMap[(int)$alr['id']] = ['total' => floatval($alr['total_days']), 'used' => floatval($alr['used_days'])];
            }
            $annualFromDB = true;
        } catch (PDOException $e) {
            $annualFromDB = false;
        }

        foreach ($rows as $row) {
            $seed = abs(crc32($row['name']));
            $empSeed = abs(crc32($row['name'] . $curYear . $curMonth));
            $pos = $row['position'] ?: '사원';

            $employees[] = [
                'no' => (int)$row['id'], 'empNo' => $row['employee_no'] ?: '',
                'name' => $row['name'],
                'org' => $row['division_name'] ?: '', 'dept' => $row['department_name'] ?: '',
                'rank' => $pos, 'type' => $row['employment_type'] ?: '정규직',
                'status' => $row['employment_status'] ?: '재직', 'date' => $row['hire_date'] ?: '',
                'birth' => $row['birth_date'] ?: '', 'gender' => $row['gender'] ?: '',
                'address' => $row['address'] ?: '',
            ];

            $contractData[] = [
                'no' => (int)$row['id'], 'name' => $row['name'],
                'org' => $row['division_name'] ?: '', 'dept' => $row['department_name'] ?: '',
                'rank' => $pos, 'type' => $row['employment_type'] ?: '정규직',
                'date' => $row['hire_date'] ?: '',
            ];

            // 연차/급여는 퇴사자 제외
            $empStatus = $row['employment_status'] ?: '재직';
            if ($empStatus === '퇴사') continue;

            // 연차 · DB 우선
            if ($annualFromDB && isset($alMap[(int)$row['id']])) {
                $al = $alMap[(int)$row['id']];
                $daily = round(($basePay[$pos] ?? 2000000) / 209);
                $remaining = $al['total'] - $al['used'];
                $annualData[] = [
                    'no' => (int)$row['id'], 'name' => $row['name'],
                    'org' => $row['division_name'] ?: '', 'dept' => $row['department_name'] ?: '',
                    'rank' => $row['position'] ?? '',
                    'total' => $al['total'], 'used' => $al['used'], 'remaining' => $remaining,
                    'daily' => $daily, 'compensation' => round($remaining * $daily),
                ];
            } elseif (!$annualFromDB) {
                $total = 15 + ($seed % 7);
                $used = $seed % ($total + 1);
                $daily = round(($seed % 40000) + 10000);
                $annualData[] = [
                    'no' => (int)$row['id'], 'name' => $row['name'],
                    'org' => $row['division_name'] ?: '', 'dept' => $row['department_name'] ?: '',
                    'rank' => $row['position'] ?? '',
                    'total' => $total, 'used' => $used, 'remaining' => $total - $used,
                    'daily' => $daily, 'compensation' => ($total - $used) * $daily,
                ];
            }

            // 급여는 payslips 테이블에서 별도 로딩 (아래 블록)
        }
    }
} catch (PDOException $e) { error_log('[Labor] employees load error: ' . $e->getMessage()); }

// 시범 데이터: labor_contracts가 비어있으면 시드
try {
    if ($pdo) {
        $pdo->query("SELECT 1 FROM labor_contracts LIMIT 1");
        $lcCount = (int)$pdo->query("SELECT COUNT(*) FROM labor_contracts")->fetchColumn();
        if ($lcCount === 0 && count($rows) > 0) {
            $lcIns = $pdo->prepare("INSERT INTO labor_contracts (employee_id, contract_status, version, company_name, company_ceo, contract_type, contract_start, contract_end, job_description, workplace, base_pay, meal_allowance, car_allowance, monthly_total, annual_total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($rows as $r) {
                if (($r['employment_status'] ?: '재직') === '퇴사') continue;
                $eid = (int)$r['id'];
                $s = abs(crc32($r['name'] . 'contract'));
                $status = ($s % 5 === 0) ? 'draft' : 'signed';
                $ctype = ($r['employment_type'] === '계약직') ? 'fixed' : 'permanent';
                $hd = $r['hire_date'] ?: '2024-01-01';
                $cEnd = ($ctype === 'fixed') ? date('Y-m-d', strtotime($hd . ' +1 year')) : null;
                $pos = $r['position'] ?: '사원';
                $baseMap = ['대표이사'=>6000000,'부장'=>4500000,'차장'=>4000000,'과장'=>3500000,'대리'=>3200000,'사원'=>2800000];
                $bp = $baseMap[$pos] ?? 3000000;
                $bp += ($s % 5) * 100000;
                $meal = 200000;
                $car = ($pos === '부장' || $pos === '대표이사') ? 200000 : 0;
                $mt = $bp + $meal + $car;
                $at = $mt * 12;
                $lcIns->execute([$eid, $status, 1, '(주)재밋', '김대표', $ctype, $hd, $cEnd,
                    $r['department_name'] . ' 업무', '서울특별시 강남구 테헤란로 123', $bp, $meal, $car, $mt, $at]);
            }
        }
    }
} catch (PDOException $e) { error_log('[Labor] contract seed: ' . $e->getMessage()); }

// 계약 상태를 DB에서 조회하여 contractData에 매핑
$contractMap = []; // employee_id => ['contract_id'=>..., 'status'=>...]
try {
    if ($pdo) {
        $cStmt = $pdo->query("
            SELECT lc.id AS contract_id, lc.employee_id, lc.contract_status, lc.contract_end
            FROM labor_contracts lc
            INNER JOIN (
                SELECT employee_id, MAX(version) AS max_ver
                FROM labor_contracts GROUP BY employee_id
            ) latest ON lc.employee_id = latest.employee_id AND lc.version = latest.max_ver
        ");
        foreach ($cStmt->fetchAll() as $cr) {
            $status = $cr['contract_status'];
            // signed → 체결완료, 만료 30일 이내 → 만료예정
            if ($status === 'signed') {
                if ($cr['contract_end'] && (strtotime($cr['contract_end']) - time()) / 86400 <= 30
                    && strtotime($cr['contract_end']) >= time()) {
                    $displayStatus = '만료예정';
                } else {
                    $displayStatus = '체결완료';
                }
            } elseif ($status === 'draft') {
                $displayStatus = '미체결';
            } else {
                $displayStatus = '미체결';
            }
            $contractMap[(int)$cr['employee_id']] = [
                'contract_id' => (int)$cr['contract_id'],
                'status' => $displayStatus,
                'contract_end' => $cr['contract_end'] ?: null,
            ];
        }
    }
} catch (PDOException $e) { error_log('[Labor] contractMap load error: ' . $e->getMessage()); }

// contractData에 상태 매핑
foreach ($contractData as &$cd) {
    $cm = $contractMap[$cd['no']] ?? null;
    $cd['contract_status'] = $cm ? $cm['status'] : '미체결';
    $cd['contract_id'] = $cm ? $cm['contract_id'] : null;
    $cd['contract_end'] = $cm ? $cm['contract_end'] : null;
}
unset($cd);

// ── 임금대장: payslips + payslip_items 기반 조회 / 자동생성 ──
$payrollStatus = 'empty';
$payTypes = [];
if ($pdo) {
    require_once __DIR__ . '/../includes/payroll_calc.php';
    try {
        $payTypes = getPayTypes($pdo);
        $payTypesById = array_column($payTypes, null, 'id');
        $payTypesByCode = array_column($payTypes, null, 'code');

        $pdo->query('SELECT 1 FROM payslips LIMIT 1');
        $psStmt = $pdo->prepare("
            SELECT p.id, p.employee_id, p.employee_name,
                   p.gross_pay, p.total_deduction, p.net_pay,
                   p.status, p.memo,
                   e.position, e.hire_date, e.birth_date,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name,'') END AS division_name,
                   COALESCE(lc.contract_status, '') AS contract_status
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            LEFT JOIN (
                SELECT lc2.employee_id, lc2.contract_status
                FROM labor_contracts lc2
                INNER JOIN (SELECT employee_id, MAX(version) AS mv FROM labor_contracts GROUP BY employee_id) lat
                  ON lc2.employee_id = lat.employee_id AND lc2.version = lat.mv
            ) lc ON lc.employee_id = p.employee_id
            WHERE p.year = ? AND p.month = ?
            ORDER BY e.id
        ");
        $psStmt->execute([$payYear, $payMonth]);
        $existing = $psStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($existing) > 0) {
            $statusSet = array_unique(array_column($existing, 'status'));
            if (in_array('paid', $statusSet)) $payrollStatus = 'paid';
            elseif (in_array('confirmed', $statusSet)) $payrollStatus = 'confirmed';
            else $payrollStatus = 'draft';

            $siStmt = $pdo->prepare("
                SELECT si.payslip_id, si.pay_type_id, si.amount, si.hours, pt.code
                FROM payslip_items si
                JOIN payroll_pay_types pt ON si.pay_type_id = pt.id
                WHERE si.payslip_id IN (SELECT id FROM payslips WHERE year = ? AND month = ?)
            ");
            $siStmt->execute([$payYear, $payMonth]);
            $allItems = $siStmt->fetchAll(PDO::FETCH_ASSOC);
            $itemsByPayslip = [];
            foreach ($allItems as $si) {
                $itemsByPayslip[(int)$si['payslip_id']][$si['code']] = $si;
            }

            foreach ($existing as $ps) {
                $psId = (int)$ps['id'];
                $items = $itemsByPayslip[$psId] ?? [];
                $row = [
                    'id' => $psId,
                    'no' => (int)$ps['employee_id'], 'name' => $ps['employee_name'],
                    'org' => $ps['division_name'] ?? '', 'rank' => $ps['position'] ?? '',
                    'hireDate' => $ps['hire_date'] ?? '', 'birthDate' => $ps['birth_date'] ?? '',
                    'gross' => (int)$ps['gross_pay'], 'deduction' => (int)$ps['total_deduction'],
                    'net' => (int)$ps['net_pay'], 'status' => $ps['status'], 'memo' => $ps['memo'],
                    'items' => [],
                    'contractStatus' => $ps['contract_status'] ?? '',
                ];
                foreach ($payTypes as $pt) {
                    $si = $items[$pt['code']] ?? null;
                    $row['items'][$pt['code']] = [
                        'amount' => $si ? (int)$si['amount'] : 0,
                        'hours' => $si ? $si['hours'] : null,
                    ];
                }
                $payrollData[] = $row;
            }
            $payrollFromDB = true;
            $payrollRates = getPayrollRates($pdo, $payYear);
        } else {
            $payrollRates = getPayrollRates($pdo, $payYear);
            $lcStmt = $pdo->query("
                SELECT lc.employee_id, lc.base_pay, lc.meal_allowance, lc.car_allowance,
                       lc.child_allowance, lc.contract_status, e.name, e.position, e.hire_date, e.birth_date,
                       CASE WHEN pd.parent_id IS NOT NULL THEN pd.name ELSE COALESCE(d.name,'') END AS division_name
                FROM labor_contracts lc
                INNER JOIN (
                    SELECT employee_id, MAX(version) AS max_ver
                    FROM labor_contracts GROUP BY employee_id
                ) latest ON lc.employee_id = latest.employee_id AND lc.version = latest.max_ver
                LEFT JOIN employees e ON lc.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN departments pd ON d.parent_id = pd.id
                WHERE (e.employment_status IS NULL OR e.employment_status != '퇴사')
                ORDER BY e.id
            ");
            $contracts = $lcStmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($contracts) > 0) {
                $insStmt = $pdo->prepare("
                    INSERT INTO payslips (employee_id, employee_name, year, month,
                        gross_pay, total_deduction, net_pay, status)
                    VALUES (?,?,?,?, ?,?,?,'draft')
                ");
                $siIns = $pdo->prepare("INSERT INTO payslip_items (payslip_id, pay_type_id, amount, hours) VALUES (?,?,?,?)");
                $deductTypes = array_filter($payTypes, fn($t) => $t['category'] === 'deduct');
                $codeToId = array_column($payTypes, 'id', 'code');

                $pdo->beginTransaction();
                foreach ($contracts as $c) {
                    $base  = (int)($c['base_pay'] ?: 0);
                    $meal  = (int)($c['meal_allowance'] ?: 0);
                    $car   = (int)($c['car_allowance'] ?: 0);
                    $child = (int)($c['child_allowance'] ?: 0);
                    $gross = $base + $meal + $car + $child;
                    $dedResult = calcDeductionsFromTypes($deductTypes, $base, $gross);
                    $net = $gross - $dedResult['total'];

                    $insStmt->execute([
                        (int)$c['employee_id'], $c['name'], $payYear, $payMonth,
                        $gross, $dedResult['total'], $net
                    ]);
                    $psId = (int)$pdo->lastInsertId();

                    $payItems = [
                        'BASE' => $base, 'MEAL' => $meal, 'CAR' => $car,
                        'CHILD' => $child, 'OT' => 0,
                    ];
                    foreach ($payItems as $code => $amount) {
                        if (isset($codeToId[$code])) {
                            $siIns->execute([$psId, $codeToId[$code], $amount, null]);
                        }
                    }
                    foreach ($dedResult['items'] as $di) {
                        $siIns->execute([$psId, $di['pay_type_id'], $di['amount'], null]);
                    }

                    $itemsMap = [];
                    foreach ($payTypes as $pt) {
                        $amt = $payItems[$pt['code']] ?? 0;
                        foreach ($dedResult['items'] as $di) {
                            if ($di['code'] === $pt['code']) $amt = $di['amount'];
                        }
                        $itemsMap[$pt['code']] = ['amount' => $amt, 'hours' => null];
                    }

                    $payrollData[] = [
                        'id' => $psId,
                        'no' => (int)$c['employee_id'], 'name' => $c['name'],
                        'org' => $c['division_name'] ?? '', 'rank' => $c['position'] ?? '',
                        'hireDate' => $c['hire_date'] ?? '', 'birthDate' => $c['birth_date'] ?? '',
                        'gross' => $gross, 'deduction' => $dedResult['total'],
                        'net' => $net, 'status' => 'draft', 'memo' => '',
                        'items' => $itemsMap,
                        'contractStatus' => $c['contract_status'] ?? '',
                    ];
                }
                $pdo->commit();
                $payrollFromDB = true;
                $payrollStatus = 'draft';
            }
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[Labor] payroll load error: ' . $e->getMessage());
    }
}
$payPayTypes = array_filter($payTypes, fn($t) => $t['category'] === 'pay');
$deductPayTypes = array_filter($payTypes, fn($t) => $t['category'] === 'deduct');

// 통계
$totalEmp = count($employees);
$activeCount = count(array_filter($employees, fn($e) => $e['status'] === '재직'));
$leaveCount = count(array_filter($employees, fn($e) => $e['status'] === '휴직'));
$resignedCount = count(array_filter($employees, fn($e) => $e['status'] === '퇴사'));
$contractDone = count(array_filter($contractData, fn($c) => ($c['contract_status'] ?? '') === '체결완료'));
$contractExpiring = count(array_filter($contractData, fn($c) => ($c['contract_status'] ?? '') === '만료예정'));
$contractNone = count(array_filter($contractData, fn($c) => ($c['contract_status'] ?? '') === '미체결'));
$totalAnnual = array_sum(array_column($annualData, 'total'));
$usedAnnual = array_sum(array_column($annualData, 'used'));
$remainAnnual = $totalAnnual - $usedAnnual;
$annualRate = $totalAnnual > 0 ? round($usedAnnual / $totalAnnual * 100) : 0;
$totalGross = array_sum(array_column($payrollData, 'gross'));
$totalDeduction = array_sum(array_column($payrollData, 'deduction'));
$totalNet = $totalGross - $totalDeduction;

// 퇴사자 목록 (연차 정산용)
$resignedList = [];
if ($pdo) {
    try {
        $rSt = $pdo->prepare("SELECT id, name, resign_date FROM employees WHERE employment_status = '퇴사' AND resign_date IS NOT NULL AND resign_date != '' ORDER BY resign_date DESC LIMIT 50");
        $rSt->execute();
        $resignedList = $rSt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[Labor] resigned employee load error: ' . $e->getMessage());
    }
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">
        <div class="flex items-center gap-2 mb-5">
            <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h2 class="text-lg font-bold text-slate-100">노무관리</h2>
            <?php if (!$payrollFromDB): ?>
            <span class="ml-2 px-2 py-0.5 text-sm bg-amber-100 text-amber-700 rounded">시범 데이터</span>
            <?php endif; ?>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-5">
            <?php foreach ($tabs as $key => $label): ?>
            <button type="button" data-labor-tab-btn="<?= $key ?>" onclick="switchLaborTab('<?= $key ?>')"
               class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab === $key ? 'approval-tab active' : 'approval-tab' ?>">
                <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>

        <style>
            .rf-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--zm-text-muted);background:var(--zm-surface-2);border:1px solid var(--zm-border);cursor:pointer;user-select:none;transition:all .15s;white-space:nowrap}
            .rf-pill:hover{border-color:var(--zm-text-subtle);color:var(--zm-text-default)}
            .rf-pill--active{border-color:var(--zm-text-strong)!important;color:var(--zm-text-strong)!important;font-weight:600!important}
            .rf-drop{position:absolute;min-width:200px;max-height:320px;overflow-y:auto;background:var(--zm-surface-1);border:1px solid var(--zm-border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:200;padding:4px}
            .rf-drop__item{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:13px;color:var(--zm-text-default);cursor:pointer;transition:background .1s}
            .rf-drop__item:hover{background:var(--zm-surface-3)}
            .rf-drop__item--active{color:var(--zm-text-strong);background:var(--zm-surface-2);font-weight:600}
            .rf-drop__item--active::after{content:'✓';margin-left:auto;font-size:12px}
            .rf-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 6px 4px 10px;border-radius:6px;font-size:12px;font-weight:500;background:var(--zm-surface-2);color:var(--zm-text-strong);white-space:nowrap}
            .rf-chip__x{width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;cursor:pointer;transition:background .15s}
            .rf-chip__x:hover{background:rgba(0,0,0,0.08)}
            .rf-chip__x svg{width:10px;height:10px}
        </style>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">


        <div data-labor-tab="contract" <?= $tab !== 'contract' ? 'hidden' : '' ?>>
        <?php include __DIR__ . '/labor/_tab_contract.php'; ?>
        </div>

        <div data-labor-tab="roster" <?= $tab !== 'roster' ? 'hidden' : '' ?>>
        <?php include __DIR__ . '/labor/_tab_roster.php'; ?>
        </div>

        <?php
            $statusLabels = ['empty'=>'데이터 없음','draft'=>'작성중','confirmed'=>'확정','paid'=>'지급완료'];
            $statusBadge  = ['empty'=>'badge-gray','draft'=>'badge-info','confirmed'=>'badge-warning','paid'=>'badge-success'];
            $isDraft = $payrollStatus === 'draft';
            $isConfirmed = $payrollStatus === 'confirmed';
            $isPaid = $payrollStatus === 'paid';
        ?>
        <div data-labor-tab="payroll" <?= $tab !== 'payroll' ? 'hidden' : '' ?>>
        <?php include __DIR__ . '/labor/_tab_payroll.php'; ?>
        </div>

        <div data-labor-tab="annual" <?= $tab !== 'annual' ? 'hidden' : '' ?>>
        <?php include __DIR__ . '/labor/_tab_annual.php'; ?>
        </div>

        <div data-labor-tab="rules" <?= $tab !== 'rules' ? 'hidden' : '' ?>>
        <?php include __DIR__ . '/labor/_tab_rules.php'; ?>
        </div>


        <!-- 자체 리치 에디터 (rules 탭 전용 · 중복 로드 방지용 id 부여) -->
        <link id="rulesEditorCss" rel="stylesheet" href="<?= $basePath ?>/assets/editor/editor.css">
        <script id="rulesEditorJs" src="<?= $basePath ?>/assets/editor/editor.js"></script>
        <style>
        /* 취업규칙 문서 타이포그래피 · 계약서 양식과 동일 계열 */
        #rulesDocument { color: var(--zm-text-default); font-size: 15px; line-height: 1.85; }
        #rulesDocument h2 {
            font-size: 18px; font-weight: 700; color: var(--zm-text-strong);
            padding-bottom: 10px; border-bottom: 1px solid var(--zm-border);
            margin: 2.25rem 0 1rem; scroll-margin-top: 7.5rem;
        }
        #rulesDocument h2:first-child { margin-top: 0; }
        #rulesDocument h3 {
            font-size: 15px; font-weight: 600; color: var(--zm-text-strong);
            margin: 1.25rem 0 0.4rem;
        }
        #rulesDocument p { margin: 0.3rem 0 0.6rem; white-space: pre-line; }
        #rulesDocument ul, #rulesDocument ol { padding-left: 1.4em; margin: 0.4rem 0 0.8rem; }
        #rulesDocument li { margin: 0.15rem 0; }
        #rulesDocument strong { color: var(--zm-text-strong); }
        #rulesDocument a { color: var(--zm-primary-fg); text-decoration: underline; }
        #rulesDocument table {
            border-collapse: collapse; width: 100%; margin: 1rem 0; font-size: 14px;
        }
        #rulesDocument th, #rulesDocument td {
            border: 1px solid var(--zm-border); padding: 0.5rem 0.75rem; vertical-align: top;
        }
        #rulesDocument th { background: var(--zm-surface-2); color: var(--zm-text-strong); font-weight: 600; text-align: left; }
        #rulesDocument td { color: var(--zm-text-default); }

        /* 목차 활성 하이라이트 */
        #rulesToc .rule-toc-link.is-active { background: var(--zm-primary-tint-12); color: var(--zm-primary-fg); font-weight: 600; }

        @media print {
            @page { size: A4; margin: 0; }
            @page :first { margin: 0; }

            /* UI 전부 숨김 */
            header, #sidebar, .sticky, aside, nav,
            .zm-tab-container, .flex.items-center.gap-2.mb-5,
            #rulesEditView, #rulesEditBtn, #rulesSaveBtn, #rulesCancelBtn,
            [onclick*="print"], #rulesToc, #rulesTocAside,
            .fixed { display: none !important; }

            /* 레이아웃 초기화 */
            html, body { margin: 0 !important; padding: 0 !important; background: white !important; }
            #mainContent { margin: 0 !important; padding: 0 !important; }
            main { padding: 0 !important; }
            main > .bg-slate-900 { border: none !important; padding: 0 !important; background: white !important; box-shadow: none !important; }
            .grid { display: block !important; }
            #rulesContentSection { width: 100% !important; }

            /* 표지 표시 */
            #rulesPrintCover { display: block !important; page-break-after: always; }

            /* 본문 여백 + 서체 */
            #rulesReadView {
                background: white !important; color: black !important;
                border: none !important; box-shadow: none !important; border-radius: 0 !important;
                padding: 20mm 20mm !important;
            }
            #rulesReadView * { color: black !important; background: transparent !important; }

            /* 문서 타이포그래피 */
            #rulesDocument { font-size: 13px !important; line-height: 1.9 !important; }
            #rulesDocument h2 {
                font-size: 17px !important; font-weight: 800 !important;
                border-bottom: 2px solid #222 !important;
                padding-bottom: 8px !important; margin-top: 2rem !important;
                page-break-after: avoid;
            }
            #rulesDocument h3 {
                font-size: 14px !important; font-weight: 700 !important;
                margin-top: 1rem !important;
                page-break-after: avoid;
            }
            #rulesDocument p { orphans: 3; widows: 3; }
            #rulesDocument table { page-break-inside: avoid; }
            #rulesDocument th, #rulesDocument td { border: 1px solid #555 !important; font-size: 12px !important; }
            #rulesDocument th { background: #f0f0f0 !important; color: black !important; }
        }
        </style>

        </div>
    </main>
</div>

<script>
// PHP→JS 데이터 전달 (외부 JS 파일에서 LABOR_DATA.xxx 로 참조)
var LABOR_DATA = {
    basePath: '<?= $basePath ?>',
    payYear: <?= $payYear ?>,
    payMonth: <?= $payMonth ?>,
    payrollStatus: '<?= $payrollStatus ?>',
    payTypes: <?= json_encode(array_values($payTypes), JSON_UNESCAPED_UNICODE) ?>,
    pyOpts: <?= json_encode(isset($pyOrgs) ? $pyOrgs : [], JSON_UNESCAPED_UNICODE) ?>,
    pyOrgLabel: <?= json_encode($showDivision ? getOrgLabel('division') : '본부', JSON_UNESCAPED_UNICODE) ?>,
    rosterData: <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>,
    rosterMeta: {
        total: <?= $totalEmp ?>,
        active: <?= $activeCount ?>,
        leave: <?= $leaveCount ?>,
        resigned: <?= $resignedCount ?>,
        showDiv: <?= $showDivision ? 'true' : 'false' ?>,
        showDept: <?= $showDepartment ? 'true' : 'false' ?>,
        divLabel: <?= json_encode(getOrgLabel('division'), JSON_UNESCAPED_UNICODE) ?>,
        deptLabel: <?= json_encode(getOrgLabel('department'), JSON_UNESCAPED_UNICODE) ?>
    },
    rfLabels: {
        org: <?= json_encode($showDivision ? getOrgLabel('division') : '본부', JSON_UNESCAPED_UNICODE) ?>,
        dept: <?= json_encode($showDepartment ? getOrgLabel('department') : '부서', JSON_UNESCAPED_UNICODE) ?>,
        rank: '직급', type: '고용형태', gender: '성별'
    },
    rfOpts: {
        org: <?= json_encode(array_values(array_unique(array_filter(array_column($employees, 'org')))), JSON_UNESCAPED_UNICODE) ?>.sort(),
        dept: <?= json_encode(array_values(array_unique(array_filter(array_column($employees, 'dept')))), JSON_UNESCAPED_UNICODE) ?>.sort(),
        rank: <?= json_encode(array_values(array_unique(array_filter(array_column($employees, 'rank')))), JSON_UNESCAPED_UNICODE) ?>,
        type: <?= json_encode(array_values(array_unique(array_filter(array_column($employees, 'type')))), JSON_UNESCAPED_UNICODE) ?>.sort(),
        gender: ['남','여']
    },
    cfLabels: {
        org: <?= json_encode($showDivision ? getOrgLabel('division') : '본부', JSON_UNESCAPED_UNICODE) ?>,
        dept: <?= json_encode($showDepartment ? getOrgLabel('department') : '부서', JSON_UNESCAPED_UNICODE) ?>,
        rank: '직급', type: '고용형태', cstatus: '계약상태'
    },
    cfOpts: {
        org: <?= json_encode(array_values(array_unique(array_filter(array_column($contractData, 'org')))), JSON_UNESCAPED_UNICODE) ?>.sort(),
        dept: <?= json_encode(array_values(array_unique(array_filter(array_column($contractData, 'dept')))), JSON_UNESCAPED_UNICODE) ?>.sort(),
        rank: <?= json_encode(array_values(array_unique(array_filter(array_column($contractData, 'rank')))), JSON_UNESCAPED_UNICODE) ?>,
        type: <?= json_encode(array_values(array_unique(array_filter(array_column($contractData, 'type')))), JSON_UNESCAPED_UNICODE) ?>.sort(),
        cstatus: <?= json_encode(array_values(array_unique(array_filter(array_column($contractData, 'contract_status')))), JSON_UNESCAPED_UNICODE) ?>
    },
    anLabels: {
        org: <?= json_encode($showDivision ? getOrgLabel('division') : '본부', JSON_UNESCAPED_UNICODE) ?>,
        dept: <?= json_encode($showDepartment ? getOrgLabel('department') : '부서', JSON_UNESCAPED_UNICODE) ?>,
        remain: '잔여일수'
    },
    anOpts: {
        org: <?= json_encode(isset($anOrgs) ? $anOrgs : [], JSON_UNESCAPED_UNICODE) ?>,
        dept: <?= json_encode(isset($anDepts) ? $anDepts : [], JSON_UNESCAPED_UNICODE) ?>,
        remain: ['3일 이하','5일 이하','10일 이하','10일 초과']
    },
    leaveApi: '<?= $basePath ?>/api/annual_leave.php',
    leaveYear: <?= $annualYear ?>,
    leaveTypes: <?= json_encode(array_column($leaveTypes ?? $defaultLeaveTypes ?? [], 'name', 'code'), JSON_UNESCAPED_UNICODE) ?>,
    deductTypes: <?= json_encode($deductCodes ?? ['AL','HAM','HAP']) ?>,
    leaveTypeMeta: <?= json_encode($leaveTypeMeta ?? [], JSON_UNESCAPED_UNICODE) ?>,
    leaveEmployees: <?= json_encode($leaveEmployees ?? [], JSON_UNESCAPED_UNICODE) ?>,
    resignedEmployees: <?= json_encode($resignedList, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= $basePath ?>/assets/js/labor.js"></script>
<script src="<?= $basePath ?>/assets/js/labor-payroll.js"></script>
<script src="<?= $basePath ?>/assets/js/labor-rules.js"></script>
<script src="<?= $basePath ?>/assets/js/labor-contract.js"></script>
<script src="<?= $basePath ?>/assets/js/labor-annual.js"></script>
<script>window.LEAVE_API_URL = '<?= $basePath ?>/api/annual_leave.php'; window.LEAVE_YEAR = <?= $annualYear ?>;</script>
<script src="<?= $basePath ?>/assets/js/annual-leave.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
