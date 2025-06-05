<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/config/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] .'/ticket/includes/autoload.php';

$order = new Order();

// Pagination setup
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($currentPage - 1) * $perPage;

// Search and filter handling
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Get orders with filters
$orders = $order->getAllOrders($offset, $perPage, $search, $statusFilter);
$totalOrders = $order->countAllOrders($search, $statusFilter);
$totalPages = ceil($totalOrders / $perPage);

// Status counts for filter tabs
$statusCounts = $order->getOrderStatusCounts();

$pageTitle = "Manage Orders";
include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mt-4"><?= htmlspecialchars($pageTitle) ?></h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars(urldecode($_GET['success'])) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars(urldecode($_GET['error'])) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-table me-1"></i>
                            Order List
                        </div>
                        <div>
                            <a href="export_orders.php" class="btn btn-sm btn-success">
                                <i class="fas fa-file-export"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter and Search -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="btn-group" role="group">
                                <a href="?status=" class="btn btn-sm btn-outline-secondary <?= empty($statusFilter) ? 'active' : '' ?>">
                                    All <span class="badge bg-secondary"><?= $statusCounts['total'] ?></span>
                                </a>
                                <?php foreach (['pending', 'processing', 'completed', 'cancelled'] as $status): ?>
                                    <a href="?status=<?= $status ?>" class="btn btn-sm btn-outline-<?= getStatusColor($status) ?> <?= $statusFilter === $status ? 'active' : '' ?>">
                                        <?= ucfirst($status) ?> <span class="badge bg-<?= getStatusColor($status) ?>"><?= $statusCounts[$status] ?? 0 ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <form method="get" class="input-group">
                                <input type="text" class="form-control form-control-sm" placeholder="Search orders..." name="search" value="<?= htmlspecialchars($search) ?>">
                                <?php if ($statusFilter): ?>
                                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $orderItem): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($orderItem['order_number']) ?></td>
                                        <td><?= htmlspecialchars($orderItem['customer_name']) ?></td>
                                        <td><?= date('M j, Y H:i', strtotime($orderItem['created_at'])) ?></td>
                                        <td><?= $orderItem['ticket_count'] ?></td>
                                        <td><?= number_format($orderItem['total_amount'], 2) ?></td>
                                        <td>
                                                <span class="badge bg-<?= getStatusColor($orderItem['status']) ?>">
                                                    <?= ucfirst($orderItem['status']) ?>
                                                </span>
                                        </td>
                                        <td>
                                            <a href="edit.php?id=<?= $orderItem['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view.php?id=<?= $orderItem['id'] ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-order" data-id="<?= $orderItem['id'] ?>" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $statusFilter ? '&status='.urlencode($statusFilter) : '' ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $statusFilter ? '&status='.urlencode($statusFilter) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $statusFilter ? '&status='.urlencode($statusFilter) : '' ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this order? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete Order</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>

<script>
    // Delete order confirmation
    document.querySelectorAll('.delete-order').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            document.getElementById('confirmDelete').href = `delete.php?id=${orderId}`;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });

    // Auto-close alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);
</script>

<?php
// Helper function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>
