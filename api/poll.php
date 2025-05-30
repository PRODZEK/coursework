<?php
/**
 * Long-Polling API
 * 
 * This file implements a simple long-polling mechanism to provide 
 * near real-time updates for messages and chat status
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/chat.php';

// Встановлюємо обробник помилок, щоб завжди повертати JSON
function handlePollError($message) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Встановлюємо обробники помилок і винятків
set_exception_handler(function($e) {
    handlePollError($e->getMessage());
});

set_error_handler(function($errno, $errstr) {
    handlePollError($errstr);
});

try {
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
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit(0);
    }

    // Handle CORS
    header('Access-Control-Allow-Origin: *');

    // Only allow GET requests
    if ($method !== 'GET') {
        http_response_code(405); // Method Not Allowed
        echo json_encode([
            'success' => false, 
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    // Get parameters
    $lastUpdate = isset($_GET['last_update']) ? (int) $_GET['last_update'] : 0;
    $chatId = isset($_GET['chat_id']) ? (int) $_GET['chat_id'] : null;

    // Update user status
    $conn = getDbConnection();
    $updateStatusSql = "UPDATE users SET status = 'online', last_seen = NOW() WHERE user_id = ?";
    $updateStatusStmt = $conn->prepare($updateStatusSql);
    $updateStatusStmt->bind_param("i", $userId);
    $updateStatusStmt->execute();
    $updateStatusStmt->close();

    // Визначаємо поточний час як timestamp для відповіді
    $currentTimestamp = time();
    
    // Підготовка даних для відповіді
    $response = [
        'success' => true,
        'timestamp' => $currentTimestamp,
        'status_updates' => [],
        'messages' => []
    ];
    
    // Якщо запитують конкретний чат, отримуємо нові повідомлення
    if ($chatId !== null) {
        $messagesSql = "
            SELECT 
                m.message_id, 
                m.chat_id, 
                m.sender_id, 
                m.message_text, 
                m.message_type, 
                m.file_url, 
                m.sent_at,
                u.username as sender_name,
                u.profile_picture as sender_profile_picture,
                u.user_id as sender_id,
                CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END as is_sender
            FROM 
                messages m
            JOIN 
                users u ON m.sender_id = u.user_id
            WHERE 
                m.chat_id = ? 
                AND UNIX_TIMESTAMP(m.sent_at) > ?
            ORDER BY 
                m.sent_at ASC
        ";
        
        $messagesStmt = $conn->prepare($messagesSql);
        $messagesStmt->bind_param("iii", $userId, $chatId, $lastUpdate);
        $messagesStmt->execute();
        $messagesResult = $messagesStmt->get_result();
        
        $messages = [];
        while ($message = $messagesResult->fetch_assoc()) {
            // Ensure message text is properly included
            $message['content'] = $message['message_text'];
            $messages[] = $message;
            
            // If message is from someone else, mark it as read
            if ($message['sender_id'] != $userId) {
                // Mark message as read
                $markReadSql = "
                    INSERT INTO message_status (message_id, user_id, is_read, read_at) 
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
                ";
                
                $markReadStmt = $conn->prepare($markReadSql);
                $markReadStmt->bind_param("ii", $message['message_id'], $userId);
                $markReadStmt->execute();
                $markReadStmt->close();
            }
        }
        
        $messagesStmt->close();
        
        if (!empty($messages)) {
            $response['messages'] = $messages;
        }
    } else {
        // Отримуємо оновлення статусів користувачів
        $statusSql = "
            SELECT 
                user_id, status, last_seen
            FROM 
                users
            WHERE 
                UNIX_TIMESTAMP(last_seen) > ?
                AND user_id != ?
        ";
        
        $statusStmt = $conn->prepare($statusSql);
        $statusStmt->bind_param("ii", $lastUpdate, $userId);
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        
        $statusUpdates = [];
        while ($status = $statusResult->fetch_assoc()) {
            $statusUpdates[] = $status;
        }
        
        $statusStmt->close();
        
        if (!empty($statusUpdates)) {
            $response['status_updates'] = $statusUpdates;
        }
    }
    
    closeDbConnection($conn);
    
    // Повертаємо відповідь як JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    handlePollError($e->getMessage());
} 