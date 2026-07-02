<?php
/**
 * Zaemit 그룹웨어 - 데이터베이스 설정
 * PHP 8.4 + MySQL 8.4 LTS
 */

define('DB_HOST', getenv('DOCKER_ENV') ? 'db' : '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'zaemit_groupware');
define('DB_USER', 'root');
define('DB_PASS', getenv('DOCKER_ENV') ? 'root' : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO 데이터베이스 연결
 */
function getDBConnection(): ?PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            return null;
        }
    }

    return $pdo;
}

/**
 * 인증 설정
 * false로 변경하면 로그인 없이 접근 가능 (로컬 개발용)
 */
define('AUTH_ENABLED', true);
