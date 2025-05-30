<?php
/**
 * Chat API
 * 
 * This file handles API requests related to chats and messages
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
    require_once __DIR__ . '/../includes/chat.php';

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
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit(0);
    }

    // Handle CORS
    header('Access-Control-Allow-Origin: *');

    // Handle different request methods
    switch ($method) {
        case 'GET':
            // Check which action is requested
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            
            switch ($action) {
                case 'list':
                    // Get all chats for the user
                    $chats = getUserChats($userId);
                    
                    echo json_encode([
                        'success' => true,
                        'chats' => $chats
                    ]);
                    break;
                    
                case 'get':
                    // Get specific chat by ID
                    if (!isset($_GET['chat_id'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat ID required'
                        ]);
                        exit;
                    }
                    
                    $chatId = (int) $_GET['chat_id'];
                    $chat = getChatById($chatId, $userId);
                    
                    if ($chat === null) {
                        http_response_code(404);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat not found or you are not a member'
                        ]);
                        exit;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'chat' => $chat
                    ]);
                    break;
                    
                case 'messages':
                    // Get messages for a specific chat
                    if (!isset($_GET['chat_id'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat ID required'
                        ]);
                        exit;
                    }
                    
                    $chatId = (int) $_GET['chat_id'];
                    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : MESSAGES_PER_LOAD;
                    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
                    
                    $result = getChatMessages($chatId, $userId, $limit, $offset);
                    
                    if (!$result['success']) {
                        http_response_code(403);
                        echo json_encode($result);
                        exit;
                    }
                    
                    echo json_encode($result);
                    break;
                    
                case 'search_users':
                    // Search for users to start a chat with
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
                    
                case 'unread_count':
                    // Get number of unread messages
                    $count = getUnreadMessagesCount($userId);
                    
                    echo json_encode([
                        'success' => true,
                        'count' => $count
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
            
        case 'POST':
            // Get request body
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, true);
            
            // Check if input is valid
            if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
                // Try to get form data if JSON parsing failed
                $input = $_POST;
                
                // If still empty, return error
                if (empty($input)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid JSON input: ' . json_last_error_msg()
                    ]);
                    exit;
                }
            }
            
            // Check which action is requested
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            
            switch ($action) {
                case 'create_direct':
                    // Create direct chat with another user
                    if (!isset($input['user_id'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'User ID required'
                        ]);
                        exit;
                    }
                    
                    $otherUserId = (int) $input['user_id'];
                    $result = createDirectChat($userId, $otherUserId);
                    
                    if ($result['success']) {
                        http_response_code(201); // Created
                    } else {
                        http_response_code(400); // Bad Request
                    }
                    
                    echo json_encode($result);
                    break;
                    
                case 'create_group':
                    // Create group chat
                    if (!isset($input['chat_name'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat name required'
                        ]);
                        exit;
                    }
                    
                    $chatName = $input['chat_name'];
                    $memberIds = isset($input['member_ids']) ? $input['member_ids'] : [];
                    
                    $result = createGroupChat($chatName, $userId, $memberIds);
                    
                    if ($result['success']) {
                        http_response_code(201); // Created
                    } else {
                        http_response_code(400); // Bad Request
                    }
                    
                    echo json_encode($result);
                    break;
                    
                case 'send_message':
                    // Send message in a chat
                    if (!isset($input['chat_id']) || !isset($input['message_text'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat ID and message text required'
                        ]);
                        exit;
                    }
                    
                    $chatId = (int) $input['chat_id'];
                    $messageText = $input['message_text'];
                    $messageType = isset($input['message_type']) ? $input['message_type'] : 'text';
                    $fileUrl = isset($input['file_url']) ? $input['file_url'] : null;
                    
                    $result = sendMessage($chatId, $userId, $messageText, $messageType, $fileUrl);
                    
                    if ($result['success']) {
                        http_response_code(201); // Created
                    } else {
                        http_response_code(400); // Bad Request
                    }
                    
                    echo json_encode($result);
                    break;
                    
                case 'mark_as_read':
                    // Mark message(s) as read
                    if (!isset($input['chat_id'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat ID required'
                        ]);
                        exit;
                    }
                    
                    $chatId = (int) $input['chat_id'];
                    $messageId = isset($input['message_id']) ? (int) $input['message_id'] : null;
                    
                    $result = markMessagesAsRead($chatId, $userId, $messageId);
                    
                    echo json_encode($result);
                    break;
                    
                case 'add_members':
                    // Add members to a group chat
                    if (!isset($input['chat_id']) || !isset($input['member_ids']) || !is_array($input['member_ids'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Chat ID and member IDs required'
                        ]);
                        exit;
                    }
                    
                    $chatId = (int) $input['chat_id'];
                    $memberIds = $input['member_ids'];
                    
                    $result = addChatMembers($chatId, $userId, $memberIds);
                    
                    if ($result['success']) {
                        http_response_code(200); // OK
                    } else {
                        http_response_code(400); // Bad Request
                    }
                    
                    echo json_encode($result);
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

/**
 * Mark messages as read
 * 
 * @param int $chatId Chat ID
 * @param int $userId User ID
 * @param int|null $messageId Specific message ID to mark as read (optional)
 * @return array Result with success flag
 */
function markMessagesAsRead($chatId, $userId, $messageId = null) {
    $conn = getDbConnection();
    
    // Check if user is a member of the chat
    $memberCheckSql = "SELECT chat_id FROM chat_members WHERE chat_id = ? AND user_id = ?";
    $memberCheckStmt = $conn->prepare($memberCheckSql);
    $memberCheckStmt->bind_param("ii", $chatId, $userId);
    $memberCheckStmt->execute();
    $memberCheckResult = $memberCheckStmt->get_result();
    
    if ($memberCheckResult->num_rows === 0) {
        // User is not a member of this chat
        $memberCheckStmt->close();
        closeDbConnection($conn);
        return ['success' => false, 'message' => 'You are not a member of this chat'];
    }
    
    $memberCheckStmt->close();
    
    try {
        if ($messageId !== null) {
            // Mark specific message as read
            $updateSql = "
                UPDATE message_status 
                SET is_read = 1, read_at = NOW() 
                WHERE message_id = ? AND user_id = ?
            ";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $messageId, $userId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Mark all unread messages in the chat as read
            $updateSql = "
                UPDATE message_status ms
                JOIN messages m ON ms.message_id = m.message_id
                SET ms.is_read = 1, ms.read_at = NOW() 
                WHERE m.chat_id = ? AND ms.user_id = ? AND ms.is_read = 0
            ";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $chatId, $userId);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        closeDbConnection($conn);
        
        return [
            'success' => true,
            'message' => 'Messages marked as read'
        ];
    } catch (Exception $e) {
        closeDbConnection($conn);
        return [
            'success' => false,
            'message' => 'Failed to mark messages as read: ' . $e->getMessage()
        ];
    }
} 