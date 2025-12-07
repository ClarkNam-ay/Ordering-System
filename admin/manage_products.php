<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE and params
$where = '1';
$params = [];
if ($q !== '') {
    $where = '(name LIKE ? OR description LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch page
$sql = "SELECT id, name, price, stock, image, created_at FROM products WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);

// bind params
$bindIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($bindIndex++, $p, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$csrf = $_SESSION['csrf_token'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Products — Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
    .toolbar {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: center;
        margin-bottom: 12px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }

    .p-card {
        background: #fff;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .p-card img {
        max-width: 100%;
        height: 140px;
        object-fit: cover;
        border-radius: 6px;
    }

    .small-muted {
        font-size: 13px;
        color: #666;
    }

    .actions {
        display: flex;
        gap: 6px;
    }

    .pagination {
        margin-top: 12px;
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .page-link {
        padding: 6px 8px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
    }

    .page-link.active {
        background: #0b69ff;
        color: white;
        border-color: #0b69ff;
    }
    </style>
</head>

<body>
    <?php if (file_exists('admin_nav.php')) include 'admin_nav.php'; ?>

    <div class="container">
        <div class="card">
            <div class="toolbar">
                <div>
                    <h2 style="margin:0">Manage Products</h2>
                    <div class="small-muted">Total products: <?= $total ?></div>
                </div>

                <div style="display:flex; gap:8px; align-items:center;">
                    <form method="get" action="manage_products.php" style="display:flex; gap:8px;">
                        <input type="text" name="q" placeholder="Search name or description..."
                            value="<?= htmlspecialchars($q) ?>"
                            style="padding:8px;border-radius:6px;border:1px solid #ddd;">
                        <button class="btn" type="submit">Search</button>
                    </form>
                    <a class="btn" href="add_product.php">Add Product</a>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <p>No products found. Add one using the button above.</p>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                <div class="p-card" id="product-<?= $p['id'] ?>">
                    <?php if ($p['image'] && file_exists(__DIR__ . '/../uploads/' . $p['image'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($p['image']) ?>"
                        alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                    <div
                        style="height:140px;display:flex;align-items:center;justify-content:center;background:#f4f4f4;border-radius:6px;color:#888;">
                        No image</div>
                    <?php endif; ?>

                    <div style="flex:1">
                        <h4 style="margin:0 0 6px;"><?= htmlspecialchars($p['name']) ?></h4>
                        <div class="small-muted">
                            <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 120, '...')) ?></div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                        <div>
                            <strong>₱ <?= number_format($p['price'],2) ?></strong><br>
                            <span class="small-muted">Stock: <?= (int)$p['stock'] ?></span>
                        </div>
                        <div class="actions">
                            <a class="btn small" href="edit_product.php?id=<?= $p['id'] ?>">Edit</a>
                            <button class="btn small" onclick="deleteProduct(<?= $p['id'] ?>)">Delete</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination">
                <?php
          $base = 'manage_products.php?q=' . urlencode($q) . '&page=';
          $start = max(1, $page - 3);
          $end = min($totalPages, $page + 3);
          if ($page > 1) echo '<a class="page-link" href="'.$base.($page-1).'">&laquo; Prev</a>';
          for ($p = $start; $p <= $end; $p++) {
              $active = $p === $page ? ' active' : '';
              echo '<a class="page-link'.$active.'" href="'.$base.$p.'">'.$p.'</a>';
          }
          if ($page < $totalPages) echo '<a class="page-link" href="'.$base.($page+1).'">Next &raquo;</a>';
        ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const csrf = '<?= $csrf ?>';
    async function deleteProduct(pid) {
        if (!confirm('Delete product permanently?')) return;
        try {
            const params = new URLSearchParams();
            params.append('action', 'delete');
            params.append('product_id', pid);
            params.append('csrf_token', csrf);

            const res = await fetch('products_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById('product-' + pid);
                if (el) el.remove();
                alert(data.message || 'Deleted');
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
            }
        } catch (err) {
            alert('Network error');
        }
    }
    </script>
</body>

</html>