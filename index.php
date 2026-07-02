<?php
/**
 * Zaemit 그룹웨어 - 메인 진입점
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: pages/login.php');
}
exit;
