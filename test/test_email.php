<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/mail_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] .'/ticket//includes/autoload.php';

//$mailService = new MailService();
//$result = $mailService->sendVerificationEmail(
//    'xiyanchake@gmail.com',
//    'Test User',
//    bin2hex(random_bytes(32)),
//    1
//);
$user = new User();
$result = $user->sendWelcomeEmail(3);
if ($result) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email. Check error logs.";
}
return;

//<?php
//include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/vendor/autoload.php';
//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;
//
//function sendEmail($to, $subject, $body, $attachment = null) {
//    // Send email
//    include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
//    include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/mail_config.php';
//    $mail = new PHPMailer(true);
//
//    try {
//        // Server settings
//        $mail->isSMTP();
//        $mail->Host = SMTP_HOST;
//        $mail->SMTPAuth = true;
//        $mail->Username = SMTP_USER;
//        $mail->Password = SMTP_PASS;
//        $mail->SMTPSecure = 'tls';
//        $mail->Port = SMTP_PORT;
//
//        // Recipients
//        $mail->setFrom(SMTP_USER, SITE_NAME);
//        $mail->addAddress($to);
//        $mail->addReplyTo(ADMIN_EMAIL, SITE_NAME);
//
//        // Content
//        $mail->isHTML(true);
//        $mail->Subject = $subject;
//
//        $mail->Body = $body;
//
//        // Attach file
//        if ($attachment !== null) {
//            $mail->addAttachment($attachment);
//        }
//
//        $mail->send();
//        return true;
//
//    } catch (Exception $e) {
//        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
//        return false;
//    }
//
//}
//$to = "junmin.luo@gmail.com";
//$subject = "Test Email";
//$body = "<h1>Testing</h1>
//                <p>Hello</p>
//                <p>Include Attachment</p>
//         ";
//$attachment = null;
//$result = sendEmail($to, $subject, $body);
//if ($result) {
//    echo "Message has been sent";
//} else {
//    echo "Message could not be sent. Mailer Error";
//}
