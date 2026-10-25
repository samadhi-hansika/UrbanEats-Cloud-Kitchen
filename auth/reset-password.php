<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);
        
        $reset_link = "reset-password.php?token=" . $token;
        $message = "Password reset link generated successfully!";
    } else {
        $message = "Email address not found in our system.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanEats - Password Reset</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <div class="container" style="max-width: 450px; margin-top: 5rem;">
        <div class="card">
            <h2>Reset Password</h2>
            <?php if ($message): ?>
                <div style="color: var(--primary-orange); margin-bottom: 1rem;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($reset_link): ?>
                <div style="background: #f0fdf4; padding: 1rem; border: 1px solid #10b981; margin-bottom: 1rem; word-break: break-all;">
                    <strong>Simulated Email Link:</strong><br>
                    <a href="<?= $reset_link ?>"><?= $reset_link ?></a>
                </div>
            <?php endif; ?>
            <form action="reset-password.php" method="POST">
                <div class="form-group">
                    <label>Enter your Account Email</label>
                    <input type="email" name="email" required>
                </div>
                <button type="submit" class="btn-primary">Generate Reset Link</button>
            </form>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>