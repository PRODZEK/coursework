<?php
/**
 * Profile Page View
 */

// Get user data
$user = $currentUser;
?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="md:flex">
            <!-- Profile Sidebar -->
            <div class="md:w-1/3 bg-gray-50 p-6 border-r border-gray-200">
                <div class="text-center mb-6">
                    <!-- Profile Picture -->
                    <div class="relative inline-block">
                        <div id="profile-picture-container" class="w-32 h-32 rounded-full overflow-hidden mx-auto border-4 border-white shadow-md">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img id="profile-image" src="<?php echo APP_URL . '/' . $user['profile_picture']; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-4xl font-bold">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <label for="profile-picture-upload" class="absolute bottom-0 right-0 bg-primary-600 hover:bg-primary-700 text-white rounded-full p-2 cursor-pointer shadow-md" tabindex="0" aria-label="Upload new profile picture" role="button" onkeydown="if(event.key === 'Enter' || event.key === ' ') document.getElementById('profile-picture-upload').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                            </svg>
                        </label>
                        <input type="file" id="profile-picture-upload" class="hidden" accept="image/jpeg, image/png, image/gif">
                    </div>
                    
                    <!-- User Info -->
                    <h2 class="mt-4 text-xl font-bold text-gray-900"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="mt-2 flex justify-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="status-indicator status-<?php echo $user['status']; ?> mr-1"></span>
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Account Stats -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Member since</span>
                        <span class="text-gray-900 font-medium"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Last seen</span>
                        <span class="text-gray-900 font-medium"><?php echo formatDateTime($user['last_seen']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="md:w-2/3 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Account Settings</h3>
                
                <!-- Alert Messages -->
                <div id="profile-error-message" class="mb-4 rounded-md bg-red-50 p-4 hidden">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800" id="profile-error-text"></p>
                        </div>
                    </div>
                </div>
                
                <div id="profile-success-message" class="mb-4 rounded-md bg-green-50 p-4 hidden">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800" id="profile-success-text"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Form -->
                <form id="profile-form" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-gray-100" readonly>
                    </div>
                    
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                        <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" id="save-profile-button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" tabindex="0" aria-label="Save changes">
                            Save Changes
                        </button>
                    </div>
                </form>
                
                <!-- Change Password Section -->
                <div class="mt-10 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Change Password</h3>
                    
                    <div id="password-error-message" class="mb-4 rounded-md bg-red-50 p-4 hidden">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800" id="password-error-text"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="password-success-message" class="mb-4 rounded-md bg-green-50 p-4 hidden">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800" id="password-success-text"></p>
                            </div>
                        </div>
                    </div>
                    
                    <form id="password-form" class="space-y-6">
                        <div>
                            <label for="current-password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" id="current-password" name="current_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label for="new-password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" id="new-password" name="new_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label for="confirm-new-password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" id="confirm-new-password" name="confirm_new_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" id="change-password-button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" tabindex="0" aria-label="Change password">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile picture upload
    const profilePictureUpload = document.getElementById('profile-picture-upload');
    const profileImage = document.getElementById('profile-image');
    const profilePictureContainer = document.getElementById('profile-picture-container');
    
    // Profile form
    const profileForm = document.getElementById('profile-form');
    const saveProfileButton = document.getElementById('save-profile-button');
    const profileErrorMessage = document.getElementById('profile-error-message');
    const profileErrorText = document.getElementById('profile-error-text');
    const profileSuccessMessage = document.getElementById('profile-success-message');
    const profileSuccessText = document.getElementById('profile-success-text');
    
    // Password form
    const passwordForm = document.getElementById('password-form');
    const changePasswordButton = document.getElementById('change-password-button');
    const passwordErrorMessage = document.getElementById('password-error-message');
    const passwordErrorText = document.getElementById('password-error-text');
    const passwordSuccessMessage = document.getElementById('password-success-message');
    const passwordSuccessText = document.getElementById('password-success-text');
    
    // Handle profile picture upload
    profilePictureUpload.addEventListener('change', async function(e) {
        if (e.target.files.length === 0) return;
        
        const file = e.target.files[0];
        
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            profileErrorText.textContent = 'File is too large. Maximum size is 5MB.';
            profileErrorMessage.classList.remove('hidden');
            return;
        }
        
        // Check file type
        if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
            profileErrorText.textContent = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.';
            profileErrorMessage.classList.remove('hidden');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('profile_picture', file);
        
        try {
            // Show loading indicator
            profilePictureContainer.innerHTML = `
                <div class="w-full h-full flex items-center justify-center bg-gray-100">
                    <svg class="animate-spin h-10 w-10 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            `;
            
            // Upload profile picture
            const response = await fetch('<?php echo APP_URL; ?>/api/profile.php?action=upload_profile_picture', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                profileSuccessText.textContent = data.message;
                profileSuccessMessage.classList.remove('hidden');
                profileErrorMessage.classList.add('hidden');
                
                // Update profile picture
                profilePictureContainer.innerHTML = `
                    <img id="profile-image" src="<?php echo APP_URL; ?>/${data.profile_picture}?t=${Date.now()}" alt="<?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
                `;
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    profileSuccessMessage.classList.add('hidden');
                }, 3000);
            } else {
                // Show error message
                profileErrorText.textContent = data.message;
                profileErrorMessage.classList.remove('hidden');
                profileSuccessMessage.classList.add('hidden');
                
                // Restore original profile picture
                if (profileImage) {
                    profilePictureContainer.innerHTML = `
                        <img id="profile-image" src="${profileImage.src}" alt="<?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
                    `;
                } else {
                    profilePictureContainer.innerHTML = `
                        <div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-4xl font-bold">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error uploading profile picture:', error);
            
            // Show error message
            profileErrorText.textContent = 'An error occurred. Please try again.';
            profileErrorMessage.classList.remove('hidden');
            profileSuccessMessage.classList.add('hidden');
            
            // Restore original profile picture
            if (profileImage) {
                profilePictureContainer.innerHTML = `
                    <img id="profile-image" src="${profileImage.src}" alt="<?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
                `;
            } else {
                profilePictureContainer.innerHTML = `
                    <div class="w-full h-full bg-primary-500 flex items-center justify-center text-white text-4xl font-bold">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                `;
            }
        }
    });
    
    // Handle profile form submission
    profileForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Hide messages
        profileErrorMessage.classList.add('hidden');
        profileSuccessMessage.classList.add('hidden');
        
        // Get form data
        const username = document.getElementById('username').value;
        const bio = document.getElementById('bio').value;
        
        // Validate form
        if (username.trim().length < 3) {
            profileErrorText.textContent = 'Username must be at least 3 characters long.';
            profileErrorMessage.classList.remove('hidden');
            return;
        }
        
        // Disable button and show loading state
        saveProfileButton.disabled = true;
        saveProfileButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        `;
        
        try {
            // Update profile
            const response = await fetch('<?php echo APP_URL; ?>/api/profile.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username,
                    bio
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                profileSuccessText.textContent = data.message;
                profileSuccessMessage.classList.remove('hidden');
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    profileSuccessMessage.classList.add('hidden');
                }, 3000);
            } else {
                // Show error message
                profileErrorText.textContent = data.message;
                profileErrorMessage.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            
            // Show error message
            profileErrorText.textContent = 'An error occurred. Please try again.';
            profileErrorMessage.classList.remove('hidden');
        }
        
        // Reset button
        saveProfileButton.disabled = false;
        saveProfileButton.innerHTML = 'Save Changes';
    });
    
    // Handle password form submission
    passwordForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Hide messages
        passwordErrorMessage.classList.add('hidden');
        passwordSuccessMessage.classList.add('hidden');
        
        // Get form data
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmNewPassword = document.getElementById('confirm-new-password').value;
        
        // Validate form
        if (!currentPassword) {
            passwordErrorText.textContent = 'Current password is required.';
            passwordErrorMessage.classList.remove('hidden');
            return;
        }
        
        if (newPassword.length < 8) {
            passwordErrorText.textContent = 'New password must be at least 8 characters long.';
            passwordErrorMessage.classList.remove('hidden');
            return;
        }
        
        if (newPassword !== confirmNewPassword) {
            passwordErrorText.textContent = 'Passwords do not match.';
            passwordErrorMessage.classList.remove('hidden');
            return;
        }
        
        // Disable button and show loading state
        changePasswordButton.disabled = true;
        changePasswordButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Changing...
        `;
        
        try {
            // Change password
            const response = await fetch('<?php echo APP_URL; ?>/api/profile.php?action=change_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                passwordSuccessText.textContent = data.message;
                passwordSuccessMessage.classList.remove('hidden');
                
                // Reset form
                passwordForm.reset();
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    passwordSuccessMessage.classList.add('hidden');
                }, 3000);
            } else {
                // Show error message
                passwordErrorText.textContent = data.message;
                passwordErrorMessage.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            
            // Show error message
            passwordErrorText.textContent = 'An error occurred. Please try again.';
            passwordErrorMessage.classList.remove('hidden');
        }
        
        // Reset button
        changePasswordButton.disabled = false;
        changePasswordButton.innerHTML = 'Change Password';
    });
});
</script> 