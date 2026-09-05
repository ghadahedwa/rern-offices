<div class="p-6 max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            {{ $officialHoliday?->exists ? __('home.de_holiday_edit') : __('home.de_holiday_add') }}
        </h1>
        <a href="{{ route('official-holidays.index') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <form wire:submit="save" class="space-y-5">

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.de_holiday_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="name" autocomplete="off"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.de_holiday_starts_on') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model.live="starts_on"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    @error('starts_on') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.de_holiday_ends_on') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model="ends_on" min="{{ $starts_on }}"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.de_holiday_ends_on_hint') }}</p>
                    @error('ends_on') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('official-holidays.index') }}" wire:navigate
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

    {{-- مودال التعارض: أيام سُجِّل فيها غياب أو إجازة قبل إعلان العطلة --}}
    <div x-show="$wire.showConflict"
         x-transition.opacity
         @click.self="$wire.showConflict = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none">
        <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-amber-500">
            <div class="flex items-center justify-between px-5 py-3.5 bg-amber-500">
                <h3 class="text-sm font-semibold text-white">{{ __('home.de_holiday_conflict_title') }}</h3>
                <button type="button" @click="$wire.showConflict = false"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
            </div>
            <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
                <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                    {{ __('home.de_holiday_conflict_body', ['count' => $conflictCount]) }}
                </p>
                <div class="flex gap-3">
                    <button type="button" wire:click="confirmSave"
                            class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium py-2.5 rounded-lg transition">
                        {{ __('home.de_holiday_conflict_confirm') }}
                    </button>
                    <button type="button" @click="$wire.showConflict = false"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        {{ __('home.de_holiday_conflict_cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
