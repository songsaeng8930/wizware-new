<?php
/**
 * Zaemit 그룹웨어 - 전자결재 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_doc.php';
require_once __DIR__ . '/../includes/approval_form_templates.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../includes/approval_audit.php';
require_once __DIR__ . '/../includes/approval_delegate_helper.php';

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => '데이터베이스 연결 실패']);
    exit;
}

// 현재 로그인 사용자
$currentUser = $_SESSION['user'] ?? null;
$currentUserId = (int)($currentUser['id'] ?? 0);
$currentUserName = $currentUser['name'] ?? '';
$currentUserDeptId = $currentUser['department_id'] ?? 0;

$action = $_GET['action'] ?? '';

try {
    match ($action) {
        // 문서
        'getDrafts'         => getDrafts($pdo),
        'getPending'        => getPending($pdo),
        'getArchive'        => getArchive($pdo),
        'getDocument'       => getDocument($pdo),
        'saveDocument'      => saveDocument($pdo),
        'saveDraft'         => saveDocument($pdo),
        'deleteDocument'    => deleteDocument($pdo),
        'approveDocument'   => approveDocument($pdo),
        // 결재선 / 결재라인 설정
        'getLines'          => getLines($pdo),
        'getLine'           => getLine($pdo),
        'saveLine'          => saveLine($pdo),
        'deleteLine'        => deleteLine($pdo),
        'getResolvedRoute'  => getResolvedRoute($pdo),
        'getApprovalConfig' => getApprovalConfig($pdo),
        'saveApprovalConfig'=> saveApprovalConfig($pdo),
        // 결재양식
        'getForms'          => getForms($pdo),
        'getForm'           => getForm($pdo),
        'saveForm'          => saveForm($pdo),
        'deleteForm'        => deleteForm($pdo),
        'toggleForm'        => toggleForm($pdo),
        'seedDefaultForms'  => seedDefaultForms($pdo),
        'importFormTemplate'=> importFormTemplate(),
        default             => respond(400, ['error' => '알 수 없는 액션']),
    };
} catch (PDOException $e) {
    error_log('Approval API: ' . $e->getMessage());
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

// ===== 결재문서함 =====
function getDrafts(PDO $pdo): void
{
    global $currentUserId, $currentUser;
    $isAdmin = ($currentUser['role'] ?? '') === 'admin';

    $statusFilter = $_GET['status_filter'] ?? 'all';
    $scope = $_GET['scope'] ?? '';

    $where = ['1=1'];
    $params = [];

    // scope=my: 내 기안함 — 항상 본인 문서만
    if ($scope === 'my') {
        $where = ['d.drafter_id = ?'];
        $params = [(int)$currentUserId];
    } else {
        // 기존 동작: 반려/임시저장은 본인만 (admin은 전체)
        $needMyFilter = in_array($statusFilter, ['rejected', 'temp']);
        if ($needMyFilter && !$isAdmin) {
            $where = ['d.drafter_id = ?'];
            $params = [$currentUserId];
        }
    }
    buildDocFilters($where, $params);

    match ($statusFilter) {
        'waiting'  => $where[] = "d.status = '대기'",
        'progress' => $where[] = "d.status IN ('대기','진행')",
        'rejected' => $where[] = "d.status = '반려'",
        'temp'     => $where[] = "d.status = '임시저장'",
        'approved' => $where[] = "d.status = '승인'",
        default    => $where[] = "d.status IN ('대기','진행','반려','임시저장')",
    };

    $sql = buildDocQuery($where);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $docs = $stmt->fetchAll();

    attachRoutes($pdo, $docs);

    // 탭별 건수
    if ($scope === 'my') {
        $myFilter = ['d.drafter_id = ?'];
        $myParams = [(int)$currentUserId];
        $counts = [
            'progress' => countDocs($pdo, array_merge($myFilter, ["d.status IN ('대기','진행')"]), $myParams),
            'approved' => countDocs($pdo, array_merge($myFilter, ["d.status = '승인'"]), $myParams),
            'rejected' => countDocs($pdo, array_merge($myFilter, ["d.status = '반려'"]), $myParams),
            'temp'     => countDocs($pdo, array_merge($myFilter, ["d.status = '임시저장'"]), $myParams),
        ];
    } else {
        $myFilter = $isAdmin ? ['1=1'] : ['d.drafter_id = ?'];
        $myParams = $isAdmin ? [] : [(int)$currentUserId];
        $counts = [
            'waiting'  => countDocs($pdo, ['1=1', "d.status = '대기'"]),
            'progress' => countDocs($pdo, ['1=1', "d.status = '진행'"]),
            'rejected' => countDocs($pdo, array_merge($myFilter, ["d.status = '반려'"]), $myParams),
            'temp'     => countDocs($pdo, array_merge($myFilter, ["d.status = '임시저장'"]), $myParams),
        ];
    }

    respond(200, ['documents' => $docs, 'counts' => $counts]);
}

// ===== 결재함 (내가 결재할/결재한 문서 + 대결 위임 문서) =====
function getPending(PDO $pdo): void
{
    global $currentUserId;
    $statusFilter = $_GET['status_filter'] ?? 'requested';

    // 대결 위임자 ID 조회 — 내가 대결자로 지정된 원결재자들
    expireDelegations($pdo);
    $delegatorIds = getDelegatorIdsFor($pdo, (int)$currentUserId);
    $allIds = array_merge([(int)$currentUserId], $delegatorIds);
    $placeholders = implode(',', array_fill(0, count($allIds), '?'));

    // 공통 검색 필터
    $filterWhere = [];
    $filterParams = [];
    buildDocFilters($filterWhere, $filterParams);
    $filterCond = $filterWhere ? ' AND ' . implode(' AND ', $filterWhere) : '';

    if ($statusFilter === 'completed') {
        // 내가 직접 처리했거나, 대결자로서 처리한 문서
        $sql = "SELECT DISTINCT d.*, f.title AS form_title, h2.action AS my_action, h2.action_date AS my_action_date
                FROM approval_documents d
                LEFT JOIN approval_forms f ON d.form_id = f.id
                JOIN approval_history h2 ON h2.document_id = d.id
                    AND (h2.approver_id = ? OR h2.delegate_id = ?)
                WHERE h2.action IN ('승인','반려')
                $filterCond
                ORDER BY my_action_date DESC, d.id DESC";
        $params = array_merge([$currentUserId, $currentUserId], $filterParams);
    } else {
        // 내가 결재할 차례 + 대결 위임 문서
        $sql = "SELECT DISTINCT d.*, f.title AS form_title
                FROM approval_documents d
                LEFT JOIN approval_forms f ON d.form_id = f.id
                JOIN approval_history h ON h.document_id = d.id
                WHERE h.approver_id IN ($placeholders)
                  AND h.action = '대기'
                  AND d.status IN ('대기','진행')
                  AND h.step_order = (
                    SELECT MIN(h2.step_order) FROM approval_history h2
                    WHERE h2.document_id = d.id AND h2.action = '대기'
                  )
                $filterCond
                ORDER BY d.draft_date DESC, d.id DESC";
        $params = array_merge($allIds, $filterParams);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $docs = $stmt->fetchAll();

    // 결재경로 첨부
    attachRoutes($pdo, $docs);

    // 탭별 건수 (대결 위임 문서 포함)
    $reqSql = "SELECT COUNT(DISTINCT d.id) FROM approval_documents d
               JOIN approval_history h ON h.document_id = d.id
               WHERE h.approver_id IN ($placeholders) AND h.action = '대기' AND d.status IN ('대기','진행')
               AND h.step_order = (SELECT MIN(h2.step_order) FROM approval_history h2 WHERE h2.document_id = d.id AND h2.action = '대기')";
    $reqStmt = $pdo->prepare($reqSql);
    $reqStmt->execute($allIds);
    $requestedCount = (int)$reqStmt->fetchColumn();

    $compSql = "SELECT COUNT(DISTINCT d.id) FROM approval_documents d
                JOIN approval_history h ON h.document_id = d.id
                WHERE (h.approver_id = ? OR h.delegate_id = ?) AND h.action IN ('승인','반려')";
    $compStmt = $pdo->prepare($compSql);
    $compStmt->execute([$currentUserId, $currentUserId]);
    $completedCount = (int)$compStmt->fetchColumn();

    respond(200, [
        'documents' => $docs,
        'counts' => ['requested' => $requestedCount, 'completed' => $completedCount],
    ]);
}

// ===== 문서보관함 =====
function getArchive(PDO $pdo): void
{
    global $currentUserId;
    $baseWhere = ['1=1'];
    $baseParams = [];
    buildDocFilters($baseWhere, $baseParams);

    $tabFilter = $_GET['tab_filter'] ?? 'temp';

    if ($tabFilter === 'reference') {
        // 내가 참조자인 문서
        $where = array_merge($baseWhere, ["d.status IN ('승인','반려')"]);
        $sql = "SELECT d.*, f.title AS form_title FROM approval_documents d
                LEFT JOIN approval_forms f ON d.form_id = f.id
                JOIN approval_references r ON r.document_id = d.id
                WHERE r.ref_id = ? AND " . implode(' AND ', $where) . "
                ORDER BY d.draft_date DESC";
        $params = array_merge([$currentUserId], $baseParams);
    } elseif ($tabFilter === 'recalled') {
        $where = array_merge($baseWhere, ["d.status = '회수'", "d.drafter_id = ?"]);
        $sql = buildDocQuery($where);
        $params = array_merge($baseParams, [$currentUserId]);
    } else {
        // 임시저장 (내가 기안한)
        $where = array_merge($baseWhere, ["d.status = '임시저장'", "d.drafter_id = ?"]);
        $sql = buildDocQuery($where);
        $params = array_merge($baseParams, [$currentUserId]);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $docs = $stmt->fetchAll();

    // 탭별 건수
    $counts = ['temp' => 0, 'recalled' => 0, 'reference' => 0];
    try {
        $cStmt = $pdo->prepare("SELECT
            SUM(CASE WHEN status = '임시저장' THEN 1 ELSE 0 END) AS temp_cnt,
            SUM(CASE WHEN status = '회수' THEN 1 ELSE 0 END) AS recalled_cnt
            FROM approval_documents d WHERE d.drafter_id = ?");
        $cStmt->execute([$currentUserId]);
        $c = $cStmt->fetch();
        $counts['temp']     = (int)($c['temp_cnt'] ?? 0);
        $counts['recalled'] = (int)($c['recalled_cnt'] ?? 0);

        $rStmt = $pdo->prepare("SELECT COUNT(*) FROM approval_documents d
            JOIN approval_references r ON r.document_id = d.id
            WHERE d.status IN ('승인','반려') AND r.ref_id = ?");
        $rStmt->execute([$currentUserId]);
        $counts['reference'] = (int)$rStmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('getArchive counts error: ' . $e->getMessage());
    }

    respond(200, ['documents' => $docs, 'counts' => $counts]);
}

// 문서 상세
function getDocument(PDO $pdo): void
{
    global $currentUserId;

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $stmt = $pdo->prepare('SELECT d.*, f.title AS form_title FROM approval_documents d LEFT JOIN approval_forms f ON d.form_id = f.id WHERE d.id = ?');
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if (!$doc) respond(404, ['error' => '문서를 찾을 수 없습니다.']);

    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        $allowed = ((int)($doc['drafter_id'] ?? 0) === $currentUserId);
        if (!$allowed) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM approval_history WHERE document_id = ? AND approver_id = ?');
            $chk->execute([$id, $currentUserId]);
            $allowed = ((int)$chk->fetchColumn() > 0);
        }
        if (!$allowed) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM approval_references WHERE document_id = ? AND ref_id = ?');
            $chk->execute([$id, $currentUserId]);
            $allowed = ((int)$chk->fetchColumn() > 0);
        }
        if (!$allowed) respond(403, ['error' => '이 문서에 대한 접근 권한이 없습니다.']);
    }

    $history = $pdo->prepare('SELECT * FROM approval_history WHERE document_id = ? ORDER BY step_order');
    $history->execute([$id]);
    $doc['history'] = $history->fetchAll();

    $refs = $pdo->prepare('SELECT * FROM approval_references WHERE document_id = ?');
    $refs->execute([$id]);
    $doc['references'] = $refs->fetchAll();

    try {
        $recentView = $pdo->prepare(
            'SELECT COUNT(*) FROM approval_audit_log
             WHERE event_type = ? AND actor_id = ? AND target_id = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)'
        );
        $recentView->execute(['document_viewed', $currentUserId, $id]);
        if ((int)$recentView->fetchColumn() === 0) {
            logApprovalAudit(
                $pdo, 'document_viewed', 'document',
                'document', $id,
                $doc['doc_number'] ?? $doc['title'] ?? null
            );
        }
    } catch (PDOException $e) {
        error_log('Audit log skipped (table may not exist): ' . $e->getMessage());
    }

    respond(200, ['document' => $doc]);
}

// 문서 저장
function saveDocument(PDO $pdo): void
{
    // 세션 사용자 전역 — 신규/수정 양쪽 경로에서 참조하므로 함수 진입 시 한 번만 선언
    global $currentUserId, $currentUser, $currentUserName;

    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $title = trim($data['title'] ?? '');
    if (!$title) respond(400, ['error' => '제목을 입력해주세요.']);

    $status = $data['status'] ?? '임시저장';
    $route = $data['approval_route'] ?? [];

    // 결재선이 있고 상신이면 '대기' (아직 아무도 승인 안 한 상태)
    if ($status === '진행' && !empty($route)) {
        $status = '대기';
    }

    $pdo->beginTransaction();

    if ($id > 0) {
        global $currentUserId, $currentUser;
        $role = $currentUser['role'] ?? '';
        if ($role === 'admin') {
            $stmt = $pdo->prepare('UPDATE approval_documents SET title=?, content=?, doc_type=?, status=?, draft_date=? WHERE id=?');
            $stmt->execute([$title, $data['content'] ?? '', $data['doc_type'] ?? '', $status, $data['draft_date'] ?? date('Y-m-d'), $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE approval_documents SET title=?, content=?, doc_type=?, status=?, draft_date=? WHERE id=? AND drafter_id=?');
            $stmt->execute([$title, $data['content'] ?? '', $data['doc_type'] ?? '', $status, $data['draft_date'] ?? date('Y-m-d'), $id, $currentUserId]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                respond(403, ['error' => '권한이 없거나 존재하지 않는 문서입니다.']);
            }
        }
        // 기존 결재이력 삭제 후 재삽입
        $pdo->prepare('DELETE FROM approval_history WHERE document_id = ?')->execute([$id]);
    } else {
        // 문서번호 · 단일 소스(includes/approval_doc.php)에서 생성
        $docNumber = buildApprovalDocNumber(
            $data['drafter_dept'] ?? 'Zaemit',
            $data['doc_type'] ?? '품의'
        );

        // 세션에서 기안자 정보 사용 ($currentUserId는 위에서 이미 global 선언)
        global $currentUserName;
        $drafterId = $currentUserId ?: null;
        $drafterName = $currentUserName ?: ($data['drafter_name'] ?? '');
        $drafterDept = $data['drafter_dept'] ?? '';

        $stmt = $pdo->prepare('INSERT INTO approval_documents (doc_number, title, content, form_id, doc_type, drafter_id, drafter_name, drafter_dept, status, draft_date) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $docNumber, $title, $data['content'] ?? '',
            !empty($data['form_id']) ? (int)$data['form_id'] : null,
            $data['doc_type'] ?? '', $drafterId, $drafterName,
            $drafterDept, $status, $data['draft_date'] ?? date('Y-m-d')
        ]);
        $id = (int)$pdo->lastInsertId();
    }

    // 결재선: 마지막 결재자(참조/협조 제외)를 전결로 강제 보정, 협조 역할은 보존
    if (!empty($route)) {
        $lastApproverIdx = -1;
        foreach ($route as $i => $step) {
            $r = $step['role'] ?? '결재';
            if ($r !== '참조' && $r !== '협조') $lastApproverIdx = $i;
        }
        if ($lastApproverIdx >= 0) {
            foreach ($route as $i => &$step) {
                $r = $step['role'] ?? '결재';
                if ($r === '참조' || $r === '협조') continue;
                $step['role'] = ($i === $lastApproverIdx) ? '전결' : '결재';
            }
            unset($step);
        }
    }

    // 결재선 → approval_history + approval_references 저장
    if (!empty($route)) {
        $histStmt = $pdo->prepare('INSERT INTO approval_history (document_id, approver_id, approver_name, approver_dept, role, step_order, action) VALUES (?,?,?,?,?,?,?)');
        $refStmt = $pdo->prepare('INSERT INTO approval_references (document_id, ref_id, ref_name, ref_dept) VALUES (?,?,?,?)');
        $empStmt = $pdo->prepare('SELECT e.id, d.name AS dept_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.name = ? AND e.is_active = 1 LIMIT 1');

        // 기존 참조자 삭제 (수정 시)
        $pdo->prepare('DELETE FROM approval_references WHERE document_id = ?')->execute([$id]);

        $stepOrder = 0;
        foreach ($route as $step) {
            $name = $step['name'] ?? '';
            if (!$name) continue;

            // 직원 ID/부서 조회
            $empId = !empty($step['id']) ? (int)$step['id'] : null;
            $empStmt->execute([$name]);
            $emp = $empStmt->fetch();
            if (!$empId && $emp) $empId = (int)$emp['id'];
            $dept = $emp ? ($emp['dept_name'] ?? '') : '';

            $allowedRoles = ['결재', '전결', '협조', '참조'];
            $role = in_array($step['role'] ?? '', $allowedRoles) ? $step['role'] : '결재';
            if ($role === '참조') {
                // 참조자 → approval_references
                $refStmt->execute([$id, $empId, $name, $dept]);
            } else {
                // 결재/전결 → approval_history (role도 함께 저장)
                $stepOrder++;
                $histStmt->execute([$id, $empId, $name, $dept, $role, $stepOrder, '대기']);
            }
        }
    }

    // 알림 발송 (상신일 때만, 임시저장 제외)
    if ($status !== '임시저장' && !empty($route)) {
        // 기안자 정보: 신규는 위에서 정의, 수정 시 DB에서 조회
        if (!isset($drafterId) || !isset($drafterName)) {
            $dInfo = $pdo->prepare('SELECT drafter_id, drafter_name FROM approval_documents WHERE id = ?');
            $dInfo->execute([$id]);
            $dRow = $dInfo->fetch(PDO::FETCH_ASSOC);
            $drafterId = (int)($dRow['drafter_id'] ?? 0);
            $drafterName = $dRow['drafter_name'] ?? '';
        }
        $linkUrl = '/pages/approval_view.php?id=' . $id;

        // 첫 번째 결재자(step_order=1)에게 알림
        $firstStep = $pdo->prepare("SELECT approver_id, role FROM approval_history WHERE document_id = ? AND action = '대기' ORDER BY step_order LIMIT 1");
        $firstStep->execute([$id]);
        $first = $firstStep->fetch(PDO::FETCH_ASSOC);
        if ($first && (int)$first['approver_id'] > 0 && (int)$first['approver_id'] !== $drafterId) {
            $notifType = ($first['role'] === '협조') ? 'approval_cooperated' : 'approval_pending';
            $notifTitle = ($first['role'] === '협조') ? '협조 요청' : '결재 대기';
            createNotification($pdo, (int)$first['approver_id'], $notifType,
                $notifTitle, $drafterName . '님이 "' . mb_substr($title, 0, 30) . '" 문서를 상신했습니다.', $linkUrl);
        }

        // 참조자 전원에게 알림
        $refsStmt = $pdo->prepare('SELECT ref_id FROM approval_references WHERE document_id = ? AND ref_id IS NOT NULL');
        $refsStmt->execute([$id]);
        $refIds = $refsStmt->fetchAll(PDO::FETCH_COLUMN);
        $refIds = array_filter($refIds, fn($rid) => (int)$rid > 0 && (int)$rid !== $drafterId);
        if (!empty($refIds)) {
            createNotificationBatch($pdo, array_map('intval', $refIds), 'approval_referenced',
                '참조 문서', $drafterName . '님이 "' . mb_substr($title, 0, 30) . '" 문서에 참조로 지정했습니다.', $linkUrl);
        }
    }

    // 감사 로그
    if (!isset($docNumber)) {
        $eventType = ($status === '임시저장') ? 'document_updated' : 'document_submitted';
        $lbl = $pdo->prepare('SELECT doc_number FROM approval_documents WHERE id = ?');
        $lbl->execute([$id]);
        $docLabel = $lbl->fetchColumn() ?: '';
    } else {
        $eventType = 'document_created';
        $docLabel = $docNumber;
    }
    logApprovalAudit($pdo, $eventType, 'document', 'document', $id, $docLabel,
        null, ['title' => $title, 'status' => $status]);

    $pdo->commit();
    respond(200, ['id' => $id, 'message' => '저장되었습니다.']);
}

// 문서 삭제
function deleteDocument(PDO $pdo): void
{
    global $currentUserId, $currentUser;
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(400, ['error' => '유효하지 않은 ID']);

    $old = $pdo->prepare('SELECT doc_number, title, status FROM approval_documents WHERE id = ?');
    $old->execute([$id]);
    $oldDoc = $old->fetch(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    $role = $currentUser['role'] ?? '';
    if ($role === 'admin') {
        $stmt = $pdo->prepare('DELETE FROM approval_documents WHERE id = ?');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('DELETE FROM approval_documents WHERE id = ? AND drafter_id = ?');
        $stmt->execute([$id, $currentUserId]);
    }

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        respond(403, ['error' => '권한이 없거나 존재하지 않는 문서입니다.']);
    }

    if ($oldDoc) {
        logApprovalAudit($pdo, 'document_deleted', 'document', 'document', $id,
            $oldDoc['doc_number'], $oldDoc);
    }

    $pdo->commit();
    respond(200, ['message' => '삭제되었습니다.']);
}

// 결재 처리
function approveDocument(PDO $pdo): void
{
    global $currentUserId, $currentUserName;
    $data = getJsonInput();
    $docId = (int)($data['document_id'] ?? 0);
    $action = $data['action'] ?? '';
    $allActions = ['승인', '반려', '합의', '의견'];
    if ($docId <= 0 || !in_array($action, $allActions)) respond(400, ['error' => '유효하지 않은 요청']);

    // 현재 대기 중인 결재자가 로그인 사용자(또는 대결자)인지 검증
    $checkStmt = $pdo->prepare("SELECT approver_id, role FROM approval_history WHERE document_id=? AND action='대기' ORDER BY step_order LIMIT 1");
    $checkStmt->execute([$docId]);
    $pending = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$pending) respond(403, ['error' => '현재 결재 차례가 아닙니다.']);

    $originalApproverId = (int)$pending['approver_id'];
    $isOriginalApprover = ($originalApproverId === (int)$currentUserId);
    $isDelegate = false;
    if (!$isOriginalApprover) {
        expireDelegations($pdo);
        $isDelegate = isDelegateFor($pdo, (int)$currentUserId, $originalApproverId);
    }
    if (!$isOriginalApprover && !$isDelegate) {
        respond(403, ['error' => '현재 결재 차례가 아닙니다.']);
    }
    $currentRole = $pending['role'] ?: '결재';

    // 역할별 허용 액션 검증
    if ($currentRole === '협조') {
        if (!in_array($action, ['합의', '의견'])) {
            respond(400, ['error' => '협조자는 합의 또는 의견만 가능합니다.']);
        }
    } else {
        if (!in_array($action, ['승인', '반려'])) {
            respond(400, ['error' => '결재자는 승인 또는 반려만 가능합니다.']);
        }
    }

    $pdo->beginTransaction();

    // 협조 · '합의'/'의견' 모두 통과 처리 (의견은 코멘트만 남기고 흐름은 계속)
    $historyAction = $action;
    if ($currentRole === '협조') {
        $historyAction = ($action === '합의') ? '합의' : '의견';
    }

    // 현재 대기중인 결재 이력 업데이트 (대결 시 delegate 정보 기록)
    $delegateId = $isDelegate ? (int)$currentUserId : null;
    $delegateName = $isDelegate ? $currentUserName : null;
    $pdo->prepare("UPDATE approval_history SET action=?, comment=?, action_date=NOW(), delegate_id=?, delegate_name=? WHERE document_id=? AND action='대기' ORDER BY step_order LIMIT 1")
        ->execute([$historyAction, $data['comment'] ?? '', $delegateId, $delegateName, $docId]);

    // 반려 또는 전결 승인 시: 나머지 대기 단계 모두 자동 건너뛰기
    if ($action === '반려') {
        $pdo->prepare("UPDATE approval_history SET action='건너뜀', comment='반려에 의한 자동 처리', action_date=NOW() WHERE document_id=? AND action='대기'")
            ->execute([$docId]);
    } elseif ($currentRole === '전결' && $action === '승인') {
        $pdo->prepare("UPDATE approval_history SET action='건너뜀', comment='전결에 의한 자동 처리', action_date=NOW() WHERE document_id=? AND action='대기'")
            ->execute([$docId]);
    }

    // 남은 대기 건수 확인
    $remaining = $pdo->prepare("SELECT COUNT(*) FROM approval_history WHERE document_id=? AND action='대기'");
    $remaining->execute([$docId]);

    // 기안자 정보 조회 (알림 발송용)
    $docInfo = $pdo->prepare('SELECT drafter_id, title FROM approval_documents WHERE id = ?');
    $docInfo->execute([$docId]);
    $docRow = $docInfo->fetch(PDO::FETCH_ASSOC);
    $drafterId = (int)($docRow['drafter_id'] ?? 0);
    $docTitle = $docRow['title'] ?? '';
    $linkUrl = '/pages/approval_view.php?id=' . $docId;
    $titleSnippet = mb_substr($docTitle, 0, 30);

    $remainingCount = $remaining->fetchColumn();

    if ($action === '반려' || $remainingCount == 0) {
        $finalStatus = $action === '반려' ? '반려' : '승인';

        // Phase 1 워크플로우 통합 훅: 승인 시 원본 모듈에 자동 반영
        if ($finalStatus === '승인') {
            processApprovedDocument($pdo, $docId);
        }

        $pdo->prepare('UPDATE approval_documents SET status=?, complete_date=CURDATE() WHERE id=?')->execute([$finalStatus, $docId]);

        // 알림: 기안자에게 최종 결과 통지
        if ($drafterId > 0 && $drafterId !== $currentUserId) {
            if ($finalStatus === '승인') {
                createNotification($pdo, $drafterId, 'approval_approved',
                    '결재 승인', '"' . $titleSnippet . '" 문서가 최종 승인되었습니다.', $linkUrl);
            } else {
                createNotification($pdo, $drafterId, 'approval_rejected',
                    '결재 반려', $currentUserName . '님이 "' . $titleSnippet . '" 문서를 반려했습니다.', $linkUrl);
            }
        }
    } else {
        // 아직 결재할 사람이 남아있으면 '진행'으로 전환
        $pdo->prepare("UPDATE approval_documents SET status='진행' WHERE id=? AND status='대기'")->execute([$docId]);

        // 알림: 기안자에게 진행 알림
        if ($drafterId > 0 && $drafterId !== $currentUserId) {
            createNotification($pdo, $drafterId, 'approval_progress',
                '결재 진행', $currentUserName . '님이 "' . $titleSnippet . '" 문서를 ' . $historyAction . '했습니다.', $linkUrl);
        }

        // 알림: 다음 결재자에게 대기 알림
        $nextStep = $pdo->prepare("SELECT approver_id, role FROM approval_history WHERE document_id = ? AND action = '대기' ORDER BY step_order LIMIT 1");
        $nextStep->execute([$docId]);
        $next = $nextStep->fetch(PDO::FETCH_ASSOC);
        if ($next && (int)$next['approver_id'] > 0 && (int)$next['approver_id'] !== $currentUserId) {
            $nextType = ($next['role'] === '협조') ? 'approval_cooperated' : 'approval_pending';
            $nextTitle = ($next['role'] === '협조') ? '협조 요청' : '결재 대기';
            createNotification($pdo, (int)$next['approver_id'], $nextType,
                $nextTitle, '"' . $titleSnippet . '" 문서의 결재 차례입니다.', $linkUrl);
        }
    }

    $pdo->commit();

    // 대결 처리 시 원결재자에게 알림 (commit 후 발송 — 롤백 시 유령 알림 방지)
    if ($isDelegate && $originalApproverId > 0) {
        createNotification($pdo, $originalApproverId, 'delegate_acted',
            '대결 처리', '대결자 ' . $currentUserName . '님이 "' . $titleSnippet . '" 문서를 ' . $historyAction . '했습니다.', $linkUrl);
    }

    respond(200, ['message' => '처리되었습니다.']);
}

/**
 * 승인된 결재문서를 원본 모듈에 반영하는 워크플로우 훅.
 *
 * 확장 방법: doc_type별 분기 추가 (OCP 원칙)
 *   - 경비청구서 → card_expenses.is_settled = 1
 *   - (향후) 휴가신청서 → annual_leave.used_days 차감
 *   - (향후) 야근신청서 → attendance_records.overtime 반영
 *
 * metadata 포맷: {"source": "...", "source_id": N}
 */
