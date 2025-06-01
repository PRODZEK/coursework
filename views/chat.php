<?php
/**
 * Chat Page View
 */
?>
<style>
    /* Global styles for the chat page */
    html, body {
        height: 100vh;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }
    
    body {
        display: flex;
        flex-direction: column;
    }
    
    .container {
        flex: 1;
        display: flex;
        height: 100vh;
        max-height: 100vh;
        overflow: hidden;
        padding: 0 !important;
        margin: 0 !important;
        width: 100vw;
    }
    
    main {
        flex: 1;
        height: 100vh;
        max-height: 100vh;
        overflow: hidden;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .chat-container {
        height: 100vh;
        max-height: 100vh;
        width: 100%;
        display: flex;
        flex-direction: row;
        overflow: hidden;
    }
    
    #chat-list-container {
        height: 100vh;
        max-height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    #chats-list {
        overflow-y: auto;
        flex: 1;
        max-height: calc(100vh - 116px);
    }
    
    #chat-content {
        height: 100vh;
        max-height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    #chat-messages {
        flex: 1;
        overflow-y: auto;
        max-height: calc(100vh - 132px);
        padding: 1rem;
        height: calc(100vh - 196px);
    }
    
    #message-input-container {
        position: sticky;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: white;
        border-top: 1px solid #e5e7eb;
        padding: 1rem;
        z-index: 10;
        height: 84px;
        display: flex;
        align-items: center;
    }
    
    #message-form {
        width: 100%;
        display: flex;
    }
    
    #message-text {
        flex: 1;
        height: 48px;
    }
    
    /* Message styles */
    .message-incoming {
        background-color: #f3f4f6;
        border-radius: 1rem;
        padding: 0.75rem 1rem;
        max-width: 100%;
        word-break: break-word;
        display: flex;
        flex-direction: column;
    }
    
    .message-outgoing {
        background-color: #3b82f6;
        color: white;
        border-radius: 1rem;
        padding: 0.75rem 1rem;
        max-width: 100%;
        word-break: break-word;
        display: flex;
        flex-direction: column;
    }
    
    .message-content {
        margin-bottom: 0.25rem;
        flex: 1;
    }
    
    .message-text {
        margin: 0;
        white-space: pre-wrap;
        word-wrap: break-word;
        overflow-wrap: break-word;
        font-size: 1rem;
    }
    
    .message-time {
        font-size: 0.75rem;
        text-align: right;
        opacity: 0.8;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }
    
    /* Fixed chat header */
    #chat-header {
        height: 64px;
        flex-shrink: 0;
    }
    
    /* Emoji picker styles */
    .emoji-item {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        cursor: pointer;
        text-align: center;
        border-radius: 0.25rem;
        transition: background-color 0.15s ease;
    }
    
    .emoji-item:hover {
        background-color: #f3f4f6;
    }
    
    #emoji-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0.25rem;
        padding: 0.5rem;
    }
    
    /* Fix for mobile view */
    @media (max-width: 640px) {
        .chat-container {
            flex-direction: column;
            height: 100%;
        }
        
        #chat-list-container {
            width: 100%;
            height: 40vh;
            min-height: 40vh;
            max-height: 40vh;
        }
        
        #chat-content {
            width: 100%;
            height: 60vh;
            min-height: 60vh;
        }
        
        #chat-messages {
            height: calc(100% - 128px);
            max-height: calc(100% - 128px);
        }
        
        .message-outgoing,
        .message-incoming {
            max-width: 85vw;
        }
    }
</style>

