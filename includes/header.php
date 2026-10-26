<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/orders.php';
$user_role = $_SESSION['role'] ?? 'Customer';
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$is_profile_page = $current_page === 'profile.php';
$is_admin_profile_page = $is_profile_page && $user_role === 'Administrator';
$is_sales_report_page = $current_page === 'reports.php';
$is_category_management_page = $current_page === 'categories.php';
$is_menu_page = $current_page === 'menu.php';
$is_home_page = $current_page === 'index.php';
$is_customer_user = !empty($_SESSION['user_id']) && $user_role === 'Customer';
$is_customer_dashboard_page = $current_page === 'dashboard.php' && $user_role === 'Customer';
$is_customer_orders_page = in_array($current_page, ['cart.php', 'history.php'], true) && $user_role === 'Customer';
$is_kitchen_menu_dashboard = $current_page === 'dashboard.php'
    && basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'kitchen';
$is_kitchen_inventory_page = $current_page === 'inventory.php'
    && basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'kitchen';
$is_kitchen_queue_page = $current_page === 'kitchen-queue.php';
$is_kitchen_dashboard = ($user_role === 'Kitchen Staff')
    || (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'kitchen');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - UrbanEats Cloud Kitchen' : 'UrbanEats Cloud Kitchen' ?></title>
    <link rel="icon" type="image/png" href="<?= url('public/images/urbaneats-logo.png') ?>">
    <link rel="stylesheet" href="<?= url('public/style.css') ?>">
</head>
<body class="<?= $is_kitchen_dashboard ? 'kitchen-staff-layout ' : '' ?><?= $is_kitchen_menu_dashboard ? 'kitchen-dashboard-page ' : '' ?><?= $is_kitchen_inventory_page ? 'kitchen-inventory-page ' : '' ?><?= $is_kitchen_queue_page ? 'kitchen-queue-page ' : '' ?><?= $is_profile_page ? 'profile-page ' : '' ?><?= $is_admin_profile_page ? 'admin-profile-page ' : '' ?><?= $is_category_management_page ? 'category-management-page ' : '' ?><?= $is_sales_report_page ? 'admin-sales-page ' : '' ?><?= $is_customer_user ? 'customer-user-layout ' : '' ?><?= $is_customer_dashboard_page ? 'customer-dashboard-page ' : '' ?><?= $is_customer_orders_page ? 'customer-orders-page ' : '' ?><?= $is_menu_page ? 'menu-page-layout ' : '' ?><?= $is_home_page ? 'home-page' : '' ?>">
    <header>
        <nav class="navbar">
            <a href="<?= url('index.php') ?>" class="brand">
                <img class="brand-icon" src="<?= e(url('public/images/urbaneats-logo.png')) ?>" alt=""> Urban<span>Eats</span>
            </a>
            
            <div class="nav-links<?= $user_role === 'Kitchen Staff' ? ' kitchen-nav-links' : '' ?>">
                <?php if (!$is_kitchen_dashboard && !$is_admin_profile_page): ?>
                    <a class="<?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a>
                <?php endif; ?>
                <?php if ((!$is_kitchen_dashboard || $is_menu_page) && !$is_admin_profile_page): ?>
                    <a class="<?= $current_page === 'menu.php' ? 'active' : '' ?>" href="<?= url('menu.php') ?>">Menu</a>
                <?php endif; ?>
                <?php if (!$is_kitchen_dashboard && (empty($_SESSION['user_id']) || $user_role !== 'Customer') && in_array($current_page, ['index.php', 'menu.php'], true)): ?>
                    <a href="<?= $current_page === 'index.php' ? '#about-urbaneats' : e(url('index.php#about-urbaneats')) ?>">About Us</a>
                    <a href="<?= $current_page === 'index.php' ? '#contact-urbaneats' : e(url('index.php#contact-urbaneats')) ?>">Contact Us</a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="<?= e(dashboard_url_for_role($user_role)) ?>">Dashboard</a>
                    <?php if ($user_role === 'Customer'): ?>
<a class="<?= $current_page === 'cart.php' && basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'orders' ? 'active' : '' ?>" href="<?= url('orders/cart.php') ?>">Cart (<?= function_exists('cart_count') ? cart_count() : 0 ?>)</a>
                        <a class="<?= $current_page === 'history.php' && basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'orders' ? 'active' : '' ?>" href="<?= url('orders/history.php') ?>">Order History</a>
                    <?php elseif (in_array($user_role, ['Kitchen Staff', 'Administrator'], true)): ?>
                        <a class="<?= $is_kitchen_queue_page ?? false ? 'active' : '' ?>" href="<?= url('orders/kitchen-queue.php') ?>">Order Queue</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'Administrator'): ?>
                        <a class="<?= $current_page === 'inventory.php' ? 'active' : '' ?>" href="<?= url('kitchen/inventory.php') ?>">Inventory</a>
                        <a class="<?= $current_page === 'categories.php' ? 'active' : '' ?>" href="<?= url('admin/categories.php') ?>">Categories</a>
                        <?php if (!$is_kitchen_dashboard && !$is_home_page && !$is_kitchen_queue_page): ?>
                            <a class="<?= $current_page === 'users.php' ? 'active' : '' ?>" href="<?= url('admin/users.php') ?>">Users &amp; Roles</a>
                            <a class="<?= $current_page === 'audit-log.php' ? 'active' : '' ?>" href="<?= url('admin/audit-log.php') ?>">Audit Logs</a>
                        <?php endif; ?>
                        <?php if (!$is_kitchen_dashboard && !$is_home_page): ?>
                            <a class="<?= $current_page === 'reports.php' ? 'active' : '' ?>" href="<?= url('admin/reports.php') ?>">Sales Reports</a>
                        <?php endif; ?>
                    <?php elseif ($user_role === 'Kitchen Staff'): ?>
                        <a class="<?= $current_page === 'inventory.php' ? 'active' : '' ?>" href="<?= url('kitchen/inventory.php') ?>">Inventory</a>
                        <a class="<?= $current_page === 'categories.php' ? 'active' : '' ?>" href="<?= url('admin/categories.php') ?>">Categories</a>
                    <?php endif; ?>

                    <a class="<?= $is_profile_page ? 'active' : '' ?>" href="<?= url('auth/profile.php') ?>">My Profile</a>
                <?php else: ?>
                    <a href="<?= url('auth/login.php') ?>" class="nav-login">Sign In</a>
                    <a href="<?= url('auth/register.php') ?>" class="btn-primary btn-nav">Register</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <div class="nav-account">
                    <span class="user-greeting">
                        <?= e($_SESSION['full_name'] ?? 'User') ?>
                    </span>
                    <a href="<?= url('auth/logout.php') ?>" class="btn-logout">Logout</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container main-content">
        <?php
        $flashes = get_flashes();
        if (!empty($flashes)):
            foreach ($flashes as $type => $messages):
                $alert_class = ($type === 'error' || $type === 'danger') ? 'alert-danger' : 
                               (($type === 'success') ? 'alert-success' : 'alert-info');
                foreach ($messages as $msg):
        ?>
                    <div class="alert <?= $alert_class ?>" role="alert">
                        <?= e($msg) ?>
                    </div>
        <?php
                endforeach;
            endforeach;
        endif;
        ?>