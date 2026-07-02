<?php
/**
 * Zaemit 그룹웨어 - 업무자료실/의뢰 API
 *
 * 액션:
 *   - listRequests  GET ?action=listRequests&category=hr_labor
 *   - getRequest    GET ?action=getRequest&id=N
 *   - createRequest POST (JSON) doc_name, category, description?, due_date?
 *   - cancelRequest POST (JSON) id
 *
 * 권한:
 *   - 조회: 로그인 사용자면 누구나
 *   - 생성: 로그인 사용자 (requester_id는 세션에서 주입)
 *   - 취소: 요청자 본인 또는 admin/manager
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) apiLegacyError('데이터베이스 연결 실패', 500);

$action = $_GET['action'] ?? '';

// category 컬럼이 없는 환경(마이그레이션 미실행)에서도 동작하도록 감지
$hasCategory = false;
try {
    $pdo->query("SELECT category FROM doc_requests LIMIT 1");
    $hasCategory = true;
} catch (PDOException $e) {
    $hasCategory = false;
}

try {
    match ($action) {
        'listRequests'  => listRequests($pdo, $hasCategory),
        'getRequest'    => getRequest($pdo, $hasCategory),
        'createRequest' => createRequest($pdo, $hasCategory),
        'cancelRequest' => cancelRequest($pdo),
        default         => apiLegacyError('알 수 없는 액션', 400),
    };
} catch (PDOException $e) {
    error_log('[Docs] ' . $e->getMessage());
    apiLegacyError('서버 오류가 발생했습니다.', 500);
}

function listRequests(PDO $pdo, bool $hasCategory): void
{
    $category = trim($_GET['category'] ?? '');
    $status   = trim($_GET['status'] ?? '');

    $where = ['1=1'];
    $params = [];
    if ($hasCategory && $category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    if ($status !== '' && in_array($status, ['요청중','업로드완료','확인완료','취소'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $sql = 'SELECT id, ' . ($hasCategory ? 'category, ' : "'general' AS category, ")
         . 'doc_name, description, due_date, status, requester_id, requested_at, completed_at
            FROM doc_requests
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY requested_at DESC, id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    apiLegacyOk(['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getRequest(PDO $pdo, bool $hasCategory): void
{
    $id = apiRequirePositiveInt('id', $_GET['id'] ?? 0);
    $sql = 'SELECT id, ' . ($hasCategory ? 'category, ' : "'general' AS category, ")
         . 'doc_name, description, due_date, status, requester_id, requested_at, completed_at
            FROM doc_requests WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) apiLegacyError('의뢰를 찾을 수 없습니다.', 404);

    // 업로드 파일 목록(있으면)
    try {
        $us = $pdo->prepare('SELECT id, file_name, file_size, uploaded_at FROM doc_uploads WHERE request_id = ? ORDER BY uploaded_at DESC');
        $us->execute([$id]);
        $row['uploads'] = $us->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $row['uploads'] = [];
    }

    apiLegacyOk(['request' => $row]);
}

function createRequest(PDO $pdo, bool $hasCategory): void
{
    $data = apiJsonInput();
    $docName = trim($data['doc_name'] ?? '');
    if ($docName === '') apiLegacyError('요청 서류명을 입력해주세요.', 400);

    $category = trim($data['category'] ?? 'general');
    $desc     = trim($data['description'] ?? '');
    $dueDate  = trim($data['due_date'] ?? '');
    if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        apiLegacyError('제출 기한 형식이 올바르지 않습니다. (YYYY-MM-DD)', 400);
    }

    $requesterId = apiSessionUserId() ?: 1;

    if ($hasCategory) {
        $sql = 'INSERT INTO doc_requests (requester_id, company_id, category, doc_name, description, due_date, status)
                VALUES (?, 1, ?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requesterId, $category, $docName, $desc ?: null, $dueDate ?: null, '요청중']);
    } else {
        // 마이그레이션 미실행 환경 · description 앞에 [category] prefix
        $prefixed = '[' . $category . '] ' . $desc;
        $sql = 'INSERT INTO doc_requests (requester_id, company_id, doc_name, description, due_date, status)
                VALUES (?, 1, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requesterId, $docName, $prefixed, $dueDate ?: null, '요청중']);
    }

    apiLegacyOk(['id' => (int)$pdo->lastInsertId(), 'message' => '의뢰가 접수되었습니다.'], 201);
}

function cancelRequest(PDO $pdo): void
{
    $data = apiJsonInput();
    $id = apiRequirePositiveInt('id', $data['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT requester_id, status FROM doc_requests WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) apiLegacyError('의뢰를 찾을 수 없습니다.', 404);
    if ($row['status'] === '취소') apiLegacyError('이미 취소된 의뢰입니다.', 400);
    if ($row['status'] === '확인완료') apiLegacyError('완료된 의뢰는 취소할 수 없습니다.', 400);

    $self = apiSessionUserId();
    if ((int)$row['requester_id'] !== $self && !apiIsAdminOrManager()) {
        apiLegacyError('본인 의뢰만 취소할 수 있습니다.', 403);
    }

    $pdo->prepare("UPDATE doc_requests SET status='취소', completed_at=NOW() WHERE id = ?")->execute([$id]);
    apiLegacyOk(['message' => '취소되었습니다.']);
}

// api_common.php의 apiOk/apiError 는 새 규약 · 이 API는 기존 업무자료실 UI와 정합 위해 레거시 형식 추가 제공
function apiLegacyOk(array $data = [], int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}
