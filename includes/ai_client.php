<?php
/**
 * 통합 AI 클라이언트 · OpenAI / Anthropic / Google Gemini / AWS Bedrock
 *
 * 사용법:
 *   require_once __DIR__ . '/ai_client.php';
 *   $result = ai_request('openai', 'gpt-4o-mini', $system, $user);
 *   if ($result['ok']) echo $result['content'];
 */

// ─── 프로바이더 메타데이터 ───

const AI_PROVIDERS = [
    'openai' => [
        'name'          => 'OpenAI',
        'key_fields'    => ['openai_api_key'],
        'default_model' => 'gpt-4o-mini',
    ],
    'anthropic' => [
        'name'          => 'Anthropic',
        'key_fields'    => ['anthropic_api_key'],
        'default_model' => 'claude-sonnet-4-20250514',
    ],
    'google' => [
        'name'          => 'Google Gemini',
        'key_fields'    => ['google_ai_api_key'],
        'default_model' => 'gemini-2.0-flash',
    ],
    'bedrock' => [
        'name'          => 'AWS Bedrock',
        'key_fields'    => ['aws_access_key_id', 'aws_secret_access_key'],
        'default_model' => 'us.anthropic.claude-sonnet-4-20250514-v1:0',
    ],
];

const AI_MODELS = [
    'openai' => [
        'gpt-4.1'       => ['name' => 'GPT-4.1',       'max_tokens' => 32768],
        'gpt-4.1-mini'  => ['name' => 'GPT-4.1 Mini',  'max_tokens' => 32768],
        'gpt-4.1-nano'  => ['name' => 'GPT-4.1 Nano',  'max_tokens' => 32768],
        'gpt-4o'        => ['name' => 'GPT-4o',        'max_tokens' => 16384],
        'gpt-4o-mini'   => ['name' => 'GPT-4o Mini',   'max_tokens' => 16384],
        'o3-mini'       => ['name' => 'o3-mini',        'max_tokens' => 16384],
    ],
    'anthropic' => [
        'claude-opus-4-20250514'    => ['name' => 'Claude Opus 4',    'max_tokens' => 8192],
        'claude-sonnet-4-20250514'  => ['name' => 'Claude Sonnet 4',  'max_tokens' => 8192],
        'claude-haiku-3-5-20241022' => ['name' => 'Claude 3.5 Haiku', 'max_tokens' => 8192],
    ],
    'google' => [
        'gemini-2.5-pro'    => ['name' => 'Gemini 2.5 Pro',    'max_tokens' => 8192],
        'gemini-2.5-flash'  => ['name' => 'Gemini 2.5 Flash',  'max_tokens' => 8192],
        'gemini-2.0-flash'  => ['name' => 'Gemini 2.0 Flash',  'max_tokens' => 8192],
    ],
    'bedrock' => [
        'us.anthropic.claude-opus-4-20250514-v1:0'    => ['name' => 'Claude Opus 4 (Bedrock)',    'max_tokens' => 8192],
        'us.anthropic.claude-sonnet-4-20250514-v1:0'  => ['name' => 'Claude Sonnet 4 (Bedrock)',  'max_tokens' => 8192],
        'us.anthropic.claude-haiku-3-5-20241022-v1:0' => ['name' => 'Claude 3.5 Haiku (Bedrock)', 'max_tokens' => 8192],
    ],
];

const AI_CURL_TIMEOUT = 120;

// ─── 설정 로드 ───

function ai_load_config(): array
{
    static $config = null;
    if ($config !== null) return $config;

    $file = __DIR__ . '/../config/api_settings.json';
    if (!file_exists($file)) return $config = [];

    $config = json_decode(file_get_contents($file), true) ?? [];
    return $config;
}

function ai_configured(?string $provider = null): bool
{
    $config = ai_load_config();
    if ($provider === null) {
        $provider = $config['ai_provider'] ?? '';
    }
    if (!isset(AI_PROVIDERS[$provider])) return false;

    foreach (AI_PROVIDERS[$provider]['key_fields'] as $field) {
        if (empty($config[$field])) return false;
    }
    return true;
}

function ai_get_default_provider(): string
{
    $config = ai_load_config();
    return $config['ai_provider'] ?? '';
}

function ai_get_default_model(): string
{
    $config = ai_load_config();
    return $config['ai_model'] ?? '';
}

function ai_get_models(?string $provider = null): array
{
    if ($provider !== null) {
        return AI_MODELS[$provider] ?? [];
    }
    return AI_MODELS;
}

// ─── 통합 요청 인터페이스 ───

/**
 * @param string $provider  openai|anthropic|google|bedrock
 * @param string $model     모델 ID (빈 문자열이면 프로바이더 기본값)
 * @param string $systemPrompt  시스템 프롬프트
 * @param string $userMessage   사용자 메시지
 * @param array  $options   max_tokens, temperature, json_mode(bool)
 * @return array ['ok'=>bool, 'content'=>string, 'usage'=>array, 'error'=>?string]
 */
