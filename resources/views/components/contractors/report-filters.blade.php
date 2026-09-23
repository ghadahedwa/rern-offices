{{--
    محددات تقارير الحضور: المدى الحرّ (من · إلى) + اختصارات الفترة + زرّا المسح والعرض.
    الفلاتر الخاصة بكل تقرير تُكتب في الـslot فتقع في الشبكة نفسها.

    - :auto  تُطبَّق المحددات فور تغيّرها فلا زرَّ عرض (اللوحة) — والتقارير بزرّها
             لأن استعلامها ثقيل ولا يُشغَّل مع كل ضغطة.
--}}
@props(['auto' => false])
<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-5">
    <div class="flex items-center gap-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.ct_rep_filters') }}</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('home.ct_rep_from') }}</label>
            <input type="date" wire:model{{ $auto ? '.live' : '' }}="from"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('home.ct_rep_to') }}</label>
            <input type="date" wire:model{{ $auto ? '.live' : '' }}="to"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40">
        </div>

        {{ $slot }}
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @foreach(['this_month', 'last_month', 'last_quarter', 'this_year'] as $period)
            <button type="button" wire:click="applyPeriod('{{ $period }}')"
                    class="text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-[#c9a847]/10 hover:border-[#c9a847] transition">
                {{ __('home.ct_rep_period_'.$period) }}
            </button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 dark:border-zinc-800 pt-4">
        <button type="button" wire:click="resetFilters"
                class="text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            {{ __('home.ct_rep_reset') }}
        </button>

@unless($auto)
        <button type="button" wire:click="search"
                class="inline-flex items-center justify-center gap-2 px-6 py-2 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-semibold transition shadow-sm">
            <svg wire:loading.remove wire:target="search" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
            <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ __('home.ct_rep_show') }}
        </button>
        @endunless
    </div>
</div>
