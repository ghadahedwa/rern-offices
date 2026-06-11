{{-- ── Delete Confirmation Modal (مشترك) ──
     يتطلب في الـ component: bool $showDelete, string $deletingLabel, method deleteRow() --}}
<div x-show="$wire.showDelete"
     x-transition.opacity
     @click.self="$wire.showDelete = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     style="display:none">
    <div x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-red-500">
        <div class="flex items-center justify-between px-5 py-3.5 bg-red-500">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">{{ __('home.confirm_delete_title') }}</h3>
            </div>
            <button type="button" @click="$wire.showDelete = false"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
        </div>
        <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
            <p class="text-sm text-zinc-600 dark:text-zinc-300 text-center">
                {{ __('home.confirm_delete_record') }}<br>
                <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $deletingLabel }}</span>
            </p>
            @if(!empty($deletingWarning ?? ''))
                <div class="flex items-start gap-2.5 rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-900/20 px-3.5 py-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <p class="text-xs leading-relaxed text-red-700 dark:text-red-400">{{ $deletingWarning }}</p>
                </div>
            @endif
            <div class="flex gap-3">
                <button type="button" wire:click="deleteRow"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    {{ __('home.delete') }}
                </button>
                <button type="button" @click="$wire.showDelete = false"
                        class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                    {{ __('home.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
