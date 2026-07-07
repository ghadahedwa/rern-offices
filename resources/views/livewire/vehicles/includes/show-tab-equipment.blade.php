@php
    $optionLabels = [
        'available'     => __('home.option_available'),
        'not_available' => __('home.option_not_available'),
    ];
    $statusLabels = [
        'available'     => __('home.option_available'),
        'not_available' => __('home.option_not_available'),
        'broken'        => __('home.option_broken'),
    ];
@endphp

{{-- التجهيزات --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_vehicle_equipment') }}</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.mobility_bag') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $optionLabels[$vehicle->mobility_bag] ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.laptops_count') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->laptops_count ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fingerprints_count') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->fingerprints_count ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.printers_count') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->printers_count ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.collection_machines_count') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->collection_machines_count ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.mifi_count') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $vehicle->mifi_count ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.generator_status') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $statusLabels[$vehicle->generator_status] ?? __('home.no_data') }}</p>
        </div>
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.surveillance_cameras') }}</p>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $statusLabels[$vehicle->surveillance_cameras] ?? __('home.no_data') }}</p>
        </div>
    </div>
</div>

<div class="border-t border-zinc-100 dark:border-zinc-700"></div>

{{-- الأجهزة المعطلة --}}
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_broken_devices') }}</h3>
    </div>
    @if($vehicle->brokenDevices->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.select_device_type') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('home.device_count') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($vehicle->brokenDevices as $device)
                <tr>
                    <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100">{{ $device->deviceType->name ?? __('home.no_data') }}</td>
                    <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $device->count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">{{ __('home.no_broken_devices') }}</p>
    @endif
</div>
