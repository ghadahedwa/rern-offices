{{-- «الأصل»: أول ما أُدخل للصفّ الحالي — من صورة ما قبل أقدم تصحيحاته، ومُدخِله ووقته من الصفّ نفسه.
     يتطلب: $current (تصحيحات الصفّ الحالي، الأحدث أولاً) و$record --}}
<li class="flex gap-3 text-xs">
    <span class="shrink-0 w-5 h-5 rounded-full bg-zinc-200 dark:bg-zinc-700 grid place-items-center text-zinc-500 dark:text-zinc-300">●</span>
    <div class="min-w-0">
        <p class="text-zinc-500 dark:text-zinc-400">
            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.fin_history_origin') }}</span>
            · {{ $record->creator?->name ?? '—' }}
            · <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($record->created_at) }}</span>
            {{ \App\Support\LocalTime::time($record->created_at) }}
        </p>
        <p class="mt-1 text-zinc-700 dark:text-zinc-200">{{ \App\Support\Finance\FinanceHistory::summary($current->last()->properties['before'] ?? []) }}</p>
    </div>
</li>