function processApprovedDocument(PDO $pdo, int $docId): void
{
    $st = $pdo->prepare('SELECT doc_type, metadata FROM approval_documents WHERE id = ?');
    $st->execute([$docId]);
    $doc = $st->fetch();
    if (!$doc || empty($doc['metadata'])) return;

    $meta = json_decode($doc['metadata'], true);
    if (!is_array($meta)) return;

    $source   = $meta['source']    ?? '';
    $sourceId = (int)($meta['source_id'] ?? 0);
    if ($sourceId <= 0) return;

    // 경비청구서 승인 → 해당 카드 경비를 정산완료 처리
    if ($doc['doc_type'] === '경비청구서' && $source === 'card_expense') {
        $pdo->prepare("UPDATE card_expenses
                       SET is_settled = 1,
                           compliance_status = '준수',
                           settlement_date = NOW()
                       WHERE id = ?")
            ->execute([$sourceId]);
    }
    // 향후 확장:
    // elseif ($doc['doc_type'] === '휴가신청서' && $source === 'leave_request') { ... }
    // elseif ($doc['doc_type'] === '야근신청서' && $source === 'overtime_request') { ... }
}

// ===== 결재선 =====
function getLines(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];
    if (!empty($_GET['department'])) { $where[] = 'department LIKE ?'; $params[] = '%' . $_GET['department'] . '%'; }
    if (!empty($_GET['doc_type'])) { $where[] = 'doc_type = ?'; $params[] = $_GET['doc_type']; }

    $stmt = $pdo->prepare('SELECT * FROM approval_lines WHERE ' . implode(' AND ', $where) . ' ORDER BY doc_type, amount_threshold, id DESC');
    $stmt->execute($params);
    respond(200, ['lines' => $stmt->fetchAll()]);
}

