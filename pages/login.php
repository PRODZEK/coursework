<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in? Redirect to dashboard
if (Auth::isLoggedIn()) {
    redirect('/');
}

$error = '';
$identifier = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Check for rate limiting
    $rateLimitCheck = Auth::isRateLimited($identifier);
    if ($rateLimitCheck) {
        $error = $rateLimitCheck['message'];
    } else {
        // Attempt login
        $result = Auth::login($identifier, $password, $remember);
        
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Successful login, redirect
            redirect('/');
        }
    }
}

// Get flash message if any
$flashMessage = SessionManager::getFlash('message');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= config('app.name') ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <img src="/assets/img/logo.svg" alt="<?= config('app.name') ?>">
                </div>
                <h1>Welcome back</h1>
                <p>Sign in to continue to <?= config('app.name') ?></p>
            </div>
            
            <?php if ($flashMessage): ?>
            <div class="flash-message">
                <?= e($flashMessage) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
            </div>
            <?php endif; ?>
            
            <form action="/login" method="post" class="auth-form" id="login-form">
                <div class="form-group">
                    <label for="identifier">Username or Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input 
                            type="text" 
                            id="identifier" 
                            name="identifier" 
                            value="<?= e($identifier) ?>" 
                            placeholder="Enter your username or email"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="/forgot-password" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Sign in
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="/register">Create an account</a></p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html> 