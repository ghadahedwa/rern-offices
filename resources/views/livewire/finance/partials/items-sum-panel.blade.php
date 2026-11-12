{{-- العناصر الثمانية مجموعةً (شهرٌ أو السنة) — بترتيب العناصر، في عمودين ١–٤ · ٥–٨ والصفر باهت.
     يتطلب: $items [الاسم => المبلغ] و$title --}}
@php
    $order = \App\Models\CourtBuildingItem::ordered()->pluck('name');
    $list  = $order->filter(fn ($n) => array_key_exists($n, $items))
        ->concat(collect(array_keys($items))->diff($order))
        ->values();
@endphp
<p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ $title }}</p>
<div class="grid grid-cols-1 sm:grid-cols-2 sm:grid-flow-col gap-x-8 gap-y-1.5 text-xs"
     style="grid-template-rows: repeat({{ (int) ceil($list->count() / 2) }}, auto)">
    @foreach($list as $name)
        <div class="flex items-baseline justify-between gap-3 border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-1">
            <span class="text-zinc-600 dark:text-zinc-300">{{ $loop->iteration }} · {{ $name }}</span>
            <span dir="ltr" class="shrink-0 {{ $items[$name] === 0 ? 'text-zinc-400' : 'font-semibold text-zinc-800 dark:text-zinc-100' }}">{{ number_format($items[$name]) }}</span>
        </div>
    @endforeach
</div>
