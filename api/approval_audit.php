<?php
/**
 * 결재 감사로그 조회 API (관리자 전용)
 * 응답 규격: {ok:true, data:{}} / {ok:false, error:{code,message}}
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';

$pdo = getDBConnection();
if (!$pdo) apiError('DB_ERROR', '데이터베이스 연결 실패', 500);

apiRequireAdmin();

const VALID_CATEGORIES = ['document', 'admin', 'form', 'config'];
const VALID_EVENT_TYPES = [
    'document_created', 'document_updated', 'document_submitted', 'document_deleted', 'document_viewed',
    'form_created', 'form_updated', 'form_deleted', 'form_toggled',
    'line_created', 'line_updated', 'line_deleted',
    'config_changed',
    'admin_change_approver', 'admin_batch_change_approver', 'admin_force_complete', 'admin_force_reject', 'admin_soft_delete',
];

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'getLogs'            => getLogs($pdo),
        'getStats'           => getStats($pdo),
        'getApprovalActions' => getApprovalActions($pdo),
        'getLogDetail'       => getLogDetail($pdo),
        default              => apiError('UNKNOWN_ACTION', "알 수 없는 액션: {$action}"),
    };
} catch (PDOException $e) {
    error_log('[approval_audit_api] ' . $e->getMessage());
    apiError('DB_ERROR', '처리 중 오류가 발생했습니다.', 500);
}

function getLogs(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    if (!empty($_GET['event_category'])) {
        if (!in_array($_GET['event_category'], VALID_CATEGORIES, true)) {
            apiError('INVALID_FILTER', '유효하지 않은 카테고리');
        }
        $where[] = 'event_category = ?';
        $params[] = $_GET['event_category'];
    }
    if (!empty($_GET['event_type'])) {
        if (!in_array($_GET['event_type'], VALID_EVENT_TYPES, true)) {
            apiError('INVALID_FILTER', '유효하지 않은 이벤트 유형');
        }
        $where[] = 'event_type = ?';
        $params[] = $_GET['event_type'];
    }
    if (!empty($_GET['actor'])) {
        $where[] = 'actor_name LIKE ?';
        $params[] = '%' . $_GET['actor'] . '%';
    }
    if (!empty($_GET['target'])) {
        $where[] = '(target_label LIKE ? OR event_type LIKE ?)';
        $params[] = '%' . $_GET['target'] . '%';
        $params[] = '%' . $_GET['target'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'created_at >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'created_at <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }

    $whereStr = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM approval_audit_log WHERE {$whereStr}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 50)));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM approval_audit_log WHERE {$whereStr} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as &$log) {
        if ($log['old_value']) $log['old_value'] = json_decode($log['old_value'], true);
        if ($log['new_value']) $log['new_value'] = json_decode($log['new_value'], true);
    }
    unset($log);

    apiOk([
        'logs' => $logs,
        'pagination' => [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int)ceil($total / $perPage),
        ],
    ]);
}

function getStats(PDO $pdo): void
{
    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));

    $todayStmt = $pdo->prepare("SELECT COUNT(*) FROM approval_audit_log WHERE created_at >= ?");
    $todayStmt->execute([$today . ' 00:00:00']);
    $todayCount = (int)$todayStmt->fetchColumn();

    $weekStmt = $pdo->prepare("SELECT COUNT(*) FROM approval_audit_log WHERE created_at >= ?");
    $weekStmt->execute([$weekAgo . ' 00:00:00']);
    $weekCount = (int)$weekStmt->fetchColumn();

    $adminStmt = $pdo->prepare("SELECT COUNT(*) FROM approval_audit_log WHERE event_category = 'admin' AND created_at >= ?");
    $adminStmt->execute([$weekAgo . ' 00:00:00']);
    $adminCount = (int)$adminStmt->fetchColumn();

    $catStmt = $pdo->query("SELECT event_category, COUNT(*) as cnt FROM approval_audit_log GROUP BY event_category ORDER BY cnt DESC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    $typeStmt = $pdo->query("SELECT event_type, COUNT(*) as cnt FROM approval_audit_log GROUP BY event_type ORDER BY cnt DESC LIMIT 10");
    $topEvents = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk([
        'today'      => $todayCount,
        'week'       => $weekCount,
        'admin_week' => $adminCount,
        'categories' => $categories,
        'top_events' => $topEvents,
    ]);
}

function getLogDetail(PDO $pdo): void
{
    $targetType = $_GET['target_type'] ?? '';
    $targetId = (int)($_GET['target_id'] ?? 0);
    $actorId = (int)($_GET['actor_id'] ?? 0);

    $detail = [];

    if ($targetType && $targetId) {
        if ($targetType === 'document') {
            $stmt = $pdo->prepare(
                'SELECT id, doc_number, title, status, doc_type, drafter_name, drafter_dept, created_at
                 FROM approval_documents WHERE id = ?'
            );
            $stmt->execute([$targetId]);
            $detail['document'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            $ahStmt = $pdo->prepare(
                'SELECT h.approver_name, h.action, h.action_date, h.comment, h.step_order
                 FROM approval_history h
                 WHERE h.document_id = ?
                 ORDER BY h.step_order ASC, h.action_date ASC'
            );
            $ahStmt->execute([$targetId]);
            $detail['approval_flow'] = $ahStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($targetType === 'line') {
            $stmt = $pdo->prepare(
                'SELECT id, name, scope, department FROM approval_lines WHERE id = ?'
            );
            $stmt->execute([$targetId]);
            $detail['line'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $histStmt = $pdo->prepare(
            'SELECT event_type, actor_name, created_at, comment
             FROM approval_audit_log
             WHERE target_type = ? AND target_id = ?
             ORDER BY created_at DESC LIMIT 10'
        );
        $histStmt->execute([$targetType, $targetId]);
        $detail['history'] = $histStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($actorId > 0) {
        $today = date('Y-m-d');
        $todayStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM approval_audit_log WHERE actor_id = ? AND created_at >= ?'
        );
        $todayStmt->execute([$actorId, $today . ' 00:00:00']);
        $detail['actor_today_count'] = (int)$todayStmt->fetchColumn();

        $recentStmt = $pdo->prepare(
            'SELECT event_type, target_label, created_at
             FROM approval_audit_log
             WHERE actor_id = ?
             ORDER BY created_at DESC LIMIT 5'
        );
        $recentStmt->execute([$actorId]);
        $detail['actor_recent'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    apiOk($detail);
}

function getApprovalActions(PDO $pdo): void
{
    $where = ["h.action != '대기'"];
    $params = [];

    if (!empty($_GET['approver'])) {
        $where[] = 'h.approver_name LIKE ?';
        $params[] = '%' . $_GET['approver'] . '%';
    }
    if (!empty($_GET['action_type'])) {
        $where[] = 'h.action = ?';
        $params[] = $_GET['action_type'];
    }
    if (!empty($_GET['target'])) {
        $where[] = '(d.doc_number LIKE ? OR d.title LIKE ?)';
        $params[] = '%' . $_GET['target'] . '%';
        $params[] = '%' . $_GET['target'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'h.action_date >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'h.action_date <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }

    $whereStr = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) FROM approval_history h JOIN approval_documents d ON h.document_id = d.id WHERE {$whereStr}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 50)));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT h.*, d.doc_number, d.title AS doc_title, d.drafter_name, d.drafter_dept
            FROM approval_history h
            JOIN approval_documents d ON h.document_id = d.id
            WHERE {$whereStr}
            ORDER BY h.action_date DESC
            LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiOk([
        'actions' => $actions,
        'pagination' => [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int)ceil($total / $perPage),
        ],
    ]);
}
