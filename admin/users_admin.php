<?php
require 'config.php';

// require login + admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['is_admin'])) {
    header('Location: admin_dashboard.php');
    exit;
}

// Params: search (q), page
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE
$where = '1';
$params = [];
if ($q !== '') {
    $where = '(fullname LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$countStmt->execute($params);
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalUsers / $perPage));

// Fetch page
$sql = "SELECT id, fullname, email, is_admin, created_at FROM users WHERE $where ORDER BY id ASC LIMIT ? OFFSET ?";
$fetchParams = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare($sql);
// Bind integers for limit/offset properly
$bindIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($bindIndex++, $p, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// For UI: which user is current admin (don't allow deleting self)
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$csrf = $_SESSION['csrf_token'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Users — Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
    .toolbar {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .search {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    th,
    td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        text-align: left;
        vertical-align: middle;
    }

    th {
        background: #fafafa;
    }

    .admin-tag {
        padding: 4px 8px;
        background: #0b69ff;
        color: white;
        border-radius: 6px;
        font-size: 12px;
    }

    .user-tag {
        padding: 4px 8px;
        background: #6c757d;
        color: white;
        border-radius: 6px;
        font-size: 12px;
    }

    .actions-btn {
        display: inline-flex;
        gap: 6px;
    }

    .small-muted {
        font-size: 13px;
        color: #666;
    }

    .pagination {
        margin-top: 14px;
        display: flex;
        gap: 6px;
        align-items: center;
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

    <?php if (file_exists("admin_nav.php")) include "admin_nav.php"; ?>

    <div class="container">
        <div class="card">
            <div class="toolbar">
                <div>
                    <h2 style="margin:0 0 6px">Manage Users</h2>
                    <p>All registered users in your Ordering System.</p>
                    <div class="small-muted">Total users: <?= $totalUsers ?></div>
                </div>

                <div class="search">
                    <form id="searchForm" method="get" action="users_admin.php" style="display:flex; gap:8px;">
                        <input type="text" name="q" placeholder="Search name or email..."
                            value="<?= htmlspecialchars($q) ?>"
                            style="padding:8px;border-radius:6px;border:1px solid #ddd;">
                        <button type="submit" class="btn">Search</button>
                    </form>
                    <a href="users_admin.php" class="btn" style="margin-left:8px;">Reset</a>
                </div>
            </div>

            <?php if (empty($users)): ?>
            <p>No users found.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:60px">ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th style="width:120px">Role</th>
                        <th style="width:160px">Created</th>
                        <th style="width:220px">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $u): ?>
                    <tr id="user-row-<?= $u['id'] ?>">
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['fullname']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if ($u['is_admin']): ?>
                            <span class="admin-tag">Admin</span>
                            <?php else: ?>
                            <span class="user-tag">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['created_at'] ?></td>
                        <td>
                            <div class="actions-btn">
                                <?php if ($u['id'] !== $currentUserId): // Don't allow self promote/demote/delete ?>
                                <?php if ($u['is_admin']): ?>
                                <button class="btn small"
                                    onclick="confirmAction('demote', <?= $u['id'] ?>, 'Demote this admin to user?')">Demote</button>
                                <?php else: ?>
                                <button class="btn small"
                                    onclick="confirmAction('promote', <?= $u['id'] ?>, 'Promote this user to admin?')">Promote</button>
                                <?php endif; ?>

                                <button class="btn small"
                                    onclick="confirmAction('delete', <?= $u['id'] ?>, 'Delete this user permanently?')">Delete</button>
                                <?php else: ?>
                                <span class="small-muted">You</span>
                                <?php endif; ?>

                                <a class="btn small" href="edit_user.php?id=<?= $u['id'] ?>"
                                    style="margin-left:6px;">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination" aria-label="Pagination">
                <?php
                $base = 'users_admin.php?q=' . urlencode($q) . '&page=';
                $start = max(1, $page - 3);
                $end = min($totalPages, $page + 3);
                if ($page > 1) echo '<a class="page-link" href="' . $base . ($page-1) . '">&laquo; Prev</a>';
                for ($p = $start; $p <= $end; $p++) {
                    $active = $p === $page ? ' active' : '';
                    echo '<a class="page-link' . $active . '" href="' . $base . $p . '">' . $p . '</a>';
                }
                if ($page < $totalPages) echo '<a class="page-link" href="' . $base . ($page+1) . '">Next &raquo;</a>';
                ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
    const csrfToken = '<?= $csrf ?>';

    function confirmAction(action, userId, message) {
        if (!confirm(message)) return;
        performUserAction(action, userId);
    }

    async function performUserAction(action, userId) {
        try {
            const form = new URLSearchParams();
            form.append('action', action);
            form.append('user_id', userId);
            form.append('csrf_token', csrfToken);

            const res = await fetch('users_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: form.toString()
            });
            const data = await res.json();
            if (data.success) {
                // if delete: remove row; else reload to reflect role change
                if (action === 'delete') {
                    const row = document.getElementById('user-row-' + userId);
                    if (row) row.remove();
                } else {
                    // quick feedback: reload the page to update role badges & counts
                    location.reload();
                }
                alert(data.message || 'Done');
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err) {
            alert('Network error');
        }
    }
    </script>

</body>

</html>