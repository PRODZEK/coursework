<?php
/**
 * Chat Application - Main Index
 * 
 * This is the main entry point for the chat application
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/user.php';

// Initialize session
initSession();

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    // Get current user data
    $currentUser = getUserById($_SESSION['user_id']);
}

// Determine which page to render
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define allowed pages
$allowedPages = [
    'home',
    'login',
    'register',
    'chat',
    'profile',
    'logout'
];

// Restrict access to pages based on authentication status
if (!$isLoggedIn && !in_array($page, ['login', 'register', 'home'])) {
    // Redirect to login page if user is not logged in
    header('Location: ' . APP_URL . '/index.php?page=login');
    exit;
}

if ($isLoggedIn && in_array($page, ['login', 'register'])) {
    // Redirect to chat page if user is already logged in
    header('Location: ' . APP_URL . '/index.php?page=chat');
    exit;
}

// Handle logout
if ($page === 'logout' && $isLoggedIn) {
    logoutUser($_SESSION['user_id']);
    header('Location: ' . APP_URL . '/index.php?page=login');
    exit;
}

// Ensure page is valid
if (!in_array($page, $allowedPages)) {
    $page = 'home';
}

// Get page title
$pageTitle = 'Chat App';
switch ($page) {
    case 'home':
        $pageTitle = 'Welcome to Chat App';
        break;
    case 'login':
        $pageTitle = 'Login';
        break;
    case 'register':
        $pageTitle = 'Register';
        break;
    case 'chat':
        $pageTitle = 'Chats';
        break;
    case 'profile':
        $pageTitle = 'Profile';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            100: '#E6F5FF',
                            200: '#B3E0FF',
                            300: '#80CCFF',
                            400: '#4DB8FF',
                            500: '#1AA3FF',
                            600: '#0099FF',
                            700: '#0077CC',
                            800: '#005599',
                            900: '#003366'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Inter font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F7F9FB;
            color: #333;
        }
        
        .chat-container {
            height: calc(100vh - 4rem);
        }
        
        .chat-messages {
            height: calc(100% - 4rem);
            overflow-y: auto;
        }
        
        .message-input {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Message bubble styles */
        .message-bubble {
            max-width: 70%;
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-outgoing {
            background-color: #DCF8C6;
            margin-left: auto;
            border-top-right-radius: 0;
        }
        
        .message-incoming {
            background-color: #FFFFFF;
            margin-right: auto;
            border-top-left-radius: 0;
        }
        
        /* Online status indicator */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-online {
            background-color: #4CAF50;
        }
        
        .status-offline {
            background-color: #9E9E9E;
        }
        
        .status-away {
            background-color: #FFC107;
        }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
    <!-- Top navigation bar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo APP_URL; ?>/index.php" class="font-bold text-primary-600 text-lg">Chat App</a>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-6 md:flex md:items-center md:space-x-4">
                        <a href="<?php echo APP_URL; ?>/index.php?page=chat" class="<?php echo $page === 'chat' ? 'text-primary-600 font-medium' : 'text-gray-500 hover:text-primary-600'; ?> px-3 py-2 text-sm">Chats</a>
                        <a href="<?php echo APP_URL; ?>/index.php?page=profile" class="<?php echo $page === 'profile' ? 'text-primary-600 font-medium' : 'text-gray-500 hover:text-primary-600'; ?> px-3 py-2 text-sm">Profile</a>
                        <a href="<?php echo APP_URL; ?>/index.php?page=logout" class="text-gray-500 hover:text-red-600 px-3 py-2 text-sm">Logout</a>
                    </div>
                    <div class="flex items-center ml-4">
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <?php if ($currentUser && !empty($currentUser['profile_picture'])): ?>
                                    <img class="h-8 w-8 rounded-full object-cover" src="<?php echo APP_URL . '/' . $currentUser['profile_picture']; ?>" alt="<?php echo htmlspecialchars($currentUser['username']); ?>">
                                <?php else: ?>
                                    <div class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium">
                                        <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="status-indicator status-<?php echo $currentUser['status']; ?> absolute bottom-0 right-0 transform translate-x-1 border-2 border-white"></span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main content -->
    <main>
        <?php 
        // Include the appropriate page template
        $viewPath = __DIR__ . '/views/' . $page . '.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<div class="py-12 text-center">';
            echo '<p class="text-red-600 text-xl">Error: View file not found - ' . htmlspecialchars($viewPath) . '</p>';
            echo '</div>';
        }
        ?>
    </main>
    
    <!-- Debug information -->
    <?php if (isset($_GET['debug'])): ?>
    <div class="bg-gray-100 p-4 mt-8 mx-auto max-w-7xl">
        <h3 class="text-lg font-bold">Debug Information</h3>
        <p>Current page: <?php echo htmlspecialchars($page); ?></p>
        <p>View path: <?php echo htmlspecialchars($viewPath); ?></p>
        <p>File exists: <?php echo file_exists($viewPath) ? 'Yes' : 'No'; ?></p>
        <p>APP_URL: <?php echo htmlspecialchars(APP_URL); ?></p>
        <p>Current URL: <?php echo htmlspecialchars(getCurrentUrl()); ?></p>
        <p>HTTP_HOST: <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></p>
        <p>REQUEST_URI: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Custom scripts -->
    <script>
        // Check if browser notifications are supported
        if ('Notification' in window) {
            // Request permission for notifications when user interacts with the page
            document.addEventListener('click', function() {
                if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }
            }, { once: true });
        }
    </script>
</body>
</html> 