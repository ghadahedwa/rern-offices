<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="dark">
    <head>
        @include('partials.head')
        <style>
            html { font-size: 17px; }
            * { font-size: inherit; }
            [data-flux-sidebar-item],
            [data-flux-sidebar-item] span,
            [data-flux-sidebar-item] a { font-size: 1.1rem !important; }
            [data-flux-sidebar]:not([data-flux-sidebar-collapsed-desktop]) { width: 15rem; }
            [data-flux-sidebar] { z-index: 30 !important; }
        </style>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 text-base">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header class="flex flex-col items-center gap-1 pb-0">
                <div class="w-full flex justify-center">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>
                <div class="w-full flex justify-end -mt-3">
                    <flux:sidebar.collapse class="text-[#c9a847]" />
                </div>
            </flux:sidebar.header>
            <flux:sidebar.nav class="-mt-4">
                <flux:sidebar.group class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('home.dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map-pin" :href="route('governorates.index')" :current="request()->routeIs('governorates.*')" wire:navigate>
                        {{ __('home.governorates') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('offices.index')" :current="request()->routeIs('offices.*')" wire:navigate>
                        {{ __('home.offices') }}
                    </flux:sidebar.item>
                    
                    <div x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex items-center w-full px-3 py-2 text-sm font-medium hover:bg-gray-100 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <span class="ml-2 text-[#747474]">{{ __('home.program_settings') }}</span>
                            <svg class="ml-auto w-4 h-4 transition-transform"
                                :class="{ 'rotate-180': open }"
                                viewBox="0 0 20 20">
                                <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="ml-6 mt-1 space-y-1">
                            <flux:sidebar.item icon="tag" :href="route('office-types.index')" :current="request()->routeIs('office-types.*')" wire:navigate>
                                {{ __('home.offices_type') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="map-pin" :href="route('location-descriptions.index')" :current="request()->routeIs('location-descriptions.*')" wire:navigate>
                                {{ __('home.location_description') }}
                            </flux:sidebar.item>
                        </div>
                    </div>

                    <div x-data="{ open: {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'true' : 'false' }} }">
                        <!-- Parent Item -->
                        <button @click="open = !open"
                            class="flex items-center w-full px-3 py-2 text-sm font-medium hover:bg-gray-100 rounded">

                            <span>⚙️</span>
                            <span class="ml-2 text-[#747474]">{{ __('home.users_settings') }}</span>

                            <svg class="ml-auto w-4 h-4 transition-transform"
                                :class="{ 'rotate-180': open }"
                                viewBox="0 0 20 20">
                                <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                            </svg>

                        </button>

                        <!-- Submenu -->
                        <div x-show="open" x-transition class="ml-6 mt-1 space-y-1">
                            <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                                            {{ __('home.users') }}
                                        </flux:sidebar.item>
                                        <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                                            {{ __('home.roles') }}
                                        </flux:sidebar.item>

                        </div>

                    </div>
                    
                </flux:sidebar.group>
                
            </flux:sidebar.nav>
        </flux:sidebar>

        {{-- Navbar: visible on all screen sizes --}}
        <flux:header class="border-b border-[#b8962e]" style="background-color: #c9a847;">
            {{-- Mobile sidebar toggle --}}
            <flux:sidebar.toggle class="lg:hidden text-[#c9a847]" icon="bars-2" inset="left" />

            <flux:spacer />

            {{-- User dropdown with gold styling --}}
            <flux:dropdown position="bottom" align="end">
                <button type="button"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-black/10 transition">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-[#c9a847] text-sm font-bold"
                         style="background-color: rgba(255,255,255,0.25);">
                        {{ auth()->user()->initials() }}
                    </div>
                    <span class="hidden sm:block text-sm font-semibold text-white">
                        {{ auth()->user()->name }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <flux:menu>
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                        />
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
