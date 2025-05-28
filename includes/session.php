<?php
/**
 * Session Management
 * Secure session handling for the application
 */

class SessionManager {
    /**
     * Initialize session with secure settings
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            $sessionLifetime = config('security.session_lifetime', 86400);
            
            // Set session cookie parameters
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => $sessionLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => isSecureConnection(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Custom session name
            session_name('telegram_clone_session');
            
            // Start session
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created_at'])) {
                $_SESSION['created_at'] = time();
            } elseif (time() - $_SESSION['created_at'] > 1800) { // 30 minutes
                self::regenerate();
            }
        }
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }
    
    /**
     * End session
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear session data
            $_SESSION = [];
            
            // Clear session cookie
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax'
            ]);
            
            // Destroy the session
            session_destroy();
        }
    }
    
    /**
     * Set session data
     * 
     * @param string $key Key
     * @param mixed $value Value
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session data
     * 
     * @param string $key Key
     * @param mixed $default Default value if key not found
     * @return mixed Session value or default
     */
    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Check if session key exists
     * 
     * @param string $key Key
     * @return bool
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session data
     * 
     * @param string $key Key
     */
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Set flash message (available for one request)
     * 
     * @param string $key Key
     * @param mixed $value Value
     */
    public static function setFlash($key, $value) {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message and remove it
     * 
     * @param string $key Key
     * @param mixed $default Default value if key not found
     * @return mixed Flash value or default
     */
    public static function getFlash($key, $default = null) {
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Clear all flash messages
     */
    public static function clearFlash() {
        if (isset($_SESSION['_flash'])) {
            unset($_SESSION['_flash']);
        }
    }
}

/**
 * Check if the connection is secure (HTTPS)
 * 
 * @return bool
 */
function isSecureConnection() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on');
}

// Initialize session when this file is included
SessionManager::start(); 