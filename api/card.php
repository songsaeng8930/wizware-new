<?php
/**
 * Zaemit 그룹웨어 - 법인카드 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_doc.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        // 카드 관리
        'getCards'          => getCards($pdo),
        'getCard'           => getCard($pdo),
        'saveCard'          => saveCard($pdo),
        'deleteCard'        => deleteCard($pdo),
        'toggleCard'        => toggleCard($pdo),
        // 지출내역
        'getExpenses'       => getExpenses($pdo),
        'getExpense'        => getExpense($pdo),
        'saveExpense'       => saveExpense($pdo),
        'deleteExpense'     => deleteExpense($pdo),
        // 규정
        'getRegulations'    => getRegulations($pdo),
        'saveRegulations'   => saveRegulations($pdo),
        // 규정 카테고리
        'getCategories'     => getCategories($pdo),
        'saveCategory'      => saveCategory($pdo),
        'deleteCategory'    => deleteCategory($pdo),
        // 정산
        'getSettlements'    => getSettlements($pdo),
        'updateSettlement'  => updateSettlement($pdo),
        'batchSettle'       => batchSettle($pdo),
        // 승인내역
        'getApprovals'      => getApprovals($pdo),
        // 결재 연동
        'submitCardExpenseApproval' => submitCardExpenseApproval($pdo),
        default             => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (PDOException $e) {
    error_log('Card API: ' . $e->getMessage());
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
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

// ===== 카드 관리 =====

function getCards(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    if (!empty($_GET['department'])) {
        $where[] = 'department LIKE ?';
        $params[] = '%' . $_GET['department'] . '%';
    }
    if (!empty($_GET['card_alias'])) {
        $where[] = 'card_alias LIKE ?';
        $params[] = '%' . $_GET['card_alias'] . '%';
    }
    if (!empty($_GET['manager'])) {
        $where[] = 'manager_name LIKE ?';
        $params[] = '%' . $_GET['manager'] . '%';
    }
    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $where[] = 'is_active = ?';
        $params[] = (int)$_GET['is_active'];
    }

    $sql = 'SELECT * FROM cards WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    respond(200, ['cards' => $stmt->fetchAll()]);
}

function getCard(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare('SELECT * FROM cards WHERE id = ?');
    $stmt->execute([$id]);
    $card = $stmt->fetch();
    if (!$card) respond(404, ['error' => '카드를 찾을 수 없습니다.']);
    respond(200, ['card' => $card]);
}

function saveCard(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $alias = trim($data['card_alias'] ?? '');
    if (!$alias) respond(400, ['error' => '카드별칭을 입력해주세요.']);

    $manager = trim($data['manager_name'] ?? '');
    if (!$manager) respond(400, ['error' => '책임자를 입력해주세요.']);

    $managerEmpId = !empty($data['manager_employee_id']) ? (int)$data['manager_employee_id'] : null;
    $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : null;

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE cards SET card_alias=?, card_number=?, memo=?, manager_name=?, manager_employee_id=?, affiliation=?, department=?, department_id=?, is_active=? WHERE id=?');
        $stmt->execute([
            $alias,
            $data['card_number'] ?? '',
            $data['memo'] ?? '',
            $manager,
            $managerEmpId,
            $data['affiliation'] ?? '',
            $data['department'] ?? '',
            $deptId,
            (int)($data['is_active'] ?? 1),
            $id
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cards (card_alias, card_number, memo, manager_name, manager_employee_id, affiliation, department, department_id, is_active) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $alias,
            $data['card_number'] ?? '',
            $data['memo'] ?? '',
            $manager,
            $managerEmpId,
            $data['affiliation'] ?? '',
            $data['department'] ?? '',
            $deptId,
            (int)($data['is_active'] ?? 1)
        ]);
        $id = (int)$pdo->lastInsertId();
    }
    respond(200, ['id' => $id, 'message' => '저장되었습니다.']);
}

function deleteCard(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $expenseCount = $pdo->prepare('SELECT COUNT(*) FROM card_expenses WHERE card_id = ?');
    $expenseCount->execute([$id]);
    if ((int)$expenseCount->fetchColumn() > 0) {
        respond(400, ['error' => '이 카드에 연결된 지출내역이 있어 삭제할 수 없습니다. 먼저 지출내역을 정리해주세요.']);
    }

    $pdo->prepare('DELETE FROM cards WHERE id = ?')->execute([$id]);
    respond(200, ['message' => '삭제되었습니다.']);
}

function toggleCard(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);
    $pdo->prepare('UPDATE cards SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
    respond(200, ['message' => '변경되었습니다.']);
}

// ===== 지출내역 =====

function getExpenses(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    if (!empty($_GET['card_id'])) {
        $where[] = 'e.card_id = ?';
        $params[] = (int)$_GET['card_id'];
    }
    if (!empty($_GET['card_alias'])) {
        $where[] = 'c.card_alias LIKE ?';
        $params[] = '%' . $_GET['card_alias'] . '%';
    }
    if (!empty($_GET['manager'])) {
        $where[] = 'c.manager_name LIKE ?';
        $params[] = '%' . $_GET['manager'] . '%';
    }
    if (!empty($_GET['department'])) {
        $where[] = '(c.affiliation LIKE ? OR c.department LIKE ?)';
        $params[] = '%' . $_GET['department'] . '%';
        $params[] = '%' . $_GET['department'] . '%';
    }
    if (!empty($_GET['approval_number'])) {
        $where[] = 'e.approval_number LIKE ?';
        $params[] = '%' . $_GET['approval_number'] . '%';
    }
    if (!empty($_GET['usage_type'])) {
        $where[] = 'e.usage_type = ?';
        $params[] = $_GET['usage_type'];
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'e.usage_date >= ?';
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'e.usage_date <= ?';
        $params[] = $_GET['date_to'];
    }
    if (!empty($_GET['category'])) {
        $where[] = 'e.category = ?';
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['user_name'])) {
        $where[] = 'e.user_name LIKE ?';
        $params[] = '%' . $_GET['user_name'] . '%';
    }
    if (!empty($_GET['compliance_status'])) {
        $where[] = 'e.compliance_status = ?';
        $params[] = $_GET['compliance_status'];
    }
    if (isset($_GET['is_settled']) && $_GET['is_settled'] !== '') {
        $where[] = 'e.is_settled = ?';
        $params[] = (int)$_GET['is_settled'];
    }

    $sql = 'SELECT e.*, c.card_alias, c.card_number, c.manager_name AS card_manager, c.affiliation, c.department,
                   ad.id AS approval_doc_id, ad.status AS approval_status
            FROM card_expenses e
            JOIN cards c ON e.card_id = c.id
            LEFT JOIN approval_documents ad ON ad.doc_number = e.document_number AND ad.doc_type = ' . "'경비청구서'" . '
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.usage_date DESC, e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    respond(200, ['expenses' => $stmt->fetchAll()]);
}

function getExpense(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare("SELECT e.*, c.card_alias, c.card_number, c.manager_name AS card_manager, c.affiliation, c.department,
                                  ad.id AS approval_doc_id, ad.status AS approval_status
                           FROM card_expenses e
                           JOIN cards c ON e.card_id = c.id
                           LEFT JOIN approval_documents ad ON ad.doc_number = e.document_number AND ad.doc_type = '경비청구서'
                           WHERE e.id = ?");
    $stmt->execute([$id]);
    $expense = $stmt->fetch();
    if (!$expense) respond(404, ['error' => '지출내역을 찾을 수 없습니다.']);
    respond(200, ['expense' => $expense]);
}

function saveExpense(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $cardId = (int)($data['card_id'] ?? 0);
    $amount = (int)($data['amount'] ?? 0);
    $usageDate = $data['usage_date'] ?? '';
    $category = trim($data['category'] ?? '');

    if (!$cardId) respond(400, ['error' => '법인카드를 선택해주세요.']);
    if (!$amount) respond(400, ['error' => '사용금액을 입력해주세요.']);
    if (!$usageDate) respond(400, ['error' => '사용일을 입력해주세요.']);
    if (!$category) respond(400, ['error' => '항목을 선택해주세요.']);

    $subCategory = trim($data['sub_category'] ?? '');
    $exceptionReason = trim($data['exception_reason'] ?? '');

    $regulationLimit = null;
    $complianceStatus = '미확인';

    if ($subCategory) {
        $regStmt = $pdo->prepare('SELECT limit_amount FROM card_regulations WHERE category = ? AND sub_category = ? AND is_active = 1 LIMIT 1');
        $regStmt->execute([$category, $subCategory]);
        $reg = $regStmt->fetch();
        if ($reg && (int)$reg['limit_amount'] > 0) {
            $regulationLimit = (int)$reg['limit_amount'];
            if ($amount > $regulationLimit) {
                if (!$exceptionReason) {
                    respond(400, [
                        'error' => '한도 초과',
                        'code' => 'OVER_LIMIT',
                        'limit' => $regulationLimit,
                        'message' => $subCategory . ' 한도 ' . number_format($regulationLimit) . '원을 초과했습니다. 예외 사유를 입력해주세요.'
                    ]);
                }
                $complianceStatus = '예외신청';
            } else {
                $complianceStatus = '준수';
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE card_expenses SET card_id=?, registrant_name=?, approval_number=?, usage_type=?, category=?, sub_category=?, amount=?, description=?, business_name=?, business_code=?, document_number=?, user_name=?, usage_date=?, compliance_status=?, exception_reason=?, regulation_limit=? WHERE id=?');
        $stmt->execute([
            $cardId,
            $data['registrant_name'] ?? '',
            $data['approval_number'] ?? '',
            $data['usage_type'] ?? '법인',
            $category,
            $subCategory,
            $amount,
            $data['description'] ?? '',
            $data['business_name'] ?? '',
            $data['business_code'] ?? '',
            $data['document_number'] ?? '',
            $data['user_name'] ?? '',
            $usageDate,
            $complianceStatus,
            $exceptionReason ?: null,
            $regulationLimit,
            $id
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO card_expenses (card_id, registrant_name, approval_number, usage_type, category, sub_category, amount, description, business_name, business_code, document_number, user_name, usage_date, compliance_status, exception_reason, regulation_limit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $cardId,
            $data['registrant_name'] ?? '',
            $data['approval_number'] ?? '',
            $data['usage_type'] ?? '법인',
            $category,
            $subCategory,
            $amount,
            $data['description'] ?? '',
            $data['business_name'] ?? '',
            $data['business_code'] ?? '',
            $data['document_number'] ?? '',
            $data['user_name'] ?? '',
            $usageDate,
            $complianceStatus,
            $exceptionReason ?: null,
            $regulationLimit
        ]);
        $id = (int)$pdo->lastInsertId();
    }
    respond(200, ['id' => $id, 'message' => '저장되었습니다.']);
}

function deleteExpense(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);
    $pdo->prepare('DELETE FROM card_expenses WHERE id = ?')->execute([$id]);
    respond(200, ['message' => '삭제되었습니다.']);
}

// ===== 규정 =====

function getRegulations(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT * FROM card_regulations WHERE is_active = 1 ORDER BY sort_order, id');
    respond(200, ['regulations' => $stmt->fetchAll()]);
}

function saveRegulations(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $regulations = $data['regulations'] ?? [];

    $pdo->beginTransaction();

    $existing = $pdo->query('SELECT id FROM card_regulations')->fetchAll(PDO::FETCH_COLUMN);
    $keepIds = [];

    foreach ($regulations as $i => $reg) {
        $regId = (int)($reg['id'] ?? 0);
        $category = trim($reg['category'] ?? '');
        $subCategory = trim($reg['sub_category'] ?? '');
        if (!$category || !$subCategory) continue;

        $useInRegister = isset($reg['use_in_register']) ? (int)$reg['use_in_register'] : 1;
        if ($regId > 0 && in_array($regId, $existing)) {
            $pdo->prepare('UPDATE card_regulations SET category=?, sub_category=?, limit_amount=?, required_fields=?, guide=?, use_in_register=?, sort_order=? WHERE id=?')
                ->execute([$category, $subCategory, (int)($reg['limit_amount'] ?? 0), $reg['required_fields'] ?? '', $reg['guide'] ?? '', $useInRegister, $i + 1, $regId]);
            $keepIds[] = $regId;
        } else {
            $pdo->prepare('INSERT INTO card_regulations (category, sub_category, limit_amount, required_fields, guide, use_in_register, sort_order) VALUES (?,?,?,?,?,?,?)')
                ->execute([$category, $subCategory, (int)($reg['limit_amount'] ?? 0), $reg['required_fields'] ?? '', $reg['guide'] ?? '', $useInRegister, $i + 1]);
        }
    }

    $removeIds = array_diff($existing, $keepIds);
    if (!empty($removeIds)) {
        $ph = implode(',', array_fill(0, count($removeIds), '?'));
        $pdo->prepare("DELETE FROM card_regulations WHERE id IN ($ph)")->execute(array_values($removeIds));
    }

    $pdo->commit();
    respond(200, ['message' => '규정이 저장되었습니다.']);
}

// ===== 규정 카테고리 =====

function getCategories(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT * FROM card_regulation_categories WHERE is_active = 1 ORDER BY sort_order, id');
    respond(200, ['categories' => $stmt->fetchAll()]);
}

function saveCategory(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $color = trim($data['color'] ?? 'gray');

    if (!$name) respond(400, ['error' => '항목 이름을 입력해주세요.']);

    // 중복 체크 (자기 자신 제외)
    $dupStmt = $pdo->prepare('SELECT id FROM card_regulation_categories WHERE name = ? AND is_active = 1 AND id != ?');
    $dupStmt->execute([$name, $id]);
    if ($dupStmt->fetch()) respond(400, ['error' => "'{$name}' 항목이 이미 존재합니다."]);

    if ($id > 0) {
        $oldName = trim($data['old_name'] ?? '');
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE card_regulation_categories SET name = ?, color = ? WHERE id = ?')
                ->execute([$name, $color, $id]);

            // 이름이 바뀐 경우 규정 테이블 동기화
            if ($oldName && $oldName !== $name) {
                $pdo->prepare('UPDATE card_regulations SET category = ? WHERE category = ?')
                    ->execute([$name, $oldName]);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        // INSERT
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM card_regulation_categories')->fetchColumn();
        $pdo->prepare('INSERT INTO card_regulation_categories (name, color, sort_order) VALUES (?, ?, ?)')
            ->execute([$name, $color, $maxOrder + 1]);
        $id = (int)$pdo->lastInsertId();
    }

    $cat = $pdo->prepare('SELECT * FROM card_regulation_categories WHERE id = ?');
    $cat->execute([$id]);
    respond(200, ['message' => '저장되었습니다.', 'category' => $cat->fetch()]);
}

function deleteCategory(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    // 카테고리 이름 조회
    $cat = $pdo->prepare('SELECT name FROM card_regulation_categories WHERE id = ?');
    $cat->execute([$id]);
    $catName = $cat->fetchColumn();
    if (!$catName) respond(404, ['error' => '카테고리를 찾을 수 없습니다.']);

    // 해당 카테고리에 규정이 있는지 확인
    $count = $pdo->prepare('SELECT COUNT(*) FROM card_regulations WHERE category = ? AND is_active = 1');
    $count->execute([$catName]);
    if ((int)$count->fetchColumn() > 0) {
        respond(400, ['error' => "'{$catName}' 항목에 규정이 있어서 삭제할 수 없습니다. 규정을 먼저 삭제해주세요."]);
    }

    // 소프트 삭제
    $pdo->prepare('UPDATE card_regulation_categories SET is_active = 0 WHERE id = ?')->execute([$id]);
    respond(200, ['message' => "'{$catName}' 항목이 삭제되었습니다."]);
}

// ===== 정산 =====

function getSettlements(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    if (!empty($_GET['card_alias'])) {
        $where[] = 'c.card_alias LIKE ?';
        $params[] = '%' . $_GET['card_alias'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'e.usage_date >= ?';
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'e.usage_date <= ?';
        $params[] = $_GET['date_to'];
    }
    if (isset($_GET['is_settled']) && $_GET['is_settled'] !== '') {
        $where[] = 'e.is_settled = ?';
        $params[] = (int)$_GET['is_settled'];
    }
    if (!empty($_GET['compliance_status'])) {
        $where[] = 'e.compliance_status = ?';
        $params[] = $_GET['compliance_status'];
    }

    $sql = 'SELECT e.*, c.card_alias, c.manager_name AS card_manager, c.affiliation, c.department
            FROM card_expenses e
            JOIN cards c ON e.card_id = c.id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.usage_date DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    respond(200, ['settlements' => $stmt->fetchAll()]);
}

function updateSettlement(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare('UPDATE card_expenses SET is_settled=?, compliance_status=?, settlement_updater=?, settlement_date=NOW() WHERE id=?');
    $stmt->execute([
        (int)($data['is_settled'] ?? 0),
        $data['compliance_status'] ?? '미확인',
        $data['settlement_updater'] ?? '',
        $id
    ]);
    respond(200, ['message' => '정산정보가 업데이트되었습니다.']);
}

function batchSettle(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $ids = $data['ids'] ?? [];
    $updater = $data['settlement_updater'] ?? '';
    if (empty($ids)) respond(400, ['error' => '선택된 항목이 없습니다.']);

    $pdo->beginTransaction();
    try {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$updater], array_map('intval', $ids));
        $pdo->prepare("UPDATE card_expenses SET is_settled = 1, compliance_status = '준수', settlement_updater = ?, settlement_date = NOW() WHERE id IN ($ph)")
            ->execute($params);
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('batchSettle error: ' . $e->getMessage());
        respond(500, ['error' => '정산 처리 중 오류가 발생했습니다.']);
    }
    respond(200, ['message' => count($ids) . '건이 정산 처리되었습니다.']);
}

// ===== 승인내역 =====

function getApprovals(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];

    if (!empty($_GET['card_alias'])) {
        $where[] = 'c.card_alias LIKE ?';
        $params[] = '%' . $_GET['card_alias'] . '%';
    }
    if (!empty($_GET['approval_number'])) {
        $where[] = 'a.approval_number LIKE ?';
        $params[] = '%' . $_GET['approval_number'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'DATE(a.approval_date) >= ?';
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'DATE(a.approval_date) <= ?';
        $params[] = $_GET['date_to'];
    }
    if (!empty($_GET['approval_status'])) {
        $where[] = 'a.approval_status = ?';
        $params[] = $_GET['approval_status'];
    }

    $sql = 'SELECT a.*, c.card_alias, c.manager_name AS card_manager
            FROM card_approvals a
            JOIN cards c ON a.card_id = c.id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY a.approval_date DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    respond(200, ['approvals' => $stmt->fetchAll()]);
}

// ===== 결재 연동 =====

/**
 * 카드 경비를 전자결재로 상신 (경비청구서 생성)
 * Phase 1 워크플로우 통합: 경비청구서 → 회계 연동
 *
 * 처리 흐름:
 *  1) card_expenses 조회 + 중복 상신/정산 완료 여부 가드
 *  2) 문서번호 생성 (Zaemit_{부서}_경비_{YYYYMMDDHHMMSS})
 *  3) approval_documents INSERT (metadata에 source=card_expense, source_id 저장)
 *  4) approval_history에 임시 1단계 결재선 INSERT
 *  5) card_expenses.document_number 역연결 UPDATE
 */
