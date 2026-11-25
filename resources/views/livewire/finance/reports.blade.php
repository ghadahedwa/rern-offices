{{-- تقارير الشئون المالية — نموذج وافقت عليه المستخدمة 2026-11-17. الأرقام من `FinanceReport` (الشاشة والملفّان).
     الجدول يتمرّر أفقياً داخل إطاره (أعمدة الحسابات الأربعة واتصالات بثلاثة) والعمود الأول ثابت. --}}
@php
    $seg     = fn (bool $on) => $on ? 'bg-[#c9a847] text-white font-medium' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800';
    $select  = 'border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
    $fmt     = fn ($v) => $v === null ? '—' : \App\Support\Finance\Money::format($v);
    $sticky  = 'sticky right-0 z-10 border-l border-zinc-200 dark:border-zinc-700';
    $pending = 'px-3 py-2.5 text-xs text-center bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 whitespace-nowrap';
@endphp
<div class="max-w-6xl mx-auto p-6 space-y-6">

    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fin_reports') }}</h1>

    {{-- ── الفلاتر ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-end gap-x-6 gap-y-4">
            <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_rep_kind') }}</p>
                <div class="inline-flex rounded-lg border border-zinc-300 dark:border-zinc-600 overflow-hidden text-sm">
                    <button type="button" wire:click="$set('kind', 'unit')" class="px-4 py-2 transition {{ $seg($kind === 'unit') }}">{{ __('home.fin_rep_kind_unit') }}</button>
                    <button type="button" wire:click="$set('kind', 'total')" class="px-4 py-2 transition {{ $seg($kind === 'total') }}">{{ __('home.fin_rep_kind_total') }}</button>
                </div>
            </div>

            @if($kind === 'unit')
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_unit') }}</p>
                    <select wire:model.live="unit" class="{{ $select }} min-w-56">
                        @foreach($units as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_rep_period') }}</p>
                <div class="inline-flex rounded-lg border border-zinc-300 dark:border-zinc-600 overflow-hidden text-sm">
                    @foreach(\App\Support\Finance\FinanceReport::PERIODS as $p)
                        <button type="button" wire:click="$set('period', '{{ $p }}')" class="px-4 py-2 transition whitespace-nowrap {{ $seg($period === $p) }}">{{ __('home.fin_rep_period_'.$p) }}</button>
                    @endforeach
                </div>
            </div>

            @if($period === 'month')
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_month') }}</p>
                    <select wire:model.live="month" class="{{ $select }}">
                        @foreach($months as $m)
                            <option value="{{ $m->format('Y-m') }}">{{ \App\Support\Finance\FinanceCalendar::monthLabel($m) }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($period === 'range')
                <div class="flex items-end gap-2">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_rep_from') }}</p>
                        <select wire:model.live="from" class="{{ $select }}">
                            @foreach($months as $m)
                                <option value="{{ $m->format('Y-m') }}">{{ \App\Support\Finance\FinanceCalendar::monthLabel($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_rep_to') }}</p>
                        <select wire:model.live="to" class="{{ $select }}">
                            @foreach($months as $m)
                                <option value="{{ $m->format('Y-m') }}">{{ \App\Support\Finance\FinanceCalendar::monthLabel($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @else
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('home.fin_fiscal_year') }}</p>
                    <select wire:model.live="year" class="{{ $select }}">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ \App\Support\Finance\FinanceCalendar::fiscalYearLabel($y) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if($report)
            <div class="flex flex-wrap items-center gap-2 border-t border-zinc-100 dark:border-zinc-800 pt-4">
                <a href="{{ $pdfUrl }}" target="_blank"
                   class="border border-red-300 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium px-4 py-2 rounded-lg transition">PDF</a>
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                        class="border border-emerald-300 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-sm font-medium px-4 py-2 rounded-lg transition">Excel</button>
            </div>
        @endif
    </div>

    @if(! $report)
        <p class="text-center text-zinc-400 py-10">{{ __('home.fin_rep_pick_unit') }}</p>
    @else
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h2 class="text-base font-semibold text-zinc-700 dark:text-zinc-200">{{ $report->title() }} — {{ $report->periodLabel() }}</h2>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                    <tr>
                        <th rowspan="2" class="px-3 py-2.5 font-medium bg-zinc-50 dark:bg-zinc-800 {{ $sticky }}">{{ $report->kind === 'unit' ? __('home.fin_month') : __('home.fin_unit') }}</th>
                        @foreach($report->accounts() as $account)
                            @if($account->isLedger())
                                <th colspan="3" class="px-3 py-1.5 font-medium text-center border-x border-zinc-200 dark:border-zinc-700">{{ __('home.fin_tab_'.$account->key) }}</th>
                            @else
                                <th rowspan="2" class="px-3 py-2.5 font-medium text-center whitespace-nowrap">{{ __('home.fin_tab_'.$account->key) }}</th>
                            @endif
                        @endforeach
                        <th rowspan="2" class="px-3 py-2.5 font-medium text-center whitespace-nowrap">{{ $report->kind === 'unit' ? __('home.fin_rep_orders') : __('home.fin_rep_entered') }}</th>
                    </tr>
                    <tr>
                        <th class="px-3 py-1.5 font-medium text-center border-r border-zinc-200 dark:border-zinc-700">{{ __('home.fin_income') }}</th>
                        <th class="px-3 py-1.5 font-medium text-center">{{ __('home.fin_expense') }}</th>
                        <th class="px-3 py-1.5 font-medium text-center whitespace-nowrap border-l border-zinc-200 dark:border-zinc-700">{{ $report->kind === 'unit' ? __('home.fin_rep_balance') : __('home.fin_rep_balance_end') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @if($report->kind === 'unit')
                        @foreach($data['rows'] as $row)
                            <tr wire:key="fin-rep-{{ $row['month']->format('Y-m') }}">
                                <th scope="row" class="px-3 py-2.5 font-medium bg-white dark:bg-zinc-900 whitespace-nowrap text-zinc-800 dark:text-zinc-100 {{ $sticky }}">{{ $row['label'] }}</th>
                                @foreach($report->accounts() as $account)
                                    @if($account->isLedger())
                                        @if($row['telecom'])
                                            <td class="px-3 py-2.5 text-center text-emerald-700 dark:text-emerald-400" dir="ltr">{{ \App\Support\Finance\Money::format($row['telecom']['income']) }}</td>
                                            <td class="px-3 py-2.5 text-center text-red-600 dark:text-red-400" dir="ltr">{{ \App\Support\Finance\Money::format($row['telecom']['expense']) }}</td>
                                            <td class="px-3 py-2.5 text-center font-semibold" dir="ltr">{{ \App\Support\Finance\Money::format($row['telecom']['balance']) }}</td>
                                        @elseif($row['ended'])
                                            <td colspan="3" class="{{ $pending }}">⏳ {{ __('home.fin_not_entered') }}</td>
                                        @else
                                            <td colspan="3" class="px-3 py-2.5 text-center text-zinc-300 dark:text-zinc-600">—</td>
                                        @endif
                                    @elseif($row['cells'][$account->key] !== null)
                                        <td class="px-3 py-2.5 text-center text-zinc-800 dark:text-zinc-100" dir="ltr">{{ \App\Support\Finance\Money::format($row['cells'][$account->key]) }}</td>
                                    @elseif($row['ended'])
                                        <td class="{{ $pending }}">⏳ {{ __('home.fin_not_entered') }}</td>
                                    @else
                                        <td class="px-3 py-2.5 text-center text-zinc-300 dark:text-zinc-600">—</td>
                                    @endif
                                @endforeach
                                <td class="px-3 py-2.5 text-center text-xs text-zinc-500 whitespace-nowrap">{{ __('home.fin_rep_orders_count', ['count' => $row['orders']]) }}</td>
                            </tr>
                        @endforeach
                    @else
                        @foreach($data['rows'] as $row)
                            <tr wire:key="fin-rep-u-{{ $row['unit']->id }}">
                                <th scope="row" class="px-3 py-2.5 font-medium bg-white dark:bg-zinc-900 whitespace-nowrap text-zinc-800 dark:text-zinc-100 {{ $sticky }}">{{ $row['unit']->name }}</th>
                                @foreach($report->accounts() as $account)
                                    @if($account->isLedger())
                                        <td class="px-3 py-2.5 text-center text-emerald-700 dark:text-emerald-400" dir="ltr">{{ \App\Support\Finance\Money::format($row['telecom']['income']) }}</td>
                                        <td class="px-3 py-2.5 text-center text-red-600 dark:text-red-400" dir="ltr">{{ \App\Support\Finance\Money::format($row['telecom']['expense']) }}</td>
                                        <td class="px-3 py-2.5 text-center font-semibold" dir="ltr">{{ $fmt($row['telecom']['balance']) }}</td>
                                    @else
                                        <td class="px-3 py-2.5 text-center text-zinc-800 dark:text-zinc-100" dir="ltr">{{ $fmt($row['cells'][$account->key]) }}</td>
                                    @endif
                                @endforeach
                                <td class="px-3 py-2.5 text-center text-xs whitespace-nowrap {{ $row['entered'] === $row['required'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                                    @if($row['entered'] === $row['required'])✓ @endif{{ __('home.fin_units_of', ['entered' => $row['entered'], 'count' => $row['required']]) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    {{-- الإجمالي --}}
                    <tr class="bg-[#c9a847]/10 font-bold">
                        <th scope="row" class="px-3 py-2.5 bg-[#f7f1e0] dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 {{ $sticky }}">{{ __('home.fin_total_label') }}</th>
                        @foreach($report->accounts() as $account)
                            @if($account->isLedger())
                                <td class="px-3 py-2.5 text-center text-emerald-700 dark:text-emerald-400" dir="ltr">{{ \App\Support\Finance\Money::format($data['totals']['telecom']['income']) }}</td>
                                <td class="px-3 py-2.5 text-center text-red-600 dark:text-red-400" dir="ltr">{{ \App\Support\Finance\Money::format($data['totals']['telecom']['expense']) }}</td>
                                <td class="px-3 py-2.5 text-center" dir="ltr">{{ $fmt($data['totals']['telecom']['balance']) }}</td>
                            @else
                                <td class="px-3 py-2.5 text-center" dir="ltr">{{ $fmt($data['totals']['cells'][$account->key]) }}</td>
                            @endif
                        @endforeach
                        <td class="px-3 py-2.5 text-center text-xs font-normal text-zinc-500">
                            @if($report->kind === 'unit'){{ __('home.fin_rep_orders_count', ['count' => $data['totals']['orders']]) }}@endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-zinc-500 dark:text-zinc-400 px-1">
            {{ $report->kind === 'unit' ? __('home.fin_rep_unit_note') : __('home.fin_rep_total_note') }}
        </p>

        @if($items)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-4">
                @include('livewire.finance.partials.items-sum-panel', ['items' => $items, 'title' => __('home.fin_rep_items_title')])
            </div>
        @endif
    @endif
</div>
