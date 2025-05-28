/**
 * API Client
 * Handles all communication with the backend API
 */
class ApiClient {
    /**
     * Make an API request
     * 
     * @param {string} url - API endpoint URL
     * @param {Object} options - Fetch options
     * @returns {Promise} - Promise with the response data
     */
    async request(url, options = {}) {
        // Default headers
        if (!options.headers) {
            options.headers = {
                'Content-Type': 'application/json'
            };
        }
        
        // Add base URL if provided
        const apiUrl = config.apiUrl + url;
        
        try {
            const response = await fetch(apiUrl, options);
            const data = await response.json();
            
            if (!response.ok) {
                throw {
                    status: response.status,
                    message: data.message || 'Unknown error',
                    errors: data.errors || {}
                };
            }
            
            return data;
        } catch (error) {
            // Rethrow the error to be handled by the caller
            throw error;
        }
    }
    
    /**
     * GET request
     * 
     * @param {string} url - API endpoint URL
     * @returns {Promise} - Promise with the response data
     */
    async get(url) {
        return this.request(url, {
            method: 'GET'
        });
    }
    
    /**
     * POST request
     * 
     * @param {string} url - API endpoint URL
     * @param {Object} data - Request body data
     * @returns {Promise} - Promise with the response data
     */
    async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * PUT request
     * 
     * @param {string} url - API endpoint URL
     * @param {Object} data - Request body data
     * @returns {Promise} - Promise with the response data
     */
    async put(url, data) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * PATCH request
     * 
     * @param {string} url - API endpoint URL
     * @param {Object} data - Request body data
     * @returns {Promise} - Promise with the response data
     */
    async patch(url, data) {
        return this.request(url, {
            method: 'PATCH',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * DELETE request
     * 
     * @param {string} url - API endpoint URL
     * @returns {Promise} - Promise with the response data
     */
    async delete(url) {
        return this.request(url, {
            method: 'DELETE'
        });
    }
}

/**
 * Authentication API
 * Handles user registration, login, and session management
 */
class AuthApi {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }
    
    /**
     * Register a new user
     * 
     * @param {Object} userData - User registration data
     * @returns {Promise} - Promise with the registered user data
     */
    async register(userData) {
        return this.apiClient.post(config.endpoints.auth.register, userData);
    }
    
    /**
     * Login a user
     * 
     * @param {string} login - Username or email
     * @param {string} password - User password
     * @returns {Promise} - Promise with the authenticated user data and token
     */
    async login(login, password) {
        return this.apiClient.post(config.endpoints.auth.login, { login, password });
    }
    
    /**
     * Logout the current user
     * 
     * @returns {Promise} - Promise with the logout result
     */
    async logout() {
        return this.apiClient.post(config.endpoints.auth.logout);
    }
    
    /**
     * Get the current authenticated user
     * 
     * @returns {Promise} - Promise with the current user data
     */
    async getCurrentUser() {
        return this.apiClient.get(config.endpoints.auth.user);
    }
}

/**
 * Chats API
 * Handles operations related to chats
 */
class ChatsApi {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }
    
    /**
     * Get all chats for the current user
     * 
     * @returns {Promise} - Promise with the user's chats
     */
    async getChats() {
        return this.apiClient.get(config.endpoints.chats.list);
    }
    
    /**
     * Get a specific chat
     * 
     * @param {number} chatId - Chat ID
     * @returns {Promise} - Promise with the chat data
     */
    async getChat(chatId) {
        return this.apiClient.get(config.endpoints.chats.get(chatId));
    }
    
    /**
     * Create a new chat (group or channel)
     * 
     * @param {Object} chatData - Chat data
     * @returns {Promise} - Promise with the created chat data
     */
    async createChat(chatData) {
        return this.apiClient.post(config.endpoints.chats.create, chatData);
    }
    
    /**
     * Update a chat's information
     * 
     * @param {number} chatId - Chat ID
     * @param {Object} chatData - Updated chat data
     * @returns {Promise} - Promise with the updated chat data
     */
    async updateChat(chatId, chatData) {
        return this.apiClient.patch(config.endpoints.chats.update(chatId), chatData);
    }
    
    /**
     * Get or create a private chat with another user
     * 
     * @param {number} userId - User ID to chat with
     * @returns {Promise} - Promise with the private chat data
     */
    async getPrivateChat(userId) {
        return this.apiClient.get(`${config.endpoints.chats.private}?user_id=${userId}`);
    }
    
    /**
     * Add a participant to a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} userId - User ID to add
     * @param {string} role - Participant role (member, admin)
     * @returns {Promise} - Promise with the updated chat data
     */
    async addParticipant(chatId, userId, role = 'member') {
        return this.apiClient.post(
            config.endpoints.chats.addParticipant(chatId), 
            { user_id: userId, role }
        );
    }
    
    /**
     * Remove a participant from a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} userId - User ID to remove
     * @returns {Promise} - Promise with the updated chat data
     */
    async removeParticipant(chatId, userId) {
        return this.apiClient.delete(config.endpoints.chats.removeParticipant(chatId, userId));
    }
    
    /**
     * Update a participant's role
     * 
     * @param {number} chatId - Chat ID
     * @param {number} userId - User ID
     * @param {string} role - New role (member, admin)
     * @returns {Promise} - Promise with the updated chat data
     */
    async updateParticipantRole(chatId, userId, role) {
        return this.apiClient.patch(
            config.endpoints.chats.updateRole(chatId, userId), 
            { role }
        );
    }
}

/**
 * Messages API
 * Handles operations related to messages
 */
class MessagesApi {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }
    
