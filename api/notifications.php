<?php
/**
 * 알림 API
 *
 * GET  ?action=getNotifications   · 본인 알림 목록 (최신 20개)
 * GET  ?action=getUnreadCount     · 읽지 않은 알림 건수 (배지용)
 * POST ?action=markRead           · 특정 알림 읽음 처리
 * POST ?action=markAllRead        · 전체 알림 읽음 처리
 * POST ?action=deleteNotification · 특정 알림 삭제
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
    case 'getNotifications':
        getNotifications($pdo);
        break;
    case 'getUnreadCount':
        getUnreadCount($pdo);
        break;
    case 'markRead':
    case 'markAllRead':
    case 'deleteNotification':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('METHOD_NOT_ALLOWED', 'POST 메서드만 허용됩니다.', 405);
        }
        if ($action === 'markRead') markRead($pdo);
        elseif ($action === 'markAllRead') markAllRead($pdo);
        else deleteNotification($pdo);
        break;
    default:
        apiError('BAD_ACTION', '알 수 없는 액션입니다.', 400);
}

function getNotifications(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $limit = min((int)($_GET['limit'] ?? 20), 50);
    $readFilter = $_GET['is_read'] ?? '';

    $where = 'user_id = ?';
    $params = [$userId];

    if ($readFilter === '0' || $readFilter === '1') {
        $where .= ' AND is_read = ?';
        $params[] = (int)$readFilter;
    }

    $params[] = $limit;
    $stmt = $pdo->prepare("
        SELECT id, type, title, message, link_url, is_read, created_at
        FROM notifications
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $countStmt->execute([$userId]);
    $unreadCount = (int)$countStmt->fetchColumn();

    apiOk([
        'items' => $items,
        'unread_count' => $unreadCount,
    ]);
}

function getUnreadCount(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();

    apiOk(['count' => $count]);
}

function markRead(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        apiError('BAD_INPUT', 'id가 필요합니다.');
    }

    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    apiOk();
}

function markAllRead(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);

    apiOk(['updated' => $stmt->rowCount()]);
}

function deleteNotification(PDO $pdo): void
{
    $userId = apiSessionUserId();
    if ($userId <= 0) {
        apiError('AUTH', '로그인이 필요합니다.', 401);
    }

    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        apiError('BAD_INPUT', 'id가 필요합니다.');
    }

    $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    apiOk();
}
