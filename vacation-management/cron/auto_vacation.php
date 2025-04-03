<?php
/**
 * Cron Job Script: Auto Vacation Days Assignment
 * 
 * This script should be run daily via cron job to:
 * 1. Check for employees who have completed one year of employment
 * 2. Assign them their vacation days (15 days)
 * 3. Send notification emails
 * 
 * Recommended cron schedule: Daily at midnight
 * 0 0 * * * /usr/bin/php /path/to/vacation-management/cron/auto_vacation.php
 */

// Set script execution time limit (0 = no limit)
set_time_limit(0);

// Load configuration and functions
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Initialize logging
$logFile = __DIR__ . '/auto_vacation.log';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    writeLog("Starting auto vacation days assignment process...");
    
    $pdo = connectDB();
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get eligible employees (completed 1 year and haven't received vacation days yet)
    $stmt = $pdo->prepare("
        SELECT id, fullname, email, date_hire, vacation_days
        FROM USERS
        WHERE EXTRACT(YEAR FROM age(CURRENT_DATE, date_hire)) >= 1
        AND vacation_days = 0
        AND role != 'admin'
    ");
    $stmt->execute();
    $eligibleEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Found " . count($eligibleEmployees) . " eligible employees.");
    
    foreach ($eligibleEmployees as $employee) {
        try {
            // Update employee's vacation days
            $stmt = $pdo->prepare("
                UPDATE USERS 
                SET vacation_days = :vacation_days,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :employee_id
            ");
            
            $stmt->execute([
                'vacation_days' => DEFAULT_VACATION_DAYS,
                'employee_id' => $employee['id']
            ]);
            
            // Send notification email
            $emailSubject = "Vacation Days Credited - " . APP_NAME;
            $emailMessage = "
                <h2>Vacation Days Credited</h2>
                <p>Dear {$employee['fullname']},</p>
                <p>Congratulations on completing one year with us!</p>
                <p>We are pleased to inform you that {DEFAULT_VACATION_DAYS} vacation days have been credited to your account.</p>
                <p>You can now submit vacation requests through the system.</p>
                <p>Best regards,<br>" . APP_NAME . " Team</p>
            ";
            
            if (sendEmailNotification($employee['email'], $emailSubject, $emailMessage)) {
                writeLog("Successfully processed employee: {$employee['fullname']} (ID: {$employee['id']})");
            } else {
                writeLog("Warning: Email notification failed for employee: {$employee['fullname']} (ID: {$employee['id']})");
            }
            
            // Optional: Send WhatsApp notification
            if (defined('WHATSAPP_API_URL') && !empty($employee['phone'])) {
                $whatsappMessage = "Congratulations on completing one year!\n";
                $whatsappMessage .= DEFAULT_VACATION_DAYS . " vacation days have been credited to your account.\n";
                $whatsappMessage .= "You can now submit vacation requests through the system.";
                
                sendWhatsappNotification($employee['phone'], $whatsappMessage);
            }
            
        } catch (Exception $e) {
            writeLog("Error processing employee {$employee['id']}: " . $e->getMessage());
            continue;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    writeLog("Auto vacation days assignment process completed successfully.");
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    writeLog("Error: " . $e->getMessage());
    writeLog("Process terminated with errors.");
    
    // Send alert email to admin
    $adminEmail = 'admin@yourdomain.com'; // Configure this
    $errorSubject = "Error: Auto Vacation Days Assignment - " . APP_NAME;
    $errorMessage = "
        <h2>Auto Vacation Days Assignment Error</h2>
        <p>An error occurred during the automatic vacation days assignment process:</p>
        <pre>{$e->getMessage()}</pre>
        <p>Please check the log file for more details.</p>
    ";
    
    sendEmailNotification($adminEmail, $errorSubject, $errorMessage);
}

// Function to calculate years of service
function calculateYearsOfService($hireDate) {
    $hire = new DateTime($hireDate);
    $now = new DateTime();
    $interval = $hire->diff($now);
    return $interval->y;
}