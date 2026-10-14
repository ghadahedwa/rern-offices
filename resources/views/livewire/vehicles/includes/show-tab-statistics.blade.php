@php
    $months = \App\Livewire\Vehicles\StatTab\Documentation::MONTHS;
    $txId   = \App\Support\VehicleTransactionPeriods::STAT_TYPE_ID;
@endphp

{{-- المتوسطات اليومية --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.vstat_daily_averages') }}</h3>
    </div>
    @include('livewire.vehicles.includes.daily-averages', ['vehicle' => $vehicle])
</div>

@foreach($statTypes as $type)
@php
    $rows     = $statistics->get($type->id, collect());
    $isAmount = $type->value_type === 'amount';
    $label    = \App\Livewire\Vehicles\StatTab\Documentation::labelFor($type);
    $isTx     = $type->id === $txId;
    $showMonthCol = $isTx || $type->period === 'monthly';
@endphp

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ $label }}
            @unless($isTx)
            <span class="text-xs font-normal text-zinc-400 normal-case">· {{ $type->period === 'yearly' ? 'سنوية' : 'شهرية' }}</span>
            @endunless
        </h3>
    </div>

    @if($isTx && $yearTotals)
    <div class="flex flex-wrap gap-2 mb-3">
        @foreach($yearTotals as $y => $total)
        <span class="inline-flex items-baseline gap-1.5 px-2.5 py-1 rounded-lg bg-[#c9a847]/10 text-xs">
            <span class="text-zinc-500 dark:text-zinc-400">{{ __('home.vstat_year_total', ['year' => $y]) }}</span>
            <span class="font-semibold text-[#b8962e] dark:text-[#c9a847]">{{ number_format($total) }}</span>
        </span>
        @endforeach
    </div>
    @endif

    @if($rows->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
        <table class="w-full max-sm:whitespace-nowrap text-sm text-right max-sm:[&_th]:px-2 max-sm:[&_td]:px-2">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-4 py-2.5 font-medium">السنة</th>
                    @if($showMonthCol)<th class="px-4 py-2.5 font-medium">{{ $isTx ? __('home.vstat_period') : 'الشهر' }}</th>@endif
                    <th class="px-4 py-2.5 font-medium">{{ $isAmount ? 'المبلغ (ج)' : 'العدد' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($rows as $stat)
                <tr>
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $stat->year }}</td>
                    @if($showMonthCol)
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">
                        {{ $isTx ? \App\Support\VehicleTransactionPeriods::label($stat->year, $stat->month, $months) : ($months[$stat->month] ?? $stat->month) }}
                    </td>
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
