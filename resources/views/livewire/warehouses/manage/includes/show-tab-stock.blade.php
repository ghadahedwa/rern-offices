<div class="space-y-4">

    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="2">
        <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('home.item_name') }}" />
    </x-filter-bar>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    @include('livewire.partials.sortable-th', ['column' => 'item',     'label' => __('home.item_name')])
                    @include('livewire.partials.sortable-th', ['column' => 'unit',     'label' => __('home.item_unit')])
                    @include('livewire.partials.sortable-th', ['column' => 'quantity', 'label' => __('home.wh_current_balance')])
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($stocks as $stock)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $stock->item->name }}</td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $stock->item->unit?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-[#b8962e] tabular-nums">{{ number_format($stock->quantity) }}</span>
                            @if($warehouse->isMain() && $stock->item->min_stock !== null && $stock->quantity <= $stock->item->min_stock)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30 ms-2">
                                    {{ __('home.wh_below_min_stock') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $stocks->links() }}</div>

</div>
