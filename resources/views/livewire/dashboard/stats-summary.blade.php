{{-- ملخص الإحصائيات --}}
<div class="space-y-3">
    <div class="flex items-center gap-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ __('home.stats_summary_title') }}
        </h3>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @foreach($statsSummary as $stat)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">

            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">
                {{ __($stat['label']) }}
            </p>

            @if(($stat['breakdown'] ?? null) !== null)
                {{-- بطاقة مفصّلة: نماذج وحوافظ في صفوف (سنة أحدث / سابقة / مقارنة) --}}
                @if($stat['latestYear'] && $stat['breakdown']->isNotEmpty())
                <div class="space-y-3">

                    {{-- السنة الأحدث --}}
                    <div>
                        <div class="flex items-center gap-1.5 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                                <flux:icon.document-text variant="outline" class="w-3.5 h-3.5 text-[#b8962e] dark:text-[#c9a847]" />
                            </div>
                            <p class="text-xs text-zinc-400">{{ __('home.stats_year') }} {{ $stat['latestYear'] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($stat['breakdown'] as $item)
                            <div>
                                <p class="text-[11px] text-zinc-400 truncate">{{ $item['name'] }}</p>
                                <p class="text-base font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($item['latestTotal']) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @if($stat['prevYear'])
                    {{-- السنة السابقة --}}
                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-1.5 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                <flux:icon.clock variant="outline" class="w-3.5 h-3.5 text-zinc-400" />
                            </div>
                            <p class="text-xs text-zinc-400">{{ __('home.stats_year') }} {{ $stat['prevYear'] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($stat['breakdown'] as $item)
                            <div>
                                <p class="text-[11px] text-zinc-400 truncate">{{ $item['name'] }}</p>
                                <p class="text-base font-bold text-zinc-500 dark:text-zinc-400">{{ number_format($item['prevTotal']) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- المقارنة --}}
                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-1.5 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center shrink-0">
                                <flux:icon.arrow-trending-up variant="outline" class="w-3.5 h-3.5 text-emerald-500" />
                            </div>
                            <p class="text-xs text-zinc-400">{{ __('home.stats_vs_prev_year') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($stat['breakdown'] as $item)
                            <div>
                                <p class="text-[11px] text-zinc-400 truncate">{{ $item['name'] }}</p>
                                @if($item['change'] !== null)
                                    <p class="text-base font-bold flex items-center gap-1 {{ $item['change'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                        @if($item['change'] >= 0)
                                            <flux:icon.arrow-trending-up class="w-3.5 h-3.5" />
                                        @else
                                            <flux:icon.arrow-trending-down class="w-3.5 h-3.5" />
                                        @endif
                                        {{ $item['change'] >= 0 ? '+' : '' }}{{ $item['change'] }}%
                                    </p>
                                @else
                                    <p class="text-base font-bold text-zinc-400">—</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
                @else
                    <p class="text-sm text-zinc-400 py-2">{{ __('home.stats_no_data') }}</p>
                @endif
            @elseif($stat['latestYear'])
            <div class="grid grid-cols-3 gap-3">

                {{-- السنة الأحدث --}}
                <div class="flex items-start gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0 mt-0.5">
                        <flux:icon.document-text variant="outline" class="w-4 h-4 text-[#b8962e] dark:text-[#c9a847]" />
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.stats_year') }} {{ $stat['latestYear'] }}</p>
                        <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($stat['latestTotal']) }}</p>
                    </div>
                </div>

                {{-- السنة السابقة --}}
                <div class="flex items-start gap-2">
                    <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0 mt-0.5">
                        <flux:icon.clock variant="outline" class="w-4 h-4 text-zinc-400" />
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.stats_year') }} {{ $stat['latestYear'] - 1 }}</p>
                        <p class="text-xl font-bold text-zinc-500 dark:text-zinc-400">{{ number_format($stat['prevTotal']) }}</p>
                    </div>
                </div>

                {{-- المقارنة --}}
                <div class="flex items-start gap-2">
                    <div class="{{ $stat['change'] === null ? 'bg-zinc-100 dark:bg-zinc-800' : ($stat['change'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20') }} w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        @if($stat['change'] === null)
                            <flux:icon.minus variant="outline" class="w-4 h-4 text-zinc-400" />
                        @elseif($stat['change'] >= 0)
                            <flux:icon.arrow-trending-up variant="outline" class="w-4 h-4 text-emerald-500" />
                        @else
                            <flux:icon.arrow-trending-down variant="outline" class="w-4 h-4 text-red-500" />
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.stats_vs_prev_year') }}</p>
                        @if($stat['change'] !== null)
                            <p class="text-xl font-bold {{ $stat['change'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $stat['change'] >= 0 ? '+' : '' }}{{ $stat['change'] }}%
                            </p>
                        @else
                            <p class="text-xl font-bold text-zinc-400">—</p>
                        @endif
                    </div>
                </div>

            </div>
            @else
                <p class="text-sm text-zinc-400 py-2">{{ __('home.stats_no_data') }}</p>
            @endif

        </div>
        @endforeach
    </div>
</div>
