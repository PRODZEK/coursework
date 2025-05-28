/**
 * Realtime Updates Module
 * Handles real-time updates using Server-Sent Events (SSE)
 */

// Global realtime state
const realtimeState = {
    eventSource: null,
    connected: false,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    reconnectTimeout: null
};

/**
 * Initialize the realtime updates
 */
const initRealtime = () => {
    connectEventSource();
    
    // Add window event listeners for visibility changes
    document.addEventListener('visibilitychange', handleVisibilityChange);
};

/**
 * Connect to the event source for real-time updates
 */
const connectEventSource = () => {
    // Close existing connection if any
    if (realtimeState.eventSource) {
        realtimeState.eventSource.close();
    }
    
    try {
        // Create new EventSource
        realtimeState.eventSource = new EventSource(config.endpoints.updates);
        
        // Handle connection open
        realtimeState.eventSource.addEventListener('open', () => {
            console.log('SSE connection established');
            realtimeState.connected = true;
            realtimeState.reconnectAttempts = 0; // Reset reconnect attempts
            
            // Clear any pending reconnect timeout
            if (realtimeState.reconnectTimeout) {
                clearTimeout(realtimeState.reconnectTimeout);
                realtimeState.reconnectTimeout = null;
            }
        });
        
        // Handle initial data
        realtimeState.eventSource.addEventListener('init', handleInitEvent);
        
        // Handle updates
        realtimeState.eventSource.addEventListener('updates', handleUpdatesEvent);
        
        // Handle errors
        realtimeState.eventSource.addEventListener('error', handleEventSourceError);
        
        // Handle server closing the connection
        realtimeState.eventSource.addEventListener('close', () => {
            console.log('SSE connection closed by server');
            realtimeState.connected = false;
            realtimeState.eventSource.close();
            
            // Attempt to reconnect if not reached max attempts
            scheduleReconnect();
        });
    } catch (error) {
        console.error('Error connecting to event source:', error);
        scheduleReconnect();
    }
};

/**
 * Handle visibility change (tab active/inactive)
 */
const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        // If tab becomes visible and we're not connected, try to reconnect
        if (!realtimeState.connected) {
            connectEventSource();
        }
    }
};

/**
 * Handle initial data event
 * 
 * @param {Event} event - SSE event
 */
const handleInitEvent = (event) => {
    try {
        const data = JSON.parse(event.data);
        console.log('Received initial data:', data);
        
        // Update user ID if provided
        if (data.user_id) {
            app.currentUser.user_id = data.user_id;
        }
        
        // Update chats if provided
        if (data.chats) {
            chatState.chats = data.chats;
            
            // If the chat list is already rendered, update it
            if (typeof renderChatList === 'function') {
                renderChatList();
            }
        }
        
        // Update unread counts if provided
        if (data.unread_counts) {
            chatState.unreadCounts = data.unread_counts;
            
            // Update unread badges in the UI
            updateUnreadBadges();
        }
    } catch (error) {
        console.error('Error handling init event:', error);
    }
};

/**
 * Handle updates event
 * 
 * @param {Event} event - SSE event
 */
const handleUpdatesEvent = (event) => {
    try {
        const updates = JSON.parse(event.data);
        console.log('Received updates:', updates);
        
        // Process each update
        updates.forEach(update => {
            switch (update.type) {
                case 'new_message':
                    handleNewMessageUpdate(update);
                    break;
                case 'online_status':
                    handleOnlineStatusUpdate(update);
                    break;
                default:
                    console.log('Unknown update type:', update.type);
            }
        });
    } catch (error) {
        console.error('Error handling updates event:', error);
    }
};

/**
 * Handle new message update
 * 
 * @param {Object} update - New message update data
 */
