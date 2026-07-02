<?php
/**
 * AES-256-GCM 암호화/복호화 헬퍼
 * 계좌 비밀번호 등 복호화가 필요한 민감 데이터용
 */

define('ENCRYPTION_CIPHER', 'aes-256-gcm');
define('ENCRYPTION_TAG_LENGTH', 16);

function getEncryptionKey(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $envKey = getenv('APP_ENCRYPTION_KEY');
    if ($envKey !== false && $envKey !== '') {
        $cached = base64_decode($envKey);
        return $cached;
    }

    $settingsFile = __DIR__ . '/../config/api_settings.json';
    $settings = [];
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true) ?? [];
    }

    if (!empty($settings['encryption_key'])) {
        $cached = base64_decode($settings['encryption_key']);
        return $cached;
    }

    $key = random_bytes(32);
    $settings['encryption_key'] = base64_encode($key);

    $tmp = $settingsFile . '.tmp';
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($tmp, $json) === false) {
        throw new RuntimeException('Failed to write encryption key');
    }
    if (PHP_OS_FAMILY !== 'Windows') {
        chmod($tmp, 0600);
    }
    rename($tmp, $settingsFile);
    if (PHP_OS_FAMILY !== 'Windows') {
        chmod($settingsFile, 0600);
    }

    $cached = $key;
    return $cached;
}

function encryptValue(string $plaintext): string
{
    if ($plaintext === '') {
        return '';
    }

    $key = getEncryptionKey();
    $iv = random_bytes(openssl_cipher_iv_length(ENCRYPTION_CIPHER));
    $tag = '';

    $ciphertext = openssl_encrypt($plaintext, ENCRYPTION_CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', ENCRYPTION_TAG_LENGTH);

    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed');
    }

    return base64_encode($iv . $tag . $ciphertext);
}

function decryptValue(string $encoded): string
{
    if ($encoded === '') {
        return '';
    }

    $raw = base64_decode($encoded, true);
    if ($raw === false) {
        return $encoded;
    }

    $ivLen = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $minLen = $ivLen + ENCRYPTION_TAG_LENGTH + 1;
    if (strlen($raw) < $minLen) {
        return $encoded;
    }

    $key = getEncryptionKey();
    $iv = substr($raw, 0, $ivLen);
    $tag = substr($raw, $ivLen, ENCRYPTION_TAG_LENGTH);
    $ciphertext = substr($raw, $ivLen + ENCRYPTION_TAG_LENGTH);

    $plaintext = openssl_decrypt($ciphertext, ENCRYPTION_CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($plaintext === false) {
        return $encoded;
    }

    return $plaintext;
}
