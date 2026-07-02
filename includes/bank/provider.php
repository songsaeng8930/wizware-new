<?php
/**
 * 은행 제공자 어댑터(seam) · 실연동(bankapi.co.kr) ↔ 샌드박스(목) 전환 단일 지점.
 *
 * 모드 결정(config/api_settings.json 의 'bank_provider_mode'):
 *   - 'real' : 항상 실제 bankapi.co.kr (키 없으면 기존대로 '키 미설정' 에러)
 *   - 'mock' : 항상 샌드박스(목)
 *   - 'auto' (기본/미설정) : 키(BANKAPI_API_KEY/SECRET)가 있으면 real, 없으면 mock 으로 자동 폴백
 *
 * 호출부(api/bankapi.php, api/bank_sync.php)는 bank_provider_request() 만 쓰면
 * 모드와 무관하게 동일한 ['ok','status','data'] 형태를 돌려받는다.
 */

require_once __DIR__ . '/../../config/bankapi.php';   // bankapi_request(), BANKAPI_* 상수
require_once __DIR__ . '/mock_provider.php';

/** 현재 모드 'real' | 'mock' 으로 해석. */
function bank_provider_mode(): string
{
    static $resolved = null;
    if ($resolved !== null) return $resolved;

    $mode = 'auto';
    $file = __DIR__ . '/../../config/api_settings.json';
    if (is_readable($file)) {
        $json = json_decode((string)file_get_contents($file), true);
        if (is_array($json) && !empty($json['bank_provider_mode'])) {
            $m = strtolower((string)$json['bank_provider_mode']);
            if (in_array($m, ['auto', 'real', 'mock'], true)) $mode = $m;
        }
    }

    $hasKeys = defined('BANKAPI_API_KEY') && BANKAPI_API_KEY && defined('BANKAPI_SECRET') && BANKAPI_SECRET;
    if ($mode === 'real') return $resolved = 'real';
    if ($mode === 'mock') return $resolved = 'mock';
    return $resolved = ($hasKeys ? 'real' : 'mock'); // auto
}

/** 샌드박스(목) 동작 중인지 · UI 배너/응답에 표기용. */
function bank_provider_is_sandbox(): bool
{
    return bank_provider_mode() === 'mock';
}

/**
 * 제공자 요청 디스패치 · bankapi_request() 와 동일 시그니처/반환.
 */
function bank_provider_request(string $method, string $path, array $data = [], array $query = []): array
{
    if (bank_provider_mode() === 'mock') {
        return bank_mock_request($method, $path, $data, $query);
    }
    return bankapi_request($method, $path, $data, $query);
}
