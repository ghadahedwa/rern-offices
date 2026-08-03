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
                    @include('livewire.feedback-results.includes.sortable-th', [
                        'column' => 'created_at', 'label' => __('home.fr_date'), 'thClass' => 'w-[11%]',
                    ])
                    <th class="px-3 py-3 font-medium w-[24%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[18%]">{{ __('home.fr_citizen') }}</th>
                    @include('livewire.feedback-results.includes.sortable-th', [
                        'column' => 'topics_count', 'label' => __('home.fr_topics_count'), 'thClass' => 'w-[10%]',
                    ])
                    <th class="px-3 py-3 font-medium w-[27%] hidden xl:table-cell">{{ __('home.fr_other_suggestion') }}</th>
                    <th class="px-3 py-3 font-medium w-[6%]">{{ __('home.fr_details') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($suggestions as $suggestion)
                    {{-- الصف المفتوح + صف تفاصيله يُقرآن ككتلة واحدة (نفس مبدأ شاشة التقييمات) --}}
                    <tr class="transition {{ $expanded === $suggestion->id
                        ? 'bg-[#c9a847]/10 dark:bg-[#c9a847]/15'
                        : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        <td class="px-3 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $suggestions->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                            {{ \App\Support\LocalTime::date($suggestion->created_at) }}
                            <span class="block text-xs text-zinc-400">{{ \App\Support\LocalTime::time($suggestion->created_at) }}</span>
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
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md border transition
                                        {{ $expanded === $suggestion->id
                                            ? 'border-[#c9a847] bg-[#c9a847] text-white'
                                            : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                                <svg class="w-4 h-4 transition-transform {{ $expanded === $suggestion->id ? 'rotate-180' : '' }}"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </td>
                    </tr>

                    @if($expanded === $suggestion->id)
                        {{-- border-t-0!: يلغي فاصل divide-y فيلتحم بالصف الأعلى · border-b-2: يقفل الكتلة --}}
                        <tr class="bg-[#c9a847]/10 dark:bg-[#c9a847]/15 border-t-0! border-b-2 border-b-zinc-300 dark:border-b-zinc-600">
                            <td colspan="7" class="px-4 pt-0 pb-5">
                              <div class="rounded-lg border border-[#c9a847]/30 bg-white dark:bg-zinc-900 p-5 shadow-sm">
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
                              </div>
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
