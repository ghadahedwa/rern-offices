<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.vehicles_title') }}</h1>
        @can('vehicles.create')
            <a href="{{ route('vehicles.create') }}" wire:navigate
               class="px-4 py-2 text-sm border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 rounded-lg transition">
                + {{ __('home.add_vehicle') }}
            </a>
        @endcan
    </div>
    <p class="text-zinc-500 dark:text-zinc-400 text-sm">{{ __('home.coming_soon') }}</p>
</div>
