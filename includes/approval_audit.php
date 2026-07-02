<?php
/**
 * 결재 감사 로그 헬퍼
 *
 * approval_audit_log 테이블에 INSERT ONLY로 이벤트를 기록한다.
 * 결재 행위(승인/반려)는 approval_history가 담당하므로 여기서는 기록하지 않는다.
 * 대상: 문서 생성/수정/삭제, 관리자 조작, 양식 변경, 설정 변경.
 */

/**
 * @param string      $eventType     이벤트 종류 (document_created, form_updated, admin_force_complete 등)
 * @param string      $eventCategory 카테고리 (document, admin, form, config)
 * @param string      $targetType    대상 종류 (document, form, line, config)
 * @param int|null    $targetId      대상 레코드 ID
 * @param string|null $targetLabel   사람이 읽을 수 있는 식별자 (문서번호, 양식명 등)
 * @param mixed       $oldValue      변경 전 값 (JSON 직렬화)
 * @param mixed       $newValue      변경 후 값 (JSON 직렬화)
 * @param string|null $comment       사유 (강제 처리 시 필수)
 */
function logApprovalAudit(
    PDO $pdo,
    string $eventType,
    string $eventCategory,
    string $targetType,
    ?int $targetId,
    ?string $targetLabel,
    mixed $oldValue = null,
    mixed $newValue = null,
    ?string $comment = null
): void {
    $actorId   = (int)($_SESSION['user_id'] ?? 0) ?: null;
    $actorName = (string)($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? '시스템');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare(
        'INSERT INTO approval_audit_log
            (event_type, event_category, actor_id, actor_name, ip_address,
             target_type, target_id, target_label,
             old_value, new_value, comment)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $eventType,
        $eventCategory,
        $actorId,
        $actorName,
        $ipAddress,
        $targetType,
        $targetId,
        $targetLabel,
        $oldValue !== null ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
        $newValue !== null ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
        $comment,
    ]);
}
