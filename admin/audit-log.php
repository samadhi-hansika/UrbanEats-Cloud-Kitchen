<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role('Administrator');

$logs = $pdo->query("SELECT al.*, u.full_name FROM audit_log al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 50")->fetchAll();
$page_title = 'System Audit Logs';
include '../includes/header.php';
?>

    <div class="admin-records-container">
        <div class="card">
            <h2>System Audit Logs</h2>
            <div class="admin-records-table-wrap">
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
    </div>
    <?php include '../includes/footer.php'; ?>