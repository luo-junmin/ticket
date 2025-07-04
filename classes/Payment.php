<?php
class Payment {
    private $pdo;
    private $lang;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->lang = Language::getInstance();
    }

    public function processPayment($orderId, $method) {
        $this->pdo->beginTransaction();

        try {
            // Verify order exists and is unpaid
            $stmt = $this->pdo->prepare("
                SELECT * FROM orders 
                WHERE order_id = ? AND payment_status = 'pending'
                FOR UPDATE
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => $this->lang->get('invalid_order')];
            }

            // Process payment based on method
            $success = false;
            $transactionId = null;

            switch ($method) {
                case 'paynow':
                    $result = $this->processPayNow($order['total_amount']);
                    $success = $result['success'];
                    $transactionId = $result['transaction_id'];
                    break;

                case 'credit_card':
                    $result = $this->processCreditCard($order['total_amount']);
                    $success = $result['success'];
                    $transactionId = $result['transaction_id'];
                    break;

                default:
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => $this->lang->get('invalid_payment_method')];
            }

            if ($success) {
                // Update order status
                $now = date('Y-m-d H:i:s');
                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET payment_method = ?, 
                        payment_status = 'completed',
                        payment_transaction_id = ?,
                        payment_date = '$now'
                    WHERE order_id = ?
                ");
                $stmt->execute([$method, $transactionId, $orderId]);

                $this->pdo->commit();
                return ['success' => true];
            }

            $this->pdo->rollBack();
            return ['success' => false, 'message' => $this->lang->get('payment_failed')];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function processPayNow($amount) {
        // In a real implementation, this would call the PayNow API
        // This is a simulation that always succeeds
        return [
            'success' => true,
            'transaction_id' => 'PN' . strtoupper(bin2hex(random_bytes(8)))
        ];
    }

    private function processCreditCard($amount) {
        // In a real implementation, this would call the payment gateway API
        // This is a simulation that always succeeds
        return [
            'success' => true,
            'transaction_id' => 'CC' . strtoupper(bin2hex(random_bytes(8)))
        ];
    }

    public function getOrder($orderId, $userId = null) {
        $sql = "
            SELECT o.*, e.title, e.event_date, e.location
            FROM orders o
            JOIN events e ON o.event_id = e.event_id
            WHERE o.order_id = ?
        ";

        if ($userId) {
            $sql .= " AND o.user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId, $userId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $parameters = array($orderId);
            $stmt->execute($parameters);

//            $stmt->execute([$orderId]);
        }

        return $stmt->fetch();
    }


    function generateTransactionId($orderId, $paymentMethod) {
        $prefix = '';
        switch ($paymentMethod) {
            case 'paynow':
                $prefix = 'PN';
                break;
            case 'credit_card':
                $prefix = 'CC';
                break;
            case 'alipay':
                $prefix = 'AP';
                break;
            default:
                $prefix = 'TX';
        }

        $timestamp = time();
        $randomStr = bin2hex(random_bytes(4)); // 8字符随机字符串

        return sprintf('%s-%d-%s-%d', $prefix, $orderId, $randomStr, $timestamp);
    }

    public function uploadReceipt($orderId, $userId, $file) {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 2MB.'];
        }

        // Create upload directory if not exists
