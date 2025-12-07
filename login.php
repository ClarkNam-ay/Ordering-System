<?php
require 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($password === '') $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, fullname, email, password, is_admin FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['is_admin'] = (isset($user['is_admin']) && $user['is_admin']) ? 1 : 0;

    // redirect
    if ($_SESSION['is_admin']) {
        header('Location: admin/admin_dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
} else {
    $errors[] = 'Incorrect email or password.';
}

    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login — Ordering System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="card">
        <h2>Sign in</h2>

        <?php if ($errors): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form id="loginForm" method="post" action="login.php" onsubmit="return validateLogin()">
            <?= csrf_input() ?>
            <label>Email
                <input type="email" name="email" id="loginEmail" required>
            </label>
            <label>Password
                <input type="password" name="password" id="loginPassword" required>
            </label>

            <button type="submit">Login</button>
            <p class="muted">Don't have an account? <a href="register.php">Register</a></p>
        </form>
    </div>

    <script src="js/script.js"></script>
</body>

</html>