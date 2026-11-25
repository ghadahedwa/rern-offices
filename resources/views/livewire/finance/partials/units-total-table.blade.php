{{-- جدول «الوحدات × الشهور» لحسابٍ واحد في «الإجمالي العام» — البيانات من `UnitsTotal::build` ($t).
     الوحدات صفوف والشهور أعمدة، والجدول متقاطع فيتمرّر أفقياً وعمود الوحدة ثابت (الاستثناء المقصود).
     اتصالات (الشكل «ب»): الخلية رصيد آخر الشهر وتحته + الإيراد و− المنصرف، وعمود «أول السنة» قبل الشهور.
     ⚠️ العناصر والملاحظات لوحاتٌ تحت الجدول لا صفوفٌ داخله: صفٌّ بعرض الجدول المتمرّر يقع خارج الشاشة. --}}
@php
    $account  = $t['account'];
    $isLedger = $t['ledger'] !== null;
    $pill     = 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition';
    $stickyTh = 'sticky right-0 z-10 border-l border-zinc-200 dark:border-zinc-700';
    $prefix   = $account->key.'@';
@endphp
<section id="fin-{{ $account->slug() }}" class="space-y-3 scroll-mt-6" x-data="{ itemsOpen: null, notesOpen: null }">
    <div class="flex items-center gap-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h2 class="text-base font-semibold text-zinc-700 dark:text-zinc-200">{{ $n }} · {{ __('home.fin_tab_'.$account->key) }}</h2>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-x-auto">
        @if($t['rows'])
            <table class="min-w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                    <tr>
                        <th class="px-3 py-3 font-medium bg-zinc-50 dark:bg-zinc-800 min-w-28 {{ $stickyTh }}">{{ __('home.fin_unit') }}</th>
                        @if($isLedger)
                            <th class="px-3 py-2.5 font-medium text-center whitespace-nowrap min-w-24">
                                @if($t['ledger']['firstYear'])
                                    {{ __('home.fin_opening_col') }}<span class="block">{{ __('home.fin_opening_col_date') }}</span>
                                @else
                                    {{ __('home.fin_year_start') }}
                                @endif
                            </th>
                        @endif
                        @foreach($t['rows'] as $row)
                            <th wire:key="fin-th-{{ $prefix }}{{ $row['key'] }}" class="px-3 py-2.5 font-medium text-center whitespace-nowrap min-w-28"
                                @if($row['cells']->isEmpty() && ! $row['ended']) title="{{ __('home.fin_month_not_ended') }}" @endif>
                                <span class="block font-semibold text-zinc-700 dark:text-zinc-200">{{ $row['label'] }}</span>
                                @if($row['complete'])
                                    <span class="block mt-0.5 text-emerald-700 dark:text-emerald-300">✓ {{ __('home.fin_month_complete') }}</span>
                                @elseif($row['cells']->isEmpty() && ! $row['ended'])
                                    <span class="block mt-0.5 text-zinc-400">{{ __('home.fin_month_open') }}</span>
                                @else
                                    <span class="block mt-0.5 {{ $row['ended'] ? 'text-amber-700 dark:text-amber-300' : 'text-zinc-400' }}">{{ __('home.fin_units_of', ['entered' => $row['entered'], 'count' => $t['required']]) }}</span>
                                @endif
                            </th>
                        @endforeach
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap min-w-32 bg-[#c9a847]/10 text-zinc-700 dark:text-zinc-200">
                            {{ $isLedger ? __('home.fin_year_col_ledger') : __('home.fin_year_row') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($t['units'] as $unit)
                        <tr wire:key="fin-u-{{ $prefix }}{{ $unit->id }}">
                            <th scope="row" class="px-3 py-3 font-medium bg-white dark:bg-zinc-900 whitespace-nowrap {{ $stickyTh }}">
                                @if($linked->contains($unit->id))
                                    {{-- رابط الوحدة (على تبويب الحساب) لما يفتحه المستخدم وحده — والحارس في صفحة الوحدة --}}
                                    <a href="{{ route('finance.unit', [$unit, $account->slug()]) }}" wire:navigate
                                       class="text-[#b8962e] dark:text-[#d8b856] font-semibold underline decoration-dotted underline-offset-4 hover:text-[#c9a847]">{{ $unit->name }}</a>
                                @else
                                    <span class="text-zinc-800 dark:text-zinc-100">{{ $unit->name }}</span>
                                @endif
                                @unless($unit->is_active)
                                    <span class="block text-[11px] font-normal text-zinc-400">{{ __('home.ct_status_inactive_badge') }}</span>
                                @endunless
                            </th>
                            @if($isLedger)
                                <td class="px-3 py-3 text-center text-zinc-500 dark:text-zinc-400 whitespace-nowrap" dir="ltr">
                                    {{ $t['ledger']['start'][$unit->id] !== null ? \App\Support\Finance\Money::format($t['ledger']['start'][$unit->id]) : '—' }}
                                </td>
                            @endif
                            @foreach($t['rows'] as $row)
                                @if($row['cells']->has($unit->id))
                                    @if($isLedger)
                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                            @include('livewire.finance.partials.ledger-cell', ['c' => $row['ledger'][$unit->id]])
                                        </td>
                                    @else
                                        <td class="px-3 py-3 font-semibold text-zinc-800 dark:text-zinc-100 text-center whitespace-nowrap" dir="ltr">{{ \App\Support\Finance\Money::format($row['cells'][$unit->id]) }}</td>
                                    @endif
                                @elseif($unit->is_active && $row['ended'])
                                    <td class="px-3 py-3 text-xs text-center whitespace-nowrap bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300">⏳ {{ __('home.fin_not_entered') }}</td>
                                @else
                                    {{-- الشهر الجاري لم ينتهِ، أو وحدةٌ معطَّلة --}}
                                    <td class="px-3 py-3 text-center text-zinc-300 dark:text-zinc-600">—</td>
                                @endif
                            @endforeach
                            @if($isLedger)
                                @php $y = $t['ledger']['year'][$unit->id]; @endphp
                                <td class="px-3 py-2 text-center whitespace-nowrap bg-[#c9a847]/5">
                                    @if($y['entered'])
                                        @include('livewire.finance.partials.ledger-cell', ['c' => $y])
                                    @else
                                        <span dir="ltr" class="text-zinc-500">{{ $y['balance'] !== null ? \App\Support\Finance\Money::format($y['balance']) : '—' }}</span>
                                    @endif
                                </td>
                            @else
                                <td class="px-3 py-3 text-center font-semibold whitespace-nowrap bg-[#c9a847]/5 text-zinc-800 dark:text-zinc-100" dir="ltr">
                                    {{ $t['unitTotals']->has($unit->id) ? \App\Support\Finance\Money::format($t['unitTotals'][$unit->id]) : '—' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- الإجمالي --}}
                    <tr class="bg-[#c9a847]/10 font-bold">
                        <th scope="row" class="px-3 py-3 bg-[#f7f1e0] dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 {{ $stickyTh }}">{{ __('home.fin_total_label') }}</th>
                        @if($isLedger)
                            <td class="px-3 py-3 text-center whitespace-nowrap" dir="ltr">{{ \App\Support\Finance\Money::format($t['ledger']['startTotal']) }}</td>
                        @endif
                        @foreach($t['rows'] as $row)
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                @if($row['cells']->isEmpty())
                                    <span class="text-zinc-400 font-normal">—</span>
                                @else
                                    @if($isLedger)
                                        @include('livewire.finance.partials.ledger-cell', ['c' => $row['ledgerTotal']])
                                    @else
                                        <span dir="ltr" class="{{ $row['complete'] ? 'text-zinc-900 dark:text-zinc-50' : 'text-zinc-500' }}">{{ \App\Support\Finance\Money::format($row['total']) }}</span>
                                    @endif
                                    @unless($row['complete'])
                                        <span class="block text-[11px] font-normal text-amber-700 dark:text-amber-300">{{ __('home.fin_so_far') }}</span>
                                    @endunless
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-2 text-center whitespace-nowrap bg-[#c9a847]/20 text-zinc-900 dark:text-zinc-50" style="font-size: 1.05rem">
                            @if($isLedger)
                                @include('livewire.finance.partials.ledger-cell', ['c' => $t['ledger']['yearTotal']])
                            @else
                                <span dir="ltr">{{ \App\Support\Finance\Money::format($t['yearTotal']) }}</span>
                            @endif
                        </td>
                    </tr>

                    {{-- العناصر (أبنية المحاكم): زرٌّ لكل شهر وللسنة، واللوحة تحت الجدول --}}
                    @if($account->isItemized())
                        <tr>
                            <th scope="row" class="px-3 py-2.5 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-500 dark:text-zinc-400 {{ $stickyTh }}">{{ __('home.fin_items_toggle') }}</th>
                            @foreach($t['rows'] as $row)
                                <td class="px-3 py-2.5 text-center">
                                    @if($row['items'] !== [])
                                        <button type="button" @click="itemsOpen = itemsOpen === '{{ $row['key'] }}' ? null : '{{ $row['key'] }}'"
                                                :class="itemsOpen === '{{ $row['key'] }}' ? 'bg-[#c9a847] text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200'"
                                                class="{{ $pill }}">{{ __('home.fin_items_show') }}</button>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-2.5 text-center bg-[#c9a847]/5">
                                @if($t['yearItems'])
                                    <button type="button" @click="itemsOpen = itemsOpen === 'year' ? null : 'year'"
                                            :class="itemsOpen === 'year' ? 'bg-[#c9a847] text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200'"
                                            class="{{ $pill }}">{{ __('home.fin_items_show') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endif

                    {{-- الملاحظات: زرّ الشهر بعددها، واللوحة تحت الجدول --}}
                    <tr>
                        <th scope="row" class="px-3 py-2.5 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-500 dark:text-zinc-400 {{ $stickyTh }}">{{ __('home.fin_notes_row') }}</th>
                        @if($isLedger)<td></td>@endif
                        @foreach($t['rows'] as $row)
                            @php $key = $prefix.$row['key']; @endphp
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

    @if($isLedger && $t['rows'])
        <p class="text-xs text-zinc-500 dark:text-zinc-400 px-1">{{ __('home.fin_ledger_cell_legend') }}</p>
    @endif

    {{-- لوحات العناصر والملاحظات --}}
    @foreach($t['rows'] as $row)
        @php $key = $prefix.$row['key']; @endphp
        @if($row['items'] !== [])
            <div wire:key="fin-items-{{ $key }}" x-show="itemsOpen === '{{ $row['key'] }}'" style="display:none"
                 class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-4">
                @include('livewire.finance.partials.items-sum-panel', ['items' => $row['items'], 'title' => __('home.fin_items_of_month', ['month' => $row['label']])])
            </div>
        @endif
        <div wire:key="fin-notes-{{ $key }}" x-show="notesOpen === '{{ $key }}'" style="display:none"
             class="rounded-xl border border-sky-100 dark:border-sky-900/40 bg-sky-50/60 dark:bg-sky-900/10 p-4">
            @include('livewire.finance.partials.notes-panel', ['notesTitle' => __('home.fin_notes_of_month', ['month' => $row['label']])])
        </div>
    @endforeach
    @if($t['yearItems'])
        <div wire:key="fin-items-{{ $prefix }}year" x-show="itemsOpen === 'year'" style="display:none"
             class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-4">
            @include('livewire.finance.partials.items-sum-panel', ['items' => $t['yearItems'], 'title' => __('home.fin_items_of_year')])
        </div>
    @endif
</section>
