<?php
/**
 * Message Model
 * Handles message-related database operations
 */
class Message {
    private $db;
    private $messageTable = 'messages';
    private $statusTable = 'message_status';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new message
     * 
     * @param array $messageData Message data
     * @return int|bool Message ID on success, false on failure
     */
    public function create(array $messageData) {
        $requiredFields = ['chat_id', 'sender_id', 'message_type', 'content'];
        foreach ($requiredFields as $field) {
            if (!isset($messageData[$field])) {
                return false;
            }
        }
        
        try {
            $this->db->beginTransaction();
            
            // Create the message
            $sql = "INSERT INTO {$this->messageTable} 
                    (chat_id, sender_id, reply_to_message_id, message_type, content, file_path, thumbnail_path, is_forwarded, forwarded_from_id) 
                    VALUES (:chat_id, :sender_id, :reply_to_message_id, :message_type, :content, :file_path, :thumbnail_path, :is_forwarded, :forwarded_from_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'chat_id' => $messageData['chat_id'],
                'sender_id' => $messageData['sender_id'],
                'reply_to_message_id' => $messageData['reply_to_message_id'] ?? null,
                'message_type' => $messageData['message_type'],
                'content' => $messageData['content'],
                'file_path' => $messageData['file_path'] ?? null,
                'thumbnail_path' => $messageData['thumbnail_path'] ?? null,
                'is_forwarded' => $messageData['is_forwarded'] ?? false,
                'forwarded_from_id' => $messageData['forwarded_from_id'] ?? null
            ]);
            
            $messageId = $this->db->lastInsertId();
            
            // Update chat's updated_at timestamp
            $updateChatSql = "UPDATE chats SET updated_at = NOW() WHERE chat_id = :chat_id";
            $updateChatStmt = $this->db->prepare($updateChatSql);
            $updateChatStmt->execute(['chat_id' => $messageData['chat_id']]);
            
            // Create message status entries for all participants (except sender)
            $this->createMessageStatus($messageId, $messageData['chat_id'], $messageData['sender_id']);
            
            $this->db->commit();
            
            return $messageId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Find message by ID
     * 
     * @param int $messageId Message ID
     * @return array|bool Message data on success, false on failure
     */
    public function findById(int $messageId) {
        $sql = "SELECT m.*, 
                u.username as sender_username, u.full_name as sender_name, u.profile_image as sender_image,
                r.content as reply_content, r.message_type as reply_type,
                ru.username as reply_username, ru.full_name as reply_name
                FROM {$this->messageTable} m
                LEFT JOIN users u ON m.sender_id = u.user_id
                LEFT JOIN {$this->messageTable} r ON m.reply_to_message_id = r.message_id
                LEFT JOIN users ru ON r.sender_id = ru.user_id
                WHERE m.message_id = :message_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get messages for a chat
     * 
     * @param int $chatId Chat ID
     * @param int $limit Number of messages to retrieve
     * @param int $offset Offset for pagination
     * @return array List of messages
     */
    public function getChatMessages(int $chatId, int $limit = 50, int $offset = 0) {
        $sql = "SELECT m.*, 
                u.username as sender_username, u.full_name as sender_name, u.profile_image as sender_image,
                r.content as reply_content, r.message_type as reply_type,
                ru.username as reply_username, ru.full_name as reply_name
                FROM {$this->messageTable} m
                LEFT JOIN users u ON m.sender_id = u.user_id
                LEFT JOIN {$this->messageTable} r ON m.reply_to_message_id = r.message_id
                LEFT JOIN users ru ON r.sender_id = ru.user_id
                WHERE m.chat_id = :chat_id
                ORDER BY m.sent_at DESC
                LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':chat_id', $chatId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll();
            
            // Get message status for each message
            foreach ($messages as &$message) {
                $message['status'] = $this->getMessageStatus($message['message_id']);
            }
            
            return array_reverse($messages); // Reverse to get chronological order
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Update a message
     * 
     * @param int $messageId Message ID
     * @param string $content New content
     * @param int $userId User ID (for verification)
     * @return bool True on success, false on failure
     */
    public function update(int $messageId, string $content, int $userId) {
        // Check if the user is the sender of the message
        $sql = "SELECT sender_id FROM {$this->messageTable} WHERE message_id = :message_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            $message = $stmt->fetch();
            
            if (!$message || $message['sender_id'] != $userId) {
                return false;
            }
            
            // Update the message
            $updateSql = "UPDATE {$this->messageTable} SET content = :content, is_edited = true, edited_at = NOW() WHERE message_id = :message_id";
            $updateStmt = $this->db->prepare($updateSql);
            return $updateStmt->execute([
                'content' => $content,
                'message_id' => $messageId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Delete a message (soft delete)
     * 
     * @param int $messageId Message ID
     * @param int $userId User ID (for verification)
     * @return bool True on success, false on failure
     */
    public function delete(int $messageId, int $userId) {
        // Check if the user is the sender of the message
        $sql = "SELECT sender_id, chat_id FROM {$this->messageTable} WHERE message_id = :message_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            $message = $stmt->fetch();
            
            if (!$message || $message['sender_id'] != $userId) {
                // Check if the user is an admin or owner of the chat
                if ($message) {
                    $chatSql = "SELECT role FROM chat_participants 
                                WHERE chat_id = :chat_id AND user_id = :user_id 
                                AND role IN ('admin', 'owner')";
                    $chatStmt = $this->db->prepare($chatSql);
                    $chatStmt->execute([
                        'chat_id' => $message['chat_id'],
                        'user_id' => $userId
                    ]);
                    
                    if (!$chatStmt->fetch()) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            
            // Soft delete the message
            $deleteSql = "UPDATE {$this->messageTable} SET is_deleted = true WHERE message_id = :message_id";
            $deleteStmt = $this->db->prepare($deleteSql);
            return $deleteStmt->execute([
                'message_id' => $messageId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Create message status entries for all participants
     * 
     * @param int $messageId Message ID
     * @param int $chatId Chat ID
     * @param int $senderId Sender ID (to exclude from status entries)
     * @return bool True on success, false on failure
     */
    private function createMessageStatus(int $messageId, int $chatId, int $senderId) {
        // Get all participants except the sender
        $sql = "SELECT user_id FROM chat_participants 
                WHERE chat_id = :chat_id AND user_id != :sender_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'chat_id' => $chatId,
                'sender_id' => $senderId
            ]);
            
            $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Create message status entries for each participant
            foreach ($participants as $userId) {
                $statusSql = "INSERT INTO {$this->statusTable} 
                            (message_id, user_id, delivered_at) 
                            VALUES (:message_id, :user_id, NOW())";
                $statusStmt = $this->db->prepare($statusSql);
                $statusStmt->execute([
                    'message_id' => $messageId,
                    'user_id' => $userId
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get status of a message
     * 
     * @param int $messageId Message ID
     * @return array Status information
     */
    public function getMessageStatus(int $messageId) {
        $sql = "SELECT ms.*, u.username, u.full_name 
                FROM {$this->statusTable} ms
                JOIN users u ON ms.user_id = u.user_id
                WHERE ms.message_id = :message_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['message_id' => $messageId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Mark messages as read
     * 
     * @param int $chatId Chat ID
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public function markAsRead(int $chatId, int $userId) {
        $sql = "UPDATE {$this->statusTable} ms
                JOIN {$this->messageTable} m ON ms.message_id = m.message_id
                SET ms.is_read = true, ms.read_at = NOW()
                WHERE m.chat_id = :chat_id
                AND ms.user_id = :user_id
                AND ms.is_read = false";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'chat_id' => $chatId,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get unread message count for a user in a specific chat
     * 
     * @param int $chatId Chat ID
     * @param int $userId User ID
     * @return int Count of unread messages
     */
    public function getUnreadCount(int $chatId, int $userId) {
        $sql = "SELECT COUNT(*) FROM {$this->statusTable} ms
                JOIN {$this->messageTable} m ON ms.message_id = m.message_id
                WHERE m.chat_id = :chat_id
                AND ms.user_id = :user_id
                AND ms.is_read = false";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'chat_id' => $chatId,
                'user_id' => $userId
            ]);
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get total unread message count for a user across all chats
     * 
     * @param int $userId User ID
     * @return array Array with chat_id => unread_count pairs
     */
    public function getTotalUnreadCounts(int $userId) {
        $sql = "SELECT m.chat_id, COUNT(*) as unread_count
                FROM {$this->statusTable} ms
                JOIN {$this->messageTable} m ON ms.message_id = m.message_id
                WHERE ms.user_id = :user_id
                AND ms.is_read = false
                GROUP BY m.chat_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            $result = [];
            while ($row = $stmt->fetch()) {
                $result[$row['chat_id']] = (int)$row['unread_count'];
            }
            
            return $result;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Forward a message to another chat
     * 
     * @param int $messageId Original message ID
     * @param int $toChatId Destination chat ID
     * @param int $userId User ID (forwarder)
     * @return int|bool New message ID on success, false on failure
     */
    public function forwardMessage(int $messageId, int $toChatId, int $userId) {
        // Get original message
        $originalMessage = $this->findById($messageId);
        
        if (!$originalMessage) {
            return false;
        }
        
        // Create new message with forwarded flag
        return $this->create([
            'chat_id' => $toChatId,
            'sender_id' => $userId,
            'message_type' => $originalMessage['message_type'],
            'content' => $originalMessage['content'],
            'file_path' => $originalMessage['file_path'],
            'thumbnail_path' => $originalMessage['thumbnail_path'],
            'is_forwarded' => true,
            'forwarded_from_id' => $originalMessage['sender_id']
        ]);
    }
} 