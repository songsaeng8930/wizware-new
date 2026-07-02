<?php
/**
 * 결재 운영 — 관리자 전용 API
 *
 * 전체 결재 현황(getStatus/getExceptions) + 관리자 액션(changeApprover/
 * batchChangeApprover/forceComplete/forceReject/softDelete).
 * 응답 규격: {ok:true, data:{}} / {ok:false, error:{code,message}}
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/approval_audit.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$pdo = getDBConnection();
if (!$pdo) apiError('DB_ERROR', '데이터베이스 연결 실패', 500);

apiRequireAdmin();

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'getStatus'            => getStatus($pdo),
        'getExceptions'        => getExceptions($pdo),
        'getDocSteps'          => getDocSteps($pdo),
        'getEmployeeList'      => getEmployeeList($pdo),
        'changeApprover'       => changeApprover($pdo),
        'batchChangeApprover'  => batchChangeApprover($pdo),
        'forceComplete'        => forceComplete($pdo),
        'forceReject'          => forceReject($pdo),
        'softDelete'           => softDelete($pdo),
        'sendReminder'         => sendReminder($pdo),
        'adminWithdraw'        => adminWithdraw($pdo),
        default                => apiError('UNKNOWN_ACTION', "알 수 없는 액션: {$action}"),
    };
} catch (PDOException $e) {
    error_log('[approval_admin] ' . $e->getMessage());
    apiError('DB_ERROR', '처리 중 오류가 발생했습니다.', 500);
}

// ───────────────────────────────────────────────
// 조회
// ───────────────────────────────────────────────

function getStatus(PDO $pdo): void
{
    // KPI 카드 4개
    $inProgress = (int)$pdo->query(
        "SELECT COUNT(*) FROM approval_documents WHERE status IN ('대기','진행') AND (is_deleted = 0 OR is_deleted IS NULL)"
    )->fetchColumn();

    $monthCompleted = (int)$pdo->query(
        "SELECT COUNT(*) FROM approval_documents
         WHERE status = '승인' AND complete_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
         AND (is_deleted = 0 OR is_deleted IS NULL)"
    )->fetchColumn();

    $avgDays = $pdo->query(
        "SELECT ROUND(AVG(DATEDIFF(complete_date, draft_date)), 1) FROM approval_documents
         WHERE status IN ('승인','반려') AND complete_date IS NOT NULL
         AND (is_deleted = 0 OR is_deleted IS NULL)"
    )->fetchColumn();

    $exceptionCount = countExceptions($pdo);

    // 문서 목록 (필터 + 페이지네이션)
    $where = ["(d.is_deleted = 0 OR d.is_deleted IS NULL)"];
    $params = [];
    buildAdminDocFilters($where, $params);

    $countSql = "SELECT COUNT(*) FROM approval_documents d WHERE " . implode(' AND ', $where);
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetchColumn();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT d.*, f.title AS form_title,
                (SELECT h.approver_name FROM approval_history h
                 WHERE h.document_id = d.id AND h.action = '대기'
                 ORDER BY h.step_order LIMIT 1) AS current_approver,
                (SELECT h.approver_id FROM approval_history h
                 WHERE h.document_id = d.id AND h.action = '대기'
                 ORDER BY h.step_order LIMIT 1) AS current_approver_id,
                DATEDIFF(NOW(), d.draft_date) AS elapsed_days
            FROM approval_documents d
            LEFT JOIN approval_forms f ON d.form_id = f.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.draft_date DESC, d.id DESC
            LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk([
        'kpi' => [
            'in_progress'     => $inProgress,
            'month_completed' => $monthCompleted,
            'avg_days'        => $avgDays !== null ? (float)$avgDays : null,
            'exceptions'      => $exceptionCount,
        ],
        'documents' => $docs,
        'pagination' => [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int)ceil($total / $perPage),
        ],
    ]);
}

function getExceptions(PDO $pdo): void
{
    // 퇴사자 결재 대기
    $resigned = $pdo->query(
        "SELECT d.id, d.doc_number, d.title, d.doc_type, d.drafter_name, d.drafter_dept,
                d.status, d.draft_date, DATEDIFF(NOW(), d.draft_date) AS elapsed_days,
                h.approver_name, h.approver_id, h.step_order,
                e.employment_status
         FROM approval_documents d
         JOIN approval_history h ON h.document_id = d.id AND h.action = '대기'
         JOIN employees e ON e.id = h.approver_id
         WHERE d.status IN ('대기','진행')
           AND e.employment_status = '퇴사'
           AND (d.is_deleted = 0 OR d.is_deleted IS NULL)
         ORDER BY d.draft_date"
    )->fetchAll(PDO::FETCH_ASSOC);

    // 7일 초과 지연
    $delayed = $pdo->query(
        "SELECT d.id, d.doc_number, d.title, d.doc_type, d.drafter_name, d.drafter_dept,
                d.status, d.draft_date, DATEDIFF(NOW(), d.draft_date) AS elapsed_days,
                (SELECT h.approver_name FROM approval_history h
                 WHERE h.document_id = d.id AND h.action = '대기'
                 ORDER BY h.step_order LIMIT 1) AS current_approver
         FROM approval_documents d
         WHERE d.status IN ('대기','진행')
           AND DATEDIFF(NOW(), d.draft_date) > 7
           AND (d.is_deleted = 0 OR d.is_deleted IS NULL)
         ORDER BY d.draft_date"
    )->fetchAll(PDO::FETCH_ASSOC);

    apiOk([
        'resigned' => $resigned,
        'delayed'  => $delayed,
    ]);
}

function getDocSteps(PDO $pdo): void
{
    $docId = apiRequirePositiveInt('document_id', $_GET['document_id'] ?? 0);

    $steps = $pdo->prepare(
        "SELECT h.id, h.step_order, h.approver_id, h.approver_name, h.approver_dept,
                h.role, h.action, h.action_date
         FROM approval_history h
         WHERE h.document_id = ?
         ORDER BY h.step_order"
    );
    $steps->execute([$docId]);
    apiOk(['steps' => $steps->fetchAll(PDO::FETCH_ASSOC)]);
}

function getEmployeeList(PDO $pdo): void
{
    $keyword = trim($_GET['keyword'] ?? '');

    $sql = "SELECT e.id, e.name, COALESCE(d.name,'') AS dept_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.employment_status = '재직'";
    $params = [];

    if ($keyword !== '') {
        $sql .= " AND (e.name LIKE ? OR d.name LIKE ?)";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
    }

    $sql .= " ORDER BY e.name LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    apiOk(['employees' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ───────────────────────────────────────────────
// 관리자 액션
// ───────────────────────────────────────────────

function changeApprover(PDO $pdo): void
{
    $data = apiJsonInput();
    $docId = apiRequirePositiveInt('document_id', $data['document_id'] ?? 0);
    $stepOrder = apiRequirePositiveInt('step_order', $data['step_order'] ?? 0);
    $newApproverId = apiRequirePositiveInt('new_approver_id', $data['new_approver_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    $doc = fetchDoc($pdo, $docId);

    // 새 결재자 정보
    $newEmp = $pdo->prepare("SELECT e.id, e.name, COALESCE(d.name,'') AS dept_name
        FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?");
    $newEmp->execute([$newApproverId]);
    $newApprover = $newEmp->fetch(PDO::FETCH_ASSOC);
    if (!$newApprover) apiError('NOT_FOUND', '새 결재자를 찾을 수 없습니다.', 404);

    // 기존 결재 단계 조회
    $oldStep = $pdo->prepare(
        "SELECT id, approver_id, approver_name, approver_dept FROM approval_history
         WHERE document_id = ? AND step_order = ? AND action = '대기'"
    );
    $oldStep->execute([$docId, $stepOrder]);
    $old = $oldStep->fetch(PDO::FETCH_ASSOC);
    if (!$old) apiError('INVALID_STEP', '해당 결재 단계가 대기 상태가 아닙니다.');

    $pdo->beginTransaction();

    // 결재자 변경
    $pdo->prepare(
        "UPDATE approval_history SET approver_id = ?, approver_name = ?, approver_dept = ? WHERE id = ?"
    )->execute([$newApproverId, $newApprover['name'], $newApprover['dept_name'], $old['id']]);

    // 감사 로그
    logApprovalAudit($pdo, 'admin_change_approver', 'admin', 'document', $docId, $doc['doc_number'],
        ['approver_id' => $old['approver_id'], 'approver_name' => $old['approver_name'], 'step_order' => $stepOrder],
        ['approver_id' => $newApproverId, 'approver_name' => $newApprover['name'], 'step_order' => $stepOrder],
        $comment ?: null);

    // 알림: 기존 결재자 + 기안자에게 결재선 변경 알림, 새 결재자에게 대기 알림
    $linkUrl = '/pages/approval_view.php?id=' . $docId;
    $docTitle = mb_substr($doc['title'], 0, 30);

    if ($old['approver_id'] && (int)$old['approver_id'] !== (int)$doc['drafter_id']) {
        createNotification($pdo, (int)$old['approver_id'], 'approval_line_changed',
            '결재선 변경', "\"$docTitle\" 문서의 결재자가 변경되었습니다.", $linkUrl);
    }
    createNotification($pdo, $newApproverId, 'approval_pending',
        '결재 대기', "\"$docTitle\" 문서의 결재가 요청되었습니다.", $linkUrl);
    if ((int)$doc['drafter_id'] > 0) {
        createNotification($pdo, (int)$doc['drafter_id'], 'approval_line_changed',
            '결재선 변경', "\"$docTitle\" 문서의 결재자가 {$old['approver_name']}에서 {$newApprover['name']}(으)로 변경되었습니다.", $linkUrl);
    }

    $pdo->commit();
    apiOk(['message' => '결재자가 변경되었습니다.']);
}

function batchChangeApprover(PDO $pdo): void
{
    $data = apiJsonInput();
    $oldApproverId = apiRequirePositiveInt('old_approver_id', $data['old_approver_id'] ?? 0);
    $newApproverId = apiRequirePositiveInt('new_approver_id', $data['new_approver_id'] ?? 0);
    $comment = trim($data['comment'] ?? '') ?: '퇴사자 일괄 결재선 변경';

    $newEmp = $pdo->prepare("SELECT e.id, e.name, COALESCE(d.name,'') AS dept_name
        FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?");
    $newEmp->execute([$newApproverId]);
    $newApprover = $newEmp->fetch(PDO::FETCH_ASSOC);
    if (!$newApprover) apiError('NOT_FOUND', '새 결재자를 찾을 수 없습니다.', 404);

    $oldEmp = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $oldEmp->execute([$oldApproverId]);
    $oldName = $oldEmp->fetchColumn() ?: '(알 수 없음)';

    // 대기 중인 해당 결재자의 모든 건
    $pending = $pdo->prepare(
        "SELECT h.id, h.document_id, h.step_order, d.doc_number, d.title, d.drafter_id
         FROM approval_history h
         JOIN approval_documents d ON d.id = h.document_id
         WHERE h.approver_id = ? AND h.action = '대기'
           AND d.status IN ('대기','진행')
           AND (d.is_deleted = 0 OR d.is_deleted IS NULL)"
    );
    $pending->execute([$oldApproverId]);
    $rows = $pending->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) apiError('NO_PENDING', '해당 결재자의 대기 중인 문서가 없습니다.');

    $pdo->beginTransaction();

    $update = $pdo->prepare(
        "UPDATE approval_history SET approver_id = ?, approver_name = ?, approver_dept = ? WHERE id = ?"
    );

    $changed = 0;
    foreach ($rows as $row) {
        $update->execute([$newApproverId, $newApprover['name'], $newApprover['dept_name'], $row['id']]);
        $changed++;

        logApprovalAudit($pdo, 'admin_batch_change_approver', 'admin', 'document',
            (int)$row['document_id'], $row['doc_number'],
            ['approver_id' => $oldApproverId, 'approver_name' => $oldName, 'step_order' => $row['step_order']],
            ['approver_id' => $newApproverId, 'approver_name' => $newApprover['name']],
            $comment);

        $linkUrl = '/pages/approval_view.php?id=' . $row['document_id'];
        $docTitle = mb_substr($row['title'], 0, 30);
        createNotification($pdo, $newApproverId, 'approval_pending',
            '결재 대기', "\"$docTitle\" 문서의 결재가 요청되었습니다.", $linkUrl);
        if ((int)$row['drafter_id'] > 0) {
            createNotification($pdo, (int)$row['drafter_id'], 'approval_line_changed',
                '결재선 변경', "\"$docTitle\" 문서의 결재자가 {$oldName}에서 {$newApprover['name']}(으)로 변경되었습니다.", $linkUrl);
        }
    }

    $pdo->commit();
    apiOk(['message' => "{$changed}건의 결재자가 변경되었습니다.", 'changed' => $changed]);
}

function forceComplete(PDO $pdo): void
{
    $data = apiJsonInput();
    $docId = apiRequirePositiveInt('document_id', $data['document_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');
    if (!$comment) apiError('COMMENT_REQUIRED', '강제 완료 사유를 입력해 주세요.');

    $doc = fetchDoc($pdo, $docId);
    if (!in_array($doc['status'], ['대기', '진행'], true)) {
        apiError('INVALID_STATUS', '진행 중인 문서만 강제 완료할 수 있습니다.');
    }

    $pdo->beginTransaction();

    // 남은 대기 단계를 모두 '건너뜀' 처리 (기존 전결 패턴 재활용)
    $pending = $pdo->prepare(
        "SELECT id, approver_id, approver_name FROM approval_history
         WHERE document_id = ? AND action = '대기' ORDER BY step_order"
    );
    $pending->execute([$docId]);
    $pendingSteps = $pending->fetchAll(PDO::FETCH_ASSOC);

    $skipStmt = $pdo->prepare(
        "UPDATE approval_history SET action = '건너뜀', comment = ?, action_date = NOW() WHERE id = ?"
    );
    foreach ($pendingSteps as $step) {
        $skipStmt->execute(['관리자 강제 완료: ' . $comment, $step['id']]);

        if ((int)$step['approver_id'] > 0) {
            createNotification($pdo, (int)$step['approver_id'], 'approval_progress',
                '결재 건너뜀', "\"" . mb_substr($doc['title'], 0, 30) . "\" 문서가 관리자에 의해 강제 완료되었습니다.",
                '/pages/approval_view.php?id=' . $docId);
        }
    }

    // 문서 상태 → 승인
    $pdo->prepare("UPDATE approval_documents SET status = '승인', complete_date = CURDATE() WHERE id = ?")
        ->execute([$docId]);

    // 감사 로그
    logApprovalAudit($pdo, 'admin_force_complete', 'admin', 'document', $docId, $doc['doc_number'],
        ['status' => $doc['status']], ['status' => '승인'], $comment);

    // 기안자 알림
    if ((int)$doc['drafter_id'] > 0) {
        createNotification($pdo, (int)$doc['drafter_id'], 'approval_approved',
            '결재 승인', "\"" . mb_substr($doc['title'], 0, 30) . "\" 문서가 관리자에 의해 강제 승인되었습니다.",
            '/pages/approval_view.php?id=' . $docId);
    }

    $pdo->commit();
    apiOk(['message' => '문서가 강제 완료되었습니다.']);
}

function forceReject(PDO $pdo): void
{
    $data = apiJsonInput();
    $docId = apiRequirePositiveInt('document_id', $data['document_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');
    if (!$comment) apiError('COMMENT_REQUIRED', '강제 반려 사유를 입력해 주세요.');

    $doc = fetchDoc($pdo, $docId);
    if (!in_array($doc['status'], ['대기', '진행'], true)) {
        apiError('INVALID_STATUS', '진행 중인 문서만 강제 반려할 수 있습니다.');
    }

    $pdo->beginTransaction();

    // 남은 대기 단계를 모두 '건너뜀' 처리
    $pdo->prepare(
        "UPDATE approval_history SET action = '건너뜀', comment = ?, action_date = NOW()
         WHERE document_id = ? AND action = '대기'"
    )->execute(['관리자 강제 반려: ' . $comment, $docId]);

    $pdo->prepare("UPDATE approval_documents SET status = '반려', complete_date = CURDATE() WHERE id = ?")
        ->execute([$docId]);

    logApprovalAudit($pdo, 'admin_force_reject', 'admin', 'document', $docId, $doc['doc_number'],
        ['status' => $doc['status']], ['status' => '반려'], $comment);

    if ((int)$doc['drafter_id'] > 0) {
        createNotification($pdo, (int)$doc['drafter_id'], 'approval_rejected',
            '결재 반려', "\"" . mb_substr($doc['title'], 0, 30) . "\" 문서가 관리자에 의해 강제 반려되었습니다.",
            '/pages/approval_view.php?id=' . $docId);
    }

    $pdo->commit();
    apiOk(['message' => '문서가 강제 반려되었습니다.']);
}

function sendReminder(PDO $pdo): void
{
    $data = apiJsonInput();
    $docId = apiRequirePositiveInt('document_id', $data['document_id'] ?? 0);

    $doc = fetchDoc($pdo, $docId);
    if (!in_array($doc['status'], ['대기', '진행'], true)) {
        apiError('INVALID_STATUS', '진행 중인 문서만 재알림할 수 있습니다.');
    }

    $pending = $pdo->prepare(
        "SELECT approver_id, approver_name FROM approval_history
         WHERE document_id = ? AND action = '대기' ORDER BY step_order LIMIT 1"
    );
    $pending->execute([$docId]);
    $step = $pending->fetch(PDO::FETCH_ASSOC);
    if (!$step || (int)$step['approver_id'] <= 0) {
        apiError('NO_PENDING', '대기 중인 결재자가 없습니다.');
    }

    $linkUrl = '/pages/approval_view.php?id=' . $docId;
    $approverId = (int)$step['approver_id'];

    $recent = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications
         WHERE user_id = ? AND type = 'approval_reminder' AND link_url = ?
         AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
    );
    $recent->execute([$approverId, $linkUrl]);
    if ((int)$recent->fetchColumn() > 0) {
        apiError('RATE_LIMITED', '최근 10분 이내에 이미 재알림을 보냈습니다. 잠시 후 다시 시도해 주세요.');
    }

    $docTitle = mb_substr($doc['title'], 0, 30);
    createNotification($pdo, $approverId, 'approval_reminder',
        '결재 재알림', "\"$docTitle\" 문서의 결재를 요청드립니다. 확인 부탁드립니다.", $linkUrl);

    logApprovalAudit($pdo, 'admin_send_reminder', 'admin', 'document', $docId, $doc['doc_number'],
        null, ['target_approver' => $step['approver_name']], null);

    apiOk(['message' => $step['approver_name'] . '님에게 재알림을 보냈습니다.']);
}

function adminWithdraw(PDO $pdo): void
{
    $data = apiJsonInput();
    $docId = apiRequirePositiveInt('document_id', $data['document_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');
    if (!$comment) apiError('COMMENT_REQUIRED', '회수 사유를 입력해 주세요.');

    $doc = fetchDoc($pdo, $docId);
    if (!in_array($doc['status'], ['대기', '진행'], true)) {
        apiError('INVALID_STATUS', '진행 중인 문서만 회수할 수 있습니다.');
    }

    $pdo->beginTransaction();

    $pdo->prepare(
        "UPDATE approval_history SET action = '건너뜀', comment = ?, action_date = NOW()
         WHERE document_id = ? AND action = '대기'"
    )->execute(['관리자 회수: ' . $comment, $docId]);

    $pdo->prepare("UPDATE approval_documents SET status = '회수', complete_date = CURDATE() WHERE id = ?")
        ->execute([$docId]);

    logApprovalAudit($pdo, 'admin_withdraw', 'admin', 'document', $docId, $doc['doc_number'],
        ['status' => $doc['status']], ['status' => '회수'], $comment);

    if ((int)$doc['drafter_id'] > 0) {
        createNotification($pdo, (int)$doc['drafter_id'], 'approval_withdrawn',
            '결재 회수', "\"" . mb_substr($doc['title'], 0, 30) . "\" 문서가 관리자에 의해 회수되었습니다.",
            '/pages/approval_view.php?id=' . $docId);
    }

    $pdo->commit();
    apiOk(['message' => '문서가 회수되었습니다.']);
}

function softDelete(PDO $pdo): void
{
    $data = apiJsonInput();
    $docId = apiRequirePositiveInt('document_id', $data['document_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');
    if (!$comment) apiError('COMMENT_REQUIRED', '삭제 사유를 입력해 주세요.');

    $doc = fetchDoc($pdo, $docId);
    if (in_array($doc['status'], ['대기', '진행'], true)) {
        apiError('INVALID_STATUS', '진행 중인 문서는 먼저 강제 반려 후 삭제할 수 있습니다.');
    }

    $actorId = apiSessionUserId();

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE approval_documents SET is_deleted = 1, deleted_by = ? WHERE id = ?")
        ->execute([$actorId, $docId]);

    logApprovalAudit($pdo, 'admin_soft_delete', 'admin', 'document', $docId, $doc['doc_number'],
        ['status' => $doc['status'], 'title' => $doc['title']], null, $comment);
    $pdo->commit();

    apiOk(['message' => '문서가 삭제되었습니다.']);
}

// ───────────────────────────────────────────────
// 공통 헬퍼
// ───────────────────────────────────────────────

function fetchDoc(PDO $pdo, int $docId): array
{
    $stmt = $pdo->prepare(
        "SELECT id, doc_number, title, status, drafter_id, drafter_name
         FROM approval_documents WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)"
    );
    $stmt->execute([$docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) apiError('NOT_FOUND', '문서를 찾을 수 없습니다.', 404);
    return $doc;
}

function countExceptions(PDO $pdo): int
{
    // 퇴사자 대기 건수
    $r = (int)$pdo->query(
        "SELECT COUNT(DISTINCT d.id) FROM approval_documents d
         JOIN approval_history h ON h.document_id = d.id AND h.action = '대기'
         JOIN employees e ON e.id = h.approver_id
         WHERE d.status IN ('대기','진행') AND e.employment_status = '퇴사'
           AND (d.is_deleted = 0 OR d.is_deleted IS NULL)"
    )->fetchColumn();

    // 7일 초과 지연 건수
    $d = (int)$pdo->query(
        "SELECT COUNT(*) FROM approval_documents
         WHERE status IN ('대기','진행') AND DATEDIFF(NOW(), draft_date) > 7
           AND (is_deleted = 0 OR is_deleted IS NULL)"
    )->fetchColumn();

    return $r + $d;
}

function buildAdminDocFilters(array &$where, array &$params): void
{
    if (!empty($_GET['status'])) {
        $where[] = 'd.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['title'])) {
        $where[] = 'd.title LIKE ?';
        $params[] = '%' . $_GET['title'] . '%';
    }
    if (!empty($_GET['drafter'])) {
        $where[] = 'd.drafter_name LIKE ?';
        $params[] = '%' . $_GET['drafter'] . '%';
    }
    if (!empty($_GET['drafter_dept'])) {
        $where[] = 'd.drafter_dept LIKE ?';
        $params[] = '%' . $_GET['drafter_dept'] . '%';
    }
    if (!empty($_GET['doc_type'])) {
        $where[] = 'd.doc_type = ?';
        $params[] = $_GET['doc_type'];
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'd.draft_date >= ?';
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'd.draft_date <= ?';
        $params[] = $_GET['date_to'];
    }
    if (!empty($_GET['search'])) {
        $where[] = '(d.title LIKE ? OR d.doc_number LIKE ? OR d.drafter_name LIKE ?)';
        $s = '%' . $_GET['search'] . '%';
        array_push($params, $s, $s, $s);
    }
}
