@php
    $statusColors = [
        'working'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'maintenance' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'stopped'     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    ];
    $groupedLocations = $vehicle->locations->groupBy('address');
@endphp

{{-- البيانات الأساسية --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_basic_data') }}</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.governorate') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->governorate->name ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.vehicle_name') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->name }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.vehicle_type') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->type->name ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.vehicle_work_system') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->workSystem->name ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.vehicle_brand') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->brand->name ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.manufacture_year') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->manufacture_year ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.vehicle_operated_at') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->operated_at?->format('Y-m-d') ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.license_plate') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align:right">{{ $vehicle->license_plate ?: __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.chassis_number') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align:right">{{ $vehicle->chassis_number ?: __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.license_expiry_date') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->license_expiry_date?->format('Y-m-d') ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.vehicle_status') }}</p>
            @if($vehicle->status)
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$vehicle->status] ?? '' }}">
                    {{ \App\Models\Vehicle::STATUSES[$vehicle->status] }}
                </span>
            @else
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('home.no_data') }}</p>
            @endif
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.working_hours') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->workingHour->name ?? __('home.no_data') }}</p>
        </div>
    </div>
</div>

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

{{-- أيام التمركز --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.vehicle_locations') }}</h3>
    </div>
    @if($groupedLocations->isNotEmpty())
        <div class="space-y-3">
            @foreach($groupedLocations as $address => $group)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 mb-2">{{ $address }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($group as $loc)
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                        {{ \App\Models\VehicleLocation::DAYS[$loc->day] ?? $loc->day }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">{{ __('home.no_locations') }}</p>
    @endif
</div>

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

{{-- بيانات إضافية --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.vehicle_extra_data') }}</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.overnight_address') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->overnight_address ?: __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.storage_room_location') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->storage_room_location ?: __('home.no_data') }}</p>
        </div>
        <div class="md:col-span-2">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.notes') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->notes ?: __('home.no_data') }}</p>
        </div>
    </div>
</div>
