<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once  __DIR__ . '/classes/User.php';


// 如果用户已登录，重定向到主页
if (isset($_SESSION['user_id'])) {

    // 取消会话变量
    unset($_SESSION['user_id']);
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    unset($_SESSION['logged_in']);

//    print_r($_SESSION);

    header("Location: index.php");
    exit;
}

