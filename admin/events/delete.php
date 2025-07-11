<?php
//require_once __DIR__ . '/../../../config.php';
//require_once __DIR__ . '/../../../includes/database.php';
//require_once __DIR__ . '/../../../includes/auth.php';
//require_once __DIR__ . '/../../../includes/file_utils.php'; // 假设有一个文件工具类
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/file_utils.php';

// 验证管理员权限
if (!is_admin()) {
    header('HTTP/1.0 403 Forbidden');
    exit('您没有权限执行此操作');
}

// 检查是否是POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    exit('只允许POST请求');
}

// 验证CSRF令牌
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('无效的CSRF令牌');
}

// 获取事件ID
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if ($event_id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    exit('无效的事件ID');
}

// 获取事件信息（主要是为了获取图片路径）
//$conn = get_db_connection();
//$stmt = $conn->prepare("SELECT image_url FROM events WHERE id = ?");
//$stmt->bind_param("i", $event_id);
//$stmt->execute();
//$result = $stmt->get_result();
//$event = $result->fetch_assoc();
//$stmt->close();
//
//if (!$event) {
//    header('HTTP/1.0 404 Not Found');
//    exit('找不到指定的事件');
//}
//
//// 删除关联的图片文件（如果存在）
//if (!empty($event['image_url'])) {
//    $image_path = get_upload_path($event['image_url']); // 假设这个函数返回完整的文件路径
//
//    if (file_exists($image_path)) {
//        if (!unlink($image_path)) {
//            // 记录错误但继续删除数据库记录
//            error_log("无法删除事件图片: " . $image_path);
//        }
//    }
//}
//
//// 从数据库删除事件
//$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
//$stmt->bind_param("i", $event_id);
//
//if ($stmt->execute()) {
//    // 删除成功，重定向回事件列表
//    header('Location: /ticket/admin/events/?deleted=1');
//    exit();
//} else {
//    // 删除失败
//    header('HTTP/1.0 500 Internal Server Error');
//    exit('删除事件失败: ' . $conn->error);
//}
//
//$stmt->close();
//$conn->close();

try {
    // 获取PDO数据库连接
    $pdo = get_pdo_connection();

    // 开启事务
    $pdo->beginTransaction();

    // 1. 获取事件信息（包括图片路径）
    $stmt = $pdo->prepare("SELECT image_url FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception('找不到指定的事件');
    }

    // 2. 删除关联的图片文件（如果存在）
    if (!empty($event['image_url'])) {
        $image_path = get_upload_path($event['image_url']);

        if (file_exists($image_path)) {
            if (!unlink($image_path)) {
                error_log("无法删除事件图片: " . $image_path);
                // 这里可以选择抛出异常或继续执行
            }
        }
    }

    // 3. 从数据库删除事件
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);

    // 提交事务
    $pdo->commit();

    // 删除成功，重定向回事件列表
    header('Location: /ticket/admin/events/?deleted=1');
    exit();

} catch (PDOException $e) {
    // 回滚事务
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('HTTP/1.0 500 Internal Server Error');
    exit('数据库错误: ' . $e->getMessage());

} catch (Exception $e) {
    // 回滚事务
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('HTTP/1.0 500 Internal Server Error');
    exit('操作失败: ' . $e->getMessage());
}

?>