function ai_request(string $provider, string $model, string $systemPrompt, string $userMessage, array $options = []): array
{
    if (!isset(AI_PROVIDERS[$provider])) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => "알 수 없는 프로바이더: {$provider}"];
    }

    if (!ai_configured($provider)) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => "{$provider} API 키가 설정되지 않았습니다."];
    }

    if ($model === '') {
        $model = AI_PROVIDERS[$provider]['default_model'];
    }

    $maxTokens   = $options['max_tokens'] ?? 4096;
    $temperature = $options['temperature'] ?? 0.1;
    $jsonMode    = $options['json_mode'] ?? false;

    return match ($provider) {
        'openai'    => _ai_openai_request($model, $systemPrompt, $userMessage, $maxTokens, $temperature, $jsonMode),
        'anthropic' => _ai_anthropic_request($model, $systemPrompt, $userMessage, $maxTokens, $temperature),
        'google'    => _ai_google_request($model, $systemPrompt, $userMessage, $maxTokens, $temperature, $jsonMode),
        'bedrock'   => _ai_bedrock_request($model, $systemPrompt, $userMessage, $maxTokens, $temperature),
        default     => ['ok' => false, 'content' => '', 'usage' => [], 'error' => "미지원 프로바이더: {$provider}"],
    };
}

// ─── OpenAI ───

function _ai_openai_request(string $model, string $system, string $user, int $maxTokens, float $temperature, bool $jsonMode): array
{
    $config = ai_load_config();
    $apiKey = $config['openai_api_key'];

    $body = [
        'model'       => $model,
        'messages'    => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ],
        'max_tokens'  => $maxTokens,
        'temperature' => $temperature,
    ];

    if ($jsonMode) {
        $body['response_format'] = ['type' => 'json_object'];
    }

    $res = _ai_curl_post(
        'https://api.openai.com/v1/chat/completions',
        ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
        json_encode($body, JSON_UNESCAPED_UNICODE)
    );

    if (!$res['ok']) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $res['error'] ?? "HTTP {$res['status']}"];
    }

    $data = json_decode($res['body'], true) ?? [];

    if (isset($data['error'])) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $data['error']['message'] ?? 'OpenAI 오류'];
    }

    return [
        'ok'      => true,
        'content' => $data['choices'][0]['message']['content'] ?? '',
        'usage'   => $data['usage'] ?? [],
        'error'   => null,
    ];
}

// ─── Anthropic ───

function _ai_anthropic_request(string $model, string $system, string $user, int $maxTokens, float $temperature): array
{
    $config = ai_load_config();
    $apiKey = $config['anthropic_api_key'];

    $body = [
        'model'      => $model,
        'max_tokens' => $maxTokens,
        'system'     => $system,
        'messages'   => [
            ['role' => 'user', 'content' => $user],
        ],
    ];

    if ($temperature > 0) {
        $body['temperature'] = $temperature;
    }

    $res = _ai_curl_post(
        'https://api.anthropic.com/v1/messages',
        [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ],
        json_encode($body, JSON_UNESCAPED_UNICODE)
    );

    if (!$res['ok']) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $res['error'] ?? "HTTP {$res['status']}"];
    }

    $data = json_decode($res['body'], true) ?? [];

    if (isset($data['error'])) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $data['error']['message'] ?? 'Anthropic 오류'];
    }

    $content = '';
    foreach (($data['content'] ?? []) as $block) {
        if ($block['type'] === 'text') {
            $content .= $block['text'];
        }
    }

    return [
        'ok'      => true,
        'content' => $content,
        'usage'   => [
            'prompt_tokens'     => $data['usage']['input_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
            'total_tokens'      => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
        ],
        'error' => null,
    ];
}

// ─── Google Gemini ───

function _ai_google_request(string $model, string $system, string $user, int $maxTokens, float $temperature, bool $jsonMode): array
{
    $config = ai_load_config();
    $apiKey = $config['google_ai_api_key'];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $body = [
        'systemInstruction' => [
            'parts' => [['text' => $system]],
        ],
        'contents' => [
            ['parts' => [['text' => $user]]],
        ],
        'generationConfig' => [
            'maxOutputTokens' => $maxTokens,
            'temperature'     => $temperature,
        ],
    ];

    if ($jsonMode) {
        $body['generationConfig']['responseMimeType'] = 'application/json';
    }

    $res = _ai_curl_post(
        $url,
        ['Content-Type: application/json'],
        json_encode($body, JSON_UNESCAPED_UNICODE)
    );

    if (!$res['ok']) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $res['error'] ?? "HTTP {$res['status']}"];
    }

    $data = json_decode($res['body'], true) ?? [];

    if (isset($data['error'])) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $data['error']['message'] ?? 'Gemini 오류'];
    }

    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

    $usage = $data['usageMetadata'] ?? [];
    return [
        'ok'      => true,
        'content' => $content,
        'usage'   => [
            'prompt_tokens'     => $usage['promptTokenCount'] ?? 0,
            'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
            'total_tokens'      => $usage['totalTokenCount'] ?? 0,
        ],
        'error' => null,
    ];
}

// ─── AWS Bedrock (Anthropic 모델) ───

