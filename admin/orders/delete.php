<?php
// admin/orders/delete.php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';

// Check if admin is logged in
//admin_check_login();

// Check if order_id is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid order ID";
    header("Location: index.php");
    exit();
}
trigger_error(print_r($_SESSION,true));
$order_id = (int)$_GET['id'];
$admin_id = $_SESSION['admin_logged_in']; // Assuming admin ID is stored in session

$conn = get_pdo_connection();
try {
    // Start transaction
    $conn->beginTransaction();

    // 1. Check if order exists
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND is_deleted = 0");
    $stmt->execute([$order_id]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Order not found or already deleted";
        header("Location: index.php");
        exit();
    }

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Create backup in archive table (soft delete)
    // Archive orders
    $stmt = $conn->prepare("
        INSERT INTO orders_archive 
        (original_order_id, user_id, event_id, total_amount, payment_method, 
         payment_status, payment_transaction_id, payment_date, transaction_expiry, 
         created_at, deleted_by, deleted_at, deletion_reason)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Admin deletion')
    ");
    $stmt->execute([
        $order['order_id'],
        $order['user_id'],
        $order['event_id'],
        $order['total_amount'],
        $order['payment_method'],
        $order['payment_status'],
        $order['payment_transaction_id'],
        $order['payment_date'],
        $order['transaction_expiry'],
        $order['created_at'],
        $admin_id
    ]);

    // Archive order details
//    $stmt = $conn->prepare("
//        INSERT INTO order_details_archive
//        SELECT *, NOW(), ?
//        FROM order_details
//        WHERE order_id = ?
//    ");
//    $stmt->execute([$admin_id, $order_id]);
    $stmt = $conn->prepare("
        INSERT INTO order_details_archive (
            detail_id , order_id, zone_id, discount_id,  quantity, price_per_ticket, 
            deleted_by, deleted_at
        )
        SELECT 
            detail_id , order_id, zone_id, discount_id,  quantity, price_per_ticket, 
            ?, NOW()
        FROM order_details
        WHERE order_id = ?
    ");
    $stmt->execute([$admin_id, $order_id]);

    // 3. Mark as deleted in main tables (soft delete)
    $stmt = $conn->prepare("UPDATE orders SET is_deleted = 1 WHERE order_id = ?");
    $stmt->execute([$order_id]);

    // 4. Log the deletion in audit table
    $stmt = $conn->prepare("
        INSERT INTO audit_log 
        (user_id, user_type, action, entity_type, entity_id, details, created_at)
        VALUES (?, 'admin', 'delete', 'order', ?, ?, NOW())
    ");
    $stmt->execute([
        $admin_id,
        $order_id,
        "Deleted order #$order_id with status: {$order['payment_status']}"
    ]);

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = "Order #$order_id archived successfully";
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Error archiving order: " . $e->getMessage();
    error_log("Order deletion error: " . $e->getMessage());
}

header("Location: index.php");
exit();
?>