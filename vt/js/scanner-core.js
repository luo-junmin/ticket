class ScannerCore {
    constructor() {
        this.instance = null;
        this.currentStream = null;
        this.availableCameras = [];
        this.currentCameraIndex = 0;
        this.isScanning = false;
        this.ticketCache = new Map();
    }

    async init() {
        await this.cleanup();
        this.instance = new Html5Qrcode("reader");
        return this.instance;
    }

    async cleanup() {
        if (this.instance?.isScanning) {
            await this.instance.stop();
        }

        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
        }

        this.instance = null;
        this.currentStream = null;
    }

    async getCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.availableCameras = devices.filter(d => d.kind === 'videoinput');

            // 尝试识别后置摄像头
            const backCameraIndex = this.availableCameras.findIndex(d =>
                /back|rear|environment/i.test(d.label)
            );

            if (backCameraIndex !== -1) {
                this.currentCameraIndex = backCameraIndex;
            }

            return this.availableCameras;
        } catch (error) {
            console.error('获取摄像头失败:', error);
            return [];
        }
    }

    async startScanning(cameraConfig) {
        // if (this.isScanning) return;
        if (this.isScanning || this.availableCameras.length === 0) return;


        try {
            await this.init();
            this.isScanning = true;

            // const cameraId = this.availableCameras[this.currentCameraIndex].deviceId;
            //
            // this.currentStream = await this.html5QrCode.start(
            //     cameraId,
            //     {
            //         fps: 10,
            //         qrbox: { width: 250, height: 250 }
            //     },
            //     (decodedText) => {
            //         this.handleScanSuccess(decodedText);
            //     },
            //     (error) => {
            //         this.handleScanError(error);
            //     }
            // );
            //
            // return true;

            await this.instance.start(
                cameraConfig,
                {
                    fps: CONFIG.CAMERA.fps,
                    qrbox: CONFIG.CAMERA.qrbox
                },
                (decodedText) => {
                    this.handleScanSuccess(decodedText);
                    this.validateTicket(decodedText);
                },
                (error) => {
                    this.handleScanError(error);
                }
            );

            return true;
        } catch (error) {
            this.isScanning = false;
            console.error('启动扫描失败:', error);
            throw error;
        }
    }

    async switchCamera() {
        if (this.availableCameras.length < 2) {
            console.warn('只有一个摄像头可用，无法切换');
            return;
        }

        this.isScanning = false;

        try {
            // 计算下一个摄像头索引
            this.currentCameraIndex =
                (this.currentCameraIndex + 1) % this.availableCameras.length;

            console.log('切换到摄像头:',
                this.availableCameras[this.currentCameraIndex].label ||
                `摄像头 ${this.currentCameraIndex + 1}`);

            // 停止当前扫描
            await this.html5QrCode.stop();

            // 使用新摄像头重新启动
            await this.startScanning();

            return true;
        } catch (error) {
            console.error('切换摄像头失败:', error);
            throw error;
        }
    }

    getCurrentCamera() {
        if (this.availableCameras.length === 0) return null;
        return this.availableCameras[this.currentCameraIndex];
    }

    async validateTicket(code) {
        if (this.ticketCache.has(code)) {
            return this.ticketCache.get(code);
        }

        const params = new URLSearchParams({ ticket_code: code });
        const response = await fetch(`scan_ticket.php?lang=${currentLang}`, {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-API-KEY': CONFIG.API.key,
                'X-CLIENT-IP': CONFIG.API.clientIp
            },
            body: params
        });

        if (!response.ok) throw new Error('验证失败');

        const data = await response.json();
        this.ticketCache.set(code, data);

        setTimeout(() => {
            this.ticketCache.delete(code);
        }, 300000); // 5分钟缓存

        return data;
    }
}