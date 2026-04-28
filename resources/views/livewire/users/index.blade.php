<div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.users') }}</h1>
            <a href="{{ route('users.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_user') }}
            </a>
        </div>

        {{-- Search --}}
        <div class="max-w-sm">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="{{ __('home.search') }}"
                class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]"
            />
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.username') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.role') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.governorates') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($users as $user)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-3 text-zinc-500">{{ $users->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->username }}</td>
                            <td class="px-4 py-3">
                                @if($user->roles->first())
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                                        {{ $user->roles->first()->name }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->governorates->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->governorates as $gov)
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                                {{ $gov->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->id !== 1)
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('users.edit', $user) }}" wire:navigate
                                       class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.edit') }}
                                    </a>
                                    <button
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="{{ __('home.confirm_delete') }}"
                                        class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                </div>
                                @else
                                <span class="text-xs text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-400">
                                {{ __('home.no_users') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $users->links() }}
        </div>

</div>
