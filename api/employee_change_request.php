<?php
/**
 * 직원 정보 변경요청 API
 *
 * 직원이 개인정보(생년월일, 성별, 주소) 변경을 요청하고,
 * 관리자가 승인/반려하는 워크플로우.
 *
 * GET  ?action=getMyRequests         · 본인 요청 이력
 * POST ?action=submitRequest         · 변경 요청 제출
 * POST ?action=cancelRequest         · 대기 중 요청 취소
 * GET  ?action=getPendingRequests    · 관리자: 요청 목록
 * POST ?action=reviewRequest         · 관리자: 승인/반려
 * GET  ?action=getPendingCount       · 관리자: 대기 건수
 *
 * 응답: {ok, data} / {ok: false, error: {code, message}}
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    apiError('DB_ERROR', '데이터베이스 연결 실패', 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getMyRequests':
        getMyRequests($pdo);
        break;
    case 'submitRequest':
        submitRequest($pdo);
        break;
    case 'cancelRequest':
        cancelRequest($pdo);
        break;
    case 'getPendingRequests':
        getPendingRequests($pdo);
        break;
    case 'reviewRequest':
        reviewRequest($pdo);
        break;
    case 'getPendingCount':
        getPendingCount($pdo);
        break;
    default:
        apiError('BAD_ACTION', '알 수 없는 액션입니다.', 400);
}

// ─── 허용 필드 화이트리스트 ───

const CHANGEABLE_FIELDS = ['birth_date', 'gender'];

const FIELD_LABELS = [
    'birth_date' => '생년월일',
    'gender'     => '성별',
];

// ─── 본인: 요청 이력 조회 ───

function getMyRequests(PDO $pdo): void
{
    $empId = apiSessionUserId();
    if ($empId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $statusFilter = $_GET['status'] ?? '';
    $where = 'r.employee_id = ?';
    $params = [$empId];

    if ($statusFilter !== '' && in_array($statusFilter, ['대기', '승인', '반려'], true)) {
        $where .= ' AND r.status = ?';
        $params[] = $statusFilter;
    }

    $stmt = $pdo->prepare("
        SELECT r.*, rv.name AS reviewed_by_name
        FROM employee_change_requests r
        LEFT JOIN employees rv ON r.reviewed_by = rv.id
        WHERE {$where}
        ORDER BY r.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk(['items' => $items]);
}

// ─── 본인: 변경 요청 제출 ───

function submitRequest(PDO $pdo): void
{
    $empId = apiSessionUserId();
    if ($empId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $input = apiJsonInput();
    $changes = $input['changes'] ?? [];
    $reason  = trim((string)($input['reason'] ?? ''));

    if (!is_array($changes) || empty($changes)) {
        apiError('BAD_INPUT', '변경할 항목을 입력해주세요.');
    }

    // 화이트리스트 검증
    foreach (array_keys($changes) as $field) {
        if (!in_array($field, CHANGEABLE_FIELDS, true)) {
            apiError('BAD_INPUT', "'{$field}'은(는) 변경할 수 없는 항목입니다.");
        }
    }

    // 값 형식 검증
    foreach ($changes as $field => $value) {
        $value = trim((string)$value);
        switch ($field) {
            case 'birth_date':
                if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    apiError('BAD_INPUT', '생년월일은 YYYY-MM-DD 형식이어야 합니다.');
                }
                break;
            case 'gender':
                if ($value !== '' && !in_array($value, ['M', 'F'], true)) {
                    apiError('BAD_INPUT', '성별은 M 또는 F만 가능합니다.');
                }
                break;
        }
    }

    // 현재 값 조회
    $cols = implode(', ', CHANGEABLE_FIELDS);
    $stmt = $pdo->prepare("SELECT {$cols} FROM employees WHERE id = ? AND is_active = 1");
    $stmt->execute([$empId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current) {
        apiError('NOT_FOUND', '직원 정보를 찾을 수 없습니다.', 404);
    }

    // old/new 쌍 생성 (실제 변경된 항목만)
    $changesJson = [];
    foreach ($changes as $field => $newVal) {
        $newVal = trim((string)$newVal);
        $oldVal = $current[$field] ?? null;
        if ($oldVal === '') $oldVal = null;
        $newNorm = $newVal === '' ? null : $newVal;
        if ((string)($oldVal ?? '') !== (string)($newNorm ?? '')) {
            $changesJson[$field] = ['old' => $oldVal, 'new' => $newNorm];
        }
    }

    if (empty($changesJson)) {
        apiError('NO_CHANGE', '변경된 항목이 없습니다.');
    }

    $pdo->beginTransaction();
    try {
        // 대기 중 요청 중복 체크 (FOR UPDATE로 race condition 방지)
        $pending = $pdo->prepare('SELECT id FROM employee_change_requests WHERE employee_id = ? AND status = ? LIMIT 1 FOR UPDATE');
        $pending->execute([$empId, '대기']);
        if ($pending->fetchColumn()) {
            $pdo->rollBack();
            apiError('PENDING_EXISTS', '이미 대기 중인 변경 요청이 있습니다. 기존 요청을 취소한 후 다시 제출해주세요.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO employee_change_requests (employee_id, changes_json, reason)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([
            $empId,
            json_encode($changesJson, JSON_UNESCAPED_UNICODE),
            $reason !== '' ? $reason : null,
        ]);
        $newId = (int)$pdo->lastInsertId();

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('submitRequest 실패: ' . $e->getMessage());
        apiError('SERVER', '변경 요청 제출 중 오류가 발생했습니다.', 500);
    }

    apiOk(['id' => $newId], 201);
}

// ─── 본인: 대기 요청 취소 ───

function cancelRequest(PDO $pdo): void
{
    $empId = apiSessionUserId();
    if ($empId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        apiError('BAD_INPUT', 'id가 필요합니다.');
    }

    $chk = $pdo->prepare('SELECT id, employee_id, status FROM employee_change_requests WHERE id = ?');
    $chk->execute([$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        apiError('NOT_FOUND', '요청을 찾을 수 없습니다.', 404);
    }
    if ((int)$row['employee_id'] !== $empId) {
        apiError('FORBIDDEN', '본인의 요청만 취소할 수 있습니다.', 403);
    }
    if ($row['status'] !== '대기') {
        apiError('BAD_STATUS', '대기 상태의 요청만 취소할 수 있습니다.');
    }

    $pdo->prepare('DELETE FROM employee_change_requests WHERE id = ?')->execute([$id]);

    apiOk();
}

// ─── 관리자: 요청 목록 조회 ───

function getPendingRequests(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $statusFilter = $_GET['status'] ?? '대기';
    $where = '1=1';
    $params = [];

    if ($statusFilter !== '' && $statusFilter !== '전체') {
        if (in_array($statusFilter, ['대기', '승인', '반려'], true)) {
            $where .= ' AND r.status = ?';
            $params[] = $statusFilter;
        }
    }

    $stmt = $pdo->prepare("
        SELECT r.*, e.name AS employee_name, e.employee_no,
               d.name AS department_name, rv.name AS reviewed_by_name
        FROM employee_change_requests r
        INNER JOIN employees e ON r.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN employees rv ON r.reviewed_by = rv.id
        WHERE {$where}
        ORDER BY
            CASE r.status WHEN '대기' THEN 0 WHEN '반려' THEN 1 ELSE 2 END,
            r.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 상태별 건수
    $counts = $pdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM employee_change_requests
        GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    apiOk([
        'items'  => $items,
        'counts' => [
            '대기' => (int)($counts['대기'] ?? 0),
            '승인' => (int)($counts['승인'] ?? 0),
            '반려' => (int)($counts['반려'] ?? 0),
        ],
    ]);
}

// ─── 관리자: 승인/반려 처리 ───

function reviewRequest(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $input    = apiJsonInput();
    $id       = (int)($input['id'] ?? 0);
    $decision = trim((string)($input['decision'] ?? ''));
    $rejectReason = trim((string)($input['reject_reason'] ?? ''));

    if ($id <= 0) {
        apiError('BAD_INPUT', 'id가 필요합니다.');
    }
    if (!in_array($decision, ['승인', '반려'], true)) {
        apiError('BAD_INPUT', 'decision은 승인 또는 반려여야 합니다.');
    }
    if ($decision === '반려' && $rejectReason === '') {
        apiError('BAD_INPUT', '반려 사유를 입력해주세요.');
    }
    if ($decision === '반려' && mb_strlen($rejectReason) > 500) {
        apiError('BAD_INPUT', '반려 사유는 500자 이내여야 합니다.');
    }

    $reviewerId = apiSessionUserId();
    if ($reviewerId <= 0) {
        apiError('AUTH', '세션에서 사용자 정보를 가져올 수 없습니다. 페이지를 새로고침하고 다시 시도해주세요.', 401);
    }

    $pdo->beginTransaction();
    try {
        $chk = $pdo->prepare('SELECT id, employee_id, status, changes_json FROM employee_change_requests WHERE id = ? FOR UPDATE');
        $chk->execute([$id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $pdo->rollBack();
            apiError('NOT_FOUND', '요청을 찾을 수 없습니다.', 404);
        }
        if ($row['status'] !== '대기') {
            $pdo->rollBack();
            apiError('BAD_STATUS', '이미 처리된 요청입니다.');
        }

        if ($decision === '승인') {
            $changes = json_decode($row['changes_json'], true);
            if (!is_array($changes) || empty($changes)) {
                $pdo->rollBack();
                apiError('BAD_DATA', '변경 데이터가 손상되었습니다.', 500);
            }

            $setClauses = [];
            $setParams  = [];
            foreach ($changes as $field => $vals) {
                if (!in_array($field, CHANGEABLE_FIELDS, true)) continue;
                $setClauses[] = "{$field} = ?";
                $setParams[]  = $vals['new'];
            }

            if (!empty($setClauses)) {
                $setParams[] = (int)$row['employee_id'];
                $sql = 'UPDATE employees SET ' . implode(', ', $setClauses) . ' WHERE id = ?';
                $pdo->prepare($sql)->execute($setParams);
            }

            $pdo->prepare('
                UPDATE employee_change_requests
                SET status = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ')->execute(['승인', $reviewerId, $id]);
        } else {
            $pdo->prepare('
                UPDATE employee_change_requests
                SET status = ?, reject_reason = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ')->execute(['반려', $rejectReason, $reviewerId, $id]);
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('reviewRequest 처리 실패: ' . $e->getMessage());
        apiError('SERVER', '처리 중 오류가 발생했습니다.', 500);
    }

    apiOk();
}

// ─── 관리자: 대기 건수 (뱃지용) ───

function getPendingCount(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $stmt = $pdo->query("SELECT COUNT(*) FROM employee_change_requests WHERE status = '대기'");
    $count = (int)$stmt->fetchColumn();

    apiOk(['count' => $count]);
}
