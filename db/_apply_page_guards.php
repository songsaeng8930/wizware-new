<?php
/**
 * 관리자/매니저 전용 페이지에 접근권한 가드(requireMenuPermission)를 일괄 삽입.
 *
 * - 각 페이지의 header.php include 줄 "앞"에 가드를 넣는다 (출력 전 리다이렉트 → 깔끔).
 * - menu_key 는 사이드바 메뉴 id 와 동일 → 접근권한 관리(menu_permissions) 매트릭스가 실제로 작동.
 * - 멱등: 이미 requireMenuPermission 이 있으면 건너뜀.
 * - admin 은 permissions.php 에서 항상 통과하므로 대표는 절대 잠기지 않음.
 *
 * 실행: php db/_apply_page_guards.php   (실제 적용)
 *       php db/_apply_page_guards.php --dry  (미리보기)
 */

$dry = in_array('--dry', $argv, true);
$pagesDir = __DIR__ . '/../pages';

// 페이지 → 권한키 (사이드바 메뉴 id)
$map = [
    'organization' => 'hr', 'employees' => 'hr', 'employee_register' => 'hr',
    'employee_bulk' => 'hr', 'employee_change_requests' => 'hr',
    'labor' => 'labor', 'labor_contract_form' => 'labor', 'labor_contract_template' => 'labor',
    'approval_status' => 'approval_admin', 'approval_line' => 'approval_admin',
    'approval_forms' => 'approval_admin', 'approval_form_register' => 'approval_admin',
    'approval_audit' => 'approval_admin', 'approval_delegate' => 'approval_admin',
    'acct_dashboard' => 'accounting', 'acct_bank' => 'accounting', 'acct_card' => 'accounting',
    'acct_invoice' => 'accounting', 'acct_report' => 'accounting', 'acct_settings' => 'accounting',
    'tax_account_popbill' => 'accounting',
    'business' => 'business', 'business_detail' => 'business',
    'business_docs' => 'business_docs',
    'settings' => 'groupware', 'api_settings' => 'groupware', 'manual' => 'groupware',
];

$done = 0; $skip = 0; $miss = 0;
foreach ($map as $page => $key) {
    $file = "$pagesDir/$page.php";
    if (!is_file($file)) { echo "  ❌ 없음: $page.php\n"; $miss++; continue; }
    $src = file_get_contents($file);

    if (strpos($src, 'requireMenuPermission') !== false) {
        echo "  ⏭  이미 가드됨: $page.php\n"; $skip++; continue;
    }

    // header.php include 줄 찾기 (require_once 또는 include)
    if (!preg_match('/^([ \t]*)(?:require_once|include)\s+__DIR__\s*\.\s*[\'"]\/\.\.\/includes\/header\.php[\'"]\s*;.*$/m', $src, $m, PREG_OFFSET_CAPTURE)) {
        echo "  ⚠  header.php 줄 못 찾음: $page.php\n"; $miss++; continue;
    }
    $indent = $m[1][0];
    $insertPos = $m[0][1];

    $guard = $indent . "require_once __DIR__ . '/../includes/permissions.php';\n"
           . $indent . "requireMenuPermission('$key', 'view'); // 접근권한 관리 연동 (admin 항상 통과)\n";

    $newSrc = substr($src, 0, $insertPos) . $guard . substr($src, $insertPos);

    if ($dry) {
        echo "  ✎  [DRY] $page.php ← '$key'\n";
    } else {
        file_put_contents($file, $newSrc);
        echo "  ✅ $page.php ← '$key'\n";
    }
    $done++;
}

echo "\n결과: 적용 $done · 스킵 $skip · 문제 $miss" . ($dry ? " (DRY RUN)" : "") . "\n";
