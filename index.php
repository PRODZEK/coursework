<?php
/**
 * Telegram Clone - Main entry point
 */
session_start();

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']);

// Redirect to login if not authenticated
if (!$isAuthenticated && !in_array($_SERVER['REQUEST_URI'], ['/login', '/register'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Clone</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?= $isAuthenticated ? 'app-layout' : 'auth-layout' ?>">
    <div id="app">
        <?php if ($isAuthenticated): ?>
            <!-- Main app UI will be loaded by JS -->
            <div id="sidebar" class="sidebar">
                <!-- Sidebar content will be loaded by JS -->
                <div class="sidebar-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
            <div id="main" class="main-content">
                <!-- Main content will be loaded by JS -->
                <div class="main-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading messages...</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Authentication UI will be loaded by JS -->
            <div id="auth-container" class="auth-container">
                <div class="auth-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading...</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Templates for dynamic content -->
    <?php include 'components/templates.php'; ?>
    
    <!-- Core JS libraries -->
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/utils.js"></script>
    <script src="/assets/js/api.js"></script>
    
    <?php if ($isAuthenticated): ?>
        <!-- App specific JS -->
        <script src="/assets/js/chat.js"></script>
        <script src="/assets/js/messages.js"></script>
        <script src="/assets/js/realtime.js"></script>
        <script src="/assets/js/app.js"></script>
    <?php else: ?>
        <!-- Auth specific JS -->
        <script src="/assets/js/auth.js"></script>
    <?php endif; ?>
</body>
</html> 