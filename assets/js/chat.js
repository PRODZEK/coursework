/**
 * Chat Application Core JavaScript
 */

// Main Chat Controller
const ChatApp = {
    // State
    currentChatId: null,
    chats: [],
    users: [],
    pollingInterval: null,
    lastMessageTimestamp: 0,
    selectedUsers: [],
    notificationsEnabled: false,
    soundEnabled: true,
    doNotDisturb: false,
    notificationSound: null,
    lastGlobalUpdateTimestamp: null,
    currentMessages: null,
    lastDisplayedDateSeparatorString: null, // Added to track the last date separator shown
    // Flag to prevent multiple file dialogs
    _fileDialogOpen: false,
    // Flag to track emoji picker state
    _emojiPickerOpen: false,

    // DOM Elements
    elements: {},

    // Initialize the application
    init() {
        // Initialize DOM elements safely
        this.initElements();
        
        // Only proceed if required elements are available
        if (!this.elementsLoaded) {
            console.error('Error initializing chat app: Required elements not found');
            this.showInitializationError();
            return;
        }
        
        try {
            this.bindEvents();
            this.loadChats();
            this.setupNotifications();
            this.setupNotificationSound();
            this.loadSettings();
            this.setupDeleteChatModal();
        } catch (error) {
            console.error('Error initializing chat app:', error);
            this.showInitializationError(error.message);
        }
    },

    // Initialize DOM elements
    initElements() {
        // Message elements
        this.elements.chatList = document.getElementById('chats-list');
        this.elements.chatMessages = document.getElementById('chat-messages');
        this.elements.messageForm = document.getElementById('message-form');
        this.elements.messageText = document.getElementById('message-text');
        this.elements.messageInput = document.getElementById('message-text');
        this.elements.emptyState = document.getElementById('empty-chat-state');
        this.elements.activeChat = document.getElementById('active-chat');
        
        // Chat header elements
        this.elements.chatHeader = document.getElementById('chat-header');
        this.elements.chatName = document.getElementById('chat-name');
        this.elements.chatStatus = document.getElementById('chat-status');
        this.elements.chatAvatar = document.getElementById('chat-avatar');
        
        // Chat creation elements
        this.elements.newChatButton = document.getElementById('new-chat-button');
        this.elements.startNewChatButton = document.getElementById('start-new-chat-button');
        this.elements.newChatModal = document.getElementById('new-chat-modal');
        this.elements.closeNewChatModal = document.getElementById('close-new-chat-modal');
        this.elements.userSearch = document.getElementById('user-search');
        this.elements.userSearchResults = document.getElementById('user-search-results');
        this.elements.createDirectChatButton = document.getElementById('create-direct-chat-button');
        this.elements.createGroupChatButton = document.getElementById('create-group-chat-button');
        this.elements.groupChatName = document.getElementById('group-chat-name');
        this.elements.groupMembersList = document.getElementById('group-members-list');
        
        // Settings elements
        this.elements.settingsButton = document.getElementById('settings-button');
        this.elements.settingsModal = document.getElementById('settings-modal');
        this.elements.closeSettingsModal = document.getElementById('close-settings-modal');
        this.elements.desktopNotificationsToggle = document.getElementById('desktop-notifications-toggle');
        this.elements.soundToggle = document.getElementById('sound-toggle');
        this.elements.doNotDisturbToggle = document.getElementById('do-not-disturb-toggle');
        this.elements.saveSettingsButton = document.getElementById('save-settings-button');
        
        // Delete chat modal
        this.elements.deleteChatModal = document.getElementById('delete-chat-modal');
        this.elements.deleteChatMessage = document.getElementById('delete-chat-message');
        this.elements.confirmDeleteChatBtn = document.getElementById('confirm-delete-chat');
        this.elements.cancelDeleteChatBtn = document.getElementById('cancel-delete-chat');
        this.elements.deleteChatBackdrop = document.getElementById('delete-chat-backdrop');
        
        // Media and attachment elements
        this.elements.attachmentButton = document.getElementById('attachment-button');
        this.elements.fileInput = document.getElementById('file-input');
        this.elements.emojiButton = document.getElementById('emoji-button');
        this.elements.emojiPicker = document.getElementById('emoji-picker');
        this.elements.emojiPickerContainer = document.getElementById('emoji-picker-container');
        this.elements.emojiGrid = document.getElementById('emoji-grid');
        this.elements.closeEmojiPicker = document.getElementById('close-emoji-picker');
        this.elements.emojiBackdrop = document.getElementById('emoji-backdrop');
        this.elements.selectedAttachments = document.getElementById('selected-attachments');
        
        // Audio recording elements
        this.elements.audioRecordButton = document.getElementById('audio-record-button');
        this.elements.audioRecordingUI = document.getElementById('audio-recording-ui');
        this.elements.recordingTime = document.getElementById('recording-time');
        this.elements.cancelRecording = document.getElementById('cancel-recording');
        this.elements.stopRecording = document.getElementById('stop-recording');

        // Check if required elements exist
        this.elementsLoaded = !!(
            this.elements.chatList && 
            this.elements.messageForm && 
            this.elements.messageText && 
            this.elements.emptyState && 
            this.elements.activeChat
        );
    },

    // Show initialization error
    showInitializationError(errorMessage = 'Failed to initialize chat') {
        const chatsList = document.getElementById('chats-list');
        if (chatsList) {
            chatsList.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-500 p-4">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-500 font-medium mb-2">Error initializing chat</p>
                        <p class="text-gray-600 text-sm mb-4">${errorMessage}</p>
                        <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" onclick="location.reload()">
                            Reload page
                        </button>
                    </div>
                </div>
            `;
        } else {
            // If even the chatsList element isn't available, create an error popup
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-0 left-0 right-0 bg-red-500 text-white p-4 text-center';
            errorDiv.innerHTML = `
                <p><strong>Error:</strong> ${errorMessage}</p>
                <button class="ml-4 bg-white text-red-500 px-2 py-1 rounded" onclick="location.reload()">Reload</button>
            `;
            document.body.appendChild(errorDiv);
        }
    },

    // Bind event listeners
    bindEvents() {
        // Message form submission
        if (this.elements.messageForm) {
            this.elements.messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        // New chat button click
        if (this.elements.newChatButton) {
            this.elements.newChatButton.addEventListener('click', () => this.openNewChatModal());
        }
        
        if (this.elements.startNewChatButton) {
            this.elements.startNewChatButton.addEventListener('click', () => this.openNewChatModal());
        }
        
        // Close modal button click
        if (this.elements.closeNewChatModal) {
            this.elements.closeNewChatModal.addEventListener('click', () => this.closeNewChatModal());
        }
        
        // User search input
        if (this.elements.userSearch) {
            this.elements.userSearch.addEventListener('input', (e) => this.handleUserSearch(e.target.value));
        }
        
        // Direct chat button click
        if (this.elements.createDirectChatButton) {
            this.elements.createDirectChatButton.addEventListener('click', () => this.createDirectChat());
        }
        
        // Group chat button click
        if (this.elements.createGroupChatButton) {
            this.elements.createGroupChatButton.addEventListener('click', () => this.createGroupChat());
        }
        
        // Group chat name input
        if (this.elements.groupChatName) {
            this.elements.groupChatName.addEventListener('input', () => this.validateGroupChatForm());
        }
        
        // Settings modal
        if (this.elements.settingsButton) {
            this.elements.settingsButton.addEventListener('click', () => this.openSettingsModal());
        }
        
        if (this.elements.closeSettingsModal) {
            this.elements.closeSettingsModal.addEventListener('click', () => this.closeSettingsModal());
        }
        
        if (this.elements.saveSettingsButton) {
            this.elements.saveSettingsButton.addEventListener('click', () => this.saveSettings());
        }
        
        // Settings toggles
        if (this.elements.desktopNotificationsToggle) {
            this.elements.desktopNotificationsToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.requestNotificationPermission();
                } else {
                    this.notificationsEnabled = false;
                }
            });
        }
        
        if (this.elements.soundToggle) {
            this.elements.soundToggle.addEventListener('change', (e) => {
                this.soundEnabled = e.target.checked;
            });
        }
        
        if (this.elements.doNotDisturbToggle) {
            this.elements.doNotDisturbToggle.addEventListener('change', (e) => {
                this.doNotDisturb = e.target.checked;
            });
        }
        
        // Media and attachment bindings
        if (this.elements.attachmentButton) {
            this.elements.attachmentButton.addEventListener('click', () => this.openFileSelector());
        }
        
        if (this.elements.fileInput) {
            this.elements.fileInput.addEventListener('change', (e) => this.handleFileSelection(e));
        }
        
        // Handle emoji button click with proper event handling
        if (this.elements.emojiButton) {
            // Remove any existing event listeners first
            const newEmojiButton = this.elements.emojiButton.cloneNode(true);
            this.elements.emojiButton.parentNode.replaceChild(newEmojiButton, this.elements.emojiButton);
            this.elements.emojiButton = newEmojiButton;
            
            this.elements.emojiButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                console.log('Emoji button clicked');
                this.toggleEmojiPicker();
            });
        }
        
        if (this.elements.closeEmojiPicker) {
            this.elements.closeEmojiPicker.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                this.closeEmojiPickerModal();
            });
        }
        
        if (this.elements.emojiBackdrop) {
            this.elements.emojiBackdrop.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeEmojiPickerModal();
            });
        }
        
        // Audio recording bindings
        if (this.elements.audioRecordButton) {
            this.elements.audioRecordButton.addEventListener('click', () => this.startAudioRecording());
        }
        
        if (this.elements.cancelRecording) {
            this.elements.cancelRecording.addEventListener('click', () => this.cancelAudioRecording());
        }
        
        if (this.elements.stopRecording) {
            this.elements.stopRecording.addEventListener('click', () => this.stopAudioRecording());
        }
        
        // Auto-resize textarea
        if (this.elements.messageInput) {
            this.elements.messageInput.addEventListener('input', () => {
                this.elements.messageInput.style.height = 'auto';
                this.elements.messageInput.style.height = (this.elements.messageInput.scrollHeight) + 'px';
            });
        }
    },

    // Load chats from API
    async loadChats() {
        try {
            // Use relative API URL
            const response = await fetch('api/chat.php?action=list');
            const data = await this.safeJsonParse(response, 'loadChats');

            if (data && data.success) {
                // Check that chat data exists and is in the correct format
                if (data.chats && Array.isArray(data.chats)) {
                    // Format data for display
                    this.chats = data.chats.map(chat => {
                        // For direct chats, use the other user's name as the chat name
                        let name = chat.chat_name;
                        let avatar = null;
                        let status = 'offline';
                        let userId = null;
                        
                        if (chat.chat_type === 'direct' && chat.other_user) {
                            name = chat.other_user.username;
                            avatar = chat.other_user.profile_picture;
                            status = chat.other_user.status;
                            userId = chat.other_user.user_id;
                        }
                        
                        return {
                            id: chat.chat_id,
                            name: name,
                            avatar: avatar,
                            status: status,
                            is_group: chat.chat_type === 'group',
                            last_message: chat.last_message ? chat.last_message.message_text : 'No messages yet',
                            last_message_time: chat.last_message ? chat.last_message.sent_at : chat.created_at,
                            unread_count: chat.unread_count || 0,
                            member_count: chat.member_count || 0,
                            user_id: userId
                        };
                    });
                    this.renderChatList();
                } else {
                    console.error('Unexpected chat data format:', data);
                    this.renderChatListError();
                }
            } else {
                console.error('Failed to load chats:', data ? data.message : 'No data returned');
                this.renderChatListError();
            }
        } catch (error) {
            console.error('Error loading chats:', error);
            this.renderChatListError();
        }
    },

    // Render chat list
    renderChatList() {
        if (!this.chats || this.chats.length === 0) {
            this.elements.chatList.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-500">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p>No chats yet</p>
                        <p class="text-sm mt-2">Start a new conversation!</p>
                    </div>
                </div>
            `;
            return;
        }

        this.elements.chatList.innerHTML = this.chats.map(chat => `
            <div class="chat-item relative group p-3 border-b border-gray-200 hover:bg-gray-50" 
                 data-chat-id="${chat.id}">
                <div class="flex items-center">
                    <div class="flex-1 min-w-0 cursor-pointer"
                         tabindex="0"
                         aria-label="Chat with ${chat.name}"
                         role="button"
                         onclick="ChatApp.openChat(${chat.id})"
                         onkeydown="if(event.key === 'Enter' || event.key === ' ') ChatApp.openChat(${chat.id})">
                        <div class="flex items-center">
                            <div class="relative w-12 h-12 rounded-full bg-gray-300 flex-shrink-0 mr-3 overflow-hidden">
                                ${chat.avatar ? `<img src="${chat.avatar}" alt="${chat.name}" class="w-full h-full object-cover">` : 
                                `<div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-lg font-bold">
                                    ${chat.name.charAt(0).toUpperCase()}
                                 </div>`}
                                 
                                ${!chat.is_group ? `<span class="status-indicator status-${chat.status || 'offline'} absolute bottom-0 right-0 transform translate-x-1 border-2 border-white"></span>` : ''}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between">
                                    <h3 class="font-medium text-gray-900 truncate">${chat.name}</h3>
                                    ${chat.last_message_time ? 
                                    `<span class="text-xs text-gray-500">${this.formatTime(chat.last_message_time)}</span>` : ''}
                                </div>
                                <div class="flex items-center">
                                    ${!chat.is_group && chat.status === 'online' ? 
                                    `<span class="text-xs text-green-500 mr-1">online</span>` : 
                                    (chat.is_group ? 
                                        `<span class="text-xs text-gray-500 mr-1">${chat.member_count || 0} members</span>` : 
                                        '')}
                                <p class="text-sm text-gray-500 truncate">
                                    ${chat.last_message ? chat.last_message : 'No messages yet'}
                                </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        ${chat.unread_count ? `
                        <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary-500 text-xs font-medium text-white">
                            ${chat.unread_count}
                        </span>` : ''}
                        
                        <button class="delete-chat-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-2 rounded-full text-gray-400 hover:bg-red-100 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-300"
                                tabindex="0"
                                aria-label="Delete chat with ${chat.name}"
                                title="Delete chat"
                                onclick="event.stopPropagation(); ChatApp.confirmDeleteChat(${chat.id}, '${chat.name.replace(/'/g, "\\'")}')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    },

    // Render chat list error
    renderChatListError() {
        this.elements.chatList.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-gray-500 p-4">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-500 font-medium mb-2">Failed to load chats</p>
                    <p class="text-gray-600 text-sm mb-4">There was a problem connecting to the server.</p>
                    <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" onclick="ChatApp.loadChats()">
                        Try again
                    </button>
                </div>
            </div>
        `;
    },

    // Open a chat
    async openChat(chatId) {
        this.currentChatId = chatId;
        this.elements.emptyState.classList.add('hidden');
        this.elements.activeChat.classList.remove('hidden');
        
        const chat = this.chats.find(c => c.id === chatId);
        if (chat) {
            this.elements.chatName.textContent = chat.name;
            this.elements.chatStatus.textContent = chat.is_group ? `${chat.member_count} members` : 'Online';
            
            this.elements.chatAvatar.innerHTML = chat.avatar 
                ? `<img src="${chat.avatar}" alt="${chat.name}" class="w-full h-full object-cover">` 
                : `<div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-lg font-bold">
                    ${chat.name.charAt(0).toUpperCase()}
                  </div>`;
                  
            // Highlight the selected chat
            document.querySelectorAll('.chat-item').forEach(item => {
                if (Number(item.dataset.chatId) === chatId) {
                    item.classList.add('bg-gray-100');
                } else {
                    item.classList.remove('bg-gray-100');
                }
            });
        }
        
        // Load messages for this chat
        this.loadMessages(chatId);
        
        // Start polling for new messages
        this.startPolling();
    },

    // Load messages for a chat
    async loadMessages(chatId) {
        try {
            // Clear existing messages first
            this.elements.chatMessages.innerHTML = `
                <div class="flex justify-center">
                    <div class="inline-block px-4 py-2 bg-gray-200 rounded-lg text-gray-700">
                        Loading messages...
                    </div>
                </div>
            `;
            
            const response = await fetch(`api/chat.php?action=messages&chat_id=${chatId}`);
            const data = await this.safeJsonParse(response, 'loadMessages');

            if (data && data.success) {
                // Store messages in memory
                this.currentMessages = data.messages || [];
                
                // Update UI with messages
                this.renderMessages(this.currentMessages);
                
                // Update last message timestamp for polling
                if (this.currentMessages && this.currentMessages.length > 0) {
                    const latestMessage = this.currentMessages.reduce((latest, msg) => {
                        const msgTime = new Date(msg.created_at || msg.sent_at).getTime();
                        return msgTime > latest ? msgTime : latest;
                    }, 0);
                    this.lastMessageTimestamp = Math.floor(latestMessage / 1000);
                }
                
                // Mark messages as read
                this.markMessagesAsRead();
                
                // Start polling for new messages
                this.startPolling();
                
                return this.currentMessages;
            } else {
                console.error('Failed to load messages:', data ? data.message : 'No data returned');
                this.renderMessagesError();
                return [];
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            this.renderMessagesError();
            return [];
        }
    },

    // Render messages error
    renderMessagesError() {
        this.elements.chatMessages.innerHTML = `
            <div class="flex justify-center">
                <div class="inline-block px-4 py-2 bg-red-50 text-red-700 rounded-lg">
                    Failed to load messages
                    <button class="ml-2 text-primary-600 hover:text-primary-800" onclick="ChatApp.loadMessages(ChatApp.currentChatId)">
                        Try again
                    </button>
                </div>
            </div>
        `;
    },

    // Render messages
    renderMessages(messages) {
        if (!messages || messages.length === 0) {
            this.elements.chatMessages.innerHTML = `
                <div class="flex justify-center">
                    <div class="inline-block px-4 py-2 bg-gray-200 rounded-lg text-gray-700">
                        No messages yet. Start the conversation!
                    </div>
                </div>
            `;
            this.lastDisplayedDateSeparatorString = null; // Reset if no messages
            return;
        }
        
        // Messages are now expected to be sorted by the server (oldest first)
        // So, we can remove the client-side sort.
        /* 
        messages.sort((a, b) => {
            const timeA = new Date(a.created_at || a.sent_at || 0).getTime();
            const timeB = new Date(b.created_at || b.sent_at || 0).getTime();
            return timeA - timeB; // Ascending order (oldest first)
        });
        */

        // Clear existing messages from the display
        this.elements.chatMessages.innerHTML = '';
        this.lastDisplayedDateSeparatorString = null; // Reset before rendering a new batch

        messages.forEach(message => {
            this.addMessageToUI(message, true); // Delegate rendering, skip scroll logic here
        });
        
        // Scroll to bottom after all messages have been rendered
        // Use setTimeout to ensure it runs after DOM updates and layout calculations
        setTimeout(() => {
        this.scrollToBottom();
        }, 0);
    },

    // Format message text with links and emojis
    formatMessageText(text) {
        // Handle null, undefined or non-string values
        if (text === null || text === undefined) {
            return '';
        }
        
        // Convert non-string values to string
        if (typeof text !== 'string') {
            try {
                text = String(text);
            } catch (e) {
                console.error('Error converting message text to string:', e);
                return '[Message content error]';
            }
        }
        
        // Safely escape HTML
        let safeText = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        
        // Convert URLs to links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        safeText = safeText.replace(urlRegex, url => `<a href="${url}" target="_blank" rel="noopener noreferrer" class="underline hover:text-primary-300">${url}</a>`);
        
        // Replace newlines with <br>
        safeText = safeText.replace(/\n/g, '<br>');
        
        return safeText;
    },

    // Format date for messages
    formatDateForMessages(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        
        // If today
        if (date.toDateString() === now.toDateString()) {
            return 'Today';
        }
        
        // If yesterday
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        }
        
        // If this week
        const daysDiff = Math.floor((now - date) / (1000 * 60 * 60 * 24));
        if (daysDiff < 7) {
            const options = { weekday: 'long' };
            return date.toLocaleDateString(undefined, options);
        }
        
        // Otherwise show full date
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    },

    // Format time for messages
    formatMessageTime(timestamp) {
        // Validate timestamp and handle different formats
        if (!timestamp) {
            return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Handle Unix timestamp (seconds)
        if (typeof timestamp === 'number' && timestamp < 20000000000) {
            // If timestamp is in seconds (Unix format), convert to milliseconds
            timestamp = timestamp * 1000;
        }
        
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) {
            // Invalid date, return current time
            console.warn('Invalid timestamp format:', timestamp);
            return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    // Scroll to bottom of chat messages
    scrollToBottom() {
        if (!this.elements.chatMessages) return;
        const messagesContainer = this.elements.chatMessages;
        
        requestAnimationFrame(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            this.hideNewMessageIndicator();
        });
    },
    
    // Scroll to bottom only if already at bottom or if a new message is sent by current user
    scrollToBottomIfNeeded() {
        if (!this.elements.chatMessages) return;
        
        const messagesContainer = this.elements.chatMessages;
        // Check if user is already near the bottom (within 200px) or if it's a new message from current user
        const scrollPosition = messagesContainer.scrollHeight - messagesContainer.clientHeight - messagesContainer.scrollTop;
        const isAtBottom = scrollPosition < 200;
        
        // Only scroll to bottom if user is already near the bottom
        if (isAtBottom) {
            requestAnimationFrame(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            });
        } else {
            // If not at bottom, show "new messages" indicator
            this.showNewMessageIndicator();
        }
    },

    // Show indicator when new messages arrive but user is scrolled up
    showNewMessageIndicator() {
        // Create indicator if not exists
        if (!document.getElementById('new-message-indicator')) {
            const indicator = document.createElement('button');
            indicator.id = 'new-message-indicator';
            indicator.className = 'fixed bottom-24 right-8 bg-primary-600 text-white rounded-full shadow-lg px-4 py-2 z-20 flex items-center';
            indicator.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                New messages
            `;
            indicator.addEventListener('click', () => this.scrollToBottom());
            document.body.appendChild(indicator);
        }
    },
    
    // Hide new message indicator
    hideNewMessageIndicator() {
        const indicator = document.getElementById('new-message-indicator');
        if (indicator) {
            indicator.remove();
        }
    },

    // Send a message
    async sendMessage() {
        if (!this.elements.messageText) {
            console.error('Message text element not found');
            return;
        }

        const messageText = this.elements.messageText.value.trim();
        
        if (!messageText || !this.currentChatId) return;
        
        // Clear input
        this.elements.messageText.value = '';
        
        // Reset textarea height if auto-resizing was applied
        if (this.elements.messageText.style) {
            this.elements.messageText.style.height = 'auto';
        }
        
        // Add message to UI immediately (optimistic update)
        const tempId = 'temp-' + Date.now();
        const tempMessage = {
            id: tempId,
            message_id: tempId,
            content: messageText,
            message_text: messageText,
            sender_id: this.currentUserId,
            created_at: new Date().toISOString(),
            is_read: false,
            is_group: false,
            sender_name: this.currentUserName,
            sender_avatar: this.currentUserAvatar,
            is_sender: true
        };
        
        // Add to current messages array
        if (!this.currentMessages) {
            this.currentMessages = [];
        }
        this.currentMessages.push(tempMessage);
        
        // Add the message to the UI
        this.addMessageToUI(tempMessage);
        
        try {
            console.log('Sending message:', messageText); // Debug
            
            const response = await fetch('api/chat.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chat_id: this.currentChatId,
                    message_text: messageText,
                    message_type: 'text'
                })
            });
            
            const data = await this.safeJsonParse(response, 'sendMessage');
            
            console.log('Send message response:', data); // Debug the response
            
            if (data && data.success) {
                // Create a base message with the original message text
                let realMessage = {
                    id: data.message_id || tempId,
                    message_id: data.message_id || tempId,
                    content: messageText,
                    message_text: messageText,
                    sender_id: this.currentUserId,
                    sent_at: new Date().toISOString(),
                    created_at: new Date().toISOString(),
                    is_read: false,
                    sender_name: this.currentUserName,
                    is_sender: true
                };
                
                // If API returns message data, merge it with our base message
                if (data.data && typeof data.data === 'object') {
                    // Create a new object with API data
                    realMessage = {
                        ...data.data,
                        // Always ensure the original message text is preserved
                        content: messageText,
                        message_text: messageText
                    };
                }
                
                // Update the temporary message with real data
                this.updateTempMessage(tempId, realMessage);
                
                // Update chat's last message
                this.updateChatLastMessage(this.currentChatId, messageText);
                
                return realMessage;
            } else {
                console.error('Failed to send message:', data ? data.message : 'No data returned');
                this.showMessageError(tempId);
                return null;
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showMessageError(tempId);
            return null;
        }
    },
    
    // Add message to UI
    addMessageToUI(message, skipScrollLogic = false) {
        if (!message) return;
        
        const messageId = `message-${message.id || message.message_id || message.temp_id || Date.now()}`;
        const existingMessageElement = document.getElementById(messageId);
        
        // If message exists and is not a temp message, update its status instead of re-adding
        if (existingMessageElement && !message.temp_id && message.message_id) {
            this.updateMessageReadStatusInUI(message.message_id, message.is_read);
            return;
        }
        
        // If it's a temp message being replaced by a real one, remove the old one first
        if (message.id && existingMessageElement && existingMessageElement.id.startsWith('message-temp-')) {
            existingMessageElement.remove();
        }
        
        const messageTimestamp = message.sent_at || message.created_at || new Date().toISOString();
        const messageDateObj = new Date(messageTimestamp);
        let dateSeparatorHtml = '';

        if (isNaN(messageDateObj.getTime())) {
            console.warn("Invalid date for new message, separator logic might be affected:", message);
        } else {
            const currentMessageDateForComparison = messageDateObj.toISOString().split('T')[0];
            if (currentMessageDateForComparison !== this.lastDisplayedDateSeparatorString) {
                dateSeparatorHtml = `
                    <div class="flex justify-center my-4 date-separator">
                <div class="px-4 py-1 bg-gray-100 rounded-full text-gray-500 text-xs">
                            ${this.formatDateForMessages(messageTimestamp)}
                        </div>
                </div>
            `;
                this.lastDisplayedDateSeparatorString = currentMessageDateForComparison;
            }
        }

        const isOutgoing = message.sender_id === this.currentUserId || message.is_sender;
        const messageAlign = isOutgoing ? 'justify-end' : 'justify-start';
        const formattedTime = this.formatMessageTime(message.sent_at || message.created_at);
        
        // Determine status based on message.is_read for outgoing, or default to 'sent' if property missing
        const initialStatus = (isOutgoing && message.hasOwnProperty('is_read')) ? (message.is_read ? 'read' : 'sent') : (isOutgoing ? 'sent' : '');
        
        // Create message content using renderMessageContent
        // This function now handles different message types (text, image, file, etc.)
        const renderedContentHtml = this.renderMessageContent(message); 

        const messageElement = document.createElement('div');
        messageElement.id = messageId;
        messageElement.className = `flex ${messageAlign} mb-3`; 
        
        messageElement.innerHTML = `
            ${!isOutgoing ? `
            <div class="w-8 h-8 rounded-full bg-gray-300 flex-shrink-0 mr-2 overflow-hidden">
                ${message.sender_avatar ? 
                            `<img src="${message.sender_avatar}" alt="${message.sender_name}" class="w-full h-full object-cover">` :
                    `<div class="w-full h-full bg-gray-400 flex items-center justify-center text-white text-sm font-bold">
                        ${message.sender_name ? message.sender_name.charAt(0).toUpperCase() : '?'}
                    </div>`
                }
            </div>
            ` : ''}
            <div class="max-w-xs sm:max-w-md break-words">
                ${!isOutgoing && (message.is_group || message.is_system || (message.chat_type === 'group' && !message.is_sender)) ? 
                    `<div class="text-xs font-medium text-gray-500 mb-1 ml-1">${message.sender_name}</div>` : ''
                }
                <div class="${isOutgoing ? 'message-outgoing' : 'message-incoming'}">
                    ${renderedContentHtml}
                    <div class="message-meta flex items-center justify-end text-xs mt-1 ${isOutgoing ? 'text-white text-opacity-70' : 'text-gray-500'}">
                        <span class="message-time">${formattedTime}</span>
                        ${isOutgoing ? `<span id="message-status-message-${message.message_id}" class="message-status-icon ml-1">${this.getReadStatusIcon(initialStatus)}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Add message to chat
        if (dateSeparatorHtml) {
            this.elements.chatMessages.insertAdjacentHTML('beforeend', dateSeparatorHtml);
        }
        this.elements.chatMessages.appendChild(messageElement);
        
        // Log message content for debugging
        console.log('Added message to UI:', {
            id: messageId,
            content: message.content || message.message_text || message.text || '',
            time: this.formatMessageTime(message.created_at || message.sent_at || new Date().toISOString()),
            isCurrentUser: message.is_sender
        });
        
        if (!skipScrollLogic) {
        // Always scroll to bottom after adding a new message from the current user
            if (message.is_sender) {
            this.scrollToBottom();
        } else {
            this.scrollToBottomIfNeeded();
            }
        }
    },
    
    // Update a temporary message with real data
    updateTempMessage(tempId, realMessage) {
        console.log('Updating temp message', tempId, 'with data:', realMessage);
        
        // Find the index of the temporary message in the currentMessages array
        const tempMsgIndex = this.currentMessages ? this.currentMessages.findIndex(
            m => m.message_id === tempId || m.id === tempId || m.temp_id === tempId
        ) : -1;
                
                if (tempMsgIndex !== -1) {
            // Get the temporary message object
                    const tempMessage = this.currentMessages[tempMsgIndex];

            // Revoke the local object URL if it was used for preview
            if (tempMessage.localPreviewUrl) {
                try {
                    URL.revokeObjectURL(tempMessage.localPreviewUrl);
                    console.log('Revoked Object URL:', tempMessage.localPreviewUrl);
                } catch (e) {
                    console.error("Error revoking object URL:", e);
                }
            }

            // Update the message in the array with the real data
            // Ensure to clear out upload-specific flags and objects
            // Also, ensure realMessage has is_sender set correctly
            const updatedMessage = {
                ...realMessage, // Spread the real message data from the server
                is_sender: realMessage.sender_id === this.currentUserId, // Explicitly set is_sender
                is_uploading: false, // Explicitly set to false
                file_object: null,   // Clear the temporary file object
                localPreviewUrl: null // Clear the local preview URL
                    };
                    
            this.currentMessages[tempMsgIndex] = updatedMessage;
                    console.log('Updated message in memory:', this.currentMessages[tempMsgIndex]);
            
            // Re-render all messages to reflect the update
            // This will ensure the correct message content and status icon are displayed
            this.renderMessages(this.currentMessages);

        } else {
            console.error(`Temporary message with ID ${tempId} not found in currentMessages array.`);
            // If not found in memory, try to find and replace in DOM directly (less ideal but a fallback)
            const messageElement = document.getElementById(`message-${tempId}`);
            if (messageElement) {
                console.warn(`Replacing message ${tempId} directly in DOM as it was not in currentMessages.`);
                
                // Ensure is_sender is set for direct DOM replacement as well
                const finalRealMessage = {
                    ...realMessage,
                    is_sender: realMessage.sender_id === this.currentUserId,
                    is_uploading: false,
                    file_object: null,
                    localPreviewUrl: null
                };

                // Create a new message in the UI with the real data
                this.addMessageToUI(finalRealMessage);
                // Remove the temporary message element if it still exists
                const oldTempElement = document.getElementById(`message-${tempId}`);
                if(oldTempElement) oldTempElement.remove();

            } else {
                console.error(`Message element with ID message-${tempId} also not found in DOM.`);
            }
        }
    },
    
    // Show error on a temporary message
    showMessageError(tempId) {
        const messageElement = document.getElementById(`message-${tempId}`);
        if (!messageElement) return;
        
        const messageContainer = messageElement.querySelector('div:last-child');
        if (!messageContainer) return;
        
        const messageContentContainer = messageContainer.querySelector('div.message-incoming, div.message-outgoing');
        if (!messageContentContainer) return;
        
        messageContentContainer.classList.add('bg-red-100', 'text-red-800');
        messageContentContainer.classList.remove('bg-primary-500', 'text-white');
        
        const messageStatus = messageContentContainer.querySelector('div.message-time');
        if (messageStatus) {
            messageStatus.innerHTML = `
                ${this.formatMessageTime(new Date().toISOString())}
                <span class="ml-1 text-red-500">Failed to send</span>
                <button class="ml-1 text-red-700 hover:text-red-900 underline" onclick="ChatApp.retryMessage('${tempId}')">Retry</button>
            `;
        }
    },
    
    // Retry sending a failed message
    retryMessage(tempId) {
        const messageElement = document.getElementById(`message-${tempId}`);
        if (!messageElement) return;
        
        const messageContainer = messageElement.querySelector('div:last-child');
        if (!messageContainer) return;
        
        const messageContentContainer = messageContainer.querySelector('div.message-incoming, div.message-outgoing');
        if (!messageContentContainer) return;
        
        const messageText = messageContentContainer.querySelector('p.message-text').textContent;
        
        // Remove the failed message
        messageElement.remove();
        
        // Reset the message text in input field
        this.elements.messageText.value = messageText;
        
        // Focus on the input field
        this.elements.messageText.focus();
    },
    
    // Update the last message in chat list
    updateChatLastMessage(chatId, lastMessage) {
        this.chats = this.chats.map(chat => {
            if (chat.id === chatId) {
                return {
                    ...chat,
                    last_message: lastMessage,
                    last_message_time: new Date().toISOString()
                };
            }
            return chat;
        });
        
        // Move this chat to the top of the list
        this.chats.sort((a, b) => {
            if (!a.last_message_time) return 1;
            if (!b.last_message_time) return -1;
            return new Date(b.last_message_time) - new Date(a.last_message_time);
        });
        
        // Re-render the chat list
        this.renderChatList();
        
        // Re-highlight the current chat
        document.querySelectorAll('.chat-item').forEach(item => {
            if (Number(item.dataset.chatId) === this.currentChatId) {
                item.classList.add('bg-gray-100');
            }
        });
    },
    
    // Start polling for new messages
    startPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        // Initial poll for messages
        this.pollNewMessages();
        
        // Start polling every 3 seconds
        this.pollingInterval = setInterval(() => {
            this.pollNewMessages();
        }, 3000);
        
        // Also start polling for global updates (statuses, new chats, etc.)
        this.startGlobalPolling();
    },
    
    // Stop polling for new messages
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    },
    
    // Setup notifications
    setupNotifications() {
        // Check if browser supports notifications
        if (!('Notification' in window)) {
            console.log('This browser does not support notifications');
            return;
        }

        // Check if permission is already granted
        if (Notification.permission === 'granted') {
            this.notificationsEnabled = true;
        }
    },

    // Show notification for new message
    showNotification(message, chatName) {
        if (!this.notificationsEnabled || document.hasFocus() || this.doNotDisturb) {
            return;
        }

        // Extract message content from any available field
        let messageText = '';
        if (message.content) {
            messageText = message.content;
        } else if (message.message_text) {
            messageText = message.message_text;
        } else if (message.text) {
            messageText = message.text;
        } else {
            messageText = 'New message';
        }

        // Create notification
        const notification = new Notification('New message from ' + chatName, {
            body: message.is_system ? messageText : `${message.sender_name}: ${messageText}`,
            icon: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDA5OUZGIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgY2xhc3M9ImZlYXRoZXIgZmVhdGhlci1tZXNzYWdlLWNpcmNsZSI+PHBhdGggZD0iTTIxIDExLjVhOC4zOCA4LjM4IDAgMCAxLS45IDMuOCA4LjUgOC41IDAgMCAxLTcuNiA0LjcgOC4zOCA4LjM4IDAgMCAxLTMuOC0uOUwzIDIxbDEuOS01LjcgYTguMzggOC4zOCAwIDAgMS0uOS0zLjggOC41IDguNSAwIDAgMSA0LjctNy42IDguMzggOC4zOCAwIDAgMSAzLjgtLjlIMTJhOC40OCA4LjQ4IDAgMCAxIDggMy4yIDguNDggOC40OCAwIDAgMSAxIDQuMyB6Ij48L3BhdGg+PC9zdmc+'
        });

        // Close notification and focus window when clicked
        notification.onclick = function() {
            window.focus();
            notification.close();
        };

        // Auto close after 5 seconds
        setTimeout(() => {
            notification.close();
        }, 5000);
    },

    // Setup notification sound
    setupNotificationSound() {
        // Create audio element for notification sound
        this.notificationSound = new Audio('data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA='); // Short silent WAV
        
        // Add sound toggle to settings (example: in a settings modal)
        const settingsHTML = `
            <div class="flex items-center mt-2">
                <input type="checkbox" id="sound-toggle" class="mr-2" ${this.soundEnabled ? 'checked' : ''}>
                <label for="sound-toggle" class="text-sm text-gray-700">Enable sound notifications</label>
            </div>
        `;
        
        // If we have a settings container, add the sound toggle
        const settingsContainer = document.getElementById('settings-container');
        if (settingsContainer) {
            settingsContainer.insertAdjacentHTML('beforeend', settingsHTML);
            
            // Add event listener for sound toggle
            document.getElementById('sound-toggle').addEventListener('change', (e) => {
                this.soundEnabled = e.target.checked;
                // Save preference to localStorage
                localStorage.setItem('chat_sound_enabled', this.soundEnabled ? 'true' : 'false');
            });
        }
        
        // Load sound preference from localStorage
        const savedSoundPreference = localStorage.getItem('chat_sound_enabled');
        if (savedSoundPreference !== null) {
            this.soundEnabled = savedSoundPreference === 'true';
            
            // Update checkbox if it exists
            const soundToggle = document.getElementById('sound-toggle');
            if (soundToggle) {
                soundToggle.checked = this.soundEnabled;
            }
        }
    },
    
    // Play notification sound
    playNotificationSound() {
        if (this.soundEnabled && this.notificationSound && !this.doNotDisturb) {
            // Reset sound to beginning (in case it's already playing)
            this.notificationSound.currentTime = 0;
            
            // Play the sound
            this.notificationSound.play().catch(error => {
                console.log('Error playing notification sound:', error);
            });
        }
    },
    
    // Poll for new messages
    async pollNewMessages() {
        if (!this.currentChatId) return;
        
        try {
            // Use long polling endpoint to get new messages since last timestamp
            const response = await fetch(`api/poll.php?chat_id=${this.currentChatId}&last_update=${this.lastMessageTimestamp || 0}`);
            const data = await this.safeJsonParse(response, 'pollNewMessages');
            
            if (data && data.success) {
                // Check if the chat has been deleted
                if (data.chat_deleted) {
                    console.log('Chat has been deleted, closing chat view');
                    this.currentChatId = null;
                    this.elements.emptyState.classList.remove('hidden');
                    this.elements.activeChat.classList.add('hidden');
                    this.stopPolling();
                    this.showToast('This chat has been deleted by another participant');
                    
                    // Remove the chat from the list and re-render
                    this.chats = this.chats.filter(chat => chat.id !== this.currentChatId);
                    this.renderChatList();
                    return;
                }
                
                // If there are new messages or status updates
                if (data.messages && data.messages.length > 0) {
                    // Get the latest timestamp from new messages
                    const latestMessageTimestampFromNew = data.messages.reduce((latest, msg) => {
                        const msgTime = new Date(msg.created_at || msg.sent_at).getTime();
                        return msgTime > latest ? msgTime : latest;
                    }, this.lastMessageTimestamp ? (this.lastMessageTimestamp * 1000) : 0);
                    
                    // Update last message timestamp (convert back to seconds for API)
                    this.lastMessageTimestamp = Math.floor(latestMessageTimestampFromNew / 1000);
                    
                    // Add to current messages array
                    if (!this.currentMessages) {
                        this.currentMessages = [];
                    }
                    
                    // Add each message to memory and UI
                    data.messages.forEach(message => {
                        const existingMsgIndex = this.currentMessages.findIndex(
                            m => (m.message_id && m.message_id === message.message_id) || 
                                 (m.id && m.id === message.id) // Also check by id for temp messages
                        );
                        
                        if (existingMsgIndex === -1) {
                            // Add new message to in-memory messages array
                            message.is_sender = message.sender_id === this.currentUserId;
                            this.currentMessages.push(message);
                            this.addMessageToUI(message);
                        } else {
                            // Message already exists, check if its read status needs updating
                            const oldMessage = this.currentMessages[existingMsgIndex];
                            if (message.is_read !== oldMessage.is_read) {
                                this.currentMessages[existingMsgIndex].is_read = message.is_read;
                                this.updateMessageReadStatusInUI(message.message_id, message.is_read);
                            }
                            // Potentially update other fields if necessary, e.g., content if edited (not implemented yet)
                        }
                    });
                    
                    // Play notification sound for new messages from others
                    const hasNewMessagesFromOthers = data.messages.some(msg => 
                        msg.sender_id !== this.currentUserId
                    );
                    
                    if (hasNewMessagesFromOthers) {
                        this.playNotificationSound();
                        
                        // Show notification if tab is not active and notifications are enabled
                        if (this.notificationsEnabled && !document.hasFocus() && !this.doNotDisturb) {
                            // Get chat name for notification
                            const chat = this.chats.find(c => c.id === this.currentChatId);
                            const chatName = chat ? chat.name : 'Chat';
                            
                            // Show notification for the latest message from someone else
                            const latestOtherMessage = data.messages
                                .filter(msg => msg.sender_id !== this.currentUserId)
                                .pop();
                                
                            if (latestOtherMessage) {
                                this.showNotification(latestOtherMessage, chatName);
                            }
                        }
                    }
                    
                    // Mark messages as read if tab is active
                    if (document.hasFocus()) {
                        this.markMessagesAsRead();
                    }
                }
                
                // Process read status updates specifically from the poll response
                if (data.read_updates && data.read_updates.length > 0) {
                    data.read_updates.forEach(update => {
                        // Update in-memory messages array
                        const msgIndex = this.currentMessages.findIndex(m => m.message_id === update.message_id);
                        if (msgIndex !== -1) {
                            this.currentMessages[msgIndex].is_read = update.is_read;
                        }
                        // Update UI
                        this.updateMessageReadStatusInUI(update.message_id, update.is_read);
                    });
                }
                
                // Update chat UI with latest status
                if (data.chat_status) {
                    // Update chat header status
                    this.elements.chatStatus.textContent = data.chat_status;
                }
                
                return data.messages || [];
            }
        } catch (error) {
            console.error('Error polling for new messages:', error);
            return [];
        }
    },
    
    // Mark messages as read
    async markMessagesAsRead() {
        if (!this.currentChatId) return;
        
        try {
            const response = await fetch('api/chat.php?action=mark_as_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chat_id: this.currentChatId
                })
            });
            
            const data = await this.safeJsonParse(response, 'markMessagesAsRead');
            
            if (data && data.success) {
                // Update the unread count in the chat list
                this.chats = this.chats.map(chat => {
                    if (chat.id === this.currentChatId) {
                        return { ...chat, unread_count: 0 };
                    }
                    return chat;
                });
                
                // Re-render the chat list to update the unread count
                this.renderChatList();
                
                // Re-highlight the current chat
                document.querySelectorAll('.chat-item').forEach(item => {
                    if (Number(item.dataset.chatId) === this.currentChatId) {
                        item.classList.add('bg-gray-100');
                    }
                });
            }
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    },

    // Format time for chat list
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        
        // If today, show time
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // If this week, show day
        const daysDiff = Math.floor((now - date) / (1000 * 60 * 60 * 24));
        if (daysDiff < 7) {
            return date.toLocaleDateString([], { weekday: 'short' });
        }
        
        // Otherwise show date
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    },

    // Load settings from localStorage
    loadSettings() {
        // Load notification settings
        const notificationsEnabled = localStorage.getItem('chat_notifications_enabled');
        if (notificationsEnabled !== null) {
            this.notificationsEnabled = notificationsEnabled === 'true';
            this.elements.desktopNotificationsToggle.checked = this.notificationsEnabled;
        } else {
            this.elements.desktopNotificationsToggle.checked = this.notificationsEnabled;
        }
        
        // Load sound settings
        const soundEnabled = localStorage.getItem('chat_sound_enabled');
        if (soundEnabled !== null) {
            this.soundEnabled = soundEnabled === 'true';
            this.elements.soundToggle.checked = this.soundEnabled;
        } else {
            this.elements.soundToggle.checked = this.soundEnabled;
        }
        
        // Load do not disturb settings
        const doNotDisturb = localStorage.getItem('chat_do_not_disturb');
        if (doNotDisturb !== null) {
            this.doNotDisturb = doNotDisturb === 'true';
            this.elements.doNotDisturbToggle.checked = this.doNotDisturb;
        } else {
            this.elements.doNotDisturbToggle.checked = this.doNotDisturb;
        }
    },
    
    // Save settings to localStorage
    saveSettings() {
        localStorage.setItem('chat_notifications_enabled', this.notificationsEnabled.toString());
        localStorage.setItem('chat_sound_enabled', this.soundEnabled.toString());
        localStorage.setItem('chat_do_not_disturb', this.doNotDisturb.toString());
        
        this.closeSettingsModal();
        
        // Show confirmation toast
        this.showToast('Settings saved successfully');
    },
    
    // Show toast notification
    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        // Fade out and remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    },
    
    // Open settings modal
    openSettingsModal() {
        this.elements.settingsModal.classList.remove('hidden');
        
        // Set current values
        this.elements.desktopNotificationsToggle.checked = this.notificationsEnabled;
        this.elements.soundToggle.checked = this.soundEnabled;
        this.elements.doNotDisturbToggle.checked = this.doNotDisturb;
    },
    
    // Close settings modal
    closeSettingsModal() {
        this.elements.settingsModal.classList.add('hidden');
    },
    
    // Request notification permission
    requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('This browser does not support notifications');
            return;
        }
        
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                this.notificationsEnabled = true;
                this.elements.desktopNotificationsToggle.checked = true;
            } else {
                this.notificationsEnabled = false;
                this.elements.desktopNotificationsToggle.checked = false;
                this.showToast('Notification permission denied');
            }
        });
    },

    // Update user status
    updateUserStatus(userId, status) {
        // Update user status in chats list
        this.chats.forEach(chat => {
            if (!chat.is_group && chat.user_id === userId) {
                chat.status = status;
            }
        });
        
        // Re-render chat list to show updated status
        this.renderChatList();
        
        // If the current chat is with this user, update the header
        if (this.currentChatId) {
            const currentChat = this.chats.find(c => c.id === this.currentChatId);
            if (currentChat && !currentChat.is_group && currentChat.user_id === userId) {
                this.elements.chatStatus.textContent = status === 'online' ? 'Online' : 
                    (status === 'away' ? 'Away' : 'Offline');
            }
        }
    },
    
    // Open new chat modal
    openNewChatModal() {
        const modal = this.elements.newChatModal;
        if (modal) {
            modal.classList.remove('hidden');
            this.elements.userSearch.value = '';
            this.elements.userSearchResults.innerHTML = '';
            this.selectedUsers = [];
            this.elements.groupMembersList.innerHTML = '';
            this.elements.groupChatName.value = '';
        }
    },
    
    // Close new chat modal
    closeNewChatModal() {
        const modal = this.elements.newChatModal;
        if (modal) {
            modal.classList.add('hidden');
        }
    },
    
    // Handle user search
    async handleUserSearch(query) {
        if (!query || query.length < 2) {
            this.elements.userSearchResults.innerHTML = `
                <div class="text-center text-gray-500 py-4">
                    Type at least 2 characters to search
                </div>
            `;
            return;
        }
        
        // Show loading indicator
        this.elements.userSearchResults.innerHTML = `
            <div class="text-center text-gray-500 py-4">
                <svg class="animate-spin h-5 w-5 mx-auto text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Searching...
            </div>
        `;
        
        try {
            const response = await fetch(`api/chat.php?action=search_users&query=${encodeURIComponent(query)}`);
            const data = await this.safeJsonParse(response, 'handleUserSearch');
            
            if (data && data.success) {
                if (data.users && data.users.length > 0) {
                    this.renderUserSearchResults(data.users);
                } else {
                    this.elements.userSearchResults.innerHTML = `
                        <div class="text-center text-gray-500 py-4">
                            No users found matching "${query}"
                        </div>
                    `;
                }
            } else {
                this.elements.userSearchResults.innerHTML = `
                    <div class="text-center text-red-500 py-4">
                        Error searching users: ${data ? data.message : 'Unknown error'}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error searching users:', error);
            this.elements.userSearchResults.innerHTML = `
                <div class="text-center text-red-500 py-4">
                    Error searching users: ${error.message}
                </div>
            `;
        }
    },
    
    // Render user search results
    renderUserSearchResults(users) {
        // Filter out already selected users and current user
        const filteredUsers = users.filter(user => 
            user.user_id !== this.currentUserId && 
            !this.selectedUsers.some(selected => selected.user_id === user.user_id)
        );
        
        if (filteredUsers.length === 0) {
            this.elements.userSearchResults.innerHTML = `
                <div class="text-center text-gray-500 py-4">
                    No more users available
                </div>
            `;
            return;
        }
        
        this.elements.userSearchResults.innerHTML = filteredUsers.map(user => `
            <div class="user-item p-3 border-b border-gray-200 hover:bg-gray-50 cursor-pointer flex items-center" 
                 data-user-id="${user.user_id}"
                 tabindex="0"
                 role="button"
                 aria-label="Select user ${user.username}"
                 onclick="ChatApp.selectUser(${user.user_id}, '${user.username}', '${user.profile_picture || ''}', '${user.status || 'offline'}')"
                 onkeydown="if(event.key === 'Enter' || event.key === ' ') ChatApp.selectUser(${user.user_id}, '${user.username}', '${user.profile_picture || ''}', '${user.status || 'offline'}')">
                <div class="relative w-10 h-10 rounded-full bg-gray-300 flex-shrink-0 mr-3 overflow-hidden">
                    ${user.profile_picture ? 
                        `<img src="${user.profile_picture}" alt="${user.username}" class="w-full h-full object-cover">` : 
                        `<div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-lg font-bold">
                            ${user.username.charAt(0).toUpperCase()}
                        </div>`
                    }
                    <span class="status-indicator status-${user.status || 'offline'} absolute bottom-0 right-0 transform translate-x-1 border-2 border-white"></span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900">${user.username}</h4>
                    <p class="text-sm text-gray-500">${user.status === 'online' ? 'Online' : 'Offline'}</p>
                </div>
                <div>
                    <button class="bg-primary-500 hover:bg-primary-600 text-white rounded-full p-1" title="Add to chat">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');
    },
    
    // Select user for chat
    selectUser(userId, username, profilePicture, status) {
        // Check if user is already selected
        if (this.selectedUsers.some(user => user.user_id === userId)) {
            return;
        }
        
        // Add user to selected users array
        const user = {
            user_id: userId,
            username: username,
            profile_picture: profilePicture,
            status: status
        };
        this.selectedUsers.push(user);
        
        // Update group members list
        this.updateGroupMembersList();
        
        // If only one user is selected, enable the create direct chat button
        if (this.selectedUsers.length === 1) {
            this.elements.createDirectChatButton.disabled = false;
            this.elements.createDirectChatButton.classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
            this.elements.createDirectChatButton.classList.add('bg-primary-600', 'hover:bg-primary-700', 'text-white');
        }
        
        // Remove the selected user from search results
        const userItem = this.elements.userSearchResults.querySelector(`[data-user-id="${userId}"]`);
        if (userItem) {
            userItem.remove();
        }
        
        // Validate group chat form
        this.validateGroupChatForm();
    },
    
    // Update group members list
    updateGroupMembersList() {
        if (this.selectedUsers.length === 0) {
            this.elements.groupMembersList.innerHTML = `
                <div class="text-center text-gray-500 py-2">
                    No members selected
                </div>
            `;
            return;
        }
        
        this.elements.groupMembersList.innerHTML = this.selectedUsers.map(user => `
            <div class="group-member-item flex items-center justify-between p-2 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gray-300 flex-shrink-0 mr-2 overflow-hidden">
                        ${user.profile_picture ? 
                            `<img src="${user.profile_picture}" alt="${user.username}" class="w-full h-full object-cover">` : 
                            `<div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-sm font-bold">
                                ${user.username.charAt(0).toUpperCase()}
                            </div>`
                        }
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 text-sm">${user.username}</h4>
                    </div>
                </div>
                <button class="text-gray-500 hover:text-red-600" 
                        onclick="ChatApp.removeSelectedUser(${user.user_id})"
                        title="Remove">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        `).join('');
    },
    
    // Remove selected user
    removeSelectedUser(userId) {
        // Remove user from selected users array
        this.selectedUsers = this.selectedUsers.filter(user => user.user_id !== userId);
        
        // Update group members list
        this.updateGroupMembersList();
        
        // If no users are selected, disable the direct chat button
        if (this.selectedUsers.length === 0) {
            this.elements.createDirectChatButton.disabled = true;
            this.elements.createDirectChatButton.classList.remove('bg-primary-600', 'hover:bg-primary-700', 'text-white');
            this.elements.createDirectChatButton.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
        }
        
        // Validate group chat form
        this.validateGroupChatForm();
    },
    
    // Validate group chat form
    validateGroupChatForm() {
        // Check if group chat can be created
        const isValid = this.selectedUsers.length >= 2 && 
                      this.elements.groupChatName.value.trim().length >= 3;
        
        // Update button state
        this.elements.createGroupChatButton.disabled = !isValid;
        
        if (isValid) {
            this.elements.createGroupChatButton.classList.remove('bg-gray-200', 'hover:bg-gray-300');
            this.elements.createGroupChatButton.classList.add('bg-primary-600', 'hover:bg-primary-700');
        } else {
            this.elements.createGroupChatButton.classList.remove('bg-primary-600', 'hover:bg-primary-700');
            this.elements.createGroupChatButton.classList.add('bg-gray-200', 'hover:bg-gray-300');
        }
    },
    
    // Create direct chat with selected user
    async createDirectChat() {
        if (this.selectedUsers.length !== 1) {
            this.showToast('Please select exactly one user');
            return;
        }
        
        const selectedUser = this.selectedUsers[0];
        
        try {
            // Show loading state
            this.elements.createDirectChatButton.disabled = true;
            this.elements.createDirectChatButton.innerHTML = `
                <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
            
            // Create direct chat
            const response = await fetch('api/chat.php?action=create_direct', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: selectedUser.user_id
                })
            });
            
            const data = await this.safeJsonParse(response, 'createDirectChat');
            
            if (data && data.success) {
                // Close modal
                this.closeNewChatModal();
                
                // Open the new chat
                if (data.chat_id) {
                    // Reload chats to ensure we have the latest data
                    await this.loadChats();
                    this.openChat(data.chat_id);
                } else {
                    // If no chat_id was returned, just refresh the chat list
                    this.loadChats();
                }
                
                this.showToast(`Chat with ${selectedUser.username} created`);
            } else {
                console.error('Failed to create direct chat:', data ? data.message : 'No data returned');
                this.showToast(data && data.message ? data.message : 'Failed to create chat');
            }
        } catch (error) {
            console.error('Error creating direct chat:', error);
            this.showToast('Error creating chat: ' + error.message);
        } finally {
            // Reset button
            this.elements.createDirectChatButton.disabled = false;
            this.elements.createDirectChatButton.textContent = 'Create Direct Chat';
        }
    },
    
    // Create group chat
    async createGroupChat() {
        const groupName = this.elements.groupChatName.value.trim();
        
        if (this.selectedUsers.length < 2) {
            this.showToast('Please select at least two users');
            return;
        }
        
        if (groupName.length < 3) {
            this.showToast('Please enter a group name (at least 3 characters)');
            return;
        }
        
        try {
            // Show loading state
            this.elements.createGroupChatButton.disabled = true;
            this.elements.createGroupChatButton.innerHTML = `
                <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
            
            // Get user IDs
            const userIds = this.selectedUsers.map(user => user.user_id);
            
            // Create group chat
            const response = await fetch('api/chat.php?action=create_group', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chat_name: groupName,
                    member_ids: userIds
                })
            });
            
            const data = await this.safeJsonParse(response, 'createGroupChat');
            
            if (data && data.success) {
                // Close modal
                this.closeNewChatModal();
                
                // Open the new chat
                if (data.chat_id) {
                    // Reload chats to ensure we have the latest data
                    await this.loadChats();
                    this.openChat(data.chat_id);
                } else {
                    // If no chat_id was returned, just refresh the chat list
                    this.loadChats();
                }
                
                this.showToast(`Group chat "${groupName}" created`);
            } else {
                console.error('Failed to create group chat:', data ? data.message : 'No data returned');
                this.showToast(data && data.message ? data.message : 'Failed to create chat');
            }
        } catch (error) {
            console.error('Error creating group chat:', error);
            this.showToast('Error creating chat: ' + error.message);
        } finally {
            // Reset button
            this.elements.createGroupChatButton.disabled = false;
            this.elements.createGroupChatButton.textContent = 'Create Group Chat';
        }
    },
    
    // Start global polling for updates
    startGlobalPolling() {
        // Poll for global updates every 10 seconds
        if (!this.globalPollingInterval) {
            this.globalPollingInterval = setInterval(() => {
                this.pollGlobalUpdates();
            }, 10000);
            
            // Initial poll for updates
            this.pollGlobalUpdates();
        }
    },
    
    // Stop global polling
    stopGlobalPolling() {
        if (this.globalPollingInterval) {
            clearInterval(this.globalPollingInterval);
            this.globalPollingInterval = null;
        }
    },
    
    // Poll for global updates (statuses, new chats, etc.)
    async pollGlobalUpdates() {
        try {
            // Fetch global updates (no chat_id) using last_update parameter
            const response = await fetch(`api/poll.php?last_update=${this.lastGlobalUpdateTimestamp || 0}`);
            const data = await this.safeJsonParse(response, 'pollGlobalUpdates');
            
            if (data && data.success) {
                // Process status updates
                if (data.status_updates && data.status_updates.length > 0) {
                    data.status_updates.forEach(update => {
                        this.updateUserStatus(update.user_id, update.status);
                    });
                }
                
                // Check for deleted chats
                if (data.deleted_chats && data.deleted_chats.length > 0) {
                    data.deleted_chats.forEach(chatId => {
                        console.log('Chat deleted notification received:', chatId);
                        
                        // Remove the chat from our local list
                        this.chats = this.chats.filter(chat => chat.id !== chatId);
                        
                        // Re-render the chat list
                        this.renderChatList();
                        
                        // If the user was viewing the deleted chat, show empty state
                        if (this.currentChatId === chatId) {
                            this.currentChatId = null;
                            this.elements.emptyState.classList.remove('hidden');
                            this.elements.activeChat.classList.add('hidden');
                            this.stopPolling();
                            this.showToast('This chat has been deleted by another participant');
                        }
                    });
                }
                
                // Process valid chats updates - remove any chats that are not in the valid list
                if (data.valid_chats && Array.isArray(data.valid_chats)) {
                    const validChatIds = new Set(data.valid_chats);
                    
                    // Filter out chats that are no longer valid
                    const initialChatCount = this.chats.length;
                    this.chats = this.chats.filter(chat => validChatIds.has(chat.id));
                    
                    // If chats were removed, re-render the list
                    if (this.chats.length < initialChatCount) {
                        console.log('Removed invalid chats. New chat count:', this.chats.length);
                        this.renderChatList();
                        
                        // If current chat was removed, show empty state
                        if (this.currentChatId && !validChatIds.has(this.currentChatId)) {
                            this.currentChatId = null;
                            this.elements.emptyState.classList.remove('hidden');
                            this.elements.activeChat.classList.add('hidden');
                            this.stopPolling();
                            this.showToast('This chat is no longer available');
                        }
                    }
                }
                
                // Process chat list updates
                if (data.chat_updates && data.chat_updates.length > 0) {
                    await this.loadChats(); // Reload all chats to get fresh data
                }
                
                // Update timestamp for next poll
                if (data.timestamp) {
                    this.lastGlobalUpdateTimestamp = data.timestamp;
                }
            }
        } catch (error) {
            console.error('Error polling for global updates:', error);
        }
    },

    // Update message status
    updateMessageStatus(messageId, isRead) {
        const messageElement = document.getElementById(`message-${messageId}`);
        if (messageElement) {
            const readStatusElement = messageElement.querySelector('.read-status');
            if (readStatusElement) {
                readStatusElement.innerHTML = isRead ? `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                ` : `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                `;
            }
        }
    },
    
    // Check if we should show a date separator
    shouldShowDateSeparator(timestamp) {
        if (!timestamp) return false;
        
        try {
            const messageDateObj = new Date(timestamp);
            if (isNaN(messageDateObj.getTime())) {
                console.warn('Invalid timestamp in shouldShowDateSeparator:', timestamp);
                return false; // Cannot determine if separator is needed for invalid date
            }
            const currentMessageDateForComparison = messageDateObj.toISOString().split('T')[0];
            
            // If no prior separator date is set, or if the current message's date is different, show separator
            return !this.lastDisplayedDateSeparatorString || currentMessageDateForComparison !== this.lastDisplayedDateSeparatorString;
        } catch (error) {
            console.error('Error in shouldShowDateSeparator:', error, timestamp);
            return false; // Default to not showing separator on error
        }
    },
    
    // Extract date from separator text
    getDateFromSeparatorText(text) {
        // Handle special cases
        if (text === 'Today') {
            return new Date().toLocaleDateString();
        } else if (text === 'Yesterday') {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            return yesterday.toLocaleDateString();
        }
        
        // Try to parse the date text
        try {
            // Convert from "Jan 15, 2023" format to Date object
            const date = new Date(text);
            return date.toLocaleDateString();
        } catch (e) {
            console.error('Error parsing date:', e);
            return '';
        }
    },

    // Safe JSON parsing
    async safeJsonParse(response, action) {
        if (!response) {
            console.error(`No response object in ${action}`);
            return { success: false, message: 'No server response' };
        }
        
        try {
            // First check if response is ok
            if (!response.ok) {
                console.error(`HTTP error in ${action}: ${response.status} ${response.statusText}`);
                return { 
                    success: false, 
                    message: `Server error: ${response.status} ${response.statusText}` 
                };
            }
            
            // Try to get the response text
            const text = await response.text();
            
            // If empty response
            if (!text || text.trim() === '') {
                console.error(`Empty response in ${action}`);
                return { success: false, message: 'Empty server response' };
            }
            
            // Try to parse as JSON
            try {
                return JSON.parse(text);
            } catch (parseError) {
                console.error(`Invalid JSON in ${action} response:`, text.substring(0, 200));
                console.error('Parse error:', parseError);
                
                // Show detailed error for debugging
                this.showDebugError(action, text);
                
                return { 
                    success: false, 
                    message: `Invalid server response: ${parseError.message}`,
                    originalText: text.substring(0, 500)
                };
            }
        } catch (error) {
            console.error(`Error reading ${action} response:`, error);
            return { 
                success: false, 
                message: `Error reading response: ${error.message}` 
            };
        }
    },
    
    // Відображаємо деталі помилки для користувача (тільки в режимі розробки)
    showDebugError(action, responseText) {
        // Показуємо тільки перші 500 символів, щоб не переповнювати консоль
        const truncatedText = responseText.substring(0, 500) + (responseText.length > 500 ? '...' : '');
        
        // Створюємо елемент помилки, якщо він ще не існує
        let debugElement = document.getElementById('debug-error');
        if (!debugElement) {
            debugElement = document.createElement('div');
            debugElement.id = 'debug-error';
            debugElement.className = 'fixed bottom-4 left-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50 max-w-lg';
            document.body.appendChild(debugElement);
        }
        
        // Заповнюємо елемент інформацією про помилку
        debugElement.innerHTML = `
            <div class="flex justify-between">
                <strong class="font-bold">Error in ${action}</strong>
                <button onclick="this.parentNode.parentNode.remove()" class="ml-4 font-bold">&times;</button>
            </div>
            <div class="mt-2">
                <p class="text-sm">Server returned invalid JSON. This might indicate a PHP error:</p>
                <pre class="mt-2 text-xs bg-red-50 p-2 rounded overflow-auto max-h-32">${truncatedText}</pre>
                <button onclick="this.parentNode.parentNode.remove()" class="mt-2 bg-red-600 text-white px-3 py-1 rounded text-xs">Close</button>
            </div>
        `;
        
        // Автоматично видаляємо повідомлення через 30 секунд
        setTimeout(() => {
            if (debugElement && debugElement.parentNode) {
                debugElement.parentNode.removeChild(debugElement);
            }
        }, 30000);
    },

    // Setup delete chat modal
    setupDeleteChatModal() {
        // Store these as instance properties for use in confirmDeleteChat
        this.pendingDeleteChatId = null;
        
        // Setup event listeners
        this.elements.confirmDeleteChatBtn.addEventListener('click', () => {
            if (this.pendingDeleteChatId) {
                this.deleteChat(this.pendingDeleteChatId);
                this.closeDeleteChatModal();
            }
        });
        
        this.elements.cancelDeleteChatBtn.addEventListener('click', this.closeDeleteChatModal.bind(this));
        this.elements.deleteChatBackdrop.addEventListener('click', this.closeDeleteChatModal.bind(this));
    },
    
    // Show delete chat modal
    openDeleteChatModal() {
        this.elements.deleteChatModal.classList.remove('hidden');
    },
    
    // Hide delete chat modal
    closeDeleteChatModal() {
        this.elements.deleteChatModal.classList.add('hidden');
        this.pendingDeleteChatId = null;
    },
    
    // Confirm deletion of a chat
    confirmDeleteChat(chatId, chatName) {
        // Set pending delete chat ID
        this.pendingDeleteChatId = chatId;
        
        // Update modal message
        this.elements.deleteChatMessage.textContent = `Are you sure you want to delete your conversation with "${chatName}"? This action cannot be undone.`;
        
        // Show modal
        this.openDeleteChatModal();
    },
    
    // Delete a chat
    async deleteChat(chatId) {
        try {
            const response = await fetch(`api/chat.php?action=delete_chat&chat_id=${chatId}`, {
                method: 'DELETE'
            });
            
            const data = await this.safeJsonParse(response, 'deleteChat');
            
            if (data && data.success) {
                // Remove chat from the list
                this.chats = this.chats.filter(chat => chat.id !== chatId);
                
                // Re-render the chat list
                this.renderChatList();
                
                // If the deleted chat was the active chat, show empty state
                if (this.currentChatId === chatId) {
                    this.currentChatId = null;
                    this.elements.emptyState.classList.remove('hidden');
                    this.elements.activeChat.classList.add('hidden');
                    this.stopPolling();
                }
                
                this.showToast('Chat deleted successfully');
            } else {
                this.showToast(data?.message || 'Failed to delete chat');
            }
        } catch (error) {
            console.error('Error deleting chat:', error);
            this.showToast('Error deleting chat');
        }
    },

    // Toggle emoji picker
    toggleEmojiPicker() {
        console.log('Toggle emoji picker called', { currentlyOpen: this._emojiPickerOpen });
        
        // Check if the emoji picker container exists
        if (!this.elements.emojiPickerContainer) {
            console.error('Emoji picker container not found');
            return;
        }
        
        if (this._emojiPickerOpen) {
            // If already open, close it
            console.log('Emoji picker is open, closing it');
            this.closeEmojiPickerModal();
        } else {
            // If closed, open it
            console.log('Emoji picker is closed, opening it');
            this.openEmojiPickerModal();
        }
    },

    // Open emoji picker modal
    openEmojiPickerModal() {
        console.log('Opening emoji picker modal');
        
        // First, remove any existing document click handler to prevent conflicts
        if (this.handleDocumentClick) {
            document.removeEventListener('click', this.handleDocumentClick);
            this.handleDocumentClick = null;
        }
        
        // Set flag
        this._emojiPickerOpen = true;
        
        // Load emojis if not already loaded
        if (!this.emojisLoaded) {
            this.loadEmojis();
        }
        
        // Make sure elements exist
        if (this.elements.emojiPickerContainer && this.elements.emojiPicker && this.elements.emojiBackdrop) {
            // Position the emoji picker relative to the button
            if (this.elements.emojiButton) {
                const buttonRect = this.elements.emojiButton.getBoundingClientRect();
                
                // Calculate position to show above the emoji button
                const pickerWidth = 320; // width of the emoji picker (w-80 = 20rem = 320px)
                const leftPosition = Math.max(10, buttonRect.left - (pickerWidth / 2) + (buttonRect.width / 2));
                const topPosition = buttonRect.top - 300; // Show above the button
                
                this.elements.emojiPickerContainer.style.position = 'fixed';
                this.elements.emojiPickerContainer.style.top = `${topPosition}px`;
                this.elements.emojiPickerContainer.style.left = `${leftPosition}px`;
                this.elements.emojiPickerContainer.style.zIndex = '1050';
                
                console.log('Positioned emoji picker at:', {top: topPosition, left: leftPosition});
            }
            
            // Show the emoji picker container and backdrop
            this.elements.emojiPickerContainer.classList.remove('hidden');
            this.elements.emojiBackdrop.classList.remove('hidden');
            
            // Add slight delay to allow display:block to take effect before transform
            setTimeout(() => {
                this.elements.emojiPicker.classList.remove('scale-95', 'opacity-0');
                this.elements.emojiPicker.classList.add('scale-100', 'opacity-100');
                this.elements.emojiBackdrop.classList.remove('bg-opacity-0');
                this.elements.emojiBackdrop.classList.add('bg-opacity-25');
            }, 10); // Small delay
            
            // Add document click handler to close when clicking outside
            // Use setTimeout to avoid immediate closing due to the same click event
            setTimeout(() => {
                document.addEventListener('click', this.handleDocumentClick = (e) => {
                    // Only close if the click is outside the emoji picker and not on the emoji button
                    if (this._emojiPickerOpen && 
                        !this.elements.emojiPicker.contains(e.target) && 
                        e.target !== this.elements.emojiButton &&
                        !this.elements.emojiButton.contains(e.target)) {
                        this.closeEmojiPickerModal();
                    }
                });
            }, 300); // Increased delay to prevent immediate closing
        }
    },

    // Close emoji picker modal
    closeEmojiPickerModal() {
        console.log('Closing emoji picker modal');
        
        // Reset flag
        this._emojiPickerOpen = false;
        
        // Make sure elements exist
        if (this.elements.emojiPickerContainer && this.elements.emojiPicker && this.elements.emojiBackdrop) {
            this.elements.emojiPicker.classList.remove('scale-100', 'opacity-100');
            this.elements.emojiPicker.classList.add('scale-95', 'opacity-0');
            this.elements.emojiBackdrop.classList.remove('bg-opacity-25');
            this.elements.emojiBackdrop.classList.add('bg-opacity-0');
            
            // Hide after animation
            setTimeout(() => {
            this.elements.emojiPickerContainer.classList.add('hidden');
                this.elements.emojiBackdrop.classList.add('hidden');
            }, 300); // Match animation duration
        }
        
        // Remove document click handler
        if (this.handleDocumentClick) {
            document.removeEventListener('click', this.handleDocumentClick);
            this.handleDocumentClick = null;
        }
    },

    // Load emojis
    loadEmojis() {
        console.log('Loading emojis into picker');
        
        // Common emoji categories
        const emojis = [
            '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '🥲', '☺️', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘',
            '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥸', '🤩', '🥳', '😏', '😒', '😞', '😔',
            '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😮', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔',
            '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐',
            '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽',
            '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾', '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌',
            '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌',
            '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦵', '🦿', '🦶', '👣', '👂', '🦻', '👃', '🫀', '🫁', '🧠',
            '🦷', '🦴', '👀', '👁', '👅', '👄', '💋', '🩸', '❤️', '🧡', '💛', '💚', '💙', '💜', '🤎', '🖤', '🤍', '💔', '❣️',
            '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐',
            '⛎', '♈️', '♉️', '♊️', '♋️', '♌️', '♍️', '♎️', '♏️', '♐️', '♑️', '♒️', '♓️', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴'
        ];
        
        // Verify the emoji grid element exists
        if (!this.elements.emojiGrid) {
            console.error('Emoji grid element not found');
            return;
        }
        
        // Create emoji grid with properly centered emojis
        let emojiHtml = '';
        emojis.forEach(emoji => {
            emojiHtml += `
                <button type="button" class="emoji-item flex items-center justify-center w-10 h-10 hover:bg-gray-100 rounded transition" 
                    style="cursor: pointer; font-size: 1.5rem; width: 40px; height: 40px;">
                    ${emoji}
                </button>
            `;
        });
        
        this.elements.emojiGrid.innerHTML = emojiHtml;
        console.log('Initialized emoji grid with centered emojis');
        
        // Add click event to each emoji button
        const emojiItems = this.elements.emojiGrid.querySelectorAll('.emoji-item');
        
        if (emojiItems.length === 0) {
            console.error('No emoji items found after initialization');
        } else {
            console.log(`Initialized ${emojiItems.length} emoji buttons`);
        }
        
        // Properly handle emoji selection
        emojiItems.forEach(item => {
            // Remove any existing event listeners
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
            
            // Add new event listener
            newItem.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // This is critical to prevent event bubbling
                const emoji = newItem.textContent.trim();
                console.log('Emoji clicked:', emoji);
                this.insertEmoji(emoji);
            });
        });
        
        console.log(`Emojis loaded: ${emojiItems.length} emojis available`);
        this.emojisLoaded = true;
    },

    // Insert emoji into message input
    insertEmoji(emoji) {
        console.log('Inserting emoji:', emoji);
        
        if (!this.elements.messageText) {
            console.error('Message text element not found');
            return;
        }
        
        const input = this.elements.messageText;
        const startPos = input.selectionStart || 0;
        const endPos = input.selectionEnd || 0;
        const text = input.value || '';
        
        input.value = text.substring(0, startPos) + emoji + text.substring(endPos);
        
        // Set selection position after the inserted emoji
        input.selectionStart = input.selectionEnd = startPos + emoji.length;
        
        // Focus the input
        input.focus();
        
        // Trigger an input event to handle any auto-resize functionality
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
        
        // Keep the emoji picker open to allow selecting multiple emojis
        // Return focus to the input field after inserting emoji
        setTimeout(() => {
            input.focus();
        }, 10);
    },

    // Open file selector
    openFileSelector() {
        // Prevent multiple file dialogs from opening
        if (this._fileDialogOpen) {
            console.log('File dialog already open, ignoring click');
            return;
        }
        
        // Set flag to indicate dialog is open
        this._fileDialogOpen = true;
        
        // Open file dialog
        this.elements.fileInput.click();
        
        // Reset flag after a short delay
        setTimeout(() => {
            this._fileDialogOpen = false;
        }, 500);
    },

    // Handle file selection
    handleFileSelection(e) {
        const files = e.target.files;
        
        if (files.length === 0) {
            return;
        }
        
        // Limit number of files
        if (files.length > 5) {
            this.showToast('You can only upload up to 5 files at once');
            return;
        }
        
        // Check file sizes
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > 50 * 1024 * 1024) { // 50MB
                this.showToast(`File ${files[i].name} exceeds the 50MB limit`);
                return;
            }
        }
        
        // Upload files
        for (let i = 0; i < files.length; i++) {
            this.uploadFile(files[i]);
        }
        
        // Clear file input
        this.elements.fileInput.value = '';
    },

    // Upload a file
    async uploadFile(file) {
        if (!this.currentChatId) {
            this.showToast('Please select a chat first');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('file', file);
        formData.append('chat_id', this.currentChatId);
        
        // Show temporary message with spinner
        const tempId = 'temp_' + Date.now();
        const tempMessage = {
            message_id: tempId,
            sender_id: this.currentUserId,
            message_text: file.name, // Use the original filename as text for the temporary message
            message_type: this.getFileType(file),
            sent_at: new Date().toISOString(),
            is_uploading: true,
            file_object: file, // Store the actual File object for potential preview
            localPreviewUrl: null, // Initialize localPreviewUrl
            is_sender: true, // Ensure it's marked as sender for correct styling
            sender_name: this.currentUserName, // Add sender info for consistency
            sender_profile_picture: this.currentUserAvatar
        };
        
        // Generate local preview URL for images and store it
        if (tempMessage.message_type === 'image' && tempMessage.file_object) {
            try {
                tempMessage.localPreviewUrl = URL.createObjectURL(tempMessage.file_object);
            } catch (e) {
                console.error("Error creating object URL for preview:", e);
                tempMessage.localPreviewUrl = null; // Ensure it's null if creation fails
            }
        }
        
        // Add temporary message to UI
        this.addMessageToUI(tempMessage);
        
        try {
            console.log('Uploading file:', file.name, 'type:', file.type, 'size:', file.size);
            
            // Upload file
            const response = await fetch('api/chat.php?action=upload_file', {
                method: 'POST',
                body: formData
            });
            
            const data = await this.safeJsonParse(response, 'uploadFile');
            console.log('Upload response:', data);
            
            if (data && data.success) {
                // Create a real message with the file URL
                const messageDataFromPHP = data.data || {}; // Default to empty object

                const realMessage = {
                    ...messageDataFromPHP, // Spread PHP data first
                    message_id: data.message_id || messageDataFromPHP.message_id || tempId,
                    sender_id: this.currentUserId,
                    message_text: file.name, // Default to original filename
                    message_type: this.getFileType(file),
                    sent_at: messageDataFromPHP.sent_at || new Date().toISOString(), // Use PHP sent_at if available
                    created_at: messageDataFromPHP.created_at || new Date().toISOString(), // Use PHP created_at if available
                    file_url: data.file_url, // Explicitly use file_url from the top-level API response
                    is_sender: true,
                    sender_name: this.currentUserName,
                    sender_profile_picture: this.currentUserAvatar
                };
                
                // If PHP provided a specific message_text different from the filename, prefer that.
                if (messageDataFromPHP.message_text && messageDataFromPHP.message_text !== file.name) {
                    realMessage.message_text = messageDataFromPHP.message_text;
                }
                
                console.log('Creating real message with data (Chat.js - uploadFile):', JSON.stringify(realMessage));
                
                // Update temporary message with real data
                this.updateTempMessage(tempId, realMessage);
                
                // Update chat list with new last message
                this.updateChatLastMessage(this.currentChatId, `File: ${file.name}`);
            } else {
                // Show error
                console.error('File upload failed:', data ? data.message : 'Unknown error');
                this.showMessageError(tempId);
            }
        } catch (error) {
            console.error('Error uploading file:', error);
            this.showMessageError(tempId);
        }
    },

    // Get file type based on mime type
    getFileType(file) {
        const fileType = file.type;
        
        if (fileType.startsWith('image/')) {
            return 'image';
        } else if (fileType.startsWith('video/')) {
            return 'video';
        } else if (fileType.startsWith('audio/')) {
            return 'audio';
        } else {
            return 'file';
        }
    },

    // Audio recording variables
    mediaRecorder: null,
    audioChunks: [],
    recordingStartTime: null,
    recordingTimer: null,

    // Start audio recording
    async startAudioRecording() {
        try {
            console.log('Starting audio recording...');
            
            // Check if browser supports basic audio recording requirements
            if (!window.MediaRecorder) {
                console.error('MediaRecorder not supported in this browser');
                this.showToast('Your browser does not support audio recording');
                return;
            }
            
            // Check if navigator.mediaDevices is available (this is often the culprit)
            if (!navigator || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                console.error('navigator.mediaDevices.getUserMedia is not available');
                this.showToast('Audio recording is not supported in this browser or requires HTTPS');
                return;
            }
            
            console.log('Browser supports audio recording, requesting permission...');
            
            // Request user permission with explicit audio constraints
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            console.log('Microphone permission granted, setting up recorder...');
            
            // Initialize media recorder with supported MIME type
            const mimeType = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp3';
            this.mediaRecorder = new MediaRecorder(stream, { mimeType });
            this.audioChunks = [];
            
            // Handle data available event
            this.mediaRecorder.addEventListener('dataavailable', (e) => {
                if (e.data && e.data.size > 0) {
                    this.audioChunks.push(e.data);
                    console.log(`Audio chunk received: ${e.data.size} bytes`);
                }
            });
            
            // Handle recording stop event
            this.mediaRecorder.addEventListener('stop', () => {
                console.log('Recording stopped, processing audio...');
                
                if (this.audioChunks.length === 0) {
                    console.error('No audio data recorded');
                    this.showToast('No audio data recorded');
                    return;
                }
                
                // Create audio blob
                const audioBlob = new Blob(this.audioChunks, { type: mimeType });
                console.log(`Audio blob created: ${audioBlob.size} bytes`);
                
                // Create file object from blob
                const audioFile = new File([audioBlob], `voice_message_${Date.now()}.${mimeType.split('/')[1]}`, {
                    type: mimeType
                });
                
                // Upload audio file
                this.uploadFile(audioFile);
                
                // Reset recording variables
                this.audioChunks = [];
                this.mediaRecorder = null;
                
                // Hide recording UI
                if (this.elements.audioRecordingUI) {
                    this.elements.audioRecordingUI.classList.add('hidden');
                }
                
                // Clear recording timer
                if (this.recordingTimer) {
                    clearInterval(this.recordingTimer);
                    this.recordingTimer = null;
                }
                
                // Stop all tracks in the stream
                stream.getTracks().forEach(track => track.stop());
                console.log('Audio stream tracks stopped');
            });
            
            // Set up error handling for the recorder
            this.mediaRecorder.addEventListener('error', (e) => {
                console.error('MediaRecorder error:', e);
                this.showToast(`Recording error: ${e.error.message || 'Unknown error'}`);
            });
            
            // Start recording
            this.mediaRecorder.start();
            console.log('MediaRecorder started');
            
            // Show recording UI
            if (this.elements.audioRecordingUI) {
                this.elements.audioRecordingUI.classList.remove('hidden');
            } else {
                console.error('Audio recording UI element not found');
            }
            
            // Set recording start time
            this.recordingStartTime = Date.now();
            
            // Start recording timer
            this.recordingTimer = setInterval(() => {
                this.updateRecordingTime();
            }, 1000);
            
        } catch (error) {
            console.error('Error starting audio recording:', error);
            
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                this.showToast('Microphone access denied. Please allow microphone access to record audio.');
            } else if (error.name === 'NotFoundError') {
                this.showToast('No microphone device found. Please connect a microphone and try again.');
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                this.showToast('Could not access your microphone. It may be in use by another application.');
            } else if (error.name === 'SecurityError') {
                this.showToast('Audio recording requires a secure context (HTTPS).');
            } else if (error.name === 'TypeError' && error.message.includes('getUserMedia')) {
                this.showToast('Audio recording not supported in this browser environment.');
            } else {
                this.showToast('Error starting audio recording: ' + error.message);
            }
            
            // Reset any partial setup
            if (this.recordingTimer) {
                clearInterval(this.recordingTimer);
                this.recordingTimer = null;
            }
            
            if (this.elements.audioRecordingUI) {
                this.elements.audioRecordingUI.classList.add('hidden');
            }
            
            this.audioChunks = [];
            this.mediaRecorder = null;
        }
    },

    // Update recording time display
    updateRecordingTime() {
        if (!this.recordingStartTime) return;
        
        const elapsed = Math.floor((Date.now() - this.recordingStartTime) / 1000);
        const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const seconds = (elapsed % 60).toString().padStart(2, '0');
        
        this.elements.recordingTime.textContent = `${minutes}:${seconds}`;
        
        // Auto-stop recording after 5 minutes
        if (elapsed >= 300) {
            this.stopAudioRecording();
        }
    },

    // Stop audio recording
    stopAudioRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
        }
    },

    // Cancel audio recording
    cancelAudioRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            // Stop the recorder but don't save
            this.mediaRecorder.stop();
            
            // Reset recording variables
            this.audioChunks = [];
            this.mediaRecorder = null;
            
            // Hide recording UI
            this.elements.audioRecordingUI.classList.add('hidden');
            
            // Clear recording timer
            if (this.recordingTimer) {
                clearInterval(this.recordingTimer);
                this.recordingTimer = null;
            }
        }
    },

    // Render a message content based on its type
    renderMessageContent(message) {
        const isOutgoing = message.sender_id === this.currentUserId || message.is_sender;
        const textClass = isOutgoing ? 'text-white' : 'text-gray-800';
        
        let contentHtml = '';
        
        // Debug information about the message being rendered
        console.log('Rendering message (renderMessageContent):', {
            id: message.id || message.message_id,
            type: message.message_type,
            fileUrl: message.file_url,
            text: message.message_text,
            isSender: isOutgoing
        });
        
        // Handle different message types
        switch (message.message_type) {
            case 'image':
                if (message.is_uploading && message.localPreviewUrl) {
                    // Show a local preview while uploading if the localPreviewUrl is available
                    contentHtml = `
                        <div class="message-content relative">
                            <img src="${message.localPreviewUrl}" alt="${message.message_text || 'Uploading image'}" class="max-w-full rounded-lg max-h-60 opacity-50">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                } else if (message.is_uploading && message.file_object && !message.localPreviewUrl) {
                    // Fallback if localPreviewUrl couldn't be created but it IS uploading
                    contentHtml = `
                        <div class="message-content relative">
                            <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center opacity-50">
                                <svg class="animate-spin h-8 w-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                } else if (!message.file_url) {
                    console.error('Missing file_url for image message (renderMessageContent):', message);
                    contentHtml = `
                        <div class="message-content">
                            <p class="text-red-500">Error: Image not available</p>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="message-content">
                            <img src="${message.file_url}" alt="${message.message_text || 'Image'}" class="max-w-full rounded-lg max-h-60 cursor-pointer" onclick="window.open('${message.file_url}', '_blank')">
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                }
                break;
                
            case 'video':
                if (!message.file_url) {
                    console.error('Missing file_url for video message (renderMessageContent):', message);
                    contentHtml = `
                        <div class="message-content">
                            <p class="text-red-500">Error: Video not available</p>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="message-content">
                            <video src="${message.file_url}" controls class="max-w-full rounded-lg max-h-60"></video>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                }
                break;
                
            case 'audio':
                if (!message.file_url) {
                    console.error('Missing file_url for audio message (renderMessageContent):', message);
                    contentHtml = `
                        <div class="message-content">
                            <p class="text-red-500">Error: Audio not available</p>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="message-content">
                            <audio src="${message.file_url}" controls class="max-w-full w-full"></audio>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                }
                break;
                
            case 'file':
                if (!message.file_url) {
                    console.error('Missing file_url for file message (renderMessageContent):', message);
                    contentHtml = `
                        <div class="message-content">
                            <p class="text-red-500">Error: File not available</p>
                            ${message.message_text ? `<p class="text-xs ${textClass} opacity-70 mt-1">${this.formatMessageText(message.message_text)}</p>` : ''}
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="message-content">
                            <a href="${message.file_url}" target="_blank" rel="noopener noreferrer" class="flex items-center p-2 bg-black bg-opacity-10 rounded-lg hover:bg-opacity-20">
                                <div class="mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 ${isOutgoing ? 'text-white' : 'text-primary-500'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="text-sm ${textClass} truncate font-medium">${message.message_text || 'File'}</p>
                                    <p class="text-xs ${textClass} opacity-70">Download</p>
                                </div>
                            </a>
                        </div>
                    `;
                }
                break;
                
            case 'text': // Explicitly handle text type
            default: // Default to text if type is unknown or not set
                contentHtml = `
                    <div class="message-content">
                        <p class="message-text">${this.formatMessageText(message.message_text || message.content || '')}</p>
                    </div>
                `;
        }
        
        // Add loading indicator for uploading files (though this might be less relevant if called for already uploaded files)
        if (message.is_uploading) {
            contentHtml += `
                <div class="flex items-center mt-1">
                    <svg class="animate-spin h-4 w-4 mr-1 ${isOutgoing ? 'text-white' : 'text-primary-500'}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs ${isOutgoing ? 'text-white' : 'text-gray-500'}">Uploading...</span>
                </div>
            `;
        }
        
        return contentHtml;
    },

    // Get read status icon
    getReadStatusIcon(status) {
        if (status === 'read') {
            // Double white checkmark character
            return `<span class="text-white">✓✓</span>`;
        } else if (status === 'sent') {
            // Single white checkmark character with opacity
            return `<span class="text-white opacity-60">✓</span>`;
        }
        return ''; // No icon by default
    },

    // Update message read status icon in the UI
    updateMessageReadStatusInUI(messageId, isRead) {
        const statusElement = document.getElementById(`message-status-message-${messageId}`);
        if (statusElement) {
            const status = isRead ? 'read' : 'sent';
            statusElement.innerHTML = this.getReadStatusIcon(status);
            console.log(`Updated read status for message ${messageId} to ${status}`);
        } else {
            // This can happen if the message is not yet in the DOM or ID is slightly different
            // Attempt to find it with the message-ID prefix that addMessageToUI uses for the span
            const fullStatusElementId = `message-status-message-${messageId}`;
            const altStatusElement = document.getElementById(fullStatusElementId);
             if (altStatusElement) {
                const status = isRead ? 'read' : 'sent';
                altStatusElement.innerHTML = this.getReadStatusIcon(status);
                console.log(`Updated read status for message ${messageId} (using alt ID: ${fullStatusElementId}) to ${status}`);
            } else {
                console.warn(`Status element for message ${messageId} (or ${fullStatusElementId}) not found in UI to update read status.`);
            }
        }
    },
};

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Set current user info
    ChatApp.currentUserId = window.currentUserId || null;
    ChatApp.currentUserName = window.currentUserName || '';
    ChatApp.currentUserAvatar = window.currentUserAvatar || '';
    
    ChatApp.init();
}); 