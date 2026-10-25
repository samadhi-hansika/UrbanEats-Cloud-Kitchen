<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('Customer');
$page_title = 'Customer Dashboard';
include '../includes/header.php';
?>

<section class="role-dashboard customer-dashboard">
    <div class="role-dashboard-copy">
        <span class="role-dashboard-eyebrow">YOUR URBANEATS ACCOUNT</span>
        <h1>Welcome back,<br><span><?= e($_SESSION['full_name'] ?? 'Customer') ?></span></h1>
        <p>Thanks for being part of UrbanEats. Your next delicious meal is just around the corner.</p>
        <div class="role-dashboard-actions">
            <a class="role-dashboard-primary" href="<?= e(url('menu.php')) ?>">Browse Menu</a>
            <a class="role-dashboard-secondary" href="<?= e(url('orders/cart.php')) ?>">View cart</a>
            <a class="role-dashboard-secondary" href="<?= e(url('orders/history.php')) ?>">View your orders</a>
            <a class="role-dashboard-secondary" href="<?= e(url('auth/profile.php')) ?>">Manage your profile</a>
        </div>
    </div>
    <div class="role-dashboard-visual">
        <img src="<?= e(url('public/images/customer-dashboard-platter.png')) ?>" alt="A platter of pizza, pasta, and freshly prepared dishes">
    </div>
</section>

<section class="dashboard-welcome-card customer-welcome-card">
    <span class="dashboard-welcome-icon" aria-hidden="true">✦</span>
    <div>
        <h2>Your account is ready</h2>
        <p>Keep your contact details up to date so your UrbanEats experience stays fresh.</p>
    </div>
    <a href="<?= e(url('auth/profile.php')) ?>">Update profile <span aria-hidden="true">→</span></a>
</section>

<?php include '../includes/footer.php'; ?>
