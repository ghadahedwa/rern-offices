<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name, username and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">

            <!-- Name -->
            <div class="flex flex-col gap-1">
                <label for="name" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Name') }}
                </label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    required
                    autofocus
                    autocomplete="name"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                    type="text"
                    wire:model="username"
                    required
                    autocomplete="username"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Email -->
            <div class="flex flex-col gap-1">
                <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Email') }}
                </label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-md px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Your email address is unverified.') }}
                        <button type="button" wire:click.prevent="resendVerificationNotification" class="text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    {{ __('Save') }}
                </button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
