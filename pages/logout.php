<?php
/**
 * Logout Handler
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Only process POST requests for security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the user out
    Auth::logout();
}

// Redirect to login page
redirect('/login'); 