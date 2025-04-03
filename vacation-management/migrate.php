<?php
/**
 * Database Migration Script
 * 
 * This script helps migrate data between different versions of the system.
 * It should be run after updating the system to a new version.
 */

require_once 'config.php';
require_once 'functions.php';

// Set script execution time limit
set_time_limit(0);

// Initialize logging
$logFile = __DIR__ . '/logs/migration_' . date('Y-m-d_H-i-s') . '.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "$message\n";
}

// Available migrations
$migrations = [
    '1.0.0' => [
        'description' => 'Initial schema setup',
        'queries' => [
            // These queries are already in schema.sql
        ]
    ],
    '1.0.1' => [
        'description' => 'Add email preferences',
        'queries' => [
            "ALTER TABLE USERS ADD email_notifications NUMBER(1) DEFAULT 1",
            "ALTER TABLE USERS ADD whatsapp_notifications NUMBER(1) DEFAULT 0",
            "COMMENT ON COLUMN USERS.email_notifications IS 'Enable/disable email notifications'",
            "COMMENT ON COLUMN USERS.whatsapp_notifications IS 'Enable/disable WhatsApp notifications'"
        ]
    ],
    '1.0.2' => [
        'description' => 'Add vacation categories',
        'queries' => [
            "CREATE TABLE VACATION_CATEGORIES (
                id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                name VARCHAR2(50) NOT NULL,
                description VARCHAR2(200),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "ALTER TABLE VACATION_REQUESTS ADD category_id NUMBER",
            "ALTER TABLE VACATION_REQUESTS ADD CONSTRAINT fk_vacation_category 
             FOREIGN KEY (category_id) REFERENCES VACATION_CATEGORIES(id)"
        ]
    ],
    '1.0.3' => [
        'description' => 'Add department management',
        'queries' => [
            "CREATE TABLE DEPARTMENTS (
                id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                name VARCHAR2(100) NOT NULL,
                description VARCHAR2(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "ALTER TABLE USERS ADD department_id NUMBER",
            "ALTER TABLE USERS ADD CONSTRAINT fk_user_department 
             FOREIGN KEY (department_id) REFERENCES DEPARTMENTS(id)"
        ]
    ]
];

try {
    // Get current version from database
    $pdo = connectDB();
    
    // Create version table if it doesn't exist
    $pdo->exec("
        BEGIN
            EXECUTE IMMEDIATE 'CREATE TABLE SYSTEM_VERSION (
                version VARCHAR2(20) NOT NULL,
                installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )';
            EXECUTE IMMEDIATE 'INSERT INTO SYSTEM_VERSION (version) VALUES (''1.0.0'')';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE = -955 THEN NULL; END IF;
        END;
    ");
    
    // Get current version
    $stmt = $pdo->query("SELECT version FROM SYSTEM_VERSION ORDER BY installed_at DESC");
    $currentVersion = $stmt->fetchColumn();
    
    writeLog("Current system version: $currentVersion");
    
    // Show available migrations
    echo "\nAvailable migrations:\n";
    foreach ($migrations as $version => $migration) {
        if (version_compare($version, $currentVersion, '>')) {
            echo "- $version: {$migration['description']}\n";
        }
    }
    
    // Ask which version to migrate to
    echo "\nEnter target version (or 'latest' for latest version): ";
    $targetVersion = trim(fgets(STDIN));
    
    if ($targetVersion === 'latest') {
        $targetVersion = max(array_keys($migrations));
    }
    
    if (!isset($migrations[$targetVersion])) {
        throw new Exception("Invalid target version");
    }
    
    if (version_compare($targetVersion, $currentVersion, '<=')) {
        throw new Exception("Target version must be higher than current version");
    }
    
    // Confirm migration
    echo "\nWARNING: This will migrate the database from $currentVersion to $targetVersion\n";
    echo "Make sure you have a backup before proceeding!\n";
    echo "Continue? (yes/no): ";
    $confirm = trim(fgets(STDIN));
    
    if (strtolower($confirm) !== 'yes') {
        throw new Exception("Migration cancelled by user");
    }
    
    // Start migration
    writeLog("Starting migration to version $targetVersion");
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Run migrations in order
    foreach ($migrations as $version => $migration) {
        if (version_compare($version, $currentVersion, '>') && 
            version_compare($version, $targetVersion, '<=')) {
            
            writeLog("Applying migration $version: {$migration['description']}");
            
            foreach ($migration['queries'] as $query) {
                writeLog("Executing: $query");
                $pdo->exec($query);
            }
            
            // Update version in database
            $stmt = $pdo->prepare("INSERT INTO SYSTEM_VERSION (version) VALUES (:version)");
            $stmt->execute(['version' => $version]);
            
            writeLog("Migration to version $version completed");
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    writeLog("Migration completed successfully");
    writeLog("New system version: $targetVersion");
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    writeLog("ERROR: " . $e->getMessage());
    writeLog("Migration failed - database rolled back");
    
    // Send alert email to admin
    $adminEmail = 'admin@yourdomain.com'; // Configure this
    $errorSubject = "Error: Database Migration - " . APP_NAME;
    $errorMessage = "
        <h2>Database Migration Error</h2>
        <p>An error occurred during database migration:</p>
        <pre>{$e->getMessage()}</pre>
        <p>Please check the migration log for more details.</p>
    ";
    
    sendEmailNotification($adminEmail, $errorSubject, $errorMessage);
    
    exit(1);
}

// Exit successfully
exit(0);