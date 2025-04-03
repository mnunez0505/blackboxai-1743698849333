<?php
require_once '../config.php';
require_once '../functions.php';

// Set header to return JSON response
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('You must be logged in to request vacation');
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
    if (empty($_POST['start_date']) || empty($_POST['end_date']) || empty($_POST['reason'])) {
        throw new Exception('All fields are required');
    }

    // Sanitize and validate inputs
    $startDate = date('Y-m-d', strtotime($_POST['start_date']));
    $endDate = date('Y-m-d', strtotime($_POST['end_date']));
    $reason = sanitizeInput($_POST['reason']);
    $employeeId = $_SESSION['user_id'];

    // Validate dates
    $today = date('Y-m-d');
    if ($startDate < $today) {
        throw new Exception('Start date cannot be in the past');
    }
    if ($endDate < $startDate) {
        throw new Exception('End date must be after start date');
    }

    try {
        $pdo = connectDB();
        
        // Begin transaction
        $pdo->beginTransaction();

        // Get employee's available vacation days and supervisor
        $stmt = $pdo->prepare("
            SELECT u.vacation_days, u.supervisor_id, u.fullname,
                   s.email as supervisor_email, s.fullname as supervisor_name
            FROM USERS u
            LEFT JOIN USERS s ON u.supervisor_id = s.id
            WHERE u.id = :employee_id
        ");
        $stmt->execute(['employee_id' => $employeeId]);
        $employeeInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employeeInfo['supervisor_id']) {
            throw new Exception('No supervisor assigned. Please contact HR.');
        }

        // Calculate requested days
        $requestedDays = calculateVacationDays($startDate, $endDate);

        // Check if employee has enough vacation days
        if ($requestedDays > $employeeInfo['vacation_days']) {
            throw new Exception('Insufficient vacation days available');
        }

        // Check for overlapping requests
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM VACATION_REQUESTS 
            WHERE employee_id = :employee_id 
            AND status = 'pending'
            AND (
                (start_date BETWEEN :start_date AND :end_date)
                OR (end_date BETWEEN :start_date AND :end_date)
                OR (:start_date BETWEEN start_date AND end_date)
            )
        ");
        $stmt->execute([
            'employee_id' => $employeeId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('You already have a pending request for these dates');
        }

        // Insert vacation request
        $stmt = $pdo->prepare("
            INSERT INTO VACATION_REQUESTS (
                employee_id,
                start_date,
                end_date,
                days_requested,
                reason,
                status,
                request_date
            ) VALUES (
                :employee_id,
                TO_DATE(:start_date, 'YYYY-MM-DD'),
                TO_DATE(:end_date, 'YYYY-MM-DD'),
                :days_requested,
                :reason,
                'pending',
                CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            'employee_id' => $employeeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $requestedDays,
            'reason' => $reason
        ]);

        // Commit transaction
        $pdo->commit();

        // Send email notification to supervisor
        $emailSubject = "New Vacation Request - " . APP_NAME;
        $emailMessage = "
            <h2>New Vacation Request</h2>
            <p>Dear {$employeeInfo['supervisor_name']},</p>
            <p>A new vacation request has been submitted by {$employeeInfo['fullname']}.</p>
            <p><strong>Details:</strong></p>
            <ul>
                <li>Start Date: " . date('M d, Y', strtotime($startDate)) . "</li>
                <li>End Date: " . date('M d, Y', strtotime($endDate)) . "</li>
                <li>Days Requested: {$requestedDays}</li>
                <li>Reason: {$reason}</li>
            </ul>
            <p>Please login to the system to approve or reject this request.</p>
            <p>Best regards,<br>" . APP_NAME . " Team</p>
        ";

        sendEmailNotification($employeeInfo['supervisor_email'], $emailSubject, $emailMessage);

        // Send WhatsApp notification if configured
        if (defined('WHATSAPP_API_URL') && !empty($employeeInfo['supervisor_phone'])) {
            $whatsappMessage = "New vacation request from {$employeeInfo['fullname']}\n";
            $whatsappMessage .= "Start: " . date('M d, Y', strtotime($startDate)) . "\n";
            $whatsappMessage .= "End: " . date('M d, Y', strtotime($endDate)) . "\n";
            $whatsappMessage .= "Days: {$requestedDays}";

            sendWhatsappNotification($employeeInfo['supervisor_phone'], $whatsappMessage);
        }

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Vacation request submitted successfully. Your supervisor will be notified.'
        ]);

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database Error in request_vacation.php: " . $e->getMessage());
        throw new Exception('An error occurred while processing your request');
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}