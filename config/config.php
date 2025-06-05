<?php
require_once __DIR__ . '/database.php';

// Site configuration
define('SITE_NAME', 'TicketHub');
//define('SITE_URL', 'https://tickets.yourdomain.com');
define('SITE_URL', '/ticket');
define('ADMIN_EMAIL', 'admin@yourdomain.com');

// Path configuration
define('BASE_PATH', '/var/www/html/ticketing-system');
define('QR_CODE_PATH', BASE_PATH . '/assets/qrcodes/');
define('QR_CODE_URL', SITE_URL . '/assets/qrcodes/');

// Payment configuration
define('PAYNOW_MERCHANT_ID', 'your_merchant_id');
define('PAYNOW_API_KEY', 'your_api_key');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone
date_default_timezone_set('Asia/Singapore');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize language
//require_once BASE_PATH . '/classes/Language.php';
include_once $_SERVER['DOCUMENT_ROOT'] .  '/ticket/classes/Language.php';
$language = new Language();