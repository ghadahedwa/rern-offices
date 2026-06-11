<?php

namespace App\Livewire\DeviceTypes;

use App\Models\DeviceType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نوع الجهاز')]
class Create extends Component
{
    public ?DeviceType $deviceType = null;

    public string $name = '';

    public function mount(?DeviceType $deviceType = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($deviceType?->exists) {
            $this->deviceType = $deviceType;
            $this->name       = $deviceType->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->deviceType?->exists) {
            $this->deviceType->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.device_type_updated'));
        } else {
            DeviceType::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.device_type_created'));
        }

        $this->redirect(route('device-types.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.device-types.create');
    }
}
