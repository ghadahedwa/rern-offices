<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.fin_units_title') }}</h1>
        <a href="{{ route('finance-units.create') }}" wire:navigate
           class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('home.fin_unit_add') }}
        </a>
    </div>

    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-3xl">{{ __('home.fin_units_hint') }}</p>

    {{-- قائمة قصيرة (ست) فلا بحث ولا ترقيم صفحات --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium max-sm:hidden">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.name') }}</th>
                    <th class="px-4 py-3 font-medium max-sm:hidden">{{ __('home.ct_status_order') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($units as $unit)
                    <tr wire:key="unit-{{ $unit->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500 max-sm:hidden">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 max-sm:px-3 font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $unit->name }}
                            @unless($unit->is_active)
                                <span class="inline-flex items-center ms-2 text-[11px] font-medium px-2 py-0.5 rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                    {{ __('home.ct_status_inactive_badge') }}
                                </span>
                            @endunless
                        </td>
                        <td class="px-4 py-3 text-zinc-500 max-sm:hidden">{{ $unit->order }}</td>
                        <td class="px-4 py-3 max-sm:px-2">
                            <div class="flex max-sm:flex-col max-sm:items-stretch items-center gap-2">
                                <a href="{{ route('finance-units.edit', $unit) }}" wire:navigate
                                   class="inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60 transition">
                                    {{ __('home.edit') }}
                                </a>
                                {{-- المستعمَل بلا زرّ حذف — والحارس في الإجراء كذلك --}}
                                @unless($unit->isInUse())
                                    <button wire:click="askDelete({{ $unit->id }})"
                                            class="inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-full bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('livewire.partials.delete-modal')

</div>
