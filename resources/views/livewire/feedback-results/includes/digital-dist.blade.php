{{-- توزيع سؤال واحد من DigitalReport::distribution — العنوان والمقام ثم شريط لكل إجابة.
     ⚠️ المقام ظاهر دائماً: النسبة بلا مقامها تُقرأ خطأً (مشاكل الحجز من الحاجزين لا من الكل). --}}
<div>
    <div class="flex items-baseline justify-between gap-3 mb-1">
        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200" title="{{ $dist['question'] }}">{{ $dist['title'] }}</p>
        <span class="text-xs text-zinc-400 whitespace-nowrap">{{ __('home.fr_dg_base', ['base' => $dist['base']]) }}</span>
    </div>
    @if($dist['multi'])
        <p class="text-xs text-zinc-400 mb-2">{{ __('home.fr_dg_multi_note') }}</p>
    @endif

    @if($dist['base'] === 0)
        <p class="text-xs text-zinc-400 py-2">{{ __('home.fr_no_data') }}</p>
    @else
        <div class="space-y-2.5 mt-2">
            @foreach($dist['rows'] as $row)
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="text-xs text-zinc-600 dark:text-zinc-300">{{ $row['label'] }}</p>
                        <span class="text-xs text-zinc-400 whitespace-nowrap">{{ $row['count'] }} · {{ $row['percent'] }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        <div class="h-full bg-[#c9a847] rounded-full" style="width: {{ min(100, $row['percent'] ?? 0) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
