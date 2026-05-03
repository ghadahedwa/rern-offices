<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.offices') }}</h1>
        @if($canCreate)
            <a href="{{ route('offices.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_office') }}
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-48 max-w-sm">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.search') }}"
                   class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
        </div>
        <div class="min-w-48">
            <select wire:model.live="governorate_id"
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                <option value="">{{ __('home.all_governorates') }}</option>
                @foreach ($governorates as $gov)
                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.office_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.governorate_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.office_type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.supervising_counselor') }}</th>
                    @if($canEdit || $canDelete)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($offices as $office)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $offices->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $office->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $office->governorate->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                              {{ $office->officeType->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            {{ $office->governorate->supervising_counselor ?? '—' }}
                        </td>
                        @if($canEdit || $canDelete)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($canEdit)
                                        <span class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-500 dark:text-zinc-400">
                                            {{ __('home.edit') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_offices') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $offices->links() }}</div>

</div>
