/**
 * 后台管理全局 JavaScript 功能
 * 包含表格操作、表单验证、UI 增强等功能
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. 启用 Bootstrap 工具提示
    enableTooltips();

    // 2. 表格增强功能
    enhanceTables();

    // 3. 表单验证和提交处理
    handleForms();

    // 4. 侧边栏和导航交互
    setupNavigation();

    // 5. 确认对话框
    setupConfirmationDialogs();

    // 6. 实时数据更新
    setupRealTimeUpdates();
});

/**
 * 启用 Bootstrap 工具提示
 */
function enableTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
}

/**
 * 表格增强功能
 */
function enhanceTables() {
    // 全选/取消全选
    document.querySelectorAll('.table [data-select-all]').forEach(selectAll => {
        selectAll.addEventListener('change', function() {
            const table = this.closest('table');
            table.querySelectorAll('[data-select-item]').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    });

    // 行点击效果
    document.querySelectorAll('.table tbody tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // 忽略按钮和链接的点击
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            window.location.href = this.dataset.href;
        });
    });

    // 表格排序
    document.querySelectorAll('.table-sortable th[data-sort]').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.dataset.sort;
            const direction = this.classList.contains('sorted-asc') ? 'desc' : 'asc';

            // 重置其他列的排序状态
            table.querySelectorAll('th[data-sort]').forEach(th => {
                th.classList.remove('sorted-asc', 'sorted-desc');
            });

            // 设置当前列的排序状态
            this.classList.add(`sorted-${direction}`);

            // 实际排序逻辑（这里需要根据实际情况实现或调用后端排序）
            sortTable(table, column, direction);
        });
    });
}

/**
 * 表单处理
 */
function handleForms() {
    // 表单验证
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                // 显示错误消息
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger mt-3';
                errorDiv.textContent = 'Please fill in all required fields correctly.';
                this.appendChild(errorDiv);
            }
        });
    });

    // 图片预览
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);

            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // 富文本编辑器初始化
    document.querySelectorAll('[data-rich-text]').forEach(textarea => {
        // 这里可以集成 CKEditor 或 TinyMCE
        // 简单实现：增加行高和基本样式
        textarea.style.minHeight = '200px';
        textarea.style.lineHeight = '1.6';
    });
}

/**
 * 导航和侧边栏设置
 */
function setupNavigation() {
    // 侧边栏折叠/展开
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');

            // 保存用户偏好到本地存储
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    // 从本地存储恢复侧边栏状态
    if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        document.body.classList.add('sb-sidenav-toggled');
    }

    // 当前活动菜单项高亮
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
}

/**
 * 确认对话框设置
 */
function setupConfirmationDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 实时数据更新
 */
function setupRealTimeUpdates() {
    // 如果有实时数据需求，可以设置定时器或 WebSocket
    // 例如更新订单状态、新用户通知等

    // 示例：每30秒检查新订单
    if (document.body.classList.contains('dashboard-page')) {
        setInterval(() => {
            fetch('/api/admin/orders/count?status=pending')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('#pendingOrdersBadge');
                    if (badge && data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';

                        // 如果有新订单，闪烁提醒
                        if (data.count > parseInt(badge.dataset.lastCount || '0')) {
                            flashElement(badge.parentElement);
                        }

                        badge.dataset.lastCount = data.count;
                    }
                });
        }, 30000);
    }
}

/**
 * 辅助函数：表单验证
 */
function validateForm(form) {
    let isValid = true;

    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    // 密码确认验证
    const password = form.querySelector('[name="password"]');
    const confirmPassword = form.querySelector('[name="confirm_password"]');
    if (password && confirmPassword && password.value !== confirmPassword.value) {
        confirmPassword.classList.add('is-invalid');
        isValid = false;
    }

    return isValid;
}

/**
 * 辅助函数：表格排序
 */
function sortTable(table, column, direction) {
    // 实际排序逻辑需要根据表格结构实现
    // 这里只是一个示例，实际项目中可能需要:
    // 1. 调用后端API进行排序
    // 2. 或者在前端对现有数据进行排序

    console.log(`Sorting by ${column} in ${direction} order`);
    // 实现前端排序或发起AJAX请求
}

/**
 * 辅助函数：元素闪烁效果
 */
function flashElement(element, times = 3, duration = 500) {
    let count = 0;
    const interval = setInterval(() => {
        element.style.backgroundColor = (count % 2 === 0) ? 'rgba(255, 193, 7, 0.3)' : '';
        count++;
        if (count >= times * 2) {
            clearInterval(interval);
            element.style.backgroundColor = '';
        }
    }, duration);
}

/**
 * AJAX 辅助函数
 */
function sendAjaxRequest(url, method, data, successCallback, errorCallback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            const response = xhr.responseText ? JSON.parse(xhr.responseText) : null;
            successCallback(response, xhr.status, xhr);
        } else {
            const error = xhr.responseText ? JSON.parse(xhr.responseText) : null;
            errorCallback(error, xhr.status, xhr);
        }
    };

    xhr.onerror = function() {
        errorCallback(null, xhr.status, xhr);
    };

    xhr.send(JSON.stringify(data));
}

/**
 * 显示Toast通知
 */
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toastId = 'toast-' + Date.now();

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.id = toastId;

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // 自动移除
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });

    return toastId;
}

/**
 * 创建Toast容器
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1100';
    document.body.appendChild(container);
    return container;
}

/**
 * 初始化日期时间选择器
 */
function initDateTimePickers() {
    document.querySelectorAll('[data-datepicker]').forEach(input => {
        flatpickr(input, {
            enableTime: input.dataset.time === 'true',
            dateFormat: input.dataset.format || 'Y-m-d H:i',
            minDate: input.dataset.min || 'today',
            maxDate: input.dataset.max || '',
            defaultDate: input.value || ''
        });
    });
}

// 暴露一些函数给全局作用域
window.Admin = {
    showToast,
    sendAjaxRequest,
    initDateTimePickers
};

// 初始化日期时间选择器（如果页面有需要）
if (typeof flatpickr !== 'undefined') {
    initDateTimePickers();
}