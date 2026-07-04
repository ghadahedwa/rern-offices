<?php

namespace App\Livewire\VehicleBrands;

use App\Models\VehicleBrand;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('ماركة السيارة')]
class Create extends Component
{
    public ?VehicleBrand $vehicleBrand = null;

    public string $name = '';

    public function mount(?VehicleBrand $vehicleBrand = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($vehicleBrand?->exists) {
            $this->vehicleBrand = $vehicleBrand;
            $this->name         = $vehicleBrand->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->vehicleBrand?->exists) {
            $this->vehicleBrand->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_brand_updated'));
        } else {
            VehicleBrand::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_brand_created'));
        }

        $this->redirect(route('vehicle-brands.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.vehicle-brands.create');
    }
}
