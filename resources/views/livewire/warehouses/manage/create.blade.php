<div class="p-6 max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            {{ $warehouse?->exists ? __('home.warehouse') : __('home.add_warehouse') }}
        </h1>
        <a href="{{ route('warehouse-manage.index') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <form wire:submit="save" class="space-y-5">

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.warehouse_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="name" autocomplete="off"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.warehouse_type') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="warehouse_type_id"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">— {{ __('home.warehouse_type') }} —</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ __('home.warehouse_type_level') }} {{ $type->level }})</option>
                        @endforeach
                    </select>
                    @error('warehouse_type_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.warehouse_governorate') }}
                    </label>
                    <select wire:model="governorate_id"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">— {{ __('home.warehouse_governorate') }} —</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                        @endforeach
                    </select>
                    @error('governorate_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.warehouse_letterhead') }}
                </label>
                <input type="text" wire:model="letterhead" autocomplete="off"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.warehouse_letterhead_hint') }}</p>
                @error('letterhead') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="is_active"
                       class="w-4 h-4 rounded accent-[#c9a847]" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('home.warehouse_active') }}</span>
            </label>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('warehouse-manage.index') }}" wire:navigate
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

</div>
