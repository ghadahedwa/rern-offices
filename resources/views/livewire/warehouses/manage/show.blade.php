<div class="p-6 max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('warehouse-manage.index') }}" wire:navigate
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $warehouse->name }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ $warehouse->type?->name ?? '—' }}
                    @if($warehouse->governorate) &mdash; {{ $warehouse->governorate->name }} @endif
                    &mdash;
                    @if($warehouse->is_active)
                        <span class="text-green-600 dark:text-green-400">{{ __('home.warehouse_active') }}</span>
                    @else
                        <span class="text-zinc-400">{{ __('home.warehouse_inactive') }}</span>
                    @endif
                </p>
            </div>
        </div>

        @if($canEdit)
            <a href="{{ route('warehouse-manage.edit', $warehouse) }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium transition">
                {{ __('home.edit') }}
            </a>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            <button type="button" wire:click="setTab('stock')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'stock' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.wh_stock') }}
            </button>
            <button type="button" wire:click="setTab('movements')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'movements' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.wh_movements') }}
            </button>
            @if($warehouse->isMain())
                <button type="button" wire:click="setTab('incoming')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                    {{ $tab === 'incoming' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    {{ __('home.wh_incoming') }}
                </button>
            @endif
            <button type="button" wire:click="setTab('transfers')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                {{ $tab === 'transfers' ? 'border-[#c9a847] text-[#c9a847]' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ __('home.wh_transfers') }}
            </button>
        </nav>
    </div>

    {{-- Tab Content --}}
    @if($tab === 'stock')
        @include('livewire.warehouses.manage.includes.show-tab-stock')
    @elseif($tab === 'movements')
        @include('livewire.warehouses.manage.includes.show-tab-movements')
    @elseif($tab === 'incoming')
        @include('livewire.warehouses.manage.includes.show-tab-incoming')
    @elseif($tab === 'transfers')
        @include('livewire.warehouses.manage.includes.show-tab-transfers')
    @endif

</div>
