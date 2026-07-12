<?php

namespace App\Livewire\StructuralConditions;

use App\Models\StructuralCondition;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الحالة الإنشائية')]
class Create extends Component
{
    public ?StructuralCondition $structuralCondition = null;

    public string $name = '';

    public function mount(?StructuralCondition $structuralCondition = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($structuralCondition?->exists) {
            $this->structuralCondition = $structuralCondition;
            $this->name                = $structuralCondition->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->structuralCondition?->exists) {
            $this->structuralCondition->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.structural_condition_updated'));
        } else {
            StructuralCondition::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.structural_condition_created'));
        }

        $this->redirect(route('structural-conditions.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.structural-conditions.create');
    }
}
