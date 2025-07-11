<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:");

include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/Event.php';

$event = new Event();
$eventId = $_GET['id'] ?? 0;
$eventData = $eventId ? $event->getEventById($eventId) : null;
//trigger_error(print_r($eventData, true));

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'event_date' => $_POST['event_date'],
        'location' => $_POST['location'],
        'venue' => $_POST['venue'],
        'min_price' => $_POST['min_price'],
        'max_price' => $_POST['max_price'],
        'image_url' => $_POST['image_url'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // 处理图片上传
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            $data['image_url'] = $upload['path'];
            // 删除旧图片
            if ($eventData && $eventData['image_url']) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $eventData['image_url']);
            }
        } else {
            error_log("上传失败：" . $upload['message']);
        }
    }

    if ($eventId) {
        // 更新现有事件
        $result = $event->updateEvent($eventId, $data);
    } else {
        // 添加新事件
        $result = $event->addEvent($data);
    }

    if ($result['success']) {
        header("Location: index.php");
        exit;
    } else {
        $error = $result['message'];
    }
}

function uploadImage($file)
{
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxSize = 2 * 1024 * 1024; // 2MB
    $maxWidth = 1024;

    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . UPLOADS_PATH . '/images/events/';
    $webBase = UPLOADS_PATH . '/images/events/';
    $filename = uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;
    $webPath = $webBase . $filename;

    $targetDir = rtrim($uploadDir, '/\\');
    $targetPath = $targetDir . '/' . $filename;

//    trigger_error($destination);
//    trigger_error(print_r($file, true));

    // 创建目录
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 如果文件大小 ≤ 2MB，直接保存
    if ($file['size'] <= $maxSize) {
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => '保存文件失败'];
        }
        return ['success' => true, 'path' => $webPath];
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
//        trigger_error("File exceed 2M");
        // 超过2MB，尝试压缩
        list($origWidth, $origHeight) = getimagesize($file['tmp_name']);
        $newWidth = $origWidth > $maxWidth ? $maxWidth : $origWidth;
        $newHeight = intval($origHeight * ($newWidth / $origWidth));

        switch ($file['type']) {
            case 'image/jpeg':
                $srcImage = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $srcImage = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $srcImage = imagecreatefromwebp($file['tmp_name']);
                break;
            default:
                return ['success' => false, 'message' => '无法识别图片格式'];
        }

        if (!$srcImage) {
            return ['success' => false, 'message' => '无法读取图像'];
        }

        // 创建缩小版本
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // 保持透明度（仅 png/webp）
        if (in_array($file['type'], ['image/png', 'image/webp'])) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0,
            $newWidth, $newHeight, $origWidth, $origHeight);

        // 保存压缩文件
        $saved = false;
        switch ($file['type']) {
            case 'image/jpeg':
                $saved = imagejpeg($dstImage, $targetPath, 85);
                break;
            case 'image/png':
                $saved = imagepng($dstImage, $targetPath, 6); // 0-9
                break;
            case 'image/webp':
                $saved = imagewebp($dstImage, $targetPath, 85);
                break;
        }

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (!$saved) {
            return ['success' => false, 'message' => '压缩保存失败'];
        }

        return ['success' => true, 'path' => $webPath];
    }
}

