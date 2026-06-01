<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('offices.index') }}" wire:navigate
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $office->name }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ $office->governorate->name ?? '—' }}
                    @if($office->officeType) &mdash; {{ $office->officeType->name }} @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('offices.statistics', $office->id) }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                {{ __('home.office_statistics') }}
            </a>
            @if($canEdit)
            <a href="{{ route('offices.edit', $office->id) }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ __('home.edit_office') }}
            </a>
            @endif
        </div>
    </div>

    {{-- Card with Tabs --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">

        {{-- Tabs Nav --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700 px-4">
            <nav class="flex gap-1">
                @php
                    $tabs = [
                        'basic'      => __('home.show_tab_basic'),
                        'services'   => __('home.show_tab_services'),
                        'assessment' => __('home.show_tab_assessment'),
                        'media'      => __('home.show_tab_media'),
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                <button type="button"
                        wire:click="$set('activeTab', '{{ $key }}')"
                        class="px-4 py-3 text-sm font-medium border-b-2 -mb-px transition cursor-pointer
                            {{ $activeTab === $key
                                ? 'border-[#c9a847] text-[#c9a847]'
                                : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    {{ $label }}
                </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6 space-y-6">

            @if($activeTab === 'basic')
                @include('livewire.offices.includes.show-tab-basic')

            @elseif($activeTab === 'services')
                @include('livewire.offices.includes.show-tab-services')

            @elseif($activeTab === 'assessment')
                @include('livewire.offices.includes.show-tab-assessment')

            @elseif($activeTab === 'media')
                @include('livewire.offices.includes.show-tab-media')
            @endif

        </div>
    </div>

</div>
