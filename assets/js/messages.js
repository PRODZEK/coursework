/**
 * Messages Module
 * Handles message operations and UI
 */

// Global message state
const messageState = {
    messages: {},
    loadingMessages: false,
    messageOffset: 0,
    hasMoreMessages: true,
    replyingTo: null
};

// DOM elements
let mainElement;
let messagesContainer;
let messageInput;
let sendButton;
let messageForm;
let chatHeader;

/**
 * Initialize the messages module
 */
const initMessages = () => {
    mainElement = document.getElementById('main');
    
    // Create message UI if main element exists
    if (mainElement) {
        createMessageUI();
    }
    
    // Listen for chat selection event
    window.addEventListener('chatSelected', handleChatSelected);
};

/**
 * Create the message UI elements
 */
const createMessageUI = () => {
    // Create chat header
    chatHeader = document.createElement('div');
    chatHeader.className = 'chat-header';
    chatHeader.innerHTML = `
        <div class="chat-header-info">
            <h4 class="chat-header-title">Select a chat</h4>
            <p class="chat-header-subtitle"></p>
        </div>
        <div class="chat-header-actions">
            <button class="header-action-btn chat-info-btn" aria-label="Chat Info">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>
    `;
    
    // Create messages container
    messagesContainer = document.createElement('div');
    messagesContainer.className = 'chat-messages';
    messagesContainer.innerHTML = `
        <div class="chat-welcome">
            <h3>Welcome to Telegram Clone</h3>
            <p>Select a chat to start messaging</p>
        </div>
    `;
    
    // Create message input container
    const messageInputContainer = document.createElement('div');
    messageInputContainer.className = 'message-input-container';
    
    // Create message form
    messageForm = document.createElement('form');
    messageForm.className = 'message-form';
    messageForm.style.display = 'flex';
    messageForm.style.alignItems = 'center';
    messageForm.style.width = '100%';
    messageForm.style.gap = '0.75rem';
    
    // Create message input
    messageInput = document.createElement('textarea');
    messageInput.className = 'message-input';
    messageInput.placeholder = 'Type a message...';
    messageInput.rows = 1;
    
    // Create attachment button
    const attachmentButton = document.createElement('button');
    attachmentButton.type = 'button';
    attachmentButton.className = 'message-action-btn';
    attachmentButton.setAttribute('aria-label', 'Add attachment');
    attachmentButton.innerHTML = '<i class="fas fa-paperclip"></i>';
    
    // Create send button
    sendButton = document.createElement('button');
    sendButton.type = 'submit';
    sendButton.className = 'message-action-btn send-btn';
    sendButton.setAttribute('aria-label', 'Send message');
    sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
    
    // Add elements to the form
    messageForm.appendChild(attachmentButton);
    messageForm.appendChild(messageInput);
    messageForm.appendChild(sendButton);
    
    // Add form to the input container
    messageInputContainer.appendChild(messageForm);
    
    // Clear the main content and add the new elements
    mainElement.innerHTML = '';
    mainElement.appendChild(chatHeader);
    mainElement.appendChild(messagesContainer);
    mainElement.appendChild(messageInputContainer);
    
    // Set up event listeners
    setupMessageEventListeners();
};

/**
 * Set up message-related event listeners
 */
const setupMessageEventListeners = () => {
    // Message form submit
    if (messageForm) {
        messageForm.addEventListener('submit', handleSendMessage);
    }
    
    // Message input auto-resize
    if (messageInput) {
        // Auto-resize input as user types
        messageInput.addEventListener('input', () => {
            messageInput.style.height = 'auto';
            messageInput.style.height = Math.min(messageInput.scrollHeight, 150) + 'px';
        });
        
        // Handle Enter key (Send on Enter, new line on Shift+Enter)
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                messageForm.dispatchEvent(new Event('submit'));
            }
        });
    }
    
    // Infinite scroll for messages
    if (messagesContainer) {
        messagesContainer.addEventListener('scroll', handleMessagesScroll);
    }
    
    // Chat info button
    const chatInfoBtn = document.querySelector('.chat-info-btn');
    if (chatInfoBtn) {
        chatInfoBtn.addEventListener('click', handleShowChatInfo);
    }
};

/**
 * Handle chat selection event
 * 
 * @param {CustomEvent} event - Chat selected event
 */
