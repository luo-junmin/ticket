<?php
// 验证管理员权限
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/autoload.php';

// 包含用户类
//include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/User.php';
$user = new User();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $phone = trim($_POST['phone'] ?? '');

    // 验证输入
    $errors = [];
    if (empty($name)) $errors[] = '姓名不能为空';
//    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = '邮箱格式不正确';

    // 邮箱验证
    if (empty($email)) {
        $errors[] = '邮箱不能为空';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '邮箱格式不正确';
    } elseif ($user->emailExists($email)) {  // 检查邮箱是否已存在
        $errors[] = '该邮箱已被注册';
    }

    if (strlen($password) < 8) $errors[] = '密码至少需要8个字符';
    if (!in_array($role, ['user', 'inspector', 'admin'])) $errors[] = '无效的用户角色';

    if (empty($errors)) {
        // 创建用户
        $result = $user->createUser([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'phone' => $phone
        ]);

        if ($result) {
            $_SESSION['success'] = '用户添加成功';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = '添加用户失败，请重试';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加用户 | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_header.php'; ?>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">添加新用户</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> 返回用户列表
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">姓名</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">
                            请输入用户姓名
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">邮箱</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            请输入有效的邮箱地址
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <div class="invalid-feedback">
                            密码至少需要8个字符
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label">角色</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user">普通用户</option>
                            <option value="inspector">验票员</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">电话 (可选)</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                </div>

                <hr class="my-4">

                <button class="w-100 btn btn-primary btn-lg" type="submit">添加用户</button>
            </form>
        </main>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_footer.php'; ?>

<script>
    // 表单验证
    // (function () {
    //     'use strict'
    //
    //     // 获取所有需要验证的表单
    //     var forms = document.querySelectorAll('.needs-validation')
    //
    //     // 循环处理每个表单
    //     Array.prototype.slice.call(forms)
    //         .forEach(function (form) {
    //             form.addEventListener('submit', function (event) {
    //                 if (!form.checkValidity()) {
    //                     event.preventDefault()
    //                     event.stopPropagation()
    //                 }
    //
    //                 form.classList.add('was-validated')
    //             }, false)
    //         })
    // })()
    // 添加实时邮箱验证
    document.getElementById('email').addEventListener('blur', function() {
        const email = this.value;
        if (!email) return;

        fetch('/ticket/api/check_email.php?email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                const feedback = document.querySelector('#email + .invalid-feedback');
                if (data.exists) {
                    this.setCustomValidity('该邮箱已被注册');
                    feedback.textContent = '该邮箱已被注册';
                } else {
                    this.setCustomValidity('');
                    feedback.textContent = '请输入有效的邮箱地址';
                }
                this.reportValidity();
            });
    });

</script>
</body>
</html>