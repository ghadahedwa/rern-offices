<div class="p-6 max-w-2xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.edit_user') }}</h1>
            <a href="{{ route('users.index') }}" wire:navigate
               class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                ← {{ __('home.back') }}
            </a>
        </div>

        {{-- Form Card --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
            <form wire:submit="save" class="space-y-5">

                {{-- Name --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                {{-- Username --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.username') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="username"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    @error('username') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('validation.attributes.password') }}
                        <span class="text-xs text-zinc-400 font-normal">({{ __('home.leave_blank_password') }})</span>
                    </label>
                    <input type="password" wire:model="password" autocomplete="new-password"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    @error('password') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('validation.attributes.password_confirmation') }}
                    </label>
                    <input type="password" wire:model="password_confirmation" autocomplete="new-password"
                           class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                </div>

                {{-- Role --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.role') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="role"
                            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">-- {{ __('home.select_role') }} --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                {{-- Governorates --}}
                <div class="flex flex-col gap-3">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('home.governorates') }}
                    </label>
                    @if($governorates->isEmpty())
                        <p class="text-xs text-zinc-400">{{ __('home.no_governorates') }}</p>
                    @else
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($governorates as $governorate)
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition">
                                    <input type="checkbox" wire:model="selectedGovernorates"
                                           value="{{ $governorate->id }}"
                                           class="w-4 h-4 rounded accent-[#c9a847]" />
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $governorate->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                        {{ __('home.save') }}
                    </button>
                    <a href="{{ route('users.index') }}" wire:navigate
                       class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                        {{ __('home.cancel') }}
                    </a>
                </div>

            </form>
        </div>

</div>
