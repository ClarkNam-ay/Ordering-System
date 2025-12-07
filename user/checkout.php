<?php
require 'config.php';
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php'); exit;
}

// fetch items like in cart.php
$placeholders = implode(',', array_fill(0, count($cart), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($cart));
$rows = $stmt->fetchAll();

$items = []; $total = 0;
foreach ($rows as $r) {
  $qty = $cart[$r['id']];
  $subtotal = $r['price'] * $qty;
  $items[] = array_merge($r, ['qty'=>$qty, 'subtotal'=>$subtotal]);
  $total += $subtotal;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Checkout — Ordering System</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <h2>Checkout</h2>

        <div class="card">
            <form method="post" action="process_order.php">
                <?= csrf_input() ?>
                <label>Customer name
                    <input type="text" name="customer_name" required>
                </label>
                <label>Phone
                    <input type="text" name="customer_phone">
                </label>
                <label>Address
                    <textarea name="customer_address" rows="3"></textarea>
                </label>

                <h3>Order Summary</h3>
                <ul>
                    <?php foreach ($items as $it): ?>
                    <li><?= htmlspecialchars($it['name']) ?> — <?= $it['qty'] ?> × ₱
                        <?= number_format($it['price'],2) ?> = ₱ <?= number_format($it['subtotal'],2) ?></li>
                    <?php endforeach; ?>
                </ul>

                <p><strong>Total: ₱ <?= number_format($total,2) ?></strong></p>
                <input type="hidden" name="total" value="<?= number_format($total,2, '.', '') ?>">

                <button type="submit" class="btn">Place Order</button>
            </form>
        </div>
    </div>
</body>

</html>