<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role('Administrator');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - UrbanEats</title>
    <link rel="icon" type="image/png" href="../public/images/urbaneats-logo.png">
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="admin-dashboard admin-dashboard-home">
    <header class="admin-navbar">
        <a class="admin-brand" href="../index.php">
            <img class="admin-brand-icon" src="../public/images/urbaneats-logo.png" alt="">
            <span>Urban<span>Eats</span><small>CLOUD KITCHEN</small></span>
        </a>
        <nav class="admin-nav-links" aria-label="Admin navigation">
            <a class="active" href="dashboard.php">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="users.php">Users &amp; Roles</a>
            <a href="audit-log.php">Audit Logs</a>
            <a href="reports.php">Sales Reports</a>
        </nav>
        <div class="admin-account">
            <span class="admin-account-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator') ?></span>
            <a class="admin-logout" href="../auth/logout.php" aria-label="Sign out"><span aria-hidden="true">↗</span> Logout</a>
        </div>
    </header>

    <main class="admin-main">
        <section class="admin-hero">
            <div class="admin-hero-copy">
                <span class="admin-eyebrow"><span aria-hidden="true">✦</span> ADMIN CONTROL CENTER</span>
                <h1>Welcome to your<br>UrbanEats <span>Dashboard</span></h1>
                <p>Manage your food categories, team accounts, and platform activity to keep your cloud kitchen running smoothly from one place.</p>
                <div class="admin-hero-actions">
                    <a class="admin-action-primary" href="categories.php">Manage Categories <span aria-hidden="true">→</span></a>
                    <a class="admin-action-secondary" href="users.php">Manage Users</a>
                    <a class="admin-action-secondary" href="audit-log.php">Audit Logs</a>
                </div>
                <div class="admin-hero-note"><span aria-hidden="true">●</span> Your kitchen, under control.</div>
            </div>
            <div class="admin-hero-art" aria-hidden="true">
                <div class="admin-hero-glass">
                    <img class="admin-dashboard-image" src="../public/images/admin-burger-hero.png" alt="">
                </div>
            </div>
            <span class="admin-hero-decoration" aria-hidden="true">UE</span>
        </section>

        <section class="admin-quick-links" aria-label="Quick links">
            <a class="admin-quick-card" href="categories.php">
                <span class="admin-quick-icon categories-icon" aria-hidden="true">📑</span>
                <span><strong>Food Categories</strong><small>Add, edit &amp; classify dishes</small></span>
                <span class="admin-quick-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="admin-quick-card" href="users.php">
                <span class="admin-quick-icon users-icon" aria-hidden="true">♙</span>
                <span><strong>Manage Users &amp; Roles</strong><small>Review accounts and access</small></span>
                <span class="admin-quick-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="admin-quick-card" href="audit-log.php">
                <span class="admin-quick-icon logs-icon" aria-hidden="true">▤</span>
                <span><strong>System Audit Logs</strong><small>Monitor recent platform activity</small></span>
                <span class="admin-quick-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="admin-quick-card" href="reports.php">
                <span class="admin-quick-icon logs-icon" aria-hidden="true">▥</span>
                <span><strong>Sales Reports</strong><small>Review revenue and top dishes</small></span>
                <span class="admin-quick-arrow" aria-hidden="true">↗</span>
            </a>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>