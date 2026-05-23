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
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">الإحصائيات</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $office->name }}</p>
        </div>
        
        <a href="{{ route('offices.edit', $office->id) }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            {{ __('home.edit_office') }}
        </a>
    </div>

    

    {{-- Tabs Nav --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            @foreach($tabs as $key => $label)
            <button type="button"
                    wire:click="$set('activeTab', '{{ $key }}')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition cursor-pointer
                        {{ $activeTab === $key
                            ? 'border-[#c9a847] text-[#c9a847]'
                            : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                {{ $label }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab Content --}}
    <div>
        @if($activeTab === 'transactions')
            <livewire:offices.stat-tab.transactions-sales :office="$office" :key="'ts-'.$office->id" />

        @elseif($activeTab === 'forms_folders')
            <livewire:offices.stat-tab.forms-and-folders :office="$office" :key="'ff-'.$office->id" />

        @elseif($activeTab === 'shaher_requests')
            <livewire:offices.stat-tab.requests :office="$office" :key="'sr-'.$office->id" />

        @elseif($activeTab === 'registry_requests')
            <livewire:offices.stat-tab.registry-requests :office="$office" :key="'rr-'.$office->id" />
        @endif
    </div>

</div>
