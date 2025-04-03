<?php
require_once 'config.php';
require_once 'functions.php';

// Get error code from URL
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 404;

// Define error messages
$errorMessages = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'The request could not be understood by the server due to malformed syntax.',
        'icon' => 'exclamation-triangle'
    ],
    401 => [
        'title' => 'Unauthorized',
        'message' => 'Authentication is required and has failed or has not yet been provided.',
        'icon' => 'lock'
    ],
    403 => [
        'title' => 'Forbidden',
        'message' => 'You do not have permission to access this resource.',
        'icon' => 'ban'
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'The requested page could not be found on this server.',
        'icon' => 'search'
    ],
    500 => [
        'title' => 'Internal Server Error',
        'message' => 'The server encountered an internal error and was unable to complete your request.',
        'icon' => 'exclamation-circle'
    ]
];

// Get error details
$error = $errorMessages[$errorCode] ?? $errorMessages[404];

// Log error if it's server-side (5xx)
if ($errorCode >= 500) {
    error_log("Server Error {$errorCode}: {$_SERVER['REQUEST_URI']}");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $error['title']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #764ba2;
            margin-bottom: 1.5rem;
        }
        
        .error-code {
            font-size: 3.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .error-title {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            color: #777;
            margin-bottom: 2rem;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .additional-links {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .additional-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        
        .additional-links a:hover {
            color: #764ba2;
        }
        
        @media (max-width: 576px) {
            .error-container {
                padding: 2rem;
            }
            
            .error-code {
                font-size: 3rem;
            }
            
            .error-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-<?php echo $error['icon']; ?>"></i>
        </div>
        
        <div class="error-code"><?php echo $errorCode; ?></div>
        <div class="error-title"><?php echo $error['title']; ?></div>
        <div class="error-message"><?php echo $error['message']; ?></div>
        
        <a href="index.php" class="btn-home">
            <i class="fas fa-home me-2"></i>Back to Home
        </a>
        
        <div class="additional-links">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="index.php">Login</a>
            <?php endif; ?>
            
            <?php if ($errorCode === 404): ?>
                <a href="javascript:history.back()">Go Back</a>
            <?php endif; ?>
            
            <a href="mailto:support@yourdomain.com">Contact Support</a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>