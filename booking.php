<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Event.php';
require_once __DIR__ . '/classes/Language.php';
require_once __DIR__ . '/classes/User.php';
//require_once __DIR__ . '/api/auth.php';
require_once __DIR__ . '/includes/h_header.php';

$showLoginModal = false; // 根据业务逻辑设置该值

if (!isset($_SESSION['user_id'])) {
    $showLoginModal = true; // 根据业务逻辑设置该值
}

if (!isset($_GET['event']) || !isset($_GET['zone'])) {
    header("Location: index.php");
    exit;
}

$eventId = (int)$_GET['event'];
$zoneId = (int)$_GET['zone'];

$event = new Event();
$eventDetails = $event->getEventById($eventId);
$zoneDetails = $event->getZoneDetails($zoneId)[0];
$userDiscounts = (new User())->getUserDiscounts($_SESSION['user_id']);
//trigger_error(print_r($_SESSION, true));

if (!$eventDetails || !$zoneDetails) {
    header("Location: index.php");
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int)$_POST['quantity'];
    $discountId = (int)$_POST['discount_id'];

    $result = $event->createOrder(
        $_SESSION['user_id'],
        $eventId,
        $zoneId,
        $quantity,
        $discountId
    );

    if ($result['success']) {
        header("Location: payment.php?order_id=" . $result['order_id']);
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

    <div class="container">
        <h1><?= Language::getInstance()->get('book_tickets') ?></h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="booking-container">
            <div class="event-summary">
                <h2><?= htmlspecialchars($eventDetails['title']) ?></h2>
                <p><strong><?= Language::getInstance()->get('date') ?>:</strong>
                    <?= date('Y-m-d H:i', strtotime($eventDetails['event_date'])) ?></p>
                <p><strong><?= Language::getInstance()->get('zone') ?>:</strong>
                    <?= htmlspecialchars($zoneDetails['zone_name']) ?></p>
                <p><strong><?= Language::getInstance()->get('category') ?>:</strong>
                    <?= strtoupper($zoneDetails['zone_category']) ?></p>
                <p><strong><?= Language::getInstance()->get('base_price') ?>:</strong>
                    SGD <?= number_format($zoneDetails['base_price'], 2) ?></p>
            </div>

            <form method="POST" class="booking-form">
                <div class="form-group">
                    <label for="quantity"><?= Language::getInstance()->get('quantity') ?>:</label>
                    <input type="number" id="quantity" name="quantity"
                           min="1" max="<?= $zoneDetails['available_seats'] ?>"
                           required class="form-control">
                </div>

                <div class="form-group">
                    <label for="discount_id"><?= Language::getInstance()->get('discount') ?>:</label>
                    <select id="discount_id" name="discount_id" class="form-control" required>
                        <option value="0"><?= Language::getInstance()->get('standard_price') ?></option>
                        <?php foreach ($userDiscounts as $discount): ?>
                            <option value="<?= $discount['discount_id'] ?>">
                                <?= htmlspecialchars($discount['name']) ?>
                                <?php if ($discount['discount_percent'] > 0): ?>
                                    (<?= $discount['discount_percent'] ?>% <?= Language::getInstance()->get('discount') ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <br>
                    <button type="submit" class="btn btn-primary">
                        <?= Language::getInstance()->get('proceed_to_payment') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        <?php if ($showLoginModal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        });
        <?php endif; ?>
    </script>

<?php require_once __DIR__ . '/includes/h_footer.php'; ?>