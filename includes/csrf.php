<?php
/**
 * CSRF 토큰 발급/검증
 *
 * 정책:
 *  - 세션당 1개 토큰 (없으면 생성)
 *  - 상태 변경 요청(POST/PUT/DELETE/PATCH)에만 검증 적용
 *  - 헤더 `X-CSRF-Token` 또는 POST 바디 `_csrf` 로 전달
 *  - 비교는 hash_equals()로 타이밍 공격 방어
 *
 * 프론트엔드는 `<meta name="csrf-token">` 값을 읽어 fetch에 자동 주입된다
 * (header.php 하단의 monkey-patched fetch 참고).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** 세션의 CSRF 토큰 조회 (없으면 발급) */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * 현재 요청의 CSRF 토큰 검증.
 * GET/HEAD/OPTIONS 요청은 통과시킨다 (읽기만 하는 요청은 CSRF 대상 아님).
 * 검증 실패 시 403으로 즉시 응답 종료.
 */
function csrfVerify(): void
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }

    $expected = $_SESSION['_csrf_token'] ?? '';
    $received = $_SERVER['HTTP_X_CSRF_TOKEN']
             ?? $_POST['_csrf']
             ?? '';

    if ($received === '' && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        // JSON 바디에 _csrf가 들어있을 수도 있음 (파일 업로드가 아닌 경우)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && !empty($decoded['_csrf'])) {
                    $received = (string)$decoded['_csrf'];
                }
            }
        }
    }

    if ($expected === '' || !is_string($received) || !hash_equals($expected, $received)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'CSRF 토큰 검증 실패 · 페이지를 새로고침하고 다시 시도해주세요.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}
