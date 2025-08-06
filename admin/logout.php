<?php
// 启动会话
session_start();

// 记录登出日志（可选）
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['user_id'];
    $username = $_SESSION['user']['name'];
    $logMessage = "用户登出: ID $userId ($username)";
    error_log($logMessage);
}

// 清除所有会话变量
$_SESSION = array();

// 删除会话cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到登录页面
//header("Location: /ticket/admin/login.php");
header("Location: /ticket/index.php");
exit;
?>