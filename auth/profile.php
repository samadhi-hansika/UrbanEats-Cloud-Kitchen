<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$message = '';
$profile_name = '';
$profile_phone = '';
$profile_stmt = $pdo->prepare('SELECT full_name, email, phone, password_hash FROM users WHERE user_id = ?');
$profile_stmt->execute([$user_id]);
$current_user = $profile_stmt->fetch();

if (!$current_user) {
    http_response_code(404);
    exit('User profile not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    if (isset($_POST['update_profile'])) {
        $profile_name = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
        $profile_phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';

        if ($profile_name === '') {
            $message = 'Full name is required.';
        } elseif (mb_strlen($profile_name) > 100) {
            $message = 'Full name cannot exceed 100 characters.';
        } elseif (!is_valid_phone($profile_phone)) {
            $message = 'Enter a valid phone number, such as 0771234567 or +94771234567.';
        } else {
            $update_stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?');
            $update_stmt->execute([$profile_name, $profile_phone, $user_id]);
            $_SESSION['full_name'] = $profile_name;
            log_action($pdo, $user_id, 'PROFILE_UPDATE', 'User updated personal details');
            set_flash('success', 'Your profile details were updated.');
            redirect('auth/profile.php');
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new_password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm_password = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

        if (!password_verify($current_password, $current_user['password_hash'])) {
            $message = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New password and confirmation do not match.';
        } elseif (password_verify($new_password, $current_user['password_hash'])) {
            $message = 'Choose a new password that differs from your current password.';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $update_stmt->execute([$password_hash, $user_id]);
            log_action($pdo, $user_id, 'PASSWORD_CHANGE', 'User changed account password');
            set_flash('success', 'Your password was changed successfully.');
            redirect('auth/profile.php');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_profile'])) {
    $profile_name = $current_user['full_name'];
    $profile_phone = $current_user['phone'];
}

$page_title = 'My Profile';
include '../includes/header.php';
?>

<section class="account-profile-page">
    <header class="account-profile-heading">
        <div class="account-profile-avatar" aria-hidden="true">
            <?= e(mb_strtoupper(mb_substr($current_user['full_name'], 0, 1))) ?>
        </div>
        <div class="account-profile-heading-copy">
            <span class="role-dashboard-eyebrow">URBANEATS ACCOUNT</span>
            <h1>My Profile</h1>
            <p>Manage your account information and keep your sign-in details secure.</p>
        </div>
        <span class="account-profile-role"><?= e($_SESSION['role'] ?? 'User') ?></span>
    </header>

    <?php if ($message !== ''): ?>
        <div class="account-profile-message error" role="alert"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="account-profile-grid">
        <section class="account-profile-card personal-information-card" aria-labelledby="profile-details-title">
            <div class="account-profile-card-heading">
                <span class="account-profile-card-icon" aria-hidden="true">👤</span>
                <div>
                    <h2 id="profile-details-title">Personal information</h2>
                    <p>Update the details associated with your account.</p>
                </div>
            </div>

            <form method="post" action="<?= e(url('auth/profile.php')) ?>" class="account-profile-form">
                <?= csrf_field() ?>
                <input type="hidden" name="update_profile" value="1">
                <div class="account-profile-field">
                    <label for="profile-full-name">Full name</label>
                    <input type="text" id="profile-full-name" name="full_name" maxlength="100" autocomplete="name" value="<?= e($profile_name) ?>" required>
                </div>
                <div class="account-profile-field">
                    <label for="profile-email">Email address</label>
                    <input type="email" id="profile-email" value="<?= e($current_user['email']) ?>" readonly>
                    <small>Email is managed as your sign-in ID and cannot be changed here.</small>
                </div>
                <div class="account-profile-field">
                    <label for="profile-phone">Phone number</label>
                    <input type="tel" id="profile-phone" name="phone" autocomplete="tel" placeholder="0771234567" value="<?= e($profile_phone) ?>" required>
                    <small>Use a 10-digit local number or +94 followed by 9 digits.</small>
                </div>
                <button type="submit" class="account-profile-submit">Save profile</button>
            </form>
        </section>

        <section class="account-profile-card password-change-card" aria-labelledby="profile-password-title">
            <div class="account-profile-card-heading">
                <span class="account-profile-card-icon password" aria-hidden="true">🔒</span>
                <div>
                    <h2 id="profile-password-title">Change password</h2>
                    <p>Choose a strong password you do not use elsewhere.</p>
                </div>
            </div>

            <form method="post" action="<?= e(url('auth/profile.php')) ?>" class="account-profile-form">
                <?= csrf_field() ?>
                <input type="hidden" name="change_password" value="1">
                <div class="account-profile-field">
                    <label for="current-password">Current password</label>
                    <input type="password" id="current-password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="account-profile-field">
                    <label for="new-password">New password</label>
                    <input type="password" id="new-password" name="new_password" autocomplete="new-password" minlength="8" required>
                    <small>Use at least 8 characters.</small>
                </div>
                <div class="account-profile-field">
                    <label for="confirm-password">Confirm new password</label>
                    <input type="password" id="confirm-password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                </div>
                <button type="submit" class="account-profile-submit password-submit">Update password</button>
            </form>
        </section>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
