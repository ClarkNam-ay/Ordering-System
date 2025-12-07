<?php
// create_admin.php - ONE TIME USE. Delete this file after creating the admin.
require 'config.php';

// === CONFIGURE THESE BEFORE RUNNING ===
// set the admin email and password you want
$admin_email = 'admin123@gmail.com';
$admin_fullname = 'Site Admin';
$admin_password = 'admin123'; // choose a secure password and change this immediately after login
// =====================================

if (php_sapi_name() === 'cli') {
    // if run from CLI, optional prompt could be added. For simplicity we continue.
}

try {
    // check if user with email already exists
    $stmt = $pdo->prepare('SELECT id, is_admin FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$admin_email]);
    $u = $stmt->fetch();

    if ($u && (int)$u['is_admin'] === 1) {
        echo "An admin with that email already exists. Exiting.\n";
        exit;
    }

    if ($u && (int)$u['is_admin'] === 0) {
        // promote existing user to admin (update)
        $stmt = $pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
        $stmt->execute([$u['id']]);
        echo "Existing user promoted to admin. Done.\n";
        exit;
    }

    // create new user with hashed password
    $hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password, is_admin) VALUES (?,?,?,1)');
    $stmt->execute([$admin_fullname, $admin_email, $hash]);

    echo "Admin user created successfully.\n";
    echo "Email: $admin_email\n";
    echo "Password: (the one in this file)\n";
    echo "IMPORTANT: Delete create_admin.php now and change the admin password after first login.\n";

} catch (PDOException $e) {
    echo "DB error: " . $e->getMessage();
}