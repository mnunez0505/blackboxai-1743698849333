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
    if (empty($_POST['username']) || empty($_POST['password'])) {
        throw new Exception('Please provide both username and password');
    }

    // Sanitize inputs
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password before verification

    // Attempt authentication
    $user = authenticateUser($username, $password);

    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        // Update last login timestamp in database
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("UPDATE USERS SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
        } catch (PDOException $e) {
            // Log error but don't stop the login process
            error_log("Failed to update last login: " . $e->getMessage());
        }

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'role' => $user['role']
        ]);
    } else {
        throw new Exception('Invalid username or password');
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}