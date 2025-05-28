-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS telegram_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE telegram_clone;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT,
    profile_image VARCHAR(255),
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    is_online BOOLEAN DEFAULT FALSE,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Chats table
CREATE TABLE IF NOT EXISTS chats (
    chat_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_type ENUM('private', 'group', 'channel') NOT NULL,
    title VARCHAR(100),
    description TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    photo VARCHAR(255),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_chat_type (chat_type)
) ENGINE=InnoDB;

-- Chat participants table
CREATE TABLE IF NOT EXISTS chat_participants (
    participant_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('member', 'admin', 'owner') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_message_id INT UNSIGNED DEFAULT NULL,
    is_muted BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_chat_user (chat_id, user_id),
    FOREIGN KEY (chat_id) REFERENCES chats(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_chat_user (chat_id, user_id)
) ENGINE=InnoDB;

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    message_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED,
    reply_to_message_id INT UNSIGNED,
    message_type ENUM('text', 'photo', 'video', 'file', 'audio', 'location', 'contact', 'system') NOT NULL,
    content TEXT,
    file_path VARCHAR(255),
    thumbnail_path VARCHAR(255),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    is_forwarded BOOLEAN DEFAULT FALSE,
    forwarded_from_id INT UNSIGNED,
    FOREIGN KEY (chat_id) REFERENCES chats(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reply_to_message_id) REFERENCES messages(message_id) ON DELETE SET NULL,
    FOREIGN KEY (forwarded_from_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_chat_sender_time (chat_id, sender_id, sent_at)
) ENGINE=InnoDB;

-- Message status table
CREATE TABLE IF NOT EXISTS message_status (
    status_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_message_user (message_id, user_id)
) ENGINE=InnoDB; 