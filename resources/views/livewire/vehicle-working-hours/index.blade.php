<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.vehicle_working_hours_title') }}</h1>
        @if($isSuperAdmin)
            <a href="{{ route('vehicle-working-hours.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_vehicle_working_hour') }}
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
                    <th class="px-4 py-3 font-medium">{{ __('home.name') }}</th>
                    @if($isSuperAdmin)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($vehicleWorkingHours as $vehicleWorkingHour)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $vehicleWorkingHours->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicleWorkingHour->name }}</td>
                        @if($isSuperAdmin)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('vehicle-working-hours.edit', $vehicleWorkingHour) }}" wire:navigate
                                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.edit') }}
                                    </a>
                                    <button
                                        wire:click="askDelete({{ $vehicleWorkingHour->id }})"
                                        class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 3 : 2 }}" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_data') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $vehicleWorkingHours->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
