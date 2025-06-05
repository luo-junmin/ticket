<?php
// Start output buffering at the VERY beginning
ob_start();

include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
$lang = Language::getInstance();
$currentPage = basename($_SERVER['PHP_SELF']);

// 处理语言切换
if (isset($_GET['lang'])) {
    $language = Language::getInstance();
    if ($language->setLanguage($_GET['lang'])) {
        // 重定向到当前页面去掉lang参数
        $url = strtok($_SERVER['REQUEST_URI'], '?'); // 获取问号前的部分
        $query = $_GET;
        unset($query['lang']);
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        header("Location: " . $url);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $lang->getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= $lang->get($currentPage) ?></title>
<!--    <link href="/ticket/assets/css/bootstrap.min.css" rel="stylesheet">-->

    <link href="/ticket/assets/css/all.min.css" rel="stylesheet">
    <link href="/ticket/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/ticket/assets/fonts/bootstrap-icons.css" rel="stylesheet">
<!--    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
    <script src="/ticket/assets/js/bootstrap.bundle.min.js"></script>
    <link href="/ticket/assets/css/style.css" rel="stylesheet">
</head>
<body>
<!-- 在header.php的</header>标签前添加模态框代码 -->
<!-- 登录模态框 -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel"><?= $lang->get('login') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm" method="POST" action="/ticket/api/auth.php?action=login">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label"><?= $lang->get('email') ?></label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label"><?= $lang->get('password') ?></label>
                        <input type="password" class="form-control" id="loginPassword" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe"><?= $lang->get('remember_me') ?></label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?= $lang->get('login') ?></button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">
                        <?= $lang->get('forgot_password') ?>
                    </a>
                </div>
                <div class="text-center mt-2">
                    <span><?= $lang->get('no_account') ?></span>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">
                        <?= $lang->get('register_here') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 注册模态框 -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel"><?= $lang->get('register') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm" method="POST" action="/ticket/api/auth.php?action=register">
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label"><?= $lang->get('email') ?>*</label>
                        <input type="email" class="form-control" id="registerEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label"><?= $lang->get('password') ?>*</label>
                        <input type="password" class="form-control" id="registerPassword" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label"><?= $lang->get('confirm_password') ?>*</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerName" class="form-label"><?= $lang->get('name') ?></label>
                        <input type="text" class="form-control" id="registerName" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="registerPhone" class="form-label"><?= $lang->get('phone') ?></label>
                        <input type="tel" class="form-control" id="registerPhone" name="phone">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree_terms" required>
                        <label class="form-check-label" for="agreeTerms">
                            <?= $lang->get('i_agree_to') ?>
                            <a href="/terms.php" target="_blank"><?= $lang->get('terms_of_service') ?></a>
                        </label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?= $lang->get('register') ?></button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <span><?= $lang->get('already_have_account') ?></span>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">
                        <?= $lang->get('login_here') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 忘记密码模态框 -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel"><?= $lang->get('forgot_password') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="forgotPasswordForm" method="POST" action="/ticket/api/auth.php?action=forgot_password">
                    <div class="mb-3">
                        <label for="forgotEmail" class="form-label"><?= $lang->get('email') ?></label>
                        <input type="email" class="form-control" id="forgotEmail" name="email" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?= $lang->get('reset_password') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<header class="horizontal-nav">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/ticket"><?= SITE_NAME ?></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="/ticket"><?= $lang->get('home') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'events.php' ? 'active' : '' ?>" href="/ticket/events.php"><?= $lang->get('events') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'account') !== false ? 'active' : '' ?>" href="/ticket/account.php"><?= $lang->get('my_account') ?></a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, 'admin') !== false ? 'active' : '' ?>" href="/ticket/admin/login.php"><?= $lang->get('admin') ?></a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown">
                            <?= strtoupper($lang->getCurrentLanguage()) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?lang=zh">中文</a></li>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['email']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/ticket/account.php"><?= $lang->get('my_account') ?></a></li>
                                <li><a class="dropdown-item" href="/ticket/orders.php"><?= $lang->get('my_orders') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ticket/logout.php"><?= $lang->get('logout') ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button class="nav-link btn btn-link" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <?= $lang->get('login') ?>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link btn btn-link" data-bs-toggle="modal" data-bs-target="#registerModal">
                                <?= $lang->get('register') ?>
                            </button>
                        </li>

                    <?php endif; ?>
                </ul>

            </div>
        </div>
    </nav>
</header>
<br>
<main class="container">