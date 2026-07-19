<div class="p-6 space-y-6">

    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.wh_movements') }}</h1>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
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

        <select wire:model.live="itemFilter"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
            <option value="">— {{ __('home.item') }} —</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="typeFilter"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
            <option value="">— {{ __('home.wh_movement_type') }} —</option>
            <option value="opening">{{ __('home.wh_type_opening') }}</option>
            <option value="incoming">{{ __('home.wh_type_incoming') }}</option>
            <option value="transfer_out">{{ __('home.wh_type_transfer_out') }}</option>
            <option value="transfer_in">{{ __('home.wh_type_transfer_in') }}</option>
        </select>

        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_date_from') }}</span>
            <input wire:model.live="dateFrom" type="date"
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
            <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.wh_date_to') }}</span>
            <input wire:model.live="dateTo" type="date"
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('home.warehouse') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.item') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_movement_type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_quantity') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_balance_before') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_balance_after') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.user') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_movement_date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($movements as $movement)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $movement->warehouse?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $movement->item?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
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
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ number_format($movement->quantity) }}</td>
                        <td class="px-4 py-3 text-zinc-400 dark:text-zinc-500">{{ number_format($movement->balance_before) }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ number_format($movement->balance_after) }}</td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $movement->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-400 dark:text-zinc-500">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $movements->links() }}</div>

</div>
