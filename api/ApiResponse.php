<?php
/**
 * API Response Handler
 * Standardizes API responses across the application
 */
class ApiResponse {
    /**
     * Send a successful response
     * 
     * @param mixed $data The data to include in the response
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        self::send([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     * @return void
     */
    public static function error(string $message = 'Error', int $statusCode = 400, array $errors = []): void {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        self::send($response, $statusCode);
    }
    
    /**
     * Send the JSON response
     * 
     * @param array $data The response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    private static function send(array $data, int $statusCode): void {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Set JSON header
        header('Content-Type: application/json');
        
        // Allow CORS for API
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Output the response
        echo json_encode($data);
        exit;
    }
} 