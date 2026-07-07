<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.vehicles_title') }}</h1>
        @if($canCreate)
            <a href="{{ route('vehicles.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_vehicle') }}
            </a>
        @endif
    </div>

    {{-- Filters --}}
    @php $filterCls = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]'; @endphp
    <div class="space-y-3">
        <div class="grid grid-cols-4 gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('home.search') }}"
                   class="{{ $filterCls }}" />
            <select wire:model.live="governorate_id" class="{{ $filterCls }}">
                <option value="">{{ __('home.all_governorates') }}</option>
                @foreach($governorates as $gov)
                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="type_id" class="{{ $filterCls }}">
                <option value="">— {{ __('home.vehicle_type') }} —</option>
                @foreach($types as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="work_system_id" class="{{ $filterCls }}">
                <option value="">— {{ __('home.vehicle_work_system') }} —</option>
                @foreach($workSystems as $ws)
                    <option value="{{ $ws->id }}">{{ $ws->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="toggleAdvanced"
                    class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border transition
                        {{ $showAdvanced
                            ? 'bg-[#c9a847]/10 border-[#c9a847] text-[#b8962e] dark:text-[#c9a847]'
                            : 'bg-white border-zinc-300 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 hover:border-[#c9a847] hover:text-[#b8962e]' }}">
                <flux:icon.adjustments-horizontal variant="outline" class="w-4 h-4" />
                {{ __('home.advanced_search') }}
                <flux:icon.chevron-down variant="micro" class="w-3.5 h-3.5 transition {{ $showAdvanced ? 'rotate-180' : '' }}" />
            </button>
        </div>

        @if($showAdvanced)
            <div class="grid grid-cols-4 gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                <select wire:model.live="status" class="{{ $filterCls }}">
                    <option value="">— {{ __('home.vehicle_status') }} —</option>
                    @foreach(\App\Models\Vehicle::STATUSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.vehicle_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.governorate') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.vehicle_type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.vehicle_work_system') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('home.vehicle_status') }}</th>
                    @if($canView || $canEdit || $canDelete)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($vehicles as $vehicle)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500">{{ $vehicles->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $vehicle->governorate->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                                {{ $vehicle->type->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            {{ $vehicle->workSystem->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($vehicle->status)
                                @php
                                    $colors = [
                                        'working'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'maintenance' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        'stopped'     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$vehicle->status] ?? '' }}">
                                    {{ \App\Models\Vehicle::STATUSES[$vehicle->status] }}
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        @if($canView || $canEdit || $canDelete)
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($canEdit)
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            {{ __('home.edit') }}
                                        </a>
                                    @endif
                                    @if($canView)
                                        <a href="{{ route('vehicles.show', $vehicle) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            {{ __('home.view_vehicle') }}
                                        </a>
                                    @endif
                                    @if($canDelete)
                                        <button wire:click="askDelete({{ $vehicle->id }})"
                                                class="inline-flex items-center text-xs px-3 py-1.5 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            {{ __('home.delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">
                            {{ __('home.no_vehicles') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $vehicles->links() }}</div>

    @include('livewire.partials.delete-modal')

</div>
