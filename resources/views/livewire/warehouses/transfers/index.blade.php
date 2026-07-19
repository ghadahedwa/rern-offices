<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_transfers') }}</h1>
        @if($canCreate)
            <a href="{{ route('warehouses.transfers.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.wh_transfer_add') }}
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="max-w-sm flex-1 min-w-50">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.search') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        <div class="flex flex-col gap-1">
            <input wire:model.live="dateFrom" type="date"
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        <div class="flex flex-col gap-1">
            <input wire:model.live="dateTo" type="date"
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_transferred_at') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_from_warehouse') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_to_warehouse') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_document_type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.items_title') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($transfers as $transfer)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $transfer->transferred_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $transfer->fromWarehouse?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $transfer->toWarehouse?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $transfer->document_type ?: '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $transfer->items->count() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button wire:click="view({{ $transfer->id }})"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                    {{ __('home.view') }}
                                </button>
                                @if($canDelete)
                                    <button wire:click="askDelete({{ $transfer->id }})"
                                            class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $transfers->links() }}</div>

    @include('livewire.partials.delete-modal')

    {{-- View modal --}}
    <div x-show="$wire.showView"
         x-transition.opacity
         @click.self="$wire.showView = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between px-5 py-3.5 bg-zinc-100 dark:bg-zinc-800">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.wh_transfers') }}</h3>
                <button type="button" @click="$wire.showView = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition text-base leading-none">×</button>
            </div>
            @if($viewing)
                <div class="bg-white dark:bg-zinc-900 px-5 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.wh_from_warehouse') }}</p>
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $viewing->fromWarehouse?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.wh_to_warehouse') }}</p>
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $viewing->toWarehouse?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.wh_transferred_at') }}</p>
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $viewing->transferred_at->format('Y-m-d') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.wh_document_type') }}</p>
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $viewing->document_type ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-lg overflow-hidden">
                        @foreach($viewing->items as $line)
                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $line->item?->name ?? '—' }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $line->quantity }} {{ $line->item?->unit?->name }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($canAttach)
                        <a href="{{ asset('storage/' . $viewing->attachment_path) }}" target="_blank"
                           class="inline-flex items-center gap-2 text-sm text-[#b8962e] hover:text-[#c9a847] font-medium transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ $viewing->attachment_original_name }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>
