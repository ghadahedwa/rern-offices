<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_suggestions') }}</h1>
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $suggestions->total() }}</span>
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- Search --}}
    <div class="max-w-sm">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('home.fr_search_placeholder') }}"
               class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        {{-- نفس مبدأ شاشة التقييمات: نِسَب مئوية مجموعها ١٠٠٪ + min-w --}}
        <table class="w-full min-w-140 table-fixed text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-3 py-3 font-medium w-[4%] hidden 2xl:table-cell">#</th>
                    <th class="px-3 py-3 font-medium w-[11%]">{{ __('home.fr_date') }}</th>
                    <th class="px-3 py-3 font-medium w-[24%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[18%]">{{ __('home.fr_citizen') }}</th>
                    <th class="px-3 py-3 font-medium w-[10%]">{{ __('home.fr_topics_count') }}</th>
                    <th class="px-3 py-3 font-medium w-[27%] hidden xl:table-cell">{{ __('home.fr_other_suggestion') }}</th>
                    <th class="px-3 py-3 font-medium w-[6%]">{{ __('home.fr_details') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($suggestions as $suggestion)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-3 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $suggestions->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                            {{ $suggestion->created_at->format('Y-m-d') }}
                            <span class="block text-xs text-zinc-400">{{ $suggestion->created_at->format('H:i') }}</span>
                        </td>
                        {{-- المحافظة سطر فرعي تحت المقر — نفس ترتيب شاشة التقييمات --}}
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 truncate"
                                  title="{{ $suggestion->office?->name }}">
                                {{ $suggestion->office?->name ?? __('home.fr_deleted_office') }}
                            </span>
                            <span class="block text-xs text-zinc-400 truncate">{{ $suggestion->governorate?->name ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 truncate"
                                  title="{{ $suggestion->name }}">{{ $suggestion->name }}</span>
                            <span class="block text-xs text-zinc-400">{{ $suggestion->national_id }}</span>
                            <span class="block text-xs text-zinc-400">{{ $suggestion->phone }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $suggestion->topics_count > 0 ? 'bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400' }}">
                                {{ $suggestion->topics_count }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 hidden xl:table-cell">
                            <span class="block truncate" title="{{ $suggestion->other_suggestion }}">
                                {{ $suggestion->other_suggestion ?: '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <button wire:click="toggle({{ $suggestion->id }})"
                                    class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                {{ $expanded === $suggestion->id ? '−' : '+' }}
                            </button>
                        </td>
                    </tr>

                    @if($expanded === $suggestion->id)
                        <tr class="bg-zinc-50/70 dark:bg-zinc-800/40">
                            <td colspan="7" class="px-6 py-5">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ __('home.fr_topics') }}
                                    </h3>
                                </div>

                                @php $byDomain = $suggestion->topics->groupBy(fn ($t) => $t->domain?->name ?? '—'); @endphp

                                @forelse($byDomain as $domainName => $topics)
                                    <div class="mb-4">
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1.5">{{ $domainName }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($topics as $topic)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-[#c9a847]/12 text-[#b8962e] dark:text-[#c9a847] border border-[#c9a847]/30">
                                                    {{ $topic->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-400 mb-4">{{ __('home.fr_no_topics') }}</p>
                                @endforelse

                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ __('home.fr_other_suggestion') }}
                                    </h3>
                                </div>
                                <p class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">
                                    {{ $suggestion->other_suggestion ?: '—' }}
                                </p>

                                <p class="mt-4 text-xs text-zinc-400">
                                    {{ __('home.fr_ip') }}: {{ $suggestion->ip_address ?? '—' }}
                                </p>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">{{ __('home.fr_no_suggestions') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $suggestions->links() }}</div>

</div>
