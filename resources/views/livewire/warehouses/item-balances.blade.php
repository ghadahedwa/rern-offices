<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_item_balances_title') }}</h1>
    </div>

    {{-- Filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="5">
        <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('home.item_name') }} / {{ __('home.item_code') }}" />

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

        <x-filter-select :label="__('home.wh_total_quantity')" wire:model.live="balanceFilter">
            <option value="">—</option>
            <option value="positive">{{ __('home.wh_balance_positive') }}</option>
            <option value="zero">{{ __('home.wh_balance_zero') }}</option>
        </x-filter-select>

        <x-filter-select :label="__('home.warehouse_status')" wire:model.live="statusFilter">
            <option value="">—</option>
            <option value="yes">{{ __('home.warehouse_active') }}</option>
            <option value="no">{{ __('home.warehouse_inactive') }}</option>
        </x-filter-select>

        {{-- الحد الأدنى يُقاس على المخزن الرئيسي وحده — الخانة تقول ذلك صراحةً --}}
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
                    @include('livewire.partials.sortable-th', ['column' => 'name',       'label' => __('home.item_name')])
                    @include('livewire.partials.sortable-th', ['column' => 'category',   'label' => __('home.item_category')])
                    @include('livewire.partials.sortable-th', ['column' => 'unit',       'label' => __('home.item_unit')])
                    @include('livewire.partials.sortable-th', ['column' => 'total',      'label' => __('home.wh_total_in_all_warehouses')])
                    @include('livewire.partials.sortable-th', ['column' => 'warehouses', 'label' => __('home.wh_warehouses_with_stock')])
                    @include('livewire.partials.sortable-th', ['column' => 'main',       'label' => __('home.wh_main_quantity')])
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($items as $item)
                    @php
                        $total = (int) $item->total_quantity;
                        $main  = (int) $item->main_quantity;
                        // نفس قاعدة الشاشات الأربع الأخرى: الحد الأدنى على الرئيسي وحده
                        $belowMin = $item->min_stock !== null && $main <= $item->min_stock;
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $items->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">
                            <a href="{{ route('warehouses.items.show', $item) }}" wire:navigate
                               class="underline decoration-dotted decoration-zinc-300 dark:decoration-zinc-600 underline-offset-4 hover:text-[#c9a847] hover:decoration-[#c9a847] transition">{{ $item->name }}</a>
                            @if($item->code)
                                <span class="mr-2 inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-normal text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">{{ $item->code }}</span>
                            @endif
                            @unless($item->is_active)
                                <span class="mr-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-700/40">{{ __('home.warehouse_inactive') }}</span>
                            @endunless
                        </td>
                        <td class="px-4 py-3">
                            @if($item->category)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-[#8a7020] bg-[#c9a847]/15 dark:text-[#e0c76b] dark:bg-[#c9a847]/10">{{ $item->category->name }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('home.item_category_none') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $item->unit?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($total === 0)
                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                            @else
                                <span class="font-bold text-[#b8962e] tabular-nums">{{ number_format($total) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 tabular-nums">{{ number_format((int) $item->warehouses_count) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-zinc-600 dark:text-zinc-300 tabular-nums">{{ number_format($main) }}</span>
                            @if($belowMin)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30 ms-2">
                                    {{ __('home.wh_below_min_stock') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.wh_no_items') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $items->links() }}</div>

</div>
