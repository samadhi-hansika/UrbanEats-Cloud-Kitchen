<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role('Administrator');

// Handle Toggle User Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $uid = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
    $stmt->execute([$uid]);
    log_action($pdo, $_SESSION['user_id'], 'USER_STATUS_TOGGLE', "Toggled status for user ID: $uid");
    header("Location: users.php");
    exit();
}

$users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanEats - Manage Users</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>User Management Console</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role_name']) ?></td>
                        <td>
                            <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Disabled' ?>
                            </span>
                        </td>
                        <td>
                            <a href="users.php?action=toggle&id=<?= $u['user_id'] ?>" style="color: var(--primary-orange);">Toggle Status</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>