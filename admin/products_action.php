<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']); exit;
}
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'Invalid CSRF']); exit;
}

$action = $_POST['action'] ?? '';
$pid = (int)($_POST['product_id'] ?? 0);
if ($pid <= 0) { echo json_encode(['error' => 'Invalid product id']); exit; }

// fetch product to get image filename
$stmt = $pdo->prepare("SELECT id, image FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$pid]);
$product = $stmt->fetch();
if (!$product) { echo json_encode(['error' => 'Product not found']); exit; }

try {
    if ($action === 'delete') {
        // optionally: check orders referencing product (order_items) and prevent deletion or warn
        // delete DB row
        $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $del->execute([$pid]);

        // delete image file
        if ($product['image']) {
            $path = __DIR__ . '/uploads/' . $product['image'];
            if (file_exists($path)) @unlink($path);
        }

        echo json_encode(['success' => true, 'message' => 'Product deleted']);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}