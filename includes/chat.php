<?php
/**
 * Chat Functions
 * 
 * This file contains functions related to chats and messages
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/user.php';

/**
 * Get chats for a user
 * 
 * @param int $userId User ID
 * @return array User's chats
 */
function getUserChats($userId) {
    $conn = getDbConnection();
    
    // Get all chats where the user is a member
    $sql = "
        SELECT 
            c.chat_id, 
            c.chat_name, 
            c.chat_type,
            c.created_at,
            c.updated_at,
            cm.role,
            (
                SELECT MAX(m.sent_at) 
                FROM messages m 
                WHERE m.chat_id = c.chat_id
            ) as last_message_time,
            (
                SELECT COUNT(*) 
                FROM messages m 
                LEFT JOIN message_status ms ON m.message_id = ms.message_id AND ms.user_id = ?
                WHERE m.chat_id = c.chat_id 
                AND (ms.is_read = 0 OR ms.status_id IS NULL)
                AND m.sender_id != ?
            ) as unread_count
        FROM 
            chats c
        JOIN 
            chat_members cm ON c.chat_id = cm.chat_id
        WHERE 
            cm.user_id = ?
        ORDER BY 
            last_message_time DESC, c.updated_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $chats = [];
    while ($chat = $result->fetch_assoc()) {
        // For direct chats, get the other user's details
        if ($chat['chat_type'] === 'direct') {
            $otherUserSql = "
                SELECT 
                    u.user_id, u.username, u.profile_picture, u.status, u.last_seen
                FROM 
                    chat_members cm
                JOIN 
                    users u ON cm.user_id = u.user_id
                WHERE 
                    cm.chat_id = ? AND cm.user_id != ?
            ";
            
            $otherUserStmt = $conn->prepare($otherUserSql);
            $otherUserStmt->bind_param("ii", $chat['chat_id'], $userId);
            $otherUserStmt->execute();
            $otherUserResult = $otherUserStmt->get_result();
            
            if ($otherUserResult->num_rows > 0) {
                $otherUser = $otherUserResult->fetch_assoc();
                $chat['other_user'] = $otherUser;
                
                // Use other user's name as chat name if not set
                if (empty($chat['chat_name'])) {
                    $chat['chat_name'] = $otherUser['username'];
                }
            }
            
            $otherUserStmt->close();
        } else {
            // For group chats, get member count
            $memberCountSql = "SELECT COUNT(*) as member_count FROM chat_members WHERE chat_id = ?";
            $memberCountStmt = $conn->prepare($memberCountSql);
            $memberCountStmt->bind_param("i", $chat['chat_id']);
            $memberCountStmt->execute();
            $memberCountResult = $memberCountStmt->get_result();
            $memberCount = $memberCountResult->fetch_assoc()['member_count'];
            $chat['member_count'] = $memberCount;
            $memberCountStmt->close();
        }
        
        // Get last message
        $lastMessageSql = "
            SELECT 
                m.message_id, 
                m.sender_id, 
                m.message_text,
                m.message_type,
                m.sent_at,
                u.username as sender_name
            FROM 
                messages m
            LEFT JOIN 
                users u ON m.sender_id = u.user_id
            WHERE 
                m.chat_id = ?
            ORDER BY 
                m.sent_at DESC
            LIMIT 1
        ";
        
        $lastMessageStmt = $conn->prepare($lastMessageSql);
        $lastMessageStmt->bind_param("i", $chat['chat_id']);
        $lastMessageStmt->execute();
        $lastMessageResult = $lastMessageStmt->get_result();
        
        if ($lastMessageResult->num_rows > 0) {
            $lastMessage = $lastMessageResult->fetch_assoc();
            $chat['last_message'] = $lastMessage;
        }
        
        $lastMessageStmt->close();
        
        $chats[] = $chat;
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    return $chats;
}

/**
 * Get chat by ID
 * 
 * @param int $chatId Chat ID
 * @param int $userId User ID requesting the chat
 * @return array|null Chat data or null if not found/not a member
 */
function getChatById($chatId, $userId) {
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
        return null;
    }
    
    $memberCheckStmt->close();
    
    // Get chat details
    $chatSql = "
        SELECT 
            c.chat_id, 
            c.chat_name, 
            c.chat_type,
            c.created_by,
            c.created_at,
            c.updated_at,
            cm.role
        FROM 
            chats c
        JOIN 
            chat_members cm ON c.chat_id = cm.chat_id AND cm.user_id = ?
        WHERE 
            c.chat_id = ?
    ";
    
    $chatStmt = $conn->prepare($chatSql);
    $chatStmt->bind_param("ii", $userId, $chatId);
    $chatStmt->execute();
    $chatResult = $chatStmt->get_result();
    
    if ($chatResult->num_rows === 0) {
        $chatStmt->close();
        closeDbConnection($conn);
        return null;
    }
    
    $chat = $chatResult->fetch_assoc();
    $chatStmt->close();
    
    // For direct chats, get the other user's details
    if ($chat['chat_type'] === 'direct') {
        $otherUserSql = "
            SELECT 
                u.user_id, u.username, u.profile_picture, u.status, u.last_seen
            FROM 
                chat_members cm
            JOIN 
                users u ON cm.user_id = u.user_id
            WHERE 
                cm.chat_id = ? AND cm.user_id != ?
        ";
        
        $otherUserStmt = $conn->prepare($otherUserSql);
        $otherUserStmt->bind_param("ii", $chatId, $userId);
        $otherUserStmt->execute();
        $otherUserResult = $otherUserStmt->get_result();
        
        if ($otherUserResult->num_rows > 0) {
            $otherUser = $otherUserResult->fetch_assoc();
            $chat['other_user'] = $otherUser;
            
            // Use other user's name as chat name if not set
            if (empty($chat['chat_name'])) {
                $chat['chat_name'] = $otherUser['username'];
            }
        }
        
        $otherUserStmt->close();
    } else {
        // For group chats, get members
        $membersSql = "
            SELECT 
                u.user_id, 
                u.username, 
                u.profile_picture, 
                u.status,
                cm.role
            FROM 
                chat_members cm
            JOIN 
                users u ON cm.user_id = u.user_id
            WHERE 
                cm.chat_id = ?
            ORDER BY 
                CASE WHEN cm.role = 'admin' THEN 0 ELSE 1 END,
                u.username
        ";
        
        $membersStmt = $conn->prepare($membersSql);
        $membersStmt->bind_param("i", $chatId);
        $membersStmt->execute();
        $membersResult = $membersStmt->get_result();
        
        $members = [];
        while ($member = $membersResult->fetch_assoc()) {
            $members[] = $member;
        }
        
        $chat['members'] = $members;
        $chat['member_count'] = count($members);
        
        $membersStmt->close();
    }
    
    closeDbConnection($conn);
    
    return $chat;
}

