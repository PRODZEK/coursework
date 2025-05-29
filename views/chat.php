<?php
/**
 * Chat Page View
 */
?>
<div class="chat-container flex h-full">
    <!-- Left Sidebar - Chat List -->
    <div id="chat-list-container" class="w-1/3 border-r border-gray-200 bg-white h-full flex flex-col">
        <!-- Search and New Chat -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Chats</h2>
                <button id="new-chat-button" class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-2 focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Create new chat" title="Create new chat">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                </button>
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
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4">
                <!-- Messages will be loaded here via JavaScript -->
                <div class="flex justify-center">
                    <div class="inline-block px-4 py-2 bg-gray-200 rounded-lg text-gray-700">
                        Loading messages...
                    </div>
                </div>
            </div>
            
            <!-- Message Input -->
            <div id="message-input-container" class="p-4 bg-white border-t border-gray-200">
                <form id="message-form" class="flex items-center">
                    <input type="text" id="message-text" class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" id="send-message-button" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-primary-500" tabindex="0" aria-label="Send message">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </form>
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