function getLine(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM approval_lines WHERE id = ?');
    $stmt->execute([$id]);
    $line = $stmt->fetch();
    if (!$line) respond(404, ['error' => '결재선을 찾을 수 없습니다.']);
    respond(200, ['line' => $line]);
}

function saveLine(PDO $pdo): void
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $role = (string)($_SESSION['user']['role'] ?? '');
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $scope = ($data['scope'] ?? 'personal') === 'global' ? 'global' : 'personal';
    if (!$name) respond(400, ['error' => '결재선 이름을 입력해주세요.']);

    $isAdmin = in_array($role, ['admin', 'manager'], true);

    if ($scope === 'global' && !$isAdmin) {
        respond(403, ['error' => '전사 결재선은 관리자만 저장할 수 있어요.']);
    }

    $createdBy = ($scope === 'personal') ? $userId : null;

    $amountThreshold = max(0, (int)($data['amount_threshold'] ?? 0));

    $isNew = ($id <= 0);
    if ($id > 0) {
        $check = $pdo->prepare('SELECT created_by, scope FROM approval_lines WHERE id = ?');
        $check->execute([$id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if (!$existing) respond(404, ['error' => '결재선을 찾을 수 없습니다.']);
        if (($existing['scope'] === 'global' || $scope === 'global') && !$isAdmin) {
            respond(403, ['error' => '전사 결재선은 관리자만 수정할 수 있어요.']);
        }
        if ($existing['scope'] === 'personal' && (int)$existing['created_by'] !== $userId && !$isAdmin) {
            respond(403, ['error' => '본인의 결재선만 수정할 수 있어요.']);
        }
        if (!$isAdmin) { $scope = $existing['scope']; $createdBy = $existing['created_by']; }
        $pdo->prepare('UPDATE approval_lines SET name=?, department=?, doc_type=?, line_data=?, amount_threshold=?, created_by=?, scope=? WHERE id=?')
            ->execute([$name, $data['department'] ?? '', $data['doc_type'] ?? '', json_encode($data['line_data'] ?? [], JSON_UNESCAPED_UNICODE), $amountThreshold, $createdBy, $scope, $id]);
    } else {
        $pdo->prepare('INSERT INTO approval_lines (name, department, doc_type, line_data, amount_threshold, created_by, scope) VALUES (?,?,?,?,?,?,?)')
            ->execute([$name, $data['department'] ?? '', $data['doc_type'] ?? '', json_encode($data['line_data'] ?? [], JSON_UNESCAPED_UNICODE), $amountThreshold, $createdBy, $scope]);
        $id = (int)$pdo->lastInsertId();
    }

    logApprovalAudit($pdo, $isNew ? 'line_created' : 'line_updated', 'config', 'line', $id, $name,
        null, ['department' => $data['department'] ?? '', 'doc_type' => $data['doc_type'] ?? '', 'scope' => $scope]);
    respond(200, ['id' => $id, 'message' => '저장되었습니다.']);
}

function deleteLine(PDO $pdo): void
{
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) respond(403, ['error' => '권한이 없습니다.']);

    $data = getJsonInput();
    $lineId = (int)($data['id'] ?? 0);

    $old = $pdo->prepare('SELECT name, department, doc_type FROM approval_lines WHERE id = ?');
    $old->execute([$lineId]);
    $oldLine = $old->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare('DELETE FROM approval_lines WHERE id = ?')->execute([$lineId]);

    if ($oldLine) {
        logApprovalAudit($pdo, 'line_deleted', 'config', 'line', $lineId, $oldLine['name'], $oldLine);
    }
    respond(200, ['message' => '삭제되었습니다.']);
}

