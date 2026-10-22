<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('Administrator');

$message = '';
$allowed_roles = ['Kitchen Staff', 'Administrator'];
$role_stmt = $pdo->prepare("SELECT role_id, role_name FROM roles WHERE role_name IN (?, ?) ORDER BY role_id");
$role_stmt->execute($allowed_roles);
$available_roles = $role_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_name = $_POST['role_name'] ?? '';

    if ($full_name === '' || $email === '' || $phone === '' || $password === '' || $role_name === '') {
        $message = 'All fields are required to create an account.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (!in_array($role_name, $allowed_roles, true)) {
        $message = 'Please select a valid staff role.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $message = 'Email address is already registered.';
        } else {
            $role_id = null;
            foreach ($available_roles as $role) {
                if ($role['role_name'] === $role_name) {
                    $role_id = $role['role_id'];
                    break;
                }
            }

            if ($role_id === null) {
                $message = 'The selected role is not available.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $phone, $password_hash, $role_id]);
                $new_user_id = $pdo->lastInsertId();
                log_action($pdo, $_SESSION['user_id'], 'USER_CREATED', "Created {$role_name} account with user ID: {$new_user_id}");
                $message = "{$role_name} account created successfully.";
            }
        }
    }
}

// Handle Toggle User Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $uid = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
    $stmt->execute([$uid]);
    log_action($pdo, $_SESSION['user_id'], 'USER_STATUS_TOGGLE', "Toggled status for user ID: $uid");
    header("Location: users.php");
    exit();
}

$users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UrbanEats - Manage Users</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>User Management Console</h2>
            <h3>Create Kitchen Staff or Administrator Account</h3>
            <?php if ($message): ?>
                <div role="alert" style="color: <?= str_contains($message, 'successfully') ? 'var(--success-green)' : 'var(--danger-red)' ?>; margin-bottom: 1rem;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="users.php" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="role_name">Account Role</label>
                    <select id="role_name" name="role_name" required>
                        <option value="">Select a role</option>
                        <?php foreach ($available_roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['role_name']) ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
        </div>

        <div class="card">
            <h2>Existing Accounts</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role_name']) ?></td>
                        <td>
                            <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Disabled' ?>
                            </span>
                        </td>
                        <td>
                            <a href="users.php?action=toggle&id=<?= $u['user_id'] ?>" style="color: var(--primary-orange);">Toggle Status</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>