<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/UrbanEats-Cloud-Kitchen');
}

$host = 'localhost';
$db   = 'urbaneats_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log error to logs/error.log
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    error_log("[Database Connection Error] " . $e->getMessage() . "\n", 3, $log_dir . '/error.log');
    
    // Friendly error message without leaking sensitive credentials or raw SQL
    http_response_code(500);
    include __DIR__ . '/../errors/500.php';
    exit();
}