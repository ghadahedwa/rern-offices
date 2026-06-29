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
        const debtData = {{ Js::from($govDebtTooltip) }};
        const debtLabel = {{ Js::from(__('home.claims_debt_total')) }};
        const curLabel = {{ Js::from(__('home.claims_currency')) }};
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
                                const idx = items[0].dataIndex;
                                const lines = [];
                                const d = tooltipData[idx];
                                if (d && Object.keys(d).length) {
                                    Object.values(d).forEach(function(s) {
                                        lines.push('— ' + s.label + ' —');
                                        s.years.forEach(function(y) {
                                            lines.push('  ' + y.year + ': ' + y.total.toLocaleString('ar-EG'));
                                        });
                                    });
                                }
                                if (debtData.length && debtData[idx] !== undefined && debtData[idx] !== null) {
                                    lines.push('— ' + debtLabel + ' —');
                                    lines.push('  ' + Number(debtData[idx]).toLocaleString('ar-EG') + ' ' + curLabel);
                                }
                                return lines;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { size: 11 }, color: '#9ca3af',
                            maxRotation: 90, minRotation: 90,
                            autoSkip: false,
                            callback: function(val) {
                                const label = this.getLabelForValue(val);
                                return label.length > 12 ? label.slice(0, 12) + '…' : label;
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
