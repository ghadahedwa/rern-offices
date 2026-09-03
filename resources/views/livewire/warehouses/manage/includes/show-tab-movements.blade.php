<div class="space-y-4">

    {{-- Filters --}}
    <x-filter-bar :active="$this->hasActiveFilters()" :per-page-options="$this->perPageOptions()">
        <x-filter-select :label="__('home.item')" wire:model.live="itemFilter">
            <option value="">—</option>
            @include('livewire.warehouses.partials.item-options', ['items' => $movementItems])
        </x-filter-select>

        <x-filter-select :label="__('home.wh_movement_type')" wire:model.live="typeFilter">
            <option value="">—</option>
            @foreach($types as $type)
                <option value="{{ $type }}">{{ __('home.wh_type_'.$type) }}</option>
            @endforeach
        </x-filter-select>

        <x-filter-input type="date" :label="__('home.wh_date_from')" wire:model.live="dateFrom" />
        <x-filter-input type="date" :label="__('home.wh_date_to')" wire:model.live="dateTo" />

        <x-slot:shortcuts>
            <x-period-shortcuts :options="$this->periodOptions()" :active="$this->activePeriod()" />
        </x-slot:shortcuts>
    </x-filter-bar>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    @include('livewire.partials.sortable-th', ['column' => 'item',          'label' => __('home.item')])
                    @include('livewire.partials.sortable-th', ['column' => 'type',          'label' => __('home.wh_movement_type')])
                    @include('livewire.partials.sortable-th', ['column' => 'quantity',      'label' => __('home.wh_quantity')])
                    <th class="px-4 py-3 font-medium">{{ __('home.wh_balance_before') }}</th>
                    @include('livewire.partials.sortable-th', ['column' => 'balance_after', 'label' => __('home.wh_balance_after')])
                    <th class="px-4 py-3 font-medium">{{ __('home.user') }}</th>
                    @include('livewire.partials.sortable-th', ['column' => 'date',          'label' => __('home.wh_movement_date')])
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($movements as $movement)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $movement->item?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($movement->type) {
                                    'opening'      => 'text-zinc-600 bg-zinc-100 dark:text-zinc-300 dark:bg-zinc-700/40',
                                    'incoming'     => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
                                    'transfer_in'  => 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30',
                                    'transfer_out' => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30',
                                    'issue'        => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30',
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
                        <td class="px-4 py-3 text-zinc-400 dark:text-zinc-500">{{ \App\Support\LocalTime::stamp($movement->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $movements->links() }}</div>

</div>
