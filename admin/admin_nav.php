<?php
// nav.php - Enhanced navigation bar
$cart_count = array_sum($_SESSION['cart'] ?? []);
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="dashboard-header">
    <div class="header-container">
        <a href="admin_dashboard.php" class="logo">
            🛒 Ordering System - ADMIN
        </a>

        <nav class="main-nav">
            <a href="admin_dashboard.php" class="nav-link <?= $current_page === 'admin_dashboard' ? 'active' : '' ?>">
                📊 Dashboard
            </a>
            <a href="manage_products.php" class="nav-link <?= $current_page === 'manage_products' ? 'active' : '' ?>">
                📋 Manage Products
            </a>
            <a href="users_admin.php" class="nav-link <?= $current_page === 'users_admin' ? 'active' : '' ?>">
                🛒 Manage Users
                <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <a href="orders_admin.php" class="nav-link <?= $current_page === 'orders_admin' ? 'active' : '' ?>">
                📦 View Orders
            </a>
        </nav>

        <div class="user-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
            <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="logout.php" class="logout-link">Logout</a>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>