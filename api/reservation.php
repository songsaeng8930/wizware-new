<?php
/**
 * Zaemit 그룹웨어 - 자원예약 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getResources':     getResources();     break;
    case 'getReservations':  getReservations();  break;
    case 'createReservation': createReservation(); break;
    case 'deleteReservation': deleteReservation(); break;
    case 'updateMaxCount':   updateMaxCount();   break;
    default:
        respond(400, ['error' => '알 수 없는 액션']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== 자원 목록 (공통코드 + max_count) ==========
function getResources(): void
{
    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    }
    try {
        $stmt = $pdo->query("
            SELECT i.id, i.name, COALESCE(c.max_count, 1) AS max_count
            FROM common_code_items i
            JOIN common_code_groups g ON i.group_id = g.id
            LEFT JOIN reservation_resource_config c ON c.item_id = i.id
            WHERE g.module = 'reservation' AND i.is_active = 1
            ORDER BY i.sort_order, i.id
        ");
        respond(200, ['resources' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        error_log('reservation getResources error: ' . $e->getMessage());
        respond(500, ['error' => '자원 목록을 불러오는 중 오류가 발생했습니다.']);
    }
}

// ========== 날짜별 예약 목록 ==========
function getReservations(): void
{
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        respond(400, ['error' => '날짜 형식 오류']);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    }
    try {
        $stmt = $pdo->prepare("
            SELECT id, resource_item_id, title, user_name,
                   DATE_FORMAT(start_time, '%H:%i') AS start_time,
                   DATE_FORMAT(end_time,   '%H:%i') AS end_time,
                   description, status
            FROM reservations
            WHERE reservation_date = ? AND status = 'confirmed'
            ORDER BY start_time
        ");
        $stmt->execute([$date]);
        respond(200, ['reservations' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        error_log('reservation getReservations error: ' . $e->getMessage());
        respond(500, ['error' => '예약 목록을 불러오는 중 오류가 발생했습니다.']);
    }
}

// ========== 예약 생성 (용량 초과 검사 포함) ==========
function createReservation(): void
{
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $resourceItemId = (int)($data['resource_item_id'] ?? 0);
    $title          = trim($data['title'] ?? '');
    $userName       = trim($_SESSION['user']['name'] ?? $data['user_name'] ?? '');
    $date           = $data['date'] ?? '';
    $startTime      = $data['start_time'] ?? '';
    $endTime        = $data['end_time'] ?? '';
    $description    = trim($data['description'] ?? '');

    if (!$resourceItemId || !$title || !$date || !$startTime || !$endTime) {
        respond(400, ['error' => '필수 항목을 입력해주세요.']);
    }
    if ($startTime >= $endTime) {
        respond(400, ['error' => '종료 시간은 시작 시간보다 늦어야 합니다.']);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    }

    try {
        $pdo->beginTransaction();

        // 최대 점유 수 조회
        $stmt = $pdo->prepare("
            SELECT COALESCE(c.max_count, 1) AS max_count
            FROM common_code_items i
            LEFT JOIN reservation_resource_config c ON c.item_id = i.id
            WHERE i.id = ?
        ");
        $stmt->execute([$resourceItemId]);
        $row = $stmt->fetch();
        $maxCount = $row ? (int)$row['max_count'] : 1;

        // 겹치는 예약 수 조회
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM reservations
            WHERE resource_item_id = ?
              AND reservation_date = ?
              AND status = 'confirmed'
              AND start_time < ?
              AND end_time > ?
        ");
        $stmt->execute([$resourceItemId, $date, $endTime, $startTime]);
        $currentCount = (int)$stmt->fetch()['cnt'];

        if ($currentCount >= $maxCount) {
            $pdo->rollBack();
            respond(409, [
                'error'      => "해당 시간에 최대 점유 수({$maxCount})를 초과하였습니다.",
                'type'       => 'capacity_exceeded',
                'max_count'  => $maxCount,
                'current'    => $currentCount,
            ]);
        }

        // 예약 저장
        $stmt = $pdo->prepare("
            INSERT INTO reservations (resource_item_id, title, user_name, reservation_date, start_time, end_time, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$resourceItemId, $title, $userName, $date, $startTime . ':00', $endTime . ':00', $description ?: null]);
        $newId = (int)$pdo->lastInsertId();

        $pdo->commit();
        respond(200, ['success' => true, 'id' => $newId, 'message' => '예약이 완료되었습니다.']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('reservation createReservation error: ' . $e->getMessage());
        respond(500, ['error' => '예약 처리 중 오류가 발생했습니다.']);
    }
}

// ========== 예약 취소 ==========
function deleteReservation(): void
{
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($data['id'] ?? 0);

    if (!$id) {
        respond(400, ['error' => '잘못된 요청입니다.']);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    }
    try {
        $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        respond(200, ['success' => true, 'message' => '예약이 취소되었습니다.']);
    } catch (PDOException $e) {
        error_log('reservation deleteReservation error: ' . $e->getMessage());
        respond(500, ['error' => '취소 처리 중 오류가 발생했습니다.']);
    }
}

// ========== 자원 최대 점유 수 업데이트 ==========
function updateMaxCount(): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $itemId   = (int)($data['item_id'] ?? 0);
    $maxCount = max(1, (int)($data['max_count'] ?? 1));

    if (!$itemId) {
        respond(400, ['error' => '잘못된 요청입니다.']);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    }
    try {
        $pdo->prepare("
            INSERT INTO reservation_resource_config (item_id, max_count)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE max_count = ?
        ")->execute([$itemId, $maxCount, $maxCount]);
        respond(200, ['success' => true]);
    } catch (PDOException $e) {
        error_log('reservation updateMaxCount error: ' . $e->getMessage());
        respond(500, ['error' => '저장 중 오류가 발생했습니다.']);
    }
}
