<?php
/**
 * 조직 계층 용어 헬퍼
 *
 * 저장소: config/org_hierarchy.json (런타임 생성, .gitignore 대상)
 * 기본값: 회사(대표) → 본부(본부장) → 부서(부서장) 3단계
 * 최대 7단계까지 회사별 용어를 추가할 수 있다.
 *
 * 사용처:
 *  - includes/header.php              : window.ORG_LABELS 주입
 *  - includes/approval_doc.php        : resolveSlot() 슬롯 매핑
 *  - api/org_hierarchy.php            : 설정 조회/저장 API
 *  - pages/org_hierarchy_settings.php : 관리 페이지
 *  - pages/*.php                      : 필터/테이블/폼 라벨 동적 렌더링
 */

const ORG_HIERARCHY_DEFAULTS = [
    'levels' => [
        ['depth' => 0, 'key' => 'company',    'label' => '회사', 'head_title' => '대표',   'enabled' => true],
        ['depth' => 1, 'key' => 'division',   'label' => '본부', 'head_title' => '본부장', 'enabled' => true],
        ['depth' => 2, 'key' => 'department', 'label' => '부서', 'head_title' => '부서장', 'enabled' => true],
    ],
    'slot_map' => [
        '부서장' => 'department.head',
        '본부장' => 'division.head',
        '대표'   => 'company.head',
    ],
];

const ORG_HIERARCHY_REQUIRED_KEYS = ['company', 'division', 'department'];
const ORG_HIERARCHY_MAX_LEVELS = 7;

function orgHierarchyFile(): string
{
    return __DIR__ . '/../config/org_hierarchy.json';
}

function getOrgHierarchy(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) return $cache;

    $settings = ORG_HIERARCHY_DEFAULTS;
    $file = orgHierarchyFile();
    if (is_readable($file)) {
        $json = json_decode((string)file_get_contents($file), true);
        if (is_array($json)) {
            if (!empty($json['levels']) && is_array($json['levels'])) {
                $settings['levels'] = $json['levels'];
            }
            if (!empty($json['slot_map']) && is_array($json['slot_map'])) {
                $settings['slot_map'] = array_merge($settings['slot_map'], $json['slot_map']);
            }
            if (isset($json['title_system'])) {
                $settings['title_system'] = $json['title_system'];
            }
        }
    }
    $settings['title_system'] = $settings['title_system'] ?? 'rank_and_duty';

    foreach ($settings['levels'] as &$lv) {
        $lv['depth']      = (int)($lv['depth'] ?? 0);
        $lv['key']        = (string)($lv['key'] ?? '');
        $lv['label']      = (string)($lv['label'] ?? '');
        $lv['head_title'] = (string)($lv['head_title'] ?? '');
        $lv['enabled']    = (bool)($lv['enabled'] ?? true);
    }
    unset($lv);

    usort($settings['levels'], fn($a, $b) => $a['depth'] <=> $b['depth']);

    return $cache = $settings;
}

function validateOrgHierarchyPayload(array $data): array
{
    $errors = [];
    if (empty($data['levels']) || !is_array($data['levels'])) {
        return ['LEVELS_REQUIRED'];
    }
    if (count($data['levels']) > ORG_HIERARCHY_MAX_LEVELS) {
        $errors[] = 'TOO_MANY_LEVELS';
    }

    $keys = [];
    foreach ($data['levels'] as $i => $lv) {
        $key = trim((string)($lv['key'] ?? ''));
        $label = trim((string)($lv['label'] ?? ''));
        $headTitle = trim((string)($lv['head_title'] ?? ''));

        if ($key === '') $errors[] = 'INVALID_LEVEL_KEY:' . ($i + 1);
        if ($label === '') $errors[] = 'INVALID_LEVEL_LABEL:' . ($i + 1);
        if ($headTitle === '') $errors[] = 'INVALID_LEVEL_HEAD:' . ($i + 1);
        if ($key !== '' && !preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            $errors[] = 'INVALID_KEY_FORMAT:' . ($i + 1);
        }
        if (preg_match('/[<>"\'&]/', $label) || preg_match('/[<>"\'&]/', $headTitle)) {
            $errors[] = 'INVALID_CHARS:' . ($i + 1);
        }
        if ($key !== '') $keys[] = $key;
    }

    if (count($keys) !== count(array_unique($keys))) {
        $errors[] = 'DUPLICATE_KEY';
    }
    foreach (ORG_HIERARCHY_REQUIRED_KEYS as $requiredKey) {
        if (!in_array($requiredKey, $keys, true)) {
            $errors[] = 'REQUIRED_KEY_MISSING:' . $requiredKey;
        }
    }

    return $errors;
}

