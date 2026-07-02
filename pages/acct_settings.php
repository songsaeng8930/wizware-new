<?php
$pageTitle = '환경설정';
$currentPage = 'accounting';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bankapi.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$tab = $_GET['tab'] ?? 'bank';
$tabs = [
    'bank' => ['label' => '계좌등록', 'icon' => 'landmark'],
    'card' => ['label' => '카드등록', 'icon' => 'credit-card'],
    'hometax' => ['label' => '홈택스 연동', 'icon' => 'link-2'],
    'cert' => ['label' => '공인인증서', 'icon' => 'shield-check'],
    'categories' => ['label' => '계정과목', 'icon' => 'list-tree'],
];

// acct_bank_register.php가 기대하는 변수
$banks = BANKAPI_BANKS;
$apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-gray-50">
        <!-- 헤더 -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-[var(--zm-text-strong)]">환경설정</h2>
                <p class="text-sm text-[var(--zm-text-muted)] mt-0.5">재무관리에 필요한 계좌 · 카드 · 계정과목 · 홈택스 · 인증서를 설정합니다</p>
            </div>
        </div>

        <!-- 탭 네비게이션 -->
        <div class="zm-tab-container mb-5">
            <?php foreach ($tabs as $key => $info): ?>
            <a href="?tab=<?= $key ?>" class="zm-tab <?= $tab === $key ? 'zm-tab-active' : '' ?>">
                <i data-lucide="<?= $info['icon'] ?>" class="w-4 h-4"></i>
                <?= $info['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php
        if ($tab === 'card') include __DIR__ . '/acct_card_register.php';
        elseif ($tab === 'bank') include __DIR__ . '/acct_bank_register.php';
        elseif ($tab === 'categories') include __DIR__ . '/acct_bank_categories.php';
        elseif ($tab === 'hometax') include __DIR__ . '/acct_settings_hometax.php';
        elseif ($tab === 'cert') include __DIR__ . '/acct_settings_cert.php';
        ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
