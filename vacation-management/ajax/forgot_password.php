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

    // Validate email
    if (empty($_POST['email'])) {
        throw new Exception('Please provide an email address');
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    try {
        $pdo = connectDB();

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, username, fullname FROM USERS WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // For security, don't reveal if email exists or not
            echo json_encode([
                'status' => 'success',
                'message' => 'If your email is registered, you will receive password reset instructions shortly.'
            ]);
            exit;
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token in database
        $stmt = $pdo->prepare("
            UPDATE USERS 
            SET reset_token = :token,
                reset_token_expiry = TO_TIMESTAMP(:token_expiry, 'YYYY-MM-DD HH24:MI:SS')
            WHERE id = :user_id
        ");

        $stmt->execute([
            'token' => $token,
            'token_expiry' => $token_expiry,
            'user_id' => $user['id']
        ]);

        // Generate reset link
        $resetLink = APP_URL . '/reset_password.php?token=' . $token;

        // Prepare email content
        $emailSubject = "Password Reset Request - " . APP_NAME;
        $emailMessage = "
            <h2>Password Reset Request</h2>
            <p>Dear {$user['fullname']},</p>
            <p>We received a request to reset your password. Click the link below to set a new password:</p>
            <p><a href='{$resetLink}' style='
                display: inline-block;
                padding: 10px 20px;
                background-color: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            '>Reset Password</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p>{$resetLink}</p>
            <p>This link will expire in 1 hour for security reasons.</p>
            <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
            <p>Best regards,<br>" . APP_NAME . " Team</p>
        ";

        // Send reset email
        if (sendEmailNotification($email, $emailSubject, $emailMessage)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'If your email is registered, you will receive password reset instructions shortly.'
            ]);
        } else {
            throw new Exception('Failed to send reset email');
        }

    } catch (PDOException $e) {
        error_log("Database Error in forgot_password.php: " . $e->getMessage());
        throw new Exception('An error occurred while processing your request');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}