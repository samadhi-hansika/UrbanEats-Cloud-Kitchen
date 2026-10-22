<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
$user_role = $_SESSION['role'] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - UrbanEats Cloud Kitchen' : 'UrbanEats Cloud Kitchen' ?></title>
    <link rel="stylesheet" href="<?= url('public/style.css') ?>">
</head>
<body<?= $user_role === 'Kitchen Staff' ? ' class="kitchen-staff-layout"' : '' ?>>
    <header>
        <nav class="navbar">
            <a href="<?= url('index.php') ?>" class="brand">
                <span class="brand-icon">🍔</span> Urban<span>Eats</span>
            </a>
            
            <div class="nav-links">
                <a href="<?= url('index.php') ?>">Home</a>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= e(dashboard_url_for_role($user_role)) ?>">Dashboard</a>
                    <?php if ($user_role === 'Administrator'): ?>
                        <a href="<?= url('admin/users.php') ?>">Users &amp; Roles</a>
                        <a href="<?= url('admin/audit-log.php') ?>">Audit Logs</a>
                    <?php elseif ($user_role === 'Kitchen Staff'): ?>
                        <span class="role-pill pill-staff">Kitchen Staff</span>
                    <?php endif; ?>

                    <a href="<?= url('auth/profile.php') ?>">My Profile</a>
                    
                    <span class="user-greeting">
                        <?= e($_SESSION['full_name'] ?? 'User') ?>
                        <span class="role-badge"><?= e($user_role) ?></span>
                    </span>

                    <a href="<?= url('auth/logout.php') ?>" class="btn-logout">Logout</a>
                <?php else: ?>
                    <a href="<?= url('auth/login.php') ?>" class="nav-login">Sign In</a>
                    <a href="<?= url('auth/register.php') ?>" class="btn-primary btn-nav">Register</a>
                <?php endif; ?>
            </div>
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