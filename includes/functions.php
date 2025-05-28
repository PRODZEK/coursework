<?php
/**
 * Common Helper Functions
 * Utility functions for the application
 */

/**
 * Get the configuration array or a specific config value
 * 
 * @param string|null $key Dot notation key (e.g., 'app.name')
 * @param mixed $default Default value if key not found
 * @return mixed Config value or entire config array
 */
function config($key = null, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = require_once __DIR__ . '/../config/config.php';
    }
    
    if ($key === null) {
        return $config;
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Get a database connection
 * 
 * @return PDO
 */
function db() {
    return Database::getInstance()->getConnection();
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format a timestamp for display
 * 
 * @param string|int $timestamp Timestamp to format
 * @param bool $includeTime Whether to include the time
 * @return string Formatted date/time
 */
function formatDate($timestamp, $includeTime = true) {
    if (!$timestamp) {
        return '';
    }
    
    $format = $includeTime ? 'd M Y, H:i' : 'd M Y';
    
    if (is_numeric($timestamp)) {
        $timestamp = date('Y-m-d H:i:s', $timestamp);
    }
    
    $date = new DateTime($timestamp);
    return $date->format($format);
}

/**
 * Relative time (e.g., "2 hours ago")
 * 
 * @param string|int $timestamp Timestamp to format
 * @return string Relative time
 */
function timeAgo($timestamp) {
    if (!$timestamp) return '';
    
    if (is_numeric($timestamp)) {
        $timestamp = date('Y-m-d H:i:s', $timestamp);
    }
    
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' ' . ($mins == 1 ? 'minute' : 'minutes') . ' ago';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours') . ' ago';
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' ' . ($days == 1 ? 'day' : 'days') . ' ago';
    } else {
        return formatDate($timestamp);
    }
}

/**
 * Sanitize HTML output
 * 
 * @param string $str String to sanitize
 * @return string Sanitized string
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Get current authenticated user ID
 * 
 * @return int|null User ID or null if not authenticated
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current authenticated user data
 * 
 * @return array|null User data or null if not authenticated
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    static $user = null;
    
    if ($user === null) {
        $stmt = db()->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    
    return $user;
}

/**
 * Check if the request is AJAX
 * 
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Generate a URL-friendly slug
 * 
 * @param string $str String to slugify
 * @return string Slug
 */
function slugify($str) {
    $str = transliterator_transliterate('Any-Latin; Latin-ASCII', $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $status HTTP status code
 */
function redirect($url, $status = 302) {
    header("Location: $url", true, $status);
    exit;
}

/**
 * Check if a string starts with a specific substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool
 */
function startsWith($haystack, $needle) {
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

/**
 * Check if a string ends with a specific substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool
 */
function endsWith($haystack, $needle) {
    return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

/**
 * Get the client's IP address
 * 
 * @return string
 */
function getIpAddress() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    return $ip;
} 