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

        {{-- المحافظات: منسدلة تُظهر عدد المختار، فالنطاق معلومٌ بلا سطرٍ يشرحه --}}
        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button type="button" @click="open = ! open"
                    class="{{ $box }} inline-flex items-center gap-1.5 {{ $picked ? 'border-[#c9a847] text-[#b8962e]' : '' }}">
                <span>{{ $picked ? __('home.ct_dash_picked_governorates', ['count' => $picked]) : __('home.ct_rep_all_governorates') }}</span>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>

            {{-- `style="display:none"` بدل `x-cloak`: لا يحتاج قاعدة CSS في البناء --}}
            <div x-show="open" style="display: none"
                 class="absolute z-20 mt-1 w-64 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg p-2 space-y-0.5">
                @forelse($governorates as $governorate)
                    <label class="flex items-center gap-2 text-xs px-2 py-1.5 rounded-md cursor-pointer hover:bg-[#c9a847]/10 transition">
                        <input type="checkbox" wire:model.live="governorateIds" value="{{ $governorate->id }}"
                               class="rounded border-zinc-300 text-[#c9a847] focus:ring-[#c9a847]/40 shrink-0">
                        <span class="text-zinc-700 dark:text-zinc-200 truncate" title="{{ $governorate->name }}">{{ $governorate->name }}</span>
                    </label>
                @empty
                    <span class="block text-xs text-zinc-400 px-2 py-1.5">—</span>
                @endforelse
            </div>
        </div>

        @if($picked)
            <button type="button" wire:click="$set('governorateIds', [])"
                    class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-[#c9a847] transition underline">
                {{ __('home.ct_rep_clear_governorates') }}
            </button>
        @endif
    </div>
</div>
