/**
 * Auth API Module
 * Handle all API calls to the authentication endpoints
 */

const authApi = (() => {
    // Base auth endpoint
    const endpoint = config.endpoints.auth;
    
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
     * Register a new user
     * 
     * @param {Object} userData - User registration data with properties:
     *   - username: string (required)
     *   - password: string (required)
     *   - email: string (required)
     *   - full_name: string (required)
     * @returns {Promise} - Promise with response data
     */
    const register = async (userData) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        };
        
        return await apiClient.request(`${endpoint}/register`, options);
    };
    
    /**
     * Log in a user
     * 
     * @param {string} username - Username
     * @param {string} password - Password
     * @returns {Promise} - Promise with response data (including token)
     */
    const login = async (username, password) => {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        };
        
        const response = await apiClient.request(`${endpoint}/login`, options);
        
        // Store token if successful
        if (response.status === 'success' && response.data && response.data.token) {
            authToken = response.data.token;
            localStorage.setItem('auth_token', authToken);
        }
        
        return response;
    };
    
    /**
     * Log out a user
     * 
     * @returns {Promise} - Promise with response data
     */
    const logout = async () => {
        const options = {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        const response = await apiClient.request(`${endpoint}/logout`, options);
        
        // Clear token regardless of response
        authToken = null;
        localStorage.removeItem('auth_token');
        
        return response;
    };
    
    /**
     * Get information about the current authenticated user
     * 
     * @returns {Promise} - Promise with response data
     */
    const getCurrentUser = async () => {
        const options = {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        };
        
        return await apiClient.request(`${endpoint}/me`, options);
    };
    
    /**
     * Update user profile
     * 
     * @param {Object} userData - User data to update
     * @returns {Promise} - Promise with response data
     */
    const updateProfile = async (userData) => {
        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(userData)
        };
        
        return await apiClient.request(`${endpoint}/me`, options);
    };
    
    /**
     * Update user profile picture
     * 
     * @param {File} imageFile - Profile image file
     * @returns {Promise} - Promise with response data
     */
    const updateProfilePicture = async (imageFile) => {
        const formData = new FormData();
        formData.append('profile_image', imageFile);
        
        const options = {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            },
            body: formData
        };
        
        return await apiClient.request(`${endpoint}/me/profile-image`, options);
    };
    
    /**
     * Change user password
     * 
     * @param {string} currentPassword - Current password
     * @param {string} newPassword - New password
     * @returns {Promise} - Promise with response data
     */
    const changePassword = async (currentPassword, newPassword) => {
        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        };
        
        return await apiClient.request(`${endpoint}/me/password`, options);
    };
    
    // Public API
    return {
        setAuthToken,
        register,
        login,
        logout,
        getCurrentUser,
        updateProfile,
        updateProfilePicture,
        changePassword
    };
})(); 