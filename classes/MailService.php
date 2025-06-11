<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/mail_config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private $mailer;
    private $lang;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->lang = Language::getInstance();

        // 配置SMTP
        $this->configureMailer();
    }

    private function configureMailer() {
        // 服务器配置
        $this->mailer->isSMTP();
        $this->mailer->Host       = SMTP_HOST; // 主SMTP服务器
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = SMTP_USER; // SMTP用户名
        $this->mailer->Password   = SMTP_PASS; // SMTP密码
//        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 加密方式
        $this->mailer->SMTPSecure = 'tls'; // 加密方式
        $this->mailer->Port       = SMTP_PORT; // TCP端口

        // 发件人配置
        $this->mailer->setFrom(SMTP_USER, SITE_NAME);
        $this->mailer->addReplyTo(ADMIN_EMAIL, 'Support Team');
    }

    public function sendVerificationEmail($email, $name, $token, $userId) {
        try {
            $this->mailer->addAddress($email, $name);

            // 构建变量
            $verificationUrl = SITE_URL . "/verify_email.php?token=$token&id=$userId";
            $expiryHours = 24;

            // 获取邮件模板
            $emailTemplate = $this->lang->get('verify_email_body');

            // 替换占位符
            $emailBody = str_replace(
                ['{url}', '{hours}'],
                [$verificationUrl, $expiryHours],
                $emailTemplate
            );

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $this->lang->get('verify_email_subject');
            $this->mailer->Body    = $emailBody;
            $this->mailer->AltBody = strip_tags($emailBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

}