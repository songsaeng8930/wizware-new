<?php
/**
 * 직원 이력서 API
 *   GET  ?action=list&employee_id=X       → 목록
 *   POST ?action=upload (multipart)       → 업로드 (employee_id, file)
 *   GET  ?action=download&id=X            → 다운로드 (스트리밍)
 *   POST ?action=delete                   → 삭제 (id)
 *
 * 권한: admin/manager는 전체, 일반 사용자는 본인(=$_SESSION['user_id']) 소유만.
 * 응답 규격: api_common.php 의 apiOk/apiError ({ok,data,error})
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';

$pdo = getDBConnection();
if (!$pdo) apiError('DB_DOWN', 'DB 연결 실패', 500);

$action = $_GET['action'] ?? '';

// ── 업로드/조회 대상 직원에 대한 권한 체크 ──
function canAccessResume(int $targetEmpId): bool
{
    if ($targetEmpId <= 0) return false;
    $role = apiSessionRole();
    if (in_array($role, ['admin', 'manager'], true)) return true;
    return apiSessionUserId() === $targetEmpId;
}

// ── 다운로드는 위 JSON 래퍼가 아닌 스트리밍이라 별도 처리 ──
if ($action === 'download') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); exit('bad id'); }

    $stmt = $pdo->prepare("SELECT employee_id, file_name, file_path, mime_type FROM employee_resumes WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { http_response_code(404); exit('not found'); }

    if (!canAccessResume((int)$row['employee_id'])) { http_response_code(403); exit('forbidden'); }

    $full = realpath(__DIR__ . '/../' . $row['file_path']);
    $base = realpath(__DIR__ . '/../uploads/resumes');
    if (!$full || !$base || strpos($full, $base) !== 0 || !is_file($full)) {
        http_response_code(404); exit('file missing');
    }

    // 모든 버퍼 정리 후 바이너리 스트리밍
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($full));
    $encoded = rawurlencode($row['file_name']);
    header("Content-Disposition: attachment; filename*=UTF-8''{$encoded}");
    readfile($full);
    exit;
}

try {
    switch ($action) {

        // ─── 목록 ───
        case 'list': {
            $empId = (int)($_GET['employee_id'] ?? 0);
            if ($empId <= 0) apiError('BAD_INPUT', 'employee_id 필요', 400);
            if (!canAccessResume($empId)) apiError('FORBIDDEN', '권한이 없습니다.', 403);

            $stmt = $pdo->prepare("
                SELECT r.id, r.file_name, r.file_size, r.mime_type, r.uploaded_at,
                       r.uploaded_by, u.name AS uploader_name
                  FROM employee_resumes r
             LEFT JOIN employees u ON u.id = r.uploaded_by
                 WHERE r.employee_id = ?
              ORDER BY r.uploaded_at DESC
            ");
            $stmt->execute([$empId]);
            apiOk(['resumes' => $stmt->fetchAll()]);
        }

        // ─── 업로드 ───
        case 'upload': {
            $empId = (int)($_POST['employee_id'] ?? 0);
            if ($empId <= 0) apiError('BAD_INPUT', 'employee_id 필요', 400);
            if (!canAccessResume($empId)) apiError('FORBIDDEN', '권한이 없습니다.', 403);

            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                apiError('BAD_INPUT', '파일이 첨부되지 않았거나 업로드 오류', 400);
            }
            $file = $_FILES['file'];

            // 용량 10MB 제한
            if ($file['size'] > 10 * 1024 * 1024) {
                apiError('FILE_TOO_LARGE', '10MB를 초과할 수 없습니다.', 400);
            }

            // 확장자 화이트리스트
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'hwp', 'hwpx', 'txt'];
            if (!in_array($ext, $allowed, true)) {
                apiError('BAD_EXT', '허용되지 않는 확장자입니다. (' . implode(', ', $allowed) . ')', 400);
            }

            // 실제 MIME 검사 (서버 측)
            $mime = function_exists('mime_content_type') ? (mime_content_type($file['tmp_name']) ?: 'application/octet-stream') : 'application/octet-stream';

            // 저장 경로: uploads/resumes/{empId}/
            $dir = __DIR__ . '/../uploads/resumes/' . $empId;
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                apiError('IO_ERROR', '업로드 디렉토리 생성 실패', 500);
            }

            // 한글 보존하면서 위험 문자 제거
            $origName = preg_replace('/[\\/\\\\:\\*\\?"<>\\|]/u', '', $file['name']);
            $safeBase = preg_replace('/[^a-zA-Z0-9가-힣._\\-]/u', '_', pathinfo($origName, PATHINFO_FILENAME));
            $stored = time() . '_' . $safeBase . '.' . $ext;
            $dest = $dir . '/' . $stored;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                apiError('IO_ERROR', '파일 저장 실패', 500);
            }

            $relPath = 'uploads/resumes/' . $empId . '/' . $stored;
            $stmt = $pdo->prepare("
                INSERT INTO employee_resumes (employee_id, file_name, file_path, file_size, mime_type, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$empId, $origName, $relPath, (int)$file['size'], $mime, apiSessionUserId()]);

            apiOk([
                'id'         => (int)$pdo->lastInsertId(),
                'file_name'  => $origName,
                'file_size'  => (int)$file['size'],
                'uploaded_at'=> date('Y-m-d H:i:s'),
            ]);
        }

        // ─── 삭제 ───
        case 'delete': {
            $in = apiJsonInput();
            $id = (int)($in['id'] ?? 0);
            if ($id <= 0) apiError('BAD_INPUT', 'id 필요', 400);

            $stmt = $pdo->prepare("SELECT employee_id, file_path FROM employee_resumes WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) apiError('NOT_FOUND', '이력서를 찾을 수 없습니다.', 404);

            if (!canAccessResume((int)$row['employee_id'])) apiError('FORBIDDEN', '권한이 없습니다.', 403);

            $pdo->prepare("DELETE FROM employee_resumes WHERE id = ?")->execute([$id]);

            // 파일 실제 삭제 (경로 안전성 확인)
            $full = realpath(__DIR__ . '/../' . $row['file_path']);
            $base = realpath(__DIR__ . '/../uploads/resumes');
            if ($full && $base && strpos($full, $base) === 0 && is_file($full)) {
                @unlink($full);
            }
            apiOk();
        }

        default:
            apiError('UNKNOWN_ACTION', "알 수 없는 action: " . htmlspecialchars($action), 400);
    }
} catch (PDOException $e) {
    error_log('[employee_resume] PDO: ' . $e->getMessage());
    apiError('DB_ERROR', 'DB 오류: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    error_log('[employee_resume] ' . $e->getMessage());
    apiError('SERVER_ERROR', $e->getMessage(), 500);
}
