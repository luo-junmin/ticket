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


$image_url = "/ticket-uploads/images/events/686ffc13d0042.jpg";
 echo get_upload_path($image_url);