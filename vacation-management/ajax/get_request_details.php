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

    // Validate request_id
    if (empty($_POST['request_id'])) {
        throw new Exception('Request ID is required');
    }

    $requestId = (int)$_POST['request_id'];
    $supervisorId = $_SESSION['user_id'];

    try {
        $pdo = connectDB();

        // Get request details with employee information
        $stmt = $pdo->prepare("
            SELECT 
                vr.*,
                e.fullname as employee_name,
                e.email as employee_email,
                e.phone as employee_phone,
                e.date_hire,
                e.vacation_days as available_days,
                e.supervisor_id,
                CASE 
                    WHEN vr.status = 'pending' THEN 'badge bg-warning text-dark'
                    WHEN vr.status = 'approved' THEN 'badge bg-success'
                    ELSE 'badge bg-danger'
                END as status_class
            FROM VACATION_REQUESTS vr
            JOIN USERS e ON vr.employee_id = e.id
            WHERE vr.id = :request_id
        ");
        $stmt->execute(['request_id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception('Request not found');
        }

        if ($request['supervisor_id'] !== $supervisorId) {
            throw new Exception('You are not authorized to view this request');
        }

        // Calculate employment duration
        $hireDate = new DateTime($request['date_hire']);
        $now = new DateTime();
        $employmentDuration = $hireDate->diff($now);

        // Format duration string
        $durationStr = '';
        if ($employmentDuration->y > 0) {
            $durationStr .= $employmentDuration->y . ' year(s) ';
        }
        if ($employmentDuration->m > 0) {
            $durationStr .= $employmentDuration->m . ' month(s)';
        }
        if (empty($durationStr)) {
            $durationStr = 'Less than a month';
        }

        // Build HTML for request details
        $html = "
            <div class='mb-4'>
                <h6 class='text-muted mb-3'>Employee Information</h6>
                <div class='row g-3'>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Name:</strong></p>
                        <p>{$request['employee_name']}</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Email:</strong></p>
                        <p>{$request['employee_email']}</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Phone:</strong></p>
                        <p>{$request['employee_phone']}</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Employment Duration:</strong></p>
                        <p>{$durationStr}</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Available Days:</strong></p>
                        <p>{$request['available_days']} days</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Status:</strong></p>
                        <p><span class='{$request['status_class']}'>" . ucfirst($request['status']) . "</span></p>
                    </div>
                </div>
            </div>

            <div class='mb-4'>
                <h6 class='text-muted mb-3'>Request Details</h6>
                <div class='row g-3'>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Start Date:</strong></p>
                        <p>" . date('M d, Y', strtotime($request['start_date'])) . "</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>End Date:</strong></p>
                        <p>" . date('M d, Y', strtotime($request['end_date'])) . "</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Days Requested:</strong></p>
                        <p>{$request['days_requested']} days</p>
                    </div>
                    <div class='col-sm-6'>
                        <p class='mb-1'><strong>Request Date:</strong></p>
                        <p>" . date('M d, Y', strtotime($request['request_date'])) . "</p>
                    </div>
                    <div class='col-12'>
                        <p class='mb-1'><strong>Reason:</strong></p>
                        <p>{$request['reason']}</p>
                    </div>
                </div>
            </div>";

        // Add supervisor comments if request has been processed
        if ($request['status'] !== 'pending' && !empty($request['supervisor_comment'])) {
            $html .= "
                <div class='mb-4'>
                    <h6 class='text-muted mb-3'>Supervisor Comments</h6>
                    <p>{$request['supervisor_comment']}</p>
                </div>";
        }

        // Return success response with HTML
        echo json_encode([
            'status' => 'success',
            'html' => $html
        ]);

    } catch (PDOException $e) {
        error_log("Database Error in get_request_details.php: " . $e->getMessage());
        throw new Exception('An error occurred while fetching request details');
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}