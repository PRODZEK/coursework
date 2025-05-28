/**
 * Authentication Module
 * Handles user authentication and registration
 */

// DOM elements
let loginForm;
let registerForm;
let authContainer;
let formError;

/**
 * Initialize the authentication module
 */
const initAuth = () => {
    authContainer = document.getElementById('auth-container');
    
    // Check the current path to show the appropriate form
    const path = window.location.pathname;
    if (path === '/login') {
        showLoginForm();
    } else if (path === '/register') {
        showRegisterForm();
    } else {
        // Default to login
        showLoginForm();
    }
    
    // Listen for route changes
    window.addEventListener('popstate', handleRouteChange);
};

/**
 * Show the login form
 */
const showLoginForm = () => {
    // Clear the auth container and show the login form
    if (authContainer) {
        const template = document.getElementById('login-template');
        if (template) {
            authContainer.innerHTML = '';
            authContainer.appendChild(template.content.cloneNode(true));
            
            // Get the form and add event listeners
            loginForm = document.getElementById('login-form');
            formError = document.querySelector('.form-error');
            
            if (loginForm) {
                loginForm.addEventListener('submit', handleLogin);
                
                // Add toggle password visibility
                const toggleBtn = loginForm.querySelector('.toggle-password-btn');
                const passwordInput = loginForm.querySelector('input[type="password"]');
                
                if (toggleBtn && passwordInput) {
                    toggleBtn.addEventListener('click', () => togglePasswordVisibility(toggleBtn, passwordInput));
                    toggleBtn.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            togglePasswordVisibility(toggleBtn, passwordInput);
                        }
                    });
                }
            }
            
            // Add event listeners to switch between login and register forms
            const switchLinks = document.querySelectorAll('.switch-auth-link');
            switchLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = link.getAttribute('href');
                    history.pushState({}, '', href);
                    handleRouteChange();
                });
            });
        }
    }
    
    // Update the document title
    document.title = 'Sign In - Telegram Clone';
};

/**
 * Show the registration form
 */
const showRegisterForm = () => {
    // Clear the auth container and show the register form
    if (authContainer) {
        const template = document.getElementById('register-template');
        if (template) {
            authContainer.innerHTML = '';
            authContainer.appendChild(template.content.cloneNode(true));
            
            // Get the form and add event listeners
            registerForm = document.getElementById('register-form');
            formError = document.querySelector('.form-error');
            
            if (registerForm) {
                registerForm.addEventListener('submit', handleRegister);
                
                // Add toggle password visibility
                const toggleBtn = registerForm.querySelector('.toggle-password-btn');
                const passwordInput = registerForm.querySelector('input[type="password"]');
                
                if (toggleBtn && passwordInput) {
                    toggleBtn.addEventListener('click', () => togglePasswordVisibility(toggleBtn, passwordInput));
                    toggleBtn.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            togglePasswordVisibility(toggleBtn, passwordInput);
                        }
                    });
                }
            }
            
            // Add event listeners to switch between login and register forms
            const switchLinks = document.querySelectorAll('.switch-auth-link');
            switchLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = link.getAttribute('href');
                    history.pushState({}, '', href);
                    handleRouteChange();
                });
            });
        }
    }
    
    // Update the document title
    document.title = 'Sign Up - Telegram Clone';
};

/**
 * Handle login form submission
 * 
 * @param {Event} e - Form submit event
 */
const handleLogin = async (e) => {
    e.preventDefault();
    
    // Clear any previous errors
    if (formError) {
        formError.textContent = '';
    }
    
    // Get form data
    const login = loginForm.elements['login'].value;
    const password = loginForm.elements['password'].value;
    
    // Validate form data
    if (!login || !password) {
        if (formError) {
            formError.textContent = 'Please enter both username/email and password';
        }
        return;
    }
    
    // Disable the form and show loading
    toggleFormLoading(loginForm, true);
    
    try {
        // Send login request
        const response = await authApi.login(login, password);
        
        if (response.status === 'success') {
            // Redirect to main app
            window.location.href = '/';
        } else {
            // Show error message
            if (formError) {
                formError.textContent = response.message || 'Login failed';
            }
            toggleFormLoading(loginForm, false);
        }
    } catch (error) {
        // Show error message
        if (formError) {
            formError.textContent = error.message || 'Login failed. Please check your credentials.';
        }
        toggleFormLoading(loginForm, false);
    }
};

/**
 * Handle register form submission
 * 
 * @param {Event} e - Form submit event
 */
const handleRegister = async (e) => {
    e.preventDefault();
    
    // Clear any previous errors
    if (formError) {
        formError.textContent = '';
    }
    
    // Get form data
    const userData = {
        full_name: registerForm.elements['full_name'].value,
        username: registerForm.elements['username'].value,
        email: registerForm.elements['email'].value,
        password: registerForm.elements['password'].value
    };
    
    // Validate form data
    if (!userData.full_name || !userData.username || !userData.email || !userData.password) {
        if (formError) {
            formError.textContent = 'Please fill in all fields';
        }
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(userData.email)) {
        if (formError) {
            formError.textContent = 'Please enter a valid email address';
        }
        return;
    }
    
    // Validate password length
    if (userData.password.length < 8) {
        if (formError) {
            formError.textContent = 'Password must be at least 8 characters long';
        }
        return;
    }
    
    // Disable the form and show loading
    toggleFormLoading(registerForm, true);
    
    try {
        // Send register request
        const response = await authApi.register(userData);
        
        if (response.status === 'success') {
            // Show success message and redirect to login
            alert('Registration successful! You can now log in.');
            window.location.href = '/login';
        } else {
            // Show error message
            if (formError) {
                formError.textContent = response.message || 'Registration failed';
            }
            toggleFormLoading(registerForm, false);
        }
    } catch (error) {
        // Show error message
        if (formError) {
            const errors = error.errors || {};
            let errorMessage = error.message || 'Registration failed';
            
            // If we have field-specific errors, show them
            if (Object.keys(errors).length > 0) {
                errorMessage = Object.values(errors)[0];
            }
            
            formError.textContent = errorMessage;
        }
        toggleFormLoading(registerForm, false);
    }
};

/**
 * Toggle password visibility
 * 
 * @param {HTMLElement} toggleBtn - Toggle button element
 * @param {HTMLElement} passwordInput - Password input element
 */
const togglePasswordVisibility = (toggleBtn, passwordInput) => {
    const type = passwordInput.getAttribute('type');
    
    if (type === 'password') {
        passwordInput.setAttribute('type', 'text');
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        passwordInput.setAttribute('type', 'password');
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    }
};

/**
 * Toggle form loading state
 * 
 * @param {HTMLElement} form - Form element
 * @param {boolean} isLoading - Whether the form is loading
 */
const toggleFormLoading = (form, isLoading) => {
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (submitBtn) {
        if (isLoading) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = form.id === 'login-form' ? 'Sign In' : 'Sign Up';
        }
    }
    
    // Disable or enable all form inputs
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.disabled = isLoading;
    });
};

/**
 * Handle route changes
 */
const handleRouteChange = () => {
    const path = window.location.pathname;
    if (path === '/login') {
        showLoginForm();
    } else if (path === '/register') {
        showRegisterForm();
    }
};

// Initialize the auth module when the DOM is loaded
document.addEventListener('DOMContentLoaded', initAuth); 