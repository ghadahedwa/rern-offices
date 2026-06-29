<div class="max-w-4xl mx-auto p-6 space-y-6">

    @php
        $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
        $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 flex items-center justify-center text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.claims_statement_title') }}</h1>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_filters') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="{{ $lbl }}">{{ __('home.claims_governorate') }}</label>
                <select wire:model="governorateId" class="{{ $inp }}">
                    <option value="">— {{ __('home.claims_select_placeholder') }} —</option>
                    @foreach($governorates as $gov)
                        <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                    @endforeach
                </select>
                @error('governorateId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="{{ $lbl }}">{{ __('home.report_from') }} — {{ __('home.claims_year') }}</label>
                <select wire:model="fromYear" class="{{ $inp }}">
                    @foreach($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.claims_month') }}</label>
                <select wire:model="fromMonth" class="{{ $inp }}">
                    @foreach($months as $num => $name)<option value="{{ $num }}">{{ $name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.report_to') }} — {{ __('home.claims_year') }}</label>
                <select wire:model="toYear" class="{{ $inp }}">
                    @foreach($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.claims_month') }}</label>
                <select wire:model="toMonth" class="{{ $inp }}">
                    @foreach($months as $num => $name)<option value="{{ $num }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
        @error('fromYear') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

        <div class="flex items-center gap-2">
            <button type="button" wire:click="search"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition cursor-pointer">
                {{ __('home.report_search') }}
            </button>
            @if($statement)
            <button type="button" wire:click="exportPdf"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('home.report_pdf') }}
            </button>
            @endif
        </div>
    </div>

    {{-- Statement --}}
    @if($statement)
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5 space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                {{ $statement['governorate']->name }}
            </h3>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_year') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_month') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_demands_total') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_cancelled_total') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_collected') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('home.claims_statement_balance') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    {{-- رصيد افتتاحي --}}
                    <tr class="bg-zinc-50/60 dark:bg-zinc-800/40">
                        <td colspan="5" class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 font-medium">{{ __('home.claims_statement_opening') }}</td>
                        <td class="px-4 py-2.5 font-semibold {{ $statement['opening'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100' }}">{{ number_format($statement['opening'], 2) }}</td>
                    </tr>
                    @forelse($statement['rows'] as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $row['year'] }}</td>
                        <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $months[$row['month']] ?? $row['month'] }}</td>
                        <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100">{{ $row['demand'] ? number_format($row['demand'], 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-amber-600 dark:text-amber-400">{{ $row['cancelled'] ? number_format($row['cancelled'], 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-emerald-600 dark:text-emerald-400">{{ $row['collected'] ? number_format($row['collected'], 2) : '—' }}</td>
                        <td class="px-4 py-2.5 font-semibold {{ $row['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100' }}">{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400">{{ __('home.claims_empty') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-[#c9a847]/10 text-zinc-800 dark:text-zinc-100 font-semibold">
                    <tr>
                        <td colspan="2" class="px-4 py-2.5">{{ __('home.report_total') }}</td>
                        <td class="px-4 py-2.5">{{ number_format($statement['totalDemand'], 2) }}</td>
                        <td class="px-4 py-2.5 text-amber-700 dark:text-amber-400">{{ number_format($statement['totalCancelled'], 2) }}</td>
                        <td class="px-4 py-2.5 text-emerald-700 dark:text-emerald-400">{{ number_format($statement['totalCollected'], 2) }}</td>
                        <td class="px-4 py-2.5 {{ $statement['closing'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ number_format($statement['closing'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-wrap gap-x-8 gap-y-1 text-xs text-zinc-400">
            <p>
                {{ __('home.claims_statement_period_net') }}:
                <span class="font-semibold {{ $statement['periodNet'] < 0 ? 'text-red-500' : 'text-zinc-600 dark:text-zinc-300' }}">{{ number_format($statement['periodNet'], 2) }} {{ __('home.claims_currency') }}</span>
            </p>
            <p>
                {{ __('home.claims_statement_closing') }}:
                <span class="font-semibold {{ $statement['closing'] < 0 ? 'text-red-500' : 'text-zinc-600 dark:text-zinc-300' }}">{{ number_format($statement['closing'], 2) }} {{ __('home.claims_currency') }}</span>
            </p>
        </div>
    </div>
    @endif

</div>
