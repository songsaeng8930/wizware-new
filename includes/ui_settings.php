<?php
/**
 * UI 설정 헬퍼 · 전역 디자인 테마 등
 *
 * 저장소: config/ui_settings.json (런타임 생성, .gitignore 대상)
 * 기본값: theme = 'light' (화이트 톤)
 *
 * 사용처:
 *  - includes/header.php  : <html data-theme="..."> 렌더링
 *  - api/ui_settings.php  : 설정 조회/저장 API
 *  - pages/display_settings.php : 그룹웨어 관리 > 디자인 설정 화면
 */

const UI_THEMES = ['light', 'dark'];

function uiSettingsFile(): string
{
    return __DIR__ . '/../config/ui_settings.json';
}

/**
 * UI 설정 로드 (요청 단위 캐시).
 * 파일이 없거나 손상돼도 항상 안전한 기본값을 반환한다.
 */
function getUiSettings(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) return $cache;

    $settings = ['theme' => 'light'];
    $file = uiSettingsFile();
    if (is_readable($file)) {
        $json = json_decode((string)file_get_contents($file), true);
        if (is_array($json)) {
            $settings = array_merge($settings, $json);
        }
    }
    if (!in_array($settings['theme'] ?? '', UI_THEMES, true)) {
        $settings['theme'] = 'light';
    }
    return $cache = $settings;
}

/** 현재 전역 테마 · 'light' | 'dark' */
function getUiTheme(): string
{
    return getUiSettings()['theme'];
}

/**
 * UI 설정 저장 (기존 설정과 병합).
 * 검증 실패/쓰기 실패 시 false.
 */
function saveUiSettings(array $newSettings): bool
{
    $merged = array_merge(getUiSettings(true), $newSettings);
    if (!in_array($merged['theme'] ?? '', UI_THEMES, true)) {
        return false;
    }
    $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $ok = @file_put_contents(uiSettingsFile(), $json, LOCK_EX);
    if ($ok === false) {
        error_log('[ui_settings] 설정 파일 쓰기 실패: ' . uiSettingsFile());
        return false;
    }
    getUiSettings(true); // 요청 단위 캐시 갱신
    return true;
}
