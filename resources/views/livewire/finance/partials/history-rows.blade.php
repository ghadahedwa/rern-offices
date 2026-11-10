{{-- سجلّ الشهر: مَن · متى · لماذا · من كم إلى كم (قرار العميل). يتطلب: $events و$key و$record
     الأحدث أولاً، ولكل حدثٍ رقمه بترتيب وقوعه (ثابتٌ مهما أُضيف بعده)، وسطر «الأصل» تحت
     تصحيحات الصفّ الحالي — أول ما أُدخل ومَن أدخله — فتُقرأ القصة كاملة. --}}
@if($events->isNotEmpty())
    @php
        [$current, $older] = $record
            ? \App\Support\Finance\FinanceHistory::splitAtDeletion($events)
            : [collect(), $events];
        $total = $events->count();
    @endphp
    <tr wire:key="fin-history-{{ $key }}" x-show="open === '{{ $key }}'" style="display:none" class="bg-amber-50/50 dark:bg-amber-900/10">
        <td colspan="{{ $cols ?? 4 }}" class="px-4 py-3">
            <ol class="space-y-3">
                @foreach($current->concat($older) as $index => $event)
                    @if($loop->index === $current->count() && $current->isNotEmpty())
                        @include('livewire.finance.partials.history-origin')
                    @endif
                    <li class="flex gap-3 text-xs">
                        <span class="shrink-0 w-5 h-5 rounded-full bg-white dark:bg-zinc-800 border border-amber-300 dark:border-amber-700 grid place-items-center font-semibold text-amber-700 dark:text-amber-300">{{ $total - $loop->index }}</span>
                        <div class="min-w-0">
                            <p class="text-zinc-500 dark:text-zinc-400">
                                <span class="font-semibold {{ $event->event === \App\Support\Finance\FinanceHistory::EVENT_DELETED ? 'text-red-600 dark:text-red-400' : 'text-amber-700 dark:text-amber-300' }}">
                                    {{ __('home.fin_history_event_'.$event->event) }}
                                </span>
                                · {{ $event->causer?->name ?? '—' }}
                                · <span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($event->created_at) }}</span>
                                {{ \App\Support\LocalTime::time($event->created_at) }}
                                @if($loop->first)
                                    <span class="ms-1 rounded bg-amber-200/70 dark:bg-amber-800/50 px-1.5 py-0.5 font-medium text-amber-800 dark:text-amber-200">{{ __('home.fin_history_latest') }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-zinc-700 dark:text-zinc-200">
                                <span class="text-zinc-400">{{ __('home.fin_reason_label') }}:</span> {{ $event->properties['reason'] ?? '' }}
                            </p>
                            <ul class="mt-1 space-y-0.5">
                                @foreach(\App\Support\Finance\FinanceHistory::changes($event) as $change)
                                    <li class="text-zinc-600 dark:text-zinc-300">
                                        <span class="text-zinc-400">{{ $change['label'] }}:</span>
                                        @if($change['from'] !== null)
                                            <span class="line-through text-zinc-400">{{ $change['from'] }}</span>
                                        @endif
                                        @if($change['to'] !== null)
                                            @if($change['from'] !== null) ← @endif
                                            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $change['to'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
                @if($current->isNotEmpty() && $older->isEmpty())
                    @include('livewire.finance.partials.history-origin')
                @endif
            </ol>
        </td>
    </tr>
@endif
