<?php
/**
 * Authentication API
 * 
 * This file handles API requests related to user authentication
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../includes/user.php';

// Initialize session
initSession();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight OPTIONS request
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Handle CORS
header('Access-Control-Allow-Origin: *');

// Handle different request methods
switch ($method) {
    case 'POST':
        // Get request body
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
        
        // Check if input is valid
        if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
            // Try to get form data if JSON parsing failed
            $input = $_POST;
        }
        
        // Check which action is requested
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'register':
                // Register new user
                if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Missing required fields'
                    ]);
                    exit;
                }
                
                $result = registerUser(
                    $input['username'],
                    $input['email'],
                    $input['password']
                );
                
                if ($result['success']) {
                    http_response_code(201); // Created
                } else {
                    http_response_code(400); // Bad Request
                }
                
                echo json_encode($result);
                break;
                
            case 'login':
                // Login user
                if (!isset($input['username']) || !isset($input['password'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Missing required fields'
                    ]);
                    exit;
                }
                
                $result = loginUser(
                    $input['username'],
                    $input['password']
                );
                
                if ($result['success']) {
                    // Set session
                    $_SESSION['user_id'] = $result['user']['user_id'];
                    $_SESSION['username'] = $result['user']['username'];
                    $_SESSION['email'] = $result['user']['email'];
                    
                    http_response_code(200); // OK
                } else {
                    http_response_code(401); // Unauthorized
                }
                
                echo json_encode($result);
                break;
                
            case 'logout':
                // Check if user is logged in
                if (!isLoggedIn()) {
                    http_response_code(401);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Not logged in'
                    ]);
                    exit;
                }
                
                // Logout user
                $result = logoutUser($_SESSION['user_id']);
                
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Logged out successfully' : 'Failed to logout'
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
        
    case 'GET':
        // Check which action is requested
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'status':
                // Check authentication status
                $isAuthenticated = isLoggedIn();
                
                if ($isAuthenticated) {
                    $user = getUserById($_SESSION['user_id']);
                    
                    echo json_encode([
                        'success' => true,
                        'authenticated' => true,
                        'user' => [
                            'user_id' => $user['user_id'],
                            'username' => $user['username'],
                            'email' => $user['email'],
                            'profile_picture' => $user['profile_picture'],
                            'status' => $user['status'],
                            'last_seen' => $user['last_seen']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'authenticated' => false
                    ]);
                }
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