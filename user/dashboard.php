<?php
require '../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch products for the dashboard menu
$stmt = $pdo->query("SELECT id, name, description, price, stock, image FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Calculate cart count
$cart_count = array_sum($_SESSION['cart'] ?? []);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Ordering System</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- Optional: Google Fonts for better typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header / Navigation -->
    <header class="dashboard-header">
        <?php include 'nav.php'; ?>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h2>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h2>
                <p>Browse our menu and add items to your cart to get started.</p>
            </div>
            <!-- <div class="welcome-actions">
                <a href="cart.php" class="btn btn-primary">
                    🛒 View Cart (<?= $cart_count ?>)
                </a>
                <a href="orders_admin.php" class="btn btn-secondary">
                    📦 My Orders
                </a>
            </div> -->
        </div>

        <!-- Products Section -->
        <div class="section-header">
            <div>
                <h3>Featured Products</h3>
                <p class="section-subtitle">Fresh items available for order</p>
            </div>
        </div>

        <?php if (empty($products)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <h4>No Products Available</h4>
            <p>No products found. Please add products via your admin panel or run the sample inserts.</p>
        </div>
        <?php else: ?>
        <!-- Products Grid -->
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card">
                <?php if ($p['image']): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                    class="product-image">
                <?php else: ?>
                <div class="product-image"
                    style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc;">
                    🍽️
                </div>
                <?php endif; ?>

                <div class="product-body">
                    <h4 class="product-title"><?= htmlspecialchars($p['name']) ?></h4>
                    <p class="product-description"><?= htmlspecialchars($p['description']) ?></p>

                    <div class="product-footer">
                        <div class="product-price-section">
                            <div class="product-price">₱ <?= number_format($p['price'], 2) ?></div>
                            <div class="product-stock">
                                <span
                                    class="stock-indicator <?= $p['stock'] > 10 ? 'in-stock' : ($p['stock'] > 0 ? 'low-stock' : 'out-of-stock') ?>"></span>
                                <?php if ($p['stock'] > 0): ?>
                                <?= (int)$p['stock'] ?> in stock
                                <?php else: ?>
                                Out of stock
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-actions">
                            <?php if ($p['stock'] > 0): ?>
                            <input type="number" min="1" max="<?= (int)$p['stock'] ?>" value="1"
                                id="qty_<?= $p['id'] ?>" class="quantity-input" title="Quantity">
                            <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-success add-to-cart-btn">
                                Add to Cart
                            </button>
                            <?php else: ?>
                            <button class="btn" disabled
                                style="background: #e5e7eb; color: #9ca3af; cursor: not-allowed;">
                                Out of Stock
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function addToCart(pid) {
        const qtyEl = document.getElementById('qty_' + pid);
        const qty = parseInt(qtyEl.value) || 1;
        const button = qtyEl.nextElementSibling;
        const originalText = button.textContent;

        // Disable button and show loading state
        button.disabled = true;
        button.textContent = 'Adding...';

        fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${encodeURIComponent(pid)}&qty=${encodeURIComponent(qty)}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Show success feedback
                    button.textContent = '✓ Added!';
                    button.style.background = '#10b981';

                    // Update cart count in UI
                    const cartBadges = document.querySelectorAll('.cart-badge');
                    cartBadges.forEach(badge => {
                        badge.textContent = data.cart_count;
                    });

                    // Update cart links
                    const cartLinks = document.querySelectorAll('a[href="cart.php"]');
                    cartLinks.forEach(link => {
                        const badge = link.querySelector('.cart-badge');
                        if (badge) {
                            badge.textContent = data.cart_count;
                        } else {
                            const text = link.textContent.replace(/\d+/, data.cart_count);
                            link.textContent = text;
                        }
                    });

                    // Reset button after delay
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.style.background = '';
                        button.disabled = false;
                    }, 2000);
                } else {
                    alert('Error: ' + (data.error || 'Could not add to cart'));
                    button.textContent = originalText;
                    button.disabled = false;
                }
            })
            .catch(e => {
                alert('Network error. Please try again.');
                button.textContent = originalText;
                button.disabled = false;
            });
    }

    // Add keyboard support for quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const productId = this.id.replace('qty_', '');
                addToCart(productId);
            }
        });
    });
    </script>

</body>

</html>