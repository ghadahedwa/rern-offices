<div class="p-6 space-y-6">

    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.warehouses_dashboard') }}</h1>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.warehouses_manage_title') }}</p>
            <p class="text-3xl font-bold text-[#b8962e]">{{ number_format($warehousesCount) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.items_title') }}</p>
            <p class="text-3xl font-bold text-[#b8962e]">{{ number_format($itemsCount) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.wh_movements') }} — {{ now()->translatedFormat('F Y') }}</p>
            <p class="text-3xl font-bold text-[#b8962e]">{{ number_format($movementsMonth) }}</p>
        </div>
    </div>

    {{-- تنبيه الحد الأدنى --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-1 h-5 bg-red-500 rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.wh_low_stock_alert') }}</h3>
        </div>

        @if($lowStock->isEmpty())
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.no_data') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="text-zinc-500 dark:text-zinc-400 text-xs uppercase border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-3 py-2 font-medium">{{ __('home.item_name') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.wh_current_balance') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.item_min_stock') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($lowStock as $item)
                            <tr>
                                <td class="px-3 py-2 font-medium text-zinc-800 dark:text-zinc-100">{{ $item->name }}</td>
                                <td class="px-3 py-2">
                                    <span class="font-semibold text-red-600">{{ number_format($item->current_qty) }}</span>
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ number_format($item->min_stock) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
