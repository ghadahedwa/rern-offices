@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40 focus:border-[#c9a847] transition';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="space-y-6">

    {{-- ── السائق ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_driver') }}</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.driver_name') }}</label>
                <input wire:model="driver_name" type="text" placeholder="{{ __('home.driver_name') }}" class="{{ $inp }}" />
                @error('driver_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.driver_phone') }}</label>
                <input wire:model="driver_phone" type="tel" inputmode="numeric" maxlength="11"
                       x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')"
                       placeholder="{{ __('home.placeholder_head_mobile') }}" dir="ltr" class="{{ $inp }} text-right" />
                @error('driver_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ── الموثق ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_notary') }}</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.notary_name') }}</label>
                <input wire:model="notary_name" type="text" placeholder="{{ __('home.notary_name') }}" class="{{ $inp }}" />
                @error('notary_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.notary_phone') }}</label>
                <input wire:model="notary_phone" type="tel" inputmode="numeric" maxlength="11"
                       x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')"
                       placeholder="{{ __('home.placeholder_head_mobile') }}" dir="ltr" class="{{ $inp }} text-right" />
                @error('notary_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ── المراجع ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_reviewer') }}</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.reviewer_name') }}</label>
                <input wire:model="reviewer_name" type="text" placeholder="{{ __('home.reviewer_name') }}" class="{{ $inp }}" />
                @error('reviewer_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.reviewer_phone') }}</label>
                <input wire:model="reviewer_phone" type="tel" inputmode="numeric" maxlength="11"
                       x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')"
                       placeholder="{{ __('home.placeholder_head_mobile') }}" dir="ltr" class="{{ $inp }} text-right" />
                @error('reviewer_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

</div>
