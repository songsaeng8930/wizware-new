<?php
/**
 * Zaemit 그룹웨어 - 일정 캘린더 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_common.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getCategories':        getCategories();        break;
    case 'getEvents':            getEvents();            break;
    case 'getEventsForCalendar': getEventsForCalendar(); break;
    case 'getEvent':             getEvent();             break;
    case 'createEvent':          createEvent();          break;
    case 'updateEvent':          updateEvent();          break;
    case 'moveEvent':            moveEvent();            break;
    case 'deleteEvent':          deleteEvent();          break;
    case 'toggleImportant':      toggleImportant();      break;
    case 'getColorOverrides':    getColorOverrides();    break;
    case 'saveColorOverride':    saveColorOverride();    break;
    case 'searchEvents':         searchEvents();         break;
    case 'getCustomCalendars':   getCustomCalendars();   break;
    case 'createCustomCalendar': createCustomCalendar(); break;
    case 'updateCustomCalendar': updateCustomCalendar(); break;
    case 'deleteCustomCalendar': deleteCustomCalendar(); break;
    default:
        respond(400, ['error' => '알 수 없는 액션']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function isValidDate(string $date): bool
{
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}

// ========== 카테고리 + 색상 목록 ==========
function getCategories(): void
{
    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
        return;
    }
    try {
        $stmt = $pdo->query("
            SELECT ci.id AS item_id, ci.name, ci.code,
                   COALESCE(scc.color_code, 'blue') AS color_code
            FROM common_code_items ci
            JOIN common_code_groups cg ON ci.group_id = cg.id
            LEFT JOIN schedule_category_config scc ON scc.item_id = ci.id
            WHERE cg.module = 'schedule' AND cg.name = '일정유형' AND ci.is_active = 1
            ORDER BY ci.sort_order, ci.id
        ");
        respond(200, ['categories' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        error_log('schedule getCategories error: ' . $e->getMessage());
        respond(500, ['error' => '일정 카테고리를 불러오는 중 오류가 발생했습니다.']);
    }
}

// ========== 일정 목록 (날짜 범위) ==========
function getEvents(): void
{
    $start = $_GET['start'] ?? '';
    $end   = $_GET['end']   ?? '';
    if (!$start || !$end) {
        respond(400, ['error' => 'start, end 파라미터가 필요합니다']);
        return;
    }
    if (!isValidDate($start) || !isValidDate($end)) {
        respond(400, ['error' => '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD)']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   e.name AS creator_name, e.position AS creator_position,
                   d.name AS creator_department,
                   ci.name AS category_name, ci.code AS category_code,
                   COALESCE(scc.color_code, 'blue') AS color_code
            FROM schedules s
            JOIN employees e ON s.creator_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN common_code_items ci ON s.category_item_id = ci.id
            LEFT JOIN schedule_category_config scc ON scc.item_id = s.category_item_id
            WHERE s.status = 'active'
              AND s.start_date <= :end_date
              AND s.end_date >= :start_date
            ORDER BY s.start_date, s.start_time
        ");
        $stmt->execute(['start_date' => $start, 'end_date' => $end]);
        $events = $stmt->fetchAll();

        // 참석자 일괄 조회
        if (!empty($events)) {
            $ids = array_column($events, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $aStmt = $pdo->prepare("
                SELECT sa.schedule_id, sa.employee_id, e.name, e.position
                FROM schedule_attendees sa
                JOIN employees e ON sa.employee_id = e.id
                WHERE sa.schedule_id IN ($placeholders)
                ORDER BY e.name
            ");
            $aStmt->execute($ids);
            $attendees = $aStmt->fetchAll();

            // schedule_id 기준으로 그루핑
            $attendeeMap = [];
            foreach ($attendees as $a) {
                $attendeeMap[$a['schedule_id']][] = [
                    'employee_id' => (int)$a['employee_id'],
                    'name' => $a['name'],
                    'position' => $a['position'],
                ];
            }

            foreach ($events as &$ev) {
                $ev['attendees'] = $attendeeMap[$ev['id']] ?? [];
            }
            unset($ev);
        }

        // 공휴일 조회
        $holidays = [];
        try {
            $hStmt = $pdo->prepare(
                'SELECT id, holiday_date, name, type FROM holidays
                 WHERE holiday_date >= :hs AND holiday_date <= :he ORDER BY holiday_date'
            );
            $hStmt->execute(['hs' => $start, 'he' => $end]);
            $holidays = $hStmt->fetchAll();
        } catch (PDOException $ignore) {
            // holidays 테이블 미존재 시 무시
        }

        // 캘린더 이벤트 (세무/보험/노무/회사) 조회
        $calendarEvents = [];
        try {
            $ceStmt = $pdo->prepare(
                'SELECT id, title, description, event_date, end_date, category,
                        is_system, is_deadline, source_ref
                 FROM calendar_events
                 WHERE event_date <= :ce AND COALESCE(end_date, event_date) >= :cs
                 ORDER BY event_date, category, title'
            );
            $ceStmt->execute(['cs' => $start, 'ce' => $end]);
            $calendarEvents = $ceStmt->fetchAll();
        } catch (PDOException $ignore) {
            // calendar_events 테이블 미존재 시 무시
        }

        respond(200, [
            'events'         => $events,
            'holidays'       => $holidays,
            'calendarEvents' => $calendarEvents,
        ]);
    } catch (PDOException $e) {
        error_log('schedule getEvents error: ' . $e->getMessage());
        respond(500, ['error' => '일정 목록을 불러오는 중 오류가 발생했습니다.']);
    }
}

// ========== 일정 단건 상세 ==========
function getEvent(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        respond(400, ['error' => 'id 파라미터가 필요합니다']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   e.name AS creator_name, e.position AS creator_position,
                   d.name AS creator_department,
                   ci.name AS category_name,
                   COALESCE(scc.color_code, 'blue') AS color_code
            FROM schedules s
            JOIN employees e ON s.creator_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN common_code_items ci ON s.category_item_id = ci.id
            LEFT JOIN schedule_category_config scc ON scc.item_id = s.category_item_id
            WHERE s.id = :id AND s.status = 'active'
        ");
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch();

        if (!$event) {
            respond(404, ['error' => '일정을 찾을 수 없습니다']);
            return;
        }

        // 참석자
        $aStmt = $pdo->prepare("
            SELECT sa.employee_id, e.name, e.position
            FROM schedule_attendees sa
            JOIN employees e ON sa.employee_id = e.id
            WHERE sa.schedule_id = :sid
            ORDER BY e.name
        ");
        $aStmt->execute(['sid' => $id]);
        $event['attendees'] = $aStmt->fetchAll();

        respond(200, ['event' => $event]);
    } catch (PDOException $e) {
        error_log('schedule getEvent error: ' . $e->getMessage());
        respond(500, ['error' => '서버 오류가 발생했습니다']);
    }
}

// ========== 일정 등록 ==========
function createEvent(): void
{
    $data = getJsonInput();
    $title = trim($data['title'] ?? '');
    if (!$title) { respond(400, ['error' => '일정 제목을 입력해주세요']); return; }

    $startDate = $data['start_date'] ?? '';
    $endDate   = $data['end_date']   ?? '';
    if (!$startDate || !$endDate) { respond(400, ['error' => '날짜를 입력해주세요']); return; }
    if (!isValidDate($startDate) || !isValidDate($endDate)) { respond(400, ['error' => '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD)']); return; }
    if ($endDate < $startDate) { respond(400, ['error' => '종료일이 시작일보다 빠릅니다']); return; }

    $isAllDay = !empty($data['is_all_day']) ? 1 : 0;
    $startTime = $isAllDay ? null : ($data['start_time'] ?? null);
    $endTime   = $isAllDay ? null : ($data['end_time']   ?? null);
    $creatorId = (int)($_SESSION['user_id'] ?? 0);
    if (!$creatorId) { respond(401, ['error' => '로그인이 필요합니다']); return; }

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO schedules (title, description, start_date, start_time, end_date, end_time, is_all_day, category_item_id, custom_calendar_id, creator_id)
            VALUES (:title, :desc, :sd, :st, :ed, :et, :allday, :cat, :ccal, :creator)
        ");
        $stmt->execute([
            'title'   => $title,
            'desc'    => $data['description'] ?? null,
            'sd'      => $startDate,
            'st'      => $startTime,
            'ed'      => $endDate,
            'et'      => $endTime,
            'allday'  => $isAllDay,
            'cat'     => !empty($data['category_item_id']) ? (int)$data['category_item_id'] : null,
            'ccal'    => !empty($data['custom_calendar_id']) ? (int)$data['custom_calendar_id'] : null,
            'creator' => $creatorId,
        ]);
        $scheduleId = (int)$pdo->lastInsertId();

        // 참석자
        $attendeeIds = $data['attendee_ids'] ?? [];
        if (!empty($attendeeIds)) {
            $aStmt = $pdo->prepare("INSERT INTO schedule_attendees (schedule_id, employee_id) VALUES (:sid, :eid)");
            foreach ($attendeeIds as $eid) {
                $aStmt->execute(['sid' => $scheduleId, 'eid' => (int)$eid]);
            }
        }

        $pdo->commit();
        respond(201, ['success' => true, 'id' => $scheduleId, 'message' => '일정이 등록되었습니다.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('schedule createEvent error: ' . $e->getMessage());
        respond(500, ['error' => '일정 등록에 실패했습니다']);
    }
}

// ========== 일정 수정 ==========
function updateEvent(): void
{
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if (!$id) { respond(400, ['error' => 'id가 필요합니다']); return; }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }
    $isAdmin = in_array($_SESSION['role'] ?? '', ['admin', 'manager'], true);

    $title = trim($data['title'] ?? '');
    if (!$title) { respond(400, ['error' => '일정 제목을 입력해주세요']); return; }

    $startDate = $data['start_date'] ?? '';
    $endDate   = $data['end_date']   ?? '';
    if (!$startDate || !$endDate) { respond(400, ['error' => '날짜를 입력해주세요']); return; }
    if (!isValidDate($startDate) || !isValidDate($endDate)) { respond(400, ['error' => '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD)']); return; }
    if ($endDate < $startDate) { respond(400, ['error' => '종료일이 시작일보다 빠릅니다']); return; }

    $isAllDay  = !empty($data['is_all_day']) ? 1 : 0;
    $startTime = $isAllDay ? null : ($data['start_time'] ?? null);
    $endTime   = $isAllDay ? null : ($data['end_time']   ?? null);

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE schedules SET
                title = :title, description = :desc,
                start_date = :sd, start_time = :st,
                end_date = :ed, end_time = :et,
                is_all_day = :allday, category_item_id = :cat,
                custom_calendar_id = :ccal, updated_at = NOW()
            WHERE id = :id AND status = 'active'
              AND (creator_id = :uid OR :is_admin = 1)
        ");
        $stmt->execute([
            'title'  => $title,
            'desc'   => $data['description'] ?? null,
            'sd'     => $startDate,
            'st'     => $startTime,
            'ed'     => $endDate,
            'et'     => $endTime,
            'allday' => $isAllDay,
            'cat'    => !empty($data['category_item_id']) ? (int)$data['category_item_id'] : null,
            'ccal'   => !empty($data['custom_calendar_id']) ? (int)$data['custom_calendar_id'] : null,
            'id'     => $id,
            'uid'    => $userId,
            'is_admin' => $isAdmin ? 1 : 0,
        ]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            respond(403, ['error' => '수정 권한이 없거나 일정을 찾을 수 없습니다']);
            return;
        }

        // 참석자: 기존 삭제 후 재삽입
        $pdo->prepare("DELETE FROM schedule_attendees WHERE schedule_id = ?")->execute([$id]);
        $attendeeIds = $data['attendee_ids'] ?? [];
        if (!empty($attendeeIds)) {
            $aStmt = $pdo->prepare("INSERT INTO schedule_attendees (schedule_id, employee_id) VALUES (:sid, :eid)");
            foreach ($attendeeIds as $eid) {
                $aStmt->execute(['sid' => $id, 'eid' => (int)$eid]);
            }
        }

        $pdo->commit();
        respond(200, ['success' => true, 'message' => '일정이 수정되었습니다.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('schedule updateEvent error: ' . $e->getMessage());
        respond(500, ['error' => '일정 수정에 실패했습니다']);
    }
}

// ========== 일정 삭제 (soft delete) ==========
function deleteEvent(): void
{
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if (!$id) { respond(400, ['error' => 'id가 필요합니다']); return; }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }
    $isAdmin = in_array($_SESSION['role'] ?? '', ['admin', 'manager'], true);

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $stmt = $pdo->prepare("
            UPDATE schedules SET status = 'cancelled', updated_at = NOW()
            WHERE id = :id AND (creator_id = :uid OR :is_admin = 1)
        ");
        $stmt->execute(['id' => $id, 'uid' => $userId, 'is_admin' => $isAdmin ? 1 : 0]);

        if ($stmt->rowCount() === 0) {
            respond(403, ['error' => '삭제 권한이 없거나 일정을 찾을 수 없습니다']);
            return;
        }
        respond(200, ['success' => true, 'message' => '일정이 삭제되었습니다.']);
    } catch (PDOException $e) {
        error_log('schedule deleteEvent error: ' . $e->getMessage());
        respond(500, ['error' => '일정 삭제에 실패했습니다']);
    }
}

// ========== FullCalendar 전용 이벤트 (FC 포맷으로 3개 소스 통합 반환) ==========
function getEventsForCalendar(): void
{
    $start = $_GET['start'] ?? '';
    $end   = $_GET['end']   ?? '';
    if (!$start || !$end) {
        apiError('MISSING_PARAMS', 'start, end 파라미터가 필요합니다');
    }
    if (!isValidDate($start) || !isValidDate($end)) {
        apiError('INVALID_DATE', '날짜 형식이 올바르지 않습니다 (YYYY-MM-DD)');
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        apiError('DB_ERROR', 'DB 연결 실패', 503);
    }

    $colorHex = [
        'blue' => '#3b82f6', 'green' => '#22c55e', 'red' => '#ef4444',
        'purple' => '#8b5cf6', 'yellow' => '#eab308', 'orange' => '#f97316',
        'teal' => '#14b8a6', 'pink' => '#ec4899', 'gray' => '#94a3b8',
    ];
    $ceColorHex = [
        'tax' => '#f43f5e', 'insurance' => '#06b6d4',
        'labor' => '#f59e0b', 'company' => '#6366f1',
    ];
    $ceLabels = [
        'tax' => '세무', 'insurance' => '4대보험',
        'labor' => '노무', 'company' => '회사행사',
    ];

    $fcEvents = [];

    try {
        // 1) 일반 일정
        $hasImportant = false;
        try {
            $pdo->query("SELECT is_important FROM schedules LIMIT 0");
            $hasImportant = true;
        } catch (PDOException $ignore) {}

        $importantCol = $hasImportant ? 's.is_important' : '0 AS is_important';
        $stmt = $pdo->prepare("
            SELECT s.id, s.title, s.start_date, s.start_time, s.end_date, s.end_time,
                   s.is_all_day, s.category_item_id, s.custom_calendar_id, s.description, s.creator_id,
                   COALESCE(scc.color_code, 'blue') AS color_code,
                   ci.name AS category_name, {$importantCol},
                   cc.name AS custom_calendar_name, cc.color_code AS custom_calendar_color
            FROM schedules s
            LEFT JOIN schedule_category_config scc ON scc.item_id = s.category_item_id
            LEFT JOIN common_code_items ci ON s.category_item_id = ci.id
            LEFT JOIN custom_calendars cc ON s.custom_calendar_id = cc.id
            WHERE s.status = 'active'
              AND s.start_date <= :end_date
              AND s.end_date >= :start_date
            ORDER BY s.start_date, s.start_time
        ");
        $stmt->execute(['start_date' => $start, 'end_date' => $end]);

        foreach ($stmt->fetchAll() as $ev) {
            $hex = $colorHex[$ev['color_code']] ?? '#3b82f6';
            $allDay = (int)$ev['is_all_day'] === 1;

            $fc = [
                'id'              => 'schedule_' . $ev['id'],
                'title'           => $ev['title'],
                'allDay'          => $allDay,
                'backgroundColor' => $hex . '1f',
                'borderColor'     => $hex,
                'textColor'       => $hex,
                'classNames'      => array_filter(['fc-evt-schedule', ((int)($ev['is_important'] ?? 0)) ? 'fc-evt-important' : null]),
                'extendedProps'   => [
                    '_source'        => 'schedule',
                    '_id'            => (int)$ev['id'],
                    'colorCode'      => $ev['color_code'],
                    'colorHex'       => $hex,
                    'categoryItemId' => $ev['category_item_id'] ? (int)$ev['category_item_id'] : null,
                    'categoryName'   => $ev['category_name'],
                    'customCalendarId' => $ev['custom_calendar_id'] ? (int)$ev['custom_calendar_id'] : null,
                    'customCalendarColor' => $ev['custom_calendar_color'] ?? null,
                    'isImportant'    => (int)($ev['is_important'] ?? 0),
                ],
            ];

            if ($allDay) {
                $fc['start'] = $ev['start_date'];
                $endDt = new DateTime($ev['end_date']);
                $endDt->modify('+1 day');
                $fc['end'] = $endDt->format('Y-m-d');
            } else {
                $st = $ev['start_time'] ? substr($ev['start_time'], 0, 5) : '00:00';
                $fc['start'] = $ev['start_date'] . 'T' . $st . ':00';
                if ($ev['end_time']) {
                    $et = substr($ev['end_time'], 0, 5);
                    $fc['end'] = $ev['end_date'] . 'T' . $et . ':00';
                }
            }

            $fcEvents[] = $fc;
        }

        // 2) 공휴일
        try {
            $hStmt = $pdo->prepare(
                'SELECT id, holiday_date, name, type FROM holidays
                 WHERE holiday_date >= :hs AND holiday_date <= :he ORDER BY holiday_date'
            );
            $hStmt->execute(['hs' => $start, 'he' => $end]);
            foreach ($hStmt->fetchAll() as $h) {
                $endDt = new DateTime($h['holiday_date']);
                $endDt->modify('+1 day');
                $fcEvents[] = [
                    'id'              => 'holiday_' . $h['id'],
                    'title'           => $h['name'],
                    'start'           => $h['holiday_date'],
                    'end'             => $endDt->format('Y-m-d'),
                    'allDay'          => true,
                    'backgroundColor' => 'rgba(239,68,68,0.06)',
                    'borderColor'     => 'transparent',
                    'textColor'       => '#dc2626',
                    'classNames'      => ['fc-evt-holiday'],
                    'editable'        => false,
                    'extendedProps'   => [
                        '_source'     => 'holiday',
                        '_id'         => (int)$h['id'],
                        'holidayType' => $h['type'],
                    ],
                ];
            }
        } catch (PDOException $ignore) {}

        // 3) 캘린더 이벤트 (세무/보험/노무/회사)
        try {
            $ceStmt = $pdo->prepare(
                'SELECT id, title, description, event_date, end_date, category,
                        is_system, is_deadline, source_ref
                 FROM calendar_events
                 WHERE event_date <= :ce AND COALESCE(end_date, event_date) >= :cs
                 ORDER BY event_date, category, title'
            );
            $ceStmt->execute(['cs' => $start, 'ce' => $end]);
            foreach ($ceStmt->fetchAll() as $ce) {
                $hex = $ceColorHex[$ce['category']] ?? '#f43f5e';
                $endDateStr = $ce['end_date'] ?: $ce['event_date'];
                $endDt = new DateTime($endDateStr);
                $endDt->modify('+1 day');

                $fcEvents[] = [
                    'id'              => 'ce_' . $ce['id'],
                    'title'           => $ce['title'],
                    'start'           => $ce['event_date'],
                    'end'             => $endDt->format('Y-m-d'),
                    'allDay'          => true,
                    'backgroundColor' => $hex . '14',
                    'borderColor'     => 'transparent',
                    'textColor'       => $hex,
                    'classNames'      => ['fc-evt-ce', 'fc-ce-' . $ce['category']],
                    'editable'        => false,
                    'extendedProps'   => [
                        '_source'       => 'calendar_event',
                        '_id'           => (int)$ce['id'],
                        'category'      => $ce['category'],
                        'categoryLabel' => $ceLabels[$ce['category']] ?? $ce['category'],
                        'isDeadline'    => (int)$ce['is_deadline'],
                        'isSystem'      => (int)$ce['is_system'],
                        'description'   => $ce['description'],
                        'sourceRef'     => $ce['source_ref'],
                    ],
                ];
            }
        } catch (PDOException $ignore) {}

        apiOk(['events' => $fcEvents]);
    } catch (PDOException $e) {
        error_log('schedule getEventsForCalendar error: ' . $e->getMessage());
        apiError('DB_ERROR', '일정을 불러오는 중 오류가 발생했습니다.', 500);
    }
}

// ========== 일정 이동 (드래그&드롭 전용 — 날짜/시간만 변경) ==========
function moveEvent(): void
{
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if (!$id) { respond(400, ['error' => 'id가 필요합니다']); return; }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }
    $isAdmin = in_array($_SESSION['role'] ?? '', ['admin', 'manager'], true);

    $startDate = $data['start_date'] ?? '';
    $endDate   = $data['end_date']   ?? '';
    if (!$startDate || !$endDate) { respond(400, ['error' => '날짜가 필요합니다']); return; }
    if (!isValidDate($startDate) || !isValidDate($endDate)) { respond(400, ['error' => '날짜 형식 오류']); return; }
    if ($endDate < $startDate) { respond(400, ['error' => '종료일이 시작일보다 빠릅니다']); return; }

    $isAllDay  = !empty($data['is_all_day']) ? 1 : 0;
    $startTime = $isAllDay ? null : ($data['start_time'] ?? null);
    $endTime   = $isAllDay ? null : ($data['end_time']   ?? null);

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $stmt = $pdo->prepare("
            UPDATE schedules SET
                start_date = :sd, end_date = :ed,
                start_time = :st, end_time = :et,
                is_all_day = :allday, updated_at = NOW()
            WHERE id = :id AND status = 'active'
              AND (creator_id = :uid OR :is_admin = 1)
        ");
        $stmt->execute([
            'sd'     => $startDate,
            'ed'     => $endDate,
            'st'     => $startTime,
            'et'     => $endTime,
            'allday' => $isAllDay,
            'id'     => $id,
            'uid'    => $userId,
            'is_admin' => $isAdmin ? 1 : 0,
        ]);

        if ($stmt->rowCount() === 0) {
            respond(403, ['error' => '이동 권한이 없거나 일정을 찾을 수 없습니다']);
            return;
        }
        respond(200, ['success' => true, 'message' => '일정이 이동되었습니다.']);
    } catch (PDOException $e) {
        error_log('schedule moveEvent error: ' . $e->getMessage());
        respond(500, ['error' => '일정 이동에 실패했습니다']);
    }
}

// ========== 중요 일정 토글 ==========
function toggleImportant(): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if (!$id) { apiError('MISSING_PARAMS', 'id 필요'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    try {
        $pdo->query("SELECT is_important FROM schedules LIMIT 0");
    } catch (PDOException $e) {
        apiError('NOT_READY', 'is_important 컬럼이 없습니다. 마이그레이션을 실행해주세요.');
        return;
    }

    $userId = apiSessionUserId();

    try {
        $check = $pdo->prepare("SELECT creator_id FROM schedules WHERE id = :id AND status = 'active'");
        $check->execute(['id' => $id]);
        $owner = $check->fetch();
        if (!$owner) { apiError('NOT_FOUND', '일정을 찾을 수 없습니다', 404); }

        $role = $_SESSION['role'] ?? '';
        if ((int)$owner['creator_id'] !== $userId && !in_array($role, ['admin', 'manager'], true)) {
            apiError('FORBIDDEN', '본인 일정만 변경할 수 있습니다', 403);
        }

        $stmt = $pdo->prepare("UPDATE schedules SET is_important = NOT is_important WHERE id = :id AND status = 'active'");
        $stmt->execute(['id' => $id]);

        $val = $pdo->prepare("SELECT is_important FROM schedules WHERE id = :id");
        $val->execute(['id' => $id]);
        $row = $val->fetch();

        apiOk(['is_important' => (int)$row['is_important']]);
    } catch (PDOException $e) {
        error_log('schedule toggleImportant error: ' . $e->getMessage());
        apiError('DB_ERROR', '중요 일정 변경에 실패했습니다', 500);
    }
}

// ========== 캘린더 색상 오버라이드 조회 ==========
function getColorOverrides(): void
{
    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    $userId = apiSessionUserId();

    try {
        $stmt = $pdo->prepare("SELECT calendar_key, color_code FROM calendar_color_overrides WHERE employee_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $overrides = [];
        foreach ($stmt->fetchAll() as $row) {
            $overrides[$row['calendar_key']] = $row['color_code'];
        }
        apiOk(['overrides' => $overrides]);
    } catch (PDOException $e) {
        apiOk(['overrides' => []]);
    }
}

// ========== 캘린더 색상 오버라이드 저장 ==========
function saveColorOverride(): void
{
    $input = apiJsonInput();
    $calendarKey = trim($input['calendar_key'] ?? '');
    $colorCode = trim($input['color_code'] ?? '');

    $allowedKeys = ['schedules', 'holidays', 'tax', 'insurance', 'labor', 'company'];
    if (!in_array($calendarKey, $allowedKeys, true)) {
        apiError('INVALID_KEY', '유효하지 않은 캘린더 키');
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorCode)) {
        apiError('INVALID_COLOR', '유효하지 않은 색상 코드');
    }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    $userId = apiSessionUserId();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO calendar_color_overrides (employee_id, calendar_key, color_code)
            VALUES (:uid, :key, :color)
            ON DUPLICATE KEY UPDATE color_code = :color2, updated_at = NOW()
        ");
        $stmt->execute([
            'uid' => $userId, 'key' => $calendarKey,
            'color' => $colorCode, 'color2' => $colorCode,
        ]);
        apiOk(['saved' => true]);
    } catch (PDOException $e) {
        error_log('schedule saveColorOverride error: ' . $e->getMessage());
        apiError('DB_ERROR', '색상 저장에 실패했습니다', 500);
    }
}

function searchEvents(): void {
    $q = trim($_GET['q'] ?? '');
    if (mb_strlen($q) < 1) { apiError('INVALID', '검색어를 입력해주세요', 400); return; }

    $period = $_GET['period'] ?? 'month';
    $source = $_GET['source'] ?? 'all';

    $pdo = getDBConnection();
    $results = [];

    $dateWhere = '';
    $dateParams = [];
    if ($period === 'month') {
        $y = (int)($_GET['year'] ?? date('Y'));
        $m = (int)($_GET['month'] ?? date('n'));
        $dateWhere = ' AND s.start_date >= :dstart AND s.start_date < :dend';
        $dateParams = ['dstart' => sprintf('%04d-%02d-01', $y, $m), 'dend' => sprintf('%04d-%02d-01', $m == 12 ? $y+1 : $y, $m == 12 ? 1 : $m+1)];
    } elseif ($period === 'year') {
        $y = (int)($_GET['year'] ?? date('Y'));
        $dateWhere = ' AND s.start_date >= :dstart AND s.start_date < :dend';
        $dateParams = ['dstart' => "{$y}-01-01", 'dend' => ($y+1) . "-01-01"];
    }

    $like = '%' . $q . '%';

    if ($source === 'all' || $source === 'schedules') {
        try {
            $sql = "SELECT s.id, s.title, s.start_date, s.color_code, 'schedule' AS _source
                    FROM schedules s
                    WHERE (s.title LIKE :q1 OR s.description LIKE :q2) {$dateWhere}
                    ORDER BY s.start_date DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge(['q1' => $like, 'q2' => $like], $dateParams));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = $row;
            }
        } catch (PDOException $e) { error_log('search schedules: ' . $e->getMessage()); }
    }

    $ceCategories = [];
    if ($source === 'all') $ceCategories = ['tax','insurance','labor','company'];
    elseif (in_array($source, ['tax','insurance','labor','company'])) $ceCategories = [$source];

    if (!empty($ceCategories)) {
        try {
            $ceDateWhere = str_replace('s.start_date', 'ce.start_date', $dateWhere);
            $ceDateParams = [];
            foreach ($dateParams as $k => $v) $ceDateParams[$k] = $v;
            $placeholders = implode(',', array_map(fn($i) => ":cat{$i}", array_keys($ceCategories)));
            $sql = "SELECT ce.id, ce.title, ce.start_date, ce.category, 'calendar_event' AS _source
                    FROM calendar_events ce
                    WHERE (ce.title LIKE :q1 OR ce.description LIKE :q2)
                    AND ce.category IN ({$placeholders}) {$ceDateWhere}
                    ORDER BY ce.start_date DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $params = array_merge(['q1' => $like, 'q2' => $like], $ceDateParams);
            foreach ($ceCategories as $i => $cat) $params["cat{$i}"] = $cat;
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = $row;
            }
        } catch (PDOException $e) { error_log('search calendar_events: ' . $e->getMessage()); }
    }

    usort($results, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));
    apiOk(['results' => array_slice($results, 0, 20)]);
}

// ========== 커스텀 캘린더 CRUD ==========
function getCustomCalendars(): void
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); return; }

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, color_code, sort_order
            FROM custom_calendars
            WHERE creator_id = :uid AND is_active = 1
            ORDER BY sort_order, id
        ");
        $stmt->execute(['uid' => $userId]);
        apiOk(['calendars' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        error_log('getCustomCalendars error: ' . $e->getMessage());
        apiError('DB_ERROR', '커스텀 캘린더 조회 실패');
    }
}

function createCustomCalendar(): void
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }

    $data = getJsonInput();
    $name = trim($data['name'] ?? '');
    if (!$name) { respond(400, ['error' => '캘린더 이름을 입력해주세요']); return; }
    if (mb_strlen($name) > 100) { respond(400, ['error' => '캘린더 이름은 100자 이내로 입력해주세요']); return; }

    $colorCode = trim($data['color_code'] ?? '#4F6AFF');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorCode)) { $colorCode = '#4F6AFF'; }

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO custom_calendars (name, color_code, creator_id, sort_order)
            VALUES (:name, :color, :uid, (SELECT COALESCE(MAX(cc.sort_order), 0) + 1 FROM custom_calendars cc WHERE cc.creator_id = :uid2))
        ");
        $stmt->execute(['name' => $name, 'color' => $colorCode, 'uid' => $userId, 'uid2' => $userId]);
        $newId = (int)$pdo->lastInsertId();
        apiOk(['id' => $newId, 'name' => $name, 'color_code' => $colorCode]);
    } catch (PDOException $e) {
        error_log('createCustomCalendar error: ' . $e->getMessage());
        respond(500, ['error' => '캘린더 생성에 실패했습니다']);
    }
}

function updateCustomCalendar(): void
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if (!$id) { respond(400, ['error' => 'id가 필요합니다']); return; }

    $name = trim($data['name'] ?? '');
    if (!$name) { respond(400, ['error' => '캘린더 이름을 입력해주세요']); return; }
    if (mb_strlen($name) > 100) { respond(400, ['error' => '캘린더 이름은 100자 이내로 입력해주세요']); return; }

    $colorCode = trim($data['color_code'] ?? '#4F6AFF');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorCode)) { $colorCode = '#4F6AFF'; }

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $stmt = $pdo->prepare("
            UPDATE custom_calendars SET name = :name, color_code = :color
            WHERE id = :id AND creator_id = :uid AND is_active = 1
        ");
        $stmt->execute(['name' => $name, 'color' => $colorCode, 'id' => $id, 'uid' => $userId]);
        if ($stmt->rowCount() === 0) {
            respond(403, ['error' => '수정 권한이 없거나 캘린더를 찾을 수 없습니다']);
            return;
        }
        apiOk(['id' => $id, 'name' => $name, 'color_code' => $colorCode]);
    } catch (PDOException $e) {
        error_log('updateCustomCalendar error: ' . $e->getMessage());
        respond(500, ['error' => '캘린더 수정에 실패했습니다']);
    }
}

function deleteCustomCalendar(): void
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { respond(401, ['error' => '로그인이 필요합니다']); return; }

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if (!$id) { respond(400, ['error' => 'id가 필요합니다']); return; }

    $pdo = getDBConnection();
    if (!$pdo) { respond(500, ['error' => 'DB 연결 실패']); return; }

    try {
        $pdo->beginTransaction();

        // 소프트 삭제
        $stmt = $pdo->prepare("
            UPDATE custom_calendars SET is_active = 0
            WHERE id = :id AND creator_id = :uid AND is_active = 1
        ");
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            respond(403, ['error' => '삭제 권한이 없거나 캘린더를 찾을 수 없습니다']);
            return;
        }

        // 연결된 일정 해제
        $pdo->prepare("UPDATE schedules SET custom_calendar_id = NULL WHERE custom_calendar_id = :cid")
            ->execute(['cid' => $id]);

        $pdo->commit();
        apiOk(['deleted' => $id]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('deleteCustomCalendar error: ' . $e->getMessage());
        respond(500, ['error' => '캘린더 삭제에 실패했습니다']);
    }
}
