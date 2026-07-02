<?php
/**
 * 대시보드 위젯 설정 API
 * - getWidgetSettings: 사용자별 위젯 표시 설정 조회
 * - saveWidgetSettings: 위젯 표시 설정 저장
 * - resetWidgetSettings: 기본값 복원 (DB 레코드 삭제)
 */
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../pages/dashboard/_widgets.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getWidgetSettings':
        $userId = apiSessionUserId();
        if ($userId <= 0) apiError('AUTH', '로그인이 필요합니다.', 401);
        $role   = apiSessionRole();
        $pdo    = getDBConnection();
        $settings = dashLoadWidgetSettings($pdo, $userId, $role);

        $list = [];
        foreach ($settings as $id => $w) {
            $list[] = [
                'id'      => $id,
                'label'   => $w['label'],
                'fixed'   => $w['fixed'],
                'visible' => $w['visible'],
                'order'   => $w['order'],
            ];
        }
        apiOk(['widgets' => $list]);
        break;

    case 'saveWidgetSettings':
        $userId = apiSessionUserId();
        if ($userId <= 0) apiError('AUTH', '로그인이 필요합니다.', 401);

        $input   = apiJsonInput();
        $widgets = $input['widgets'] ?? [];
        if (!is_array($widgets)) apiError('BAD_INPUT', 'widgets 배열이 필요합니다.');

        $defs = dashWidgetDefs();
        $pdo  = getDBConnection();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("
                INSERT INTO user_dashboard_widgets (employee_id, widget_id, is_visible)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE is_visible = VALUES(is_visible), updated_at = NOW()
            ");
            foreach ($widgets as $w) {
                $wid = $w['widget_id'] ?? '';
                if (!isset($defs[$wid]) || ($defs[$wid]['fixed'] ?? false)) continue;
                $visible = !empty($w['is_visible']) ? 1 : 0;
                $st->execute([$userId, $wid, $visible]);
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('[Dashboard API] saveWidgetSettings: ' . $e->getMessage());
            apiError('DB_ERROR', '저장 중 오류가 발생했습니다.', 500);
        }
        apiOk();
        break;

    case 'resetWidgetSettings':
        $userId = apiSessionUserId();
        if ($userId <= 0) apiError('AUTH', '로그인이 필요합니다.', 401);

        $pdo = getDBConnection();
        try {
            $st = $pdo->prepare("DELETE FROM user_dashboard_widgets WHERE employee_id = ?");
            $st->execute([$userId]);
        } catch (\Exception $e) {
            error_log('[Dashboard API] resetWidgetSettings: ' . $e->getMessage());
        }
        apiOk();
        break;

    default:
        apiError('UNKNOWN_ACTION', '알 수 없는 액션입니다.');
}
