<?php
class Event {
    private $pdo;
    private $lang;

    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->lang = Language::getInstance();
    }

    public function getActiveEvents() {
        $stmt = $this->pdo->query("
            SELECT e.*, 
                   MIN(z.base_price) as min_price, 
                   MAX(z.base_price) as max_price
            FROM events e
            LEFT JOIN ticket_zones z ON e.event_id = z.event_id
            WHERE e.is_active = TRUE AND e.event_date >= NOW()
            GROUP BY e.event_id
            ORDER BY e.event_date ASC
        ");
        return $stmt->fetchAll();
    }

    public function getEventById($eventId) {
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   MIN(z.base_price) as min_price, 
                   MAX(z.base_price) as max_price
            FROM events e
            LEFT JOIN ticket_zones z ON e.event_id = z.event_id
            WHERE e.event_id = ?
            GROUP BY e.event_id
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetch();
    }

    public function getTicketZones($eventId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM ticket_zones 
            WHERE event_id = ? 
            ORDER BY 
                CASE zone_category 
                    WHEN 'cat1' THEN 1
                    WHEN 'cat2' THEN 2
                    WHEN 'cat3' THEN 3
                    WHEN 'cat4' THEN 4
                    WHEN 'restricted' THEN 5
                    ELSE 6
                END,
                base_price DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public function getPricingTable($eventId) {
        $zones = $this->getTicketZones($eventId);
        $discounts = $this->getDiscountTypes();

        $pricingTable = [];

        foreach ($discounts as $discount) {
            $row = [
                'discount_name' => $discount['name'],
                'categories' => []
            ];

            foreach ($zones as $zone) {
                $stmt = $this->pdo->prepare("
                    SELECT discounted_price, availability_status 
                    FROM zone_discounts 
                    WHERE zone_id = ? AND discount_id = ?
                ");
                $stmt->execute([$zone['zone_id'], $discount['discount_id']]);
                $zoneDiscount = $stmt->fetch();

                if ($zoneDiscount) {
                    $price = $zoneDiscount['discounted_price'];
                    $status = $zoneDiscount['availability_status'];
                } else {
                    $price = $zone['base_price'] * (1 - $discount['discount_percent'] / 100);
                    $status = $this->getAvailabilityStatus($zone['available_seats']);
                }

                $row['categories'][$zone['zone_category']] = [
                    'price' => $price,
                    'status' => $status,
                    'display' => $this->formatPriceDisplay($price, $status)
                ];
            }

            $pricingTable[] = $row;
        }

        return $pricingTable;
    }

    private function getDiscountTypes() {
        $stmt = $this->pdo->query("SELECT * FROM discount_types WHERE is_active = TRUE");
        return $stmt->fetchAll();
    }

    private function getAvailabilityStatus($availableSeats) {
        if ($availableSeats <= 0) return 'sold_out';
        if ($availableSeats <= 5) return 'single_seats';
        if ($availableSeats <= 15) return 'limited';
        return 'available';
    }

    private function formatPriceDisplay($price, $status) {
        $formatted = 'SGD ' . number_format($price, 2);

        switch ($status) {
            case 'available': return $formatted . ' (Available)';
            case 'limited': return $formatted . ' (Limited Seats)';
            case 'single_seats': return $formatted . ' (Single Seats)';
            case 'sold_out': return $formatted . ' (Sold Out)';
            default: return $formatted;
        }
    }

    public function createOrder($userId, $eventId, $zoneId, $quantity, $discountId) {
        $this->pdo->beginTransaction();

        try {
            // Get zone details with lock
            $stmt = $this->pdo->prepare("
                SELECT * FROM ticket_zones 
                WHERE zone_id = ? 
                FOR UPDATE
            ");
            $stmt->execute([$zoneId]);
            $zone = $stmt->fetch();

            if (!$zone || $zone['available_seats'] < $quantity) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => $this->lang->get('not_enough_seats')
                ];
            }

            // Calculate price
            $stmt = $this->pdo->prepare("
                SELECT discounted_price FROM zone_discounts
                WHERE zone_id = ? AND discount_id = ?
            ");
            $stmt->execute([$zoneId, $discountId]);
            $discountPrice = $stmt->fetchColumn();

            $pricePerTicket = $discountPrice ?:
                $zone['base_price'] * (1 - $this->getDiscountPercent($discountId) / 100);

            $totalAmount = $pricePerTicket * $quantity;

            $payment_transaction_id = 1;    //???

            // Create order [order_id	user_id	event_id	total_amount	payment_method	payment_status	payment_transaction_id	order_date]
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, event_id, total_amount, payment_transaction_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $eventId, $totalAmount,$payment_transaction_id]);
            $orderId = $this->pdo->lastInsertId();

            // Add order details [detail_id	order_id	zone_id	quantity	price_per_ticket]
            $stmt = $this->pdo->prepare("
                INSERT INTO order_details (order_id, zone_id, quantity, price_per_ticket) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$orderId, $zoneId, $quantity, $pricePerTicket]);

            // Update available seats
            $stmt = $this->pdo->prepare("
                UPDATE ticket_zones 
                SET available_seats = available_seats - ? 
                WHERE zone_id = ?
            ");
            $stmt->execute([$quantity, $zoneId]);

            $this->pdo->commit();
            return ['success' => true, 'order_id' => $orderId];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => $this->lang->get('order_failed') . ': ' . $e->getMessage()
            ];
        }
    }

    private function getDiscountPercent($discountId) {
        $stmt = $this->pdo->prepare("
            SELECT discount_percent FROM discount_types 
            WHERE discount_id = ?
        ");
        $stmt->execute([$discountId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function getAllEvents() {
        $stmt = $this->pdo->query("
        SELECT * FROM events 
        ORDER BY event_date DESC
    ");
        return $stmt->fetchAll();
    }

    public function getEventCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM events");
        return $stmt->fetchColumn();
    }

    public function bulkUpdateStatus($eventIds, $status) {
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmt = $this->pdo->prepare("
        UPDATE events 
        SET is_active = ? 
        WHERE event_id IN ($placeholders)
    ");
        $stmt->execute(array_merge([$status], $eventIds));
        return $stmt->rowCount();
    }

    public function bulkDelete($eventIds) {
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmt = $this->pdo->prepare("
        DELETE FROM events 
        WHERE event_id IN ($placeholders)
    ");
        $stmt->execute($eventIds);
        return $stmt->rowCount();
    }

    public function addEvent($data) {
        try {
            $stmt = $this->pdo->prepare("
            INSERT INTO events (title, description, event_date, location, venue, image_url, is_active)
            VALUES (:title, :description, :event_date, :location, :venue, :image_url, :is_active)
        ");

            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':event_date' => $data['event_date'],
                ':location' => $data['location'],
                ':venue' => $data['venue'],
                ':image_url' => $data['image_url'] ?? null,
                ':is_active' => $data['is_active']
            ]);

            return ['success' => true, 'event_id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => '数据库错误: ' . $e->getMessage()];
        }
    }

    public function updateEvent($eventId, $data) {
        try {
            $stmt = $this->pdo->prepare("
            UPDATE events (title, description, event_date, location, venue, min_price, max_price, image_url, is_active)
            VALUES (:title, :description, :event_date, :location, :venue, :min_price, :max_price, :image_url, :is_active)
            WHERE event_id = :event_id
        ");

            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':event_date' => $data['event_date'],
                ':location' => $data['location'],
                ':venue' => $data['venue'],
                ':min_price' => $data['min_price'],
                ':max_price' => $data['max_price'],
                ':image_url' => $data['image_url'] ?? null,
                ':is_active' => $data['is_active'],
                ':event_id' => $eventId
            ]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => '数据库错误: ' . $e->getMessage()];
        }
    }

    public function getTicketPricing($eventId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM ticket_zones 
            WHERE event_id = ?
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public function getZoneDetails($zoneId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM ticket_zones 
            WHERE zone_id = ?
        ");
        $stmt->execute([$zoneId]);
        return $stmt->fetchAll();
    }
}