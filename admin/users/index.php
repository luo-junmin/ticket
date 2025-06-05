<?php
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/classes/User.php';
$user = new User();
$users = $user->getAllUsers();

// 处理用户状态更改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    $action = $_POST['action'];

    switch ($action) {
        case 'activate':
            $user->updateUserStatus($userId, 1);
            break;
        case 'deactivate':
            $user->updateUserStatus($userId, 0);
            break;
        case 'delete':
            $user->deleteUser($userId);
            break;
        case 'make_admin':
            $user->updateUserRole($userId, 'admin');
            break;
        case 'remove_admin':
            $user->updateUserRole($userId, 'user');
            break;
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | <?= SITE_NAME ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_header.php'; ?>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add New User
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                            </td>
                            <td>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <?php if ($user['is_active']): ?>
                                                    <button type="submit" name="action" value="deactivate" class="dropdown-item">Deactivate</button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="activate" class="dropdown-item">Activate</button>
                                                <?php endif; ?>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <button type="submit" name="action" value="remove_admin" class="dropdown-item">Remove Admin</button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="make_admin" class="dropdown-item">Make Admin</button>
                                                <?php endif; ?>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <button type="submit" name="action" value="delete" class="dropdown-item text-danger">Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/admin_footer.php'; ?>
</body>
</html>