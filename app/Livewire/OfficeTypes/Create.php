<?php

namespace App\Livewire\OfficeTypes;

use App\Models\OfficeType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نوع المقر')]
class Create extends Component
{
    public ?OfficeType $officeType = null;

    public string $name = '';

    public function mount(?OfficeType $officeType = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($officeType?->exists) {
            $this->officeType = $officeType;
            $this->name       = $officeType->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->officeType?->exists) {
            $this->officeType->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.office_type_updated'));
        } else {
            OfficeType::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.office_type_created'));
        }

        $this->redirect(route('office-types.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.office-types.create');
    }
}
