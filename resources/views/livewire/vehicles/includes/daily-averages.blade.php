{{-- المتوسطات اليومية الثلاثة — مشترك بين تاب الإحصائيات (الإدخال) وشاشة عرض السيارة --}}
@php
    $averages = [
        ['label' => __('home.average_daily_transactions'), 'value' => $vehicle->avg_daily_transactions, 'unit' => __('home.vstat_unit_transaction')],
        ['label' => __('home.average_daily_support'),      'value' => $vehicle->avg_daily_support,      'unit' => __('home.vstat_unit_support')],
        ['label' => __('home.average_daily_trips'),        'value' => $vehicle->avg_daily_trips,        'unit' => __('home.vstat_unit_trip')],
    ];
@endphp
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    @foreach($averages as $avg)
    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ $avg['label'] }}</p>
        <div class="flex items-baseline gap-1.5">
            <span class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $avg['value'] !== null ? number_format($avg['value']) : '—' }}</span>
            @if($avg['value'] !== null)
            <span class="text-xs text-zinc-400">{{ $avg['unit'] }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
