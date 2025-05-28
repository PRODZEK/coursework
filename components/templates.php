<!-- 
    HTML Templates
    These templates will be cloned and used by JavaScript to create dynamic content 
-->

<!-- Chat item in sidebar template -->
<template id="chat-item-template">
    <div class="chat-item" data-chat-id="">
        <div class="chat-avatar">
            <img src="" alt="Avatar" class="chat-avatar-img">
            <span class="status-indicator"></span>
        </div>
        <div class="chat-content">
            <div class="chat-header">
                <h4 class="chat-name"></h4>
                <span class="chat-time"></span>
            </div>
            <div class="chat-message-preview">
                <p class="preview-text"></p>
                <span class="unread-badge"></span>
            </div>
        </div>
    </div>
</template>

<!-- Message template -->
<template id="message-template">
    <div class="message" data-message-id="">
        <div class="message-avatar">
            <img src="" alt="Avatar" class="avatar-img">
        </div>
        <div class="message-content">
            <div class="message-header">
                <span class="message-sender"></span>
                <span class="message-time"></span>
            </div>
            <div class="message-body">
                <p class="message-text"></p>
                <div class="message-media"></div>
            </div>
            <div class="message-footer">
                <span class="message-status"></span>
            </div>
        </div>
    </div>
</template>

<!-- System message template -->
<template id="system-message-template">
    <div class="message system-message">
        <div class="message-body">
            <p class="message-text"></p>
        </div>
    </div>
</template>

<!-- User info popup template -->
<template id="user-info-template">
    <div class="user-info-popup">
        <div class="user-info-header">
            <div class="user-avatar">
                <img src="" alt="Avatar" class="avatar-img">
                <span class="status-indicator"></span>
            </div>
            <h3 class="user-name"></h3>
            <p class="user-status"></p>
        </div>
        <div class="user-info-body">
            <div class="user-bio"></div>
            <div class="user-actions">
                <button class="btn btn-primary message-btn">
                    <i class="fas fa-comment"></i> Message
                </button>
                <button class="btn btn-secondary voice-call-btn">
                    <i class="fas fa-phone-alt"></i> Voice Call
                </button>
                <button class="btn btn-secondary video-call-btn">
                    <i class="fas fa-video"></i> Video Call
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Chat info template -->
<template id="chat-info-template">
    <div class="chat-info-panel">
        <div class="chat-info-header">
            <div class="chat-avatar">
                <img src="" alt="Chat Avatar" class="avatar-img">
            </div>
            <h3 class="chat-title"></h3>
            <p class="chat-subtitle"></p>
        </div>
        <div class="chat-info-body">
            <div class="chat-description"></div>
            <div class="chat-participants">
                <h4>Participants</h4>
                <div class="participants-list"></div>
            </div>
            <div class="chat-actions">
                <button class="btn btn-danger leave-chat-btn">
                    <i class="fas fa-sign-out-alt"></i> Leave Chat
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Participant item template -->
<template id="participant-template">
    <div class="participant-item" data-user-id="">
        <div class="participant-avatar">
            <img src="" alt="Avatar" class="avatar-img">
            <span class="status-indicator"></span>
        </div>
        <div class="participant-info">
            <h5 class="participant-name"></h5>
            <span class="participant-role"></span>
        </div>
        <div class="participant-actions">
            <button class="remove-participant-btn" title="Remove">
                <i class="fas fa-times"></i>
            </button>
            <button class="role-participant-btn" title="Change Role">
                <i class="fas fa-crown"></i>
            </button>
        </div>
    </div>
</template>

<!-- Login form template -->
<template id="login-template">
    <div class="auth-form login-form">
        <div class="auth-logo">
            <img src="/assets/img/logo.png" alt="Telegram Clone Logo">
            <h1>Telegram Clone</h1>
        </div>
        <h2>Sign In</h2>
        <form id="login-form">
            <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="toggle-password-btn" tabindex="0" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-error"></div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
        <div class="auth-footer">
            <p>Don't have an account? <a href="/register" class="switch-auth-link">Sign Up</a></p>
        </div>
    </div>
</template>

<!-- Register form template -->
<template id="register-template">
    <div class="auth-form register-form">
        <div class="auth-logo">
            <img src="/assets/img/logo.png" alt="Telegram Clone Logo">
            <h1>Telegram Clone</h1>
        </div>
        <h2>Create Account</h2>
        <form id="register-form">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required autocomplete="name">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="register-password">Password</label>
                <div class="password-input">
                    <input type="password" id="register-password" name="password" required autocomplete="new-password">
                    <button type="button" class="toggle-password-btn" tabindex="0" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-error"></div>
            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
        </form>
        <div class="auth-footer">
            <p>Already have an account? <a href="/login" class="switch-auth-link">Sign In</a></p>
        </div>
    </div>
</template>

<!-- Create chat popup template -->
<template id="create-chat-template">
    <div class="popup-form create-chat-form">
        <h2>Create New Chat</h2>
        <form id="create-chat-form">
            <div class="form-group">
                <label for="chat-type">Chat Type</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="chat_type" value="group" checked> Group
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="chat_type" value="channel"> Channel
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="chat-title">Title</label>
                <input type="text" id="chat-title" name="title" required>
            </div>
            <div class="form-group">
                <label for="chat-description">Description (Optional)</label>
                <textarea id="chat-description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Add Participants</label>
                <div class="search-container">
                    <input type="text" id="search-users" placeholder="Search users...">
                    <div class="search-results" id="user-search-results"></div>
                </div>
                <div id="selected-participants" class="selected-participants"></div>
            </div>
            <div class="form-error"></div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</template> 