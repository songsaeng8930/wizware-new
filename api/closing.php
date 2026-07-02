<?php
/**
 * Zaemit 그룹웨어 - 결산 준비 현황 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

$action = $_GET['action'] ?? '';

$closingWriteActions = ['toggleCheck', 'uploadFile', 'deleteFile', 'downloadZip'];
if (in_array($action, $closingWriteActions, true)) {
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 또는 매니저 권한이 필요합니다.']);
    }
}

try {
    match ($action) {
        'getStatus'    => getStatus($pdo),
        'getChecklist' => getChecklist($pdo),
        'toggleCheck'  => toggleCheck($pdo),
        'getFiles'     => getFiles($pdo),
        'uploadFile'   => uploadFile($pdo),
        'deleteFile'   => deleteFile($pdo),
        'downloadZip'  => downloadZip($pdo),
        default        => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (PDOException $e) {
    error_log('Closing API: ' . $e->getMessage());
    respond(500, ['error' => '서버 오류가 발생했습니다.']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

/* ── 자동 체크 항목 상태 조회 ── */
function getStatus(PDO $pdo): void
{
    $fy = intval($_GET['fy'] ?? date('Y'));
    $data = ['fiscal_year' => $fy];

    // 1. 법인카드 정산 현황
    try {
        $st = $pdo->prepare('SELECT COUNT(*) as total, SUM(is_settled = 0) as unsettled, SUM(compliance_status = ?) as unchecked_compliance, COALESCE(SUM(amount), 0) as total_amount FROM card_expenses WHERE YEAR(usage_date) = ?');
        $st->execute(['미확인', $fy]);
        $data['card'] = $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data['card'] = ['total' => 0, 'unsettled' => 0, 'unchecked_compliance' => 0, 'total_amount' => 0];
    }

    // 2. 통장 거래 확정 현황
    try {
        $st = $pdo->prepare('SELECT COUNT(*) as total, SUM(is_confirmed = 0) as unconfirmed, COALESCE(SUM(CASE WHEN tx_type = ? THEN amount ELSE 0 END), 0) as total_income, COALESCE(SUM(CASE WHEN tx_type = ? THEN amount ELSE 0 END), 0) as total_expense FROM bank_transactions WHERE YEAR(transaction_date) = ?');
        $st->execute(['입금', '출금', $fy]);
        $data['bank'] = $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data['bank'] = ['total' => 0, 'unconfirmed' => 0, 'total_income' => 0, 'total_expense' => 0];
    }

    // 3. 세금계산서 합계
    try {
        $st = $pdo->prepare('SELECT invoice_type, COUNT(*) as cnt, COALESCE(SUM(supply_amount), 0) as supply, COALESCE(SUM(tax_amount), 0) as tax FROM tax_invoices WHERE YEAR(issue_date) = ? AND invoice_status = ? GROUP BY invoice_type');
        $st->execute([$fy, '정상']);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $data['invoice'] = ['매출' => ['cnt' => 0, 'supply' => 0, 'tax' => 0], '매입' => ['cnt' => 0, 'supply' => 0, 'tax' => 0]];
        foreach ($rows as $r) {
            $data['invoice'][$r['invoice_type']] = ['cnt' => (int)$r['cnt'], 'supply' => (int)$r['supply'], 'tax' => (int)$r['tax']];
        }
    } catch (PDOException $e) {
        $data['invoice'] = ['매출' => ['cnt' => 0, 'supply' => 0, 'tax' => 0], '매입' => ['cnt' => 0, 'supply' => 0, 'tax' => 0]];
    }

    // 4. 급여대장 월별 존재
    try {
        $st = $pdo->prepare('SELECT DISTINCT month FROM payslips WHERE year = ?');
        $st->execute([$fy]);
        $months = $st->fetchAll(PDO::FETCH_COLUMN);
        $data['payslip'] = ['months' => array_map('intval', $months), 'total_months' => count($months)];

        $st2 = $pdo->prepare('SELECT COALESCE(SUM(gross_pay), 0) as total_gross FROM payslips WHERE year = ?');
        $st2->execute([$fy]);
        $data['payslip']['total_gross'] = (int)$st2->fetchColumn();
    } catch (PDOException $e) {
        $data['payslip'] = ['months' => [], 'total_months' => 0, 'total_gross' => 0];
    }

    respond(200, $data);
}

