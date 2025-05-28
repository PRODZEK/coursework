<?php
/**
 * EventStream Class
 * Handles Server-Sent Events (SSE) for real-time updates
 */
class EventStream {
    /**
     * Start an SSE stream
     */
    public static function start() {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable buffering for Nginx
        
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Prevent buffer issues
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', 'off');
        
        // Flush PHP buffer
        if (ob_get_level()) {
            ob_end_flush();
        }
        
        // Send initial comment to keep connection open
        echo ": " . str_repeat(' ', 2048) . "\n\n";
        flush();
    }
    
    /**
     * Send an event with data
     * 
     * @param string $event Event name
     * @param mixed $data Data to send (will be JSON encoded)
     */
    public static function send(string $event, $data) {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }
    
    /**
     * Send a keep-alive comment to prevent connection timeout
     */
    public static function keepAlive() {
        echo ": keep-alive\n\n";
        flush();
    }
    
    /**
     * End the SSE stream
     */
    public static function end() {
        echo "event: close\n";
        echo "data: {\"message\": \"Stream closed\"}\n\n";
        flush();
        exit;
    }
} 