// 创建scan-worker.js文件
// 导入必要的库
importScripts('https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js');

let html5QrCode = null;

// 接收主线程消息
self.onmessage = function(e) {
    const { action, config } = e.data;

    if (action === 'start') {
        // 初始化扫描器
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length > 0) {
                html5QrCode = new Html5Qrcode("worker-scanner");

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