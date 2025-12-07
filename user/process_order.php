<?php
require 'config.php';

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php'); exit;
}
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) { header('Location: cart.php'); exit; }

$customer_name = trim($_POST['customer_name'] ?? 'Guest');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_address = trim($_POST['customer_address'] ?? '');
$total = (float)($_POST['total'] ?? 0);

// Start transaction
$pdo->beginTransaction();

try {
    // re-check stock & prices
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute(array_keys($cart));
    $rows = $stmt->fetchAll();

    $items = []; $calculated_total = 0;
    foreach ($rows as $r) {
        $pid = $r['id'];
        $qty = $cart[$pid] ?? 0;
        if ($qty <= 0) continue;
        if ($r['stock'] < $qty) throw new Exception("Not enough stock for {$r['name']}");
        $subtotal = $r['price'] * $qty;
        $calculated_total += $subtotal;
        $items[] = ['product_id'=>$pid,'product_name'=>$r['name'],'price'=>$r['price'],'qty'=>$qty,'subtotal'=>$subtotal];
    }

    if (count($items) === 0) throw new Exception("No valid items in cart");
    // optional: compare totals
    if (abs($calculated_total - $total) > 0.01) $total = $calculated_total;

    // insert order
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_phone, customer_address, total, user_id) VALUES (?,?,?,?,?)");
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt->execute([$customer_name, $customer_phone, $customer_address, $total, $user_id]);
    $order_id = $pdo->lastInsertId();

    // insert order_items & reduce stock
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, qty, subtotal) VALUES (?,?,?,?,?,?)");
    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    foreach ($items as $it) {
        $stmtItem->execute([$order_id, $it['product_id'], $it['product_name'], $it['price'], $it['qty'], $it['subtotal']]);
        $stmtUpdateStock->execute([$it['qty'], $it['product_id']]);
    }

    $pdo->commit();

    // clear cart
    unset($_SESSION['cart']);

    // redirect to success/receipt page
    header("Location: order_success.php?id=" . $order_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    // in production, log the error. For dev, show message
    die("Order failed: " . $e->getMessage());
}