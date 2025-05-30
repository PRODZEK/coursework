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

    // DOM Elements
    elements: {
        chatList: document.getElementById('chats-list'),
        chatMessages: document.getElementById('chat-messages'),
        messageForm: document.getElementById('message-form'),
        messageText: document.getElementById('message-text'),
        emptyState: document.getElementById('empty-chat-state'),
        activeChat: document.getElementById('active-chat'),
        chatHeader: document.getElementById('chat-header'),
        chatName: document.getElementById('chat-name'),
        chatStatus: document.getElementById('chat-status'),
        chatAvatar: document.getElementById('chat-avatar'),
        newChatButton: document.getElementById('new-chat-button'),
        startNewChatButton: document.getElementById('start-new-chat-button'),
        newChatModal: document.getElementById('new-chat-modal'),
        closeNewChatModal: document.getElementById('close-new-chat-modal'),
        userSearch: document.getElementById('user-search'),
        userSearchResults: document.getElementById('user-search-results'),
        createDirectChatButton: document.getElementById('create-direct-chat-button'),
        createGroupChatButton: document.getElementById('create-group-chat-button'),
        groupChatName: document.getElementById('group-chat-name'),
        groupMembersList: document.getElementById('group-members-list'),
        // Settings elements
        settingsButton: document.getElementById('settings-button'),
        settingsModal: document.getElementById('settings-modal'),
        closeSettingsModal: document.getElementById('close-settings-modal'),
        desktopNotificationsToggle: document.getElementById('desktop-notifications-toggle'),
        soundToggle: document.getElementById('sound-toggle'),
        doNotDisturbToggle: document.getElementById('do-not-disturb-toggle'),
        saveSettingsButton: document.getElementById('save-settings-button')
    },

    // Initialize the application
    init() {
        this.bindEvents();
        this.loadChats();
        this.setupNotifications();
        this.setupNotificationSound();
        this.loadSettings();
    },

    // Bind event listeners
    bindEvents() {
        // Message form submission
        this.elements.messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        // New chat button click
        this.elements.newChatButton.addEventListener('click', () => this.openNewChatModal());
        this.elements.startNewChatButton.addEventListener('click', () => this.openNewChatModal());
        
        // Close modal button click
        this.elements.closeNewChatModal.addEventListener('click', () => this.closeNewChatModal());
        
        // User search input
        this.elements.userSearch.addEventListener('input', (e) => this.handleUserSearch(e.target.value));
        
        // Direct chat button click
        this.elements.createDirectChatButton.addEventListener('click', () => this.createDirectChat());
        
        // Group chat button click
        this.elements.createGroupChatButton.addEventListener('click', () => this.createGroupChat());
        
        // Group chat name input
        this.elements.groupChatName.addEventListener('input', () => this.validateGroupChatForm());
        
        // Settings modal
        this.elements.settingsButton.addEventListener('click', () => this.openSettingsModal());
        this.elements.closeSettingsModal.addEventListener('click', () => this.closeSettingsModal());
        this.elements.saveSettingsButton.addEventListener('click', () => this.saveSettings());
        
        // Settings toggles
        this.elements.desktopNotificationsToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                this.requestNotificationPermission();
            } else {
                this.notificationsEnabled = false;
            }
        });
        
        this.elements.soundToggle.addEventListener('change', (e) => {
            this.soundEnabled = e.target.checked;
        });
        
        this.elements.doNotDisturbToggle.addEventListener('change', (e) => {
            this.doNotDisturb = e.target.checked;
        });
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
            <div class="chat-item p-3 border-b border-gray-200 hover:bg-gray-50 cursor-pointer" 
                 data-chat-id="${chat.id}" 
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
                    ${chat.unread_count ? `
                    <div class="ml-2">
                        <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary-500 text-xs font-medium text-white">
                            ${chat.unread_count}
                        </span>
                    </div>` : ''}
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
            return;
        }

        // Sort messages by timestamp to ensure chronological order (oldest first)
        messages.sort((a, b) => {
            const timeA = new Date(a.created_at || a.sent_at || 0).getTime();
            const timeB = new Date(b.created_at || b.sent_at || 0).getTime();
            return timeA - timeB; // Ascending order (oldest first)
        });

        let currentDate = '';
        let html = '';

        messages.forEach(message => {
            // Use sent_at for timestamp if created_at is not provided
            const messageTimestamp = message.sent_at || message.created_at;
            const messageDate = new Date(messageTimestamp).toLocaleDateString();
            
            // Add date separator if this is a new date
            if (messageDate !== currentDate) {
                html += `
                    <div class="flex justify-center my-4">
                        <div class="px-4 py-1 bg-gray-100 rounded-full text-gray-500 text-xs">
                            ${this.formatDateForMessages(messageDate)}
                        </div>
                    </div>
                `;
                currentDate = messageDate;
            }
            
            // Determine if message is from current user
            const isCurrentUser = message.sender_id === this.currentUserId || message.is_sender;
            
            // Ensure message content is properly displayed by checking all possible fields
            let messageContent = '';
            
            if (message.content && typeof message.content === 'string') {
                messageContent = message.content;
            } else if (message.message_text && typeof message.message_text === 'string') {
                messageContent = message.message_text;
            } else if (message.text && typeof message.text === 'string') {
                messageContent = message.text;
            } else if (message.data && message.data.message_text) {
                messageContent = message.data.message_text;
            } else if (typeof message === 'string') {
                messageContent = message;
            } else {
                // Try to extract message from unknown structure
                if (typeof message === 'object') {
                    for (const key in message) {
                        if (typeof message[key] === 'string' && key !== 'sender_name' && key !== 'sender_avatar') {
                            messageContent = message[key];
                            break;
                        }
                    }
                }
                
                if (!messageContent) {
                    console.warn('Message content not found in renderMessages. Message structure:', JSON.stringify(message));
                    messageContent = 'Failed to display message content';
                }
            }
            
            html += `
                <div id="message-${message.id || message.message_id || Date.now()}" class="flex ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-2">
                    ${!isCurrentUser ? `
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
                        ${!isCurrentUser && message.is_group ? 
                            `<div class="text-xs font-medium text-gray-500 mb-1">${message.sender_name}</div>` : ''
                        }
                        <div class="${isCurrentUser ? 'message-outgoing' : 'message-incoming'}">
                            <div class="message-content">
                                <p class="message-text">${this.formatMessageText(messageContent)}</p>
                            </div>
                            <div class="text-xs text-right mt-1 message-time ${isCurrentUser ? 'text-white text-opacity-70' : 'text-gray-500'}">
                                ${this.formatMessageTime(messageTimestamp)}
                                ${message.is_read && isCurrentUser ? 
                                    '<span class="ml-1">✓</span>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        this.elements.chatMessages.innerHTML = html;
        
        // Scroll to bottom after initial render
        this.scrollToBottom();
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
        const messageText = this.elements.messageText.value.trim();
        
        if (!messageText || !this.currentChatId) return;
        
        // Clear input
        this.elements.messageText.value = '';
        
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
    addMessageToUI(message) {
        if (!message) return;
        
        // Create a unique ID for the message element
        const messageId = `message-${message.id || message.message_id || message.temp_id || Date.now()}`;
        
        // Check if message already exists in the DOM
        const existingMessage = document.getElementById(messageId);
        if (existingMessage) {
            // If it's a temporary message being replaced with a real one
            if (message.temp_id) {
                this.updateTempMessage(message.temp_id, message);
            }
            return;
        }
        
        // Determine if message is from current user
        const isCurrentUser = message.sender_id === this.currentUserId || message.is_sender;
        
        // Get message content from any available field
        let messageContent = '';
        
        if (message.content && typeof message.content === 'string') {
            messageContent = message.content;
        } else if (message.message_text && typeof message.message_text === 'string') {
            messageContent = message.message_text;
        } else if (message.text && typeof message.text === 'string') {
            messageContent = message.text;
        } else if (message.data && message.data.message_text) {
            messageContent = message.data.message_text;
        } else if (typeof message === 'string') {
            messageContent = message;
        } else {
            // Try to extract message from unknown structure
            if (typeof message === 'object') {
                for (const key in message) {
                    if (typeof message[key] === 'string' && 
                        key !== 'sender_name' && 
                        key !== 'sender_avatar' &&
                        key !== 'id' && 
                        key !== 'message_id' && 
                        key !== 'created_at' && 
                        key !== 'sent_at') {
                        messageContent = message[key];
                        break;
                    }
                }
            }
            
            if (!messageContent) {
                console.error('Message content not found. Message structure:', JSON.stringify(message));
                messageContent = 'Failed to display message content';
            }
        }
        
        // Format timestamp
        const messageTime = this.formatMessageTime(message.created_at || message.sent_at || new Date().toISOString());
        
        // Determine if we should show the date separator
        const messageDate = new Date(message.created_at || message.sent_at || new Date()).toLocaleDateString();
        const showDateSeparator = this.shouldShowDateSeparator(messageDate);
        
        // Add date separator if needed
        if (showDateSeparator) {
            const dateElement = document.createElement('div');
            dateElement.className = 'flex justify-center my-4 w-full date-separator';
            dateElement.innerHTML = `
                <div class="px-4 py-1 bg-gray-100 rounded-full text-gray-500 text-xs">
                    ${this.formatDateForMessages(messageDate)}
                </div>
            `;
            this.elements.chatMessages.appendChild(dateElement);
        }
        
        // Create message element
        const messageElement = document.createElement('div');
        messageElement.id = messageId;
        messageElement.className = `flex ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-3`;
        
        // Create message HTML
        messageElement.innerHTML = `
            ${!isCurrentUser ? `
            <div class="w-8 h-8 rounded-full bg-gray-300 flex-shrink-0 mr-2 overflow-hidden">
                ${message.sender_avatar || message.sender_profile_picture ? 
                    `<img src="${message.sender_avatar || message.sender_profile_picture}" alt="${message.sender_name}" class="w-full h-full object-cover">` :
                    `<div class="w-full h-full bg-gray-400 flex items-center justify-center text-white text-sm font-bold">
                        ${message.sender_name ? message.sender_name.charAt(0).toUpperCase() : '?'}
                    </div>`
                }
            </div>
            ` : ''}
            <div class="max-w-xs sm:max-w-md break-words">
                ${!isCurrentUser && (message.is_group || message.is_system) ? 
                    `<div class="text-xs font-medium text-gray-500 mb-1 ml-1">${message.sender_name}</div>` : ''
                }
                <div class="${isCurrentUser ? 'message-outgoing' : 'message-incoming'}">
                    <div class="message-content">
                        <p class="message-text">${this.formatMessageText(messageContent)}</p>
                    </div>
                    <div class="text-xs text-right mt-1 message-time ${isCurrentUser ? 'text-white text-opacity-70' : 'text-gray-500'}">
                        ${messageTime}
                        ${message.is_read && isCurrentUser ? '<span class="ml-1">✓</span>' : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Add message to chat
        this.elements.chatMessages.appendChild(messageElement);
        
        // Log message content for debugging
        console.log('Added message to UI:', {
            id: messageId,
            content: messageContent,
            time: messageTime,
            isCurrentUser
        });
        
        // Always scroll to bottom after adding a new message from the current user
        if (isCurrentUser) {
            this.scrollToBottom();
        } else {
            this.scrollToBottomIfNeeded();
        }
    },
    
    // Update a temporary message with real data
    updateTempMessage(tempId, realMessage) {
        const messageElement = document.getElementById(`message-${tempId}`);
        if (!messageElement) return;
        
        console.log('Updating temp message:', tempId, 'with data:', realMessage);
        
        // Ensure the message content is preserved
        const originalContent = realMessage.content || realMessage.message_text;
        
        // Update ID using message_id if available
        if (realMessage.id || realMessage.message_id) {
            messageElement.id = `message-${realMessage.id || realMessage.message_id}`;
            
            // Also update in our currentMessages array
            if (this.currentMessages) {
                const tempMsgIndex = this.currentMessages.findIndex(
                    m => m.id === tempId || m.message_id === tempId || m.temp_id === tempId
                );
                
                if (tempMsgIndex !== -1) {
                    // Get the original message content
                    const tempMessage = this.currentMessages[tempMsgIndex];
                    const preservedContent = tempMessage.content || tempMessage.message_text;
                    
                    // Update message but preserve content
                    this.currentMessages[tempMsgIndex] = {
                        ...realMessage,
                        content: preservedContent || realMessage.content,
                        message_text: preservedContent || realMessage.message_text
                    };
                    
                    console.log('Updated message in memory:', this.currentMessages[tempMsgIndex]);
                }
            }
        }
        
        const messageContainer = messageElement.querySelector('div:last-child');
        if (!messageContainer) return;
        
        const messageContentContainer = messageContainer.querySelector('div.message-incoming, div.message-outgoing');
        if (!messageContentContainer) return;
        
        // Update message text if different
        const messageTextElement = messageContentContainer.querySelector('p.message-text');
        if (messageTextElement) {
            // First try to get content from the original element
            const originalText = messageTextElement.textContent;
            
            // Only update if we have content and it's different
            if (originalContent && originalText !== originalContent && messageTextElement.innerHTML !== this.formatMessageText(originalContent)) {
                messageTextElement.innerHTML = this.formatMessageText(originalContent);
            }
        }
        
        const messageStatus = messageContentContainer.querySelector('div.message-time');
        if (messageStatus) {
            messageStatus.innerHTML = `
                ${this.formatMessageTime(realMessage.created_at || realMessage.sent_at || new Date().toISOString())}
                ${realMessage.is_read ? '<span class="ml-1">✓</span>' : ''}
            `;
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
        this.notificationSound = new Audio('data:audio/mp3;base64,SUQzAwAAAAAAJlRQRTEAAAAcAAAAU291bmRKYXkuY29tIFNvdW5kIEVmZmVjdHMAVEFMQgAAABYAAAB1cmw6IFNvdW5kSmF5LmNvbQBUQ09OAAAACwAAAFNvdW5kIEZ4AENPTQMAAABcAEVuVwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//uQYAAP8AAAaQAAAAgAAA0gAAABAAABpAAAACAAADSAAAAETEFNRTMuOTlyBLgAAAAAAAAAADQgJAi4XQAA');
        
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
                // If there are new messages
                if (data.messages && data.messages.length > 0) {
                    // Get the latest timestamp
                    const latestMessage = data.messages.reduce((latest, msg) => {
                        const msgTime = new Date(msg.created_at || msg.sent_at).getTime();
                        return msgTime > latest ? msgTime : latest;
                    }, this.lastMessageTimestamp || 0);
                    
                    // Update last message timestamp
                    this.lastMessageTimestamp = Math.floor(latestMessage / 1000);
                    
                    // Add to current messages array
                    if (!this.currentMessages) {
                        this.currentMessages = [];
                    }
                    
                    // Add each message to memory and UI
                    data.messages.forEach(message => {
                        // Check if message already exists in currentMessages
                        const existingMsgIndex = this.currentMessages.findIndex(
                            m => (m.id && m.id === message.id) || 
                                 (m.message_id && m.message_id === message.message_id)
                        );
                        
                        if (existingMsgIndex === -1) {
                            // Add to in-memory messages array
                            this.currentMessages.push(message);
                            
                            // Add to UI
                            this.addMessageToUI(message);
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
                
                // Process chat list updates
                if (data.chat_updates) {
                    this.chats = data.chat_updates;
                    this.renderChatList();
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
        
        // Get all date separators
        const dateSeparators = this.elements.chatMessages.querySelectorAll('.date-separator');
        if (dateSeparators.length === 0) return true;
        
        // Check if the date is already shown
        const messageDate = new Date(timestamp).toLocaleDateString();
        
        // Get the date from the last separator
        const lastSeparator = dateSeparators[dateSeparators.length - 1];
        const lastSeparatorText = lastSeparator.textContent.trim();
        
        // Parse the date text to a comparable format
        const lastSeparatorDate = this.getDateFromSeparatorText(lastSeparatorText);
        
        // Return true if the dates are different
        return messageDate !== lastSeparatorDate;
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
};

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Set current user info
    ChatApp.currentUserId = window.currentUserId || null;
    ChatApp.currentUserName = window.currentUserName || '';
    ChatApp.currentUserAvatar = window.currentUserAvatar || '';
    
    ChatApp.init();
}); 