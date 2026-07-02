<?php
declare(strict_types=1);
/**
 * Zaemit 그룹웨어 - 캘린더 이벤트 API (세무/보험/노무/회사행사)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) apiError('DB_ERROR', '데이터베이스 연결 실패', 500);

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'getEvents'     => getEvents($pdo),
        'getCategories' => getCategories(),
        'getEvent'      => getEvent($pdo),
        'createEvent'   => createEvent($pdo),
        'updateEvent'   => updateEvent($pdo),
        'deleteEvent'   => deleteEvent($pdo),
        default         => apiError('BAD_ACTION', '알 수 없는 액션입니다.'),
    };
} catch (PDOException $e) {
    error_log('CalendarEvents API: ' . $e->getMessage());
    apiError('SERVER_ERROR', '서버 오류가 발생했습니다.', 500);
}

// ========== 목록 조회 (날짜 범위) ==========
function getEvents(PDO $pdo): void
{
    $start    = $_GET['start'] ?? '';
    $end      = $_GET['end'] ?? '';
    $category = $_GET['category'] ?? '';

    if (!$start || !$end) {
        apiError('BAD_INPUT', 'start, end 파라미터가 필요합니다.');
    }

    $where  = ['event_date <= :end_date', 'COALESCE(end_date, event_date) >= :start_date'];
    $params = ['start_date' => $start, 'end_date' => $end];

    $validCategories = ['tax', 'insurance', 'labor', 'company'];
    if ($category && in_array($category, $validCategories, true)) {
        $where[]             = 'category = :cat';
        $params['cat'] = $category;
    }

    $sql = 'SELECT * FROM calendar_events WHERE ' . implode(' AND ', $where)
         . ' ORDER BY event_date, category, title';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    apiOk(['events' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ========== 카테고리 메타 ==========
function getCategories(): void
{
    apiOk([
        'categories' => [
            ['key' => 'tax',       'label' => '세무',     'color' => 'rose'],
            ['key' => 'insurance', 'label' => '4대보험',  'color' => 'cyan'],
            ['key' => 'labor',     'label' => '노무',     'color' => 'amber'],
            ['key' => 'company',   'label' => '회사행사', 'color' => 'indigo'],
        ],
    ]);
}

// ========== 단건 조회 ==========
function getEvent(PDO $pdo): void
{
    $id = apiRequirePositiveInt('id', $_GET['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM calendar_events WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) apiError('NOT_FOUND', '이벤트를 찾을 수 없습니다.', 404);

    apiOk(['event' => $row]);
}

// ========== 등록 ==========
function createEvent(PDO $pdo): void
{
    apiRequireAdminOrManager();

    $input = apiJsonInput();
    $title     = trim($input['title'] ?? '');
    $desc      = trim($input['description'] ?? '') ?: null;
    $eventDate = $input['event_date'] ?? '';
    $endDate   = $input['end_date'] ?? null;
    $category  = $input['category'] ?? '';
    $isDead    = (int)($input['is_deadline'] ?? 0);
    $sourceRef = trim($input['source_ref'] ?? '') ?: null;

    if (!$title) apiError('BAD_INPUT', '제목을 입력해주세요.');
    if (!$eventDate) apiError('BAD_INPUT', '날짜를 입력해주세요.');
    apiRequireInList('category', $category, ['tax', 'insurance', 'labor', 'company']);

    if ($endDate && $endDate < $eventDate) {
        apiError('BAD_INPUT', '종료일은 시작일 이후여야 합니다.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO calendar_events (title, description, event_date, end_date, category, is_system, is_deadline, source_ref, created_by)
         VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)'
    );
    $stmt->execute([
        $title, $desc, $eventDate, $endDate ?: null,
        $category, $isDead, $sourceRef, apiSessionUserId(),
    ]);

    apiOk(['id' => (int)$pdo->lastInsertId()], 201);
}

// ========== 수정 ==========
function updateEvent(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = apiRequirePositiveInt('id', $input['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT is_system FROM calendar_events WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) apiError('NOT_FOUND', '이벤트를 찾을 수 없습니다.', 404);

    if ((int)$row['is_system'] === 1) {
        apiRequireAdmin();
    } else {
        apiRequireAdminOrManager();
    }

    $title     = trim($input['title'] ?? '');
    $desc      = trim($input['description'] ?? '') ?: null;
    $eventDate = $input['event_date'] ?? '';
    $endDate   = $input['end_date'] ?? null;
    $category  = $input['category'] ?? '';
    $isDead    = (int)($input['is_deadline'] ?? 0);
    $sourceRef = trim($input['source_ref'] ?? '') ?: null;

    if (!$title) apiError('BAD_INPUT', '제목을 입력해주세요.');
    if (!$eventDate) apiError('BAD_INPUT', '날짜를 입력해주세요.');
    apiRequireInList('category', $category, ['tax', 'insurance', 'labor', 'company']);

    if ($endDate && $endDate < $eventDate) {
        apiError('BAD_INPUT', '종료일은 시작일 이후여야 합니다.');
    }

    $upd = $pdo->prepare(
        'UPDATE calendar_events
         SET title = ?, description = ?, event_date = ?, end_date = ?,
             category = ?, is_deadline = ?, source_ref = ?
         WHERE id = ?'
    );
    $upd->execute([
        $title, $desc, $eventDate, $endDate ?: null,
        $category, $isDead, $sourceRef, $id,
    ]);

    apiOk();
}

// ========== 삭제 ==========
function deleteEvent(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = apiRequirePositiveInt('id', $input['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT is_system FROM calendar_events WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) apiError('NOT_FOUND', '이벤트를 찾을 수 없습니다.', 404);

    if ((int)$row['is_system'] === 1) {
        apiRequireAdmin();
    } else {
        apiRequireAdminOrManager();
    }

    $del = $pdo->prepare('DELETE FROM calendar_events WHERE id = ?');
    $del->execute([$id]);

    apiOk();
}
