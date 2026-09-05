<div class="p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            {{ $operator?->exists ? __('home.de_operator_edit') : __('home.de_operator_add') }}
        </h1>
        <a href="{{ route('data-entry.index') }}" wire:navigate
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
            ← {{ __('home.back') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <form wire:submit="save" class="space-y-6">

            {{-- بيانات المدخل --}}
            <div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.de_operator_data') }}</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('home.de_operator_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" autocomplete="off"
                               class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                        @error('name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_phone') }}</label>
                        <input type="text" wire:model="phone" inputmode="numeric" autocomplete="off"
                               class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                        @error('phone') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            @unless($operator?->exists)
                {{-- التسكين الأول — يُنشأ مع الإضافة وحدها --}}
                <div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.de_operator_assignment') }}</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_governorate') }}</label>
                            <select wire:model.live="governorate"
                                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                                <option value="">—</option>
                                @foreach($governorates as $gov)
                                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('home.de_operator_office') }} <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="office"
                                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                                <option value="">—</option>
                                @foreach($offices as $off)
                                    <option value="{{ $off->id }}" title="{{ $off->name }}">{{ $off->short_name }}</option>
                                @endforeach
                            </select>
                            @error('office') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('home.de_operator_started_on') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="started_on"
                                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                            @error('started_on') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-[#c9a847]/40 bg-[#c9a847]/[0.06] px-3.5 py-3">
                    <p class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('home.de_operator_assignment_note') }}</p>
                </div>
            @endunless

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.de_operator_notes') }}</label>
                <textarea wire:model="notes" rows="3"
                          class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]"></textarea>
                @error('notes') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                    {{ __('home.save') }}
                </button>
                <a href="{{ route('data-entry.index') }}" wire:navigate
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    {{ __('home.cancel') }}
                </a>
            </div>

        </form>
    </div>

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>

</div>
