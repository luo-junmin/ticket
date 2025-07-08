<?php

// middleware/InspectorAuthMiddleware.php
class InspectorAuthMiddleware
{


    public function handle()
    {
//        session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: /ticket/index.php');
            exit;
        }

        $userRole = $_SESSION['role'];

        // 允许管理员和验票员访问
        if ($userRole !== 'admin' && $userRole !== 'inspector') {
            header('HTTP/1.0 403 Forbidden');
            echo '您没有权限访问验票系统';
            exit;
        }
    }
}
