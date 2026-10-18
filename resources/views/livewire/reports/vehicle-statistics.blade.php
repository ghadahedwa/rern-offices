<div class="max-w-5xl mx-auto p-6 space-y-6">

    @include('livewire.partials.no-governorates-notice')

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_vehicle_statistics_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_vehicle_statistics_hint') }}</p>
            </div>
        </div>
        <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            <span class="max-sm:hidden">{{ __('home.report_reset_filters') }}</span>
        </button>
    </div>

    {{-- الفلاتر --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_basic_filters') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'governorateIds', 'options' => $governorates->pluck('name', 'id')->all(), 'label' => __('home.governorate_name')])
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('home.vstat_report_year') }}</label>
                <select wire:model="year"
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- زر البحث --}}
    <div class="flex items-center justify-center gap-3">
        <button type="button" wire:click="search"
                class="inline-flex items-center justify-center gap-2 px-8 py-2.5 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-semibold transition shadow-sm">
            <svg wire:loading.remove wire:target="search" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ __('home.report_search_btn') }}
        </button>
    </div>

    {{-- النتائج --}}
    @if($report)
    @php
        $groups  = $report->groups();
        $grand   = $report->grandTotals();
        $y       = $report->year();
        $partial = $report->isPartial();
        $fmt     = fn ($v) => $v === null ? '—' : number_format($v);
        $num     = 'px-2 py-2.5 text-center';
    @endphp
    <div class="space-y-3">
        @if($groups->isNotEmpty())
        <div class="flex items-center justify-between gap-2 flex-wrap">
            @if($partial)
            <p class="text-xs text-amber-700 dark:text-amber-400">{{ __('home.vstat_report_partial_note', ['year' => $y]) }}</p>
            @else
            <span></span>
            @endif
            <div class="flex items-center gap-2">
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-900/20 transition disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('home.report_export_excel') }}
                </button>
                <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('home.report_export_pdf') }}
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <table class="w-full text-sm text-right sm:table-fixed max-sm:[&_th]:px-1.5 max-sm:[&_td]:px-1.5">
                <thead class="text-xs">
                    <tr class="bg-[#c9a847] text-white">
                        {{-- الترتيب داخل كل محافظة — الرأس الذهبي يحتاج لوناً نشطاً أبيض مسطَّراً لا الذهبي الافتراضي --}}
                        @php $gold = ['activeClass' => 'text-white underline underline-offset-4', 'hoverClass' => 'hover:text-white/80']; @endphp
                        {{-- النِّسَب مجموعها ١٠٠٪ في الحالتين: ٢٨+٣٠+١٣+١٣+١٦ · ٢٢+٣٠+١١+١١+١٣+١٣ --}}
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'name', 'label' => __('home.vehicle_name'), 'thClass' => ($partial ? 'sm:w-[28%]' : 'sm:w-[22%]') . ' font-semibold'])
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'avg_tx', 'label' => __('home.vstat_col_avg_tx'), 'thClass' => 'w-[10%] text-center max-sm:hidden'])
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'avg_support', 'label' => __('home.vstat_col_avg_support'), 'thClass' => 'w-[10%] text-center max-sm:hidden'])
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'avg_trips', 'label' => __('home.vstat_col_avg_trips'), 'thClass' => 'w-[10%] text-center max-sm:hidden'])
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'trips', 'label' => __('home.vstat_col_trips', ['year' => $y]), 'thClass' => ($partial ? 'sm:w-[13%]' : 'sm:w-[11%]') . ' text-center'])
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'instant', 'label' => __('home.vstat_col_instant_trips', ['year' => $y]), 'thClass' => ($partial ? 'sm:w-[13%]' : 'sm:w-[11%]') . ' text-center'])
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'tx', 'label' => __('home.vstat_col_tx', ['year' => $y]) . ($partial ? ' (' . __('home.vstat_so_far') . ')' : ''), 'thClass' => ($partial ? 'sm:w-[16%]' : 'sm:w-[13%]') . ' text-center bg-[#b8962e]'])
                        @unless($partial)
                        @include('livewire.partials.sortable-th', $gold + ['column' => 'tx_prev', 'label' => __('home.vstat_col_tx', ['year' => $y - 1]), 'thClass' => 'sm:w-[13%] text-center'])
                        @endunless
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($groups as $group)
                        <tr class="bg-zinc-50 dark:bg-zinc-800/70">
                            <td colspan="{{ $partial ? 7 : 8 }}" class="px-3 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ $group['governorate']->name ?? '—' }}</td>
                        </tr>
                        @foreach($group['vehicles'] as $row)
                            @php $v = $row['vehicle']; @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('vehicles.show', $v) }}" wire:navigate
                                       class="block font-medium text-zinc-800 dark:text-zinc-100 hover:text-[#b8962e] sm:truncate" title="{{ $v->name }}">{{ $v->name }}</a>
                                    <span class="sm:hidden block text-xs text-zinc-400 mt-0.5">{{ __('home.vstat_mobile_avgs', ['tx' => $fmt($v->avg_daily_transactions), 'support' => $fmt($v->avg_daily_support), 'trips' => $fmt($v->avg_daily_trips)]) }}</span>
                                </td>
                                <td class="{{ $num }} text-zinc-600 dark:text-zinc-300 max-sm:hidden">{{ $fmt($v->avg_daily_transactions) }}</td>
                                <td class="{{ $num }} text-zinc-600 dark:text-zinc-300 max-sm:hidden">{{ $fmt($v->avg_daily_support) }}</td>
                                <td class="{{ $num }} text-zinc-600 dark:text-zinc-300 max-sm:hidden">{{ $fmt($v->avg_daily_trips) }}</td>
                                <td class="{{ $num }} font-medium text-zinc-800 dark:text-zinc-100">{{ $fmt($row['trips']) }}</td>
                                <td class="{{ $num }} font-medium text-zinc-800 dark:text-zinc-100">{{ $fmt($row['instant']) }}</td>
                                <td class="{{ $num }} font-semibold text-zinc-800 dark:text-zinc-100 bg-[#c9a847]/5">{{ $fmt($row['tx']) }}</td>
                                @unless($partial)
                                <td class="{{ $num }} text-zinc-500 dark:text-zinc-400">{{ $fmt($row['txPrev']) }}</td>
                                @endunless
                            </tr>
                        @endforeach
                        {{-- إجمالي المحافظة — المتوسطات لا تُجمع --}}
                        <tr class="bg-[#c9a847]/10 font-semibold text-zinc-700 dark:text-zinc-200 text-xs">
                            <td class="px-3 py-2">{{ __('home.vstat_gov_total', ['name' => $group['governorate']->name ?? '']) }}</td>
                            <td class="max-sm:hidden"></td><td class="max-sm:hidden"></td><td class="max-sm:hidden"></td>
                            <td class="{{ $num }}">{{ $fmt($group['totals']['trips']) }}</td>
                            <td class="{{ $num }}">{{ $fmt($group['totals']['instant']) }}</td>
                            <td class="{{ $num }}">{{ $fmt($group['totals']['tx']) }}</td>
                            @unless($partial)<td class="{{ $num }}">{{ $fmt($group['totals']['txPrev']) }}</td>@endunless
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#c9a847]/25 font-bold text-zinc-800 dark:text-zinc-100">
                        <td class="px-3 py-3">{{ __('home.report_total') }}</td>
                        <td class="max-sm:hidden"></td><td class="max-sm:hidden"></td><td class="max-sm:hidden"></td>
                        <td class="{{ $num }}">{{ $fmt($grand['trips']) }}</td>
                        <td class="{{ $num }}">{{ $fmt($grand['instant']) }}</td>
                        <td class="{{ $num }} text-[#b8962e] dark:text-[#c9a847]">{{ $fmt($grand['tx']) }}</td>
                        @unless($partial)<td class="{{ $num }}">{{ $fmt($grand['txPrev']) }}</td>@endunless
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.vstat_report_footnote') }}</p>
        @else
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.no_vehicles') }}</p>
        </div>
        @endif
    </div>
    @else
    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.report_search_prompt') }}</p>
    </div>
    @endif

    {{-- keepalive --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
