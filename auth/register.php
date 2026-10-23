<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '' || $email === '' || $phone === '' || $password === '') {
        $message = "All required fields must be filled!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $message = "Email address is already registered!";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $email, $phone, $password_hash, 1])) {
                $new_user_id = $pdo->lastInsertId();
                log_action($pdo, $new_user_id, 'USER_REGISTERED', 'New user account created successfully');
                header("Location: login.php?registered=success");
                exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanEats - Register</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <div class="container" style="max-width: 450px; margin-top: 3rem;">
        <div class="card">
            <h2 style="text-align: center; color: var(--primary-orange);">Create Account</h2>
            <?php if ($message): ?>
                <div style="color: var(--danger-red); margin-bottom: 1rem; text-align: center;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Register</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;">
                Already have an account? <a href="login.php">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>