<div class="chat-container flex h-full">
    <!-- Left Sidebar - Chat List -->
    <div id="chat-list-container" class="w-1/3 border-r border-gray-200 bg-white h-full flex flex-col">
        <!-- Search and New Chat -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Chats</h2>
                <div class="flex items-center">
                    <button id="settings-button" class="text-gray-500 hover:text-primary-600 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 mr-2" tabindex="0" aria-label="Settings" title="Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button id="new-chat-button" class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-2 focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Create new chat" title="Create new chat">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="relative">
                <input type="text" id="chat-search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Search chats...">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Chat List -->
        <div id="chats-list" class="overflow-y-auto flex-1">
            <!-- Chats will be loaded here via JavaScript -->
            <div class="flex items-center justify-center h-full text-gray-500">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <p>Loading chats...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Content - Chat Messages -->
    <div id="chat-content" class="w-2/3 h-full flex flex-col bg-gray-50">
        <!-- Empty State / Welcome -->
        <div id="empty-chat-state" class="flex items-center justify-center h-full">
            <div class="text-center max-w-md p-6">
                <div class="rounded-full bg-primary-100 p-4 mx-auto w-20 h-20 flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Welcome to Chat App</h3>
                <p class="text-gray-600 mb-4">Select a chat from the list or start a new conversation.</p>
                <button id="start-new-chat-button" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Start a new chat">
                    Start a new chat
                </button>
                <button id="reload-chats-button" class="ml-2 border border-primary-600 text-primary-600 hover:bg-primary-50 px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Reload chats" onclick="ChatApp.loadChats()">
                    Reload chats
                </button>
            </div>
        </div>
        
        <!-- Active Chat (Hidden by default, shown when a chat is selected) -->
        <div id="active-chat" class="h-full flex flex-col hidden">
            <!-- Chat Header -->
            <div id="chat-header" class="px-4 py-3 bg-white border-b border-gray-200 flex items-center">
                <div id="chat-avatar" class="w-10 h-10 rounded-full bg-gray-300 flex-shrink-0 mr-3">
                    <!-- Avatar will be set via JavaScript -->
                </div>
                <div class="flex-1">
                    <h3 id="chat-name" class="font-bold text-gray-800">Chat Name</h3>
                    <p id="chat-status" class="text-sm text-gray-500">Status</p>
                </div>
                <div>
                    <button id="chat-info-button" class="text-gray-500 hover:text-primary-600 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Chat information">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4 scroll-smooth">
                <!-- Messages will be loaded here via JavaScript -->
                <div class="flex justify-center">
                    <div class="inline-block px-4 py-2 bg-gray-200 rounded-lg text-gray-700">
                        Loading messages...
                    </div>
                </div>
            </div>
            
            <!-- Message Input -->
            <div id="message-input-container" class="p-3 bg-gray-50 border-t border-gray-200 sticky bottom-0 left-0 right-0 z-10 shadow-md">
                <form id="message-form" class="flex items-center gap-2 bg-white border border-gray-300 rounded-xl px-2 py-1">
                    <!-- Left Icons Group -->
                    <div class="flex items-center">
                        <!-- Attachment -->
                        <div class="flex-shrink-0">
                        <button type="button" id="attachment-button" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 focus:outline-none" aria-label="Attach files">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                        </button>
                        <input type="file" id="file-input" class="hidden" multiple accept="image/*,video/*,audio/*,application/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                    </div>
                        <!-- Emoji Button & Picker -->
                        <div class="flex-shrink-0 relative">
                        <button type="button" id="emoji-button" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 focus:outline-none" aria-label="Insert emoji">
                                <!-- Standard Smiley Face Emoji Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-2.5-6.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm5 0c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm-.42-3.03c-.37-.37-.88-.58-1.41-.58h-2.34c-.53 0-1.04.21-1.41.58-.37.37-.58.88-.58 1.41 0 .2.03.39.08.58.16.67.76 1.15 1.45 1.28.15.03.3.04.45.04s.3-.01.45-.04c.7-.13 1.29-.61 1.45-1.28.05-.19.08-.38.08-.58 0-.53-.21-1.04-.58-1.41z"/>
                                </svg>
                            </button>
                            <!-- Emoji Picker Container -->
                            <div id="emoji-picker-container" class="hidden fixed z-50">
                                <div id="emoji-picker" class="bg-white border border-gray-200 rounded-lg shadow-xl w-80 h-72 overflow-y-auto transform transition-all duration-300 ease-in-out scale-95 opacity-0">
                                    <div class="p-2 border-b border-gray-200 flex justify-between items-center">
                                        <h3 class="text-sm font-medium text-gray-700">Select Emoji</h3>
                                        <button type="button" id="close-emoji-picker" class="p-1 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-600 focus:outline-none" aria-label="Close emoji picker">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                                    <div id="emoji-grid" class="p-2 grid grid-cols-5 gap-1">
                                        <!-- Emojis will be loaded here by JavaScript -->
                                    </div>
                                </div>
                                <!-- Arrow pointing to the button -->
                                <div class="absolute left-1/2 -translate-x-1/2 bottom-[-8px] w-4 h-4 bg-white border-b border-r border-gray-200 transform rotate-45"></div>
                            </div>
                        </div>
                        <!-- Mic Button -->
                        <div class="flex-shrink-0">
                        <button type="button" id="audio-record-button" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 focus:outline-none" aria-label="Record audio message">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </button>
                        </div>
                    </div>
                    <div id="selected-attachments" class="hidden flex-wrap items-center mb-2 gap-2"></div>
                    <!-- Textarea -->
                    <div class="relative flex-1">
                        <textarea id="message-text" rows="1" class="w-full p-2.5 border-none focus:outline-none focus:ring-0 resize-none overflow-y-auto bg-transparent placeholder-gray-500 text-sm" placeholder="Type your message..."></textarea>
                    </div>
                    <!-- Send Button -->
                    <div class="flex-shrink-0">
                        <button type="submit" id="send-button" class="p-2.5 rounded-full bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500" aria-label="Send message">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                </form>
                <!-- Emoji Picker Backdrop -->
                <div id="emoji-backdrop" class="hidden fixed inset-0 bg-black bg-opacity-0 z-10 transition-opacity duration-300 ease-in-out"></div>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal (Hidden by default) -->