/**
 * 결재 경로 자동 해소 · 작성화면 자동 채움 + 관리자 시뮬레이터 공용.
 * 입력: doc_type(필수), employee_id(선택, 없으면 세션 사용자)
 * 출력: { route: [{employee_id,name,dept,position,title,role}] }
 */
function getResolvedRoute(PDO $pdo): void
{
    global $currentUserId;
    $docType = trim($_GET['doc_type'] ?? '');

    $empId = (int)($_GET['employee_id'] ?? 0) ?: (int)$currentUserId;
    if ($empId <= 0) respond(400, ['error' => '작성자를 확인할 수 없습니다.']);

    // 작성자 부서 id + 부서명
    $stmt = $pdo->prepare(
        "SELECT e.department_id, COALESCE(d.name,'') AS dept_name
         FROM employees e LEFT JOIN departments d ON e.department_id = d.id
         WHERE e.id = ? LIMIT 1"
    );
    $stmt->execute([$empId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $deptId   = $row ? (int)($row['department_id'] ?? 0) : 0;
    $deptName = $row ? (string)($row['dept_name'] ?? '') : '';

    $amount = isset($_GET['amount']) ? (int)$_GET['amount'] : null;
    $route = resolveApprovalRoute($pdo, $deptName, $docType, $deptId ?: null, $empId, $amount) ?? [];

    // position/title 보강 (작성화면·시뮬레이터 표시용)
    $ids = array_values(array_filter(array_map(static fn($r) => $r['employee_id'] ?? null, $route)));
    $meta = [];
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $ps = $pdo->prepare("SELECT id, position, title FROM employees WHERE id IN ($in)");
        $ps->execute($ids);
        foreach ($ps->fetchAll(PDO::FETCH_ASSOC) as $e) $meta[(int)$e['id']] = $e;
    }
    foreach ($route as &$r) {
        $eid = $r['employee_id'] ?? null;
        $r['position'] = ($eid && isset($meta[$eid])) ? (string)$meta[$eid]['position'] : '';
        $r['title']    = ($eid && isset($meta[$eid])) ? (string)$meta[$eid]['title'] : '';
    }
    unset($r);

    respond(200, ['route' => $route]);
}

/** 결재라인 설정 조회 · 기본 depth + 부서장 미지정 경고 */
function getApprovalConfig(PDO $pdo): void
{
    $missing = $pdo->query(
        "SELECT d.id, d.name FROM departments d
         WHERE d.is_active = 1 AND (d.head_employee_id IS NULL OR d.head_employee_id = 0)
         ORDER BY d.sort_order, d.name"
    )->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'default_depth' => getApprovalDefaultDepth(),
        'depths'        => array_keys(getApprovalDepthOptions()),
        'depth_options' => getApprovalDepthOptions(),
        'missing_heads' => $missing,
    ]);
}

