{{-- اتصالات: الرصيد الافتتاحي مطلوب قبل أول شهر — يُكتب مرة ويُقفل (نموذج 2026-11-10) --}}
@php
    $openingDigits = preg_replace('/[\s,٬،]/u', '', \App\Support\ArabicDigits::toLatin(trim($openingInput))) ?? '';
    $openingValid  = preg_match('/^\d{1,12}$/', $openingDigits) === 1;
@endphp
<div class="rounded-xl border-2 border-dashed border-[#c9a847] bg-[#c9a847]/5 p-6">
    <p class="font-bold text-zinc-800 dark:text-zinc-100" style="font-size: 1.15rem">{{ __('home.fin_opening_needed_title') }}</p>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ __('home.fin_opening_needed_note') }}</p>

    @if($canSetOpening)
        <div class="mt-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48 max-w-xs">
                <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('home.fin_opening_date_label') }}</label>
                <input type="text" inputmode="numeric" dir="ltr" autocomplete="off"
                       oninput="this.value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[^0-9.,٬،]/g, '')"
                       wire:model.live.debounce.400ms="openingInput" style="font-size: 1.3rem"
                       class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2.5 font-bold text-left bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
            </div>
            <button type="button" wire:click="saveOpening" wire:loading.attr="disabled" wire:target="saveOpening"
                    class="bg-[#c9a847] hover:bg-[#b8962e] disabled:opacity-50 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                {{ __('home.fin_opening_save') }}
            </button>
        </div>
        @if($openingValid)
            <p class="text-xs text-[#b8962e] dark:text-[#d8b856] mt-2">{{ \App\Support\Finance\AmountInWords::pounds((int) $openingDigits) }}</p>
        @endif
        @error('openingInput') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-amber-700 dark:text-amber-300 mt-2">{{ __('home.fin_opening_lock_note') }}</p>
    @else
        <p class="text-xs text-zinc-400 mt-3">{{ __('home.fin_opening_needed_wait') }}</p>
    @endif
</div>
