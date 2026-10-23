<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $phone, $user_id]);
        $message = "Profile updated successfully!";
        log_action($pdo, $user_id, 'PROFILE_UPDATE', 'User updated personal details');
    } elseif (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_pass, $user['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$new_hash, $user_id]);
            $message = "Password changed successfully!";
            log_action($pdo, $user_id, 'PASSWORD_CHANGE', 'User changed account password');
        } else {
            $message = "Current password is incorrect!";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanEats - Profile</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="profile-page">
    <div class="container profile-container">
        <div class="card profile-glass-card">
            <span class="profile-eyebrow">URBANEATS ACCOUNT</span>
            <h2>User Profile Settings</h2>
            <?php if ($message): ?>
                <div class="profile-message" role="status"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form action="profile.php" method="POST" class="profile-form">
                <h3>Update Information</h3>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($current_user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($current_user['phone']) ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
            </form>

            <hr class="profile-divider">

            <form action="profile.php" method="POST" class="profile-form">
                <h3>Change Password</h3>
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                </div>
                <button type="submit" name="change_password" class="btn-primary profile-password-button">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>