/** 결재라인 설정 저장 · 기본 depth (관리자 전용) */
function saveApprovalConfig(PDO $pdo): void
{
    global $currentUser;
    if (($currentUser['role'] ?? '') !== 'admin') respond(403, ['error' => '관리자만 변경할 수 있습니다.']);
    $data = getJsonInput();
    $depth = trim($data['default_depth'] ?? '');
    if (!isset(getApprovalDepthOptions()[$depth])) respond(400, ['error' => '잘못된 depth 값입니다.']);
    $oldDepth = getApprovalDefaultDepth();
    if (!saveApprovalSettings(['default_depth' => $depth])) respond(500, ['error' => '저장에 실패했습니다.']);

    logApprovalAudit($pdo, 'config_changed', 'config', 'config', null, '결재 기본 depth',
        ['default_depth' => $oldDepth], ['default_depth' => $depth]);
    respond(200, ['message' => '저장되었습니다.', 'default_depth' => $depth]);
}

// ===== 결재양식 =====
function getForms(PDO $pdo): void
{
    $where = ['1=1'];
    $params = [];
    if (!empty($_GET['title'])) { $where[] = 'title LIKE ?'; $params[] = '%' . $_GET['title'] . '%'; }
    if (!empty($_GET['doc_type'])) { $where[] = 'doc_type = ?'; $params[] = $_GET['doc_type']; }

    $stmt = $pdo->prepare('SELECT * FROM approval_forms WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC');
    $stmt->execute($params);
    respond(200, ['forms' => $stmt->fetchAll()]);
}

