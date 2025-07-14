<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/User.php';

/**
 * 后台管理员认证中间件
 * 验证用户是否登录且具有管理员权限
 */

// 启动会话（如果尚未启动）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 定义管理员角色常量
define('ADMIN_ROLE', 'admin');

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
    header("Location: /login.php?redirect=/admin/");
    exit;
}

// 获取当前用户信息
$user = (new User())->getUserById($_SESSION['user_id']);

// 检查用户是否存在
if (!$user) {
    session_destroy();
    header("Location: /login.php?error=invalid_user");
    exit;
}

// 检查用户角色（假设users表有role字段）
if ($user['role'] !== ADMIN_ROLE) {
    header("HTTP/1.1 403 Forbidden");
    die("
        <div style='text-align:center; padding:50px;'>
            <h1>403 Access Denied</h1>
            <p>You don't have permission to access this page.</p>
            <a href='/'>Return to Homepage</a>
        </div>
    ");
}

// 检查最后活动时间（可选的安全增强）
$inactive = 36000; // 30分钟无操作超时
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: /login.php?error=session_timeout");
    exit;
}
$_SESSION['last_activity'] = time();

// 记录管理员操作日志（可选）
function logAdminAction($action) {
    $log = sprintf(
        "[%s] Admin %s (ID: %d) - %s%s",
        date('Y-m-d H:i:s'),
        $_SESSION['email'],
        $_SESSION['user_id'],
        $action,
        PHP_EOL
    );
    file_put_contents(__DIR__ . '/../logs/admin.log', $log, FILE_APPEND);
}

// 如果是敏感操作（如删除），可以添加CSRF保护
function verifyCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            header("HTTP/1.1 403 Forbidden");
            die("Invalid CSRF token");
        }
    }
}

// 生成CSRF令牌（在表单中使用）
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

//-----
/**
 * 检查当前用户是否是管理员
 */
function is_admin() {
    // 检查用户是否已登录且具有管理员权限
    // 根据你的用户系统调整这部分逻辑
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * 验证CSRF令牌
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

// 安全头设置
//header("X-Frame-Options: DENY");
//header("X-Content-Type-Options: nosniff");
//header("X-XSS-Protection: 1; mode=block");
//header("Referrer-Policy: strict-origin-when-cross-origin");
//header("Content-Security-Policy: default-src 'self'");