<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fr_rejected') }}</h1>
        <span class="text-xs text-zinc-400">{{ __('home.fr_rejected_retention_note', ['days' => $retentionDays]) }}</span>
    </div>

    {{-- ملخص الأسباب --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach(\App\Livewire\FeedbackResults\RejectedAttempts::REASONS as $reasonKey)
            <button type="button" wire:click="$set('reason', '{{ $reason === $reasonKey ? '' : $reasonKey }}')"
                    class="text-right rounded-xl border bg-white dark:bg-zinc-900 shadow-sm p-5 transition
                        {{ $reason === $reasonKey ? 'border-[#c9a847] ring-1 ring-[#c9a847]/40' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.fr_reason_'.$reasonKey) }}</p>
                <p class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $reasonCounts[$reasonKey] ?? 0 }}</p>
            </button>
        @endforeach
    </div>

    @include('livewire.feedback-results.includes.filters')

    {{-- Search + type filter --}}
    <div class="flex flex-wrap items-end gap-4">
        <div class="max-w-sm flex-1 min-w-50">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.fr_search_placeholder_plain') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        <div class="w-48">
            <select wire:model.live="type"
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                <option value="">{{ __('home.fr_type') }}</option>
                @foreach($types as $t)
                    <option value="{{ $t }}">{{ __('home.fr_type_'.$t) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @php $pageIds = $attempts->pluck('id')->all(); @endphp
    @include('livewire.feedback-results.includes.bulk-bar', ['pageIds' => $pageIds, 'total' => $attempts->total()])

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        {{-- نفس مبدأ باقي شاشات الموديول: نِسَب مئوية مجموعها ١٠٠٪ + min-w --}}
        <table class="w-full min-w-140 table-fixed text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    @include('livewire.feedback-results.includes.bulk-th', ['pageIds' => $pageIds])
                    <th class="px-3 py-3 font-medium w-[4%] hidden 2xl:table-cell">#</th>
                    <th class="px-3 py-3 font-medium w-[11%]">{{ __('home.fr_date') }}</th>
                    <th class="px-3 py-3 font-medium w-[9%]">{{ __('home.fr_type') }}</th>
                    <th class="px-3 py-3 font-medium w-[16%]">{{ __('home.fr_reason') }}</th>
                    <th class="px-3 py-3 font-medium w-[24%]">{{ __('home.fr_office') }}</th>
                    <th class="px-3 py-3 font-medium w-[18%]">{{ __('home.fr_citizen') }}</th>
                    <th class="px-3 py-3 font-medium w-[14%] hidden xl:table-cell">{{ __('home.fr_ip') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($attempts as $attempt)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        @include('livewire.feedback-results.includes.bulk-td', ['rowId' => $attempt->id])
                        <td class="px-3 py-3 text-zinc-500 hidden 2xl:table-cell">{{ $attempts->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                            {{ \App\Support\LocalTime::date($attempt->created_at) }}
                            <span class="block text-xs text-zinc-400">{{ \App\Support\LocalTime::time($attempt->created_at) }}</span>
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ __('home.fr_type_'.$attempt->type) }}</td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                                {{ __('home.fr_reason_'.$attempt->reason) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-zinc-800 dark:text-zinc-100">
                            <span class="block truncate" title="{{ $attempt->office?->name }}">
                                {{ $attempt->office?->name ?? __('home.fr_deleted_office') }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300 text-xs">
                            <span class="block">{{ $attempt->national_id ?? '—' }}</span>
                            <span class="block text-zinc-400">{{ $attempt->phone ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-zinc-500 text-xs hidden xl:table-cell" title="{{ $attempt->user_agent }}">
                            <span class="block truncate">{{ $attempt->ip_address ?? '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-400">{{ __('home.fr_no_rejected') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $attempts->links() }}</div>

</div>