const handleNewMessageUpdate = (update) => {
    const { chat_id, message } = update;
    
    // Update the message list if this chat is currently open
    if (chat_id === chatState.currentChatId) {
        // Add message to state
        if (!messageState.messages[chat_id]) {
            messageState.messages[chat_id] = [];
        }
        
        // Skip if we already have this message
        if (!messageState.messages[chat_id].some(m => m.message_id === message.message_id)) {
            messageState.messages[chat_id].push(message);
            
            // Add message to UI
            const messageElement = renderMessage(message);
            messagesContainer.appendChild(messageElement);
            
            // Scroll to bottom if near the bottom
            const { scrollTop, scrollHeight, clientHeight } = messagesContainer;
            const isNearBottom = scrollTop + clientHeight >= scrollHeight - 100;
            
            if (isNearBottom) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Mark message as read if chat is open and user is active
            if (document.visibilityState === 'visible') {
                messagesApi.markAsRead(chat_id, message.message_id);
            } else {
                // If not visible, increment unread count
                if (!chatState.unreadCounts[chat_id]) {
                    chatState.unreadCounts[chat_id] = 0;
                }
                chatState.unreadCounts[chat_id] += 1;
                
                // Update unread badge in the UI
                updateUnreadBadges();
                
                // Show notification
                showNotification(
                    message.sender_name || 'New message',
                    message.content || 'You have a new message',
                    message.sender_image || config.defaultAvatars.user
                );
                
                // Play notification sound
                playSound('notification');
            }
        }
    } else {
        // If chat is not open, increment unread count
        if (!chatState.unreadCounts[chat_id]) {
            chatState.unreadCounts[chat_id] = 0;
        }
        chatState.unreadCounts[chat_id] += 1;
        
        // Update unread badge in the UI
        updateUnreadBadges();
        
        // Show notification
        showNotification(
            message.sender_name || 'New message',
            message.content || 'You have a new message',
            message.sender_image || config.defaultAvatars.user
        );
        
        // Play notification sound
        playSound('notification');
        
        // Update chat preview for the new message
        updateChatPreview(chat_id, message);
    }
};

/**
 * Handle online status update
 * 
 * @param {Object} update - Online status update data
 */
const handleOnlineStatusUpdate = (update) => {
    const { user } = update;
    
    // Update online status in chat header if this is a private chat with this user
    if (chatState.currentChat && chatState.currentChat.chat_type === 'private') {
        const otherParticipant = chatState.currentChat.participants.find(p => 
            p.user_id === user.user_id
        );
        
        if (otherParticipant) {
            otherParticipant.is_online = user.is_online;
            otherParticipant.last_seen = user.last_seen;
            
            // Update the chat header subtitle
            const subtitleElement = document.querySelector('.chat-header-subtitle');
            if (subtitleElement) {
                if (user.is_online) {
                    subtitleElement.textContent = 'Online';
                } else if (user.last_seen) {
                    subtitleElement.textContent = `Last seen ${formatRelativeTime(user.last_seen)}`;
                }
            }
        }
    }
    
    // Update online status indicator in chat list
    chatState.chats.forEach(chat => {
        if (chat.chat_type === 'private') {
            const participant = chat.participants.find(p => 
                p.user_id === user.user_id
            );
            
            if (participant) {
                participant.is_online = user.is_online;
                participant.last_seen = user.last_seen;
                
                // Update the status indicator in the UI
                const chatItem = document.querySelector(`.chat-item[data-chat-id="${chat.chat_id}"]`);
                if (chatItem) {
                    const statusIndicator = chatItem.querySelector('.status-indicator');
                    if (statusIndicator) {
                        if (user.is_online) {
                            statusIndicator.classList.add('online');
                        } else {
                            statusIndicator.classList.remove('online');
                        }
                    }
                }
            }
        }
    });
};

/**
 * Update chat preview with new message
 * 
 * @param {number} chatId - Chat ID
 * @param {Object} message - Message data
 */
