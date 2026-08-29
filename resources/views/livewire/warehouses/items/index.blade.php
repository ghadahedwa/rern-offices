<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.items_title') }}</h1>
        @if($canManage)
            <a href="{{ route('items.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_item') }}
            </a>
        @endif
    </div>

    {{-- Search + filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()">
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

        <x-filter-select :label="__('home.warehouse_status')" wire:model.live="statusFilter">
            <option value="">—</option>
            <option value="yes">{{ __('home.warehouse_active') }}</option>
            <option value="no">{{ __('home.warehouse_inactive') }}</option>
        </x-filter-select>
    </x-filter-bar>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    @include('livewire.partials.sortable-th', ['column' => 'name',      'label' => __('home.item_name')])
                    @include('livewire.partials.sortable-th', ['column' => 'category',  'label' => __('home.item_category')])
                    @include('livewire.partials.sortable-th', ['column' => 'unit',      'label' => __('home.item_unit')])
                    @include('livewire.partials.sortable-th', ['column' => 'min_stock', 'label' => __('home.item_min_stock')])
                    @include('livewire.partials.sortable-th', ['column' => 'status',    'label' => __('home.warehouse_status')])
                    @if($canManage)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($items as $item)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $items->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">
                            {{-- الاسم رابطٌ يبدو رابطاً: التسطير المنقّط أهدأ من اللون
                                 على ٣٧٧ صفاً، ويبقى ظاهراً بلا مرور الماوس --}}
                            <a href="{{ route('warehouses.items.show', $item) }}" wire:navigate
                               class="underline decoration-dotted decoration-zinc-300 dark:decoration-zinc-600 underline-offset-4 hover:text-[#c9a847] hover:decoration-[#c9a847] transition">{{ $item->name }}</a>
                            @if($item->code)
                                <span class="mr-2 inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-normal text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">{{ $item->code }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($item->category)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-[#8a7020] bg-[#c9a847]/15 dark:text-[#e0c76b] dark:bg-[#c9a847]/10">{{ $item->category->name }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30">{{ __('home.item_category_none') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->unit?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->min_stock ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($item->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30">{{ __('home.warehouse_active') }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-700/40">{{ __('home.warehouse_inactive') }}</span>
                            @endif
                        </td>
                        @if($canManage)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    {{-- «عرض» أولاً كنمط جداول المشروع — الاسم رابطٌ كذلك،
                                         والزرّ هو ما تقع عليه العين في عمود العمليات --}}
                                    <a href="{{ route('warehouses.items.show', $item) }}" wire:navigate
                                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
                                        {{ __('home.view') }}
                                    </a>
                                    <a href="{{ route('items.edit', $item) }}" wire:navigate
                                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.edit') }}
                                    </a>
                                    <button
                                        wire:click="askDelete({{ $item->id }})"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_data') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $items->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
