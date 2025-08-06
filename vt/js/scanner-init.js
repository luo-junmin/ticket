// 初始化扫描系统
document.addEventListener('DOMContentLoaded', async () => {
    const scannerCore = new ScannerCore();
    const scannerUI = new ScannerUI(scannerCore);

    try {
        scannerUI.showLoading(true);

        // 初始化摄像头
        await scannerCore.getCameras();
        const cameraConfig = scannerCore.availableCameras[0]?.deviceId ||
            { facingMode: CONFIG.CAMERA.preferred };

        await scannerCore.startScanning(cameraConfig);

        // 初始化UI
        scannerUI.initEventListeners();

    } catch (error) {
        console.error("初始化失败:", error);
        scannerUI.showError(error);
    } finally {
        scannerUI.showLoading(false);
    }
});