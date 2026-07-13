<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.meetings_title') }}</h1>
        <div class="flex items-center gap-2">
            {{-- زر «طباعة اليوم» مخفي مؤقتاً عن العميل — لإعادة إظهاره: احذف @if(false) --}}
            @if(false && $dateFilter)
                <a href="{{ route('meetings.print', ['date' => $dateFilter]) }}" target="_blank"
                   class="inline-flex items-center gap-2 border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    {{ __('home.print_day') }}
                </a>
            @endif
            @if($canCreate)
                <a href="{{ route('meetings.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('home.add_meeting') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Search + date filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('home.meetings_search_placeholder') }}"
               class="flex-1 min-w-[200px] max-w-sm border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        <div class="flex items-center gap-2">
            <label class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('home.meeting_date') }}:</label>
            <input wire:model.live="dateFilter" type="date"
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
            @if($dateFilter)
                <button wire:click="$set('dateFilter', '')" class="text-xs text-zinc-400 hover:text-red-500 transition">✕</button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right whitespace-nowrap">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.meeting_date') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.meeting_subject') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.meeting_location') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.meeting_concerned_party') }}</th>
                    @if($canEdit || $canDelete)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($meetings as $meeting)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $meetings->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 text-zinc-800 dark:text-zinc-100">
                            {{ optional($meeting->date)->format('Y-m-d') }}
                            <span class="block text-xs text-zinc-400">{{ optional($meeting->date)->locale('ar')->dayName }}@if($meeting->time) · {{ substr($meeting->time, 0, 5) }}@endif</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100" title="{{ $meeting->subject }}">
                            <div style="max-width:280px; white-space:normal; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden;">{{ $meeting->subject }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300" title="{{ $meeting->location }}">
                            <div style="max-width:200px; white-space:normal; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden;">{{ $meeting->location ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            @forelse($meeting->attendees as $att)
                                <span class="block">{{ $att->name }}@if($att->title)<span class="text-xs text-zinc-400"> ({{ $att->title }})</span>@endif</span>
                            @empty
                                —
                            @endforelse
                        </td>
                        @if($canEdit || $canDelete)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($canEdit)
                                    <a href="{{ route('meetings.edit', $meeting) }}" wire:navigate
                                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.edit') }}
                                    </a>
                                    @endif
                                    @if($canDelete)
                                    <button wire:click="askDelete({{ $meeting->id }})"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($canEdit || $canDelete) ? 6 : 5 }}" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_meetings') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $meetings->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
