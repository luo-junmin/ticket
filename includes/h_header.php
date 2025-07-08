<?php
// Start output buffering at the VERY beginning
ob_start();

include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
$lang = Language::getInstance();
$currentPage = basename($_SERVER['PHP_SELF']);
// 检查变量是否存在再使用
$userRole = $_SESSION['user_role'] ?? 'guest';
$userEmail = $_SESSION['user_email'] ?? '';

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
//trigger_error(print_r($_SESSION, true));
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
                <!-- 添加消息显示区域 -->
                <div id="registerMessage" class="alert d-none"></div>
                <form id="registerForm" method="POST" action="/ticket/api/auth.php?action=register">
                    <?php include_once $_SERVER['DOCUMENT_ROOT'].'/ticket/includes/csrf.php'; ?>
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password"
                               required>
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
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel"><?= $lang->get('forgot_password') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="forgotPasswordForm" method="POST" action="/ticket/api/auth.php?action=forgot_password">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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

<script>
    $(document).ready(function () {

        // 获取CSRF令牌
        function getCsrfToken() {
            return $('input[name="csrf_token"]').val() || '';
        }


        $('#registerForm').on('submit', function (e) {
            e.preventDefault();

            // 清除之前的状态
            $('#registerMessage').addClass('d-none').removeClass('alert-success alert-danger');

            // 显示加载状态
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

            // 获取表单数据包括CSRF令牌
            var formData = $(this).serialize();

            // 发送AJAX请求
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                // data: $(this).serialize(),
                data: formData, // 自动包含CSRF令牌
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // 注册成功 - 禁用表单防止重复提交
                        $('#registerForm').find('input, button').prop('disabled', true);
                        $('#registerMessage').removeClass('d-none').addClass('alert-success').html(`
                        <i class="bi bi-check-circle-fill"></i> ${response.message}
                        <div class="mt-2">We've sent a verification email to your inbox.</div>
                        `);

                        // 5秒后关闭弹窗
                        setTimeout(function () {
                            $('#registerModal').modal('hide');
                        }, 9000);
                    } else {
                        // 注册失败
                        $('#registerMessage').removeClass('d-none').addClass('alert-danger').html(`
                        <i class="bi bi-exclamation-triangle-fill"></i> ${response.message}
                        `);
                    }
                },
                error: function (xhr) {
                    let errorMsg = 'An unexpected error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    $('#registerMessage').removeClass('d-none').addClass('alert-danger').text(errorMsg);
                },
                complete: function () {
                    // 恢复按钮状态
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // main.js 或相关前端代码
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            // 确保CSRF令牌随表单提交
            let formData = $(this).serializeArray();
            formData.push({
                name: 'csrf_token',
                value: $('input[name="csrf_token"]').val()
            });

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // 显示成功消息
                        $('#loginMessage').html(`
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Login successful! Redirecting...
                            </div>
                        `);

                        // 2秒后刷新页面或跳转
                        setTimeout(() => {
                            window.location.href = '/ticket/profile.php'; // 跳转到用户主页
                        }, 2000);
                    } else {
                        // 显示错误消息
                        $('#loginMessage').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> ${response.message}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#loginMessage').removeClass('alert-success').addClass('alert-danger')
                        .text('Network error. Please try later.').removeClass('d-none');
                }
            });
        });

    });
</script>

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
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
                           href="/ticket"><?= $lang->get('home') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'events.php' ? 'active' : '' ?>"
                           href="/ticket/events.php"><?= $lang->get('events') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, 'account') !== false ? 'active' : '' ?>"
                           href="/ticket/account.php"><?= $lang->get('my_account') ?></a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, 'admin') !== false ? 'active' : '' ?>"
                               href="/ticket/admin/login.php"><?= $lang->get('admin') ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'inspector')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, 'inspect') !== false ? 'active' : '' ?>"
                               href="/ticket/gpt/tv.php"><?= $lang->get('inspect') ?></a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button"
                           data-bs-toggle="dropdown">
                            <?= strtoupper($lang->getCurrentLanguage()) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?lang=zh">中文</a></li>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                               data-bs-toggle="dropdown">
                                <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['email']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item"
                                       href="/ticket/account.php"><?= $lang->get('my_account') ?></a></li>
                                <li><a class="dropdown-item"
                                       href="/ticket/orders.php"><?= $lang->get('my_orders') ?></a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/ticket/logout.php"><?= $lang->get('logout') ?></a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button class="nav-link btn btn-link" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                <?= $lang->get('login') ?>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link btn btn-link" data-bs-toggle="modal"
                                    data-bs-target="#registerModal">
                                <i class="fas fa-user-plus me-1"></i>
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