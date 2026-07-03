<?php
$pageTitle = '세금계산서';
$currentPage = 'accounting';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('accounting', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$tab = $_GET['tab'] ?? 'issue';
$tabs = [
    'issue'   => '세금계산서',
    'match'   => '매칭',
    'pattern' => '패턴',
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-100">세금계산서</h2>
        </div>
        <div class="flex items-center justify-between mb-5">
            <div class="zm-tab-container" style="margin-bottom:0">
                <?php foreach ($tabs as $key => $label): ?>
                <a href="?tab=<?= $key ?>"
                   class="zm-tab <?= $tab === $key ? 'zm-tab-active' : '' ?>">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center gap-2" id="invoiceHeaderActions"></div>
        </div>

        <?php
        if ($tab === 'issue') include __DIR__ . '/acct_invoice_issue.php';
        elseif ($tab === 'match') include __DIR__ . '/acct_invoice_match.php';
        elseif ($tab === 'pattern') include __DIR__ . '/acct_invoice_pattern.php';
        ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
