{{--
    شريط محددات اللوحة — **مضغوط في سطر واحد**.

    ⚠️ **الفلتر يسبق ما يفلتره** (تصحيح المستخدمة ٢٠٢٣-٠٩-٢٣): وُضع أسفل الصفحة مرةً
       ليترك أول شاشةٍ للأرقام، فصار المستخدم يقرأ نتيجةً مفلترة ولا يرى ما فلترها.
       والحلّ أنه يبقى فوق **ويصغر**: صفٌّ واحد بلا عنوان قسمٍ ولا شبكةِ مربعات
       مفتوحة — لا كارتٌ طويل يدفع كل معلومة تحت حدّ الشاشة.
    ⚠️ **والمحافظات في منسدلةٍ تُظهر عددَ المختار** فيبقى النطاق معلوماً بلا سطرٍ
       إضافي فوق الأرقام يشرحه.

    المحددات تُطبَّق فوراً (`.live`) — اللوحة تُفتح لتُقرأ لا لتُملأ.
--}}
@php
    $box = 'border border-zinc-300 dark:border-zinc-600 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40';
    $picked = count($governorateIds);
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm px-4 py-3">
    <div class="flex flex-wrap items-center gap-2">

        {{-- المدى --}}
        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_from') }}</span>
        <input type="date" wire:model.live="from" class="{{ $box }}">
        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_to') }}</span>
        <input type="date" wire:model.live="to" class="{{ $box }}">

        <span class="w-px h-5 bg-zinc-200 dark:bg-zinc-700 mx-1"></span>

        {{-- اختصارات الفترة --}}
        @foreach(['this_month', 'last_month', 'last_quarter', 'this_year'] as $period)
            <button type="button" wire:click="applyPeriod('{{ $period }}')"
                    class="text-xs px-2.5 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-[#c9a847]/10 hover:border-[#c9a847] transition">
                {{ __('home.ct_rep_period_'.$period) }}
            </button>
        @endforeach

        <span class="w-px h-5 bg-zinc-200 dark:bg-zinc-700 mx-1"></span>

        {{-- المحافظات: المنسدلة نفسها التي في التقريرين (بحثٌ داخلها)، والزرّ يُظهر المختار --}}
        @include('livewire.contractors.reports.includes.governorate-picker', ['selected' => $governorateIds, 'choices' => $governorateChoices, 'compact' => true])

        @if($picked)
            <button type="button" wire:click="$set('governorateIds', [])"
                    class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-[#c9a847] transition underline">
                {{ __('home.ct_rep_clear_governorates') }}
            </button>
        @endif
    </div>
</div>
