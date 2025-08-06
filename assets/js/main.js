document.addEventListener('DOMContentLoaded', function() {
    // 处理登录表单提交
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAuthForm(this, 'login');
        });
    }

    // 处理注册表单提交
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAuthForm(this, 'register');
        });
    }

    // 处理忘记密码表单提交
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAuthForm(this, 'forgot_password');
        });
    }

    // 显示错误消息
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        showModalError(error);
    }
});

function submitAuthForm(form, action) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';

    fetch(`/ticket/api/auth.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                showAlert(data.message || 'An error occurred', 'danger');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            showAlert('Network error. Please try again.', 'danger');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    const container = document.querySelector('.modal-body');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        alert.close();
    }, 5000);
}

function showModalError(error) {
    let errorMessage = '';
    switch (error) {
        case 'invalid_credentials':
            errorMessage = 'Invalid email or password';
            break;
        case 'email_exists':
            errorMessage = 'Email already registered';
            break;
        case 'session_timeout':
            errorMessage = 'Session expired. Please login again.';
            break;
        default:
            errorMessage = 'An error occurred';
    }

    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
    showAlert(errorMessage, 'danger');
}