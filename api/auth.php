<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/User.php';

//header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$user = new User();

switch ($action) {
    case 'login':
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $result = $user->login($email, $password);
//        trigger_error(print_r($result, true));

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
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        $result = $user->register($email, $password, $name, $phone);
        echo json_encode($result);
        break;

    case 'forgot_password':
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        // 发送密码重置邮件（需要实现sendPasswordResetEmail方法）
        $result = $user->sendPasswordResetEmail($email);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}