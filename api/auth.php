<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/csrf.php';

//error_log("CSRF Token Received: " . ($_POST['csrf_token'] ?? 'NULL'));
//error_log("Session CSRF Token: " . ($_SESSION['csrf_token'] ?? 'NULL'));

// 确保session已启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


//header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

$user = new User();

switch ($action) {
    case 'login':
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $result = $user->login($email, $password);
        // 调试输出（正式环境应移除）
//        error_log("Session after login: " . print_r($_SESSION, true));

        if ($result['success']) {
            if ($remember) {
                // 设置30天有效期的记住我cookie
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/');
                // 存储token到数据库（需要添加remember_token字段到users表）
            }
//            trigger_error(print_r($_SESSION, true));
            echo json_encode(['success' => true, 'redirect' => $_SESSION['login_redirect'] ?? '/ticket/']);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        break;

    case 'register':
        // 确保获取了CSRF令牌
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrfToken)) {  // 这里必须传递参数
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            break;
        }

        $email = strtolower(trim($_POST['email'] ?? '')); // 规范化邮箱
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // 基础验证
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        if (empty($_POST['agree_terms'])) {
            echo json_encode(['success' => false, 'message' => 'You must agree to the terms']);
            exit;
        }

        try {
            $result = $user->register($email, $password, $name, $phone);

            // 确保返回统一格式
            if ($result['success']) {
                http_response_code(201); // Created
            } else {
                http_response_code(400); // Bad Request
            }
            $response = $result;

        } catch (Exception $e) {
            http_response_code(500);
            error_log("Registration error: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Registration failed'];
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    case 'forgot_password':
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        // 发送密码重置邮件（需要实现sendPasswordResetEmail方法）
        $result = $user->sendPasswordResetEmail($email);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}