{{-- اتصالات: جدول الشهور بالمرحَّل والإيراد والمنصرف والرصيد — الأرصدة من `TelecomLedger::chain`
     (لحظية لا مخزَّنة)، وأول صفّ في السنة الأولى الرصيد الافتتاحي ويُصحَّح كالشهر. --}}
@php
    $lockIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><title>'.e(__('home.fin_locked')).'</title><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>';
    $cols = 7;
@endphp
<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-right sm:table-fixed">
        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
            <tr>
                <th class="px-4 py-3 font-medium sm:w-[18%]">{{ __('home.fin_month') }}</th>
                <th class="px-3 py-3 font-medium sm:w-[13%] max-sm:hidden">{{ __('home.fin_carried_in') }}</th>
                <th class="px-3 py-3 font-medium sm:w-[13%]">{{ __('home.fin_income') }}</th>
                <th class="px-3 py-3 font-medium sm:w-[13%]">{{ __('home.fin_expense') }}</th>
                <th class="px-3 py-3 font-medium sm:w-[13%]">{{ __('home.fin_balance') }}</th>
                <th class="px-3 py-3 font-medium sm:w-[16%] max-sm:hidden">{{ __('home.fin_payment_order') }}</th>
                <th class="px-3 py-3 font-medium sm:w-[14%] max-sm:hidden">{{ __('home.fin_entered_by') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700" x-data="{ open: null }">
            @if($showOpening)
                @php $key = \App\Support\Finance\FinanceHistory::OPENING; $events = $history->get($key, collect()); $record = null; @endphp
                <tr wire:key="fin-row-opening" class="bg-zinc-50/60 dark:bg-zinc-800/30">
                    <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300" colspan="4">
                        <span class="inline-flex items-center gap-1.5">{{ __('home.fin_opening_row') }} {!! $lockIcon !!}</span>
                        @include('livewire.finance.partials.history-badge')
                    </td>
                    <td class="px-3 py-3"><span dir="ltr" class="font-semibold text-zinc-700 dark:text-zinc-200">{{ number_format($account->opening_balance) }}</span></td>
                    <td class="px-3 py-3 max-sm:hidden"></td>
                    <td class="px-3 py-3 max-sm:hidden text-xs">
                        <p class="text-zinc-800 dark:text-zinc-100">{{ $openingBy ?? '—' }}</p>
                        @if($canCorrect && ! $correctingOpening)
                            <button type="button" wire:click="startOpeningCorrection"
                                    class="mt-2 inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                                {{ __('home.fin_correct') }}
                            </button>
                        @endif
                    </td>
                </tr>
                @if($correctingOpening)
                    {{-- تصحيح الافتتاحي في مكانه — بسببٍ إجباري، ويُرفض إن صار شهرٌ سالباً --}}
                    <tr wire:key="fin-opening-correct" class="bg-amber-50/50 dark:bg-amber-900/10">
                        <td colspan="{{ $cols }}" class="px-4 py-4">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                                <div>
                                    <label class="block text-xs text-zinc-500 mb-1">{{ __('home.fin_opening_balance') }}</label>
                                    <input type="text" inputmode="numeric" dir="ltr" wire:model="openingInput"
                                           oninput="this.value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9.,٬،]/g, '')"
                                           class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-left font-semibold bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100" />
                                    @error('openingInput') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-zinc-500 mb-1">{{ __('home.fin_reason_label') }} <span class="text-red-500">*</span></label>
                                    <textarea wire:model="openingReason" rows="1" class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"></textarea>
                                    @error('openingReason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-3">
                                <button type="button" wire:click="correctOpening" wire:loading.attr="disabled" wire:target="correctOpening"
                                        class="bg-[#c9a847] hover:bg-[#b8962e] disabled:opacity-50 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition">{{ __('home.fin_opening_correct_save') }}</button>
                                <button type="button" wire:click="$set('correctingOpening', false)" class="text-sm text-zinc-500 hover:text-zinc-700">{{ __('home.cancel') }}</button>
                            </div>
                        </td>
                    </tr>
                @endif
                @include('livewire.finance.partials.history-rows')
            @endif

            @foreach($rows as $row)
                @php
                    $record = $row['record'];
                    $key    = $row['month']->format('Y-m');
                    $events = $history->get($key, collect());
                    $calc   = $ledger[$key] ?? null;
                @endphp
                @if($record)
                    <tr wire:key="fin-row-{{ $key }}">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-100">{{ $row['label'] }} {!! $lockIcon !!}</span>
                            @include('livewire.finance.partials.history-badge')
                            <div class="sm:hidden mt-2 space-y-2">
                                <p class="text-xs text-zinc-400">{{ __('home.fin_carried_in') }}: <span dir="ltr">{{ number_format($calc['carried'] ?? 0) }}</span></p>
                                @include('livewire.finance.partials.payment-orders-cell', ['record' => $record])
                                @include('livewire.finance.partials.month-actions')
                            </div>
                        </td>
                        <td class="px-3 py-3 max-sm:hidden text-zinc-400" dir="ltr" style="text-align: right">{{ number_format($calc['carried'] ?? 0) }}</td>
                        <td class="px-3 py-3 font-semibold text-emerald-700 dark:text-emerald-400" dir="ltr" style="text-align: right">{{ number_format((int) $record->income) }}</td>
                        <td class="px-3 py-3 font-semibold text-red-600 dark:text-red-400" dir="ltr" style="text-align: right">{{ number_format($record->amount) }}</td>
                        <td class="px-3 py-3 font-bold text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align: right; font-size: 1.05rem">{{ number_format($calc['balance'] ?? 0) }}</td>
                        <td class="px-3 py-3 max-sm:hidden">
                            @include('livewire.finance.partials.payment-orders-cell', ['record' => $record])
                        </td>
                        <td class="px-3 py-3 max-sm:hidden text-xs">
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $record->creator?->name ?? '—' }}</p>
                            <p class="text-zinc-500 dark:text-zinc-400 mt-0.5"><span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($record->created_at) }}</span></p>
                            @include('livewire.finance.partials.month-actions')
                        </td>
                    </tr>
                    @include('livewire.finance.partials.history-rows')
                @else
                    <tr wire:key="fin-row-{{ $key }}" class="bg-[#c9a847]/10">
                        <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $row['label'] }}
                            @include('livewire.finance.partials.history-badge')
                        </td>
                        <td class="px-3 py-3" colspan="{{ $cols - 1 }}">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-medium text-[#b8962e] dark:text-[#d8b856]">{{ __('home.fin_awaiting_entry') }}</span>
                                @if($canEnter)
                                    <a href="{{ route('finance.entry', $account->slug()) }}" wire:navigate
                                       class="inline-flex items-center gap-1 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                        + {{ __('home.fin_enter_short') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @include('livewire.finance.partials.history-rows')
                @endif
            @endforeach

            @if($later)
                <tr wire:key="fin-row-later" class="bg-zinc-50/60 dark:bg-zinc-800/30">
                    <td class="px-4 py-3 text-xs text-zinc-400" colspan="{{ $cols }}">{{ __('home.fin_later_months', $later) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
