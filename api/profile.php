<?php
/**
 * Profile API
 * 
 * This file handles API requests related to user profiles
 */

header('Content-Type: application/json');

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

// Handle preflight OPTIONS request
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Handle CORS
header('Access-Control-Allow-Origin: *');

// Handle different request methods
switch ($method) {
    case 'GET':
        // Get user profile
        $user = getUserById($userId);
        
        if ($user === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'User not found'
            ]);
            exit;
        }
        
        // Remove sensitive data
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        break;
        
    case 'PUT':
        // Update user profile
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
        
        // Check if input is valid
        if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
            // Try to get form data if JSON parsing failed
            $input = $_POST;
        }
        
        // Update profile data
        $result = updateUserProfile($userId, $input);
        
        if ($result['success']) {
            // Get updated user data
            $user = getUserById($userId);
            unset($user['password']);
            
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'user' => $user
            ]);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;
        
    case 'POST':
        // Check which action is requested
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'upload_profile_picture':
                // Upload profile picture
                if (!isset($_FILES['profile_picture'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'No file uploaded'
                    ]);
                    exit;
                }
                
                $result = updateProfilePicture($userId, $_FILES['profile_picture']);
                
                if ($result['success']) {
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode($result);
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