@php
    $card = 'rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5';
@endphp

<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('feedback-results.digital', $this->filterSet()->toQuery()) }}" wire:navigate
               class="w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition"
               title="{{ __('home.fr_digital') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_dg_summary') }}</h1>
        </div>
        @include('livewire.feedback-results.includes.export-bar')
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="{{ $card }}">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_dg_kpi_total') }}</p>
            <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $headline['total'] }}</p>
        </div>
        <div class="{{ $card }}">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_dg_kpi_booked') }}</p>
            <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $headline['booked_percent'] !== null ? $headline['booked_percent'].'%' : '—' }}</p>
            <p class="text-xs text-zinc-400 mt-1">{{ __('home.fr_dg_count_of_base', ['count' => $headline['booked'], 'base' => $headline['total']]) }}</p>
        </div>
        <div class="{{ $card }}">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_dg_kpi_score') }}</p>
            <div class="flex items-baseline gap-2">
                <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $headline['score_avg'] ?? '—' }}</p>
                <span class="text-xs text-zinc-400">{{ __('home.fr_dg_of_ten') }}</span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">{{ __('home.fr_dg_base', ['base' => $headline['score_base']]) }}</p>
        </div>
        <div class="{{ $card }}">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_dg_kpi_on_time') }}</p>
            <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $headline['on_time_percent'] !== null ? $headline['on_time_percent'].'%' : '—' }}</p>
            <p class="text-xs text-zinc-400 mt-1">{{ __('home.fr_dg_count_of_base', ['count' => $headline['on_time'], 'base' => $headline['on_time_base']]) }}</p>
        </div>
    </div>

    @if($headline['total'] === 0)
        <div class="{{ $card }}">
            <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
        </div>
    @else

    {{-- المحاور بترتيب الاستمارة --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- الحجز --}}
        <div class="{{ $card }}">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_dg_sec_booking') }}</h3>
            </div>
            <div class="space-y-6">
                @foreach($sections['booking'] as $dist)
                    @include('livewire.feedback-results.includes.digital-dist', ['dist' => $dist])
                @endforeach
            </div>
        </div>

        {{-- درجة المنصة --}}
        <div class="{{ $card }}">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ $score['title'] }}</h3>
                </div>
                <span class="text-xs text-zinc-400">{{ __('home.fr_dg_base', ['base' => $score['base']]) }}</span>
            </div>

            @if($score['base'] === 0)
                <p class="text-sm text-zinc-400 py-6 text-center">{{ __('home.fr_no_data') }}</p>
            @else
                <div class="flex items-baseline gap-2 mb-5">
                    <p class="text-3xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $score['avg'] }}</p>
                    <span class="text-sm text-zinc-400">{{ __('home.fr_dg_of_ten') }}</span>
                </div>

                <p class="text-xs text-zinc-400 mb-2">{{ __('home.fr_dg_score_distribution') }}</p>
                {{-- أعمدة الدرجات: ارتفاع كل عمود نسبته من أكبر عمود — المقارنة بين الدرجات لا الحجم المطلق --}}
                @php $maxCount = max(1, collect($score['distribution'])->max('count')); @endphp
                <div class="grid grid-cols-11 gap-1.5 items-end h-28" dir="ltr">
                    @foreach($score['distribution'] as $d)
                        <div class="flex flex-col items-center justify-end h-full gap-1" title="{{ $d['count'] }} · {{ $d['percent'] }}%">
                            <span class="text-[10px] text-zinc-400">{{ $d['count'] ?: '' }}</span>
                            <div class="w-full rounded-t bg-[#c9a847]" style="height: {{ round($d['count'] * 100 / $maxCount) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="grid grid-cols-11 gap-1.5 mt-1" dir="ltr">
                    @foreach($score['distribution'] as $d)
                        <span class="text-center text-xs text-zinc-500">{{ $d['score'] }}</span>
                    @endforeach
                </div>

                <p class="text-xs text-zinc-400 mt-6 mb-2">{{ __('home.fr_dg_by_platform') }}</p>
                <div class="space-y-2">
                    @foreach($score['platforms'] as $p)
                        <div class="flex items-center justify-between gap-3 py-1.5 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $p['label'] }}</span>
                            <span class="text-sm">
                                <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $p['avg'] ?? '—' }}</span>
                                <span class="text-xs text-zinc-400">· {{ __('home.fr_dg_base', ['base' => $p['count']]) }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- الزيارة والميعاد --}}
        <div class="{{ $card }}">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_dg_sec_visit') }}</h3>
            </div>
            <div class="space-y-6">
                @foreach($sections['visit'] as $dist)
                    @include('livewire.feedback-results.includes.digital-dist', ['dist' => $dist])
                @endforeach
            </div>
        </div>

        {{-- معرفة المنصات --}}
        <div class="{{ $card }}">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_dg_sec_awareness') }}</h3>
            </div>
            <div class="space-y-6">
                @foreach($sections['awareness'] as $dist)
                    @include('livewire.feedback-results.includes.digital-dist', ['dist' => $dist])
                @endforeach
            </div>
        </div>

        {{-- الإعلان والحملة --}}
        <div class="{{ $card }} lg:col-span-2">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_dg_sec_ads') }}</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($sections['ads'] as $dist)
                    @include('livewire.feedback-results.includes.digital-dist', ['dist' => $dist])
                @endforeach
            </div>
        </div>

        {{-- النصوص الحرة — قراءة وبحث فقط (قرار المستخدمة) --}}
        @foreach($texts as $block)
            <div class="{{ $card }}">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ $block['title'] }}</h3>
                    </div>
                    <a href="{{ route('feedback-results.digital', $this->filterSet()->toQuery()) }}" wire:navigate class="text-xs text-[#c9a847] hover:underline">{{ __('home.fr_view_all') }}</a>
                </div>
                <p class="text-xs text-zinc-400 mb-4">{{ __('home.fr_dg_texts_written', ['written' => $block['written'], 'base' => $block['base']]) }}</p>

                @forelse($block['items'] as $item)
                    <div class="py-2.5 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                        <p class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">{{ $item['text'] }}</p>
                        <p class="text-xs text-zinc-400 mt-1">{{ $item['office'] }} — {{ \App\Support\LocalTime::date($item['date']) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 py-4 text-center">{{ __('home.fr_no_data') }}</p>
                @endforelse
            </div>
        @endforeach
    </div>

    {{-- الاتجاه الشهري --}}
    <div class="{{ $card }}">
        <div class="flex items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_dg_trend') }}</h3>
            </div>
            @if($from === '' && $to === '')
                <span class="text-xs text-zinc-400">{{ __('home.fr_last_12_months') }}</span>
            @endif
        </div>
        <p class="text-xs text-zinc-400 mb-5">{{ __('home.fr_dg_trend_hint') }}</p>

        <div class="overflow-x-auto">
            <table class="w-full min-w-140 table-fixed text-sm text-right">
                <thead class="text-xs text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-3 py-2 font-medium w-[18%]">{{ __('home.fr_export_month') }}</th>
                        <th class="px-3 py-2 font-medium w-[16%]">{{ __('home.fr_export_opinions_count') }}</th>
                        <th class="px-3 py-2 font-medium w-[46%]">{{ __('home.fr_dg_kpi_booked') }}</th>
                        <th class="px-3 py-2 font-medium w-[20%]">{{ __('home.fr_dg_kpi_score') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($trend as $m)
                        <tr>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200" dir="ltr">{{ $m['label'] }}</td>
                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $m['count'] }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                        <div class="h-full bg-[#c9a847] rounded-full" style="width: {{ $m['booked_percent'] ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-zinc-500 w-24 text-left">{{ $m['booked_percent'] }}% ({{ $m['booked'] }})</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $m['score_avg'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- مقارنة المقرات بنسبة الحجز --}}
    <div class="{{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.fr_dg_offices') }}</h3>
            </div>
            <button type="button" wire:click="toggleOfficeOrder"
                    class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                {{ $this->safeOfficeOrder() === 'desc' ? __('home.fr_dg_order_desc') : __('home.fr_dg_order_asc') }}
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-140 table-fixed text-sm text-right">
                <thead class="text-xs text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-3 py-2 font-medium w-[34%]">{{ __('home.fr_office') }}</th>
                        <th class="px-3 py-2 font-medium w-[12%]">{{ __('home.fr_export_opinions_count') }}</th>
                        <th class="px-3 py-2 font-medium w-[30%]">{{ __('home.fr_dg_kpi_booked') }}</th>
                        <th class="px-3 py-2 font-medium w-[12%]">{{ __('home.fr_dg_kpi_score') }}</th>
                        <th class="px-3 py-2 font-medium w-[12%]">{{ __('home.fr_export_sample_col', ['min' => $minSample]) }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($offices as $o)
                        <tr class="{{ $o['enough'] ? '' : 'bg-zinc-50 dark:bg-zinc-800/40' }}">
                            <td class="px-3 py-2">
                                <span class="block text-zinc-800 dark:text-zinc-100 truncate" title="{{ $o['office'] }}">{{ $o['office'] }}</span>
                                <span class="block text-xs text-zinc-400 truncate">{{ $o['governorate'] }}</span>
                            </td>
                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $o['count'] }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                        <div class="h-full rounded-full {{ $o['enough'] ? 'bg-[#c9a847]' : 'bg-zinc-300 dark:bg-zinc-600' }}" style="width: {{ $o['booked_percent'] ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-zinc-500 w-20 text-left">{{ $o['booked_percent'] }}% ({{ $o['booked'] }})</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">{{ $o['score_avg'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs {{ $o['enough'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}">
                                {{ $o['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif

</div>
