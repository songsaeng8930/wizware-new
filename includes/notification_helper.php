<?php
/**
 * 알림 생성 공통 헬퍼
 *
 * 결재, 정보변경요청 등 모든 모듈에서 재사용.
 * notifications 테이블에 INSERT만 담당 — 조회/읽음은 api/notifications.php에서 처리.
 */

declare(strict_types=1);

function createNotification(PDO $pdo, int $userId, string $type, string $title, string $message = '', string $linkUrl = ''): int
{
    if ($userId <= 0) return 0;

    $stmt = $pdo->prepare('
        INSERT INTO notifications (user_id, type, title, message, link_url, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ');
    $stmt->execute([$userId, $type, $title, $message ?: null, $linkUrl ?: null]);
    return (int)$pdo->lastInsertId();
}

function createNotificationBatch(PDO $pdo, array $userIds, string $type, string $title, string $message = '', string $linkUrl = ''): int
{
    $userIds = array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0));
    if (empty($userIds)) return 0;

    $stmt = $pdo->prepare('
        INSERT INTO notifications (user_id, type, title, message, link_url, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ');

    $count = 0;
    foreach ($userIds as $uid) {
        $stmt->execute([$uid, $type, $title, $message ?: null, $linkUrl ?: null]);
        $count++;
    }
    return $count;
}