function getOrgLevels(bool $enabledOnly = false): array
{
    $levels = getOrgHierarchy()['levels'] ?? [];
    $filtered = [];
    foreach ($levels as $lv) {
        if ($enabledOnly && empty($lv['enabled'])) continue;
        $filtered[] = $lv;
    }
    return $filtered;
}

function getOrgDisplayLevels(bool $enabledOnly = true): array
{
    $levels = $enabledOnly ? getOrgLevels(true) : getOrgLevels(false);
    return array_values(array_filter($levels, fn($lv) => ($lv['key'] ?? '') !== 'company'));
}

function saveOrgHierarchy(array $data): bool
{
    if (validateOrgHierarchyPayload($data) !== []) {
        return false;
    }

    $levels = [];
    foreach ($data['levels'] as $i => $lv) {
        $key = trim((string)($lv['key'] ?? ''));
        $label = trim((string)($lv['label'] ?? ''));
        $headTitle = trim((string)($lv['head_title'] ?? ''));
        if ($key === '' || $label === '' || $headTitle === '') continue;

        if (preg_match('/[<>"\'&]/', $label) || preg_match('/[<>"\'&]/', $headTitle)) {
            continue;
        }

        $levels[] = [
            'depth'      => (int)($lv['depth'] ?? $i),
            'key'        => $key,
            'label'      => $label,
            'head_title' => $headTitle,
            'enabled'    => $key === 'company' ? true : (bool)($lv['enabled'] ?? true),
        ];
    }

    if (empty($levels)) return false;

    usort($levels, fn($a, $b) => $a['depth'] <=> $b['depth']);

    $old = getOrgHierarchy(true);
    $slotMap = $old['slot_map'] ?? [];

    foreach ($levels as $lv) {
        $semanticKey = $lv['key'] . '.head';
        $slotMap[$lv['head_title']] = $semanticKey;
    }

    $save = [
        'levels'       => $levels,
        'slot_map'     => $slotMap,
        'title_system' => $data['title_system'] ?? $old['title_system'] ?? 'rank_and_duty',
    ];

    $json = json_encode($save, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $ok = @file_put_contents(orgHierarchyFile(), $json, LOCK_EX);
    if ($ok === false) {
        error_log('[org_hierarchy] 설정 파일 쓰기 실패: ' . orgHierarchyFile());
        return false;
    }
    getOrgHierarchy(true);
    return true;
}

function getOrgLabel(string $key): string
{
    $hierarchy = getOrgHierarchy();
    foreach ($hierarchy['levels'] as $lv) {
        if ($lv['key'] === $key) return $lv['label'];
    }
    return $key;
}

function getOrgHeadTitle(string $key): string
{
    $hierarchy = getOrgHierarchy();
    foreach ($hierarchy['levels'] as $lv) {
        if ($lv['key'] === $key) return $lv['head_title'];
    }
    return $key;
}

function isOrgLevelEnabled(string $key): bool
{
    $hierarchy = getOrgHierarchy();
    foreach ($hierarchy['levels'] as $lv) {
        if ($lv['key'] === $key) return $lv['enabled'];
    }
    return false;
}

function getOrgLevelByDepth(int $depth): ?array
{
    $hierarchy = getOrgHierarchy();
    foreach ($hierarchy['levels'] as $lv) {
        if ($lv['depth'] === $depth) return $lv;
    }
    return null;
}

function resolveSlotLabel(string $semanticKey): string
{
    $hierarchy = getOrgHierarchy();
    foreach ($hierarchy['levels'] as $lv) {
        if ($lv['key'] . '.head' === $semanticKey) return $lv['head_title'];
    }
    return $semanticKey;
}

function getOrgLabelsForJS(): string
{
    $hierarchy = getOrgHierarchy();
    $map = [];
    $levels = [];
    $displayLevels = [];
    foreach ($hierarchy['levels'] as $lv) {
        $level = [
            'key'     => $lv['key'],
            'label'   => $lv['label'],
            'head'    => $lv['head_title'],
            'enabled' => $lv['enabled'],
            'depth'   => $lv['depth'],
        ];
        $map[$lv['key']] = [
            'label'   => $lv['label'],
            'head'    => $lv['head_title'],
            'enabled' => $lv['enabled'],
            'depth'   => $lv['depth'],
        ];
        $levels[] = $level;
        if ($lv['key'] !== 'company' && $lv['enabled']) {
            $displayLevels[] = $level;
        }
    }
    $map['_levels'] = $levels;
    $map['_display_levels'] = $displayLevels;
    $map['_max_levels'] = ORG_HIERARCHY_MAX_LEVELS;
    $map['_slot_map'] = $hierarchy['slot_map'] ?? [];
    return json_encode($map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
