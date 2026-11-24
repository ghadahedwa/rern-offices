<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_msg_'.$messageType) }}</h1>
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $messages->total() }}</span>
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- Search + سلة المحذوفات --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="max-w-sm flex-1 min-w-50">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.fr_msg_search_placeholder') }}"
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

    @php
        $pageIds = $messages->pluck('id')->all();
        $columns = ($this->canDelete() ? 1 : 0) + ($hasEmployee ? 7 : 6);
    @endphp
    @include('livewire.feedback-results.includes.bulk-bar', ['pageIds' => $pageIds, 'total' => $messages->total()])

    {{-- Table — نِسَب مجموعها ١٠٠٪ (مع عمود الموظف وبدونه)، والموبايل: التاريخ والمواطن سطران تحت المقر --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full sm:min-w-140 sm:table-fixed text-sm text-right max-sm:[&_th]:px-1.5 max-sm:[&_td]:px-1.5">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    @include('livewire.feedback-results.includes.bulk-th', ['pageIds' => $pageIds])
                    <th class="px-3 py-3 font-medium w-[4%] hidden 2xl:table-cell">#</th>
                    @include('livewire.partials.sortable-th', [
                        'column' => 'created_at', 'label' => __('home.fr_date'), 'thClass' => 'w-[11%] max-sm:hidden',
                    ])
                    <th class="px-3 py-3 font-medium sm:w-[22%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[18%] max-sm:hidden">{{ __('home.fr_citizen') }}</th>
                    @if($hasEmployee)
                        <th class="px-3 py-3 font-medium sm:w-[15%]">{{ __('home.fr_msg_employee') }}</th>
                    @endif
                    <th class="px-3 py-3 font-medium {{ $hasEmployee ? 'w-[24%]' : 'w-[39%]' }} hidden xl:table-cell">{{ __('home.fr_msg_body') }}</th>
                    <th class="px-3 py-3 font-medium w-[6%]">{{ __('home.fr_details') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($messages as $msg)
                    <tr class="transition {{ $expanded === $msg->id
                        ? 'bg-[#c9a847]/10 dark:bg-[#c9a847]/15'
                        : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        @include('livewire.feedback-results.includes.bulk-td', ['rowId' => $msg->id])
                        <td class="px-3 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $messages->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap max-sm:hidden">
                            {{ \App\Support\LocalTime::date($msg->created_at) }}
                            <span class="block text-xs text-zinc-400">{{ \App\Support\LocalTime::time($msg->created_at) }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100 sm:truncate" title="{{ $msg->office?->name }}">
                                {{ $msg->office?->name ?? ($msg->office_id ? __('home.fr_deleted_office') : '—') }}
                            </span>
                            <span class="block text-xs text-zinc-400 sm:truncate">{{ $msg->governorate?->name ?? '—' }}</span>
                            <div class="sm:hidden mt-1 text-xs text-zinc-500 dark:text-zinc-400"><span dir="ltr" class="inline-block">{{ \App\Support\LocalTime::date($msg->created_at) }}</span> {{ \App\Support\LocalTime::time($msg->created_at) }}</div>
                            <div class="sm:hidden mt-1">@include('livewire.feedback-results.includes.citizen-cell', ['row' => $msg])</div>
                        </td>
                        <td class="px-3 py-3 max-sm:hidden">
                            @include('livewire.feedback-results.includes.citizen-cell', ['row' => $msg])
                        </td>
                        @if($hasEmployee)
                            <td class="px-3 py-3 text-zinc-700 dark:text-zinc-200">
                                <span class="block sm:truncate" title="{{ $msg->employee_name }}">{{ $msg->employee_name ?: '—' }}</span>
                            </td>
                        @endif
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 hidden xl:table-cell">
                            <span class="block truncate" title="{{ $msg->body }}">{{ $msg->body }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <button wire:click="toggle({{ $msg->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md border transition
                                        {{ $expanded === $msg->id
                                            ? 'border-[#c9a847] bg-[#c9a847] text-white'
                                            : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                                <svg class="w-4 h-4 transition-transform {{ $expanded === $msg->id ? 'rotate-180' : '' }}"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </td>
                    </tr>

                    @if($expanded === $msg->id)
                        <tr class="bg-[#c9a847]/10 dark:bg-[#c9a847]/15 border-t-0! border-b-2 border-b-zinc-300 dark:border-b-zinc-600">
                            <td colspan="{{ $columns }}" class="px-4 pt-0 pb-5">
                              <div class="rounded-lg border border-[#c9a847]/30 bg-white dark:bg-zinc-900 p-5 shadow-sm">
                                @if($hasEmployee)
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fr_msg_employee') }}</p>
                                    <p class="text-sm text-zinc-800 dark:text-zinc-100 mb-4">{{ $msg->employee_name ?: '—' }}</p>
                                @endif

                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        {{ __('home.fr_msg_body') }}
                                    </h3>
                                </div>
                                <p class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">{{ $msg->body }}</p>

                                <p class="mt-4 text-xs text-zinc-400">
                                    {{ __('home.fr_ip') }}: {{ $msg->ip_address ?? '—' }}
                                </p>
                              </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $columns }}" class="px-4 py-10 text-center text-zinc-400">{{ __('home.fr_no_messages') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $messages->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
