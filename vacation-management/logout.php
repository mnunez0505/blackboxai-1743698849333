<?php
require_once 'config.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Log the logout action if needed
    if (isset($_SESSION['user_id'])) {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("
                UPDATE USERS 
                SET last_logout = CURRENT_TIMESTAMP 
                WHERE id = :user_id
            ");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
        } catch (PDOException $e) {
            // Log error but continue with logout
            error_log("Logout Error: " . $e->getMessage());
        }
    }

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();

} catch (Exception $e) {
    error_log("Logout Error: " . $e->getMessage());
} finally {
    // Redirect to login page
    header('Location: index.php');
    exit;
}