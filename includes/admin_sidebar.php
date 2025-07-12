<!-- includes/admin_sidebar.php -->
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="/ticket/admin">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/events/') !== false ? 'active' : '' ?>" href="/ticket/admin/events">
                    <i class="bi bi-calendar-event me-2"></i>
                    Events
                </a>
            </li>
            <!-- 添加Zone管理入口 -->
            <li class="nav-item">
                <a class="nav-link" href="/ticket/admin/events/zones.php">
                    <i class="bi bi-grid me-2"></i>
                    Zone
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/orders/') !== false ? 'active' : '' ?>" href="/ticket/admin/orders">
                    <i class="bi bi-receipt me-2"></i>
                    Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/users/') !== false ? 'active' : '' ?>" href="/ticket/admin/users">
                    <i class="bi bi-people me-2"></i>
                    Users
                </a>
            </li>
        </ul>
    </div>
</div>