<?php
$pageTitle = '계좌관리';
$currentPage = 'accounting';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bankapi.php';

$apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');

// 탭 결정 + 이관된 탭 리다이렉트 (출력 전에 처리)
$tab = $_GET['tab'] ?? 'manage';
if ($tab === 'register') $tab = 'manage';
if ($tab === 'pattern') $tab = 'rules';
if ($tab === 'categories') {                          // 계정과목 → 환경설정으로 이관
    header('Location: ' . $apiBasePath . '/pages/acct_settings.php?tab=categories');
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$tabs = [
    'manage' => '연결계좌',
    'history' => '거래내역',
    'classify' => '분류',
    'rules' => '분류규칙',
];
if (!isset($tabs[$tab])) $tab = 'manage';

$banks = BANKAPI_BANKS;
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-100">계좌관리</h2>
        </div>
        <div class="zm-tab-container mb-5">
            <?php foreach ($tabs as $key => $label): ?>
            <a href="?tab=<?= $key ?>"
               class="zm-tab <?= $tab === $key ? 'zm-tab-active' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php
        if ($tab === 'manage') include __DIR__ . '/acct_bank_manage.php';
        elseif ($tab === 'history') include __DIR__ . '/acct_bank_history.php';
        elseif ($tab === 'classify') include __DIR__ . '/acct_bank_classify.php';
        elseif ($tab === 'rules') include __DIR__ . '/acct_bank_pattern.php';
        ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
