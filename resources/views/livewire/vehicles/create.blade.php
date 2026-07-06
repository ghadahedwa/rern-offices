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

    {{-- Tab Progress --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <div class="flex items-center justify-between">
            @php
                $tabLabels = [1 => 'tab_basic_data', 2 => 'tab_workers', 3 => 'tab_equipment', 4 => 'tab_media', 5 => 'tab_statistics'];
            @endphp
            @foreach ($tabLabels as $num => $label)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-1">
                        @if($vehicle_id && $num !== $activeTab)
                        <button type="button" wire:click="goToTab({{ $num }})"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all cursor-pointer
                                    {{ $activeTab > $num ? 'bg-[#c9a847] text-white hover:bg-[#b8962e]' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                            @if ($activeTab > $num)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </button>
                        @else
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
                                {{ $activeTab === $num ? 'bg-[#c9a847] text-white ring-4 ring-[#c9a847]/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 opacity-50' }}">
                            @if ($activeTab > $num)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        @endif
                        <span class="text-xs font-medium hidden sm:block {{ $activeTab === $num ? 'text-[#c9a847]' : 'text-zinc-400' }}">
                            {{ __('home.' . $label) }}
                        </span>
                    </div>
                    @if (!$loop->last)
                        <div class="flex-1 h-0.5 mx-3 {{ $activeTab > $num ? 'bg-[#c9a847]' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Tab content --}}
    @if($activeTab === 1)
        @include('livewire.vehicles.includes.create-tab-basic')
    @elseif($activeTab === 2)
        @include('livewire.vehicles.includes.create-tab-workers')
    @elseif($activeTab === 3)
        @include('livewire.vehicles.includes.create-tab-equipment')
    @elseif($activeTab === 4)
        @include('livewire.vehicles.includes.create-tab-media')
    @elseif($activeTab === 5)
        @include('livewire.vehicles.includes.create-tab-statistics')
    @endif

    {{-- Navigation Buttons --}}
    <div class="flex items-center justify-between">
        <div>
            @if ($activeTab > 1)
                <button wire:click="prevTab" type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('home.save_and_previous') }}
                </button>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($activeTab === 1)
                <button wire:click="saveAndExit" type="button" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition disabled:opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('home.save_and_exit') }}
                </button>
            @endif
            @if ($activeTab < $totalTabs)
                <button wire:click="nextTab" type="button" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition disabled:opacity-60">
                    {{ __('home.next_step') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <button wire:click="saveAndExit" type="button" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition disabled:opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('home.save_and_exit') }}
                </button>
            @endif
        </div>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
