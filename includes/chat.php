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
            UNIX_TIMESTAMP(c.created_at) as created_at_timestamp,
            DATE_FORMAT(c.created_at, '%Y-%m-%dT%H:%i:%s') as created_at,
            UNIX_TIMESTAMP(c.updated_at) as updated_at_timestamp,
            DATE_FORMAT(c.updated_at, '%Y-%m-%dT%H:%i:%s') as updated_at,
            cm.role,
            (
                SELECT UNIX_TIMESTAMP(MAX(m.sent_at))
                FROM messages m 
                WHERE m.chat_id = c.chat_id
            ) as last_message_timestamp,
            (
                SELECT DATE_FORMAT(MAX(m.sent_at), '%Y-%m-%dT%H:%i:%s')
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
            last_message_timestamp DESC, updated_at_timestamp DESC
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
                    u.user_id, u.username, u.profile_picture, u.status, 
                    UNIX_TIMESTAMP(u.last_seen) as last_seen_timestamp,
                    DATE_FORMAT(u.last_seen, '%Y-%m-%dT%H:%i:%s') as last_seen
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
                UNIX_TIMESTAMP(m.sent_at) as sent_at_timestamp,
                DATE_FORMAT(m.sent_at, '%Y-%m-%dT%H:%i:%s') as sent_at,
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
        // ---- START sendMessage DEBUG LOGS ----
        error_log("[sendMessage] Attempting to bind and insert. ChatID: $chatId, SenderID: $senderId, Text: '$messageText', Type: '$messageType', FileURL: '$fileUrl'");
        // ---- END sendMessage DEBUG LOGS ----
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
                UNIX_TIMESTAMP(m.sent_at) as sent_at_timestamp,
                DATE_FORMAT(m.sent_at, '%Y-%m-%dT%H:%i:%s') as sent_at,
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
        
        // ---- START sendMessage DEBUG LOGS ----
        error_log("[sendMessage] Message fetched after insert: " . json_encode($message));
        // ---- END sendMessage DEBUG LOGS ----

        // Debug log
        error_log("Message sent: ID=$messageId, Type=$messageType, FileURL=" . ($fileUrl ?? 'none'));
        
        // Make sure file_url is set correctly in the message data
        if (!empty($fileUrl) && (!isset($message['file_url']) || $message['file_url'] === null || $message['file_url'] === '')) {
            error_log("[sendMessage] file_url was empty or null in fetched message. Original fileUrl: '$fileUrl'. Manually setting it and updating DB.");
            $message['file_url'] = $fileUrl;
            
            // Update the message in the database with the correct file_url
            $updateFileUrlSql = "UPDATE messages SET file_url = ? WHERE message_id = ?";
            $updateFileUrlStmt = $conn->prepare($updateFileUrlSql);
            $updateFileUrlStmt->bind_param("si", $fileUrl, $messageId);
            $updateFileUrlStmt->execute();
            $updateFileUrlStmt->close();
            
            error_log("Updated message $messageId with file URL: $fileUrl");
        }
        
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
        
        error_log("Error sending message: " . $e->getMessage());
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
            UNIX_TIMESTAMP(m.sent_at) as sent_at_timestamp,
            DATE_FORMAT(m.sent_at, '%Y-%m-%dT%H:%i:%s') as sent_at,
            u.username as sender_name,
            u.profile_picture as sender_profile_picture,
            -- For outgoing messages, check if all recipients have read it
            IF(m.sender_id = ?, 
                (SELECT MIN(ms_other.is_read) 
                 FROM message_status ms_other 
                 JOIN chat_members cm_other ON ms_other.user_id = cm_other.user_id
                 WHERE ms_other.message_id = m.message_id AND cm_other.chat_id = m.chat_id AND ms_other.user_id != m.sender_id)
                , 
                (SELECT ms_own.is_read FROM message_status ms_own WHERE ms_own.message_id = m.message_id AND ms_own.user_id = ?)
            ) as is_read_by_recipient -- Renamed for clarity, 1 if read by all, 0 if at least one unread, NULL if no other recipients or not applicable
        FROM 
            messages m
        JOIN 
            users u ON m.sender_id = u.user_id
        WHERE 
            m.chat_id = ?
        ORDER BY 
            m.sent_at ASC
        LIMIT ? OFFSET ?
    ";
    
    $messagesStmt = $conn->prepare($messagesSql);
    // Parameters: sender_id (for IF), user_id (for own status), chat_id, limit, offset
    $messagesStmt->bind_param("iiiiii", $userId, $userId, $chatId, $limit, $offset);
    $messagesStmt->execute();
    $messagesResult = $messagesStmt->get_result();
    
    $messages = [];
    while ($message = $messagesResult->fetch_assoc()) {
        // Convert is_read_by_recipient to a simple boolean for the frontend
        // If it's an outgoing message, is_read means all recipients have read it.
        // If it's an incoming message, is_read means the current user has read it.
        $message['is_read'] = (bool) $message['is_read_by_recipient'];
        unset($message['is_read_by_recipient']); // Clean up the temporary field

        error_log("[getChatMessages] Fetched message row: " . json_encode($message));
        $messages[] = $message;
    }
    
    $messagesStmt->close();
    
    // Mark incoming messages as read for the current user
    if (!empty($messages)) {
        $messageIdsToMark = [];
        foreach ($messages as $msg) {
            if ($msg['sender_id'] != $userId && !$msg['is_read']) { // Only mark unread incoming messages
                $messageIdsToMark[] = $msg['message_id'];
            }
        }

        if (!empty($messageIdsToMark)) {
            $placeholders = implode(',', array_fill(0, count($messageIdsToMark), '?'));
            $types = str_repeat('i', count($messageIdsToMark));

        $updateReadStatusSql = "
            UPDATE message_status 
            SET is_read = 1, read_at = NOW() 
            WHERE 
                    message_id IN ({$placeholders}) 
                AND user_id = ? 
                AND is_read = 0
        ";
        
        $updateReadStatusStmt = $conn->prepare($updateReadStatusSql);
            $params = array_merge($messageIdsToMark, [$userId]);
            $updateReadStatusStmt->bind_param($types . 'i', ...$params);
        $updateReadStatusStmt->execute();
        $updateReadStatusStmt->close();
        }
    }
    
    closeDbConnection($conn);
    
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

/**
 * Delete a chat
 * 
 * @param int $chatId Chat ID to delete
 * @param int $userId User ID requesting deletion
 * @return array Result with success flag and message
 */
function deleteChat($chatId, $userId) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if user is a member of the chat
        $memberCheckSql = "SELECT chat_id, role FROM chat_members WHERE chat_id = ? AND user_id = ?";
        $memberCheckStmt = $conn->prepare($memberCheckSql);
        $memberCheckStmt->bind_param("ii", $chatId, $userId);
        $memberCheckStmt->execute();
        $memberCheckResult = $memberCheckStmt->get_result();
        
        if ($memberCheckResult->num_rows === 0) {
            // User is not a member of this chat
            $memberCheckStmt->close();
            $conn->rollback();
            closeDbConnection($conn);
            return ['success' => false, 'message' => 'You are not a member of this chat'];
        }
        
        $memberData = $memberCheckResult->fetch_assoc();
        $memberRole = $memberData['role'];
        $memberCheckStmt->close();
        
        // Get all member IDs before deleting the chat for notification purposes
        $membersSql = "SELECT user_id FROM chat_members WHERE chat_id = ?";
        $membersStmt = $conn->prepare($membersSql);
        $membersStmt->bind_param("i", $chatId);
        $membersStmt->execute();
        $membersResult = $membersStmt->get_result();
        
        $memberIds = [];
        while ($member = $membersResult->fetch_assoc()) {
            $memberIds[] = $member['user_id'];
        }
        $membersStmt->close();
        
        // For group chats, only admin can delete the chat completely
        $chatTypeSql = "SELECT chat_type FROM chats WHERE chat_id = ?";
        $chatTypeStmt = $conn->prepare($chatTypeSql);
        $chatTypeStmt->bind_param("i", $chatId);
        $chatTypeStmt->execute();
        $chatTypeResult = $chatTypeStmt->get_result();
        $chatData = $chatTypeResult->fetch_assoc();
        $chatTypeStmt->close();
        
        $isGroupChat = $chatData && $chatData['chat_type'] === 'group';
        
        if ($isGroupChat && $memberRole !== 'admin') {
            // For group chats, if user is not admin, just remove them from the chat
            $leaveGroupSql = "DELETE FROM chat_members WHERE chat_id = ? AND user_id = ?";
            $leaveGroupStmt = $conn->prepare($leaveGroupSql);
            $leaveGroupStmt->bind_param("ii", $chatId, $userId);
            $leaveGroupStmt->execute();
            $leaveGroupStmt->close();
            
            // Record this user as having "deleted" the chat from their view
            $recordDeletedSql = "INSERT INTO deleted_chat_members (chat_id, user_id) VALUES (?, ?)";
            $recordDeletedStmt = $conn->prepare($recordDeletedSql);
            $recordDeletedStmt->bind_param("ii", $chatId, $userId);
            $recordDeletedStmt->execute();
            $recordDeletedStmt->close();
            
            $conn->commit();
            closeDbConnection($conn);
            
            return ['success' => true, 'message' => 'You have left the group chat'];
        }
        
        // For direct chats or if user is admin in group chat, delete everything

        // First, record the chat as deleted and store all members who need to be notified
        $recordDeletedChatSql = "INSERT INTO deleted_chats (chat_id, deleted_by) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE deleted_by = ?, deleted_at = CURRENT_TIMESTAMP";
        $recordDeletedChatStmt = $conn->prepare($recordDeletedChatSql);
        $recordDeletedChatStmt->bind_param("iii", $chatId, $userId, $userId);
        $recordDeletedChatStmt->execute();
        $recordDeletedChatStmt->close();
        
        // Record all members as needing notification about this deletion
        $recordMembersSql = "INSERT INTO deleted_chat_members (chat_id, user_id) VALUES (?, ?)";
        $recordMembersStmt = $conn->prepare($recordMembersSql);
        
        foreach ($memberIds as $memberId) {
            if ($memberId != $userId) { // We don't need to notify the user who deleted the chat
                $recordMembersStmt->bind_param("ii", $chatId, $memberId);
                $recordMembersStmt->execute();
            }
        }
        $recordMembersStmt->close();
        
        // 1. Delete messages in the chat
        $deleteMessagesSql = "DELETE FROM messages WHERE chat_id = ?";
        $deleteMessagesStmt = $conn->prepare($deleteMessagesSql);
        $deleteMessagesStmt->bind_param("i", $chatId);
        $deleteMessagesStmt->execute();
        $deleteMessagesStmt->close();
        
        // 2. Delete message statuses for this chat
        $deleteStatusesSql = "DELETE ms FROM message_status ms 
                             JOIN messages m ON ms.message_id = m.message_id 
                             WHERE m.chat_id = ?";
        $deleteStatusesStmt = $conn->prepare($deleteStatusesSql);
        $deleteStatusesStmt->bind_param("i", $chatId);
        $deleteStatusesStmt->execute();
        $deleteStatusesStmt->close();
        
        // 3. Delete chat members
        $deleteMembersSql = "DELETE FROM chat_members WHERE chat_id = ?";
        $deleteMembersStmt = $conn->prepare($deleteMembersSql);
        $deleteMembersStmt->bind_param("i", $chatId);
        $deleteMembersStmt->execute();
        $deleteMembersStmt->close();
        
        // 4. Delete the chat
        $deleteChatSql = "DELETE FROM chats WHERE chat_id = ?";
        $deleteChatStmt = $conn->prepare($deleteChatSql);
        $deleteChatStmt->bind_param("i", $chatId);
        $deleteChatStmt->execute();
        $deleteChatStmt->close();
        
        // Commit transaction
        $conn->commit();
        closeDbConnection($conn);
        
        return ['success' => true, 'message' => 'Chat deleted successfully'];
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        closeDbConnection($conn);
        return ['success' => false, 'message' => 'Failed to delete chat: ' . $e->getMessage()];
    }
}

