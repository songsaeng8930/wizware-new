<?php
/**
 * Zaemit 그룹웨어 - 사내게시판 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getPosts':          getPosts();          break;
    case 'getPost':           getPost();           break;
    case 'createPost':        createPost();        break;
    case 'updatePost':        updatePost();        break;
    case 'deletePost':        deletePost();        break;
    case 'getComments':       getComments();       break;
    case 'addComment':        addComment();        break;
    case 'deleteComment':     deleteComment();     break;
    case 'uploadAttachment':  uploadAttachment();  break;
    case 'deleteAttachment':  deleteAttachment();  break;
    case 'downloadAttachment': downloadAttachment(); break;
    default:
        respond(400, ['error' => '알 수 없는 액션']);
}

/* 테이블은 db/schema_board.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */

// ========== 헬퍼 ==========

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

function getSessionUser(): array
{
    $sess = $_SESSION['user'] ?? [];
    return [
        'id'            => (int)($sess['id'] ?? 0),
        'role'          => (string)($sess['role'] ?? 'user'),
        'department_id' => (int)($sess['department_id'] ?? 0),
    ];
}

function isAdminOrManager(array $user): bool
{
    return in_array($user['role'], ['admin', 'manager'], true);
}

function isDeptHead(PDO $pdo, int $employeeId, int $departmentId): bool
{
    if ($employeeId <= 0 || $departmentId <= 0) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM employees WHERE id = ? AND department_id = ? AND is_dept_head = 1 AND is_active = 1");
    $stmt->execute([$employeeId, $departmentId]);
    return (bool)$stmt->fetchColumn();
}

function canPostNotice(PDO $pdo, array $user): bool
{
    if (isAdminOrManager($user)) return true;
    return isDeptHead($pdo, $user['id'], $user['department_id']);
}

function checkDeptAccess(PDO $pdo, int $postId, array $user): void
{
    $stmt = $pdo->prepare("SELECT board_type, department_id FROM board_posts WHERE id = ? AND status = 'active'");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) { return; }
    if ($post['board_type'] === 'department'
        && !isAdminOrManager($user)
        && (int)$post['department_id'] !== $user['department_id']) {
        apiError('FORBIDDEN', '다른 부서의 게시글에 접근할 수 없습니다.', 403);
    }
}

// ========== 게시글 목록 ==========
function getPosts(): void
{
    $type     = $_GET['type'] ?? 'notice';
    $category = $_GET['category'] ?? '전체';
    $keyword  = trim($_GET['keyword'] ?? '');
    $field    = $_GET['field'] ?? 'title';
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $perPage  = max(1, min(100, (int)($_GET['perPage'] ?? 10)));

    $validTypes = ['notice', 'free', 'archive', 'department'];
    if (!in_array($type, $validTypes, true)) {
        $type = 'notice';
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
        return;
    }

    $user = getSessionUser();

    try {
        $where  = ['bp.board_type = ?', "bp.status = 'active'"];
        $params = [$type];

        // 부서게시판: 부서 격리
        if ($type === 'department') {
            $filterDeptId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

            if (isAdminOrManager($user) && $filterDeptId > 0) {
                $where[]  = 'bp.department_id = ?';
                $params[] = $filterDeptId;
            } elseif ($user['department_id'] > 0) {
                $where[]  = 'bp.department_id = ?';
                $params[] = $user['department_id'];
            } else {
                respond(200, ['posts' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage]);
                return;
            }
        }

        // 카테고리 필터 (부서게시판 외)
        if ($type !== 'department' && $category !== '전체' && $category !== '') {
            $where[]  = 'bp.category = ?';
            $params[] = $category;
        }

        // 키워드 검색
        if ($keyword !== '') {
            $validFields = ['title', 'author', 'content'];
            if (!in_array($field, $validFields, true)) {
                $field = 'title';
            }
            $like = "%{$keyword}%";
            if ($field === 'author') {
                $where[]  = 'bp.author_name LIKE ?';
                $params[] = $like;
            } elseif ($field === 'content') {
                $where[]  = '(bp.title LIKE ? OR bp.content LIKE ?)';
                $params[] = $like;
                $params[] = $like;
            } else {
                $where[]  = 'bp.title LIKE ?';
                $params[] = $like;
            }
        }

        $whereSql = implode(' AND ', $where);

        // 총 건수
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM board_posts bp WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // 페이지네이션
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT bp.id, bp.board_type, bp.category, bp.title, bp.author_id, bp.author_name, bp.author_dept,
                   bp.department_id, bp.is_pinned, bp.views, bp.created_at, bp.updated_at,
                   (SELECT COUNT(*) FROM board_comments bc WHERE bc.post_id = bp.id AND bc.status = 'active') AS comment_count,
                   (SELECT COUNT(*) FROM board_attachments ba WHERE ba.post_id = bp.id) AS attachment_count
            FROM board_posts bp
            WHERE {$whereSql}
            ORDER BY bp.is_pinned DESC, bp.created_at DESC
            LIMIT ? OFFSET ?
        ");
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();

        respond(200, [
            'posts'   => $posts,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ]);
    } catch (PDOException $e) {
        error_log('[Board API] getPosts error: ' . $e->getMessage());
        respond(500, ['error' => '게시글 목록을 불러오는 중 오류가 발생했습니다.']);
    }
}