//function uploadImage($fileInputName, $subDir = 'events') {
//    error_log(print_r($_FILES, true));
//    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
//    $maxSize = 2 * 1024 * 1024; // 2MB
//    $uploadBase = $_SERVER['DOCUMENT_ROOT'] . '/ticket/assets/images/' . $subDir;
//    $webBase = '/assets/images/' . $subDir;
//
//    error_log($uploadBase);
//    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
//        error_log("Failed to upload image.");
//        return ['success' => false, 'message' => '上传失败或无文件'];
//    }
//
//    $file = $_FILES[$fileInputName];
//    error_log($file);
//
//    // 类型限制
//    if (!in_array($file['type'], $allowedTypes)) {
//        return ['success' => false, 'message' => '文件类型不支持'];
//    }
//
//    // 生成唯一文件名
//    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
//    $filename = uniqid() . '.' . $ext;
//    $targetDir = rtrim($uploadBase, '/\\');
//    $targetPath = $targetDir . '/' . $filename;
//    $webPath = $webBase . '/' . $filename;
//
//    // 创建目录
//    if (!is_dir($targetDir)) {
//        mkdir($targetDir, 0755, true);
//    }
//
//    // 如果文件大小 ≤ 2MB，直接保存
//    if ($file['size'] <= $maxSize) {
//        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
//            return ['success' => false, 'message' => '保存文件失败'];
//        }
//        return ['success' => true, 'path' => $webPath];
//    }
//
//    // 否则尝试压缩
//    $image = null;
//    switch ($file['type']) {
//        case 'image/jpeg':
//            $image = imagecreatefromjpeg($file['tmp_name']);
//            break;
//        case 'image/png':
//            $image = imagecreatefrompng($file['tmp_name']);
//            break;
//        case 'image/webp':
//            $image = imagecreatefromwebp($file['tmp_name']);
//            break;
//    }
//
//    if (!$image) {
//        return ['success' => false, 'message' => '读取图片失败'];
//    }
//
//    // 压缩（逐步降低质量）
//    $quality = 90;
//    do {
//        ob_start();
//        if ($file['type'] === 'image/png') {
//            // PNG 的压缩级别是 0-9，9 最压缩
//            imagepng($image, null, round((9 * (100 - $quality)) / 100));
//        } elseif ($file['type'] === 'image/webp') {
//            imagewebp($image, null, $quality);
//        } else {
//            imagejpeg($image, null, $quality);
//        }
//        $compressed = ob_get_clean();
//        $size = strlen($compressed);
//        $quality -= 5;
//    } while ($size > $maxSize && $quality > 10);
//
//    // 保存压缩文件
//    if (file_put_contents($targetPath, $compressed) === false) {
//        return ['success' => false, 'message' => '保存压缩文件失败'];
//    }
//
//    return ['success' => true, 'path' => $webPath];
//}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $eventId ? 'Edit' : 'Add' ?> Event | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_header.php'; ?>
    <link href="/ticket/assets/css/flatpickr.min.css" rel="stylesheet">
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $eventId ? 'Edit Event' : 'Add New Event' ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        Back to Events
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" id="title" name="title"
                                           value="<?= htmlspecialchars($eventData['title'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($eventData['description'] ?? '') ?></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="event_date" class="form-label">Event Date *</label>
                                        <input type="datetime-local" class="form-control" id="event_date" name="event_date"
                                               value="<?= isset($eventData['event_date']) ? date('Y-m-d\TH:i', strtotime($eventData['event_date'])) : '' ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="location" class="form-label">Location *</label>
                                        <input type="text" class="form-control" id="location" name="location"
                                               value="<?= htmlspecialchars($eventData['location'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="venue" class="form-label">Venue</label>
                                    <input type="text" class="form-control" id="venue" name="venue"
                                           value="<?= htmlspecialchars($eventData['venue'] ?? '') ?>">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="min_price" class="form-label">Min Price *</label>
                                        <input type="text" class="form-control" id="min_price" name="min_price"
                                               value="<?= htmlspecialchars($eventData['min_price'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max_price" class="form-label">Max Price *</label>
                                        <input type="text" class="form-control" id="max_price" name="max_price"
                                               value="<?= htmlspecialchars($eventData['max_price'] ?? '') ?>" required>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            <?= (isset($eventData['is_active']) && $eventData['is_active']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">Event Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">

                                    <?php if (isset($eventData['image_url']) && $eventData['image_url']): ?>
                                    <input type="hidden" name="image_url" value="<?= $eventData['image_url'] ?>">
                                        <div class="mt-2">
                                            <img src="<?= $eventData['image_url'] ?>" class="img-thumbnail" style="max-height: 150px;">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image">
                                                <label class="form-check-label" for="remove_image">Remove current image</label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Event</button>
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
        minDate: "today"
    });
</script>
</body>
</html>