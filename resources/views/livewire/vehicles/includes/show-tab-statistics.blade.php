@php
    $months = [
        1 => 'يناير',  2 => 'فبراير', 3 => 'مارس',    4 => 'أبريل',
        5 => 'مايو',   6 => 'يونيو',  7 => 'يوليو',   8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر',11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
@endphp

{{-- متوسط المعاملات اليومية للتوثيق --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.average_daily_transactions') }}</h3>
    </div>
    <div class="flex items-baseline gap-2">
        <span class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
            {{ $vehicle->avg_daily_transactions !== null ? number_format($vehicle->avg_daily_transactions) : '—' }}
        </span>
        @if($vehicle->avg_daily_transactions !== null)
        <span class="text-sm text-zinc-400">{{ __('home.avg_daily_transactions') }}</span>
        @endif
    </div>
</div>

@foreach($statTypes as $type)
@php
    $rows     = $statistics->get($type->id, collect());
    $isAmount = $type->value_type === 'amount';
    $label    = isset($statLabels[$type->id]) ? __('home.' . $statLabels[$type->id]) : $type->name;
@endphp

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ $label }}
            <span class="text-xs font-normal text-zinc-400 normal-case">· {{ $type->period === 'yearly' ? 'سنوية' : 'شهرية' }}</span>
        </h3>
    </div>

    @if($rows->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-4 py-2.5 font-medium">السنة</th>
                    @if($type->period === 'monthly')<th class="px-4 py-2.5 font-medium">الشهر</th>@endif
                    <th class="px-4 py-2.5 font-medium">{{ $isAmount ? 'المبلغ (ج)' : 'العدد' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($rows as $stat)
                <tr>
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $stat->year }}</td>
                    @if($type->period === 'monthly')
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $months[$stat->month] ?? $stat->month }}</td>
                    @endif
                    <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $isAmount ? number_format($stat->value, 2) : number_format($stat->value) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">{{ __('home.stats_no_data') }}</p>
    @endif
</div>
@endforeach
