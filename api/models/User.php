<?php
/**
 * User Model
 * Handles all user-related database operations
 */
class User {
    private $db;
    private $table = 'users';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data (username, email, password, full_name)
     * @return int|bool User ID on success, false on failure
     */
    public function create(array $userData) {
        // Hash the password
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO {$this->table} (username, email, password, full_name) 
                VALUES (:username, :email, :password, :full_name)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'full_name' => $userData['full_name']
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Find user by ID
     * 
     * @param int $userId User ID
     * @return array|bool User data on success, false on failure
     */
    public function findById(int $userId) {
        $sql = "SELECT user_id, username, email, full_name, bio, profile_image, last_seen, 
                created_at, status, is_online 
                FROM {$this->table} WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            $user = $stmt->fetch();
            if (!$user) {
                return false;
            }
            
            return $user;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Find user by username or email
     * 
     * @param string $value Username or email
     * @return array|bool User data on success, false on failure
     */
    public function findByUsernameOrEmail(string $value) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :value OR email = :value";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['value' => $value]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $userData Updated user data
     * @return bool True on success, false on failure
     */
    public function update(int $userId, array $userData) {
        $allowedFields = ['full_name', 'bio', 'profile_image', 'status'];
        $setFields = [];
        $params = ['user_id' => $userId];
        
        foreach ($userData as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $setFields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($setFields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setFields) . " WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword The new password
     * @return bool True on success, false on failure
     */
    public function updatePassword(int $userId, string $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} SET password = :password WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'password' => $hashedPassword,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Verify user password
     * 
     * @param array $user User data containing hashed password
     * @param string $password Plain password for verification
     * @return bool True if password is valid, false otherwise
     */
    public function verifyPassword(array $user, string $password) {
        return password_verify($password, $user['password']);
    }
    
    /**
     * Search users by username or full name
     * 
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array List of matching users
     */
    public function search(string $query, int $limit = 20) {
        $sql = "SELECT user_id, username, full_name, profile_image, status
                FROM {$this->table} 
                WHERE username LIKE :query OR full_name LIKE :query 
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Update user's online status
     * 
     * @param int $userId User ID
     * @param bool $isOnline True for online, false for offline
     * @return bool True on success, false on failure
     */
    public function updateOnlineStatus(int $userId, bool $isOnline) {
        $sql = "UPDATE {$this->table} SET is_online = :is_online, last_seen = NOW() 
                WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'is_online' => $isOnline ? 1 : 0,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
} 