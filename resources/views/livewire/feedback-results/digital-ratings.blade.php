<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_digital') }}</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('feedback-results.digital-summary', $this->filterSet()->toQuery()) }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition whitespace-nowrap">
                {{ __('home.fr_dg_summary') }}
            </a>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $rows->total() }}</span>
        </div>
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- المسار + البحث + السلة + التصدير --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="inline-flex rounded-lg border border-zinc-300 dark:border-zinc-600 overflow-hidden text-xs">
            @foreach(['' => __('home.fr_dg_path_all'), 'booked' => __('home.fr_dg_path_booked'), 'walk_in' => __('home.fr_dg_path_walk_in')] as $value => $label)
                <button type="button" wire:click="$set('path', '{{ $value }}')"
                        class="px-3 py-2 transition {{ $path === $value
                            ? 'bg-[#c9a847] text-white'
                            : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="max-w-sm flex-1 min-w-50">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.fr_dg_search_placeholder') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        @include('livewire.feedback-results.includes.trash-toggle')
        @include('livewire.feedback-results.includes.export-bar')
    </div>

    @if($this->viewingTrash())
        <p class="text-xs text-zinc-500 dark:text-zinc-400 border-r-2 border-[#c9a847] pr-3">
            {{ __('home.fr_trash_note') }}
        </p>
    @endif

    @php $pageIds = $rows->pluck('id')->all(); @endphp
    @include('livewire.feedback-results.includes.bulk-bar', ['pageIds' => $pageIds, 'total' => $rows->total()])

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full min-w-140 table-fixed text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    @include('livewire.feedback-results.includes.bulk-th', ['pageIds' => $pageIds])
                    <th class="px-3 py-3 font-medium w-[5%] hidden 2xl:table-cell">#</th>
                    @include('livewire.partials.sortable-th', [
                        'column' => 'created_at', 'label' => __('home.fr_date'), 'thClass' => 'w-[12%]',
                    ])
                    <th class="px-3 py-3 font-medium w-[25%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[19%]">{{ __('home.fr_citizen') }}</th>
                    <th class="px-3 py-3 font-medium w-[18%]">{{ __('home.fr_dg_path') }}</th>
                    @include('livewire.partials.sortable-th', [
                        'column' => 'q205', 'label' => __('home.fr_dg_q205'), 'thClass' => 'w-[10%]',
                    ])
                    <th class="px-3 py-3 font-medium w-[7%]">{{ __('home.fr_details') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr class="transition {{ $expanded === $row->id
                        ? 'bg-[#c9a847]/10 dark:bg-[#c9a847]/15'
                        : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        @include('livewire.feedback-results.includes.bulk-td', ['rowId' => $row->id])
                        <td class="px-3 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $rows->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                            {{ \App\Support\LocalTime::date($row->created_at) }}
                            <span class="block text-xs text-zinc-400">{{ \App\Support\LocalTime::time($row->created_at) }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 truncate" title="{{ $row->office?->name }}">
                                {{ $row->office?->name ?? __('home.fr_deleted_office') }}
                            </span>
                            <span class="block text-xs text-zinc-400 truncate">{{ $row->governorate?->name ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3">
                            @include('livewire.feedback-results.includes.citizen-cell', ['row' => $row])
                        </td>
                        {{-- المسار: الحجز ومنصته، أو بدون حجز ومعرفته بالمنصة --}}
                        <td class="px-3 py-3">
                            <span class="block text-zinc-800 dark:text-zinc-100">{{ $row->answerLabel('q201') }}</span>
                            <span class="block text-xs text-zinc-400 truncate">
                                @if($row->q201 === 'booked')
                                    {{ $row->answerLabel('q202') ?? '—' }}
                                @else
                                    {{ $row->q208 === 'yes' ? __('home.fr_dg_knows_platform') : __('home.fr_dg_not_knows_platform') }}
                                @endif
                            </span>
                        </td>
                        <td class="px-3 py-3 text-zinc-800 dark:text-zinc-100">
                            @if($row->q205 !== null)
                                <span class="font-semibold">{{ $row->q205 }}</span><span class="text-xs text-zinc-400"> / 10</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <button wire:click="toggle({{ $row->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md border transition
                                        {{ $expanded === $row->id
                                            ? 'border-[#c9a847] bg-[#c9a847] text-white'
                                            : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                                <svg class="w-4 h-4 transition-transform {{ $expanded === $row->id ? 'rotate-180' : '' }}"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </td>
                    </tr>

                    @if($expanded === $row->id)
                        <tr class="bg-[#c9a847]/10 dark:bg-[#c9a847]/15 border-t-0! border-b-2 border-b-zinc-300 dark:border-b-zinc-600">
                            <td colspan="{{ $this->canDelete() ? 8 : 7 }}" class="px-4 pt-0 pb-5">
                              <div class="rounded-lg border border-[#c9a847]/30 bg-white dark:bg-zinc-900 p-5 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ __('home.fr_dg_answers') }}
                                    </h3>
                                </div>

                                {{-- الأسئلة التي سُئلها وحدها، بنصّها كما رآه المواطن --}}
                                <div class="space-y-3">
                                    @foreach($row->askedQuestions() as $key)
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-1 md:gap-4 pb-3 border-b border-zinc-100 dark:border-zinc-800 last:border-0 last:pb-0">
                                            <p class="md:col-span-3 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                                <span class="text-zinc-400">{{ ltrim($key, 'q') }} —</span> {{ $questions[$key][1] }}
                                            </p>
                                            <p class="md:col-span-2 text-sm text-zinc-800 dark:text-zinc-100 whitespace-pre-line">{{ $row->answerLabel($key) ?? __('home.fr_dg_no_text') }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="mt-4 text-xs text-zinc-400">
                                    {{ __('home.fr_ip') }}: {{ $row->ip_address ?? '—' }}
                                </p>
                              </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $this->canDelete() ? 8 : 7 }}" class="px-4 py-10 text-center text-zinc-400">{{ __('home.fr_dg_no_rows') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
