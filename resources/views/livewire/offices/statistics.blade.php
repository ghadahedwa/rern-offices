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
        
        <div class="flex items-center gap-2">
            @if($canView)
            <a href="{{ route('offices.show', $office->id) }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                {{ __('home.view_office') }}
            </a>
            @endif
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

    

    {{-- Tabs Nav --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <div class="flex items-start justify-between">
            @php $tabKeys = array_keys($tabs); @endphp
            @foreach($tabs as $key => $label)
            @php $num = array_search($key, $tabKeys) + 1; @endphp
            <div class="flex items-start {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center gap-1">
                    <button type="button"
                            wire:click="$set('activeTab', '{{ $key }}')"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all cursor-pointer shrink-0
                                {{ $activeTab === $key
                                    ? 'bg-[#c9a847] text-white ring-4 ring-[#c9a847]/20'
                                    : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                        {{ $num }}
                    </button>
                    <span class="text-xs font-medium hidden sm:block text-center max-w-20 leading-tight
                        {{ $activeTab === $key ? 'text-[#c9a847]' : 'text-zinc-400' }}">
                        {{ $label }}
                    </span>
                </div>
                @if(!$loop->last)
                <div class="flex-1 h-0.5 mx-2 mt-4.5 shrink bg-zinc-200 dark:bg-zinc-700"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tab Content --}}
    <div>
        @if($activeTab === 'transactions')
            <livewire:offices.stat-tab.transactions-sales :office="$office" :canEdit="$canEdit" :key="'ts-'.$office->id" />

        @elseif($activeTab === 'forms_folders')
            <livewire:offices.stat-tab.forms-and-folders :office="$office" :canEdit="$canEdit" :key="'ff-'.$office->id" />

        @elseif($activeTab === 'shaher_requests')
            <livewire:offices.stat-tab.requests :office="$office" :canEdit="$canEdit" :key="'sr-'.$office->id" />

        @elseif($activeTab === 'registry_requests')
            <livewire:offices.stat-tab.registry-requests :office="$office" :canEdit="$canEdit" :key="'rr-'.$office->id" />

        @elseif($activeTab === 'registry_forms_folders')
            <livewire:offices.stat-tab.registry-forms-and-folders :office="$office" :canEdit="$canEdit" :key="'rff-'.$office->id" />

        @elseif($activeTab === 'law9_registrations')
            <livewire:offices.stat-tab.law9-registrations :office="$office" :canEdit="$canEdit" :key="'l9-'.$office->id" />

        @elseif($activeTab === 'monthly_forms_folders')
            <livewire:offices.stat-tab.monthly-forms-and-folders :office="$office" :canEdit="$canEdit" :key="'mff-'.$office->id" />

        @elseif($activeTab === 'law27_forms_folders')
            <livewire:offices.stat-tab.law27-forms-and-folders :office="$office" :canEdit="$canEdit" :key="'l27-'.$office->id" />
        @endif
    </div>

</div>
