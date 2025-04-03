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
    <title><?php echo APP_NAME; ?> - Register</title>
    
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
            padding: 2rem 0;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            margin: 1rem;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
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
        
        .btn-register {
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
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .register-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .register-footer a:hover {
            color: #764ba2;
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
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p class="text-muted">Join our vacation management system</p>
        </div>
        
        <!-- Alert for errors/messages -->
        <div id="alertMessage" class="alert" style="display: none;"></div>
        
        <form id="registerForm" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
                <input type="text" class="form-control" id="fullname" name="fullname" 
                       placeholder="Full Name" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                </span>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Email Address" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-phone"></i>
                </span>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       placeholder="Phone Number" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-user-circle"></i>
                </span>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Username" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Password" required>
            </div>
            <div class="password-strength" id="passwordStrength"></div>
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="Confirm Password" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-calendar"></i>
                </span>
                <input type="date" class="form-control" id="date_hire" name="date_hire" 
                       placeholder="Date of Hire" required>
            </div>
            
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus me-2"></i> Create Account
            </button>
        </form>
        
        <div class="register-footer">
            <p class="mb-0">
                Already have an account? <a href="index.php">Login</a>
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
        // Set max date for date_hire to today
        const today = new Date();
        $('#date_hire').attr('max', formatDate(today));
        
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
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate inputs
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const email = $('#email').val();
            
            if (!isValidEmail(email)) {
                showAlert('Please enter a valid email address');
                return;
            }
            
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
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Creating Account...').prop('disabled', true);
            
            // Submit form via Ajax
            $.ajax({
                url: 'ajax/register.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert('Registration successful! Redirecting to login...', 'success');
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    } else {
                        showAlert(response.message || 'Registration failed. Please try again.');
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
</body>
</html>