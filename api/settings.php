<?php
/**
 * API 설정 관리 엔드포인트
 *
 * GET  ?action=load          설정 불러오기 (키값 마스킹)
 * POST ?action=save          설정 저장
 * POST ?action=test_bankapi  bankapi 연결 테스트
 * POST ?action=test_ai       AI 프로바이더 연결 테스트
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';

$settingsFile = __DIR__ . '/../config/api_settings.json';
$action = $_GET['action'] ?? '';

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

try {
    switch ($action) {

        // ─── 설정 불러오기 (마스킹) ───
        case 'load':
            $settings = loadSettings($settingsFile);
            $masked = $settings;

            // bankapi.co.kr (기존)
            foreach (['bankapi_key', 'bankapi_secret'] as $k) {
                if (!empty($masked[$k])) $masked[$k] = maskKey($masked[$k]);
            }
            $masked['has_bankapi_key']    = !empty($settings['bankapi_key']);
            $masked['has_bankapi_secret'] = !empty($settings['bankapi_secret']);

            // 금융결제원 오픈뱅킹 (신규) · 법인/개인사업자 각각
            foreach (['corp', 'sole'] as $t) {
                $idKey  = "openbanking_{$t}_client_id";
                $secKey = "openbanking_{$t}_client_secret";
                $cntKey = "openbanking_{$t}_cntr_account";
                if (!empty($masked[$idKey]))  $masked[$idKey]  = maskKey($masked[$idKey]);
                if (!empty($masked[$secKey])) $masked[$secKey] = maskKey($masked[$secKey]);
                $masked["has_openbanking_{$t}"] = !empty($settings[$idKey]) && !empty($settings[$secKey]);
                if (empty($masked[$cntKey])) $masked[$cntKey] = '';
            }
            $masked['openbanking_env'] = $settings['openbanking_env'] ?? 'test';
            // 은행 제공자 모드: auto(키 있으면 실연동·없으면 샌드박스) | real | mock
            $masked['bank_provider_mode'] = $settings['bank_provider_mode'] ?? 'auto';

            // AI 프로바이더 키 마스킹
            foreach (['openai_api_key', 'anthropic_api_key', 'google_ai_api_key', 'aws_access_key_id', 'aws_secret_access_key'] as $k) {
                if (!empty($masked[$k])) $masked[$k] = maskKey($masked[$k]);
            }
            $masked['has_openai']    = !empty($settings['openai_api_key']);
            $masked['has_anthropic'] = !empty($settings['anthropic_api_key']);
            $masked['has_google_ai'] = !empty($settings['google_ai_api_key']);
            $masked['has_bedrock']   = !empty($settings['aws_access_key_id']) && !empty($settings['aws_secret_access_key']);
            $masked['ai_provider']   = $settings['ai_provider'] ?? '';
            $masked['ai_model']      = $settings['ai_model'] ?? '';
            $masked['aws_region']    = $settings['aws_region'] ?? 'us-east-1';

            respond(200, 'OK', $masked);
            break;

        // ─── 설정 저장 (admin 전용) ───
        case 'save':
            $role = (string)($_SESSION['user']['role'] ?? '');
            if ($role !== 'admin') {
                respond(403, '관리자 권한이 필요합니다.');
            }
            $settings = loadSettings($settingsFile);

            // bankapi 키 업데이트 (기존 · 하위호환 유지)
            if (isset($input['bankapi_key']) && $input['bankapi_key'] !== '' && !str_contains($input['bankapi_key'], '****')) {
                $settings['bankapi_key'] = trim($input['bankapi_key']);
            }
            if (isset($input['bankapi_secret']) && $input['bankapi_secret'] !== '' && !str_contains($input['bankapi_secret'], '****')) {
                $settings['bankapi_secret'] = trim($input['bankapi_secret']);
            }

            // 오픈뱅킹 키 업데이트 · 법인/개인사업자 두 세트 독립 저장
            foreach (['corp', 'sole'] as $t) {
                foreach (['client_id', 'client_secret', 'cntr_account'] as $field) {
                    $key = "openbanking_{$t}_{$field}";
                    if (isset($input[$key]) && $input[$key] !== '' && !str_contains($input[$key], '****')) {
                        $settings[$key] = trim($input[$key]);
                    }
                }
            }
            // 환경(test|prod)
            if (isset($input['openbanking_env']) && in_array($input['openbanking_env'], ['test', 'prod'], true)) {
                $settings['openbanking_env'] = $input['openbanking_env'];
            }
            // 은행 제공자 모드 (화이트리스트)
            if (isset($input['bank_provider_mode']) && in_array($input['bank_provider_mode'], ['auto', 'real', 'mock'], true)) {
                $settings['bank_provider_mode'] = $input['bank_provider_mode'];
            }

            // AI 프로바이더 키 업데이트
            foreach (['openai_api_key', 'anthropic_api_key', 'google_ai_api_key', 'aws_access_key_id', 'aws_secret_access_key'] as $k) {
                if (isset($input[$k]) && $input[$k] !== '' && !str_contains($input[$k], '****')) {
                    $settings[$k] = trim($input[$k]);
                }
            }
            // AI 기본 프로바이더/모델/리전
            if (isset($input['ai_provider']) && in_array($input['ai_provider'], ['openai', 'anthropic', 'google', 'bedrock', ''], true)) {
                $settings['ai_provider'] = $input['ai_provider'];
            }
            if (isset($input['ai_model']) && is_string($input['ai_model'])) {
                $settings['ai_model'] = trim($input['ai_model']);
            }
            if (isset($input['aws_region']) && is_string($input['aws_region']) && $input['aws_region'] !== '') {
                $settings['aws_region'] = trim($input['aws_region']);
            }

            $settings['updated_at'] = date('Y-m-d H:i:s');

            if (!saveSettings($settingsFile, $settings)) {
                respond(500, '설정 파일 저장에 실패했습니다. config 폴더의 쓰기 권한을 확인하세요.');
            }

            respond(200, '설정이 저장되었습니다.');
            break;

        // ─── 오픈뱅킹 연결 테스트 (client_credentials 토큰 발급으로 검증) ───
        case 'test_openbanking':
            $entityType = $input['entity_type'] ?? $_GET['entity_type'] ?? '';
            if (!in_array($entityType, ['corp', 'sole'], true)) {
                respond(400, 'entity_type 은 corp 또는 sole 이어야 합니다.');
            }
            require_once __DIR__ . '/../config/openbanking.php';
            if (!openbanking_configured($entityType)) {
                respond(400, "오픈뱅킹({$entityType}) 클라이언트 키가 설정되지 않았습니다. 먼저 저장하세요.");
            }
            $res = openbanking_issue_client_token($entityType, 'oob');
            if ($res['ok']) {
                respond(200, '오픈뱅킹 연결 성공 · 이용기관 토큰 발급 확인', [
                    'entity_type' => $entityType,
                    'env'         => OPENBANKING_ENV,
                    'expires_in'  => $res['expires_in'] ?? null,
                ]);
            }
            respond(401, '오픈뱅킹 연결 실패: ' . ($res['error'] ?? '알 수 없음'), [
                'entity_type' => $entityType,
                'raw'         => $res['raw'] ?? null,
            ]);
            break;

        // ─── bankapi 연결 테스트 ───
        case 'test_bankapi':
            $settings = loadSettings($settingsFile);
            $apiKey = $settings['bankapi_key'] ?? '';
            $secret = $settings['bankapi_secret'] ?? '';

            if (!$apiKey || !$secret) {
                respond(400, 'API Key와 Secret Key가 설정되지 않았습니다. 먼저 키를 저장하세요.');
            }

            $url = 'https://api.bankapi.co.kr/v1/accounts';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey . ':' . $secret,
                ],
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                respond(500, '서버 연결 실패: ' . $curlError);
            }

            $decoded = json_decode($response, true) ?? [];

            if ($httpCode === 401) {
                respond(401, 'API 인증 실패 · API Key 또는 Secret Key가 올바르지 않습니다.', [
                    'http_code' => $httpCode,
                    'response' => $decoded,
                ]);
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                respond(200, 'bankapi.co.kr 연결 성공!', [
                    'http_code' => $httpCode,
                    'accounts' => is_array($decoded) ? count($decoded) : 0,
                ]);
            }

            respond($httpCode, 'API 응답 코드: ' . $httpCode, [
                'http_code' => $httpCode,
                'response' => $decoded,
            ]);
            break;

        // ─── AI 연결 테스트 ───
        case 'test_ai':
            $provider = $input['provider'] ?? $_GET['provider'] ?? '';
            $validProviders = ['openai', 'anthropic', 'google', 'bedrock'];
            if (!in_array($provider, $validProviders, true)) {
                respond(400, 'provider는 ' . implode(', ', $validProviders) . ' 중 하나여야 합니다.');
            }

            require_once __DIR__ . '/../includes/ai_client.php';

            if (!ai_configured($provider)) {
                respond(400, "{$provider} API 키가 설정되지 않았습니다. 먼저 키를 저장하세요.");
            }

            $result = ai_test_connection($provider);

            if ($result['ok']) {
                respond(200, "{$provider} 연결 성공!", [
                    'provider' => $provider,
                    'response' => mb_substr($result['content'], 0, 200),
                    'usage'    => $result['usage'],
                ]);
            }

            respond(401, "{$provider} 연결 실패: " . ($result['error'] ?? '알 수 없는 오류'), [
                'provider' => $provider,
            ]);
            break;

        default:
            respond(400, '알 수 없는 action: ' . htmlspecialchars($action));
    }
} catch (Throwable $e) {
    respond(500, '서버 오류: ' . $e->getMessage());
}

// ─── 헬퍼 함수 ───

function loadSettings(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveSettings(string $file, array $settings): bool {
    $dir = dirname($file);
    if (!is_dir($dir)) return false;
    $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($file, $json) !== false;
}

function maskKey(string $key): string {
    $len = mb_strlen($key);
    if ($len <= 8) return str_repeat('*', $len);
    return mb_substr($key, 0, 4) . str_repeat('*', $len - 8) . mb_substr($key, -4);
}

function respond(int $code, string $message, $data = null): never {
    http_response_code($code);
    $body = ['success' => ($code >= 200 && $code < 300), 'message' => $message];
    if ($data !== null) $body['data'] = $data;
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
