<?php

$source = file_get_contents(__DIR__ . '/../pages/payslip.php');
if ($source === false) {
    fwrite(STDERR, "pages/payslip.php 파일을 읽을 수 없습니다.\n");
    exit(1);
}

$normalized = str_replace(["\r\n", "\r"], "\n", $source);

function assertSourceContains(string $needle, string $source, string $message): void
{
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, $message . "\n");
        fwrite(STDERR, 'Missing: ' . $needle . "\n");
        exit(1);
    }
}

function assertSourceNotContains(string $needle, string $source, string $message): void
{
    if (str_contains($source, $needle)) {
        fwrite(STDERR, $message . "\n");
        fwrite(STDERR, 'Found: ' . $needle . "\n");
        exit(1);
    }
}

assertSourceContains("\$currentPage = 'my_payslip';", $normalized, '내 급여 메뉴가 노무관리 메뉴로 활성화되면 안 됩니다.');
assertSourceContains('$currentEmployeeId', $normalized, '급여 조회는 세션의 현재 직원 ID를 기준으로 해야 합니다.');
assertSourceContains('WHERE p.year = ? AND p.month = ? AND p.employee_id = ?', $normalized, '저장 급여 조회는 현재 직원 1명으로 제한되어야 합니다.');
assertSourceNotContains('WHERE e.is_active = 1', $normalized, '내 급여 페이지가 전체 활성 직원을 조회하면 안 됩니다.');
assertSourceNotContains('function savePayslips(', $normalized, '내 급여 페이지에 관리자 저장 액션이 있으면 안 됩니다.');
assertSourceNotContains('function syncAttendance(', $normalized, '내 급여 페이지에 관리자 근태연동 액션이 있으면 안 됩니다.');
assertSourceNotContains('api/payslip.php?action=save', $normalized, '내 급여 페이지에서 급여 저장 API를 호출하면 안 됩니다.');
assertSourceNotContains('급여 확정 저장', $normalized, '내 급여 페이지에 관리자용 급여 확정 버튼이 보이면 안 됩니다.');
assertSourceNotContains('엑셀 다운', $normalized, '내 급여 페이지에 전체 급여 다운로드 버튼이 보이면 안 됩니다.');

echo "payslip_personal_scope_test passed\n";
