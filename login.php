<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    logUserAction('auto_redirect', 'Already logged in user redirected to dashboard');
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Log login attempt
        logSystemEvent('login_attempt', "Login attempt for email: $email");

        // Validate required fields
        $validationErrors = validateRequiredFields(['email', 'password'], $_POST);
        
        if (!empty($validationErrors)) {
            throw new Exception(implode(' ', $validationErrors));
        }

        // Use the global $pdo instance
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            
            // Log successful login
            logUserAction('login_success', "User logged in successfully: {$user['email']}");
            
            header('Location: dashboard.php');
            exit();
        } else {
            // Log failed login attempt
            logSecurityEvent('login_failed', "Failed login attempt for email: $email");
            throw new Exception("Invalid email or password");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mbogafasta CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 800px;
            width: 100%;
            margin: 20px;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .logo-container {
            flex: 1;
            text-align: center;
            padding: 20px;
            border-right: 1px solid #eee;
        }
        
        .logo-container img {
            max-width: 100%;
            height: auto;
            max-height: 200px;
        }
        
        .form-container {
            flex: 1;
            padding: 0 20px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                gap: 20px;
                max-width: 380px;
            }
            
            .logo-container {
                border-right: none;
                border-bottom: 1px solid #eee;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            
            .form-container {
                padding: 0;
            }
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .logo img {
            max-width: 150px;
            height: auto;
        }
        
        .form-control {
            border-radius: 6px;
            padding: 10px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        p.text-muted {
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .alert {
            padding: 10px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .input-group-text {
            padding: 10px;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-left: 1px solid var(--secondary-color);
        }
        
        .forgot-password {
            margin-top: 8px;
        }
        
        .forgot-password a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.85rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .d-grid {
            margin-top: 20px;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #666;
            z-index: 2;
        }
        
        .password-toggle:hover {
            color: var(--secondary-color);
        }
        
        .password-input-container {
            position: relative;
        }
        
        .password-input-container .form-control {
            padding-right: 40px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="Mbogafasta Logo">
        </div>
        
        <div class="form-container">
            <h2 class="text-center mb-4" style="color: var(--primary-color);">Welcome Back</h2>
            <p class="text-center text-muted mb-4">Please login to access your dashboard</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope text-muted"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group password-input-container">
                        <span class="input-group-text">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 