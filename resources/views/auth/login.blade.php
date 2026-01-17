<x-guest-layout>
    <div class=" flex items-center justify-center  px-4">
        <div class="w-full pt-5">

            <!-- Session Status -->
            <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

            <!-- Logo / Title -->
            <div class="text-center mb-8">
                <a href="/">
                    <h1 class="text-3xl font-extrabold text-blue-900 tracking-wide">
                        Ticket Tracker
                    </h1>
                </a>
                <p class="text-sm text-gray-500 mt-1">
                    Secure login to manage your tickets
                </p>
                <div class="w-24 mx-auto h-1 mt-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full"></div>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label for="email" value="Email address" />
                    <x-text-input
                        id="email"
                        class="block mt-1 w-full rounded-lg"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input
                        id="password"
                        class="block mt-1 w-full rounded-lg"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label for="remember_me" class="flex items-center text-sm text-gray-600">
                        <input
                            id="remember_me"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            name="remember"
                        >
                        <span class="ml-2">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                        >
                            Forgot password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <x-primary-button class="w-full justify-center py-3 text-base rounded-lg bg-indigo-600 hover:bg-indigo-700 transition">
                    Log in
                </x-primary-button>
            </form>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-400 mt-8">
                © {{ date('Y') }} Ticket Tracker. All rights reserved.
            </p>
        </div>
    </div>
</x-guest-layout>
