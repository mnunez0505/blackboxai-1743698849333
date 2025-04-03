<?php
require_once '../config.php';
require_once '../functions.php';

// Set header to return JSON response
header('Content-Type: application/json');

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    // Validate required fields
    if (empty($_POST['token']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
        throw new Exception('All fields are required');
    }

    // Validate password
    if (strlen($_POST['password']) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        throw new Exception('Passwords do not match');
    }

    $token = $_POST['token'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $pdo = connectDB();

        // Begin transaction
        $pdo->beginTransaction();

        // Get user by reset token and check expiration
        $stmt = $pdo->prepare("
            SELECT id, email, fullname 
            FROM USERS 
            WHERE reset_token = :token 
            AND reset_token_expiry > CURRENT_TIMESTAMP
        ");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('Invalid or expired reset token');
        }

        // Update password and clear reset token
        $stmt = $pdo->prepare("
            UPDATE USERS 
            SET password = :password,
                reset_token = NULL,
                reset_token_expiry = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
        ");

        $stmt->execute([
            'password' => $password,
            'user_id' => $user['id']
        ]);

        // Commit transaction
        $pdo->commit();

        // Send confirmation email
        $emailSubject = "Password Successfully Reset - " . APP_NAME;
        $emailMessage = "
            <h2>Password Reset Successful</h2>
            <p>Dear {$user['fullname']},</p>
            <p>Your password has been successfully reset. You can now log in with your new password.</p>
            <p>If you did not make this change, please contact support immediately.</p>
            <p>Best regards,<br>" . APP_NAME . " Team</p>
        ";

        sendEmailNotification($user['email'], $emailSubject, $emailMessage);

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Password has been reset successfully. You can now login with your new password.'
        ]);

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database Error in reset_password.php: " . $e->getMessage());
        throw new Exception('An error occurred while resetting your password');
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}