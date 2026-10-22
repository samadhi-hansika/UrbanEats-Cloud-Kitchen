<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && is_logged_in()) {
    header('Location: ' . dashboard_url_for_role($_SESSION['role'] ?? ''));
    exit();
}

$message = '';

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
    <title>UrbanEats - Login</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="login-page">
    <main class="container login-container">
        <div class="card login-card">
            <h2 class="login-title">UrbanEats Login</h2>
            <?php if ($message): ?>
                <div class="login-message" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary">Sign In</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;">
                Don't have an account? <a href="register.php">Create one</a>
            </p>
        </div>
    </main>
</body>
</html>