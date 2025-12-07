<?php
require '../config.php';
header('Content-Type: application/json');

// ensure admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$targetId = (int)($_POST['user_id'] ?? 0);
$me = (int)$_SESSION['user_id'];

// basic validation
if ($targetId <= 0) {
    echo json_encode(['error' => 'Invalid user id']);
    exit;
}

// fetch target user
$stmt = $pdo->prepare("SELECT id, email, fullname, is_admin FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$targetId]);
$target = $stmt->fetch();
if (!$target) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// prevent acting on yourself for safety
if ($target['id'] === $me) {
    echo json_encode(['error' => 'You cannot perform this action on your own account']);
    exit;
}

try {
    if ($action === 'delete') {
        // Delete user
        // (Optionally: check for orders and prevent deletion or transfer ownership)
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$targetId]);
        echo json_encode(['success' => true, 'message' => 'User deleted']);
        exit;
    }

    if ($action === 'promote') {
        // promote to admin
        $up = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $up->execute([$targetId]);
        echo json_encode(['success' => true, 'message' => 'User promoted to admin']);
        exit;
    }

    if ($action === 'demote') {
        // Before demoting, ensure there is at least one other admin left
        // count admins
        $countAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
        if ($countAdmin <= 1) {
            echo json_encode(['error' => 'Cannot demote the last admin']);
            exit;
        }
        $up = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $up->execute([$targetId]);
        echo json_encode(['success' => true, 'message' => 'Admin demoted to user']);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;

} catch (PDOException $e) {
    // In production log error; return generic message
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}