<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_dashboard') }}</h1>
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('feedback-results.ratings') }}" wire:navigate
           class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 hover:border-[#c9a847] transition">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_total_ratings') }}</p>
            <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $kpis['ratings'] }}</p>
        </a>

        <a href="{{ route('feedback-results.suggestions') }}" wire:navigate
           class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 hover:border-[#c9a847] transition">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_total_suggestions') }}</p>
            <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $kpis['suggestions'] }}</p>
        </a>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_avg_overall') }}</p>
            @if($kpis['avg_overall'] === null)
                <p class="text-2xl font-semibold text-zinc-300 dark:text-zinc-600">—</p>
            @else
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $kpis['avg_overall'] }}</p>
                    <span class="text-xs text-zinc-400">{{ __('home.fr_of_five') }}</span>
                </div>
                <div class="mt-1">
                    @include('livewire.feedback-results.includes.stars', ['value' => $kpis['avg_overall'], 'showNumber' => false])
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_rated_offices') }}</p>
            <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $kpis['rated_offices'] }}</p>
        </div>
    </div>

    {{-- الاتجاه الشهري — يجيب على «بيتحسن ولا بيسوء؟» بعكس لقطة الفترة الواحدة --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_monthly_trend') }}</h3>
            </div>
            @if($from === '' && $to === '')
                <span class="text-xs text-zinc-400">{{ __('home.fr_last_12_months') }}</span>
            @endif
        </div>
        <p class="text-xs text-zinc-400 mb-5">{{ __('home.fr_trend_hint') }}</p>

        @if(count($trend) === 0)
            <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
        @else
            <div class="flex items-end gap-2 overflow-x-auto pb-1" style="min-height: 11rem;">
                @foreach($trend as $month)
                    <div class="flex-1 min-w-14 flex flex-col items-center justify-end gap-1">
                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $month['avg'] }}</span>
                        {{-- ارتفاع العمود = المتوسط ÷ ٥ من مساحة ٧rem --}}
                        <div class="w-full bg-[#c9a847] rounded-t transition-all"
                             style="height: {{ max(4, round($month['avg'] / 5 * 112)) }}px"
                             title="{{ $month['label'] }} — {{ $month['avg'] }}/5 ({{ $month['count'] }})"></div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ $month['label'] }}</span>
                        <span class="text-xs text-zinc-400">{{ $month['count'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- متوسط المحاور + توزيع مدة الانتظار --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_criteria_averages') }}</h3>
            </div>

            @if($kpis['ratings'] === 0)
                <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
            @else
                <div class="space-y-4">
                    @foreach($criteria as $c)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $c['label'] }}</p>
                                <span class="text-xs text-zinc-400 whitespace-nowrap">
                                    {{ __('home.fr_answered_count') }}: {{ $c['count'] }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                    <div class="h-full bg-[#c9a847] rounded-full" style="width: {{ $c['avg'] === null ? 0 : $c['avg'] * 20 }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300 w-10 text-left">
                                    {{ $c['avg'] ?? '—' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_wait_distribution') }}</h3>
            </div>

            @if($kpis['ratings'] === 0)
                <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
            @else
                <div class="space-y-4">
                    @foreach($waits as $w)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $w['label'] }}</p>
                                <span class="text-xs text-zinc-400">{{ $w['count'] }} · {{ $w['percent'] }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="h-full bg-[#c9a847] rounded-full" style="width: {{ $w['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ترتيب المقرات --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_offices_ranking') }}</h3>
        </div>

        @if($ranking['ranked_count'] === 0 && $ranking['insufficient']->isEmpty())
            <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3">{{ __('home.fr_top_offices') }}</p>
                    @forelse($ranking['top'] as $row)
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <span class="text-sm text-zinc-800 dark:text-zinc-100">{{ $row['office'] }}</span>
                            <span class="flex items-center gap-2 whitespace-nowrap">
                                @include('livewire.feedback-results.includes.stars', ['value' => $row['avg']])
                                <span class="text-xs text-zinc-400">({{ $row['count'] }})</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">{{ __('home.fr_no_data') }}</p>
                    @endforelse
                </div>

                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3">{{ __('home.fr_bottom_offices') }}</p>
                    @forelse($ranking['bottom'] as $row)
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <span class="text-sm text-zinc-800 dark:text-zinc-100">{{ $row['office'] }}</span>
                            <span class="flex items-center gap-2 whitespace-nowrap">
                                @include('livewire.feedback-results.includes.stars', ['value' => $row['avg']])
                                <span class="text-xs text-zinc-400">({{ $row['count'] }})</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">{{ __('home.fr_no_data') }}</p>
                    @endforelse
                </div>
            </div>

            @if($ranking['insufficient']->isNotEmpty())
                <div class="mt-6 pt-5 border-t border-zinc-100 dark:border-zinc-800">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_insufficient_sample') }}</p>
                    <p class="text-xs text-zinc-400 mb-3">{{ __('home.fr_insufficient_sample_hint', ['min' => $minSample]) }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($ranking['insufficient'] as $row)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                                {{ $row['office'] }}
                                <span class="text-zinc-400">({{ $row['count'] }})</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ترتيب المحافظات --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_governorates_ranking') }}</h3>
        </div>

        @if($govRanking->isEmpty())
            <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                @foreach($govRanking as $row)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-sm text-zinc-800 dark:text-zinc-100 truncate">
                            {{ $row['governorate'] }}
                            @unless($row['enough'])
                                <span class="text-xs text-zinc-400">({{ __('home.fr_insufficient_sample') }})</span>
                            @endunless
                        </span>
                        <span class="flex items-center gap-2 whitespace-nowrap">
                            @include('livewire.feedback-results.includes.stars', ['value' => $row['avg']])
                            <span class="text-xs text-zinc-400">({{ $row['count'] }})</span>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- أولويات المقترحات --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_top_topics') }}</h3>
                </div>
                <a href="{{ route('feedback-results.suggestions') }}" wire:navigate class="text-xs text-[#c9a847] hover:underline">{{ __('home.fr_view_all') }}</a>
            </div>

            @if($priority['topics']->isEmpty())
                <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
            @else
                <div class="space-y-3">
                    @foreach($priority['topics'] as $topic)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $topic['name'] }}</p>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ $topic['count'] }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="h-full bg-[#c9a847] rounded-full"
                                     style="width: {{ $priority['max'] > 0 ? round($topic['count'] * 100 / $priority['max']) : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-zinc-400 mt-0.5">{{ $topic['domain'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_domains_distribution') }}</h3>
            </div>

            @if($priority['domains']->isEmpty())
                <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
            @else
                @php $domainMax = $priority['domains']->max('count') ?: 1; @endphp
                <div class="space-y-4">
                    @foreach($priority['domains'] as $domain)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $domain['name'] }}</p>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $domain['count'] }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="h-full bg-[#c9a847] rounded-full" style="width: {{ round($domain['count'] * 100 / $domainMax) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- نصوص حرة --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_free_texts') }}</h3>
        </div>

        @if($freeTexts['notes']->isEmpty() && $freeTexts['others']->isEmpty())
            <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3">{{ __('home.fr_notes') }}</p>
                    @forelse($freeTexts['notes'] as $note)
                        <div class="py-2.5 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $note->notes }}</p>
                            <p class="text-xs text-zinc-400 mt-1">
                                {{ $note->office?->name ?? __('home.fr_deleted_office') }} · {{ \App\Support\LocalTime::date($note->created_at) }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">{{ __('home.fr_no_data') }}</p>
                    @endforelse
                </div>

                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3">{{ __('home.fr_other_suggestion') }}</p>
                    @forelse($freeTexts['others'] as $other)
                        <div class="py-2.5 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $other->other_suggestion }}</p>
                            <p class="text-xs text-zinc-400 mt-1">
                                {{ $other->office?->name ?? __('home.fr_deleted_office') }} · {{ \App\Support\LocalTime::date($other->created_at) }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">{{ __('home.fr_no_data') }}</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    {{-- ملخص المحاولات المرفوضة --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_rejected_summary') }}</h3>
            </div>
            <a href="{{ route('feedback-results.rejected') }}" wire:navigate class="text-xs text-[#c9a847] hover:underline">{{ __('home.fr_view_all') }}</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach(\App\Livewire\FeedbackResults\RejectedAttempts::REASONS as $reasonKey)
                <div class="rounded-lg border border-zinc-100 dark:border-zinc-800 p-4">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_reason_'.$reasonKey) }}</p>
                    <p class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $rejected[$reasonKey] ?? 0 }}</p>
                </div>
            @endforeach
        </div>
    </div>

</div>
