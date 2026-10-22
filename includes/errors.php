<?php
/**
 * Custom Error and Exception Handler
 * IT3113 Requirement: "Custom error/exception handler, friendly error pages, errors written to a log file, and no raw PHP or SQL errors shown to the user."
 */

function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] [PHP Error $errno] $errstr in $errfile on line $errline" . PHP_EOL;
    @error_log($message, 3, $log_dir . '/error.log');
    return false; // Let standard error handling continue for notices/warnings if configured
}

function custom_exception_handler($exception) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] [Uncaught Exception] " . $exception->getMessage() . 
               " in " . $exception->getFile() . " on line " . $exception->getLine() . 
               PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
    @error_log($message, 3, $log_dir . '/error.log');

    // Friendly error page
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (file_exists(__DIR__ . '/../errors/500.php')) {
        include __DIR__ . '/../errors/500.php';
    } else {
        echo "<h1>500 - Server Error</h1><p>A system error occurred. Please try again later.</p>";
    }
    exit();
}

set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');