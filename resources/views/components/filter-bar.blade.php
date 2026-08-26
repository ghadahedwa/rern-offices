{{--
    شريط فلاتر موحّد لجداول النظام.

    الفلاتر نفسها تُمرَّر في الـslot، والشريط يتكفّل بالإطار وبما يتكرّر في كل
    شاشة: زر مسح الفلاتر (يظهر حين يكون منها مفعّل)، ومنتقي عدد الصفوف.

    - :active           هل من فلتر مفعّل الآن؟ (لإظهار زر المسح)
    - :per-page-options قائمة أعداد الصفوف، أو null لإخفاء المنتقي
    - :columns          أعمدة الشبكة على الشاشة العريضة (2..5)
    - $shortcuts        slot اختياري لاختصارات الفترة
--}}
@props(['active' => false, 'perPageOptions' => null, 'columns' => 4])

@php
    // الفئات مكتوبة صريحة لا مركَّبة بالنص — Tailwind لا يرى الفئة المُركَّبة وقت البناء
    $grid = match ((int) $columns) {
        2       => 'lg:grid-cols-2',
        3       => 'lg:grid-cols-3',
        5       => 'lg:grid-cols-5',
        default => 'lg:grid-cols-4',
    };
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ $grid }} gap-4">
        {{ $slot }}
    </div>

    @isset($shortcuts)
        <div class="flex flex-wrap items-center gap-2">
            {{ $shortcuts }}
        </div>
    @endisset

    @if($active || $perPageOptions)
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 dark:border-zinc-800 pt-3">
            <div>
                @if($active)
                    <button type="button" wire:click="resetFilters"
                            class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('home.reset_filters') }}
                    </button>
                @endif
            </div>

            @if($perPageOptions)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.per_page') }}</span>
                    <select wire:model.live="perPage"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-2 py-1.5 text-xs bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    @endif
</div>
