/**
 * Chat Module
 * Manages chat operations and UI
 */

// Global chat state
const chatState = {
    chats: [],
    currentChat: null,
    currentChatId: null,
    unreadCounts: {},
    lastMessageIds: {}
};

// DOM elements
let sidebarElement;
let chatListElement;

/**
 * Initialize the chat module
 * 
 * @param {Object} userData - Current user data
 */
const initChat = async (userData) => {
    // Store DOM elements
    sidebarElement = document.getElementById('sidebar');
    
    // Create chat list element
    chatListElement = document.createElement('div');
    chatListElement.className = 'chat-list';
    
    // Create sidebar header
    const sidebarHeader = document.createElement('div');
    sidebarHeader.className = 'sidebar-header';
    sidebarHeader.innerHTML = `
        <h3 class="sidebar-title">Chats</h3>
        <button id="new-chat-btn" class="header-action-btn" aria-label="New Chat">
            <i class="fas fa-plus"></i>
        </button>
    `;
    
    // Create search element
    const searchContainer = document.createElement('div');
    searchContainer.className = 'search-container';
    searchContainer.innerHTML = `
        <input type="text" id="search-chats" class="search-input" placeholder="Search chats...">
        <i class="fas fa-search search-icon"></i>
    `;
    
    // Create sidebar content element
    const sidebarContent = document.createElement('div');
    sidebarContent.className = 'sidebar-content';
    sidebarContent.appendChild(chatListElement);
    
    // Create sidebar footer
    const sidebarFooter = document.createElement('div');
    sidebarFooter.className = 'sidebar-footer';
    sidebarFooter.innerHTML = `
        <div class="user-info">
            <span>${userData.full_name}</span>
        </div>
        <button id="logout-btn" class="header-action-btn" aria-label="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    `;
    
    // Clear the sidebar and add the new elements
    sidebarElement.innerHTML = '';
    sidebarElement.appendChild(sidebarHeader);
    sidebarElement.appendChild(searchContainer);
    sidebarElement.appendChild(sidebarContent);
    sidebarElement.appendChild(sidebarFooter);
    
    // Get chat list
    await loadChats();
    
    // Set up event listeners
    setupEventListeners();
};

/**
 * Set up event listeners
 */
const setupEventListeners = () => {
    // New chat button
    const newChatBtn = document.getElementById('new-chat-btn');
    if (newChatBtn) {
        newChatBtn.addEventListener('click', handleNewChat);
    }
    
    // Logout button
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    
    // Search input
    const searchInput = document.getElementById('search-chats');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearchChats, 300));
    }
};

/**
 * Load user's chats
 */
