{{-- ── تاب المطالبات الملغاة (محافظة + سنة + شهر + مبلغ + سبب) ── --}}
@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 space-y-4">

    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.claims_tab_cancelled') }}</h3>
        </div>

        @if($this->canEdit())
        <button type="button" wire:click="openAddCancelled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('home.claims_cancelled_add') }}
        </button>
        @endif
    </div>

    {{-- Filters: المحافظة + السنة --}}
    <div class="flex items-center justify-end gap-2">
        <select wire:model.live="filterGovernorate" class="{{ $inp }} max-w-50">
            <option value="">{{ __('home.claims_all_governorates') }}</option>
            @foreach($allGovernorates as $gov)
                <option value="{{ $gov->id }}">{{ $gov->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterYear" class="{{ $inp }} max-w-35">
            <option value="">{{ __('home.claims_all_years') }}</option>
            @foreach($years as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>
    </div>

    @if($cancelled->total() > 0)
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.claims_governorate') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.claims_year') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.claims_month') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.claims_amount_egp') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.claims_cancel_reason') }}</th>
                    <th class="px-4 py-2.5 font-medium w-24 text-center">{{ __('home.claims_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($cancelled as $row)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $row->governorate->name }}</td>
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $row->year }}</td>
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $months[$row->month] ?? $row->month }}</td>
                    <td class="px-4 py-2.5 font-medium text-amber-600 dark:text-amber-400">{{ number_format($row->amount, 2) }}</td>
                    <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 max-w-[220px] truncate" title="{{ $row->reason }}">{{ $row->reason ?: '—' }}</td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center justify-center gap-1">
                            @if($this->canEdit())
                            <button type="button" wire:click="openEditCancelled({{ $row->id }})"
                                    class="p-1.5 text-zinc-400 hover:text-[#b8962e] hover:bg-[#c9a847]/10 rounded-lg transition cursor-pointer" title="{{ __('home.claims_edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button type="button" wire:click="askDeleteCancelled({{ $row->id }})"
                                    class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition cursor-pointer" title="{{ __('home.claims_delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
        @if($cancelled->hasPages())
        <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
            {{ $cancelled->links() }}
        </div>
        @endif
    </div>
    @else
    <p class="text-sm text-zinc-400 text-center py-6 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
        {{ __('home.claims_empty') }}
    </p>
    @endif

</div>

{{-- ── Add/Edit Cancelled Modal ── --}}
<div x-show="$wire.showCancelled"
     x-transition.opacity
     @click.self="$wire.showCancelled = false"
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">
                    {{ $editingCancelledId ? __('home.claims_edit') : __('home.claims_add') }} — {{ __('home.claims_tab_cancelled') }}
                </h3>
            </div>
            <button type="button" @click="$wire.showCancelled = false"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
        </div>
        <div class="bg-white dark:bg-zinc-900 px-5 py-6">
            <form wire:submit="saveCancelled" class="space-y-5">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_governorate') }} <span class="text-red-500">*</span></label>
                    <select wire:model="cancGov" class="{{ $inp }}">
                        <option value="">— {{ __('home.claims_governorate') }} —</option>
                        @foreach($allGovernorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                        @endforeach
                    </select>
                    @error('cancGov') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_year') }} <span class="text-red-500">*</span></label>
                        <select wire:model="cancYear" class="{{ $inp }}">
                            <option value="">— {{ __('home.claims_year') }} —</option>
                            @foreach($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                        </select>
                        @error('cancYear') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_month') }} <span class="text-red-500">*</span></label>
                        <select wire:model="cancMonth" class="{{ $inp }}">
                            <option value="">— {{ __('home.claims_month') }} —</option>
                            @foreach($months as $num => $name)<option value="{{ $num }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('cancMonth') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_amount_egp') }} <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="cancAmount" min="0" step="0.01" placeholder="0.00" class="{{ $inp }}" />
                    @error('cancAmount') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.claims_cancel_reason') }}</label>
                    <textarea wire:model="cancReason" rows="2" class="{{ $inp }}" placeholder="{{ __('home.claims_cancel_reason') }}"></textarea>
                    @error('cancReason') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit"
                            class="flex-1 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.claims_save') }}
                    </button>
                    <button type="button" @click="$wire.showCancelled = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        {{ __('home.claims_cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Delete Cancelled Modal ── --}}
<div x-show="$wire.showDeleteCancelled"
     x-transition.opacity
     @click.self="$wire.showDeleteCancelled = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     style="display:none">
    <div x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-red-500">
        <div class="flex items-center justify-between px-5 py-3.5 bg-red-500">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">{{ __('home.claims_delete_confirm_title') }}</h3>
            </div>
            <button type="button" @click="$wire.showDeleteCancelled = false"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
        </div>
        <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
            <p class="text-sm text-zinc-600 dark:text-zinc-300 text-center">
                {{ __('home.claims_delete_confirm_text') }}<br>
                <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $deletingCancelledLabel }}</span>
            </p>
            <div class="flex gap-3">
                <button type="button" wire:click="deleteCancelled"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    {{ __('home.claims_delete') }}
                </button>
                <button type="button" @click="$wire.showDeleteCancelled = false"
                        class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                    {{ __('home.claims_cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