<div id="new-chat-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">New Chat</h3>
                <button id="close-new-chat-modal" class="text-gray-500 hover:text-gray-700 focus:outline-none" tabindex="0" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-4">
            <div class="mb-4">
                <div class="relative">
                    <input type="text" id="user-search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Search users...">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
            <div id="user-search-results" class="max-h-60 overflow-y-auto mb-4">
                <!-- User search results will be displayed here -->
                <div class="text-center text-gray-500 py-4">
                    Search for users to start a chat
                </div>
            </div>
            
            <div id="create-group-chat" class="mb-4 pt-4 border-t border-gray-200">
                <h4 class="font-bold text-gray-800 mb-2">Create Group Chat</h4>
                <div class="mb-4">
                    <label for="group-chat-name" class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
                    <input type="text" id="group-chat-name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Enter group name">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add Members</label>
                    <div id="group-members-list" class="max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-2">
                        <!-- Selected members will be displayed here -->
                        <div class="text-center text-gray-500 py-2">
                            No members selected
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button id="create-direct-chat-button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2 focus:outline-none focus:ring-2 focus:ring-gray-500" tabindex="0" aria-label="Create direct chat" disabled>
                Create Direct Chat
            </button>
            <button id="create-group-chat-button" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Create group chat" disabled>
                Create Group Chat
            </button>
        </div>
    </div>
</div>

