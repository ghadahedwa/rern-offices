{{-- منتقي مستوى الرؤية الهرمي للدور --}}
<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ __('home.role_level') }} <span class="text-red-500">*</span>
    </label>
    <p class="text-xs text-zinc-400 dark:text-zinc-500 -mt-1">{{ __('home.role_level_hint') }}</p>

    <div class="grid gap-2 mt-1">
        @foreach([1 => 'role_level_1', 2 => 'role_level_2', 3 => 'role_level_3'] as $value => $key)
        <label class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition
            {{ (int) $level === $value
                ? 'border-[#c9a847] bg-[#c9a847]/5 dark:bg-[#c9a847]/10'
                : 'border-zinc-300 dark:border-zinc-600 hover:border-[#c9a847]/50' }}">
            <input type="radio" wire:model.live="level" value="{{ $value }}"
                   class="w-4 h-4 accent-[#c9a847] shrink-0" />
            <span class="text-sm text-zinc-800 dark:text-zinc-100">{{ __('home.' . $key) }}</span>
        </label>
        @endforeach
    </div>
    @error('level') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
</div>
