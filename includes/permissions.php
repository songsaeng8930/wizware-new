<?php
/**
 * Zaemit 그룹웨어 · 메뉴/기능 접근권한 헬퍼
 *
 * 핵심 API:
 *   hasMenuPermission(string $menuKey, string $level='view'): bool
 *   requireMenuPermission(string $menuKey, string $level='view'): void   // 없으면 리다이렉트/403
 *   apiRequireMenuPermission(...): void                                  // API용 JSON 403
 *   getAccessibleMenus(): array                                          // 사이드바 필터용
 *
 * 동작:
 *   1) admin 역할 사용자는 항상 통과 (admin 시드 + 런타임 가드)
 *   2) menu_permissions 테이블에 (menu_key, role|dept) 조합 조회
 *   3) 액세스 레벨은 view < edit < admin. 요청 레벨 이상이면 통과.
 *   4) 테이블 미생성 환경(마이그레이션 미실행) 에서는 '기본 안전 모드' 로 동작:
 *      - admin 전용 메뉴 (groupware.*, labor.rules, accounting.settle) 는 admin 만
 *      - 그 외 모든 메뉴는 로그인 사용자 전원 허용 · 기존 동작과 동일
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!defined('PERMISSION_LEVEL_VIEW'))  define('PERMISSION_LEVEL_VIEW',  1);
if (!defined('PERMISSION_LEVEL_EDIT'))  define('PERMISSION_LEVEL_EDIT',  2);
if (!defined('PERMISSION_LEVEL_ADMIN')) define('PERMISSION_LEVEL_ADMIN', 3);

function permissionLevelInt(string $level): int {
    return match ($level) {
        'admin' => PERMISSION_LEVEL_ADMIN,
        'edit'  => PERMISSION_LEVEL_EDIT,
        default => PERMISSION_LEVEL_VIEW,
    };
}

/**
 * menu_permissions 테이블이 없으면 생성 + 기본 시드 삽입.
 * 마이그레이션을 실행하지 않은 환경에서도 헬퍼가 동작하도록 한다.
 */
