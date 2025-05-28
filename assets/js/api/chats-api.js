/**
 * Chats API Module
 * Handle all API calls to the chats endpoints
 */

const chatsApi = (() => {
    // Base chats endpoint
    const endpoint = config.endpoints.chats;
    
    // Authorization token
    let authToken;
    
    /**
     * Set the auth token for future requests
     * 
     * @param {string} token - JWT auth token
     */
    const setAuthToken = (token) => {
        authToken = token;
    };
    
    /**
     * Get all chats for the current user
     * 
     * @returns {Promise} - Promise with response data
     */
    const getChats = async () => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(endpoint, options);
    };
    
    /**
     * Get details for a specific chat
     * 
     * @param {number} chatId - Chat ID
     * @returns {Promise} - Promise with response data
     */
    const getChat = async (chatId) => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${chatId}`, options);
    };
    
    /**
     * Create a new chat
     * 
     * @param {Object} chatData - Chat data with properties:
     *   - chat_type: string ('private', 'group', 'channel')
     *   - title: string (required for group and channel)
     *   - description: string (optional)
     *   - participants: array of user IDs (required for private chat)
     * @returns {Promise} - Promise with response data
     */
    const createChat = async (chatData) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(chatData)
        };
        
        return await apiClient.request(endpoint, options);
    };
    
    /**
     * Update a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {Object} chatData - Chat data to update
     * @returns {Promise} - Promise with response data
     */
    const updateChat = async (chatId, chatData) => {
        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(chatData)
        };
        
        return await apiClient.request(`${endpoint}/${chatId}`, options);
    };
    
    /**
     * Add a participant to a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} userId - User ID to add
     * @returns {Promise} - Promise with response data
     */
    const addParticipant = async (chatId, userId) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ user_id: userId })
        };
        
        return await apiClient.request(`${endpoint}/${chatId}/participants`, options);
    };
    
    /**
     * Remove a participant from a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} userId - User ID to remove
     * @returns {Promise} - Promise with response data
     */
    const removeParticipant = async (chatId, userId) => {
        const options = {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${chatId}/participants/${userId}`, options);
    };
    
    /**
     * Search for users to add to a chat
     * 
     * @param {string} query - Search query
     * @returns {Promise} - Promise with response data
     */
    const searchUsers = async (query) => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${config.endpoints.users}?search=${encodeURIComponent(query)}`, options);
    };
    
    /**
     * Get all participants in a chat
     * 
     * @param {number} chatId - Chat ID
     * @returns {Promise} - Promise with response data
     */
    const getParticipants = async (chatId) => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${chatId}/participants`, options);
    };
    
    /**
     * Delete a chat
     * 
     * @param {number} chatId - Chat ID
     * @returns {Promise} - Promise with response data
     */
    const deleteChat = async (chatId) => {
        const options = {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${chatId}`, options);
    };
    
    // Public API
    return {
        setAuthToken,
        getChats,
        getChat,
        createChat,
        updateChat,
        addParticipant,
        removeParticipant,
        searchUsers,
        getParticipants,
        deleteChat
    };
})(); 