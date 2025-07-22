<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Order.php';

// 安全的日期格式化函数
function formatDate($dateString) {
    if (empty($dateString)) {
        return 'No date set';
    }

    try {
        $date = new DateTime($dateString);
        return $date->format('Y-m-d H:i');
    } catch (Exception $e) {
        error_log("Wrong date format: " . $e->getMessage());
        return 'Invalid Date';
    }
}

$order = new Order();
$orders = $order->getAllOrders();

// 处理筛选
$statusFilter = $_GET['status'] ?? '';
if ($statusFilter) {
    $orders = array_filter($orders, function($order) use ($statusFilter) {
        return $order['status'] === $statusFilter;
    });
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_header.php'; ?>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_navbar.php'; ?>


<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Orders</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="?status=" class="btn btn-sm btn-outline-secondary">All</a>
                        <a href="?status=pending" class="btn btn-sm btn-outline-secondary">Pending</a>
                        <a href="?status=completed" class="btn btn-sm btn-outline-secondary">Completed</a>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Event</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Order Date</th>
                        <th>Paid Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><a href="edit.php?id=<?= $order['order_id'] ?>">#<?= $order['order_id'] ?></a></td>
                            <td><?= htmlspecialchars($order['title']) ?></td>
                            <td><?= htmlspecialchars($order['customer_email']) ?></td>
                            <td>SGD <?= number_format($order['total_amount'], 2) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
<!--                            <td>--><?php //= date('Y-m-d H:i', strtotime($order['payment_date'])) ?><!--</td>-->
                            <td><?= formatDate($order['payment_date']) ?></td>

                            <td>
                                    <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                            </td>
                            <td>
                                <a href="edit.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_footer.php'; ?>
</body>
</html>