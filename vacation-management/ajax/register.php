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
    $required_fields = ['fullname', 'email', 'phone', 'username', 'password', 'confirm_password', 'date_hire'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please provide {$field}");
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate password strength
    if (strlen($_POST['password']) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        throw new Exception('Passwords do not match');
    }

    // Sanitize inputs
    $fullname = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $username = sanitizeInput($_POST['username']);
    $date_hire = sanitizeInput($_POST['date_hire']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $pdo = connectDB();

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM USERS WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Username already exists');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM USERS WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email already registered');
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO USERS (
                username, 
                password, 
                email, 
                phone, 
                fullname, 
                role, 
                date_hire, 
                vacation_days, 
                created_at
            ) VALUES (
                :username,
                :password,
                :email,
                :phone,
                :fullname,
                'employee',
                TO_DATE(:date_hire, 'YYYY-MM-DD'),
                0,
                CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'phone' => $phone,
            'fullname' => $fullname,
            'date_hire' => $date_hire
        ]);

        // Get the new user ID
        $userId = $pdo->lastInsertId();

        // Commit transaction
        $pdo->commit();

        // Send welcome email
        $emailSubject = "Welcome to " . APP_NAME;
        $emailMessage = "
            <h2>Welcome to " . APP_NAME . "!</h2>
            <p>Dear {$fullname},</p>
            <p>Your account has been successfully created. You can now log in using your username and password.</p>
            <p>Username: {$username}</p>
            <p>Please keep your login credentials secure.</p>
            <p>Best regards,<br>" . APP_NAME . " Team</p>
        ";

        sendEmailNotification($email, $emailSubject, $emailMessage);

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful! You can now login.'
        ]);

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}