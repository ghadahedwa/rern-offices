{{-- ── تاب المديونية (محسوبة: مطالبات − محصل) ── --}}
@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 space-y-4">

    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.claims_debt_title') }}</h3>
        </div>

        {{-- فلتر بالمحافظة --}}
        <select wire:model.live="filterGovernorate" class="{{ $inp }} max-w-50">
            <option value="">{{ __('home.claims_all_governorates') }}</option>
            @foreach($allGovernorates as $gov)
                <option value="{{ $gov->id }}">{{ $gov->name }}</option>
            @endforeach
        </select>
    </div>

    @if($debtRows->isNotEmpty())
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    @php
                        $cur = __('home.claims_currency');
                        $sortCols = [
                            'name'      => __('home.claims_governorate'),
                            'demands'   => __('home.claims_demands_total') . ' (' . $cur . ')',
                            'collected' => __('home.claims_collected') . ' (' . $cur . ')',
                            'debt'      => __('home.claims_debt_total') . ' (' . $cur . ')',
                        ];
                    @endphp
                    @foreach($sortCols as $field => $label)
                    <th class="px-4 py-2.5 font-medium">
                        <button type="button" wire:click="sortBy('{{ $field }}')"
                                class="inline-flex items-center gap-1 hover:text-[#b8962e] transition cursor-pointer {{ $sortField === $field ? 'text-[#b8962e]' : '' }}">
                            {{ $label }}
                            @if($sortField === $field)
                                <span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-[10px] text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </button>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($debtRows as $gov)
                @php
                    $demandsT = $demandsTotals[$gov->id] ?? 0;
                    $collected = $collectedTotals[$gov->id] ?? 0;
                    $debt = $demandsT - $collected;
                @endphp
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $gov->name }}</td>
                    <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ number_format($demandsT, 2) }}</td>
                    <td class="px-4 py-2.5 font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($collected, 2) }}</td>
                    <td class="px-4 py-2.5 font-bold {{ $debt < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100' }}">
                        {{ number_format($debt, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-sm text-zinc-400 text-center py-6 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
        {{ __('home.claims_no_governorates') }}
    </p>
    @endif

</div>
