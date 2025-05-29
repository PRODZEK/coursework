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

// Set timeout
$timeout = 30; // 30 seconds
$startTime = time();

// Update user status
$conn = getDbConnection();
$updateStatusSql = "UPDATE users SET status = 'online', last_seen = NOW() WHERE user_id = ?";
$updateStatusStmt = $conn->prepare($updateStatusSql);
$updateStatusStmt->bind_param("i", $userId);
$updateStatusStmt->execute();
$updateStatusStmt->close();
closeDbConnection($conn);

// Function to check for updates
function checkForUpdates($userId, $lastUpdate, $chatId = null) {
    $conn = getDbConnection();
    $updates = [];
    
    // If a specific chat is requested, check for new messages in that chat
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
                u.profile_picture as sender_profile_picture
            FROM 
                messages m
            JOIN 
                users u ON m.sender_id = u.user_id
            WHERE 
                m.chat_id = ? 
                AND UNIX_TIMESTAMP(m.sent_at) > ?
                AND m.sender_id != ?
            ORDER BY 
                m.sent_at ASC
        ";
        
        $messagesStmt = $conn->prepare($messagesSql);
        $messagesStmt->bind_param("iii", $chatId, $lastUpdate, $userId);
        $messagesStmt->execute();
        $messagesResult = $messagesStmt->get_result();
        
        $messages = [];
        while ($message = $messagesResult->fetch_assoc()) {
            $messages[] = $message;
            
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
        
        $messagesStmt->close();
        
        if (!empty($messages)) {
            $updates['messages'] = $messages;
        }
    } else {
        // Check for new messages in all chats
        $chatsSql = "
            SELECT 
                c.chat_id
            FROM 
                chats c
            JOIN 
                chat_members cm ON c.chat_id = cm.chat_id
            WHERE 
                cm.user_id = ?
        ";
        
        $chatsStmt = $conn->prepare($chatsSql);
        $chatsStmt->bind_param("i", $userId);
        $chatsStmt->execute();
        $chatsResult = $chatsStmt->get_result();
        
        $chatIds = [];
        while ($chat = $chatsResult->fetch_assoc()) {
            $chatIds[] = $chat['chat_id'];
        }
        
        $chatsStmt->close();
        
        if (!empty($chatIds)) {
            // Get the latest message in each chat
            $latestMessagesSql = "
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
                    c.chat_name,
                    c.chat_type
                FROM 
                    messages m
                JOIN 
                    users u ON m.sender_id = u.user_id
                JOIN 
                    chats c ON m.chat_id = c.chat_id
                WHERE 
                    m.chat_id IN (" . implode(',', $chatIds) . ")
                    AND UNIX_TIMESTAMP(m.sent_at) > ?
                    AND m.sender_id != ?
                ORDER BY 
                    m.sent_at ASC
            ";
            
            $latestMessagesStmt = $conn->prepare($latestMessagesSql);
            $latestMessagesStmt->bind_param("ii", $lastUpdate, $userId);
            $latestMessagesStmt->execute();
            $latestMessagesResult = $latestMessagesStmt->get_result();
            
            $chatMessages = [];
            while ($message = $latestMessagesResult->fetch_assoc()) {
                if (!isset($chatMessages[$message['chat_id']])) {
                    $chatMessages[$message['chat_id']] = [
                        'chat_id' => $message['chat_id'],
                        'chat_name' => $message['chat_name'],
                        'chat_type' => $message['chat_type'],
                        'messages' => []
                    ];
                }
                
                $chatMessages[$message['chat_id']]['messages'][] = $message;
                
                // Mark message as read if it's a direct chat (maintain unread for group chats)
                if ($message['chat_type'] === 'direct') {
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
            
            $latestMessagesStmt->close();
            
            if (!empty($chatMessages)) {
                $updates['chat_messages'] = array_values($chatMessages);
            }
        }
        
        // Check for user status changes
        $statusSql = "
            SELECT 
                u.user_id, 
                u.username, 
                u.status,
                u.last_seen
            FROM 
                users u
            JOIN (
                SELECT DISTINCT m.sender_id
                FROM chat_members cm
                JOIN chat_members cm2 ON cm.chat_id = cm2.chat_id
                JOIN users u ON cm2.user_id = u.user_id
                WHERE cm.user_id = ? AND cm2.user_id != ?
            ) AS contacts ON u.user_id = contacts.sender_id
            WHERE 
                UNIX_TIMESTAMP(u.last_seen) > ?
        ";
        
        $statusStmt = $conn->prepare($statusSql);
        $statusStmt->bind_param("iii", $userId, $userId, $lastUpdate);
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        
        $statusUpdates = [];
        while ($status = $statusResult->fetch_assoc()) {
            $statusUpdates[] = $status;
        }
        
        $statusStmt->close();
        
        if (!empty($statusUpdates)) {
            $updates['status_updates'] = $statusUpdates;
        }
        
        // Get unread count
        $unreadCountSql = "
            SELECT 
                COUNT(*) as unread_count
            FROM 
                messages m
            JOIN 
                message_status ms ON m.message_id = ms.message_id
            WHERE 
                ms.user_id = ? AND ms.is_read = 0
        ";
        
        $unreadCountStmt = $conn->prepare($unreadCountSql);
        $unreadCountStmt->bind_param("i", $userId);
        $unreadCountStmt->execute();
        $unreadCountResult = $unreadCountStmt->get_result();
        $unreadCount = $unreadCountResult->fetch_assoc()['unread_count'];
        $unreadCountStmt->close();
        
        $updates['unread_count'] = $unreadCount;
    }
    
    closeDbConnection($conn);
    
    return $updates;
}

// Long-polling loop
while (time() - $startTime < $timeout) {
    $updates = checkForUpdates($userId, $lastUpdate, $chatId);
    
    if (!empty($updates)) {
        // We have updates, return them
        $updates['timestamp'] = time();
        $updates['success'] = true;
        
        echo json_encode($updates);
        exit;
    }
    
    // No updates yet, wait a bit before checking again
    usleep(500000); // 500ms
}

// Timeout reached, return empty response
echo json_encode([
    'success' => true,
    'timestamp' => time(),
    'message' => 'No updates'
]); 