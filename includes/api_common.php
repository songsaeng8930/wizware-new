<?php
/**
 * API 공통 헬퍼 · 신규 API 작성 시 이 파일만 require_once 해서 사용.
 *
 * 설계 원칙:
 *  - respond() / getJsonInput() / currentUser 류 함수는 반드시 이 파일을 거쳐 사용한다.
 *  - 기존 API들이 내부에 같은 이름의 함수를 로컬 정의해둔 것은 점진적으로 제거 대상.
 *    (한 번에 모두 교체하면 "리팩토링 폭탄"이 되므로 신규 코드부터 이 헬퍼를 쓰고,
 *     기존은 각 모듈 수정 시 함께 이관한다.)
 *  - 응답 스키마 가이드:
 *      성공: {"ok": true, "data": ...}
 *      실패: {"ok": false, "error": {"code": "STR", "message": "..."}}
 *    기존 `{error: "..."}` 계열은 `apiLegacyError()`로 임시 호환.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();

// ─── 응답 ───

/** 새 응답 규격: ok+data 성공 */
function apiOk(array $data = [], int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true] + ($data ? ['data' => $data] : []), JSON_UNESCAPED_UNICODE);
    exit;
}

/** 새 응답 규격: ok=false + error 코드/메시지 */
function apiError(string $code, string $message, int $status = 400, array $extra = []): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['ok' => false, 'error' => ['code' => $code, 'message' => $message] + $extra],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

/**
 * 레거시 호환 · 기존 API들이 쓰는 `{"error": "..."}` 형식.
 * 새 코드에선 apiError()를 쓰고, 이 함수는 기존 API에서만 사용한다.
 */
function apiLegacyError(string $message, int $status = 400): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── 입력 ───

/** JSON 바디 파싱 · 항상 array 반환 */
function apiJsonInput(): array
{
    static $cached = null;
    if ($cached !== null) return $cached;
    $raw = file_get_contents('php://input');
    $decoded = $raw !== '' && $raw !== false ? json_decode($raw, true) : null;
    $cached = is_array($decoded) ? $decoded : [];
    return $cached;
}

// ─── 세션/권한 ───

function apiSessionUser(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function apiSessionUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function apiSessionRole(): string
{
    return (string)($_SESSION['user']['role'] ?? '');
}

function apiIsAdmin(): bool
{
    return apiSessionRole() === 'admin';
}

function apiIsAdminOrManager(): bool
{
    return in_array(apiSessionRole(), ['admin', 'manager'], true);
}

/** 관리자 아니면 즉시 403 */
function apiRequireAdmin(): void
{
    if (!apiIsAdmin()) apiError('FORBIDDEN', '관리자 권한이 필요합니다.', 403);
}

/** admin/manager 아니면 즉시 403 */
function apiRequireAdminOrManager(): void
{
    if (!apiIsAdminOrManager()) apiError('FORBIDDEN', '권한이 없습니다.', 403);
}

// ─── 검증 유틸 ───

/** 허용 값 화이트리스트 검증 · 아니면 400 */
function apiRequireInList(string $fieldName, mixed $value, array $allowed): void
{
    if (!in_array($value, $allowed, true)) {
        apiError('BAD_INPUT', "{$fieldName} 값이 유효하지 않습니다.", 400, ['field' => $fieldName]);
    }
}

/** 양의 정수 요구 */
function apiRequirePositiveInt(string $fieldName, mixed $value): int
{
    $n = is_numeric($value) ? (int)$value : 0;
    if ($n <= 0) apiError('BAD_INPUT', "{$fieldName} 값이 필요합니다.", 400, ['field' => $fieldName]);
    return $n;
}
