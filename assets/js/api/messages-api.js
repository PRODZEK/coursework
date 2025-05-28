/**
 * Messages API Module
 * Handle all API calls to the messages endpoints
 */

const messagesApi = (() => {
    // Base messages endpoint
    const endpoint = config.endpoints.messages;
    
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
     * Get messages from a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} limit - Number of messages to get
     * @param {number} offset - Offset for pagination
     * @returns {Promise} - Promise with response data
     */
    const getMessages = async (chatId, limit = 20, offset = 0) => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}?chat_id=${chatId}&limit=${limit}&offset=${offset}`, options);
    };
    
    /**
     * Send a new message
     * 
     * @param {Object} messageData - Message data with properties:
     *   - chat_id: number (required)
     *   - content: string (required for text messages)
     *   - message_type: string (default: 'text')
     *   - reply_to_message_id: number (optional)
     *   - file: File object (for photo, video, file types)
     * @returns {Promise} - Promise with response data
     */
    const sendMessage = async (messageData) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(messageData)
        };
        
        // If there's a file, use FormData instead of JSON
        if (messageData.file) {
            const formData = new FormData();
            
            // Add all message data except file to the form
            Object.keys(messageData).forEach(key => {
                if (key !== 'file') {
                    formData.append(key, messageData[key]);
                }
            });
            
            // Add the file
            formData.append('file', messageData.file);
            
            // Update options to use FormData
            options.headers = {
                'Authorization': `Bearer ${authToken}`
            };
            options.body = formData;
        }
        
        return await apiClient.request(endpoint, options);
    };
    
    /**
     * Get a specific message
     * 
     * @param {number} messageId - Message ID
     * @returns {Promise} - Promise with response data
     */
    const getMessage = async (messageId) => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${messageId}`, options);
    };
    
    /**
     * Edit a message
     * 
     * @param {number} messageId - Message ID
     * @param {string} content - New message content
     * @returns {Promise} - Promise with response data
     */
    const editMessage = async (messageId, content) => {
        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ content })
        };
        
        return await apiClient.request(`${endpoint}/${messageId}`, options);
    };
    
    /**
     * Delete a message
     * 
     * @param {number} messageId - Message ID
     * @returns {Promise} - Promise with response data
     */
    const deleteMessage = async (messageId) => {
        const options = {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${messageId}`, options);
    };
    
    /**
     * Forward a message to another chat
     * 
     * @param {number} messageId - Message ID to forward
     * @param {number} chatId - Destination chat ID
     * @returns {Promise} - Promise with response data
     */
    const forwardMessage = async (messageId, chatId) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ chat_id: chatId })
        };
        
        return await apiClient.request(`${endpoint}/${messageId}/forward`, options);
    };
    
    /**
     * Mark messages as read in a chat
     * 
     * @param {number} chatId - Chat ID
     * @param {number} messageId - Optional - ID of the last read message
     * @returns {Promise} - Promise with response data
     */
    const markAsRead = async (chatId, messageId = null) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({
                chat_id: chatId,
                message_id: messageId
            })
        };
        
        return await apiClient.request(`${config.endpoints.chats}/${chatId}/read`, options);
    };
    
    /**
     * Get unread message counts for all chats
     * 
     * @returns {Promise} - Promise with response data
     */
    const getUnreadCounts = async () => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${config.endpoints.chats}/unread`, options);
    };
    
    /**
     * Get message reactions
     * 
     * @param {number} messageId - Message ID
     * @returns {Promise} - Promise with response data
     */
    const getReactions = async (messageId) => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${messageId}/reactions`, options);
    };
    
    /**
     * Add a reaction to a message
     * 
     * @param {number} messageId - Message ID
     * @param {string} reaction - Reaction emoji or code
     * @returns {Promise} - Promise with response data
     */
    const addReaction = async (messageId, reaction) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ reaction })
        };
        
        return await apiClient.request(`${endpoint}/${messageId}/reactions`, options);
    };
    
    /**
     * Remove a reaction from a message
     * 
     * @param {number} messageId - Message ID
     * @param {string} reaction - Reaction emoji or code
     * @returns {Promise} - Promise with response data
     */
    const removeReaction = async (messageId, reaction) => {
        const options = {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/${messageId}/reactions/${encodeURIComponent(reaction)}`, options);
    };
    
    // Public API
    return {
        setAuthToken,
        getMessages,
        sendMessage,
        getMessage,
        editMessage,
        deleteMessage,
        forwardMessage,
        markAsRead,
        getUnreadCounts,
        getReactions,
        addReaction,
        removeReaction
    };
})(); 