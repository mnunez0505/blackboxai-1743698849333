<?php
/**
 * System Test Script
 * 
 * This script performs basic tests to verify the system's functionality.
 * Run this after installation to ensure everything is working correctly.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Initialize test results
$tests = [
    'database' => false,
    'email' => false,
    'whatsapp' => false,
    'file_permissions' => false,
    'cron_permissions' => false
];

$errors = [];

echo "Starting system tests...\n\n";

// Test database connection
echo "Testing database connection... ";
try {
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT 1 FROM DUAL");
    $stmt->fetch();
    $tests['database'] = true;
    echo "OK\n";
} catch (Exception $e) {
    $errors['database'] = $e->getMessage();
    echo "FAILED\n";
}

// Test email functionality
echo "Testing email configuration... ";
try {
    $result = sendEmailNotification(
        'test@example.com',
        'Test Email',
        'This is a test email from the vacation management system.'
    );
    $tests['email'] = $result;
    echo $result ? "OK\n" : "FAILED\n";
} catch (Exception $e) {
    $errors['email'] = $e->getMessage();
    echo "FAILED\n";
}

// Test WhatsApp API (if configured)
echo "Testing WhatsApp API... ";
if (defined('WHATSAPP_API_URL') && defined('WHATSAPP_API_KEY')) {
    try {
        $result = sendWhatsappNotification(
            '1234567890',
            'Test message from vacation management system.'
        );
        $tests['whatsapp'] = $result;
        echo $result ? "OK\n" : "FAILED\n";
    } catch (Exception $e) {
        $errors['whatsapp'] = $e->getMessage();
        echo "FAILED\n";
    }
} else {
    echo "SKIPPED (not configured)\n";
}

// Test file permissions
echo "Testing file permissions... ";
$requiredDirs = [
    __DIR__ . '/../cron',
    __DIR__ . '/../assets',
    __DIR__ . '/../database'
];

$permissionsOk = true;
foreach ($requiredDirs as $dir) {
    if (!is_readable($dir)) {
        $errors['file_permissions'][] = "Directory not readable: $dir";
        $permissionsOk = false;
    }
    if (!is_writable($dir)) {
        $errors['file_permissions'][] = "Directory not writable: $dir";
        $permissionsOk = false;
    }
}

$tests['file_permissions'] = $permissionsOk;
echo $permissionsOk ? "OK\n" : "FAILED\n";

// Test cron job permissions
echo "Testing cron job permissions... ";
$cronFile = __DIR__ . '/../cron/auto_vacation.php';
$cronPermissionsOk = is_readable($cronFile) && is_executable($cronFile);
$tests['cron_permissions'] = $cronPermissionsOk;
echo $cronPermissionsOk ? "OK\n" : "FAILED\n";

// Display summary
echo "\nTest Summary:\n";
echo "============\n\n";

foreach ($tests as $test => $result) {
    echo sprintf(
        "%-20s: %s\n",
        ucfirst(str_replace('_', ' ', $test)),
        $result ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m"
    );
}

// Display errors if any
if (!empty($errors)) {
    echo "\nError Details:\n";
    echo "=============\n\n";
    
    foreach ($errors as $test => $error) {
        echo ucfirst(str_replace('_', ' ', $test)) . " Errors:\n";
        if (is_array($error)) {
            foreach ($error as $err) {
                echo "- $err\n";
            }
        } else {
            echo "- $error\n";
        }
        echo "\n";
    }
}

// Verify required PHP extensions
echo "\nPHP Extensions:\n";
echo "==============\n\n";

$requiredExtensions = [
    'pdo',
    'pdo_oci',
    'curl',
    'json',
    'mbstring',
    'openssl'
];

foreach ($requiredExtensions as $ext) {
    echo sprintf(
        "%-20s: %s\n",
        $ext,
        extension_loaded($ext) ? "\033[32mInstalled\033[0m" : "\033[31mMissing\033[0m"
    );
}

// System Information
echo "\nSystem Information:\n";
echo "=================\n\n";

echo sprintf("%-20s: %s\n", "PHP Version", PHP_VERSION);
echo sprintf("%-20s: %s\n", "Operating System", PHP_OS);
echo sprintf("%-20s: %s\n", "Server Software", $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
echo sprintf("%-20s: %s\n", "Database", 'Oracle');
echo sprintf("%-20s: %s\n", "Document Root", $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown');
echo sprintf("%-20s: %s\n", "Server Name", $_SERVER['SERVER_NAME'] ?? 'Unknown');

// Recommendations
echo "\nRecommendations:\n";
echo "===============\n\n";

if (!$tests['database']) {
    echo "- Please verify your database configuration in config.php\n";
}

if (!$tests['email']) {
    echo "- Check your email configuration and SMTP settings\n";
}

if (!$tests['whatsapp'] && defined('WHATSAPP_API_URL')) {
    echo "- Verify your WhatsApp API credentials\n";
}

if (!$tests['file_permissions']) {
    echo "- Adjust directory permissions using: chmod 755 -R /path/to/vacation-management\n";
}

if (!$tests['cron_permissions']) {
    echo "- Set proper permissions for cron job: chmod +x /path/to/cron/auto_vacation.php\n";
}

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "- Install required PHP extension: $ext\n";
    }
}

echo "\nTest completed.\n";