<?php

namespace App\Livewire\WorkingHours;

use App\Models\WorkingHour;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('وقت العمل')]
class Create extends Component
{
    public ?WorkingHour $workingHour = null;

    public string $name = '';

    public function mount(?WorkingHour $workingHour = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($workingHour?->exists) {
            $this->workingHour = $workingHour;
            $this->name        = $workingHour->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->workingHour?->exists) {
            $this->workingHour->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.working_hour_updated'));
        } else {
            WorkingHour::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.working_hour_created'));
        }

        $this->redirect(route('working-hours.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.working-hours.create');
    }
}
