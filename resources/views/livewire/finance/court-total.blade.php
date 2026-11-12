{{-- إجمالي أبنية المحاكم — الشهور × الوحدات (نموذج 2026-11-11). لا إدخال هنا: الشهور من صفحات
     الوحدات، والملاحظات وحدها تُكتب. الجدول متقاطع فيتمرّر أفقياً على الموبايل وعمود الشهر ثابت
     (الاستثناء المقصود من «يدخل عرض الشاشة»، كتقرير إحصائيات السيارات). --}}
@php
    $cols     = $units->count() + 2;
    $unitW    = round(66 / max(1, $units->count()), 2);
    $fmtNames = fn ($ids) => $ids->map(fn ($id) => $units->firstWhere('id', $id)?->shortName())->filter()->implode(' · ');
@endphp
<div class="max-w-5xl mx-auto p-6 space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            {{ $account->label() }}
            <span class="text-base font-normal text-zinc-400">— {{ __('home.fin_total_subtitle') }}</span>
        </h1>
        <div class="flex items-center gap-3">
            <label class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('home.fin_fiscal_year') }}</label>
            <select wire:model.live="year"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                @foreach($years as $y)
                    <option value="{{ $y }}">{{ \App\Support\Finance\FinanceCalendar::fiscalYearLabel($y) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ── الملخّص: إجمالي السنة + أقدم شهرٍ لم يكتمل ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('home.fin_year_total_label') }} {{ \App\Support\Finance\FinanceCalendar::fiscalYearLabel($year) }}
                — {{ __('home.fin_year_range', ['from' => $yearFrom, 'to' => $yearTo]) }}
            </p>
            <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-1" dir="ltr" style="text-align: right; font-size: 2rem; line-height: 2.5rem">{{ number_format($yearTotal) }}</p>
            @if($yearTotal > 0)
                <p class="text-xs text-[#b8962e] dark:text-[#d8b856] mt-1">{{ \App\Support\Finance\AmountInWords::pounds($yearTotal) }}</p>
            @endif
            {{-- «يشمل» ما في الإجمالي من شهورٍ ناقصة وحده — شهرٌ بلا رقمٍ لا يدخل فيه --}}
            @php $partialIn = $incomplete->filter(fn ($r) => $r['cells']->isNotEmpty()); @endphp
            @if($partialIn->isNotEmpty())
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-2">
                    {{ __('home.fin_total_includes_incomplete', ['months' => $partialIn->pluck('label')->implode('، ')]) }}
                </p>
            @endif
        </div>

        @if($oldestLate)
            <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.fin_oldest_incomplete') }}</p>
                <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-1" style="font-size: 1.3rem">
                    {{ $oldestLate['label'] }} — {{ __('home.fin_units_of', ['entered' => $oldestLate['entered'], 'count' => $required]) }}
                </p>
                <p class="text-xs text-amber-800 dark:text-amber-200 mt-2">{{ __('home.fin_units_missing', ['names' => $fmtNames($oldestLate['missing'])]) }}</p>
                @php $others = $incomplete->reject(fn ($r) => $r['key'] === $oldestLate['key']); @endphp
                @if($others->isNotEmpty())
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
                        {{ $others->map(fn ($r) => $r['label'].': '.__('home.fin_units_of', ['entered' => $r['entered'], 'count' => $required]))->implode(' · ') }}
                    </p>
                @endif
            </div>
        @else
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.fin_units_title') }}</p>
                <p class="font-bold text-emerald-800 dark:text-emerald-200 mt-1" style="font-size: 1.1rem">{{ __('home.fin_all_ended_complete') }}</p>
            </div>
        @endif
    </div>

    {{-- ── الشهور × الوحدات ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-sm text-right table-fixed max-sm:min-w-[720px]">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-3 py-3 font-medium sticky right-0 bg-zinc-50 dark:bg-zinc-800 z-10" style="width: 15%">{{ __('home.fin_month') }}</th>
                    @foreach($units as $unit)
                        <th class="px-2 py-3 font-medium" style="width: {{ $unitW }}%" title="{{ $unit->name }}">
                            @if($linked->contains($unit->id))
                                {{-- رابط الوحدة على ما يفتحه المستخدم وحده — والحارس في صفحة الوحدة --}}
                                <a href="{{ route('finance.unit', $unit) }}" wire:navigate
                                   class="text-[#b8962e] dark:text-[#d8b856] font-semibold underline decoration-dotted underline-offset-4 hover:text-[#c9a847]">{{ $unit->shortName() }}</a>
                            @else
                                {{ $unit->shortName() }}
                            @endif
                            @unless($unit->is_active)
                                <span class="block text-[11px] text-zinc-400">{{ __('home.ct_status_inactive_badge') }}</span>
                            @endunless
                        </th>
                    @endforeach
                    <th class="px-3 py-3 font-medium bg-[#c9a847]/10 text-zinc-700 dark:text-zinc-200" style="width: 19%">{{ __('home.fin_total_label') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700" x-data="{ itemsOpen: null, notesOpen: null }">
                @forelse($rows as $row)
                    @php $key = $row['key']; $hasItems = $row['items'] !== []; @endphp
                    <tr wire:key="fin-total-{{ $key }}">
                        <td class="px-3 py-3 sticky right-0 bg-white dark:bg-zinc-900 z-10">
                            <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</span>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if($hasItems)
                                    <button type="button" @click="itemsOpen = itemsOpen === '{{ $key }}' ? null : '{{ $key }}'"
                                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 transition">
                                        {{ __('home.fin_items_toggle') }}
                                    </button>
                                @endif
                                @include('livewire.finance.partials.notes-button')
                            </div>
                        </td>

                        @if($row['cells']->isEmpty() && ! $row['ended'])
                            {{-- الشهر الجاري ولم يُدخله أحد — لا ست خاناتٍ صفراء لشهرٍ لم ينتهِ --}}
                            <td class="px-2 py-3 text-xs text-zinc-400" colspan="{{ $units->count() }}">{{ __('home.fin_month_not_ended') }}</td>
                        @else
                            @foreach($units as $unit)
                                @if($row['cells']->has($unit->id))
                                    <td class="px-2 py-3 font-semibold text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align: right">{{ number_format($row['cells'][$unit->id]) }}</td>
                                @elseif($unit->is_active)
                                    <td class="px-2 py-3 text-xs {{ $row['ended'] ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' : 'text-zinc-400' }}">
                                        {{ $row['ended'] ? '⏳ '.__('home.fin_not_entered') : '—' }}
                                    </td>
                                @else
                                    <td class="px-2 py-3 text-zinc-300">—</td>
                                @endif
                            @endforeach
                        @endif

                        <td class="px-3 py-3 bg-[#c9a847]/5">
                            @if($row['cells']->isEmpty())
                                <span class="text-zinc-400">—</span>
                            @else
                                <span dir="ltr" class="font-bold {{ $row['complete'] ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-500' }}" style="font-size: 1.05rem">{{ number_format($row['total']) }}</span>
                                @unless($row['complete'])
                                    <p class="text-xs text-amber-700 dark:text-amber-300">{{ __('home.fin_units_of', ['entered' => $row['entered'], 'count' => $required]) }} · {{ __('home.fin_so_far') }}</p>
                                @endunless
                            @endif
                        </td>
                    </tr>

                    @if($hasItems)
                        @include('livewire.finance.partials.items-sum-row', ['items' => $row['items'], 'title' => __('home.fin_items_of_month', ['month' => $row['label']])])
                    @endif
                    @include('livewire.finance.partials.notes-rows')
                @empty
                    <tr><td colspan="{{ $cols }}" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td></tr>
                @endforelse

                @if($rows)
                    @php $key = 'year'; @endphp
                    <tr class="bg-zinc-100/80 dark:bg-zinc-800/60 font-bold">
                        <td class="px-3 py-3 sticky right-0 bg-zinc-100 dark:bg-zinc-800 z-10 text-zinc-700 dark:text-zinc-200">
                            {{ __('home.fin_year_row') }}
                            @if($yearItems)
                                <div class="mt-1">
                                    <button type="button" @click="itemsOpen = itemsOpen === 'year' ? null : 'year'"
                                            class="inline-flex items-center rounded-full bg-white dark:bg-zinc-900 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 transition">
                                        {{ __('home.fin_items_toggle') }}
                                    </button>
                                </div>
                            @endif
                        </td>
                        @foreach($units as $unit)
                            <td class="px-2 py-3 text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align: right">
                                {{ $unitTotals->has($unit->id) ? number_format($unitTotals[$unit->id]) : '—' }}
                            </td>
                        @endforeach
                        <td class="px-3 py-3 bg-[#c9a847]/15 text-zinc-900 dark:text-zinc-50" dir="ltr" style="text-align: right">{{ number_format($yearTotal) }}</td>
                    </tr>
                    @if($yearItems)
                        @include('livewire.finance.partials.items-sum-row', ['items' => $yearItems, 'title' => __('home.fin_items_of_year')])
                    @endif
                @endif

                @if($later)
                    <tr class="bg-zinc-50/60 dark:bg-zinc-800/30">
                        <td class="px-4 py-3 text-xs text-zinc-400" colspan="{{ $cols }}">{{ __('home.fin_later_months', $later) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- حذف ملاحظةٍ بلا سبب --}}
    @include('livewire.partials.delete-modal', ['deletingReasonModel' => ''])
</div>
