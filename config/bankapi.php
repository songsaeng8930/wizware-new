<?php
/**
 * Bank API (bankapi.co.kr) 연동 설정
 *
 * 설정값은 config/api_settings.json 에서 관리됩니다.
 * 관리 화면: 그룹웨어 관리 > API 설정
 */

// ─── JSON 설정 파일에서 로드 ───
$_bankapiSettings = [];
$_settingsFile = __DIR__ . '/api_settings.json';
if (file_exists($_settingsFile)) {
    $_bankapiSettings = json_decode(file_get_contents($_settingsFile), true) ?? [];
}

// ─── API 인증 ───
define('BANKAPI_BASE_URL', 'https://api.bankapi.co.kr');
define('BANKAPI_API_KEY',  $_bankapiSettings['bankapi_key'] ?? '');
define('BANKAPI_SECRET',   $_bankapiSettings['bankapi_secret'] ?? '');

// ─── 지원 은행 코드 ───
// bankapi.co.kr 공식 안내 기준: 농협, 국민, 우리은행만 지원.
// 국민은행부터 연동 테스트할 수 있도록 KB를 첫 번째로 노출한다.
define('BANKAPI_BANKS', [
    'KB'  => '국민은행',
    'NH'  => '농협은행',
    'WR'  => '우리은행',
]);

function bankapi_supported_bank_codes(): string
{
    return implode(', ', array_keys(BANKAPI_BANKS));
}

/**
 * Bank API HTTP 요청 헬퍼
 *
 * @param string $method  GET|POST|DELETE
 * @param string $path    /v1/accounts 등
 * @param array  $data    요청 바디 (POST/DELETE)
 * @param array  $query   쿼리 파라미터 (GET)
 * @return array ['ok' => bool, 'status' => int, 'data' => mixed]
 */
function bankapi_request(string $method, string $path, array $data = [], array $query = []): array {
    if (!BANKAPI_API_KEY || !BANKAPI_SECRET) {
        return ['ok' => false, 'status' => 0, 'data' => ['error' => 'API Key가 설정되지 않았습니다. 그룹웨어 관리 > API 설정에서 키를 등록하세요.']];
    }

    $url = BANKAPI_BASE_URL . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $caBundle = __DIR__ . '/cacert.pem';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . BANKAPI_API_KEY . ':' . BANKAPI_SECRET,
        ],
        CURLOPT_CAINFO         => $caBundle,
    ]);

    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'GET':
        default:
            break;
    }

    $response   = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'status' => 0, 'data' => ['error' => $curlError]];
    }

    $decoded = json_decode($response, true) ?? [];
    return [
        'ok'     => ($httpCode >= 200 && $httpCode < 300),
        'status' => $httpCode,
        'data'   => $decoded,
    ];
}
