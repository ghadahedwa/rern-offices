<?php

namespace App\Livewire\VehicleTypes;

use App\Models\VehicleType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نوع السيارة')]
class Create extends Component
{
    public ?VehicleType $vehicleType = null;

    public string $name = '';

    public function mount(?VehicleType $vehicleType = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($vehicleType?->exists) {
            $this->vehicleType = $vehicleType;
            $this->name        = $vehicleType->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->vehicleType?->exists) {
            $this->vehicleType->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_type_updated'));
        } else {
            VehicleType::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_type_created'));
        }

        $this->redirect(route('vehicle-types.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.vehicle-types.create');
    }
}
