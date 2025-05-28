/**
 * Utility Functions
 * Helper functions used throughout the application
 */

/**
 * Format a date according to the application's needs
 * 
 * @param {Date|string} date - Date to format
 * @param {string} format - Format type (time, date, dateTime, fullDate)
 * @returns {string} - Formatted date string
 */
const formatDate = (date, format = 'time') => {
    if (!date) return '';
    
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const options = config.dateFormats[format] || config.dateFormats.time;
    
    // If date is today, show only time
    const today = new Date();
    if (format === 'dateTime' && 
        dateObj.getDate() === today.getDate() &&
        dateObj.getMonth() === today.getMonth() &&
        dateObj.getFullYear() === today.getFullYear()) {
        return dateObj.toLocaleTimeString([], config.dateFormats.time);
    }
    
    return dateObj.toLocaleString([], options);
};

/**
 * Format a relative time (e.g., "2 minutes ago")
 * 
 * @param {Date|string} date - Date to format
 * @returns {string} - Relative time string
 */
const formatRelativeTime = (date) => {
    if (!date) return '';
    
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diffInSeconds = Math.floor((now - dateObj) / 1000);
    
    // Less than a minute
    if (diffInSeconds < 60) {
        return 'just now';
    }
    
    // Less than an hour
    if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
    }
    
    // Less than a day
    if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    }
    
    // Less than a week
    if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    }
    
    // Default to formatted date
    return formatDate(dateObj, 'date');
};

/**
 * Truncate text to a maximum length
 * 
 * @param {string} text - Text to truncate
 * @param {number} maxLength - Maximum length
 * @returns {string} - Truncated text
 */
const truncateText = (text, maxLength = 50) => {
    if (!text || text.length <= maxLength) return text || '';
    return text.substring(0, maxLength) + '...';
};

/**
 * Get the appropriate avatar URL
 * 
 * @param {Object} entity - User or chat object
 * @param {string} type - Type of entity (user, group, channel)
 * @returns {string} - Avatar URL
 */
const getAvatarUrl = (entity, type = 'user') => {
    if (!entity) return config.defaultAvatars[type];
    
    if (type === 'user' && entity.profile_image) {
        return entity.profile_image;
    } else if ((type === 'group' || type === 'channel') && entity.photo) {
        return entity.photo;
    }
    
    return config.defaultAvatars[type];
};

/**
 * Escape HTML special characters
 * 
 * @param {string} text - Text to escape
 * @returns {string} - Escaped text
 */
const escapeHtml = (text) => {
    if (!text) return '';
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, (m) => map[m]);
};

/**
 * Format message content with HTML (e.g., links, line breaks)
 * 
 * @param {string} content - Message content
 * @returns {string} - Formatted HTML content
 */
const formatMessageContent = (content) => {
    if (!content) return '';
    
    // First escape HTML
    let formatted = escapeHtml(content);
    
    // Convert URLs to links
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    formatted = formatted.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    
    // Convert line breaks to <br>
    formatted = formatted.replace(/\n/g, '<br>');
    
    return formatted;
};

/**
 * Play a sound
 * 
 * @param {string} soundType - Type of sound to play
 */
const playSound = (soundType) => {
    const soundUrl = config.ui[`${soundType}Sound`];
    if (soundUrl) {
        const audio = new Audio(soundUrl);
        audio.play().catch(e => {
            // Silence autoplay errors (browsers may block autoplay)
        });
    }
};

/**
 * Show a notification
 * 
 * @param {string} title - Notification title
 * @param {string} body - Notification body
 * @param {string} icon - Notification icon URL
 */
const showNotification = (title, body, icon = '/assets/img/logo.png') => {
    // Check if notifications are supported
    if (!('Notification' in window)) return;
    
    // Check notification permission
    if (Notification.permission === 'granted') {
        new Notification(title, { body, icon });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification(title, { body, icon });
            }
        });
    }
};

/**
 * Debounce a function
 * 
 * @param {Function} func - Function to debounce
 * @param {number} wait - Time to wait in milliseconds
 * @returns {Function} - Debounced function
 */
const debounce = (func, wait = 300) => {
    let timeout;
    
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Create a DOM element from an HTML string
 * 
 * @param {string} html - HTML string
 * @returns {HTMLElement} - Created DOM element
 */
const createElementFromHTML = (html) => {
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    return div.firstChild;
};

/**
 * Clone a template and populate it with data
 * 
 * @param {string} templateId - ID of the template element
 * @param {Object} data - Data to populate the template with
 * @returns {HTMLElement} - Populated template element
 */
const renderTemplate = (templateId, data = {}) => {
    const template = document.getElementById(templateId);
    if (!template) return null;
    
    const element = template.content.cloneNode(true).firstElementChild;
    
    // Set data attributes
    Object.keys(data).forEach(key => {
        if (key.startsWith('data-')) {
            element.setAttribute(key, data[key]);
        }
    });
    
    // Find elements with data-field attribute and set their content
    element.querySelectorAll('[data-field]').forEach(el => {
        const field = el.getAttribute('data-field');
        const value = data[field];
        
        if (value !== undefined) {
            if (el.tagName === 'IMG') {
                el.src = value;
            } else {
                el.textContent = value;
            }
        }
    });
    
    return element;
}; 