/**
 * Create a new direct chat between two users
 * 
 * @param int $userId1 First user ID
 * @param int $userId2 Second user ID
 * @return array Result with chat ID and success flag
 */
function createDirectChat($userId1, $userId2) {
    if ($userId1 === $userId2) {
        return ['success' => false, 'message' => 'Cannot create chat with yourself'];
    }
    
    $conn = getDbConnection();
    
    // Check if both users exist
    $userCheckSql = "SELECT user_id FROM users WHERE user_id IN (?, ?)";
    $userCheckStmt = $conn->prepare($userCheckSql);
    $userCheckStmt->bind_param("ii", $userId1, $userId2);
    $userCheckStmt->execute();
    $userCheckResult = $userCheckStmt->get_result();
    
    if ($userCheckResult->num_rows !== 2) {
        $userCheckStmt->close();
        closeDbConnection($conn);
        return ['success' => false, 'message' => 'One or both users do not exist'];
    }
    
    $userCheckStmt->close();
    
    // Check if a direct chat already exists between these users
    $chatCheckSql = "
        SELECT 
            c.chat_id
        FROM 
            chats c
        JOIN 
            chat_members cm1 ON c.chat_id = cm1.chat_id AND cm1.user_id = ?
        JOIN 
            chat_members cm2 ON c.chat_id = cm2.chat_id AND cm2.user_id = ?
        WHERE 
            c.chat_type = 'direct'
    ";
    
    $chatCheckStmt = $conn->prepare($chatCheckSql);
    $chatCheckStmt->bind_param("ii", $userId1, $userId2);
    $chatCheckStmt->execute();
    $chatCheckResult = $chatCheckStmt->get_result();
    
    if ($chatCheckResult->num_rows > 0) {
        // Chat already exists
        $chat = $chatCheckResult->fetch_assoc();
        $chatCheckStmt->close();
        closeDbConnection($conn);
        
        return [
            'success' => true,
            'message' => 'Chat already exists',
            'chat_id' => $chat['chat_id']
        ];
    }
    
    $chatCheckStmt->close();
    
    // Create new chat
    $conn->begin_transaction();
    
    try {
        // Create chat
        $createChatSql = "INSERT INTO chats (chat_type, created_by) VALUES ('direct', ?)";
        $createChatStmt = $conn->prepare($createChatSql);
        $createChatStmt->bind_param("i", $userId1);
        $createChatStmt->execute();
        $chatId = $conn->insert_id;
        $createChatStmt->close();
        
        // Add members
        $addMembersSql = "INSERT INTO chat_members (chat_id, user_id, role) VALUES (?, ?, 'member')";
        $addMembersStmt = $conn->prepare($addMembersSql);
        
        $addMembersStmt->bind_param("ii", $chatId, $userId1);
        $addMembersStmt->execute();
        
        $addMembersStmt->bind_param("ii", $chatId, $userId2);
        $addMembersStmt->execute();
        
        $addMembersStmt->close();
        
        $conn->commit();
        
        closeDbConnection($conn);
        
        return [
            'success' => true,
            'message' => 'Chat created successfully',
            'chat_id' => $chatId
        ];
    } catch (Exception $e) {
        $conn->rollback();
        closeDbConnection($conn);
        
        return [
            'success' => false,
            'message' => 'Failed to create chat: ' . $e->getMessage()
        ];
    }
}

