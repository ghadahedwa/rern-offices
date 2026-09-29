<div class="space-y-4">

    {{-- Filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="3">
        <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('home.wh_supplier') }}" />

        <x-filter-input type="date" :label="__('home.wh_date_from')" wire:model.live="dateFrom" />
        <x-filter-input type="date" :label="__('home.wh_date_to')" wire:model.live="dateTo" />

        <x-slot:shortcuts>
            <x-period-shortcuts :options="$this->periodOptions()" :active="$this->activePeriod()" />
        </x-slot:shortcuts>
    </x-filter-bar>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    @include('livewire.partials.sortable-th', ['column' => 'received_at', 'label' => __('home.wh_received_at')])
                    @include('livewire.partials.sortable-th', ['column' => 'supplier',    'label' => __('home.wh_supplier')])
                    @include('livewire.partials.sortable-th', ['column' => 'items_count', 'label' => __('home.items_title')])
                    <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($incomings as $incoming)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $incoming->received_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $incoming->supplier_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $incoming->items_count }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="viewIncoming({{ $incoming->id }})"
                                    class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                {{ __('home.view') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $incomings->links() }}</div>

    {{-- View modal --}}
    <div x-show="$wire.showViewIncoming"
         x-transition.opacity
         @click.self="$wire.showViewIncoming = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between px-5 py-3.5 bg-zinc-100 dark:bg-zinc-800">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.wh_incoming') }}</h3>
                <button type="button" @click="$wire.showViewIncoming = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition text-base leading-none">×</button>
            </div>
            @if($viewingIncoming)
                <div class="bg-white dark:bg-zinc-900 px-5 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.wh_received_at') }}</p>
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $viewingIncoming->received_at->format('Y-m-d') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.wh_supplier') }}</p>
                            <p class="text-zinc-800 dark:text-zinc-100">{{ $viewingIncoming->supplier_name ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-lg overflow-hidden">
                        @foreach($viewingIncoming->items as $line)
                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $line->item?->name ?? '—' }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $line->quantity }} {{ $line->item?->unit?->name }}</span>
                            </div>
                        @endforeach
                    </div>

                    @can('warehouses.attachments')
                        <livewire:warehouses.document-attachments type="incoming" :document-id="$viewingIncoming->id" :key="'att-incoming-'.$viewingIncoming->id" />
                    @endcan
                </div>
            @endif
        </div>
    </div>

</div>