const updateChatPreview = (chatId, message) => {
    // Find chat in state
    const chatIndex = chatState.chats.findIndex(chat => chat.chat_id === chatId);
    
    if (chatIndex !== -1) {
        const chat = chatState.chats[chatIndex];
        
        // Update chat in DOM
        const chatItem = document.querySelector(`.chat-item[data-chat-id="${chatId}"]`);
        if (chatItem) {
            // Update message preview
            const previewText = chatItem.querySelector('.preview-text');
            if (previewText) {
                const senderName = message.sender_name || '';
                const content = truncateText(message.content || '', 30);
                
                if (message.message_type === 'text') {
                    previewText.textContent = `${senderName}: ${content}`;
                } else {
                    const typeMap = {
                        photo: 'Photo',
                        video: 'Video',
                        file: 'File',
                        audio: 'Audio',
                        location: 'Location',
                        contact: 'Contact'
                    };
                    
                    const typeText = typeMap[message.message_type] || 'Message';
                    previewText.textContent = `${senderName}: ${typeText}${message.content ? `: ${content}` : ''}`;
                }
            }
            
            // Update time
            const chatTime = chatItem.querySelector('.chat-time');
            if (chatTime) {
                chatTime.textContent = formatDate(message.sent_at, 'time');
            }
            
            // Update unread badge
            const unreadBadge = chatItem.querySelector('.unread-badge');
            if (unreadBadge) {
                const unreadCount = chatState.unreadCounts[chatId] || 0;
                unreadBadge.textContent = unreadCount > 0 ? unreadCount : '';
            }
            
            // Move chat to top of the list
            const chatList = chatItem.parentElement;
            if (chatList && chatList.firstChild !== chatItem) {
                chatList.removeChild(chatItem);
                chatList.insertBefore(chatItem, chatList.firstChild);
            }
            
            // Update chat's updated_at in state
            chat.updated_at = message.sent_at;
            
            // Re-sort chats
            chatState.chats.sort((a, b) => {
                return new Date(b.updated_at) - new Date(a.updated_at);
            });
        }
    }
};

/**
 * Update all unread badges in the chat list UI
 */
const updateUnreadBadges = () => {
    // Update unread badges for each chat
    Object.entries(chatState.unreadCounts).forEach(([chatId, count]) => {
        const chatItem = document.querySelector(`.chat-item[data-chat-id="${chatId}"]`);
        if (chatItem) {
            const unreadBadge = chatItem.querySelector('.unread-badge');
            if (unreadBadge) {
                unreadBadge.textContent = count > 0 ? count : '';
            }
        }
    });
};

/**
 * Handle EventSource errors
 * 
 * @param {Event} event - Error event
 */
const handleEventSourceError = (event) => {
    console.error('EventSource error:', event);
    realtimeState.connected = false;
    
    // Close the connection
    if (realtimeState.eventSource) {
        realtimeState.eventSource.close();
    }
    
    // Schedule reconnection attempt
    scheduleReconnect();
};

/**
 * Schedule a reconnection attempt
 */
const scheduleReconnect = () => {
    // Check if we've reached max attempts
    if (realtimeState.reconnectAttempts >= realtimeState.maxReconnectAttempts) {
        console.log('Max reconnect attempts reached, giving up');
        return;
    }
    
    // Exponential backoff for reconnect
    const delay = Math.min(1000 * Math.pow(2, realtimeState.reconnectAttempts), 30000);
    console.log(`Scheduling reconnect in ${delay}ms (attempt ${realtimeState.reconnectAttempts + 1})`);
    
    // Clear any existing timeout
    if (realtimeState.reconnectTimeout) {
        clearTimeout(realtimeState.reconnectTimeout);
    }
    
    // Set timeout for reconnect
    realtimeState.reconnectTimeout = setTimeout(() => {
        realtimeState.reconnectAttempts++;
        connectEventSource();
    }, delay);
};

/**
 * Close the realtime connection
 */
const closeRealtime = () => {
    if (realtimeState.eventSource) {
        realtimeState.eventSource.close();
        realtimeState.eventSource = null;
    }
    
    if (realtimeState.reconnectTimeout) {
        clearTimeout(realtimeState.reconnectTimeout);
        realtimeState.reconnectTimeout = null;
    }
    
    realtimeState.connected = false;
};

// Export the realtime module
const realtimeModule = {
    init: initRealtime,
    close: closeRealtime,
    state: realtimeState
}; 