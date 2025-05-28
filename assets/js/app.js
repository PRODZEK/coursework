/**
 * Main Application
 * Initializes all modules and sets up the application
 */

// Global app state
const app = {
    currentUser: null,
    isAuthenticated: false,
    currentPath: window.location.pathname,
    init: null, // Will be defined below
};

/**
 * Show notification for new messages
 * 
 * @param {string} title - Notification title
 * @param {string} body - Notification body
 * @param {string} icon - Notification icon URL
 */
const showNotification = (title, body, icon) => {
    // Check if the browser supports notifications
    if (!("Notification" in window)) {
        console.log("This browser does not support desktop notification");
        return;
    }
    
    // Check if permission is granted
    if (Notification.permission === "granted") {
        new Notification(title, { body, icon });
    }
    // Otherwise, request permission
    else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                new Notification(title, { body, icon });
            }
        });
    }
};

/**
 * Play a sound
 * 
 * @param {string} soundName - Sound name from config.sounds
 */
const playSound = (soundName) => {
    if (!config.sounds || !config.sounds[soundName]) return;
    
    const audio = new Audio(config.sounds[soundName]);
    audio.play().catch(error => {
        console.log('Error playing sound:', error);
    });
};

/**
 * Format message content with links, emoji, etc.
 * 
 * @param {string} content - Message content
 * @returns {string} - Formatted content
 */
const formatMessageContent = (content = '') => {
    if (!content) return '';
    
    // Escape HTML
    let formatted = escapeHtml(content);
    
    // Replace URLs with links
    formatted = formatted.replace(
        /(https?:\/\/[^\s]+)/g, 
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
    );
    
    // Replace line breaks with <br>
    formatted = formatted.replace(/\n/g, '<br>');
    
    return formatted;
};

/**
 * Format relative time (e.g., "2 hours ago")
 * 
 * @param {string|Date} date - Date to format
 * @returns {string} - Formatted relative time
 */
const formatRelativeTime = (date) => {
    if (!date) return '';
    
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diff = Math.floor((now - dateObj) / 1000); // difference in seconds
    
    if (diff < 60) {
        return 'just now';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    } else if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    } else {
        return formatDate(dateObj, 'date');
    }
};

/**
 * Initialize the application
 */
app.init = async () => {
    try {
        // Check if we should show the auth pages or the main app
        const token = localStorage.getItem('auth_token');
        
        app.isAuthenticated = !!token;
        
        // Initialize API modules
        const apis = {
            auth: authApi,
            chats: chatsApi,
            messages: messagesApi
        };
        
        // Set authorization token for API requests
        if (token) {
            Object.values(apis).forEach(api => {
                if (api && api.setAuthToken) {
                    api.setAuthToken(token);
                }
            });
        }
        
        // Initialize modules based on authentication state
        if (app.isAuthenticated) {
            // Get current user info
            try {
                const response = await authApi.getCurrentUser();
                
                if (response.status === 'success' && response.data) {
                    app.currentUser = response.data;
                    
                    // Check if we're on an auth page and redirect if needed
                    if (['/login', '/register'].includes(app.currentPath)) {
                        window.location.href = '/';
                        return;
                    }
                    
                    // Initialize app modules
                    await initAppModules();
                } else {
                    // Invalid token or user, go to login
                    localStorage.removeItem('auth_token');
                    redirectToLogin();
                }
            } catch (error) {
                console.error('Error getting current user:', error);
                localStorage.removeItem('auth_token');
                redirectToLogin();
            }
        } else {
            // Not authenticated, show auth pages
            if (!['/login', '/register'].includes(app.currentPath)) {
                redirectToLogin();
                return;
            }
            
            // Initialize auth module
            initAuth();
        }
    } catch (error) {
        console.error('Error initializing application:', error);
        showError('Failed to initialize application. Please try again later.');
    }
    
    // Request notification permission
    requestNotificationPermission();
};

/**
 * Initialize all app modules for authenticated users
 */
const initAppModules = async () => {
    // Create main layout
    createAppLayout();
    
    // Initialize modules
    initChat(app.currentUser);
    initMessages();
    initRealtime();
    
    // Set up global event listeners
    setupGlobalEventListeners();
};

/**
 * Create the main app layout
 */
