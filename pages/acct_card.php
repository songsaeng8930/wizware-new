<?php
$pageTitle = '카드관리';
$currentPage = 'accounting';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$tab = $_GET['tab'] ?? 'expenses';
$tabs = [
    'expenses' => '지출내역',
    'register' => '카드목록',
    'rules' => '규정',
    'settle' => '정산',
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-100">카드관리</h2>
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
        if ($tab === 'expenses') include __DIR__ . '/acct_card_expenses.php';
        elseif ($tab === 'register') include __DIR__ . '/acct_card_register.php';
        elseif ($tab === 'rules') include __DIR__ . '/acct_card_rules.php';
        elseif ($tab === 'settle') include __DIR__ . '/acct_card_settle.php';
        ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
