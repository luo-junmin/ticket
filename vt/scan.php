<?php
/**
 * vt/scan.php
 */
// 加载配置文件
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 生成动态密钥
$dynamicKey = hash('sha256', API_KEY . $_SERVER['REMOTE_ADDR']);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎫 实时扫码验票 / Live Ticket Validation</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="css/scan.css">
</head>
<body>
<!-- 语言切换 -->
<div class="language-switcher">
<!--    <button class="language-btn active" onclick="scanner.setLanguage('zh')">中文</button>-->
<!--    <button class="language-btn" onclick="scanner.setLanguage('en')">English</button>-->
    <!-- 修改后 -->
    <button class="language-btn active" id="lang-zh">中文</button>
    <button class="language-btn" id="lang-en">English</button>
</div>

<!-- 主标题 -->
<div class="header">
    <h1>🎫 <span id="title">实时扫码验票</span></h1>
    <p id="instructions">请将二维码置于扫描框内 / Please position QR code in the frame</p>
</div>

<!-- 扫描器容器 -->
<div id="scanner-container">
    <div id="reader"></div>
    <div id="result"></div>
    <button id="restartBtn" class="restart-btn">
        <span id="restartText">重新扫描 / Scan Again</span>
    </button>
</div>

    <!-- 手动输入区域 -->
<!--    <div id="manualEntry">-->
    <div id="manualEntry" style="margin-top: 20px; display: none;">
        <h3 id="manualTitle">手动输入票号 / Manual Entry</h3>
        <input type="text" id="manualTicketCode" placeholder="输入票号 / Enter ticket code">
        <button id="validateBtn">验证 / Validate</button>

        <div id="batchEntry">
            <textarea id="batchTicketCodes" placeholder="批量输入票号，每行一个 / Enter multiple codes, one per line"></textarea>
            <button id="batchBtn">批量验证 / Batch Validate</button>
        </div>
    </div>

    <!-- 加载指示器 -->
    <div id="scanner-loading">
        <div class="spinner"></div>
        <p>初始化摄像头...</p>
    </div>

<!-- 控制按钮 -->
<button id="toggleManualBtn">↕️ 切换手动输入 / Toggle Manual Entry</button>
<button id="switchCameraBtn">切换摄像头 / Switch Camera</button>
<button id="reinit-btn">重新初始化摄像头</button>

<!-- 调试面板 -->
<div id="debug-panel">
    <h3>调试日志</h3>
    <div id="debug-log"></div>
</div>

<script>
    // 全局配置
    const CONFIG = {
        API: {
            key: "<?php echo $dynamicKey; ?>",
            clientIp: "<?php echo $_SERVER['REMOTE_ADDR']; ?>"
        },
        CAMERA: {
            preferred: 'environment',
            fallback: 'user',
            qrbox: { width: 250, height: 250 },
            fps: 10
        }
    };
</script>

<script src="js/scanner-core.js"></script>
<script src="js/scanner-ui.js"></script>
<script src="js/scanner-init.js"></script>
</body>
</html>
