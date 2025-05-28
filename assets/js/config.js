/**
 * Telegram Clone
 * Frontend configuration
 */

const config = {
    // API base URL
    apiUrl: '', // Empty string for same-origin API
    
    // API endpoints
    endpoints: {
        // Authentication endpoints
        auth: '/api/auth',
        
        // Chat endpoints
        chats: '/api/chats',
        
        // Message endpoints
        messages: '/api/messages',
        
        // User endpoints
        users: '/api/users',
        
        // Real-time updates
        updates: '/api/updates'
    },
    
    // Default avatars
    defaultAvatars: {
        user: '/assets/img/default-avatar.png',
        group: '/assets/img/default-group.png',
        channel: '/assets/img/default-channel.png'
    },
    
    // Sounds
    sounds: {
        notification: '/assets/sound/notification.mp3',
        messageSent: '/assets/sound/message-sent.mp3',
        call: '/assets/sound/call.mp3'
    },
    
    // UI settings
    ui: {
        messageLoadCount: 20,
        typingTimeout: 2000,
        theme: 'light'
    },
    
    // Date formatting options
    dateFormats: {
        time: { hour: '2-digit', minute: '2-digit' },
        date: { month: 'short', day: 'numeric' },
        dateTime: { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' },
        fullDate: { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }
    }
}; 