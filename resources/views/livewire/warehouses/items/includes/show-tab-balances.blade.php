{{-- تاب «الرصيد في المخازن»: كل المخازن لا التي للصنف فيها رصيد وحدها --}}

<x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()" :columns="3">
    <x-filter-input :label="__('home.search')" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('home.warehouse_name') }}" />

    <x-filter-select :label="__('home.warehouse_type')" wire:model.live="warehouseTypeFilter">
        <option value="">{{ __('home.wh_all_types') }}</option>
        @foreach($warehouseTypes as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
    </x-filter-select>

    <x-filter-select :label="__('home.wh_current_balance')" wire:model.live="balanceFilter">
        <option value="">—</option>
        <option value="positive">{{ __('home.wh_balance_positive') }}</option>
        <option value="zero">{{ __('home.wh_balance_zero') }}</option>
    </x-filter-select>
</x-filter-bar>

<div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
    <table class="w-full text-sm text-right">
        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
            <tr>
                <th class="px-4 py-3 font-medium">#</th>
                @include('livewire.partials.sortable-th', ['column' => 'warehouse',   'label' => __('home.warehouse')])
                @include('livewire.partials.sortable-th', ['column' => 'type',        'label' => __('home.warehouse_type')])
                @include('livewire.partials.sortable-th', ['column' => 'governorate', 'label' => __('home.warehouse_governorate')])
                @include('livewire.partials.sortable-th', ['column' => 'quantity',    'label' => __('home.wh_current_balance')])
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
            @forelse($balances as $warehouse)
                @php $quantity = (int) $warehouse->stock_quantity; @endphp
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                    <td class="px-4 py-3 text-zinc-500">{{ $balances->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">
                        {{-- صار البروفايل يُفتح بـ`warehouses.index` أيضاً، وصفوف هذا
                             التاب كلها داخل نطاق المستخدم — فالرابط للجميع، ويبدو رابطاً --}}
                        <a href="{{ route('warehouse-manage.show', $warehouse) }}" wire:navigate
                           class="underline decoration-dotted decoration-zinc-300 dark:decoration-zinc-600 underline-offset-4 hover:text-[#c9a847] hover:decoration-[#c9a847] transition">{{ $warehouse->name }}</a>
                        @unless($warehouse->is_active)
                            <span class="mr-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-zinc-500 bg-zinc-100 dark:text-zinc-400 dark:bg-zinc-700/40">{{ __('home.warehouse_inactive') }}</span>
                        @endunless
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $warehouse->type?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $warehouse->governorate?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($quantity === 0)
                            {{-- الصفر شرطةً كما في الدفتر — والرقم وحده يلفت العين لما فيه رصيد --}}
                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                        @else
                            <span class="font-bold text-[#b8962e] tabular-nums">{{ number_format($quantity) }}</span>
                        @endif

                        {{-- نفس قاعدة الأرصدة والبروفايل: الشارة على الرئيسي وحده --}}
                        @if($warehouse->type?->level === 1 && $item->min_stock !== null && $quantity <= $item->min_stock)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30 ms-2">
                                {{ __('home.wh_below_min_stock') }}
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-zinc-400">
                        {{-- الجدول يعرض المخازن كلها، فخلوّه بلا فلتر يعني ألّا مخزن أصلاً --}}
                        {{ $this->hasActiveFilters() ? __('home.wh_item_no_warehouses') : __('home.wh_item_no_warehouses_any') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($summary['total'] === 0 && $summary['withStock'] === 0)
    {{-- جوابٌ صريح لسؤال الشاشة: لا مكان — بدل أن يقرأه المستخدم من ثلاثين شرطة --}}
    <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('home.wh_item_none_anywhere') }}</p>
@else
    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_item_zero_dash_hint') }}</p>
@endif

<div>{{ $balances->links() }}</div>
