<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_ratings') }}</h1>
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $ratings->total() }}</span>
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
        {{-- table-fixed + نِسَب مئوية مجموعها ١٠٠٪: الجدول = عرض الحاوية بالضبط مهما طال المحتوى.
             min-w يمنع سحق الأعمدة على الشاشات الضيقة (يتحوّل لتمرير أفقي داخل البطاقة). --}}
        <table class="w-full min-w-140 table-fixed text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-3 py-3 font-medium w-[4%] hidden 2xl:table-cell">#</th>
                    <th class="px-3 py-3 font-medium w-[11%]">{{ __('home.fr_date') }}</th>
                    <th class="px-3 py-3 font-medium w-[21%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[16%]">{{ __('home.fr_citizen') }}</th>
                    <th class="px-3 py-3 font-medium w-[10%] hidden xl:table-cell">{{ __('home.fr_wait_time') }}</th>
                    <th class="px-3 py-3 font-medium w-[13%]">{{ __('home.fr_overall_rating') }}</th>
                    <th class="px-3 py-3 font-medium w-[19%] hidden xl:table-cell">{{ __('home.fr_notes') }}</th>
                    <th class="px-3 py-3 font-medium w-[6%]">{{ __('home.fr_details') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($ratings as $rating)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-3 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $ratings->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                            {{ $rating->created_at->format('Y-m-d') }}
                            <span class="block text-xs text-zinc-400">{{ $rating->created_at->format('H:i') }}</span>
                        </td>
                        {{-- المحافظة سطر فرعي تحت المقر بدل عمود مستقل — توفير عرض بلا فقد معلومة --}}
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 truncate"
                                  title="{{ $rating->office?->name }}">
                                {{ $rating->office?->name ?? __('home.fr_deleted_office') }}
                            </span>
                            <span class="block text-xs text-zinc-400 truncate">{{ $rating->governorate?->name ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 truncate"
                                  title="{{ $rating->name }}">{{ $rating->name }}</span>
                            <span class="block text-xs text-zinc-400">{{ $rating->national_id }}</span>
                            <span class="block text-xs text-zinc-400">{{ $rating->phone }}</span>
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 truncate hidden xl:table-cell"
                            title="{{ $waitTimes[$rating->wait_time] ?? $rating->wait_time }}">
                            {{ $waitTimesShort[$rating->wait_time] ?? $rating->wait_time }}
                        </td>
                        {{-- التقييم العام + متوسط المحاور في خلية واحدة (رقمان مختلفان، لكن عمودان مهدرَان) --}}
                        <td class="px-3 py-3">
                            @include('livewire.feedback-results.includes.stars', ['value' => $rating->overall_rating])
                            <span class="block text-xs text-zinc-400 mt-0.5 truncate"
                                  title="{{ __('home.fr_criteria_avg') }}">
                                {{ __('home.fr_criteria_avg_short') }}: {{ $rating->criteriaAverage() ?? '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 hidden xl:table-cell">
                            <span class="block truncate" title="{{ $rating->notes }}">
                                {{ $rating->notes ?: '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <button wire:click="toggle({{ $rating->id }})"
                                    class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                {{ $expanded === $rating->id ? '−' : '+' }}
                            </button>
                        </td>
                    </tr>

                    @if($expanded === $rating->id)
                        <tr class="bg-zinc-50/70 dark:bg-zinc-800/40">
                            <td colspan="8" class="px-6 py-5">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ __('home.fr_criteria') }}
                                    </h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
                                    {{-- مدة الانتظار هنا أيضاً لأن عمودها يختفي على الشاشات الأضيق --}}
                                    <div>
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fr_wait_time') }}</p>
                                        <p class="text-sm text-zinc-800 dark:text-zinc-100">
                                            {{ $waitTimes[$rating->wait_time] ?? $rating->wait_time }}
                                        </p>
                                    </div>
                                    @foreach($criteria as $field => [$label, $optional])
                                        <div>
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ $label }}</p>
                                            @include('livewire.feedback-results.includes.stars', ['value' => $rating->{$field}])
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ __('home.fr_notes') }}
                                    </h3>
                                </div>
                                <p class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">
                                    {{ $rating->notes ?: __('home.fr_no_notes') }}
                                </p>

                                <p class="mt-4 text-xs text-zinc-400">
                                    {{ __('home.fr_ip') }}: {{ $rating->ip_address ?? '—' }}
                                </p>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-400">{{ __('home.fr_no_ratings') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $ratings->links() }}</div>

</div>
