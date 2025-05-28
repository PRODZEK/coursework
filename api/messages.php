<?php
/**
 * Message API Endpoints
 */
require_once __DIR__ . '/controllers/MessageController.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Create an instance of the controller
$messageController = new MessageController();

// Get the endpoint from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/messages';

// Remove query string if present
$requestUri = strtok($requestUri, '?');

// Extract the endpoint
$endpoint = str_replace($basePath, '', $requestUri);
$endpoint = trim($endpoint, '/');

// Route to the appropriate controller method
if ($endpoint === '') {
    // GET /api/messages?chat_id={chatId} - Get messages for a chat
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $messageController->getMessages();
    }
    // POST /api/messages - Send a new message
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $messageController->sendMessage();
    }
} else if (preg_match('/^(\d+)$/', $endpoint, $matches)) {
    // Message ID endpoint
    $messageId = $matches[1];
    $_GET['id'] = $messageId;
    
    // PUT or PATCH /api/messages/{id} - Edit a message
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $messageController->editMessage();
    }
    // DELETE /api/messages/{id} - Delete a message
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $messageController->deleteMessage();
    }
} else if ($endpoint === 'forward') {
    // POST /api/messages/forward - Forward a message
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $messageController->forwardMessage();
    }
} else if ($endpoint === 'read') {
    // POST /api/messages/read - Mark messages as read
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $messageController->markAsRead();
    }
} else if ($endpoint === 'unread') {
    // GET /api/messages/unread - Get unread message counts
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $messageController->getUnreadCounts();
    }
} else {
    // Return 404 for unknown endpoints
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Endpoint not found'
    ]);
} 