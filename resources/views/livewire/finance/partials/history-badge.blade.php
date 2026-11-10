{{-- علامة «عُدِّل» — تفتح سجلّ الشهر تحته. يتطلب: $events (سجلّ الشهر) و$key (`Y-m`) --}}
@if($events->isNotEmpty())
    @php
        $corrections = $events->where('event', \App\Support\Finance\FinanceHistory::EVENT_CORRECTED)->count();
        $deletions   = $events->where('event', \App\Support\Finance\FinanceHistory::EVENT_DELETED)->count();
        // الاثنان يُعدّان — شهرٌ حُذف ثم أُعيد إدخاله لا يُخفي حذفه خلف «عُدِّل»
        $notes       = $events->filter(fn ($e) => \App\Support\Finance\FinanceHistory::isNoteEvent($e))->count();
        $parts = array_filter([
            $corrections ? __('home.fin_history_badge', ['count' => $corrections]) : null,
            $deletions ? __('home.fin_history_badge_deleted', ['count' => $deletions]) : null,
            $notes ? __('home.fin_history_badge_notes', ['count' => $notes]) : null,
        ]);
    @endphp
    <button type="button" @click="open = open === '{{ $key }}' ? null : '{{ $key }}'"
            class="ms-1.5 inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-200 hover:bg-amber-200 dark:hover:bg-amber-900/50 transition">
        {{ implode(' · ', $parts) }}
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition" :class="open === '{{ $key }}' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
@endif
