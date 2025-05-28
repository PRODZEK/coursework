<?php
/**
 * Profile Page
 * User profile and settings
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure user is logged in
if (!Auth::isLoggedIn()) {
    redirect('/login');
}

// Get current user data
$user = getCurrentUser();
$successMessage = SessionManager::getFlash('success');
$errorMessage = SessionManager::getFlash('error');

// Handle form submission for profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        $db = db();
        
        // Update profile information
        if ($action === 'update_profile') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            
            // Validate inputs
            if (empty($firstName) || empty($lastName)) {
                SessionManager::setFlash('error', 'First name and last name are required');
                redirect('/profile');
            }
            
            $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE user_id = ?");
            $stmt->execute([$firstName, $lastName, $bio, $user['user_id']]);
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatarFile = $_FILES['avatar'];
                
                // Validate file type
                $allowedTypes = config('uploads.avatar.allowed_types');
                $fileType = $avatarFile['type'];
                
                if (!in_array($fileType, $allowedTypes)) {
                    SessionManager::setFlash('error', 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
                    redirect('/profile');
                }
                
                // Validate file size
                $maxSize = config('uploads.avatar.max_size');
                if ($avatarFile['size'] > $maxSize) {
                    SessionManager::setFlash('error', 'File too large. Max size: ' . ($maxSize / 1024 / 1024) . ' MB');
                    redirect('/profile');
                }
                
                // Create uploads directory if it doesn't exist
                $uploadDir = config('uploads.avatar.path');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user['user_id'] . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($avatarFile['tmp_name'], $filePath)) {
                    // Update user avatar in database
                    $relativePath = '/assets/uploads/avatars/' . $filename;
                    $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                    $stmt->execute([$relativePath, $user['user_id']]);
                } else {
                    SessionManager::setFlash('error', 'Failed to upload avatar');
                    redirect('/profile');
                }
            }
            
            SessionManager::setFlash('success', 'Profile updated successfully');
            redirect('/profile');
        }
        
        // Change password
        elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate inputs
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                SessionManager::setFlash('error', 'All password fields are required');
                redirect('/profile');
            }
            
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            $storedHash = $stmt->fetchColumn();
            
            if (!password_verify($currentPassword, $storedHash)) {
                SessionManager::setFlash('error', 'Current password is incorrect');
                redirect('/profile');
            }
            
            // Validate new password
            $minLength = config('security.password_min_length', 8);
            if (strlen($newPassword) < $minLength) {
                SessionManager::setFlash('error', "New password must be at least {$minLength} characters");
                redirect('/profile');
            }
            
            if ($newPassword !== $confirmPassword) {
                SessionManager::setFlash('error', 'New passwords do not match');
                redirect('/profile');
            }
            
            // Hash new password
            $passwordHash = password_hash(
                $newPassword, 
                config('security.password_algo', PASSWORD_DEFAULT),
                config('security.password_options', ['cost' => 10])
            );
            
            // Update password in database
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$passwordHash, $user['user_id']]);
            
            SessionManager::setFlash('success', 'Password changed successfully');
            redirect('/profile');
        }
        
        // Privacy settings
        elseif ($action === 'privacy_settings') {
            $lastSeen = $_POST['last_seen'] ?? 'everyone';
            $profilePhoto = $_POST['profile_photo'] ?? 'everyone';
            
            // Update privacy settings in user_settings table (create if not exists)
            $stmt = $db->prepare("
                INSERT INTO user_settings (user_id, last_seen_privacy, profile_photo_privacy) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE last_seen_privacy = ?, profile_photo_privacy = ?
            ");
            $stmt->execute([$user['user_id'], $lastSeen, $profilePhoto, $lastSeen, $profilePhoto]);
            
            SessionManager::setFlash('success', 'Privacy settings updated successfully');
            redirect('/profile');
        }
    } catch (PDOException $e) {
        error_log('Profile update error: ' . $e->getMessage());
        SessionManager::setFlash('error', 'An error occurred while updating your profile');
        redirect('/profile');
    }
}

// Get current privacy settings
try {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        $settings = [
            'last_seen_privacy' => 'everyone',
            'profile_photo_privacy' => 'everyone'
        ];
    }
} catch (PDOException $e) {
    $settings = [
        'last_seen_privacy' => 'everyone',
        'profile_photo_privacy' => 'everyone'
    ];
}

// Refresh user data after potential updates
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?= config('app.name') ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="profile-page">
    <div class="container">
        <!-- Header -->
        <header class="page-header">
            <div class="back-button">
                <a href="/" aria-label="Go back to dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            <h1>Profile Settings</h1>
        </header>
        
        <!-- Messages -->
        <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= e($successMessage) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= e($errorMessage) ?>
        </div>
        <?php endif; ?>
        
        <!-- Profile card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar-container">
                    <?php if ($user['profile_image']): ?>
                        <img src="<?= e($user['profile_image']) ?>" alt="Profile image" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar default-avatar">
                            <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h2><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                    <p class="username">@<?= e($user['username']) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Settings tabs -->
        <div class="settings-tabs">
            <div class="tabs">
                <button class="tab-button active" data-tab="profile">Profile</button>
                <button class="tab-button" data-tab="security">Security</button>
                <button class="tab-button" data-tab="privacy">Privacy</button>
            </div>
            
            <!-- Profile tab -->
            <div class="tab-content active" id="profile-tab">
                <form action="/profile" method="post" enctype="multipart/form-data" class="settings-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="avatar">Profile Photo</label>
                        <div class="avatar-upload">
                            <div class="avatar-preview">
                                <?php if ($user['profile_image']): ?>
                                    <img src="<?= e($user['profile_image']) ?>" alt="Avatar preview" id="avatar-preview">
                                <?php else: ?>
                                    <div class="default-avatar-preview" id="avatar-preview-text">
                                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="avatar-edit">
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="file-input">
                                <label for="avatar" class="btn btn-outline">Change Photo</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3" maxlength="160" placeholder="Tell something about yourself"><?= e($user['bio'] ?? '') ?></textarea>
                        <small class="form-text">Maximum 160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
                        <small class="form-text">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?= e($user['username']) ?>" disabled>
                        <small class="form-text">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
            
            <!-- Security tab -->
            <div class="tab-content" id="security-tab">
                <form action="/profile" method="post" class="settings-form" id="password-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                minlength="<?= config('security.password_min_length', 8) ?>" 
                                required
                            >
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text">Minimum <?= config('security.password_min_length', 8) ?> characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password"
                                minlength="<?= config('security.password_min_length', 8) ?>" 
                                required
                            >
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
                
                <div class="settings-section">
                    <h3>Active Sessions</h3>
                    <p>You can view and manage your active sessions. If you see any suspicious activity, change your password immediately.</p>
                    <button type="button" class="btn btn-danger" id="logout-all">Log out from all devices</button>
                </div>
            </div>
            
            <!-- Privacy tab -->
            <div class="tab-content" id="privacy-tab">
                <form action="/profile" method="post" class="settings-form">
                    <input type="hidden" name="action" value="privacy_settings">
                    
                    <div class="form-group">
                        <label>Last Seen</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="last_seen_everyone" 
                                    name="last_seen" 
                                    value="everyone" 
                                    <?= ($settings['last_seen_privacy'] === 'everyone') ? 'checked' : '' ?>
                                >
                                <label for="last_seen_everyone">Everyone</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="last_seen_contacts" 
                                    name="last_seen" 
                                    value="contacts" 
                                    <?= ($settings['last_seen_privacy'] === 'contacts') ? 'checked' : '' ?>
                                >
                                <label for="last_seen_contacts">Contacts Only</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="last_seen_nobody" 
                                    name="last_seen" 
                                    value="nobody" 
                                    <?= ($settings['last_seen_privacy'] === 'nobody') ? 'checked' : '' ?>
                                >
                                <label for="last_seen_nobody">Nobody</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Profile Photo</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="profile_photo_everyone" 
                                    name="profile_photo" 
                                    value="everyone" 
                                    <?= ($settings['profile_photo_privacy'] === 'everyone') ? 'checked' : '' ?>
                                >
                                <label for="profile_photo_everyone">Everyone</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="profile_photo_contacts" 
                                    name="profile_photo" 
                                    value="contacts" 
                                    <?= ($settings['profile_photo_privacy'] === 'contacts') ? 'checked' : '' ?>
                                >
                                <label for="profile_photo_contacts">Contacts Only</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="profile_photo_nobody" 
                                    name="profile_photo" 
                                    value="nobody" 
                                    <?= ($settings['profile_photo_privacy'] === 'nobody') ? 'checked' : '' ?>
                                >
                                <label for="profile_photo_nobody">Nobody</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                    </div>
                </form>
                
                <div class="settings-section">
                    <h3>Data & Storage</h3>
                    <p>Manage how your data is stored and processed.</p>
                    <button type="button" class="btn btn-outline" id="export-data">Export Chat History</button>
                    <button type="button" class="btn btn-outline" id="clear-data">Clear Cache</button>
                </div>
            </div>
        </div>
        
        <!-- Logout button -->
        <div class="profile-footer">
            <form action="/logout" method="post">
                <button type="submit" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to current tab
                    this.classList.add('active');
                    document.getElementById(this.dataset.tab + '-tab').classList.add('active');
                });
            });
            
            // Avatar preview
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatar-preview');
            const avatarPreviewText = document.getElementById('avatar-preview-text');
            
            if (avatarInput) {
                avatarInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            if (avatarPreview) {
                                avatarPreview.src = e.target.result;
                                avatarPreview.style.display = 'block';
                            }
                            
                            if (avatarPreviewText) {
                                avatarPreviewText.style.display = 'none';
                            }
                        };
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Password visibility toggle
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });
            
            // Password form validation
            const passwordForm = document.getElementById('password-form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(event) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (newPassword !== confirmPassword) {
                        event.preventDefault();
                        alert('New passwords do not match');
                    }
                });
            }
            
            // Logout all devices
            const logoutAllBtn = document.getElementById('logout-all');
            if (logoutAllBtn) {
                logoutAllBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to log out from all devices?')) {
                        // Send request to log out all sessions
                        fetch('/api/auth/logout-all', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = '/login';
                            } else {
                                alert(data.error || 'An error occurred');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        });
                    }
                });
            }
            
            // Export data
            const exportBtn = document.getElementById('export-data');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    // TODO: Implement chat history export
                    alert('This feature is coming soon');
                });
            }
            
            // Clear cache
            const clearCacheBtn = document.getElementById('clear-data');
            if (clearCacheBtn) {
                clearCacheBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to clear local cache? This will not delete your messages from the server.')) {
                        // Clear local storage
                        localStorage.clear();
                        
                        // Show confirmation
                        alert('Cache cleared successfully');
                    }
                });
            }
        });
    </script>
</body>
</html> 