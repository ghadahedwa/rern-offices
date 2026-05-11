<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.governorates') }}</h1>
        @if($isSuperAdmin)
            <a href="{{ route('governorates.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_governorate') }}
            </a>
        @endif
    </div>

    {{-- Search --}}
    <div class="max-w-sm">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('home.search') }}"
               class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.governorate_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.supervising_counselor') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.offices') }}</th>
                    @if($isSuperAdmin)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($governorates as $governorate)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $governorates->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $governorate->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $governorate->supervising_counselor ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('offices.index', ['governorate_id' => $governorate->id]) }}" wire:navigate
                               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition
                                   {{ $governorate->offices_count > 0 ? 'bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847] hover:bg-[#c9a847]/25' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 hover:bg-zinc-200' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                {{ $governorate->offices_count }} مقر
                            </a>
                        </td>
                        @if($isSuperAdmin)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('governorates.edit', $governorate) }}" wire:navigate
                                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.edit') }}
                                    </a>
                                    <button
                                        wire:click="deleteGovernorate({{ $governorate->id }})"
                                        wire:confirm="{{ __('home.confirm_delete') }}"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_governorates') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $governorates->links() }}</div>

</div>
