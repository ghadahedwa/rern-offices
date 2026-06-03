<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- Welcome Banner --}}
    <div class="rounded-xl border border-[#c9a847]/30 bg-transparent p-6 flex items-center justify-between">
        <div>
            <p class="text-zinc-400 dark:text-zinc-500 text-sm mb-1">{{ __('home.welcome_back') }}</p>
            <h2 class="text-2xl font-bold text-[#b8962e] dark:text-[#c9a847]">{{ $user->name }}</h2>
            <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-1">{{ __('home.welcome_subtitle') }}</p>
        </div>
        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-full bg-[#c9a847]/10">
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

    {{-- Charts Row --}}
    @if($officesByGov->isNotEmpty() || $officesByType->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Bar Chart: توزيع المقرات على المحافظات --}}
        @if($officesByGov->isNotEmpty())
        <div class="lg:col-span-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.chart_offices_by_gov') }}
                </h3>
            </div>
            <div x-data x-init="
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
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { font: { size: 11 }, color: '#9ca3af' }, grid: { display: false } },
                            y: { ticks: { font: { size: 11 }, color: '#9ca3af' }, grid: { color: 'rgba(156,163,175,0.1)' }, beginAtZero: true }
                        }
                    }
                })
            ">
                <canvas x-ref="barChart" style="max-height: 260px;"></canvas>
            </div>
        </div>
        @endif

        {{-- Donut Chart: توزيع المقرات حسب النوع --}}
        @if($officesByType->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.chart_offices_by_type') }}
                </h3>
            </div>
            <div x-data x-init="
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
                                labels: { font: { size: 11 }, padding: 12, color: '#9ca3af', boxWidth: 12 }
                            }
                        }
                    }
                })
            ">
                <canvas x-ref="donutChart" style="max-height: 260px;"></canvas>
            </div>
        </div>
        @endif

    </div>
    @endif

    {{-- Bottom Row: Online Users + Activity Log --}}
    @if($isSuperAdmin)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @else
    <div class="grid grid-cols-1 gap-4">
    @endif

        {{-- Online Users (super-admin only) --}}
        @if($isSuperAdmin)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-4">
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
                <p class="text-sm text-zinc-400 text-center py-6">{{ __('home.online_users_empty') }}</p>
            @else
                <div class="space-y-2">
                    @foreach($onlineUsers as $u)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-100 dark:border-zinc-800 px-3 py-2.5">
                        <div class="w-8 h-8 rounded-full bg-[#c9a847]/15 flex items-center justify-center text-xs font-bold text-[#b8962e]">
                            {{ mb_substr($u->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">{{ $u->name }}</p>
                            <p class="text-xs text-zinc-400">{{ $u->ip_address }}</p>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        {{-- Activity Log --}}
        <div class="@if($isSuperAdmin) lg:col-span-2 @endif rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">

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
                    {{ $activities->links() }}
                </div>
            @endif

        </div>

    </div>

</div>
