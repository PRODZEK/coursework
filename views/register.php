<?php
/**
 * Register Page View
 */
?>
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create a new account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="<?php echo APP_URL; ?>/index.php?page=login" class="font-medium text-primary-600 hover:text-primary-500" tabindex="0">
                    sign in to your existing account
                </a>
            </p>
        </div>
        <div id="error-message" class="rounded-md bg-red-50 p-4 hidden">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800" id="error-text"></p>
                </div>
            </div>
        </div>
        <div id="success-message" class="rounded-md bg-green-50 p-4 hidden">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800" id="success-text"></p>
                </div>
            </div>
        </div>
        <form id="register-form" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" placeholder="Username">
                </div>
                <div>
                    <label for="email" class="sr-only">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" placeholder="Email address">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" placeholder="Password (min. 8 characters)">
                </div>
                <div>
                    <label for="confirm-password" class="sr-only">Confirm Password</label>
                    <input id="confirm-password" name="confirm-password" type="password" autocomplete="new-password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" placeholder="Confirm Password">
                </div>
            </div>

            <div>
                <button type="submit" id="register-button" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" tabindex="0" aria-label="Create your account">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-primary-500 group-hover:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('register-form');
        const registerButton = document.getElementById('register-button');
        const errorMessage = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const successMessage = document.getElementById('success-message');
        const successText = document.getElementById('success-text');
        
        // Validate form
        const validateForm = () => {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (username.trim().length < 3) {
                return { valid: false, message: 'Username must be at least 3 characters' };
            }
            
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                return { valid: false, message: 'Invalid email address' };
            }
            
            if (password.length < 8) {
                return { valid: false, message: 'Password must be at least 8 characters' };
            }
            
            if (password !== confirmPassword) {
                return { valid: false, message: 'Passwords do not match' };
            }
            
            return { valid: true };
        };
        
        // Handle form submission
        const handleSubmit = async (e) => {
            e.preventDefault();
            
            // Hide messages
            errorMessage.classList.add('hidden');
            successMessage.classList.add('hidden');
            
            // Validate form
            const validation = validateForm();
            if (!validation.valid) {
                errorText.textContent = validation.message;
                errorMessage.classList.remove('hidden');
                return;
            }
            
            // Disable button and show loading state
            registerButton.disabled = true;
            registerButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating account...
            `;
            
            // Get form data
            const formData = new FormData(registerForm);
            const username = formData.get('username');
            const email = formData.get('email');
            const password = formData.get('password');
            
            try {
                // Send registration request
                const response = await fetch('<?php echo APP_URL; ?>/api/auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        email,
                        password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    successText.textContent = data.message;
                    successMessage.classList.remove('hidden');
                    
                    // Reset form
                    registerForm.reset();
                    
                    // Redirect to login page after a short delay
                    setTimeout(() => {
                        window.location.href = '<?php echo APP_URL; ?>/index.php?page=login';
                    }, 2000);
                } else {
                    // Show error message
                    errorText.textContent = data.message || 'An error occurred. Please try again.';
                    errorMessage.classList.remove('hidden');
                    
                    // Reset button
                    registerButton.disabled = false;
                    registerButton.innerHTML = `
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-primary-500 group-hover:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        Create Account
                    `;
                }
            } catch (error) {
                // Show error message
                errorText.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.remove('hidden');
                
                // Reset button
                registerButton.disabled = false;
                registerButton.innerHTML = `
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-primary-500 group-hover:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Create Account
                `;
                
                console.error('Registration error:', error);
            }
        };
        
        // Add event listeners
        registerForm.addEventListener('submit', handleSubmit);
        
        // Handle keyboard events
        registerButton.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                registerForm.dispatchEvent(new Event('submit'));
            }
        });
    });
</script> 