<?php
require_once __DIR__ . '/../includes/org_hierarchy.php';
require_once __DIR__ . '/../includes/org_path.php';
require_once __DIR__ . '/../includes/approval_settings.php';
require_once __DIR__ . '/../includes/approval_doc.php';

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assertContainsValue($needle, array $haystack, string $message): void
{
    if (!in_array($needle, $haystack, true)) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Needle: ' . var_export($needle, true) . PHP_EOL);
        fwrite(STDERR, 'Haystack: ' . var_export($haystack, true) . PHP_EOL);
        exit(1);
    }
}

$payloadWithoutDepartment = [
    'levels' => [
        ['depth' => 0, 'key' => 'company', 'label' => '회사', 'head_title' => '대표', 'enabled' => true],
        ['depth' => 1, 'key' => 'division', 'label' => '본부', 'head_title' => '본부장', 'enabled' => true],
    ],
];
$errors = validateOrgHierarchyPayload($payloadWithoutDepartment);
assertContainsValue('REQUIRED_KEY_MISSING:department', $errors, '필수 department 내부 키 누락을 막아야 합니다.');

$sevenLevelPayload = [
    'levels' => [
        ['depth' => 0, 'key' => 'company', 'label' => '조직', 'head_title' => '총괄', 'enabled' => true],
        ['depth' => 1, 'key' => 'division', 'label' => '사업단', 'head_title' => '단장', 'enabled' => true],
        ['depth' => 2, 'key' => 'department', 'label' => '센터', 'head_title' => '센터장', 'enabled' => true],
        ['depth' => 3, 'key' => 'level_3', 'label' => '팀', 'head_title' => '팀장', 'enabled' => true],
        ['depth' => 4, 'key' => 'level_4', 'label' => '파트', 'head_title' => '파트장', 'enabled' => true],
        ['depth' => 5, 'key' => 'level_5', 'label' => '셀', 'head_title' => '셀 리더', 'enabled' => true],
        ['depth' => 6, 'key' => 'level_6', 'label' => '유닛', 'head_title' => '유닛 리더', 'enabled' => true],
    ],
];
assertSameValue([], validateOrgHierarchyPayload($sevenLevelPayload), '조직 용어는 최대 7단계까지 허용되어야 합니다.');

$eightLevelPayload = $sevenLevelPayload;
$eightLevelPayload['levels'][] = ['depth' => 7, 'key' => 'level_7', 'label' => '소그룹', 'head_title' => '소그룹장', 'enabled' => true];
assertContainsValue('TOO_MANY_LEVELS', validateOrgHierarchyPayload($eightLevelPayload), '8단계 이상은 저장 전에 막아야 합니다.');

$customHierarchy = [
    'levels' => [
        ['depth' => 0, 'key' => 'company', 'label' => '회사', 'head_title' => '대표', 'enabled' => true],
        ['depth' => 1, 'key' => 'division', 'label' => '본부', 'head_title' => '본부장', 'enabled' => true],
        ['depth' => 2, 'key' => 'department', 'label' => '부서', 'head_title' => '부서장', 'enabled' => true],
        ['depth' => 3, 'key' => 'squad', 'label' => '스쿼드', 'head_title' => '스쿼드장', 'enabled' => true],
    ],
];
$options = getApprovalDepthOptions($customHierarchy);
assertSameValue(['team_lead', 'division_head', 'level_3', 'ceo'], array_keys($options), '계층 추가 시 결재 흐름 옵션이 동적으로 늘어나야 합니다.');
assertSameValue(4, $options['ceo']['levels'], '대표까지 결재 흐름은 모든 활성 계층을 포함해야 합니다.');

$disabledMiddleHierarchy = [
    'levels' => [
        ['depth' => 0, 'key' => 'company', 'label' => '회사', 'head_title' => '대표', 'enabled' => true],
        ['depth' => 1, 'key' => 'division', 'label' => '본부', 'head_title' => '본부장', 'enabled' => false],
        ['depth' => 2, 'key' => 'department', 'label' => '부서', 'head_title' => '부서장', 'enabled' => true],
    ],
    'slot_map' => [
        '부서장' => 'department.head',
        '본부장' => 'division.head',
        '대표' => 'company.head',
    ],
];
$disabledOptions = getApprovalDepthOptions($disabledMiddleHierarchy);
assertSameValue([0, 2], $disabledOptions['ceo']['chain_indexes'], '중간 계층이 비활성화되어도 대표 결재는 실제 조직도 최상위까지 올라가야 합니다.');
assertSameValue(3, $disabledOptions['ceo']['chain_limit'], '중간 계층을 건너뛸 때도 DB parent 체인은 충분히 올라가야 합니다.');
assertSameValue(null, resolveSlotChainIndex('본부장', $disabledMiddleHierarchy, 3), '비활성 계층 슬롯은 최하위 책임자로 대체되면 안 됩니다.');
assertSameValue(2, resolveSlotChainIndex('대표', $disabledMiddleHierarchy, 3), '대표 슬롯은 비활성 중간 계층을 건너뛰고 최상위 체인으로 해소되어야 합니다.');

$labels = json_decode(getOrgLabelsForJS(), true);
assertSameValue(true, array_key_exists('_levels', $labels), 'JS 전역 조직 라벨에 전체 계층 목록이 포함되어야 합니다.');
assertSameValue(true, array_key_exists('_display_levels', $labels), 'JS 전역 조직 라벨에 화면 표시용 계층 목록이 포함되어야 합니다.');
assertSameValue(ORG_HIERARCHY_MAX_LEVELS, $labels['_max_levels'], 'JS 전역 조직 라벨에 최대 계층 수가 포함되어야 합니다.');

$deptFixture = [
    ['id' => 1, 'parent_id' => null, 'name' => '위즈웨어', 'code' => 'ROOT'],
    ['id' => 2, 'parent_id' => 1, 'name' => '병원사업단', 'code' => 'HOSP'],
    ['id' => 3, 'parent_id' => 2, 'name' => '부산센터', 'code' => 'BS'],
    ['id' => 4, 'parent_id' => 3, 'name' => '운영팀', 'code' => 'OPS'],
];
$path = orgPathFromDepartmentList($deptFixture, 4);
assertSameValue('위즈웨어 / 병원사업단 / 부산센터 / 운영팀', orgPathLabel($path), '말단 조직에서 전체 조직 경로를 계산해야 합니다.');

$mapped = orgMapPathToLevels($path, [
    'levels' => [
        ['depth' => 0, 'key' => 'company', 'label' => '회사', 'head_title' => '대표', 'enabled' => true],
        ['depth' => 1, 'key' => 'division', 'label' => '사업단', 'head_title' => '단장', 'enabled' => true],
        ['depth' => 2, 'key' => 'department', 'label' => '센터', 'head_title' => '센터장', 'enabled' => true],
        ['depth' => 3, 'key' => 'level_3', 'label' => '팀', 'head_title' => '팀장', 'enabled' => true],
    ],
]);
assertSameValue('병원사업단', $mapped['division']['name'], '조직 경로를 고객사가 정한 단계명에 맞춰 매핑해야 합니다.');
assertSameValue('운영팀', $mapped['level_3']['name'], '추가 계층도 조직 경로 매핑에 포함되어야 합니다.');

echo "org_hierarchy_connectivity_test passed\n";
