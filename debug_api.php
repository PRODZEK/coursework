<?php
/**
 * API Debugging Tool
 * Використовуйте цей скрипт для тестування API викликів
 */

// Включити відображення помилок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Заголовки для CORS та JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Отримати URL-адресу API з параметра
$api_url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($api_url)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide an API URL using the "url" parameter'
    ]);
    exit;
}

// Додаємо базовий шлях, якщо URL не починається з /
if (strpos($api_url, '/') !== 0) {
    $api_url = '/api/' . $api_url;
}

// Повний шлях до API
$full_url = 'http://' . $_SERVER['HTTP_HOST'] . $api_url;

echo json_encode([
    'debug_info' => [
        'requested_url' => $full_url,
        'method' => $_SERVER['REQUEST_METHOD'],
        'server_info' => [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'script_filename' => $_SERVER['SCRIPT_FILENAME']
        ]
    ]
]);

// Тестуємо запит до API
try {
    // Ініціалізуємо cURL
    $ch = curl_init();
    
    // Встановлюємо опції cURL
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Виконуємо запит
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Перевіряємо на помилки
    if (curl_errno($ch)) {
        echo json_encode([
            'success' => false,
            'message' => 'cURL Error: ' . curl_error($ch),
            'status' => $status
        ]);
    } else {
        // Спробуємо розпарсити відповідь як JSON
        $json_response = json_decode($response, true);
        
        if ($json_response === null && json_last_error() !== JSON_ERROR_NONE) {
            // Якщо не JSON, повертаємо перші 1000 символів відповіді
            echo json_encode([
                'success' => false,
                'message' => 'API returned non-JSON response',
                'status' => $status,
                'json_error' => json_last_error_msg(),
                'raw_response_preview' => debug_api . phpsubstr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : '')
            ]);
        } else {
            // JSON відповідь успішна
            echo json_encode([
                'success' => true,
                'status' => $status,
                'response' => $json_response
            ]);
        }
    }
    
    // Закриваємо cURL
    curl_close($ch);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
} 