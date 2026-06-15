
            {{-- ── Section 1: البيانات الأساسية ── --}}
            <div class="mb-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_basic_data') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.governorate') }} <span class="text-red-500">*</span></label>
                        <select wire:model.live="governorate_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_governorate') }}</option>
                            @foreach ($governorates as $gov)
                                <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                            @endforeach
                        </select>
                        @error('governorate_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.supervising_counselor') }}</label>
                        @php $counselor = $governorate_id ? ($governorates->firstWhere('id', $governorate_id)?->supervising_counselor ?? '—') : '—'; @endphp
                        <div class="w-full border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 min-h-9.5">
                            {{ $counselor }}
                        </div>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.office_name') }} <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="{{ __('home.placeholder_office_name') }}" class="{{ $inp }}" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.head_mobile') }}</label>
                        <input wire:model="head_mobile" type="tel" inputmode="numeric" maxlength="11"
                               x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')"
                               placeholder="{{ __('home.placeholder_head_mobile') }}" dir="ltr" class="{{ $inp }} text-right" />
                        @error('head_mobile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.office_type') }} <span class="text-red-500">*</span></label>
                        <select wire:model="type_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_type') }}</option>
                            @foreach ($types as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('type_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    {{-- parent_office_id — hidden for now
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.parent_office') }}</label>
                        <select wire:model="parent_office_id" class="{{ $inp }}">
                            <option value="">{{ __('home.no_parent_office') }}</option>
                            @foreach ($mainOffices as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.location_description') }}<span class="text-red-500">*</span></label>
                        <select wire:model.live="location_description_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_location') }}</option>
                            @foreach ($locations as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('location_description_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.established_at') }}</label>
                        <input wire:model="established_at" type="date" class="{{ $inp }}" />
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 mb-2"></div>

            {{-- ── Section 2: أوقات وأنظمة العمل ── --}}
            <div class="mb-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_work_schedule') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.mechanization_at') }}</label>
                        <input wire:model="mechanization_at" type="date" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.work_system') }}</label>
                        <select wire:model="work_system_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_work_system') }}</option>
                            @foreach ($workSystems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.working_hours') }}</label>
                        <select wire:model="working_hours_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_working_hours') }}</option>
                            @foreach ($workingHoursOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('working_hours_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.working_days') }}</label>
                        <select wire:model="working_days" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            <option value="full_week">{{ __('home.working_days_full_week') }}</option>
                            <option value="one_day">{{ __('home.working_days_one_day') }}</option>
                            <option value="two_days">{{ __('home.working_days_two_days') }}</option>
                            <option value="three_days">{{ __('home.working_days_three_days') }}</option>
                            <option value="four_days">{{ __('home.working_days_four_days') }}</option>
                            <option value="five_days">{{ __('home.working_days_five_days') }}</option>
                        </select>
                    </div>
                    @if($locations->firstWhere('id', $location_description_id)?->shows_windows_count)
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.windows_count') }}</label>
                        <input wire:model="windows_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    @endif
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 mb-2"></div>

            {{-- ── Section 3: بيانات الموقع ── --}}
            <div class="mb-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_location_data') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.office_area') }}</label>
                        <input wire:model="office_area" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.floors_description') }}</label>
                        <input wire:model="floors_description" type="text" placeholder="{{ __('home.placeholder_floors') }}" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.contractual_status') }}</label>
                        <select wire:model="contractual_status_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_contractual_status') }}</option>
                            @foreach ($contractualStatuses as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.google_maps_link') }}</label>
                        <input wire:model="google_maps_link" type="url" placeholder="{{ __('home.placeholder_google_maps') }}" class="{{ $inp }}" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $lbl }}">{{ __('home.district_court') }}</label>
                        <input wire:model="district_court" type="text" placeholder="{{ __('home.placeholder_district_court') }}" class="{{ $inp }}" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="{{ $lbl }}">{{ __('home.address') }}</label>
                        <textarea wire:model="address" rows="2" placeholder="{{ __('home.placeholder_address') }}"
                                  class="{{ $inp }} resize-none"></textarea>
                    </div>                    
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 mb-2"></div>

            {{-- ── Section 3: بيانات التشغيل ── --}}
            <div class="mb-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_operations_data') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.connection_type') }}</label>
                        <select wire:model="connection_type_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_connection') }}</option>
                            @foreach ($connections as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- نُقل إلى صفحة الإحصائيات (تاب المعاملات والمبيعات). محفوظ هنا للاسترجاع السهل لو عُدل القرار.
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.avg_daily_transactions') }}</label>
                        <input wire:model="avg_daily_transactions" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    --}}

                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 mb-2"></div>
