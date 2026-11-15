{{-- شريط «المطلوب إدخاله الآن» أعلى صفحة الوحدة — كارتٌ لكل حساب بحالة دفتره (`IsAccountPage::unitStrip`)،
     والتبويبات تحته للتفاصيل. على الموبايل كارتان في الصفّ. --}}
@php
    $doneCount = collect($unitStrip)->where('state', 'done')->count();
    $badge     = 'w-5 h-5 rounded-full text-xs flex items-center justify-center shrink-0';
@endphp
<div>
    <div class="flex flex-wrap items-center gap-3 mb-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ __('home.fin_strip_title') }}</h3>
        <span class="text-xs text-zinc-400">{{ __('home.fin_strip_count', ['done' => $doneCount, 'count' => count($unitStrip)]) }}</span>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($unitStrip as $card)
            @if($card['state'] === 'done')
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/10 p-4">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $card['label'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.fin_strip_through', ['month' => $card['month']]) }}</p>
                    <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                        <span class="{{ $badge }} bg-emerald-600 text-white">✓</span> {{ __('home.fin_strip_done') }}
                    </p>
                    @if($card['amount'] !== null)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            @if($card['ledger']) {{ __('home.fin_strip_balance') }} @endif
                            <span dir="ltr">{{ number_format($card['amount']) }}</span>
                        </p>
                    @endif
                    <p class="text-xs text-zinc-400 mt-1">{{ __('home.fin_strip_opens', ['month' => $card['opens']]) }}</p>
                </div>
            @else
                <div class="rounded-xl border-2 border-[#c9a847] bg-white dark:bg-zinc-900 p-4 shadow-sm">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $card['label'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $card['month'] }}</p>
                    @if($card['state'] === 'opening')
                        <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 dark:text-amber-400">
                            <span class="{{ $badge }} bg-amber-100 dark:bg-amber-900/40">!</span> {{ __('home.fin_strip_opening_first') }}
                        </p>
                        @if($card['url'])
                            <a href="{{ $card['url'] }}" wire:navigate
                               class="mt-2 block text-center border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-sm font-medium px-3 py-1.5 rounded-lg transition">{{ __('home.fin_strip_opening_write') }}</a>
                        @endif
                    @else
                        <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 dark:text-amber-400">
                            <span class="{{ $badge }} bg-amber-100 dark:bg-amber-900/40">⏳</span> {{ __('home.fin_strip_pending') }}
                        </p>
                        @if($card['url'])
                            <a href="{{ $card['url'] }}" wire:navigate
                               class="mt-2 block text-center bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-3 py-1.5 rounded-lg transition">{{ __('home.fin_strip_enter', ['month' => $card['month']]) }}</a>
                        @endif
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</div>
