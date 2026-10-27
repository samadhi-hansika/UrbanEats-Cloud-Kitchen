<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role('Administrator');

$page_title = 'Admin Dashboard';
include '../includes/header.php';
?>

    <section class="admin-main">
        <section class="admin-hero">
            <div class="admin-hero-copy">
                <span class="admin-eyebrow"><span aria-hidden="true">✦</span> ADMIN CONTROL CENTER</span>
                <h1>Welcome to your<br>UrbanEats <span>Dashboard</span></h1>
                <p>Manage your food categories, team accounts, and platform activity to keep your cloud kitchen running smoothly from one place.</p>
                <div class="admin-hero-actions">
                    <a class="admin-action-primary" href="categories.php">Manage Categories <span aria-hidden="true">→</span></a>
                    <a class="admin-action-secondary" href="users.php">Manage Users</a>
                    <a class="admin-action-secondary" href="audit-log.php">Audit Logs</a>
                    <a class="admin-action-secondary" href="reports.php">Sales Reports</a>
                </div>
                <div class="admin-hero-note"><span aria-hidden="true">●</span> Your kitchen, under control.</div>
            </div>
            <div class="admin-hero-art" aria-hidden="true">
                <div class="admin-hero-tile admin-hero-tile-back">
                    <img src="../public/images/admin-dashboard-fries.png" alt="">
                </div>
                <div class="admin-hero-tile admin-hero-tile-smoothie">
                    <img src="../public/images/admin-dashboard-smoothie.png" alt="">
                </div>
                <div class="admin-hero-glass">
                    <img class="admin-dashboard-image" src="../public/images/admin-burger-hero.png" alt="">
                </div>
                <div class="admin-hero-tile admin-hero-tile-front">
                    <img src="../public/images/admin-dashboard-bowl.png" alt="">
                </div>
                <div class="admin-hero-tile admin-hero-tile-drinks">
                    <img src="../public/images/admin-dashboard-drinks.png" alt="">
                </div>
                <span class="admin-hero-art-label">URBANEATS · CLOUD KITCHEN</span>
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
    </section>
    <?php include '../includes/footer.php'; ?>