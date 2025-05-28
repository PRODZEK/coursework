<?php
/**
 * Authentication Functions
 * Handles user authentication, registration, and authorization
 */

// Include required files if not already included
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/session.php';

class Auth {
    /**
     * Register a new user
     * 
     * @param array $userData User data (username, email, password, etc.)
     * @return array|bool Array with user data on success, false on failure
     */
    public static function register($userData) {
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                return ['error' => 'Missing required field: ' . $field];
            }
        }
        
        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email address'];
        }
        
        // Validate username (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $userData['username'])) {
            return ['error' => 'Username must be 4-20 characters and contain only letters, numbers, and underscores'];
        }
        
        // Validate password strength
        $minLength = config('security.password_min_length', 8);
        if (strlen($userData['password']) < $minLength) {
            return ['error' => "Password must be at least {$minLength} characters"];
        }
        
        // Check if username or email already exists
        try {
            $db = db();
            
            $checkStmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $checkStmt->execute([$userData['username'], $userData['email']]);
            
            if ($checkStmt->fetchColumn()) {
                return ['error' => 'Username or email already exists'];
            }
            
            // Hash the password
            $passwordHash = password_hash(
                $userData['password'], 
                config('security.password_algo', PASSWORD_DEFAULT),
                config('security.password_options', ['cost' => 10])
            );
            
            // Insert new user
            $insertStmt = $db->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, created_at, last_seen)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $insertStmt->execute([
                $userData['username'],
                $userData['email'],
                $passwordHash,
                $userData['first_name'],
                $userData['last_name']
            ]);
            
            $userId = $db->lastInsertId();
            
            // Return user data (without password)
            $userStmt = $db->prepare("SELECT user_id, username, email, first_name, last_name, created_at FROM users WHERE user_id = ?");
            $userStmt->execute([$userId]);
            return $userStmt->fetch();
            
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            return ['error' => 'An error occurred during registration'];
        }
    }
    
    /**
     * Authenticate a user with username/email and password
     * 
     * @param string $identifier Username or email
     * @param string $password Password
     * @param bool $remember Remember the user
     * @return array|bool Array with user data on success, false on failure
     */
    public static function login($identifier, $password, $remember = false) {
        if (empty($identifier) || empty($password)) {
            return ['error' => 'Username/email and password are required'];
        }
        
        try {
            $db = db();
            
            // Check if identifier is username or email
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'username';
            
            // Get user by username or email
            $stmt = $db->prepare("SELECT * FROM users WHERE {$field} = ? LIMIT 1");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['error' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_hash_verify($password, $user['password'])) {
                // Log failed attempt for rate limiting
                self::logLoginAttempt($identifier, false);
                return ['error' => 'Invalid credentials'];
            }
            
            // Remove password from user data
            unset($user['password']);
            
            // Update last seen
            $updateStmt = $db->prepare("UPDATE users SET last_seen = NOW(), status = 'online', is_online = 1 WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            // Set session variables
            SessionManager::set('user_id', $user['user_id']);
            SessionManager::set('username', $user['username']);
            SessionManager::set('authenticated', true);
            
            // Regenerate session ID for security
            SessionManager::regenerate();
            
            // Handle "remember me" functionality
            if ($remember) {
                self::createRememberToken($user['user_id']);
            }
            
            // Log successful login
            self::logLoginAttempt($identifier, true);
            
            return $user;
            
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            return ['error' => 'An error occurred during login'];
        }
    }
    
    /**
     * Log out the current user
     */
    public static function logout() {
        try {
            // Update user status
            $userId = SessionManager::get('user_id');
            if ($userId) {
                $db = db();
                $stmt = $db->prepare("UPDATE users SET status = 'offline', is_online = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);
            }
            
            // Remove remember token if exists
            self::clearRememberToken();
            
            // Destroy the session
            SessionManager::destroy();
            
            return true;
        } catch (PDOException $e) {
            error_log('Logout error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function isLoggedIn() {
        return SessionManager::has('authenticated') && SessionManager::get('authenticated') === true;
    }
    
    /**
     * Create a remember me token
     * 
     * @param int $userId User ID
     * @return bool
     */
    private static function createRememberToken($userId) {
        try {
            $token = generateToken(64);
            $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days
            $tokenHash = password_hash($token, PASSWORD_DEFAULT);
            
            $db = db();
            $stmt = $db->prepare("
                INSERT INTO remember_tokens (user_id, token, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$userId, $tokenHash, $expires, $tokenHash, $expires]);
            
            // Set cookie
            $secureCookie = isSecureConnection();
            setcookie('remember_token', $userId . ':' . $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'domain' => '',
                'secure' => $secureCookie,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Remember token error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear remember token
     * 
     * @return bool
     */
    private static function clearRememberToken() {
        if (isset($_COOKIE['remember_token'])) {
            list($userId, $token) = explode(':', $_COOKIE['remember_token'], 2);
            
            try {
                $db = db();
                $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $stmt->execute([$userId]);
            } catch (Exception $e) {
                error_log('Clear remember token error: ' . $e->getMessage());
            }
            
            // Clear cookie
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isSecureConnection(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        return true;
    }
    
    /**
     * Log login attempt for rate limiting
     * 
     * @param string $identifier Username or email
     * @param bool $success Whether login was successful
     * @return bool
     */
    private static function logLoginAttempt($identifier, $success) {
        try {
            $ip = getIpAddress();
            $timestamp = date('Y-m-d H:i:s');
            
            $db = db();
            $stmt = $db->prepare("
                INSERT INTO login_attempts (identifier, ip_address, success, created_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$identifier, $ip, $success ? 1 : 0, $timestamp]);
            
            return true;
        } catch (Exception $e) {
            error_log('Login attempt log error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if login is currently rate limited
     * 
     * @param string $identifier Username or email
     * @param string $ip IP address
     * @return bool|array False if not rate limited, array with error info if rate limited
     */
    public static function isRateLimited($identifier, $ip = null) {
        if ($ip === null) {
            $ip = getIpAddress();
        }
        
        $maxAttempts = config('rate_limits.login_attempts.max', 5);
        $window = config('rate_limits.login_attempts.window', 300); // 5 minutes
        $lockoutTime = config('rate_limits.login_attempts.lockout_time', 900); // 15 minutes
        
        try {
            $db = db();
            $stmt = $db->prepare("
                SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt 
                FROM login_attempts 
                WHERE (identifier = ? OR ip_address = ?) 
                AND success = 0 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$identifier, $ip, $window]);
            $result = $stmt->fetch();
            
            if ($result['attempts'] >= $maxAttempts) {
                // Calculate time remaining until unlock
                $lastAttempt = strtotime($result['last_attempt']);
                $unlockTime = $lastAttempt + $lockoutTime;
                $now = time();
                
                if ($unlockTime > $now) {
                    $remainingTime = $unlockTime - $now;
                    $minutes = ceil($remainingTime / 60);
                    
                    return [
                        'error' => 'Too many failed login attempts',
                        'message' => "Account locked for {$minutes} minutes due to too many failed login attempts",
                        'remaining_seconds' => $remainingTime
                    ];
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Rate limit check error: ' . $e->getMessage());
            return false; // Don't rate limit on error
        }
    }
}

/**
 * Verifies that a password matches a hash in a timing-attack safe manner
 * 
 * @param string $password The password to verify
 * @param string $hash The stored hash
 * @return bool
 */
function password_hash_verify($password, $hash) {
    return password_verify($password, $hash);
} 