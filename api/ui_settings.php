<?php
/**
 * UI 설정 API · 전역 디자인 테마 (화이트/다크)
 *
 * GET  ?action=get  : 현재 설정 조회 (로그인 사용자 전원)
 * POST ?action=save : 설정 저장 (admin 전용) · body: {"theme": "light"|"dark"}
 *
 * 응답 규약: 신규 {ok, data, error:{code,message}} (includes/api_common.php)
 */

require_once __DIR__ . '/../includes/api_auth.php';   // 세션 + 로그인 + CSRF
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/ui_settings.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        apiOk(['settings' => getUiSettings(true)]);

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('METHOD_NOT_ALLOWED', 'POST 요청만 허용됩니다.', 405);
        }
        apiRequireAdmin();

        $input = apiJsonInput();
        $theme = (string)($input['theme'] ?? '');
        if (!in_array($theme, UI_THEMES, true)) {
            apiError('INVALID_THEME', "theme 값은 'light' 또는 'dark' 여야 합니다.");
        }
        if (!saveUiSettings(['theme' => $theme])) {
            apiError('SAVE_FAILED', '설정 저장에 실패했습니다. 서버 로그를 확인하세요.', 500);
        }
        apiOk(['settings' => getUiSettings(true)]);

    default:
        apiError('UNKNOWN_ACTION', '알 수 없는 action 입니다.', 400);
}
