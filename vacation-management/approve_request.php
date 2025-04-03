<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is a supervisor
if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: index.php');
    exit;
}

// Get pending requests for this supervisor
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT 
            vr.*,
            e.fullname as employee_name,
            e.email as employee_email,
            e.vacation_days as available_days
        FROM VACATION_REQUESTS vr
        JOIN USERS e ON vr.employee_id = e.id
        WHERE e.supervisor_id = :supervisor_id
        ORDER BY 
            CASE 
                WHEN vr.status = 'pending' THEN 1
                ELSE 2
            END,
            vr.request_date DESC
    ");
    $stmt->execute(['supervisor_id' => $_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching requests: " . $e->getMessage());
    $error = 'An error occurred while loading the requests';
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Approve Requests</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1rem;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: block;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 1.25rem;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-action {
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0"><?php echo APP_NAME; ?></h4>
        </div>
        
        <ul class="sidebar-menu">
            <?php foreach (getMenuByRole($_SESSION['user_role']) as $item): ?>
            <li>
                <a href="<?php echo $item['url']; ?>" <?php echo basename($_SERVER['PHP_SELF']) === $item['url'] ? 'class="active"' : ''; ?>>
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <?php echo $item['title']; ?>
                </a>
            </li>
            <?php endforeach; ?>
            
            <li>
                <a href="#" id="logoutLink">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <button class="btn btn-primary toggle-sidebar d-md-none mb-3" style="display: none;">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Alert for messages -->
        <div id="alertMessage" class="alert" style="display: none;"></div>
        
        <!-- Requests Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Vacation Requests</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterRequests('all')">
                        All
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterRequests('pending')">
                        Pending
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="filterRequests('approved')">
                        Approved
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="filterRequests('rejected')">
                        Rejected
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                <p class="text-center text-muted my-5">No vacation requests found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr class="request-row" data-status="<?php echo $request['status']; ?>">
                                <td><?php echo htmlspecialchars($request['employee_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['end_date'])); ?></td>
                                <td><?php echo $request['days_requested']; ?></td>
                                <td><?php echo $request['available_days']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-action view-request" 
                                            data-id="<?php echo $request['id']; ?>"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success btn-action approve-request" 
                                            data-id="<?php echo $request['id']; ?>"
                                            title="Approve Request">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-action reject-request" 
                                            data-id="<?php echo $request['id']; ?>"
                                            title="Reject Request">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Request Modal -->
    <div class="modal fade" id="viewRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="requestDetails"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="actionForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="request_id" id="requestId">
                        <input type="hidden" name="action" id="actionType">
                        
                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea class="form-control" name="comments" rows="3" required></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/custom.js"></script>
    
    <script>
    $(document).ready(function() {
        // View request details
        $('.view-request').on('click', function() {
            const requestId = $(this).data('id');
            
            $.ajax({
                url: 'ajax/get_request_details.php',
                type: 'POST',
                data: {
                    request_id: requestId,
                    csrf_token: '<?php echo $csrf_token; ?>'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#requestDetails').html(response.html);
                        new bootstrap.Modal('#viewRequestModal').show();
                    } else {
                        showAlert(response.message);
                    }
                }
            });
        });
        
        // Approve request
        $('.approve-request').on('click', function() {
            $('#requestId').val($(this).data('id'));
            $('#actionType').val('approve');
            $('.modal-title').text('Approve Request');
            new bootstrap.Modal('#actionModal').show();
        });
        
        // Reject request
        $('.reject-request').on('click', function() {
            $('#requestId').val($(this).data('id'));
            $('#actionType').val('reject');
            $('.modal-title').text('Reject Request');
            new bootstrap.Modal('#actionModal').show();
        });
        
        // Handle action form submission
        $('#actionForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...').prop('disabled', true);
            
            $.ajax({
                url: 'ajax/approve_request.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert(response.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert(response.message);
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                    bootstrap.Modal.getInstance('#actionModal').hide();
                }
            });
        });
        
        // Filter requests
        window.filterRequests = function(status) {
            $('.request-row').show();
            if (status !== 'all') {
                $('.request-row').not(`[data-status="${status}"]`).hide();
            }
        };
        
        // Toggle sidebar on mobile
        $('.toggle-sidebar').on('click', function() {
            $('.sidebar').toggleClass('active');
        });
        
        // Logout functionality
        $('#logoutLink').on('click', function(e) {
            e.preventDefault();
            confirmAction('Are you sure you want to logout?', function() {
                window.location.href = 'logout.php';
            });
        });
    });
    </script>
</body>
</html>