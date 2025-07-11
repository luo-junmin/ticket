<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '16~20Mgzttmp');
define('DB_NAME', 'ticket');
define('DB_CHARSET', 'utf8mb4');

/**
 * 获取PDO数据库连接
 */
function get_pdo_connection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('数据库连接失败: ' . $e->getMessage());
            throw new Exception('无法连接数据库');
        }
    }

    return $pdo;
}