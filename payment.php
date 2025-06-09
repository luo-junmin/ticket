<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

// Check user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check order ID
if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Database.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Payment.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Order.php';

$orderId = (int)$_GET['order_id'];
$db = new Database();
$payment = new Payment($db);
$order = new Order($db);

//$orderDetails = $order->getOrder($orderId, $_SESSION['user_id']);
$orderDetails = $payment->getOrder($orderId, $_SESSION['user_id']);

if (!$orderDetails) {
    header("Location: index.php");
    exit;
}

// If order is already paid, show ticket
if ($orderDetails['payment_status'] === 'completed') {
    header("Location: ticket.php?order_id=$orderId");
    exit;
}

// Handle file upload and payment confirmation
$error = null;
$success = null;
$receiptUploaded = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle receipt upload
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $payment->uploadReceipt($orderId, $_SESSION['user_id'], $_FILES['receipt']);

        if ($uploadResult['success']) {
            $receiptUploaded = true;
            $success = $language->get('receipt_upload_success');
        } else {
            $error = $uploadResult['message'];
        }
    }

    // Handle payment confirmation
    if (isset($_POST['confirm_payment'])) {
        if ($payment->hasPendingReceipt($orderId)) {
            if ($payment->confirmPayment($orderId)) {
                $success = $language->get('payment_confirmation_success');
            } else {
                $error = $language->get('payment_confirmation_failed');
            }
        } else {
            $error = $language->get('no_receipt_uploaded');
        }
    }
}

// Check if receipt already uploaded
$hasPendingReceipt = $payment->hasPendingReceipt($orderId);

$pageTitle = $language->get('payment');
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';
?>

    <div class="payment-container">
        <h1><?= $language->get('payment') ?></h1>

        <div class="order-summary">
            <h2><?= $language->get('order_summary') ?></h2>
            <p><?= $language->get('order_number') ?>: #<?= $orderId ?></p>
            <p><?= $language->get('event') ?>: <?= htmlspecialchars($orderDetails['title']) ?></p>
            <p><?= $language->get('total_amount') ?>: $<?= number_format($orderDetails['total_amount'], 2) ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="payment-method-paynow">
            <h2><?= $language->get('paynow_payment') ?></h2>

            <div class="paynow-qr-container">
                <p><?= $language->get('scan_paynow_qr') ?></p>
<!--                <img src="--><?php //= SITE_URL ?><!--/assets/images/paynow_qr.jpg" alt="PayNow QR Code" class="paynow-qr">-->
                <img src="<?= SITE_URL ?>/assets/images/paynow_ljm.jpg" alt="PayNow" class="paynow-qr>
                <p><?= $language->get('paynow_reference') ?>: ORDER<?= $orderId ?></p>
                <p><?= $language->get('amount_to_pay') ?>: $<?= number_format($orderDetails['total_amount'], 2) ?></p>
            </div>

            <div class="receipt-upload-section">
                <h3><?= $language->get('upload_receipt') ?></h3><br>

                <?php if ($hasPendingReceipt): ?>
                    <div class="alert alert-info">
                        <?= $language->get('receipt_uploaded_pending') ?>
                    </div>
                <?php elseif ($orderDetails['payment_status'] === 'completed'): ?>
                    <div class="alert alert-success">
                        <?= $language->get('payment_completed') ?>
                    </div>
                <?php else: ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="receipt"><?= $language->get('select_receipt') ?></label>
                            <input type="file" id="receipt" name="receipt" accept="image/*,.pdf" required>
                            <small class="form-text text-muted"><?= $language->get('receipt_upload_help') ?></small>
                        </div><br>
                        <button type="submit" class="btn btn-primary"><?= $language->get('upload_receipt') ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($hasPendingReceipt && $orderDetails['payment_status'] !== 'completed'): ?>
                <form method="post">
                    <input type="hidden" name="confirm_payment" value="1">
                    <button type="submit" class="btn btn-success"><?= $language->get('confirm_payment') ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

<?php
$includeQRCode = false;
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
?>

<!---------------->