function ensureMenuPermissionsTable(?PDO $pdo = null): bool {
    $pdo = $pdo ?: getDBConnection();
    if (!$pdo) return false;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS menu_permissions (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            menu_key       VARCHAR(60) NOT NULL,
            role_key       VARCHAR(30) NULL,
            department_id  INT NULL,
            access_level   ENUM('view','edit','admin') NOT NULL DEFAULT 'view',
            note           VARCHAR(200) NULL,
            created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_menu_role_dept (menu_key, role_key, department_id),
            INDEX idx_menu (menu_key),
            INDEX idx_role (role_key),
            INDEX idx_dept (department_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        // 시드가 비어있으면 기본값 주입
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM menu_permissions")->fetchColumn();
        if ($cnt === 0) {
            $seed = [
                ['*','admin','admin','관리자는 모든 메뉴 접근'],
                ['dashboard','manager','edit',null],
                ['dashboard','user','view',null],
                ['attendance','manager','edit',null],
                ['attendance','user','edit',null],
                ['schedule','manager','edit',null],
                ['schedule','user','edit',null],
                ['approval','manager','edit',null],
                ['approval','user','edit',null],
                ['board','manager','edit',null],
                ['board','user','edit',null],
                ['hr','manager','edit',null],
                ['hr','user','view',null],
                ['hospital','manager','edit','병원 전용 운영관리'],
                ['hospital','user','view','병원 전용 운영조회'],
                ['accounting','manager','view',null],
                ['accounting.settle','manager','edit','회계 정산'],
                ['labor','manager','view',null],
                ['labor.rules','manager','view','취업규칙 편집은 관리자 전용'],
                ['business','manager','edit',null],
                ['business','user','view',null],
                ['business_docs','manager','view',null],
                ['business_docs','user','view',null],
                ['groupware','admin','admin',null],
                ['groupware.permissions','admin','admin','접근권한 관리'],
            ];
            $st = $pdo->prepare("INSERT IGNORE INTO menu_permissions (menu_key, role_key, department_id, access_level, note)
                                  VALUES (?, ?, NULL, ?, ?)");
            foreach ($seed as $s) {
                $st->execute([$s[0], $s[1], $s[2], $s[3]]);
            }
        }
        return true;
    } catch (Throwable $e) {
        error_log('[permissions] ensureMenuPermissionsTable: ' . $e->getMessage());
        return false;
    }
}

/**
 * 현재 로그인 사용자가 특정 메뉴에 접근 가능한지 검사.
 * 테이블이 없으면 '기본 안전 모드' 로 폴백.
 */
function hasMenuPermission(string $menuKey, string $level = 'view'): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;
    $role = (string)($user['role'] ?? 'user');
    $dept = (int)($user['department_id'] ?? 0);

    // admin 은 항상 통과
    if ($role === 'admin') return true;

    $pdo = getDBConnection();
    if (!$pdo) return defaultSafeCheck($menuKey, $role);

    // 테이블 존재 여부 확인 (미실행 환경 대비)
    static $tableReady = null;
    if ($tableReady === null) {
        try { $pdo->query("SELECT 1 FROM menu_permissions LIMIT 1"); $tableReady = true; }
        catch (Throwable $e) { $tableReady = false; }
    }
    if (!$tableReady) return defaultSafeCheck($menuKey, $role);

    $required = permissionLevelInt($level);
    try {
        // '*' 역할 시드(관리자 전체 허용)도 함께 체크
        $q = $pdo->prepare("
            SELECT access_level FROM menu_permissions
            WHERE (menu_key = ? OR menu_key = '*')
              AND (role_key = ? OR role_key IS NULL OR (department_id IS NOT NULL AND department_id = ?))
        ");
        $q->execute([$menuKey, $role, $dept]);
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $lv) {
            if (permissionLevelInt((string)$lv) >= $required) return true;
        }
    } catch (Throwable $e) {
        error_log('[permissions] hasMenuPermission: ' . $e->getMessage());
        return defaultSafeCheck($menuKey, $role);
    }
    return false;
}

/** 테이블 없을 때의 기본 폴백 · 기존 동작 유지 */
function defaultSafeCheck(string $menuKey, string $role): bool {
    // admin 전용 메뉴 목록 (경영 민감 영역)
    $adminOnly = ['groupware', 'groupware.permissions', 'labor.rules', 'accounting.settle'];
    foreach ($adminOnly as $m) {
        if ($menuKey === $m || str_starts_with($menuKey, $m . '.')) {
            return $role === 'admin';
        }
    }
    return true; // 일반 메뉴는 로그인 사용자 전원
}

/** 페이지 진입 가드 · 부족 시 대시보드로 리다이렉트 + 플래시 */
function requireMenuPermission(string $menuKey, string $level = 'view'): void {
    if (hasMenuPermission($menuKey, $level)) return;
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['_perm_flash'] = '해당 메뉴에 접근할 권한이 없습니다. (' . htmlspecialchars($menuKey) . ')';
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $projectRoot = realpath(__DIR__ . '/..');
    $basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');
    header('Location: ' . $basePath . '/pages/dashboard.php');
    exit;
}

/** API 가드 · JSON 403 반환 */
function apiRequireMenuPermission(string $menuKey, string $level = 'view'): void {
    if (hasMenuPermission($menuKey, $level)) return;
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '해당 기능에 접근할 권한이 없습니다.', 'menu' => $menuKey, 'level' => $level], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 사이드바 메뉴 필터용 · 해당 사용자가 접근 가능한 메뉴 키 집합.
 * 성능상 한 번 계산 후 정적 캐시.
 */
function getAccessibleMenus(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    $user = $_SESSION['user'] ?? null;
    if (!$user) return $cache;
    $role = (string)($user['role'] ?? 'user');
    $dept = (int)($user['department_id'] ?? 0);
    if ($role === 'admin') { $cache = ['*']; return $cache; }

    $pdo = getDBConnection();
    if (!$pdo) return $cache;
    try { $pdo->query("SELECT 1 FROM menu_permissions LIMIT 1"); }
    catch (Throwable $e) { return $cache; }
    try {
        $q = $pdo->prepare("SELECT DISTINCT menu_key FROM menu_permissions
                            WHERE role_key = ? OR role_key IS NULL OR department_id = ?");
        $q->execute([$role, $dept]);
        $cache = array_map('strval', $q->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        error_log('[permissions] getAccessibleMenus: ' . $e->getMessage());
    }
    return $cache;
}

/**
 * 사이드바에서 menu id 로 접근 가능한지 빠른 체크.
 * 세부 메뉴(accounting.settle 등) 까지 모두 권한이 없으면 상위 메뉴는 보여도 OK ·
 * 사용자가 상위 클릭 시 페이지 가드에서 처리.
 */
function sidebarMenuVisible(string $menuId): bool {
    // admin 은 전부 표시
    $user = $_SESSION['user'] ?? null;
    if (($user['role'] ?? '') === 'admin') return true;
    // 기본 공개 메뉴
    $alwaysVisible = ['dashboard'];
    if (in_array($menuId, $alwaysVisible, true)) return true;
    return hasMenuPermission($menuId, 'view');
}
