<?php
// /ticket/includes/file_utils.php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';

/**
 * 获取上传文件的完整路径
 */
function get_upload_path($filename, $subdir="/images/events") {
    // 确保文件名安全
    $safe_filename = basename($filename);

    // 定义上传目录（根据你的实际结构调整）
    $upload_dir = __DIR__ . '/../../public/uploads/';
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . UPLOADS_PATH . $subdir. '/';

    // 如果目录不存在则创建
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    return $upload_dir . $safe_filename;
}
//function get_upload_path($image_url) {
//    // 根据你的文件存储结构返回完整路径
//    // 例如，如果图片存储在/public/uploads/目录下
//    return $_SERVER['DOCUMENT_ROOT'] . $image_url;
//}

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

//$image_url = "/ticket-uploads/images/events/686ffc13d0042.jpg";
// echo get_upload_path($image_url);