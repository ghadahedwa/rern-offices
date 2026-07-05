@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40 focus:border-[#c9a847] transition';
    $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
@endphp

<div class="space-y-6">

    {{-- ── التجهيزات ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_vehicle_equipment') }}</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.mobility_bag') }}</label>
                <select wire:model="mobility_bag" class="{{ $inp }}">
                    <option value="">{{ __('home.select_option') }}</option>
                    <option value="available">{{ __('home.option_available') }}</option>
                    <option value="not_available">{{ __('home.option_not_available') }}</option>
                </select>
                @error('mobility_bag') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.laptops_count') }}</label>
                <input wire:model="laptops_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                @error('laptops_count') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.fingerprints_count') }}</label>
                <input wire:model="fingerprints_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                @error('fingerprints_count') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.printers_count') }}</label>
                <input wire:model="printers_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                @error('printers_count') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.collection_machines_count') }}</label>
                <input wire:model="collection_machines_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                @error('collection_machines_count') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.mifi_count') }}</label>
                <input wire:model="mifi_count" type="number" min="0" placeholder="0" class="{{ $inp }}" />
                @error('mifi_count') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.generator_status') }}</label>
                <select wire:model="generator_status" class="{{ $inp }}">
                    <option value="">{{ __('home.select_option') }}</option>
                    <option value="available">{{ __('home.option_available') }}</option>
                    <option value="broken">{{ __('home.option_broken') }}</option>
                    <option value="not_available">{{ __('home.option_not_available') }}</option>
                </select>
                @error('generator_status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.surveillance_cameras') }}</label>
                <select wire:model="surveillance_cameras" class="{{ $inp }}">
                    <option value="">{{ __('home.select_option') }}</option>
                    <option value="available">{{ __('home.option_available') }}</option>
                    <option value="broken">{{ __('home.option_broken') }}</option>
                    <option value="not_available">{{ __('home.option_not_available') }}</option>
                </select>
                @error('surveillance_cameras') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ── الأجهزة المعطلة ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-5">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_broken_devices') }}</h3>
            </div>
            @if(count($brokenDevices) < count($deviceTypes))
            <button type="button" wire:click="addBrokenDevice"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_device') }}
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
                        <option value="">{{ __('home.select_device_type') }}</option>
                        @foreach($deviceTypes as $type)
                            @if(!in_array($type->id, $otherSelected))
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="w-28">
                    <input type="number" wire:model="brokenDevices.{{ $index }}.count"
                           min="1" placeholder="{{ __('home.device_count') }}"
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
        <p class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">{{ __('home.no_broken_devices') }}</p>
        @endif
    </div>

</div>