const loadChats = async () => {
    try {
        // Show loading state
        if (chatListElement) {
            chatListElement.innerHTML = '<div class="sidebar-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        }
        
        // Get unread message counts
        const unreadResponse = await messagesApi.getUnreadCounts();
        if (unreadResponse.status === 'success') {
            chatState.unreadCounts = unreadResponse.data || {};
        }
        
        // Get chats
        const response = await chatsApi.getChats();
        
        if (response.status === 'success' && response.data) {
            chatState.chats = response.data || [];
            
            // Render chat list
            renderChatList();
        } else {
            // Show error
            if (chatListElement) {
                chatListElement.innerHTML = '<div class="error-message">Failed to load chats</div>';
            }
        }
    } catch (error) {
        // Show error
        if (chatListElement) {
            chatListElement.innerHTML = '<div class="error-message">Failed to load chats</div>';
        }
        console.error('Error loading chats:', error);
    }
};

/**
 * Render the chat list
 * 
 * @param {Array} filteredChats - Optional array of filtered chats
 */
const renderChatList = (filteredChats) => {
    if (!chatListElement) return;
    
    // Clear the chat list
    chatListElement.innerHTML = '';
    
    const chatsToRender = filteredChats || chatState.chats;
    
    if (chatsToRender.length === 0) {
        chatListElement.innerHTML = '<div class="empty-state">No chats yet</div>';
        return;
    }
    
    // Sort chats by updated_at
    chatsToRender.sort((a, b) => {
        return new Date(b.updated_at) - new Date(a.updated_at);
    });
    
    // Render each chat
    chatsToRender.forEach(chat => {
        const chatElement = renderChatItem(chat);
        chatListElement.appendChild(chatElement);
    });
};

/**
 * Render a chat list item
 * 
 * @param {Object} chat - Chat data
 * @returns {HTMLElement} - Chat item element
 */
const renderChatItem = (chat) => {
    const chatElement = document.createElement('div');
    chatElement.className = 'chat-item';
    chatElement.dataset.chatId = chat.chat_id;
    
    // Check if this is the current chat
    if (chatState.currentChatId === chat.chat_id) {
        chatElement.classList.add('active');
    }
    
    // Get the last message for preview
    let lastMessagePreview = 'No messages yet';
    let lastMessageTime = '';
    let senderName = '';
    
    // If there are participants, get the other user's name for private chats
    // or use the chat title for groups/channels
    let chatName = chat.title || '';
    let isOnline = false;
    
    if (chat.chat_type === 'private' && chat.participants && chat.participants.length > 0) {
        // Find the other participant (not the current user)
        const otherParticipant = chat.participants.find(p => 
            p.user_id !== app.currentUser.user_id
        );
        
        if (otherParticipant) {
            chatName = otherParticipant.full_name;
            isOnline = otherParticipant.is_online;
            senderName = otherParticipant.full_name;
        }
    } else {
        senderName = chat.title;
    }
    
    // Get message preview from last messages if we have them
    // This would come from the backend in a real app
    
    // Get unread count
    const unreadCount = chatState.unreadCounts[chat.chat_id] || 0;
    
    // Determine avatar image
    let avatarSrc;
    if (chat.chat_type === 'private') {
        const otherParticipant = chat.participants.find(p => 
            p.user_id !== app.currentUser.user_id
        );
        avatarSrc = otherParticipant && otherParticipant.profile_image 
            ? otherParticipant.profile_image 
            : config.defaultAvatars.user;
    } else if (chat.chat_type === 'group') {
        avatarSrc = chat.photo || config.defaultAvatars.group;
    } else {
        avatarSrc = chat.photo || config.defaultAvatars.channel;
    }
    
    chatElement.innerHTML = `
        <div class="chat-avatar">
            <img src="${avatarSrc}" alt="${escapeHtml(chatName)}" class="chat-avatar-img">
            <span class="status-indicator ${isOnline ? 'online' : ''}"></span>
        </div>
        <div class="chat-content">
            <div class="chat-header">
                <h4 class="chat-name">${escapeHtml(chatName)}</h4>
                <span class="chat-time">${lastMessageTime}</span>
            </div>
            <div class="chat-message-preview">
                <p class="preview-text">${
                    senderName && lastMessagePreview !== 'No messages yet' 
                        ? escapeHtml(`${truncateText(senderName, 10)}: ${lastMessagePreview}`) 
                        : escapeHtml(lastMessagePreview)
                }</p>
                <span class="unread-badge">${unreadCount > 0 ? unreadCount : ''}</span>
            </div>
        </div>
    `;
    
    // Add click event listener
    chatElement.addEventListener('click', () => selectChat(chat.chat_id));
    
    // Add keyboard accessibility
    chatElement.setAttribute('tabindex', '0');
    chatElement.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            selectChat(chat.chat_id);
        }
    });
    
    return chatElement;
};

/**
 * Select a chat to display in the main content
 * 
 * @param {number} chatId - Chat ID
 */
