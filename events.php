<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Database.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Event.php';
//include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$eventId = (int)$_GET['id'];
$db = new Database();
$event = new Event($db);
$eventDetails = $event->getEventById($eventId);
$ticketZones = $event->getTicketZones($eventId);
$pricingData = $event->getTicketPricing($eventId);

if (!$eventDetails) {
    header("Location: index.php");
    exit;
}

$pageTitle = $eventDetails['title'];
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_header.php';
?>

    <div class="event-detail">
        <img src="<?= htmlspecialchars($eventDetails['image_url']) ?>" alt="<?= htmlspecialchars($eventDetails['title']) ?>" class="event-image">

        <h1><?= htmlspecialchars($eventDetails['title']) ?></h1>
        <p class="date"><i class="far fa-calendar-alt"></i> <?= $language->get('event_date') ?>: <?= date('Y-m-d H:i', strtotime($eventDetails['event_date'])) ?></p>
        <p class="location"><i class="fas fa-map-marker-alt"></i> <?= $language->get('event_location') ?>: <?= htmlspecialchars($eventDetails['location']) ?></p>
        <?php if ($eventDetails['venue']): ?>
            <p class="venue"><i class="fas fa-building"></i> <?= $language->get('event_venue') ?>: <?= htmlspecialchars($eventDetails['venue']) ?></p>
        <?php endif; ?>

        <div class="description">
            <h2><?= $language->get('event_description') ?></h2>
            <p><?= nl2br(htmlspecialchars($eventDetails['description'])) ?></p>
        </div>

        <div class="ticket-info">
            <h2><?= $language->get('ticket_info') ?></h2>

            <div class="pricing-table">
                <table>
                    <thead>
                    <tr>
                        <th><?= $language->get('price_category') ?></th>
                        <th>Cat 1</th>
                        <th>Cat 2</th>
                        <th>Cat 3</th>
                        <th>Cat 4</th>
                        <th>Restricted View (Cat 4)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pricingData as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['zone_name']) ?></td>
                            <td><?= $row['zone_category'] == 'cat1' ? $row['base_price'] : '--' ?></td>
                            <td><?= $row['zone_category'] == 'cat2' ? $row['base_price'] : '--' ?></td>
                            <td><?= $row['zone_category'] == 'cat3' ? $row['base_price'] : '--' ?></td>
                            <td><?= $row['zone_category'] == 'cat4' ? $row['base_price'] : '--' ?></td>
                            <td><?= $row['zone_category'] == 'restricted' ? $row['base_price'] : '--' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3><?= $language->get('available_zones') ?></h3>
            <table class="table table-striped">
<!--                <table class="zones-table">-->
                <thead>
                <tr>
                    <th><?= $language->get('zone') ?></th>
                    <th><?= $language->get('type') ?></th>
                    <th><?= $language->get('price') ?></th>
                    <th><?= $language->get('availability') ?></th>
                    <th><?= $language->get('action') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ticketZones as $zone): ?>
                    <tr>
                        <td><?= htmlspecialchars($zone['zone_name']) ?></td>
                        <td><?=
                            $zone['zone_category'] === 'regular' ? $language->get('regular') :
                                ($zone['zone_category'] === 'vip' ? $language->get('vip') : $language->get('standard'))
                            ?></td>
                        <td>$<?= number_format($zone['base_price'], 2) ?></td>
                        <td><?=
                            $zone['available_seats'] > 10 ? $language->get('available') :
                                ($zone['available_seats'] > 0 ? $language->get('limited_seats') : $language->get('sold_out'))
                            ?></td>
                        <td>
                            <?php if ($zone['available_seats'] > 0): ?>
                                <a href="booking.php?event=<?= $eventId ?>&zone=<?= $zone['zone_id'] ?>" class="btn">
                                    <i class="fas fa-shopping-cart me-1"></i><?= $language->get('book_now') ?>
                                </a>
                            <?php else: ?>
                                <span class="sold-out"><?= $language->get('sold_out') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
$includeQRCode = false;
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/h_footer.php';
?>