<?php
/**
 * Zaemit 그룹웨어 - 인증 미들웨어
 *
 * 보안 정책:
 *  - 세션 쿠키는 HttpOnly + SameSite=Lax, HTTPS 요청이면 Secure 자동 적용
 *  - 로그인 성공 시 session_regenerate_id(true) · 세션 고정(fixation) 방지
 *  - 비활동 30분 이상이면 자동 로그아웃 (idle timeout)
 *  - UA 지문 저장 후 급격한 변경 감지 시 강제 로그아웃 (세션 탈취 완화)
 *  - 역할(role)은 DB 단일 소스에서만 결정되며, 세션은 캐시일 뿐
 */

// ── junction/symlink 배포 환경 대응 (DOCUMENT_ROOT 정규화) ──
// XAMPP htdocs가 junction으로 프로젝트를 가리키면 PHP realpath()는 프로젝트
// 경로만 실제 물리 경로로 해석해, DOCUMENT_ROOT(htdocs)와 프로젝트 루트가
// 서로 다른 물리 트리가 된다. 그 결과 프로젝트 전역이 공유하는
// basePath = str_replace(docRoot, '', projectRoot) 계산이 깨져(파일시스템
// 절대경로가 링크에 박힘) 모든 페이지 이동/리다이렉트가 불가능해진다.
// 여기서 SCRIPT_NAME(URL)으로 프로젝트 루트의 URL 위치를 역산해 DOCUMENT_ROOT를
// 프로젝트와 같은 물리 트리로 교정한다. 정상 환경에서는 조기 반환해 손대지 않는다.
(static function (): void {
    $projPhys = realpath(__DIR__ . '/..');
    if ($projPhys === false) return;
    $projPhys = str_replace('\\', '/', $projPhys);

    $docPhys = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $docPhys = $docPhys === false ? '' : str_replace('\\', '/', $docPhys);

    // 이미 DOCUMENT_ROOT가 프로젝트의 조상 경로면 정상 환경 → 손대지 않음
    if ($docPhys !== '' && strpos($projPhys, $docPhys) === 0) return;

    $scriptPhys = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptPhys = $scriptPhys === false ? '' : str_replace('\\', '/', $scriptPhys);
    $scriptUrl  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptPhys === '' || $scriptUrl === '' || strpos($scriptPhys, $projPhys) !== 0) return;

    // 프로젝트 루트 기준 현재 스크립트의 하위 깊이(물리) 만큼 URL에서 거슬러 올라감
    $rel = trim(substr($scriptPhys, strlen($projPhys)), '/');   // 예: pages/dashboard.php
    if ($rel === '') return;
    $depth = substr_count($rel, '/') + 1;

    $urlBase = $scriptUrl;
    for ($i = 0; $i < $depth; $i++) $urlBase = dirname($urlBase);
    $urlBase = str_replace('\\', '/', $urlBase);
    $urlSegs = ($urlBase === '' || $urlBase === '/' || $urlBase === '.')
             ? 0 : substr_count(trim($urlBase, '/'), '/') + 1;

    // 가상 DocumentRoot = 프로젝트 물리 루트에서 URL 세그먼트 수만큼 상위 폴더
    $virtualDoc = $projPhys;
    for ($i = 0; $i < $urlSegs; $i++) $virtualDoc = dirname($virtualDoc);
    $_SERVER['DOCUMENT_ROOT'] = $virtualDoc;
})();

// ── 세션 쿠키 보안 설정 ── (session_start 이전에 적용해야 효력이 있음)
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,            // 브라우저 종료 시 만료
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secureCookie,
        'httponly' => true,         // JS에서 세션 쿠키 접근 차단 (XSS 완화)
        'samesite' => 'Lax',        // CSRF 완화
    ]);
    // 세션 ID를 쿠키로만 전달 (URL 파라미터 차단)
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ── 비활동 타임아웃 (30분) ──
const AUTH_IDLE_TIMEOUT = 1800;

/** 로그인 여부 확인 (세션 유효성 + 타임아웃 + UA 지문 검증) */
function isLoggedIn(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    // 비활동 타임아웃
    $last = (int)($_SESSION['last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > AUTH_IDLE_TIMEOUT) {
        logout();
        return false;
    }

    // UA 지문 검증 (세션 탈취 간단 완화)
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fp = hash('sha256', $ua);
    if (!empty($_SESSION['ua_fp']) && !hash_equals($_SESSION['ua_fp'], $fp)) {
        logout();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/** 로그인 필수 · 미인증 시 login.php로 리다이렉트 */
function requireLogin(): void
{
    if (!defined('AUTH_ENABLED') || AUTH_ENABLED === false) {
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        return;
    }
    if (!isLoggedIn()) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $projectRoot = realpath(__DIR__ . '/..');
        $basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');
        header('Location: ' . $basePath . '/pages/login.php');
        exit;
    }
    session_write_close();
}

/** 현재 로그인 사용자 정보 */
function getCurrentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** 현재 사용자 역할 · 'admin' | 'manager' | 'user' | '' */
function getCurrentUserRole(): string
{
    return (string)($_SESSION['user']['role'] ?? '');
}

/** 관리자(전사) 여부 */
function isAdmin(): bool
{
    return getCurrentUserRole() === 'admin';
}

/** 팀 관리자(부서장) 여부 */
function isManager(): bool
{
    return getCurrentUserRole() === 'manager';
}

/** 관리자 또는 부서장 여부 · 인사이트 패널 노출 기준 */
function canViewInsights(): bool
{
    return in_array(getCurrentUserRole(), ['admin', 'manager'], true);
}

/** 이메일+비밀번호 인증 */
function authenticate(PDO $pdo, string $email, string $password): ?array
{
    $stmt = $pdo->prepare(
        "SELECT id, name, email, password_hash, position, title, user_role,
                profile_image, department_id
         FROM employees
         WHERE email = ? AND is_active = 1 AND employment_status <> '퇴사'
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        // 역할 값 화이트리스트 검증 (DB에 비정상 값이 있어도 차단)
        $role = in_array($user['user_role'], ['admin', 'manager', 'user'], true)
              ? $user['user_role']
              : 'user';
        $user['user_role'] = $role;
        return $user;
    }
    return null;
}

/** 세션에 사용자 정보 저장 · 로그인 직후 호출 */
function loginUser(array $user): void
{
    // 세션 고정 방지: 새 세션 ID 발급 + 이전 세션 완전 폐기
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user'] = [
        'id'            => (int)$user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'position'      => $user['position'],
        'title'         => $user['title'],
        'role'          => $user['user_role'],        // admin | manager | user
        'profile_image' => $user['profile_image'],
        'department_id' => $user['department_id'] !== null ? (int)$user['department_id'] : null,
    ];
    $_SESSION['login_time']    = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ua_fp']         = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

/** 로그아웃 · 세션 데이터/쿠키/파일 모두 파기 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
