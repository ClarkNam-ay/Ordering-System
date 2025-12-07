<?php
require 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // basic CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token.';
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($fullname === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // check if email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        } else {
            // insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$fullname, $email, $hash]);
            $success = 'Registration successful.';
            // regenerate CSRF token to avoid resubmission issues
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Register — Ordering System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="card">
        <h2>Create account</h2>

        <?php if ($errors): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <form id="registerForm" method="post" action="register.php" onsubmit="return validateRegister()">
            <?= csrf_input() ?>
            <label>Full name
                <input type="text" name="fullname" id="fullname"
                    value="<?= isset($fullname) ? htmlspecialchars($fullname) : '' ?>" required>
            </label>
            <label>Email
                <input type="email" name="email" id="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                    required>
            </label>
            <label>Password
                <input type="password" name="password" id="password" required>
            </label>
            <label>Confirm Password
                <input type="password" name="confirm_password" id="confirm_password" required>
            </label>

            <button type="submit">Register</button>
            <p class="muted">Already have an account? <a href="login.php">Login</a></p>
        </form>
    </div>

    <script src="js/script.js"></script>
</body>

</html>