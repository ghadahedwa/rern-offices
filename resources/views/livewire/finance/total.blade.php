{{-- «الإجمالي العام» — كارتٌ لكل حساب ثم جداوله الأربعة تحت بعضها (نموذج وافقت عليه المستخدمة 2026-11-17).
     لا إدخال هنا: الشهور من صفحات الوحدات، والملاحظات وحدها تُكتب. --}}
<div class="max-w-6xl mx-auto p-6 space-y-8">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fin_general_total') }}</h1>
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

    {{-- ── كارتٌ لكل حساب: إجمالي السنة (واتصالات رصيدها الآن) وحالة الشهور — والضغط ينزل لجدوله ── --}}
    <div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ __('home.fin_year_range', ['from' => $yearFrom, 'to' => $yearTo]) }}</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($tables as $t)
                @php $acc = $t['account']; @endphp
                <a href="#fin-{{ $acc->slug() }}" class="block rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-4 hover:border-[#c9a847] transition">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        {{ __('home.fin_tab_'.$acc->key) }}{{ $t['ledger'] ? ' — '.__('home.fin_balance_now_label') : '' }}
                    </p>
                    <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-1" dir="ltr" style="text-align: right; font-size: 1.3rem">
                        {{ number_format($t['ledger'] ? $t['ledger']['yearTotal']['balance'] : $t['yearTotal']) }}
                    </p>
                    @if($t['oldestLate'])
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                            {{ __('home.fin_card_month_of', ['month' => $t['oldestLate']['label'], 'entered' => $t['oldestLate']['entered'], 'count' => $t['required']]) }}
                        </p>
                    @elseif($t['lastEnded'])
                        <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-1">✓ {{ __('home.fin_card_month_complete', ['month' => $t['lastEnded']['label']]) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    @foreach($tables as $t)
        @include('livewire.finance.partials.units-total-table', ['t' => $t, 'n' => $loop->iteration])
    @endforeach

    {{-- حذف ملاحظةٍ بلا سبب --}}
    @include('livewire.partials.delete-modal', ['deletingReasonModel' => ''])
</div>
