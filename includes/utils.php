<?php
/**
 * Utility Functions
 * 
 * This file contains common helper functions used throughout the application
 */

/**
 * Sanitize user input
 * 
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email address
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date in human-readable form
 * 
 * @param string $datetime Date and time string
 * @return string Formatted date
 */
function formatDateTime($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'yesterday';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Format time in human-readable form
 * 
 * @param string $datetime Date and time string
 * @return string Formatted time
 */
function formatTime($datetime) {
    return date('H:i', strtotime($datetime));
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect user to specified page
 * 
 * @param string $page Page to redirect to
 * @return void
 */
function redirect($page) {
    header("Location: " . APP_URL . "/$page");
    exit;
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $uri;
}

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @param string $level Log level
 * @return void
 */
function logMessage($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level]: $message" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
} 