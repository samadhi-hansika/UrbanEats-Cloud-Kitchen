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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UrbanEats - Audit Logs</title>
    <link rel="icon" type="image/png" href="../public/images/urbaneats-logo.png">
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="admin-dashboard admin-records-page admin-audit-page">
    <header class="admin-navbar">
        <a class="admin-brand" href="../index.php">
            <img class="admin-brand-icon" src="../public/images/urbaneats-logo.png" alt="">
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
            <a class="admin-logout" href="../auth/logout.php" aria-label="Sign out"><span aria-hidden="true">↗</span> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-page-eyebrow">SECURITY &amp; ACTIVITY</span>
                    <h1>System audit logs</h1>
                    <p>Review the latest administrative actions across UrbanEats.</p>
                </div>
                <span class="admin-log-count"><?= count($logs) ?> <?= count($logs) === 1 ? 'entry' : 'entries' ?></span>
            </div>
            <div class="admin-records-table-wrap">
                <table class="data-table">
                    <caption class="sr-only">The latest 50 system audit log entries</caption>
                    <thead>
                        <tr>
                            <th scope="col">Log ID</th>
                            <th scope="col">User</th>
                            <th scope="col">Action</th>
                            <th scope="col">Details</th>
                            <th scope="col">IP Address</th>
                            <th scope="col">Timestamp</th>
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
                            <td><?= htmlspecialchars($l['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?>
                        <tr>
                            <td class="admin-empty-state" colspan="6">No audit activity has been recorded yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>