<?php
require '../config.php';
header('Content-Type: application/json');

// basic request validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']); exit;
}
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'Invalid CSRF']); exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));
if ($product_id <= 0) { echo json_encode(['error'=>'Invalid product']); exit; }

// fetch product
$stmt = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) { echo json_encode(['error'=>'Product not found']); exit; }
if ($product['stock'] < $qty) {
    echo json_encode(['error'=>'Not enough stock']); exit;
}

// session cart structure: $_SESSION['cart'][product_id] = qty
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
// add or increment
$current = $_SESSION['cart'][$product_id] ?? 0;
$new = $current + $qty;
if ($product['stock'] < $new) {
    echo json_encode(['error'=>'Exceeds available stock']); exit;
}
$_SESSION['cart'][$product_id] = $new;

echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
exit;