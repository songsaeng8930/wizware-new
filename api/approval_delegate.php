<?php
/**
 * 대결자 지정 API
 *
 * NEW 패턴 (apiOk / apiError) 사용.
 * 액션: getMyDelegate, setDelegate, cancelDelegate,
 *       getDelegatesAll, setDelegateAdmin, cancelDelegateAdmin,
 *       getEligibleDelegates, getMyHistory
 */

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_delegate_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if (file_exists(__DIR__ . '/../includes/approval_audit.php')) {
    require_once __DIR__ . '/../includes/approval_audit.php';
}

$pdo = getDBConnection();
if (!$pdo) apiError('DB_ERROR', 'DB 연결 실패', 500);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo->query("SELECT 1 FROM approval_delegates LIMIT 0");
} catch (PDOException $e) {
    apiError('TABLE_MISSING', 'approval_delegates 테이블이 없습니다. migrate_approval_delegates.sql을 실행하세요.', 500);
}

match ($action) {
    'getMyDelegate'        => getMyDelegate($pdo),
    'setDelegate'          => setDelegate($pdo),
    'cancelDelegate'       => cancelDelegate($pdo),
    'getDelegatesAll'      => getDelegatesAll($pdo),
    'setDelegateAdmin'     => setDelegateAdmin($pdo),
    'cancelDelegateAdmin'  => cancelDelegateAdmin($pdo),
    'getEligibleDelegates' => getEligibleDelegates($pdo),
    'getMyHistory'         => getMyHistory($pdo),
    default                => apiError('UNKNOWN_ACTION', "알 수 없는 액션: {$action}"),
};

// ── 사용자 액션 ──

function getMyDelegate(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401);

    expireDelegations($pdo);
    $delegation = findActiveDelegate($pdo, $userId);

    apiOk(['delegation' => $delegation]);
}

function setDelegate(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401);

    $userName = getEmployeeName($pdo, $userId);
    if (!$userName) apiError('NOT_FOUND', '본인 정보를 찾을 수 없습니다.', 404);

    $input = apiJsonInput();
    $delegateId = (int)($input['delegate_id'] ?? 0);
    $startDate  = trim($input['start_date'] ?? '');
    $endDate    = trim($input['end_date'] ?? '');
    $reason     = trim($input['reason'] ?? '');

    if ($delegateId <= 0) apiError('BAD_INPUT', '대결자를 선택하세요.');
    if ($delegateId === $userId) apiError('BAD_INPUT', '본인을 대결자로 지정할 수 없습니다.');
    if (!$startDate || !$endDate) apiError('BAD_INPUT', '시작일과 종료일을 입력하세요.');
    if (!isValidDateFormat($startDate) || !isValidDateFormat($endDate)) apiError('BAD_INPUT', '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD).');
    if ($endDate < $startDate) apiError('BAD_INPUT', '종료일은 시작일 이후여야 합니다.');
    if ($startDate < date('Y-m-d')) apiError('BAD_INPUT', '시작일은 오늘 이후여야 합니다.');

    saveDelegation($pdo, $userId, $userName, $delegateId, $startDate, $endDate, $reason, 'self', $userId);
}

function cancelDelegate(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401);

    $input = apiJsonInput();
    $delegationId = (int)($input['id'] ?? 0);
    if ($delegationId <= 0) apiError('BAD_INPUT', '대결 ID가 필요합니다.');

    cancelDelegation($pdo, $delegationId, $userId, false);
}

// ── 관리자 액션 ──

function getDelegatesAll(PDO $pdo): void
{
    apiRequireAdminOrManager();
    expireDelegations($pdo);

    $statusFilter = $_GET['status'] ?? 'active';
    $allowedStatuses = ['active', 'expired', 'cancelled'];
    $where = '';
    $params = [];

    if ($statusFilter !== 'all' && in_array($statusFilter, $allowedStatuses, true)) {
        $where = 'WHERE d.status = ?';
        $params[] = $statusFilter;
    }

    $stmt = $pdo->prepare(
        "SELECT d.*, e1.name AS delegator_current_name, e2.name AS delegate_current_name
         FROM approval_delegates d
         LEFT JOIN employees e1 ON e1.id = d.delegator_id
         LEFT JOIN employees e2 ON e2.id = d.delegate_id
         {$where}
         ORDER BY d.created_at DESC
         LIMIT 200"
    );
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk(['delegations' => $list]);
}

