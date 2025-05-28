<?php
/**
 * Application Configuration
 * General settings for the Telegram Clone application
 */

return [
    // Application settings
    'app' => [
        'name' => 'Telegram Clone',
        'version' => '1.0.0',
        'url' => 'http://localhost:8080', // Update based on your local setup
        'timezone' => 'UTC',
        'debug' => true,
        'env' => 'development'
    ],
    
    // Security settings
    'security' => [
        'session_lifetime' => 3600 * 24, // 24 hours
        'password_min_length' => 8,
        'token_expiry' => 3600, // 1 hour 
        'password_algo' => PASSWORD_DEFAULT,
        'password_options' => [
            'cost' => 10,
        ],
    ],
    
    // File upload settings
    'uploads' => [
        'avatar' => [
            'path' => __DIR__ . '/../assets/uploads/avatars/',
            'max_size' => 2 * 1024 * 1024, // 2MB
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
        ],
        'attachments' => [
            'path' => __DIR__ . '/../assets/uploads/attachments/',
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain']
        ]
    ],
    
    // Real-time settings
    'realtime' => [
        'method' => 'sse', // Options: 'sse', 'long-polling', 'websocket'
        'polling_interval' => 3000, // 3 seconds (for long-polling)
        'keep_alive_interval' => 30000, // 30 seconds (for SSE keep-alive)
    ],
    
    // Rate limiting
    'rate_limits' => [
        'messages' => [
            'max' => 30,
            'window' => 60, // 30 messages per minute
        ],
        'login_attempts' => [
            'max' => 5,
            'window' => 300, // 5 attempts per 5 minutes
            'lockout_time' => 900 // 15 minutes
        ]
    ]
]; 