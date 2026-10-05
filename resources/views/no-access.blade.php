<x-layouts::auth :title="__('home.no_access_title')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('home.no_access_title')"
            :description="__('home.no_access_body', ['name' => auth()->user()->username ?? auth()->user()->name])"
        />

        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">{{ __('home.no_access_hint') }}</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="primary" type="submit" class="w-full" data-test="no-access-logout">
                {{ __('home.no_access_logout') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
