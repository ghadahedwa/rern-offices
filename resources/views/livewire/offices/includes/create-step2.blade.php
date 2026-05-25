
            {{-- ── Section 1: الخدمات والتجهيزات ── --}}
            <div class="mb-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_services_equipment') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.microfilm_option') }}</label>
                        <select wire:model="microfilm_option_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            @foreach ($microfilmOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.disabilities_access_label') }}</label>
                        <select wire:model="disabilities_access_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            @foreach ($disabilitiesOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.fire_safety_label') }}</label>
                        <select wire:model="fire_safety_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            @foreach ($fireSafetyOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.document_photocopying_service') }}</label>
                        <select wire:model="document_photocopying_service_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            @foreach ($photocopyingOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.buffet_service') }}</label>
                        <select wire:model="buffet_service_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            @foreach ($buffetOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.cleanliness_contract') }}</label>
                        <select wire:model="cleanliness_contract_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            @foreach ($cleanlinessContractOptions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 mb-2"></div>

            {{-- ── Section 2: الأجهزة والمعدات ── --}}
            <div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_devices_counts') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.braille_sign_device') }}</label>
                        <select wire:model="Braille_sign_device" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            <option value="available">{{ __('home.option_available') }}</option>
                            <option value="not_available">{{ __('home.option_not_available') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.queue_management_system') }}</label>
                        <select wire:model="queue_management_system" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            <option value="working">{{ __('home.option_working') }}</option>
                            <option value="not_working">{{ __('home.option_not_working') }}</option>
                            <option value="not_available">{{ __('home.option_not_available') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.payment_machine_count') }}</label>
                        <input wire:model="payment_machine_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.computers_count') }}</label>
                        <input wire:model="computers_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">عدد شاشات العرض</label>
                        <input wire:model="monitors_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.scanners_count') }}</label>
                        <input wire:model="scanners_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.printers_count') }}</label>
                        <input wire:model="printers_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.fingerprints_count') }}</label>
                        <input wire:model="fingerprints_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">عدد التكييفات</label>
                        <input wire:model="air_conditioners_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                    </div>
                    <div>
                        <label class="{{ $lbl }}">عداد الكهرباء</label>
                        <select wire:model="electricity_meter_type" class="{{ $inp }}">
                            <option value="">— اختر —</option>
                            <option value="prepaid">مسبق الدفع</option>
                            <option value="invoice">فاتورة</option>
                            <option value="entity_meter">عداد خاص بالجهة التابع لها المقر</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">مديونية عداد الكهرباء</label>
                        <select wire:model="electricity_meter_debt" class="{{ $inp }}">
                            <option value="">— اختر —</option>
                            <option value="yes">يوجد</option>
                            <option value="no">لا يوجد</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">عداد المياه</label>
                        <select wire:model="water_meter_type" class="{{ $inp }}">
                            <option value="">— اختر —</option>
                            <option value="prepaid">مسبق الدفع</option>
                            <option value="invoice">فاتورة</option>
                            <option value="entity_meter">عداد خاص بالجهة التابع لها المقر</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">مديونية عداد المياه</label>
                        <select wire:model="water_meter_debt" class="{{ $inp }}">
                            <option value="">— اختر —</option>
                            <option value="yes">يوجد</option>
                            <option value="no">لا يوجد</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-700 mb-2"></div>

            {{-- ── Section 3: الأجهزة المعطلة ── --}}
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">الأجهزة المعطلة</h3>
                    </div>
                    @if(count($brokenDevices) < count($deviceTypes))
                    <button type="button" wire:click="addBrokenDevice"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        إضافة جهاز
                    </button>
                    @endif
                </div>

                @if(count($brokenDevices) > 0)
                @php
                    $selectedDeviceTypeIds = collect($brokenDevices)->pluck('device_type_id')->filter()->map(fn($v) => (int)$v)->toArray();
                @endphp
                <div class="space-y-3">
                    @foreach($brokenDevices as $index => $device)
                    @php
                        $currentId = (int)($device['device_type_id'] ?? 0);
                        $otherSelected = array_filter($selectedDeviceTypeIds, fn($id) => $id !== $currentId);
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <select wire:model="brokenDevices.{{ $index }}.device_type_id" class="{{ $inp }}">
                                <option value="">— نوع الجهاز —</option>
                                @foreach($deviceTypes as $type)
                                    @if(!in_array($type->id, $otherSelected))
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="w-28">
                            <input type="number" wire:model="brokenDevices.{{ $index }}.count"
                                   min="1" placeholder="العدد"
                                   class="{{ $inp }}" />
                        </div>
                        <button type="button" wire:click="removeBrokenDevice({{ $index }})"
                                class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">لا توجد أجهزة معطلة مسجلة</p>
                @endif
            </div>
