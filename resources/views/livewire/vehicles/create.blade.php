<div class="max-w-4xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('vehicles.index') }}" wire:navigate
               class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 flex items-center justify-center text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                {{ $isEditing ? __('home.edit_vehicle') : __('home.add_vehicle') }}
            </h1>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            <button type="button"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition border-[#c9a847] text-[#c9a847]">
                {{ __('home.tab_basic_data') }}
            </button>
            @if($isEditing)
                <button type="button"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition border-transparent text-zinc-400 dark:text-zinc-500 cursor-not-allowed">
                    {{ __('home.tab_workers') }}
                </button>
                <button type="button"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition border-transparent text-zinc-400 dark:text-zinc-500 cursor-not-allowed">
                    {{ __('home.tab_equipment') }}
                </button>
                <button type="button"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition border-transparent text-zinc-400 dark:text-zinc-500 cursor-not-allowed">
                    {{ __('home.tab_media') }}
                </button>
                <button type="button"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition border-transparent text-zinc-400 dark:text-zinc-500 cursor-not-allowed">
                    {{ __('home.tab_statistics') }}
                </button>
            @endif
        </nav>
    </div>

    {{-- Tab 1: البيانات الأساسية --}}
    @include('livewire.vehicles.includes.create-tab-basic')

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
