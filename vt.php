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

        #result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .processing {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
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

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        #interactive {
            position: relative;
            width: 100%;
            height: 300px;
            border: 2px solid #ddd;
            overflow: hidden;
        }

        #interactive canvas.drawing, #interactive canvas.drawingBuffer {
            position: absolute;
            left: 0;
            top: 0;
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

    // function startScanner() {
    //     Quagga.init({
    //         inputStream: {
    //             name: "Live",
    //             type: "LiveStream",
    //             target: document.querySelector('#interactive'),
    //             constraints: {
    //                 width: 480,
    //                 height: 320,
    //                 facingMode: "environment"
    //             },
    //         },
    //         decoder: {
    //             readers: ["qrcode_reader"]
    //         },
    //     }, function(err) {
    //         if (err) {
    //             console.error(err);
    //             document.getElementById('result').textContent = translations[currentLang].noCamera;
    //             return;
    //         }
    //         document.getElementById('startButton').disabled = true;
    //         document.getElementById('stopButton').disabled = false;
    //         document.getElementById('result').textContent = translations[currentLang].scanning;
    //         Quagga.start();
    //     });
    //
    //     Quagga.onDetected(function(result) {
    //         const code = result.codeResult.code;
    //         stopScanner();
    //         validateTicket(code);
    //     });
    // }

    function stopScanner() {
        Quagga.stop();
        document.getElementById('startButton').disabled = false;
        document.getElementById('stopButton').disabled = true;
    }

    // function validateTicket(ticketCode) {
    //     fetch('api/validate_ticket.php?lang=' + currentLang, {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //         },
    //         body: JSON.stringify({ ticket_code: ticketCode })
    //     })
    //         .then(response => response.json())
    //         .then(data => {
    //             const resultDiv = document.getElementById('result');
    //             if (data.success) {
    //                 resultDiv.className = 'valid';
    //                 resultDiv.innerHTML = `
    //                     <h3>${translations[currentLang].scanSuccess}</h3>
    //                     <p>${data.ticket.status}</p>
    //                     <p><strong>${data.ticket.welcome_message}</strong></p>
    //                     <p>Ticket Code: ${data.ticket.code}</p>
    //                 `;
    //             } else {
    //                 resultDiv.className = 'invalid';
    //                 resultDiv.textContent = data.message;
    //             }
    //         })
    //         .catch(error => {
    //             console.error('Error:', error);
    //             document.getElementById('result').textContent = 'Error validating ticket';
    //         });
    // }

    // 初始化语言
    setLanguage(currentLang);
</script>
<script>
    // 更新validateTicket函数，添加详细错误处理
    async function validateTicket(ticketCode) {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>Processing...</p>';
        resultDiv.className = 'processing';

        try {
            // 添加请求超时处理
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            const response = await fetch('api/validate_ticket.php?lang=' + currentLang, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ticket_code: ticketCode }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            console.log('API Response:', data); // 调试输出

            if (data.success) {
                resultDiv.className = 'valid';
                resultDiv.innerHTML = `
                <h3>${translations[currentLang].scanSuccess}</h3>
                <p>${data.ticket?.status || translations[currentLang].ticketValid}</p>
                <p><strong>${data.ticket?.welcome_message || translations[currentLang].welcome}</strong></p>
                <p>Ticket Code: ${data.ticket?.code || ticketCode}</p>
            `;
            } else {
                resultDiv.className = 'invalid';
                resultDiv.innerHTML = `
                <h3>${translations[currentLang].scanSuccess}</h3>
                <p>${data.message || 'Validation failed'}</p>
            `;
            }
        } catch (error) {
            console.error('Validation Error:', error);
            resultDiv.className = 'invalid';

            if (error.name === 'AbortError') {
                resultDiv.innerHTML = `<p>${translations[currentLang].timeout || 'Request timeout'}</p>`;
            } else {
                resultDiv.innerHTML = `
                <p>${translations[currentLang].error || 'Error'}: ${error.message}</p>
                <p>Please check your connection and try again</p>
            `;
            }
        }
    }

    // 在Quagga初始化中添加错误处理
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
                readers: ["qrcode_reader"],
                debug: {
                    drawBoundingBox: true,
                    showFrequency: true,
                    drawScanline: true,
                    showPattern: true
                }
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error('Quagga init error:', err);
                document.getElementById('result').innerHTML = `
                <p class="error">${translations[currentLang].noCamera}</p>
                <p>Error details: ${err.message}</p>
                <p>Please ensure camera access is allowed</p>
            `;
                return;
            }
            document.getElementById('startButton').disabled = true;
            document.getElementById('stopButton').disabled = false;
            document.getElementById('result').innerHTML = `<p>${translations[currentLang].scanning}</p>`;
            Quagga.start();

            // 添加调试绘制
            Quagga.onProcessed(function(result) {
                const drawingCtx = Quagga.canvas.ctx.overlay;
                const drawingCanvas = Quagga.canvas.dom.overlay;

                if (result) {
                    if (result.boxes) {
                        drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                        result.boxes.filter(function(box) {
                            return box !== result.box;
                        }).forEach(function(box) {
                            Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                        });
                    }

                    if (result.box) {
                        Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "blue", lineWidth: 2});
                    }

                    if (result.codeResult && result.codeResult.code) {
                        Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
                    }
                }
            });
        });

        Quagga.onDetected(function(result) {
            console.log('Detection result:', result); // 调试输出
            const code = result.codeResult.code;
            stopScanner();
            validateTicket(code);
        });
    }
</script>
</body>
</html>
