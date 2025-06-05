<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';
//session_start();

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

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Payment.php';

$orderId = (int)$_GET['order_id'];
$db = new Database();
$payment = new Payment($db);
$order = $payment->getOrder($orderId, $_SESSION['user_id']);

if (!$order) {
    header("Location: index.php");
    exit;
}

// If order is already paid, show ticket
if ($order['payment_status'] === 'completed') {
    header("Location: ticket.php?order_id=$orderId");
    exit;
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'];

    if (in_array($paymentMethod, ['paynow', 'credit_card'])) {
        // Process payment
        if ($payment->processPayment($orderId, $paymentMethod)) {
            // Generate tickets
            require_once __DIR__ . '/classes/Ticket.php';
            $ticket = new Ticket($db);
            $ticket->generateTickets($orderId);

            // Send confirmation email
            $ticket->sendConfirmationEmail($orderId, $_SESSION['email']);

            header("Location: ticket.php?order_id=$orderId");
            exit;
        }
    }

    $error = $language->get('payment_failed');
}

$pageTitle = $language->get('payment');
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';
//require_once __DIR__ . '/includes/header.php';
?>

    <div class="payment-container">
        <h1><?= $language->get('payment') ?></h1>

        <div class="order-summary">
            <h2><?= $language->get('order_summary') ?></h2>
            <p><?= $language->get('order_number') ?>: #<?= $orderId ?></p>
            <p><?= $language->get('event') ?>: <?= htmlspecialchars($order['title']) ?></p>
            <p><?= $language->get('date') ?>: <?= date('Y-m-d H:i', strtotime($order['event_date'])) ?></p>
            <p><?= $language->get('location') ?>: <?= htmlspecialchars($order['location']) ?></p>
            <p><?= $language->get('total_amount') ?>: $<?= number_format($order['total_amount'], 2) ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="payment-methods">
                <h2><?= $language->get('payment_method') ?></h2>

                <div class="payment-option">
                    <input type="radio" id="paynow" name="payment_method" value="paynow" checked>
                    <label for="paynow">
                        <img src="<?= SITE_URL ?>/assets/images/paynow-logo.png" alt="PayNow">
                        <span>PayNow</span>
                    </label>
                </div>

                <div class="payment-option">
                    <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                    <label for="credit_card">
                        <img src="<?= SITE_URL ?>/assets/images/visa-mastercard.png" alt="Visa/MasterCard">
                        <span><?= $language->get('credit_card') ?></span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn"><?= $language->get('confirm_payment') ?></button>
            </div>
        </form>
    </div>

<?php
$includeQRCode = false;
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
//require_once __DIR__ . '/includes/footer.php';
?>