/**
 * Create a new group chat
 * 
 * @param string $chatName Chat name
 * @param int $creatorId Creator user ID
 * @param array $memberIds IDs of members to add
 * @return array Result with chat ID and success flag
 */
function createGroupChat($chatName, $creatorId, $memberIds = []) {
    if (empty($chatName)) {
        return ['success' => false, 'message' => 'Chat name is required'];
    }
    
    $conn = getDbConnection();
    
    // Check if creator exists
    $userCheckSql = "SELECT user_id FROM users WHERE user_id = ?";
    $userCheckStmt = $conn->prepare($userCheckSql);
    $userCheckStmt->bind_param("i", $creatorId);
    $userCheckStmt->execute();
    $userCheckResult = $userCheckStmt->get_result();
    
    if ($userCheckResult->num_rows === 0) {
        $userCheckStmt->close();
        closeDbConnection($conn);
        return ['success' => false, 'message' => 'Creator user does not exist'];
    }
    
    $userCheckStmt->close();
    
    // Add creator to members if not already included
    if (!in_array($creatorId, $memberIds)) {
        $memberIds[] = $creatorId;
    }
    
    // Create new chat
    $conn->begin_transaction();
    
    try {
        // Create chat
        $createChatSql = "INSERT INTO chats (chat_name, chat_type, created_by) VALUES (?, 'group', ?)";
        $createChatStmt = $conn->prepare($createChatSql);
        $createChatStmt->bind_param("si", $chatName, $creatorId);
        $createChatStmt->execute();
        $chatId = $conn->insert_id;
        $createChatStmt->close();
        
        // Add members
        $addMembersSql = "INSERT INTO chat_members (chat_id, user_id, role) VALUES (?, ?, ?)";
        $addMembersStmt = $conn->prepare($addMembersSql);
        
        foreach ($memberIds as $memberId) {
            $role = ($memberId === $creatorId) ? 'admin' : 'member';
            $addMembersStmt->bind_param("iis", $chatId, $memberId, $role);
            $addMembersStmt->execute();
        }
        
        $addMembersStmt->close();
        
        $conn->commit();
        
        closeDbConnection($conn);
        
        return [
            'success' => true,
            'message' => 'Group chat created successfully',
            'chat_id' => $chatId
        ];
    } catch (Exception $e) {
        $conn->rollback();
        closeDbConnection($conn);
        
        return [
            'success' => false,
            'message' => 'Failed to create group chat: ' . $e->getMessage()
        ];
    }
}

