<?php
/**
 * XAMPP Configuration Example File
 * 
 * Copy this file to config.php and update the values according to your environment.
 * This configuration is optimized for XAMPP on Windows.
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Application settings
define('APP_NAME', 'Vacation Management System');
define('APP_URL', 'http://localhost/vacation-management'); // Update with your URL
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/New_York'); // Update with your timezone
date_default_timezone_set(APP_TIMEZONE);

// Database configuration
// Option 1: MySQL (Default in XAMPP)
define('DB_TYPE', 'mysql'); // mysql or oracle
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'vacation_management');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP MySQL has no password
define('DB_CHARSET', 'utf8mb4');

// Option 2: Oracle (If installed)
/*
define('DB_TYPE', 'oracle');
define('DB_DSN', 'oci:dbname=//localhost:1521/XE'); // XE for Oracle Express Edition
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'AL32UTF8');
*/

// Database connection function
function connectDB() {
    try {
        if (DB_TYPE === 'mysql') {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } else {
            return new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception('Database connection failed');
    }
}

// Email configuration (using Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls or ssl
define('SMTP_AUTH', true);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_specific_password'); // Use App Password for Gmail
define('EMAIL_FROM', 'your_email@gmail.com');
define('EMAIL_FROM_NAME', APP_NAME);

// File upload settings
define('UPLOAD_PATH', __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

// Logging configuration
define('LOG_PATH', __DIR__ . '/logs');
define('ERROR_LOG', LOG_PATH . '/error.log');
define('ACCESS_LOG', LOG_PATH . '/access.log');

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_PATH', __DIR__ . '/cache');
define('CACHE_LIFETIME', 3600); // 1 hour

// Security settings
define('CSRF_TOKEN_SECRET', 'change-this-to-a-random-string');
define('PASSWORD_RESET_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Vacation settings
define('DEFAULT_VACATION_DAYS', 15);
define('MIN_DAYS_ADVANCE_REQUEST', 7);
define('MAX_CONSECUTIVE_DAYS', 30);

// Development mode (disable in production)
define('DEV_MODE', true);

// Create required directories
$directories = [
    LOG_PATH,
    UPLOAD_PATH,
    CACHE_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $message = sprintf(
        "[%s] %s\nFile: %s\nLine: %d\n",
        date('Y-m-d H:i:s'),
        $errstr,
        $errfile,
        $errline
    );

    error_log($message, 3, ERROR_LOG);

    if (DEV_MODE) {
        echo "<pre>$message</pre>";
    } else {
        echo "An error occurred. Please check the error log.";
    }

    return true;
}

set_error_handler('customErrorHandler');

// Function to get configuration value
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Validate critical configurations
try {
    $required = [
        'DB_USER',
        'DB_PASS',
        'APP_URL',
        'EMAIL_FROM',
        'CSRF_TOKEN_SECRET'
    ];

    foreach ($required as $key) {
        if (!defined($key) || empty(constant($key))) {
            throw new Exception("Missing required configuration: $key");
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    if (DEV_MODE) {
        die($e->getMessage());
    } else {
        die('Configuration error. Please check the error logs.');
    }
}