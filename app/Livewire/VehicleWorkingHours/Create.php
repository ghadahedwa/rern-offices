<?php

namespace App\Livewire\VehicleWorkingHours;

use App\Models\VehicleWorkingHour;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('وقت عمل السيارة')]
class Create extends Component
{
    public ?VehicleWorkingHour $vehicleWorkingHour = null;

    public string $name = '';

    public function mount(?VehicleWorkingHour $vehicleWorkingHour = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($vehicleWorkingHour?->exists) {
            $this->vehicleWorkingHour = $vehicleWorkingHour;
            $this->name               = $vehicleWorkingHour->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->vehicleWorkingHour?->exists) {
            $this->vehicleWorkingHour->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_working_hour_updated'));
        } else {
            VehicleWorkingHour::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_working_hour_created'));
        }

        $this->redirect(route('vehicle-working-hours.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.vehicle-working-hours.create');
    }
}
