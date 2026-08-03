<div class="p-6 space-y-6">

    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.warehouses_dashboard') }}</h1>

        @if($canCreate)
            <div class="flex items-center gap-2">
                <a href="{{ route('warehouses.incoming.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    {{ __('home.wh_incoming_add') }}
                </a>
                <a href="{{ route('warehouses.transfers.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition">
                    {{ __('home.wh_transfer_add') }}
                </a>
                <a href="{{ route('warehouses.opening-balances') }}" wire:navigate
                   class="inline-flex items-center gap-2 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-sm font-medium px-4 py-2 rounded-lg transition">
                    {{ __('home.wh_opening_balances') }}
                </a>
            </div>
        @endif
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.warehouses_manage_title') }}</p>
                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                    <flux:icon.building-office-2 variant="outline" class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                </div>
            </div>
            <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($warehousesCount) }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.items_title') }}</p>
                <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                    <flux:icon.cube variant="outline" class="w-5 h-5 text-violet-500 dark:text-violet-400" />
                </div>
            </div>
            <p class="text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($itemsCount) }}</p>
        </div>

        <div class="rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/5 dark:bg-[#c9a847]/10 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs text-[#b8962e] dark:text-[#c9a847]">{{ __('home.wh_movements') }} — {{ now()->translatedFormat('F Y') }}</p>
                <div class="w-9 h-9 rounded-lg bg-[#c9a847]/15 flex items-center justify-center">
                    <flux:icon.arrow-path variant="outline" class="w-5 h-5 text-[#b8962e] dark:text-[#c9a847]" />
                </div>
            </div>
            <p class="text-3xl font-bold text-[#b8962e] dark:text-[#c9a847]">{{ number_format($movementsMonth) }}</p>
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

    {{-- آخر الحركات --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.wh_movements') }}</h3>
        </div>

        @if($recentMovements->isEmpty())
            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.no_data') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="text-zinc-500 dark:text-zinc-400 text-xs uppercase border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-3 py-2 font-medium">{{ __('home.warehouse') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.item') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.wh_movement_type') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.wh_quantity') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.wh_balance_before') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.wh_balance_after') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('home.wh_movement_date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($recentMovements as $movement)
                            <tr>
                                <td class="px-3 py-2 font-medium text-zinc-800 dark:text-zinc-100">{{ $movement->warehouse?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $movement->item?->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $badge = match($movement->type) {
                                            'opening'      => 'text-zinc-600 bg-zinc-100 dark:text-zinc-300 dark:bg-zinc-700/40',
                                            'incoming'     => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
                                            'transfer_in'  => 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30',
                                            'transfer_out' => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30',
                                            default        => 'text-zinc-600 bg-zinc-100',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ __('home.wh_type_'.$movement->type) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ number_format($movement->quantity) }}</td>
                                <td class="px-3 py-2 text-zinc-400 dark:text-zinc-500">{{ number_format($movement->balance_before) }}</td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ number_format($movement->balance_after) }}</td>
                                <td class="px-3 py-2 text-zinc-400 dark:text-zinc-500">{{ \App\Support\LocalTime::stamp($movement->created_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