/**
 * Upload a file for a chat message
 * 
 * @param array $file File data from $_FILES
 * @param int $chatId Chat ID
 * @param int $userId User ID
 * @return array Result with success flag and file info
 */
function uploadChatFile($file, $chatId, $userId) {
    // Check if user is a member of the chat
    $conn = getDbConnection();
    
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
    closeDbConnection($conn);
    
    // Validate file size
    $maxSize = 50 * 1024 * 1024; // 50 MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds the maximum limit of 50 MB'];
    }
    
    // Get file info
    $originalName = sanitizeInput($file['name']);
    $tmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];
    
    // Generate unique filename
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = uniqid('chat_' . $chatId . '_' . $userId . '_') . '.' . $extension;
    
    // Determine file type category
    $messageType = getMessageTypeFromMimeType($fileType, $extension);
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../assets/uploads/chat_files/' . $chatId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $targetPath = $uploadDir . '/' . $filename;
    $relativeUrl = 'assets/uploads/chat_files/' . $chatId . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($tmpPath, $targetPath)) {
        // For videos, generate thumbnail
        $thumbnailUrl = null;
        if ($messageType === 'video') {
            $thumbnailUrl = generateVideoThumbnail($targetPath, $uploadDir, $filename);
            if ($thumbnailUrl) {
                $thumbnailUrl = 'assets/uploads/chat_files/' . $chatId . '/' . $thumbnailUrl;
            }
        }
        
        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_url' => $relativeUrl,
            'original_name' => $originalName,
            'type' => $messageType,
            'size' => $fileSize,
            'thumbnail_url' => $thumbnailUrl
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Determine message type from MIME type
 * 
 * @param string $mimeType MIME type
 * @param string $extension File extension
 * @return string Message type (image, video, audio, file)
 */
function getMessageTypeFromMimeType($mimeType, $extension) {
    // Primary check by MIME type
    if (strpos($mimeType, 'image/') === 0) {
        return 'image';
    } elseif (strpos($mimeType, 'video/') === 0) {
        return 'video';
    } elseif (strpos($mimeType, 'audio/') === 0) {
        return 'audio';
    }
    
    // Secondary check by extension
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
    $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
    
    $extension = strtolower($extension);
    
    if (in_array($extension, $imageExtensions)) {
        return 'image';
    } elseif (in_array($extension, $videoExtensions)) {
        return 'video';
    } elseif (in_array($extension, $audioExtensions)) {
        return 'audio';
    }
    
    // Default to generic file
    return 'file';
}

/**
 * Generate a thumbnail for a video file
 * 
 * @param string $videoPath Path to video file
 * @param string $outputDir Directory to save thumbnail
 * @param string $videoFilename Video filename
 * @return string|null Thumbnail filename or null on failure
 */
function generateVideoThumbnail($videoPath, $outputDir, $videoFilename) {
    // Check if FFmpeg is available
    $ffmpegPath = 'ffmpeg'; // Assumes ffmpeg is in PATH
    
    // If we want to explicitly set the path, uncomment and modify this line:
    // $ffmpegPath = 'C:/ffmpeg/bin/ffmpeg.exe'; // Windows example
    
    // Generate thumbnail filename
    $thumbnailFilename = 'thumb_' . pathinfo($videoFilename, PATHINFO_FILENAME) . '.jpg';
    $thumbnailPath = $outputDir . '/' . $thumbnailFilename;
    
    // Command to extract a frame at 1 second
    $command = sprintf(
        '%s -i %s -ss 00:00:01.000 -vframes 1 %s 2>&1',
        escapeshellarg($ffmpegPath),
        escapeshellarg($videoPath),
        escapeshellarg($thumbnailPath)
    );
    
    // Execute the command
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    
    // Check if thumbnail was created
    if ($returnVar === 0 && file_exists($thumbnailPath)) {
        return $thumbnailFilename;
    }
    
    return null;
}

/**
 * Get file upload error message
 * 
 * @param int $errorCode Error code from $_FILES['file']['error']
 * @return string Human-readable error message
 */
function getFileUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
} 