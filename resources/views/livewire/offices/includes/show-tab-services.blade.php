
            @php
            $brailleLabels = [
                'available'     => __('home.option_available'),
                'not_available' => __('home.option_not_available'),
            ];
            $queueLabels = [
                'working'       => __('home.option_working'),
                'not_working'   => __('home.option_not_working'),
                'not_available' => __('home.option_not_available'),
            ];
            $meterTypeLabels = [
                'prepaid'      => __('home.meter_type_prepaid'),
                'invoice'      => __('home.meter_type_invoice'),
                'entity_meter' => __('home.meter_type_entity'),
            ];
            $meterDebtLabels = [
                'yes' => __('home.option_available'),
                'no'  => __('home.option_not_available'),
            ];
            @endphp

            {{-- الخدمات والتجهيزات --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_services_equipment') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.microfilm_option') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->MicrofilmOption->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.disabilities_access_label') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->DisabilitieAccess->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fire_safety_label') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->FireSafety->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.document_photocopying_service') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->DocumentPhotocopyingService->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.buffet_service') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->BuffetService->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.cleanliness_contract') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->CleanlinessContract->name ?? __('home.no_data') }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700"></div>

            {{-- الأجهزة والمعدات --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_devices_counts') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.braille_sign_device') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $brailleLabels[$office->Braille_sign_device] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.queue_management_system') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $queueLabels[$office->queue_management_system] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.payment_machine_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->payment_machine_count ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.computers_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->computers_count ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.monitors_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->monitors_count ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.scanners_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->scanners_count ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.printers_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->printers_count ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.fingerprints_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->fingerprints_count ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.air_conditioners_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->air_conditioners_count ?? __('home.no_data') }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700"></div>

            {{-- العدادات --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_meters') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.electricity_meter_type') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $meterTypeLabels[$office->electricity_meter_type] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.electricity_meter_debt') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $meterDebtLabels[$office->electricity_meter_debt] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.water_meter_type') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $meterTypeLabels[$office->water_meter_type] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.water_meter_debt') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $meterDebtLabels[$office->water_meter_debt] ?? __('home.no_data') }}</p>
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
                @if($office->brokenDevices->isNotEmpty())
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">{{ __('home.select_device_type') }}</th>
                                <th class="px-4 py-2.5 font-medium">{{ __('home.device_count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($office->brokenDevices as $device)
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
