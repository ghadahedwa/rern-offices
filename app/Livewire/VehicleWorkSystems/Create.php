<?php

namespace App\Livewire\VehicleWorkSystems;

use App\Models\VehicleWorkSystem;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نظام عمل السيارة')]
class Create extends Component
{
    public ?VehicleWorkSystem $vehicleWorkSystem = null;

    public string $name = '';

    public function mount(?VehicleWorkSystem $vehicleWorkSystem = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($vehicleWorkSystem?->exists) {
            $this->vehicleWorkSystem = $vehicleWorkSystem;
            $this->name              = $vehicleWorkSystem->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->vehicleWorkSystem?->exists) {
            $this->vehicleWorkSystem->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_work_system_updated'));
        } else {
            VehicleWorkSystem::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.vehicle_work_system_created'));
        }

        $this->redirect(route('vehicle-work-systems.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.vehicle-work-systems.create');
    }
}