function setDelegateAdmin(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $adminId   = apiSessionUserId();
    $input     = apiJsonInput();
    $delegatorId = (int)($input['delegator_id'] ?? 0);
    $delegateId  = (int)($input['delegate_id'] ?? 0);
    $startDate   = trim($input['start_date'] ?? '');
    $endDate     = trim($input['end_date'] ?? '');
    $reason      = trim($input['reason'] ?? '');

    if ($delegatorId <= 0) apiError('BAD_INPUT', '원결재자를 선택하세요.');
    if ($delegateId <= 0) apiError('BAD_INPUT', '대결자를 선택하세요.');
    if ($delegatorId === $delegateId) apiError('BAD_INPUT', '같은 사람을 지정할 수 없습니다.');
    if (!$startDate || !$endDate) apiError('BAD_INPUT', '기간을 입력하세요.');
    if (!isValidDateFormat($startDate) || !isValidDateFormat($endDate)) apiError('BAD_INPUT', '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD).');
    if ($endDate < $startDate) apiError('BAD_INPUT', '종료일은 시작일 이후여야 합니다.');

    $delegatorName = getEmployeeName($pdo, $delegatorId);
    if (!$delegatorName) apiError('NOT_FOUND', '원결재자를 찾을 수 없습니다.', 404);

    saveDelegation($pdo, $delegatorId, $delegatorName, $delegateId, $startDate, $endDate, $reason, 'admin', $adminId);
}

function cancelDelegateAdmin(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $adminId = apiSessionUserId();
    $input = apiJsonInput();
    $delegationId = (int)($input['id'] ?? 0);
    if ($delegationId <= 0) apiError('BAD_INPUT', '대결 ID가 필요합니다.');

    cancelDelegation($pdo, $delegationId, $adminId, true);
}

function getEligibleDelegates(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401);

    $search = trim($_GET['q'] ?? '');
    $filter = trim($_GET['filter'] ?? 'all');

    $sql = "SELECT e.id, e.name, COALESCE(dep.name, '') AS dept_name,
                   COALESCE(r.name, '') AS rank_name
            FROM employees e
            LEFT JOIN departments dep ON e.department_id = dep.id
            LEFT JOIN hr_ranks r ON e.rank_id = r.id
            WHERE e.is_active = 1
              AND e.employment_status = '재직'
              AND e.id != ?";
    $params = [$userId];

    if ($filter === 'dept') {
        $deptStmt = $pdo->prepare("SELECT department_id FROM employees WHERE id = ?");
        $deptStmt->execute([$userId]);
        $myDeptId = $deptStmt->fetchColumn();
        if ($myDeptId) {
            $sql .= " AND e.department_id = ?";
            $params[] = $myDeptId;
        }
    }

    if ($search !== '') {
        $sql .= " AND (e.name LIKE ? OR COALESCE(dep.name, '') LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $sql .= " ORDER BY r.sort_order ASC, e.name ASC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk(['employees' => $employees]);
}

function getMyHistory(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401);

    $stmt = $pdo->prepare(
        "SELECT * FROM approval_delegates
         WHERE delegator_id = ?
         ORDER BY created_at DESC
         LIMIT 20"
    );
    $stmt->execute([$userId]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk(['history' => $list]);
}

// ── 내부 함수 ──

