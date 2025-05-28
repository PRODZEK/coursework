<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../ApiResponse.php';

/**
 * Message Controller
 * Handles operations related to messages
 */
class MessageController {
    private $messageModel;
    private $chatModel;
    
    public function __construct() {
        $this->messageModel = new Message();
        $this->chatModel = new Chat();
    }
    
    /**
     * Get authenticated user ID from session
     * 
     * @return int|bool User ID if authenticated, false otherwise
     */
    private function getAuthUserId() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return $_SESSION['user_id'];
    }
    
    /**
     * Get messages for a chat
     */
    public function getMessages() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Get chat ID from URL
        if (!isset($_GET['chat_id'])) {
            ApiResponse::error('Chat ID is required', 400);
        }
        
        $chatId = (int)$_GET['chat_id'];
        
        // Check if the user is a participant of the chat
        if (!$this->chatModel->isParticipant($chatId, $userId)) {
            ApiResponse::error('You are not a participant of this chat', 403);
        }
        
        // Get pagination parameters
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // Limit should be between 1 and 100
        $limit = max(1, min(100, $limit));
        
        // Get messages
        $messages = $this->messageModel->getChatMessages($chatId, $limit, $offset);
        
        // Mark messages as read
        $this->messageModel->markAsRead($chatId, $userId);
        
        ApiResponse::success([
            'messages' => $messages,
            'total' => count($messages),
            'offset' => $offset,
            'limit' => $limit
        ]);
    }
    
    /**
     * Send a new message
     */
    public function sendMessage() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            ApiResponse::error('Invalid request data', 400);
        }
        
        // Validate required fields
        if (!isset($data['chat_id']) || !isset($data['message_type']) || !isset($data['content'])) {
            ApiResponse::error('Chat ID, message type and content are required', 400);
        }
        
        $chatId = (int)$data['chat_id'];
        
        // Check if the user is a participant of the chat
        if (!$this->chatModel->isParticipant($chatId, $userId)) {
            ApiResponse::error('You are not a participant of this chat', 403);
        }
        
        // Validate message type
        $validTypes = ['text', 'photo', 'video', 'file', 'audio', 'location', 'contact'];
        if (!in_array($data['message_type'], $validTypes)) {
            ApiResponse::error('Invalid message type', 400);
        }
        
        // Create message data
        $messageData = [
            'chat_id' => $chatId,
            'sender_id' => $userId,
            'message_type' => $data['message_type'],
            'content' => $data['content'],
            'reply_to_message_id' => isset($data['reply_to_message_id']) ? (int)$data['reply_to_message_id'] : null
        ];
        
        // Add file paths if provided
        if (isset($data['file_path'])) {
            $messageData['file_path'] = $data['file_path'];
        }
        
        if (isset($data['thumbnail_path'])) {
            $messageData['thumbnail_path'] = $data['thumbnail_path'];
        }
        
        // Create the message
        $messageId = $this->messageModel->create($messageData);
        
        if (!$messageId) {
            ApiResponse::error('Failed to send message', 500);
        }
        
        // Get the created message
        $message = $this->messageModel->findById($messageId);
        
        // Update the last read message for the sender
        $this->chatModel->updateLastReadMessage($chatId, $userId, $messageId);
        
        ApiResponse::success($message, 'Message sent successfully', 201);
    }
    
    /**
     * Edit a message
     */
    public function editMessage() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a PUT or PATCH request
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get message ID from URL
        if (!isset($_GET['id'])) {
            ApiResponse::error('Message ID is required', 400);
        }
        
        $messageId = (int)$_GET['id'];
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['content'])) {
            ApiResponse::error('Content is required', 400);
        }
        
        // Update the message
        $updated = $this->messageModel->update($messageId, $data['content'], $userId);
        
        if (!$updated) {
            ApiResponse::error('Failed to update message or not authorized', 403);
        }
        
        // Get the updated message
        $message = $this->messageModel->findById($messageId);
        
        ApiResponse::success($message, 'Message updated successfully');
    }
    
    /**
     * Delete a message
     */
    public function deleteMessage() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a DELETE request
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get message ID from URL
        if (!isset($_GET['id'])) {
            ApiResponse::error('Message ID is required', 400);
        }
        
        $messageId = (int)$_GET['id'];
        
        // Delete the message
        $deleted = $this->messageModel->delete($messageId, $userId);
        
        if (!$deleted) {
            ApiResponse::error('Failed to delete message or not authorized', 403);
        }
        
        ApiResponse::success(null, 'Message deleted successfully');
    }
    
    /**
     * Forward a message to another chat
     */
    public function forwardMessage() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['message_id']) || !isset($data['to_chat_id'])) {
            ApiResponse::error('Message ID and target chat ID are required', 400);
        }
        
        $messageId = (int)$data['message_id'];
        $toChatId = (int)$data['to_chat_id'];
        
        // Check if the user is a participant of the target chat
        if (!$this->chatModel->isParticipant($toChatId, $userId)) {
            ApiResponse::error('You cannot forward messages to chats you are not a participant of', 403);
        }
        
        // Forward the message
        $newMessageId = $this->messageModel->forwardMessage($messageId, $toChatId, $userId);
        
        if (!$newMessageId) {
            ApiResponse::error('Failed to forward message', 500);
        }
        
        // Get the forwarded message
        $message = $this->messageModel->findById($newMessageId);
        
        ApiResponse::success($message, 'Message forwarded successfully');
    }
    
    /**
     * Mark messages in a chat as read
     */
    public function markAsRead() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['chat_id'])) {
            ApiResponse::error('Chat ID is required', 400);
        }
        
        $chatId = (int)$data['chat_id'];
        
        // Check if the user is a participant of the chat
        if (!$this->chatModel->isParticipant($chatId, $userId)) {
            ApiResponse::error('You are not a participant of this chat', 403);
        }
        
        // Mark messages as read
        $updated = $this->messageModel->markAsRead($chatId, $userId);
        
        if (!$updated) {
            ApiResponse::error('Failed to mark messages as read', 500);
        }
        
        // If message_id is provided, update last read message
        if (isset($data['message_id'])) {
            $messageId = (int)$data['message_id'];
            $this->chatModel->updateLastReadMessage($chatId, $userId, $messageId);
        }
        
        ApiResponse::success(null, 'Messages marked as read');
    }
    
    /**
     * Get unread counts for all chats
     */
    public function getUnreadCounts() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Get unread counts
        $unreadCounts = $this->messageModel->getTotalUnreadCounts($userId);
        
        ApiResponse::success($unreadCounts);
    }
} 