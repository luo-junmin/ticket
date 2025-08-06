<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';

class Admin {
    private $pdo;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    public function _login($email, $password) {
        // 这里应该使用更安全的管理员验证方式
        $stmt = $this->pdo->prepare("
            SELECT * FROM admins 
            WHERE email = ? AND password = ?
        ");
//        $stmt->execute([$email, sha1($password)]);
        $stmt->execute([$email, $password]);
        return $stmt->fetch();
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("
        SELECT user_id, email, password, role, is_verified 
        FROM users 
        WHERE email = :email
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => $this->lang->get('invalid_credentials')
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => $this->lang->get('invalid_credentials')
            ];
        }

        if (!$user['is_verified']) {
            return [
                'success' => false,
                'message' => $this->lang->get('account_not_verified')
            ];
        }

        // 登录成功逻辑
        // 设置会话变量
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role']; // 确保角色被设置
        $_SESSION['logged_in'] = true;
        return ['success' => true, 'user_id' => $user['user_id']];
    }

}