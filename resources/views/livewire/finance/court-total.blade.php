{{-- إجمالي أبنية المحاكم — الوحدات صفوف والشهور أعمدة (طلب المستخدمة 2026-11-12؛ كانت الشهور صفوفاً).
     لا إدخال هنا: الشهور من صفحات الوحدات، والملاحظات وحدها تُكتب. الجدول متقاطع فيتمرّر أفقياً وعمود
     الوحدة ثابت (الاستثناء المقصود من «يدخل عرض الشاشة»، كتقرير إحصائيات السيارات).
     ⚠️ العناصر والملاحظات لوحاتٌ تحت الجدول لا صفوفٌ داخله: صفٌّ بعرض الجدول المتمرّر يقع خارج الشاشة
        على الموبايل. --}}
@php
    $fmtNames = fn ($ids) => $ids->map(fn ($id) => $units->firstWhere('id', $id)?->shortName())->filter()->implode(' · ');
    $pill     = 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition';
@endphp
<div class="max-w-6xl mx-auto p-6 space-y-6">

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

    {{-- ── الوحدات × الشهور ── --}}
    <div class="space-y-3" x-data="{ itemsOpen: null, notesOpen: null }">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-x-auto">
            @if($rows)
                <table class="min-w-full text-sm text-right">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                        <tr>
                            <th class="px-3 py-3 font-medium sticky right-0 bg-zinc-50 dark:bg-zinc-800 z-10 min-w-28 max-sm:min-w-24 border-l border-zinc-200 dark:border-zinc-700">{{ __('home.fin_unit') }}</th>
                            @foreach($rows as $row)
                                <th wire:key="fin-total-h-{{ $row['key'] }}" class="px-3 py-2.5 font-medium text-center whitespace-nowrap min-w-28"
                                    @if($row['cells']->isEmpty() && ! $row['ended']) title="{{ __('home.fin_month_not_ended') }}" @endif>
                                    <span class="block font-semibold text-zinc-700 dark:text-zinc-200">{{ $row['label'] }}</span>
                                    @if($row['complete'])
                                        <span class="block mt-0.5 text-emerald-700 dark:text-emerald-300">✓ {{ __('home.fin_month_complete') }}</span>
                                    @elseif($row['cells']->isEmpty() && ! $row['ended'])
                                        <span class="block mt-0.5 text-zinc-400">{{ __('home.fin_month_open') }}</span>
                                    @else
                                        <span class="block mt-0.5 {{ $row['ended'] ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-400' }}">{{ __('home.fin_units_of', ['entered' => $row['entered'], 'count' => $required]) }}</span>
                                    @endif
                                </th>
                            @endforeach
                            <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap min-w-32 bg-[#c9a847]/10 text-zinc-700 dark:text-zinc-200">{{ __('home.fin_year_row') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($units as $unit)
                            <tr wire:key="fin-total-u-{{ $unit->id }}">
                                <th scope="row" class="px-3 py-3 font-medium sticky right-0 bg-white dark:bg-zinc-900 z-10 whitespace-nowrap border-l border-zinc-200 dark:border-zinc-700" title="{{ $unit->name }}">
                                    @if($linked->contains($unit->id))
                                        {{-- رابط الوحدة على ما يفتحه المستخدم وحده — والحارس في صفحة الوحدة --}}
                                        <a href="{{ route('finance.unit', $unit) }}" wire:navigate
                                           class="text-[#b8962e] dark:text-[#d8b856] font-semibold underline decoration-dotted underline-offset-4 hover:text-[#c9a847]">{{ $unit->shortName() }}</a>
                                    @else
                                        <span class="text-zinc-800 dark:text-zinc-100">{{ $unit->shortName() }}</span>
                                    @endif
                                    @unless($unit->is_active)
                                        <span class="block text-[11px] font-normal text-zinc-400">{{ __('home.ct_status_inactive_badge') }}</span>
                                    @endunless
                                </th>
                                @foreach($rows as $row)
                                    @if($row['cells']->has($unit->id))
                                        <td class="px-3 py-3 font-semibold text-zinc-800 dark:text-zinc-100 text-center whitespace-nowrap" dir="ltr">{{ number_format($row['cells'][$unit->id]) }}</td>
                                    @elseif($unit->is_active && $row['ended'])
                                        <td class="px-3 py-3 text-xs text-center whitespace-nowrap bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300">⏳ {{ __('home.fin_not_entered') }}</td>
                                    @else
                                        {{-- الشهر الجاري لم ينتهِ، أو وحدةٌ معطَّلة --}}
                                        <td class="px-3 py-3 text-center text-zinc-300 dark:text-zinc-600">—</td>
                                    @endif
                                @endforeach
                                <td class="px-3 py-3 text-center font-semibold whitespace-nowrap bg-[#c9a847]/5 text-zinc-800 dark:text-zinc-100" dir="ltr">
                                    {{ $unitTotals->has($unit->id) ? number_format($unitTotals[$unit->id]) : '—' }}
                                </td>
                            </tr>
                        @endforeach

                        {{-- الإجمالي --}}
                        <tr class="bg-[#c9a847]/10 font-bold">
                            <th scope="row" class="px-3 py-3 sticky right-0 z-10 bg-[#f7f1e0] dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 border-l border-zinc-200 dark:border-zinc-700">{{ __('home.fin_total_label') }}</th>
                            @foreach($rows as $row)
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    @if($row['cells']->isEmpty())
                                        <span class="text-zinc-400 font-normal">—</span>
                                    @else
                                        <span dir="ltr" class="{{ $row['complete'] ? 'text-zinc-900 dark:text-zinc-50' : 'text-zinc-500' }}">{{ number_format($row['total']) }}</span>
                                        @unless($row['complete'])
                                            <span class="block text-[11px] font-normal text-amber-700 dark:text-amber-300">{{ __('home.fin_so_far') }}</span>
                                        @endunless
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-3 text-center whitespace-nowrap bg-[#c9a847]/20 text-zinc-900 dark:text-zinc-50" dir="ltr" style="font-size: 1.05rem">{{ number_format($yearTotal) }}</td>
                        </tr>

                        {{-- العناصر: زرٌّ لكل شهر وللسنة، واللوحة تحت الجدول --}}
                        <tr>
                            <th scope="row" class="px-3 py-2.5 sticky right-0 z-10 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-500 dark:text-zinc-400 border-l border-zinc-200 dark:border-zinc-700">{{ __('home.fin_items_toggle') }}</th>
                            @foreach($rows as $row)
                                <td class="px-3 py-2.5 text-center">
                                    @if($row['items'] !== [])
                                        <button type="button" @click="itemsOpen = itemsOpen === '{{ $row['key'] }}' ? null : '{{ $row['key'] }}'"
                                                :class="itemsOpen === '{{ $row['key'] }}' ? 'bg-[#c9a847] text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200'"
                                                class="{{ $pill }}">{{ __('home.fin_items_show') }}</button>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-2.5 text-center bg-[#c9a847]/5">
                                @if($yearItems)
                                    <button type="button" @click="itemsOpen = itemsOpen === 'year' ? null : 'year'"
                                            :class="itemsOpen === 'year' ? 'bg-[#c9a847] text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200'"
                                            class="{{ $pill }}">{{ __('home.fin_items_show') }}</button>
                                @endif
                            </td>
                        </tr>

                        {{-- الملاحظات: زرّ الشهر بعددها، واللوحة تحت الجدول --}}
                        <tr>
                            <th scope="row" class="px-3 py-2.5 sticky right-0 z-10 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-500 dark:text-zinc-400 border-l border-zinc-200 dark:border-zinc-700">{{ __('home.fin_notes_row') }}</th>
                            @foreach($rows as $row)
                                @php $key = $row['key']; @endphp
                                <td class="px-3 py-2.5 text-center">@include('livewire.finance.partials.notes-button')</td>
                            @endforeach
                            <td class="bg-[#c9a847]/5"></td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</p>
            @endif
        </div>

        @if($later)
            <p class="text-xs text-zinc-400 px-1">{{ __('home.fin_later_months', $later) }}</p>
        @endif

        {{-- لوحات العناصر والملاحظات --}}
        @foreach($rows as $row)
            @php $key = $row['key']; @endphp
            @if($row['items'] !== [])
                <div wire:key="fin-items-{{ $key }}" x-show="itemsOpen === '{{ $key }}'" style="display:none"
                     class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-4">
                    @include('livewire.finance.partials.items-sum-panel', ['items' => $row['items'], 'title' => __('home.fin_items_of_month', ['month' => $row['label']])])
                </div>
            @endif
            <div wire:key="fin-notes-{{ $key }}" x-show="notesOpen === '{{ $key }}'" style="display:none"
                 class="rounded-xl border border-sky-100 dark:border-sky-900/40 bg-sky-50/60 dark:bg-sky-900/10 p-4">
                @include('livewire.finance.partials.notes-panel', ['notesTitle' => __('home.fin_notes_of_month', ['month' => $row['label']])])
            </div>
        @endforeach
        @if($yearItems)
            <div wire:key="fin-items-year" x-show="itemsOpen === 'year'" style="display:none"
                 class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-4">
                @include('livewire.finance.partials.items-sum-panel', ['items' => $yearItems, 'title' => __('home.fin_items_of_year')])
            </div>
        @endif
    </div>

    {{-- حذف ملاحظةٍ بلا سبب --}}
    @include('livewire.partials.delete-modal', ['deletingReasonModel' => ''])
</div>