// ========== 게시글 상세 + 조회수 증가 ==========
function getPost(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        respond(400, ['error' => '잘못된 요청입니다.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(404, ['error' => 'DB 연결 실패']);
        return;
    }

    $user = getSessionUser();

    try {
        $stmt = $pdo->prepare("
            SELECT id, board_type, category, title, content, author_id, author_name, author_dept,
                   department_id, is_pinned, views, created_at, updated_at
            FROM board_posts
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) {
            respond(404, ['error' => '게시글을 찾을 수 없습니다.']);
            return;
        }

        // 부서게시판: 본인 부서만 열람 가능 (admin/manager 예외)
        if ($post['board_type'] === 'department'
            && !isAdminOrManager($user)
            && (int)$post['department_id'] !== $user['department_id']) {
            respond(403, ['error' => '다른 부서의 게시글에 접근할 수 없습니다.']);
            return;
        }

        // 접근 허용 후 조회수 증가 (세션 기반 중복 방지)
        $viewKey = 'board_viewed_' . $id;
        if (empty($_SESSION[$viewKey])) {
            $pdo->prepare("UPDATE board_posts SET views = views + 1 WHERE id = ?")->execute([$id]);
            $_SESSION[$viewKey] = true;
            $post['views'] = (int)$post['views'] + 1;
        }

        // 본인 글 여부 플래그
        $post['is_mine'] = ($user['id'] > 0 && (int)$post['author_id'] === $user['id']);
        $post['can_edit'] = $post['is_mine'] || isAdminOrManager($user);

        // 첨부파일 목록
        $attStmt = $pdo->prepare("SELECT id, original_name, file_size, mime_type, uploaded_by, created_at FROM board_attachments WHERE post_id = ? ORDER BY created_at ASC");
        $attStmt->execute([$id]);
        $attachments = $attStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($attachments as &$a) {
            $a['can_delete'] = ($user['id'] > 0 && ((int)$a['uploaded_by'] === $user['id'] || $post['is_mine'])) || isAdminOrManager($user);
        }
        unset($a);
        $post['attachments'] = $attachments;

        // 댓글 수
        $cmtCount = $pdo->prepare("SELECT COUNT(*) FROM board_comments WHERE post_id = ? AND status = 'active'");
        $cmtCount->execute([$id]);
        $post['comment_count'] = (int)$cmtCount->fetchColumn();

        respond(200, ['post' => $post]);
    } catch (PDOException $e) {
        error_log('[Board API] getPost error: ' . $e->getMessage());
        respond(500, ['error' => '서버 오류가 발생했습니다.']);
    }
}

// ========== 게시글 등록 ==========
function createPost(): void
{
    $data = getJsonInput();
    $user = getSessionUser();
    if ($user['id'] <= 0) { respond(401, ['error' => '로그인이 필요합니다.']); return; }

    $type       = trim($data['type'] ?? '');
    $category   = trim($data['category'] ?? '');
    $title      = trim($data['title'] ?? '');
    $content    = trim($data['content'] ?? '');
    $isPinned   = (int)($data['isPinned'] ?? 0);

    $validTypes = ['notice', 'free', 'archive', 'department'];
    if (!in_array($type, $validTypes, true)) {
        respond(400, ['error' => '게시판 유형이 올바르지 않습니다.']);
        return;
    }
    if ($title === '') {
        respond(400, ['error' => '제목을 입력해주세요.']);
        return;
    }
    if ($content === '') {
        respond(400, ['error' => '내용을 입력해주세요.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
        return;
    }

    // 작성자 정보를 세션에서 추출
    $authorId   = $user['id'] ?: null;
    $authorName = (string)($_SESSION['user']['name'] ?? '');
    $authorDept = '';
    if ($user['department_id'] > 0) {
        $dStmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $dStmt->execute([$user['department_id']]);
        $authorDept = (string)($dStmt->fetchColumn() ?: '');
    }

    if ($authorName === '') {
        respond(400, ['error' => '작성자 정보가 없습니다.']);
        return;
    }

    // 부서게시판: department_id 결정
    $departmentId = null;
    if ($type === 'department') {
        if ($user['department_id'] <= 0) {
            respond(400, ['error' => '소속 부서가 없어 부서게시판에 글을 작성할 수 없습니다.']);
            return;
        }
        $departmentId = $user['department_id'];
    }

    // 고정글(공지): admin/manager 또는 부서장만
    $canPin = ($type === 'department') ? canPostNotice($pdo, $user) : isAdminOrManager($user);
    if ($isPinned && !$canPin) {
        $isPinned = 0;
    }

    // 부서 공지글은 카테고리를 '공지'로 고정
    if ($type === 'department' && $isPinned) {
        $category = '공지';
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO board_posts (board_type, category, title, content, author_id, author_name, author_dept, department_id, is_pinned)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$type, $category, $title, $content, $authorId, $authorName, $authorDept ?: null, $departmentId, $isPinned]);
        $newId = (int)$pdo->lastInsertId();

        respond(200, ['success' => true, 'id' => $newId, 'message' => '게시글이 등록되었습니다.']);
    } catch (PDOException $e) {
        error_log('[Board API] createPost error: ' . $e->getMessage());
        respond(500, ['error' => '게시글 등록 중 오류가 발생했습니다.']);
    }
}

// ========== 게시글 수정 ==========
function updatePost(): void
{
    $data = getJsonInput();
    $user = getSessionUser();
    if ($user['id'] <= 0) { respond(401, ['error' => '로그인이 필요합니다.']); return; }

    $id       = (int)($data['id'] ?? 0);
    $title    = trim($data['title'] ?? '');
    $content  = trim($data['content'] ?? '');
    $isPinned = (int)($data['isPinned'] ?? 0);

    if (!$id) {
        respond(400, ['error' => '잘못된 요청입니다.']);
        return;
    }
    if ($title === '') {
        respond(400, ['error' => '제목을 입력해주세요.']);
        return;
    }
    if ($content === '') {
        respond(400, ['error' => '내용을 입력해주세요.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
        return;
    }

    try {
        // 본인 글 확인
        $check = $pdo->prepare("SELECT author_id, board_type FROM board_posts WHERE id = ? AND status = 'active'");
        $check->execute([$id]);
        $post = $check->fetch();

        if (!$post) {
            respond(404, ['error' => '게시글을 찾을 수 없습니다.']);
            return;
        }

        if ((int)$post['author_id'] !== $user['id'] && !isAdminOrManager($user)) {
            respond(403, ['error' => '본인 글만 수정할 수 있습니다.']);
            return;
        }

        $postType = $post['board_type'] ?? '';
        $canPin = ($postType === 'department') ? canPostNotice($pdo, $user) : isAdminOrManager($user);
        if ($isPinned && !$canPin) {
            $isPinned = 0;
        }

        $stmt = $pdo->prepare("
            UPDATE board_posts
            SET title = ?, content = ?, is_pinned = ?
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$title, $content, $isPinned, $id]);

        respond(200, ['success' => true, 'message' => '게시글이 수정되었습니다.']);
    } catch (PDOException $e) {
        error_log('[Board API] updatePost error: ' . $e->getMessage());
        respond(500, ['error' => '게시글 수정 중 오류가 발생했습니다.']);
    }
}

// ========== 게시글 삭제 (soft delete) ==========
function deletePost(): void
{
    $data = getJsonInput();
    $user = getSessionUser();
    if ($user['id'] <= 0) { respond(401, ['error' => '로그인이 필요합니다.']); return; }
    $id = (int)($data['id'] ?? 0);

    if (!$id) {
        respond(400, ['error' => '잘못된 요청입니다.']);
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        respond(503, ['error' => 'DB 연결에 실패했습니다. 잠시 후 다시 시도해주세요.']);
        return;
    }

    try {
        // 본인 글 확인
        $check = $pdo->prepare("SELECT author_id FROM board_posts WHERE id = ? AND status = 'active'");
        $check->execute([$id]);
        $post = $check->fetch();

        if (!$post) {
            respond(404, ['error' => '게시글을 찾을 수 없습니다.']);
            return;
        }

        if ((int)$post['author_id'] !== $user['id'] && !isAdminOrManager($user)) {
            respond(403, ['error' => '본인 글만 삭제할 수 있습니다.']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE board_posts SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);

        respond(200, ['success' => true, 'message' => '게시글이 삭제되었습니다.']);
    } catch (PDOException $e) {
        error_log('[Board API] deletePost error: ' . $e->getMessage());
        respond(500, ['error' => '게시글 삭제 중 오류가 발생했습니다.']);
    }
}

// ========== 댓글 목록 ==========
function getComments(): void
{
    $postId = (int)($_GET['post_id'] ?? 0);
    if ($postId <= 0) { apiError('BAD_INPUT', '게시글 ID가 필요합니다.'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    $user = getSessionUser();
    checkDeptAccess($pdo, $postId, $user);

    try {
        $stmt = $pdo->prepare(
            "SELECT id, post_id, author_id, author_name, author_dept, content, created_at
             FROM board_comments
             WHERE post_id = ? AND status = 'active'
             ORDER BY created_at ASC"
        );
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$c) {
            $c['is_mine'] = ($user['id'] > 0 && (int)$c['author_id'] === $user['id']);
            $c['can_delete'] = $c['is_mine'] || isAdminOrManager($user);
        }
        unset($c);

        apiOk(['comments' => $comments, 'count' => count($comments)]);
    } catch (PDOException $e) {
        error_log('[Board API] getComments error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '댓글을 불러오는 중 오류가 발생했습니다.', 500);
    }
}

// ========== 댓글 등록 ==========
function addComment(): void
{
    $user = getSessionUser();
    if ($user['id'] <= 0) { apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401); }

    $data    = getJsonInput();
    $postId  = (int)($data['post_id'] ?? 0);
    $content = trim($data['content'] ?? '');

    if ($postId <= 0) { apiError('BAD_INPUT', '게시글 ID가 필요합니다.'); }
    if ($content === '') { apiError('BAD_INPUT', '댓글 내용을 입력해주세요.'); }
    if (mb_strlen($content) > 2000) { apiError('BAD_INPUT', '댓글은 2000자까지 입력할 수 있습니다.'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    checkDeptAccess($pdo, $postId, $user);

    try {
        $check = $pdo->prepare("SELECT id FROM board_posts WHERE id = ? AND status = 'active'");
        $check->execute([$postId]);
        if (!$check->fetchColumn()) { apiError('NOT_FOUND', '게시글을 찾을 수 없습니다.', 404); }

        $authorName = (string)($_SESSION['user']['name'] ?? '');
        $authorDept = '';
        if ($user['department_id'] > 0) {
            $ds = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
            $ds->execute([$user['department_id']]);
            $authorDept = (string)($ds->fetchColumn() ?: '');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO board_comments (post_id, author_id, author_name, author_dept, content)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$postId, $user['id'], $authorName, $authorDept ?: null, $content]);
        $newId = (int)$pdo->lastInsertId();

        apiOk(['id' => $newId, 'message' => '댓글이 등록되었습니다.']);
    } catch (PDOException $e) {
        error_log('[Board API] addComment error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '댓글 등록 중 오류가 발생했습니다.', 500);
    }
}

// ========== 댓글 삭제 (소프트) ==========
function deleteComment(): void
{
    $user = getSessionUser();
    if ($user['id'] <= 0) { apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401); }

    $data = getJsonInput();
    $commentId = (int)($data['id'] ?? 0);
    if ($commentId <= 0) { apiError('BAD_INPUT', '댓글 ID가 필요합니다.'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    try {
        $check = $pdo->prepare("SELECT author_id FROM board_comments WHERE id = ? AND status = 'active'");
        $check->execute([$commentId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row) { apiError('NOT_FOUND', '댓글을 찾을 수 없습니다.', 404); }
        if ((int)$row['author_id'] !== $user['id'] && !isAdminOrManager($user)) {
            apiError('FORBIDDEN', '본인 댓글만 삭제할 수 있습니다.', 403);
        }

        $pdo->prepare("UPDATE board_comments SET status = 'deleted' WHERE id = ?")->execute([$commentId]);

        apiOk(['message' => '댓글이 삭제되었습니다.']);
    } catch (PDOException $e) {
        error_log('[Board API] deleteComment error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '댓글 삭제 중 오류가 발생했습니다.', 500);
    }
}

// ========== 첨부파일 업로드 ==========
function uploadAttachment(): void
{
    $user = getSessionUser();
    if ($user['id'] <= 0) { apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401); }

    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId <= 0) { apiError('BAD_INPUT', '게시글 ID가 필요합니다.'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    checkDeptAccess($pdo, $postId, $user);

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        apiError('BAD_INPUT', '파일이 첨부되지 않았거나 업로드 오류가 발생했습니다.');
    }

    $file = $_FILES['file'];
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        apiError('FILE_TOO_LARGE', '파일 크기는 10MB를 초과할 수 없습니다.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','hwp','hwpx','xls','xlsx','ppt','pptx','txt','zip','jpg','jpeg','png','gif'];
    if (!in_array($ext, $allowed, true)) {
        apiError('BAD_EXT', '허용되지 않는 파일 형식입니다. (' . implode(', ', $allowed) . ')');
    }

    $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';

    try {
        $check = $pdo->prepare("SELECT id FROM board_posts WHERE id = ? AND status = 'active'");
        $check->execute([$postId]);
        if (!$check->fetchColumn()) { apiError('NOT_FOUND', '게시글을 찾을 수 없습니다.', 404); }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM board_attachments WHERE post_id = ?");
        $countStmt->execute([$postId]);
        $maxFiles = 5;
        if ((int)$countStmt->fetchColumn() >= $maxFiles) {
            apiError('LIMIT_EXCEEDED', '첨부파일은 게시글당 최대 ' . $maxFiles . '개까지 가능합니다.');
        }

        $uploadDir = __DIR__ . '/../uploads/board/' . $postId;
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $origName = preg_replace('/[\\/\\\\:\\*\\?"<>\\|]/u', '', $file['name']);
        $safeBase = preg_replace('/[^a-zA-Z0-9가-힣._\-]/u', '_', pathinfo($origName, PATHINFO_FILENAME));
        $storedName = time() . '_' . $safeBase . '.' . $ext;
        $destPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            apiError('UPLOAD_FAILED', '파일 저장에 실패했습니다.', 500);
        }

        $realDest = realpath($destPath);
        $realBase = realpath(__DIR__ . '/../uploads');
        if ($realDest === false || $realBase === false || strpos($realDest, $realBase) !== 0) {
            @unlink($destPath);
            apiError('PATH_ERROR', '파일 경로 검증 실패', 500);
        }

        $relPath = 'uploads/board/' . $postId . '/' . $storedName;

        $stmt = $pdo->prepare(
            "INSERT INTO board_attachments (post_id, original_name, stored_name, file_path, file_size, mime_type, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$postId, $origName, $storedName, $relPath, $file['size'], $mime, $user['id']]);

        apiOk([
            'id'        => (int)$pdo->lastInsertId(),
            'file_name' => $origName,
            'file_size' => $file['size'],
            'message'   => '파일이 업로드되었습니다.',
        ]);
    } catch (PDOException $e) {
        error_log('[Board API] uploadAttachment error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '파일 업로드 중 오류가 발생했습니다.', 500);
    }
}

// ========== 첨부파일 삭제 ==========
function deleteAttachment(): void
{
    $user = getSessionUser();
    if ($user['id'] <= 0) { apiError('AUTH_REQUIRED', '로그인이 필요합니다.', 401); }

    $data = getJsonInput();
    $attachId = (int)($data['id'] ?? 0);
    if ($attachId <= 0) { apiError('BAD_INPUT', '첨부파일 ID가 필요합니다.'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    try {
        $stmt = $pdo->prepare("SELECT a.*, p.author_id AS post_author_id FROM board_attachments a JOIN board_posts p ON p.id = a.post_id WHERE a.id = ?");
        $stmt->execute([$attachId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) { apiError('NOT_FOUND', '첨부파일을 찾을 수 없습니다.', 404); }

        $isOwner = ($user['id'] > 0 && ((int)$row['uploaded_by'] === $user['id'] || (int)$row['post_author_id'] === $user['id']));
        if (!$isOwner && !isAdminOrManager($user)) {
            apiError('FORBIDDEN', '삭제 권한이 없습니다.', 403);
        }

        $filePath = __DIR__ . '/../' . $row['file_path'];
        if (file_exists($filePath)) { @unlink($filePath); }

        $pdo->prepare("DELETE FROM board_attachments WHERE id = ?")->execute([$attachId]);

        apiOk(['message' => '첨부파일이 삭제되었습니다.']);
    } catch (PDOException $e) {
        error_log('[Board API] deleteAttachment error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '첨부파일 삭제 중 오류가 발생했습니다.', 500);
    }
}

// ========== 첨부파일 다운로드 ==========
function downloadAttachment(): void
{
    $user = getSessionUser();
    $attachId = (int)($_GET['id'] ?? 0);
    if ($attachId <= 0) { apiError('BAD_INPUT', '첨부파일 ID가 필요합니다.'); }

    $pdo = getDBConnection();
    if (!$pdo) { apiError('DB_ERROR', 'DB 연결 실패', 503); }

    try {
        $stmt = $pdo->prepare(
            "SELECT a.*, p.board_type, p.department_id
             FROM board_attachments a
             JOIN board_posts p ON p.id = a.post_id
             WHERE a.id = ? AND p.status = 'active'"
        );
        $stmt->execute([$attachId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) { apiError('NOT_FOUND', '첨부파일을 찾을 수 없습니다.', 404); }

        if ($row['board_type'] === 'department'
            && !isAdminOrManager($user)
            && (int)$row['department_id'] !== $user['department_id']) {
            apiError('FORBIDDEN', '다른 부서의 첨부파일에 접근할 수 없습니다.', 403);
        }

        $filePath = __DIR__ . '/../' . $row['file_path'];
        if (!file_exists($filePath)) { apiError('NOT_FOUND', '파일이 존재하지 않습니다.', 404); }

        header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . rawurlencode($row['original_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        readfile($filePath);
        exit;
    } catch (PDOException $e) {
        error_log('[Board API] downloadAttachment error: ' . $e->getMessage());
        apiError('SERVER_ERROR', '파일 다운로드 중 오류가 발생했습니다.', 500);
    }
}
