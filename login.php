<?php
//require_once 'includes/config.php';
//require_once 'includes/db.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once  __DIR__ . '/classes/User.php';

//session_start();

// 如果用户已登录，重定向到主页
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

