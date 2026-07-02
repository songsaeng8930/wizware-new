<?php
/**
 * 조직 용어 설정 API
 *
 * GET  ?action=get        : 계층 설정 조회
 * POST ?action=save       : 계층 설정 저장 (admin)
 * GET  ?action=getRanks      : 직급 목록
 * GET  ?action=getDuties     : 직책 목록
 * GET  ?action=getPositions  : 직위 목록
 * POST ?action=saveRank      : 직급 저장/수정 (admin)
 * POST ?action=saveDuty      : 직책 저장/수정 (admin)
 * POST ?action=savePosition  : 직위 저장/수정 (admin)
 * POST ?action=deleteRank    : 직급 삭제 (admin)
 * POST ?action=deleteDuty    : 직책 삭제 (admin)
 * POST ?action=deletePosition: 직위 삭제 (admin)
 * POST ?action=reorderRanks    : 직급 순서 변경 (admin)
 * POST ?action=reorderDuties   : 직책 순서 변경 (admin)
 * POST ?action=reorderPositions: 직위 순서 변경 (admin)
 * GET  ?action=getDisplayConfig  : 표시 형식 설정 조회
 * POST ?action=saveDisplayConfig : 표시 형식 설정 저장 (admin)
 *
 * 응답 규약: {ok, data, error:{code,message}}
 */

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/org_hierarchy.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        apiOk(['settings' => getOrgHierarchy(true)]);

    case 'getRanks':
        apiOk(['items' => _getHrItems('hr_ranks')]);

    case 'getDuties':
        apiOk(['items' => _getHrItems('hr_duties')]);

    case 'getPositions':
        apiOk(['items' => _getHrItems('hr_positions')]);

    case 'saveRank':
        _requirePostAdmin();
        _saveHrItem('hr_ranks');

    case 'saveDuty':
        _requirePostAdmin();
        _saveHrItem('hr_duties');

    case 'savePosition':
        _requirePostAdmin();
        _saveHrItem('hr_positions');

    case 'deleteRank':
        _requirePostAdmin();
        _deleteHrItem('hr_ranks', 'rank_id');

    case 'deleteDuty':
        _requirePostAdmin();
        _deleteHrItem('hr_duties', 'duty_id');

    case 'deletePosition':
        _requirePostAdmin();
        _deleteHrItem('hr_positions', 'position_id');

    case 'reorderRanks':
        _requirePostAdmin();
        _reorderHrItems('hr_ranks');

    case 'reorderDuties':
        _requirePostAdmin();
        _reorderHrItems('hr_duties');

    case 'reorderPositions':
        _requirePostAdmin();
        _reorderHrItems('hr_positions');

    case 'resetRanks':
        _requirePostAdmin();
        _resetHrItems('hr_ranks', 'rank_id');

    case 'resetDuties':
        _requirePostAdmin();
        _resetHrItems('hr_duties', 'duty_id');

    case 'resetPositions':
        _requirePostAdmin();
        _resetHrItems('hr_positions', 'position_id');

    case 'saveTitleSystem':
        _requirePostAdmin();
        _saveTitleSystem();

    case 'getDisplayConfig':
        _getDisplayConfig();

    case 'saveDisplayConfig':
        _requirePostAdmin();
        _saveDisplayConfig();

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('METHOD_NOT_ALLOWED', 'POST 요청만 허용됩니다.', 405);
        }
        apiRequireAdmin();

        $input = apiJsonInput();
        if (empty($input['levels']) || !is_array($input['levels'])) {
            apiError('INVALID_INPUT', 'levels 배열이 필요합니다.');
        }

        $errors = validateOrgHierarchyPayload($input);
        if ($errors !== []) {
            $first = $errors[0];
            if ($first === 'DUPLICATE_KEY') {
                apiError('DUPLICATE_KEY', '시스템 키가 중복되었습니다.');
            }
            if ($first === 'TOO_MANY_LEVELS') {
                apiError('TOO_MANY_LEVELS', '조직 계층은 최대 ' . ORG_HIERARCHY_MAX_LEVELS . '단계까지 설정할 수 있습니다.');
            }
            if (str_starts_with($first, 'REQUIRED_KEY_MISSING:')) {
                apiError('REQUIRED_KEY_MISSING', '기본 조직 단계는 삭제하거나 변경할 수 없습니다.');
            }
            if (str_starts_with($first, 'INVALID_KEY_FORMAT:')) {
                apiError('INVALID_KEY_FORMAT', '시스템 키는 영문 소문자로 시작하고 영문 소문자, 숫자, 밑줄만 사용할 수 있습니다.');
            }
            if (str_starts_with($first, 'INVALID_CHARS:')) {
                apiError('INVALID_CHARS', '계층 이름이나 책임자 직책명에 사용할 수 없는 문자(<, >, ", \', &)가 포함되어 있습니다.');
            }
            apiError('INVALID_LEVEL', '계층 이름과 책임자 호칭을 모두 입력해주세요.');
        }

        $oldSlotMap = (getOrgHierarchy())['slot_map'] ?? [];

        if (!saveOrgHierarchy($input)) {
            apiError('SAVE_FAILED', '설정 저장에 실패했습니다.', 500);
        }

        $pdo = getDBConnection();
        if ($pdo) {
            require_once __DIR__ . '/../includes/approval_doc.php';
            $saved = getOrgHierarchy(true);
            migrateApprovalLineSlotKeys($pdo, $saved['levels'] ?? [], $oldSlotMap);
        }

        apiOk(['settings' => getOrgHierarchy(true)]);

    default:
        apiError('UNKNOWN_ACTION', '알 수 없는 action 입니다.', 400);
}


