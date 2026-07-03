<?php
/**
 * Zaemit 그룹웨어 · 접근권한 관리 API (admin 전용).
 *
 * 액션:
 *   list         GET          전체 권한 행 조회
 *   menus        GET          사이드바의 menu_key 목록 (관리 화면 UI 구성용)
 *   setCell      POST         (menu_key, role_key, access_level|null) → upsert/delete
 *   reset        POST         기본 시드로 재설정 (개발용)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../includes/org_hierarchy.php';

if (!function_exists('apiLegacyOk')) {
    function apiLegacyOk(array $data = [], int $code = 200): never {
        http_response_code($code);
        echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 접근권한 관리 자체는 admin 전용
apiRequireMenuPermission('groupware.permissions', 'admin');

$pdo = getDBConnection();
if (!$pdo) apiLegacyError('DB 연결 실패', 500);
ensureMenuPermissionsTable($pdo);

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        'list'     => listPerm($pdo),
        'menus'    => listMenus($pdo),
        'setCell'  => setCell($pdo),
        'reset'    => resetSeed($pdo),
        default    => apiLegacyError('알 수 없는 액션', 400),
    };
} catch (Throwable $e) {
    error_log('[permissions API] ' . $e->getMessage());
    apiLegacyError('서버 오류가 발생했습니다.', 500);
}

function listPerm(PDO $pdo): void {
    $rows = $pdo->query("SELECT id, menu_key, role_key, department_id, access_level, note
                         FROM menu_permissions ORDER BY menu_key, role_key, department_id")
               ->fetchAll(PDO::FETCH_ASSOC);
    apiLegacyOk(['rows' => $rows]);
}

/** 프로젝트에 정의된 메뉴 키 목록 · 권한 매트릭스 행 구성용 */
function listMenus(PDO $pdo): void {
    // 사이드바(includes/sidebar.php) 실제 메뉴 구조와 1:1 일치. group = 사이드바 섹션.
    $menus = [
        // ── 내 업무 (전 직원) ──
        ['key' => 'dashboard',             'label' => '대시보드',        'group' => '내 업무'],
        ['key' => 'attendance',            'label' => '근태',            'group' => '내 업무'],
        ['key' => 'schedule',              'label' => '일정',            'group' => '내 업무'],
        ['key' => 'approval',              'label' => '전자결재',        'group' => '내 업무'],
        ['key' => 'board',                 'label' => '게시판',          'group' => '내 업무'],
        ['key' => 'card',                  'label' => '법인카드',        'group' => '내 업무'],
        ['key' => 'company',               'label' => '우리 회사',       'group' => '내 업무'],
        ['key' => 'my_payslip',            'label' => '내 급여',         'group' => '내 업무'],
        // ── 병원 운영 ──
        ['key' => 'hospital',              'label' => '병원 전용',       'group' => '병원 운영'],
        // ── 조직운영 (관리자/부서장) ──
        ['key' => 'hr',                    'label' => '인사관리',        'group' => '조직운영'],
        ['key' => 'attendance.manage',     'label' => '인사관리 > 근태 관리', 'group' => '조직운영'],
        ['key' => 'labor',                 'label' => '노무관리',        'group' => '조직운영'],
        ['key' => 'labor.rules',           'label' => '노무관리 > 취업규칙 편집', 'group' => '조직운영'],
        ['key' => 'approval_admin',        'label' => '결재 운영 (관리)', 'group' => '조직운영'],
        ['key' => 'accounting',            'label' => '재무관리',        'group' => '조직운영'],
        ['key' => 'accounting.settle',     'label' => '재무관리 > 카드 정산 (회계)', 'group' => '조직운영'],
        ['key' => 'business',              'label' => '사업',            'group' => '조직운영'],
        ['key' => 'business_docs',         'label' => '업무자료실/의뢰',  'group' => '조직운영'],
        // ── 시스템 (관리자) ──
        ['key' => 'groupware',             'label' => '그룹웨어 관리',    'group' => '시스템'],
        ['key' => 'groupware.permissions', 'label' => '그룹웨어 관리 > 접근권한', 'group' => '시스템'],
    ];
    $roles = [
        ['key' => 'admin',   'label' => '관리자(admin)'],
        ['key' => 'manager', 'label' => getOrgHeadTitle('department') . '(manager)'],
        ['key' => 'user',    'label' => '일반(user)'],
    ];
    apiLegacyOk(['menus' => $menus, 'roles' => $roles]);
}

/**
 * 매트릭스 한 셀 업데이트.
 * 입력: { menu_key, role_key, access_level: 'none'|'view'|'edit'|'admin' }
 * - access_level 이 'none' 이면 해당 (menu_key, role_key) 행 삭제
 * - 그 외 레벨이면 INSERT ... ON DUPLICATE KEY UPDATE
 */
function setCell(PDO $pdo): void {
    $in = apiJsonInput();
    $menu  = trim((string)($in['menu_key']  ?? ''));
    $role  = trim((string)($in['role_key']  ?? ''));
    $level = trim((string)($in['access_level'] ?? 'view'));
    if ($menu === '' || $role === '') apiLegacyError('menu_key, role_key 가 필요합니다.', 400);
    if (!in_array($level, ['none','view','edit','admin'], true)) apiLegacyError('access_level 값이 유효하지 않습니다.', 400);

    if ($level === 'none') {
        $pdo->prepare("DELETE FROM menu_permissions WHERE menu_key = ? AND role_key = ? AND department_id IS NULL")
            ->execute([$menu, $role]);
    } else {
        $pdo->prepare("INSERT INTO menu_permissions (menu_key, role_key, department_id, access_level)
                       VALUES (?, ?, NULL, ?)
                       ON DUPLICATE KEY UPDATE access_level = VALUES(access_level), updated_at = CURRENT_TIMESTAMP")
            ->execute([$menu, $role, $level]);
    }
    apiLegacyOk(['menu_key' => $menu, 'role_key' => $role, 'access_level' => $level]);
}

function resetSeed(PDO $pdo): void {
    $pdo->exec("DELETE FROM menu_permissions");
    ensureMenuPermissionsTable($pdo); // 빈 테이블이면 다시 시드
    apiLegacyOk(['message' => '초기 권한 매트릭스가 복원되었습니다.']);
}


