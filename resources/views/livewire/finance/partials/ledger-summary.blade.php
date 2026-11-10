{{-- اتصالات: الرصيد الحالي بالتفقيط، وتحته إيراد السنة ومنصرفها والافتتاحي --}}
<div class="sm:col-span-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
    <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ $balanceAfter ? __('home.fin_balance_now_after', ['month' => $balanceAfter]) : __('home.fin_balance_now') }}
    </p>
    <p class="font-bold text-zinc-800 dark:text-zinc-100 mt-1" dir="ltr" style="text-align: right; font-size: 2rem; line-height: 2.5rem">{{ $balanceNow !== null ? number_format($balanceNow) : '—' }}</p>
    @if($balanceNow)
        <p class="text-xs text-[#b8962e] dark:text-[#d8b856] mt-1">{{ \App\Support\Finance\AmountInWords::pounds($balanceNow) }}</p>
    @endif
    <div class="mt-4 grid grid-cols-3 gap-3 text-xs border-t border-zinc-100 dark:border-zinc-800 pt-3">
        <div>
            <p class="text-zinc-400">{{ __('home.fin_year_income', ['year' => \App\Support\Finance\FinanceCalendar::fiscalYearLabel($year)]) }}</p>
            <p dir="ltr" class="text-right font-semibold text-emerald-700 dark:text-emerald-400" style="font-size: 1rem">{{ number_format($yearIncome) }}</p>
        </div>
        <div>
            <p class="text-zinc-400">{{ __('home.fin_year_expense', ['year' => \App\Support\Finance\FinanceCalendar::fiscalYearLabel($year)]) }}</p>
            <p dir="ltr" class="text-right font-semibold text-red-600 dark:text-red-400" style="font-size: 1rem">{{ number_format($yearExpense) }}</p>
        </div>
        <div>
            <p class="text-zinc-400">{{ __('home.fin_opening_balance') }}</p>
            <p dir="ltr" class="text-right font-semibold text-zinc-700 dark:text-zinc-200" style="font-size: 1rem">{{ $account->opening_balance !== null ? number_format($account->opening_balance) : '—' }}</p>
        </div>
    </div>
    <p class="text-xs text-zinc-400 mt-3">{{ __('home.fin_entered_of', ['entered' => $entered, 'count' => $monthCount]) }} · {{ __('home.fin_year_range', ['from' => $yearFrom, 'to' => $yearTo]) }}</p>
</div>
