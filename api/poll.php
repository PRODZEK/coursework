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
        'messages' => [],
        'deleted_chats' => [],
        'valid_chats' => [],
        'read_updates' => [],
        'chat_updates' => []
    ];
    
    // Якщо запитують конкретний чат, отримуємо нові повідомлення
    if ($chatId !== null) {
        // First check if the chat still exists
        $chatCheckSql = "SELECT chat_id FROM chats WHERE chat_id = ?";
        $chatCheckStmt = $conn->prepare($chatCheckSql);
        $chatCheckStmt->bind_param("i", $chatId);
        $chatCheckStmt->execute();
        $chatCheckResult = $chatCheckStmt->get_result();
        
        if ($chatCheckResult->num_rows === 0) {
            // Chat has been deleted
            $response['chat_deleted'] = true;
            $chatCheckStmt->close();
            
            closeDbConnection($conn);
            echo json_encode($response);
            exit;
        }
        $chatCheckStmt->close();
        
        // Continue with getting messages for existing chat
        $messagesSql = "
            SELECT 
                m.message_id, 
                m.chat_id, 
                m.sender_id, 
                m.message_text, 
                m.message_type, 
                m.file_url, 
                UNIX_TIMESTAMP(m.sent_at) as sent_at_timestamp,
                DATE_FORMAT(m.sent_at, '%Y-%m-%dT%H:%i:%s') as sent_at,
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
        
        // Check for messages read by OTHERS in this chat (for messages sent BY THE CURRENT USER)
        $readUpdatesSql = "
            SELECT 
                ms.message_id, 
                MIN(ms.is_read) as all_recipients_read
            FROM message_status ms
            JOIN messages m ON ms.message_id = m.message_id
            WHERE m.chat_id = ? AND m.sender_id = ? 
            AND UNIX_TIMESTAMP(ms.read_at) > ?
            GROUP BY ms.message_id
        ";
        $readStmt = $conn->prepare($readUpdatesSql);
        $readStmt->bind_param("iii", $chatId, $userId, $lastUpdate);
        $readStmt->execute();
        $readResult = $readStmt->get_result();
        while ($row = $readResult->fetch_assoc()) {
            $response['read_updates'][] = [
                'message_id' => $row['message_id'],
                'is_read' => (bool)$row['all_recipients_read']
            ];
        }
        $readStmt->close();
    } else {
        // Global polling - check for deleted chats
        $deletedChatsSql = "
            SELECT dc.chat_id, 
                UNIX_TIMESTAMP(dc.deleted_at) as deleted_at_timestamp,
                DATE_FORMAT(dc.deleted_at, '%Y-%m-%dT%H:%i:%s') as deleted_at
            FROM deleted_chats dc
            JOIN deleted_chat_members dcm ON dc.chat_id = dcm.chat_id 
            WHERE dcm.user_id = ? AND UNIX_TIMESTAMP(dc.deleted_at) > ?
        ";
        
        $deletedChatsStmt = $conn->prepare($deletedChatsSql);
        if ($deletedChatsStmt) {
            $deletedChatsStmt->bind_param("ii", $userId, $lastUpdate);
            $deletedChatsStmt->execute();
            $deletedChatsResult = $deletedChatsStmt->get_result();
            
            $deletedChats = [];
            if ($deletedChatsResult) {
                while ($deletedChat = $deletedChatsResult->fetch_assoc()) {
                    $deletedChats[] = $deletedChat['chat_id'];
                }
            }
            
            $deletedChatsStmt->close();
            
            if (!empty($deletedChats)) {
                $response['deleted_chats'] = $deletedChats;
            }
        }
        
        // Get a fresh list of all valid chats for this user
        $validChatsSql = "
            SELECT c.chat_id 
            FROM chats c 
            JOIN chat_members cm ON c.chat_id = cm.chat_id 
            WHERE cm.user_id = ?
        ";
        
        $validChatsStmt = $conn->prepare($validChatsSql);
        $validChatsStmt->bind_param("i", $userId);
        $validChatsStmt->execute();
        $validChatsResult = $validChatsStmt->get_result();
        
        $validChats = [];
        while ($chat = $validChatsResult->fetch_assoc()) {
            $validChats[] = $chat['chat_id'];
        }
        $validChatsStmt->close();
        
        $response['valid_chats'] = $validChats;
        
        // Отримуємо оновлення статусів користувачів
        $statusSql = "
            SELECT 
                user_id, 
                status, 
                UNIX_TIMESTAMP(last_seen) as last_seen_timestamp,
                DATE_FORMAT(last_seen, '%Y-%m-%dT%H:%i:%s') as last_seen
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
        
        // Check if there are any updated chats (new messages, etc)
        $chatUpdatesSql = "
            SELECT 
                c.chat_id,
                UNIX_TIMESTAMP(c.updated_at) as updated_at_timestamp,
                DATE_FORMAT(c.updated_at, '%Y-%m-%dT%H:%i:%s') as updated_at
            FROM 
                chats c
            JOIN 
                chat_members cm ON c.chat_id = cm.chat_id
            WHERE 
                cm.user_id = ?
                AND UNIX_TIMESTAMP(c.updated_at) > ?
        ";
        
        $chatUpdatesStmt = $conn->prepare($chatUpdatesSql);
        $chatUpdatesStmt->bind_param("ii", $userId, $lastUpdate);
        $chatUpdatesStmt->execute();
        $chatUpdatesResult = $chatUpdatesStmt->get_result();
        
        $chatUpdates = [];
        while ($chatUpdate = $chatUpdatesResult->fetch_assoc()) {
            $chatUpdates[] = $chatUpdate['chat_id'];
        }
        
        $chatUpdatesStmt->close();
        
        if (!empty($chatUpdates)) {
            $response['chat_updates'] = $chatUpdates;
        }
    }
    
    closeDbConnection($conn);
    
    // Повертаємо відповідь як JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    handlePollError($e->getMessage());
} 