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
        {{-- المحافظات: اختيارٌ متعدد، والفارغ = كل نطاق المستخدم.
             ⚠️ **كلها ظاهرة بلا تمرير** (طلب المستخدمة): الصندوق القصير كان يُخفي أغلب
                المحافظات خلف سكرول داخليّ، فلا يرى المستخدم ما اختاره ولا ما بقي. --}}
        <div class="sm:col-span-2 lg:col-span-4">
            <div class="flex items-center justify-between gap-3 mb-1">
                <label class="{{ $lbl }} mb-0">{{ __('home.ct_rep_governorate') }}</label>
                @if(count($governorateIds) > 0)
                    <button type="button" wire:click="$set('governorateIds', [])"
                            class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-[#c9a847] transition">
                        {{ __('home.ct_rep_clear_governorates') }}
                    </button>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-300 dark:border-zinc-600 p-2">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-3 gap-y-1.5">
                    @forelse($governorates as $governorate)
                        <label class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-md cursor-pointer hover:bg-[#c9a847]/10 transition">
                            <input type="checkbox" wire:model="governorateIds" value="{{ $governorate->id }}"
                                   class="rounded border-zinc-300 text-[#c9a847] focus:ring-[#c9a847]/40 shrink-0">
                            <span class="text-zinc-700 dark:text-zinc-200 truncate" title="{{ $governorate->name }}">{{ $governorate->name }}</span>
                        </label>
                    @empty
                        <span class="text-xs text-zinc-400">—</span>
                    @endforelse
                </div>
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

        {{-- تنبيه «لم تُرصد أيام حضورهم» — يظهر حين يقع وحده --}}
        @include('livewire.contractors.reports.includes.unrecorded-note', ['count' => $unrecorded])

        @if(count($groups) > 0)
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
                <table class="w-full text-sm text-center">
                    <thead class="text-xs">
                        <tr class="bg-[#c9a847] text-white">
                            <th class="px-3 py-2.5 font-semibold text-right">{{ __('home.ct_rep_governorate') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_contractors') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_working') }}</th>
                            <th class="px-3 py-2.5 font-semibold">{{ __('home.ct_rep_col_present') }}</th>
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
                                <td class="px-3 py-2.5 text-emerald-700 dark:text-emerald-400 font-medium">{{ \App\Support\Contractors\AttendanceReport::attended($group) }}</td>
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
                            <td class="px-3 py-2.5">{{ \App\Support\Contractors\AttendanceReport::attended($totals) }}</td>
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
