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
