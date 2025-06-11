<?php
// 在所有PHP文件顶部确保首先启动session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}