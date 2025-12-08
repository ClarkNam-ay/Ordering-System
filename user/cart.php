<?php
require '../config.php';
$cart = $_SESSION['cart'] ?? [];

$items = [];
$total = 0.00;
if ($cart) {
    // fetch product details for all ids
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock, image, description FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $pid = $r['id'];
        $qty = $cart[$pid] ?? 0;
        $subtotal = $r['price'] * $qty;
        $items[] = array_merge($r, ['qty'=>$qty, 'subtotal'=>$subtotal]);
        $total += $subtotal;
    }
}

// handle update/remove via POST (non-AJAX fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token';
    } else {
        if (isset($_POST['update'])) {
            // update quantities
            foreach ($_POST['qty'] as $pid => $q) {
                $pid = (int)$pid; $q = max(0, (int)$q);
                if ($q <= 0) { unset($_SESSION['cart'][$pid]); }
                else { $_SESSION['cart'][$pid] = $q; }
            }
        } elseif (isset($_POST['clear'])) {
            unset($_SESSION['cart']);
        } elseif (isset($_POST['remove'])) {
            $remove_id = (int)$_POST['remove'];
            unset($_SESSION['cart'][$remove_id]);
        }
    }
    // reload page to show updated cart
    header('Location: cart.php');
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart – Ordering System</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/cart.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header / Navigation -->
    <header class="cart-header">
        <?php include 'nav.php'; ?>
    </header>

    <div class="cart-container">
        <!-- Cart Header -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h2>🛒 Shopping Cart</h2>
                <p>Browse our menu and add items to your cart to get started.</p>
            </div>
        </div>

        <?php if (empty($items)): ?>
        <!-- Empty Cart State -->
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Add some delicious items to your cart to get started!</p>
            <a href="dashboard.php" class="btn-continue">
                ← Continue Shopping
            </a>
        </div>
        <?php else: ?>
        <!-- Cart Form -->
        <form method="post" action="cart.php" id="cartForm">
            <?= csrf_input() ?>

            <!-- Cart Table -->
            <div class="cart-table-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div class="cart-item-info">
                                    <?php if ($it['image']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($it['image']) ?>"
                                        alt="<?= htmlspecialchars($it['name']) ?>" class="cart-item-image">
                                    <?php else: ?>
                                    <div class="cart-item-image"
                                        style="display: flex; align-items: center; justify-content: center; font-size: 32px;">
                                        🍽️
                                    </div>
                                    <?php endif; ?>
                                    <div class="cart-item-details">
                                        <h4><?= htmlspecialchars($it['name']) ?></h4>
                                        <p><?= htmlspecialchars(substr($it['description'] ?? '', 0, 50)) ?><?= strlen($it['description'] ?? '') > 50 ? '...' : '' ?>
                                        </p>
                                        <?php if ($it['qty'] > $it['stock']): ?>
                                        <div class="stock-warning">
                                            ⚠️ Only <?= $it['stock'] ?> left in stock
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cart-price">₱ <?= number_format($it['price'], 2) ?></div>
                            </td>
                            <td>
                                <div class="cart-quantity">
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn"
                                            onclick="decreaseQty(<?= $it['id'] ?>)">−</button>
                                        <input type="number" name="qty[<?= $it['id'] ?>]" id="qty_<?= $it['id'] ?>"
                                            value="<?= $it['qty'] ?>" min="0" max="<?= $it['stock'] ?>"
                                            class="quantity-input" onchange="updateCart()">
                                        <button type="button" class="quantity-btn"
                                            onclick="increaseQty(<?= $it['id'] ?>, <?= $it['stock'] ?>)">+</button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cart-subtotal">₱ <?= number_format($it['subtotal'], 2) ?></div>
                            </td>
                            <td>
                                <button type="submit" name="remove" value="<?= $it['id'] ?>" class="remove-btn"
                                    onclick="return confirm('Remove this item from cart?')">
                                    🗑️ Remove
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <div class="summary-row">
                    <span class="summary-label">Subtotal (<?= count($items) ?> items)</span>
                    <span class="summary-value">₱ <?= number_format($total, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Delivery Fee</span>
                    <span class="summary-value">₱ 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₱ <?= number_format($total, 2) ?></span>
                </div>
            </div>

            <!-- Cart Actions -->
            <div class="cart-actions">
                <a href="dashboard.php" class="btn-continue">
                    ← Continue Shopping
                </a>
                <button type="submit" name="update" class="btn-update">
                    🔄 Update Cart
                </button>
                <button type="submit" name="clear" class="btn-clear"
                    onclick="return confirm('Clear all items from cart?')">
                    🗑️ Clear Cart
                </button>
                <a href="checkout.php" class="btn-checkout">
                    Proceed to Checkout →
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
    function increaseQty(productId, maxStock) {
        const input = document.getElementById('qty_' + productId);
        const currentVal = parseInt(input.value) || 0;
        if (currentVal < maxStock) {
            input.value = currentVal + 1;
            updateCart();
        }
    }

    function decreaseQty(productId) {
        const input = document.getElementById('qty_' + productId);
        const currentVal = parseInt(input.value) || 0;
        if (currentVal > 1) {
            input.value = currentVal - 1;
            updateCart();
        } else if (currentVal === 1) {
            if (confirm('Remove this item from cart?')) {
                input.value = 0;
                updateCart();
            }
        }
    }

    function updateCart() {
        // Auto-submit form when quantity changes
        // You can add a small delay to batch multiple changes
        clearTimeout(window.updateCartTimer);
        window.updateCartTimer = setTimeout(() => {
            document.getElementById('cartForm').submit();
        }, 500);
    }

    // Prevent negative values in quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
    });
    </script>

</body>

</html>