<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/mail_config.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/ticket/includes/autoload.php';

$email = 'xiyanchake@gmail.com'; // 替换为你的测试邮箱
$user = new User();

try {
    $testToken = bin2hex(random_bytes(32));
    $result = $user->sendVerificationEmail(0, $email, $testToken);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Test email sent' : 'Failed to send test email'
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}