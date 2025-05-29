<?php
/**
 * Home Page View
 */
?>
<div class="bg-primary-600">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl sm:tracking-tight lg:text-6xl">Welcome to Chat App</h1>
            <p class="max-w-xl mx-auto mt-5 text-xl text-primary-100">A simple, secure, and feature-rich messaging platform.</p>
            <div class="mt-8 flex justify-center">
                <?php if (!$isLoggedIn): ?>
                    <div class="inline-flex rounded-md shadow">
                        <a href="<?php echo APP_URL; ?>/index.php?page=login" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50" tabindex="0" aria-label="Login to your account" role="button">
                            Login
                        </a>
                    </div>
                    <div class="ml-3 inline-flex">
                        <a href="<?php echo APP_URL; ?>/index.php?page=register" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-700 hover:bg-primary-800" tabindex="0" aria-label="Create a new account" role="button">
                            Register
                        </a>
                    </div>
                <?php else: ?>
                    <div class="inline-flex rounded-md shadow">
                        <a href="<?php echo APP_URL; ?>/index.php?page=chat" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50" tabindex="0" aria-label="Go to your chats" role="button">
                            Go to Chats
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center">
            <h2 class="text-base text-primary-600 font-semibold tracking-wide uppercase">Features</h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                A better way to connect with others
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                Our chat application offers a variety of features to enhance your messaging experience.
            </p>
        </div>

        <div class="mt-10">
            <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
                <div class="relative">
                    <dt>
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Real-time Messaging</p>
                    </dt>
                    <dd class="mt-2 ml-16 text-base text-gray-500">
                        Send and receive messages instantly with our real-time messaging system using long-polling technology.
                    </dd>
                </div>

                <div class="relative">
                    <dt>
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Group Chats</p>
                    </dt>
                    <dd class="mt-2 ml-16 text-base text-gray-500">
                        Create group chats with multiple participants to collaborate and communicate with your team or friends.
                    </dd>
                </div>

                <div class="relative">
                    <dt>
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Secure Messaging</p>
                    </dt>
                    <dd class="mt-2 ml-16 text-base text-gray-500">
                        Your conversations are protected with secure authentication and data handling practices.
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<div class="bg-primary-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                Get Started Today
            </h2>
            <p class="mt-4 text-lg leading-6 text-gray-500">
                Join thousands of users who are already using our platform to connect with others.
            </p>
            <div class="mt-8 flex justify-center">
                <?php if (!$isLoggedIn): ?>
                    <div class="inline-flex rounded-md shadow">
                        <a href="<?php echo APP_URL; ?>/index.php?page=register" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700" tabindex="0" aria-label="Create your account now" role="button">
                            Create Account
                        </a>
                    </div>
                <?php else: ?>
                    <div class="inline-flex rounded-md shadow">
                        <a href="<?php echo APP_URL; ?>/index.php?page=chat" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700" tabindex="0" aria-label="Go to your chats" role="button">
                            Go to Chats
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 