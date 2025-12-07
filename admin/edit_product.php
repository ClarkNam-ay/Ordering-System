<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../login.php'); exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: manage_products.php'); exit;
}

// fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Location: manage_products.php'); exit; }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token.';
    }

    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name === '') $errors[] = 'Product name required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($stock < 0) $errors[] = 'Stock must be 0 or greater.';

    // handle new image upload (optional)
    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Invalid image type. Use JPG/PNG/WebP/GIF.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image too large (max 2MB).';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload error.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newImage = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $newImage;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Failed to move uploaded image.';
            }
        }
    }

    if (empty($errors)) {
        // update
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        $imageToSave = $newImage ? $newImage : $product['image'];
        $stmt->execute([$name, $desc, $price, $stock, $imageToSave, $id]);

        // if replaced image, delete old file
        if ($newImage && $product['image']) {
            $oldPath = __DIR__ . '/../uploads/' . $product['image'];
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $success = 'Product updated.';
        // refresh product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Edit Product — Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <?php if (file_exists('admin_nav.php')) include 'admin_nav.php'; ?>

    <div class="container">
        <div class="card">
            <h2>Edit Product</h2>

            <?php if ($errors): ?>
            <div class="alert error">
                <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" action="edit_product.php?id=<?= $id ?>" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <label>Product name
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                </label>

                <label>Description
                    <textarea name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                </label>

                <label>Price
                    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>"
                        required>
                </label>

                <label>Stock
                    <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" required>
                </label>

                <label>Current image</label>
                <?php if ($product['image'] && file_exists(__DIR__.'/../uploads/'.$product['image'])): ?>
                <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt=""
                    style="max-width:200px;display:block;margin-bottom:8px;">
                <?php else: ?>
                <div class="small-muted">No image</div>
                <?php endif; ?>

                <label>Replace image (optional)
                    <input type="file" name="image" accept="image/*">
                </label>

                <button class="btn" type="submit">Save changes</button>
                <a class="btn" href="manage_products.php" style="margin-left:8px;">Back</a>
            </form>
        </div>
    </div>
</body>

</html>