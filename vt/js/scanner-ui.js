class ScannerUI {
    constructor(scannerCore) {
        this.core = scannerCore;
        this.currentLang = 'zh';
        this.translations = {
            en: { /* 英文翻译 */ },
            zh: { /* 中文翻译 */ }
        };
    }

    initEventListeners() {
        document.getElementById('switchCameraBtn')
            .addEventListener('click', () => this.core.switchCamera());

        document.getElementById('restartBtn')
            .addEventListener('click', () => this.restartScanner());

        // 其他事件监听...
    }

    showResult(data) {
        const resultDiv = document.getElementById('result');
        resultDiv.className = data.status;

        // 根据状态显示不同内容
        let message = '';
        switch(data.status) {
            case 'valid':
                message = this.createValidMessage(data);
                break;
            case 'used':
                message = this.createUsedMessage(data);
                break;
            // 其他状态...
        }

        resultDiv.innerHTML = message;
        this.playSound(data.status === 'valid' ? 'success' : 'error');
    }

    showLoading(show) {
        document.getElementById('scanner-loading')
            .style.display = show ? 'flex' : 'none';
    }

    setLanguage(lang) {
        this.currentLang = lang;
        // 更新界面文本...
    }
}