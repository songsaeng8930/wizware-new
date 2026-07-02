<?php
/**
 * Zaemit 그룹웨어 - 병원 전용 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

$writeActions = ['saveShift','deleteShift','saveLeaveRequest','deleteLeaveRequest',
    'toggleCheck','seedDailyChecks','saveClosing','saveAsset','deleteAsset',
    'savePurchaseRequest','deletePurchaseRequest','saveCredential','deleteCredential'];
$adminActions = ['syncClosing'];

if (in_array($action, $adminActions, true)) {
    apiRequireMenuPermission('hospital', 'edit');
    apiRequireAdminOrManager();
} elseif (in_array($action, $writeActions, true)) {
    apiRequireMenuPermission('hospital', 'edit');
} else {
    apiRequireMenuPermission('hospital', 'view');
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    match ($action) {
        'overview'          => overview($pdo),
        'shifts'            => listShifts($pdo),
        'staffingWarnings'  => staffingWarnings($pdo),
        'saveShift'         => saveShift($pdo),
        'deleteShift'       => deleteShift($pdo),
        'leaveRequests'     => listLeaveRequests($pdo),
        'saveLeaveRequest'  => saveLeaveRequest($pdo),
        'deleteLeaveRequest'=> deleteLeaveRequest($pdo),
        'checks'            => listChecks($pdo),
        'toggleCheck'       => toggleCheck($pdo),
        'seedDailyChecks'   => seedDailyChecks($pdo),
        'closing'           => getClosing($pdo),
        'saveClosing'       => saveClosing($pdo),
        'syncClosing'       => syncClosing($pdo),
        'assets'            => listAssets($pdo),
        'saveAsset'         => saveAsset($pdo),
        'deleteAsset'       => deleteAsset($pdo),
        'purchaseRequests'  => listPurchaseRequests($pdo),
        'savePurchaseRequest' => savePurchaseRequest($pdo),
        'deletePurchaseRequest' => deletePurchaseRequest($pdo),
        'credentials'       => listCredentials($pdo),
        'saveCredential'    => saveCredential($pdo),
        'deleteCredential'  => deleteCredential($pdo),
        default             => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (Throwable $e) {
    error_log('[Hospital API] ' . $e->getMessage());
    respond(500, ['error' => '서버 오류가 발생했습니다.']);
}

function respond(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function input(): array
{
    $raw = json_decode(file_get_contents('php://input'), true);
    return is_array($raw) ? $raw : [];
}

function today(): string
{
    return date('Y-m-d');
}

function overview(PDO $pdo): void
{
    $date = $_GET['date'] ?? today();
    $overview = [
        'date' => $date,
        'shift_count' => 0,
        'open_checks_total' => 0,
        'open_checks_done' => 0,
        'closing_total' => 0,
        'low_stock_count' => 0,
        'asset_due_count' => 0,
        'credential_due_count' => 0,
        'pending_leave_count' => 0,
        'pending_purchase_count' => 0,
        'staffing_warning_count' => 0,
    ];

    $st = $pdo->prepare('SELECT COUNT(*) FROM hospital_shift_slots WHERE slot_date = ?');
    $st->execute([$date]);
    $overview['shift_count'] = (int)$st->fetchColumn();

    $st = $pdo->prepare('SELECT COUNT(*) total, COALESCE(SUM(is_done = 1),0) done FROM hospital_daily_checks WHERE check_date = ?');
    $st->execute([$date]);
    $checks = $st->fetch();
    $overview['open_checks_total'] = (int)($checks['total'] ?? 0);
    $overview['open_checks_done'] = (int)($checks['done'] ?? 0);

    $st = $pdo->prepare('SELECT COALESCE(cash_amount + card_amount + transfer_amount - refund_amount, 0) FROM hospital_cash_closings WHERE closing_date = ?');
    $st->execute([$date]);
    $overview['closing_total'] = (int)$st->fetchColumn();

    $overview['low_stock_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_assets WHERE asset_type = '재고' AND current_qty IS NOT NULL AND min_qty IS NOT NULL AND current_qty <= min_qty")->fetchColumn();
    $overview['asset_due_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_assets WHERE asset_type = '장비' AND next_due_date IS NOT NULL AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    $overview['credential_due_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_staff_credentials WHERE expire_date IS NOT NULL AND expire_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    $overview['pending_leave_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_leave_requests WHERE status = '신청'")->fetchColumn();
    $overview['pending_purchase_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_purchase_requests WHERE status IN ('요청','승인')")->fetchColumn();
    $overview['staffing_warning_count'] = count(buildStaffingWarnings($pdo, $date, $date));

    respond(200, ['overview' => $overview]);
}

function listShifts(PDO $pdo): void
{
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-t');
    $st = $pdo->prepare('SELECT * FROM hospital_shift_slots WHERE slot_date BETWEEN ? AND ? ORDER BY slot_date, start_time, id');
    $st->execute([$from, $to]);
    respond(200, ['shifts' => $st->fetchAll()]);
}

function staffingWarnings(PDO $pdo): void
{
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-t');
    respond(200, ['warnings' => buildStaffingWarnings($pdo, $from, $to)]);
}

function buildStaffingWarnings(PDO $pdo, string $from, string $to): array
{
    $st = $pdo->prepare("SELECT slot_date,
            SUM(role_name LIKE '%원무%') AS reception_count,
            SUM(role_name LIKE '%간호%' OR role_name LIKE '%진료%') AS clinical_count,
            COUNT(*) AS total_count
        FROM hospital_shift_slots
        WHERE slot_date BETWEEN ? AND ? AND status <> '취소'
        GROUP BY slot_date");
    $st->execute([$from, $to]);
    $byDate = [];
    foreach ($st->fetchAll() as $row) {
        $byDate[$row['slot_date']] = $row;
    }

    $leave = $pdo->prepare("SELECT employee_name, start_date, end_date, substitute_name, status
        FROM hospital_leave_requests
        WHERE status IN ('신청','승인') AND start_date <= ? AND end_date >= ?");
    $leave->execute([$to, $from]);
    $leaveRows = $leave->fetchAll();

    $warnings = [];
    $period = new DatePeriod(new DateTime($from), new DateInterval('P1D'), (new DateTime($to))->modify('+1 day'));
    foreach ($period as $dt) {
        $date = $dt->format('Y-m-d');
        $row = $byDate[$date] ?? ['reception_count' => 0, 'clinical_count' => 0, 'total_count' => 0];
        if ((int)$row['total_count'] === 0) {
            $warnings[] = ['date' => $date, 'level' => 'danger', 'message' => '등록된 근무자가 없습니다.'];
            continue;
        }
        if ((int)$row['reception_count'] < 1) {
            $warnings[] = ['date' => $date, 'level' => 'warning', 'message' => '원무/접수 담당 근무자가 없습니다.'];
        }
        if ((int)$row['clinical_count'] < 1) {
            $warnings[] = ['date' => $date, 'level' => 'warning', 'message' => '간호/진료지원 담당 근무자가 없습니다.'];
        }
        foreach ($leaveRows as $lr) {
            if ($lr['start_date'] <= $date && $lr['end_date'] >= $date) {
                $sub = trim((string)($lr['substitute_name'] ?? ''));
                $warnings[] = [
                    'date' => $date,
                    'level' => $sub ? 'info' : 'danger',
                    'message' => $lr['employee_name'] . ' ' . $lr['status'] . ' 휴가' . ($sub ? ' / 대체: ' . $sub : ' / 대체근무자 미지정'),
                ];
            }
        }
    }
    return $warnings;
}

function saveShift(PDO $pdo): void
{
    $d = input();
    $id = (int)($d['id'] ?? 0);
    $slotDate = trim($d['slot_date'] ?? '');
    $shiftType = trim($d['shift_type'] ?? '');
    $roleName = trim($d['role_name'] ?? '');
    $employeeName = trim($d['employee_name'] ?? '');
    $startTime = trim($d['start_time'] ?? '');
    $endTime = trim($d['end_time'] ?? '');
    if (!$slotDate || !$shiftType || !$roleName || !$employeeName || !$startTime || !$endTime) {
        respond(400, ['error' => '근무일, 근무구분, 역할, 직원, 시간을 입력해주세요.']);
    }
    if ($endTime <= $startTime) {
        respond(400, ['error' => '종료 시간이 시작 시간보다 빨라서는 안 됩니다.']);
    }
    $overlap = $pdo->prepare('SELECT COUNT(*) FROM hospital_shift_slots
        WHERE slot_date = ? AND employee_name = ? AND status <> "취소" AND id <> ?
          AND start_time < ? AND end_time > ?');
    $overlap->execute([$slotDate, $employeeName, $id, $endTime, $startTime]);
    if ((int)$overlap->fetchColumn() > 0) {
        respond(400, ['error' => '같은 직원의 근무 시간이 이미 겹칩니다.']);
    }
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE hospital_shift_slots SET slot_date=?, shift_type=?, role_name=?, employee_name=?, start_time=?, end_time=?, note=?, status=? WHERE id=?');
        $st->execute([$slotDate, $shiftType, $roleName, $employeeName, $startTime, $endTime, $d['note'] ?? '', $d['status'] ?? '확정', $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO hospital_shift_slots (slot_date, shift_type, role_name, employee_name, start_time, end_time, note, status) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute([$slotDate, $shiftType, $roleName, $employeeName, $startTime, $endTime, $d['note'] ?? '', $d['status'] ?? '확정']);
        $id = (int)$pdo->lastInsertId();
    }
    respond(200, ['success' => true, 'id' => $id]);
}

function deleteShift(PDO $pdo): void
{
    $id = (int)(input()['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'ID가 필요합니다.']);
    $pdo->prepare('DELETE FROM hospital_shift_slots WHERE id = ?')->execute([$id]);
    respond(200, ['success' => true]);
}

function listLeaveRequests(PDO $pdo): void
{
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-t');
    $st = $pdo->prepare('SELECT * FROM hospital_leave_requests WHERE start_date <= ? AND end_date >= ? ORDER BY start_date, id');
    $st->execute([$to, $from]);
    respond(200, ['requests' => $st->fetchAll()]);
}

function saveLeaveRequest(PDO $pdo): void
{
    $d = input();
    $id = (int)($d['id'] ?? 0);
    $employee = trim($d['employee_name'] ?? '');
    $start = trim($d['start_date'] ?? '');
    $end = trim($d['end_date'] ?? '');
    if (!$employee || !$start || !$end) respond(400, ['error' => '직원명과 휴가 기간을 입력해주세요.']);
    if ($end < $start) respond(400, ['error' => '종료일이 시작일보다 빠릅니다.']);
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE hospital_leave_requests SET employee_name=?, leave_type=?, start_date=?, end_date=?, substitute_name=?, reason=?, status=? WHERE id=?');
        $st->execute([$employee, $d['leave_type'] ?? '연차', $start, $end, $d['substitute_name'] ?? '', $d['reason'] ?? '', $d['status'] ?? '신청', $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO hospital_leave_requests (employee_name, leave_type, start_date, end_date, substitute_name, reason, status) VALUES (?,?,?,?,?,?,?)');
        $st->execute([$employee, $d['leave_type'] ?? '연차', $start, $end, $d['substitute_name'] ?? '', $d['reason'] ?? '', $d['status'] ?? '신청']);
        $id = (int)$pdo->lastInsertId();
    }
    respond(200, ['success' => true, 'id' => $id]);
}

function deleteLeaveRequest(PDO $pdo): void
{
    $id = (int)(input()['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'ID가 필요합니다.']);
    $pdo->prepare('DELETE FROM hospital_leave_requests WHERE id = ?')->execute([$id]);
    respond(200, ['success' => true]);
}

function listChecks(PDO $pdo): void
{
    $date = $_GET['date'] ?? today();
    $st = $pdo->prepare('SELECT * FROM hospital_daily_checks WHERE check_date = ? ORDER BY shift_type, category, id');
    $st->execute([$date]);
    respond(200, ['checks' => $st->fetchAll()]);
}

function seedDailyChecks(PDO $pdo): void
{
    $date = input()['date'] ?? today();
    $items = [
        ['오픈', '진료실 소독 상태 확인', '감염관리', ''],
        ['오픈', '냉장 보관 의약품 온도 확인', '약품관리', '2~8도 유지'],
        ['오픈', '접수대 카드단말기/프린터 확인', '시설점검', ''],
        ['마감', '카드 단말기 매출 마감', '수납마감', ''],
        ['마감', '현금 시재 확인', '수납마감', ''],
        ['마감', '의료폐기물 보관함 잠금 확인', '시설점검', ''],
    ];
    $st = $pdo->prepare('INSERT IGNORE INTO hospital_daily_checks (check_date, shift_type, item_name, category, note) VALUES (?,?,?,?,?)');
    foreach ($items as $item) {
        $st->execute([$date, $item[0], $item[1], $item[2], $item[3]]);
    }
    listChecks($pdo);
}

function toggleCheck(PDO $pdo): void
{
    $d = input();
    $id = (int)($d['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'ID가 필요합니다.']);
    $done = (int)($d['is_done'] ?? 0);
    $user = getCurrentUser()['name'] ?? '';
    $st = $pdo->prepare('UPDATE hospital_daily_checks SET is_done=?, checked_by=?, checked_at=IF(?=1,NOW(),NULL), note=? WHERE id=?');
    $st->execute([$done, $done ? $user : null, $done, $d['note'] ?? '', $id]);
    respond(200, ['success' => true]);
}

function getClosing(PDO $pdo): void
{
    $date = $_GET['date'] ?? today();
    $st = $pdo->prepare('SELECT * FROM hospital_cash_closings WHERE closing_date = ?');
    $st->execute([$date]);
    respond(200, ['closing' => $st->fetch()]);
}

function saveClosing(PDO $pdo): void
{
    $d = input();
    $date = $d['closing_date'] ?? today();
    $user = getCurrentUser()['name'] ?? '';
    $st = $pdo->prepare("INSERT INTO hospital_cash_closings
        (closing_date, cash_amount, card_amount, transfer_amount, refund_amount, unpaid_amount, patient_count, memo, status, closed_by)
        VALUES (?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE cash_amount=VALUES(cash_amount), card_amount=VALUES(card_amount), transfer_amount=VALUES(transfer_amount),
            refund_amount=VALUES(refund_amount), unpaid_amount=VALUES(unpaid_amount), patient_count=VALUES(patient_count),
            memo=VALUES(memo), status=VALUES(status), closed_by=VALUES(closed_by), updated_at=CURRENT_TIMESTAMP");
    $st->execute([
        $date,
        (int)($d['cash_amount'] ?? 0),
        (int)($d['card_amount'] ?? 0),
        (int)($d['transfer_amount'] ?? 0),
        (int)($d['refund_amount'] ?? 0),
        (int)($d['unpaid_amount'] ?? 0),
        (int)($d['patient_count'] ?? 0),
        $d['memo'] ?? '',
        $d['status'] ?? '작성중',
        $user,
    ]);
    respond(200, ['success' => true]);
}

function syncClosing(PDO $pdo): void
{
    $d = input();
    $date = $d['closing_date'] ?? today();
    $st = $pdo->prepare('SELECT * FROM hospital_cash_closings WHERE closing_date = ?');
    $st->execute([$date]);
    $closing = $st->fetch();
    if (!$closing) respond(404, ['error' => '수납마감 데이터가 없습니다.']);
    if (!empty($closing['bank_transaction_id'])) {
        respond(200, ['success' => true, 'bank_transaction_id' => (int)$closing['bank_transaction_id'], 'message' => '이미 재무에 반영되었습니다.']);
    }

    $accountId = (int)$pdo->query('SELECT id FROM bank_accounts WHERE is_active = 1 ORDER BY id LIMIT 1')->fetchColumn();
    if ($accountId <= 0) respond(400, ['error' => '활성 계좌가 없어 재무 반영을 할 수 없습니다.']);

    $amount = (int)$closing['cash_amount'] + (int)$closing['card_amount'] + (int)$closing['transfer_amount'] - (int)$closing['refund_amount'];
    $desc = '병원 수납마감 ' . $date . ' / 내원 ' . (int)$closing['patient_count'] . '명';
    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare("INSERT INTO bank_transactions (account_id, transaction_date, description, amount, tx_type, account_code, account_name, ai_confidence, is_confirmed, memo)
            VALUES (?, ?, ?, ?, '입금', '401', '진료수입', 100, 1, ?)");
        $ins->execute([$accountId, $date, $desc, $amount, 'hospital_cash_closing:' . $closing['id']]);
        $txId = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE hospital_cash_closings SET bank_transaction_id=?, status='마감완료' WHERE id=?")->execute([$txId, $closing['id']]);
        $pdo->commit();
        respond(200, ['success' => true, 'bank_transaction_id' => $txId]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function listAssets(PDO $pdo): void
{
    $type = $_GET['type'] ?? '';
    if ($type) {
        $st = $pdo->prepare('SELECT * FROM hospital_assets WHERE asset_type = ? ORDER BY status DESC, name');
        $st->execute([$type]);
    } else {
        $st = $pdo->query('SELECT * FROM hospital_assets ORDER BY asset_type, status DESC, name');
    }
    respond(200, ['assets' => $st->fetchAll()]);
}

function saveAsset(PDO $pdo): void
{
    $d = input();
    $id = (int)($d['id'] ?? 0);
    $name = trim($d['name'] ?? '');
    $type = trim($d['asset_type'] ?? '재고');
    if (!$name) respond(400, ['error' => '이름을 입력해주세요.']);
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE hospital_assets SET asset_type=?, name=?, category=?, current_qty=?, min_qty=?, unit=?, expire_date=?, location=?, vendor=?, last_checked_at=?, next_due_date=?, status=?, memo=? WHERE id=?');
        $params = [$type, $name, $d['category'] ?? '', nullableInt($d['current_qty'] ?? null), nullableInt($d['min_qty'] ?? null), $d['unit'] ?? '', emptyToNull($d['expire_date'] ?? ''), $d['location'] ?? '', $d['vendor'] ?? '', emptyToNull($d['last_checked_at'] ?? ''), emptyToNull($d['next_due_date'] ?? ''), $d['status'] ?? '정상', $d['memo'] ?? '', $id];
    } else {
        $st = $pdo->prepare('INSERT INTO hospital_assets (asset_type, name, category, current_qty, min_qty, unit, expire_date, location, vendor, last_checked_at, next_due_date, status, memo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $params = [$type, $name, $d['category'] ?? '', nullableInt($d['current_qty'] ?? null), nullableInt($d['min_qty'] ?? null), $d['unit'] ?? '', emptyToNull($d['expire_date'] ?? ''), $d['location'] ?? '', $d['vendor'] ?? '', emptyToNull($d['last_checked_at'] ?? ''), emptyToNull($d['next_due_date'] ?? ''), $d['status'] ?? '정상', $d['memo'] ?? ''];
    }
    $st->execute($params);
    respond(200, ['success' => true, 'id' => $id ?: (int)$pdo->lastInsertId()]);
}

function deleteAsset(PDO $pdo): void
{
    $id = (int)(input()['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'ID가 필요합니다.']);
    $pdo->prepare('DELETE FROM hospital_assets WHERE id = ?')->execute([$id]);
    respond(200, ['success' => true]);
}

function listPurchaseRequests(PDO $pdo): void
{
    $st = $pdo->query('SELECT pr.*, a.current_qty, a.min_qty FROM hospital_purchase_requests pr LEFT JOIN hospital_assets a ON a.id = pr.asset_id ORDER BY FIELD(pr.status, "요청", "승인", "발주완료", "취소"), pr.created_at DESC');
    respond(200, ['requests' => $st->fetchAll()]);
}

function savePurchaseRequest(PDO $pdo): void
{
    $d = input();
    $id = (int)($d['id'] ?? 0);
    $item = trim($d['item_name'] ?? '');
    if (!$item) respond(400, ['error' => '발주 품목을 입력해주세요.']);
    $user = getCurrentUser()['name'] ?? '';
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE hospital_purchase_requests SET asset_id=?, item_name=?, requested_qty=?, unit=?, requester_name=?, vendor=?, reason=?, status=? WHERE id=?');
        $st->execute([nullableInt($d['asset_id'] ?? null), $item, max(1, (int)($d['requested_qty'] ?? 1)), $d['unit'] ?? '', $d['requester_name'] ?? $user, $d['vendor'] ?? '', $d['reason'] ?? '', $d['status'] ?? '요청', $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO hospital_purchase_requests (asset_id, item_name, requested_qty, unit, requester_name, vendor, reason, status) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute([nullableInt($d['asset_id'] ?? null), $item, max(1, (int)($d['requested_qty'] ?? 1)), $d['unit'] ?? '', $d['requester_name'] ?? $user, $d['vendor'] ?? '', $d['reason'] ?? '', $d['status'] ?? '요청']);
        $id = (int)$pdo->lastInsertId();
    }
    respond(200, ['success' => true, 'id' => $id]);
}

function deletePurchaseRequest(PDO $pdo): void
{
    $id = (int)(input()['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'ID가 필요합니다.']);
    $pdo->prepare('DELETE FROM hospital_purchase_requests WHERE id = ?')->execute([$id]);
    respond(200, ['success' => true]);
}

function listCredentials(PDO $pdo): void
{
    $st = $pdo->query('SELECT * FROM hospital_staff_credentials ORDER BY expire_date IS NULL, expire_date, employee_name');
    respond(200, ['credentials' => $st->fetchAll()]);
}

function saveCredential(PDO $pdo): void
{
    $d = input();
    $id = (int)($d['id'] ?? 0);
    $employee = trim($d['employee_name'] ?? '');
    $name = trim($d['credential_name'] ?? '');
    if (!$employee || !$name) respond(400, ['error' => '직원명과 자격/교육명을 입력해주세요.']);
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE hospital_staff_credentials SET employee_name=?, credential_type=?, credential_name=?, issue_date=?, expire_date=?, status=?, memo=? WHERE id=?');
        $params = [$employee, $d['credential_type'] ?? '법정교육', $name, emptyToNull($d['issue_date'] ?? ''), emptyToNull($d['expire_date'] ?? ''), $d['status'] ?? '유효', $d['memo'] ?? '', $id];
    } else {
        $st = $pdo->prepare('INSERT INTO hospital_staff_credentials (employee_name, credential_type, credential_name, issue_date, expire_date, status, memo) VALUES (?,?,?,?,?,?,?)');
        $params = [$employee, $d['credential_type'] ?? '법정교육', $name, emptyToNull($d['issue_date'] ?? ''), emptyToNull($d['expire_date'] ?? ''), $d['status'] ?? '유효', $d['memo'] ?? ''];
    }
    $st->execute($params);
    respond(200, ['success' => true, 'id' => $id ?: (int)$pdo->lastInsertId()]);
}

function deleteCredential(PDO $pdo): void
{
    $id = (int)(input()['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'ID가 필요합니다.']);
    $pdo->prepare('DELETE FROM hospital_staff_credentials WHERE id = ?')->execute([$id]);
    respond(200, ['success' => true]);
}

function emptyToNull($value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function nullableInt($value): ?int
{
    if ($value === null || $value === '') return null;
    return (int)$value;
}
