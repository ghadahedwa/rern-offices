<?php

namespace App\Livewire\VehicleDeviceTypes;

use App\Models\VehicleDeviceType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نوع جهاز السيارة')]
class Create extends Component
{
    public ?VehicleDeviceType $vehicleDeviceType = null;

    public string $name = '';

    public function mount(?VehicleDeviceType $vehicleDeviceType = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($vehicleDeviceType?->exists) {
            $this->vehicleDeviceType = $vehicleDeviceType;
            $this->name              = $vehicleDeviceType->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->vehicleDeviceType?->exists) {
            $this->vehicleDeviceType->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_device_type_updated'));
        } else {
            VehicleDeviceType::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_device_type_created'));
        }

        $this->redirect(route('vehicle-device-types.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.vehicle-device-types.create');
    }
}
