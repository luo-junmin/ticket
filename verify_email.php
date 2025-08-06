<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

$userId = $_GET['id'] ?? 0;
$token = $_GET['token'] ?? '';

$user = new User();
$result = $user->verifyEmail($userId, $token);

$pageTitle = $language->get('email_verification');
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';
?>

    <div class="container">
        <h1><?= $language->get('email_verification') ?></h1>

        <?php if ($result['success']): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($result['message']) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($result['message']) ?>
            </div>
        <?php endif; ?>

<!--        <a href="login.php" class="btn btn-primary">--><?php //= $language->get('login') ?><!--</a>-->
    </div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
?>