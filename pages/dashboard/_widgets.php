<?php
/**
 * 대시보드 위젯 정의 + 사용자별 설정 로딩
 * dashboard.php에서 require하여 사용. 순수 함수(사이드이펙트 없음).
 */

function dashWidgetDefs(): array
{
    return [
        'welcome'       => ['label' => '환영 인사',    'fixed' => true,  'order' => 10, 'roles' => '*'],
        'today_tasks'   => ['label' => '오늘 할 일',   'fixed' => false, 'order' => 20, 'roles' => '*'],
        'kpi'           => ['label' => '핵심 KPI',     'fixed' => false, 'order' => 30, 'roles' => '*'],
        'week_schedule' => ['label' => '이번 주 일정', 'fixed' => false, 'order' => 40, 'roles' => '*'],
        'board'         => ['label' => '게시판',       'fixed' => false, 'order' => 50, 'roles' => '*'],
        'dept_status'   => ['label' => '부서 현황',    'fixed' => false, 'order' => 60, 'roles' => ['admin', 'manager']],
    ];
}

function dashLoadWidgetSettings(?PDO $pdo, int $userId, string $role): array
{
    $defs = dashWidgetDefs();
    $result = [];

    foreach ($defs as $id => $def) {
        if ($def['roles'] !== '*' && !in_array($role, $def['roles'], true)) {
            continue;
        }
        $result[$id] = $def + ['visible' => true];
    }

    if ($pdo && $userId > 0) {
        try {
            $st = $pdo->prepare("SELECT widget_id, is_visible FROM user_dashboard_widgets WHERE employee_id = ?");
            $st->execute([$userId]);
            foreach ($st as $row) {
                $wid = $row['widget_id'];
                if (isset($result[$wid]) && !$result[$wid]['fixed']) {
                    $result[$wid]['visible'] = (bool)(int)$row['is_visible'];
                }
            }
        } catch (\PDOException $e) {
            error_log('[Dashboard] widget settings load: ' . $e->getMessage());
        }
    }

    return $result;
}
