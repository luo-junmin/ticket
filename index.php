<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Event.php';
require_once __DIR__ . '/classes/Language.php';
//global $language;
$language = new Language();
$lang = $language;
//print_r($_SESSION);
$pageTitle = $language->get('home');


$db = new Database();
$event = new Event();
$events = $event->getActiveEvents();
require_once __DIR__ . '/includes/h_header.php';
echo "<br>";
?>

    <div class="search-box">
        <input type="text" id="event-search" placeholder="<?= $language->get('search_events') ?>...">
        <img src="assets/images/icons8-search-24.png">
    </div>

    <h1><?= $language->get('upcoming_events') ?></h1>

    <div class="event-list">
        <?php if (empty($events)): ?>
            <p><?= $language->get('no_events_found') ?></p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <img src="<?= htmlspecialchars($event['image_url']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <div class="event-info">
                        <h2><a href="events.php?id=<?= $event['event_id'] ?>"><?= htmlspecialchars($event['title']) ?></a></h2>
                        <p class="date"><i class="far fa-calendar-alt"></i> <?= $language->get('event_date') ?>: <?= date('Y-m-d H:i', strtotime($event['event_date'])) ?></p>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> <?= $language->get('event_location') ?>: <?= htmlspecialchars($event['location']) ?></p>
                        <p class="price-range"><i class="fas fa-tag"></i> <?= $language->get('price_range') ?>: $<?= $event['min_price'] ?> - $<?= $event['max_price'] ?></p>
                        <a href="events.php?id=<?= $event['event_id'] ?>" class="btn">
                            <i class="fas fa-shopping-cart me-1"></i>
                            <?= $language->get('book_now') ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/includes/h_footer.php'; ?>