<?php
/**
 * 로그인 실패 원인 진단 페이지 · standalone
 *
 * 브라우저에서 /pages/auth_diagnose.php 접근 시,
 * "ceo@zaemit.com" 계정으로 각 단계를 순서대로 체크하여 어디서 막혔는지 표시한다.
 *
 * 이 페이지는 DB 상태만 읽고 변경하지 않는다. (비밀번호 시도 테스트 외)
 * 운영 배포 전 삭제 필수.
 */

require_once __DIR__ . '/../config/database.php';

$checks = [];
$email = $_GET['email'] ?? 'ceo@zaemit.com';
$password = $_GET['pw'] ?? 'zaemit1234';

function addCheck(array &$list, string $label, bool $ok, string $detail = ''): void
{
    $list[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

// 1) DB 연결
$pdo = getDBConnection();
addCheck($checks, 'DB 연결', $pdo !== null, $pdo ? 'getDBConnection() OK' : 'config/database.php 의 DB_HOST/PORT/USER/PASS 확인 필요');

if ($pdo) {
    // 2) employees 테이블 존재
    try {
        $pdo->query('SELECT COUNT(*) FROM employees');
        addCheck($checks, 'employees 테이블 존재', true);
    } catch (PDOException $e) {
        addCheck($checks, 'employees 테이블 존재', false, 'db/init.sql 실행 필요: ' . $e->getMessage());
        $pdo = null;
    }
}

$hasCols = ['password_hash' => false, 'user_role' => false, 'employment_status' => false, 'is_active' => false];
if ($pdo) {
    // 3) 필수 컬럼 존재
    foreach (array_keys($hasCols) as $col) {
        try {
            $pdo->query("SELECT {$col} FROM employees LIMIT 1");
            $hasCols[$col] = true;
            addCheck($checks, "컬럼 {$col}", true);
        } catch (PDOException $e) {
            addCheck($checks, "컬럼 {$col}", false, $col === 'password_hash' || $col === 'user_role'
                ? 'db/schema_auth.sql 실행 필요'
                : 'db/init.sql 실행 필요');
        }
    }
}

$user = null;
if ($pdo && $hasCols['password_hash']) {
    // 4) 이메일로 레코드 조회
    $st = $pdo->prepare('SELECT id, name, email, password_hash, user_role, is_active, employment_status FROM employees WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC);

    addCheck($checks, "email='{$email}' 레코드 존재", $user !== false, $user ? "id=#{$user['id']} name={$user['name']}" : '해당 이메일의 직원이 없습니다.');
}

if ($pdo && $user) {
    // 5) is_active = 1
    addCheck($checks, "is_active = 1", (int)$user['is_active'] === 1, '현재 값: ' . var_export($user['is_active'], true));

    // 6) employment_status != '퇴사'
    addCheck($checks, "employment_status <> '퇴사'", (string)$user['employment_status'] !== '퇴사', '현재 값: ' . var_export($user['employment_status'], true));

    // 7) password_hash 비어있지 않음
    $hashOk = !empty($user['password_hash']);
    addCheck($checks, 'password_hash 저장됨 (NULL 아님)', $hashOk, $hashOk
        ? '해시 길이 ' . strlen($user['password_hash']) . 'byte, prefix=' . substr((string)$user['password_hash'], 0, 4)
        : '🔴 pages/setup_passwords.php 에서 "비어있는 계정에 비밀번호 설정" 버튼을 눌러주세요.');

    // 8) password_verify
    if ($hashOk) {
        $pvOk = password_verify($password, $user['password_hash']);
        addCheck($checks, "password_verify('{$password}', hash)", $pvOk, $pvOk
            ? '입력 비밀번호가 해시와 일치합니다.'
            : '해시가 있지만 입력 비밀번호와 일치하지 않습니다. setup_passwords.php 로 재설정하세요.');
    }

    // 9) user_role 유효 값
    if (isset($user['user_role'])) {
        $ok = in_array($user['user_role'], ['admin', 'manager', 'user'], true);
        addCheck($checks, "user_role 유효값", $ok, '현재 값: ' . var_export($user['user_role'], true));
    }
}

// 세션 상태
session_name('PHPSESSID');
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
$sessInfo = [
    'session_id'   => session_id() ?: '(없음)',
    'save_path'    => session_save_path() ?: ini_get('session.save_path'),
    'cookie'       => $_COOKIE[session_name()] ?? '(쿠키 없음)',
    'user_id_key'  => $_SESSION['user_id'] ?? '(없음)',
    '_csrf_token'  => isset($_SESSION['_csrf_token']) ? substr($_SESSION['_csrf_token'], 0, 12) . '…' : '(없음)',
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>Zaemit · 인증 진단</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:["'Pretendard Variable'","Pretendard","system-ui","sans-serif"]}}}}</script>
</head>
<body class="bg-slate-50 min-h-screen py-10">
<div class="max-w-3xl mx-auto px-4">
    <h1 class="text-xl font-bold text-slate-800 mb-1">로그인 실패 원인 진단</h1>
    <p class="text-sm text-slate-500 mb-5">각 단계를 순서대로 확인합니다. 처음 <span class="bg-rose-100 text-rose-700 px-1.5 rounded">빨간색 ✗</span> 항목이 로그인 실패의 원인입니다.</p>

    <form method="GET" class="flex gap-2 mb-6">
        <input name="email" value="<?= htmlspecialchars($email) ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm flex-1" placeholder="이메일">
        <input name="pw"    value="<?= htmlspecialchars($password) ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm flex-1" placeholder="비밀번호">
        <button class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg">다시 검사</button>
    </form>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr><th class="text-left py-2 px-4 w-8"></th><th class="text-left py-2 px-4">체크 항목</th><th class="text-left py-2 px-4">상세</th></tr>
            </thead>
            <tbody>
            <?php $firstFail = null; foreach ($checks as $i => $c):
                if (!$c['ok'] && $firstFail === null) $firstFail = $i;
            ?>
                <tr class="border-t border-slate-100 <?= !$c['ok'] ? 'bg-rose-50/50' : '' ?>">
                    <td class="py-2 px-4"><?= $c['ok'] ? '<span class="text-emerald-600 font-bold">✓</span>' : '<span class="text-rose-600 font-bold">✗</span>' ?></td>
                    <td class="py-2 px-4 font-medium text-slate-700"><?= htmlspecialchars($c['label']) ?></td>
                    <td class="py-2 px-4 text-slate-500"><?= htmlspecialchars($c['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($firstFail !== null): ?>
    <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-800 mb-6">
        <strong>첫 실패 지점:</strong> "<?= htmlspecialchars($checks[$firstFail]['label']) ?>".<br>
        <span class="text-rose-700">→ <?= htmlspecialchars($checks[$firstFail]['detail']) ?></span>
    </div>
    <?php else: ?>
    <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 mb-6">
        ✅ 모든 체크 통과 · 이 자격증명으로 로그인 가능합니다. login.php 로 돌아가 재시도해주세요.
    </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">세션 상태</h2>
        <table class="w-full text-sm">
            <?php foreach ($sessInfo as $k => $v): ?>
            <tr class="border-b border-slate-100 last:border-0"><td class="py-1.5 pr-3 text-slate-500 w-40"><?= htmlspecialchars($k) ?></td><td class="py-1.5 text-slate-700 font-mono text-xs"><?= htmlspecialchars((string)$v) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="flex gap-2">
        <a href="setup_passwords.php" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg">
            → 비밀번호 초기화 (setup_passwords.php)
        </a>
        <a href="login.php" class="px-4 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg">
            ← 로그인 페이지
        </a>
    </div>

    <p class="text-xs text-slate-400 mt-6">
        이 파일은 진단용입니다. 운영 환경에 배포 전 <code>pages/auth_diagnose.php</code> 를 반드시 삭제하거나 차단하세요.
    </p>
</div>
</body>
</html>
