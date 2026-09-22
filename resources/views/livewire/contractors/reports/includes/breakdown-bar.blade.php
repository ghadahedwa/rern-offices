{{--
    تفكيك أيام العمل فوق الجدول.

    ⚠️ يُعرض التفكيك لا الرقم النهائي وحده — رقمٌ خاطئ في المعادلة يُرى بالعين
       ساعتها بدل أن يمرّ في تقريرٍ يبدو سليماً.
--}}
<div class="rounded-xl border border-[#c9a847]/30 bg-[#c9a847]/5 p-4 space-y-2">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
        <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $period }}</span>
        <span class="text-zinc-600 dark:text-zinc-300">
            {{ __('home.ct_rep_breakdown', [
                'total'    => $breakdown['total'],
                'weekend'  => $breakdown['weekend'],
                'holidays' => $breakdown['holidays'],
                'working'  => $breakdown['working'],
            ]) }}
        </span>
    </div>

    @if(!empty($holidays))
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            <span class="font-medium">{{ __('home.ct_rep_holidays_in_range') }}:</span>
            {{ implode(' · ', array_unique(array_values($holidays))) }}
        </p>
    @endif

    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.ct_rep_unreviewed_hint') }}</p>
</div>
