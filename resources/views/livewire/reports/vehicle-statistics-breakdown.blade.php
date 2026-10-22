<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('livewire.partials.no-governorates-notice')

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_vehicle_breakdown_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_vehicle_breakdown_hint') }}</p>
            </div>
        </div>
        <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            <span class="max-sm:hidden">{{ __('home.report_reset_filters') }}</span>
        </button>
    </div>

    {{-- الفلاتر --}}
    @php $sel = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]'; @endphp
    @php $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5'; @endphp
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_basic_filters') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.vbd_metric') }}</label>
                <select wire:model="metric" class="{{ $sel }}">
                    @foreach(\App\Reports\VehicleStatisticsBreakdown::METRICS as $m)
                        <option value="{{ $m }}">{{ __('home.vbd_metric_' . $m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.vbd_level') }}</label>
                {{-- سيارةٌ مختارة تُغني عن التجميع: التقرير لها وحدها --}}
                <select wire:model="level" class="{{ $sel }} disabled:opacity-50" @disabled($vehicleId !== '')>
                    @foreach(\App\Reports\VehicleStatisticsBreakdown::LEVELS as $l)
                        <option value="{{ $l }}">{{ __('home.vbd_level_' . $l) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.vbd_period') }}</label>
                <select wire:model.live="period" class="{{ $sel }}">
                    @foreach(\App\Reports\VehicleStatisticsBreakdown::PERIODS as $p)
                        <option value="{{ $p }}">{{ __('home.vbd_period_' . $p) }}</option>
                    @endforeach
                </select>
            </div>
            {{-- السنة للشهري وحده: السنوي يعرض السنين كلها --}}
            @if($period === 'monthly')
            <div>
                <label class="{{ $lbl }}">{{ __('home.vstat_report_year') }}</label>
                <select wire:model="year" class="{{ $sel }}">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- منسدلة المحافظات نفسها التي في لوحة العاملين بالتعاقد (بحث + خانات live + «تحديد الكل») —
                 والخانات live فتضيق منسدلة السيارات معها --}}
            @include('livewire.contractors.reports.includes.multi-picker', [
                'model'       => 'governorateIds',
                'searchModel' => 'governorateSearch',
                'selected'    => $governorateIds,
                'choices'     => $governorateChoices,
                'layout'      => 'grid',
                'selectAll'   => true,
                'label'       => __('home.governorate_name'),
                'allText'     => __('home.ct_rep_all_governorates'),
                'pickText'    => __('home.ct_rep_pick_governorates'),
                'pickedKey'   => 'home.ct_dash_picked_governorates',
                'searchText'  => __('home.ct_rep_search_governorate'),
            ])
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="{{ $lbl }}">{{ __('home.vbd_vehicle') }}</label>
                <select wire:model.live="vehicleId" class="{{ $sel }}">
                    <option value="">{{ __('home.vbd_vehicle_all') }}</option>
                    @foreach($vehicles as $govName => $govVehicles)
                        <optgroup label="{{ $govName }}">
                            @foreach($govVehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ __('home.vbd_vertical_hint') }}</p>
            </div>
        </div>
    </div>

    {{-- زر البحث --}}
    <div class="flex items-center justify-center gap-3">
        <button type="button" wire:click="search"
                class="inline-flex items-center justify-center gap-2 px-8 py-2.5 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-semibold transition shadow-sm">
            <svg wire:loading.remove wire:target="search" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ __('home.report_search_btn') }}
        </button>
    </div>

    {{-- النتائج --}}
    @if($report && $report->isUnavailable())
    <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6 text-center">
        <p class="text-sm text-amber-800 dark:text-amber-300">{{ __('home.vbd_tx_monthly_unavailable') }}</p>
    </div>
    @elseif($report)
    @php
        $groups    = $report->groups();
        $grand     = $report->grandTotal();
        $columns   = $report->columns();
        $byVehicle = $report->level() === 'vehicle';
        $fmt       = fn ($v) => $v === null ? '—' : number_format($v);
        $num       = 'px-2 py-2.5 text-center whitespace-nowrap';
        $sticky    = 'sticky right-0 z-10';
    @endphp
    <div class="space-y-3">
        @if($groups->isNotEmpty())
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                @if($report->isVertical()){{ $report->subjectLabel() }} — @endif{{ $report->metricLabel() }} — {{ $report->periodLabel() }}
            </p>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-900/20 transition disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('home.report_export_excel') }}
                </button>
                <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('home.report_export_pdf') }}
                </button>
            </div>
        </div>

        @if($report->isVertical())
        {{-- الشكل الرأسي: سيارةٌ أو محافظةٌ واحدة — الفترات صفوف، وعائلة المؤشر أعمدة (يدخل عرض الشاشة) --}}
        @php $vt = $report->vertical(); $series = $report->series(); @endphp
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="text-xs">
                    <tr class="bg-[#c9a847] text-white">
                        <th class="px-3 py-3 font-semibold">{{ $report->period() === 'monthly' ? __('home.vbd_col_month') : __('home.vbd_col_year') }}</th>
                        @foreach($series as $m)
                        <th class="px-2 py-3 text-center {{ $m === 'trips_all' ? 'bg-[#b8962e]' : '' }}">{{ \App\Reports\VehicleStatisticsBreakdown::seriesLabel($m) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($vt['rows'] as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-3 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</td>
                        @foreach($series as $m)
                        <td class="{{ $num }} {{ $m === 'trips_all' ? 'font-semibold bg-[#c9a847]/5' : '' }} text-zinc-700 dark:text-zinc-200">{{ $fmt($row['values'][$m]) }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#ede3c2] dark:bg-zinc-700 font-bold text-zinc-800 dark:text-zinc-100">
                        <td class="px-3 py-3">
                            {{ __('home.vbd_col_total') }}
                            @if($vt['vehicles'] > 1)
                            <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-300">{{ __('home.vbd_reporting_of', ['n' => $vt['reporting'], 'm' => $vt['vehicles']]) }}</span>
                            @endif
                        </td>
                        @foreach($series as $m)
                        <td class="{{ $num }}">{{ $fmt($vt['total'][$m]) }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        {{-- جدول متقاطع (عمود لكل شهر/سنة): الاستثناء المقصود من «يدخل عرض الشاشة» — يتمرّر داخل إطاره والاسم ثابت --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="text-xs">
                    <tr class="bg-[#c9a847] text-white">
                        <th class="{{ $sticky }} bg-[#c9a847] px-3 py-3 font-semibold min-w-40">{{ $byVehicle ? __('home.vbd_level_vehicle') : __('home.vbd_level_governorate') }}</th>
                        @unless($byVehicle)
                        <th class="px-2 py-3 text-center whitespace-nowrap">{{ __('home.vbd_col_vehicles') }}</th>
                        <th class="px-2 py-3 text-center whitespace-nowrap">{{ __('home.vbd_col_reporting') }}</th>
                        @endunless
                        @foreach($columns as $label)
                        <th class="px-2 py-3 text-center whitespace-nowrap">{{ $label }}</th>
                        @endforeach
                        <th class="px-3 py-3 text-center whitespace-nowrap bg-[#b8962e]">{{ __('home.vbd_col_total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($groups as $group)
                        @php $t = $group['total']; $govName = $group['governorate']->name ?? '—'; @endphp
                        @if($byVehicle)
                            <tr class="bg-zinc-50 dark:bg-zinc-800">
                                <td colspan="{{ count($columns) + 2 }}" class="px-3 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                    <span class="sticky right-3">{{ $govName }}</span>
                                </td>
                            </tr>
                            @foreach($group['vehicleRows'] as $row)
                                @php $v = $row['vehicle']; @endphp
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                    <td class="{{ $sticky }} bg-white dark:bg-zinc-900 px-3 py-2.5">
                                        <a href="{{ route('vehicles.show', $v) }}" wire:navigate
                                           class="font-medium text-zinc-800 dark:text-zinc-100 hover:text-[#b8962e]">{{ $v->name }}</a>
                                    </td>
                                    @foreach($columns as $key => $label)
                                    <td class="{{ $num }} text-zinc-700 dark:text-zinc-200">{{ $fmt($row['cells'][$key]) }}</td>
                                    @endforeach
                                    <td class="{{ $num }} font-semibold text-zinc-800 dark:text-zinc-100 bg-[#c9a847]/5">{{ $fmt($row['total']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-[#f5efdc] dark:bg-zinc-800 font-semibold text-zinc-700 dark:text-zinc-200 text-xs">
                                <td class="{{ $sticky }} bg-[#f5efdc] dark:bg-zinc-800 px-3 py-2">
                                    {{ __('home.vstat_gov_total', ['name' => $govName]) }}
                                    <span class="block font-normal text-zinc-500 dark:text-zinc-400">{{ __('home.vbd_reporting_of', ['n' => $t['reporting'], 'm' => $t['vehicles']]) }}</span>
                                </td>
                                @foreach($columns as $key => $label)
                                <td class="{{ $num }}">{{ $fmt($t['cells'][$key]) }}</td>
                                @endforeach
                                <td class="{{ $num }}">{{ $fmt($t['total']) }}</td>
                            </tr>
                        @else
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="{{ $sticky }} bg-white dark:bg-zinc-900 px-3 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $govName }}</td>
                                <td class="{{ $num }} text-zinc-500 dark:text-zinc-400">{{ $t['vehicles'] }}</td>
                                <td class="{{ $num }} {{ $t['reporting'] < $t['vehicles'] ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $t['reporting'] }}</td>
                                @foreach($columns as $key => $label)
                                <td class="{{ $num }} text-zinc-700 dark:text-zinc-200">{{ $fmt($t['cells'][$key]) }}</td>
                                @endforeach
                                <td class="{{ $num }} font-semibold text-zinc-800 dark:text-zinc-100 bg-[#c9a847]/5">{{ $fmt($t['total']) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#ede3c2] dark:bg-zinc-700 font-bold text-zinc-800 dark:text-zinc-100">
                        <td class="{{ $sticky }} bg-[#ede3c2] dark:bg-zinc-700 px-3 py-3">
                            {{ __('home.vbd_grand_total') }}
                            @if($byVehicle)
                            <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-300">{{ __('home.vbd_reporting_of', ['n' => $grand['reporting'], 'm' => $grand['vehicles']]) }}</span>
                            @endif
                        </td>
                        @unless($byVehicle)
                        <td class="{{ $num }}">{{ $grand['vehicles'] }}</td>
                        <td class="{{ $num }}">{{ $grand['reporting'] }}</td>
                        @endunless
                        @foreach($columns as $key => $label)
                        <td class="{{ $num }}">{{ $fmt($grand['cells'][$key]) }}</td>
                        @endforeach
                        <td class="{{ $num }} text-[#b8962e] dark:text-[#c9a847]">{{ $fmt($grand['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.vbd_footnote') }}</p>
        @else
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.no_vehicles') }}</p>
        </div>
        @endif
    </div>
    @else
    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.report_search_prompt') }}</p>
    </div>
    @endif

    {{-- keepalive --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
