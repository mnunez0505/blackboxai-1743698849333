<?php
/**
 * MySQL Connection Test Script
 * 
 * This script tests the MySQL connection and configuration
 * for the Vacation Management System in XAMPP environment.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to print colored output
function printMessage($message, $type = 'info') {
    $colors = [
        'success' => "\033[32m", // Green
        'error'   => "\033[31m", // Red
        'info'    => "\033[33m", // Yellow
        'reset'   => "\033[0m"   // Reset
    ];
    
    $prefix = [
        'success' => '[+] ',
        'error'   => '[-] ',
        'info'    => '[*] '
    ];
    
    echo $colors[$type] . $prefix[$type] . $message . $colors['reset'] . "\n";
}

// Header
echo "\n================================\n";
echo "MySQL Connection Test\n";
echo "================================\n\n";

// Test 1: Check PHP version
printMessage("Checking PHP version...", 'info');
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    printMessage("PHP version " . PHP_VERSION . " is compatible", 'success');
} else {
    printMessage("PHP version " . PHP_VERSION . " is not compatible (7.4+ required)", 'error');
}

// Test 2: Check required extensions
printMessage("\nChecking required extensions...", 'info');
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        printMessage("Extension $ext is loaded", 'success');
    } else {
        printMessage("Extension $ext is missing", 'error');
    }
}

// Test 3: Load configuration
printMessage("\nChecking configuration files...", 'info');
$config_files = ['config.php', 'config.xampp.php'];
$config_loaded = false;

foreach ($config_files as $config_file) {
    if (file_exists(__DIR__ . '/../' . $config_file)) {
        require_once __DIR__ . '/../' . $config_file;
        $config_loaded = true;
        printMessage("Loaded configuration from $config_file", 'success');
        break;
    }
}

if (!$config_loaded) {
    printMessage("No configuration file found", 'error');
    exit(1);
}

// Test 4: Check configuration values
printMessage("\nChecking database configuration...", 'info');
$required_constants = ['DB_HOST', 'DB_USER', 'DB_NAME'];
$missing_constants = [];

foreach ($required_constants as $constant) {
    if (!defined($constant)) {
        $missing_constants[] = $constant;
    }
}

if (!empty($missing_constants)) {
    printMessage("Missing configuration constants: " . implode(', ', $missing_constants), 'error');
    exit(1);
}

// Test 5: Test database connection
printMessage("\nTesting database connection...", 'info');
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS ?? '', $options);
    printMessage("Database connection successful", 'success');
    
    // Test 6: Check tables
    printMessage("\nChecking database tables...", 'info');
    $required_tables = ['users', 'vacation_requests', 'system_logs', 'departments', 'vacation_categories'];
    $existing_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            printMessage("Table '$table' exists", 'success');
        } else {
            printMessage("Table '$table' is missing", 'error');
        }
    }
    
    // Test 7: Check admin user
    printMessage("\nChecking admin user...", 'info');
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;
    
    if ($adminExists) {
        printMessage("Admin user exists", 'success');
    } else {
        printMessage("Admin user is missing", 'error');
    }
    
    // Test 8: Check database version
    printMessage("\nChecking database version...", 'info');
    $stmt = $pdo->query("SELECT version FROM system_version ORDER BY installed_at DESC LIMIT 1");
    $version = $stmt->fetchColumn();
    printMessage("Database version: $version", 'success');
    
} catch (PDOException $e) {
    printMessage("Database connection failed: " . $e->getMessage(), 'error');
    exit(1);
}

// Summary
echo "\n================================\n";
echo "Test Summary\n";
echo "================================\n\n";

printMessage("PHP Version: " . PHP_VERSION, 'info');
printMessage("MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION), 'info');
printMessage("Character Set: " . $pdo->query('SELECT @@character_set_database')->fetchColumn(), 'info');
printMessage("Collation: " . $pdo->query('SELECT @@collation_database')->fetchColumn(), 'info');

echo "\nRecommendations:\n";
echo "1. Keep regular backups of your database\n";
echo "2. Update admin password if not already changed\n";
echo "3. Configure email settings in config.php\n";
echo "4. Set up scheduled tasks for automation\n";
echo "5. Monitor error logs regularly\n\n";

// Exit successfully
exit(0);