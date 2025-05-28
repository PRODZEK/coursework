<?php
/**
 * Real-time Updates API using Server-Sent Events (SSE)
 */
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/EventStream.php';
require_once __DIR__ . '/models/Message.php';
require_once __DIR__ . '/models/Chat.php';

// Set unlimited execution time (if possible)
@set_time_limit(0);

// Start session to get user ID
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Create model instances
$messageModel = new Message();
$chatModel = new Chat();

// Get user's chats
$chats = $chatModel->getUserChats($userId);
$chatIds = array_column($chats, 'chat_id');

// Start the event stream
EventStream::start();

// Send initial data to the client
$initialData = [
    'user_id' => $userId,
    'chats' => $chats,
    'unread_counts' => $messageModel->getTotalUnreadCounts($userId)
];

EventStream::send('init', $initialData);

// Check for updates every few seconds
$lastCheck = time();
$checkInterval = 2; // Check every 2 seconds
$keepAliveInterval = 30; // Send keep-alive every 30 seconds
$lastKeepAlive = time();

// Get the last message ID for each chat
$lastMessageIds = [];
foreach ($chatIds as $chatId) {
    $messages = $messageModel->getChatMessages($chatId, 1, 0);
    if (!empty($messages)) {
        $lastMessageIds[$chatId] = $messages[0]['message_id'];
    }
}

// Keep the connection open and check for updates
while (true) {
    // Break if client disconnects
    if (connection_aborted()) {
        break;
    }
    
    // Send keep-alive ping
    if (time() - $lastKeepAlive >= $keepAliveInterval) {
        EventStream::keepAlive();
        $lastKeepAlive = time();
    }
    
    // Check for updates if enough time has passed
    if (time() - $lastCheck >= $checkInterval) {
        $updates = [];
        
        // Check for new messages in each chat
        foreach ($chatIds as $chatId) {
            $lastId = $lastMessageIds[$chatId] ?? 0;
            
            // Get new messages since the last check
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT m.*, 
                       u.username as sender_username, u.full_name as sender_name, u.profile_image as sender_image
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.user_id
                WHERE m.chat_id = :chat_id
                AND m.message_id > :last_id
                ORDER BY m.sent_at ASC
            ");
            $stmt->execute(['chat_id' => $chatId, 'last_id' => $lastId]);
            $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($newMessages)) {
                // Update the last seen message ID
                $lastMessageIds[$chatId] = end($newMessages)['message_id'];
                
                // Add to updates
                foreach ($newMessages as $message) {
                    // Don't send own messages back to the sender
                    if ($message['sender_id'] != $userId) {
                        $updates[] = [
                            'type' => 'new_message',
                            'chat_id' => $chatId,
                            'message' => $message
                        ];
                    }
                }
            }
        }
        
        // Check for online status changes of chat participants
        $participantsQuery = $db->prepare("
            SELECT u.user_id, u.username, u.full_name, u.is_online, u.last_seen
            FROM users u
            JOIN chat_participants cp ON u.user_id = cp.user_id
            WHERE cp.chat_id IN (" . implode(',', $chatIds) . ")
            AND u.user_id != :user_id
            AND (u.last_seen >= :last_check OR u.is_online = 1)
        ");
        $participantsQuery->execute([
            'user_id' => $userId,
            'last_check' => date('Y-m-d H:i:s', $lastCheck)
        ]);
        $onlineUpdates = $participantsQuery->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($onlineUpdates)) {
            foreach ($onlineUpdates as $update) {
                $updates[] = [
                    'type' => 'online_status',
                    'user' => $update
                ];
            }
        }
        
        // Send updates to the client if there are any
        if (!empty($updates)) {
            EventStream::send('updates', $updates);
        }
        
        $lastCheck = time();
    }
    
    // Sleep to reduce CPU usage
    usleep(500000); // 0.5 seconds
}

// Close the event stream when done
EventStream::end(); 