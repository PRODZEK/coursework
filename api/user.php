<?php
/**
 * User API
 * 
 * This file handles API requests related to users
 */

// Ensure we output JSON even in case of errors
header('Content-Type: application/json');

// Error handler to catch all errors and return as JSON
function handleError($message) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Register custom exception handler
set_exception_handler(function($e) {
    handleError($e->getMessage());
});

// Register error handler
set_error_handler(function($errno, $errstr) {
    handleError($errstr);
});

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/utils.php';
    require_once __DIR__ . '/../includes/user.php';

    // Initialize session
    initSession();

    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Authentication required'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    // Handle CORS
    header('Access-Control-Allow-Origin: *');
    
    // Handle preflight OPTIONS request
    if ($method === 'OPTIONS') {
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit(0);
    }

    // Handle different request methods
    switch ($method) {
        case 'GET':
            // Check which action is requested
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            
            switch ($action) {
                case 'search':
                    // Search for users
                    if (!isset($_GET['query'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Search query required'
                        ]);
                        exit;
                    }
                    
                    $query = $_GET['query'];
                    $users = searchUsers($query);
                    
                    // Filter out the current user
                    $users = array_filter($users, function($user) use ($userId) {
                        return $user['user_id'] != $userId;
                    });
                    
                    echo json_encode([
                        'success' => true,
                        'users' => array_values($users)
                    ]);
                    break;
                    
                case 'get':
                    // Get user by ID
                    if (!isset($_GET['user_id'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'User ID required'
                        ]);
                        exit;
                    }
                    
                    $targetUserId = (int) $_GET['user_id'];
                    $user = getUserById($targetUserId);
                    
                    if ($user === null) {
                        http_response_code(404);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'User not found'
                        ]);
                        exit;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'user' => $user
                    ]);
                    break;
                
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid action'
                    ]);
                    break;
            }
            break;
            
        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    handleError($e->getMessage());
} 