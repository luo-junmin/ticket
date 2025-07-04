<?php
// admin/payment_review.php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

//trigger_error(print_r($_SESSION, true));
// Check admin permission
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: ../login.php");
    exit;
}
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Database.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Payment.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Ticket.php';

$db = new Database();
$payment = new Payment($db);
$ticket = new Ticket($db);

// Get pending payments
$pendingPayments = $payment->getPendingPayments();

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiptId = (int)$_POST['receipt_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'] ?? '';

    if ($action === 'approve') {
        // Approve payment
        if ($payment->approvePayment($receiptId, $remarks)) {
            // Generate tickets
            $orderId = $payment->getOrderIdFromReceipt($receiptId);
            $ticket->generateTickets($orderId);

            // Send confirmation email
            $userEmail = $payment->getUserEmailFromReceipt($receiptId);
            $ticket->sendConfirmationEmail($orderId, $userEmail);
            trigger_error(print_r($userEmail, true));


            $success = $language->get('payment_approved_success');
        } else {
            $error = $language->get('payment_approval_failed');
        }
    } elseif ($action === 'reject') {
        // Reject payment
        if ($payment->rejectPayment($receiptId, $remarks)) {
            $success = $language->get('payment_rejected_success');
        } else {
            $error = $language->get('payment_rejection_failed');
        }
    }
}

$pageTitle = $language->get('payment_review');
//include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';
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



<!--<div class="container">-->
    <div class="container-fluid">
        <div class="row">
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

    <h1><?= $language->get('payment_review') ?></h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (empty($pendingPayments)): ?>
        <div class="alert alert-info"><?= $language->get('no_pending_payments') ?></div>
    <?php else: ?>
        <div class="payment-list">
            <?php foreach ($pendingPayments as $payment): ?>
                <div class="payment-item card mb-4">
                    <div class="card-header">
                        <h3><?= $language->get('order') ?> #<?= $payment['order_id'] ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?= $language->get('user') ?>:</strong> <?= htmlspecialchars($payment['email']) ?></p>
                                <p><strong><?= $language->get('amount') ?>:</strong> $<?= number_format($payment['total_amount'], 2) ?></p>
                                <p><strong><?= $language->get('uploaded_on') ?>:</strong> <?= $payment['upload_datetime'] ?></p>
                            </div>
                            <div class="col-md-6">
                                <div class="receipt-preview">
                                    <h4><?= $language->get('payment_receipt') ?></h4>
                                    <?php if (strpos($payment['file_url'], '.pdf') !== false): ?>
                                        <a href="<?= __DIR__ . "/../.." . UPLOADS_PATH . $payment['file_url'] ?>" target="_blank" class="btn btn-secondary">
                                            <?= $language->get('view_pdf') ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= __DIR__ . "/../.." . UPLOADS_PATH . $payment['file_url'] ?>" target="_blank">
                                            <img src="<?= __DIR__ . "/../.." . UPLOADS_PATH . $payment['file_url'] ?>" alt="Receipt" class="img-thumbnail receipt-image">
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <form method="post" class="mt-3">
                            <input type="hidden" name="receipt_id" value="<?= $payment['id'] ?>">
                            <div class="form-group">
                                <label for="remarks-<?= $payment['id'] ?>"><?= $language->get('remarks') ?></label>
                                <textarea id="remarks-<?= $payment['id'] ?>" name="remarks" class="form-control"></textarea>
                            </div>
                            <div class="btn-group">
                                <button type="submit" name="action" value="approve" class="btn btn-success">
                                    <?= $language->get('approve') ?>
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger">
                                    <?= $language->get('reject') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
            </main>

        </div>
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_footer.php'; ?>

<?php
//include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
//?>
