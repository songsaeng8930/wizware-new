<?php
/**
 * Zaemit 그룹웨어 - 로그인 페이지
 * standalone (header.php/sidebar.php 미포함)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// 브라우저가 로그인 폼을 캐시해서 낡은 CSRF 토큰으로 제출하는 것을 방지.
// 뒤로가기로 돌아왔을 때도 서버가 새 토큰으로 다시 렌더하도록 강제한다.
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');

// 로그아웃 처리
if (($_GET['action'] ?? '') === 'logout') {
    logout();
    header('Location: login.php');
    exit;
}

// 로그인 폼은 브라우저 표준 POST이므로 hidden input으로 CSRF 토큰을 검증한다.
// (API 엔드포인트는 헤더 기반 검증 · includes/csrf.php::csrfVerify())
$_loginCsrf = csrfToken();
$_loginCsrfError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['_csrf'] ?? '';
    if (!is_string($posted) || !hash_equals($_loginCsrf, $posted)) {
        $_loginCsrfError = true;
    }
}

// basePath 계산
$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRoot = realpath(__DIR__ . '/..');
$basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');

// 이미 로그인 → 대시보드
if (isLoggedIn()) {
    header('Location: ' . $basePath . '/pages/dashboard.php');
    exit;
}

// 로그인 폼 제출 처리
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($_loginCsrfError) {
        $error = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해주세요.';
    } elseif (empty($email) || empty($password)) {
        $error = '이메일과 비밀번호를 입력해주세요.';
    } else {
        $pdo = getDBConnection();
        if (!$pdo) {
            $error = '서버 연결에 실패했습니다.';
        } else {
            $user = authenticate($pdo, $email, $password);
            if ($user) {
                loginUser($user);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaemit - 로그인</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ["'Pretendard Variable'", 'Pretendard', '-apple-system', 'BlinkMacSystemFont', 'system-ui', 'Roboto', "'Helvetica Neue'", "'Segoe UI'", "'Apple SD Gothic Neo'", "'Noto Sans KR'", "'Malgun Gothic'", 'sans-serif'],
                    },
                    colors: {
                        primary: '#4F6AFF',
                        'primary-dark': '#3B54D4',
                        'primary-light': '#E8ECFF',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

<div class="w-full max-w-sm mx-4">
    <!-- 로고 -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-primary rounded-xl mb-3">
            <i data-lucide="zap" class="w-6 h-6 text-white"></i>
        </div>
        <h1 class="text-xl font-bold text-gray-800">Zaemit</h1>
        <p class="text-sm text-gray-500 mt-1">그룹웨어에 로그인하세요</p>
    </div>

    <!-- 로그인 카드 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_loginCsrf, ENT_QUOTES) ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">이메일</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? 'ceo@zaemit.com') ?>"
                       placeholder="name@zaemit.com" required autofocus
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">비밀번호</label>
                <input type="password" name="password" value="zaemit1234" required
                       placeholder="비밀번호를 입력하세요"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-primary text-white text-sm font-medium rounded-lg
                           hover:bg-primary-dark transition-colors">
                로그인
            </button>
        </form>
    </div>

    <!-- 테스트 계정 안내 -->
    <div class="mt-4 p-3 bg-gray-100 rounded-lg text-xs text-gray-500">
        <p class="font-medium text-gray-600 mb-1">테스트 계정</p>
        <p>관리자: ceo@zaemit.com / zaemit1234</p>
        <p>일반: jung@zaemit.com / zaemit1234</p>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
