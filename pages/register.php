<?php
/**
 * Registration Page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in? Redirect to dashboard
if (Auth::isLoggedIn()) {
    redirect('/');
}

$error = '';
$values = [
    'username' => '',
    'email' => '',
    'first_name' => '',
    'last_name' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
    ];
    
    // Save values for form repopulation
    $values = [
        'username' => $userData['username'],
        'email' => $userData['email'],
        'first_name' => $userData['first_name'],
        'last_name' => $userData['last_name']
    ];
    
    // Validate password match
    if ($userData['password'] !== $userData['password_confirm']) {
        $error = 'Passwords do not match';
    } else {
        // Attempt registration
        $result = Auth::register($userData);
        
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Successful registration, set flash message and redirect to login
            SessionManager::setFlash('message', 'Registration successful! You can now log in.');
            redirect('/login');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= config('app.name') ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card register-card">
            <div class="auth-header">
                <div class="logo">
                    <img src="/assets/img/logo.svg" alt="<?= config('app.name') ?>">
                </div>
                <h1>Create an account</h1>
                <p>Join <?= config('app.name') ?> and start messaging</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
            </div>
            <?php endif; ?>
            
            <form action="/register" method="post" class="auth-form" id="register-form">
                <div class="name-fields">
                    <div class="form-group half">
                        <label for="first_name">First Name</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?= e($values['first_name']) ?>" 
                                placeholder="First name"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group half">
                        <label for="last_name">Last Name</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?= e($values['last_name']) ?>" 
                                placeholder="Last name"
                                required
                            >
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-at"></i></span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?= e($values['username']) ?>" 
                            placeholder="Choose a username"
                            pattern="[a-zA-Z0-9_]{4,20}"
                            title="Username must be 4-20 characters and contain only letters, numbers, and underscores"
                            required
                        >
                    </div>
                    <small class="form-text">4-20 characters, letters, numbers and underscores only</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?= e($values['email']) ?>" 
                            placeholder="Enter your email address"
                            required
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
                            placeholder="Create a password"
                            minlength="<?= config('security.password_min_length', 8) ?>"
                            required
                        >
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text">Minimum <?= config('security.password_min_length', 8) ?> characters</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            placeholder="Confirm your password"
                            minlength="<?= config('security.password_min_length', 8) ?>"
                            required
                        >
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="/login">Sign in</a></p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(function(button) {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });
            
            // Form validation
            const form = document.getElementById('register-form');
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            
            form.addEventListener('submit', function(event) {
                if (password.value !== passwordConfirm.value) {
                    event.preventDefault();
                    alert('Passwords do not match!');
                    passwordConfirm.focus();
                }
            });
        });
    </script>
</body>
</html> 