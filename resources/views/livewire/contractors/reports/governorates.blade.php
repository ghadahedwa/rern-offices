@php
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('livewire.contractors.reports.includes.report-header', [
        'title'      => __('home.ct_rep_governorates_title'),
        'hint'       => __('home.ct_rep_governorates_hint'),
        'showExport' => $hasSearched && count($groups) > 0,
    ])

    <x-contractors.report-filters>
        {{-- المحافظات: اختيارٌ متعدد، والفارغ = كل نطاق المستخدم --}}
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}">{{ __('home.ct_rep_governorate') }}</label>
            <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto rounded-lg border border-zinc-300 dark:border-zinc-600 p-2">
                @forelse($governorates as $governorate)
                    <label class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-md border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-[#c9a847]/10 transition">
                        <input type="checkbox" wire:model="governorateIds" value="{{ $governorate->id }}"
                               class="rounded border-zinc-300 text-[#c9a847] focus:ring-[#c9a847]/40">
                        <span class="text-zinc-700 dark:text-zinc-200">{{ $governorate->name }}</span>
                    </label>
                @empty
                    <span class="text-xs text-zinc-400">—</span>
                @endforelse
            </div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ __('home.ct_rep_all_governorates') }}</p>
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

        @if(count($groups) > 0)
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
                <table class="w-full text-sm text-center">
                    <thead class="text-xs">
                        <tr class="bg-[#c9a847] text-white">
                            <th class="px-3 py-2.5 font-semibold text-right">{{ __('home.ct_rep_governorate') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_contractors') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_working') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_present') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_unreviewed') }}</th>
                            @foreach($statuses as $status)
                                <th class="px-3 py-2.5 font-semibold">{{ $status->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($groups as $id => $group)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-3 py-2.5 text-right font-medium text-zinc-800 dark:text-zinc-100">{{ $names[$id] ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-zinc-700 dark:text-zinc-200 font-semibold">{{ $group['contractors'] }}</td>
                                <td class="px-3 py-2.5 text-zinc-700 dark:text-zinc-200">{{ $group['working'] }}</td>
                                <td class="px-3 py-2.5 text-emerald-700 dark:text-emerald-400 font-medium">{{ $group['present'] }}</td>
                                <td class="px-3 py-2.5 {{ $group['unreviewed'] > 0 ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">{{ $group['unreviewed'] }}</td>
                                @foreach($statuses as $status)
                                    @php $value = $group['exceptions'][$status->id] ?? 0; @endphp
                                    <td class="px-3 py-2.5 {{ $value > 0 ? 'text-zinc-700 dark:text-zinc-200 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#c9a847]/10 font-semibold text-zinc-800 dark:text-zinc-100">
                            <td class="px-3 py-2.5 text-right">{{ __('home.ct_rep_total') }}</td>
                            <td class="px-3 py-2.5">{{ $contractors }}</td>
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
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.ct_rep_prompt') }}</p>
        </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
