<?php

namespace App\Livewire\WorkSystems;

use App\Models\WorkSystem;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نظام العمل')]
class Create extends Component
{
    public ?WorkSystem $workSystem = null;

    public string $name = '';

    public function mount(?WorkSystem $workSystem = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($workSystem?->exists) {
            $this->workSystem = $workSystem;
            $this->name       = $workSystem->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->workSystem?->exists) {
            $this->workSystem->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.work_system_updated'));
        } else {
            WorkSystem::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.work_system_created'));
        }

        $this->redirect(route('work-systems.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.work-systems.create');
    }
}
