{{-- ── تاب المديونية ── --}}
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
                        $sortCols = [
                            'name'      => __('home.claims_governorate'),
                            'debt'      => __('home.claims_debt_amount') . ' (' . __('home.claims_currency') . ')',
                            'collected' => __('home.claims_collected') . ' (' . __('home.claims_currency') . ')',
                            'remaining' => __('home.claims_remaining') . ' (' . __('home.claims_currency') . ')',
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
                    <th class="px-4 py-2.5 font-medium w-24 text-center">{{ __('home.claims_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($debtRows as $gov)
                @php
                    $collected = $collectedTotals[$gov->id] ?? 0;
                    $remaining = $gov->debt_amount !== null ? $gov->debt_amount - $collected : null;
                @endphp
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $gov->name }}</td>
                    <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $gov->debt_amount !== null ? number_format($gov->debt_amount, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2.5 font-medium text-emerald-600 dark:text-emerald-400">
                        {{ number_format($collected, 2) }}
                    </td>
                    <td class="px-4 py-2.5 font-medium {{ $remaining !== null && $remaining < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-100' }}">
                        {{ $remaining !== null ? number_format($remaining, 2) : '—' }}
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center justify-center gap-1">
                            @if($this->canEdit())
                            <button type="button" wire:click="openDebt({{ $gov->id }})"
                                    class="p-1.5 text-zinc-400 hover:text-[#b8962e] hover:bg-[#c9a847]/10 rounded-lg transition cursor-pointer" title="{{ __('home.claims_edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @else
                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </div>
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

{{-- ── Debt Edit Modal ── --}}
<div x-show="$wire.showDebt"
     x-transition.opacity
     @click.self="$wire.showDebt = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     style="display:none">
    <div x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-[#c9a847]">
        <div class="flex items-center justify-between px-5 py-3.5 bg-[#c9a847]">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">{{ __('home.claims_debt_modal_title') }}</h3>
            </div>
            <button type="button" @click="$wire.showDebt = false"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
        </div>
        <div class="bg-white dark:bg-zinc-900 px-5 py-6">
            <form wire:submit="saveDebt" class="space-y-5">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_governorate') }}</label>
                    <div class="{{ $inp }} bg-zinc-50 dark:bg-zinc-800/60 text-zinc-500 dark:text-zinc-400">{{ $editGovName }}</div>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_debt_amount') }} ({{ __('home.claims_currency') }})</label>
                    <input type="number" wire:model="debtAmount" min="0" step="0.01" placeholder="0.00" class="{{ $inp }}" />
                    @error('debtAmount') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="submit"
                            class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.claims_save') }}
                    </button>
                    <button type="button" @click="$wire.showDebt = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        {{ __('home.claims_cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
