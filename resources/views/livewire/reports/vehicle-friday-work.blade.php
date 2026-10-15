<div class="max-w-5xl mx-auto p-6 space-y-6">

    @include('livewire.partials.no-governorates-notice')

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_vehicle_friday_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_vehicle_friday_hint') }}</p>
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
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_basic_filters') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'governorateIds', 'options' => $governorates->pluck('name', 'id')->all(), 'label' => __('home.governorate_name')])
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('home.works_on_friday') }}</label>
                <select wire:model="friday"
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                    <option value="">{{ __('home.report_friday_all') }}</option>
                    @foreach($labels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
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
    @if($hasSearched)
    @php
        $keys      = array_keys($labels);
        $colTotals = array_fill_keys($keys, 0);
        foreach ($rows as $gov) {
            foreach ($keys as $k) { $colTotals[$k] += $counts[$gov->id][$k] ?? 0; }
        }
        $grandTotal = array_sum($colTotals);
        $cellColor  = [
            'works'     => 'text-green-600 dark:text-green-400',
            'not_works' => 'text-red-600 dark:text-red-400',
            'unset'     => 'text-zinc-500 dark:text-zinc-400',
        ];
        $badge = [
            'works'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'not_works' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'unset'     => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
        ];
    @endphp
    <div class="space-y-6">
        @if($rows->isNotEmpty() && $grandTotal > 0)
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

        @if($colTotals['unset'] > 0)
        <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
            {{ __('home.report_friday_unset_note', ['count' => $colTotals['unset']]) }}
        </div>
        @endif

        {{-- الملخص حسب المحافظة --}}
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_friday_summary') }}</h3>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                <table class="w-full text-sm text-center table-fixed max-sm:[&_th]:px-1.5 max-sm:[&_td]:px-1.5">
                    <thead class="text-xs">
                        <tr class="bg-[#c9a847] text-white">
                            <th class="w-[32%] px-4 py-2.5 font-semibold text-right">{{ __('home.governorate_name') }}</th>
                            @foreach($labels as $label)
                                <th class="w-[17%] px-3 py-2.5 font-medium">{{ $label }}</th>
                            @endforeach
                            <th class="w-[17%] px-3 py-2.5 font-semibold bg-[#b8962e]">{{ __('home.report_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($rows as $gov)
                            @php $rowTotal = array_sum($counts[$gov->id] ?? []); @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-4 py-2.5 text-right font-medium text-zinc-800 dark:text-zinc-100 bg-[#c9a847]/5 sm:truncate" title="{{ $gov->name }}">{{ $gov->name }}</td>
                                @foreach($keys as $k)
                                    @php $v = $counts[$gov->id][$k] ?? 0; @endphp
                                    <td class="px-3 py-2.5 font-medium {{ $v > 0 ? $cellColor[$k] : 'text-zinc-300 dark:text-zinc-600' }}">{{ $v }}</td>
                                @endforeach
                                <td class="px-3 py-2.5 font-semibold text-[#b8962e] dark:text-[#c9a847] bg-[#c9a847]/5">{{ $rowTotal }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#c9a847]/15 font-semibold text-zinc-800 dark:text-zinc-100">
                            <td class="px-4 py-3 text-right">{{ __('home.report_total') }}</td>
                            @foreach($keys as $k)
                                <td class="px-3 py-3">{{ $colTotals[$k] }}</td>
                            @endforeach
                            <td class="px-3 py-3 text-[#b8962e] dark:text-[#c9a847]">{{ $grandTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- قائمة السيارات --}}
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('home.report_friday_list') }}
                    <span class="text-xs font-normal text-zinc-400 normal-case">· {{ $vehicles->count() }}</span>
                </h3>
            </div>
            @if($vehicles->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                <table class="w-full text-sm text-right sm:table-fixed max-sm:[&_th]:px-1.5 max-sm:[&_td]:px-1.5">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                        <tr>
                            <th class="w-[6%] px-3 py-2.5 font-medium text-center max-sm:hidden">#</th>
                            @include('livewire.partials.sortable-th', ['column' => 'governorate', 'label' => __('home.governorate_name'), 'thClass' => 'w-[24%] max-sm:hidden'])
                            @include('livewire.partials.sortable-th', ['column' => 'name', 'label' => __('home.vehicle_name'), 'thClass' => 'w-[34%]'])
                            @include('livewire.partials.sortable-th', ['column' => 'type', 'label' => __('home.vehicle_type'), 'thClass' => 'w-[18%] max-sm:hidden'])
                            @include('livewire.partials.sortable-th', ['column' => 'friday', 'label' => __('home.works_on_friday'), 'thClass' => 'w-[18%] text-center'])
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($vehicles as $i => $vehicle)
                            @php $k = \App\Reports\VehicleFridayWork::keyOf($vehicle->works_on_friday); @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-3 py-2.5 text-center text-zinc-400 max-sm:hidden">{{ $i + 1 }}</td>
                                <td class="px-3 py-2.5 text-zinc-600 dark:text-zinc-300 sm:truncate max-sm:hidden" title="{{ $vehicle->governorate->name ?? '' }}">{{ $vehicle->governorate->name ?? '—' }}</td>
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" wire:navigate
                                       class="block font-medium text-zinc-800 dark:text-zinc-100 hover:text-[#b8962e] sm:truncate" title="{{ $vehicle->name }}">{{ $vehicle->name }}</a>
                                    <span class="sm:hidden block text-xs text-zinc-400 mt-0.5">{{ $vehicle->governorate->name ?? '—' }} · {{ $vehicle->type->name ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-zinc-600 dark:text-zinc-300 sm:truncate max-sm:hidden" title="{{ $vehicle->type->name ?? '' }}">{{ $vehicle->type->name ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $badge[$k] }}">{{ $labels[$k] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-8 text-center">
                <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.no_vehicles') }}</p>
            </div>
            @endif
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
