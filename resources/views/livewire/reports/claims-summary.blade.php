<div class="max-w-5xl mx-auto p-6 space-y-6">

    @php
        $rateClasses = function ($rate) {
            if ($rate === null) return 'text-zinc-400';
            return $rate >= 80 ? 'text-emerald-600 dark:text-emerald-400'
                : ($rate >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
        };
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 flex items-center justify-center text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.claims_summary_title') }}</h1>
        </div>
        <button type="button" wire:click="exportPdf"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('home.report_pdf') }}
        </button>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_governorate') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_demands_total') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_cancelled_total') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_collected') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_debt_total') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_collection_rate') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($summary['rows'] as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $row['name'] }}</td>
                        <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100">{{ number_format($row['demands'], 2) }}</td>
                        <td class="px-4 py-2.5 text-amber-600 dark:text-amber-400">{{ number_format($row['cancelled'], 2) }}</td>
                        <td class="px-4 py-2.5 text-emerald-600 dark:text-emerald-400">{{ number_format($row['collected'], 2) }}</td>
                        <td class="px-4 py-2.5 font-bold {{ $row['debt'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100' }}">{{ number_format($row['debt'], 2) }}</td>
                        <td class="px-4 py-2.5 font-semibold {{ $rateClasses($row['rate']) }}">{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400">{{ __('home.claims_no_governorates') }}</td></tr>
                    @endforelse
                </tbody>
                @if(count($summary['rows']))
                <tfoot class="bg-[#c9a847]/10 text-zinc-800 dark:text-zinc-100 font-semibold">
                    <tr>
                        <td class="px-4 py-2.5">{{ __('home.report_total') }}</td>
                        <td class="px-4 py-2.5">{{ number_format($summary['totalDemands'], 2) }}</td>
                        <td class="px-4 py-2.5 text-amber-700 dark:text-amber-400">{{ number_format($summary['totalCancelled'], 2) }}</td>
                        <td class="px-4 py-2.5 text-emerald-700 dark:text-emerald-400">{{ number_format($summary['totalCollected'], 2) }}</td>
                        <td class="px-4 py-2.5 {{ $summary['totalDebt'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ number_format($summary['totalDebt'], 2) }}</td>
                        <td class="px-4 py-2.5 {{ $rateClasses($summary['rate']) }}">{{ $summary['rate'] !== null ? $summary['rate'] . '%' : '—' }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <p class="text-xs text-zinc-400 mt-3">{{ __('home.claims_summary_note') }}</p>
    </div>

</div>
