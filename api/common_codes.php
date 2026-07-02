<?php
/**
 * Zaemit 그룹웨어 - 공통코드 설정 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'getGroups'      => getGroups($pdo),
        'getGroup'       => getGroup($pdo),
        'saveGroup'      => saveGroup($pdo),
        'deleteGroup'    => deleteGroup($pdo),
        'saveItems'      => saveItems($pdo),
        'reorderItems'   => reorderItems($pdo),
        'reorderGroups'  => reorderGroups($pdo),
        'toggleItem'     => toggleItem($pdo),
        'deleteItem'     => deleteItem($pdo),
        'checkItemUsage' => checkItemUsage($pdo),
        default          => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (PDOException $e) {
    error_log('CommonCodes API: ' . $e->getMessage());
    respond(500, ['error' => '서버 오류가 발생했습니다.']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

// 모듈별 그룹 목록
function getGroups(PDO $pdo): void
{
    $module = $_GET['module'] ?? '';
    if (!$module) respond(400, ['error' => '모듈을 지정해주세요.']);

    $stmt = $pdo->prepare('
        SELECT g.*, (SELECT COUNT(*) FROM common_code_items WHERE group_id = g.id AND is_active = 1) AS item_count
        FROM common_code_groups g
        WHERE g.module = ? AND g.is_active = 1
        ORDER BY g.sort_order, g.id
    ');
    $stmt->execute([$module]);
    respond(200, ['groups' => $stmt->fetchAll()]);
}

// 그룹 상세 + 항목
function getGroup(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare('SELECT * FROM common_code_groups WHERE id = ?');
    $stmt->execute([$id]);
    $group = $stmt->fetch();
    if (!$group) respond(404, ['error' => '공통정보를 찾을 수 없습니다.']);

    $itemStmt = $pdo->prepare('SELECT id, group_id, IFNULL(code,"") AS code, name, sort_order, is_active FROM common_code_items WHERE group_id = ? ORDER BY sort_order, id');
    $itemStmt->execute([$id]);
    $items = $itemStmt->fetchAll();

    $refCounts = getGroupItemRefCounts($pdo, $group['module'], $group['name'], $items);
    foreach ($items as &$item) {
        $item['ref_count'] = $refCounts[(int)$item['id']] ?? 0;
    }
    unset($item);

    $group['items'] = $items;
    respond(200, ['group' => $group]);
}

// 그룹 저장 (생성/수정)
function saveGroup(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    if (!$name) respond(400, ['error' => '공통정보명을 입력해주세요.']);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE common_code_groups SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $data['description'] ?? '', $id]);
    } else {
        $module = $data['module'] ?? '';
        if (!$module) respond(400, ['error' => '모듈을 지정해주세요.']);
        $maxOrder = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM common_code_groups WHERE module = ?');
        $maxOrder->execute([$module]);
        $stmt = $pdo->prepare('INSERT INTO common_code_groups (module, name, description, sort_order) VALUES (?,?,?,?)');
        $stmt->execute([$module, $name, $data['description'] ?? '', $maxOrder->fetchColumn()]);
        $id = (int)$pdo->lastInsertId();
    }
    respond(200, ['id' => $id, 'message' => '저장되었습니다.']);
}

// 그룹 삭제
function deleteGroup(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    // 하위 항목 중 참조 중인 것이 있는지 확인
    $gStmt = $pdo->prepare('SELECT module, name FROM common_code_groups WHERE id = ?');
    $gStmt->execute([$id]);
    $groupInfo = $gStmt->fetch();
    if ($groupInfo) {
        $iStmt = $pdo->prepare('SELECT id, code, name FROM common_code_items WHERE group_id = ? AND is_active = 1');
        $iStmt->execute([$id]);
        foreach ($iStmt->fetchAll() as $item) {
            $refs = checkItemRefs($pdo, array_merge($item, [
                'module'     => $groupInfo['module'],
                'group_name' => $groupInfo['name'],
            ]));
            if (!empty($refs)) {
                $detail = implode(', ', array_map(fn($r) => "{$r['label']}({$r['count']}건)", $refs));
                respond(400, ['error' => "'{$item['name']}' 항목이 사용 중이어서 그룹을 삭제할 수 없습니다 ({$detail})."]);
            }
        }
    }

    $pdo->prepare('UPDATE common_code_groups SET is_active = 0 WHERE id = ?')->execute([$id]);
    respond(200, ['message' => '삭제되었습니다.']);
}

// 항목 전체 저장 (추가/수정 일괄)
function saveItems(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $groupId = (int)($data['group_id'] ?? 0);
    $items = $data['items'] ?? [];
    if ($groupId <= 0) respond(400, ['error' => '유효하지 않은 그룹 ID']);

    // 이름 중복 검증
    $names = [];
    foreach ($items as $item) {
        $name = trim($item['name'] ?? '');
        if ($name === '') continue;
        if (isset($names[$name])) {
            respond(400, ['error' => "항목명 '{$name}'이(가) 중복됩니다. 같은 그룹 내에서 항목명은 고유해야 합니다."]);
        }
        $names[$name] = true;
    }

    // 코드 중복 검증
    $codes = [];
    foreach ($items as $item) {
        $code = strtoupper(trim($item['code'] ?? ''));
        if ($code === '') continue;
        if (isset($codes[$code])) {
            respond(400, ['error' => "코드 '{$code}'가 중복됩니다. 같은 그룹 내에서 코드는 고유해야 합니다."]);
        }
        $codes[$code] = true;
    }

    // 기존 항목 ID 수집
    $existing = $pdo->prepare('SELECT id FROM common_code_items WHERE group_id = ?');
    $existing->execute([$groupId]);
    $existingIds = array_column($existing->fetchAll(), 'id');

    // 유지 대상 ID 수집 (DB 작업 전 참조 확인용)
    $keepIds = [];
    foreach ($items as $item) {
        $itemId = (int)($item['id'] ?? 0);
        if ($itemId > 0 && in_array($itemId, $existingIds)) {
            $keepIds[] = $itemId;
        }
    }

    $removeIds = array_diff($existingIds, $keepIds);

    $pdo->beginTransaction();
    try {
        // 삭제 대상 참조 확인 (트랜잭션 내에서 경쟁 조건 방지)
        if (!empty($removeIds)) {
            $gStmt = $pdo->prepare('SELECT module, name FROM common_code_groups WHERE id = ?');
            $gStmt->execute([$groupId]);
            $groupInfo = $gStmt->fetch();

            if ($groupInfo) {
                $ph = implode(',', array_fill(0, count($removeIds), '?'));
                $rStmt = $pdo->prepare("SELECT id, code, name FROM common_code_items WHERE id IN ($ph)");
                $rStmt->execute(array_values($removeIds));

                foreach ($rStmt->fetchAll() as $ri) {
                    $refs = checkItemRefs($pdo, array_merge($ri, [
                        'module'     => $groupInfo['module'],
                        'group_name' => $groupInfo['name'],
                    ]));
                    if (!empty($refs)) {
                        $pdo->rollBack();
                        $detail = implode(', ', array_map(fn($r) => "{$r['label']}({$r['count']}건)", $refs));
                        respond(400, ['error' => "'{$ri['name']}' 항목이 사용 중이어서 삭제할 수 없습니다 ({$detail}). 비활성(OFF)으로 전환해주세요."]);
                    }
                }
            }
        }

        // 이름 변경 cascade를 위해 그룹 정보 + 기존 항목 이름 수집
        $gStmt2 = $pdo->prepare('SELECT module, name FROM common_code_groups WHERE id = ?');
        $gStmt2->execute([$groupId]);
        $groupMeta = $gStmt2->fetch();

        $oldNames = [];
        if ($groupMeta) {
            $onStmt = $pdo->prepare('SELECT id, name, code FROM common_code_items WHERE group_id = ? FOR UPDATE');
            $onStmt->execute([$groupId]);
            foreach ($onStmt->fetchAll() as $row) {
                $oldNames[(int)$row['id']] = $row;
            }
        }

        foreach ($items as $i => $item) {
            $itemId = (int)($item['id'] ?? 0);
            $name = trim($item['name'] ?? '');
            if ($name === '') continue;
            $isActive = isset($item['is_active']) ? (int)$item['is_active'] : 1;
            $code = trim($item['code'] ?? '');

            if ($itemId > 0 && in_array($itemId, $existingIds)) {
                // 이름 변경 시 참조 테이블도 cascade update
                if ($groupMeta && isset($oldNames[$itemId]) && $oldNames[$itemId]['name'] !== $name) {
                    cascadeNameUpdate($pdo, $groupMeta, $oldNames[$itemId], $name);
                }
                $pdo->prepare('UPDATE common_code_items SET code = ?, name = ?, sort_order = ?, is_active = ? WHERE id = ?')
                    ->execute([$code, $name, $i + 1, $isActive, $itemId]);
            } else {
                $pdo->prepare('INSERT INTO common_code_items (group_id, code, name, sort_order, is_active) VALUES (?,?,?,?,?)')
                    ->execute([$groupId, $code, $name, $i + 1, $isActive]);
            }
        }

        if (!empty($removeIds)) {
            $ph = implode(',', array_fill(0, count($removeIds), '?'));
            $pdo->prepare("DELETE FROM common_code_items WHERE id IN ($ph)")->execute(array_values($removeIds));
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    respond(200, ['message' => '항목이 저장되었습니다.']);
}

// 순서 변경
function reorderItems(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $orders = $data['orders'] ?? []; // [{id, sort_order}, ...]
    foreach ($orders as $o) {
        $pdo->prepare('UPDATE common_code_items SET sort_order = ? WHERE id = ?')
            ->execute([(int)$o['sort_order'], (int)$o['id']]);
    }
    respond(200, ['message' => '순서가 변경되었습니다.']);
}

// 항목 토글
function toggleItem(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);
    $pdo->prepare('UPDATE common_code_items SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
    respond(200, ['message' => '변경되었습니다.']);
}

// 항목 삭제
function deleteItem(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare('
        SELECT ci.id, ci.code, ci.name, cg.module, cg.name AS group_name
        FROM common_code_items ci
        JOIN common_code_groups cg ON ci.group_id = cg.id
        WHERE ci.id = ?
    ');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) respond(404, ['error' => '항목을 찾을 수 없습니다.']);

    $refs = checkItemRefs($pdo, $item);
    if (!empty($refs)) {
        $detail = implode(', ', array_map(fn($r) => "{$r['label']}({$r['count']}건)", $refs));
        respond(400, ['error' => "'{$item['name']}' 항목이 사용 중이어서 삭제할 수 없습니다 ({$detail}). 비활성(OFF)으로 전환해주세요."]);
    }

    $pdo->prepare('DELETE FROM common_code_items WHERE id = ?')->execute([$id]);
    respond(200, ['message' => '삭제되었습니다.']);
}

function _isAllowedRefTarget(string $table, string $col): bool
{
    static $allowed = [
        'employees.employment_type', 'employees.employment_status',
        'attendance_records.work_type', 'leave_requests.leave_type',
        'card_expenses.category',
        'schedules.category_item_id', 'schedule_category_config.item_id',
        'reservations.resource_item_id', 'reservation_resource_config.item_id',
    ];
    if (!in_array("{$table}.{$col}", $allowed, true)) {
        error_log("[common_codes] 허용되지 않은 참조 대상: {$table}.{$col}");
        return false;
    }
    return true;
}

// 공통코드 항목의 다른 테이블 참조 확인 (organization.php:checkEmployeeLinks 패턴)
function checkItemRefs(PDO $pdo, array $item): array
{
    $refMap = [
        'hr:고용형태'         => [['employees', 'employment_type', 'name', '직원 고용형태']],
        'hr:고용상태'         => [['employees', 'employment_status', 'name', '직원 고용상태']],
        'attendance:근무유형' => [['attendance_records', 'work_type', 'name', '근태 기록']],
        'attendance:휴가유형' => [['leave_requests', 'leave_type', 'code', '휴가 신청']],
        'card:비용항목'       => [['card_expenses', 'category', 'name', '카드 지출']],
        'schedule:일정유형'   => [
            ['schedules', 'category_item_id', 'id', '일정'],
            ['schedule_category_config', 'item_id', 'id', '일정 색상설정'],
        ],
        'reservation:자원목록' => [
            ['reservations', 'resource_item_id', 'id', '예약'],
            ['reservation_resource_config', 'item_id', 'id', '자원 설정'],
        ],
    ];

    $key = $item['module'] . ':' . $item['group_name'];
    $links = [];

    if (!isset($refMap[$key])) return $links;

    foreach ($refMap[$key] as [$table, $col, $matchField, $label]) {
        if (!_isAllowedRefTarget($table, $col)) continue;
        $matchValue = match ($matchField) {
            'name' => $item['name'],
            'code' => $item['code'],
            'id'   => $item['id'],
        };
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$col} = ?");
            $stmt->execute([$matchValue]);
            $cnt = (int)$stmt->fetchColumn();
            if ($cnt > 0) {
                $links[] = ['label' => $label, 'count' => $cnt];
            }
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S02') {
                error_log("[checkItemRefs] {$table}.{$col}: " . $e->getMessage());
            }
        }
    }

    return $links;
}

// 그룹 내 전체 항목의 참조 건수를 효율적으로 조회 (GROUP BY 1회/테이블)
function getGroupItemRefCounts(PDO $pdo, string $module, string $groupName, array $items): array
{
    $refMap = [
        'hr:고용형태'         => [['employees', 'employment_type', 'name']],
        'hr:고용상태'         => [['employees', 'employment_status', 'name']],
        'attendance:근무유형' => [['attendance_records', 'work_type', 'name']],
        'attendance:휴가유형' => [['leave_requests', 'leave_type', 'code']],
        'card:비용항목'       => [['card_expenses', 'category', 'name']],
        'schedule:일정유형'   => [['schedules', 'category_item_id', 'id'], ['schedule_category_config', 'item_id', 'id']],
        'reservation:자원목록' => [['reservations', 'resource_item_id', 'id'], ['reservation_resource_config', 'item_id', 'id']],
    ];

    $key = $module . ':' . $groupName;
    $counts = [];
    foreach ($items as $item) { $counts[(int)$item['id']] = 0; }
    if (!isset($refMap[$key])) return $counts;

    foreach ($refMap[$key] as [$table, $col, $matchField]) {
        if (!_isAllowedRefTarget($table, $col)) continue;
        $lookup = [];
        foreach ($items as $item) {
            $val = match ($matchField) {
                'name' => $item['name'],
                'code' => $item['code'] ?? '',
                'id'   => (string)$item['id'],
            };
            if ($val !== '') $lookup[$val] = (int)$item['id'];
        }
        if (empty($lookup)) continue;
        try {
            $stmt = $pdo->prepare("SELECT {$col}, COUNT(*) as cnt FROM {$table} GROUP BY {$col}");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $val = (string)$row[$col];
                if (isset($lookup[$val])) $counts[$lookup[$val]] += (int)$row['cnt'];
            }
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S02') {
                error_log("[getGroupItemRefCounts] {$table}.{$col}: " . $e->getMessage());
            }
        }
    }
    return $counts;
}

// 항목 이름 변경 시 참조 테이블 cascade update (checkItemRefs와 같은 $refMap 사용)
function cascadeNameUpdate(PDO $pdo, array $groupMeta, array $oldItem, string $newName): void
{
    $refMap = [
        'hr:고용형태'         => [['employees', 'employment_type', 'name']],
        'hr:고용상태'         => [['employees', 'employment_status', 'name']],
        'attendance:근무유형' => [['attendance_records', 'work_type', 'name']],
        'attendance:휴가유형' => [['leave_requests', 'leave_type', 'code']],
        'card:비용항목'       => [['card_expenses', 'category', 'name']],
    ];

    $key = $groupMeta['module'] . ':' . $groupMeta['name'];
    if (!isset($refMap[$key])) return;

    foreach ($refMap[$key] as [$table, $col, $matchField]) {
        if ($matchField !== 'name') continue;
        if (!_isAllowedRefTarget($table, $col)) continue;
        try {
            $pdo->prepare("UPDATE {$table} SET {$col} = ? WHERE {$col} = ?")
                ->execute([$newName, $oldItem['name']]);
        } catch (PDOException $e) {
            error_log("[common_codes] cascade update failed: {$table}.{$col} '{$oldItem['name']}' → '{$newName}': " . $e->getMessage());
        }
    }
}

// 항목 사용처 확인 API
function checkItemUsage(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare('
        SELECT ci.id, ci.code, ci.name, cg.module, cg.name AS group_name
        FROM common_code_items ci
        JOIN common_code_groups cg ON ci.group_id = cg.id
        WHERE ci.id = ?
    ');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) respond(404, ['error' => '항목을 찾을 수 없습니다.']);

    $links = checkItemRefs($pdo, $item);

    respond(200, [
        'item_id'   => (int)$item['id'],
        'item_name' => $item['name'],
        'links'     => $links,
        'hasLinks'  => !empty($links),
    ]);
}

// 그룹 순서 변경
function reorderGroups(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $modules = $data['modules'] ?? ($data['module'] ? [$data['module']] : []);
    if (is_string($modules)) $modules = [$modules];
    $groupIds = is_array($data['group_ids'] ?? null) ? $data['group_ids'] : [];
    $groupIds = array_values(array_filter(array_map('intval', $groupIds), fn($x) => $x > 0));

    if (empty($modules) || empty($groupIds)) {
        respond(400, ['error' => 'modules와 group_ids가 필요합니다.']);
    }

    $gph = implode(',', array_fill(0, count($groupIds), '?'));
    $mph = implode(',', array_fill(0, count($modules), '?'));
    $params = array_merge($modules, $groupIds);
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM common_code_groups WHERE module IN ($mph) AND id IN ($gph) AND is_active = 1");
    $cntStmt->execute($params);
    if ((int)$cntStmt->fetchColumn() !== count($groupIds)) {
        respond(400, ['error' => '지정 모듈에 속하지 않은 그룹이 포함되어 있습니다.']);
    }

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE common_code_groups SET sort_order = ? WHERE id = ?');
        $order = 10;
        foreach ($groupIds as $gid) {
            $upd->execute([$order, $gid]);
            $order += 10;
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    respond(200, ['message' => '그룹 순서가 변경되었습니다.']);
}