<!-- Settings Modal (Hidden by default) -->
<div id="settings-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Settings</h3>
                <button id="close-settings-modal" class="text-gray-500 hover:text-gray-700 focus:outline-none" tabindex="0" aria-label="Close settings">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-4">
            <h4 class="font-bold text-gray-800 mb-4">Notification Settings</h4>
            
            <div id="settings-container">
                <!-- Desktop Notifications -->
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-gray-700">Desktop Notifications</h5>
                            <p class="text-sm text-gray-500">Get notified when you receive new messages</p>
                        </div>
                        <div class="relative inline-block w-12 align-middle select-none">
                            <input type="checkbox" id="desktop-notifications-toggle" class="sr-only">
                            <label for="desktop-notifications-toggle" class="block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer toggle-label">
                                <span class="block h-6 w-6 rounded-full bg-white shadow transform transition-transform duration-200 ease-in-out toggle-dot"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Sound Notifications -->
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-gray-700">Sound Notifications</h5>
                            <p class="text-sm text-gray-500">Play sound when new messages arrive</p>
                        </div>
                        <div class="relative inline-block w-12 align-middle select-none">
                            <input type="checkbox" id="sound-toggle" class="sr-only">
                            <label for="sound-toggle" class="block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer toggle-label">
                                <span class="block h-6 w-6 rounded-full bg-white shadow transform transition-transform duration-200 ease-in-out toggle-dot"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Do Not Disturb -->
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-gray-700">Do Not Disturb</h5>
                            <p class="text-sm text-gray-500">Disable all notifications temporarily</p>
                        </div>
                        <div class="relative inline-block w-12 align-middle select-none">
                            <input type="checkbox" id="do-not-disturb-toggle" class="sr-only">
                            <label for="do-not-disturb-toggle" class="block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer toggle-label">
                                <span class="block h-6 w-6 rounded-full bg-white shadow transform transition-transform duration-200 ease-in-out toggle-dot"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button id="save-settings-button" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Save settings">
                Save Settings
            </button>
        </div>
    </div>
</div>

<!-- Delete Chat Confirmation Modal -->
<div id="delete-chat-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" id="delete-chat-backdrop"></div>
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 z-10 relative">
        <div class="text-center">
            <svg class="h-12 w-12 text-red-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            
            <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Chat</h3>
            <p class="text-gray-600 mb-6" id="delete-chat-message">Are you sure you want to delete this chat? This action cannot be undone.</p>
            
            <div class="flex justify-center space-x-4">
                <button id="cancel-delete-chat" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
                <button id="confirm-delete-chat" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Audio recording UI -->
<div id="audio-recording-ui" class="hidden fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 shadow-lg z-50">
    <div class="flex items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center animate-pulse mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium">Recording audio message</div>
                <div id="recording-time" class="text-xs text-gray-500">00:00</div>
            </div>
        </div>
        <div class="flex items-center">
            <button id="cancel-recording" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md mr-2">Cancel</button>
            <button id="stop-recording" class="px-4 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700 rounded-md">Send</button>
        </div>
    </div>
</div>

<!-- Add the Chat App Script -->
<script src="<?php echo APP_URL; ?>/assets/js/chat.js"></script>

