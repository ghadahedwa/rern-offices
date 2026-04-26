<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Name -->
            <div class="flex flex-col gap-1">
                <label for="name" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Name') }}
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="{{ __('Full name') }}"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Username -->
            <div class="flex flex-col gap-1">
                <label for="username" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Username') }}
                </label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    required
                    autocomplete="username"
                    placeholder="{{ __('Choose a username') }}"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('username') border-red-500 @enderror"
                />
                @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Email Address -->
            <div class="flex flex-col gap-1">
                <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Email address') }}
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    placeholder="email@example.com ({{ __('optional') }})"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                />
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1">
                <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Password') }}
                </label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Password') }}"
                        class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10 @error('password') border-red-500 @enderror"
                    />
                    <button
                        type="button"
                        onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password'"
                        class="absolute inset-y-0 end-0 flex items-center px-3 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                        aria-label="{{ __('Toggle password visibility') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col gap-1">
                <label for="password_confirmation" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Confirm password') }}
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}"
                        class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10 @error('password_confirmation') border-red-500 @enderror"
                    />
                    <button
                        type="button"
                        onclick="const i=document.getElementById('password_confirmation');i.type=i.type==='password'?'text':'password'"
                        class="absolute inset-y-0 end-0 flex items-center px-3 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                        aria-label="{{ __('Toggle confirm password visibility') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                @error('password_confirmation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end">
                <button
                    type="submit"
                    data-test="register-user-button"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition"
                >
                    {{ __('Create account') }}
                </button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>
                {{ __('Log in') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