function saveDelegation(
    PDO $pdo, int $delegatorId, string $delegatorName,
    int $delegateId, string $startDate, string $endDate,
    string $reason, string $createdByType, int $createdById
): void {
    if (!isDelegateRankEligible($pdo, $delegatorId, $delegateId)) {
        apiError('RANK_INELIGIBLE', '대결자는 같은 직급 이상이어야 합니다.');
    }

    $delegateName = getEmployeeName($pdo, $delegateId);
    if (!$delegateName) apiError('NOT_FOUND', '대결자를 찾을 수 없습니다.', 404);

    $pdo->beginTransaction();
    try {
        expireDelegations($pdo);

        if (hasActiveDelegation($pdo, $delegatorId)) {
            $pdo->rollBack();
            apiError('ALREADY_ACTIVE', '이미 활성 대결이 있습니다. 기존 대결을 해제한 후 다시 지정하세요.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO approval_delegates
                (delegator_id, delegator_name, delegate_id, delegate_name,
                 start_date, end_date, status, created_by_id, created_by_type, reason)
             VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)"
        );
        $stmt->execute([
            $delegatorId, $delegatorName, $delegateId, $delegateName,
            $startDate, $endDate, $createdById, $createdByType,
            $reason ?: null,
        ]);
        $newId = (int)$pdo->lastInsertId();

        $pdo->commit();

        createNotification($pdo, $delegateId, 'delegate_assigned',
            '대결자 지정',
            $delegatorName . '님이 대결자로 지정했습니다 (' . $startDate . ' ~ ' . $endDate . ')',
            '/pages/approval_delegate.php');

        if ($createdByType === 'admin' && $createdById !== $delegatorId) {
            createNotification($pdo, $delegatorId, 'delegate_admin_set',
                '대결 설정 (관리자)',
                '관리자가 ' . $delegateName . '님을 대결자로 설정했습니다 (' . $startDate . ' ~ ' . $endDate . ')',
                '/pages/approval_delegate.php');
        }

        if (function_exists('logApprovalAudit')) {
            logApprovalAudit($pdo, 'delegate_set', 'delegate', 'delegate', $newId,
                $delegatorName . ' → ' . $delegateName,
                null,
                ['delegator_id' => $delegatorId, 'delegate_id' => $delegateId,
                 'start' => $startDate, 'end' => $endDate],
                $reason ?: null);
        }

        apiOk(['id' => $newId, 'message' => '대결자가 지정되었습니다.']);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[approval_delegate] saveDelegation error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '대결자 지정 중 오류가 발생했습니다.', 500);
    }
}

function cancelDelegation(PDO $pdo, int $delegationId, int $actorId, bool $isAdmin): void
{
    $stmt = $pdo->prepare("SELECT * FROM approval_delegates WHERE id = ? AND status = 'active'");
    $stmt->execute([$delegationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) apiError('NOT_FOUND', '활성 대결을 찾을 수 없습니다.', 404);

    if (!$isAdmin && (int)$row['delegator_id'] !== $actorId) {
        apiError('FORBIDDEN', '본인의 대결만 해제할 수 있습니다.', 403);
    }

    $pdo->prepare(
        "UPDATE approval_delegates SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW() WHERE id = ?"
    )->execute([$delegationId]);

    createNotification($pdo, (int)$row['delegate_id'], 'delegate_cancelled',
        '대결 해제',
        $row['delegator_name'] . '님의 대결이 해제되었습니다.',
        '/pages/approval_delegate.php');

    if (function_exists('logApprovalAudit')) {
        $eventType = $isAdmin ? 'admin_delegate_cancelled' : 'delegate_cancelled';
        logApprovalAudit($pdo, $eventType, 'delegate', 'delegate', $delegationId,
            $row['delegator_name'] . ' → ' . $row['delegate_name'] . ' (해제)',
            ['status' => 'active'], ['status' => 'cancelled']);
    }

    apiOk(['message' => '대결이 해제되었습니다.']);
}

function isValidDateFormat(string $d): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$y, $m, $day] = array_map('intval', explode('-', $d));
    return checkdate($m, $day, $y);
}

function getEmployeeName(PDO $pdo, int $empId): ?string
{
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$empId]);
    $name = $stmt->fetchColumn();
    return $name !== false ? (string)$name : null;
}