    /**
     * Get messages for a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} limit - Number of messages to retrieve
     * @param {number} offset - Offset for pagination
     * @returns {Promise} - Promise with the messages data
     */
    async getMessages(chatId, limit = 50, offset = 0) {
        return this.apiClient.get(
            `${config.endpoints.messages.list}?chat_id=${chatId}&limit=${limit}&offset=${offset}`
        );
    }
    
    /**
     * Send a new message
     * 
     * @param {Object} messageData - Message data
     * @returns {Promise} - Promise with the sent message data
     */
    async sendMessage(messageData) {
        return this.apiClient.post(config.endpoints.messages.send, messageData);
    }
    
    /**
     * Edit a message
     * 
     * @param {number} messageId - Message ID
     * @param {string} content - New content
     * @returns {Promise} - Promise with the updated message data
     */
    async editMessage(messageId, content) {
        return this.apiClient.patch(config.endpoints.messages.edit(messageId), { content });
    }
    
    /**
     * Delete a message
     * 
     * @param {number} messageId - Message ID
     * @returns {Promise} - Promise with the deletion result
     */
    async deleteMessage(messageId) {
        return this.apiClient.delete(config.endpoints.messages.delete(messageId));
    }
    
    /**
     * Forward a message
     * 
     * @param {number} messageId - Message ID to forward
     * @param {number} toChatId - Destination chat ID
     * @returns {Promise} - Promise with the forwarded message data
     */
    async forwardMessage(messageId, toChatId) {
        return this.apiClient.post(config.endpoints.messages.forward, {
            message_id: messageId,
            to_chat_id: toChatId
        });
    }
    
    /**
     * Mark messages as read
     * 
     * @param {number} chatId - Chat ID
     * @param {number} messageId - Last read message ID (optional)
     * @returns {Promise} - Promise with the read status result
     */
    async markAsRead(chatId, messageId = null) {
        const data = { chat_id: chatId };
        if (messageId) {
            data.message_id = messageId;
        }
        return this.apiClient.post(config.endpoints.messages.read, data);
    }
    
    /**
     * Get unread message counts for all chats
     * 
     * @returns {Promise} - Promise with the unread counts data
     */
    async getUnreadCounts() {
        return this.apiClient.get(config.endpoints.messages.unread);
    }
}

/**
 * Create and export API instances
 */
const apiClient = new ApiClient();
const authApi = new AuthApi(apiClient);
const chatsApi = new ChatsApi(apiClient);
const messagesApi = new MessagesApi(apiClient); 