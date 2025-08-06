<?php
/**
 * gpt/tv.php
 *  Real-time QR Ticket Validation
 *
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 数据库配置
    $db_host = DB_HOST;
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;

    $ticket_code = $_POST['ticket_code'];
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM tickets WHERE ticket_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ticket_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();

    header('Content-Type: application/json');
    $data = array();
    if (!$ticket) {
        $data = json_encode(['status' => 'invalid']);
    } elseif ($ticket['is_used']) {
        $data = json_encode(['status' => 'used', 'used_at' => $ticket['used_at']]);
    } else {
        $update = $conn->prepare("UPDATE tickets SET is_used = 1, used_at = NOW() WHERE ticket_id = ?");
        $update->bind_param("i", $ticket['ticket_id']);
        $update->execute();
        $data = json_encode(['status' => 'valid']);
    }
    echo $data;
    $conn->close();
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
        body {
            font-family: sans-serif;
            text-align: center;
        }

        #result {
            font-size: 1.2em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<h2>🎫 实时扫码验票<br>Real-time QR Ticket Validation</h2>
<div id="reader" style="width:300px; margin:auto;"></div>
<div id="result"></div>

<!-- 手动输入区域 -->
<div id="manualEntry" style="margin-top: 20px; display: none;">
    <h3 id="manualTitle">手动输入票号 / Manual Entry</h3>
    <input type="text" id="manualTicketCode" placeholder="输入票号 / Enter ticket code">
    <button id="validateBtn">验证 / Validate</button>

    <div id="batchEntry">
        <textarea id="batchTicketCodes" placeholder="批量输入票号，每行一个 / Enter multiple codes, one per line"></textarea>
        <button id="batchBtn">批量验证 / Batch Validate</button>
    </div>
</div>

<!-- 控制按钮 -->
<button id="toggleManualBtn">↕️ 切换手动输入 / Toggle Manual Entry</button>

<script>
    function showResult(msg, color) {
        const result = document.getElementById("result");
        result.innerHTML = msg;
        result.style.color = color;
    }

    function validateTicket(code) {
        // 1. 验证输入
        if (!code || typeof code !== 'string') {
            showResult("❌ 票码无效 / Invalid Ticket Code", "red");
            return;
        }

        // 2. 使用URLSearchParams更安全地构建请求体
        const params = new URLSearchParams();
        params.append('ticket_code', code);

        fetch("tv.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest' // 标识AJAX请求
            },
            body: params
        })
        .then(response => {
            // 3. 检查HTTP状态码
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // alert(response.json());
            return response.json();
        })
        .then(data => {
            // 4. 验证响应数据结构
            if (!data) {
                // if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format');
            }

            // 5. 处理不同状态
            switch(data.status) {
                case 'valid':
                    showResult("✅ 验票成功 / Ticket Valid", "green");
                    break;
                case 'used':
                    const time = data.used_at ? `时间: ${data.used_at}` : '';
                    showResult(`⚠️ 此票已使用 / Already Used<br>${time}`, "orange");
                    break;
                default:
                    showResult("❌ 无效票码 / Invalid Ticket", "red");
            }
        })
        .catch(error => {
            console.error('验票请求失败:', error);
            showResult("⚠️ 系统错误，请重试 / System Error", "red");
        });
    }

    const qrCodeScanner = new Html5Qrcode("reader");
    qrCodeScanner.start(
        {facingMode: "environment"},
        {
            fps: 10,
            qrbox: {width: 250, height: 250}
        },
        qrCodeMessage => {
            qrCodeScanner.stop(); // 扫到后暂停
            console.log(qrCodeMessage);
            validateTicket(qrCodeMessage);
            setTimeout(() => location.reload(), 5000); // 4秒后重启
        }
    );

    // -----

    function initManualToggle() {
        const toggleBtn = document.getElementById('toggleManualBtn');
        if (toggleBtn) {
            // Remove any existing listeners to prevent duplication
            toggleBtn.removeEventListener('click', toggleManualEntry.bind(this));
            // Add the listener properly
            toggleBtn.addEventListener('click', () => toggleManualEntry());
        }
    }

    function toggleManualEntry() {
        const showManual = manualSection.style.display === 'block';

        // Toggle visibility
        manualSection.style.display = showManual ? 'none' : 'block';

        // Focus if showing manual entry
        if (!showManual) {
            document.getElementById('manualTicketCode')?.focus();
        }

        // Prevent default and stop propagation to avoid double triggering
        return false;
    }

    const manualSection = document.getElementById('manualEntry');
    initManualToggle();
</script>
</body>
</html>