function submitCardExpenseApproval(PDO $pdo): void
{
    $data = getJsonInput();
    $expenseId = (int)($data['expense_id'] ?? 0);
    if ($expenseId <= 0) respond(400, ['error' => '경비 ID가 필요합니다.']);

    try {
        $pdo->beginTransaction();

        // 1) 경비 + 카드 정보 조회 (규정 준수 정보 포함)
        $st = $pdo->prepare('SELECT e.id, e.document_number, e.usage_date, e.amount,
                                    e.business_name, e.category, e.sub_category,
                                    e.user_name, e.registrant_name, e.is_settled,
                                    e.compliance_status, e.exception_reason, e.regulation_limit,
                                    c.card_alias, c.manager_name AS card_manager,
                                    c.affiliation, c.department
                             FROM card_expenses e
                             JOIN cards c ON e.card_id = c.id
                             WHERE e.id = ?');
        $st->execute([$expenseId]);
        $exp = $st->fetch();

        if (!$exp) {
            $pdo->rollBack();
            respond(404, ['error' => '경비 내역을 찾을 수 없습니다.']);
        }
        if (!empty($exp['document_number'])) {
            $pdo->rollBack();
            respond(409, ['error' => '이미 결재 상신된 건입니다. 문서번호: ' . $exp['document_number']]);
        }
        if ((int)$exp['is_settled'] === 1) {
            $pdo->rollBack();
            respond(409, ['error' => '이미 정산 완료된 건은 상신할 수 없습니다.']);
        }

        // 2) 문서번호 생성 · 포맷은 includes/approval_doc.php 단일 소스
        $deptRaw = $exp['department'] ?: ($exp['affiliation'] ?: '경영지원');
        $docNumber = buildApprovalDocNumber($deptRaw, '경비');

        // 3) 기안자 정보 (사용자 우선, 없으면 등록자, 그것도 없으면 카드 책임자)
        $drafterName = $exp['user_name'] ?: ($exp['registrant_name'] ?: $exp['card_manager'] ?: '미지정');
        $drafterDept = $deptRaw;

        // 4) 문서 제목/내용
        $title = sprintf('[경비청구] %s - %s (%s원)',
            $exp['business_name'] ?: '-',
            $exp['category'] ?: '기타',
            number_format((int)$exp['amount'])
        );
        $complianceStatus = $exp['compliance_status'] ?: '미확인';
        $exceptionReason = $exp['exception_reason'] ?: '';
        $regLimit = $exp['regulation_limit'] ? (int)$exp['regulation_limit'] : null;
        $expAmount = (int)$exp['amount'];

        $content = sprintf(
            '<p><b>사용일:</b> %s</p>'
            . '<p><b>가맹점:</b> %s</p>'
            . '<p><b>금액:</b> %s원</p>'
            . '<p><b>카테고리:</b> %s %s</p>'
            . '<p><b>사용자:</b> %s (%s)</p>'
            . '<p><b>카드:</b> %s</p>',
            htmlspecialchars($exp['usage_date'] ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($exp['business_name'] ?: '-', ENT_QUOTES, 'UTF-8'),
            number_format($expAmount),
            htmlspecialchars($exp['category'] ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($exp['sub_category'] ?: '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($drafterName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($drafterDept, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($exp['card_alias'] ?: '-', ENT_QUOTES, 'UTF-8')
        );

        // 5) 메타데이터 · 승인 훅에서 card_expense를 역추적할 키 + 규정 준수 정보
        $metaArray = ['source' => 'card_expense', 'source_id' => $expenseId];
        if ($complianceStatus !== '미확인') {
            $metaArray['compliance_status'] = $complianceStatus;
            if ($regLimit !== null) $metaArray['regulation_limit'] = $regLimit;
            if ($exceptionReason) $metaArray['exception_reason'] = $exceptionReason;
        }
        $metadata = json_encode($metaArray, JSON_UNESCAPED_UNICODE);

        // 6) 결재선 해결 · 금액별 결재선 자동 라우팅 (Phase 2)
        $route = resolveApprovalRoute($pdo, $drafterDept, '경비청구서', null, null, $expAmount);
        if ($route === null) {
            $pdo->rollBack();
            respond(500, ['error' => '결재선을 찾을 수 없습니다. "결재선 설정"에서 경비청구서 결재선을 먼저 등록해주세요.']);
        }

        // 7) approval_documents INSERT
        //    결재선이 여러 단계면 첫 단계 대기 상태 = '대기', 1단계뿐이어도 동일하게 처리
        $ins = $pdo->prepare('INSERT INTO approval_documents
            (doc_number, title, content, metadata, doc_type, drafter_name, drafter_dept, status, draft_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())');
        $ins->execute([
            $docNumber,
            $title,
            $content,
            $metadata,
            '경비청구서',
            $drafterName,
            $drafterDept,
            '대기',
        ]);
        $docId = (int)$pdo->lastInsertId();

        // 8) 결재선을 approval_history에 단계별 INSERT
        $hist = $pdo->prepare('INSERT INTO approval_history
            (document_id, approver_id, approver_name, approver_dept, role, step_order, action)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($route as $idx => $step) {
            $hist->execute([
                $docId,
                $step['employee_id'],
                $step['name'],
                $step['dept'],
                $step['role'],
                $idx + 1,
                '대기',
            ]);
        }

        // 9) card_expenses.document_number 역연결
        $pdo->prepare('UPDATE card_expenses SET document_number = ? WHERE id = ?')
            ->execute([$docNumber, $expenseId]);

        $pdo->commit();

        respond(200, [
            'success'     => true,
            'document_id' => $docId,
            'doc_number'  => $docNumber,
            'message'     => '결재 상신이 완료되었습니다.',
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[Card] submitCardExpenseApproval 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 오류가 발생했습니다.']);
    }
}
