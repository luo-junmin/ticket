<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $phone = $_POST['phone'] ?? '';

    try {
        $user = new User();
        $result = $user->register($email, $password, $name, $phone);

        if ($result['success']) {
            $success = $language->get('registration_success');
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = $language->get('register');
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';
?>

    <div class="container">
        <h1><?= $language->get('register') ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email"><?= $language->get('email') ?></label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password"><?= $language->get('password') ?></label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="name"><?= $language->get('full_name') ?></label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="phone"><?= $language->get('phone') ?></label>
                <input type="tel" id="phone" name="phone" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary"><?= $language->get('register') ?></button>
        </form>
    </div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
?>