<?php
//require_once 'includes/auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';

if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$orderId = (int)$_GET['order_id'];
$ticket = new Ticket();
$tickets = $ticket->getTicketsByOrder($orderId, $_SESSION['user_id']);

if (empty($tickets)) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['print'])) {
    $pdfPath = $_SERVER['DOCUMENT_ROOT'] . PUBLIC_PATH . '/assets/tickets/order_'.$orderId.'.pdf';
    if (file_exists($pdfPath)) {
        // 防止之前输出任何内容
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="tickets_'.$orderId.'.pdf"');
//        header('Content-Disposition: attachment; filename="tickets_'.$orderId.'.pdf"');
        readfile($pdfPath);
    } else {
        http_response_code(404);
        echo "PDF file not found.";
    }
    exit;
}

?>

    <div class="container">
        <div class="container print-content"> <!-- 添加print-content类 -->
            <div class="ticket-header">
                <h1><?= Language::getInstance()->get('your_tickets') ?></h1>
                <p><?= Language::getInstance()->get('order_number') ?>: #<?= $orderId ?></p>

                <div class="ticket-actions">
                    <button onclick="window.print()" class="btn btn-primary">
                        <?= Language::getInstance()->get('print_tickets') ?>
                    </button>
                    <a href="ticket.php?order_id=<?= $orderId ?>&print=1" class="btn btn-secondary">
                        <?= Language::getInstance()->get('download_pdf') ?>
                    </a>
                </div>
            </div>

            <div class="event-info">
                <h2><?= htmlspecialchars($tickets[0]['title']) ?></h2>
                <p><?= date('Y-m-d H:i', strtotime($tickets[0]['event_date'])) ?></p>
                <p><?= htmlspecialchars($tickets[0]['location']) ?></p>
            </div>

            <div class="tickets-list">
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket" id="ticket-<?= $t['ticket_id'] ?>">
                        <div class="ticket-body">
                            <div class="ticket-info">
                                <h3><?= Language::getInstance()->get('ticket') ?> #<?= $t['ticket_code'] ?></h3>
                                <p><strong><?= Language::getInstance()->get('zone') ?>:</strong> <?= $t['zone_name'] ?></p>
                                <p><strong><?= Language::getInstance()->get('category') ?>:</strong> <?= strtoupper($t['zone_category']) ?></p>
                                <p><strong><?= Language::getInstance()->get('price') ?>:</strong> SGD <?= number_format($t['price_per_ticket'], 2) ?></p>
                                <?php if ($t['discount_name']): ?>
                                    <p><strong><?= Language::getInstance()->get('discount') ?>:</strong> <?= $t['discount_name'] ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="ticket-qr">
                                <img src="<?= SITE_URL.PUBLIC_PATH.$t['qr_code_path'] ?>" alt="QR Code">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="no-print"> <!-- 添加print-content类 -->
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .event-info, .event-info *, .ticket, .ticket * {
                visibility: visible;
            }
            .event-info， .ticket {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .ticket-actions {
                display: none;
            }
        }

    </style>

<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php'; ?>
