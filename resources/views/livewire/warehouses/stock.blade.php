<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_stock') }}</h1>
    </div>

    {{-- Filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="3">
        <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('home.item_name') }}" />

        <x-filter-select :label="__('home.warehouse')" wire:model.live="warehouseFilter">
            <option value="">{{ __('home.wh_all_warehouses') }}</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-select :label="__('home.item_category')" wire:model.live="categoryFilter">
            <option value="">{{ __('home.item_category_all') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
            <option value="none">{{ __('home.item_category_none') }}</option>
        </x-filter-select>
    </x-filter-bar>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    @include('livewire.partials.sortable-th', ['column' => 'warehouse', 'label' => __('home.warehouse')])
                    @include('livewire.partials.sortable-th', ['column' => 'item',      'label' => __('home.item_name')])
                    @include('livewire.partials.sortable-th', ['column' => 'unit',      'label' => __('home.item_unit')])
                    @include('livewire.partials.sortable-th', ['column' => 'quantity',  'label' => __('home.wh_current_balance')])
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($stocks as $stock)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $stocks->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                            {{ $stock->warehouse->name }}
                            @if($stock->warehouse->type)
                                <span class="text-xs text-zinc-400">({{ $stock->warehouse->type->name }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $stock->item->name }}</td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $stock->item->unit?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-[#b8962e] tabular-nums">{{ number_format($stock->quantity) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_data') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $stocks->links() }}</div>

</div>
