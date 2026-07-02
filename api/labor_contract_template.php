<?php
/**
 * Zaemit 그룹웨어 - 계약서 양식 API (다중 버전/종류 지원)
 * action:
 *   list       · 전체 양식 목록
 *   get        · 양식 1건 (id)
 *   save       · 신규/수정 UPSERT (id 있으면 UPDATE, 없으면 INSERT)
 *   delete     · 양식 삭제
 *   setDefault · 기본 양식 지정 (한 건만 is_default=1)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

// 모든 액션 공통: 테이블·컬럼 자동 보정 (마이그레이션 미실행 환경 보호)
try {
    $__pdo = getDBConnection();
} catch (Throwable $e) { error_log('[labor_contract_template] ensure: ' . $e->getMessage()); }

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':       listTemplates();   break;
    case 'get':        getTemplate();     break;
    case 'save':       saveTemplate();    break;
    case 'delete':     deleteTemplate();  break;
    case 'setDefault': setDefault();      break;
    default:           respond(400, ['error' => '알 수 없는 액션']);
}

/* 테이블은 db/schema_contract_template.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */

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

function listTemplates(): void
{
    try {
        $pdo = getDBConnection();
        $rows = $pdo->query("SELECT id, name, version_label, description, is_default, is_active, updated_by, updated_at
                             FROM contract_templates
                             WHERE is_active = 1
                             ORDER BY is_default DESC, updated_at DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
        respond(200, ['success' => true, 'templates' => $rows]);
    } catch (PDOException $e) {
        respond(500, ['error' => 'DB 오류: ' . $e->getMessage()]);
    }
}

function getTemplate(): void
{
    $id = (int)($_GET['id'] ?? 0);
    try {
        $pdo = getDBConnection();
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT id, name, version_label, description, body, is_default, is_active, updated_by, updated_at
                                   FROM contract_templates WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // 기본 양식
            $stmt = $pdo->query("SELECT id, name, version_label, description, body, is_default, is_active, updated_by, updated_at
                                 FROM contract_templates
                                 WHERE is_default = 1 AND is_active = 1
                                 ORDER BY id DESC LIMIT 1");
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) respond(404, ['error' => '양식을 찾을 수 없습니다']);
        respond(200, ['success' => true, 'template' => $row]);
    } catch (PDOException $e) {
        respond(500, ['error' => 'DB 오류']);
    }
}

function saveTemplate(): void
{
    $input = getJsonInput();
    $id     = (int)($input['id'] ?? 0);
    $name   = trim((string)($input['name'] ?? '표준 근로계약서'));
    $ver    = trim((string)($input['version_label'] ?? ''));
    $desc   = trim((string)($input['description'] ?? ''));
    $body   = (string)($input['body'] ?? '');
    $isDef  = (int)($input['is_default'] ?? 0) ? 1 : 0;

    if ($name === '') respond(400, ['error' => '양식 이름을 입력해주세요']);
    if (trim(strip_tags($body)) === '') respond(400, ['error' => '계약서 본문이 비어 있습니다']);

    $user = $_SESSION['user_name'] ?? $_SESSION['username'] ?? null;

    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE contract_templates
                                   SET name=?, version_label=?, description=?, body=?, updated_by=?
                                   WHERE id=?");
            $stmt->execute([$name, $ver ?: null, $desc ?: null, $body, $user, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO contract_templates (name, version_label, description, body, is_default, is_active, updated_by)
                                   VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$name, $ver ?: null, $desc ?: null, $body, $isDef, $user]);
            $id = (int)$pdo->lastInsertId();
        }

        // 기본 양식 설정 · 1건만 default 가 되도록 다른 건은 0 으로
        if ($isDef) {
            $pdo->prepare("UPDATE contract_templates SET is_default = 0 WHERE id <> ?")->execute([$id]);
            $pdo->prepare("UPDATE contract_templates SET is_default = 1 WHERE id = ?")->execute([$id]);
        }

        $pdo->commit();

        $fetchStmt = $pdo->prepare("SELECT id, name, version_label, description, body, is_default, is_active, updated_by, updated_at
                            FROM contract_templates WHERE id = ?");
        $fetchStmt->execute([$id]);
        $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        respond(200, ['success' => true, 'template' => $row]);
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[labor_contract_template] save 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 저장 실패: ' . $e->getMessage()]);
    }
}

function deleteTemplate(): void
{
    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'id 가 필요합니다']);

    try {
        $pdo = getDBConnection();

        // 이미 계약서에서 사용 중이면 is_active = 0 (소프트 삭제)
        $usedStmt = $pdo->prepare("SELECT COUNT(*) FROM labor_contracts WHERE template_id = ?");
        try { $usedStmt->execute([$id]); $used = (int)$usedStmt->fetchColumn(); }
        catch (PDOException $e) { $used = 0; } // template_id 컬럼 미생성 환경

        // 기본 양식은 삭제 금지
        $defStmt = $pdo->prepare("SELECT is_default FROM contract_templates WHERE id = ?");
        $defStmt->execute([$id]);
        $row = $defStmt->fetch();
        if ($row && (int)$row['is_default'] === 1) {
            respond(400, ['error' => '기본 양식은 삭제할 수 없습니다. 먼저 다른 양식을 기본으로 지정해주세요.']);
        }

        if ($used > 0) {
            $pdo->prepare("UPDATE contract_templates SET is_active = 0 WHERE id = ?")->execute([$id]);
            respond(200, ['success' => true, 'soft_deleted' => true, 'used_by' => $used]);
        } else {
            $pdo->prepare("DELETE FROM contract_templates WHERE id = ?")->execute([$id]);
            respond(200, ['success' => true, 'soft_deleted' => false]);
        }
    } catch (PDOException $e) {
        respond(500, ['error' => 'DB 오류']);
    }
}

function setDefault(): void
{
    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => 'id 가 필요합니다']);

    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        $pdo->exec("UPDATE contract_templates SET is_default = 0");
        $pdo->prepare("UPDATE contract_templates SET is_default = 1 WHERE id = ?")->execute([$id]);
        $pdo->commit();
        respond(200, ['success' => true]);
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        respond(500, ['error' => 'DB 오류']);
    }
}
