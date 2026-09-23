{{--
    مخطط أعمدة للوحة الفرع (Chart.js من الـCDN في `partials/head`).

    ⚠️ **`wire:ignore` مع `wire:key` يتغيّر بالبيانات**: بدون `wire:ignore` يستبدل
       Livewire الـcanvas في كل عرضٍ فيُتلِف المخطط، وبـ`wire:ignore` وحده **يتجمّد
       المخطط على أرقام أول تحميل** ولا يتبع منتقي الفترة — وهو أسوأ من غيابه: رقمٌ
       قديم بجوار فلترٍ جديد يُقرأ على أنه نتيجته. فالمفتاح يحمل بصمة البيانات،
       فيُستبدل العنصر ويُعاد بناء المخطط حين تتغيّر وحدها.
--}}
@php
    $labels = array_column($rows, 'label');
    $values = array_column($rows, 'value');
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ $title }}</h3>
    </div>

    @if($rows === [])
        <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-8">{{ __('home.ct_dash_no_data') }}</p>
    @else
        <div wire:ignore wire:key="chart-{{ $name }}-{{ md5(json_encode($rows)) }}"
             x-data x-init="
                // canvas جديد في كل مرة، والتدمير احتياطٌ ضد إعادة استعمالٍ لاحق
                window.Chart?.getChart($refs.bar)?.destroy();
                new Chart($refs.bar, {
                    type: 'bar',
                    data: {
                        labels: {{ Js::from($labels) }},
                        datasets: [{
                            label: {{ Js::from($title) }},
                            data: {{ Js::from($values) }},
                            backgroundColor: 'rgba(201,168,71,0.75)',
                            borderColor: '#b8962e',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                            x: { ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 } },
                        },
                    },
                });
             ">
            <canvas x-ref="bar" style="max-height: {{ $height ?? 300 }}px; height: {{ $height ?? 300 }}px;"></canvas>
        </div>
    @endif
</div>
