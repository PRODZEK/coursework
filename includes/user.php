<?php
/**
 * User Functions
 * 
 * This file contains functions related to user management and authentication
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Register a new user
 * 
 * @param string $username Username
 * @param string $email Email address
 * @param string $password Password
 * @return array Result with success flag and message
 */
function registerUser($username, $email, $password) {
    $conn = getDbConnection();
    
    // Sanitize inputs
    $username = sanitizeInput($username);
    $email = sanitizeInput($email);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Username must be at least 3 characters'];
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    $success = $stmt->execute();
    
    $stmt->close();
    closeDbConnection($conn);
    
    if ($success) {
        return [
            'success' => true, 
            'message' => 'Registration successful. You can now login.',
            'user_id' => $conn->insert_id
        ];
    } else {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Authenticate user
 * 
 * @param string $username Username or email
 * @param string $password Password
 * @return array Result with success flag and user data
 */
function loginUser($username, $password) {
    $conn = getDbConnection();
    
    // Sanitize inputs
    $username = sanitizeInput($username);
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    // Check if input is email or username
    $isEmail = isValidEmail($username);
    $field = $isEmail ? 'email' : 'username';
    
    // Get user from database
    $stmt = $conn->prepare("SELECT user_id, username, email, password FROM users WHERE $field = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Update user status and last seen
        $updateStmt = $conn->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE user_id = ?");
        $updateStmt->bind_param("i", $user['user_id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Remove password from user array
        unset($user['password']);
        
        closeDbConnection($conn);
        
        return [
            'success' => true, 
            'message' => 'Login successful',
            'user' => $user
        ];
    } else {
        closeDbConnection($conn);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
}

/**
 * Log out user
 * 
 * @param int $userId User ID
 * @return bool True if successful
 */
function logoutUser($userId) {
    $conn = getDbConnection();
    
    // Update user status and last seen
    $stmt = $conn->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();
    $stmt->close();
    
    closeDbConnection($conn);
    
    // Destroy session
    session_destroy();
    
    return $success;
}

/**
 * Get user by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT user_id, username, email, profile_picture, bio, last_seen, status, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        return null;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    closeDbConnection($conn);
    
    return $user;
}

/**
 * Get multiple users by their IDs
 * 
 * @param array $userIds Array of user IDs
 * @return array Array of user data
 */
function getUsersByIds($userIds) {
    if (empty($userIds)) {
        return [];
    }
    
    $conn = getDbConnection();
    
    // Create placeholders for the IN clause
    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT user_id, username, profile_picture, status, last_seen FROM users WHERE user_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    return $users;
}

/**
 * Search users by username
 * 
 * @param string $query Search query
 * @return array Matching users
 */
function searchUsers($query) {
    $conn = getDbConnection();
    
    $searchQuery = "%" . sanitizeInput($query) . "%";
    
    $stmt = $conn->prepare("SELECT user_id, username, profile_picture, status FROM users WHERE username LIKE ? LIMIT 20");
    $stmt->bind_param("s", $searchQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    return $users;
}

/**
 * Update user profile
 * 
 * @param int $userId User ID
 * @param array $data Profile data to update
 * @return array Result with success flag and message
 */
function updateUserProfile($userId, $data) {
    $conn = getDbConnection();
    
    // Sanitize inputs
    $fields = [];
    $types = '';
    $values = [];
    
    if (isset($data['username'])) {
        $username = sanitizeInput($data['username']);
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->bind_param("si", $username, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            closeDbConnection($conn);
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        $fields[] = "username = ?";
        $types .= "s";
        $values[] = $username;
    }
    
    if (isset($data['bio'])) {
        $bio = sanitizeInput($data['bio']);
        $fields[] = "bio = ?";
        $types .= "s";
        $values[] = $bio;
    }
    
    if (!empty($fields)) {
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = ?";
        $types .= "i";
        $values[] = $userId;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();
        
        closeDbConnection($conn);
        
        if ($success) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    closeDbConnection($conn);
    return ['success' => false, 'message' => 'No data provided for update'];
}

/**
 * Update user profile picture
 * 
 * @param int $userId User ID
 * @param array $file File upload data ($_FILES)
 * @return array Result with success flag and message
 */
function updateProfilePicture($userId, $file) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = UPLOAD_DIR . '/profile_pictures';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = $userId . '_' . time() . '.' . $extension;
    $targetFile = $uploadDir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Update user profile in database
        $conn = getDbConnection();
        
        // Get old profile picture
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Update user profile picture in database
        $relativePath = 'assets/uploads/profile_pictures/' . $filename;
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $relativePath, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        closeDbConnection($conn);
        
        // Delete old profile picture if it exists
        if ($success && !empty($user['profile_picture'])) {
            $oldFile = __DIR__ . '/../' . $user['profile_picture'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        if ($success) {
            return [
                'success' => true, 
                'message' => 'Profile picture updated successfully',
                'profile_picture' => $relativePath
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile picture in database'];
        }
    } else {
        return ['success' => false, 'message' => 'Failed to upload profile picture'];
    }
} 