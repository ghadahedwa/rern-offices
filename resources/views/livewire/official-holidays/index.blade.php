<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.de_holidays') }}</h1>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="seedFixed" title="{{ __('home.de_holiday_seed_hint') }}"
                    class="inline-flex items-center gap-2 border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('home.de_holiday_seed') }}
            </button>
            <a href="{{ route('official-holidays.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.de_holiday_add') }}
            </a>
        </div>
    </div>

    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-3xl">{{ __('home.de_holidays_hint') }}</p>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('home.search') }}"
               class="max-w-sm flex-1 min-w-50 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />

        <select wire:model.live="year"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
            <option value="">{{ __('home.de_holiday_all_years') }}</option>
            @foreach($years as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.de_holiday_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.de_holiday_starts_on') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.de_holiday_ends_on') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.de_holiday_days_count') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($holidays as $holiday)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $holidays->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $holiday->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $holiday->starts_on->toDateString() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $holiday->ends_on->toDateString() }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ count($holiday->dates()) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('official-holidays.edit', $holiday) }}" wire:navigate
                                   class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                    {{ __('home.edit') }}
                                </a>
                                <button wire:click="askDelete({{ $holiday->id }})"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                    {{ __('home.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $holidays->links() }}</div>

    <div class="rounded-lg border border-[#c9a847]/40 bg-[#c9a847]/[0.06] px-3.5 py-3 max-w-3xl">
        <p class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-300">{{ __('home.de_holiday_super_admin_note') }}</p>
    </div>

    @include('livewire.partials.delete-modal')

</div>
