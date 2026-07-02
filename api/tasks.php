<?php
declare(strict_types=1);
/**
 * Zaemit 그룹웨어 - 개인 할 일(To-do) API
 *
 * 권한 원칙: 본인 할 일만 CRUD. 소유자 판정은 세션(owner_id = $_SESSION['user_id'])에서만 하고,
 *            요청 파라미터의 owner_id 는 신뢰하지 않는다(IDOR 차단). admin 은 지원 목적상 전체 허용.
 * 응답 규격: api_common 의 신규 패턴 {ok, data} / {ok:false, error:{code,message}}.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';

const TASK_PRIORITIES = ['low', 'normal', 'high', 'urgent'];

$pdo = getDBConnection();
if (!$pdo) apiError('DB_ERROR', '데이터베이스 연결 실패', 500);

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'list'   => listTasks($pdo),
        'create' => createTask($pdo),
        'update' => updateTask($pdo),
        'toggle' => toggleTask($pdo),
        'delete' => deleteTask($pdo),
        default  => apiError('BAD_ACTION', '알 수 없는 액션입니다.'),
    };
} catch (PDOException $e) {
    error_log('Tasks API: ' . $e->getMessage());
    apiError('SERVER_ERROR', '서버 오류가 발생했습니다.', 500);
}

// ========== 헬퍼 ==========

/** 'YYYY-MM-DD' 또는 빈값(→null) 정규화. 형식 틀리면 400. */
function normalizeDueDate(mixed $raw): ?string
{
    $d = trim((string)($raw ?? ''));
    if ($d === '') return null;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    if (!$dt || $dt->format('Y-m-d') !== $d) {
        apiError('BAD_INPUT', '날짜 형식이 올바르지 않습니다(YYYY-MM-DD).');
    }
    return $d;
}

/** 본인(또는 admin) 소유 할 일만 통과. 행 반환. */
function requireOwnTask(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM personal_tasks WHERE id = ?');
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) apiError('NOT_FOUND', '할 일을 찾을 수 없습니다.', 404);

    if ((int)$task['owner_id'] !== apiSessionUserId() && !apiIsAdmin()) {
        apiError('FORBIDDEN', '권한이 없습니다.', 403);
    }
    return $task;
}

// ========== 목록 조회 (본인) ==========
function listTasks(PDO $pdo): void
{
    // 미완료 전체 + 최근 30일 내 완료분만 반환(완료 탭 표시용).
    // 오래된 완료 건은 조회에서 제외할 뿐 DB 에는 보존된다(읽기 시점 필터, 데이터 손실 아님).
    $stmt = $pdo->prepare(
        "SELECT id, title, description, due_date, priority, status, completed_at, created_at
         FROM personal_tasks
         WHERE owner_id = ?
           AND (status = 'todo' OR completed_at >= (NOW() - INTERVAL 30 DAY))
         ORDER BY FIELD(priority,'urgent','high','normal','low'), (due_date IS NULL), due_date, id"
    );
    $stmt->execute([apiSessionUserId()]);
    apiOk(['tasks' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ========== 등록 ==========
function createTask(PDO $pdo): void
{
    $input = apiJsonInput();

    $title = trim($input['title'] ?? '');
    if ($title === '') apiError('BAD_INPUT', '할 일 내용을 입력해주세요.');
    if (mb_strlen($title) > 200) apiError('BAD_INPUT', '제목은 200자 이내로 입력해주세요.');

    $desc     = trim($input['description'] ?? '') ?: null;
    $due      = normalizeDueDate($input['due_date'] ?? null);
    $priority = $input['priority'] ?? 'normal';
    apiRequireInList('priority', $priority, TASK_PRIORITIES);

    $stmt = $pdo->prepare(
        'INSERT INTO personal_tasks (owner_id, title, description, due_date, priority)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([apiSessionUserId(), $title, $desc, $due, $priority]);

    apiOk(['id' => (int)$pdo->lastInsertId()], 201);
}

// ========== 수정 ==========
function updateTask(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = apiRequirePositiveInt('id', $input['id'] ?? 0);
    requireOwnTask($pdo, $id);

    $title = trim($input['title'] ?? '');
    if ($title === '') apiError('BAD_INPUT', '할 일 내용을 입력해주세요.');
    if (mb_strlen($title) > 200) apiError('BAD_INPUT', '제목은 200자 이내로 입력해주세요.');

    $desc     = trim($input['description'] ?? '') ?: null;
    $due      = normalizeDueDate($input['due_date'] ?? null);
    $priority = $input['priority'] ?? 'normal';
    apiRequireInList('priority', $priority, TASK_PRIORITIES);

    $upd = $pdo->prepare(
        'UPDATE personal_tasks
         SET title = ?, description = ?, due_date = ?, priority = ?
         WHERE id = ?'
    );
    $upd->execute([$title, $desc, $due, $priority, $id]);

    apiOk();
}

// ========== 완료 토글 ==========
function toggleTask(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = apiRequirePositiveInt('id', $input['id'] ?? 0);
    $task = requireOwnTask($pdo, $id);

    $newStatus = $task['status'] === 'done' ? 'todo' : 'done';
    $completedAt = $newStatus === 'done' ? date('Y-m-d H:i:s') : null;

    $upd = $pdo->prepare('UPDATE personal_tasks SET status = ?, completed_at = ? WHERE id = ?');
    $upd->execute([$newStatus, $completedAt, $id]);

    apiOk(['status' => $newStatus]);
}

// ========== 삭제 ==========
function deleteTask(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = apiRequirePositiveInt('id', $input['id'] ?? 0);
    requireOwnTask($pdo, $id);

    $del = $pdo->prepare('DELETE FROM personal_tasks WHERE id = ?');
    $del->execute([$id]);

    apiOk();
}
