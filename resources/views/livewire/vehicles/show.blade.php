<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('vehicles.index') }}" wire:navigate
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $vehicle->name }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ $vehicle->governorate->name ?? '—' }}
                    @if($vehicle->type) &mdash; {{ $vehicle->type->name }} @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($canEdit)
            <a href="{{ route('vehicles.edit', $vehicle->id) }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ __('home.edit_vehicle') }}
            </a>
            @endif
        </div>
    </div>

    {{-- Step Progress --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <div class="flex items-center justify-between">
            @php
                $tabs = [
                    'basic'      => __('home.tab_basic_data'),
                    'workers'    => __('home.tab_workers'),
                    'equipment'  => __('home.tab_equipment'),
                    'media'      => __('home.tab_media'),
                    'statistics' => __('home.tab_statistics'),
                ];
                $tabKeys = array_keys($tabs);
            @endphp
            @foreach($tabs as $key => $label)
            @php $num = array_search($key, $tabKeys) + 1; @endphp
            <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center gap-1">
                    <button type="button" wire:click="$set('activeTab', '{{ $key }}')"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all cursor-pointer
                                {{ $activeTab === $key
                                    ? 'bg-[#c9a847] text-white ring-4 ring-[#c9a847]/20'
                                    : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                        {{ $num }}
                    </button>
                    <span class="text-xs font-medium hidden sm:block {{ $activeTab === $key ? 'text-[#c9a847]' : 'text-zinc-400' }}">
                        {{ $label }}
                    </span>
                </div>
                @if(!$loop->last)
                <div class="flex-1 h-0.5 mx-3 bg-zinc-200 dark:bg-zinc-700"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="p-6 space-y-6">

            @if($activeTab === 'basic')
                @include('livewire.vehicles.includes.show-tab-basic')

            @elseif($activeTab === 'workers')
                @include('livewire.vehicles.includes.show-tab-workers')

            @elseif($activeTab === 'equipment')
                @include('livewire.vehicles.includes.show-tab-equipment')

            @elseif($activeTab === 'media')
                @include('livewire.vehicles.includes.show-tab-media')

            @elseif($activeTab === 'statistics')
                @include('livewire.vehicles.includes.show-tab-statistics')
            @endif

        </div>
    </div>

    {{-- Prev / Next --}}
    @php
        $tabOrder     = ['basic', 'workers', 'equipment', 'media', 'statistics'];
        $currentIndex = array_search($activeTab, $tabOrder);
        $prevTab      = $currentIndex > 0 ? $tabOrder[$currentIndex - 1] : null;
        $nextTab      = $currentIndex < count($tabOrder) - 1 ? $tabOrder[$currentIndex + 1] : null;
    @endphp
    <div class="flex items-center justify-between">
        <div>
            @if($prevTab)
            <button type="button" wire:click="$set('activeTab', '{{ $prevTab }}')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                {{ __('home.previous_step') }}
            </button>
            @endif
        </div>
        <div>
            @if($nextTab)
            <button type="button" wire:click="$set('activeTab', '{{ $nextTab }}')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium transition">
                {{ __('home.next_tab') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            @else
            <a href="{{ route('vehicles.index') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm font-medium transition">
                {{ __('home.exit') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endif
        </div>
    </div>

</div>
