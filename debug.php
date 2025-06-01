<?php
/**
 * Debug file to test database connection and other features
 */

// Display all PHP errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/utils.php';

// Test database connection
echo "<h1>Testing Database Connection</h1>";
try {
    $conn = getDbConnection();
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['users', 'chats', 'chat_members', 'messages', 'message_status'];
    echo "<h2>Checking Tables</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<li style='color: green;'>Table '$table' exists</li>";
        } else {
            echo "<li style='color: red;'>Table '$table' does not exist</li>";
        }
    }
    echo "</ul>";
    
    // Close connection
    closeDbConnection($conn);
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
}

// Display server info
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>App URL: " . APP_URL . "</p>";

// Check existence of view files
echo "<h2>Checking View Files</h2>";
$views = ['home.php', 'login.php', 'register.php', 'chat.php', 'profile.php'];
echo "<ul>";
foreach ($views as $view) {
    $path = __DIR__ . '/views/' . $view;
    if (file_exists($path)) {
        echo "<li style='color: green;'>View '$view' exists</li>";
    } else {
        echo "<li style='color: red;'>View '$view' does not exist</li>";
    }
}
echo "</ul>";

// Test session
echo "<h2>Testing Session</h2>";
initSession();
echo "<p>Session name: " . session_name() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// Test file permissions
echo "<h2>Checking File Permissions</h2>";
$dirs = ['views', 'includes', 'api', 'assets'];
echo "<ul>";
foreach ($dirs as $dir) {
    $path = __DIR__ . 'debug.php/' . $dir;
    if (is_readable($path)) {
        echo "<li style='color: green;'>Directory '$dir' is readable</li>";
    } else {
        echo "<li style='color: red;'>Directory '$dir' is not readable</li>";
    }
    if (is_writable($path)) {
        echo "<li style='color: green;'>Directory '$dir' is writable</li>";
    } else {
        echo "<li style='color: red;'>Directory '$dir' is not writable</li>";
    }
}
echo "</ul>"; 