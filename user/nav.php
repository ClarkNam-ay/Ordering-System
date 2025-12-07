<?php
// nav.php - Enhanced navigation bar
$cart_count = array_sum($_SESSION['cart'] ?? []);
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="dashboard-header">
    <div class="header-container">
        <a href="dashboard.php" class="logo">
            🛒 Ordering System
        </a>

        <nav class="main-nav">
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                📊 Dashboard
            </a>
            <a href="menu.php" class="nav-link <?= $current_page === 'menu' ? 'active' : '' ?>">
                📋 Menu
            </a>
            <a href="cart.php" class="nav-link <?= $current_page === 'cart' ? 'active' : '' ?>">
                🛒 View Cart
                <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <a href="my_orders.php" class="nav-link <?= $current_page === 'orders_admin' ? 'active' : '' ?>">
                📦 My Orders
            </a>
        </nav>

        <div class="user-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
            <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="../logout.php" class="logout-link">Logout</a>
            <?php else: ?>
            <a href="../login.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>