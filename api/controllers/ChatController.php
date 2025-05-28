<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../ApiResponse.php';

/**
 * Chat Controller
 * Handles operations related to chats
 */
class ChatController {
    private $chatModel;
    private $userModel;
    
    public function __construct() {
        $this->chatModel = new Chat();
        $this->userModel = new User();
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
     * Get all chats for the authenticated user
     */
    public function getChats() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        $chats = $this->chatModel->getUserChats($userId);
        ApiResponse::success($chats);
    }
    
    /**
     * Get a specific chat by ID
     */
    public function getChat() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Get chat ID from URL
        if (!isset($_GET['id'])) {
            ApiResponse::error('Chat ID is required', 400);
        }
        
        $chatId = (int)$_GET['id'];
        
        // Check if the user is a participant
        if (!$this->chatModel->isParticipant($chatId, $userId)) {
            ApiResponse::error('You are not a participant of this chat', 403);
        }
        
        // Get chat data
        $chat = $this->chatModel->findById($chatId);
        
        if (!$chat) {
            ApiResponse::error('Chat not found', 404);
        }
        
        ApiResponse::success($chat);
    }
    
    /**
     * Create a new chat (group or channel)
     */
    public function createChat() {
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
        if (!isset($data['chat_type']) || !in_array($data['chat_type'], ['group', 'channel'])) {
            ApiResponse::error('Valid chat type is required (group or channel)', 400);
        }
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            ApiResponse::error('Title is required', 400);
        }
        
        // Create participants array
        $participants = [$userId];
        if (isset($data['participants']) && is_array($data['participants'])) {
            // Validate each participant
            foreach ($data['participants'] as $participantId) {
                if (!$this->userModel->findById($participantId)) {
                    ApiResponse::error("User ID $participantId not found", 400);
                }
                
                // Add to participants array if not already there
                if (!in_array($participantId, $participants)) {
                    $participants[] = $participantId;
                }
            }
        }
        
        // Create the chat
        $chat = $this->chatModel->create(
            $data['chat_type'],
            $userId,
            $data['title'],
            $data['description'] ?? null,
            $participants
        );
        
        if (!$chat) {
            ApiResponse::error('Failed to create chat', 500);
        }
        
        ApiResponse::success($chat, 'Chat created successfully', 201);
    }
    
    /**
     * Start or get a private chat with another user
     */
    public function getPrivateChat() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Get target user ID from URL
        if (!isset($_GET['user_id'])) {
            ApiResponse::error('Target user ID is required', 400);
        }
        
        $targetUserId = (int)$_GET['user_id'];
        
        // Check if target user exists
        if (!$this->userModel->findById($targetUserId)) {
            ApiResponse::error('Target user not found', 404);
        }
        
        // Check if the user is trying to chat with themselves
        if ($userId === $targetUserId) {
            ApiResponse::error('Cannot create a private chat with yourself', 400);
        }
        
        // Find or create a private chat
        $chat = $this->chatModel->findOrCreatePrivateChat($userId, $targetUserId);
        
        if (!$chat) {
            ApiResponse::error('Failed to create private chat', 500);
        }
        
        ApiResponse::success($chat);
    }
    
    /**
     * Update a chat's information
     */
    public function updateChat() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a PUT or PATCH request
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get chat ID from URL
        if (!isset($_GET['id'])) {
            ApiResponse::error('Chat ID is required', 400);
        }
        
        $chatId = (int)$_GET['id'];
        
        // Get chat data
        $chat = $this->chatModel->findById($chatId);
        
        if (!$chat) {
            ApiResponse::error('Chat not found', 404);
        }
        
        // Check if the user is an admin or owner of the chat
        $isAuthorized = false;
        foreach ($chat['participants'] as $participant) {
            if ($participant['user_id'] == $userId && in_array($participant['role'], ['admin', 'owner'])) {
                $isAuthorized = true;
                break;
            }
        }
        
        if (!$isAuthorized) {
            ApiResponse::error('You are not authorized to update this chat', 403);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            ApiResponse::error('Invalid request data', 400);
        }
        
        // Update the chat
        $updated = $this->chatModel->update($chatId, [
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'photo' => $data['photo'] ?? null
        ]);
        
        if (!$updated) {
            ApiResponse::error('Failed to update chat', 500);
        }
        
        // Get updated chat data
        $updatedChat = $this->chatModel->findById($chatId);
        
        ApiResponse::success($updatedChat, 'Chat updated successfully');
    }
    
    /**
     * Add a participant to a chat
     */
    public function addParticipant() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get chat ID from URL
        if (!isset($_GET['id'])) {
            ApiResponse::error('Chat ID is required', 400);
        }
        
        $chatId = (int)$_GET['id'];
        
        // Get chat data
        $chat = $this->chatModel->findById($chatId);
        
        if (!$chat) {
            ApiResponse::error('Chat not found', 404);
        }
        
        // Check if the user is an admin or owner of the chat
        $isAuthorized = false;
        foreach ($chat['participants'] as $participant) {
            if ($participant['user_id'] == $userId && in_array($participant['role'], ['admin', 'owner'])) {
                $isAuthorized = true;
                break;
            }
        }
        
        if (!$isAuthorized) {
            ApiResponse::error('You are not authorized to add participants', 403);
        }
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['user_id'])) {
            ApiResponse::error('User ID is required', 400);
        }
        
        $newParticipantId = (int)$data['user_id'];
        
        // Check if user exists
        if (!$this->userModel->findById($newParticipantId)) {
            ApiResponse::error('User not found', 404);
        }
        
        // Check if user is already a participant
        if ($this->chatModel->isParticipant($chatId, $newParticipantId)) {
            ApiResponse::error('User is already a participant', 400);
        }
        
        // Add participant
        $role = isset($data['role']) && in_array($data['role'], ['member', 'admin']) ? $data['role'] : 'member';
        
        $added = $this->chatModel->addParticipant($chatId, $newParticipantId, $role);
        
        if (!$added) {
            ApiResponse::error('Failed to add participant', 500);
        }
        
        // Get updated chat data
        $updatedChat = $this->chatModel->findById($chatId);
        
        ApiResponse::success($updatedChat, 'Participant added successfully');
    }
    
    /**
     * Remove a participant from a chat
     */
    public function removeParticipant() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a DELETE request
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get chat ID and participant ID from URL
        if (!isset($_GET['id']) || !isset($_GET['user_id'])) {
            ApiResponse::error('Chat ID and user ID are required', 400);
        }
        
        $chatId = (int)$_GET['id'];
        $participantId = (int)$_GET['user_id'];
        
        // Get chat data
        $chat = $this->chatModel->findById($chatId);
        
        if (!$chat) {
            ApiResponse::error('Chat not found', 404);
        }
        
        // Check if the user is an admin or owner of the chat
        $isAuthorized = false;
        $participantToRemoveRole = '';
        $currentUserRole = '';
        
        foreach ($chat['participants'] as $participant) {
            if ($participant['user_id'] == $userId) {
                $currentUserRole = $participant['role'];
            }
            if ($participant['user_id'] == $participantId) {
                $participantToRemoveRole = $participant['role'];
            }
        }
        
        // Users can remove themselves, admins can remove members, owners can remove anyone
        if ($userId == $participantId || 
            ($currentUserRole === 'admin' && $participantToRemoveRole !== 'owner') ||
            ($currentUserRole === 'owner')) {
            $isAuthorized = true;
        }
        
        if (!$isAuthorized) {
            ApiResponse::error('You are not authorized to remove this participant', 403);
        }
        
        // Cannot remove the owner
        if ($participantToRemoveRole === 'owner' && $userId != $participantId) {
            ApiResponse::error('Cannot remove the owner of the chat', 403);
        }
        
        // Remove participant
        $removed = $this->chatModel->removeParticipant($chatId, $participantId);
        
        if (!$removed) {
            ApiResponse::error('Failed to remove participant', 500);
        }
        
        // If the user removed themselves, no need to return the updated chat
        if ($userId == $participantId) {
            ApiResponse::success(null, 'You have left the chat');
        } else {
            // Get updated chat data
            $updatedChat = $this->chatModel->findById($chatId);
            ApiResponse::success($updatedChat, 'Participant removed successfully');
        }
    }
    
    /**
     * Update participant role
     */
    public function updateParticipantRole() {
        $userId = $this->getAuthUserId();
        if (!$userId) {
            ApiResponse::error('Not authenticated', 401);
        }
        
        // Check if it's a PATCH request
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            ApiResponse::error('Method not allowed', 405);
        }
        
        // Get chat ID and participant ID from URL
        if (!isset($_GET['id']) || !isset($_GET['user_id'])) {
            ApiResponse::error('Chat ID and user ID are required', 400);
        }
        
        $chatId = (int)$_GET['id'];
        $participantId = (int)$_GET['user_id'];
        
        // Get and validate input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['role']) || !in_array($data['role'], ['member', 'admin'])) {
            ApiResponse::error('Valid role is required (member or admin)', 400);
        }
        
        // Get chat data
        $chat = $this->chatModel->findById($chatId);
        
        if (!$chat) {
            ApiResponse::error('Chat not found', 404);
        }
        
        // Check if the user is the owner of the chat
        $isOwner = false;
        foreach ($chat['participants'] as $participant) {
            if ($participant['user_id'] == $userId && $participant['role'] === 'owner') {
                $isOwner = true;
                break;
            }
        }
        
        if (!$isOwner) {
            ApiResponse::error('Only the owner can change participant roles', 403);
        }
        
        // Check if the participant exists in the chat
        if (!$this->chatModel->isParticipant($chatId, $participantId)) {
            ApiResponse::error('User is not a participant of this chat', 404);
        }
        
        // Update participant role
        $updated = $this->chatModel->addParticipant($chatId, $participantId, $data['role']);
        
        if (!$updated) {
            ApiResponse::error('Failed to update participant role', 500);
        }
        
        // Get updated chat data
        $updatedChat = $this->chatModel->findById($chatId);
        
        ApiResponse::success($updatedChat, 'Participant role updated successfully');
    }
} 