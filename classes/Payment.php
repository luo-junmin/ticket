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
                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET payment_method = ?, 
                        payment_status = 'completed',
                        payment_transaction_id = ?,
                        payment_date = NOW()
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
            $stmt->execute([$orderId]);
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
}