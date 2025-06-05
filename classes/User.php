<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';

class User {
    private $pdo;
    private $lang;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->lang = Language::getInstance();
    }

    public function register($email, $password, $name = null, $phone = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => $this->lang->get('invalid_email')];
        }

        // Check if email exists
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            return ['success' => false, 'message' => $this->lang->get('email_exists')];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Create user
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password, name, phone) 
            VALUES (?, ?, ?, ?)
        ");

        try {
            $success = $stmt->execute([$email, $hashedPassword, $name, $phone]);

            if ($success) {
                $userId = $this->pdo->lastInsertId();
                return ['success' => true, 'user_id' => $userId];
            }

            return ['success' => false, 'message' => $this->lang->get('registration_failed')];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("
            SELECT user_id, email, password, name, role  
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
//        trigger_error($email." - ".$password." - ".$user['password']);
        $temp = ($password === $user['password']) ? true : false;
//        trigger_error($temp);
        if ($user && $temp) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            if ($user['role'] == 'admin') {
                $_SESSION['role'] = 'admin';
            }
            return ['success' => true];
        }

        return ['success' => false, 'message' => $this->lang->get('invalid_credentials')];
    }

    public function getUserDiscounts($userId) {
        $stmt = $this->pdo->prepare("
            SELECT d.* FROM user_discounts ud
            JOIN discount_types d ON ud.discount_id = d.discount_id
            WHERE ud.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    // 在User.php中添加以下方法

    public function updateProfile($userId, $name, $phone) {
        try {
            $stmt = $this->pdo->prepare("
            UPDATE users 
            SET name = ?, phone = ?
            WHERE user_id = ?
        ");
            $stmt->execute([$name, $phone, $userId]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        // 验证当前密码
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $temp = ($currentPassword !== $user['password']) ? true : false;
        if (!$user || $temp ) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        return ['success' => true];
    }

    public function getUserOrders($userId) {
        $stmt = $this->pdo->prepare("
        SELECT o.*, e.title, e.event_date
        FROM orders o
        JOIN events e ON o.event_id = e.event_id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAllUsers() {
        $stmt = $this->pdo->query("
        SELECT * FROM users 
        ORDER BY created_at DESC
    ");
        return $stmt->fetchAll();
    }

    public function getUserCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }

    public function updateUserStatus($userId, $status) {
        $stmt = $this->pdo->prepare("
        UPDATE users 
        SET is_active = ? 
        WHERE user_id = ?
    ");
        return $stmt->execute([$status, $userId]);
    }

    public function updateUserRole($userId, $role) {
        $stmt = $this->pdo->prepare("
        UPDATE users 
        SET role = ? 
        WHERE user_id = ?
    ");
        return $stmt->execute([$role, $userId]);
    }

    public function deleteUser($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}