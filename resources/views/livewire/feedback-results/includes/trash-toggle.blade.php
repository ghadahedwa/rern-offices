{{-- مبدّل سلة المحذوفات (الشاشات ذات الحذف المنطقي فقط) --}}
<button type="button" wire:click="toggleTrashed"
        class="inline-flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border transition whitespace-nowrap
            {{ $showTrashed
                ? 'border-[#c9a847] bg-[#c9a847] text-white'
                : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
    </svg>
    {{ $showTrashed ? __('home.fr_trash_exit') : __('home.fr_trash') }}
</button>