/**
 * Send a message in a chat
 * 
 * @param int $chatId Chat ID
 * @param int $senderId Sender user ID
 * @param string $messageText Message text
 * @param string $messageType Message type (text, image, file)
 * @param string|null $fileUrl URL to file if message type is image or file
 * @return array Result with message ID and success flag
 */
function sendMessage($chatId, $senderId, $messageText, $messageType = 'text', $fileUrl = null) {
    $conn = getDbConnection();
    
    // Check if user is a member of the chat
    $memberCheckSql = "SELECT chat_id FROM chat_members WHERE chat_id = ? AND user_id = ?";
    $memberCheckStmt = $conn->prepare($memberCheckSql);
    $memberCheckStmt->bind_param("ii", $chatId, $senderId);
    $memberCheckStmt->execute();
    $memberCheckResult = $memberCheckStmt->get_result();
    
    if ($memberCheckResult->num_rows === 0) {
        // User is not a member of this chat
        $memberCheckStmt->close();
        closeDbConnection($conn);
        return ['success' => false, 'message' => 'You are not a member of this chat'];
    }
    
    $memberCheckStmt->close();
    
    // Send message
    $conn->begin_transaction();
    
    try {
        // Insert message
        $insertMessageSql = "
            INSERT INTO messages 
                (chat_id, sender_id, message_text, message_type, file_url) 
            VALUES 
                (?, ?, ?, ?, ?)
        ";
        $insertMessageStmt = $conn->prepare($insertMessageSql);
        $insertMessageStmt->bind_param("iisss", $chatId, $senderId, $messageText, $messageType, $fileUrl);
        $insertMessageStmt->execute();
        $messageId = $conn->insert_id;
        $insertMessageStmt->close();
        
        // Update chat's updated_at timestamp
        $updateChatSql = "UPDATE chats SET updated_at = NOW() WHERE chat_id = ?";
        $updateChatStmt = $conn->prepare($updateChatSql);
        $updateChatStmt->bind_param("i", $chatId);
        $updateChatStmt->execute();
        $updateChatStmt->close();
        
        // Create message status entries for all members except sender
        $getMembersSql = "SELECT user_id FROM chat_members WHERE chat_id = ? AND user_id != ?";
        $getMembersStmt = $conn->prepare($getMembersSql);
        $getMembersStmt->bind_param("ii", $chatId, $senderId);
        $getMembersStmt->execute();
        $getMembersResult = $getMembersStmt->get_result();
        
        if ($getMembersResult->num_rows > 0) {
            $insertStatusSql = "INSERT INTO message_status (message_id, user_id, is_read) VALUES (?, ?, 0)";
            $insertStatusStmt = $conn->prepare($insertStatusSql);
            
            while ($member = $getMembersResult->fetch_assoc()) {
                $insertStatusStmt->bind_param("ii", $messageId, $member['user_id']);
                $insertStatusStmt->execute();
            }
            
            $insertStatusStmt->close();
        }
        
        $getMembersStmt->close();
        
        $conn->commit();
        
        // Get the message with sender data
        $getMessageSql = "
            SELECT 
                m.message_id, 
                m.chat_id, 
                m.sender_id, 
                m.message_text, 
                m.message_type, 
                m.file_url, 
                m.sent_at,
                m.is_read,
                u.username as sender_name,
                u.profile_picture as sender_profile_picture
            FROM 
                messages m
            JOIN 
                users u ON m.sender_id = u.user_id
            WHERE 
                m.message_id = ?
        ";
        
        $getMessageStmt = $conn->prepare($getMessageSql);
        $getMessageStmt->bind_param("i", $messageId);
        $getMessageStmt->execute();
        $getMessageResult = $getMessageStmt->get_result();
        $message = $getMessageResult->fetch_assoc();
        $getMessageStmt->close();
        
        closeDbConnection($conn);
        
        return [
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $messageId,
            'data' => $message
        ];
    } catch (Exception $e) {
        $conn->rollback();
        closeDbConnection($conn);
        
        return [
            'success' => false,
            'message' => 'Failed to send message: ' . $e->getMessage()
        ];
    }
}

