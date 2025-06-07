<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
//include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';
//include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/User.php';
//include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Ticket.php';
//include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Language.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/api/auth.php'; // 确保用户已登录

$user = new User();
$currentUser = $user->getUserById($_SESSION['user_id']);
$ticket = new Ticket();
$orders = $ticket->getUserOrders($_SESSION['user_id']);
$lang = Language::getInstance();

// 处理账户信息更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

    $result = $user->updateProfile($_SESSION['user_id'], $name, $phone);

    if ($result['success']) {
        $_SESSION['name'] = $name;
        $successMessage = $lang->get('profile_updated');
    } else {
        $errorMessage = $result['message'];
    }
}

// 处理密码更改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $errorMessage = $lang->get('passwords_not_match');
    } else {
        $result = $user->changePassword(
            $_SESSION['user_id'],
            $currentPassword,
            $newPassword
        );

        if ($result['success']) {
            $successMessage = $lang->get('password_changed');
        } else {
            $errorMessage = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang->getCurrentLanguage() ?>">
<head>
    <title><?= $lang->get('my_account') ?> - <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php'; ?>
    <style>
        .account-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .account-nav {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            height: fit-content;
        }

        .account-nav .nav-link {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .account-nav .nav-link.active {
            background-color: var(--primary);
            color: white;
        }

        .account-nav .nav-link:hover:not(.active) {
            background-color: #e9ecef;
        }

        .account-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .order-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .account-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php //require_once 'includes/navbar.php'; ?>

<div class="container">
    <h1 class="my-4"><?= $lang->get('my_account') ?></h1>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="account-container">
        <!-- 账户导航 -->
        <div class="account-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#profile" data-bs-toggle="tab">
                        <i class="bi bi-person me-2"></i>
                        <?= $lang->get('profile') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#orders" data-bs-toggle="tab">
                        <i class="bi bi-ticket me-2"></i>
                        <?= $lang->get('my_orders') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#password" data-bs-toggle="tab">
                        <i class="bi bi-lock me-2"></i>
                        <?= $lang->get('change_password') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        <?= $lang->get('logout') ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 账户内容 -->
        <div class="account-content tab-content">
            <!-- 个人资料标签页 -->
            <div class="tab-pane fade show active" id="profile">
                <h2 class="mb-4"><?= $lang->get('profile_information') ?></h2>

                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label"><?= $lang->get('email') ?></label>
                            <input type="email" class="form-control" id="email"
                                   value="<?= htmlspecialchars($currentUser['email']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label"><?= $lang->get('name') ?></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label"><?= $lang->get('phone') ?></label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>">
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <?= $lang->get('update_profile') ?>
                    </button>
                </form>
            </div>

            <!-- 订单标签页 -->
            <div class="tab-pane fade" id="orders">
                <h2 class="mb-4"><?= $lang->get('order_history') ?></h2>

                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">
                        <?= $lang->get('no_orders_found') ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5>
                                        <a href="/ticket.php?order_id=<?= $order['order_id'] ?>">
                                            <?= htmlspecialchars($order['title']) ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted mb-1">
                                        <?= date('Y-m-d H:i', strtotime($order['order_date'])) ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="order-status status-completed">
                                        <?= strtoupper($order['payment_status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong><?= $lang->get('order_number') ?>:</strong></p>
                                    <p>#<?= $order['order_id'] ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong><?= $lang->get('event_date') ?>:</strong></p>
                                    <p><?= date('Y-m-d H:i', strtotime($order['event_date'])) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong><?= $lang->get('total') ?>:</strong></p>
                                    <p>SGD <?= number_format($order['total_amount'], 2) ?></p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <a href="/ticket.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <?= $lang->get('view_tickets') ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 更改密码标签页 -->
            <div class="tab-pane fade" id="password">
                <h2 class="mb-4"><?= $lang->get('change_password') ?></h2>

                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label"><?= $lang->get('current_password') ?></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label"><?= $lang->get('new_password') ?></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text"><?= $lang->get('password_requirements') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label"><?= $lang->get('confirm_password') ?></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary">
                        <?= $lang->get('update_password') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php'; ?>

<script>
    // 激活标签页切换
    document.addEventListener('DOMContentLoaded', function() {
        // 从URL哈希获取标签页
        const urlHash = window.location.hash;
        if (urlHash) {
            const tabTrigger = document.querySelector(`a[href="${urlHash}"]`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        }

        // 处理导航链接点击
        document.querySelectorAll('.account-nav .nav-link').forEach(link => {
            if (link.getAttribute('data-bs-toggle') === 'tab') {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.hash = this.getAttribute('href');
                });
            }
        });
    });
</script>
</body>
</html>