/* ── 수동 체크리스트 조회 ── */
function getChecklist(PDO $pdo): void
{
    $fy = intval($_GET['fy'] ?? date('Y'));

    try {
        $pdo->query('SELECT 1 FROM closing_checklist LIMIT 1');
    } catch (PDOException $e) {
        respond(200, ['items' => []]);
        return;
    }

    $st = $pdo->prepare('SELECT item_key, is_checked, checked_by, checked_at, note FROM closing_checklist WHERE fiscal_year = ?');
    $st->execute([$fy]);
    $items = $st->fetchAll(PDO::FETCH_ASSOC);

    respond(200, ['items' => $items]);
}

/* ── 수동 체크 토글 ── */
function toggleCheck(PDO $pdo): void
{
    $input = getJsonInput();
    $fy = intval($input['fiscal_year'] ?? 0);
    $key = trim($input['item_key'] ?? '');
    $checked = intval($input['is_checked'] ?? 0);

    if (!$fy || !$key) {
        respond(400, ['error' => '필수 파라미터가 누락되었습니다.']);
    }

    $st = $pdo->prepare('INSERT INTO closing_checklist (fiscal_year, item_key, is_checked, checked_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_checked = VALUES(is_checked), checked_at = NOW()');
    $st->execute([$fy, $key, $checked]);

    respond(200, ['success' => true]);
}

