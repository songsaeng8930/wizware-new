<?php
/**
 * 연차관리 공통 헬퍼
 *
 * - calcBusinessDays(): 주말+공휴일 제외 영업일 계산
 * - logLeaveAudit(): 연차 변경 감사 로그 기록
 * - ensureTable(): 테이블 존재 보장 (런타임 CREATE 최소화)
 */

declare(strict_types=1);

require_once __DIR__ . '/approval_audit.php';
require_once __DIR__ . '/notification_helper.php';

/**
 * 주말(토/일) + holidays 테이블 공휴일을 제외한 영업일수 계산
 */
function calcBusinessDays(PDO $pdo, string $startDate, string $endDate): float
{
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);

    if ($start > $end) return 0;

    try {
        $st = $pdo->prepare("SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?");
        $st->execute([$startDate, $endDate]);
        $holidays = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'holiday_date');
    } catch (PDOException $e) {
        $holidays = [];
    }

    $days = 0;
    $current = clone $start;
    while ($current <= $end) {
        $dow = (int)$current->format('N'); // 1=월 ~ 7=일
        if ($dow <= 5 && !in_array($current->format('Y-m-d'), $holidays)) {
            $days++;
        }
        $current->modify('+1 day');
    }
    return max(1, $days);
}

/**
 * 특정 기간 내 공휴일 목록 반환 (프론트 미리보기용)
 */
function getHolidaysInRange(PDO $pdo, string $startDate, string $endDate): array
{
    try {
        $st = $pdo->prepare("SELECT holiday_date, name, type FROM holidays WHERE holiday_date BETWEEN ? AND ? ORDER BY holiday_date");
        $st->execute([$startDate, $endDate]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * 연차 관련 감사 로그 기록 (approval_audit_log 테이블 재사용)
 */
function logLeaveAudit(
    PDO $pdo,
    string $eventType,
    string $targetType,
    ?int $targetId,
    ?string $targetLabel = null,
    mixed $oldValue = null,
    mixed $newValue = null,
    ?string $comment = null
): void {
    try {
        logApprovalAudit($pdo, $eventType, 'leave', $targetType, $targetId, $targetLabel, $oldValue, $newValue, $comment);
    } catch (PDOException $e) {
        error_log('[leave_helpers] audit log failed: ' . $e->getMessage());
    }
}

/**
 * 월 소정 근로일수 (통상 기준)
 */
const MONTHLY_WORKING_DAYS = 21.67;
