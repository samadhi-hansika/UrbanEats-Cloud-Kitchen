<?php
/**
 * Shared Helper Functions
 * IT3113 UrbanEats Cloud Kitchen Platform
 */

if (!defined('BASE_URL')) {
    define('BASE_URL', '/UrbanEats-Cloud-Kitchen');
}

/**
 * Return absolute URL within the application
 */
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Return the dashboard URL for a known account role
 */
function dashboard_url_for_role(string $role): string {
    $dashboards = [
        'Customer' => 'customer/dashboard.php',
        'Kitchen Staff' => 'kitchen/dashboard.php',
        'Administrator' => 'admin/dashboard.php',
    ];

    return url($dashboards[$role] ?? 'errors/403.php');
}

/**
 * Redirect to an application URL
 */
function redirect(string $path): void {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: $path");
    } else {
        header("Location: " . url($path));
    }
    exit();
}

/**
 * Escape string for safe HTML output
 * IT3113 Requirement: "output escaping with htmlspecialchars()"
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message to session
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Get flash messages by type and remove from session
 */
function get_flashes(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Return old form input from POST
 */
function old(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? e($_POST[$key]) : e($default);
}

/**
 * Validate Sri Lankan or international phone numbers
 */
function is_valid_phone(string $phone): bool {
    // Allows 10 digits (e.g. 0771234567) or +94 followed by 9 digits
    return preg_match('/^(?:\+94|0)[0-9]{9}$/', trim($phone)) === 1;
}

/**
 * Validate email address
 */
function is_valid_email(string $email): bool {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}
