<?php
/**
 * vt.php
 *  移动端验票页面
 *
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Scanner - 票务扫描</title>
    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }
        #scanner-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
        }
        #interactive {
            width: 100%;
            height: 300px;
            border: 2px solid #ddd;
        }
        #result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .valid {
            background-color: #d4edda;
            color: #155724;
        }
        .invalid {
            background-color: #f8d7da;
            color: #721c24;
        }
        .language-switcher {
            margin-bottom: 20px;
        }
        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
    </style>
</head>
<body>
<div class="language-switcher">
    <button onclick="setLanguage('en')">English</button>
    <button onclick="setLanguage('zh')">中文</button>
</div>

<h1 id="title">Ticket Scanner</h1>
<p id="instructions">Point your camera at a ticket QR code to scan</p>

<div id="scanner-container">
    <div id="interactive"></div>
</div>

<button id="startButton">Start Scanner</button>
<button id="stopButton" disabled>Stop Scanner</button>

<div id="result"></div>

<script>
    // 语言包
    const translations = {
        en: {
            title: "Ticket Scanner",
            instructions: "Point your camera at a ticket QR code to scan",
            startButton: "Start Scanner",
            stopButton: "Stop Scanner",
            scanning: "Scanning...",
            noCamera: "No camera access",
            scanSuccess: "Scan successful!",
            welcome: "Welcome to our event!",
            ticketValid: "Ticket is valid",
            ticketUsed: "Ticket already used on {time}",
            ticketNotFound: "Ticket not found"
        },
        zh: {
            title: "票务扫描",
            instructions: "将相机对准票证二维码进行扫描",
            startButton: "开始扫描",
            stopButton: "停止扫描",
            scanning: "扫描中...",
            noCamera: "无法访问相机",
            scanSuccess: "扫描成功!",
            welcome: "欢迎参加我们的活动!",
            ticketValid: "票证有效",
            ticketUsed: "票证已于 {time} 使用",
            ticketNotFound: "未找到票证"
        }
    };

    let currentLang = 'en';

    function setLanguage(lang) {
        currentLang = lang;
        document.getElementById('title').textContent = translations[lang].title;
        document.getElementById('instructions').textContent = translations[lang].instructions;
        document.getElementById('startButton').textContent = translations[lang].startButton;
        document.getElementById('stopButton').textContent = translations[lang].stopButton;
    }

    document.getElementById('startButton').addEventListener('click', startScanner);
    document.getElementById('stopButton').addEventListener('click', stopScanner);

    function startScanner() {
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#interactive'),
                constraints: {
                    width: 480,
                    height: 320,
                    facingMode: "environment"
                },
            },
            decoder: {
                readers: ["qrcode_reader"]
            },
        }, function(err) {
            if (err) {
                console.error(err);
                document.getElementById('result').textContent = translations[currentLang].noCamera;
                return;
            }
            document.getElementById('startButton').disabled = true;
            document.getElementById('stopButton').disabled = false;
            document.getElementById('result').textContent = translations[currentLang].scanning;
            Quagga.start();
        });

        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            stopScanner();
            validateTicket(code);
        });
    }

    function stopScanner() {
        Quagga.stop();
        document.getElementById('startButton').disabled = false;
        document.getElementById('stopButton').disabled = true;
    }

    function validateTicket(ticketCode) {
        fetch('api/validate_ticket.php?lang=' + currentLang, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ticket_code: ticketCode })
        })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('result');
                if (data.success) {
                    resultDiv.className = 'valid';
                    resultDiv.innerHTML = `
                        <h3>${translations[currentLang].scanSuccess}</h3>
                        <p>${data.ticket.status}</p>
                        <p><strong>${data.ticket.welcome_message}</strong></p>
                        <p>Ticket Code: ${data.ticket.code}</p>
                    `;
                } else {
                    resultDiv.className = 'invalid';
                    resultDiv.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').textContent = 'Error validating ticket';
            });
    }

    // 初始化语言
    setLanguage(currentLang);
</script>
</body>
</html>
