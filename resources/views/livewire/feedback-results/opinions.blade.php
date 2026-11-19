<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_opinions') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('home.fr_opinions_hint') }}</p>
        </div>
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $rows->total() }}</span>
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- النوع + البحث + التصدير --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="inline-flex rounded-lg border border-zinc-300 dark:border-zinc-600 overflow-hidden text-xs">
            @foreach(['' => __('home.fr_all_types'), 'rating' => __('home.fr_ratings'), 'suggestion' => __('home.fr_suggestions')] as $key => $label)
                <button type="button" wire:click="$set('type', '{{ $key }}')"
                        class="px-3 py-2 font-medium transition {{ \App\Support\FeedbackResults\OpinionsQuery::type($type) === $key
                            ? 'bg-[#c9a847] text-white'
                            : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="max-w-sm flex-1 min-w-50">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.fr_search_placeholder') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        @include('livewire.feedback-results.includes.export-bar')
    </div>

    {{-- Table: نِسَب مئوية مجموعها ١٠٠٪ من الشاشة الصغيرة فما فوق، وعلى الموبايل التاريخ والمواطن تحت المقر --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full sm:min-w-140 sm:table-fixed text-sm text-right max-sm:[&_th]:px-1.5 max-sm:[&_td]:px-1.5">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-3 py-3 font-medium w-[11%] max-sm:hidden">
                        <button type="button" wire:click="toggleSort" class="inline-flex items-center gap-1 hover:text-[#c9a847] transition">
                            {{ __('home.fr_date') }}
                            <svg class="w-3 h-3 {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </th>
                    <th class="px-3 py-3 font-medium w-[9%]">{{ __('home.fr_type') }}</th>
                    <th class="px-3 py-3 font-medium sm:w-[22%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[18%] max-sm:hidden">{{ __('home.fr_citizen') }}</th>
                    <th class="px-3 py-3 font-medium w-[40%] max-sm:hidden">{{ __('home.fr_opinion_text') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($rows as $row)
                    @php
                        $anonymous = \App\Support\FeedbackResults\OpinionsQuery::isAnonymous($row);
                        $rowTopics = $row->kind === 'suggestion' ? ($topics[$row->id] ?? '') : '';
                    @endphp
                    <tr wire:key="op-{{ $row->kind }}-{{ $row->id }}" class="align-top hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap max-sm:hidden">
                            {{ \App\Support\LocalTime::date($row->created_at) }}
                            <span class="block text-xs text-zinc-400">{{ \App\Support\LocalTime::time($row->created_at) }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap
                                {{ $row->kind === 'rating' ? 'bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]' : 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' }}">
                                {{ __('home.fr_type_'.$row->kind) }}
                            </span>
                            @if($row->overall_rating !== null)
                                <span class="block mt-1 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">★ {{ (int) $row->overall_rating }}/5</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 sm:truncate" title="{{ $row->office_name }}">
                                {{ $row->office_name ?? __('home.fr_deleted_office') }}
                            </span>
                            <span class="block text-xs text-zinc-400 sm:truncate">{{ $row->governorate_name ?? '—' }}</span>
                            <div class="sm:hidden mt-1 text-xs text-zinc-500 dark:text-zinc-400"><span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($row->created_at) }}</span> {{ \App\Support\LocalTime::time($row->created_at) }}</div>
                            <div class="sm:hidden mt-1">@include('livewire.feedback-results.includes.opinion-citizen')</div>
                            <div class="sm:hidden mt-2">@include('livewire.feedback-results.includes.opinion-text')</div>
                        </td>
                        <td class="px-3 py-3 max-sm:hidden">
                            @include('livewire.feedback-results.includes.opinion-citizen')
                        </td>
                        <td class="px-3 py-3 max-sm:hidden">
                            @include('livewire.feedback-results.includes.opinion-text')
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-zinc-400">{{ __('home.fr_no_opinions') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>

</div>
