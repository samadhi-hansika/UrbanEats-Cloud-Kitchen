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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UrbanEats - Register</title>
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
            <a class="login-nav-register" href="register.php" aria-current="page">Register</a>
        </nav>
    </header>

    <main class="login-layout register-layout">
        <section class="login-showcase" aria-labelledby="register-showcase-title">
            <div class="login-photo-wrap">
                <img src="../public/images/customer-register-fruit.png" alt="Fresh fruit with chocolate dip">
                <span class="login-photo-label"><span aria-hidden="true">✦</span> Fresh flavors, happy moments</span>
            </div>
            <div class="login-showcase-copy">
                <span class="login-eyebrow">JOIN URBANEATS</span>
                <h1 id="register-showcase-title">Good Food Brings People <span>Together</span></h1>
                <p>Create your account and get access to delicious meals, exclusive offers, and a smoother ordering experience.</p>
            </div>
            <span class="login-showcase-decoration" aria-hidden="true">UE</span>
        </section>

        <section class="card login-card" aria-labelledby="register-title">
            <div class="login-card-brand" aria-hidden="true">
                <img class="login-card-brand-icon" src="../public/images/urbaneats-logo.png" alt="">
                <strong>UrbanEats</strong>
                <small>Good Food · Better Mood</small>
            </div>
            <h2 class="login-title" id="register-title">Create Your Account</h2>
            <p class="login-subtitle">Join UrbanEats and start your food journey today!</p>
            <?php if ($message): ?>
                <div class="login-message register-message" role="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="register-full-name">Full Name</label>
                    <input type="text" id="register-full-name" name="full_name" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email Address</label>
                    <input type="email" id="register-email" name="email" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="register-phone">Phone Number</label>
                    <input type="tel" id="register-phone" name="phone" autocomplete="tel" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn-primary">Create Account <span aria-hidden="true">→</span></button>
            </form>
            <p class="login-register-prompt">
                Already have an account? <a href="login.php">Sign in</a>
            </p>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>