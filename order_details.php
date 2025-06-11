<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/config.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Database.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Order.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/language.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

$db = new Database();
$order = new Order($db);

// 获取订单ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 验证订单归属
if (!$order->belongsToUser($orderId, $_SESSION['user_id'])) {
    header("Location: orders.php");
    exit;
}

// 获取订单详情
$orderDetails = $order->getOrderDetails($orderId);

$pageTitle = $language->get('order_details') . " #" . $orderId;
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/h_header.php';
?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $language->get('order_details') ?> #<?= $orderId ?></h1>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $language->get('back_to_orders') ?>
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?= $language->get('order_information') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><?= $language->get('event_name') ?>：</strong><?= htmlspecialchars($orderDetails['event_title']) ?></p>
                        <p><strong><?= $language->get('order_date') ?>：</strong><?= date('Y-m-d H:i', strtotime($orderDetails['created_at'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><?= $language->get('total_amount') ?>：</strong><?= $language->get('currency_symbol') ?><?= number_format($orderDetails['total_amount'], 2) ?></p>
                        <p>
                            <strong><?= $language->get('payment_status') ?>：</strong>
                            <span class="badge badge-<?=
                            $orderDetails['payment_status'] === 'completed' ? 'success' :
                                ($orderDetails['payment_status'] === 'pending' ? 'warning' : 'danger')
                            ?>">
                            <?= $language->get('payment_status_'.$orderDetails['payment_status']) ?>
                        </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?= $language->get('ticket_information') ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?= $language->get('ticket_zone') ?></th>
                            <th><?= $language->get('unit_price') ?></th>
                            <th><?= $language->get('quantity') ?></th>
                            <th><?= $language->get('subtotal') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orderDetails['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['zone_name']) ?></td>
                                <td><?= $language->get('currency_symbol') ?><?= number_format($item['price_per_ticket'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= $language->get('currency_symbol') ?><?= number_format($item['price_per_ticket'] * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="3" class="text-right"><?= $language->get('total') ?>：</td>
                            <td><?= $language->get('currency_symbol') ?><?= number_format($orderDetails['total_amount'], 2) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($orderDetails['payment_status'] === 'pending'): ?>
            <div class="text-center mt-4">
                <a href="payment.php?order_id=<?= $orderId ?>" class="btn btn-lg btn-success">
                    <i class="fas fa-credit-card"></i> <?= $language->get('pay_now') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/h_footer.php';