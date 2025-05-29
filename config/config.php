<?php
/**
 * Application Configuration
 * 
 * This file contains general application settings
 */

// Application settings
define('APP_NAME', 'Chat App');
define('APP_URL', 'http://localhost/coursework');
define('APP_VERSION', '1.0.0');

// Session settings
define('SESSION_NAME', 'chat_app_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Chat settings
define('MESSAGES_PER_LOAD', 20);
define('POLLING_INTERVAL', 3000); // 3 seconds in milliseconds

// Timezone
date_default_timezone_set('UTC');

// Initialize session
function initSession() {
    // Set session cookie parameters
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/database.php'; 