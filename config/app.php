<?php
/**
 * Application Configuration
 */
return [
    // Application settings
    'app' => [
        'name' => 'Telegram Clone',
        'version' => '1.0.0',
        'debug' => true,
        'timezone' => 'UTC',
        'url' => 'http://localhost', // Change this to your domain
        'upload_dir' => __DIR__ . '/../uploads', // Directory for file uploads
    ],
    
    // Session settings
    'session' => [
        'lifetime' => 86400 * 30, // 30 days
        'secure' => false, // Set to true for HTTPS only
        'http_only' => true,
        'same_site' => 'Lax',
    ],
    
    // Security settings
    'security' => [
        'password_min_length' => 8,
        'password_algo' => PASSWORD_DEFAULT,
        'csrf_token_lifetime' => 3600, // 1 hour
    ],
    
    // Upload settings
    'uploads' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'audio' => ['mp3', 'wav', 'ogg'],
            'video' => ['mp4', 'webm', 'avi', 'mov'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        ],
    ],
    
    // Real-time settings
    'realtime' => [
        'update_interval' => 2, // Seconds between update checks
        'keep_alive_interval' => 30, // Seconds between keep-alive pings
    ],
    
    // API settings
    'api' => [
        'rate_limit' => 100, // Maximum requests per minute
        'token_lifetime' => 3600 * 24, // 24 hours
    ],
    
    // UI settings
    'ui' => [
        'theme' => 'light',
        'color_scheme' => 'blue',
        'items_per_page' => 20,
    ],
]; 