const selectChat = async (chatId) => {
    try {
        // If it's the same chat, do nothing
        if (chatState.currentChatId === chatId) {
            return;
        }
        
        // Clear current active chat
        const activeChat = document.querySelector('.chat-item.active');
        if (activeChat) {
            activeChat.classList.remove('active');
        }
        
        // Set active chat in the sidebar
        const chatElement = document.querySelector(`.chat-item[data-chat-id="${chatId}"]`);
        if (chatElement) {
            chatElement.classList.add('active');
        }
        
        // Get chat data
        const response = await chatsApi.getChat(chatId);
        
        if (response.status === 'success' && response.data) {
            // Update chat state
            chatState.currentChatId = chatId;
            chatState.currentChat = response.data;
            
            // Mark messages as read for this chat
            await messagesApi.markAsRead(chatId);
            
            // Clear unread count for this chat
            if (chatState.unreadCounts[chatId]) {
                chatState.unreadCounts[chatId] = 0;
                
                // Update unread badge in the UI
                const unreadBadge = chatElement?.querySelector('.unread-badge');
                if (unreadBadge) {
                    unreadBadge.textContent = '';
                }
            }
            
            // Emit event that a chat was selected
            window.dispatchEvent(new CustomEvent('chatSelected', { 
                detail: { chatId, chat: chatState.currentChat }
            }));
        }
    } catch (error) {
        console.error('Error selecting chat:', error);
    }
};

/**
 * Handle creating a new chat
 */
