<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get user's information and available vacation days
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT u.*, s.fullname as supervisor_name
        FROM USERS u
        LEFT JOIN USERS s ON u.supervisor_id = s.id
        WHERE u.id = :user_id
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching user info: " . $e->getMessage());
    $error = 'An error occurred while loading your information';
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Request Vacation</title>
    
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
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .vacation-info {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .vacation-days {
            font-size: 2rem;
            font-weight: 600;
            color: #667eea;
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
        
        <!-- Vacation Info -->
        <div class="vacation-info">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-3">Available Vacation Days</h5>
                    <div class="vacation-days mb-2"><?php echo $userInfo['vacation_days']; ?> days</div>
                    <p class="text-muted mb-0">
                        Supervisor: <?php echo htmlspecialchars($userInfo['supervisor_name']); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">
                        <i class="fas fa-calendar me-2"></i>
                        Hire Date: <?php echo date('M d, Y', strtotime($userInfo['date_hire'])); ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Next vacation credit in: 
                        <?php
                        $hireDate = new DateTime($userInfo['date_hire']);
                        $nextCredit = $hireDate->modify('+1 year');
                        $now = new DateTime();
                        $interval = $now->diff($nextCredit);
                        echo $interval->format('%a days');
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Request Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Submit Vacation Request</h5>
            </div>
            <div class="card-body">
                <!-- Alert for messages -->
                <div id="alertMessage" class="alert" style="display: none;"></div>
                
                <form id="vacationRequestForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Vacation</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Days Requested: <span id="daysRequested">0</span></label>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" onclick="window.history.back();">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Request
                        </button>
                    </div>
                </form>
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
        const availableDays = <?php echo $userInfo['vacation_days']; ?>;
        
        // Initialize date range picker
        initializeDateRangePicker('start_date', 'end_date', function(startDate, endDate) {
            updateDaysRequested(startDate, endDate);
        });
        
        function updateDaysRequested(startDate, endDate) {
            const days = calculateDays(startDate, endDate);
            $('#daysRequested').text(days);
            
            const percentage = (days / availableDays) * 100;
            $('.progress-bar')
                .css('width', Math.min(percentage, 100) + '%')
                .removeClass('bg-success bg-warning bg-danger')
                .addClass(percentage > 100 ? 'bg-danger' : percentage > 75 ? 'bg-warning' : 'bg-success');
        }
        
        // Form submission
        $('#vacationRequestForm').on('submit', function(e) {
            e.preventDefault();
            
            const startDate = new Date($('#start_date').val());
            const endDate = new Date($('#end_date').val());
            const days = calculateDays(startDate, endDate);
            
            if (days > availableDays) {
                showAlert('You do not have enough vacation days available');
                return;
            }
            
            // Disable submit button and show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Submitting...').prop('disabled', true);
            
            $.ajax({
                url: 'ajax/request_vacation.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 2000);
                    } else {
                        showAlert(response.message);
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                },
                error: function() {
                    showAlert('An error occurred. Please try again later.');
                    submitBtn.html(originalBtnText).prop('disabled', false);
                }
            });
        });
        
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