function getForm(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM approval_forms WHERE id = ?');
    $stmt->execute([$id]);
    $form = $stmt->fetch();
    if (!$form) respond(404, ['error' => '양식을 찾을 수 없습니다.']);
    respond(200, ['form' => $form]);
}

function saveForm(PDO $pdo): void
{
    apiRequireAdminOrManager(); // 양식 관리 = admin/manager 전용
    $data = getJsonInput();
    $id = (int)($data['id'] ?? 0);
    $docType = trim($data['doc_type'] ?? '');
    $title = trim($data['title'] ?? '');
    if (!$docType) respond(400, ['error' => '문서종류를 입력해주세요.']);

    // 저장 본문은 사용자가 통제하므로 DB 기록 전 항상 소독 (Stored XSS 차단)
    $template = sanitizeTemplateHtml((string)($data['content_template'] ?? ''));

    $description = trim($data['description'] ?? '') ?: null;
    $allowedDepts = isset($data['allowed_departments']) && is_array($data['allowed_departments']) && count($data['allowed_departments'])
        ? json_encode($data['allowed_departments'], JSON_UNESCAPED_UNICODE) : null;
    $allowedPos = isset($data['allowed_positions']) && is_array($data['allowed_positions']) && count($data['allowed_positions'])
        ? json_encode($data['allowed_positions'], JSON_UNESCAPED_UNICODE) : null;
    $retentionDays = isset($data['retention_days']) && is_numeric($data['retention_days'])
        ? (int)$data['retention_days'] : null;

    $isNew = ($id <= 0);
    if ($id > 0) {
        $pdo->prepare('UPDATE approval_forms SET doc_type=?, title=?, content_template=?, is_active=?, description=?, allowed_departments=?, allowed_positions=?, retention_days=? WHERE id=?')
            ->execute([$docType, $title ?: $docType, $template, (int)($data['is_active'] ?? 1), $description, $allowedDepts, $allowedPos, $retentionDays, $id]);
    } else {
        global $currentUserId;
        $pdo->prepare('INSERT INTO approval_forms (doc_type, title, content_template, is_active, description, allowed_departments, allowed_positions, retention_days, created_by) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$docType, $title ?: $docType, $template, (int)($data['is_active'] ?? 1), $description, $allowedDepts, $allowedPos, $retentionDays, $currentUserId ?: null]);
        $id = (int)$pdo->lastInsertId();
    }

    logApprovalAudit($pdo, $isNew ? 'form_created' : 'form_updated', 'form', 'form', $id,
        $title ?: $docType, null, ['doc_type' => $docType, 'is_active' => (int)($data['is_active'] ?? 1)]);
    respond(200, ['id' => $id, 'message' => '저장되었습니다.']);
}

function deleteForm(PDO $pdo): void
{
    apiRequireAdminOrManager(); // 양식 관리 = admin/manager 전용
    $data = getJsonInput();
    $formId = (int)($data['id'] ?? 0);

    $old = $pdo->prepare('SELECT doc_type, title, is_active FROM approval_forms WHERE id = ?');
    $old->execute([$formId]);
    $oldForm = $old->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare('DELETE FROM approval_forms WHERE id = ?')->execute([$formId]);

    if ($oldForm) {
        logApprovalAudit($pdo, 'form_deleted', 'form', 'form', $formId, $oldForm['title'], $oldForm);
    }
    respond(200, ['message' => '삭제되었습니다.']);
}

function toggleForm(PDO $pdo): void
{
    apiRequireAdminOrManager(); // 양식 관리 = admin/manager 전용
    $data = getJsonInput();
    $formId = (int)($data['id'] ?? 0);

    $old = $pdo->prepare('SELECT title, is_active FROM approval_forms WHERE id = ?');
    $old->execute([$formId]);
    $oldForm = $old->fetch(PDO::FETCH_ASSOC);
    $oldActive = $oldForm ? (int)$oldForm['is_active'] : 0;

    $pdo->prepare('UPDATE approval_forms SET is_active = NOT is_active WHERE id = ?')->execute([$formId]);

    logApprovalAudit($pdo, 'form_toggled', 'form', 'form', $formId,
        $oldForm['title'] ?? '', ['is_active' => $oldActive], ['is_active' => $oldActive ? 0 : 1]);
    respond(200, ['message' => '변경되었습니다.']);
}

function seedDefaultForms(PDO $pdo): void
{
    apiRequireAdminOrManager(); // 양식 관리 = admin/manager 전용
    $templates = approval_form_seed_templates();
    $find = $pdo->prepare('SELECT id FROM approval_forms WHERE doc_type = ? ORDER BY id LIMIT 1');
    $update = $pdo->prepare('UPDATE approval_forms SET title = ?, content_template = ?, is_active = 1 WHERE id = ?');
    $insert = $pdo->prepare('INSERT INTO approval_forms (doc_type, title, content_template, is_active) VALUES (?,?,?,1)');

    $inserted = 0;
    $updated = 0;
    foreach ($templates as $tpl) {
        $docType = trim($tpl['doc_type'] ?? '');
        $title = trim($tpl['title'] ?? '') ?: $docType;
        $html = $tpl['html'] ?? '';
        if ($docType === '') continue;

        $find->execute([$docType]);
        $id = (int)($find->fetchColumn() ?: 0);
        if ($id > 0) {
            $update->execute([$title, $html, $id]);
            $updated++;
        } else {
            $insert->execute([$docType, $title, $html]);
            $inserted++;
        }
    }

    respond(200, [
        'message' => '실무 기본 양식이 적용되었습니다.',
        'inserted' => $inserted,
        'updated' => $updated,
        'total' => count($templates),
    ]);
}

function importFormTemplate(): void
{
    apiRequireAdminOrManager(); // 양식 업로드 = admin/manager 전용 (저장형 XSS 차단)
    if (empty($_FILES['template_file']) || !is_uploaded_file($_FILES['template_file']['tmp_name'])) {
        respond(400, ['error' => '업로드된 파일이 없습니다.']);
    }

    $file = $_FILES['template_file'];
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        respond(400, ['error' => '3MB 이하 파일만 업로드할 수 있습니다.']);
    }

    $originalName = (string)($file['name'] ?? 'template');
    $baseName = trim(pathinfo($originalName, PATHINFO_FILENAME));
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $tmp = $file['tmp_name'];

    $html = '';
    if (in_array($ext, ['html', 'htm'], true)) {
        $html = sanitizeTemplateHtml((string)file_get_contents($tmp));
    } elseif (in_array($ext, ['txt', 'md'], true)) {
        $html = plainTextTemplateToHtml((string)file_get_contents($tmp));
    } elseif (in_array($ext, ['csv', 'tsv'], true)) {
        $html = delimitedTemplateToHtml($tmp, $ext === 'tsv' ? "\t" : ',');
    } elseif ($ext === 'docx') {
        $html = docxTemplateToHtml($tmp);
    } else {
        respond(400, ['error' => '지원 형식: HTML, TXT/MD, CSV/TSV, DOCX']);
    }

    if (trim(strip_tags($html)) === '') {
        respond(400, ['error' => '본문으로 변환할 내용이 없습니다.']);
    }

    $suggested = preg_replace('/[_\-]+/u', ' ', $baseName ?: '외부 양식');
    respond(200, [
        'title' => $suggested,
        'doc_type' => $suggested,
        'html' => $html,
        'message' => '양식을 불러왔습니다.',
    ]);
}

