<div class="max-w-4xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="w-1 h-8 bg-[#c9a847] rounded-full"></div>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.system_dashboard') }}</h1>
    </div>

    {{-- Empty state (مؤقت لحد ما نضيف المؤشرات) --}}
    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-12 flex flex-col items-center justify-center text-center gap-3">
        <div class="w-16 h-16 rounded-full bg-[#c9a847]/10 flex items-center justify-center">
            <flux:icon.squares-2x2 class="w-8 h-8 text-[#c9a847]" />
        </div>
        <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.system_dashboard_empty') }}</p>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md">{{ __('home.system_dashboard_empty_hint') }}</p>
    </div>

</div>
