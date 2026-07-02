<?php
/**
 * 금융결제원 오픈뱅킹 API 헬퍼
 *
 * - 이용기관 유형(법인 / 개인사업자) 양쪽 동시 지원
 *   · config/api_settings.json 의 openbanking_corp_*, openbanking_sole_* 두 세트로 키 보관
 *   · 한 유저가 법인 계좌 + 대표 개인사업자 계좌 모두 조회해야 하는 케이스 커버
 * - 기존 bankapi.co.kr 연동(config/bankapi.php)은 그대로 유지 — 데이터/키 보존
 *
 * 엔드포인트:
 *   실운영  : https://openapi.openbanking.or.kr
 *   테스트  : https://testapi.openbanking.or.kr
 *
 * 인증  : OAuth 2.0 Bearer Token
 * 절차  : authorize → user_code 발급 → access_token / user_seq_no 수령 → API 호출
 *
 * 사용 예:
 *   $res = openbanking_request('corp', 'GET', '/v2.0/account/list', [], [
 *       'user_seq_no' => '1100000001',
 *       'include_cancel_yn' => 'N',
 *       'sort_order' => 'D',
 *   ]);
 */

$_obSettings = [];
$_obSettingsFile = __DIR__ . '/api_settings.json';
if (file_exists($_obSettingsFile)) {
    $_obSettings = json_decode(file_get_contents($_obSettingsFile), true) ?? [];
}

// ─── 환경 선택 (test / prod) ───
$_obEnv = $_obSettings['openbanking_env'] ?? 'test';  // 기본값: 테스트베드
define('OPENBANKING_ENV', $_obEnv);
define('OPENBANKING_BASE_URL', $_obEnv === 'prod'
    ? 'https://openapi.openbanking.or.kr'
    : 'https://testapi.openbanking.or.kr');

// ─── 이용기관 자격 (법인 / 개인사업자 독립 저장) ───
// 동시 보유 허용 — 두 키 모두 입력돼 있으면 둘 다 사용 가능
define('OPENBANKING_CREDENTIALS', [
    'corp' => [
        'label'         => '법인',
        'client_id'     => $_obSettings['openbanking_corp_client_id']     ?? '',
        'client_secret' => $_obSettings['openbanking_corp_client_secret'] ?? '',
        'cntr_account'  => $_obSettings['openbanking_corp_cntr_account']  ?? '', // 정산계좌(옵션)
    ],
    'sole' => [
        'label'         => '개인사업자',
        'client_id'     => $_obSettings['openbanking_sole_client_id']     ?? '',
        'client_secret' => $_obSettings['openbanking_sole_client_secret'] ?? '',
        'cntr_account'  => $_obSettings['openbanking_sole_cntr_account']  ?? '',
    ],
]);

/**
 * 이용기관 유형(corp|sole)이 현재 설정되어 있는지 확인
 */
function openbanking_configured(string $entityType): bool {
    $creds = OPENBANKING_CREDENTIALS[$entityType] ?? null;
    return $creds && !empty($creds['client_id']) && !empty($creds['client_secret']);
}

/**
 * 설정된 유형 목록 반환 — ['corp', 'sole'] 또는 일부만
 */
function openbanking_active_entities(): array {
    $out = [];
    foreach (['corp', 'sole'] as $t) {
        if (openbanking_configured($t)) $out[] = $t;
    }
    return $out;
}

/**
 * OAuth 2.0 access_token 발급 (client_credentials)
 * — 이용기관 자체 인증용. 사용자 토큰과 별개.
 *
 * @return array ['ok'=>bool, 'access_token'=>?string, 'error'=>?string]
 */
function openbanking_issue_client_token(string $entityType, string $scope = 'oob'): array {
    $creds = OPENBANKING_CREDENTIALS[$entityType] ?? null;
    if (!$creds || empty($creds['client_id']) || empty($creds['client_secret'])) {
        return ['ok' => false, 'error' => "오픈뱅킹({$entityType}) 클라이언트 키가 설정되지 않았습니다."];
    }

    $url = OPENBANKING_BASE_URL . '/oauth/2.0/token';
    $body = http_build_query([
        'client_id'     => $creds['client_id'],
        'client_secret' => $creds['client_secret'],
        'scope'         => $scope,
        'grant_type'    => 'client_credentials',
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err];

    $decoded = json_decode($response, true) ?? [];
    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['access_token'])) {
        return ['ok' => true, 'access_token' => $decoded['access_token'], 'expires_in' => $decoded['expires_in'] ?? null];
    }
    return [
        'ok'    => false,
        'error' => $decoded['error_description'] ?? $decoded['rsp_message'] ?? ('HTTP ' . $httpCode),
        'raw'   => $decoded,
    ];
}

