<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Forgot Password</title>
    
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .forgot-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .forgot-header h1 {
            font-size: 1.8rem;
            color: #333;
            font-weight: 600;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            margin-bottom: 1rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 500;
            width: 100%;
            color: white;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .forgot-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .forgot-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-footer a:hover {
            color: #764ba2;
        }
        
        .input-group {
            margin-bottom: 1rem;
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
            margin-bottom: 0;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .instructions {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>Reset Password</h1>
            <p class="text-muted">Enter your email to reset your password</p>
        </div>
        
        <div class="instructions">
            <i class="fas fa-info-circle me-2"></i>
            We'll send you an email with instructions to reset your password.
        </div>
        
        <!-- Alert for errors/messages -->
        <div id="alertMessage" class="alert" style="display: none;"></div>
        
        <form id="forgotPasswordForm" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                </span>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Enter your email address" required>
            </div>
            
            <button type="submit" class="btn btn-reset">
                <i class="fas fa-paper-plane me-2"></i> Send Reset Link
            </button>
        </form>
        
        <div class="forgot-footer">
            <p class="mb-0">
                <a href="index.php"><i class="fas fa-arrow-left me-2"></i>Back to Login</a>
            </p>
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
        $('#forgotPasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#email').val().trim();
            
            if (!isValidEmail(email)) {
                showAlert('Please enter a valid email address');
                return;
            }
            
            // Disable submit button and show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Sending...').prop('disabled', true);
            
            $.ajax({
                url: 'ajax/forgot_password.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert(response.message, 'success');
                        $('#forgotPasswordForm')[0].reset();
                    } else {
                        showAlert(response.message || 'An error occurred. Please try again.');
                    }
                    submitBtn.html(originalBtnText).prop('disabled', false);
                },
                error: function() {
                    showAlert('An error occurred. Please try again later.');
                    submitBtn.html(originalBtnText).prop('disabled', false);
                }
            });
        });
    });
    </script>
</body>
</html>