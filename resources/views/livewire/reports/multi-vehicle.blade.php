<div class="max-w-6xl mx-auto p-6 space-y-6">

    @php
        $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
        $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';

        $selBase = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
        $selText = fn ($v) => 'text-zinc-900 dark:text-zinc-100';

        $availabilityOpts = ['available' => __('home.option_available'), 'not_available' => __('home.option_not_available')];
        $generatorOpts    = ['available' => __('home.option_available'), 'not_available' => __('home.option_not_available'), 'broken' => __('home.option_broken')];
        $camerasOpts      = ['available' => __('home.option_available'), 'not_available' => __('home.option_not_available'), 'broken' => __('home.option_broken')];

        $statusColors = [
            'working'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'maintenance' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'stopped'     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        ];
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.25c.623 0 1.14.483 1.185 1.104l.42 5.4M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125h-12A1.125 1.125 0 002.25 4.875v9.375c0 .621.504 1.125 1.125 1.125H4.5"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_vehicle_multi_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_vehicle_multi_hint') }}</p>
            </div>
        </div>
        <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            {{ __('home.report_reset_filters') }}
        </button>
    </div>

    {{-- ── الفلاتر ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_basic_filters') }}</h3>
        </div>

        {{-- الصف الأول: المحافظة والسيارة --}}
        <div class="flex gap-5 items-end">
            <div style="flex: 1; min-width: 0;">
                @include('livewire.reports.includes.checkbox-group', ['field' => 'governorateIds', 'options' => $governorates->pluck('name', 'id')->all(), 'label' => __('home.governorate_name'), 'live' => true])
            </div>
            <div style="flex: 1; min-width: 0;">
                @include('livewire.reports.includes.checkbox-group', ['field' => 'vehicleIds', 'options' => $vehicleOptions->pluck('name', 'id')->all(), 'label' => __('home.report_vehicle_multi'), 'wireKey' => 'vehicleIds-' . md5($vehicleOptions->pluck('id')->implode(','))])
            </div>
        </div>

        {{-- باقي الفلاتر: 4 في الصف --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 items-start">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'typeIds', 'options' => $vehicleTypes->pluck('name', 'id')->all(), 'label' => __('home.vehicle_type')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'workSystemIds', 'options' => $workSystems->pluck('name', 'id')->all(), 'label' => __('home.vehicle_work_system')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'workingHoursIds', 'options' => $workingHoursOptions->pluck('name', 'id')->all(), 'label' => __('home.working_hours')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'brandIds', 'options' => $brands->pluck('name', 'id')->all(), 'label' => __('home.vehicle_brand')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'statuses', 'options' => \App\Models\Vehicle::STATUSES, 'label' => __('home.vehicle_status')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'generatorStatus', 'options' => $generatorOpts, 'label' => __('home.generator_status')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'cameras', 'options' => $camerasOpts, 'label' => __('home.surveillance_cameras')])
            <div>
                <label class="{{ $lbl }}">{{ __('home.mobility_bag') }}</label>
                <select wire:model="mobilityBag" class="{{ $selBase }} {{ $selText($mobilityBag) }}">
                    <option value="">{{ __('home.report_select_hint') }}</option>
                    @foreach ($availabilityOpts as $val => $text)<option value="{{ $val }}">{{ $text }}</option>@endforeach
                </select>
            </div>
        </div>

        {{-- نطاقات --}}
        <div class="flex flex-col gap-4">
            <div>
                <label class="{{ $lbl }}">{{ __('home.report_manufacture_year_range') }}</label>
                <div class="flex items-center gap-2">
                    <input wire:model="manufactureYearFrom" type="number" min="1980" max="{{ date('Y') + 1 }}" class="{{ $inp }}" aria-label="{{ __('home.report_from') }}" />
                    <span class="text-xs text-zinc-400 shrink-0">{{ __('home.report_to') }}</span>
                    <input wire:model="manufactureYearTo" type="number" min="1980" max="{{ date('Y') + 1 }}" class="{{ $inp }}" aria-label="{{ __('home.report_to') }}" />
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.vehicle_operated_at') }}</label>
                <div class="flex items-center gap-2">
                    <input wire:model="operatedAtFrom" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_from') }}" />
                    <span class="text-xs text-zinc-400 shrink-0">{{ __('home.report_to') }}</span>
                    <input wire:model="operatedAtTo" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_to') }}" />
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.license_expiry_date') }}</label>
                <div class="flex items-center gap-2">
                    <input wire:model="licenseExpiryFrom" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_from') }}" />
                    <span class="text-xs text-zinc-400 shrink-0">{{ __('home.report_to') }}</span>
                    <input wire:model="licenseExpiryTo" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_to') }}" />
                </div>
            </div>
        </div>
    </div>

    {{-- ── شريط البحث ── --}}
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

    {{-- ── النتائج ── --}}
    @if($hasSearched)
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="font-medium">{{ __('home.report_results_count') }}:</span>
                <span class="px-2.5 py-0.5 rounded-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847] font-semibold">
                    {{ $vehicles->total() }} {{ __('home.report_vehicle_unit') }}
                </span>
            </div>
        </div>

        @if($vehicles->total() > 0)
        @php
            $excelBtn = 'inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-900/20 transition disabled:opacity-50';
            $pdfBtn   = 'inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition disabled:opacity-50';
            $excelSvg = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
        @endphp

        {{-- ── التقريران: شامل / مخصّص ── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm divide-y divide-zinc-100 dark:divide-zinc-700">

            {{-- تقرير شامل --}}
            <div class="p-5 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.report_comprehensive_title') }}</h3>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ __('home.report_comprehensive_hint') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="{{ $excelBtn }}">
                        {!! $excelSvg !!}
                        {{ __('home.report_export_excel') }}
                    </button>
                    <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf" class="{{ $pdfBtn }}">
                        {!! $excelSvg !!}
                        {{ __('home.report_export_pdf') }}
                    </button>
                </div>
            </div>

            {{-- تقرير مخصّص --}}
            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.report_custom_title') }}</h3>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ __('home.report_vehicle_custom_hint') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="exportCustomExcel" wire:loading.attr="disabled" wire:target="exportCustomExcel" class="{{ $excelBtn }}">
                            {!! $excelSvg !!}
                            {{ __('home.report_export_excel') }}
                        </button>
                        <button type="button" wire:click="exportCustomPdf" wire:loading.attr="disabled" wire:target="exportCustomPdf" class="{{ $pdfBtn }}">
                            {!! $excelSvg !!}
                            {{ __('home.report_export_pdf') }}
                        </button>
                    </div>
                </div>

                {{-- منتقي الأعمدة --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4" x-data>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('home.report_custom_columns') }}</p>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full transition"
                              :class="$wire.selectedColumns.length > 12
                                  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                  : 'bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]'">
                            <span x-text="$wire.selectedColumns.length"></span> {{ __('home.report_column_unit') }}
                        </span>
                    </div>
                    <p class="text-[11px] text-red-600 dark:text-red-400 mb-3" x-cloak x-show="$wire.selectedColumns.length > 12">
                        {{ __('home.report_custom_pdf_note', ['max' => 12]) }}
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                        @foreach($customColumnGroups as $group => $cols)
                        <div>
                            <p class="text-[11px] font-semibold text-[#b8962e] dark:text-[#c9a847] mb-1.5">{{ $group }}</p>
                            <div class="space-y-1">
                                @foreach($cols as $col)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200 {{ $col['fixed'] ? 'opacity-70' : 'cursor-pointer' }}"
                                       @if($col['fixed']) title="{{ __('home.report_custom_column_locked') }}" @endif>
                                    <input type="checkbox" value="{{ $col['key'] }}" wire:model="selectedColumns"
                                           @disabled($col['fixed'])
                                           class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                                    <span>{{ $col['label'] }}</span>
                                    @if($col['fixed'])<span class="text-[10px] text-zinc-400">🔒</span>@endif
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.vehicle_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.governorate_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.vehicle_type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.vehicle_status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.license_expiry_date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($vehicles as $vehicle)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-3 text-zinc-500">{{ $vehicles->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $vehicle->governorate->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                                    {{ $vehicle->type->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$vehicle->status] ?? '' }}">
                                    {{ \App\Models\Vehicle::STATUSES[$vehicle->status] ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $vehicle->license_expiry_date?->format('Y-m-d') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_vehicles') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $vehicles->links() }}</div>
    </div>
    @else
    {{-- لم يُبحث بعد --}}
    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.report_search_prompt') }}</p>
    </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
