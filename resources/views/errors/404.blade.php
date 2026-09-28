@php
    $user = auth()->user();
    $isAuthenticated = auth()->check();
    
    if ($isAuthenticated) {
        $dashboardRoute = 'dashboard';
        
        if ($user->isAdmin()) {
            $dashboardRoute = 'admin.dashboard';
        } elseif ($user->isMember()) {
            $dashboardRoute = 'member.dashboard';
        } elseif ($user->isMerchantUser()) {
            $dashboardRoute = 'merchant.dashboard.v2';
        }
    } else {
        $dashboardRoute = 'login';
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Hope Village | Page Not Found</title>
        <meta name="description" content="{{ 'A community hub for migrants community in Singapore - brought to you by Hope Initiative Allaince in partnership with Advancer IFM and other supporting partners.' }}">
        <link rel="icon" type="image/png" href="{{ asset('hv-logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('hv-logo.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-[#FAF7F4]">
        <div class="font-sans text-gray-900 bg-orange-100 antialiased px-4">
            <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl w-full">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6 sm:p-12 text-center">
                            <!-- 404 Icon/Illustration -->
                            <div class="mb-6">
                                <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-orange-100 mb-4">
                                    <svg class="w-20 h-20 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Error Code -->
                            <h1 class="text-9xl font-bold text-gray-300 mb-4">404</h1>
                            
                            <!-- Error Message -->
                            <h2 class="text-3xl font-bold text-gray-800 mb-4">Page Not Found</h2>
                            <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">
                                Sorry, the page you are looking for doesn't exist or has been moved.
                            </p>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                                <a href="{{ route('login') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    Go to Login
                                </a>
                                
                                <button onclick="window.history.back()" 
                                        class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Go Back
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>

