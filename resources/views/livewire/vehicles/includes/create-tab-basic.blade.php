@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40 focus:border-[#c9a847] transition';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="space-y-6">

    {{-- ── Section 1: المحافظة والمستشار ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_basic_data') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- المحافظة | المستشار --}}
            <div>
                <label class="{{ $lbl }}">{{ __('home.governorate') }} <span class="text-red-500">*</span></label>
                <select wire:model.live="governorate_id" class="{{ $inp }}">
                    <option value="">{{ __('home.select_governorate') }}</option>
                    @foreach($governorates as $gov)
                        <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                    @endforeach
                </select>
                @error('governorate_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.supervising_counselor') }}</label>
                @php $counselor = $governorate_id ? ($governorates->firstWhere('id', $governorate_id)?->supervising_counselor ?? '—') : '—'; @endphp
                <div class="w-full border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 min-h-[38px]">
                    {{ $counselor }}
                </div>
            </div>

            {{-- اسم السيارة --}}
            <div class="md:col-span-2">
                <label class="{{ $lbl }}">{{ __('home.vehicle_name') }} <span class="text-red-500">*</span></label>
                <input wire:model="name" type="text" placeholder="{{ __('home.vehicle_name_placeholder') }}" class="{{ $inp }}" />
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- نوع السيارة | نظام العمل --}}
            <div>
                <label class="{{ $lbl }}">{{ __('home.vehicle_type') }}</label>
                <select wire:model="type_id" class="{{ $inp }}">
                    <option value="">{{ __('home.select_type') }}</option>
                    @foreach($types as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.vehicle_work_system') }}</label>
                <select wire:model="work_system_id" class="{{ $inp }}">
                    <option value="">{{ __('home.select_work_system') }}</option>
                    @foreach($workSystems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- الماركة | سنة الصنع --}}
            <div>
                <label class="{{ $lbl }}">{{ __('home.vehicle_brand') }}</label>
                <select wire:model="brand_id" class="{{ $inp }}">
                    <option value="">{{ __('home.select_brand') }}</option>
                    @foreach($brands as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.manufacture_year') }}</label>
                <input wire:model="manufacture_year" type="number" min="1980" max="{{ date('Y') + 1 }}"
                       placeholder="{{ date('Y') }}" dir="ltr" class="{{ $inp }} text-right" />
                @error('manufacture_year') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- رقم اللوحة | رقم الشاسية --}}
            <div>
                <label class="{{ $lbl }}">{{ __('home.license_plate') }}</label>
                <input wire:model="license_plate" type="text" placeholder="{{ __('home.license_plate_placeholder') }}"
                       dir="ltr" class="{{ $inp }} text-right" />
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.chassis_number') }}</label>
                <input wire:model="chassis_number" type="text" placeholder="{{ __('home.chassis_number_placeholder') }}"
                       dir="ltr" class="{{ $inp }} text-right" />
            </div>

            {{-- تاريخ انتهاء الترخيص | حالة السيارة --}}
            <div>
                <label class="{{ $lbl }}">{{ __('home.license_expiry_date') }}</label>
                <input wire:model="license_expiry_date" type="date" class="{{ $inp }}" />
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.vehicle_status') }}</label>
                <select wire:model="status" class="{{ $inp }}">
                    <option value="">{{ __('home.select_status') }}</option>
                    @foreach(\App\Models\Vehicle::STATUSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- أوقات العمل --}}
            <div>
                <label class="{{ $lbl }}">{{ __('home.working_hours') }}</label>
                <select wire:model="working_hours_id" class="{{ $inp }}">
                    <option value="">{{ __('home.select_working_hours') }}</option>
                    @foreach($workingHours as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- ── Section 2: مواقع التمركز ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.vehicle_locations') }}</h3>
            </div>
            <button type="button" wire:click="addLocation"
                    class="text-xs border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 px-3 py-1.5 rounded-lg transition">
                + {{ __('home.add_location') }}
            </button>
        </div>

        <div class="space-y-3">
            @foreach($locations as $i => $loc)
                <div class="flex items-start gap-3">
                    <div class="w-40 shrink-0">
                        <select wire:model="locations.{{ $i }}.day" class="{{ $inp }}">
                            <option value="">{{ __('home.select_day') }}</option>
                            @foreach(\App\Models\VehicleLocation::DAYS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <input wire:model="locations.{{ $i }}.address" type="text"
                               placeholder="{{ __('home.location_address_placeholder') }}" class="{{ $inp }}" />
                    </div>
                    @if(count($locations) > 1)
                        <button type="button" wire:click="removeLocation({{ $i }})"
                                class="mt-0.5 w-8 h-9 flex items-center justify-center rounded-lg border border-red-200 dark:border-red-800 text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Section 3: بيانات إضافية ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.vehicle_extra_data') }}</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.overnight_address') }}</label>
                <input wire:model="overnight_address" type="text" placeholder="{{ __('home.overnight_address_placeholder') }}" class="{{ $inp }}" />
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.storage_room_location') }}</label>
                <input wire:model="storage_room_location" type="text" placeholder="{{ __('home.storage_room_placeholder') }}" class="{{ $inp }}" />
            </div>
            <div class="md:col-span-2">
                <label class="{{ $lbl }}">{{ __('home.notes') }}</label>
                <textarea wire:model="notes" rows="3" placeholder="{{ __('home.notes_placeholder') }}" class="{{ $inp }} resize-none"></textarea>
            </div>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="flex justify-start">
        <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium rounded-lg transition disabled:opacity-60">
            <span wire:loading.remove wire:target="save">{{ __('home.save') }}</span>
            <span wire:loading wire:target="save">{{ __('home.saving') }}</span>
        </button>
    </div>

</div>