/**
 * Get messages in a chat
 * 
 * @param int $chatId Chat ID
 * @param int $userId User ID requesting the messages
 * @param int $limit Number of messages to retrieve
 * @param int $offset Offset for pagination
 * @return array Result with messages
 */
function getChatMessages($chatId, $userId, $limit = 20, $offset = 0) {
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
    
    // Get messages
    $messagesSql = "
        SELECT 
            m.message_id, 
            m.chat_id, 
            m.sender_id, 
            m.message_text, 
            m.message_type, 
            m.file_url, 
            m.sent_at,
            COALESCE(ms.is_read, 1) as is_read,
            u.username as sender_name,
            u.profile_picture as sender_profile_picture
        FROM 
            messages m
        JOIN 
            users u ON m.sender_id = u.user_id
        LEFT JOIN 
            message_status ms ON m.message_id = ms.message_id AND ms.user_id = ?
        WHERE 
            m.chat_id = ?
        ORDER BY 
            m.sent_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $messagesStmt = $conn->prepare($messagesSql);
    $messagesStmt->bind_param("iiii", $userId, $chatId, $limit, $offset);
    $messagesStmt->execute();
    $messagesResult = $messagesStmt->get_result();
    
    $messages = [];
    while ($message = $messagesResult->fetch_assoc()) {
        $messages[] = $message;
    }
    
    $messagesStmt->close();
    
    // Mark messages as read
    if (!empty($messages)) {
        $updateReadStatusSql = "
            UPDATE message_status 
            SET is_read = 1, read_at = NOW() 
            WHERE 
                message_id IN (
                    SELECT message_id 
                    FROM messages 
                    WHERE chat_id = ? AND sender_id != ?
                ) 
                AND user_id = ? 
                AND is_read = 0
        ";
        
        $updateReadStatusStmt = $conn->prepare($updateReadStatusSql);
        $updateReadStatusStmt->bind_param("iii", $chatId, $userId, $userId);
        $updateReadStatusStmt->execute();
        $updateReadStatusStmt->close();
    }
    
    closeDbConnection($conn);
    
    // Reverse messages to show oldest first
    $messages = array_reverse($messages);
    
    return [
        'success' => true,
        'messages' => $messages,
        'has_more' => count($messages) === $limit
    ];
}

/**
 * Get number of unread messages for a user
 * 
 * @param int $userId User ID
 * @return int Number of unread messages
 */
function getUnreadMessagesCount($userId) {
    $conn = getDbConnection();
    
    $sql = "
        SELECT 
            COUNT(*) as unread_count
        FROM 
            messages m
        JOIN 
            message_status ms ON m.message_id = ms.message_id
        WHERE 
            ms.user_id = ? AND ms.is_read = 0
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    closeDbConnection($conn);
    
    return $row['unread_count'];
} 