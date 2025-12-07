<?php
require 'config.php';

// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// require admin
if (empty($_SESSION['is_admin'])) {
    // optional: show friendly message or redirect to normal dashboard
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard — Ordering System</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
    .admin-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }
    </style>
</head>

<body>
    <?php if (file_exists('admin_nav.php')) include 'admin_nav.php'; ?>
    <div class="container">
        <div class="card">
            <h2>Admin Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> — you are signed in as an
                <strong>admin</strong>.
            </p>

            <!-- <div class="admin-actions">
                <a class="btn" href="orders_admin.php">View Orders</a>
                <a class="btn" href="products_admin.php">Manage Products</a>
                <a class="btn" href="users_admin.php">Manage Users</a>
            </div> -->

            <h3>Recent Orders</h3>
            <?php
      // sample recent orders preview (limit 6)
      $stmt = $pdo->query("SELECT id, customer_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 6");
      $orders = $stmt->fetchAll();
      if (empty($orders)) {
          echo '<p>No recent orders.</p>';
      } else {
          echo '<ul>';
          foreach ($orders as $o) {
              echo '<li>Order #' . $o['id'] . ' — ' . htmlspecialchars($o['customer_name']) . ' — ₱ ' . number_format($o['total'],2) . ' — ' . htmlspecialchars($o['status']) . ' — ' . $o['created_at'] . '</li>';
          }
          echo '</ul>';
      }
    ?>
        </div>
    </div>
</body>

</html>