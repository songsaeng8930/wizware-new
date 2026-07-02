<?php
/**
 * HR 공통코드 헬퍼
 *
 * 직급(hr_ranks)·직책(hr_duties) → 전용 테이블 조회
 * 고용형태·고용상태 등 기타 → common_code_items 경유
 */

require_once __DIR__ . '/../config/database.php';

function getHrCodeItems(string $groupName, ?string $includeValue = null): array
{
    $dedicatedTable = match ($groupName) {
        '직급' => 'hr_ranks',
        '직책' => 'hr_duties',
        default => null,
    };

    if ($dedicatedTable !== null) {
        return _queryHrDedicatedTable($dedicatedTable);
    }

    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        // 활성 항목 + 기존 값(비활성이더라도) 포함
        $sql = '
            SELECT ci.code, ci.name, ci.sort_order, ci.is_active
            FROM common_code_items ci
            INNER JOIN common_code_groups cg ON ci.group_id = cg.id
            WHERE cg.module = \'hr\' AND cg.name = ? AND (ci.is_active = 1' . ($includeValue !== null ? ' OR ci.name = ? OR ci.code = ?' : '') . ')
            ORDER BY ci.sort_order, ci.id
        ';
        $params = [$groupName];
        if ($includeValue !== null) { $params[] = $includeValue; $params[] = $includeValue; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getHrCodeItems error: ' . $e->getMessage());
        return [];
    }
}

function _queryHrDedicatedTable(string $table): array
{
    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query(
            "SELECT id, name, code, sort_order FROM {$table} WHERE is_active = 1 ORDER BY sort_order, id"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("_queryHrDedicatedTable({$table}) error: " . $e->getMessage());
        return [];
    }
}

function getHrCodeOptions(string $groupName, string $placeholder = '', bool $useIdValue = false): string
{
    $items = getHrCodeItems($groupName);
    $html = '';
    if ($placeholder !== '') {
        $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
    }
    foreach ($items as $item) {
        $val = $useIdValue && isset($item['id']) ? $item['id'] : $item['name'];
        $html .= '<option value="' . htmlspecialchars((string)$val) . '">'
            . htmlspecialchars($item['name']) . '</option>';
    }
    return $html;
}

/**
 * 범용 공통코드 조회 (모듈 불문)
 */
function getCommonCodeItems(string $module, string $groupName, ?string $includeValue = null): array
{
    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        $sql = '
            SELECT ci.id, ci.code, ci.name, ci.sort_order, ci.is_active
            FROM common_code_items ci
            INNER JOIN common_code_groups cg ON ci.group_id = cg.id
            WHERE cg.module = ? AND cg.name = ? AND (ci.is_active = 1' . ($includeValue !== null ? ' OR ci.name = ? OR ci.code = ?' : '') . ')
            ORDER BY ci.sort_order, ci.id
        ';
        $params = [$module, $groupName];
        if ($includeValue !== null) { $params[] = $includeValue; $params[] = $includeValue; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getCommonCodeItems({$module}:{$groupName}) error: " . $e->getMessage());
        return [];
    }
}

function getCommonCodeOptions(string $module, string $groupName, string $placeholder = '', ?string $includeValue = null, ?string $selectedValue = null): string
{
    $items = getCommonCodeItems($module, $groupName, $includeValue);
    $html = '';
    if ($placeholder !== '') {
        $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
    }
    foreach ($items as $item) {
        $sel = ($selectedValue !== null && $item['name'] === $selectedValue) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($item['name']) . '"' . $sel . '>'
            . htmlspecialchars($item['name']) . '</option>';
    }
    return $html;
}


/**
 * ID→이름 / 이름→ID 변환 (이중 기록용)
 */
function resolveRankName(PDO $pdo, int $rankId): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT name FROM hr_ranks WHERE id = ? AND is_active = 1");
        $stmt->execute([$rankId]);
        $name = $stmt->fetchColumn();
        return $name !== false ? $name : null;
    } catch (PDOException $e) {
        error_log('resolveRankName error: ' . $e->getMessage());
        return null;
    }
}

function resolveRankId(PDO $pdo, string $positionName): ?int
{
    if ($positionName === '') return null;
    try {
        $stmt = $pdo->prepare("SELECT id FROM hr_ranks WHERE name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$positionName]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (PDOException $e) {
        error_log('resolveRankId error: ' . $e->getMessage());
        return null;
    }
}

function resolveDutyName(PDO $pdo, int $dutyId): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT name FROM hr_duties WHERE id = ? AND is_active = 1");
        $stmt->execute([$dutyId]);
        $name = $stmt->fetchColumn();
        return $name !== false ? $name : null;
    } catch (PDOException $e) {
        error_log('resolveDutyName error: ' . $e->getMessage());
        return null;
    }
}

function resolveDutyId(PDO $pdo, string $titleName): ?int
{
    if ($titleName === '') return null;
    try {
        $stmt = $pdo->prepare("SELECT id FROM hr_duties WHERE name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$titleName]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (PDOException $e) {
        error_log('resolveDutyId error: ' . $e->getMessage());
        return null;
    }
}
