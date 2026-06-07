<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Welcome Banner --}}
    <div class="rounded-xl border border-[#c9a847]/30 bg-transparent p-6 flex items-center justify-between">
        <div>
            <p class="text-zinc-400 dark:text-zinc-500 text-sm mb-1">{{ __('home.welcome_back') }}</p>
            <h2 class="text-2xl font-bold text-[#b8962e] dark:text-[#c9a847]">{{ $user->name }}</h2>
            <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-1">{{ __('home.welcome_subtitle') }}</p>
        </div>
        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-full border-2 border-[#c9a847]/40 bg-[#c9a847]/15 dark:bg-[#c9a847]/20">
            <flux:icon.building-office-2 variant="outline" class="w-8 h-8 text-[#b8962e] dark:text-[#c9a847]" />
        </div>
    </div>

    {{-- KPI Cards --}}
    @if($isSuperAdmin)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @else
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
    @endif

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_offices') }}</p>
                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                    <flux:icon.building-office-2 variant="outline" class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                </div>
            </div>
            <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalOffices) }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_governorates') }}</p>
                <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                    <flux:icon.map-pin variant="outline" class="w-5 h-5 text-violet-500 dark:text-violet-400" />
                </div>
            </div>
            <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalGovernorates) }}</p>
        </div>

        @if($isSuperAdmin)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.kpi_total_users') }}</p>
                <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                    <flux:icon.users variant="outline" class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
                </div>
            </div>
            <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($totalUsers) }}</p>
        </div>
        @endif

        <div class="rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/5 dark:bg-[#c9a847]/10 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-[#b8962e] dark:text-[#c9a847]">{{ __('home.kpi_added_this_month') }}</p>
                <div class="w-9 h-9 rounded-lg bg-[#c9a847]/15 flex items-center justify-center">
                    <flux:icon.calendar-days variant="outline" class="w-5 h-5 text-[#b8962e] dark:text-[#c9a847]" />
                </div>
            </div>
            <p class="text-3xl font-bold text-[#b8962e] dark:text-[#c9a847]">{{ number_format($addedThisMonth) }}</p>
        </div>

    </div>

    {{-- مقرات تحتاج زيارة --}}
    @if($needsVisitCount > 0)
    <div class="rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-900/10 p-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                <flux:icon.exclamation-triangle variant="outline" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    {{ number_format($needsVisitCount) }} {{ __('home.needs_visit_label') }}
                </p>
                <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">{{ __('home.needs_visit_desc') }}</p>
            </div>
        </div>
        <a href="{{ route('offices.index', ['needs_visit' => 1]) }}" wire:navigate
           class="shrink-0 text-xs font-medium text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 rounded-lg px-3 py-1.5 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">
            {{ __('home.needs_visit_action') }}
        </a>
    </div>
    @endif

    {{-- Bar Chart: توزيع المقرات على المحافظات (صف مستقل) --}}
    @if($officesByGov->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('home.chart_offices_by_gov') }}
            </h3>
        </div>
        <div wire:ignore x-data x-init="
            const tooltipData = {{ Js::from($govTooltipData) }};
            new Chart($refs.barChart, {
                type: 'bar',
                data: {
                    labels: {{ Js::from($officesByGov->pluck('name')) }},
                    datasets: [{
                        label: '{{ __('home.chart_offices_count') }}',
                        data: {{ Js::from($officesByGov->pluck('total')) }},
                        backgroundColor: 'rgba(201,168,71,0.75)',
                        borderColor: '#b8962e',
                        borderWidth: 1.5,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            rtl: true,
                            callbacks: {
                                title: function(items) {
                                    return items[0].label;
                                },
                                label: function(item) {
                                    return 'عدد المقرات: ' + item.parsed.y;
                                },
                                afterBody: function(items) {
                                    const d = tooltipData[items[0].dataIndex];
                                    if (!d || !Object.keys(d).length) return [];
                                    const lines = [];
                                    Object.values(d).forEach(function(s) {
                                        lines.push('— ' + s.label + ' —');
                                        s.years.forEach(function(y) {
                                            lines.push('  ' + y.year + ': ' + y.total.toLocaleString('ar-EG'));
                                        });
                                    });
                                    return lines;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: { size: 11 }, color: '#9ca3af',
                                maxRotation: 35, minRotation: 35,
                                callback: function(val) {
                                    const label = this.getLabelForValue(val);
                                    return label.length > 10 ? label.slice(0, 10) + '…' : label;
                                }
                            },
                            grid: { display: false }
                        },
                        y: { ticks: { font: { size: 11 }, color: '#9ca3af' }, grid: { color: 'rgba(156,163,175,0.1)' }, beginAtZero: true }
                    }
                }
            })
        ">
            <canvas x-ref="barChart" style="max-height: 350px;"></canvas>
        </div>
    </div>
    @endif

    {{-- Charts Row: النوع + الحالة الإنشائية --}}
    @if($officesByType->isNotEmpty() || $officesByStructure->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Donut Chart: توزيع المقرات حسب النوع --}}
        @if($officesByType->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.chart_offices_by_type') }}
                </h3>
            </div>
            <div wire:ignore x-data x-init="
                new Chart($refs.donutChart, {
                    type: 'doughnut',
                    data: {
                        labels: {{ Js::from($officesByType->pluck('name')) }},
                        datasets: [{
                            data: {{ Js::from($officesByType->pluck('total')) }},
                            backgroundColor: ['#c9a847','#6366f1','#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4'],
                            borderWidth: 2,
                            borderColor: 'transparent',
                            hoverOffset: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font: { size: 10 }, padding: 10, color: '#9ca3af', boxWidth: 10 }
                            }
                        }
                    }
                })
            ">
                <canvas x-ref="donutChart" style="max-height: 240px;"></canvas>
            </div>
        </div>
        @endif

        {{-- Horizontal Bar: توزيع المقرات حسب الحالة الإنشائية --}}
        @if($officesByStructure->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.chart_offices_by_structure') }}
                </h3>
            </div>
            <div wire:ignore x-data x-init="
                new Chart($refs.structureChart, {
                    type: 'bar',
                    data: {
                        labels: {{ Js::from($officesByStructure->pluck('name')) }},
                        datasets: [{
                            label: '{{ __('home.chart_offices_count') }}',
                            data: {{ Js::from($officesByStructure->pluck('total')) }},
                            backgroundColor: ['#10b981','#c9a847','#f59e0b','#ef4444','#dc2626'],
                            borderWidth: 0,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { font: { size: 10 }, color: '#9ca3af' }, grid: { color: 'rgba(156,163,175,0.1)' }, beginAtZero: true },
                            y: { ticks: { font: { size: 10 }, color: '#9ca3af' }, grid: { display: false } }
                        }
                    }
                })
            ">
                <canvas x-ref="structureChart" style="max-height: 240px;"></canvas>
            </div>
        </div>
        @endif

    </div>
    @endif

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

                @if($stat['latestYear'])
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

    {{-- Online Users (super-admin only) — صف أفقي --}}
    @if($isSuperAdmin)
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-4">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('home.online_users_title') }}
            </h3>
            <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ $onlineUsers->count() }}
            </span>
        </div>

        @if($onlineUsers->isEmpty())
            <p class="text-sm text-zinc-400">{{ __('home.online_users_empty') }}</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach($onlineUsers as $u)
                <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <div class="w-6 h-6 rounded-full bg-[#c9a847]/15 flex items-center justify-center text-xs font-bold text-[#b8962e]">
                        {{ mb_substr($u->name, 0, 1) }}
                    </div>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $u->name }}</span>
                    <span class="text-xs text-zinc-400">{{ $u->ip_address }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- Activity Log — صف مستقل كامل العرض --}}
    <div>
        <div wire:poll.300s class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">

            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.activity_log_title') }}
                </h3>
            </div>

            {{-- Filters --}}
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('home.search') }}..."
                    class="w-full sm:flex-1 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40"
                >
                <select
                    wire:model.live="filterEvent"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40"
                >
                    <option value="">{{ __('home.activity_filter_all') }}</option>
                    <option value="created">{{ __('home.activity_filter_created') }}</option>
                    <option value="updated">{{ __('home.activity_filter_updated') }}</option>
                    <option value="deleted">{{ __('home.activity_filter_deleted') }}</option>
                </select>
            </div>

            @if($activities->isEmpty())
                <p class="text-sm text-zinc-400 py-8 text-center">{{ __('home.activity_empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_time') }}</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_user') }}</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_action') }}</th>
                                <th class="text-right py-2 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('home.activity_col_subject') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($activities as $activity)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $activity->created_at->diffForHumans() }}</p>
                                    <p class="text-xs text-zinc-300 dark:text-zinc-600">{{ $activity->created_at->format('Y-m-d H:i') }}</p>
                                </td>
                                <td class="py-2.5 px-3 font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap text-xs">
                                    {{ optional($activity->causer)->name ?? '—' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @php
                                        $badgeClass = match($activity->event) {
                                            'created' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                            'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            default   => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                        {{ $activity->description }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-400">
                                    @if($activity->subject_type === \App\Models\Office::class && $activity->subject)
                                        <a href="{{ route('offices.show', $activity->subject_id) }}" wire:navigate
                                           class="text-[#c9a847] hover:underline text-xs">
                                            {{ $activity->subject->name ?? '#' . $activity->subject_id }}
                                        </a>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $activities->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>

    </div>

</div>
