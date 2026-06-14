<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_by_type_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_by_type_hint') }}</p>
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
            @include('livewire.reports.includes.checkbox-group', ['field' => 'typeIds', 'options' => $officeTypes->pluck('name', 'id')->all(), 'label' => __('home.office_type')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'locationIds', 'options' => $locations->pluck('name', 'id')->all(), 'label' => __('home.location_description')])
        </div>

        {{-- اختيار مجموعات الأعمدة المعروضة --}}
        <div class="flex flex-wrap items-center gap-5 pt-1">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('home.report_show_groups') }}:</span>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer">
                <input type="checkbox" wire:model="showTypes" class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                <span>{{ __('home.report_show_types') }}</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer">
                <input type="checkbox" wire:model="showLocations" class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                <span>{{ __('home.report_show_locations') }}</span>
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
        $rows           = $matrix['governorates'];
        $types          = $matrix['types'];
        $locations      = $matrix['locations'];
        $typeCounts     = $matrix['typeCounts'];
        $locationCounts = $matrix['locationCounts'];
        $hasTypes       = $types->isNotEmpty();
        $hasLocations   = $locations->isNotEmpty();
        $typeColTotals  = array_fill_keys($types->pluck('id')->all(), 0);
        $locColTotals   = array_fill_keys($locations->pluck('id')->all(), 0);
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
                        @if($hasTypes)<th class="px-3 py-2 font-semibold" colspan="{{ $types->count() + 1 }}">{{ __('home.office_type') }}</th>@endif
                        @if($hasLocations)<th class="px-3 py-2 font-semibold bg-[#a85]" colspan="{{ $locations->count() + 1 }}">{{ __('home.location_description') }}</th>@endif
                    </tr>
                    <tr class="bg-[#c9a847] text-white">
                        @foreach($types as $type)<th class="px-2 py-2 font-medium">{{ $type->name }}</th>@endforeach
                        @if($hasTypes)<th class="px-2 py-2 font-semibold bg-[#b8962e]">{{ __('home.report_total') }}</th>@endif
                        @foreach($locations as $loc)<th class="px-2 py-2 font-medium bg-[#a85]">{{ $loc->name }}</th>@endforeach
                        @if($hasLocations)<th class="px-2 py-2 font-semibold bg-[#b8962e]">{{ __('home.report_total') }}</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($rows as $gov)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-2.5 text-right font-medium text-zinc-800 dark:text-zinc-100 bg-[#c9a847]/5">{{ $gov->name }}</td>
                            @php $typeRowTotal = 0; $locRowTotal = 0; @endphp
                            @foreach($types as $type)
                                @php $v = $typeCounts[$gov->id][$type->id] ?? 0; $typeColTotals[$type->id] += $v; $typeRowTotal += $v; @endphp
                                <td class="px-2 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $v }}</td>
                            @endforeach
                            @if($hasTypes)<td class="px-2 py-2.5 font-semibold text-[#b8962e] dark:text-[#c9a847] bg-[#c9a847]/5">{{ $typeRowTotal }}</td>@endif
                            @foreach($locations as $loc)
                                @php $v = $locationCounts[$gov->id][$loc->id] ?? 0; $locColTotals[$loc->id] += $v; $locRowTotal += $v; @endphp
                                <td class="px-2 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $v }}</td>
                            @endforeach
                            @if($hasLocations)<td class="px-2 py-2.5 font-semibold text-[#b8962e] dark:text-[#c9a847] bg-[#c9a847]/5">{{ $locRowTotal }}</td>@endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#c9a847]/15 font-semibold text-zinc-800 dark:text-zinc-100">
                        <td class="px-4 py-3 text-right">{{ __('home.report_total') }}</td>
                        @foreach($types as $type)<td class="px-2 py-3">{{ $typeColTotals[$type->id] }}</td>@endforeach
                        @if($hasTypes)<td class="px-2 py-3 text-[#b8962e] dark:text-[#c9a847]">{{ array_sum($typeColTotals) }}</td>@endif
                        @foreach($locations as $loc)<td class="px-2 py-3">{{ $locColTotals[$loc->id] }}</td>@endforeach
                        @if($hasLocations)<td class="px-2 py-3 text-[#b8962e] dark:text-[#c9a847]">{{ array_sum($locColTotals) }}</td>@endif
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.no_offices') }}</p>
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
