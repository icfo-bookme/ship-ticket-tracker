<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Access Denied - Ship Booking</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-200">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-lg max-w-md w-full p-8 text-center">
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-5">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>

            <h1 class="text-6xl font-extrabold text-blue-950 mb-2">403</h1>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Access Denied</h2>
            <p class="text-sm text-gray-500 mb-8">
                Sorry, you don't have permission to access this page.
                <br>
                Please contact an administrator if you believe this is a mistake.
            </p>

            <div class="flex items-center justify-center gap-3">
                <a href="{{ url('/dashboard') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-[#006172] text-white text-sm font-medium rounded-lg hover:bg-blue-950 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    Go to Dashboard
                </a>
                @guest
                <a href="{{ route('login') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    Login
                </a>
                @endguest
            </div>
        </div>

        <p class="mt-6 text-xs text-gray-400">Ship Booking — BookMe</p>
    </div>
</body>
</html>
