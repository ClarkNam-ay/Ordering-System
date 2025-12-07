<?php
require 'config.php';

// fetch products
$stmt = $pdo->query("SELECT id, name, description, price, stock, image FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Menu — Ordering System</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <h2>Menu</h2>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card">
                <?php if ($p['image']): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                    style="width:100%;border-radius:6px;">
                <?php endif; ?>
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <p class="muted small"><?= htmlspecialchars($p['description']) ?></p>
                <p><strong>₱ <?= number_format($p['price'],2) ?></strong></p>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="number" min="1" max="<?= (int)$p['stock'] ?>" value="1" id="qty_<?= $p['id'] ?>"
                        style="width:70px;padding:6px;border-radius:6px;border:1px solid #ddd">
                    <button onclick="addToCart(<?= $p['id'] ?>)" class="btn">Add to cart</button>
                </div>
                <?php if ($p['stock'] <= 0): ?>
                <div class="alert error small">Out of stock</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // uses fetch to call add_to_cart.php
    function addToCart(pid) {
        const qty = parseInt(document.getElementById('qty_' + pid).value) || 1;
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `product_id=${encodeURIComponent(pid)}&qty=${encodeURIComponent(qty)}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
        }).then(r => r.json()).then(data => {
            if (data.success) {
                alert('Added to cart');
                // optionally update UI badge, stored in session on server
            } else {
                alert('Error: ' + (data.error || 'Could not add to cart'));
            }
        }).catch(e => alert('Network error'));
    }
    </script>
</body>

</html>