/**
 * 양식 본문 HTML 소독 · 허용리스트(HTML Purifier) 기반.
 * 표/서식 태그는 살리고 script·이벤트핸들러·javascript: 등 실행 경로는 파서 단계에서 차단한다.
 * (이전의 정규식 블록리스트는 중첩·인코딩·개행 우회에 취약하여 교체됨.)
 */
function sanitizeTemplateHtml(string $html): string
{
    static $purifier = null;
    if ($purifier === null) {
        require_once __DIR__ . '/../includes/lib/htmlpurifier/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null); // 캐시 디렉토리 쓰기 불필요(소량 처리)
        // 결재 양식에 필요한 서식·표 태그만 허용
        $config->set('HTML.Allowed',
            'p,br,b,strong,i,em,u,s,span[style],div[style],'
            . 'h1,h2,h3,h4,h5,h6,ul,ol,li,hr,blockquote,'
            . 'table,thead,tbody,tr,td[colspan|rowspan|style],th[colspan|rowspan|style],a[href]');
        $config->set('CSS.AllowedProperties',
            'font-weight,font-style,text-align,text-decoration,width,color,background-color');
        $config->set('Attr.AllowedFrameTargets', []); // target=_blank 등 차단
        $purifier = new HTMLPurifier($config);
    }
    $clean = trim($purifier->purify($html));
    return stripTableElementBackgrounds($clean);
}

function stripTableElementBackgrounds(string $html): string
{
    return preg_replace_callback('/<(tr|thead|tbody|th|td)(\b[^>]*)>/iu', static function ($matches) {
        $tag = $matches[0];
        $tag = preg_replace('/\s*background(?:-color)?\s*:\s*[^;"\']+\s*;?/iu', '', $tag) ?? $tag;
        $tag = preg_replace('/\sstyle=("|\')\s*\1/iu', '', $tag) ?? $tag;
        return $tag;
    }, $html) ?? $html;
}

function plainTextTemplateToHtml(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    if ($text === '') return '';

    $blocks = preg_split('/\n{2,}/', $text) ?: [];
    $html = '';
    foreach ($blocks as $block) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), static fn($v) => $v !== ''));
        if (!$lines) continue;
        if (count($lines) === 1 && preg_match('/^(#{1,3}\s+|[■□▶-]\s*)/u', $lines[0])) {
            $title = preg_replace('/^(#{1,3}\s+|[■□▶-]\s*)/u', '', $lines[0]);
            $html .= '<p style="margin:14px 0 8px 0;font-weight:700;color:#111827;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            $html .= '<p style="margin:8px 0;line-height:1.8;color:#111827;">' . nl2br(htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8')) . '</p>';
        }
    }
    return $html;
}

function delimitedTemplateToHtml(string $path, string $delimiter): string
{
    $handle = fopen($path, 'r');
    if (!$handle) return '';
    $rows = [];
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $rows[] = $row;
        if (count($rows) >= 80) break;
    }
    fclose($handle);
    if (!$rows) return '';

    $html = '<table style="width:100%;border-collapse:collapse;font-size:13px;color:#111827;">';
    foreach ($rows as $i => $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $tag = $i === 0 ? 'th' : 'td';
            $style = $i === 0
                ? 'border:1px solid #d1d5db;padding:8px 10px;text-align:left;color:#374151;font-weight:700;'
                : 'border:1px solid #d1d5db;padding:8px 10px;';
            $html .= '<' . $tag . ' style="' . $style . '">' . htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') . '</' . $tag . '>';
        }
        $html .= '</tr>';
    }
    return $html . '</table>';
}