const handleNewChat = () => {
    // Create modal for creating a new chat
    const createChatTemplate = document.getElementById('create-chat-template');
    if (!createChatTemplate) return;
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'popup';
    modal.appendChild(createChatTemplate.content.cloneNode(true));
    document.body.appendChild(modal);
    
    // Get form and add event listeners
    const createChatForm = document.getElementById('create-chat-form');
    const cancelBtn = modal.querySelector('.cancel-btn');
    
    if (createChatForm) {
        createChatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleCreateChat(createChatForm, modal);
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(modal);
        });
    }
    
    // Set up user search
    const searchInput = document.getElementById('search-users');
    const searchResults = document.getElementById('user-search-results');
    const selectedParticipants = document.getElementById('selected-participants');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(async (e) => {
            const query = e.target.value.trim();
            if (query.length < 2) {
                searchResults.innerHTML = '';
                return;
            }
            
            try {
                // This would be a real API call to search users
                // For demo, we'll just use mock data
                const users = [
                    { user_id: 1, username: 'john_doe', full_name: 'John Doe', profile_image: config.defaultAvatars.user },
                    { user_id: 2, username: 'jane_smith', full_name: 'Jane Smith', profile_image: config.defaultAvatars.user },
                    { user_id: 3, username: 'robert_johnson', full_name: 'Robert Johnson', profile_image: config.defaultAvatars.user }
                ];
                
                // Filter users that match the query
                const filteredUsers = users.filter(user => 
                    user.username.includes(query) || 
                    user.full_name.toLowerCase().includes(query.toLowerCase())
                );
                
                // Clear previous results
                searchResults.innerHTML = '';
                
                if (filteredUsers.length === 0) {
                    searchResults.innerHTML = '<div class="search-result-empty">No users found</div>';
                    return;
                }
                
                // Display results
                filteredUsers.forEach(user => {
                    const userElement = document.createElement('div');
                    userElement.className = 'search-result-item';
                    userElement.dataset.userId = user.user_id;
                    userElement.innerHTML = `
                        <div class="user-avatar">
                            <img src="${user.profile_image || config.defaultAvatars.user}" alt="${user.full_name}" class="avatar-img">
                        </div>
                        <div class="user-info">
                            <h4>${escapeHtml(user.full_name)}</h4>
                            <span>@${escapeHtml(user.username)}</span>
                        </div>
                    `;
                    
                    // Add click event
                    userElement.addEventListener('click', () => {
                        // Check if already selected
                        if (!selectedParticipants.querySelector(`[data-user-id="${user.user_id}"]`)) {
                            // Add to selected participants
                            const selectedUser = document.createElement('div');
                            selectedUser.className = 'selected-user';
                            selectedUser.dataset.userId = user.user_id;
                            selectedUser.innerHTML = `
                                <span>${escapeHtml(user.full_name)}</span>
                                <button type="button" class="remove-user-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            
                            // Add remove event
                            const removeBtn = selectedUser.querySelector('.remove-user-btn');
                            if (removeBtn) {
                                removeBtn.addEventListener('click', () => {
                                    selectedParticipants.removeChild(selectedUser);
                                });
                            }
                            
                            selectedParticipants.appendChild(selectedUser);
                        }
                        
                        // Clear search input and results
                        searchInput.value = '';
                        searchResults.innerHTML = '';
                    });
                    
                    searchResults.appendChild(userElement);
                });
            } catch (error) {
                console.error('Error searching users:', error);
                searchResults.innerHTML = '<div class="search-result-error">Error searching users</div>';
            }
        }, 300));
    }
};

/**
 * Handle creating a chat
 * 
 * @param {HTMLFormElement} form - Create chat form
 * @param {HTMLElement} modal - Modal element
 */
const handleCreateChat = async (form, modal) => {
    // Get form data
    const chatType = form.elements['chat_type'].value;
    const title = form.elements['title'].value;
    const description = form.elements['description'].value;
    
    // Get selected participants
    const selectedUsers = document.querySelectorAll('.selected-user');
    const participants = Array.from(selectedUsers).map(user => 
        parseInt(user.dataset.userId)
    );
    
    // Validate form
    if ((chatType === 'group' || chatType === 'channel') && !title.trim()) {
        const formError = form.querySelector('.form-error');
        if (formError) {
            formError.textContent = 'Title is required for groups and channels';
        }
        return;
    }
    
    // Disable form
    form.querySelectorAll('button, input, textarea').forEach(el => {
        el.disabled = true;
    });
    
    try {
        // Create chat
        const response = await chatsApi.createChat({
            chat_type: chatType,
            title,
            description,
            participants
        });
        
        if (response.status === 'success' && response.data) {
            // Add the new chat to the chat list
            chatState.chats.unshift(response.data);
            
            // Re-render chat list
            renderChatList();
            
            // Select the new chat
            selectChat(response.data.chat_id);
            
            // Close modal
            document.body.removeChild(modal);
        } else {
            const formError = form.querySelector('.form-error');
            if (formError) {
                formError.textContent = response.message || 'Failed to create chat';
            }
            
            // Re-enable form
            form.querySelectorAll('button, input, textarea').forEach(el => {
                el.disabled = false;
            });
        }
    } catch (error) {
        const formError = form.querySelector('.form-error');
        if (formError) {
            formError.textContent = error.message || 'Failed to create chat';
        }
        
        // Re-enable form
        form.querySelectorAll('button, input, textarea').forEach(el => {
            el.disabled = false;
        });
        
        console.error('Error creating chat:', error);
    }
};

/**
 * Handle searching chats
 * 
 * @param {Event} e - Input event
 */
const handleSearchChats = (e) => {
    const query = e.target.value.trim().toLowerCase();
    
    if (!query) {
        renderChatList();
        return;
    }
    
    // Filter chats that match the query
    const filteredChats = chatState.chats.filter(chat => {
        // For private chats, search in participant names
        if (chat.chat_type === 'private' && chat.participants) {
            return chat.participants.some(participant => 
                participant.full_name.toLowerCase().includes(query) ||
                participant.username.toLowerCase().includes(query)
            );
        }
        
        // For groups and channels, search in title
        return chat.title && chat.title.toLowerCase().includes(query);
    });
    
    renderChatList(filteredChats);
};

/**
 * Handle logout
 */
const handleLogout = async () => {
    try {
        await authApi.logout();
        window.location.href = '/login';
    } catch (error) {
        console.error('Error logging out:', error);
    }
};

// Export the chat module functions
const chatModule = {
    init: initChat,
    loadChats,
    selectChat,
    handleNewChat,
    handleSearchChats,
    handleLogout,
    state: chatState
}; 