<?php
/**
 * Campus Canteen Management System - Login Page
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check user by email or username
        $user = fetchOne("SELECT * FROM users WHERE email = ? OR full_name = ?", [$username, $username], "ss");

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Log activity
            logActivity($user['user_id'], 'Login', 'User logged in successfully');

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            header("Location: pages/dashboard.php");
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-left">
                <div class="login-logo">
                    <i class="fas fa-utensils"></i>
                </div>
                <h1>CCMS</h1>
                <p>Campus Canteen Management System<br>A Smart Solution for Modern Canteen Operations</p>
                
                <div class="login-features">
                    <div class="login-feature">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Digital Ordering</span>
                    </div>
                    <div class="login-feature">
                        <i class="fas fa-boxes"></i>
                        <span>Real-time Inventory</span>
                    </div>
                    <div class="login-feature">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics & Reporting</span>
                    </div>
                    <div class="login-feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure & Reliable</span>
                    </div>
                </div>
            </div>
            
            <div class="login-right">
                <h2>Welcome Back!</h2>
                <p class="subtitle">Sign in to your account to continue</p>
                
                <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <?php 
                $flash = getFlash();
                if ($flash && $flash['type'] === 'error'): 
                ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $flash['message']; ?></span>
                </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username or Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Enter username or email" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                            <span class="password-toggle"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-btn">
                        Sign In
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="login-divider">Demo Credentials</div>
                
                <div class="login-hint">
                    <p><strong>Admin:</strong> <code>admin</code> / <code>admin</code></p>
                    <p style="margin-top: 4px;"><strong>User:</strong> <code>user</code> / <code>user</code></p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
