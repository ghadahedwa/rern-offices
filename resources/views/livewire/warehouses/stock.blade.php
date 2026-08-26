<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_stock') }}</h1>
    </div>

    {{-- Filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="5">
        <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('home.item_name') }} / {{ __('home.item_code') }}" />

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

        <x-filter-select :label="__('home.item_unit')" wire:model.live="unitFilter">
            <option value="">{{ __('home.item_unit_all') }}</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
            <option value="none">{{ __('home.item_unit_none') }}</option>
        </x-filter-select>

        <x-filter-select :label="__('home.wh_current_balance')" wire:model.live="balanceFilter">
            <option value="">—</option>
            <option value="positive">{{ __('home.wh_balance_positive') }}</option>
            <option value="zero">{{ __('home.wh_balance_zero') }}</option>
        </x-filter-select>

        {{-- الحد الأدنى يُقاس على المخازن الرئيسية وحدها — الخانة تقول ذلك صراحةً --}}
        <label class="sm:col-span-2 lg:col-span-5 inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer">
            <input type="checkbox" wire:model.live="lowOnly"
                   class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]">
            {{ __('home.wh_below_min_only') }}
            <span class="text-xs text-zinc-400">({{ __('home.wh_low_stock_main_only') }})</span>
        </label>
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
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $stock->item->name }}
                            @if($stock->item->code)
                                <span class="mr-2 inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-normal text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">{{ $stock->item->code }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $stock->item->unit?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-[#b8962e] tabular-nums">{{ number_format($stock->quantity) }}</span>
                            {{-- نفس قاعدة البروفايل: الشارة على الرئيسي وحده --}}
                            @if($stock->warehouse->type?->level === 1 && $stock->item->min_stock !== null && $stock->quantity <= $stock->item->min_stock)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30 ms-2">
                                    {{ __('home.wh_below_min_stock') }}
                                </span>
                            @endif
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
