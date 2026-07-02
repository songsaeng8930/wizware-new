<?php
/**
 * 회사 용어 설정 V2 — 데이터 마이그레이션 스크립트
 *
 * DDL 먼저 실행:  mysql -u root zaemit_groupware < db/migrate_terminology.sql
 * 데이터 시딩:    php db/migrate_terminology_data.php
 *
 * 멱등 실행 가능 (INSERT IGNORE + 존재 체크)
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    fwrite(STDERR, "[ERROR] DB 연결 실패\n");
    exit(1);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$report = ['org_levels' => 0, 'hr_ranks' => 0, 'hr_duties' => 0, 'display_config' => 0, 'rank_backfill' => 0, 'duty_backfill' => 0, 'dept_level' => 0];


// ============================================================
// 1. org_levels ← config/org_hierarchy.json
// ============================================================
echo "[1/7] org_levels 시딩...\n";

$configFile = __DIR__ . '/../config/org_hierarchy.json';
$levels = [];
if (file_exists($configFile)) {
    $json = json_decode(file_get_contents($configFile), true);
    $levels = $json['levels'] ?? [];
}
if (empty($levels)) {
    $levels = [
        ['depth' => 0, 'key' => 'company',    'label' => '회사', 'head_title' => '대표',   'enabled' => true],
        ['depth' => 1, 'key' => 'division',   'label' => '본부', 'head_title' => '본부장', 'enabled' => true],
        ['depth' => 2, 'key' => 'department', 'label' => '부서', 'head_title' => '부서장', 'enabled' => true],
    ];
}

$requiredKeys = ['company', 'division', 'department'];
$stmt = $pdo->prepare(
    "INSERT IGNORE INTO org_levels (depth, key_name, label, head_title, is_enabled, is_required)
     VALUES (:depth, :key_name, :label, :head_title, :is_enabled, :is_required)"
);

foreach ($levels as $lv) {
    $key = $lv['key'] ?? '';
    if ($key === '') continue;

    $stmt->execute([
        ':depth'       => (int)($lv['depth'] ?? 0),
        ':key_name'    => $key,
        ':label'       => $lv['label'] ?? $key,
        ':head_title'  => $lv['head_title'] ?? '',
        ':is_enabled'  => (int)($lv['enabled'] ?? true),
        ':is_required' => in_array($key, $requiredKeys, true) ? 1 : 0,
    ]);
    if ($stmt->rowCount() > 0) $report['org_levels']++;
}
echo "  → {$report['org_levels']}건 추가\n";


// ============================================================
// 2. hr_ranks ← common_code_items (직급 그룹)
// ============================================================
echo "[2/7] hr_ranks 시딩...\n";

try {
    $rankRows = $pdo->query(
        "SELECT ci.code, ci.name, ci.sort_order
         FROM common_code_items ci
         JOIN common_code_groups cg ON ci.group_id = cg.id
         WHERE cg.module = 'hr' AND cg.name = '직급'
         ORDER BY ci.sort_order"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[마이그레이션] common_code_items 직급 조회 실패, 기본값 사용: ' . $e->getMessage());
    $rankRows = [];
}

if (empty($rankRows)) {
    $rankRows = [
        ['code' => 'CEO', 'name' => '대표이사', 'sort_order' => 1],
        ['code' => 'DIR', 'name' => '이사',     'sort_order' => 2],
        ['code' => 'GM',  'name' => '부장',     'sort_order' => 3],
        ['code' => 'DGM', 'name' => '차장',     'sort_order' => 4],
        ['code' => 'MGR', 'name' => '과장',     'sort_order' => 5],
        ['code' => 'AM',  'name' => '대리',     'sort_order' => 6],
        ['code' => 'SR',  'name' => '주임',     'sort_order' => 7],
        ['code' => 'STF', 'name' => '사원',     'sort_order' => 8],
        ['code' => 'INT', 'name' => '인턴',     'sort_order' => 9],
    ];
}

$stmtRank = $pdo->prepare(
    "INSERT IGNORE INTO hr_ranks (name, code, sort_order) VALUES (:name, :code, :sort_order)"
);
foreach ($rankRows as $r) {
    $stmtRank->execute([':name' => $r['name'], ':code' => $r['code'], ':sort_order' => (int)$r['sort_order']]);
    if ($stmtRank->rowCount() > 0) $report['hr_ranks']++;
}
echo "  → {$report['hr_ranks']}건 추가\n";


// ============================================================
// 3. hr_duties ← common_code_items (직책 그룹)
// ============================================================
echo "[3/7] hr_duties 시딩...\n";

try {
    $dutyRows = $pdo->query(
        "SELECT ci.code, ci.name, ci.sort_order
         FROM common_code_items ci
         JOIN common_code_groups cg ON ci.group_id = cg.id
         WHERE cg.module = 'hr' AND cg.name = '직책'
         ORDER BY ci.sort_order"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[마이그레이션] common_code_items 직책 조회 실패, 기본값 사용: ' . $e->getMessage());
    $dutyRows = [];
}

if (empty($dutyRows)) {
    $dutyRows = [
        ['code' => 'CEO',  'name' => 'CEO',    'sort_order' => 1],
        ['code' => 'CTO',  'name' => 'CTO',    'sort_order' => 2],
        ['code' => 'CFO',  'name' => 'CFO',    'sort_order' => 3],
        ['code' => 'COO',  'name' => 'COO',    'sort_order' => 4],
        ['code' => 'HEAD', 'name' => '본부장', 'sort_order' => 5],
        ['code' => 'TL',   'name' => '팀장',   'sort_order' => 6],
        ['code' => 'PL',   'name' => '파트장', 'sort_order' => 7],
    ];
}

$stmtDuty = $pdo->prepare(
    "INSERT IGNORE INTO hr_duties (name, code, sort_order) VALUES (:name, :code, :sort_order)"
);
foreach ($dutyRows as $d) {
    $stmtDuty->execute([':name' => $d['name'], ':code' => $d['code'], ':sort_order' => (int)$d['sort_order']]);
    if ($stmtDuty->rowCount() > 0) $report['hr_duties']++;
}

try {
    $extraTitles = $pdo->query(
        "SELECT DISTINCT e.title FROM employees e
         WHERE e.title IS NOT NULL AND e.title != ''
           AND e.title NOT IN (SELECT name FROM hr_duties)"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('[마이그레이션] employees.title 조회 실패: ' . $e->getMessage());
    $extraTitles = [];
}

$extraOrder = 100;
foreach ($extraTitles as $title) {
    $stmtDuty->execute([':name' => $title, ':code' => null, ':sort_order' => $extraOrder++]);
    if ($stmtDuty->rowCount() > 0) $report['hr_duties']++;
}
echo "  → {$report['hr_duties']}건 추가\n";


// ============================================================
// 4. terminology_display_config 기본값
// ============================================================
echo "[4/7] terminology_display_config 시딩...\n";

$configs = [
    ['context_key' => 'default',   'format_pattern' => '{name} {rank}',                          'suffix' => null],
    ['context_key' => 'org_chart', 'format_pattern' => '{name} {duty}',                          'suffix' => null],
    ['context_key' => 'approval',  'format_pattern' => '{name} {rank}',                          'suffix' => null],
    ['context_key' => 'board',     'format_pattern' => '{name}{suffix}',                         'suffix' => '님'],
    ['context_key' => 'profile',   'format_pattern' => '{name} {rank} / {dept} {duty}',          'suffix' => null],
];

$stmtCfg = $pdo->prepare(
    "INSERT IGNORE INTO terminology_display_config (context_key, format_pattern, suffix)
     VALUES (:context_key, :format_pattern, :suffix)"
);
foreach ($configs as $c) {
    $stmtCfg->execute($c);
    if ($stmtCfg->rowCount() > 0) $report['display_config']++;
}
echo "  → {$report['display_config']}건 추가\n";


// ============================================================
// 5. employees.rank_id 백필 (position 텍스트 → hr_ranks.name)
// ============================================================
echo "[5/7] employees.rank_id 백필...\n";

$updated = $pdo->exec(
    "UPDATE employees e
     JOIN hr_ranks r ON r.name = e.position
     SET e.rank_id = r.id
     WHERE e.rank_id IS NULL AND e.position IS NOT NULL AND e.position != ''"
);
$report['rank_backfill'] = (int)$updated;
echo "  → {$report['rank_backfill']}건 매핑\n";

$unmappedRanks = $pdo->query(
    "SELECT id, name, position FROM employees
     WHERE rank_id IS NULL AND position IS NOT NULL AND position != ''"
)->fetchAll(PDO::FETCH_ASSOC);
if (!empty($unmappedRanks)) {
    echo "  [주의] 매핑 실패 " . count($unmappedRanks) . "건:\n";
    foreach ($unmappedRanks as $u) {
        echo "    - id={$u['id']} {$u['name']}: \"{$u['position']}\" (hr_ranks에 없음)\n";
    }
}


// ============================================================
// 6. employees.duty_id 백필 (title 텍스트 → hr_duties.name)
// ============================================================
echo "[6/7] employees.duty_id 백필...\n";

$updated = $pdo->exec(
    "UPDATE employees e
     JOIN hr_duties d ON d.name = e.title
     SET e.duty_id = d.id
     WHERE e.duty_id IS NULL AND e.title IS NOT NULL AND e.title != ''"
);
$report['duty_backfill'] = (int)$updated;
echo "  → {$report['duty_backfill']}건 매핑\n";

$unmappedDuties = $pdo->query(
    "SELECT id, name, title FROM employees
     WHERE duty_id IS NULL AND title IS NOT NULL AND title != ''"
)->fetchAll(PDO::FETCH_ASSOC);
if (!empty($unmappedDuties)) {
    echo "  [주의] 매핑 실패 " . count($unmappedDuties) . "건:\n";
    foreach ($unmappedDuties as $u) {
        echo "    - id={$u['id']} {$u['name']}: \"{$u['title']}\" (hr_duties에 없음)\n";
    }
}


// ============================================================
// 7. departments.level_id 백필 (트리 depth → org_levels.depth)
// ============================================================
echo "[7/7] departments.level_id 백필...\n";

$levelByDepth = [];
$lvRows = $pdo->query("SELECT id, depth FROM org_levels ORDER BY depth")->fetchAll(PDO::FETCH_ASSOC);
foreach ($lvRows as $lv) {
    $levelByDepth[(int)$lv['depth']] = (int)$lv['id'];
}

$depts = $pdo->query("SELECT id, parent_id FROM departments ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$parentMap = [];
foreach ($depts as $d) {
    $parentMap[(int)$d['id']] = $d['parent_id'] !== null ? (int)$d['parent_id'] : null;
}

function computeDepth(int $id, array $parentMap): int {
    $depth = 0;
    $current = $id;
    $visited = [];
    while (isset($parentMap[$current]) && $parentMap[$current] !== null) {
        if (isset($visited[$current])) {
            fwrite(STDERR, "  [경고] 순환 parent_id 감지: dept_id={$id}, loop at {$current}\n");
            return -1;
        }
        $visited[$current] = true;
        $depth++;
        $current = $parentMap[$current];
    }
    return $depth;
}

$stmtLevel = $pdo->prepare("UPDATE departments SET level_id = :level_id WHERE id = :id AND level_id IS NULL");
foreach ($depts as $d) {
    $depth = computeDepth((int)$d['id'], $parentMap);
    if ($depth < 0) continue;
    $levelId = $levelByDepth[$depth] ?? null;
    if ($levelId !== null) {
        $stmtLevel->execute([':level_id' => $levelId, ':id' => $d['id']]);
        if ($stmtLevel->rowCount() > 0) $report['dept_level']++;
    }
}
echo "  → {$report['dept_level']}건 매핑\n";


// ============================================================
// 결과 요약
// ============================================================
echo "\n========== 마이그레이션 완료 ==========\n";
echo "org_levels:      {$report['org_levels']}건 추가\n";
echo "hr_ranks:        {$report['hr_ranks']}건 추가\n";
echo "hr_duties:       {$report['hr_duties']}건 추가\n";
echo "display_config:  {$report['display_config']}건 추가\n";
echo "rank_id 백필:    {$report['rank_backfill']}건\n";
echo "duty_id 백필:    {$report['duty_backfill']}건\n";
echo "dept level_id:   {$report['dept_level']}건\n";

$totalEmp = (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$mappedRank = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE rank_id IS NOT NULL")->fetchColumn();
$mappedDuty = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE duty_id IS NOT NULL")->fetchColumn();
echo "\n직급 매핑률: {$mappedRank}/{$totalEmp} (" . ($totalEmp > 0 ? round($mappedRank / $totalEmp * 100) : 0) . "%)\n";
echo "직책 매핑률: {$mappedDuty}/{$totalEmp} (" . ($totalEmp > 0 ? round($mappedDuty / $totalEmp * 100) : 0) . "%)\n";
