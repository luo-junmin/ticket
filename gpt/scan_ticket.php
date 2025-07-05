<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 数据库配置
    $db_host = DB_HOST;
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;

    $ticket_code = $_POST['ticket_code'];
    $pdo = new PDO('mysql:host = $db_host; dbname = $db_name; charset=utf8', $db_user, $db_pass);
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_code = ?");
    $params = array($ticket_code);
//    $stmt->bindParam(':ticket_code', $ticket_code);
    trigger_error(print_r($stmt,true));
    $stmt->execute($params);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    trigger_error(print_r($ticket,true));

    header('Content-Type: application/json');
    if (!$ticket) {
        echo json_encode(['status' => 'invalid']);
    } elseif ($ticket['is_used']) {
        echo json_encode(['status' => 'used', 'used_at' => $ticket['used_at']]);
    } else {
        $update = $pdo->prepare("UPDATE tickets SET is_used = 1, used_at = NOW() WHERE ticket_id = ?");
        $update->execute([$ticket['ticket_id']]);
        echo json_encode(['status' => 'valid']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>实时扫码验票 / Live Ticket Scan</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body { font-family: sans-serif; text-align: center; }
        #result { font-size: 1.2em; margin-top: 10px; }
    </style>
</head>
<body>
<h2>🎫 实时扫码验票<br>Real-time QR Ticket Validation</h2>
<div id="reader" style="width:300px; margin:auto;"></div>
<div id="result"></div>

<script>
    function showResult(msg, color) {
        const result = document.getElementById("result");
        result.innerHTML = msg;
        result.style.color = color;
    }

    function validateTicket(code) {
        fetch("scan_ticket.php", {
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: "ticket_code=" + encodeURIComponent(code)
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'valid') {
                    showResult("✅ 验票成功 / Ticket Valid", "green");
                } else if (data.status === 'used') {
                    showResult("⚠️ 此票已使用 / Already Used<br>时间: " + data.used_at, "orange");
                } else {
                    showResult("❌ 无效票码 / Invalid Ticket", "red");
                }
            });
    }

    const qrCodeScanner = new Html5Qrcode("reader");
    qrCodeScanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        qrCodeMessage => {
            qrCodeScanner.stop(); // 扫到后暂停
            console.log(qrCodeMessage);
            validateTicket(qrCodeMessage);
            setTimeout(() => location.reload(), 4000); // 4秒后重启
        }
    );
</script>
</body>
</html>
