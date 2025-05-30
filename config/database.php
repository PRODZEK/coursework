<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection parameters
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'chat_app');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default WAMP password is empty

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Close database connection
 * 
 * @param mysqli $conn Database connection object
 * @return void
 */
function closeDbConnection($conn) {
    if ($conn) {
        $conn->close();
    }
} 