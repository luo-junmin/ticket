<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
function getBaseUrl($option='host') {
    // 判断是否为 HTTPS
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';

    // 获取主机名
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];

    if ($option == 'host') {
        return $protocol . $host;
    } else {
        // 获取脚本路径（处理子目录情况）
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;

        return $protocol . $host . $basePath;
    }
}
$host = getBaseUrl();
// Site configuration
define('SITE_NAME', 'TicketHub');
//define('SITE_URL', 'http://localhost/ticket');
define('SITE_URL', $host.'/ticket');
define('SITE_EMAIL', 'tickethub.luo@gmail.com');
define('ADMIN_EMAIL', 'admin@yourdomain.com');

// Path configuration
define('BASE_PATH', '/var/www/html/ticketing-system');
define('QR_CODE_PATH', BASE_PATH . '/assets/qrcodes/');
define('QR_CODE_URL', SITE_URL . '/assets/qrcodes/');

define('PUBLIC_PATH', '/var/www/smilesrus/ticket-public');
define('UPLOADS_PATH', '/var/www/smilesrus/ticket-uploads');

// Payment configuration
define('PAYNOW_MERCHANT_ID', 'your_merchant_id');
define('PAYNOW_API_KEY', 'your_api_key');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone
date_default_timezone_set('Asia/Singapore');

// Initialize language
//require_once BASE_PATH . '/classes/Language.php';
include_once $_SERVER['DOCUMENT_ROOT'] .  '/ticket/classes/Language.php';
$language = new Language();