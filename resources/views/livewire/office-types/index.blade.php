<div class="p-6 max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.offices_type') }}</h1>
        @role('super-admin')
        <a href="{{ route('office-types.create') }}" wire:navigate
           class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('home.add') }}
        </a>
        @endrole
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">

        <div class="p-4 border-b border-zinc-100 dark:border-zinc-800">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="{{ __('home.search') }}..."
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full max-w-xs bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-100 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                    <th class="px-4 py-3 text-start">#</th>
                    <th class="px-4 py-3 text-start">{{ __('home.name') }}</th>
                    @role('super-admin')
                    <th class="px-4 py-3 text-end">{{ __('home.actions') }}</th>
                    @endrole
                </tr>
            </thead>
            <tbody>
                @forelse($officeTypes as $type)
                    <tr class="border-b border-zinc-50 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="px-4 py-3 text-zinc-400">{{ $type->id }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $type->name }}</td>
                        @role('super-admin')
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('office-types.edit', $type) }}" wire:navigate
                                   class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                    {{ __('home.edit') }}
                                </a>
                                <button type="button"
                                        wire:click="delete({{ $type->id }})"
                                        wire:confirm="{{ __('home.confirm_delete') }}"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                    {{ __('home.delete') }}
                                </button>
                            </div>
                        </td>
                        @endrole
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-400 text-sm">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($officeTypes->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $officeTypes->links() }}
            </div>
        @endif

    </div>

</div>
