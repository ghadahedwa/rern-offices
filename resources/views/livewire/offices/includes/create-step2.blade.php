
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
                </div>
            </div>
