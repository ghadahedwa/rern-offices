
            {{-- البيانات الأساسية --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_basic_data') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.governorate') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->governorate->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.supervising_counselor') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->governorate->supervising_counselor ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.office_name') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.head_mobile') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100" dir="ltr" style="text-align:right">{{ $office->head_mobile ?: __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.office_type') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->officeType->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.location_description') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->locationDescription->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.established_at') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->established_at?->format('Y-m-d') ?? __('home.no_data') }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700"></div>

            {{-- أوقات وأنظمة العمل --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_work_schedule') }}</h3>
                </div>
                @php
                $workingDaysLabels = [
                    'full_week'  => __('home.working_days_full_week'),
                    'one_day'    => __('home.working_days_one_day'),
                    'two_days'   => __('home.working_days_two_days'),
                    'three_days' => __('home.working_days_three_days'),
                    'four_days'  => __('home.working_days_four_days'),
                    'five_days'  => __('home.working_days_five_days'),
                ];
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.mechanization_at') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->mechanization_at?->format('Y-m-d') ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.work_system') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->workSystem->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.working_hours') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->workingHour->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.working_days') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $workingDaysLabels[$office->working_days] ?? __('home.no_data') }}</p>
                    </div>
                    @if($office->locationDescription?->shows_windows_count)
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.windows_count') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->windows_count ?? __('home.no_data') }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.connection_type') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->connectionType->name ?? __('home.no_data') }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700"></div>

            {{-- بيانات الموقع --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_location_data') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.office_area') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $office->office_area ? $office->office_area . ' م²' : __('home.no_data') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.floors_description') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->floors_description ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.contractual_status') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->contractualStatus->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.district_court') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->district_court ?? __('home.no_data') }}</p>
                    </div>
                    @if($office->google_maps_link)
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.google_maps_link') }}</p>
                        <a href="{{ $office->google_maps_link }}" target="_blank"
                           class="inline-flex items-center gap-1 text-sm font-medium text-[#c9a847] hover:text-[#b8962e] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            {{ __('home.open_maps_link') }}
                        </a>
                    </div>
                    @endif
                    @if($office->address)
                    <div class="md:col-span-2">
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.address') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->address }}</p>
                    </div>
                    @endif
                </div>
            </div>
