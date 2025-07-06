class ScannerUI {
    constructor(scannerCore) {
        this.core = scannerCore;
        this.currentLang = 'zh';
        this.translations = {
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

        this.manualSection = document.getElementById('manualEntry');
        this.scannerSection = document.getElementById('scanner-container');
        // Initialize event listeners properly
        this.initManualToggle();

        this.initLanguageSwitcher();
        this.initEventListeners();

        this.switchBtn = document.getElementById('switchCameraBtn');
        this.initCameraControls();

    }

    initLanguageSwitcher() {
        document.getElementById('lang-zh')?.addEventListener('click', () => this.setLanguage('zh'));
        document.getElementById('lang-en')?.addEventListener('click', () => this.setLanguage('en'));
    }

    initEventListeners() {
        document.getElementById('switchCameraBtn')?.addEventListener('click', () => this.core.switchCamera());
        document.getElementById('restartBtn')?.addEventListener('click', () => this.restartScanner());
        // Add other event listeners here...
        document.getElementById('toggleManualBtn')?.addEventListener('click', () => this.toggleManualEntry());
    }

    setLanguage(lang) {
        if (!this.translations[lang]) return;

        this.currentLang = lang;

        // Update UI texts
        document.getElementById('title').textContent = this.translations[lang].title;
        document.getElementById('instructions').textContent = this.translations[lang].instructions;
        document.getElementById('restartText').textContent = this.translations[lang].restart;

        // Update language button states
        document.querySelectorAll('.language-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.id === `lang-${lang}`) {
                btn.classList.add('active');
            }
        });
    }

    initManualToggle() {
        const toggleBtn = document.getElementById('toggleManualBtn');
        if (toggleBtn) {
            // Remove any existing listeners to prevent duplication
            toggleBtn.removeEventListener('click', this.toggleManualEntry.bind(this));
            // Add the listener properly
            toggleBtn.addEventListener('click', () => this.toggleManualEntry());
        }
    }

    toggleManualEntry() {
        const showManual = this.manualSection.style.display === 'block';

        // Toggle visibility
        this.manualSection.style.display = showManual ? 'none' : 'block';
        this.scannerSection.style.display = showManual ? 'block' : 'none';

        // Focus if showing manual entry
        if (!showManual) {
            document.getElementById('manualTicketCode')?.focus();
        }

        // Prevent default and stop propagation to avoid double triggering
        return false;
    }

    initCameraControls() {
        // 初始化摄像头切换按钮
        this.updateCameraButton();

        // 绑定事件监听器
        this.switchBtn?.addEventListener('click', async () => {
            try {
                this.switchBtn.disabled = true;
                await this.core.switchCamera();
                this.updateCameraButton();
            } catch (error) {
                console.error('切换摄像头出错:', error);
            } finally {
                this.switchBtn.disabled = false;
            }
        });
    }

    updateCameraButton() {
        if (!this.switchBtn || this.core.availableCameras.length < 2) {
            this.switchBtn.style.display = 'none';
            return;
        }

        const currentCamera = this.core.getCurrentCamera();
        const isBackCamera = /back|rear|environment/i.test(currentCamera?.label || '');

        this.switchBtn.style.display = 'block';
        this.switchBtn.textContent = isBackCamera
            ? '切换到前置摄像头'
            : '切换到后置摄像头';
    }

    showResult(data) {
        const resultDiv = document.getElementById('result');
        if (!resultDiv) return;

        resultDiv.className = data.status;

        let message = '';
        switch(data.status) {
            case 'valid':
                message = this.createValidMessage(data);
                break;
            case 'used':
                message = this.createUsedMessage(data);
                break;
            case 'invalid':
                message = this.createInvalidMessage(data);
                break;
            case 'error':
                message = this.createErrorMessage(data);
                break;
            default:
                message = this.translations[this.currentLang].error;
        }

        resultDiv.innerHTML = message;
        this.playSound(data.status === 'valid' ? 'success' : 'error');
    }

    createValidMessage(data) {
        return `
            <h3>✅ ${this.translations[this.currentLang].valid}</h3>
            <p><strong>${this.translations[this.currentLang].welcome}</strong></p>
            <p>Ticket Code: ${data.ticket_code}</p>
        `;
    }

    createUsedMessage(data) {
        const time = data.used_at || this.translations[this.currentLang].used.replace('{time}', '');
        return `
            <h3>⚠️ ${this.translations[this.currentLang].used.replace('{time}', time)}</h3>
            <p>Used at: ${time}</p>
        `;
    }

    createInvalidMessage(data) {
        return `<h3>❌ ${this.translations[this.currentLang].invalid}</h3>`;
    }

    createErrorMessage(data) {
        return `<h3>⚠️ ${data.message || this.translations[this.currentLang].error}</h3>`;
    }

    showLoading(show) {
        const loader = document.getElementById('scanner-loading');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    playSound(type) {
        try {
            const audio = new Audio();
            audio.src = type === 'success' ? 'success.mp3' : 'error.mp3';
            audio.play().catch(e => console.log('Audio playback failed:', e));
        } catch (e) {
            console.error('Sound error:', e);
        }
    }

    restartScanner() {
        const resultDiv = document.getElementById('result');
        if (resultDiv) {
            resultDiv.innerHTML = '';
            resultDiv.className = '';
        }

        const restartBtn = document.getElementById('restartBtn');
        if (restartBtn) {
            restartBtn.style.display = 'none';
        }

        this.core.startScanning();
    }
}