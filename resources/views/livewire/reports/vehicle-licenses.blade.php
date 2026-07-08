<div class="max-w-5xl mx-auto p-6 space-y-6">

    @php
        $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
        $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';
        $statusBadge = [
            'منتهية'       => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'تنتهي قريباً' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'سارية'        => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'غير مسجّل'    => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        ];
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_vehicle_licenses_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_vehicle_licenses_hint') }}</p>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'governorateIds', 'options' => $governorates->pluck('name', 'id')->all(), 'label' => __('home.governorate_name')])
            <div>
                <label class="{{ $lbl }}">{{ __('home.report_licenses_within_days') }}</label>
                <input wire:model="withinDays" type="number" min="0" placeholder="60" class="{{ $inp }}" />
                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-1">{{ __('home.report_licenses_within_hint') }}</p>
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
    <div class="space-y-3">
        @if($rows->isNotEmpty())
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="font-medium">{{ __('home.report_results_count') }}:</span>
                <span class="px-2.5 py-0.5 rounded-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847] font-semibold">
                    {{ $rows->count() }} {{ __('home.report_vehicle_unit') }}
                </span>
            </div>
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

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.governorate_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.vehicle_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.license_plate') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.license_expiry_date') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.report_licenses_remaining') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.report_licenses_status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($rows as $v)
                        @php [$remaining, $status] = \App\Exports\VehicleLicensesExport::licenseInfo($v->license_expiry_date, $this->soonDays()); @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-3 text-zinc-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $v->governorate->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $v->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $v->license_plate ?: '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $v->license_expiry_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $remaining }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge[$status] ?? '' }}">{{ $status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
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
