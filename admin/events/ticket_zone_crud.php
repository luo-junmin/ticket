<?php
// admin/events/ticket_zone_crud.php
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_auth.php';

$pdo = Database::getInstance()->getConnection();

$eventId = $_GET['event_id'] ?? 0;
$action = $_GET['action'] ?? '';
$zoneId = $_GET['zone_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'event_id' => $eventId,
        'zone_name' => $_POST['zone_name'],
        'zone_category' => $_POST['zone_category'],
        'base_price' => $_POST['base_price'],
        'capacity' => $_POST['capacity'],
        'available_seats' => $_POST['available_seats'],
    ];

    if ($action === 'edit' && $zoneId) {
        $stmt = $pdo->prepare("UPDATE ticket_zones SET zone_name=?, zone_category=?, base_price=?, capacity=?, available_seats=? WHERE zone_id=?");
        $stmt->execute([
            $data['zone_name'], $data['zone_category'], $data['base_price'], $data['capacity'], $data['available_seats'], $zoneId
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ticket_zones (event_id, zone_name, zone_category, base_price, capacity, available_seats) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['event_id'], $data['zone_name'], $data['zone_category'], $data['base_price'], $data['capacity'], $data['available_seats']
        ]);
    }

    header("Location: zones.php?event_id=$eventId");
    exit;
}

if ($action === 'delete' && $zoneId) {
    $pdo->prepare("DELETE FROM ticket_zones WHERE zone_id = ?")->execute([$zoneId]);
    header("Location: zones.php?event_id=$eventId");
    exit;
}

$zones = [];
if ($eventId) {
    $stmt = $pdo->prepare("SELECT * FROM ticket_zones WHERE event_id = ? ORDER BY zone_id ASC");
    $stmt->execute([$eventId]);
    $zones = $stmt->fetchAll();
}

$editZone = null;
if ($action === 'edit' && $zoneId) {
    $stmt = $pdo->prepare("SELECT * FROM ticket_zones WHERE zone_id = ?");
    $stmt->execute([$zoneId]);
    $editZone = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket Zones</title>
    <link href="/ticket/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Manage Ticket Zones for Event ID <?= htmlspecialchars($eventId) ?></h3>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Zone Name</label>
            <input type="text" name="zone_name" class="form-control" required value="<?= $editZone['zone_name'] ?? '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="zone_category" class="form-control" required>
                <?php
                foreach (["cat1", "cat2", "cat3", "cat4", "restricted"] as $cat) {
                    $selected = ($editZone['zone_category'] ?? '') === $cat ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Base Price</label>
            <input type="number" step="0.01" name="base_price" class="form-control" required value="<?= $editZone['base_price'] ?? '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" class="form-control" required value="<?= $editZone['capacity'] ?? '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Available Seats</label>
            <input type="number" name="available_seats" class="form-control" required value="<?= $editZone['available_seats'] ?? '' ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="zones.php?event_id=<?= $eventId ?>" class="btn btn-secondary">Cancel</a>
    </form>

    <hr>
    <h4>Existing Zones</h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Capacity</th>
            <th>Available</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($zones as $z): ?>
            <tr>
                <td><?= $z['zone_id'] ?></td>
                <td><?= htmlspecialchars($z['zone_name']) ?></td>
                <td><?= $z['zone_category'] ?></td>
                <td><?= $z['base_price'] ?></td>
                <td><?= $z['capacity'] ?></td>
                <td><?= $z['available_seats'] ?></td>
                <td>
                    <a href="zones.php?event_id=<?= $eventId ?>&action=edit&zone_id=<?= $z['zone_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="zones.php?event_id=<?= $eventId ?>&action=delete&zone_id=<?= $z['zone_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
