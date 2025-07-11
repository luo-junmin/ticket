<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Event.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/file_utils.php';

$event = new Event();
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'event_date' => trim($_POST['event_date']),
            'location' => trim($_POST['location']),
            'venue' => trim($_POST['venue'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // 验证必填字段
        if (empty($data['title']) || empty($data['event_date']) || empty($data['location'])) {
            throw new Exception('请填写所有必填字段');
        }

        // 处理图片上传
        if (!empty($_FILES['image']['name'])) {
            $uploadResult = uploadImage($_FILES['image']);
            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['message']);
            }
            $data['image_url'] = $uploadResult['path'];
        }

        // 添加事件
        $result = $event->addEvent($data);

        if ($result['success']) {
            $success = '事件添加成功！';
            // 清空表单或重定向
            $_POST = [];
        } else {
            throw new Exception($result['message']);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/**
 * 图片上传处理
 */
//function uploadImage($file) {
//    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
//    $maxSize = 2 * 1024 * 1024; // 2MB
//
//    // 验证文件类型
//    if (!in_array($file['type'], $allowedTypes)) {
//        return ['success' => false, 'message' => '只允许上传JPEG, PNG或GIF图片'];
//    }
//
//    // 验证文件大小
//    if ($file['size'] > $maxSize) {
//        return ['success' => false, 'message' => '图片大小不能超过2MB'];
//    }
//
//    // 生成唯一文件名
//    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
//    $filename = uniqid() . '.' . $ext;
//    $uploadDir = '/assets/images/events/';
//    $uploadPath = __DIR__ . '/../../..' . $uploadDir;
//
//    // 确保目录存在
//    if (!file_exists($uploadPath)) {
//        mkdir($uploadPath, 0755, true);
//    }
//
//    // 移动上传文件
//    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
//        return ['success' => true, 'path' => $uploadDir . $filename];
//    } else {
//        return ['success' => false, 'message' => '文件上传失败'];
//    }
//}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加新活动 - 后台管理 | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_header.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .image-preview {
            max-width: 300px;
            max-height: 200px;
            display: none;
            margin-top: 10px;
        }
        .form-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">添加新活动</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> 返回活动列表
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-section">
                            <h5 class="mb-4">活动基本信息</h5>

                            <div class="mb-3">
                                <label for="title" class="form-label required-field">活动标题</label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                                <div class="invalid-feedback">请输入活动标题</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">活动描述</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label required-field">活动日期</label>
                                    <input type="datetime-local" class="form-control" id="event_date" name="event_date"
                                           value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" required>
                                    <div class="invalid-feedback">请选择活动日期</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label required-field">活动地点</label>
                                    <input type="text" class="form-control" id="location" name="location"
                                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                                    <div class="invalid-feedback">请输入活动地点</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="venue" class="form-label">具体场馆/场地</label>
                                <input type="text" class="form-control" id="venue" name="venue"
                                       value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-section">
                            <h5 class="mb-4">活动设置</h5>

                            <div class="mb-3">
                                <label class="form-label">活动状态</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
                                    <label class="form-check-label" for="is_active">激活活动</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">活动封面图片</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">建议尺寸：1200x630像素，最大2MB</small>
                                <img id="imagePreview" class="img-thumbnail image-preview" alt="预览图">
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> 保存活动
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // 初始化日期时间选择器
    flatpickr("#event_date", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        locale: "zh" // 中文
    });

    // 图片预览功能
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('imagePreview');
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // 表单验证
    (function () {
        'use strict'

        const forms = document.querySelectorAll('.needs-validation')

        Array.from(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
    })();
</script>
</body>
</html>