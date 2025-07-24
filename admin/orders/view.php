<?php
// require_once '../config/database.php';
// require_once '../auth/admin_check.php';
require_once __DIR__ . '/../../includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';
$pdo = get_pdo_connection();

$order_id = intval($_GET['id'] ?? 0);

// Fetch complete order information with joins
$order_query = "SELECT
                o.*,
                u.user_id, u.email, u.name, u.phone, u.role,
                e.event_id, e.title AS event_title, e.event_date, e.location, e.venue,
                COUNT(od.detail_id) AS item_count
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                JOIN events e ON o.event_id = e.event_id
                LEFT JOIN order_details od ON o.order_id = od.order_id
                WHERE o.order_id = ? AND o.is_deleted = 0
                GROUP BY o.order_id";
$order_stmt = $pdo->prepare($order_query);
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or has been deleted");
}

// Fetch order details with zone information
$details_query = "SELECT
                  od.*,
                  tz.zone_name, tz.zone_category, tz.base_price,
                  (od.price_per_ticket * od.quantity) AS subtotal
                  FROM order_details od
                  JOIN ticket_zones tz ON od.zone_id = tz.zone_id
                  WHERE od.order_id = ?
                  ORDER BY tz.zone_category, tz.zone_name";
$details_stmt = $pdo->prepare($details_query);
$details_stmt->execute([$order_id]);
$order_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_quantity = 0;
$calculated_total = 0;
foreach ($order_details as $detail) {
    $total_quantity += $detail['quantity'];
    $calculated_total += $detail['subtotal'];
}

