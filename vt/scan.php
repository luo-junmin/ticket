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

<button onclick="toggleManualEntry()" id="toggleManualBtn" style="margin-top: 20px;">
    ↕️ 切换手动输入 / Toggle Manual Entry
</button>

<button id="switchCameraBtn" style="margin-top: 10px;">

    切换摄像头 / Switch Camera
</button>

<script>
    const API_CONFIG = {
        apiKey: "<?php echo hash('sha256', API_KEY . $_SERVER['REMOTE_ADDR']); ?>",
        clientIp: "<?php echo $_SERVER['REMOTE_ADDR']; ?>"
    };

    // 摄像头配置
    const cameraConfig = {
        preferredCamera: 'environment', // 强制后置摄像头
        fallbackCamera: 'user',         // 备用前置摄像头
        qrbox: { width: 250, height: 250 },
        fps: 10
    };


    // 在全局变量中存储Worker实例
    let scanWorker = null;
    let currentCameraIndex = 0;
    let availableCameras = [];

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
    async function switchCamera() {
        if (availableCameras.length < 2) return;

        currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
        const cameraId = availableCameras[currentCameraIndex].deviceId;

        try {
            await window.html5QrCode.stop();
            await window.html5QrCode.start(
                cameraId,
                {
                    fps: cameraConfig.fps,
                    qrbox: cameraConfig.qrbox
                },
                qrCodeMessage => {
                    validateTicket(qrCodeMessage);
                }
            );

            updateCameraButtonText();
        } catch (error) {
            console.error("切换摄像头失败:", error);
        }

        // 重启扫描器使用新摄像头
        // if (window.Worker) {
        //     stopWorkerScanner();
        //     startWorkerScanner(cameraId);
        // } else {
        //     if (window.currentScanner) {
        //         window.currentScanner.stop().then(() => {
        //             startMainThreadScanner(cameraId);
        //         });
        //     }
        // }
    }

    // 更新按钮文本
    function updateCameraButtonText() {
        const btn = document.getElementById('switchCameraBtn');
        if (!btn) return;

        const isBackCamera = availableCameras[currentCameraIndex]?.label
            .toLowerCase().includes('back');

        btn.innerHTML = isBackCamera ?
            "切换到前置摄像头" :
            "切换到后置摄像头";
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

    // 手动输入功能
    function toggleManualEntry() {
        const manualDiv = document.getElementById('manualEntry');
        if (manualDiv.style.display === 'none') {
            manualDiv.style.display = 'block';
            document.getElementById('toggleManualBtn').textContent = '↕️ 隐藏手动输入 / Hide Manual Entry';
        } else {
            manualDiv.style.display = 'none';
            document.getElementById('toggleManualBtn').textContent = '↕️ 切换手动输入 / Toggle Manual Entry';
        }
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

        // const formData = new FormData();
        // formData.append('ticket_code', code);
        // console.log(formData);

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

    //启动扫描器
    // function startScanner() {
    //     if (qrCodeScanner && qrCodeScanner.isScanning) {
    //         qrCodeScanner.stop();
    //     }
    //
    //     Html5Qrcode.getCameras().then(cameras => {
    //         if (cameras && cameras.length) {
    //             qrCodeScanner = new Html5Qrcode("reader");
    //
    //             qrCodeScanner.start(
    //                 cameras[0].id,  // 使用第一个摄像头
    //                 {
    //                     fps: 10,
    //                     qrbox: { width: 250, height: 250 },
    //                     disableFlip: false
    //                 },
    //                 qrCodeMessage => {
    //                     qrCodeScanner.stop();
    //                     validateTicket(qrCodeMessage);
    //                 },
    //                 errorMessage => {
    //                     // console.error(errorMessage);
    //                 }
    //             ).catch(err => {
    //                 console.error(err);
    //                 document.getElementById('result').innerHTML = `
    //                         <p class="error">${translations[currentLang].noCamera}</p>
    //                     `;
    //             });
    //         } else {
    //             throw new Error(translations[currentLang].noCamera);
    //         }
    //     }).catch(err => {
    //         console.error(err);
    //         document.getElementById('result').innerHTML = `
    //                 <p class="error">${translations[currentLang].noCamera}</p>
    //             `;
    //     });
    // }
    //

    // 摄像头配置
    // const cameraConfig = {
    //     preferredCamera: 'environment', // 强制后置摄像头
    //     fallbackCamera: 'user',         // 备用前置摄像头
    //     qrbox: { width: 250, height: 250 },
    //     fps: 10
    // };

    async function startScanner() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');

            // 优先选择后置摄像头
            const backCamera = videoDevices.find(d =>
                d.label.toLowerCase().includes('back') ||
                d.label.toLowerCase().includes('rear') ||
                d.label.toLowerCase().includes('environment')
            );

            const cameraId = backCamera ? backCamera.deviceId :
                { exact: cameraConfig.preferredCamera };

            if (window.html5QrCode) {
                await window.html5QrCode.stop();
            }

            window.html5QrCode = new Html5Qrcode("reader");
            await window.html5QrCode.start(
                cameraId,
                {
                    fps: cameraConfig.fps,
                    qrbox: cameraConfig.qrbox
                },
                qrCodeMessage => {
                    validateTicket(qrCodeMessage);
                },
                errorMessage => {
                    console.error(`扫描错误: ${errorMessage}`);
                }
            );

            console.log("摄像头已启动:", backCamera?.label || "默认摄像头");
        } catch (error) {
            console.error("摄像头初始化失败:", error);
            fallbackToManualInput();
        }
    }

    // 修改扫描初始化代码
    async function initScanner() {
        try {
            // 检查摄像头支持
            // const hasCamera = await checkCameraAvailability();
            // if (hasCamera) {
            //     await startCameraScanner();
            // } else {
            //     c
            // }

            const hasCameras = await getCameras();

            if (hasCameras) {
                await startScanner();
                initSwitchButton();
            } else {
                showManualInputOption();
            }

        } catch (error) {
            console.error("初始化错误:", error);
            showManualInputOption();
        }
    }

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

    // 页面加载时初始化
    window.addEventListener('DOMContentLoaded', initBasedOnDevice);

    // 初始化
    window.onload = startScanner;
</script>
</body>
</html>