function _requirePostAdmin(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apiError('METHOD_NOT_ALLOWED', 'POST 요청만 허용됩니다.', 405);
    }
    apiRequireAdmin();
}

/* 테이블은 db/migrate_terminology.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */
function _ensureHrTables(PDO $pdo): void
{
    // no-op: 스키마는 db/migrate_terminology.sql 에서 관리
}

function _getHrItems(string $table): array
{
    $pdo = getDBConnection();
    if (!$pdo) return [];

    _ensureHrTables($pdo);
    $fkMap = ['hr_ranks' => 'rank_id', 'hr_duties' => 'duty_id', 'hr_positions' => 'position_id'];
    $fkColumn = $fkMap[$table] ?? 'rank_id';
    try {
        $stmt = $pdo->query(
            "SELECT t.id, t.name, t.code, t.sort_order, t.tier, t.is_active, "
            . "COALESCE(u.cnt, 0) AS usage_count "
            . "FROM {$table} t "
            . "LEFT JOIN (SELECT {$fkColumn} AS fk_id, COUNT(*) AS cnt FROM employees GROUP BY {$fkColumn}) u "
            . "ON u.fk_id = t.id "
            . "ORDER BY t.tier, t.sort_order, t.name"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        error_log("_getHrItems({$table}) error: " . $e->getMessage());
        return [];
    }
}

function _saveHrItem(string $table): void
{
    $pdo = getDBConnection();
    if (!$pdo) apiError('DB_ERROR', 'DB 연결 실패', 500);

    _ensureHrTables($pdo);
    $input = apiJsonInput();
    $name = trim($input['name'] ?? '');
    if ($name === '') apiError('INVALID_INPUT', '이름을 입력해주세요.');

    $id   = !empty($input['id']) ? (int)$input['id'] : null;
    $code = trim($input['code'] ?? '') ?: null;
    $sortOrder = (int)($input['sort_order'] ?? 0);
    $tier      = (int)($input['tier'] ?? $sortOrder ?: 1);
    $isActive  = (int)($input['is_active'] ?? 1);

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE {$table} SET name = :name, code = :code, sort_order = :sort_order, tier = :tier, is_active = :is_active WHERE id = :id");
            $stmt->execute(['name' => $name, 'code' => $code, 'sort_order' => $sortOrder, 'tier' => $tier, 'is_active' => $isActive, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO {$table} (name, code, sort_order, tier, is_active) VALUES (:name, :code, :so, :tier, :ia) "
                . "ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), tier = VALUES(tier), is_active = VALUES(is_active)"
            );
            $stmt->execute(['name' => $name, 'code' => $code, 'so' => $sortOrder, 'tier' => $tier, 'ia' => $isActive]);
            $id = (int)$pdo->lastInsertId();
            if (!$id) {
                $existing = $pdo->prepare("SELECT id FROM {$table} WHERE name = ?");
                $existing->execute([$name]);
                $id = (int)$existing->fetchColumn();
            }
        }
    } catch (\Throwable $e) {
        error_log("_saveHrItem({$table}) error: " . $e->getMessage());
        apiError('DB_ERROR', '저장 중 오류: ' . $e->getMessage(), 500);
    }

    apiOk(['id' => $id]);
}

function _deleteHrItem(string $table, string $fkColumn): void
{
    $pdo = getDBConnection();
    if (!$pdo) apiError('DB_ERROR', 'DB 연결 실패', 500);

    _ensureHrTables($pdo);
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('INVALID_INPUT', 'ID가 필요합니다.');

    try {
        $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE {$fkColumn} = ?");
        $usageStmt->execute([$id]);
        $usage = (int)$usageStmt->fetchColumn();
        if ($usage > 0) {
            apiError('IN_USE', "이 항목을 사용 중인 직원이 {$usage}명 있습니다. 먼저 해당 직원의 직급/직책을 변경해주세요.");
        }

        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
    } catch (\Throwable $e) {
        error_log("_deleteHrItem({$table}) error: " . $e->getMessage());
        apiError('DB_ERROR', '삭제 중 오류: ' . $e->getMessage(), 500);
    }

    apiOk(['deleted' => $stmt->rowCount()]);
}

