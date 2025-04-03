<?php
/**
 * Configuration Example File
 * 
 * Copy this file to config.php and update the values according to your environment.
 * DO NOT commit config.php to version control.
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Enable in production with HTTPS
session_start();

// Application settings
define('APP_NAME', 'Vacation Management System');
define('APP_URL', 'http://localhost:8000'); // Update with your domain
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'UTC'); // Update with your timezone
date_default_timezone_set(APP_TIMEZONE);

// Database configuration (Oracle)
define('DB_DSN', 'oci:dbname=//localhost:1521/YOUR_SID');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'AL32UTF8');

// Email configuration (SMTP)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls or ssl
define('SMTP_AUTH', true);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_email_password');
define('EMAIL_FROM', 'noreply@example.com');
define('EMAIL_FROM_NAME', APP_NAME);

// WhatsApp API configuration (optional)
define('WHATSAPP_ENABLED', false);
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/v1/messages');
define('WHATSAPP_API_KEY', 'your_api_key');
define('WHATSAPP_SENDER_ID', 'your_sender_id');

// Security settings
define('CSRF_TOKEN_SECRET', 'change-this-to-a-random-string');
define('PASSWORD_RESET_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// Vacation settings
define('DEFAULT_VACATION_DAYS', 15);
define('MIN_DAYS_ADVANCE_REQUEST', 7); // Minimum days in advance for vacation request
define('MAX_CONSECUTIVE_DAYS', 30); // Maximum consecutive vacation days allowed
define('EMPLOYMENT_YEARS_FOR_VACATION', 1); // Years of employment before vacation eligibility

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/uploads');

// Logging configuration
define('LOG_PATH', __DIR__ . '/logs');
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR
define('MAX_LOG_FILES', 30); // Maximum number of daily log files to keep

// Cache settings (if implemented)
define('CACHE_ENABLED', false);
define('CACHE_PATH', __DIR__ . '/cache');
define('CACHE_LIFETIME', 3600); // 1 hour in seconds

// API rate limiting
define('API_RATE_LIMIT', 100); // Requests per hour
define('API_RATE_WINDOW', 3600); // 1 hour in seconds

// Development mode (disable in production)
define('DEV_MODE', true);

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorType = match($errno) {
        E_ERROR, E_USER_ERROR => 'Fatal Error',
        E_WARNING, E_USER_WARNING => 'Warning',
        E_NOTICE, E_USER_NOTICE => 'Notice',
        default => 'Unknown Error'
    };

    $message = sprintf(
        "[%s] %s\nFile: %s\nLine: %d\n",
        $errorType,
        $errstr,
        $errfile,
        $errline
    );

    // Log error
    error_log($message);

    // Display error in development mode
    if (DEV_MODE) {
        echo "<pre>$message</pre>";
    }

    // Don't execute PHP internal error handler
    return true;
}

// Set custom error handler
set_error_handler('customErrorHandler');

// Ensure required directories exist
$requiredDirs = [
    LOG_PATH,
    UPLOAD_PATH,
    CACHE_PATH
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Initialize logging
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0777, true);
}

// Clean old log files (if needed)
function cleanOldLogs() {
    $files = glob(LOG_PATH . '/*.log');
    if (count($files) > MAX_LOG_FILES) {
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        $filesToDelete = array_slice($files, 0, count($files) - MAX_LOG_FILES);
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }
}

// Clean logs periodically (1% chance on each request)
if (rand(1, 100) === 1) {
    cleanOldLogs();
}

// Function to get configuration value with default
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Validate critical configurations
function validateConfig() {
    $required = [
        'DB_DSN',
        'DB_USER',
        'DB_PASS',
        'APP_URL',
        'EMAIL_FROM',
        'CSRF_TOKEN_SECRET'
    ];

    $missing = [];
    foreach ($required as $key) {
        if (!defined($key) || empty(constant($key))) {
            $missing[] = $key;
        }
    }

    if (!empty($missing)) {
        throw new Exception('Missing required configuration: ' . implode(', ', $missing));
    }
}

// Validate configuration on load
try {
    validateConfig();
} catch (Exception $e) {
    error_log($e->getMessage());
    if (DEV_MODE) {
        die($e->getMessage());
    } else {
        die('Configuration error. Please check the error logs.');
    }
}