{{-- ملخص المطالبات المالي --}}
<div class="space-y-3">
    <div class="flex items-center gap-3">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ __('home.claims_dashboard_title') }}
        </h3>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

            {{-- إجمالي المطالبات --}}
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0 mt-0.5">
                    <flux:icon.document-text variant="outline" class="w-4 h-4 text-[#b8962e] dark:text-[#c9a847]" />
                </div>
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.claims_demands_total') }}</p>
                    <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($claimsDemands, 2) }}</p>
                    <p class="text-[11px] text-zinc-400">{{ __('home.claims_currency') }}</p>
                </div>
            </div>

            {{-- ما تم تحصيله --}}
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center shrink-0 mt-0.5">
                    <flux:icon.banknotes variant="outline" class="w-4 h-4 text-emerald-500" />
                </div>
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.claims_collected') }}</p>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($claimsCollected, 2) }}</p>
                    <p class="text-[11px] text-zinc-400">{{ __('home.claims_currency') }}</p>
                </div>
            </div>

            {{-- المديونية --}}
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-lg {{ $claimsDebt < 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-zinc-100 dark:bg-zinc-800' }} flex items-center justify-center shrink-0 mt-0.5">
                    <flux:icon.scale variant="outline" class="w-4 h-4 {{ $claimsDebt < 0 ? 'text-red-500' : 'text-zinc-400' }}" />
                </div>
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.claims_debt_total') }}</p>
                    <p class="text-xl font-bold {{ $claimsDebt < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100' }}">{{ number_format($claimsDebt, 2) }}</p>
                    <p class="text-[11px] text-zinc-400">{{ __('home.claims_currency') }}</p>
                </div>
            </div>

            {{-- نسبة التحصيل --}}
            <div class="flex items-start gap-2">
                @php
                    $rateColor = $claimsRate === null
                        ? 'text-zinc-400'
                        : ($claimsRate >= 80 ? 'text-emerald-600 dark:text-emerald-400'
                            : ($claimsRate >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'));
                    $rateBg = $claimsRate === null
                        ? 'bg-zinc-100 dark:bg-zinc-800'
                        : ($claimsRate >= 80 ? 'bg-emerald-50 dark:bg-emerald-900/20'
                            : ($claimsRate >= 50 ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-red-50 dark:bg-red-900/20'));
                @endphp
                <div class="w-8 h-8 rounded-lg {{ $rateBg }} flex items-center justify-center shrink-0 mt-0.5">
                    <flux:icon.chart-pie variant="outline" class="w-4 h-4 {{ $rateColor }}" />
                </div>
                <div>
                    <p class="text-xs text-zinc-400 mb-0.5">{{ __('home.claims_collection_rate') }}</p>
                    <p class="text-xl font-bold {{ $rateColor }}">{{ $claimsRate !== null ? $claimsRate . '%' : '—' }}</p>
                </div>
            </div>

        </div>
    </div>
</div>