const handleChatSelected = async (event) => {
    const { chatId, chat } = event.detail;
    
    // Update header
    updateChatHeader(chat);
    
    // Clear current messages
    messagesContainer.innerHTML = '';
    
    // Reset message state
    messageState.messages[chatId] = [];
    messageState.messageOffset = 0;
    messageState.hasMoreMessages = true;
    
    // Enable the message form
    enableMessageForm(true);
    
    // Load messages
    await loadMessages(chatId);
};

/**
 * Update the chat header with chat information
 * 
 * @param {Object} chat - Chat data
 */
const updateChatHeader = (chat) => {
    if (!chatHeader) return;
    
    const titleElement = chatHeader.querySelector('.chat-header-title');
    const subtitleElement = chatHeader.querySelector('.chat-header-subtitle');
    
    if (titleElement && subtitleElement) {
        if (chat.chat_type === 'private' && chat.participants && chat.participants.length > 0) {
            // Find the other participant (not the current user)
            const otherParticipant = chat.participants.find(p => 
                p.user_id !== app.currentUser.user_id
            );
            
            if (otherParticipant) {
                titleElement.textContent = otherParticipant.full_name;
                
                if (otherParticipant.is_online) {
                    subtitleElement.textContent = 'Online';
                } else if (otherParticipant.last_seen) {
                    subtitleElement.textContent = `Last seen ${formatRelativeTime(otherParticipant.last_seen)}`;
                } else {
                    subtitleElement.textContent = '';
                }
            }
        } else {
            titleElement.textContent = chat.title || 'Chat';
            subtitleElement.textContent = `${chat.participants ? chat.participants.length : 0} members`;
        }
    }
};

/**
 * Load messages for a chat
 * 
 * @param {number} chatId - Chat ID
 * @param {boolean} loadMore - Whether to load more messages (for pagination)
 */
