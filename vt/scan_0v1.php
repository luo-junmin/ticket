<?php
// 加载配置文件
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';

// 生成动态密钥（示例：密钥+IP的哈希）
$dynamicKey = hash('sha256', API_KEY . $_SERVER['REMOTE_ADDR']);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎫 实时扫码验票 / Live Ticket Validation</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .header {
            margin-bottom: 30px;
        }
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        #result {
            padding: 15px;
            margin: 20px auto;
            border-radius: 8px;
            max-width: 500px;
            transition: all 0.3s ease;
        }
        .valid {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .invalid {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .used {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .error {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        .language-switcher {
            margin-bottom: 20px;
        }
        .language-btn {
            padding: 8px 15px;
            margin: 0 5px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .language-btn.active {
            background-color: #007bff;
        }
        .restart-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
            display: none;
        }

        #scanner-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        #reader {
            min-height: 300px;
            background: #f0f0f0;
        }

        .spinner {
            border: 4px solid rgba(0,0,0,0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #09f;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>
<div class="language-switcher">
    <button class="language-btn active" onclick="setLanguage('zh')">中文</button>
    <button class="language-btn" onclick="setLanguage('en')">English</button>
</div>

<div class="header">
    <h1>🎫 <span id="title">实时扫码验票</span></h1>
    <p id="instructions">请将二维码置于扫描框内 / Please position QR code in the frame</p>
</div>

<div id="scanner-container">

    <div id="reader"></div>
    <div id="result"></div>
    <button id="restartBtn" class="restart-btn" onclick="restartScanner()">
        <span id="restartText">重新扫描 / Scan Again</span>
    </button>

    <div id="worker-scanner" style="display: none;"></div>

    <div id="manualEntry" style="margin-top: 20px; display: none;">
        <h3 id="manualTitle">手动输入票号 / Manual Entry</h3>
        <input type="text" id="manualTicketCode" placeholder="输入票号 / Enter ticket code">
        <button onclick="validateManualEntry()" id="validateBtn">验证 / Validate</button>
        <div id="batchEntry" style="margin-top: 10px;">
            <textarea id="batchTicketCodes" placeholder="批量输入票号，每行一个 / Enter multiple codes, one per line"></textarea>
            <button onclick="validateBatch()" id="batchBtn">批量验证 / Batch Validate</button>
        </div>
    </div>

    <!-- 加载指示器 -->
    <div id="scanner-loading style="display:none;">
        <div class="spinner"></div>
        <p>初始化摄像头...</p>
    </div>

    <!-- 控制按钮 -->
    <button id="reinit-btn">重新初始化摄像头</button>
    <button id="switch-btn" style="display:none;">切换摄像头</button>
</div>

<!--</div>-->

<button onclick="toggleManualEntry()" id="toggleManualBtn" style="margin-top: 20px;">
    ↕️ 切换手动输入 / Toggle Manual Entry
</button>

<button id="switchCameraBtn" style="margin-top: 10px;">

    切换摄像头 / Switch Camera
</button>

<div id="debug-panel" style="position:fixed; bottom:0; left:0; background:rgba(0,0,0,0.8); color:white; padding:10px; z-index:1000; max-height:200px; overflow:auto;">
    <h3>调试日志</h3>
    <div id="debug-log"></div>
</div>

<script>
    const API_CONFIG = {
        apiKey: "<?php echo hash('sha256', API_KEY . $_SERVER['REMOTE_ADDR']); ?>",
        clientIp: "<?php echo $_SERVER['REMOTE_ADDR']; ?>"
    };

    // 摄像头配置
    const cameraConfig = {
        preferredCamera: 'environment', // 强制后置摄像头
        fallbackCamera: 'user',        // 备用前置摄像头
        qrbox: { width: 250, height: 250 },
        fps: 10,

        // 支持的facingMode值
        facingModes: ['environment', 'user']
    };
    const qrScannerConfig = {
        // 支持的格式
        formatsToSupport: [
            Html5QrcodeSupportedFormats.QR_CODE
        ],

        // 扫描设置
        fps: 10,
        qrbox: 250,
        disableFlip: false,

        // 摄像头配置（二选一）
        cameraConfig: {
            // 选项1：使用facingMode
            facingMode: "environment", // "environment"或"user"

            // 选项2：或使用deviceId
            deviceId: "deviceId"
        }
    };



    // 在全局变量中存储Worker实例
    let scanWorker = null;
    let currentCameraIndex = 0;
    let availableCameras = [];

//-----
//     class ScannerManager {
//         constructor() {
//             this.instance = null;
//             this.stream = null;
//         }
//
//         async init(containerId = "reader") {
//             await this.cleanup();
//
//             // 创建干净的容器
//             const container = document.getElementById(containerId);
//             container.innerHTML = '<div id="scanner-inner"></div>';
//
//             this.instance = new Html5Qrcode("scanner-inner");
//             return this.instance;
//         }
//
//         async cleanup() {
//             // 停止扫描器
//             if (this.instance) {
//                 try {
//                     if (this.instance.isScanning) {
//                         await this.instance.stop();
//                     }
//                 } catch (error) {
//                     console.warn("停止扫描器时警告:", error);
//                 }
//                 this.instance = null;
//             }
//
//             // 停止媒体流
//             if (this.stream) {
//                 this.stream.getTracks().forEach(track => track.stop());
//                 this.stream = null;
//             }
//
//             // 完全移除视频元素
//             const videos = document.querySelectorAll("video");
//             videos.forEach(video => {
//                 if (video.parentNode) {
//                     video.parentNode.removeChild(video);
//                 }
//             });
//         }
//
//         async start(config) {
//             await this.cleanup();
//             const html5QrCode = await this.init();
//             try {
//                 // 验证配置
//                 if (!isValidCameraConfig(config.cameraIdOrConfig)) {
//                     throw new Error("无效的摄像头配置");
//                 }
//
//                 return await html5QrCode.start(
//                     config.cameraIdOrConfig,
//                     config.scanConfig,
//                     config.qrCodeSuccessCallback,
//                     config.qrCodeErrorCallback
//                 );
//             } catch (error) {
//                 await this.cleanup();
//                 throw error;
//             }
//
//         }
//     }
    class ScannerManager {
        constructor() {
            this.instance = null;
            this.isInitializing = false;
        }

        async getScanner() {
            if (this.instance) return this.instance;

            if (this.isInitializing) {
                await new Promise(resolve => {
                    const check = () => {
                        if (!this.isInitializing) resolve();
                        else setTimeout(check, 100);
                    };
                    check();
                });
                return this.instance;
            }

            this.isInitializing = true;
            try {
                this.instance = new Html5Qrcode("reader");
                return this.instance;
            } finally {
                this.isInitializing = false;
            }
        }

        async cleanup() {
            if (this.instance) {
                try {
                    if (this.instance.isScanning) {
                        await this.instance.stop();
                    }
                } catch (error) {
                    console.warn("停止扫描器时警告:", error);
                }
                this.instance = null;
            }
        }
    }

    // 全局单例
    const scannerManager = new ScannerManager();
//-----
    // 获取所有摄像头
    async function getCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableCameras = devices.filter(device => device.kind === 'videoinput');

            // 自动识别后置摄像头
            const backIndex = availableCameras.findIndex(d =>
                d.label.toLowerCase().includes('back') ||
                d.label.toLowerCase().includes('rear')
            );

            if (backIndex !== -1) currentCameraIndex = backIndex;

            return availableCameras.length > 1;

            // return availableCameras;
        } catch (error) {
            console.error('Error getting cameras:', error);
            return [];
        }
    }

    // 切换摄像头
    let currentCamera = null;

    async function switchCamera() {
        try {
            if (!window.html5QrCode?.isScanning) return;

            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');

            if (videoDevices.length < 2) {
                alert("未检测到多个摄像头");
                return;
            }

            // 确定下一个摄像头
            const currentDeviceId = currentCamera?.deviceId ||
                (window.html5QrCode.getRunningTrackSettings()?.deviceId);

            let nextIndex = 0;
            if (currentDeviceId) {
                const currentIndex = videoDevices.findIndex(
                    d => d.deviceId === currentDeviceId
                );
                nextIndex = (currentIndex + 1) % videoDevices.length;
            }

            const nextCamera = {
                deviceId: videoDevices[nextIndex].deviceId,
                label: videoDevices[nextIndex].label || `摄像头${nextIndex + 1}`
            };

            // 重新启动扫描器
            await startScannerWithDevice(nextCamera);

            currentCamera = nextCamera;
            updateCameraButtonText();

        } catch (error) {
            console.error("切换摄像头失败:", error);
            alert("切换摄像头失败，请重试");
        }
    }

    async function startScannerWithDevice(camera) {
        if (window.html5QrCode?.isScanning) {
            await window.html5QrCode.stop();
        }

        if (!window.html5QrCode) {
            window.html5QrCode = new Html5Qrcode("reader");
        }

        await window.html5QrCode.start(
            camera.deviceId,
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            qrCodeMessage => {
                validateTicket(qrCodeMessage);
            }
        );
    }

    // 更新按钮文本
    function updateCameraButtonText() {
        const btn = document.getElementById('switchCameraBtn');
        if (!btn || !currentCamera) return;

        const isBackCamera = /back|rear|environment/i.test(currentCamera.label);

        btn.textContent = isBackCamera ?
            "切换到前置摄像头" :
            "切换到后置摄像头";
    }

    function toggleManualEntry() {
        const scannerDiv = document.getElementById('scanner-container');
        const manualDiv = document.getElementById('manual-input');

        if (scannerDiv && manualDiv) {
            const showManual = scannerDiv.style.display !== 'none';
            scannerDiv.style.display = showManual ? 'none' : 'block';
            manualDiv.style.display = showManual ? 'block' : 'none';

            if (!showManual) {
                document.getElementById('manual-ticket-code').focus();
            }
        }
    }

    // 初始化时获取摄像头
    document.getElementById('switchCameraBtn').addEventListener('click', switchCamera);

    // 在页面加载时获取摄像头列表
    window.addEventListener('load', async () => {
        await getCameras();
        if (availableCameras.length > 1) {
            document.getElementById('switchCameraBtn').style.display = 'inline-block';
        }
    });

    // 启动Worker扫描器
    function startWorkerScanner() {
        // 如果Worker已经存在，先终止
        if (scanWorker) {
            scanWorker.terminate();
        }

        // 创建新的Web Worker
        scanWorker = new Worker('scan-worker.js');

        // 处理Worker返回的消息
        scanWorker.onmessage = function(e) {
            const { status, result, error } = e.data;

            switch(status) {
                case 'success':
                    // 扫描到二维码，验证票证
                    validateTicket(result);
                    stopWorkerScanner(); // 扫描成功后暂停
                    break;

                case 'scanner_started':
                    console.log('Scanner started in worker');
                    break;

                case 'scanner_stopped':
                    console.log('Scanner stopped in worker');
                    break;

                case 'error':
                    console.error('Worker error:', error);
                    document.getElementById('result').innerHTML = `
                      <p class="error">Scanner error: ${error}</p>
                    `;
                    break;
            }
        };

        // 发送启动命令给Worker
        scanWorker.postMessage({
            action: 'start',
            config: {
                cameraId: cameraId,  // 传入特定的摄像头ID
                fps: 10,
                qrbox: { width: 250, height: 250 }
            }
        });
    }

    // 停止Worker扫描器
    function stopWorkerScanner() {
        if (scanWorker) {
            scanWorker.postMessage({ action: 'stop' });
        }
    }

    // 修改页面加载时的初始化
    window.onload = function() {
        // 检查浏览器是否支持Web Worker
        if (window.Worker) {
            console.log('Using Web Worker for scanning');
            startWorkerScanner();
        } else {
            console.log('Web Workers not supported, falling back to main thread');
            // 回退到非Worker实现
            startMainThreadScanner();
        }
    };

    // 页面卸载时清理Worker
    window.onbeforeunload = function() {
        stopWorkerScanner();
    };

    // 重新启动扫描器
    function restartScanner() {
        if (window.Worker) {
            startWorkerScanner();
        } else {
            startMainThreadScanner();
        }
        document.getElementById('result').innerHTML = '';
        document.getElementById('restartBtn').style.display = 'none';
    }

    // 主线程扫描实现（用于不支持Worker的浏览器）
    function startMainThreadScanner() {
        const qrCodeScanner = new Html5Qrcode("reader");

        qrCodeScanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            qrCodeMessage => {
                qrCodeScanner.stop();
                validateTicket(qrCodeMessage);
            },
            errorMessage => {
                console.error(errorMessage);
                document.getElementById('result').innerHTML = `
        <p class="error">${translations[currentLang].noCamera}</p>
      `;
            }
        ).catch(err => {
            console.error(err);
            document.getElementById('result').innerHTML = `
      <p class="error">${translations[currentLang].noCamera}</p>
    `;
        });
    }

    function validateManualEntry() {
        const code = document.getElementById('manualTicketCode').value.trim();
        if (code) {
            validateTicket(code);
        }
    }

    // 批量验票功能
    function validateBatch() {
        const codesText = document.getElementById('batchTicketCodes').value.trim();
        if (!codesText) return;

        const codes = codesText.split('\n').filter(code => code.trim() !== '');
        if (codes.length === 0) return;

        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `<p>验证中 ${codes.length} 张票证 / Validating ${codes.length} tickets...</p>`;

        // 使用Promise.all处理批量验证
        const promises = codes.map(code => {
            return fetch(`scan_ticket.php?lang=${currentLang}`, {
                method: "POST",
                headers: { 'X-API-KEY': 'YOUR_SECURE_API_KEY_123' },
                body: new URLSearchParams({ ticket_code: code.trim() })
            }).then(res => res.json());
        });

        Promise.all(promises).then(results => {
            let validCount = 0, usedCount = 0, invalidCount = 0;

            const resultHTML = results.map(result => {
                if (result.status === 'valid') validCount++;
                else if (result.status === 'used') usedCount++;
                else invalidCount++;

                return `<p>${result.ticket_code || 'N/A'}: ${result.message} ${
                    result.status === 'used' ? 'at ' + result.used_at : ''
                }</p>`;
            }).join('');

            resultDiv.innerHTML = `
            <h3>批量验证结果 / Batch Results</h3>
            <p>有效: ${validCount} | 已使用: ${usedCount} | 无效: ${invalidCount}</p>
            ${resultHTML}
        `;
        });
    }

    // 声音提示
    function playSound(type) {
        const audio = new Audio();
        audio.src = type === 'success' ? 'success.mp3' : 'error.mp3';
        audio.play().catch(e => console.log('Audio playback failed:', e));
    }

    // 语言包
    const translations = {
        en: {
            title: "Live Ticket Validation",
            instructions: "Please position QR code in the frame",
            scanning: "Scanning...",
            valid: "Ticket validated successfully",
            welcome: "Welcome to the event!",
            invalid: "Invalid ticket",
            used: "Ticket already used at {time}",
            error: "System error, please try again",
            restart: "Scan Again",
            noCamera: "Camera access denied or not available"
        },
        zh: {
            title: "实时扫码验票",
            instructions: "请将二维码置于扫描框内",
            scanning: "扫描中...",
            valid: "票证验证成功",
            welcome: "欢迎参加活动！",
            invalid: "无效票证",
            used: "票证已于 {time} 使用",
            error: "系统错误，请重试",
            restart: "重新扫描",
            noCamera: "无法访问摄像头或摄像头不可用"
        }
    };

    let currentLang = 'zh';
    let qrCodeScanner = null;

    // 设置语言
    function setLanguage(lang) {
        currentLang = lang;
        document.getElementById('title').textContent = translations[lang].title;
        document.getElementById('instructions').textContent = translations[lang].instructions;
        document.getElementById('restartText').textContent = translations[lang].restart;

        // 更新语言按钮状态
        document.querySelectorAll('.language-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent === (lang === 'zh' ? '中文' : 'English')) {
                btn.classList.add('active');
            }
        });
    }

    // 显示结果
    function showResult(data) {
        const resultDiv = document.getElementById('result');
        resultDiv.className = data.status;

        let message = '';
        switch(data.status) {
            case 'valid':
                message = `
                        <h3>✅ ${data.message}</h3>
                        <p><strong>${data.welcome}</strong></p>
                        <p>Ticket Code: ${data.ticket_code}</p>
                    `;
                break;
            case 'used':
                message = `
                        <h3>⚠️ ${data.message}</h3>
                        <p>Used at: ${data.used_at}</p>
                    `;
                break;
            case 'invalid':
                message = `<h3>❌ ${data.message}</h3>`;
                break;
            case 'error':
                message = `<h3>⚠️ ${data.message}</h3>`;
                break;
        }

        resultDiv.innerHTML = message;
        document.getElementById('restartBtn').style.display = 'block';
        // 添加声音提示
        if (data.status === 'valid') {
            playSound('success');
        } else {
            playSound('error');
        }

    }

    // 添加票证缓存
    const ticketCache = new Map();

    // 验证票证
    function validateTicket(code) {
        if (!code) return;

        // 检查缓存
        if (ticketCache.has(code)) {
            const cachedResult = ticketCache.get(code);
            showResult(cachedResult);
            return;
        }

        const resultDiv = document.getElementById('result');
        resultDiv.className = '';
        resultDiv.innerHTML = `<p>${translations[currentLang].scanning}</p>`;

        // 使用URLSearchParams替代FormData
        const params = new URLSearchParams();
        params.append('ticket_code', code);

        fetch(`scan_ticket.php?lang=${currentLang}`, {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-API-KEY': API_CONFIG.apiKey,
                'X-CLIENT-IP': API_CONFIG.clientIp
            },
            body: params
        })
            .then(response => {
                // if (!response.ok) throw new Error('Network response was not ok');
                // return response.json();
                if (!response.ok) {
                    // 尝试获取更详细的错误信息
                    return response.text().then(text => {
                        throw new Error(`${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            // .then(data => showResult(data))
            .then(data => {
                // 缓存结果（5分钟有效期）
                ticketCache.set(code, data);
                setTimeout(() => ticketCache.delete(code), 300000);
                showResult(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showResult({
                    status: 'error',
                    message: translations[currentLang].error
                });
            });
    }

    // 重启扫描器
    function restartScanner() {
        document.getElementById('result').innerHTML = '';
        document.getElementById('restartBtn').style.display = 'none';
        startScanner();
    }

    // 启动扫描器

    // 安全停止扫描器
    async function safeStopScanner() {
        try {
            if (window.html5QrCode?.isScanning) {
                await window.html5QrCode.stop();
                await new Promise(resolve => setTimeout(resolve, 300)); // 添加延迟
            }
        } catch (error) {
            console.warn("停止扫描器时警告:", error);
        }
    }

    // 智能摄像头选择
    // async function selectCamera(availableCameras) {
    //     // 1. 如果有明确配置的deviceId
    //     if (qrScannerConfig.cameraConfig.deviceId) {
    //         return qrScannerConfig.cameraConfig.deviceId;
    //     }
    //
    //     // 2. 尝试通过标签识别后置摄像头
    //     const backCamera = availableCameras.find(cam =>
    //         /back|rear|environment/i.test(cam.label));
    //
    //     if (backCamera) {
    //         return backCamera.deviceId;
    //     }
    //
    //     // 3. 使用配置的facingMode
    //     if (qrScannerConfig.cameraConfig.facingMode) {
    //         return { facingMode: qrScannerConfig.cameraConfig.facingMode };
    //     }
    //
    //     // 4. 默认使用第一个摄像头
    //     if (availableCameras.length > 0) {
    //         return availableCameras[0].deviceId;
    //     }
    //
    //     // 5. 最终回退方案
    //     return { facingMode: "environment" }; // 即使没有摄像头也返回合法值
    //
    //     // throw new Error("未找到可用摄像头");
    // }
    function selectCamera(availableCameras = []) {
        // 确保总是返回有效值
        if (!Array.isArray(availableCameras)) {
            console.error("摄像头列表不是数组，使用默认配置");
            return { facingMode: "user" };
        }

        // 1. 检查是否有可用摄像头
        if (availableCameras.length === 0) {
            console.warn("未检测到摄像头设备，使用默认配置");
            return { facingMode: "user" }; // 确保返回对象而非undefined
        }

        // 2. 优先使用配置中的设备ID
        const configDeviceId = qrScannerConfig.cameraConfig?.deviceId;
        if (configDeviceId) {
            const matchedCamera = availableCameras.find(cam => cam.deviceId === configDeviceId);
            if (matchedCamera) {
                console.log("使用配置的摄像头:", matchedCamera.label);
                return matchedCamera.deviceId; // 返回字符串deviceId
            }
        }

        // 3. 默认使用第一个摄像头（确保存在）
        const firstCamera = availableCameras[0];
        // if (firstCamera && firstCamera.deviceId) {
        //     console.log("使用第一个可用摄像头:", firstCamera.label);
        //     return firstCamera.deviceId; // 返回字符串deviceId
        // }
        if (firstCamera && firstCamera.id) {
            console.log("使用第一个可用摄像头:", firstCamera.label);
            return firstCamera.id; // 返回字符串deviceId
        }

        // 最终回退方案
        console.warn("无法确定摄像头，使用默认facingMode");
        return { facingMode: "user" }; // 确保总是返回有效值
    }

    function validateCameraConfig(config) {
        // 情况1：字符串形式的deviceId
        if (typeof config === 'string' && config.trim().length > 0) {
            return true;
        }

        // 情况2：合法的facingMode对象
        if (typeof config === 'object' && config !== null) {
            const keys = Object.keys(config);
            if (keys.length === 1 && keys[0] === 'facingMode') {
                return ['environment', 'user'].includes(config.facingMode);
            }
        }

        return false;
    }

    async function applyOptimalCameraSettings(html5QrCode) {
        try {
            // 获取当前视频轨道
            const videoElement = document.querySelector("#reader video");
            if (!videoElement) return;

            const stream = videoElement.srcObject;
            const [track] = stream.getVideoTracks();

            if (track && track.getCapabilities) {
                const capabilities = track.getCapabilities();
                const settings = track.getSettings();

                // 设置理想分辨率
                const constraints = {
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                };

                // 自动对焦
                if (capabilities.focusMode && capabilities.focusMode.includes("continuous")) {
                    constraints.focusMode = "continuous";
                }

                await track.applyConstraints(constraints);
            }
        } catch (err) {
            console.warn("摄像头设置优化失败:", err);
        }
    }

//----
    async function startScanner() {
        try {
            const html5QrCode = new Html5Qrcode("reader");

            // 获取可用摄像头
            const cameras = await Html5Qrcode.getCameras();

            // 智能选择摄像头
            const cameraConfig = await selectCamera(cameras);

            // 启动扫描器
            await html5QrCode.start(
                cameraConfig, // 只能包含facingMode或deviceId
                {
                    fps: qrScannerConfig.fps,
                    qrbox: qrScannerConfig.qrbox,
                    formatsToSupport: qrScannerConfig.formatsToSupport,
                    disableFlip: qrScannerConfig.disableFlip
                },
                qrCodeMessage => {
                    handleSuccessfulScan(qrCodeMessage);
                },
                errorMessage => {
                    handleScanError(errorMessage);
                }
            );

            return html5QrCode;
        } catch (err) {
            console.error("扫描器启动失败:", err);
            throw err;
        }
    }

    // 在Html5Qrcode配置中添加实验性功能
    const experimentalFeatures = {
        useBarCodeDetectorIfSupported: true,
        experimentalImagePreprocessing: {
            apply: true,
            config: {
                contrast: 1.2,
                brightness: 1.1,
                grayscale: false,
                sharpen: true
            }
        }
    };

    // 更新配置
    Object.assign(qrScannerConfig, { experimentalFeatures });

    function handleScanError(errorMessage) {
        const errorMap = {
            "No MultiFormat Readers": {
                message: "无法识别二维码",
                solution: "请调整角度和距离，确保二维码清晰可见"
            },
            "ImageData is null": {
                message: "摄像头数据获取失败",
                solution: "请检查摄像头权限或刷新页面"
            },
            "Decoder could not be initialized": {
                message: "解码器初始化失败",
                solution: "请尝试更换浏览器或设备"
            }
        };

        const matchedError = Object.entries(errorMap).find(([key]) =>
            errorMessage.includes(key)
        );

        if (matchedError) {
            const [_, { message, solution }] = matchedError;
            showUserFeedback(`${message}，${solution}`);
        } else {
            console.error("未知扫描错误:", errorMessage);
            showUserFeedback("扫描失败，请重试");
        }

        // 自动重试机制
        if (window.currentScanner && retryCount < 3) {
            retryCount++;
            setTimeout(() => {
                window.currentScanner.resume();
            }, 500);
        }
    }

    async function optimizeCameraSettings() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: "environment",
                    advanced: [
                        { focusMode: "continuous" },
                        { whiteBalanceMode: "continuous" },
                        { exposureMode: "continuous" }
                    ]
                }
            });

            const [track] = stream.getVideoTracks();
            if (track && track.getCapabilities) {
                const capabilities = track.getCapabilities();
                const settings = track.getSettings();

                // 自动对焦设置
                if (capabilities.focusMode && capabilities.focusMode.includes("continuous")) {
                    await track.applyConstraints({ advanced: [{ focusMode: "continuous" }] });
                }

                // 白平衡设置
                if (capabilities.whiteBalanceMode && capabilities.whiteBalanceMode.includes("continuous")) {
                    await track.applyConstraints({ advanced: [{ whiteBalanceMode: "continuous" }] });
                }
            }

            return stream;
        } catch (err) {
            console.warn("摄像头优化设置失败:", err);
            return null;
        }
    }

    // ----

    // 摄像头选择策略
    function getCameraConfig(videoDevices) {
        // 1. 尝试通过标签识别后置摄像头
        const backCamera = videoDevices.find(d =>
            /back|rear|environment/i.test(d.label)
        );

        if (backCamera) {
            return {
                deviceId: backCamera.deviceId,
                description: backCamera.label || "后置摄像头"
            };
        }

        // 2. 尝试通过设备数量判断（通常第二个是后置）
        if (videoDevices.length > 1) {
            return {
                deviceId: videoDevices[1].deviceId,
                description: videoDevices[1].label || "摄像头2"
            };
        }

        // 3. 回退到facingMode选择
        return {
            facingMode: { exact: "environment" },
            description: "默认后置摄像头"
        };
    }

    // 辅助函数：查找后置摄像头
    function findBackCamera(devices) {
        // 优先通过标签识别
        const backCamera = devices.find(d =>
            d.label.toLowerCase().includes('back') ||
            d.label.toLowerCase().includes('rear') ||
            d.label.toLowerCase().includes('environment')
        );

        return backCamera?.deviceId || null;
    }

    // 辅助函数：获取摄像头标签
    function getCameraLabel(devices, deviceId) {
        return devices.find(d => d.deviceId === deviceId)?.label || '未知摄像头';
    }

    // 修改扫描初始化代码
    let isInitializing = false;

    async function initScanner() {
        try {
            showLoading(true);
            forceCleanScannerElements();


            // 1. 获取摄像头列表
            let cameras = [];
            try {
                cameras = await Html5Qrcode.getCameras();
                console.log("检测到摄像头数量:", cameras.length);
            } catch (error) {
                console.warn("获取摄像头列表失败:", error);
            }

            // 2. 选择摄像头
            const cameraConfig = selectCamera(cameras);
            console.log("选择的摄像头配置:", cameraConfig);

            // 3. 验证配置
            if (!validateCameraConfig(cameraConfig)) {
                throw new Error(`无效的摄像头配置: ${JSON.stringify(cameraConfig)}`);
            }

            // 4. 初始化扫描器
            const html5QrCode = new Html5Qrcode("reader");
            await html5QrCode.start(
                cameraConfig,
                {
                    fps: 10,
                    qrbox: 250,
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
                },
                qrCodeMessage => {
                    handleSuccessfulScan(qrCodeMessage);
                },
                errorMessage => {
                    handleScanError(errorMessage);
                }
            );

            // 5. 更新UI状态
            updateCameraUI(cameras);

            return html5QrCode;

        } catch (error) {
            console.error("初始化失败:", error);
            showErrorToUser("摄像头初始化失败，请尝试手动输入");
            toggleManualEntry();
            throw error;
        } finally {
            showLoading(false);
        }
        showLoading(false);

    }

    // async function initScanner() {
    //     // 加锁防止重复执行
    //     if (window._scannerInitializing) return;
    //     window._scannerInitializing = true;
    //
    //     try {
    //         showLoading(true);
    //         forceCleanScannerElements();
    //
    //         console.debug("步骤1：获取摄像头列表");
    //         const cameras = await Html5Qrcode.getCameras().catch(err => {
    //             console.error("获取摄像头失败:", err);
    //             return [];
    //         });
    //         console.debug("获取到的摄像头:", cameras);
    //
    //         console.debug("步骤2：选择摄像头配置");
    //         const cameraConfig = selectCamera(cameras);
    //         console.debug("选择的配置:", cameraConfig);
    //
    //         console.debug("步骤3：初始化扫描器实例");
    //         const html5QrCode = await scannerManager.getScanner();
    //
    //         console.debug("步骤4：启动扫描");
    //         if (!html5QrCode.isScanning) {
    //             await html5QrCode.start(
    //                 cameraConfig,
    //                 { fps: 10, qrbox: 250 },
    //                 qrCodeMessage => {
    //                     console.debug("扫描成功:", qrCodeMessage);
    //                     handleSuccessfulScan(qrCodeMessage);
    //                 },
    //                 errorMessage => {
    //                     console.debug("扫描错误:", errorMessage);
    //                     handleScanError(errorMessage);
    //                 }
    //             );
    //         }
    //
    //         console.debug("步骤5：更新UI");
    //         updateCameraUI(cameras);
    //
    //     } catch (error) {
    //         console.error("初始化错误:", error);
    //         showErrorToUser("初始化失败: " + error.message);
    //         await scannerManager.cleanup();
    //     } finally {
    //         console.debug("初始化完成");
    //         showLoading(false);
    //         window._scannerInitializing = false;
    //     }
    // }

    function setupEventListeners() {
        const reinitBtn = document.getElementById('reinit-btn');
        if (reinitBtn) {
            reinitBtn.removeEventListener('click', initScanner); // 先移除旧的
            reinitBtn.addEventListener('click', () => {
                debugLog("点击重新初始化按钮");
                initScanner().catch(err => debugLog("重新初始化错误:" + err));
            });
        }
    }

    // 在DOM加载后调用
    document.addEventListener('DOMContentLoaded', () => {
        debugLog("DOM加载完成");
        setupEventListeners();
        initScanner().catch(err => debugLog("初始初始化错误:" + err));
    });

    function forceCleanScannerElements() {
        // 清理所有可能的残留视频元素
        const videos = document.querySelectorAll("#reader video");
        videos.forEach(video => {
            try {
                if (video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
                video.remove();
            } catch (error) {
                console.warn("移除视频元素失败:", error);
            }
        });

        // 重置容器
        const reader = document.getElementById("reader");
        if (reader) {
            reader.innerHTML = "";
            reader.style.minHeight = "300px"; // 保持布局稳定
        }
    }

    function showErrorToUser(message) {
        const errorDiv = document.getElementById('scan-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        // 同时打印到控制台
        console.error("用户可见错误:", message);
    }

    function isValidCameraConfig(config) {
        // 检查是否是字符串(deviceId)
        if (typeof config === 'string') return true;

        // 检查是否是合法的facingMode对象
        if (typeof config === 'object' && config !== null) {
            const keys = Object.keys(config);
            return keys.length === 1 &&
                keys[0] === 'facingMode' &&
                ['environment', 'user'].includes(config.facingMode);
        }

        return false;
    }

    function forceCleanScannerElements() {
        // 移除所有可能的残留元素
        const elementsToRemove = [
            ...document.querySelectorAll("#reader video"),
            ...document.querySelectorAll("#reader canvas"),
            ...document.querySelectorAll(".html5-qrcode-element")
        ];

        elementsToRemove.forEach(el => {
            try {
                el.parentNode?.removeChild(el);
            } catch (e) {
                console.warn("移除元素失败:", e);
            }
        });

        // 重置容器
        const container = document.getElementById("reader");
        if (container) {
            container.innerHTML = '<div id="scanner-inner"></div>';
        }
    }

    // 重新初始化按钮
    document.getElementById("reinit-btn").addEventListener("click", async () => {
        // 禁用按钮防止重复点击
        const btn = document.getElementById("reinit-btn");
        btn.disabled = true;
        btn.textContent = "初始化中...";

        try {
            await initScanner();
        } finally {
            btn.disabled = false;
            btn.textContent = "重新初始化摄像头";
        }
    });


    // 初始化函数
    async function initCameraUI() {
        const switchBtn = document.getElementById('switchCameraBtn');
        if (!switchBtn) {
            console.error('切换按钮元素不存在');
            return;
        }

        await getCameras();

        if (availableCameras.length > 1) {
            switchBtn.style.display = 'inline-block';
            switchBtn.addEventListener('click', switchCamera);
        } else {
            switchBtn.style.display = 'none';
        }
    }

    // 在DOM加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 其他初始化代码...
        initCameraUI();
    });

    // 检查摄像头权限
    async function checkCameraPermission() {
        try {
            await navigator.mediaDevices.getUserMedia({ video: true });
            return true;
        } catch {
            return false;
        }
    }

    // function updateCameraUI() {
    //     const switchBtn = document.getElementById('switchCameraBtn');
    //     if (switchBtn) {
    //         switchBtn.style.display =
    //             (window.availableCameras?.length || 0) > 1 ? 'block' : 'none';
    //         updateCameraButtonText();
    //     }
    // }
    function updateCameraUI(cameras = []) {
        const switchBtn = document.getElementById('switch-btn');
        const statusDiv = document.getElementById('camera-status');

        if (cameras.length === 0) {
            if (statusDiv) {
                statusDiv.textContent = "⚠️ 未检测到摄像头，使用默认配置";
                statusDiv.style.display = 'block';
            }
            if (switchBtn) switchBtn.style.display = 'none';
        } else {
            if (statusDiv) statusDiv.style.display = 'none';
            if (switchBtn) {
                switchBtn.style.display = cameras.length > 1 ? 'block' : 'none';
                switchBtn.textContent = `切换摄像头 (${cameras.length}个可用)`;
            }
        }
    }

    async function checkDeviceCompatibility() {
        const result = {
            hasCamera: false,
            isMobile: false,
            isDesktop: false
        };

        try {
            // 检查媒体设备支持
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                throw new Error("浏览器不支持设备枚举");
            }

            // 获取设备列表
            const devices = await navigator.mediaDevices.enumerateDevices();
            result.hasCamera = devices.some(d => d.kind === 'videoinput');

            // 检测设备类型
            const userAgent = navigator.userAgent.toLowerCase();
            result.isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
            result.isDesktop = !result.isMobile;

        } catch (error) {
            console.error("设备检测失败:", error);
        }

        return result;
    }

    async function initializeScannerSystem() {
        // 显示加载状态
        // showLoading(true);

        try {
            // 检查设备能力
            const { hasCamera, isMobile, isDesktop } = await checkDeviceCompatibility();

            if (!hasCamera) {
                throw new Error("未检测到摄像头设备");
            }

            // 根据设备类型调整配置
            if (isDesktop) {
                cameraConfig.preferredFacingMode = 'user'; // 笔记本电脑通常只有前置
                cameraConfig.qrbox.width = 300; // 增大扫描框
            }

            // 初始化扫描器
            await initScanner();

        } catch (error) {
            console.error("系统初始化失败:", error);
            toggleManualEntry();
        } finally {
            // showLoading(false);
        }
    }

    // 页面加载入口
    document.addEventListener('DOMContentLoaded', () => {
        // 首次初始化
        initializeScannerSystem();

        // 添加重新初始化按钮
        const retryBtn = document.createElement('button');
        retryBtn.textContent = '重新初始化扫描器';
        retryBtn.onclick = initializeScannerSystem;
        document.body.appendChild(retryBtn);
    });
//----
    
    // 初始化切换按钮
    function initSwitchButton() {
        const switchBtn = document.getElementById('switchCameraBtn');
        if (!switchBtn) return;

        switchBtn.style.display = availableCameras.length > 1 ? 'block' : 'none';
        switchBtn.addEventListener('click', switchCamera);
        updateCameraButtonText();
    }

    // 页面加载
    document.addEventListener('DOMContentLoaded', () => {
        initScanner();

        // 添加重新加载按钮
        const reloadBtn = document.createElement('button');
        reloadBtn.textContent = '重新初始化摄像头';
        reloadBtn.onclick = initScanner;
        document.body.appendChild(reloadBtn);
    });

    async function checkCameraAvailability() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.some(device => device.kind === 'videoinput');
        } catch (error) {
            console.warn("摄像头检测失败:", error);
            return false;
        }
    }

    // 全局错误处理
    window.addEventListener('error', (event) => {
        console.error("全局捕获的错误:", event.error);

        // 显示用户友好的错误信息
        const errorMessage = `
    <div class="error-alert">
      <h3>系统遇到问题</h3>
      <p>${getUserFriendlyError(event.error)}</p>
      <button onclick="location.reload()">刷新页面</button>
    </div>
  `;

        document.body.insertAdjacentHTML('beforeend', errorMessage);
    });

    function getUserFriendlyError(error) {
        const errorMap = {
            'NotFoundError': '未找到摄像头设备',
            'NotAllowedError': '摄像头访问被拒绝',
            '403': 'API验证失败，请联系管理员',
            'NetworkError': '网络连接出现问题'
        };

        return errorMap[error.name] || errorMap[error.status] || '未知错误，请重试';
    }

    // 设备能力检测
    function checkDeviceCapabilities() {
        return {
            hasCamera: 'mediaDevices' in navigator && 'enumerateDevices' in navigator.mediaDevices,
            isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
            isSecure: window.location.protocol === 'https:'
        };
    }

    // 根据设备能力初始化
    async function initBasedOnDevice() {
        const capabilities = checkDeviceCapabilities();
        // const scannerSection = document.getElementById('scanner-section');
        const scannerSection = document.getElementById('worker-scanner');
        // const manualSection = document.getElementById('manual-input-section');
        const manualSection = document.getElementById('manualEntry');

        if (capabilities.hasCamera && capabilities.isSecure) {
            scannerSection.style.display = 'block';
            manualSection.style.display = 'none';
            try {
                await initScanner();
            } catch (error) {
                scannerSection.style.display = 'none';
                manualSection.style.display = 'block';
            }
        } else {
            scannerSection.style.display = 'none';
            manualSection.style.display = 'block';
            // document.getElementById('camera-warning').style.display = 'block';
        }
    }

//-----
    let isReinitializing = false;

    document.getElementById("reinit-btn").addEventListener("click", async () => {
        if (isReinitializing) return;
        isReinitializing = true;

        const btn = document.getElementById("reinit-btn");
        const originalText = btn.textContent;

        try {
            btn.disabled = true;
            btn.textContent = "初始化中...";

            // 强制清理
            forceCleanScannerElements();
            await scannerManager.cleanup();

            // 重新初始化
            await initScanner();

        } catch (error) {
            console.error("重新初始化失败:", error);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
            isReinitializing = false;
        }
    });

    // 加载状态控制
    function showLoading(show) {
        const loader = document.getElementById('scanner-loading');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    // 扫描成功处理
    function handleSuccessfulScan(decodedText) {
        console.log('扫描成功:', decodedText);
        validateTicket(decodedText);
    }

    // 扫描错误处理
    function handleScanError(errorMessage) {
        console.warn('扫描错误:', errorMessage);
        const errorDiv = document.getElementById('scan-error');
        if (errorDiv) {
            errorDiv.textContent = `扫描错误: ${errorMessage}`;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 3000);
        }
    }

    // 手动输入切换
    function toggleManualEntry() {
        const scanner = document.getElementById('scanner-container');
        const manual = document.getElementById('manual-input');

        if (scanner && manual) {
            const showManual = scanner.style.display !== 'none';
            scanner.style.display = showManual ? 'none' : 'block';
            manual.style.display = showManual ? 'block' : 'none';

            if (showManual) {
                document.getElementById('manual-ticket-code').focus();
            }
        }
    }

    function debugLog(message) {
        const logDiv = document.getElementById('debug-log');
        if (logDiv) {
            logDiv.innerHTML += `<div>[${new Date().toISOString()}] ${message}</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        console.log(message);
    }

    // 页面加载时初始化
    window.addEventListener('DOMContentLoaded', initBasedOnDevice);

    // 初始化
    window.onload = startScanner;
</script>
</body>
</html>