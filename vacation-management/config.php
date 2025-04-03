<?php
// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_DSN', 'oci:dbname=YOUR_ORACLE_DB');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Application settings
define('APP_NAME', 'Employee Vacation Management');
define('APP_URL', 'http://localhost:8000');
define('EMAIL_FROM', 'noreply@yourdomain.com');
define('EMAIL_FROM_NAME', 'Vacation Management System');

// WhatsApp API configuration (replace with your actual API details)
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send');
define('WHATSAPP_API_KEY', 'your_api_key');

// Time settings
date_default_timezone_set('UTC');

// Security settings
define('CSRF_TOKEN_SECRET', 'your-secret-key-here');
define('PASSWORD_RESET_TIMEOUT', 3600); // 1 hour

// Vacation settings
define('DEFAULT_VACATION_DAYS', 15);
define('EMPLOYMENT_YEARS_FOR_VACATION', 1);

// Role definitions
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPERVISOR', 'supervisor');
define('ROLE_EMPLOYEE', 'employee');

// Path definitions
define('ROOT_PATH', __DIR__);
define('ASSETS_PATH', ROOT_PATH . '/assets');