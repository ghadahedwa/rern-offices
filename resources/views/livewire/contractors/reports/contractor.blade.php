@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('livewire.contractors.reports.includes.report-header', [
        'title'      => __('home.ct_rep_contractor_title'),
        'hint'       => __('home.ct_rep_contractor_hint'),
        'showExport' => $hasSearched && count($rows) > 0,
    ])

    <x-contractors.report-filters>
        <div>
            <label class="{{ $lbl }}">{{ __('home.ct_rep_governorate') }}</label>
            <select wire:model.live="governorateId" class="{{ $inp }}">
                <option value="">{{ __('home.ct_rep_all_governorates') }}</option>
                @foreach($governorates as $governorate)
                    <option value="{{ $governorate->id }}">{{ $governorate->name }}</option>
                @endforeach
            </select>
        </div>
        <x-contractors.searchable-select
            :label="__('home.ct_rep_contractor')"
            search-model="contractorSearch"
            value-model="contractorId"
            :options="$candidates"
            :placeholder="__('home.ct_rep_pick_contractor')"
            :search-placeholder="__('home.ct_rep_search_contractor')"
            :required="true" />
    </x-contractors.report-filters>

    @if($hasSearched)
        @if($breakdown)
            @include('livewire.contractors.reports.includes.breakdown-bar', [
                'breakdown' => $breakdown,
                'period'    => $this->periodLabel(),
                'holidays'  => $holidays,
            ])
        @endif

        {{-- تنبيه «لم تُرصد أيام حضورهم» — يظهر حين يقع وحده --}}
        @include('livewire.contractors.reports.includes.unrecorded-note', ['count' => $unrecorded])

        @if($subject && count($rows) > 0)
            {{-- بطاقة العامل --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ $subject->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $subject->profession?->name ?? '—' }}@if($subject->phone) · {{ $subject->phone }}@endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-center">
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_col_working') }}</p>
                            <p class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $totals['working'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_col_present') }}</p>
                            <p class="text-xl font-semibold text-emerald-700 dark:text-emerald-400">{{ \App\Support\Contractors\AttendanceReport::attended($totals) }}</p>
                        </div>
                        @foreach($statuses as $status)
                            <div>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $status->name }}</p>
                                <p class="text-xl font-semibold" style="color: {{ $status->color }}">{{ $totals['exceptions'][$status->id] ?? 0 }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- صفٌّ لكل مدة تسكين --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
                <table class="w-full text-sm text-center">
                    <thead class="text-xs">
                        <tr class="bg-[#c9a847] text-white">
                            <th class="px-3 py-2.5 font-semibold text-right">{{ __('home.ct_rep_office_col') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_assignment_period') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_working') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_present') }}</th>
                            @foreach($statuses as $status)
                                <th class="px-3 py-2.5 font-semibold">{{ $status->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($rows as $row)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-3 py-2.5 text-right text-zinc-700 dark:text-zinc-200 max-w-md truncate" title="{{ $row['office_name'] }}">{{ $row['office_name'] }}</td>
                                <td class="px-3 py-2.5 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                    {{ $row['started_on'] }} — {{ $row['ended_on'] ?? __('home.ct_rep_open_assignment') }}
                                </td>
                                <td class="px-3 py-2.5 text-zinc-700 dark:text-zinc-200">{{ $row['working'] }}</td>
                                <td class="px-3 py-2.5 text-emerald-700 dark:text-emerald-400 font-medium">{{ \App\Support\Contractors\AttendanceReport::attended($row) }}</td>
                                @foreach($statuses as $status)
                                    @php $value = $row['exceptions'][$status->id] ?? 0; @endphp
                                    <td class="px-3 py-2.5 {{ $value > 0 ? 'text-zinc-700 dark:text-zinc-200 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- تفصيل التواريخ: ما يميّز تقرير العامل — «غاب يوم ٢» لا «غاب ٢» --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.ct_rep_days_detail') }}</h3>
                </div>

                @if(count($exceptions) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($exceptions as $day)
                            <span class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-lg border"
                                  style="border-color: {{ $day['color'] }}55; background-color: {{ $day['color'] }}14;"
                                  title="{{ $day['office'] }}">
                                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $day['date'] }}</span>
                                <span style="color: {{ $day['color'] }}">{{ $day['status'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_no_exceptions') }}</p>
                @endif
            </div>
        @else
            {{-- ⚠️ «لم تختر عاملاً» ليست «لا بيانات»: الرسالة الواحدة كانت تقول للمستخدم
                 إن العامل بلا أيام، وهو لم يختر عاملاً أصلاً. --}}
            <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
                <p class="text-sm text-zinc-400 dark:text-zinc-500">
                    {{ $subject ? __('home.ct_rep_empty') : __('home.ct_rep_need_contractor') }}
                </p>
            </div>
        @endif
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_prompt') }}</p>
        </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
