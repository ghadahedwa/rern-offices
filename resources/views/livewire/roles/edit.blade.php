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

    {{-- تأكيد الربط التلقائي للمخازن.
         ⚠️ يظهر **قبل** الحفظ لا بعده: القرار جزء من الحفظ لا أثرٌ تالٍ له،
            والمدير يرى مَن سيُربط وبأي مخازن قبل أن يقع شيء. --}}
    @if($showLinkConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-xl p-6 space-y-4 max-h-[85vh] overflow-y-auto">

                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                        {{ __('home.role_link_title') }}
                    </h3>
                </div>

                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    {{ __('home.role_link_intro', ['count' => count($linkCandidates)]) }}
                </p>

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($linkCandidates as $candidate)
                        <div class="flex items-start justify-between gap-4 px-4 py-2.5">
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100 shrink-0">
                                {{ $candidate['user']->name }}
                            </span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400 text-left">
                                @if($candidate['warehouses']->isNotEmpty())
                                    {{ $candidate['warehouses']->pluck('name')->join(' · ') }}
                                @elseif($candidate['user']->governorates->isEmpty())
                                    <span class="text-amber-600 dark:text-amber-400">{{ __('home.role_link_no_governorates') }}</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">{{ __('home.role_link_no_warehouses') }}</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.role_link_note') }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('home.role_link_main_warehouse_note') }}</p>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" wire:click="saveAndLink"
                            class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                        {{ __('home.role_link_confirm') }}
                    </button>
                    <button type="button" wire:click="saveWithoutLink"
                            class="border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-sm font-medium px-5 py-2 rounded-lg transition">
                        {{ __('home.role_link_skip') }}
                    </button>
                    <button type="button" wire:click="$set('showLinkConfirm', false)"
                            class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                        {{ __('home.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
