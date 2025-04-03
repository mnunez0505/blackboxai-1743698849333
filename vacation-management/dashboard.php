<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get user's role and menu items
$userRole = $_SESSION['user_role'];
$menuItems = getMenuByRole($userRole);

// Get user's information
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT u.*, 
               s.fullname as supervisor_name,
               (SELECT COUNT(*) FROM VACATION_REQUESTS 
                WHERE employee_id = u.id AND status = 'pending') as pending_requests
        FROM USERS u
        LEFT JOIN USERS s ON u.supervisor_id = s.id
        WHERE u.id = :user_id
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent vacation requests
    $stmt = $pdo->prepare("
        SELECT vr.*, 
               u.fullname as employee_name
        FROM VACATION_REQUESTS vr
        JOIN USERS u ON vr.employee_id = u.id
        WHERE " . ($userRole === 'supervisor' ? 
                  "u.supervisor_id = :user_id" : 
                  "vr.employee_id = :user_id") . "
        ORDER BY vr.request_date DESC
        LIMIT 5
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = 'An error occurred while loading the dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    
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
        
        .sidebar-menu i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 1.25rem;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-info h3 {
            font-size: 1.8rem;
            margin: 0;
            font-weight: 600;
        }
        
        .stats-info p {
            color: #6c757d;
            margin: 0;
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
        
        .user-info {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .user-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
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
            <?php foreach ($menuItems as $item): ?>
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
        
        <!-- User Info -->
        <div class="user-info">
            <div class="row align-items-center">
                <div class="col-auto">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userInfo['fullname']); ?>&background=random" 
                         alt="Profile Picture">
                </div>
                <div class="col">
                    <h4 class="mb-1"><?php echo htmlspecialchars($userInfo['fullname']); ?></h4>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user-tag me-2"></i><?php echo ucfirst($userRole); ?>
                    </p>
                    <?php if ($userInfo['supervisor_name']): ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user-tie me-2"></i>Supervisor: <?php echo htmlspecialchars($userInfo['supervisor_name']); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-primary text-white">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $userInfo['vacation_days']; ?></h3>
                        <p>Available Vacation Days</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-warning text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $userInfo['pending_requests']; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon bg-success text-white">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo count($recentRequests); ?></h3>
                        <p>Recent Requests</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Requests -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Vacation Requests</h5>
                <a href="request_vacation.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-2"></i>New Request
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentRequests)): ?>
                <p class="text-muted text-center mb-0">No recent requests found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['employee_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['end_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info view-request" 
                                            data-id="<?php echo $request['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($userRole === 'supervisor' && $request['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success approve-request" 
                                            data-id="<?php echo $request['id']; ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger reject-request" 
                                            data-id="<?php echo $request['id']; ?>">
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/custom.js"></script>
    
    <script>
    $(document).ready(function() {
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
        
        // View request details
        $('.view-request').on('click', function() {
            const requestId = $(this).data('id');
            // Implement view request modal or redirect to details page
        });
        
        // Approve request
        $('.approve-request').on('click', function() {
            const requestId = $(this).data('id');
            confirmAction('Are you sure you want to approve this request?', function() {
                $.ajax({
                    url: 'ajax/approve_request.php',
                    type: 'POST',
                    data: {
                        request_id: requestId,
                        action: 'approve',
                        csrf_token: '<?php echo $csrf_token; ?>'
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert(response.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert(response.message);
                        }
                    }
                });
            });
        });
        
        // Reject request
        $('.reject-request').on('click', function() {
            const requestId = $(this).data('id');
            confirmAction('Are you sure you want to reject this request?', function() {
                $.ajax({
                    url: 'ajax/approve_request.php',
                    type: 'POST',
                    data: {
                        request_id: requestId,
                        action: 'reject',
                        csrf_token: '<?php echo $csrf_token; ?>'
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert(response.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert(response.message);
                        }
                    }
                });
            });
        });
    });
    </script>
</body>
</html>