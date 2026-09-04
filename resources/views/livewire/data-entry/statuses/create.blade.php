<div class="p-6 max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            {{ $attendanceStatus?->exists ? __('home.de_status_edit') : __('home.de_status_add') }}
        </h1>
        <a href="{{ route('attendance-statuses.index') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <form wire:submit="save" class="space-y-5">

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="name" autocomplete="off"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            {{-- لوحة ألوان مغلقة: اللون يُقرأ في تقويم صغير، والحرّية فيه تُخرج ألواناً لا تُقرأ --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_status_color') }}</label>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($colors as $hex => $label)
                        <button type="button" wire:click="$set('color', '{{ $hex }}')" title="{{ $label }}"
                                class="w-9 h-9 rounded-lg border-2 transition {{ $color === $hex ? 'border-zinc-800 dark:border-zinc-100 scale-110' : 'border-transparent hover:border-zinc-300' }}"
                                style="background-color: {{ $hex }}"></button>
                    @endforeach
                </div>
                @error('color') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_status_order') }}</label>
                <input type="number" wire:model="order" min="0" max="999"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-32 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.de_status_order_hint') }}</p>
                @error('order') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            @if($attendanceStatus?->is_system)
                <div class="rounded-lg border border-[#c9a847]/40 bg-[#c9a847]/[0.06] px-3.5 py-3">
                    <p class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('home.de_status_system_note') }}</p>
                </div>
            @else
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="is_active"
                           class="mt-0.5 w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                    <span>
                        <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_status_active') }}</span>
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.de_status_active_hint') }}</span>
                    </span>
                </label>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('attendance-statuses.index') }}" wire:navigate
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

</div>
