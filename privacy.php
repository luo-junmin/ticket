<?php
// 在所有PHP文件顶部确保首先启动session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';

include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
