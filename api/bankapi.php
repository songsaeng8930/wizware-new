<?php
/**
 * Bank API 프록시 엔드포인트
 *
 * 프론트엔드 → 이 파일 → bankapi.co.kr
 * API 키를 클라이언트에 노출하지 않기 위한 서버사이드 프록시
 *
 * 사용법:
 *   POST /api/bankapi.php?action=register_account
 *   POST /api/bankapi.php?action=delete_account
 *   GET  /api/bankapi.php?action=list_accounts
 *   POST /api/bankapi.php?action=check_account
 *   POST /api/bankapi.php?action=transactions
 */

header('Content-Type: application/json; charset=utf-8');
set_time_limit(120);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../bankapi_error.log');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/bankapi.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';

$action = $_GET['action'] ?? '';

// POST 바디 파싱
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$bankapiWriteActions = [
    'register_account', 'delete_account', 'toggle_account', 'update_account',
    'save_category', 'delete_category', 'save_journal', 'delete_journal',
    'closing_lock', 'save_biz_number',
];
if (in_array($action, $bankapiWriteActions, true)) {
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, '관리자 또는 매니저 권한이 필요합니다.');
    }
}

try {
    switch ($action) {

        // ─── 계좌 등록 ───
        case 'register_account':
            $bankCode      = trim($input['bankCode'] ?? '');
            $accountNumber = preg_replace('/[^0-9]/', '', $input['accountNumber'] ?? '');

            if (!$bankCode || !$accountNumber) {
                respond(400, '은행코드와 계좌번호는 필수입니다.');
            }
            if (!isset(BANKAPI_BANKS[$bankCode])) {
                respond(400, '지원하지 않는 은행입니다. 지원: ' . implode(', ', array_keys(BANKAPI_BANKS)));
            }

            $result = bankapi_request('POST', '/v1/accounts', [
                'bankCode'      => $bankCode,
                'accountNumber' => $accountNumber,
            ]);

            $alreadyRegistered = !$result['ok'] && isAlreadyRegisteredError($result);

            if ($result['ok'] || $alreadyRegistered) {
                syncAccountToLocalDB($bankCode, $accountNumber);
                $msg = $alreadyRegistered ? '이미 연결된 계좌입니다. 목록에 추가했습니다.' : '계좌가 등록되었습니다.';
                respond(200, $msg, $result['data'] ?? []);
            } else {
                respondError($result);
            }
            break;

        // ─── 계좌 삭제 ───
        case 'delete_account':
            $bankCode      = trim($input['bankCode'] ?? '');
            $accountNumber = preg_replace('/[^0-9]/', '', $input['accountNumber'] ?? '');

            if (!$bankCode || !$accountNumber) {
                respond(400, '은행코드와 계좌번호는 필수입니다.');
            }

            $result = bankapi_request('DELETE', '/v1/accounts', [
                'bankCode'      => $bankCode,
                'accountNumber' => $accountNumber,
            ]);

            if ($result['ok']) {
                removeAccountFromLocalDB($bankCode, $accountNumber);
                respond(200, '계좌가 삭제되었습니다.', $result['data']);
            } else {
                respondError($result);
            }
            break;

        // ─── 등록 계좌 목록 ───
        case 'list_accounts':
            $apiResult = bankapi_request('GET', '/v1/accounts');
            $apiCount = 0;
            $maxAccounts = 5;
            if ($apiResult['ok'] && is_array($apiResult['data'])) {
                $inner = $apiResult['data']['data'] ?? $apiResult['data'];
                $apiCount = (int)($inner['registeredCount'] ?? 0);
                $maxAccounts = (int)($inner['maxAccounts'] ?? 5);
            }

            $pdo = getDBConnection();
            $accounts = [];
            if ($pdo) {
                $stmt = $pdo->query('SELECT id, bank_code, bank_name, account_no, account_alias, owner_name, account_password, created_at FROM bank_accounts WHERE is_active = 1 ORDER BY created_at ASC');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $accounts[] = [
                        'id'            => (int)$row['id'],
                        'bankCode'      => $row['bank_code'],
                        'bankName'      => $row['bank_name'],
                        'accountNumber' => $row['account_no'],
                        'alias'         => $row['account_alias'],
                        'ownerName'     => $row['owner_name'],
                        'hasPassword'   => !empty($row['account_password']),
                        'createdAt'     => $row['created_at'],
                    ];
                }
            }

            $localCount = count($accounts);
            $syncWarning = null;
            if ($apiResult['ok'] && $apiCount !== $localCount) {
                $syncWarning = "Bank API 등록 {$apiCount}건, 로컬 {$localCount}건 · 불일치";
            }

            respond(200, 'OK', [
                'accounts'        => $accounts,
                'registeredCount' => $localCount,
                'maxAccounts'     => $maxAccounts,
                'remaining'       => max(0, $maxAccounts - $apiCount),
                'syncWarning'     => $syncWarning,
            ]);
            break;

        // ─── 계좌 등록 여부 확인 ───
        case 'check_account':
            $bankCode      = trim($input['bankCode'] ?? '');
            $accountNumber = preg_replace('/[^0-9]/', '', $input['accountNumber'] ?? '');

            if (!$bankCode || !$accountNumber) {
                respond(400, '은행코드와 계좌번호는 필수입니다.');
            }

            $result = bankapi_request('POST', '/v1/accounts/check', [
                'bankCode'      => $bankCode,
                'accountNumber' => $accountNumber,
            ]);

            if ($result['ok']) {
                respond(200, 'OK', $result['data']);
            } else {
                respondError($result);
            }
            break;

        // ─── 거래내역 조회 ───
        case 'transactions':
            $bankCode        = trim($input['bankCode'] ?? '');
            $accountNumber   = preg_replace('/[^0-9]/', '', $input['accountNumber'] ?? '');
            $accountPassword = $input['accountPassword'] ?? '';
            $residentNumber  = preg_replace('/[^0-9]/', '', $input['residentNumber'] ?? '');
            $startDate       = preg_replace('/[^0-9]/', '', $input['startDate'] ?? '');
            $endDate         = preg_replace('/[^0-9]/', '', $input['endDate'] ?? '');

            if ($accountPassword === '__saved__') {
                $pdo = getDBConnection();
                if ($pdo) {
                    $pwStmt = $pdo->prepare('SELECT account_password FROM bank_accounts WHERE bank_code = ? AND account_no = ? AND is_active = 1 LIMIT 1');
                    $pwStmt->execute([$bankCode, $accountNumber]);
                    $encrypted = $pwStmt->fetchColumn() ?: '';
                    $accountPassword = $encrypted ? decryptValue($encrypted) : '';
                }
            }

            if (!$bankCode || !$accountNumber || !$accountPassword || !$residentNumber) {
                respond(400, '은행코드, 계좌번호, 계좌비밀번호, 주민(사업자)번호는 필수입니다.');
            }
            if (!$startDate || !$endDate) {
                respond(400, '조회 시작일과 종료일은 필수입니다. (YYYYMMDD)');
            }

            set_time_limit(120);
            $result = bankapi_request('POST', '/v1/transactions', [
                'bankCode'        => $bankCode,
                'accountNumber'   => $accountNumber,
                'accountPassword' => $accountPassword,
                'residentNumber'  => $residentNumber,
                'startDate'       => $startDate,
                'endDate'         => $endDate,
            ]);

            $allTransactions = [];
            $totalSaved = 0;
            $lastError = null;

            if ($result['ok']) {
                $inner = $result['data'];
                if (isset($inner['success']) && $inner['success'] === false) {
                    $lastError = $inner['error'] ?? $inner['message'] ?? '거래내역 조회에 실패했습니다.';
                } else {
                    $totalSaved = persistTransactions($bankCode, $accountNumber, $inner);
                    $txList = $inner['data']['transactions'] ?? $inner['transactions'] ?? $inner['data'] ?? [];
                    if (is_array($txList) && !empty($txList) && isset($txList[0])) {
                        $allTransactions = $txList;
                    }
                }
            } else {
                $lastError = $result['data']['error'] ?? $result['data']['message'] ?? 'API 요청 실패';
            }

            if (empty($allTransactions) && $totalSaved === 0 && $lastError) {
                respond(400, $lastError);
            }

            $dbRecords = getDbTransactions($bankCode, $accountNumber, $startDate, $endDate);
            respond(200, 'OK', [
                'transactions'   => $allTransactions,
                'dbTransactions' => $dbRecords,
                'saved'          => $totalSaved,
            ]);
            break;

        // ─── DB 캐시에서 거래내역 조회 (은행 API 호출 없음) ───
        case 'db_transactions':
            $bankCode      = trim($input['bankCode'] ?? $_GET['bankCode'] ?? '');
            $accountNumber = preg_replace('/[^0-9]/', '', $input['accountNumber'] ?? $_GET['accountNumber'] ?? '');
            $startDate     = preg_replace('/[^0-9]/', '', $input['startDate'] ?? $_GET['startDate'] ?? '');
            $endDate       = preg_replace('/[^0-9]/', '', $input['endDate'] ?? $_GET['endDate'] ?? '');

            if (!$bankCode || !$accountNumber || !$startDate || !$endDate) {
                respond(400, '은행코드, 계좌번호, 조회기간은 필수입니다.');
            }

            $dbRecords = getDbTransactions($bankCode, $accountNumber, $startDate, $endDate);

            $lastSync = null;
            $latestBalance = null;
            $pdo = getDBConnection();
            if ($pdo) {
                $acctStmt = $pdo->prepare("SELECT id FROM bank_accounts WHERE bank_code = ? AND REPLACE(account_no, '-', '') = ?");
                $acctStmt->execute([$bankCode, $accountNumber]);
                $acctId = $acctStmt->fetchColumn();
                if ($acctId) {
                    $syncStmt = $pdo->prepare('SELECT MAX(uploaded_at) FROM bank_transactions WHERE account_id = ?');
                    $syncStmt->execute([$acctId]);
                    $lastSync = $syncStmt->fetchColumn() ?: null;

                    $balStmt = $pdo->prepare(
                        'SELECT balance FROM bank_transactions WHERE account_id = ?
                         ORDER BY transaction_date DESC, transaction_time DESC, balance DESC LIMIT 1'
                    );
                    $balStmt->execute([$acctId]);
                    $latestBalance = $balStmt->fetchColumn();
                    if ($latestBalance !== false) $latestBalance = (int)$latestBalance;
                    else $latestBalance = null;
                }
            }

            respond(200, 'OK', [
                'dbTransactions' => $dbRecords,
                'fromCache'      => true,
                'lastSync'       => $lastSync,
                'latestBalance'  => $latestBalance,
            ]);
            break;

        // ─── 통장내역 일괄 조회 & DB 저장 ───
        case 'bulk_fetch':
            $startDate = preg_replace('/[^0-9]/', '', $input['startDate'] ?? '');
            $endDate   = preg_replace('/[^0-9]/', '', $input['endDate'] ?? '');
            if (!$startDate || !$endDate) {
                respond(400, '조회 시작일과 종료일은 필수입니다. (YYYYMMDD)');
            }

            $settingsFile = __DIR__ . '/../config/api_settings.json';
            if (!file_exists($settingsFile)) {
                respond(400, '사업자 번호가 등록되지 않았습니다. 계좌관리에서 먼저 등록하세요.');
            }
            $settings = json_decode(file_get_contents($settingsFile), true) ?? [];
            $residentNumber = $settings['bankapi_resident_number'] ?? '';
            $accountPassword = $input['accountPassword'] ?? '';

            if (!$residentNumber || !$accountPassword) {
                respond(400, '사업자(주민)번호와 계좌 비밀번호가 필요합니다.');
            }

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $accounts = $pdo->query("SELECT bank_code, account_no, account_password FROM bank_accounts WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($accounts)) respond(400, '등록된 활성 계좌가 없습니다.');

            $totalSaved = 0;
            $accountResults = [];

            foreach ($accounts as $acct) {
                $acctPw = $accountPassword;
                if ($acctPw === '__saved__' && !empty($acct['account_password'])) {
                    $acctPw = decryptValue($acct['account_password']);
                }

                $fetchResult = bankapi_request('POST', '/v1/transactions', [
                    'bankCode'        => $acct['bank_code'],
                    'accountNumber'   => $acct['account_no'],
                    'accountPassword' => $acctPw,
                    'residentNumber'  => $residentNumber,
                    'startDate'       => $startDate,
                    'endDate'         => $endDate,
                ]);

                $saved = 0;
                if ($fetchResult['ok']) {
                    $fetchInner = $fetchResult['data'];
                    if (!isset($fetchInner['success']) || $fetchInner['success'] !== false) {
                        $saved = persistTransactions($acct['bank_code'], $acct['account_no'], $fetchInner);
                    }
                }

                $totalSaved += $saved;
                $accountResults[] = [
                    'bank_code'  => $acct['bank_code'],
                    'account_no' => substr($acct['account_no'], 0, 6) . '****',
                    'saved'      => $saved,
                ];
            }

            respond(200, '일괄 조회 완료', [
                'accounts'          => count($accounts),
                'transactions_saved' => $totalSaved,
                'details'           => $accountResults,
            ]);
            break;

        // ─── 계좌 관리용 전체 목록 (비활성 포함) ───
        case 'list_accounts_full':
            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            ensureBankAccountColumns($pdo);

            $monthStart = date('Y-m-01');
            $stmt = $pdo->prepare("
                SELECT ba.id, ba.bank_code, ba.bank_name, ba.account_no, ba.account_alias,
                       ba.account_type, ba.owner_name, ba.account_password, ba.memo,
                       ba.consent_agreed, ba.consent_agreed_at,
                       ba.is_active, ba.sort_order, ba.created_at,
                       COUNT(bt.id) as tx_count,
                       MAX(bt.transaction_date) as last_tx_date,
                       COALESCE((SELECT bt2.balance FROM bank_transactions bt2
                           WHERE bt2.account_id = ba.id
                           ORDER BY bt2.transaction_date DESC, bt2.id DESC LIMIT 1), 0) AS latest_balance,
                       (SELECT MAX(bt3.uploaded_at) FROM bank_transactions bt3
                           WHERE bt3.account_id = ba.id) AS last_synced_at,
                       COALESCE((SELECT SUM(bt4.amount) FROM bank_transactions bt4
                           WHERE bt4.account_id = ba.id AND bt4.tx_type = '입금'
                           AND bt4.transaction_date >= ?), 0) AS month_deposit,
                       COALESCE((SELECT SUM(bt5.amount) FROM bank_transactions bt5
                           WHERE bt5.account_id = ba.id AND bt5.tx_type = '출금'
                           AND bt5.transaction_date >= ?), 0) AS month_withdraw
                FROM bank_accounts ba
                LEFT JOIN bank_transactions bt ON bt.account_id = ba.id
                GROUP BY ba.id
                ORDER BY ba.is_active DESC, ba.sort_order ASC, ba.created_at ASC
            ");
            $stmt->execute([$monthStart, $monthStart]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($accounts as &$a) {
                $a['account_password'] = !empty($a['account_password']);
            }
            unset($a);

            $totalBalance = 0;
            foreach ($accounts as $a) {
                if ($a['is_active']) $totalBalance += (int)$a['latest_balance'];
            }

            $today = date('Y-m-d');
            $todayStmt = $pdo->prepare(
                "SELECT tx_type, SUM(amount) as total
                 FROM bank_transactions bt
                 JOIN bank_accounts ba ON ba.id = bt.account_id AND ba.is_active = 1
                 WHERE bt.transaction_date = ?
                 GROUP BY tx_type"
            );
            $todayStmt->execute([$today]);
            $todayDeposit = 0;
            $todayWithdraw = 0;
            while ($row = $todayStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['tx_type'] === '입금') $todayDeposit = (int)$row['total'];
                else $todayWithdraw = (int)$row['total'];
            }

            respond(200, 'OK', [
                'accounts'      => $accounts,
                'totalBalance'  => $totalBalance,
                'todayDeposit'  => $todayDeposit,
                'todayWithdraw' => $todayWithdraw,
            ]);
            break;

        // ─── 계좌 활성/비활성 토글 ───
        case 'toggle_account':
            $accountId = (int)($input['accountId'] ?? 0);
            if (!$accountId) respond(400, '계좌 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $stmt = $pdo->prepare('SELECT id, is_active FROM bank_accounts WHERE id = ?');
            $stmt->execute([$accountId]);
            $acct = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$acct) respond(404, '계좌를 찾을 수 없습니다.');

            $newStatus = $acct['is_active'] ? 0 : 1;
            $pdo->prepare('UPDATE bank_accounts SET is_active = ? WHERE id = ?')
                ->execute([$newStatus, $accountId]);

            respond(200, $newStatus ? '계좌가 활성화되었습니다.' : '계좌가 비활성화되었습니다.', [
                'is_active' => $newStatus,
            ]);
            break;

        // ─── 계좌 정보 수정 (별칭+예금주+동의) ───
        case 'update_account':
            $accountId = (int)($input['accountId'] ?? 0);
            if (!$accountId) respond(400, '계좌 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');
            ensureBankAccountColumns($pdo);

            $stmt = $pdo->prepare('SELECT id FROM bank_accounts WHERE id = ?');
            $stmt->execute([$accountId]);
            if (!$stmt->fetch()) respond(404, '계좌를 찾을 수 없습니다.');

            $fields = [];
            $params = [];

            if (isset($input['alias'])) {
                $fields[] = 'account_alias = ?';
                $params[] = trim($input['alias']) ?: null;
            }
            if (isset($input['accountType'])) {
                $validTypes = ['운영','급여','세금','예비','기타'];
                $type = trim($input['accountType']);
                if (in_array($type, $validTypes, true)) {
                    $fields[] = 'account_type = ?';
                    $params[] = $type;
                }
            }
            if (isset($input['ownerName'])) {
                $fields[] = 'owner_name = ?';
                $params[] = trim($input['ownerName']);
            }
            if (isset($input['memo'])) {
                $fields[] = 'memo = ?';
                $params[] = trim($input['memo']) ?: null;
            }
            if (isset($input['accountPassword'])) {
                $pw = trim($input['accountPassword']);
                $fields[] = 'account_password = ?';
                $params[] = $pw !== '' ? encryptValue($pw) : null;
            }
            if (isset($input['consentAgreed'])) {
                $fields[] = 'consent_agreed = ?';
                $params[] = $input['consentAgreed'] ? 1 : 0;
                if ($input['consentAgreed']) {
                    $fields[] = 'consent_agreed_at = NOW()';
                }
            }
            if (isset($input['sortOrder'])) {
                $fields[] = 'sort_order = ?';
                $params[] = (int)$input['sortOrder'];
            }

            if (empty($fields)) respond(400, '수정할 항목이 없습니다.');

            $params[] = $accountId;
            $pdo->prepare('UPDATE bank_accounts SET ' . implode(', ', $fields) . ' WHERE id = ?')
                ->execute($params);

            respond(200, '계좌 정보가 수정되었습니다.');
            break;

        // ─── 계좌 별칭 수정 (하위호환) ───
        case 'update_alias':
            $bankCode      = trim($input['bankCode'] ?? '');
            $accountNumber = preg_replace('/[^0-9]/', '', $input['accountNumber'] ?? '');
            $alias         = trim($input['alias'] ?? '');

            if (!$bankCode || !$accountNumber) {
                respond(400, '은행코드와 계좌번호는 필수입니다.');
            }

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $stmt = $pdo->prepare('UPDATE bank_accounts SET account_alias = ? WHERE bank_code = ? AND account_no = ?');
            $stmt->execute([$alias ?: null, $bankCode, $accountNumber]);

            if ($stmt->rowCount() > 0) {
                respond(200, '별칭이 저장되었습니다.');
            } else {
                respond(404, '해당 계좌를 찾을 수 없습니다.');
            }
            break;

        // ─── 사업자번호 저장/조회 ───
        case 'resident_number':
            $settingsFile = __DIR__ . '/../config/api_settings.json';
            $settings = json_decode(file_get_contents($settingsFile) ?: '{}', true) ?? [];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $num = preg_replace('/[^0-9]/', '', $input['residentNumber'] ?? '');
                if (!$num) respond(400, '사업자(주민)번호를 입력하세요.');
                $settings['bankapi_resident_number'] = $num;
                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                respond(200, '저장되었습니다.');
            } else {
                respond(200, 'OK', ['residentNumber' => $settings['bankapi_resident_number'] ?? '']);
            }
            break;

        // ─── 계정과목 목록 ───
        case 'get_categories':
            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $stmt = $pdo->query(
                "SELECT code, name, type, tax_type
                 FROM account_categories
                 WHERE is_active = 1 AND code NOT LIKE 'G\\_%' ESCAPE '\\\\'
                 ORDER BY FIELD(type, '자산','부채','자본','매출','매입','비용','수익'), sort_order"
            );
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(200, 'OK', ['categories' => $categories]);
            break;

        // ─── 계정과목 전체 목록 (관리용, 비활성 포함) ───
        case 'list_categories_full':
            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $chk = $pdo->query("SELECT COUNT(*) FROM account_categories WHERE code LIKE 'G\\_%' ESCAPE '\\\\'");
            if ((int)$chk->fetchColumn() === 0) {
                $pdo->exec("INSERT IGNORE INTO account_categories (code, name, parent_code, type, tax_type, is_active, sort_order) VALUES
                    ('G_CA',  '유동자산',       NULL, '자산', '불공제', 1, 1000),
                    ('G_FA',  '유형자산',       NULL, '자산', '불공제', 1, 1085),
                    ('G_IA',  '무형자산',       NULL, '자산', '불공제', 1, 1115),
                    ('G_OA',  '기타비유동자산', NULL, '자산', '불공제', 1, 1135),
                    ('G_CL',  '유동부채',       NULL, '부채', '불공제', 1, 2000),
                    ('G_NL',  '비유동부채',     NULL, '부채', '불공제', 1, 2105),
                    ('G_EQ',  '자본',           NULL, '자본', '불공제', 1, 3000),
                    ('G_SL',  '매출',           NULL, '매출', '과세',   1, 4000),
                    ('G_CG',  '매출원가',       NULL, '매입', '과세',   1, 4500),
                    ('G_NI',  '영업외수익',     NULL, '수익', '불공제', 1, 4505),
                    ('G_SGA', '판매비와관리비', NULL, '비용', '불공제', 1, 5000),
                    ('G_NE',  '영업외비용',     NULL, '비용', '불공제', 1, 5235)");
            }

            $orphan = $pdo->query("SELECT COUNT(*) FROM account_categories WHERE parent_code IS NULL AND code NOT LIKE 'G\\_%' ESCAPE '\\\\'");
            if ((int)$orphan->fetchColumn() > 0) {
                $pdo->exec("UPDATE account_categories SET parent_code = CASE
                    WHEN code IN ('10100','10300','10800','12000','12600','13100','13300','13500','13600') THEN 'G_CA'
                    WHEN code IN ('21200','21300','21900') THEN 'G_FA'
                    WHEN code IN ('23200','23900') THEN 'G_IA'
                    WHEN code = '96200' THEN 'G_OA'
                    WHEN code IN ('25100','25300','25400','25500','25700','25900','26000','26100','26200','27400','27500') THEN 'G_CL'
                    WHEN code IN ('29300','31200') THEN 'G_NL'
                    WHEN code IN ('33100','34100','37600') THEN 'G_EQ'
                    WHEN code IN ('40100','40200','41200','40400') THEN 'G_SL'
                    WHEN code IN ('45100','45200') THEN 'G_CG'
                    WHEN code IN ('41300','90100','93000') THEN 'G_NI'
                    WHEN code IN ('80200','80300','80600','80900','81100','81200','81300','81400','81600','81700','81900','82000','82100','82300','82500','82700','82800','82900','83000','83100','83300','85200','85300','85600','85700') THEN 'G_SGA'
                    WHEN code IN ('90000','93100','93200','96000') THEN 'G_NE'
                    ELSE parent_code END
                    WHERE parent_code IS NULL AND code NOT LIKE 'G\\_%'");
            }

            $stmt = $pdo->query(
                "SELECT ac.id, ac.code, ac.name, ac.parent_code, ac.type, ac.tax_type,
                        ac.is_active, ac.sort_order,
                        COUNT(bt.id) AS usage_count
                 FROM account_categories ac
                 LEFT JOIN bank_transactions bt ON bt.account_code = ac.code
                 GROUP BY ac.id
                 ORDER BY FIELD(ac.type, '자산','부채','자본','매출','매입','비용','수익'), ac.sort_order, ac.code"
            );
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(200, 'OK', ['categories' => $categories]);
            break;

        // ─── 계정과목 저장 (생성/수정) ───
        case 'save_category':
            $catId = (int)($input['id'] ?? 0);
            $code = trim($input['code'] ?? '');
            $name = trim($input['name'] ?? '');
            $type = trim($input['type'] ?? '');
            $taxType = trim($input['tax_type'] ?? '과세');
            $sortOrder = (int)($input['sort_order'] ?? 0);
            $parentCode = isset($input['parent_code']) ? trim($input['parent_code']) : null;
            if ($parentCode === '') $parentCode = null;

            if (!$code || !$name || !$type) {
                respond(400, '코드, 계정과목명, 분류는 필수입니다.');
            }

            $validTypes = ['매출','매입','자산','부채','자본','비용','수익'];
            if (!in_array($type, $validTypes, true)) {
                respond(400, '유효하지 않은 분류입니다.');
            }

            $validTaxTypes = ['과세','면세','영세율','불공제'];
            if (!in_array($taxType, $validTaxTypes, true)) {
                respond(400, '유효하지 않은 과세구분입니다.');
            }

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            if ($catId > 0) {
                $dupStmt = $pdo->prepare('SELECT id FROM account_categories WHERE code = ? AND id != ?');
                $dupStmt->execute([$code, $catId]);
                if ($dupStmt->fetch()) respond(400, '이미 사용 중인 코드입니다.');

                $oldStmt = $pdo->prepare('SELECT code FROM account_categories WHERE id = ?');
                $oldStmt->execute([$catId]);
                $oldCode = $oldStmt->fetchColumn();

                $stmt = $pdo->prepare(
                    'UPDATE account_categories SET code = ?, name = ?, type = ?, tax_type = ?, sort_order = ?, parent_code = ? WHERE id = ?'
                );
                $stmt->execute([$code, $name, $type, $taxType, $sortOrder, $parentCode, $catId]);

                if ($oldCode && $oldCode !== $code) {
                    $pdo->prepare('UPDATE bank_transactions SET account_code = ?, account_name = ? WHERE account_code = ?')
                        ->execute([$code, $name, $oldCode]);
                } elseif ($oldCode) {
                    $pdo->prepare('UPDATE bank_transactions SET account_name = ? WHERE account_code = ?')
                        ->execute([$name, $code]);
                }

                respond(200, '계정과목이 수정되었습니다.');
            } else {
                $dupStmt = $pdo->prepare('SELECT id FROM account_categories WHERE code = ?');
                $dupStmt->execute([$code]);
                if ($dupStmt->fetch()) respond(400, '이미 사용 중인 코드입니다.');

                $stmt = $pdo->prepare(
                    'INSERT INTO account_categories (code, name, type, tax_type, sort_order, parent_code) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$code, $name, $type, $taxType, $sortOrder, $parentCode]);
                respond(200, '계정과목이 추가되었습니다.', ['id' => (int)$pdo->lastInsertId()]);
            }
            break;

        // ─── 계정과목 활성/비활성 토글 ───
        case 'toggle_category':
            $catId = (int)($input['id'] ?? 0);
            if (!$catId) respond(400, '계정과목 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $stmt = $pdo->prepare('SELECT id, is_active FROM account_categories WHERE id = ?');
            $stmt->execute([$catId]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cat) respond(404, '계정과목을 찾을 수 없습니다.');

            $newStatus = $cat['is_active'] ? 0 : 1;
            $pdo->prepare('UPDATE account_categories SET is_active = ? WHERE id = ?')
                ->execute([$newStatus, $catId]);

            respond(200, $newStatus ? '활성화되었습니다.' : '비활성화되었습니다.', ['is_active' => $newStatus]);
            break;

        // ─── 계정과목 삭제 ───
        case 'delete_category':
            $catId = (int)($input['id'] ?? 0);
            if (!$catId) respond(400, '계정과목 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $stmt = $pdo->prepare('SELECT code FROM account_categories WHERE id = ?');
            $stmt->execute([$catId]);
            $code = $stmt->fetchColumn();
            if (!$code) respond(404, '계정과목을 찾을 수 없습니다.');

            $usageStmt = $pdo->prepare('SELECT COUNT(*) FROM bank_transactions WHERE account_code = ?');
            $usageStmt->execute([$code]);
            $usageCount = (int)$usageStmt->fetchColumn();

            if ($usageCount > 0) {
                respond(400, "이 계정과목은 {$usageCount}건의 거래에서 사용 중입니다. 비활성화를 이용하세요.");
            }

            $childStmt = $pdo->prepare('SELECT COUNT(*) FROM account_categories WHERE parent_code = ?');
            $childStmt->execute([$code]);
            $childCount = (int)$childStmt->fetchColumn();
            if ($childCount > 0) {
                respond(400, "이 그룹에 {$childCount}개의 하위 계정과목이 있습니다. 하위 항목을 먼저 이동/삭제하세요.");
            }

            $pdo->prepare('DELETE FROM account_categories WHERE id = ?')->execute([$catId]);
            respond(200, '삭제되었습니다.');
            break;

        // ─── 전체 계정 요약 (계정별원장 초기 화면) ───
        case 'account_summary':
            require_once __DIR__ . '/../includes/accounting_helpers.php';

            $dateFrom = trim($_GET['date_from'] ?? '');
            $dateTo   = trim($_GET['date_to'] ?? '');
            if (!$dateFrom || !$dateTo) respond(400, '시작일, 종료일이 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $rows = getUnifiedAccountSummary($pdo, $dateFrom, $dateTo);

            $result = [];
            foreach ($rows as $r) {
                $type = $r['type'];
                $isDebitNormal = in_array($type, ['자산', '비용', '매입']);
                $debit  = (int)$r['total_debit'];
                $credit = (int)$r['total_credit'];
                $balance = $isDebitNormal ? ($debit - $credit) : ($credit - $debit);

                $result[] = [
                    'code'       => $r['code'],
                    'name'       => $r['name'],
                    'type'       => $type,
                    'group_name' => $r['group_name'],
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'balance'    => $balance,
                    'tx_count'   => (int)$r['tx_count'],
                ];
            }

            echo json_encode([
                'success' => true,
                'data'    => $result,
                'period'  => ['from' => $dateFrom, 'to' => $dateTo],
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ─── 계정별원장 ───
        case 'account_ledger':
            require_once __DIR__ . '/../includes/accounting_helpers.php';

            $accountCode = trim($_GET['account_code'] ?? '');
            $dateFrom    = trim($_GET['date_from'] ?? '');
            $dateTo      = trim($_GET['date_to'] ?? '');

            if (!$accountCode || !$dateFrom || !$dateTo) {
                respond(400, '계정코드, 시작일, 종료일이 필요합니다.');
            }

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $catStmt = $pdo->prepare('SELECT code, name, type, tax_type, parent_code FROM account_categories WHERE code = ?');
            $catStmt->execute([$accountCode]);
            $account = $catStmt->fetch(PDO::FETCH_ASSOC);
            if (!$account) respond(404, '계정과목을 찾을 수 없습니다.');

            $isDebitNormal = in_array($account['type'], ['자산', '비용', '매입']);

            // 기초잔액: 기간 시작 전 통합 거래 합산
            $unified = getUnifiedTransactionSQL($pdo, 'ut');
            $openStmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN ut.tx_type = '출금' THEN ut.amount ELSE 0 END), 0) AS total_debit,
                    COALESCE(SUM(CASE WHEN ut.tx_type = '입금' THEN ut.amount ELSE 0 END), 0) AS total_credit
                FROM {$unified}
                WHERE ut.account_code = ?
                  AND ut.transaction_date < ?
            ");
            $openStmt->execute([$accountCode, $dateFrom]);
            $openRow = $openStmt->fetch(PDO::FETCH_ASSOC);
            $openingBalance = $isDebitNormal
                ? ((int)$openRow['total_debit'] - (int)$openRow['total_credit'])
                : ((int)$openRow['total_credit'] - (int)$openRow['total_debit']);

            // 기간 내 상세 거래 (상대계정 + 거래처 + 전표번호 포함)
            $hasSplits   = tableExists($pdo, 'transaction_splits');
            $hasJournals = tableExists($pdo, 'journal_entries');

            $ledgerParts = [];
            $ledgerParams = [];

            if ($hasSplits) {
                // 비분할 통장 거래
                $ledgerParts[] = "
                    SELECT bt.id AS source_id, 'bank' AS source,
                           bt.transaction_date, bt.amount, bt.tx_type,
                           bt.description, bt.counterparty, bt.memo,
                           ba.bank_name, ba.account_alias,
                           CONCAT(ba.bank_name, ' ', COALESCE(ba.account_alias,'')) AS counter_name
                    FROM bank_transactions bt
                    LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
                    WHERE bt.account_code = ? AND bt.transaction_date BETWEEN ? AND ?
                      AND bt.id NOT IN (SELECT DISTINCT ts.transaction_id FROM transaction_splits ts)
                ";
                $ledgerParams = array_merge($ledgerParams, [$accountCode, $dateFrom, $dateTo]);

                // 분할 거래
                $ledgerParts[] = "
                    SELECT ts.id AS source_id, 'split' AS source,
                           bt.transaction_date, ts.amount, bt.tx_type,
                           bt.description, bt.counterparty, ts.memo,
                           ba.bank_name, ba.account_alias,
                           CONCAT(ba.bank_name, ' ', COALESCE(ba.account_alias,'')) AS counter_name
                    FROM transaction_splits ts
                    JOIN bank_transactions bt ON bt.id = ts.transaction_id
                    LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
                    WHERE ts.account_code = ? AND bt.transaction_date BETWEEN ? AND ?
                ";
                $ledgerParams = array_merge($ledgerParams, [$accountCode, $dateFrom, $dateTo]);
            } else {
                $ledgerParts[] = "
                    SELECT bt.id AS source_id, 'bank' AS source,
                           bt.transaction_date, bt.amount, bt.tx_type,
                           bt.description, bt.counterparty, bt.memo,
                           ba.bank_name, ba.account_alias,
                           CONCAT(ba.bank_name, ' ', COALESCE(ba.account_alias,'')) AS counter_name
                    FROM bank_transactions bt
                    LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
                    WHERE bt.account_code = ? AND bt.transaction_date BETWEEN ? AND ?
                ";
                $ledgerParams = array_merge($ledgerParams, [$accountCode, $dateFrom, $dateTo]);
            }

            if ($hasJournals) {
                // 조정분개 · 차변
                $ledgerParts[] = "
                    SELECT je.id AS source_id, 'journal_dr' AS source,
                           je.entry_date AS transaction_date, je.amount, '출금' AS tx_type,
                           je.description, '' AS counterparty, je.memo,
                           '조정분개' AS bank_name, '' AS account_alias,
                           je.credit_name AS counter_name
                    FROM journal_entries je
                    WHERE je.debit_code = ? AND je.entry_date BETWEEN ? AND ?
                ";
                $ledgerParams = array_merge($ledgerParams, [$accountCode, $dateFrom, $dateTo]);

                // 조정분개 · 대변
                $ledgerParts[] = "
                    SELECT je.id AS source_id, 'journal_cr' AS source,
                           je.entry_date AS transaction_date, je.amount, '입금' AS tx_type,
                           je.description, '' AS counterparty, je.memo,
                           '조정분개' AS bank_name, '' AS account_alias,
                           je.debit_name AS counter_name
                    FROM journal_entries je
                    WHERE je.credit_code = ? AND je.entry_date BETWEEN ? AND ?
                ";
                $ledgerParams = array_merge($ledgerParams, [$accountCode, $dateFrom, $dateTo]);
            }

            $ledgerSQL = implode("\nUNION ALL\n", $ledgerParts) . "\nORDER BY transaction_date ASC, source_id ASC";
            $ledgerStmt = $pdo->prepare($ledgerSQL);
            $ledgerStmt->execute($ledgerParams);
            $transactions = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

            // 기간 합계 + 전표번호 생성
            $periodDebit = 0;
            $periodCredit = 0;
            $srcPrefix = ['bank' => 'BT', 'split' => 'SP', 'journal_dr' => 'JE', 'journal_cr' => 'JE'];
            foreach ($transactions as &$tx) {
                $tx['amount'] = (int)$tx['amount'];
                $tx['ref_no'] = ($srcPrefix[$tx['source']] ?? 'TX') . '-' . $tx['source_id'];
                if ($tx['tx_type'] === '출금') {
                    $tx['debit'] = $tx['amount'];
                    $tx['credit'] = 0;
                    $periodDebit += $tx['amount'];
                } else {
                    $tx['debit'] = 0;
                    $tx['credit'] = $tx['amount'];
                    $periodCredit += $tx['amount'];
                }
            }
            unset($tx);

            $closingBalance = $isDebitNormal
                ? ($openingBalance + $periodDebit - $periodCredit)
                : ($openingBalance + $periodCredit - $periodDebit);

            echo json_encode([
                'success' => true,
                'data' => [
                    'account' => $account,
                    'is_debit_normal' => $isDebitNormal,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'period_debit' => $periodDebit,
                    'period_credit' => $periodCredit,
                    'transactions' => $transactions,
                    'count' => count($transactions),
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;

        // ─── 거래 분할 ───
        case 'get_splits':
            $txId = (int)($_GET['transaction_id'] ?? 0);
            if (!$txId) respond(400, '거래 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $stmt = $pdo->prepare('SELECT id, account_code, account_name, amount, memo FROM transaction_splits WHERE transaction_id = ? ORDER BY id');
            $stmt->execute([$txId]);
            $splits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($splits as &$s) $s['amount'] = (int)$s['amount'];
            unset($s);

            echo json_encode(['success' => true, 'data' => $splits], JSON_UNESCAPED_UNICODE);
            exit;

        case 'save_splits':
            $txId = (int)($input['transactionId'] ?? 0);
            $splits = $input['splits'] ?? [];

            if (!$txId) respond(400, '거래 ID가 필요합니다.');
            if (!is_array($splits) || count($splits) < 2) respond(400, '분할은 최소 2개 항목이 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $txStmt = $pdo->prepare('SELECT id, amount FROM bank_transactions WHERE id = ?');
            $txStmt->execute([$txId]);
            $origTx = $txStmt->fetch(PDO::FETCH_ASSOC);
            if (!$origTx) respond(404, '거래를 찾을 수 없습니다.');

            $origAmount = (int)$origTx['amount'];
            $splitSum = 0;
            foreach ($splits as $s) {
                $amt = (int)($s['amount'] ?? 0);
                if ($amt <= 0) respond(400, '분할 금액은 양수여야 합니다.');
                $code = trim($s['account_code'] ?? '');
                if (!$code) respond(400, '계정과목 코드가 필요합니다.');
                $splitSum += $amt;
            }

            if ($splitSum !== $origAmount) {
                respond(400, "분할 합계(₩" . number_format($splitSum) . ")가 원거래 금액(₩" . number_format($origAmount) . ")과 일치하지 않습니다.");
            }

            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM transaction_splits WHERE transaction_id = ?')->execute([$txId]);

                $insStmt = $pdo->prepare('INSERT INTO transaction_splits (transaction_id, account_code, account_name, amount, memo) VALUES (?, ?, ?, ?, ?)');
                foreach ($splits as $s) {
                    $code = trim($s['account_code']);
                    $catStmt = $pdo->prepare('SELECT name FROM account_categories WHERE code = ? AND is_active = 1');
                    $catStmt->execute([$code]);
                    $catName = $catStmt->fetchColumn();
                    if (!$catName) {
                        $pdo->rollBack();
                        respond(400, "유효하지 않은 계정과목: {$code}");
                    }
                    $insStmt->execute([$txId, $code, $catName, (int)$s['amount'], trim($s['memo'] ?? '')]);
                }

                $pdo->prepare("UPDATE bank_transactions SET account_code = NULL, account_name = '분할', is_confirmed = 1 WHERE id = ?")->execute([$txId]);
                $pdo->commit();
                respond(200, '분할이 저장되었습니다.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('save_splits error: ' . $e->getMessage());
                respond(500, '분할 저장 중 오류가 발생했습니다.');
            }
            break;

        case 'delete_splits':
            $txId = (int)($input['transactionId'] ?? 0);
            if (!$txId) respond(400, '거래 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM transaction_splits WHERE transaction_id = ?')->execute([$txId]);
                $pdo->prepare("UPDATE bank_transactions SET account_code = NULL, account_name = NULL, is_confirmed = 0 WHERE id = ?")->execute([$txId]);
                $pdo->commit();
                respond(200, '분할이 해제되었습니다.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('delete_splits error: ' . $e->getMessage());
                respond(500, '분할 해제 중 오류가 발생했습니다.');
            }
            break;

        // ─── 거래내역 계정과목 업데이트 ───
        case 'update_tx_category':
            $txId = (int)($input['transactionId'] ?? 0);
            $accountCode = trim($input['accountCode'] ?? '');

            if (!$txId) respond(400, '거래내역 ID가 필요합니다.');

            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');

            if ($accountCode) {
                $catStmt = $pdo->prepare('SELECT name FROM account_categories WHERE code = ? AND is_active = 1');
                $catStmt->execute([$accountCode]);
                $catName = $catStmt->fetchColumn();
                if (!$catName) respond(400, '유효하지 않은 계정과목입니다.');

                $stmt = $pdo->prepare('UPDATE bank_transactions SET account_code = ?, account_name = ?, is_confirmed = 1 WHERE id = ?');
                $stmt->execute([$accountCode, $catName, $txId]);
            } else {
                $stmt = $pdo->prepare('UPDATE bank_transactions SET account_code = NULL, account_name = NULL, is_confirmed = 0 WHERE id = ?');
                $stmt->execute([$txId]);
            }

            respond(200, '저장되었습니다.');
            break;

        // ─── 조정 분개 ───
        case 'list_journals':
            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');
            ensureJournalEntriesTable($pdo);

            $jYear  = (int)($_GET['year'] ?? date('Y'));
            $jMonth = (int)($_GET['month'] ?? 0);

            $sql = "SELECT * FROM journal_entries WHERE period_year = ?";
            $params = [$jYear];
            if ($jMonth > 0) {
                $sql .= " AND period_month = ?";
                $params[] = $jMonth;
            }
            $sql .= " ORDER BY entry_date ASC, id ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            respond(200, 'OK', ['journals' => $rows]);
            break;

        case 'save_journal':
            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');
            ensureJournalEntriesTable($pdo);

            $jId          = (int)($input['id'] ?? 0);
            $entryDate    = trim($input['entry_date'] ?? '');
            $description  = trim($input['description'] ?? '');
            $debitCode    = trim($input['debit_code'] ?? '');
            $creditCode   = trim($input['credit_code'] ?? '');
            $jAmount      = (int)($input['amount'] ?? 0);
            $entryType    = $input['entry_type'] ?? 'adjusting';
            $jMemo        = trim($input['memo'] ?? '');

            if (!$entryDate || !$description || !$debitCode || !$creditCode || $jAmount <= 0) {
                respond(400, '일자, 적요, 차변/대변 계정, 금액(양수)은 필수입니다.');
            }
            if ($debitCode === $creditCode) {
                respond(400, '차변과 대변 계정이 같을 수 없습니다.');
            }
            if (!in_array($entryType, ['adjusting', 'closing', 'opening'])) {
                respond(400, '유효하지 않은 분개 유형입니다.');
            }

            $debitCat = $pdo->prepare('SELECT name FROM account_categories WHERE code = ? AND is_active = 1');
            $debitCat->execute([$debitCode]);
            $debitName = $debitCat->fetchColumn();
            if (!$debitName) respond(400, '유효하지 않은 차변 계정과목입니다.');

            $creditCat = $pdo->prepare('SELECT name FROM account_categories WHERE code = ? AND is_active = 1');
            $creditCat->execute([$creditCode]);
            $creditName = $creditCat->fetchColumn();
            if (!$creditName) respond(400, '유효하지 않은 대변 계정과목입니다.');

            $dateParts = explode('-', $entryDate);
            $pYear  = (int)$dateParts[0];
            $pMonth = (int)($dateParts[1] ?? 1);

            $lockCheck = $pdo->prepare("SELECT is_locked FROM closing_locks WHERE year = ? AND month = ? AND is_locked = 1");
            try { $lockCheck->execute([$pYear, $pMonth]); } catch (\Throwable $e) {}
            if ($lockCheck && $lockCheck->fetchColumn()) {
                respond(400, "{$pYear}년 {$pMonth}월은 마감된 기간입니다. 마감 해제 후 수정하세요.");
            }

            if ($jId > 0) {
                $stmt = $pdo->prepare("UPDATE journal_entries SET
                    entry_date = ?, description = ?,
                    debit_code = ?, debit_name = ?,
                    credit_code = ?, credit_name = ?,
                    amount = ?, entry_type = ?,
                    period_year = ?, period_month = ?, memo = ?
                    WHERE id = ?");
                $stmt->execute([
                    $entryDate, $description,
                    $debitCode, $debitName,
                    $creditCode, $creditName,
                    $jAmount, $entryType,
                    $pYear, $pMonth, $jMemo, $jId
                ]);
                respond(200, '분개가 수정되었습니다.', ['id' => $jId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO journal_entries
                    (entry_date, description, debit_code, debit_name, credit_code, credit_name,
                     amount, entry_type, period_year, period_month, memo, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $entryDate, $description,
                    $debitCode, $debitName,
                    $creditCode, $creditName,
                    $jAmount, $entryType,
                    $pYear, $pMonth, $jMemo,
                    $_SESSION['user_id'] ?? null
                ]);
                $newId = (int)$pdo->lastInsertId();
                respond(200, '분개가 등록되었습니다.', ['id' => $newId]);
            }
            break;

        case 'delete_journal':
            $pdo = getDBConnection();
            if (!$pdo) respond(500, 'DB 연결 실패');
            ensureJournalEntriesTable($pdo);

            $delJId = (int)($input['id'] ?? 0);
            if (!$delJId) respond(400, '분개 ID가 필요합니다.');

            $jeRow = $pdo->prepare("SELECT period_year, period_month FROM journal_entries WHERE id = ?");
            $jeRow->execute([$delJId]);
            $jeInfo = $jeRow->fetch(PDO::FETCH_ASSOC);
            if (!$jeInfo) respond(404, '분개를 찾을 수 없습니다.');

            $lockCheck2 = $pdo->prepare("SELECT is_locked FROM closing_locks WHERE year = ? AND month = ? AND is_locked = 1");
            try { $lockCheck2->execute([$jeInfo['period_year'], $jeInfo['period_month']]); } catch (\Throwable $e) {}
            if ($lockCheck2 && $lockCheck2->fetchColumn()) {
                respond(400, "마감된 기간의 분개는 삭제할 수 없습니다.");
            }

            $stmt = $pdo->prepare("DELETE FROM journal_entries WHERE id = ?");
            $stmt->execute([$delJId]);

            respond(200, '분개가 삭제되었습니다.');
            break;

        // ─── 결산 마감/해제 ───
        case 'closing_lock':
            $userRole = $_SESSION['user']['role'] ?? '';
            if (!in_array($userRole, ['admin', 'manager'])) {
                respond(403, '결산 마감/해제 권한이 없습니다.');
            }

            $lockYear  = (int)($input['year'] ?? 0);
            $lockMonth = (int)($input['month'] ?? 0);
            $doLock    = (bool)($input['lock'] ?? true);

            if ($lockYear < 2020 || $lockMonth < 1 || $lockMonth > 12) {
                respond(400, '유효하지 않은 기간입니다.');
            }

            $pdo = getDBConnection();

            if ($doLock) {
                $stmt = $pdo->prepare("
                    INSERT INTO closing_locks (year, month, is_locked, locked_by, closed_at)
                    VALUES (?, ?, 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE is_locked = 1, locked_by = VALUES(locked_by), closed_at = NOW()
                ");
                $stmt->execute([$lockYear, $lockMonth, $_SESSION['user_id'] ?? null]);
                respond(200, "{$lockYear}년 {$lockMonth}월이 마감되었습니다.");
            } else {
                $stmt = $pdo->prepare("UPDATE closing_locks SET is_locked = 0 WHERE year = ? AND month = ?");
                $stmt->execute([$lockYear, $lockMonth]);
                respond(200, "{$lockYear}년 {$lockMonth}월 마감이 해제되었습니다.");
            }
            break;

        default:
            respond(400, '알 수 없는 action: ' . htmlspecialchars($action));
    }
} catch (Throwable $e) {
    error_log('bankapi: ' . $e->getMessage());
    respond(500, '서버 오류가 발생했습니다.');
}

// ─── 응답 헬퍼 ───

function respond(int $code, string $message, $data = null): never {
    http_response_code($code);
    $body = ['success' => ($code >= 200 && $code < 300), 'message' => $message];
    if ($data !== null) {
        $body['data'] = $data;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function syncAccountToLocalDB(string $bankCode, string $accountNumber): void {
    $pdo = getDBConnection();
    if (!$pdo) return;
    $bankName = BANKAPI_BANKS[$bankCode] ?? $bankCode;
    $stmt = $pdo->prepare('SELECT id FROM bank_accounts WHERE bank_code = ? AND account_no = ?');
    $stmt->execute([$bankCode, $accountNumber]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare('INSERT INTO bank_accounts (bank_code, bank_name, account_no, owner_name) VALUES (?, ?, ?, ?)');
        $ins->execute([$bankCode, $bankName, $accountNumber, '']);
    }
}

function removeAccountFromLocalDB(string $bankCode, string $accountNumber): void {
    $pdo = getDBConnection();
    if (!$pdo) return;
    $pdo->prepare('DELETE FROM bank_accounts WHERE bank_code = ? AND account_no = ?')
        ->execute([$bankCode, $accountNumber]);
}

function isAlreadyRegisteredError(array $result): bool {
    $msg = $result['data']['message'] ?? $result['data']['error'] ?? '';
    return (bool)preg_match('/이미.*(등록|연결)|already.*regist/iu', $msg);
}

function splitDateRange(string $start, string $end, int $maxDays = 90): array
{
    $s = new \DateTime(substr($start, 0, 4) . '-' . substr($start, 4, 2) . '-' . substr($start, 6, 2));
    $e = new \DateTime(substr($end, 0, 4) . '-' . substr($end, 4, 2) . '-' . substr($end, 6, 2));

    if ($s->diff($e)->days <= $maxDays) {
        return [['start' => $start, 'end' => $end]];
    }

    $chunks = [];
    $cur = clone $s;
    while ($cur <= $e) {
        $chunkEnd = (clone $cur)->modify("+{$maxDays} days");
        if ($chunkEnd > $e) $chunkEnd = clone $e;
        $chunks[] = [
            'start' => $cur->format('Ymd'),
            'end'   => $chunkEnd->format('Ymd'),
        ];
        $cur = (clone $chunkEnd)->modify('+1 day');
    }
    return $chunks;
}

/**
 * bankapi 응답에서 거래내역을 추출해 bank_transactions 테이블에 저장
 * 중복은 INSERT IGNORE로 무시 (기존 AI 분류 데이터 보존)
 */
function persistTransactions(string $bankCode, string $accountNumber, array $apiData): int
{
    ensureBankTxSchema();
    $pdo = getDBConnection();
    if (!$pdo) return 0;

    // account_id 조회
    $acctStmt = $pdo->prepare('SELECT id FROM bank_accounts WHERE bank_code = ? AND account_no = ?');
    $acctStmt->execute([$bankCode, $accountNumber]);
    $accountId = $acctStmt->fetchColumn();
    if (!$accountId) return 0;

    // 거래 배열 추출 (API 응답 구조가 유동적)
    $transactions = $apiData['data']['transactions']
        ?? $apiData['transactions']
        ?? $apiData['data'] ?? [];

    if (!is_array($transactions)) return 0;

    // 배열인데 키가 연관배열이면 transactions 아님 · 단일 항목일 수 있음
    if (!empty($transactions) && !isset($transactions[0])) return 0;

    $sql = "INSERT IGNORE INTO bank_transactions
        (account_id, transaction_date, transaction_time, description, counterparty, amount, tx_type, balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $saved = 0;

    foreach ($transactions as $tx) {
        if (!is_array($tx)) continue;

        // 날짜 (YYYYMMDD 또는 YYYY-MM-DD)
        $dateRaw = $tx['date'] ?? $tx['transactionDate'] ?? $tx['transaction_date'] ?? '';
        $dateStr = preg_replace('/[^0-9]/', '', $dateRaw);
        if (strlen($dateStr) === 8) {
            $dateStr = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) continue;

        // 시간
        $timeRaw = $tx['time'] ?? $tx['transactionTime'] ?? $tx['transaction_time'] ?? '';
        $timeStr = trim($timeRaw);
        if ($timeStr && strpos($timeStr, ':') === false && strlen($timeStr) >= 4) {
            $timeStr = substr($timeStr, 0, 2) . ':' . substr($timeStr, 2, 2);
        }

        // 적요
        $description = trim($tx['description'] ?? $tx['memo'] ?? $tx['content'] ?? $tx['remark'] ?? '');
        if (!$description) $description = '-';

        // 거래처
        $counterparty = trim($tx['counterparty'] ?? $tx['displayName'] ?? '');

        // 금액
        $amount = abs((int)preg_replace('/[^0-9]/', '', $tx['amount'] ?? $tx['transactionAmount'] ?? '0'));
        if ($amount === 0) continue;

        // 입출금 구분
        $typeRaw = $tx['type'] ?? $tx['transactionType'] ?? $tx['tx_type'] ?? '';
        $typeStr = mb_strtolower($typeRaw);
        $isDeposit = preg_match('/deposit|입금|\bin\b/i', $typeStr);
        $txType = $isDeposit ? '입금' : '출금';

        // 잔액
        $balance = isset($tx['balance']) ? (int)preg_replace('/[^0-9\-]/', '', $tx['balance']) : null;

        try {
            $stmt->execute([$accountId, $dateStr, $timeStr ?: null, $description, $counterparty ?: null, $amount, $txType, $balance ?? 0]);
            if ($stmt->rowCount() > 0) $saved++;
        } catch (\Throwable $e) {
            error_log('persistTransactions: ' . $e->getMessage());
        }
    }

    return $saved;
}

/**
 * bank_transactions 테이블에 신규 컬럼 자동 추가 (마이그레이션 대용)
 */
function ensureBankTxSchema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = getDBConnection();
    if (!$pdo) return;

    try {
        $cols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM bank_transactions") as $row) {
            $cols[] = $row['Field'];
        }
        if (!in_array('transaction_time', $cols)) {
            $pdo->exec("ALTER TABLE bank_transactions ADD COLUMN transaction_time VARCHAR(10) NULL AFTER transaction_date");
        }
        if (!in_array('counterparty', $cols)) {
            $pdo->exec("ALTER TABLE bank_transactions ADD COLUMN counterparty VARCHAR(200) NULL AFTER description");
        }
    } catch (\Throwable $e) {
        error_log('ensureBankTxSchema: ' . $e->getMessage());
    }
}

/**
 * DB에 저장된 거래내역 조회 (ID + 계정과목 포함)
 */
function getDbTransactions(string $bankCode, string $accountNumber, string $startDate, string $endDate): array
{
    $pdo = getDBConnection();
    if (!$pdo) return [];

    // account_no 는 대시 포함 저장("234-567-890123"), 입력은 숫자만 → 대시 제거 후 비교
    $acctStmt = $pdo->prepare("SELECT id FROM bank_accounts WHERE bank_code = ? AND REPLACE(account_no, '-', '') = ?");
    $acctStmt->execute([$bankCode, $accountNumber]);
    $accountId = $acctStmt->fetchColumn();
    if (!$accountId) return [];

    $start = substr($startDate, 0, 4) . '-' . substr($startDate, 4, 2) . '-' . substr($startDate, 6, 2);
    $end   = substr($endDate, 0, 4)   . '-' . substr($endDate, 4, 2)   . '-' . substr($endDate, 6, 2);

    $stmt = $pdo->prepare(
        'SELECT id, transaction_date, transaction_time, description, counterparty,
                amount, tx_type, balance, account_code, account_name,
                ai_confidence, is_confirmed, memo
         FROM bank_transactions
         WHERE account_id = ? AND transaction_date BETWEEN ? AND ?
         ORDER BY transaction_date DESC, id DESC'
    );
    $stmt->execute([$accountId, $start, $end]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ensureBankAccountColumns(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $cols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM bank_accounts") as $row) {
            $cols[] = $row['Field'];
        }
        if (!in_array('account_type', $cols)) {
            $pdo->exec("ALTER TABLE bank_accounts ADD COLUMN account_type ENUM('운영','급여','세금','예비','기타') NOT NULL DEFAULT '운영' COMMENT '계좌 용도' AFTER account_alias");
        }
        if (!in_array('account_password', $cols)) {
            $pdo->exec("ALTER TABLE bank_accounts ADD COLUMN account_password VARCHAR(255) NULL COMMENT '계좌 비밀번호 (AES-256-GCM)' AFTER owner_name");
        }
        if (!in_array('memo', $cols)) {
            $pdo->exec("ALTER TABLE bank_accounts ADD COLUMN memo TEXT NULL COMMENT '관리 메모/비고' AFTER account_password");
        }
        if (!in_array('sort_order', $cols)) {
            $pdo->exec("ALTER TABLE bank_accounts ADD COLUMN sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서' AFTER is_active");
        }
    } catch (\Throwable $e) {
        error_log('ensureBankAccountColumns: ' . $e->getMessage());
    }
}

/* 테이블은 db/schema_journal_entries.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */
function ensureJournalEntriesTable(PDO $pdo): void
{
    // no-op: 스키마는 db/schema_journal_entries.sql 에서 관리
}

function respondError(array $result): never {
    // status=0 → 원격 호출 자체가 안 된 상태(로컬 설정 누락 등).
    // 이 경우 HTTP 200 + success:false 로 응답해 브라우저 네트워크 탭 소음을 피한다.
    $remote = (int)($result['status'] ?? 0);
    $code = $remote > 0 ? $remote : 200;
    $msg  = $result['data']['message'] ?? $result['data']['error'] ?? 'API 요청 실패';

    // bankapi 에러코드별 한글 메시지
    $friendlyMessages = [
        401 => 'API 인증에 실패했습니다. API Key / Secret을 확인하세요.',
        403 => '등록되지 않은 계좌입니다. 먼저 계좌를 등록하세요.',
        429 => 'API 요청 한도를 초과했습니다. 잠시 후 다시 시도하세요.',
    ];

    $finalMsg = $friendlyMessages[$remote] ?? $msg;

    // respond()는 2xx에서만 success:true를 보내므로, 로컬 에러(200)로 포장하면서도
    // 클라이언트가 실패를 구분할 수 있도록 명시적으로 success:false를 넣는다.
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $finalMsg,
        'data'    => $result['data'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