// Format dates
$event_date = new DateTime($order['event_date']);
$order_date = new DateTime($order['created_at']);
$payment_date = $order['payment_date'] ? new DateTime($order['payment_date']) : null;
$expiry_date = $order['transaction_expiry'] ? new DateTime($order['transaction_expiry']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> Details | Ticket Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-header {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge-category {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .cat1 { background-color: #d4edda; color: #155724; }
        .cat2 { background-color: #cce5ff; color: #004085; }
        .cat3 { background-color: #fff3cd; color: #856404; }
        .cat4 { background-color: #f8d7da; color: #721c24; }
        .restricted { background-color: #e2e3e5; color: #383d41; }
        .payment-badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }
        .pending { background-color: #fff3cd; color: #856404; }
        .completed { background-color: #d4edda; color: #155724; }
        .failed { background-color: #f8d7da; color: #721c24; }
        .user-badge {
            font-size: 0.8em;
            padding: 4px 8px;
        }
        .admin { background-color: #6f42c1; color: white; }
        .user { background-color: #20c997; color: white; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-receipt"></i> Order #<?= $order_id ?>
                <span class="payment-badge badge rounded-pill <?= $order['payment_status'] ?> ms-2">
                    <?= ucfirst($order['payment_status']) ?>
                </span>
            </h2>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-calendar-event"></i> Event Information
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($order['event_title']) ?></h5>
                        <p class="card-text">
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($order['venue'] ?: $order['location']) ?><br>
                            <i class="bi bi-clock"></i> <?= $event_date->format('F j, Y, g:i a') ?>
                        </p>
                        <a href="../events/view.php?id=<?= $order['event_id'] ?>" class="btn btn-sm btn-outline-primary">
                            View Event
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-person"></i> Customer Information
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($order['name']) ?>
                            <span class="user-badge badge rounded-pill <?= $order['role'] ?> ms-2">
                                <?= ucfirst($order['role']) ?>
                            </span>
                        </h5>
                        <p class="card-text mb-1">
                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($order['email']) ?>
                        </p>
                        <?php if ($order['phone']): ?>
                        <p class="card-text">
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($order['phone']) ?>
                        </p>
                        <?php endif; ?>
                        <a href="../users/view.php?id=<?= $order['user_id'] ?>" class="btn btn-sm btn-outline-primary">
                            View Customer
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-credit-card"></i> Payment Information
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6"><strong>Method:</strong></div>
                            <div class="col-6 text-capitalize">
                                <?= str_replace('_', ' ', $order['payment_method']) ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Transaction ID:</strong></div>
                            <div class="col-6 font-monospace">
                                <?= htmlspecialchars($order['payment_transaction_id']) ?>
                            </div>
                        </div>
                        <?php if ($payment_date): ?>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Paid On:</strong></div>
                            <div class="col-6">
                                <?= $payment_date->format('F j, Y, g:i a') ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($expiry_date): ?>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Expires On:</strong></div>
                            <div class="col-6">
                                <?= $expiry_date->format('F j, Y, g:i a') ?>
                                <?php if ($expiry_date < new DateTime() && $order['payment_status'] === 'pending'): ?>
                                    <span class="badge bg-danger ms-2">Expired</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-6"><strong>Order Date:</strong></div>
                            <div class="col-6">
                                <?= $order_date->format('F j, Y, g:i a') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-receipt-cutoff"></i> Order Summary
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6"><strong>Items:</strong></div>
                            <div class="col-6"><?= count($order_details) ?> zone(s)</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Tickets:</strong></div>
                            <div class="col-6"><?= $total_quantity ?> ticket(s)</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Subtotal:</strong></div>
                            <div class="col-6">$<?= number_format($calculated_total, 2) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Total Amount:</strong></div>
                            <div class="col-6 fw-bold">$<?= number_format($order['total_amount'], 2) ?></div>
                        </div>
                        <?php if ($calculated_total != $order['total_amount']): ?>
                        <div class="alert alert-warning mt-2 py-1">
                            <i class="bi bi-exclamation-triangle"></i> Note: Calculated subtotal differs from order total
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-ticket-perforated"></i> Ticket Details
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Zone</th>
                                <th>Category</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_details as $detail): ?>
                            <tr>
                                <td><?= htmlspecialchars($detail['zone_name']) ?></td>
                                <td>
                                    <span class="badge-category badge <?= $detail['zone_category'] ?>">
                                        <?= strtoupper($detail['zone_category']) ?>
                                    </span>
                                </td>
                                <td class="text-end">$<?= number_format($detail['price_per_ticket'], 2) ?></td>
                                <td class="text-end"><?= $detail['quantity'] ?></td>
                                <td class="text-end fw-bold">$<?= number_format($detail['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">$<?= number_format($calculated_total, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div>
                <?php if ($order['payment_status'] === 'pending' && (!$expiry_date || $expiry_date > new DateTime())): ?>
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                        <i class="bi bi-check-circle"></i> Mark as Paid
                    </button>
                <?php endif; ?>
                <?php if ($order['payment_status'] === 'pending'): ?>
                    <button class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                        <i class="bi bi-x-circle"></i> Cancel Order
                    </button>
                <?php endif; ?>
            </div>
            <div>
                <a href="print.php?id=<?= $order_id ?>" class="btn btn-outline-secondary me-2" target="_blank">
                    <i class="bi bi-printer"></i> Print
                </a>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                    <i class="bi bi-envelope"></i> Send Confirmation
                </button>
            </div>
        </div>
    </div>

    <!-- Mark as Paid Modal -->
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="mark_paid.php" method="post">
                    <div class="modal-body">
                        <p>Are you sure you want to mark this order as paid?</p>
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date & Time</label>
                            <input type="datetime-local" class="form-control" id="payment_date" name="payment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Cancel Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="cancel_order.php" method="post">
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this order?</p>
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Reason for Cancellation</label>
                            <select class="form-select" id="cancel_reason" name="cancel_reason" required>
                                <option value="">Select a reason</option>
                                <option value="payment_expired">Payment Expired</option>
                                <option value="customer_request">Customer Request</option>
                                <option value="event_cancelled">Event Cancelled</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cancel_notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="cancel_notes" name="cancel_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Email Modal -->
    <div class="modal fade" id="sendEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Order Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="send_confirmation.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <div class="mb-3">
                            <label for="email_type" class="form-label">Email Type</label>
                            <select class="form-select" id="email_type" name="email_type" required>
                                <option value="confirmation">Order Confirmation</option>
                                <option value="payment_received" <?= $order['payment_status'] === 'completed' ? '' : 'disabled' ?>>Payment Received</option>
                                <option value="tickets_issued" <?= $order['payment_status'] === 'completed' ? '' : 'disabled' ?>>Tickets Issued</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="email_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="email_subject" name="email_subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_message" class="form-label">Additional Message</label>
                            <textarea class="form-control" id="email_message" name="email_message" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default payment date to now
        document.addEventListener('DOMContentLoaded', function() {
            // For payment date
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            document.getElementById('payment_date').value = localISOTime;

            // For email subject
            const emailSubject = document.getElementById('email_subject');
            const eventTitle = "<?= addslashes($order['event_title']) ?>";
            emailSubject.value = `Your tickets for ${eventTitle} (Order #<?= $order_id ?>)`;
        });
    </script>
</body>
</html>