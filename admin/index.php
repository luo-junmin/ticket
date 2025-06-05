<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';
$event = new Event();
$order = new Order();
$user = new User();

$eventCount = $event->getEventCount();
$orderCount = $order->getOrderCount();
$userCount = $user->getUserCount();
$recentOrders = $order->getRecentOrders(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_header.php'; ?>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Events</h5>
                            <p class="card-text display-4"><?= $eventCount ?></p>
                            <a href="events/" class="text-white">View all</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Orders</h5>
                            <p class="card-text display-4"><?= $orderCount ?></p>
                            <a href="orders/" class="text-white">View all</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Users</h5>
                            <p class="card-text display-4"><?= $userCount ?></p>
                            <a href="users/" class="text-white">View all</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近订单 -->
            <div class="card">
                <div class="card-header">
                    <h5>Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Event</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><a href="orders/edit.php?id=<?= $order['order_id'] ?>">#<?= $order['order_id'] ?></a></td>
                                    <td><?= htmlspecialchars($order['event_title']) ?></td>
                                    <td><?= htmlspecialchars($order['user_email']) ?></td>
                                    <td>SGD <?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($order['order_date'])) ?></td>
                                    <td><span class="badge bg-<?= $order['payment_status'] === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_footer.php'; ?>
</body>
</html>