<?php
/**
 * 대결자 지정 공유 헬퍼
 *
 * api/approval.php, api/approval_delegate.php 에서 공통 사용.
 * 크론 불필요 — 조회 시 lazy expiry.
 */

/**
 * 만료된 대결을 일괄 expired 처리 (lazy expiry).
 * 만료 시 원결재자에게 미처리 문서 알림 전송.
 */
function expireDelegations(PDO $pdo): int
{
    $updateStmt = $pdo->prepare(
        "UPDATE approval_delegates SET status = 'expired', updated_at = NOW()
         WHERE status = 'active' AND end_date < CURDATE()"
    );
    $updateStmt->execute();
    $changed = $updateStmt->rowCount();

    if ($changed > 0 && function_exists('createNotification')) {
        $expiredRows = $pdo->prepare(
            "SELECT id, delegator_id, delegator_name, delegate_name
             FROM approval_delegates
             WHERE status = 'expired' AND updated_at >= NOW() - INTERVAL 1 MINUTE"
        );
        $expiredRows->execute();
        $rows = $expiredRows->fetchAll(PDO::FETCH_ASSOC);

        $pendingCountStmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT d.id) FROM approval_documents d
             JOIN approval_history h ON h.document_id = d.id
             WHERE h.approver_id = ? AND h.action = '대기' AND d.status IN ('대기','진행')
             AND h.step_order = (SELECT MIN(h2.step_order) FROM approval_history h2 WHERE h2.document_id = d.id AND h2.action = '대기')"
        );
        foreach ($rows as $r) {
            $pendingCountStmt->execute([$r['delegator_id']]);
            $pendingCount = (int)$pendingCountStmt->fetchColumn();
            $msg = $r['delegate_name'] . '님의 대결 기간이 종료되었습니다.';
            if ($pendingCount > 0) {
                $msg .= ' 미처리 결재 문서 ' . $pendingCount . '건이 있습니다.';
            }
            createNotification($pdo, (int)$r['delegator_id'], 'delegate_expired',
                '대결 만료', $msg, '/pages/approval_pending.php');
        }
    }

    return $changed;
}

/**
 * 특정 직원의 현재 활성 대결자 조회.
 * @return array|null delegate row 또는 null
 */
function findActiveDelegate(PDO $pdo, int $employeeId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT id, delegator_id, delegator_name, delegate_id, delegate_name,
                start_date, end_date, reason, created_by_type
         FROM approval_delegates
         WHERE delegator_id = ?
           AND status = 'active'
           AND CURDATE() BETWEEN start_date AND end_date
         LIMIT 1"
    );
    $stmt->execute([$employeeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * actorId가 approverId의 현재 활성 대결자인지 확인.
 */
function isDelegateFor(PDO $pdo, int $actorId, int $approverId): bool
{
    $delegation = findActiveDelegate($pdo, $approverId);
    return $delegation !== null && (int)$delegation['delegate_id'] === $actorId;
}

/**
 * delegateId가 대결자로 지정된 모든 delegator_id 배열 (getPending용).
 * @return int[]
 */
function getDelegatorIdsFor(PDO $pdo, int $delegateId): array
{
    $stmt = $pdo->prepare(
        "SELECT delegator_id FROM approval_delegates
         WHERE delegate_id = ?
           AND status = 'active'
           AND CURDATE() BETWEEN start_date AND end_date"
    );
    $stmt->execute([$delegateId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * 직급 적격성 검사: 대결자는 위임자와 같은 직급 이상이어야 함.
 * hr_ranks.sort_order 기준 (낮을수록 높은 직급).
 */
function isDelegateRankEligible(PDO $pdo, int $delegatorId, int $delegateId): bool
{
    $stmt = $pdo->prepare(
        "SELECT e.id, COALESCE(r.sort_order, 999) AS rank_order
         FROM employees e
         LEFT JOIN hr_ranks r ON e.rank_id = r.id
         WHERE e.id IN (?, ?)"
    );
    $stmt->execute([$delegatorId, $delegateId]);
    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[(int)$row['id']] = (int)$row['rank_order'];
    }

    $delegatorRank = $rows[$delegatorId] ?? 999;
    $delegateRank  = $rows[$delegateId] ?? 999;

    return $delegateRank <= $delegatorRank;
}

/**
 * 특정 직원의 활성 대결 설정이 있는지 (기간 무관, status=active).
 */
function hasActiveDelegation(PDO $pdo, int $delegatorId): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM approval_delegates
         WHERE delegator_id = ? AND status = 'active'"
    );
    $stmt->execute([$delegatorId]);
    return (int)$stmt->fetchColumn() > 0;
}
