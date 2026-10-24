<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role('Administrator');

$logs = $pdo->query("SELECT al.*, u.full_name FROM audit_log al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 50")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanEats - Audit Logs</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="admin-dashboard">
    <header class="admin-navbar">
        <a class="admin-brand" href="../index.php">
            <span class="admin-brand-icon" aria-hidden="true">🍴</span>
            <span>Urban<span>Eats</span><small>CLOUD KITCHEN</small></span>
        </a>
        <nav class="admin-nav-links" aria-label="Admin navigation">
            <a href="dashboard.php">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="users.php">Users &amp; Roles</a>
            <a class="active" href="audit-log.php">Audit Logs</a>
        </nav>
        <div class="admin-account">
            <span class="admin-account-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator') ?></span>
            <a class="admin-logout" href="../auth/logout.php" aria-label="Sign out">↗</a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2>System Audit Logs</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td>#<?= $l['log_id'] ?></td>
                        <td><?= htmlspecialchars($l['full_name'] ?? 'Guest/System') ?></td>
                        <td><strong><?= htmlspecialchars($l['action']) ?></strong></td>
                        <td><?= htmlspecialchars($l['details']) ?></td>
                        <td><?= htmlspecialchars($l['ip_address']) ?></td>
                        <td><?= $l['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>