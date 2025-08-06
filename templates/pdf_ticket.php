<?php
// templates/pdf_ticket.php
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .ticket { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
        .ticket-header { text-align: center; margin-bottom: 20px; }
        .ticket-info, .ticket-qr { display: inline-block; vertical-align: top; }
        .ticket-info { width: 60%; }
        .ticket-qr { width: 30%; text-align: right; }
    </style>
</head>
<body>
    <div class="ticket-header">
        <h1><?= htmlspecialchars($tickets[0]['title']) ?></h1>
<p>日期: <?= date('Y-m-d H:i', strtotime($tickets[0]['event_date'])) ?></p>
<p>地点: <?= htmlspecialchars($tickets[0]['location']) ?></p>
</div>

<?php foreach ($tickets as $t): ?>
    <div class="ticket">
        <div class="ticket-info">
            <h3>票号 #<?= $t['ticket_code'] ?></h3>
            <p><strong>区域:</strong> <?= $t['zone_name'] ?></p>
            <p><strong>类别:</strong> <?= strtoupper($t['zone_category']) ?></p>
            <p><strong>价格:</strong> SGD <?= number_format($t['price_per_ticket'], 2) ?></p>
            <?php if ($t['discount_name']): ?>
                <p><strong>折扣:</strong> <?= $t['discount_name'] ?></p>
            <?php endif; ?>
        </div>
        <div class="ticket-qr">
            <img src="<?= $_SERVER['DOCUMENT_ROOT'].PUBLIC_PATH.$t['qr_code_path'] ?>" width="150">
        </div>
    </div>
<?php endforeach; ?>
</body>
</html>