<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

class Order {
    private $pdo;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    public function getAllOrders($offset = 0, $limit = 20, $search = '', $statusFilter = '') {
        $sql = "SELECT 
                o.order_id,
                o.payment_transaction_id AS order_number,
                u.name AS customer_name,
                u.email AS customer_email,
                e.title,
                o.total_amount,
                o.payment_status AS status,
                o.created_at,
                o.payment_date,
                SUM(od.quantity) AS ticket_count
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN events e ON o.event_id = e.event_id
            LEFT JOIN order_details od ON o.order_id = od.order_id";

        $where = [];
        $params = [];

        // 添加搜索条件
        if (!empty($search)) {
            $where[] = "(o.payment_transaction_id LIKE :search OR 
                    u.name LIKE :search OR 
                    u.email LIKE :search OR 
                    e.event_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // 添加状态过滤
        if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'completed', 'failed'])) {
            $where[] = "o.payment_status = :status";
            $params[':status'] = $statusFilter;
        }

        // 组合WHERE条件
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 按订单分组
        $sql .= " GROUP BY o.order_id";

        // 按创建时间降序排列
        $sql .= " ORDER BY o.created_at DESC";

        // 添加分页
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;
//        trigger_error("SQL: ".$sql);

        try {
            $stmt = $this->pdo->prepare($sql);

            // 绑定参数
            foreach ($params as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $paramType);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // 记录错误日志
            error_log("获取订单列表错误: " . $e->getMessage());
            return [];
        }
    }

    public function getOrderCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders");
        return $stmt->fetchColumn();
    }

    public function countAllOrders($search = '', $statusFilter = '') {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS total 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN events e ON o.event_id = e.event_id";

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(o.payment_transaction_id LIKE :search OR 
                    u.name LIKE :search OR 
                    u.email LIKE :search OR 
                    e.event_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'completed', 'failed'])) {
            $where[] = "o.payment_status = :status";
            $params[':status'] = $statusFilter;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("计算订单总数错误: " . $e->getMessage());
            return 0;
        }
    }

    public function getOrderStatusCounts() {
        $sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) AS failed
            FROM orders";

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("获取订单状态统计错误: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'failed' => 0
            ];
        }
    }

    public function getRecentOrders($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, e.title AS event_title, u.email AS user_email
            FROM orders o
            JOIN events e ON o.event_id = e.event_id
            JOIN users u ON o.user_id = u.user_id
            ORDER BY o.order_date DESC
            LIMIT ?
        ");
//        $stmt->execute([$limit]);
        $stmt->execute($limit);
        return $stmt->fetchAll();
    }

    public function getOrderById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders WHERE order_id = ?
        ");
        $stmt->execute($id);
        return $stmt->fetchAll();
    }

    public function updateTransactionId($orderId, $transactionId) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_transaction_id = ? 
            WHERE order_id = ? AND payment_status = 'pending'
        ");
        return $stmt->execute([$transactionId, $orderId]);
    }

    public function updatePaymentStatus($orderId, $status, $transactionId = null) {
        $sql = "UPDATE orders SET payment_status = ?";
        $params = [$status];

        if ($transactionId) {
            $sql .= ", payment_transaction_id = ?";
            $params[] = $transactionId;
        }

        if ($status === 'completed') {
            $sql .= ", payment_date = NOW()";
        }

        $sql .= " WHERE order_id = ?";
        $params[] = $orderId;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

}