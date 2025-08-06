<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 数据库配置
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

//$pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8', 'your_user', 'your_pass');
$pdo = new PDO('mysql:host = $db_host; dbname = $db_name; charset=utf8', $db_user, $db_pass);
$stmt = $pdo->query("SELECT t.ticket_code, o.order_id, t.is_used, t.used_at
                     FROM tickets t
                     JOIN orders o ON t.order_id = o.order_id
                     ORDER BY t.used_at DESC");
var_dump($pdo);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>验票记录 / Ticket Scan Records</title>
    <style>
        table { border-collapse: collapse; width: 90%; margin: auto; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        tr.used { background-color: #e0ffe0; }
        tr.unused { background-color: #ffe0e0; }
    </style>
</head>
<body>
<h2 style="text-align:center;">🎫 验票记录 / Ticket Scan Records</h2>
<table>
    <thead>
    <tr>
        <th>票码 / Ticket Code</th>
        <th>订单号 / Order ID</th>
        <th>是否已使用 / Used?</th>
        <th>使用时间 / Used At</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tickets as $t): ?>
        <tr class="<?= $t['is_used'] ? 'used' : 'unused' ?>">
            <td><?= htmlspecialchars($t['ticket_code']) ?></td>
            <td><?= htmlspecialchars($t['order_id']) ?></td>
            <td><?= $t['is_used'] ? '✅ 是 / Yes' : '❌ 否 / No' ?></td>
            <td><?= $t['used_at'] ?: '-' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
