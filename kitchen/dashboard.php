<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('Kitchen Staff');
$page_title = 'Kitchen Staff Dashboard';
include '../includes/header.php';
?>

<section class="role-dashboard kitchen-dashboard">
    <div class="role-dashboard-copy">
        <span class="role-dashboard-eyebrow">KITCHEN TEAM WORKSPACE</span>
        <h1>Welcome to the<br><span>Kitchen, <?= e($_SESSION['full_name'] ?? 'Team') ?></span></h1>
        <p>Your team workspace is ready. Sign in to stay connected and keep your staff profile up to date.</p>
        <div class="role-dashboard-actions">
            <a class="role-dashboard-primary" href="<?= e(url('auth/profile.php')) ?>">Manage your profile</a>
            <a class="role-dashboard-secondary" href="<?= e(url('index.php')) ?>">Visit UrbanEats</a>
        </div>
    </div>
    <div class="role-dashboard-visual">
        <img src="<?= e(url('public/images/kitchen-team.png')) ?>" alt="Kitchen staff preparing food together">
    </div>
</section>

<section class="dashboard-welcome-card kitchen-welcome-card">
    <span class="dashboard-welcome-icon" aria-hidden="true">✓</span>
    <div>
        <h2>Signed in as Kitchen Staff</h2>
        <p>This is your dedicated staff dashboard. Your account and password can be managed from your profile.</p>
    </div>
    <a href="<?= e(url('auth/profile.php')) ?>">Open profile <span aria-hidden="true">→</span></a>
</section>

<?php include '../includes/footer.php'; ?>
