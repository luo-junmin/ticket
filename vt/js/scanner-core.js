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
            return this.availableCameras;
        } catch (error) {
            console.error('获取摄像头失败:', error);
            return [];
        }
    }

    async startScanning(cameraConfig) {
        if (this.isScanning) return;

        this.isScanning = true;
        try {
            await this.init();

            await this.instance.start(
                cameraConfig,
                {
                    fps: CONFIG.CAMERA.fps,
                    qrbox: CONFIG.CAMERA.qrbox
                },
                (decodedText) => {
                    this.handleScanSuccess(decodedText);
                },
                (error) => {
                    this.handleScanError(error);
                }
            );

            return true;
        } catch (error) {
            this.isScanning = false;
            throw error;
        }
    }

    async switchCamera() {
        if (this.availableCameras.length < 2) return;

        this.currentCameraIndex =
            (this.currentCameraIndex + 1) % this.availableCameras.length;

        await this.startScanning(
            this.availableCameras[this.currentCameraIndex].deviceId
        );
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