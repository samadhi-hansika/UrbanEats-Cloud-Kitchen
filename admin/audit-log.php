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