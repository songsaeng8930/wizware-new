<?php
/**
 * 조직 경로 헬퍼.
 *
 * 직원의 말단 조직(department_id)에서 최상위 조직까지의 경로를 공통 형식으로 만든다.
 * 화면은 조직명/책임자 호칭을 org_hierarchy 설정에서 표시하고, 업무 판단은 조직 ID와 경로로 한다.
 */

require_once __DIR__ . '/org_hierarchy.php';

function orgPathFromDepartmentList(array $departments, ?int $departmentId): array
{
    if (!$departmentId) return [];

    $byId = [];
    foreach ($departments as $dept) {
        $id = (int)($dept['id'] ?? 0);
        if ($id <= 0) continue;
        $byId[$id] = $dept;
    }

    $path = [];
    $seen = [];
    $currentId = (int)$departmentId;
    $guard = 0;

    while ($currentId > 0 && isset($byId[$currentId]) && $guard++ < 50) {
        if (isset($seen[$currentId])) break;
        $seen[$currentId] = true;

        $dept = $byId[$currentId];
        $path[] = [
            'id' => $currentId,
            'parent_id' => !empty($dept['parent_id']) ? (int)$dept['parent_id'] : null,
            'name' => (string)($dept['name'] ?? ''),
            'code' => (string)($dept['code'] ?? ''),
        ];
        $currentId = !empty($dept['parent_id']) ? (int)$dept['parent_id'] : 0;
    }

    return array_reverse($path);
}

function orgPathLabel(array $path, string $separator = ' / '): string
{
    $names = [];
    foreach ($path as $node) {
        $name = trim((string)($node['name'] ?? ''));
        if ($name !== '') $names[] = $name;
    }
    return implode($separator, $names);
}

function orgMapPathToLevels(array $path, ?array $hierarchy = null): array
{
    $hierarchy = $hierarchy ?? getOrgHierarchy();
    $levels = $hierarchy['levels'] ?? [];
    usort($levels, fn($a, $b) => (int)($a['depth'] ?? 0) <=> (int)($b['depth'] ?? 0));

    $mapped = [];
    foreach ($levels as $i => $level) {
        if (empty($level['enabled']) || !isset($path[$i])) continue;
        $key = (string)($level['key'] ?? '');
        if ($key === '') continue;

        $mapped[$key] = $path[$i] + [
            'level_key' => $key,
            'level_label' => (string)($level['label'] ?? ''),
            'head_title' => (string)($level['head_title'] ?? ''),
            'depth' => (int)($level['depth'] ?? $i),
        ];
    }
    return $mapped;
}

function orgFetchDepartments(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, parent_id, name, code, head_employee_id, sort_order
        FROM departments
        WHERE is_active = 1
        ORDER BY sort_order, name
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function orgPathByDepartmentId(PDO $pdo, ?int $departmentId): array
{
    return orgPathFromDepartmentList(orgFetchDepartments($pdo), $departmentId);
}

function attachOrgPathToEmployeeRows(PDO $pdo, array &$employees): void
{
    if (empty($employees)) return;

    $departments = orgFetchDepartments($pdo);
    foreach ($employees as &$employee) {
        $deptId = !empty($employee['department_id']) ? (int)$employee['department_id'] : null;
        $path = orgPathFromDepartmentList($departments, $deptId);
        $employee['org_path'] = $path;
        $employee['org_path_label'] = orgPathLabel($path);
        $employee['org_units'] = orgMapPathToLevels($path);
    }
    unset($employee);
}