function _resetHrItems(string $table, string $fkColumn): void
{
    $pdo = getDBConnection();
    if (!$pdo) apiError('DB_ERROR', 'DB 연결 실패', 500);

    _ensureHrTables($pdo);
    try {
        $usageStmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE {$fkColumn} IS NOT NULL");
        $usage = (int)$usageStmt->fetchColumn();
        if ($usage > 0) {
            $pdo->exec("UPDATE employees SET {$fkColumn} = NULL WHERE {$fkColumn} IS NOT NULL");
        }
        $deleted = $pdo->exec("DELETE FROM {$table}");
    } catch (\Throwable $e) {
        error_log("_resetHrItems({$table}) error: " . $e->getMessage());
        apiError('DB_ERROR', '초기화 중 오류: ' . $e->getMessage(), 500);
    }
    apiOk(['deleted' => $deleted]);
}

function _reorderHrItems(string $table): void
{
    $pdo = getDBConnection();
    if (!$pdo) apiError('DB_ERROR', 'DB 연결 실패', 500);

    _ensureHrTables($pdo);
    $input = apiJsonInput();
    $ids = $input['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) apiError('INVALID_INPUT', 'ids 배열이 필요합니다.');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE {$table} SET sort_order = :order, tier = :tier WHERE id = :id");
        $order = 1;
        foreach ($ids as $id) {
            $stmt->execute(['order' => $order, 'tier' => $order, 'id' => (int)$id]);
            $order++;
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log("_reorderHrItems({$table}) error: " . $e->getMessage());
        apiError('REORDER_FAILED', '순서 변경 중 오류가 발생했습니다.', 500);
    }

    apiOk(['reordered' => count($ids)]);
}


function _saveTitleSystem(): void
{
    $input = apiJsonInput();
    $system = trim($input['title_system'] ?? '');
    $valid = ['rank_and_duty', 'rank_duty_position', 'rank_only', 'duty_only', 'free_text', 'none'];
    if (!in_array($system, $valid, true)) {
        apiError('INVALID_INPUT', '유효하지 않은 직함 체계입니다.');
    }

    $file = __DIR__ . '/../config/org_hierarchy.json';
    $config = [];
    if (is_readable($file)) {
        $config = json_decode((string)file_get_contents($file), true) ?: [];
    }
    $config['title_system'] = $system;

    $ok = @file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    if ($ok === false) {
        apiError('SAVE_FAILED', '설정 파일 저장에 실패했습니다.', 500);
    }

    apiOk(['title_system' => $system]);
}


function _getDisplayConfig(): void
{
    require_once __DIR__ . '/../includes/terminology.php';

    $configs = getDisplayConfigs();

    $items = [];
    foreach ($configs as $key => $cfg) {
        $items[] = [
            'context_key'    => $key,
            'format_pattern' => $cfg['format_pattern'],
            'suffix'         => $cfg['suffix'] ?? null,
        ];
    }

    apiOk(['items' => $items]);
}


function _saveDisplayConfig(): void
{
    $pdo = getDBConnection();
    if (!$pdo) apiError('DB_ERROR', 'DB 연결 실패', 500);

    $input = apiJsonInput();
    $items = $input['items'] ?? [];
    if (!is_array($items) || empty($items)) {
        apiError('INVALID_INPUT', '설정 데이터가 필요합니다.');
    }

    $validContexts = ['default', 'org_chart', 'approval', 'board', 'profile'];
    $validTokens   = ['{name}', '{rank}', '{duty}', '{position}', '{dept}', '{suffix}'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO terminology_display_config (context_key, format_pattern, suffix)
             VALUES (:key, :pattern, :suffix)
             ON DUPLICATE KEY UPDATE format_pattern = VALUES(format_pattern), suffix = VALUES(suffix)"
        );

        foreach ($items as $item) {
            $key     = trim($item['context_key'] ?? '');
            $pattern = trim($item['format_pattern'] ?? '');
            $suffix  = isset($item['suffix']) && $item['suffix'] !== '' ? trim($item['suffix']) : null;

            if (!in_array($key, $validContexts, true)) continue;
            if ($pattern === '') $pattern = '{name} {rank}';

            $stmt->execute([
                'key'     => $key,
                'pattern' => $pattern,
                'suffix'  => $suffix,
            ]);
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log("_saveDisplayConfig error: " . $e->getMessage());
        apiError('DB_ERROR', '저장 중 오류: ' . $e->getMessage(), 500);
    }

    require_once __DIR__ . '/../includes/terminology.php';
    apiOk(['items' => getDisplayConfigs()]);
}
