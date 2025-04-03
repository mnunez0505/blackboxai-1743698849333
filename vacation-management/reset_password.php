<?php
require_once 'config.php';
require_once 'functions.php';

// Check for token
if (empty($_GET['token'])) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'];
$token_valid = false;
$error_message = '';

try {
    $pdo = connectDB();
    
    // Verify token and check expiration
    $stmt = $pdo->prepare("
        SELECT id, username 
        FROM USERS 
        WHERE reset_token = :token 
        AND reset_token_expiry > CURRENT_TIMESTAMP
    ");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token_valid = true;
    } else {
        $error_message = 'Invalid or expired reset token. Please request a new password reset.';
    }
} catch (PDOException $e) {
    error_log("Database Error in reset_password.php: " . $e->getMessage());
    $error_message = 'An error occurred. Please try again later.';
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Reset Password</title>
    
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
        
        .reset-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .reset-header h1 {
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
        
        .password-strength {
            font-size: 0.85rem;
            margin-top: -0.5rem;
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Reset Password</h1>
            <p class="text-muted">Enter your new password</p>
        </div>
        
        <?php if (!$token_valid): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
            </div>
            <div class="text-center">
                <a href="forgot_password.php" class="btn btn-reset">
                    <i class="fas fa-redo me-2"></i>Request New Reset Link
                </a>
            </div>
        <?php else: ?>
            <!-- Alert for errors/messages -->
            <div id="alertMessage" class="alert" style="display: none;"></div>
            
            <form id="resetPasswordForm" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="New Password" required>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                
                <button type="submit" class="btn btn-reset">
                    <i class="fas fa-key me-2"></i>Set New Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
            </a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/custom.js"></script>
    
    <?php if ($token_valid): ?>
    <script>
    $(document).ready(function() {
        // Password strength indicator
        $('#password').on('input', function() {
            const password = $(this).val();
            const strength = checkPasswordStrength(password);
            const strengthDiv = $('#passwordStrength');
            
            strengthDiv.removeClass('text-danger text-warning text-success');
            
            if (password.length === 0) {
                strengthDiv.html('').hide();
            } else if (password.length < 8) {
                strengthDiv.html('<i class="fas fa-times-circle"></i> Password too short').addClass('text-danger').show();
            } else if (!isValidPassword(password)) {
                strengthDiv.html('<i class="fas fa-exclamation-circle"></i> Password must contain uppercase, lowercase, and numbers').addClass('text-warning').show();
            } else {
                strengthDiv.html('<i class="fas fa-check-circle"></i> Password strength: Good').addClass('text-success').show();
            }
        });
        
        // Form submission
        $('#resetPasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (!isValidPassword(password)) {
                showAlert('Password must be at least 8 characters long and contain uppercase, lowercase, and numbers');
                return;
            }
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match');
                return;
            }
            
            // Disable submit button and show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Updating...').prop('disabled', true);
            
            $.ajax({
                url: 'ajax/reset_password.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    } else {
                        showAlert(response.message || 'Password reset failed. Please try again.');
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                },
                error: function() {
                    showAlert('An error occurred. Please try again later.');
                    submitBtn.html(originalBtnText).prop('disabled', false);
                }
            });
        });
        
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            return strength;
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>