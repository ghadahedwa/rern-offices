<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_vehicle_devices_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_vehicle_devices_hint') }}</p>
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

    {{-- الفلاتر --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_basic_filters') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'governorateIds', 'options' => $governorates->pluck('name', 'id')->all(), 'label' => __('home.governorate_name')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'workingDevices', 'options' => $deviceOptions, 'label' => __('home.report_vehicle_devices_working_filter')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'brokenTypeIds', 'options' => $brokenTypeOptions->pluck('name', 'id')->all(), 'label' => __('home.report_devices_broken_filter')])
        </div>
        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.report_devices_all_hint') }}</p>

        {{-- اختيار مجموعات الأعمدة المعروضة --}}
        <div class="flex flex-wrap items-center gap-5 pt-1">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('home.report_show_groups') }}:</span>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer">
                <input type="checkbox" wire:model="showWorking" class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                <span>{{ __('home.report_vehicle_devices_show_working') }}</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer">
                <input type="checkbox" wire:model="showBroken" class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                <span>{{ __('home.report_devices_show_broken') }}</span>
            </label>
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
    @if($hasSearched)
    @php
        $rows        = $matrix['governorates'];
        $workingCols = $matrix['workingCols'];
        $brokenTypes = $matrix['brokenTypes'];
        $sums        = $matrix['sums'];
        $brokenSums  = $matrix['brokenSums'];
        $workingKeys = array_keys($workingCols);
        $hasWorking  = ! empty($workingCols);
        $hasBroken   = $brokenTypes->isNotEmpty();
        $wColTotals  = array_fill_keys($workingKeys, 0);
        $bColTotals  = array_fill_keys($brokenTypes->pluck('id')->all(), 0);
    @endphp
    <div class="space-y-3">
        @if($rows->isNotEmpty())
        <div class="flex items-center justify-end gap-2">
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

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full text-sm text-center whitespace-nowrap">
                <thead class="text-xs">
                    <tr class="bg-[#c9a847] text-white">
                        <th class="px-4 py-2 font-semibold text-right" rowspan="2">{{ __('home.governorate_name') }}</th>
                        @if($hasWorking)
                            <th class="px-3 py-2 font-semibold" colspan="{{ count($workingCols) }}">{{ __('home.report_vehicle_devices_working') }}</th>
                        @endif
                        @if($hasBroken)
                            <th class="px-3 py-2 font-semibold bg-[#a85]" colspan="{{ $brokenTypes->count() }}">{{ __('home.report_devices_broken_group') }}</th>
                        @endif
                    </tr>
                    <tr class="bg-[#c9a847] text-white">
                        @foreach($workingCols as $col => $label)
                            <th class="px-2 py-2 font-medium">{{ $label }}</th>
                        @endforeach
                        @if($hasBroken)
                            @foreach($brokenTypes as $type)
                                <th class="px-2 py-2 font-medium bg-[#a85]">{{ $type->name }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($rows as $gov)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-2.5 text-right font-medium text-zinc-800 dark:text-zinc-100 bg-[#c9a847]/5">{{ $gov->name }}</td>
                            @foreach($workingKeys as $col)
                                @php $v = $sums[$gov->id][$col] ?? 0; $wColTotals[$col] += $v; @endphp
                                <td class="px-2 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $v }}</td>
                            @endforeach
                            @if($hasBroken)
                                @foreach($brokenTypes as $type)
                                    @php $v = $brokenSums[$gov->id][$type->id] ?? 0; $bColTotals[$type->id] += $v; @endphp
                                    <td class="px-2 py-2.5 text-red-600 dark:text-red-400">{{ $v }}</td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#c9a847]/15 font-semibold text-zinc-800 dark:text-zinc-100">
                        <td class="px-4 py-3 text-right">{{ __('home.report_total') }}</td>
                        @foreach($workingKeys as $col)
                            <td class="px-2 py-3">{{ $wColTotals[$col] }}</td>
                        @endforeach
                        @if($hasBroken)
                            @foreach($brokenTypes as $type)
                                <td class="px-2 py-3 text-red-600 dark:text-red-400">{{ $bColTotals[$type->id] }}</td>
                            @endforeach
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
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
