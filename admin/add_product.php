<?php
require 'config.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
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

    // handle image upload (optional)
    $uploadedFileName = null;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        // validate
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Invalid image type. Use JPG/PNG/WebP/GIF.';
        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
            $errors[] = 'Image too large (max 2MB).';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload error.';
        } else {
            // generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uploadedFileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $uploadedFileName;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Failed to move uploaded image.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $price, $stock, $uploadedFileName]);
        $success = 'Product added successfully.';
        // clear form
        $name = $desc = '';
        $price = $stock = '';
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Add Product — Admin</title>
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <?php if (file_exists('admin_nav.php')) include 'admin_nav.php'; ?>

    <div class="container">
        <div class="card">
            <h2>Add Product</h2>

            <?php if ($errors): ?>
            <div class="alert error">
                <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" action="add_product.php" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <label>Product name
                    <input type="text" name="name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                </label>

                <label>Description
                    <textarea name="description" rows="3"><?= isset($desc) ? htmlspecialchars($desc) : '' ?></textarea>
                </label>

                <label>Price
                    <input type="number" step="0.01" name="price"
                        value="<?= isset($price) ? htmlspecialchars($price) : '' ?>" required>
                </label>

                <label>Stock
                    <input type="number" name="stock" value="<?= isset($stock) ? htmlspecialchars($stock) : '0' ?>"
                        required>
                </label>

                <label>Image (optional) — JPG, PNG, WebP, GIF (max 2MB)
                    <input type="file" name="image" accept="image/*">
                </label>

                <button class="btn" type="submit">Add Product</button>
                <a class="btn" href="manage_products.php" style="margin-left:10px;">Back</a>
            </form>
        </div>
    </div>
</body>

</html>