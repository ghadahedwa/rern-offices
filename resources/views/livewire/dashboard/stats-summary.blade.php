{{-- ملخص الإحصائيات --}}
<div class="space-y-3">
    <div class="flex items-center gap-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ __('home.stats_summary_title') }}
        </h3>
    </div>

    {{-- عمود واحد على الموبايل: كل بطاقة فيها مقارنة سنتين جنباً إلى جنب، فبطاقتان في الصف تتداخل أرقامهما. --}}
    <div class="grid grid-cols-2 max-sm:grid-cols-1 md:grid-cols-3 gap-4">
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
            {{-- رأسياً لا ثلاثة أعمدة: الرقم ذو الثماني خانات (13,045,898) بخط كبير كان أعرض من ثلث البطاقة
                 فيركب على العمود المجاور. الآن السنة الأحدث رقماً كبيراً بعرض البطاقة، وتحته سطر
                 السنة السابقة والمقارنة يلتفّ إن ضاق. --}}
            <div class="space-y-3 tabular-nums">

                {{-- السنة الأحدث --}}
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.stats_year') }} {{ $stat['latestYear'] }}</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($stat['latestTotal']) }}</p>
                </div>

                {{-- السنة السابقة + المقارنة --}}
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <p class="text-xs text-zinc-400">
                        {{ __('home.stats_year') }} {{ $stat['latestYear'] - 1 }}:
                        <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">{{ number_format($stat['prevTotal']) }}</span>
                    </p>
                    @if($stat['change'] !== null)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold {{ $stat['change'] >= 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' }}"
                              title="{{ __('home.stats_vs_prev_year') }}">
                            @if($stat['change'] >= 0)
                                <flux:icon.arrow-trending-up class="w-3.5 h-3.5" />
                            @else
                                <flux:icon.arrow-trending-down class="w-3.5 h-3.5" />
                            @endif
                            <span dir="ltr">{{ $stat['change'] >= 0 ? '+' : '' }}{{ $stat['change'] }}%</span>
                        </span>
                    @else
                        <span class="text-xs font-bold text-zinc-400">—</span>
                    @endif
                </div>

            </div>
            @else
                <p class="text-sm text-zinc-400 py-2">{{ __('home.stats_no_data') }}</p>
            @endif

        </div>
        @endforeach
    </div>
</div>
