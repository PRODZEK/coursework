<?php
/**
 * Chat Model
 * Handles operations related to chats and chat participants
 */
class Chat {
    private $db;
    private $chatTable = 'chats';
    private $participantTable = 'chat_participants';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new chat
     * 
     * @param string $chatType Type of chat (private, group, channel)
     * @param int $createdBy User ID of creator
     * @param string|null $title Title for group/channel chats
     * @param string|null $description Description for group/channel chats
     * @param array $participants Array of user IDs for initial participants
     * @return array|bool Chat data on success, false on failure
     */
    public function create(string $chatType, int $createdBy, ?string $title = null, ?string $description = null, array $participants = []) {
        try {
            $this->db->beginTransaction();
            
            // Create the chat
            $sql = "INSERT INTO {$this->chatTable} 
                    (chat_type, title, description, created_by) 
                    VALUES (:chat_type, :title, :description, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'chat_type' => $chatType,
                'title' => $title,
                'description' => $description,
                'created_by' => $createdBy
            ]);
            
            $chatId = $this->db->lastInsertId();
            
            // Add creator as owner participant
            $this->addParticipant($chatId, $createdBy, 'owner');
            
            // Add other participants
            foreach ($participants as $userId) {
                if ($userId != $createdBy) {
                    $this->addParticipant($chatId, $userId, 'member');
                }
            }
            
            $this->db->commit();
            
            return $this->findById($chatId);
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Find chat by ID
     * 
     * @param int $chatId Chat ID
     * @return array|bool Chat data on success, false on failure
     */
    public function findById(int $chatId) {
        $sql = "SELECT * FROM {$this->chatTable} WHERE chat_id = :chat_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['chat_id' => $chatId]);
            
            $chat = $stmt->fetch();
            if (!$chat) {
                return false;
            }
            
            // Get participants
            $chat['participants'] = $this->getParticipants($chatId);
            
            return $chat;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get user's chats
     * 
     * @param int $userId User ID
     * @return array User's chats
     */
    public function getUserChats(int $userId) {
        $sql = "SELECT c.* FROM {$this->chatTable} c
                INNER JOIN {$this->participantTable} p 
                ON c.chat_id = p.chat_id
                WHERE p.user_id = :user_id
                ORDER BY c.updated_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            $chats = $stmt->fetchAll();
            
            // Get participants for each chat
            foreach ($chats as &$chat) {
                $chat['participants'] = $this->getParticipants($chat['chat_id']);
            }
            
            return $chats;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Add participant to a chat
     * 
     * @param int $chatId Chat ID
     * @param int $userId User ID
     * @param string $role Role in the chat (member, admin, owner)
     * @return bool True on success, false on failure
     */
    public function addParticipant(int $chatId, int $userId, string $role = 'member') {
        $sql = "INSERT INTO {$this->participantTable} 
                (chat_id, user_id, role) 
                VALUES (:chat_id, :user_id, :role)
                ON DUPLICATE KEY UPDATE role = :role";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'chat_id' => $chatId,
                'user_id' => $userId,
                'role' => $role
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Remove participant from a chat
     * 
     * @param int $chatId Chat ID
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public function removeParticipant(int $chatId, int $userId) {
        $sql = "DELETE FROM {$this->participantTable} 
                WHERE chat_id = :chat_id AND user_id = :user_id";
        
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
     * Get participants of a chat
     * 
     * @param int $chatId Chat ID
     * @return array List of participants with user details
     */
    public function getParticipants(int $chatId) {
        $sql = "SELECT p.*, u.username, u.full_name, u.profile_image, u.is_online, u.last_seen
                FROM {$this->participantTable} p
                INNER JOIN users u ON p.user_id = u.user_id
                WHERE p.chat_id = :chat_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['chat_id' => $chatId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Check if user is participant in a chat
     * 
     * @param int $chatId Chat ID
     * @param int $userId User ID
     * @return bool True if user is participant, false otherwise
     */
    public function isParticipant(int $chatId, int $userId) {
        $sql = "SELECT COUNT(*) FROM {$this->participantTable} 
                WHERE chat_id = :chat_id AND user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'chat_id' => $chatId,
                'user_id' => $userId
            ]);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update chat information
     * 
     * @param int $chatId Chat ID
     * @param array $chatData Chat data to update
     * @return bool True on success, false on failure
     */
    public function update(int $chatId, array $chatData) {
        $allowedFields = ['title', 'description', 'photo'];
        $setFields = [];
        $params = ['chat_id' => $chatId];
        
        foreach ($chatData as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $setFields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($setFields)) {
            return false;
        }
        
        // Add updated_at timestamp
        $setFields[] = "updated_at = NOW()";
        
        $sql = "UPDATE {$this->chatTable} SET " . implode(', ', $setFields) . " WHERE chat_id = :chat_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Find or create a private chat between two users
     * 
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @return array|bool Chat data on success, false on failure
     */
    public function findOrCreatePrivateChat(int $userId1, int $userId2) {
        // Find existing private chat
        $sql = "SELECT c.chat_id FROM {$this->chatTable} c
                INNER JOIN {$this->participantTable} p1 ON c.chat_id = p1.chat_id
                INNER JOIN {$this->participantTable} p2 ON c.chat_id = p2.chat_id
                WHERE c.chat_type = 'private'
                AND p1.user_id = :user_id1
                AND p2.user_id = :user_id2
                AND (SELECT COUNT(*) FROM {$this->participantTable} p WHERE p.chat_id = c.chat_id) = 2";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id1' => $userId1,
                'user_id2' => $userId2
            ]);
            
            $result = $stmt->fetch();
            
            if ($result) {
                // Return existing chat
                return $this->findById($result['chat_id']);
            } else {
                // Create new private chat
                return $this->create('private', $userId1, null, null, [$userId1, $userId2]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update participant's last read message
     * 
     * @param int $chatId Chat ID
     * @param int $userId User ID
     * @param int $messageId Message ID
     * @return bool True on success, false on failure
     */
    public function updateLastReadMessage(int $chatId, int $userId, int $messageId) {
        $sql = "UPDATE {$this->participantTable} 
                SET last_read_message_id = :message_id
                WHERE chat_id = :chat_id AND user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'message_id' => $messageId,
                'chat_id' => $chatId,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
}