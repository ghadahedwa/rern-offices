<div class="max-w-4xl mx-auto p-6 space-y-6"
     x-data
     @keydown.escape.window="$wire.showDemand = false; $wire.showDeleteDemand = false; $wire.showForm = false; $wire.showDelete = false">

    @php
        $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    @endphp

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.claims_title') }}</h1>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            <button type="button" wire:click="setTab('debt')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'debt' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.claims_tab_debt') }}
            </button>
            <button type="button" wire:click="setTab('demands')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'demands' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.claims_tab_demands') }}
            </button>
            <button type="button" wire:click="setTab('collection')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'collection' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.claims_tab_collection') }}
            </button>
        </nav>
    </div>

    @if($tab === 'debt')
        @include('livewire.claims.includes.debt-tab')
    @elseif($tab === 'demands')
        @include('livewire.claims.includes.demands-tab')
    @else
        @include('livewire.claims.includes.collection-tab')
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
