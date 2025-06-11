<?php
// 启动会话并检查登录状态
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 包含必要的文件
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/config.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Order.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/User.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/language.php'; // 语言文件
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

// 初始化数据库连接
$db = new Database();
$order = new Order($db);
$user = new User($db);

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 获取用户订单
$orders = $order->getUserOrders($userId);

// 设置页面标题
$pageTitle = $language->get('my_orders');

// 包含页头
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/h_header.php';
?>

    <div class="container py-5">
        <h1 class="mb-4"><?= $language->get('my_orders') ?></h1>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <?= $language->get('no_orders_message') ?>
                <a href="events.php"><?= $language->get('browse_events') ?></a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                    <tr>
                        <th><?= $language->get('order_number') ?></th>
                        <th><?= $language->get('event_name') ?></th>
                        <th><?= $language->get('order_date') ?></th>
                        <th><?= $language->get('total_amount') ?></th>
                        <th><?= $language->get('payment_status') ?></th>
                        <th><?= $language->get('actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $orderItem): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($orderItem['order_id']) ?></td>
                            <td><?= htmlspecialchars($orderItem['event_title']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($orderItem['created_at'])) ?></td>
                            <td><?= $language->get('currency_symbol') ?><?= number_format($orderItem['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?=
                                $orderItem['payment_status'] === 'completed' ? 'success' :
                                    ($orderItem['payment_status'] === 'pending' ? 'warning' : 'danger')
                                ?>">
                                    <?= $language->get('payment_status_'.$orderItem['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?= $orderItem['order_id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> <?= $language->get('view_details') ?>
                                </a>
                                <?php if ($orderItem['payment_status'] === 'pending'): ?>
                                    <a href="payment.php?order_id=<?= $orderItem['order_id'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-credit-card"></i> <?= $language->get('pay_now') ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 分页控件 -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1"><?= $language->get('previous_page') ?></a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#"><?= $language->get('next_page') ?></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

<?php
// 包含页脚
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/h_footer.php';
?>