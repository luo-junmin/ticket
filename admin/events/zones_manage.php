<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Zone.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/Event.php';

// 获取事件ID
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    header('Location: zones.php');
    exit;
}

$zone = new Zone();
$event = new Event();
$error = '';
$success = '';

// 获取活动信息
$events = $event->getAllEvents();
$event_title = '';
foreach ($events as $event) {
    if ($event['event_id'] == $event_id) {
        $event_title = htmlspecialchars($event['title']);
        break;
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    try {
        if (isset($_POST['add_zone'])) {
            // 添加新区
            $data = [
                'event_id' => $event_id,
                'zone_name' => trim($_POST['zone_name']),
                'zone_category' => $_POST['zone_category'],
                'base_price' => (float)$_POST['base_price'],
                'capacity' => (int)$_POST['capacity']
            ];

            if ($zone->addZone($data)) {
                $success = 'Zone added successfully';
            }
        }
        elseif (isset($_POST['update_zone'])) {
            // 更新区域
            $zone_id = (int)$_POST['zone_id'];
            $data = [
                'zone_name' => trim($_POST['zone_name']),
                'zone_category' => $_POST['zone_category'],
                'base_price' => (float)$_POST['base_price'],
                'capacity' => (int)$_POST['capacity']
            ];

            if ($zone->updateZone($zone_id, $data)) {
                $success = 'Zone update successful';
            }
        }
        elseif (isset($_POST['delete_zone'])) {
            // 删除区域
            $zone_id = (int)$_POST['zone_id'];
            if ($zone->deleteZone($zone_id)) {
                $success = 'Zone deleted successfully';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 获取该事件的所有区域
$zones = $zone->getZonesByEvent($event_id);
//$event_title = $event->getEventTitle($event_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Area - <?= htmlspecialchars($event_title) ?> | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_header.php'; ?>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Management Area - <?= htmlspecialchars($event_title) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="zones.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Return to Zone List
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Zone</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="add_zone" value="1">

                                <div class="mb-3">
                                    <label for="zone_name" class="form-label">Zone Name</label>
                                    <input type="text" class="form-control" id="zone_name" name="zone_name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="zone_category" class="form-label">Zone category</label>
                                    <select class="form-select" id="zone_category" name="zone_category" required>
                                        <option value="cat1">Cat1</option>
                                        <option value="cat2">Cat2</option>
                                        <option value="cat3">Cat3</option>
                                        <option value="cat4">Cat4</option>
                                        <option value="restricted">Restricted</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="base_price" class="form-label">Base Price</label>
                                    <input type="number" step="0.01" class="form-control" id="base_price" name="base_price" required>
                                </div>

                                <div class="mb-3">
                                    <label for="capacity" class="form-label">Capacity</label>
                                    <input type="number" class="form-control" id="capacity" name="capacity" required>
                                </div>

                                <button type="submit" class="btn btn-primary">Adding a Zone</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Zone List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Capacity</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($zones as $zone): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($zone['zone_name']) ?></td>
                                            <td>
                                                <?php
                                                $categoryMap = [
                                                    'cat1' => 'Cat1',
                                                    'cat2' => 'Cat2',
                                                    'cat3' => 'Cat3',
                                                    'cat4' => 'Cat4',
                                                    'restricted' => 'c'
                                                ];
                                                echo $categoryMap[$zone['zone_category']] ?? $zone['zone_category'];
                                                ?>
                                            </td>
                                            <td><?= number_format($zone['base_price'], 2) ?></td>
                                            <td><?= $zone['available_seats'] . '/' . $zone['capacity'] ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-zone"
                                                        data-zone-id="<?= $zone['zone_id'] ?>"
                                                        data-zone-name="<?= htmlspecialchars($zone['zone_name']) ?>"
                                                        data-zone-category="<?= $zone['zone_category'] ?>"
                                                        data-base-price="<?= $zone['base_price'] ?>"
                                                        data-capacity="<?= $zone['capacity'] ?>">
                                                    Edit
                                                </button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="zone_id" value="<?= $zone['zone_id'] ?>">
                                                    <input type="hidden" name="delete_zone" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this zone?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- 编辑模态框 -->
<div class="modal fade" id="editZoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editing Area</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="update_zone" value="1">
                    <input type="hidden" name="zone_id" id="edit_zone_id">

                    <div class="mb-3">
                        <label for="edit_zone_name" class="form-label">Zone Name</label>
                        <input type="text" class="form-control" id="edit_zone_name" name="zone_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_zone_category" class="form-label">Zone category</label>
                        <select class="form-select" id="edit_zone_category" name="zone_category" required>
                            <option value="cat1">Cat1</option>
                            <option value="cat2">Cat2</option>
                            <option value="cat3">Cat3</option>
                            <option value="cat4">Cat4</option>
                            <option value="restricted">Restricted</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_base_price" class="form-label">Base Price</label>
                        <input type="number" step="0.01" class="form-control" id="edit_base_price" name="base_price" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/includes/admin_footer.php'; ?>

<script>
    // 编辑区域模态框
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-zone');
        const editModal = new bootstrap.Modal(document.getElementById('editZoneModal'));

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_zone_id').value = this.dataset.zoneId;
                document.getElementById('edit_zone_name').value = this.dataset.zoneName;
                document.getElementById('edit_zone_category').value = this.dataset.zoneCategory;
                document.getElementById('edit_base_price').value = this.dataset.basePrice;
                document.getElementById('edit_capacity').value = this.dataset.capacity;

                editModal.show();
            });
        });
    });
</script>
</body>
</html>