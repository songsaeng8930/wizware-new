<?php
/**
 * 연차관리 데모 더미데이터 시드
 *
 * - leave_requests 에 '승인' 상태 사용 내역을 넣고, annual_leave.used_days 를
 *   그 합계로 재계산한다 (used_days == 승인된 차감 연차 합 → 데이터 정합성 유지).
 * - 멱등: reason 이 '[샘플]' 로 시작하는 기존 레코드를 먼저 지우고 다시 넣는다.
 *   실제 사용자가 신청한 내역([샘플] 아님)은 건드리지 않는다.
 *
 * 실행: php db/seed_annual_leave_demo.php [연도]
 *       (연도 생략 시 현재 연도)
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    fwrite(STDERR, "DB 연결 실패\n");
    exit(1);
}

$year = isset($argv[1]) ? (int)$argv[1] : (int)date('Y');
$TAG  = '[샘플]';

// 차감 대상 연차 유형 (API 규약과 동일)
$DEDUCT = ['AL', 'HAM', 'HAP'];

/* ── 근속 기반 부여일수 (annual_leave.php calcTotalDays 와 동일 규칙) ── */
function calcTotalDays(?string $hireDate, int $year): float
{
    if (!$hireDate) return 15.0;
    $tenure = $year - (int)substr($hireDate, 0, 4);
    if ($tenure < 1) return 11.0;
    return min(15 + intdiv($tenure - 1, 2), 25) * 1.0;
}

/* ── 주말 피해서 평일 날짜 반환 ── */
function weekday(int $year, int $month, int $day): string
{
    $ts = mktime(0, 0, 0, $month, $day, $year);
    $w = (int)date('N', $ts);          // 1=월 ~ 7=일
    if ($w === 6) $ts += 2 * 86400;    // 토 → 월
    if ($w === 7) $ts += 1 * 86400;    // 일 → 월
    return date('Y-m-d', $ts);
}

/* ── 직원별 사용 패턴 (id 기반 결정적 분산: 0~5일) ── */
function usagePlan(int $seed, int $year): array
{
    switch ($seed % 5) {
        case 0: return []; // 미사용
        case 1: return [
            ['AL', weekday($year, 3, 4), 1.0, '개인 연차'],
        ];
        case 2: return [
            ['AL',  weekday($year, 2, 10), 2.0, '가족 여행', 1],  // 2일 range
            ['HAM', weekday($year, 4, 15), 0.5, '오전 반차 (병원)'],
        ];
        case 3: return [
            ['AL',  weekday($year, 3, 20), 1.0, '개인 사유'],
            ['AL',  weekday($year, 5, 8),  1.0, '경조사'],
            ['HAP', weekday($year, 6, 12), 0.5, '오후 반차'],
        ];
        case 4: return [
            ['AL', weekday($year, 6, 1), 5.0, '여름 휴가', 4],   // 5일 range
        ];
    }
    return [];
}

/* ── 종료일 계산 (연속 근무일 기준 대략치) ── */
function addDays(string $date, int $plus): string
{
    return $plus > 0 ? date('Y-m-d', strtotime("$date +$plus day")) : $date;
}

$employees = $pdo->query("
    SELECT id, hire_date, position
    FROM employees
    WHERE is_active = 1 AND employment_status <> '퇴사'
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

if (!$employees) {
    fwrite(STDERR, "대상 직원 없음\n");
    exit(1);
}

// 승인자: 대표이사 있으면 그 id, 없으면 첫 직원
$approverId = (int)($pdo->query("SELECT id FROM employees WHERE position = '대표이사' AND is_active = 1 ORDER BY id LIMIT 1")->fetchColumn()
    ?: $employees[0]['id']);

$pdo->beginTransaction();
try {
    // 1) 기존 샘플 사용내역 제거 (멱등)
    $del = $pdo->prepare("DELETE FROM leave_requests WHERE YEAR(start_date) = ? AND reason LIKE ?");
    $del->execute([$year, $TAG . '%']);
    $deleted = $del->rowCount();

    $insReq = $pdo->prepare("INSERT INTO leave_requests
        (employee_id, leave_type, start_date, end_date, days_used, reason, status, approved_at, approver_id)
        VALUES (?, ?, ?, ?, ?, ?, '승인', ?, ?)");

    $ensureBal = $pdo->prepare("INSERT IGNORE INTO annual_leave (employee_id, year, total_days) VALUES (?, ?, ?)");
    // used_days 는 항상 '승인+차감유형' 합으로 재계산 → 실제 사용자 신청분까지 반영
    $recalc = $pdo->prepare("
        UPDATE annual_leave al
        SET al.used_days = COALESCE((
            SELECT SUM(lr.days_used) FROM leave_requests lr
            WHERE lr.employee_id = al.employee_id
              AND YEAR(lr.start_date) = al.year
              AND lr.status = '승인'
              AND lr.leave_type IN ('AL','HAM','HAP')
        ), 0)
        WHERE al.employee_id = ? AND al.year = ?");

    $reqCount = 0;
    $usedEmp = 0;
    foreach ($employees as $emp) {
        $eid = (int)$emp['id'];
        $total = calcTotalDays($emp['hire_date'] ?: null, $year);
        $ensureBal->execute([$eid, $year, $total]);

        $plan = usagePlan($eid, $year);
        foreach ($plan as $p) {
            [$type, $start, $days, $reason] = $p;
            $rangePlus = $p[4] ?? 0;
            $end = addDays($start, $rangePlus);
            $approvedAt = $start . ' 10:00:00';
            $insReq->execute([$eid, $type, $start, $end, $days, $TAG . ' ' . $reason, $approvedAt, $approverId]);
            $reqCount++;
        }
        if ($plan) $usedEmp++;

        $recalc->execute([$eid, $year]);
    }

    $pdo->commit();

    printf("완료: %d년 · 직원 %d명 · 샘플삭제 %d건 · 샘플삽입 %d건 · 사용발생 %d명\n",
        $year, count($employees), $deleted, $reqCount, $usedEmp);

    // 요약 출력
    $sum = $pdo->prepare("SELECT SUM(total_days) t, SUM(used_days) u FROM annual_leave WHERE year = ?");
    $sum->execute([$year]);
    $s = $sum->fetch(PDO::FETCH_ASSOC);
    printf("연차 총부여 %.1f일 · 총사용 %.1f일 · 사용률 %d%%\n",
        (float)$s['t'], (float)$s['u'], $s['t'] > 0 ? round($s['u'] / $s['t'] * 100) : 0);
} catch (PDOException $e) {
    $pdo->rollBack();
    fwrite(STDERR, "실패: " . $e->getMessage() . "\n");
    exit(1);
}
