<?php
/**
 * Chat API Endpoints
 */
require_once __DIR__ . '/controllers/ChatController.php';

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
$chatController = new ChatController();

// Get the endpoint from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/chats';

// Remove query string if present
$requestUri = strtok($requestUri, '?');

// Extract the endpoint
$endpoint = str_replace($basePath, '', $requestUri);
$endpoint = trim($endpoint, '/');

// Route to the appropriate controller method
if ($endpoint === '') {
    // GET /api/chats - List all chats for the user
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $chatController->getChats();
    }
    // POST /api/chats - Create a new chat
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chatController->createChat();
    }
} else if (preg_match('/^(\d+)$/', $endpoint, $matches)) {
    // Chat ID endpoint
    $chatId = $matches[1];
    $_GET['id'] = $chatId;
    
    // GET /api/chats/{id} - Get a specific chat
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $chatController->getChat();
    }
    // PUT or PATCH /api/chats/{id} - Update a chat
    else if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $chatController->updateChat();
    }
} else if (preg_match('/^(\d+)\/participants$/', $endpoint, $matches)) {
    // Participants endpoint
    $chatId = $matches[1];
    $_GET['id'] = $chatId;
    
    // POST /api/chats/{id}/participants - Add a participant
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chatController->addParticipant();
    }
} else if (preg_match('/^(\d+)\/participants\/(\d+)$/', $endpoint, $matches)) {
    // Specific participant endpoint
    $chatId = $matches[1];
    $userId = $matches[2];
    $_GET['id'] = $chatId;
    $_GET['user_id'] = $userId;
    
    // DELETE /api/chats/{id}/participants/{userId} - Remove a participant
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $chatController->removeParticipant();
    }
    // PATCH /api/chats/{id}/participants/{userId} - Update participant role
    else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $chatController->updateParticipantRole();
    }
} else if ($endpoint === 'private') {
    // GET /api/chats/private?user_id={userId} - Get or create a private chat
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $chatController->getPrivateChat();
    }
} else {
    // Return 404 for unknown endpoints
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Endpoint not found'
    ]);
} 