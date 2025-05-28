/**
 * Telegram Clone
 * JS Entry Point - Loads all required JavaScript files
 */

document.addEventListener('DOMContentLoaded', () => {
    // Helper function to load script
    const loadScript = (src, async = true) => {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = async;
            
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
            
            document.head.appendChild(script);
        });
    };
    
    // Load scripts in the correct order
    const loadAllScripts = async () => {
        try {
            // First load config and utility functions
            await loadScript('/assets/js/config.js', false);
            await loadScript('/assets/js/utils.js', false);
            
            // Load API client
            await loadScript('/assets/js/api.js', false);
            
            // Load API modules
            await loadScript('/assets/js/api/auth-api.js', false);
            await loadScript('/assets/js/api/chats-api.js', false);
            await loadScript('/assets/js/api/messages-api.js', false);
            
            // Load application modules
            await loadScript('/assets/js/auth.js', false);
            await loadScript('/assets/js/chat.js', false);
            await loadScript('/assets/js/messages.js', false);
            await loadScript('/assets/js/realtime.js', false);
            
            // Finally, load the main app
            await loadScript('/assets/js/app.js', false);
            
            console.log('All scripts loaded successfully');
        } catch (error) {
            console.error('Error loading scripts:', error);
        }
    };
    
    // Start loading scripts
    loadAllScripts();
}); 