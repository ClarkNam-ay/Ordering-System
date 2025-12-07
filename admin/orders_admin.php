<?php
require '../config.php';
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Orders — Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <!-- Header / Navigation -->
    <header class="dashboard-header">
        <?php include 'admin_nav.php'; ?>
    </header>

    <div class="container">
        <h2>All Orders</h2>
        <?php if (empty($orders)): ?>
        <div class="card">No orders yet.</div>
        <?php else: ?>
        <?php foreach ($orders as $o): ?>
        <div class="card">
            <strong>Order #<?= $o['id'] ?></strong> — <?= htmlspecialchars($o['customer_name']) ?>
            (<?= htmlspecialchars($o['status']) ?>)
            <div>Total: ₱ <?= number_format($o['total'],2) ?> —
                <?= $o['created_at'] ?></div>
            <div>
                <a class="btn" href="order_success.php?id=<?= $o['id'] ?>">View</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>