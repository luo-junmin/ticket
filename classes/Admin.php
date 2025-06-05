<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';

class Admin {
    private $pdo;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    public function login($email, $password) {
        // 这里应该使用更安全的管理员验证方式
        $stmt = $this->pdo->prepare("
            SELECT * FROM admins 
            WHERE email = ? AND password = ?
        ");
//        $stmt->execute([$email, sha1($password)]);
        $stmt->execute([$email, $password]);
        return $stmt->fetch();
    }
}