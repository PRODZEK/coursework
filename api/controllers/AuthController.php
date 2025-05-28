<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../ApiResponse.php';

/**
 * Authentication Controller
 * Handles user registration, login, and session management
 */
class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Register a new user
     */
    public function register() {
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            ApiResponse::error('Invalid request data', 400);
        }
        
        $required = ['username', 'email', 'password', 'full_name'];
        $errors = [];
        
        // Check required fields
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        // Validate email format
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        // Validate password strength (at least 8 characters)
        if (isset($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }
        
        // Check if username or email already exists
        if (empty($errors) && $this->userModel->findByUsernameOrEmail($data['username'])) {
            $errors['username'] = 'Username already exists';
        }
        
        if (empty($errors) && $this->userModel->findByUsernameOrEmail($data['email'])) {
            $errors['email'] = 'Email already exists';
        }
        
        // If validation errors exist, return them
        if (!empty($errors)) {
            ApiResponse::error('Validation failed', 422, $errors);
        }
        
        // Create the user
        $userId = $this->userModel->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'full_name' => $data['full_name']
        ]);
        
        if (!$userId) {
            ApiResponse::error('Failed to create user', 500);
        }
        
        // Get the user data to return (excluding password)
        $user = $this->userModel->findById($userId);
        
        // Return success response with the user data
        ApiResponse::success($user, 'Registration successful', 201);
    }
    
    /**
     * Login a user
     */
    public function login() {
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            ApiResponse::error('Invalid request data', 400);
        }
        
        // Check required fields
        if (!isset($data['login']) || !isset($data['password'])) {
            ApiResponse::error('Login and password are required', 400);
        }
        
        // Find the user by username or email
        $user = $this->userModel->findByUsernameOrEmail($data['login']);
        
        // Check if user exists and password is valid
        if (!$user || !$this->userModel->verifyPassword($user, $data['password'])) {
            ApiResponse::error('Invalid credentials', 401);
        }
        
        // If user is banned, return error
        if ($user['status'] === 'banned') {
            ApiResponse::error('Your account has been suspended', 403);
        }
        
        // Update online status
        $this->userModel->updateOnlineStatus($user['user_id'], true);
        
        // Generate session token
        $token = bin2hex(random_bytes(32));
        
        // Start session and store user data
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['token'] = $token;
        $_SESSION['expires'] = time() + (86400 * 30); // 30 days
        
        // Prepare user data for response (exclude password)
        unset($user['password']);
        
        // Return success response with user data and token
        ApiResponse::success([
            'user' => $user,
            'token' => $token,
            'expires' => $_SESSION['expires']
        ], 'Login successful');
    }
    
    /**
     * Logout the current user
     */
    public function logout() {
        session_start();
        
        // Update online status if user is logged in
        if (isset($_SESSION['user_id'])) {
            $this->userModel->updateOnlineStatus($_SESSION['user_id'], false);
            
            // Destroy session
            session_unset();
            session_destroy();
            
            ApiResponse::success(null, 'Logout successful');
        } else {
            ApiResponse::error('Not authenticated', 401);
        }
    }
    
    /**
     * Get current authenticated user profile
     */
    public function getCurrentUser() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if session is expired
        if ($_SESSION['expires'] < time()) {
            // Destroy session
            session_unset();
            session_destroy();
            ApiResponse::error('Session expired', 401);
        }
        
        // Get user data
        $user = $this->userModel->findById($_SESSION['user_id']);
        
        if (!$user) {
            // Destroy session if user no longer exists
            session_unset();
            session_destroy();
            ApiResponse::error('User not found', 404);
        }
        
        // Return user data
        ApiResponse::success($user);
    }
} 