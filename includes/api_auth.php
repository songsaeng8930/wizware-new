<?php
/**
 * API 인증 미들웨어
 * API 파일 상단에 require_once로 포함.
 *
 * 역할:
 *  1) 세션 시작 (auth.php에서 쿠키 보안 옵션 함께 적용)
 *  2) 로그인 여부 확인 · 미로그인 시 401 JSON 응답
 *  3) CSRF 토큰 검증 · 상태 변경 요청(POST/PUT/PATCH/DELETE)에만 적용
 */

require_once __DIR__ . '/auth.php';   // 세션 시작 + 쿠키 보안 설정
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/database.php';

if (!defined('AUTH_ENABLED') || AUTH_ENABLED !== false) {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// CSRF · 상태 변경 요청만 검증 (GET/HEAD/OPTIONS는 통과)
csrfVerify();
