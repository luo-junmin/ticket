<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class User {
    private $pdo;
    private $lang;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->lang = Language::getInstance();
    }

    public function sendEmail($to, $subject, $body, $attachment = null) {
        // Send email
        include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
        include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/mail_config.php';
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = 'tls';
            $mail->Port = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_USER, SITE_NAME);
            $mail->addAddress($to);
            $mail->addReplyTo(ADMIN_EMAIL, SITE_NAME);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;

            $mail->Body = $body;

            // Attach file
            if ($attachment !== null) {
                $mail->addAttachment($attachment);
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }

    }

    public function register_v1($email, $password, $name = null, $phone = null) {
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

    public function login_v1($email, $password) {
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

    // classes/User.php 中应包含的方法
    public function createUser($data) {
        // 密码哈希处理
//        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
//
//        $data = array(
//            ':name' => $data['name'],
//            ':email' => $data['email'],
//            ':password' => $hashedPassword,
//            ':role' => $data['role'],
//            ':phone' => $data['phone']
//        );
//
//        $sql = "INSERT INTO users (name, email, password, role, phone, created_at)
//            VALUES (:name, :email, :password, :role, :phone, NOW())";
//
//        $stmt = $this->pdo->prepare($sql);
//        return $stmt->execute($data);

        return $this->register($data['email'], $data['password'], $data['name'], $data['phone']);
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

    // ---
    public function register_old($email, $password, $name, $phone = '') {
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => $this->lang->get('invalid_email')];
        }

        // 检查邮箱是否已存在
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => $this->lang->get('email_exists')];
        }

        // 密码强度验证
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => $this->lang->get('weak_password')];
        }

        // 创建用户记录
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->pdo->prepare("
        INSERT INTO users 
        (email, password, name, phone, verification_token, token_expires_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

        trigger_error(print_r($stmt,true));
        $result = $stmt->execute([$email, $hashedPassword, $name, $phone, $verificationToken, $tokenExpires]);

        if ($result) {
            $userId = $this->pdo->lastInsertId();
            trigger_error(print_r($result,true));
            $this->sendVerificationEmail($userId, $email, $verificationToken);
            trigger_error("After sendVerificationEmail");

            return ['success' => true];
        }

        return ['success' => false, 'message' => $this->lang->get('registration_failed')];
    }

    private function logRegistrationAttempt($email, $success, $message = '') {
        $logMessage = sprintf(
            "[%s] Registration attempt - Email: %s, Success: %s, Message: %s",
            date('Y-m-d H:i:s'),
            $email,
            $success ? 'Yes' : 'No',
            $message
        );
        error_log($logMessage);
    }

    public function register($email, $password, $name, $phone = '') {
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => $this->lang->get('invalid_email')];
        }

        // 检查邮箱是否已存在（添加事务确保原子性）
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? FOR UPDATE");
            $stmt->execute([$email]);
            $emailExists = $stmt->fetchColumn() > 0;

            if ($emailExists) {
                $this->pdo->commit(); // 明确提交只读事务
                return ['success' => false, 'message' => $this->lang->get('email_exists')];
            }

            // 密码强度验证
            if (strlen($password) < 8) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => $this->lang->get('weak_password')];
            }

            // 创建用户记录
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = bin2hex(random_bytes(32));
            $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $this->pdo->prepare("
            INSERT INTO users 
            (email, password, name, phone, verification_token, token_expires_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

            $emailSent = $stmt->execute([$email, $hashedPassword, $name, $phone, $verificationToken, $tokenExpires]);

            $userId = $this->pdo->lastInsertId();
            $this->pdo->commit();

            // 发送验证邮件
            $this->sendVerificationEmail($userId, $email, $verificationToken);

            return [
                'success' => true,
                'message' => $this->lang->get('registration_success'),
                'email_sent' => $emailSent
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => $this->lang->get('registration_failed')];
        }
    }

    public function sendVerificationEmail($userId, $email, $token) {
        try {
            $mailService = new MailService();

            // 获取用户姓名
            $stmt = $this->pdo->prepare("SELECT name FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            return $mailService->sendVerificationEmail(
                $email,
                $user['name'] ?? 'User',
                $token,
                $userId
            );
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }

    public function emailExists($email) {
//        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
//        $stmt->execute([$email]);
//        trigger_error(print_r($stmt,true));
//        trigger_error($stmt->fetchColumn() );
//        return $stmt->fetchColumn() > 0;

        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;

    }

    public function verifyEmail($userId, $token) {
        $stmt = $this->pdo->prepare("
        SELECT user_id, verification_token, token_expires_at 
        FROM users 
        WHERE user_id = ? 
        AND is_verified = 0
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => $this->lang->get('invalid_verification')
            ];
        }

        // 检查token是否匹配且未过期
        if (!hash_equals($user['verification_token'], $token)) {
            return [
                'success' => false,
                'message' => $this->lang->get('invalid_token')
            ];
        }

        if (strtotime($user['token_expires_at']) < time()) {
            return [
                'success' => false,
                'message' => $this->lang->get('token_expired')
            ];
        }

        // 更新用户为已验证
        $stmt = $this->pdo->prepare("
        UPDATE users 
        SET is_verified = 1, 
            verification_token = NULL,
            token_expires_at = NULL
        WHERE user_id = ?
        ");
        $result = $stmt->execute([$userId]);

        if ($result) {
            // 发送欢迎邮件
            $this->sendWelcomeEmail($userId);

            return [
                'success' => true,
                'message' => $this->lang->get('verification_success')
            ];
        }

        return [
            'success' => false,
            'message' => $this->lang->get('verification_failed')
        ];
    }

    public function sendWelcomeEmail($userId) {
        $stmt = $this->pdo->prepare("SELECT email, name FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $site = "TicketHub";

        $subject = $this->lang->get('welcome_email_subject');

        // 替换占位符
        $subject = str_replace(
            ['{site}'],
            [$site],
            $subject
        );

//        $message = $this->lang->get('welcome_email_body', [
//            'name' => $user['name'],
//            'site' => SITE_NAME
//        ]);

        // 构建变量
        $name = $user['name'];
        if (NULL == $name) {
            $name = "User";
        }
        $welcomeUrl = SITE_URL."/ticket";

        // 获取邮件模板
        $emailTemplate = $this->lang->get('welcome_email_body');

        // 替换占位符
        $emailBody = str_replace(
            ['{name}', '{url}', '{site}'],
            [$name, $welcomeUrl, $site],
            $emailTemplate
        );

//        $headers = "From: " . SITE_EMAIL . "\r\n";
//        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
//        mail($user['email'], $subject, c, $headers);

        return $this->sendEmail($user['email'], $subject, $emailBody);
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