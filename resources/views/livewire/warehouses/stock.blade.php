<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_stock') }}</h1>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="max-w-sm flex-1 min-w-50">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.search') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        <select wire:model.live="warehouseFilter"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
            <option value="">{{ __('home.wh_all_warehouses') }}</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.warehouse') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.item_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.item_unit') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_current_balance') }}</th>
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
