{{-- مقرات تحتاج زيارة --}}
@if($needsVisitCount > 0)
<div class="rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-900/10 p-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
            <flux:icon.exclamation-triangle variant="outline" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
        </div>
        <div>
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                {{ number_format($needsVisitCount) }} {{ __('home.needs_visit_label') }}
            </p>
            <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">{{ __('home.needs_visit_desc') }}</p>
        </div>
    </div>
    <a href="{{ route('offices.index', ['needs_visit' => 1]) }}" wire:navigate
       class="shrink-0 text-xs font-medium text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 rounded-lg px-3 py-1.5 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">
        {{ __('home.needs_visit_action') }}
    </a>
</div>
@endif
