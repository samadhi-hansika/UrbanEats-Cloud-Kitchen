<?php
/**
 * Authentication and Authorization Guard
 * IT3113 Component A: Identity, Access and Administration
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';

// Session hardening: 30-minute idle session timeout
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        // Session expired due to inactivity
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: " . url('auth/login.php?reason=timeout'));
        exit();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Guard: Require logged in user
 */
function require_login(): void {
    if (!is_logged_in()) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $_SESSION['redirect_after_login'] = $current_url;
        header("Location: " . url('auth/login.php?reason=auth_required'));
        exit();
    }
}

/**
 * Guard: Require specific role(s)
 * Accepts a single role name string or an array of allowed roles
 */
function require_role($allowed_roles): void {
    require_login();

    $current_role = $_SESSION['role'] ?? '';
    if (is_array($allowed_roles)) {
        if (!in_array($current_role, $allowed_roles, true)) {
            header("Location: " . url('errors/403.php'));
            exit();
        }
    } else {
        if ($current_role !== $allowed_roles) {
            header("Location: " . url('errors/403.php'));
            exit();
        }
    }
}

/**
 * Log action to audit_log table
 * Supports both signatures:
 * 1) log_action('ACTION_NAME', 'Details string')
 * 2) log_action($pdo, $user_id, 'ACTION_NAME', 'Details string')
 */
function log_action($arg1, $arg2 = null, $arg3 = null, $arg4 = null): void {
    global $pdo;

    $db = null;
    $user_id = null;
    $action = '';
    $details = '';

    if ($arg1 instanceof PDO) {
        $db = $arg1;
        $user_id = $arg2;
        $action = (string)$arg3;
        $details = (string)$arg4;
    } else {
        $db = $pdo;
        $user_id = $_SESSION['user_id'] ?? null;
        $action = (string)$arg1;
        $details = (string)($arg2 ?? '');
    }

    if (!$db) {
        return;
    }

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (\Throwable $e) {
        // Fallback log to file if database logging fails
        $log_dir = __DIR__ . '/../logs';
        @error_log("[Audit Log Failure] " . $e->getMessage() . PHP_EOL, 3, $log_dir . '/error.log');
    }
}