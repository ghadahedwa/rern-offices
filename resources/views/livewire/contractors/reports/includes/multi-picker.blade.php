{{--
    منسدلة اختيارٍ متعدد ببحث — **واحدةٌ للمحافظات والمقارّ** في اللوحة والتقارير (طلب المستخدمة
    2026-09-27: المقر بطريقة المحافظة نفسها). الزرّ يُظهر المختار: اسمه إن كان واحداً، وعدده إن كانوا
    أكثر — فالنطاق معلومٌ بلا سطرٍ يشرحه.

    - $model / $searchModel   خاصيّتا المختار والبحث في المكوّن
    - $selected / $choices    المختار الآن · الخيارات بعد البحث (`filterOptions` — السيرفر بـ`ArabicText`، والمختار يبقى)
    - $label · $required      العنوان · إلزامية (الفارغ حينها «اختر…» لا «الكل»)
    - $allText · $pickText · $pickedKey · $searchText · $emptyText   نصوص الزرّ والبحث والقائمة الفارغة
    - $layout                 'grid' (قصيرة: أعمدةٌ بلا سكرول) · 'list' (طويلة: صفٌّ كامل وأعمدةٌ بسكرول، والمختار شاراتٌ تحت الزرّ)
    - $compact                شريط اللوحة: خطٌّ أصغر بلا عنوانٍ فوقه

    ⚠️ **الخانات `.live` دائماً** — الزرّ يعرض المختار من حالة السيرفر، فبلا تحديثٍ فوري يتأخّر عن الخانات.
    ⚠️ **البحث يُمسح عند إغلاق المنسدلة** — وإلا فُتحت المرة التالية على قائمةٍ ناقصة بلا سببٍ ظاهر.
--}}
@php
    $required = $required ?? false;
    $compact  = $compact ?? false;
    $layout   = $layout ?? 'grid';
    $picked   = count($selected);
    $size     = $compact ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-2 text-sm w-full';
    $box      = 'border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40';
    $byId     = collect($choices)->keyBy('id');

    $caption = match (true) {
        $picked === 1 && $byId->has((int) reset($selected)) => $byId[(int) reset($selected)]['label'],
        $picked > 0 => __($pickedKey, ['count' => $picked]),
        $required   => $pickText,
        default     => $allText,
    };
@endphp

{{-- ⚠️ الطويلة (المقارّ) **صفٌّ كامل** وقائمتها بعرضه (طلب المستخدمة 2026-09-27): في العمود الأخير
     كانت القائمة بعرض 30rem تبدأ من حافة الحقل فتخرج عن يسار الشاشة. --}}
<div class="{{ $compact ? '' : ($layout === 'list' ? 'sm:col-span-2 lg:col-span-4 min-w-0' : 'sm:col-span-2 lg:col-span-1 min-w-0') }}">
    @unless($compact)
        <div class="flex items-center justify-between gap-3 mb-1">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ $label }}
                @if($required)<span class="text-red-500">*</span>@endif
            </label>
            @if($picked)
                <button type="button" wire:click="$set('{{ $model }}', [])"
                        class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-[#c9a847] transition">
                    {{ __('home.ct_rep_clear_governorates') }}
                </button>
            @endif
        </div>
    @endunless

    <div x-data="{ open: false }"
         @click.outside="if (open) { open = false; if ($wire.{{ $searchModel }}) $wire.set('{{ $searchModel }}', '') }"
         class="relative {{ $compact ? 'inline-block' : '' }}">
        <button type="button" @click="open = ! open; if (open) $nextTick(() => $refs.search.focus())"
                class="{{ $box }} {{ $size }} inline-flex items-center justify-between gap-1.5 {{ $picked ? 'border-[#c9a847] text-[#b8962e]' : '' }}">
            <span class="truncate">{{ $caption }}</span>
            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>

        {{-- `style="display:none"` بدل `x-cloak`: لا يحتاج قاعدة CSS في البناء --}}
        <div x-show="open" style="display: none"
             class="absolute z-20 mt-1 {{ $layout === 'list' ? 'inset-x-0' : 'w-72 sm:w-[30rem] max-w-[90vw]' }} rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg p-2 space-y-1.5">
            <div class="relative">
                <svg class="w-3.5 h-3.5 absolute top-1/2 -translate-y-1/2 right-2.5 text-zinc-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="search" x-ref="search" wire:model.live.debounce.300ms="{{ $searchModel }}"
                       placeholder="{{ $searchText }}"
                       class="{{ $box }} w-full px-3 pr-8 py-1.5 text-xs">
            </div>

            {{-- ⚠️ القصيرة **أعمدةٌ لا عمودٌ بسكرول** (قرار المستخدمة في شبكة المحافظات): السكرول الداخليّ
                 يُخفي أغلب الخيارات فلا يرى المستخدم ما اختاره. والطويلة (مئات المقارّ) لا تسعها بلا سكرول،
                 فأعمدةٌ بعرض الصفّ مع سكرول — والمختار ظاهرٌ شاراتٍ تحت الزرّ، والبحث هو الأداة. --}}
            <div class="{{ $layout === 'grid' ? 'grid grid-cols-2 sm:grid-cols-3 gap-x-2 gap-y-0.5 max-h-[70vh]' : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-2 gap-y-0.5 max-h-80' }} overflow-y-auto">
                @forelse($choices as $choice)
                    <label wire:key="{{ $model }}-choice-{{ $choice['id'] }}"
                           class="flex items-center gap-2 text-xs px-2 py-1.5 rounded-md cursor-pointer hover:bg-[#c9a847]/10 transition">
                        <input type="checkbox" wire:model.live="{{ $model }}" value="{{ $choice['id'] }}"
                               class="rounded border-zinc-300 text-[#c9a847] focus:ring-[#c9a847]/40 shrink-0">
                        <span class="text-zinc-700 dark:text-zinc-200 truncate" title="{{ $choice['title'] }}">{{ $choice['label'] }}</span>
                    </label>
                @empty
                    <span class="col-span-full block text-xs text-amber-600 dark:text-amber-400 px-2 py-1.5">{{ $emptyText ?? __('home.ct_rep_no_matches') }}</span>
                @endforelse
            </div>
        </div>
    </div>

    @if($layout === 'list' && $picked > 1)
        <div class="flex flex-wrap gap-1 mt-1.5">
            @foreach($selected as $id)
                @if($byId->has((int) $id))
                    <span class="inline-flex items-center gap-1 max-w-full text-xs px-2 py-0.5 rounded-full bg-[#c9a847]/15 text-[#b8962e]">
                        <span class="truncate" title="{{ $byId[(int) $id]['title'] }}">{{ $byId[(int) $id]['label'] }}</span>
                        <button type="button" title="{{ __('home.ct_rep_remove') }}"
                                x-on:click="$wire.set('{{ $model }}', $wire.{{ $model }}.filter(v => String(v) !== '{{ (int) $id }}'))"
                                class="shrink-0 hover:text-red-600">&times;</button>
                    </span>
                @endif
            @endforeach
        </div>
    @endif
</div>
