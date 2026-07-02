<?php
/**
 * 대시보드 데이터 로딩 함수
 * dashboard.php에서 require하여 사용. 순수 함수(사이드이펙트 없음).
 */

function dashGetAnnualLeave(PDO $pdo, int $userId): array
{
    $result = ['total' => 0.0, 'used' => 0.0, 'remain' => 0.0];
    try {
        $st = $pdo->prepare("SELECT COALESCE(total_days,0) AS total_days,
                                    COALESCE(used_days,0)  AS used_days
                             FROM annual_leave
                             WHERE employee_id=? AND year=?");
        $st->execute([$userId, (int)date('Y')]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $result['total']  = (float)$row['total_days'];
            $result['used']   = (float)$row['used_days'];
            $result['remain'] = $result['total'] - $result['used'];
        }
    } catch (Exception $e) { error_log('[Dashboard] annual_leave: '.$e->getMessage()); }
    return $result;
}

function dashGetTodayAttendance(PDO $pdo, int $userId): array
{
    $result = ['clock_in' => null, 'clock_out' => null, 'work_type' => null];
    try {
        $st = $pdo->prepare("SELECT clock_in, clock_out, work_type
                             FROM attendance_records
                             WHERE employee_id=? AND record_date=CURDATE()");
        $st->execute([$userId]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $result['clock_in']  = $row['clock_in'];
            $result['clock_out'] = $row['clock_out'];
            $result['work_type'] = $row['work_type'];
        }
    } catch (Exception $e) { error_log('[Dashboard] today att: '.$e->getMessage()); }
    return $result;
}

function dashGetWeekHours(PDO $pdo, int $userId, int $target = 40): array
{
    $hours = 0.0;
    try {
        $dow = (int)date('N'); // 1=Mon..7=Sun
        $mon = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days'));
        $sun = date('Y-m-d', strtotime($mon . ' +6 days'));
        $st = $pdo->prepare("SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(clock_out, clock_in))),0) AS secs
                             FROM attendance_records
                             WHERE employee_id=? AND record_date BETWEEN ? AND ?
                               AND clock_in IS NOT NULL AND clock_out IS NOT NULL");
        $st->execute([$userId, $mon, $sun]);
        $hours = round(((int)$st->fetchColumn()) / 3600, 1);
    } catch (Exception $e) { error_log('[Dashboard] weekHours: '.$e->getMessage()); }
    $pct = $target > 0 ? min(100, (int)round($hours / $target * 100)) : 0;
    return ['hours' => $hours, 'target' => $target, 'pct' => $pct];
}

function dashGetApprovalSummary(PDO $pdo, int $userId): array
{
    $status = ['진행'=>0, '완료'=>0, '반려'=>0, '기안'=>0];
    $pending = [];
    try {
        $st = $pdo->prepare("SELECT status, COUNT(*) c FROM approval_documents
            WHERE (drafter_id = ? OR (drafter_id IS NULL AND drafter_name = (SELECT name FROM employees WHERE id = ? LIMIT 1)))
            GROUP BY status");
        $st->execute([$userId, $userId]);
        foreach ($st as $r) { $status[$r['status']] = (int)$r['c']; }
    } catch (Exception $e) { error_log('[Dashboard] approvalSummary status: '.$e->getMessage()); }

    try {
        $st = $pdo->prepare("
            SELECT d.id, d.title, d.doc_type, d.drafter_name, d.drafter_dept, d.draft_date,
                   DATEDIFF(CURDATE(), d.draft_date) AS days_pending
            FROM approval_documents d
            INNER JOIN approval_history h ON h.document_id = d.id
            WHERE (h.approver_id = ? OR (h.approver_id IS NULL AND h.approver_name = (SELECT name FROM employees WHERE id = ? LIMIT 1)))
              AND h.action = '대기' AND d.status = '진행'
            ORDER BY d.draft_date ASC LIMIT 5");
        $st->execute([$userId, $userId]);
        $pending = $st->fetchAll();
    } catch (Exception $e) { error_log('[Dashboard] approvalSummary pending: '.$e->getMessage()); }

    return ['status' => $status, 'pending' => $pending, 'pending_count' => count($pending)];
}

function dashGetScheduleModalData(PDO $pdo): array
{
    $categories = [];
    $employees  = [];
    try {
        $categories = $pdo->query("
            SELECT ci.id AS item_id, ci.name, ci.code
            FROM common_code_items ci
            JOIN common_code_groups cg ON ci.group_id = cg.id
            WHERE cg.module = 'schedule' AND cg.name = '일정유형' AND ci.is_active = 1
            ORDER BY ci.sort_order, ci.id")->fetchAll();
        $employees = $pdo->query("
            SELECT e.id, e.name, e.position, d.name AS department
            FROM employees e LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.is_active = 1 AND e.employment_status = '재직'
            ORDER BY e.name")->fetchAll();
    } catch (Exception $e) { error_log('[Dashboard] scheduleModal: '.$e->getMessage()); }

    if (empty($categories)) {
        $categories = [
            ['item_id'=>62,'name'=>'회의','code'=>'MTG'],
            ['item_id'=>63,'name'=>'외부미팅','code'=>'EXT'],
            ['item_id'=>64,'name'=>'출장','code'=>'TRIP'],
            ['item_id'=>65,'name'=>'교육','code'=>'EDU'],
            ['item_id'=>66,'name'=>'기타','code'=>'ETC'],
            ['item_id'=>86,'name'=>'외근','code'=>'OUT'],
            ['item_id'=>87,'name'=>'면담','code'=>'INTV'],
            ['item_id'=>88,'name'=>'행사','code'=>'EVT'],
            ['item_id'=>89,'name'=>'마감','code'=>'DUE'],
        ];
    }
    return ['categories' => $categories, 'employees' => $employees];
}

function dashGetBoardPosts(PDO $pdo, string $type, int $limit = 5): array
{
    try {
        $st = $pdo->prepare("
            SELECT id, title, category, created_at, is_pinned, author_name, views
            FROM board_posts
            WHERE board_type=? AND status='active'
            ORDER BY is_pinned DESC, created_at DESC
            LIMIT " . (int)$limit); /* LIMIT: MySQL prepared bind 불가, int 캐스팅으로 대체 */
        $st->execute([$type]);
        return $st->fetchAll();
    } catch (Exception $e) { error_log('[Dashboard] boardPosts: '.$e->getMessage()); return []; }
}

function dashGetDeptStatus(PDO $pdo, int $deptId): array
{
    $result = ['total'=>0, 'inOffice'=>0, 'outside'=>0, 'deptName'=>null];
    try {
        $stDept = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $stDept->execute([$deptId]);
        $result['deptName'] = $stDept->fetchColumn() ?: null;

        $stTotal = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE is_active=1 AND employment_status='재직' AND department_id = ?");
        $stTotal->execute([$deptId]);
        $result['total'] = (int)$stTotal->fetchColumn();

        $stAtt = $pdo->prepare("
            SELECT
              COUNT(DISTINCT CASE WHEN a.clock_in IS NOT NULL AND (a.work_type IS NULL OR a.work_type='') THEN a.employee_id END) AS in_office,
              COUNT(DISTINCT CASE WHEN a.work_type='외근' THEN a.employee_id END) AS outside
            FROM attendance_records a
            JOIN employees e ON e.id = a.employee_id
            WHERE a.record_date = CURDATE() AND e.department_id = ?");
        $stAtt->execute([$deptId]);
        $row = $stAtt->fetch(PDO::FETCH_ASSOC);
        $result['inOffice'] = (int)($row['in_office'] ?? 0);
        $result['outside']  = (int)($row['outside'] ?? 0);
    } catch (Exception $e) { error_log('[Dashboard] 부서 orgStats: '.$e->getMessage()); }
    return $result;
}

function dashGetAnniversaries(PDO $pdo, int $deptId): array
{
    try {
        $st = $pdo->prepare("
            SELECT id, name, position, hire_date,
                   (YEAR(CURDATE()) - YEAR(hire_date)) AS years
            FROM employees
            WHERE is_active=1 AND employment_status='재직'
              AND department_id = ?
              AND MONTH(hire_date) = MONTH(CURDATE())
              AND hire_date < CURDATE()
            ORDER BY DAY(hire_date) LIMIT 6");
        $st->execute([$deptId]);
        return $st->fetchAll();
    } catch (Exception $e) { error_log('[Dashboard] anniversaries: '.$e->getMessage()); return []; }
}

function dashGetKpiComparisons(PDO $pdo, int $userId): array
{
    $result = ['lastWeekHours' => 0.0, 'yesterdayEvents' => 0];

    $dow = (int)date('N'); // 1=Mon..7=Sun
    $thisMon = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days'));
    $lastMon = date('Y-m-d', strtotime($thisMon . ' -7 days'));
    $sameDayLastWeek = date('Y-m-d', strtotime('-7 days'));

    try {
        $st = $pdo->prepare("
            SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(clock_out, clock_in))),0) AS secs
            FROM attendance_records
            WHERE employee_id=? AND record_date BETWEEN ? AND ?
              AND clock_in IS NOT NULL AND clock_out IS NOT NULL");
        $st->execute([$userId, $lastMon, $sameDayLastWeek]);
        $result['lastWeekHours'] = round(((int)$st->fetchColumn()) / 3600, 1);
    } catch (Exception $e) { error_log('[Dashboard] kpi lastWeekHours: '.$e->getMessage()); }

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    try {
        $st = $pdo->prepare("
            SELECT COUNT(DISTINCT s.id) AS cnt
            FROM schedules s
            LEFT JOIN schedule_attendees sa ON sa.schedule_id = s.id
            WHERE s.status = 'active'
              AND s.start_date <= ? AND s.end_date >= ?
              AND (s.creator_id = ? OR sa.employee_id = ?)");
        $st->execute([$yesterday, $yesterday, $userId, $userId]);
        $result['yesterdayEvents'] = (int)$st->fetchColumn();
    } catch (Exception $e) { error_log('[Dashboard] kpi yesterdayEvents: '.$e->getMessage()); }

    return $result;
}

function dashGetTodayTasks(PDO $pdo, int $userId): array
{
    $tasks = [];

    try {
        $st = $pdo->prepare("
            SELECT id, title, doc_type, updated_at
            FROM approval_documents
            WHERE (drafter_id = ? OR (drafter_id IS NULL AND drafter_name = (SELECT name FROM employees WHERE id = ? LIMIT 1)))
              AND status = '반려'
            ORDER BY updated_at DESC LIMIT 5");
        $st->execute([$userId, $userId]);
        foreach ($st as $row) {
            $tasks[] = [
                'type'     => 'rejected',
                'id'       => (int)$row['id'],
                'title'    => $row['title'],
                'subtitle' => $row['doc_type'],
                'label'    => '재작성',
            ];
        }
    } catch (Exception $e) { error_log('[Dashboard] todayTasks rejected: '.$e->getMessage()); }

    try {
        $st = $pdo->prepare("
            SELECT d.id, d.title, d.doc_type, d.drafter_name,
                   DATEDIFF(CURDATE(), d.draft_date) AS days_pending
            FROM approval_documents d
            INNER JOIN approval_history h ON h.document_id = d.id
            WHERE (h.approver_id = ? OR (h.approver_id IS NULL AND h.approver_name = (SELECT name FROM employees WHERE id = ? LIMIT 1)))
              AND h.action = '대기' AND d.status = '진행'
            ORDER BY d.draft_date ASC LIMIT 10");
        $st->execute([$userId, $userId]);
        foreach ($st as $row) {
            $days = (int)$row['days_pending'];
            $tasks[] = [
                'type'     => 'approval',
                'id'       => (int)$row['id'],
                'title'    => $row['title'],
                'subtitle' => $row['doc_type'] . ' · ' . $row['drafter_name'],
                'label'    => $days . '일',
                'urgent'   => $days >= 3,
            ];
        }
    } catch (Exception $e) { error_log('[Dashboard] todayTasks pending: '.$e->getMessage()); }

    try {
        $st = $pdo->prepare("
            SELECT DISTINCT s.id, s.title, s.start_time, s.is_all_day
            FROM schedules s
            LEFT JOIN schedule_attendees sa ON sa.schedule_id = s.id
            WHERE s.status = 'active'
              AND s.start_date <= CURDATE() AND s.end_date >= CURDATE()
              AND (s.creator_id = ? OR sa.employee_id = ?)
            ORDER BY COALESCE(s.start_time, '00:00')
            LIMIT 8");
        $st->execute([$userId, $userId]);
        foreach ($st as $row) {
            $time = (!$row['is_all_day'] && $row['start_time'])
                ? substr($row['start_time'], 0, 5)
                : '종일';
            $tasks[] = [
                'type'     => 'schedule',
                'id'       => (int)$row['id'],
                'title'    => $row['title'],
                'subtitle' => $time,
                'label'    => '일정',
            ];
        }
    } catch (Exception $e) { error_log('[Dashboard] todayTasks schedule: '.$e->getMessage()); }

    return $tasks;
}

function dashGetWeekEvents(PDO $pdo): array
{
    $today     = date('Y-m-d');
    $dow       = (int)date('N');
    $weekStart = date('Y-m-d', strtotime("-" . ($dow - 1) . " days"));
    $weekEnd   = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $userId    = (int)($_SESSION['user_id'] ?? 0);

    $dayEventMap = [];

    // 1) 일정 (schedules)
    try {
        $st = $pdo->prepare("
            SELECT s.id, s.title, s.start_date, s.end_date, s.start_time, s.end_time, s.is_all_day
            FROM schedules s
            WHERE s.status='active' AND s.start_date <= ? AND s.end_date >= ?
            ORDER BY s.start_date, COALESCE(s.start_time,'00:00')");
        $st->execute([$weekEnd, $weekStart]);
        foreach ($st->fetchAll() as $ev) {
            $ev['event_type'] = 'schedule';
            $sd = $ev['start_date']; $ed = $ev['end_date'] ?: $sd;
            $d = ($sd < $weekStart) ? $weekStart : $sd;
            $dEnd = ($ed > $weekEnd) ? $weekEnd : $ed;
            while ($d <= $dEnd) {
                $dayEventMap[$d][] = $ev;
                $d = date('Y-m-d', strtotime($d . ' +1 day'));
            }
        }
    } catch (Exception $e) { error_log('[Dashboard] weekEvents/schedule: '.$e->getMessage()); }

    // 2) 전자결재 (내가 기안했거나 결재 대기 중인 문서)
    if ($userId > 0) {
        try {
            $userName = '';
            $uSt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
            $uSt->execute([$userId]);
            $userName = $uSt->fetchColumn() ?: '';

            if ($userName !== '') {
                $st = $pdo->prepare("
                    SELECT DISTINCT ad.id, ad.title, ad.draft_date, ad.status, ad.doc_type, ad.drafter_name
                    FROM approval_documents ad
                    LEFT JOIN approval_history ah ON ah.document_id = ad.id
                        AND ah.approver_name = ? AND ah.action = '대기'
                    WHERE ad.draft_date BETWEEN ? AND ?
                      AND ad.status IN ('기안','진행')
                      AND (ad.drafter_name = ? OR ah.id IS NOT NULL)
                    ORDER BY ad.draft_date");
                $st->execute([$userName, $weekStart, $weekEnd, $userName]);
                foreach ($st->fetchAll() as $row) {
                    $statusLabel = $row['drafter_name'] === $userName ? '기안' : '결재대기';
                    $dayEventMap[$row['draft_date']][] = [
                        'id'         => $row['id'],
                        'title'      => $row['title'],
                        'event_type' => 'approval',
                        'subtitle'   => $statusLabel,
                        'start_time' => null,
                        'is_all_day' => 1,
                    ];
                }
            }
        } catch (Exception $e) { error_log('[Dashboard] weekEvents/approval: '.$e->getMessage()); }

        // 3) 자원예약 (내 예약)
        try {
            $st = $pdo->prepare("
                SELECT r.id, r.title, r.reservation_date, r.start_time, r.end_time,
                       ci.name AS resource_name
                FROM reservations r
                LEFT JOIN common_code_items ci ON ci.id = r.resource_item_id
                WHERE r.status = 'confirmed'
                  AND r.reservation_date BETWEEN ? AND ?
                  AND r.user_name = (SELECT name FROM employees WHERE id = ? LIMIT 1)
                ORDER BY r.reservation_date, r.start_time");
            $st->execute([$weekStart, $weekEnd, $userId]);
            foreach ($st->fetchAll() as $row) {
                $dayEventMap[$row['reservation_date']][] = [
                    'id'         => $row['id'],
                    'title'      => $row['title'] ?: ($row['resource_name'] ?? '예약'),
                    'event_type' => 'reservation',
                    'subtitle'   => $row['resource_name'] ?? '',
                    'start_time' => $row['start_time'],
                    'end_time'   => $row['end_time'],
                    'is_all_day' => 0,
                ];
            }
        } catch (Exception $e) { error_log('[Dashboard] weekEvents/reservation: '.$e->getMessage()); }
    }

    $dayNames = ['일','월','화','수','목','금','토'];
    $days = [];
    $d = $weekStart;
    for ($i = 0; $i < 7; $i++) {
        $dw = (int)date('w', strtotime($d));
        $evts = $dayEventMap[$d] ?? [];
        usort($evts, function($a, $b) {
            $ta = $a['start_time'] ?? '99:99';
            $tb = $b['start_time'] ?? '99:99';
            return strcmp($ta, $tb);
        });
        $days[] = [
            'date'      => $d,
            'dayName'   => $dayNames[$dw],
            'dayNum'    => (int)date('j', strtotime($d)),
            'month'     => (int)date('n', strtotime($d)),
            'isToday'   => ($d === $today),
            'isWeekend' => ($dw === 0 || $dw === 6),
            'events'    => $evts,
        ];
        $d = date('Y-m-d', strtotime($d . ' +1 day'));
    }

    return [
        'weekStart'      => $weekStart,
        'weekEnd'        => $weekEnd,
        'today'          => $today,
        'days'           => $days,
        'todayEvents'    => $dayEventMap[$today] ?? [],
        'todayEventCount'=> count($dayEventMap[$today] ?? []),
    ];
}
