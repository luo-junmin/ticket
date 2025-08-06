// 创建scan-worker.js文件
// 导入必要的库
importScripts('https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js');

let html5QrCode = null;

function startMainThreadScanner() {
    const qrCodeScanner = new Html5Qrcode("reader");

    // 明确指定使用后置摄像头
    qrCodeScanner.start(
        { facingMode: "environment" }, // 强制使用后置摄像头
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

async function getBackCamera() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');

        // 尝试通过标签识别后置摄像头
        const backCameras = videoDevices.filter(device =>
            device.label.toLowerCase().includes('back') ||
            device.label.toLowerCase().includes('rear') ||
            device.label.toLowerCase().includes('environment')
        );

        if (backCameras.length > 0) {
            return backCameras[0].deviceId;
        }

        // 如果无法通过标签识别，尝试通过组ID识别
        if (videoDevices.length > 1) {
            // 通常第二个摄像头是后置摄像头
            return videoDevices[1].deviceId;
        }

        // 如果只有一个摄像头，返回它
        return videoDevices[0]?.deviceId || null;
    } catch (error) {
        console.error('Error enumerating devices:', error);
        return null;
    }
}

// 接收主线程消息
self.onmessage = function(e) {
    const { action, config } = e.data;

    if (action === 'start') {
        // 初始化扫描器
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length > 0) {
                html5QrCode = new Html5Qrcode("worker-scanner");

                // 优先选择后置摄像头
                const backCamera = cameras.find(cam =>
                    cam.label.toLowerCase().includes('back') ||
                    cam.label.toLowerCase().includes('rear') ||
                    cam.label.toLowerCase().includes('environment')
                );

                const cameraId = backCamera ? backCamera.id : cameras[0].id;

                html5QrCode.start(
                    cameras[0].id,  // 使用第一个摄像头
                    {
                        fps: config?.fps || 10,
                        qrbox: config?.qrbox || { width: 250, height: 250 },
                        disableFlip: config?.disableFlip || false
                    },
                    qrCodeMessage => {
                        // 扫描成功，返回结果给主线程
                        self.postMessage({
                            status: 'success',
                            result: qrCodeMessage
                        });
                    },
                    errorMessage => {
                        // 扫描错误
                        self.postMessage({
                            status: 'error',
                            error: errorMessage
                        });
                    }
                ).then(() => {
                    self.postMessage({ status: 'scanner_started' });
                }).catch(err => {
                    self.postMessage({
                        status: 'error',
                        error: `Scanner failed: ${err}`
                    });
                });
            } else {
                self.postMessage({
                    status: 'error',
                    error: 'No cameras found'
                });
            }
        }).catch(err => {
            self.postMessage({
                status: 'error',
                error: `Camera access error: ${err}`
            });
        });
    }
    else if (action === 'stop' && html5QrCode) {
        // 停止扫描
        html5QrCode.stop().then(() => {
            html5QrCode = null;
            self.postMessage({ status: 'scanner_stopped' });
        }).catch(err => {
            self.postMessage({
                status: 'error',
                error: `Failed to stop scanner: ${err}`
            });
        });
    }
};