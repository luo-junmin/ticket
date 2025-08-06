<?php
//require_once __DIR__ . '/../../classes/Order.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php?error=invalid_id');
    exit;
}

$orderId = (int)$_GET['id'];
$order = new Order();
$orderDetails1 = $order->getOrderById($orderId);

if (!$orderDetails1) {
    header('Location: orders.php?error=order_not_found');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate inputs
    $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatuses)) {
        $error = "Invalid order status";
    } else {
        // Update order
        if ($order->updateOrder($orderId, [
            'status' => $status,
            'admin_notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ])) {
            $success = "Order updated successfully!";
            // Refresh order details
            $orderDetails = $order->getOrderById($orderId);
        } else {
            $error = "Failed to update order";
        }
    }
}

$orderDetails = $orderDetails1[0];

$pageTitle = "Edit Order #" . $orderDetails['order_id'];
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_header.php';
?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_navbar.php'; ?>

    <div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4"><?= htmlspecialchars($pageTitle) ?></h1>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-edit me-1"></i>
                        Order Details
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Order Number</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($orderDetails['order_id']) ?>" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Customer</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($orderDetails['user_name']) ?>" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Order Date</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars(date('M j, Y H:i', strtotime($orderDetails['created_at']))) ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="pending" <?= $orderDetails['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $orderDetails['payment_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="completed" <?= $orderDetails['payment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $orderDetails['payment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Admin Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($orderDetails['admin_notes'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Update Order</button>
                                <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Items Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-list me-1"></i>
                        Order Items
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($order->getOrderItems($orderId) as $item): ?>
                                <?php     error_log("getOrderById " . print_r($item,true)); ?>

                                    <tr>
                                        <td><?= htmlspecialchars($item['user_name']) ?></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td><?= htmlspecialchars(number_format($item['price'], 2)) ?></td>
                                        <td><?= htmlspecialchars(number_format($item['quantity'] * $item['price'], 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th><?= htmlspecialchars(number_format($orderDetails['total_amount'], 2)) ?></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>
    </div>
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_footer.php'; ?>
