<div class="p-6 max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.edit_role') }}</h1>
        <a href="{{ route('roles.index') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <form wire:submit="save" class="space-y-6">

            {{-- Role Name --}}
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.role_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="name"
                       @if($role->name === 'super-admin') readonly @endif
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847] {{ $role->name === 'super-admin' ? 'opacity-60 cursor-not-allowed' : '' }}" />
                @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            {{-- Level --}}
            @include('livewire.roles.partials.level-select')

            {{-- Permissions --}}
            <div class="flex flex-col gap-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.permissions') }}
                </label>
                @include('livewire.roles.partials.permissions-grid')
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('roles.index') }}" wire:navigate
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

</div>