<!-- Initialize the app when the DOM is loaded -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set current user info from PHP session
        ChatApp.currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        ChatApp.currentUserName = <?php echo isset($_SESSION['username']) ? "'" . htmlspecialchars($_SESSION['username']) . "'" : 'null'; ?>;
        
        // Debug output
        console.log('Current user ID:', ChatApp.currentUserId);
        console.log('Current username:', ChatApp.currentUserName);
        
        // Add error handler for AJAX requests
        window.addEventListener('error', function(e) {
            console.error('Global error caught:', e.message);
            
            // Create error popup for fatal errors
            if (e.message.includes('Unexpected token') || e.message.includes('JSON')) {
                const errorPopup = document.createElement('div');
                errorPopup.className = 'fixed bottom-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
                errorPopup.innerHTML = `
                    <div class="flex justify-between">
                        <strong class="font-bold">Error</strong>
                        <button onclick="this.parentNode.parentNode.remove()" class="ml-4 font-bold">&times;</button>
                    </div>
                    <p class="text-sm mt-1">There was a problem with the server response. Try refreshing the page.</p>
                    <button onclick="location.reload()" class="mt-2 bg-red-600 text-white px-3 py-1 rounded text-xs">Refresh</button>
                `;
                document.body.appendChild(errorPopup);
            }
        });
        
        // Initialize the app
        try {
            ChatApp.init();
        } catch (error) {
            console.error('Error initializing chat app:', error);
            
            // Show error message in UI
            const chatsList = document.getElementById('chats-list');
            if (chatsList) {
                chatsList.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-gray-500 p-4">
                        <div class="text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-red-500 font-medium mb-2">Error initializing chat</p>
                            <p class="text-gray-600 text-sm mb-4">${error.message}</p>
                            <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" onclick="location.reload()">
                                Reload page
                            </button>
                        </div>
                    </div>
                `;
            }
        }
    });
</script>

<style>
    /* Toggle button styling */
    .toggle-label {
        transition: background-color 0.2s;
    }
    
    input:checked + .toggle-label {
        background-color: #0099FF;
    }
    
    input:checked + .toggle-label .toggle-dot {
        transform: translateX(100%);
    }
    
    /* Status indicator styling */
    .status-indicator {
        display: inline-block;
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid white;
    }
    
    /* Chat UI elements */
    .message-outgoing {
        background-color: #0099FF;
        color: white;
        border-radius: 18px;
        border-bottom-right-radius: 4px;
        padding: 10px 14px;
        word-break: break-word;
        max-width: 100%;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        display: inline-block;
    }
    
    .message-incoming {
        background-color: #f0f0f0;
        color: #333;
        border-radius: 18px;
        border-bottom-left-radius: 4px;
        padding: 10px 14px;
        word-break: break-word;
        max-width: 100%;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        display: inline-block;
    }
    
    /* Ensure content is displayed properly */
    .message-text {
        margin: 0;
        white-space: pre-wrap;
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    /* Fix safari display issues */
    @supports (-webkit-touch-callout: none) {
        .message-outgoing, 
        .message-incoming {
            display: inline-block;
        }
    }
    
    .chat-item.active {
        background-color: #e6f7ff;
    }
    
    /* Improved scrollbar */
    #chat-messages::-webkit-scrollbar {
        width: 6px;
    }
    
    #chat-messages::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    #chat-messages::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }
    
    #chat-messages::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
    
    /* Main chat layout fixes */
    .chat-container {
        display: flex;
        height: 100%;
        overflow: hidden;
    }
    
    #chat-list-container {
        width: 33.333%;
        border-right: 1px solid #e5e7eb;
        background-color: white;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    #chats-list {
        overflow-y: auto;
        flex: 1;
    }
    
    #chat-content {
        width: 66.666%;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        background-color: #f9fafb;
    }
    
    #chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        scroll-behavior: smooth;
        /* Prevent stretching beyond container */
        max-height: calc(100% - 128px);
        height: calc(100% - 128px);
    }
    
    /* Fixed message input at bottom */
    #message-input-container {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: white;
        border-top: 1px solid #e5e7eb;
        padding: 1rem;
        z-index: 10;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        height: 84px; /* Fixed height for input area */
    }
    
    /* Ensure the active chat container positions elements correctly */
    #active-chat {
        position: relative;
        height: 100%;
        overflow: hidden;
    }
    
    /* Fix chat header */
    #chat-header {
        height: 64px;
        flex-shrink: 0;
    }
    
    /* Fix for mobile view */
    @media (max-width: 640px) {
        .chat-container {
            flex-direction: column;
            height: 100%;
        }
        
        #chat-list-container {
            width: 100%;
            height: 40vh;
            min-height: 40vh;
            max-height: 40vh;
        }
        
        #chat-content {
            width: 100%;
            height: 60vh;
            min-height: 60vh;
        }
        
        #chat-messages {
            height: calc(100% - 128px);
            max-height: calc(100% - 128px);
        }
        
        .message-outgoing,
        .message-incoming {
            max-width: 85vw;
        }
    }
</style>
</div>
</body>
</html> 