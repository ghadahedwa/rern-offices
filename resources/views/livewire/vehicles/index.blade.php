<div class="p-6 space-y-6">

    @include('livewire.partials.no-governorates-notice')

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
        {{-- عمود واحد على الموبايل كشاشة المقرات --}}
        <div class="grid grid-cols-4 max-sm:grid-cols-1 gap-3">
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
            <div class="grid grid-cols-4 max-sm:grid-cols-1 gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
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
        <table class="w-full text-sm text-right max-sm:[&_th]:px-2 max-sm:[&_td]:px-2">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                <tr>
                    {{-- على الموبايل: الاسم (والمحافظة سطرٌ تحته) والحالة والعمليات — النوع ونظام العمل في صفحة العرض --}}
                    <th class="px-4 py-3 font-medium max-sm:hidden">#</th>
                    @include('livewire.partials.sortable-th', ['column' => 'name', 'label' => __('home.vehicle_name')])
                    @include('livewire.partials.sortable-th', ['column' => 'governorate', 'label' => __('home.governorate'), 'thClass' => 'max-sm:hidden'])
                    @include('livewire.partials.sortable-th', ['column' => 'type', 'label' => __('home.vehicle_type'), 'thClass' => 'max-sm:hidden'])
                    @include('livewire.partials.sortable-th', ['column' => 'work_system', 'label' => __('home.vehicle_work_system'), 'thClass' => 'max-sm:hidden'])
                    @include('livewire.partials.sortable-th', ['column' => 'status', 'label' => __('home.vehicle_status')])
                    @if($canView || $canEdit || $canDelete)
                        <th class="px-4 py-3 font-medium">{{ __('home.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($vehicles as $vehicle)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <td class="px-4 py-3 text-zinc-500 max-sm:hidden">{{ $vehicles->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 max-sm:px-3 font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $vehicle->name }}
                            <p class="sm:hidden mt-0.5 text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ $vehicle->governorate->name }}</p>
                        </td>
                        <td class="px-4 py-3 max-sm:hidden text-zinc-600 dark:text-zinc-300">{{ $vehicle->governorate->name }}</td>
                        <td class="px-4 py-3 max-sm:hidden">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                                {{ $vehicle->type->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 max-sm:hidden text-zinc-600 dark:text-zinc-300">
                            {{ $vehicle->workSystem->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 max-sm:px-2">
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
                            <td class="px-4 py-3 max-sm:px-2">
                                {{-- الأزرار تحت بعضها على الموبايل بدل صفٍّ يمدّ الجدول --}}
                                <div class="flex max-sm:flex-col max-sm:items-stretch items-center gap-2">
                                    @if($canEdit)
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                            {{ __('home.edit') }}
                                        </a>
                                    @endif
                                    @if($canView)
                                        <a href="{{ route('vehicles.show', $vehicle) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-md border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
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
