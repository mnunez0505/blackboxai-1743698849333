<?php
require_once '../config.php';
require_once '../functions.php';

// Set header to return JSON response
header('Content-Type: application/json');

try {
    // Check if user is logged in and is a supervisor
    if (!isLoggedIn() || !hasRole('supervisor')) {
        throw new Exception('Unauthorized access');
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    // Validate required fields
    if (empty($_POST['request_id']) || empty($_POST['action']) || empty($_POST['comments'])) {
        throw new Exception('All fields are required');
    }

    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $comments = sanitizeInput($_POST['comments']);
    $supervisorId = $_SESSION['user_id'];

    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }

    try {
        $pdo = connectDB();
        
        // Begin transaction
        $pdo->beginTransaction();

        // Get request details and verify supervisor
        $stmt = $pdo->prepare("
            SELECT vr.*, 
                   e.fullname as employee_name,
                   e.email as employee_email,
                   e.vacation_days as available_days,
                   e.supervisor_id
            FROM VACATION_REQUESTS vr
            JOIN USERS e ON vr.employee_id = e.id
            WHERE vr.id = :request_id
            AND vr.status = 'pending'
        ");
        $stmt->execute(['request_id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception('Request not found or already processed');
        }

        if ($request['supervisor_id'] !== $supervisorId) {
            throw new Exception('You are not authorized to process this request');
        }

        // Update request status
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $stmt = $pdo->prepare("
            UPDATE VACATION_REQUESTS 
            SET status = :status,
                supervisor_comment = :comments,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :request_id
        ");

        $stmt->execute([
            'status' => $status,
            'comments' => $comments,
            'request_id' => $requestId
        ]);

        // If approved, update employee's vacation days
        if ($action === 'approve') {
            $newVacationDays = $request['available_days'] - $request['days_requested'];
            
            if ($newVacationDays < 0) {
                throw new Exception('Employee does not have enough vacation days');
            }

            $stmt = $pdo->prepare("
                UPDATE USERS 
                SET vacation_days = :vacation_days
                WHERE id = :employee_id
            ");

            $stmt->execute([
                'vacation_days' => $newVacationDays,
                'employee_id' => $request['employee_id']
            ]);
        }

        // Commit transaction
        $pdo->commit();

        // Send email notification
        $emailSubject = "Vacation Request " . ucfirst($status) . " - " . APP_NAME;
        $emailMessage = "
            <h2>Vacation Request {$status}</h2>
            <p>Dear {$request['employee_name']},</p>
            <p>Your vacation request has been <strong>{$status}</strong>.</p>
            <p><strong>Details:</strong></p>
            <ul>
                <li>Start Date: " . date('M d, Y', strtotime($request['start_date'])) . "</li>
                <li>End Date: " . date('M d, Y', strtotime($request['end_date'])) . "</li>
                <li>Days Requested: {$request['days_requested']}</li>
                <li>Supervisor Comments: {$comments}</li>
            </ul>
            " . ($action === 'approve' ? 
                "<p>Your remaining vacation days: {$newVacationDays}</p>" : 
                "<p>Your vacation days remain unchanged: {$request['available_days']}</p>") . "
            <p>Best regards,<br>" . APP_NAME . " Team</p>
        ";

        sendEmailNotification($request['employee_email'], $emailSubject, $emailMessage);

        // Send WhatsApp notification if configured
        if (defined('WHATSAPP_API_URL') && !empty($request['employee_phone'])) {
            $whatsappMessage = "Your vacation request has been {$status}\n";
            $whatsappMessage .= "Start: " . date('M d, Y', strtotime($request['start_date'])) . "\n";
            $whatsappMessage .= "End: " . date('M d, Y', strtotime($request['end_date'])) . "\n";
            $whatsappMessage .= "Supervisor Comments: {$comments}";

            sendWhatsappNotification($request['employee_phone'], $whatsappMessage);
        }

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => "Request successfully {$status}. Employee will be notified."
        ]);

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database Error in approve_request.php: " . $e->getMessage());
        throw new Exception('An error occurred while processing the request');
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}