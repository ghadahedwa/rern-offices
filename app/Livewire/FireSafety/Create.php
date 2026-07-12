<?php

namespace App\Livewire\FireSafety;

use App\Models\FireSafety as FireSafetyModel;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الحماية المدنية')]
class Create extends Component
{
    public ?FireSafetyModel $fireSafety = null;

    public string $name = '';

    public function mount(?FireSafetyModel $fireSafety = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($fireSafety?->exists) {
            $this->fireSafety = $fireSafety;
            $this->name       = $fireSafety->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->fireSafety?->exists) {
            $this->fireSafety->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.fire_safety_updated'));
        } else {
            FireSafetyModel::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.fire_safety_created'));
        }

        $this->redirect(route('fire-safety.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.fire-safety.create');
    }
}
