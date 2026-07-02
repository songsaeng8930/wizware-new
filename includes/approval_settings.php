<?php
/**
 * 결재라인 설정 헬퍼 · 기본 결재 라인 depth.
 *
 * 저장소: config/approval_settings.json (런타임 생성, .gitignore 대상)
 * 패턴: includes/ui_settings.php 미러.
 *
 * default_depth · 문서를 올렸을 때 조직도를 따라 "어디까지" 올릴지:
 *   team_lead     = 부서장까지
 *   division_head = 본부장까지 (기본)
 *   ceo           = 대표까지
 *
 * 사용처:
 *  - includes/approval_doc.php : resolveApprovalRoute() 기본 라인 해소
 *  - api/approval.php          : getApprovalDefaultDepth / saveApprovalDefaultDepth 액션
 *  - pages/approval_line.php   : 결재라인 설정 화면의 depth 라디오
 */

require_once __DIR__ . '/org_hierarchy.php';

const APPROVAL_DEPTHS = ['team_lead', 'division_head', 'ceo'];

/** 기존 설정 파일과의 호환을 위해 첫 두 단계와 대표 단계 키는 고정한다. */
function getApprovalDepthOptions(?array $hierarchy = null): array
{
    $hierarchy = $hierarchy ?? getOrgHierarchy();
    $levels = $hierarchy['levels'] ?? [];
    usort($levels, fn($a, $b) => (int)($a['depth'] ?? 0) <=> (int)($b['depth'] ?? 0));

    $enabledCompany = null;
    $allNonCompany = [];
    $enabledNonCompany = [];
    foreach ($levels as $lv) {
        if (($lv['key'] ?? '') === 'company') {
            if (!empty($lv['enabled'])) $enabledCompany = $lv;
            continue;
        }
        $allNonCompany[] = $lv;
        if (!empty($lv['enabled'])) $enabledNonCompany[] = $lv;
    }

    $bottomUpAll = array_reverse($allNonCompany);
    $bottomUpEnabled = array_reverse($enabledNonCompany);
    $options = [];
    $legacyKeys = [1 => 'team_lead', 2 => 'division_head'];

    for ($i = 1; $i <= count($bottomUpEnabled); $i++) {
        $key = $legacyKeys[$i] ?? ('level_' . $i);
        $included = array_slice($bottomUpEnabled, 0, $i);
        $options[$key] = approvalDepthOption($key, $included, $bottomUpAll);
    }

    if ($enabledCompany !== null) {
        $included = $bottomUpEnabled;
        $included[] = $enabledCompany;
        $options['ceo'] = approvalDepthOption('ceo', $included, $bottomUpAll);
    }

    return $options;
}

function approvalDepthOption(string $key, array $includedLevels, array $bottomUpAll): array
{
    $heads = array_map(fn($lv) => (string)($lv['head_title'] ?? ''), $includedLevels);
    $chainIndexes = [];
    foreach ($includedLevels as $lv) {
        $levelKey = (string)($lv['key'] ?? '');
        $idx = null;
        if ($levelKey === 'company') {
            $idx = count($bottomUpAll);
        } else {
            foreach ($bottomUpAll as $i => $candidate) {
                if (($candidate['key'] ?? '') === $levelKey) {
                    $idx = $i;
                    break;
                }
            }
        }
        if ($idx !== null) $chainIndexes[] = $idx;
    }
    $chainIndexes = array_values(array_unique($chainIndexes));
    sort($chainIndexes);
    $chainLimit = $chainIndexes ? (max($chainIndexes) + 1) : 0;

    return [
        'key'           => $key,
        'levels'        => count($includedLevels),
        'label'         => count($includedLevels) . '단계',
        'description'   => '기안자 → ' . implode(' → ', array_filter($heads)),
        'chain_limit'   => $chainLimit,
        'chain_indexes' => $chainIndexes,
    ];
}

function getApprovalDefaultDepthFallback(): string
{
    $options = getApprovalDepthOptions();
    if (isset($options['division_head'])) return 'division_head';
    $keys = array_keys($options);
    return $keys ? (string)end($keys) : 'division_head';
}

function normalizeApprovalDepth(string $depth): string
{
    $options = getApprovalDepthOptions();
    if (isset($options[$depth])) return $depth;
    return getApprovalDefaultDepthFallback();
}

function approvalSettingsFile(): string
{
    return __DIR__ . '/../config/approval_settings.json';
}

/**
 * 결재라인 설정 로드 (요청 단위 캐시).
 * 파일이 없거나 손상돼도 항상 안전한 기본값을 반환한다.
 */
function getApprovalSettings(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) return $cache;

    $settings = ['default_depth' => 'division_head'];
    $file = approvalSettingsFile();
    if (is_readable($file)) {
        $json = json_decode((string)file_get_contents($file), true);
        if (is_array($json)) {
            $settings = array_merge($settings, $json);
        }
    }
    $settings['default_depth'] = normalizeApprovalDepth((string)($settings['default_depth'] ?? ''));
    return $cache = $settings;
}

/** 현재 기본 라인 depth 코드 · 'team_lead' | 'division_head' | 'ceo' */
function getApprovalDefaultDepth(): string
{
    return getApprovalSettings()['default_depth'];
}

/** 기본 라인 depth 코드 → DB parent 체인을 올라갈 물리 단계 수 */
function getApprovalDefaultDepthLevels(): int
{
    $options = getApprovalDepthOptions();
    $depth = getApprovalDefaultDepth();
    return (int)($options[$depth]['chain_limit'] ?? $options[$depth]['levels'] ?? 2);
}

/** 기본 라인에서 실제 결재자로 포함할 parent 체인 인덱스 목록 */
function getApprovalDefaultDepthIndexes(): array
{
    $options = getApprovalDepthOptions();
    $depth = getApprovalDefaultDepth();
    $indexes = $options[$depth]['chain_indexes'] ?? null;
    if (is_array($indexes)) {
        return array_values(array_map('intval', $indexes));
    }
    $levels = (int)($options[$depth]['levels'] ?? 2);
    if ($levels <= 0) return [];
    return range(0, $levels - 1);
}

/**
 * 결재라인 설정 저장 (기존 설정과 병합).
 * 검증 실패/쓰기 실패 시 false.
 */
function saveApprovalSettings(array $newSettings): bool
{
    $merged = array_merge(getApprovalSettings(true), $newSettings);
    $options = getApprovalDepthOptions();
    if (!isset($options[$merged['default_depth'] ?? ''])) {
        return false;
    }
    $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $ok = @file_put_contents(approvalSettingsFile(), $json, LOCK_EX);
    if ($ok === false) {
        error_log('[approval_settings] 설정 파일 쓰기 실패: ' . approvalSettingsFile());
        return false;
    }
    getApprovalSettings(true); // 요청 단위 캐시 갱신
    return true;
}
