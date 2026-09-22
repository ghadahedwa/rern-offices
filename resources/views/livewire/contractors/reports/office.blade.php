@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('livewire.contractors.reports.includes.report-header', [
        'title'      => __('home.ct_rep_offices_title'),
        'hint'       => __('home.ct_rep_offices_hint'),
        'showExport' => $hasSearched && count($rows) > 0,
    ])

    <x-contractors.report-filters>
        <div>
            {{-- المحافظة إلزامية في هذا التقرير وحده — انظر `OfficeReport::search()` --}}
            <label class="{{ $lbl }}">
                {{ __('home.ct_rep_governorate') }}
                <span class="text-red-500">*</span>
            </label>
            <select wire:model.live="governorateId" class="{{ $inp }}">
                <option value="">{{ __('home.ct_rep_pick_governorate') }}</option>
                @foreach($governorates as $governorate)
                    <option value="{{ $governorate->id }}">{{ $governorate->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $lbl }}">{{ __('home.ct_rep_office') }}</label>
            <select wire:model="officeId" class="{{ $inp }}">
                <option value="">{{ __('home.ct_worker_all_offices') }}</option>
                @foreach($offices as $office)
                    {{-- الاسم مقصوص في الخيار والكامل في title: يبلغ ١٣٦ حرفاً فتخرج المنسدلة عن الشاشة --}}
                    <option value="{{ $office->id }}" title="{{ $office->name }}">{{ $office->short_name }}</option>
                @endforeach
            </select>
        </div>
    </x-contractors.report-filters>

    @if($hasSearched)
        @if($breakdown)
            @include('livewire.contractors.reports.includes.breakdown-bar', [
                'breakdown' => $breakdown,
                'period'    => $this->periodLabel(),
                'holidays'  => $holidays,
            ])
        @endif

        @if(count($rows) > 0)
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="font-medium">{{ __('home.ct_rep_col_contractors') }}:</span>
                <span class="px-2.5 py-0.5 rounded-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847] font-semibold">{{ $contractors }}</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
                <table class="w-full text-sm text-center">
                    <thead class="text-xs">
                        <tr class="bg-[#c9a847] text-white">
                            <th class="px-3 py-2.5 font-semibold text-right">{{ __('home.ct_rep_contractor') }}</th>
                            <th class="px-3 py-2.5 font-semibold text-right">{{ __('home.ct_rep_office_col') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_working') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_present') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_unreviewed') }}</th>
                            @foreach($statuses as $status)
                                <th class="px-3 py-2.5 font-semibold">{{ $status->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($rows as $row)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-3 py-2.5 text-right">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $row['contractor_name'] }}</span>
                                    @if($row['profession'])
                                        <span class="block text-xs text-zinc-400 dark:text-zinc-500">{{ $row['profession'] }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right text-zinc-600 dark:text-zinc-300 max-w-xs truncate" title="{{ $row['office_name'] }}">{{ $row['office_name'] }}</td>
                                <td class="px-3 py-2.5 text-zinc-700 dark:text-zinc-200">{{ $row['working'] }}</td>
                                <td class="px-3 py-2.5 text-emerald-700 dark:text-emerald-400 font-medium">{{ $row['present'] }}</td>
                                <td class="px-3 py-2.5 {{ $row['unreviewed'] > 0 ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">{{ $row['unreviewed'] }}</td>
                                @foreach($statuses as $status)
                                    @php $value = $row['exceptions'][$status->id] ?? 0; @endphp
                                    <td class="px-3 py-2.5 {{ $value > 0 ? 'text-zinc-700 dark:text-zinc-200 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#c9a847]/10 font-semibold text-zinc-800 dark:text-zinc-100">
                            <td class="px-3 py-2.5 text-right" colspan="2">{{ __('home.ct_rep_total') }}</td>
                            <td class="px-3 py-2.5">{{ $totals['working'] }}</td>
                            <td class="px-3 py-2.5">{{ $totals['present'] }}</td>
                            <td class="px-3 py-2.5">{{ $totals['unreviewed'] }}</td>
                            @foreach($statuses as $status)
                                <td class="px-3 py-2.5">{{ $totals['exceptions'][$status->id] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_equation') }}</p>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
                <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_empty') }}</p>
            </div>
        @endif
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_office_prompt') }}</p>
        </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
