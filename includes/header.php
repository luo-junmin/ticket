<?php
//require_once __DIR__ . '/../config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';

?>
<!DOCTYPE html>
<html lang="<?= $language->getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $language->get('site_name') ?> - <?= $pageTitle ?? '' ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- 在header.php中引入 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/ticket/assets/css/style.css" rel="stylesheet">
</head>
<body>
<header>
    <div class="container">
        <div class="logo">
            <a href="<?= SITE_URL ?>"><?= $language->get('site_name') ?></a>
        </div>

        <nav>
            <ul>
                <li><a href="<?= SITE_URL ?>"><?= $language->get('home') ?></a></li>
                <li><a href="<?= SITE_URL ?>/event.php"><?= $language->get('events') ?></a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?= SITE_URL ?>/account.php"><?= $language->get('my_account') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/logout.php"><?= $language->get('logout') ?></a></li>
                <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php"><?= $language->get('login') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/register.php"><?= $language->get('register') ?></a></li>
                <?php endif; ?>
                <li><?= $language->getLanguageSwitcher() ?></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container">

    <!-- 在导航栏的用户相关部分修改为以下代码 -->
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
                    <li><a class="dropdown-item" href="/account.php"><?= $lang->get('my_account') ?></a></li>
                    <li><a class="dropdown-item" href="/orders.php"><?= $lang->get('my_orders') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/logout.php"><?= $lang->get('logout') ?></a></li>
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
                    <form id="loginForm" method="POST" action="/api/auth.php?action=login">
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
                    <form id="registerForm" method="POST" action="/api/auth.php?action=register">
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
                    <form id="forgotPasswordForm" method="POST" action="/api/auth.php?action=forgot_password">
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
