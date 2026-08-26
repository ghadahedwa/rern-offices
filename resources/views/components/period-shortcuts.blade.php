{{--
    اختصارات فترة جاهزة (هذا الشهر · آخر ٣ شهور · هذه السنة).
    تغني عن اختيار تاريخين يدوياً في الحالات الشائعة، والضغط على المفعّل يلغيه.
    يحتاج المكوّن App\Livewire\Concerns\WithDateRange.
--}}
@props(['options', 'active' => null])

@foreach($options as $period)
    <button type="button" wire:click="setPeriod('{{ $period }}')"
            class="px-3 py-1.5 rounded-full text-xs font-medium border transition
                {{ $active === $period
                    ? 'border-[#c9a847] bg-[#c9a847] text-white'
                    : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
        {{ __('home.period_'.$period) }}
    </button>
@endforeach
