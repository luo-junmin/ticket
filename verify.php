<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$message = '';
$ticketInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketCode = trim($_POST['ticket_code']);
    $ticket = new Ticket();
    $result = $ticket->verifyTicket($ticketCode);

    if ($result['success']) {
        $message = '<div class="alert alert-success">'.Language::getInstance()->get('ticket_valid').'</div>';
        $ticketInfo = $result['ticket'];
    } else {
        $message = '<div class="alert alert-danger">'.$result['message'].'</div>';
    }
}
?>

    <div class="container">
        <h1><?= Language::getInstance()->get('ticket_verification') ?></h1>

        <div class="verification-form">
            <?= $message ?>

            <form method="POST">
                <div class="form-group">
                    <label for="ticket_code"><?= Language::getInstance()->get('enter_ticket_code') ?></label>
                    <input type="text" id="ticket_code" name="ticket_code"
                           class="form-control" required placeholder="TICKET-XXXX-XXXX">
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= Language::getInstance()->get('verify_ticket') ?>
                </button>
            </form>
        </div>

        <?php if ($ticketInfo): ?>
            <div class="ticket-details">
                <h2><?= Language::getInstance()->get('ticket_details') ?></h2>
                <p><strong><?= Language::getInstance()->get('event') ?>:</strong> <?= $ticketInfo['title'] ?></p>
                <p><strong><?= Language::getInstance()->get('date') ?>:</strong> <?= date('Y-m-d H:i', strtotime($ticketInfo['event_date'])) ?></p>
                <p><strong><?= Language::getInstance()->get('zone') ?>:</strong> <?= $ticketInfo['zone_name'] ?></p>
            </div>
        <?php endif; ?>

        <div class="scan-instructions">
            <h2><?= Language::getInstance()->get('how_to_scan') ?></h2>
            <ol>
                <li><?= Language::getInstance()->get('scan_step1') ?></li>
                <li><?= Language::getInstance()->get('scan_step2') ?></li>
                <li><?= Language::getInstance()->get('scan_step3') ?></li>
                <li><?= Language::getInstance()->get('scan_step4') ?></li>
            </ol>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>