<!-- includes/admin_navbar.php -->
<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/ticket"><?= SITE_NAME ?> </a>
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/ticket/admin">Admin</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav ms-auto">
        <div class="nav-item text-nowrap px-3">
            <span class="text-white me-2"><?= $_SESSION['admin_email'] ?? "Not Admin"  ?></span>
            <a class="btn btn-sm btn-outline-light" href="/ticket/admin/logout.php">Sign out</a>
        </div>
    </div>
</nav>