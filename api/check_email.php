<?php
// ticket/api/check_email.php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/User.php';

header('Content-Type: application/json');

if (!isset($_GET['email'])) {
    echo json_encode(['error' => 'Email parameter missing']);
    exit;
}

$email = trim($_GET['email']);
$user = new User();

echo json_encode([
    'exists' => $user->emailExists($email),
    'email' => $email
]);