const loadMessages = async (chatId, loadMore = false) => {
    if (messageState.loadingMessages || (!loadMore && !messageState.hasMoreMessages)) {
        return;
    }
    
    messageState.loadingMessages = true;
    
    // Show loading indicator
    if (!loadMore) {
        messagesContainer.innerHTML = '<div class="messages-loading"><i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p></div>';
    } else {
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'messages-loading-more';
        loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        messagesContainer.prepend(loadingIndicator);
    }
    
    try {
        // Get messages
        const limit = 20;
        const offset = loadMore ? messageState.messageOffset : 0;
        
        const response = await messagesApi.getMessages(chatId, limit, offset);
        
        // Remove loading indicators
        const loadingElements = messagesContainer.querySelectorAll('.messages-loading, .messages-loading-more');
        loadingElements.forEach(el => el.remove());
        
        if (response.status === 'success' && response.data) {
            const { messages, total } = response.data;
            
            if (loadMore) {
                // Prepend older messages
                const scrollHeightBefore = messagesContainer.scrollHeight;
                
                messages.forEach(message => {
                    // Skip if we already have this message
                    if (messageState.messages[chatId].some(m => m.message_id === message.message_id)) {
                        return;
                    }
                    messageState.messages[chatId].unshift(message);
                    const messageElement = renderMessage(message);
                    messagesContainer.prepend(messageElement);
                });
                
                // Maintain scroll position when adding messages at the top
                messagesContainer.scrollTop = messagesContainer.scrollHeight - scrollHeightBefore;
            } else {
                // Display new messages
                messageState.messages[chatId] = messages;
                
                if (messages.length === 0) {
                    messagesContainer.innerHTML = '<div class="empty-chat">No messages yet</div>';
                } else {
                    messagesContainer.innerHTML = '';
                    messages.forEach(message => {
                        const messageElement = renderMessage(message);
                        messagesContainer.appendChild(messageElement);
                    });
                    
                    // Scroll to bottom
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
            
            // Update offset and check if we have more messages
            messageState.messageOffset = offset + messages.length;
            messageState.hasMoreMessages = total > messageState.messageOffset;
        } else {
            if (!loadMore) {
                messagesContainer.innerHTML = '<div class="error-message">Failed to load messages</div>';
            }
        }
    } catch (error) {
        if (!loadMore) {
            messagesContainer.innerHTML = '<div class="error-message">Failed to load messages</div>';
        }
        console.error('Error loading messages:', error);
    } finally {
        messageState.loadingMessages = false;
    }
};

/**
 * Render a message
 * 
 * @param {Object} message - Message data
 * @returns {HTMLElement} - Message element
 */
const renderMessage = (message) => {
    if (message.message_type === 'system') {
        return renderSystemMessage(message);
    }
    
    const isOwnMessage = message.sender_id === app.currentUser.user_id;
    const messageClasses = ['message'];
    
    if (isOwnMessage) {
        messageClasses.push('own');
    }
    
    if (message.is_deleted) {
        messageClasses.push('deleted');
    }
    
    const messageElement = document.createElement('div');
    messageElement.className = messageClasses.join(' ');
    messageElement.dataset.messageId = message.message_id;
    
    // Avatar (only for messages from others)
    let avatarHtml = '';
    if (!isOwnMessage) {
        avatarHtml = `
            <div class="message-avatar">
                <img src="${message.sender_image || config.defaultAvatars.user}" alt="Avatar" class="avatar-img">
            </div>
        `;
    }
    
    // Message content
    let contentHtml = '';
    if (message.is_deleted) {
        contentHtml = '<p class="message-text deleted">This message was deleted</p>';
    } else {
        if (message.message_type === 'text') {
            contentHtml = `<p class="message-text">${formatMessageContent(message.content)}</p>`;
        } else if (message.message_type === 'photo') {
            contentHtml = `
                <div class="message-media">
                    <img src="${message.file_path}" alt="Photo" class="message-image">
                </div>
                ${message.content ? `<p class="message-text">${formatMessageContent(message.content)}</p>` : ''}
            `;
        } else if (message.message_type === 'video') {
            contentHtml = `
                <div class="message-media">
                    <video controls class="message-video">
                        <source src="${message.file_path}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                ${message.content ? `<p class="message-text">${formatMessageContent(message.content)}</p>` : ''}
            `;
        } else if (message.message_type === 'file') {
            const fileName = message.file_path.split('/').pop();
            contentHtml = `
                <div class="message-file">
                    <i class="fas fa-file"></i>
                    <a href="${message.file_path}" target="_blank" download="${fileName}">${escapeHtml(fileName)}</a>
                </div>
                ${message.content ? `<p class="message-text">${formatMessageContent(message.content)}</p>` : ''}
            `;
        }
    }
    
    // Message edited indicator
    const editedHtml = message.is_edited ? '<span class="edited-indicator">edited</span>' : '';
    
    // Reply indicator
    let replyHtml = '';
    if (message.reply_to_message_id && message.reply_content) {
        replyHtml = `
            <div class="reply-content">
                <div class="reply-line"></div>
                <div class="reply-text">
                    <strong>${escapeHtml(message.reply_name || 'User')}</strong>
                    <span>${escapeHtml(truncateText(message.reply_content, 30))}</span>
                </div>
            </div>
        `;
    }
    
    // Forward indicator
    let forwardHtml = '';
    if (message.is_forwarded) {
        forwardHtml = `<div class="forward-indicator">Forwarded message</div>`;
    }
    
    // Message status (read/unread) - only for own messages
    let statusHtml = '';
    if (isOwnMessage) {
        statusHtml = `<span class="message-status">✓</span>`;
    }
    
    messageElement.innerHTML = `
        ${avatarHtml}
        <div class="message-content">
            <div class="message-header">
                ${!isOwnMessage ? `<span class="message-sender">${escapeHtml(message.sender_name || 'User')}</span>` : ''}
                <span class="message-time">${formatDate(message.sent_at, 'time')}</span>
                ${editedHtml}
            </div>
            ${forwardHtml}
            ${replyHtml}
            <div class="message-body">
                ${contentHtml}
            </div>
            <div class="message-footer">
                ${statusHtml}
            </div>
        </div>
    `;
    
    // Add context menu event
    messageElement.addEventListener('contextmenu', (event) => {
        event.preventDefault();
        showMessageContextMenu(event, message);
    });
    
    return messageElement;
};

/**
 * Render a system message
 * 
 * @param {Object} message - System message data
 * @returns {HTMLElement} - System message element
 */
const renderSystemMessage = (message) => {
    const messageElement = document.createElement('div');
    messageElement.className = 'message system-message';
    messageElement.dataset.messageId = message.message_id;
    
    messageElement.innerHTML = `
        <div class="message-body">
            <p class="message-text">${formatMessageContent(message.content)}</p>
        </div>
    `;
    
    return messageElement;
};

/**
 * Show message context menu
 * 
 * @param {Event} event - Context menu event
 * @param {Object} message - Message data
 */
const showMessageContextMenu = (event, message) => {
    // Remove any existing context menu
    const existingMenu = document.querySelector('.context-menu');
    if (existingMenu) {
        document.body.removeChild(existingMenu);
    }
    
    const isOwnMessage = message.sender_id === app.currentUser.user_id;
    
    // Create context menu
    const contextMenu = document.createElement('div');
    contextMenu.className = 'context-menu';
    contextMenu.style.position = 'absolute';
    contextMenu.style.zIndex = '1000';
    contextMenu.style.backgroundColor = 'white';
    contextMenu.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
    contextMenu.style.borderRadius = '8px';
    contextMenu.style.padding = '8px 0';
    
    // Calculate position
    const clickX = event.clientX;
    const clickY = event.clientY;
    
    // Set initial position
    contextMenu.style.left = `${clickX}px`;
    contextMenu.style.top = `${clickY}px`;
    
    // Menu options
    const menuOptions = [];
    
    // Reply option
    menuOptions.push({
        label: 'Reply',
        icon: 'fa-reply',
        action: () => handleReplyToMessage(message)
    });
    
    // Edit option (only for own messages)
    if (isOwnMessage && !message.is_deleted) {
        menuOptions.push({
            label: 'Edit',
            icon: 'fa-edit',
            action: () => handleEditMessage(message)
        });
    }
    
    // Forward option
    if (!message.is_deleted) {
        menuOptions.push({
            label: 'Forward',
            icon: 'fa-share',
            action: () => handleForwardMessage(message)
        });
    }
    
    // Delete option (only for own messages)
    if (isOwnMessage) {
        menuOptions.push({
            label: 'Delete',
            icon: 'fa-trash',
            action: () => handleDeleteMessage(message)
        });
    }
    
    // Create menu items
    menuOptions.forEach(option => {
        const menuItem = document.createElement('div');
        menuItem.className = 'context-menu-item';
        menuItem.innerHTML = `<i class="fas ${option.icon}"></i> ${option.label}`;
        
        // Style menu item
        menuItem.style.padding = '8px 16px';
        menuItem.style.cursor = 'pointer';
        menuItem.style.display = 'flex';
        menuItem.style.alignItems = 'center';
        menuItem.style.gap = '8px';
        
        // Hover effect
        menuItem.addEventListener('mouseenter', () => {
            menuItem.style.backgroundColor = '#f5f5f5';
        });
        
        menuItem.addEventListener('mouseleave', () => {
            menuItem.style.backgroundColor = '';
        });
        
        menuItem.addEventListener('click', () => {
            option.action();
            document.body.removeChild(contextMenu);
        });
        
        contextMenu.appendChild(menuItem);
    });
    
    // Add to document and position
    document.body.appendChild(contextMenu);
    
    // Adjust position if the menu would go outside the viewport
    const menuRect = contextMenu.getBoundingClientRect();
    
    if (menuRect.right > window.innerWidth) {
        contextMenu.style.left = `${window.innerWidth - menuRect.width - 10}px`;
    }
    
    if (menuRect.bottom > window.innerHeight) {
        contextMenu.style.top = `${window.innerHeight - menuRect.height - 10}px`;
    }
    
    // Close menu when clicking outside
    const closeMenu = (e) => {
        if (!contextMenu.contains(e.target)) {
            document.body.removeChild(contextMenu);
            document.removeEventListener('click', closeMenu);
        }
    };
    
    // Use setTimeout to avoid immediate closing
    setTimeout(() => {
        document.addEventListener('click', closeMenu);
    }, 0);
};

/**
 * Handle reply to message
 * 
 * @param {Object} message - Message to reply to
 */
const handleReplyToMessage = (message) => {
    // Store the message we're replying to
    messageState.replyingTo = message;
    
    // Check if the reply UI already exists
    let replyContainer = document.querySelector('.reply-container');
    
    if (!replyContainer) {
        // Create reply UI
        replyContainer = document.createElement('div');
        replyContainer.className = 'reply-container';
        
        // Insert before the message form
        messageForm.parentNode.insertBefore(replyContainer, messageForm);
    }
    
    // Update the reply UI content
    replyContainer.innerHTML = `
        <div class="reply-preview">
            <div class="reply-info">
                <span class="reply-to">Replying to <strong>${escapeHtml(message.sender_name || 'User')}</strong></span>
                <p class="reply-content-preview">${escapeHtml(truncateText(message.content || '', 50))}</p>
            </div>
            <button type="button" class="cancel-reply-btn" aria-label="Cancel reply">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add event listener to cancel button
    const cancelBtn = replyContainer.querySelector('.cancel-reply-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelReply);
    }
    
    // Focus the message input
    messageInput.focus();
};

/**
 * Cancel reply
 */
const cancelReply = () => {
    messageState.replyingTo = null;
    
    const replyContainer = document.querySelector('.reply-container');
    if (replyContainer) {
        replyContainer.remove();
    }
};

/**
 * Handle edit message
 * 
 * @param {Object} message - Message to edit
 */
const handleEditMessage = (message) => {
    // Store original content
    messageInput.dataset.editingMessageId = message.message_id;
    messageInput.dataset.originalContent = message.content;
    
    // Put message content in the input
    messageInput.value = message.content || '';
    messageInput.focus();
    
    // Trigger resize
    messageInput.dispatchEvent(new Event('input'));
    
    // Change send button icon to edit
    sendButton.innerHTML = '<i class="fas fa-check"></i>';
    sendButton.classList.add('edit-mode');
    
    // Add a cancel edit button
    const cancelEditBtn = document.createElement('button');
    cancelEditBtn.type = 'button';
    cancelEditBtn.className = 'message-action-btn cancel-edit-btn';
    cancelEditBtn.setAttribute('aria-label', 'Cancel edit');
    cancelEditBtn.innerHTML = '<i class="fas fa-times"></i>';
    cancelEditBtn.addEventListener('click', cancelEdit);
    
    // Add before send button
    messageForm.insertBefore(cancelEditBtn, sendButton);
};

/**
 * Cancel message editing
 */
const cancelEdit = () => {
    // Remove editing state
    delete messageInput.dataset.editingMessageId;
    delete messageInput.dataset.originalContent;
    
    // Clear input
    messageInput.value = '';
    messageInput.dispatchEvent(new Event('input'));
    
    // Reset send button
    sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
    sendButton.classList.remove('edit-mode');
    
    // Remove cancel edit button
    const cancelEditBtn = document.querySelector('.cancel-edit-btn');
    if (cancelEditBtn) {
        cancelEditBtn.remove();
    }
};

/**
 * Handle forward message
 * 
 * @param {Object} message - Message to forward
 */
const handleForwardMessage = (message) => {
    // This would open a modal to select a chat to forward to
    alert('Forward functionality would be implemented here.');
};

/**
 * Handle delete message
 * 
 * @param {Object} message - Message to delete
 */
const handleDeleteMessage = async (message) => {
    if (confirm('Are you sure you want to delete this message?')) {
        try {
            const response = await messagesApi.deleteMessage(message.message_id);
            
            if (response.status === 'success') {
                // Update the message in the UI
                const messageElement = document.querySelector(`.message[data-message-id="${message.message_id}"]`);
                if (messageElement) {
                    messageElement.classList.add('deleted');
                    
                    // Update message content
                    const messageText = messageElement.querySelector('.message-text');
                    if (messageText) {
                        messageText.textContent = 'This message was deleted';
                        messageText.classList.add('deleted');
                    }
                    
                    // Remove media content
                    const messageMedia = messageElement.querySelector('.message-media');
                    if (messageMedia) {
                        messageMedia.remove();
                    }
                    
                    // Remove file content
                    const messageFile = messageElement.querySelector('.message-file');
                    if (messageFile) {
                        messageFile.remove();
                    }
                }
            }
        } catch (error) {
            console.error('Error deleting message:', error);
            alert('Failed to delete message.');
        }
    }
};

/**
 * Handle send message form submission
 * 
 * @param {Event} event - Form submit event
 */
const handleSendMessage = async (event) => {
    event.preventDefault();
    
    const content = messageInput.value.trim();
    
    // Check if we're in edit mode
    if (messageInput.dataset.editingMessageId) {
        await handleUpdateMessage(messageInput.dataset.editingMessageId, content);
        return;
    }
    
    // Don't send empty messages
    if (!content) {
        return;
    }
    
    // Check if we have a selected chat
    if (!chatState.currentChatId) {
        return;
    }
    
    // Disable the form while sending
    enableMessageForm(false);
    
    // Create message data
    const messageData = {
        chat_id: chatState.currentChatId,
        message_type: 'text',
        content: content
    };
    
    // Add reply_to_message_id if replying
    if (messageState.replyingTo) {
        messageData.reply_to_message_id = messageState.replyingTo.message_id;
        cancelReply(); // Clear reply UI
    }
    
    try {
        // Send the message
        const response = await messagesApi.sendMessage(messageData);
        
        if (response.status === 'success' && response.data) {
            // Clear input
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            // Add message to state
            const chatId = chatState.currentChatId;
            if (!messageState.messages[chatId]) {
                messageState.messages[chatId] = [];
            }
            
            messageState.messages[chatId].push(response.data);
            
            // Add message to UI
            const messageElement = renderMessage(response.data);
            messagesContainer.appendChild(messageElement);
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Play send sound
            playSound('messageSent');
        } else {
            alert('Failed to send message.');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message.');
    } finally {
        // Re-enable the form
        enableMessageForm(true);
    }
};

/**
 * Handle updating a message
 * 
 * @param {number} messageId - Message ID to update
 * @param {string} content - New message content
 */
const handleUpdateMessage = async (messageId, content) => {
    // Check if content was changed
    if (content === messageInput.dataset.originalContent) {
        // No changes, just cancel edit mode
        cancelEdit();
        return;
    }
    
    // Don't send empty messages
    if (!content) {
        alert('Message cannot be empty.');
        return;
    }
    
    // Disable the form while sending
    enableMessageForm(false);
    
    try {
        // Send the update
        const response = await messagesApi.editMessage(messageId, content);
        
        if (response.status === 'success' && response.data) {
            // Clear edit mode
            cancelEdit();
            
            // Update message in state
            const chatId = chatState.currentChatId;
            if (messageState.messages[chatId]) {
                const messageIndex = messageState.messages[chatId].findIndex(
                    m => m.message_id === parseInt(messageId)
                );
                
                if (messageIndex !== -1) {
                    messageState.messages[chatId][messageIndex] = response.data;
                }
            }
            
            // Update message in UI
            const messageElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
            if (messageElement) {
                // Update text content
                const messageText = messageElement.querySelector('.message-text');
                if (messageText) {
                    messageText.innerHTML = formatMessageContent(content);
                }
                
                // Make sure edited indicator is shown
                const messageHeader = messageElement.querySelector('.message-header');
                if (messageHeader && !messageHeader.querySelector('.edited-indicator')) {
                    messageHeader.innerHTML += '<span class="edited-indicator">edited</span>';
                }
            }
        } else {
            alert('Failed to update message.');
        }
    } catch (error) {
        console.error('Error updating message:', error);
        alert('Failed to update message.');
    } finally {
        // Re-enable the form
        enableMessageForm(true);
    }
};

/**
 * Handle scroll events for infinite loading
 */
const handleMessagesScroll = debounce(() => {
    if (messagesContainer.scrollTop === 0 && messageState.hasMoreMessages && !messageState.loadingMessages) {
        // Load more messages when scrolled to top
        loadMessages(chatState.currentChatId, true);
    }
}, 200);

/**
 * Handle showing chat info
 */
const handleShowChatInfo = () => {
    // Check if we have a selected chat
    if (!chatState.currentChat) {
        return;
    }
    
    // Get chat info template
    const template = document.getElementById('chat-info-template');
    if (!template) return;
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'popup';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'popup-content';
    
    modalContent.appendChild(template.content.cloneNode(true));
    modal.appendChild(modalContent);
    
    // Add close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'popup-close';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    modalContent.prepend(closeBtn);
    
    document.body.appendChild(modal);
    
    // Fill chat info
    const chat = chatState.currentChat;
    const chatTitle = modal.querySelector('.chat-title');
    const chatSubtitle = modal.querySelector('.chat-subtitle');
    const chatDescription = modal.querySelector('.chat-description');
    const avatarImg = modal.querySelector('.avatar-img');
    const participantsList = modal.querySelector('.participants-list');
    
    // Determine avatar based on chat type
    let avatarSrc;
    if (chat.chat_type === 'private') {
        const otherParticipant = chat.participants.find(p => 
            p.user_id !== app.currentUser.user_id
        );
        if (otherParticipant) {
            avatarSrc = otherParticipant.profile_image || config.defaultAvatars.user;
        } else {
            avatarSrc = config.defaultAvatars.user;
        }
    } else if (chat.chat_type === 'group') {
        avatarSrc = chat.photo || config.defaultAvatars.group;
    } else {
        avatarSrc = chat.photo || config.defaultAvatars.channel;
    }
    
    if (avatarImg) {
        avatarImg.src = avatarSrc;
    }
    
    if (chatTitle) {
        if (chat.chat_type === 'private') {
            const otherParticipant = chat.participants.find(p => 
                p.user_id !== app.currentUser.user_id
            );
            if (otherParticipant) {
                chatTitle.textContent = otherParticipant.full_name;
            }
        } else {
            chatTitle.textContent = chat.title || 'Chat';
        }
    }
    
    if (chatSubtitle) {
        if (chat.chat_type === 'private') {
            const otherParticipant = chat.participants.find(p => 
                p.user_id !== app.currentUser.user_id
            );
            if (otherParticipant) {
                chatSubtitle.textContent = `@${otherParticipant.username}`;
            }
        } else {
            chatSubtitle.textContent = `${chat.chat_type} · ${chat.participants ? chat.participants.length : 0} members`;
        }
    }
    
    if (chatDescription) {
        chatDescription.textContent = chat.description || 'No description';
    }
    
    if (participantsList && chat.participants) {
        participantsList.innerHTML = '';
        
        chat.participants.forEach(participant => {
            const participantElement = document.createElement('div');
            participantElement.className = 'participant-item';
            participantElement.dataset.userId = participant.user_id;
            
            participantElement.innerHTML = `
                <div class="participant-avatar">
                    <img src="${participant.profile_image || config.defaultAvatars.user}" alt="Avatar" class="avatar-img">
                    <span class="status-indicator ${participant.is_online ? 'online' : ''}"></span>
                </div>
                <div class="participant-info">
                    <h5 class="participant-name">${escapeHtml(participant.full_name)}</h5>
                    <span class="participant-role">${participant.role}</span>
                </div>
            `;
            
            participantsList.appendChild(participantElement);
        });
    }
    
    // Add event listeners
    closeBtn.addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    const leaveBtn = modal.querySelector('.leave-chat-btn');
    if (leaveBtn) {
        leaveBtn.addEventListener('click', async () => {
            if (confirm('Are you sure you want to leave this chat?')) {
                try {
                    // Leave chat (remove self as participant)
                    await chatsApi.removeParticipant(chat.chat_id, app.currentUser.user_id);
                    
                    // Remove chat from list
                    chatState.chats = chatState.chats.filter(c => c.chat_id !== chat.chat_id);
                    
                    // Re-render chat list
                    renderChatList();
                    
                    // Clear main content
                    messagesContainer.innerHTML = `
                        <div class="chat-welcome">
                            <h3>Welcome to Telegram Clone</h3>
                            <p>Select a chat to start messaging</p>
                        </div>
                    `;
                    
                    // Reset chat header
                    updateChatHeader({});
                    
                    // Close the modal
                    document.body.removeChild(modal);
                } catch (error) {
                    console.error('Error leaving chat:', error);
                    alert('Failed to leave the chat.');
                }
            }
        });
    }
};

/**
 * Enable or disable the message form
 * 
 * @param {boolean} enabled - Whether the form should be enabled
 */
const enableMessageForm = (enabled) => {
    if (messageForm) {
        messageForm.querySelectorAll('button, textarea').forEach(el => {
            el.disabled = !enabled;
        });
    }
};

// Export the messages module
const messagesModule = {
    init: initMessages,
    loadMessages,
    handleSendMessage,
    state: messageState
}; 