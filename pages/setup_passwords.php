<?php
/**
 * 초기 비밀번호 설정 · password_hash가 비어있는 계정에 한해 'zaemit1234'로 일괄 설정.
 *
 * 언제 사용하는가:
 *   - init.sql 로 DB를 새로 구축했거나
 *   - schema_auth.sql 실행 직후 password_hash 컬럼만 추가된 상태로
 *     로그인이 "이메일 또는 비밀번호가 올바르지 않습니다"로 실패할 때.
 *
 * 안전장치:
 *   - 이미 해시가 설정된 계정은 **건드리지 않음** (비어있는 계정만 세팅)
 *   - CSRF 토큰으로 POST 보호
 *   - AUTH_ENABLED 상태와 무관하게 동작 (로그인 필요 없음)
 *
 * 운영 투입 전에는 반드시 삭제하거나 접근 차단할 것.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

$csrf = csrfToken();
$message = null;
$ok = false;
$countUpdated = 0;

$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRoot = realpath(__DIR__ . '/..');
$basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');

// 현재 상태 조회
$pdo = getDBConnection();
$accounts = [];
if ($pdo) {
    try {
        // password_hash 컬럼 존재 확인
        $pdo->query('SELECT password_hash FROM employees LIMIT 1');
        $stmt = $pdo->query("SELECT id, name, email, user_role, password_hash
                             FROM employees
                             WHERE is_active = 1 AND (employment_status IS NULL OR employment_status <> '퇴사')
                             ORDER BY id");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = ['type' => 'error', 'text' => 'password_hash 컬럼이 없습니다. 먼저 db/schema_auth.sql 을 실행해주세요. (' . $e->getMessage() . ')'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)) {
    $posted = $_POST['_csrf'] ?? '';
    if (!is_string($posted) || !hash_equals($csrf, $posted)) {
        $message = ['type' => 'error', 'text' => '보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해주세요.'];
    } elseif (!$pdo) {
        $message = ['type' => 'error', 'text' => 'DB 연결에 실패했습니다. config/database.php 를 확인하세요.'];
    } else {
        $defaultPw = $_POST['default_password'] ?? 'zaemit1234';
        if (strlen($defaultPw) < 4) {
            $message = ['type' => 'error', 'text' => '기본 비밀번호는 4자 이상이어야 합니다.'];
        } else {
            try {
                $hash = password_hash($defaultPw, PASSWORD_BCRYPT, ['cost' => 10]);
                // 비어있는 계정만 업데이트 · 이미 설정된 해시는 보존
                $upd = $pdo->prepare("UPDATE employees
                                      SET password_hash = ?
                                      WHERE is_active = 1
                                        AND (password_hash IS NULL OR password_hash = '')
                                        AND (employment_status IS NULL OR employment_status <> '퇴사')");
                $upd->execute([$hash]);
                $countUpdated = $upd->rowCount();
                $ok = true;
                // 업데이트 후 목록 재조회
                $stmt = $pdo->query("SELECT id, name, email, user_role, password_hash
                                     FROM employees
                                     WHERE is_active = 1 AND (employment_status IS NULL OR employment_status <> '퇴사')
                                     ORDER BY id");
                $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $message = [
                    'type' => 'ok',
                    'text' => $countUpdated . '건의 계정에 비밀번호를 설정했습니다. 이제 <a class="underline font-semibold" href="' . htmlspecialchars($basePath) . '/pages/login.php">로그인</a> 할 수 있습니다.'
                ];
            } catch (PDOException $e) {
                $message = ['type' => 'error', 'text' => 'DB 오류: ' . htmlspecialchars($e->getMessage())];
            }
        }
    }
}

$emptyCount = 0;
foreach ($accounts as $a) {
    if (empty($a['password_hash'])) $emptyCount++;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zaemit · 비밀번호 초기화</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:["'Pretendard Variable'","Pretendard","system-ui","sans-serif"]},colors:{primary:'#4F6AFF','primary-dark':'#3B54D4','primary-light':'#E8ECFF'}}}}</script>
</head>
<body class="bg-slate-50 min-h-screen py-10">
<div class="max-w-2xl mx-auto px-4">
    <h1 class="text-xl font-bold text-slate-800 mb-1">비밀번호 초기 설정</h1>
    <p class="text-sm text-slate-500 mb-6">
        <code>employees.password_hash</code> 가 비어있는 계정에 한해 아래 기본 비밀번호로 일괄 설정합니다.
        기존 해시가 있는 계정은 <strong>건드리지 않습니다</strong>.
    </p>

    <?php if ($message): ?>
    <div class="mb-5 p-3 rounded-lg text-sm <?= $message['type'] === 'ok' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-700' ?>">
        <?= $message['text'] /* 메시지 내부에 안전한 HTML만 포함 */ ?>
    </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">현재 계정 상태</h2>
        <?php if (empty($accounts)): ?>
        <p class="text-sm text-rose-600">활성 직원이 없습니다. db/init.sql 을 먼저 실행해주세요.</p>
        <?php else: ?>
        <p class="text-sm text-slate-500 mb-3">총 <?= count($accounts) ?>명 · 비밀번호 미설정: <strong class="text-slate-800 font-bold"><?= $emptyCount ?></strong>명</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left py-2 px-3">이메일</th>
                        <th class="text-left py-2 px-3">이름</th>
                        <th class="text-left py-2 px-3">역할</th>
                        <th class="text-left py-2 px-3">해시 상태</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($accounts as $a): ?>
                    <tr class="border-t border-slate-100">
                        <td class="py-2 px-3 text-slate-700"><?= htmlspecialchars($a['email'] ?: '데이터 없음') ?></td>
                        <td class="py-2 px-3 text-slate-700"><?= htmlspecialchars($a['name']) ?></td>
                        <td class="py-2 px-3 text-slate-500"><?= htmlspecialchars($a['user_role'] ?? 'user') ?></td>
                        <td class="py-2 px-3">
                            <?php if (empty($a['password_hash'])): ?>
                                <span class="px-2 py-0.5 text-xs rounded-full bg-rose-100 text-rose-700">미설정</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-700">설정됨</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <form method="POST" class="bg-white border border-slate-200 rounded-xl p-5">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
        <label class="block text-sm font-medium text-slate-700 mb-1">기본 비밀번호</label>
        <input type="text" name="default_password" value="zaemit1234"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm mb-3"
               autocomplete="off">
        <p class="text-xs text-slate-500 mb-4">이메일 없는 계정은 대상에서 제외됩니다. 기존 해시가 있는 계정은 보존됩니다.</p>
        <div class="flex items-center gap-3">
            <button type="submit" <?= empty($accounts) ? 'disabled' : '' ?>
                    class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark disabled:opacity-50">
                비어있는 계정에 비밀번호 설정
            </button>
            <a href="<?= htmlspecialchars($basePath) ?>/pages/login.php"
               class="text-sm text-slate-500 hover:text-slate-700">← 로그인 페이지로 돌아가기</a>
        </div>
    </form>

    <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <strong>⚠ 배포 전 확인</strong><br>
        이 페이지는 초기 설정용입니다. 운영 환경에 배포하기 전에 <code>pages/setup_passwords.php</code> 파일을 반드시 삭제하거나 <code>.htaccess</code>로 접근 차단하세요.
    </div>
</div>
</body>
</html>