/**
 * 오픈뱅킹 일반 API 요청 — 사용자 access_token 기반
 *
 * @param string $entityType   corp|sole — 어느 자격증명으로 호출할지 (로깅/감사용)
 * @param string $method       GET|POST|DELETE
 * @param string $path         예: /v2.0/account/list
 * @param string $userToken    사용자 인증 후 DB에 저장해 둔 access_token
 * @param array  $body         POST 바디 (JSON)
 * @param array  $query        GET 쿼리
 * @return array ['ok'=>bool, 'status'=>int, 'data'=>mixed, 'entity'=>string]
 */
function openbanking_request(string $entityType, string $method, string $path, string $userToken, array $body = [], array $query = []): array {
    if (!openbanking_configured($entityType)) {
        return ['ok' => false, 'status' => 0, 'entity' => $entityType,
                'data' => ['error' => "오픈뱅킹({$entityType}) 키 미설정"]];
    }
    if ($userToken === '') {
        return ['ok' => false, 'status' => 0, 'entity' => $entityType,
                'data' => ['error' => '사용자 access_token 이 없습니다. 먼저 사용자 인증을 진행하세요.']];
    }

    $url = OPENBANKING_BASE_URL . $path;
    if ($query) $url .= '?' . http_build_query($query);

    $ch = curl_init();
    $headers = [
        'Authorization: Bearer ' . $userToken,
        'Content-Type: application/json; charset=UTF-8',
    ];
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $m = strtoupper($method);
    if ($m === 'POST')   { curl_setopt($ch, CURLOPT_POST, true);           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE)); }
    elseif ($m === 'DELETE') { curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE)); }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) return ['ok' => false, 'status' => 0, 'entity' => $entityType, 'data' => ['error' => $curlError]];

    $decoded = json_decode($response, true) ?? [];
    return [
        'ok'     => ($httpCode >= 200 && $httpCode < 300 && ($decoded['rsp_code'] ?? 'A0000') === 'A0000'),
        'status' => $httpCode,
        'entity' => $entityType,
        'data'   => $decoded,
    ];
}

/**
 * 사용자 인증 URL 생성 — 프론트에서 이 URL로 리다이렉트해 오픈뱅킹 동의 화면을 띄운다.
 */
function openbanking_authorize_url(string $entityType, string $redirectUri, string $state, string $scope = 'login inquiry transfer'): string {
    $creds = OPENBANKING_CREDENTIALS[$entityType] ?? null;
    if (!$creds || empty($creds['client_id'])) return '';

    $params = [
        'response_type' => 'code',
        'client_id'     => $creds['client_id'],
        'redirect_uri'  => $redirectUri,
        'scope'         => $scope,
        'state'         => $state,
        'auth_type'     => '0',
    ];
    return OPENBANKING_BASE_URL . '/oauth/2.0/authorize?' . http_build_query($params);
}

/**
 * 사용자 authorization_code → access_token 교환
 */
function openbanking_exchange_code(string $entityType, string $code, string $redirectUri): array {
    $creds = OPENBANKING_CREDENTIALS[$entityType] ?? null;
    if (!$creds) return ['ok' => false, 'error' => '등록되지 않은 이용기관 유형: ' . $entityType];

    $url = OPENBANKING_BASE_URL . '/oauth/2.0/token';
    $body = http_build_query([
        'code'          => $code,
        'client_id'     => $creds['client_id'],
        'client_secret' => $creds['client_secret'],
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err];
    $decoded = json_decode($response, true) ?? [];

    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['access_token'])) {
        return [
            'ok'             => true,
            'access_token'   => $decoded['access_token'],
            'refresh_token'  => $decoded['refresh_token']  ?? null,
            'token_type'     => $decoded['token_type']     ?? 'Bearer',
            'expires_in'     => $decoded['expires_in']     ?? null,
            'scope'          => $decoded['scope']          ?? null,
            'user_seq_no'    => $decoded['user_seq_no']    ?? null,
            'entity'         => $entityType,
        ];
    }
    return [
        'ok'    => false,
        'error' => $decoded['error_description'] ?? $decoded['rsp_message'] ?? ('HTTP ' . $httpCode),
        'raw'   => $decoded,
    ];
}
