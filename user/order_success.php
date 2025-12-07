<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: menu.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { echo "Order not found"; exit; }

$stmt2 = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt2->execute([$id]);
$items = $stmt2->fetchAll();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Order #<?= $order['id'] ?> — Success</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <h2>Order placed — #<?= $order['id'] ?></h2>
        <div class="card">
            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
            <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['customer_address'])) ?></p>

            <h3>Items</h3>
            <ul>
                <?php foreach ($items as $it): ?>
                <li><?= htmlspecialchars($it['product_name']) ?> × <?= $it['qty'] ?> = ₱
                    <?= number_format($it['subtotal'],2) ?></li>
                <?php endforeach; ?>
            </ul>

            <p><strong>Total paid:</strong> ₱ <?= number_format($order['total'],2) ?></p>

            <a class="btn" href="menu.php">Back to Menu</a>
        </div>
    </div>
</body>

</html>