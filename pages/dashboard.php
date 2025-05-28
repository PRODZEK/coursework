<?php
/**
 * Dashboard Page
 * Main application interface after login
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure user is logged in
if (!Auth::isLoggedIn()) {
    redirect('/login');
}

// Get current user data
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= config('app.name') ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Theme color for mobile browsers -->
    <meta name="theme-color" content="#527BFF">
</head>
<body class="app-layout" data-user-id="<?= $currentUser['user_id'] ?>">
    <div id="app" class="app-container">
        <!-- Sidebar with chat list -->
        <div id="sidebar" class="sidebar">
            <!-- Sidebar header with user info and menu -->
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="profile-image">
                        <?php if ($currentUser['profile_image']): ?>
                            <img src="<?= e($currentUser['profile_image']) ?>" alt="Profile image" class="avatar">
                        <?php else: ?>
                            <div class="default-avatar">
                                <?= strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="user-name">
                        <h3><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h3>
                        <span class="status online">Online</span>
                    </div>
                </div>
                <div class="menu-buttons">
                    <button id="toggle-menu" class="icon-button" aria-label="Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            
            <!-- Search bar -->
            <div class="sidebar-search">
                <div class="input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" id="chat-search" placeholder="Search" aria-label="Search chats">
                </div>
            </div>
            
            <!-- Chat list -->
            <div class="chats-container">
                <div class="chat-list" id="chat-list">
                    <!-- Will be populated by JavaScript -->
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
                
                <!-- New chat button -->
                <button id="new-chat-btn" class="new-chat-button">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        
        <!-- Main chat area -->
        <div id="main" class="main-content">
            <!-- Default empty state -->
            <div id="empty-state" class="empty-state">
                <div class="empty-state-icon">
                    <i class="far fa-comments"></i>
                </div>
                <h2>Select a chat to start messaging</h2>
                <p>Or start a new conversation by clicking the + button</p>
            </div>
            
            <!-- Chat view (hidden by default) -->
            <div id="chat-view" class="chat-view hidden">
                <!-- Chat header -->
                <div class="chat-header" id="chat-header">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <!-- Chat messages -->
                <div class="messages-container" id="messages-container">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <!-- Chat input -->
                <div class="message-input-container" id="message-input-container">
                    <div class="message-attachments" id="message-attachments"></div>
                    <div class="input-actions">
                        <button class="attachment-button" id="attachment-button" aria-label="Add attachment">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <div class="message-input-wrapper">
                            <textarea 
                                id="message-input" 
                                class="message-input" 
                                placeholder="Type a message..." 
                                rows="1"
                                aria-label="Type a message"
                            ></textarea>
                        </div>
                        <button class="send-button" id="send-button" disabled aria-label="Send message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modals -->
        <div class="modal" id="new-chat-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>New Conversation</h3>
                    <button class="close-modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="tabs">
                        <button class="tab active" data-tab="private">Private</button>
                        <button class="tab" data-tab="group">Group</button>
                    </div>
                    
                    <div class="tab-content active" id="tab-private">
                        <div class="search-users">
                            <div class="input-group">
                                <i class="fas fa-search"></i>
                                <input 
                                    type="text" 
                                    id="user-search" 
                                    placeholder="Search users..." 
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="search-results" id="user-search-results">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="tab-content" id="tab-group">
                        <div class="form-group">
                            <label for="group-name">Group Name</label>
                            <input 
                                type="text" 
                                id="group-name" 
                                placeholder="Enter group name" 
                                maxlength="100" 
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label>Add Participants</label>
                            <div class="search-users">
                                <div class="input-group">
                                    <i class="fas fa-search"></i>
                                    <input 
                                        type="text" 
                                        id="group-user-search" 
                                        placeholder="Search users..." 
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="search-results" id="group-user-search-results">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                        <div class="selected-users" id="selected-users">
                            <!-- Will be populated by JavaScript -->
                        </div>
                        <button id="create-group-btn" class="btn btn-primary btn-block" disabled>
                            Create Group
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal" id="user-profile-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Profile</h3>
                    <button class="close-modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="profile-content">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
        
        <!-- Context menus -->
        <div class="context-menu" id="message-context-menu">
            <ul>
                <li data-action="reply"><i class="fas fa-reply"></i> Reply</li>
                <li data-action="edit"><i class="fas fa-edit"></i> Edit</li>
                <li data-action="forward"><i class="fas fa-share"></i> Forward</li>
                <li data-action="delete" class="delete"><i class="fas fa-trash"></i> Delete</li>
            </ul>
        </div>
        
        <div class="context-menu" id="chat-context-menu">
            <ul>
                <li data-action="view-profile"><i class="fas fa-user"></i> View Profile</li>
                <li data-action="mute"><i class="fas fa-bell-slash"></i> Mute</li>
                <li data-action="archive"><i class="fas fa-archive"></i> Archive</li>
                <li data-action="mark-read"><i class="fas fa-check-double"></i> Mark as Read</li>
                <li data-action="delete" class="delete"><i class="fas fa-trash"></i> Delete Chat</li>
            </ul>
        </div>
    </div>
    
    <!-- Templates -->
    <?php include_once __DIR__ . '/../components/templates.php'; ?>
    
    <!-- Core JavaScript -->
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/utils.js"></script>
    <script src="/assets/js/api.js"></script>
    
    <!-- App JavaScript -->
    <script src="/assets/js/chat.js"></script>
    <script src="/assets/js/messages.js"></script>
    <script src="/assets/js/realtime.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html> 