function docxTemplateToHtml(string $path): string
{
    $xml = '';
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            respond(400, ['error' => 'DOCX 파일을 열 수 없습니다.']);
        }
        $xml = (string)$zip->getFromName('word/document.xml');
        $zip->close();
    } else {
        $xml = zipEntryContents($path, 'word/document.xml') ?? '';
    }
    if ($xml === '') respond(400, ['error' => 'DOCX 본문을 찾을 수 없습니다.']);

    $xml = preg_replace('/<w:tab\/>/u', "\t", $xml) ?? $xml;
    $xml = preg_replace('/<\/w:tc>/u', "\t", $xml) ?? $xml;
    $xml = preg_replace('/<\/w:tr>/u', "\n", $xml) ?? $xml;
    $xml = preg_replace('/<\/w:p>/u', "\n", $xml) ?? $xml;
    $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    return plainTextTemplateToHtml($text);
}

function zipEntryContents(string $path, string $entryName): ?string
{
    $data = @file_get_contents($path);
    if ($data === false || strlen($data) < 22) return null;

    $eocd = strrpos($data, "PK\x05\x06");
    if ($eocd === false || strlen($data) < $eocd + 22) return null;

    $totalEntries = unpack('v', substr($data, $eocd + 10, 2))[1] ?? 0;
    $centralOffset = unpack('V', substr($data, $eocd + 16, 4))[1] ?? 0;
    $ptr = $centralOffset;

    for ($i = 0; $i < $totalEntries; $i++) {
        if (substr($data, $ptr, 4) !== "PK\x01\x02") return null;
        $method = unpack('v', substr($data, $ptr + 10, 2))[1] ?? 0;
        $compressedSize = unpack('V', substr($data, $ptr + 20, 4))[1] ?? 0;
        $fileNameLength = unpack('v', substr($data, $ptr + 28, 2))[1] ?? 0;
        $extraLength = unpack('v', substr($data, $ptr + 30, 2))[1] ?? 0;
        $commentLength = unpack('v', substr($data, $ptr + 32, 2))[1] ?? 0;
        $localOffset = unpack('V', substr($data, $ptr + 42, 4))[1] ?? 0;
        $name = substr($data, $ptr + 46, $fileNameLength);

        if ($name === $entryName) {
            if (substr($data, $localOffset, 4) !== "PK\x03\x04") return null;
            $localNameLength = unpack('v', substr($data, $localOffset + 26, 2))[1] ?? 0;
            $localExtraLength = unpack('v', substr($data, $localOffset + 28, 2))[1] ?? 0;
            $dataOffset = $localOffset + 30 + $localNameLength + $localExtraLength;
            $payload = substr($data, $dataOffset, $compressedSize);
            if ($method === 0) return $payload;
            if ($method === 8 && function_exists('gzinflate')) {
                $inflated = @gzinflate($payload);
                return $inflated === false ? null : $inflated;
            }
            return null;
        }
        $ptr += 46 + $fileNameLength + $extraLength + $commentLength;
    }
    return null;
}

// ===== Helper =====

/** 문서 배열에 결재경로(route) + 참조자(references) 첨부 */
function attachRoutes(PDO $pdo, array &$docs): void
{
    if (empty($docs)) return;
    $routeStmt = $pdo->prepare(
        "SELECT h.approver_id, h.approver_name AS name, h.approver_dept, h.role, h.step_order, h.action, h.action_date, h.comment,
                h.delegate_id, h.delegate_name,
                COALESCE(e.position, '') AS position,
                COALESCE(e.name, h.approver_name) AS current_name
         FROM approval_history h
         LEFT JOIN employees e ON e.id = h.approver_id
         WHERE h.document_id = ? ORDER BY h.step_order"
    );
    $refStmt = $pdo->prepare(
        "SELECT r.ref_id, r.ref_name, r.ref_dept,
                COALESCE(e.position, '') AS position,
                COALESCE(e.name, r.ref_name) AS current_name
         FROM approval_references r
         LEFT JOIN employees e ON e.id = r.ref_id
         WHERE r.document_id = ?"
    );
    foreach ($docs as &$doc) {
        // 결재/전결 경로
        $routeStmt->execute([$doc['id']]);
        $rows = $routeStmt->fetchAll();
        $doc['route'] = array_map(function ($h) {
            return [
                'role'        => $h['role'] ?? '결재',
                'approver_id' => $h['approver_id'] ? (int)$h['approver_id'] : null,
                'name'        => $h['current_name'] ?? $h['name'],
                'position'    => $h['position'],
                'status'      => match($h['action']) { '승인' => '승인', '반려' => '반려', '건너뜀' => '건너뜀', default => '대기' },
                'date'        => $h['action_date'] ? substr($h['action_date'], 0, 10) : null,
                'comment'     => $h['comment'] ?? '',
                'delegate_id'   => $h['delegate_id'] ? (int)$h['delegate_id'] : null,
                'delegate_name' => $h['delegate_name'] ?? null,
            ];
        }, $rows);

        // 참조자
        $refStmt->execute([$doc['id']]);
        $refs = $refStmt->fetchAll();
        $doc['references'] = array_map(function ($r) {
            return [
                'role'     => '참조',
                'ref_id'   => $r['ref_id'] ? (int)$r['ref_id'] : null,
                'name'     => $r['current_name'] ?? $r['ref_name'],
                'position' => $r['position'],
            ];
        }, $refs);
    }
    unset($doc);
}

function buildDocFilters(array &$where, array &$params): void
{
    if (!empty($_GET['title'])) { $where[] = 'd.title LIKE ?'; $params[] = '%' . $_GET['title'] . '%'; }
    if (!empty($_GET['drafter'])) { $where[] = 'd.drafter_name LIKE ?'; $params[] = '%' . $_GET['drafter'] . '%'; }
    if (!empty($_GET['drafter_dept'])) { $where[] = 'd.drafter_dept LIKE ?'; $params[] = '%' . $_GET['drafter_dept'] . '%'; }
    if (!empty($_GET['doc_number'])) { $where[] = 'd.doc_number LIKE ?'; $params[] = '%' . $_GET['doc_number'] . '%'; }
    if (!empty($_GET['doc_type'])) { $where[] = 'd.doc_type = ?'; $params[] = $_GET['doc_type']; }
    if (!empty($_GET['date_from'])) { $where[] = 'd.draft_date >= ?'; $params[] = $_GET['date_from']; }
    if (!empty($_GET['date_to'])) { $where[] = 'd.draft_date <= ?'; $params[] = $_GET['date_to']; }
}

function buildDocQuery(array $where): string
{
    return "SELECT d.*, f.title AS form_title FROM approval_documents d
            LEFT JOIN approval_forms f ON d.form_id = f.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.draft_date DESC, d.id DESC";
}

function countDocs(PDO $pdo, array $conditions, array $params = []): int
{
    $sql = "SELECT COUNT(*) FROM approval_documents d WHERE " . implode(' AND ', $conditions);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
