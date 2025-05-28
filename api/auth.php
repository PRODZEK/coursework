<?php
/**
 * Authentication API Endpoints
 */
require_once __DIR__ . '/controllers/AuthController.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Create an instance of the controller
$authController = new AuthController();

// Get the endpoint from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/auth';

// Remove query string if present
$requestUri = strtok($requestUri, '?');

// Extract the endpoint
$endpoint = str_replace($basePath, '', $requestUri);
$endpoint = trim($endpoint, '/');

// Route to the appropriate controller method
switch ($endpoint) {
    case 'register':
        $authController->register();
        break;
    
    case 'login':
        $authController->login();
        break;
    
    case 'logout':
        $authController->logout();
        break;
    
    case 'user':
        $authController->getCurrentUser();
        break;
    
    default:
        // Return 404 for unknown endpoints
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found'
        ]);
        break;
} 