<?php
require_once 'config.php';

/**
 * Database connection function using PDO
 * @return PDO
 */
function connectDB() {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed. Please try again later.");
    }
}

/**
 * User Authentication
 * @param string $username
 * @param string $password
 * @return array|false
 */
function authenticateUser($username, $password) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT id, username, password, role, email FROM USERS WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Remove password from array
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Authentication Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Email Notification
 * @param string $to
 * @param string $subject
 * @param string $message
 * @return bool
 */
function sendEmailNotification($to, $subject, $message) {
    try {
        $headers = [
            'From' => EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send WhatsApp Notification
 * @param string $phone
 * @param string $message
 * @return bool
 */
function sendWhatsappNotification($phone, $message) {
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => WHATSAPP_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'phone' => $phone,
                'message' => $message
            ]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . WHATSAPP_API_KEY,
                "Content-Type: application/json"
            ],
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        return $response !== false;
    } catch (Exception $e) {
        error_log("WhatsApp Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSRF Token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get Menu Items by Role
 * @param string $role
 * @return array
 */
function getMenuByRole($role) {
    $menu = [
        'employee' => [
            ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fas fa-home'],
            ['title' => 'Request Vacation', 'url' => 'request_vacation.php', 'icon' => 'fas fa-calendar-plus'],
            ['title' => 'My Requests', 'url' => 'my_requests.php', 'icon' => 'fas fa-list']
        ],
        'supervisor' => [
            ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fas fa-home'],
            ['title' => 'Request Vacation', 'url' => 'request_vacation.php', 'icon' => 'fas fa-calendar-plus'],
            ['title' => 'My Requests', 'url' => 'my_requests.php', 'icon' => 'fas fa-list'],
            ['title' => 'Approve Requests', 'url' => 'approve_request.php', 'icon' => 'fas fa-check-circle']
        ],
        'admin' => [
            ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fas fa-home'],
            ['title' => 'Manage Employees', 'url' => 'manage_employees.php', 'icon' => 'fas fa-users'],
            ['title' => 'Manage Users', 'url' => 'manage_users.php', 'icon' => 'fas fa-user-cog'],
            ['title' => 'Roles & Permissions', 'url' => 'manage_roles_permissions.php', 'icon' => 'fas fa-user-shield']
        ]
    ];
    
    return isset($menu[$role]) ? $menu[$role] : [];
}

/**
 * Sanitize Input
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if User is Logged In
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check User Role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Format Date
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

/**
 * Calculate Vacation Days
 * @param string $start_date
 * @param string $end_date
 * @return int
 */
function calculateVacationDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1;
}