const createAppLayout = () => {
    document.body.innerHTML = '';
    
    const appContainer = document.createElement('div');
    appContainer.id = 'app';
    appContainer.className = 'app-container';
    
    const sidebar = document.createElement('div');
    sidebar.id = 'sidebar';
    sidebar.className = 'sidebar';
    
    const main = document.createElement('div');
    main.id = 'main';
    main.className = 'main';
    
    appContainer.appendChild(sidebar);
    appContainer.appendChild(main);
    
    document.body.appendChild(appContainer);
    
    // Add templates
    addTemplates();
};

/**
 * Add HTML templates to the document
 */
const addTemplates = () => {
    // Create chat template
    const createChatTemplate = document.createElement('template');
    createChatTemplate.id = 'create-chat-template';
    createChatTemplate.innerHTML = `
        <div class="popup-content">
            <h3>Create New Chat</h3>
            <form id="create-chat-form" class="app-form">
                <div class="form-group">
                    <label for="chat_type">Chat Type</label>
                    <select id="chat_type" name="chat_type" required>
                        <option value="private">Private Chat</option>
                        <option value="group">Group</option>
                    </select>
                </div>
                <div class="form-group group-field" style="display:none;">
                    <label for="title">Group Title</label>
                    <input type="text" id="title" name="title" placeholder="Enter group title">
                </div>
                <div class="form-group group-field" style="display:none;">
                    <label for="description">Description (optional)</label>
                    <textarea id="description" name="description" placeholder="Enter group description"></textarea>
                </div>
                <div class="form-group">
                    <label for="search-users">Add Participants</label>
                    <input type="text" id="search-users" placeholder="Search users...">
                    <div id="user-search-results" class="search-results"></div>
                </div>
                <div id="selected-participants" class="selected-participants"></div>
                <div class="form-error"></div>
                <div class="form-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="submit-btn">Create</button>
                </div>
            </form>
        </div>
    `;
    
    // Chat info template
    const chatInfoTemplate = document.createElement('template');
    chatInfoTemplate.id = 'chat-info-template';
    chatInfoTemplate.innerHTML = `
        <div class="chat-info-container">
            <div class="chat-info-header">
                <div class="chat-avatar large">
                    <img src="" alt="Avatar" class="avatar-img">
                </div>
                <h2 class="chat-title"></h2>
                <p class="chat-subtitle"></p>
            </div>
            <div class="chat-info-body">
                <div class="info-section">
                    <h3>About</h3>
                    <p class="chat-description"></p>
                </div>
                <div class="info-section">
                    <h3>Participants</h3>
                    <div class="participants-list"></div>
                </div>
            </div>
            <div class="chat-info-footer">
                <button class="leave-chat-btn danger-btn">Leave Chat</button>
            </div>
        </div>
    `;
    
    // Append templates to body
    document.body.appendChild(createChatTemplate);
    document.body.appendChild(chatInfoTemplate);
    
    // Add chat type change listener
    document.addEventListener('DOMContentLoaded', () => {
        const chatTypeSelect = document.getElementById('chat_type');
        if (chatTypeSelect) {
            chatTypeSelect.addEventListener('change', (e) => {
                const groupFields = document.querySelectorAll('.group-field');
                if (e.target.value === 'group') {
                    groupFields.forEach(field => {
                        field.style.display = 'block';
                    });
                } else {
                    groupFields.forEach(field => {
                        field.style.display = 'none';
                    });
                }
            });
        }
    });
};

/**
 * Set up global event listeners
 */
const setupGlobalEventListeners = () => {
    // Handle clicks outside of popup
    document.addEventListener('click', (e) => {
        const popup = document.querySelector('.popup');
        if (popup && !popup.contains(e.target) && !e.target.closest('.popup-trigger')) {
            document.body.removeChild(popup);
        }
    });
    
    // Handle key press
    document.addEventListener('keydown', (e) => {
        // Close popup on Escape key
        if (e.key === 'Escape') {
            const popup = document.querySelector('.popup');
            if (popup) {
                document.body.removeChild(popup);
            }
        }
    });
};

/**
 * Redirect to login page
 */
const redirectToLogin = () => {
    window.location.href = '/login';
};

/**
 * Request notification permission
 */
const requestNotificationPermission = async () => {
    try {
        if ('Notification' in window) {
            if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                await Notification.requestPermission();
            }
        }
    } catch (error) {
        console.log('Error requesting notification permission:', error);
    }
};

/**
 * Show error message
 * 
 * @param {string} message - Error message
 */
const showError = (message) => {
    const errorElement = document.createElement('div');
    errorElement.className = 'app-error';
    errorElement.textContent = message;
    
    document.body.prepend(errorElement);
    
    setTimeout(() => {
        errorElement.remove();
    }, 5000);
};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', app.init); 