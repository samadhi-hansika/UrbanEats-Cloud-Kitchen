<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$switch_account = ($_GET['switch_account'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && is_logged_in() && !$switch_account) {
    header('Location: ' . dashboard_url_for_role($_SESSION['role'] ?? ''));
    exit();
}

$message = '';
$email_value = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? trim($_POST['email'] ?? '')
    : ($switch_account ? trim($_GET['email'] ?? '') : '');

if ($switch_account && is_logged_in()) {
    $message = 'Sign in with the new account credentials to switch accounts.';
} elseif (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $message = 'Customer account created successfully. Please sign in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check Throttling
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > NOW() - INTERVAL 15 MINUTE");
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    
    if ($stmt->fetchColumn() >= 5) {
        $message = "Account temporarily locked due to 5 failed attempts. Please try again in 15 minutes.";
    } else {
        $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_active'] == 0) {
                $message = "Your account has been deactivated. Please contact support.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role_name'];
                $_SESSION['full_name'] = $user['full_name'];

                log_action($pdo, $user['user_id'], 'LOGIN_SUCCESS', 'User logged in successfully');
                header('Location: ' . dashboard_url_for_role($user['role_name']));
                exit();
            }
        } else {
            // Log Failed Attempt
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $email]);
            $message = "Invalid email or password credentials!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UrbanEats - Login</title>
    <link rel="icon" type="image/png" href="../public/images/urbaneats-logo.png">
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="login-page">
    <header class="login-header">
        <a class="login-brand" href="../index.php">
            <img class="login-brand-icon" src="../public/images/urbaneats-logo.png" alt="">
            <span>UrbanEats <small>GOOD FOOD · BETTER MOOD</small></span>
        </a>
        <nav class="login-nav" aria-label="Main navigation">
            <a href="../index.php">Home</a>
            <a href="../menu.php">Menu</a>
            <a class="login-nav-register" href="register.php">Register</a>
        </nav>
    </header>

    <main class="login-layout">
        <section class="login-showcase" aria-labelledby="login-showcase-title">
            <div class="login-photo-wrap">
                <img src="../public/images/customer-login-food.png" alt="Freshly prepared pasta and chicken parmigiana">
                <span class="login-photo-label"><span aria-hidden="true">✦</span> Freshly made, made for you</span>
            </div>
            <div class="login-showcase-copy">
                <span class="login-eyebrow">WELCOME BACK</span>
                <h1 id="login-showcase-title">Savor the Taste of <span>Excellence</span></h1>
                <p>Sign in to your UrbanEats account to order chef-crafted dishes, track your favorites, and enjoy every bite.</p>
            </div>
            <span class="login-showcase-decoration" aria-hidden="true">UE</span>
        </section>

        <section class="card login-card" aria-labelledby="login-title">
            <div class="login-card-brand" aria-hidden="true">
                <img class="login-card-brand-icon" src="../public/images/urbaneats-logo.png" alt="">
                <strong>UrbanEats</strong>
                <small>Good Food · Better Mood</small>
            </div>
            <h2 class="login-title" id="login-title">Welcome Back</h2>
            <p class="login-subtitle">Sign in to your account to continue</p>
            <?php if ($message): ?>
                <div class="login-message" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email_value, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary">Sign In <span aria-hidden="true">→</span></button>
            </form>
            <p class="login-register-prompt">
                New to UrbanEats? <a href="register.php">Create an account</a>
            </p>
            <p class="login-staff-note">Kitchen Staff accounts are created by an Administrator. Use the email and password provided to you to sign in here.</p>
        </section>
    </main>
</body>
</html>