/* ── 첨부파일 목록 ── */
function getFiles(PDO $pdo): void
{
    $fy = intval($_GET['fy'] ?? date('Y'));

    $st = $pdo->prepare('SELECT id, file_name, file_size, uploaded_by, uploaded_at FROM closing_attachments WHERE fiscal_year = ? ORDER BY uploaded_at DESC');
    $st->execute([$fy]);
    respond(200, ['files' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ── 파일 업로드 ── */
function uploadFile(PDO $pdo): void
{
    $fy = intval($_POST['fiscal_year'] ?? 0);
    if (!$fy || empty($_FILES['file'])) {
        respond(400, ['error' => '파일과 귀속연도를 지정해주세요.']);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(400, ['error' => '파일 업로드 오류: ' . $file['error']]);
    }

    $uploadDir = __DIR__ . '/../uploads/closing/' . $fy;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9가-힣._-]/u', '', $file['name']);
    $destPath = $uploadDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        respond(500, ['error' => '파일 저장 실패']);
    }

    $relPath = 'uploads/closing/' . $fy . '/' . $safeName;
    $st = $pdo->prepare('INSERT INTO closing_attachments (fiscal_year, file_name, file_path, file_size) VALUES (?, ?, ?, ?)');
    $st->execute([$fy, $file['name'], $relPath, $file['size']]);

    respond(200, ['success' => true, 'id' => $pdo->lastInsertId(), 'file_name' => $file['name']]);
}

/* ── 파일 삭제 ── */
function deleteFile(PDO $pdo): void
{
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    if (!$id) respond(400, ['error' => 'ID를 지정해주세요.']);

    $st = $pdo->prepare('SELECT file_path FROM closing_attachments WHERE id = ?');
    $st->execute([$id]);
    $path = $st->fetchColumn();

    if ($path) {
        $fullPath = __DIR__ . '/../' . $path;
        if (file_exists($fullPath)) unlink($fullPath);
    }

    $st = $pdo->prepare('DELETE FROM closing_attachments WHERE id = ?');
    $st->execute([$id]);

    respond(200, ['success' => true]);
}

/* ── 전체 결산자료 ZIP 다운로드 ──
 *  첨부 파일(closing_attachments) + 주요 데이터(카드경비/통장/세금계산서) CSV를 하나의 ZIP으로 번들링.
 *  CSRF 토큰 검증이 미리 api_auth.php에서 이루어졌음 · 이 응답은 바이너리를 반환하므로 JSON respond() 대신 직접 헤더 작성.
 */
function downloadZip(PDO $pdo): void
{
    $fy = intval($_GET['fy'] ?? date('Y'));
    if (!$fy) respond(400, ['error' => '귀속연도를 지정해주세요.']);
    if (!class_exists('ZipArchive')) respond(500, ['error' => '서버에 ZipArchive 확장이 없습니다.']);

    $tmp = tempnam(sys_get_temp_dir(), 'closing_');
    if ($tmp === false) respond(500, ['error' => '임시 파일 생성 실패']);

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) respond(500, ['error' => 'ZIP 생성 실패']);

    // 1) 첨부 파일 번들
    try {
        $st = $pdo->prepare('SELECT file_name, file_path FROM closing_attachments WHERE fiscal_year = ?');
        $st->execute([$fy]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $abs = __DIR__ . '/../' . $row['file_path'];
            if (is_file($abs)) {
                $zip->addFile($abs, 'attachments/' . $row['file_name']);
            }
        }
    } catch (PDOException $e) { /* 테이블 없음은 무시 */ }

    // 2) 카드경비 CSV
    try {
        $st = $pdo->prepare('SELECT usage_date, card_id, amount, business_name, category, sub_category, user_name FROM card_expenses WHERE YEAR(usage_date) = ? ORDER BY usage_date');
        $st->execute([$fy]);
        $zip->addFromString('card_expenses.csv', rowsToCsvBom(
            [['사용일', '카드ID', '금액', '가맹점', '카테고리', '세부', '사용자']],
            $st->fetchAll(PDO::FETCH_NUM)
        ));
    } catch (PDOException $e) { /* noop */ }

    // 3) 통장 거래 CSV
    try {
        $st = $pdo->prepare('SELECT transaction_date, tx_type, amount, description, counterparty FROM bank_transactions WHERE YEAR(transaction_date) = ? ORDER BY transaction_date');
        $st->execute([$fy]);
        $zip->addFromString('bank_transactions.csv', rowsToCsvBom(
            [['일자', '구분', '금액', '내용', '상대']],
            $st->fetchAll(PDO::FETCH_NUM)
        ));
    } catch (PDOException $e) { /* noop */ }

    // 4) 세금계산서 CSV
    try {
        $st = $pdo->prepare('SELECT issue_date, invoice_type, supplier_name, buyer_name, supply_amount, tax_amount FROM tax_invoices WHERE YEAR(issue_date) = ? ORDER BY issue_date');
        $st->execute([$fy]);
        $zip->addFromString('tax_invoices.csv', rowsToCsvBom(
            [['발행일', '구분', '공급자', '매입자', '공급가액', '세액']],
            $st->fetchAll(PDO::FETCH_NUM)
        ));
    } catch (PDOException $e) { /* noop */ }

    $zip->close();

    // 전송
    $filename = sprintf('결산자료_%d.zip', $fy);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

/** CSV 생성 (UTF-8 BOM 포함) */
function rowsToCsvBom(array $header, array $rows): string
{
    $out = "\xEF\xBB\xBF";
    $fmt = function (array $r): string {
        return implode(',', array_map(function ($v) {
            $s = (string)($v ?? '');
            $s = str_replace(["\r\n", "\n", "\r"], ' ', $s);
            if (preg_match('/^[=+\-@]/', $s)) $s = "'" . $s;
            if (preg_match('/[",]/', $s)) $s = '"' . str_replace('"', '""', $s) . '"';
            return $s;
        }, $r));
    };
    foreach ($header as $h) $out .= $fmt($h) . "\r\n";
    foreach ($rows as $r) $out .= $fmt($r) . "\r\n";
    return $out;
}