//        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ticket/uploads/receipts/';
        $uploadDir = __DIR__ . "/../.." . UPLOADS_PATH. '/receipts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . $orderId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Save to database
            $now = date('Y-m-d H:i:s');
            $relativePath = '/receipts/' . $filename;
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_receipts 
                (order_id, user_id, file_url, upload_datetime) 
                VALUES (?, ?, ?, '$now')
                ON DUPLICATE KEY UPDATE 
                file_url = VALUES(file_url), 
                upload_datetime = '$now'
            ");
            $stmt->execute([$orderId, $userId, $relativePath]);

            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to upload file.'];
    }

    public function hasPendingReceipt($orderId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM payment_receipts 
            WHERE order_id = :orderId 
            AND payment_status = 'pending'
        ");
//        $stmt->execute([$orderId]);
        $parameters = array(
            ':orderId' => $orderId
        );
        $stmt->execute($parameters);

        return $stmt->fetchColumn() > 0;
    }

    public function confirmPayment($orderId) {
        // This just updates the order status to pending
        // Admin will verify and complete the payment later
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_status = 'pending' 
            WHERE order_id = :orderId
        ");
        $parameters = array(
            ':orderId' => $orderId
        );
        return $stmt->execute($parameters);

//        return $stmt->execute([$orderId]);
    }

    //-------------

    // Add these methods to the Payment class

    public function getPendingPayments() {
        $stmt = $this->pdo->prepare("
        SELECT pr.*, u.email, o.total_amount 
        FROM payment_receipts pr
        JOIN users u ON pr.user_id = u.user_id
        JOIN orders o ON pr.order_id = o.order_id 
        WHERE pr.payment_status = 'pending'
        ORDER BY pr.upload_datetime ASC
    ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function approvePayment($receiptId, $remarks = '') {
        $this->pdo->beginTransaction();

        try {
            // Update receipt status
            $now = date('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare("
            UPDATE payment_receipts 
            SET payment_status = 'completed', 
                admin_remarks = :remarks,
                verified_at = '$now'
            WHERE id = :receiptId
            ");
            $stmt->bindParam(':remarks', $remarks);
            $stmt->bindParam(':receiptId', $receiptId);
            $stmt->execute();
             // Get order ID
            $orderId = $this->getOrderIdFromReceipt($receiptId);

            // Update order status
            $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_status = 'completed' 
            WHERE order_id = :orderId
            ");
            $parameters = array(
                ':orderId' => $orderId
            );
            $stmt->execute($parameters);
//            trigger_error(print_r($stmt->errorInfo(), true));

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function rejectPayment($receiptId, $remarks) {
        $this->pdo->beginTransaction();

        try {
            // Update receipt status
            $now = date('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare("
            UPDATE payment_receipts 
            SET payment_status = 'failed', 
                admin_remarks = :remarks,
                verified_at = '$now'
            WHERE id = :receiptId
            ");
            $parameters = array(
                ':remarks' => $remarks,
                ':receiptId' => $receiptId
            );
            $stmt->execute($parameters);

            // Get order ID
            $orderId = $this->getOrderIdFromReceipt($receiptId);

            // Update order status
            $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_status = 'failed' 
            WHERE order_id = :orderId
            ");
            $parameters = array(
                ':orderId' => $orderId
            );
            $stmt->execute($parameters);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getOrderIdFromReceipt($receiptId) {
        $stmt = $this->pdo->prepare("
        SELECT order_id FROM payment_receipts WHERE id = :receiptId
    ");
        $stmt->bindParam(':receiptId', $receiptId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getUserEmailFromReceipt($receiptId) {
        $stmt = $this->pdo->prepare("
        SELECT u.email 
        FROM payment_receipts pr
        JOIN users u ON pr.user_id = u.user_id
        WHERE pr.id = :receiptId
    ");
//        $stmt->execute([$receiptId]);
        $parameters = array(
            ':receiptId' => $receiptId
        );
        $stmt->execute($parameters);
        return $stmt->fetchColumn();
    }

    // Add these methods to your Payment class

    public function getPendingPaymentsCount() {
        $stmt = $this->pdo->prepare("
        SELECT COUNT(*) 
        FROM payment_receipts 
        WHERE payment_status = 'pending'
    ");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getRecentPendingPayments($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT pr.*, u.email, o.total_amount 
            FROM payment_receipts pr
            JOIN users u ON pr.user_id = u.user_id
            JOIN orders o ON pr.order_id = o.order_id
            WHERE pr.payment_status = 'pending'
            ORDER BY pr.upload_datetime DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

}