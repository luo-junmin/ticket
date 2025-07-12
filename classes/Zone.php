<?php
class Zone {
    private $pdo;

    public function __construct() {
        $this->pdo = $this->getPDO();
    }

    private function getPDO() {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/database.php';
        return get_pdo_connection();
    }

    public function getZonesByEvent($event_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM ticket_zones 
            WHERE event_id = ? 
            ORDER BY zone_name
        ");
        $stmt->execute([$event_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addZone($data) {
        // 设置可用座位数等于容量
        $data['available_seats'] = $data['capacity'];

        $stmt = $this->pdo->prepare("
            INSERT INTO ticket_zones 
            (event_id, zone_name, zone_category, base_price, capacity, available_seats) 
            VALUES (:event_id, :zone_name, :zone_category, :base_price, :capacity, :available_seats)
        ");

        return $stmt->execute($data);
    }

    public function updateZone($zone_id, $data) {
        // 首先获取当前容量
        $current = $this->getZoneById($zone_id);

        // 计算新的可用座位数
        if ($current['capacity'] != $data['capacity']) {
            $difference = $data['capacity'] - $current['capacity'];
            $data['available_seats'] = $current['available_seats'] + $difference;
        } else {
            $data['available_seats'] = $current['available_seats'];
        }

        $data['zone_id'] = $zone_id;

        $stmt = $this->pdo->prepare("
            UPDATE ticket_zones SET 
            zone_name = :zone_name,
            zone_category = :zone_category,
            base_price = :base_price,
            capacity = :capacity,
            available_seats = :available_seats
            WHERE zone_id = :zone_id
        ");

        return $stmt->execute($data);
    }

    public function deleteZone($zone_id) {
        $stmt = $this->pdo->prepare("DELETE FROM ticket_zones WHERE zone_id = ?");
        return $stmt->execute([$zone_id]);
    }

    private function getZoneById($zone_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ticket_zones WHERE zone_id = ?");
        $stmt->execute([$zone_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>