function _ai_bedrock_request(string $model, string $system, string $user, int $maxTokens, float $temperature): array
{
    $config = ai_load_config();
    $accessKey = $config['aws_access_key_id'];
    $secretKey = $config['aws_secret_access_key'];
    $region    = $config['aws_region'] ?? 'us-east-1';

    $body = json_encode([
        'anthropic_version' => 'bedrock-2023-05-31',
        'max_tokens'        => $maxTokens,
        'system'            => $system,
        'messages'          => [
            ['role' => 'user', 'content' => $user],
        ],
        'temperature' => $temperature,
    ], JSON_UNESCAPED_UNICODE);

    $host    = "bedrock-runtime.{$region}.amazonaws.com";
    $path    = '/model/' . rawurlencode($model) . '/invoke';
    $url     = "https://{$host}{$path}";
    $headers = _ai_aws_sigv4_sign('POST', $host, $path, $body, $accessKey, $secretKey, $region, 'bedrock');

    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Accept: application/json';

    $res = _ai_curl_post($url, $headers, $body);

    if (!$res['ok']) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $res['error'] ?? "HTTP {$res['status']}"];
    }

    $data = json_decode($res['body'], true) ?? [];

    if (isset($data['message']) && !isset($data['content'])) {
        return ['ok' => false, 'content' => '', 'usage' => [], 'error' => $data['message'] ?? 'Bedrock 오류'];
    }

    $content = '';
    foreach (($data['content'] ?? []) as $block) {
        if ($block['type'] === 'text') {
            $content .= $block['text'];
        }
    }

    return [
        'ok'      => true,
        'content' => $content,
        'usage'   => [
            'prompt_tokens'     => $data['usage']['input_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
            'total_tokens'      => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
        ],
        'error' => null,
    ];
}

// ─── AWS Signature V4 ───

function _ai_aws_sigv4_sign(string $method, string $host, string $path, string $body, string $accessKey, string $secretKey, string $region, string $service): array
{
    $now      = gmdate('Ymd\THis\Z');
    $dateOnly = gmdate('Ymd');
    $bodyHash = hash('sha256', $body);

    $canonicalHeaders = "host:{$host}\nx-amz-date:{$now}\n";
    $signedHeaders    = 'host;x-amz-date';

    $canonicalRequest = implode("\n", [
        $method,
        $path,
        '',
        $canonicalHeaders,
        $signedHeaders,
        $bodyHash,
    ]);

    $scope = "{$dateOnly}/{$region}/{$service}/aws4_request";
    $stringToSign = implode("\n", [
        'AWS4-HMAC-SHA256',
        $now,
        $scope,
        hash('sha256', $canonicalRequest),
    ]);

    $kDate    = hash_hmac('sha256', $dateOnly,   'AWS4' . $secretKey, true);
    $kRegion  = hash_hmac('sha256', $region,     $kDate, true);
    $kService = hash_hmac('sha256', $service,    $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $auth = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

    return [
        "Authorization: {$auth}",
        "x-amz-date: {$now}",
        "x-amz-content-sha256: {$bodyHash}",
        "Host: {$host}",
    ];
}

// ─── 공통 cURL ───

function _ai_curl_post(string $url, array $headers, string $body): array
{
    $caBundle = __DIR__ . '/../config/cacert.pem';

    $ch = curl_init();
    $curlOpts = [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => AI_CURL_TIMEOUT,
        CURLOPT_HTTPHEADER     => $headers,
    ];

    if (file_exists($caBundle)) {
        $curlOpts[CURLOPT_CAINFO] = $caBundle;
    }

    curl_setopt_array($ch, $curlOpts);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'error' => '연결 오류: ' . $curlError];
    }

    return [
        'ok'     => ($httpCode >= 200 && $httpCode < 300),
        'status' => $httpCode,
        'body'   => $response,
        'error'  => ($httpCode >= 400) ? "HTTP {$httpCode}: " . mb_substr($response, 0, 200) : null,
    ];
}

// ─── JSON 응답 파싱 헬퍼 ───

/**
 * AI 응답에서 JSON 배열을 추출한다.
 * LLM이 ```json ... ``` 래핑하거나 설명 텍스트를 붙이는 경우에도 안전하게 파싱.
 */
function ai_parse_json_array(string $content): ?array
{
    $content = trim($content);

    $decoded = json_decode($content, true);
    if (is_array($decoded)) return $decoded;

    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $m)) {
        $decoded = json_decode(trim($m[1]), true);
        if (is_array($decoded)) return $decoded;
    }

    if (preg_match('/\[[\s\S]*\]/', $content, $m)) {
        $decoded = json_decode($m[0], true);
        if (is_array($decoded)) return $decoded;
    }

    if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
        $decoded = json_decode($m[0], true);
        if (is_array($decoded)) return $decoded;
    }

    return null;
}

/**
 * AI 연결 테스트 · 간단한 요청을 보내 응답을 확인한다.
 */
function ai_test_connection(string $provider): array
{
    return ai_request(
        $provider,
        '',
        '당신은 도우미입니다.',
        '안녕하세요. 이것은 연결 테스트입니다. "연결 성공"이라고만 답해주세요.',
        ['max_tokens' => 50, 'temperature' => 0]
    );
}
