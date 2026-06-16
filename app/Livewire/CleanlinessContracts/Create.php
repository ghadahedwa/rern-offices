<?php

namespace App\Livewire\CleanlinessContracts;

use App\Models\CleanlinessContract;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('عقد النظافة')]
class Create extends Component
{
    public ?CleanlinessContract $cleanlinessContract = null;

    public string $name = '';

    public function mount(?CleanlinessContract $cleanlinessContract = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($cleanlinessContract?->exists) {
            $this->cleanlinessContract = $cleanlinessContract;
            $this->name                = $cleanlinessContract->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->cleanlinessContract?->exists) {
            $this->cleanlinessContract->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.cleanliness_contract_updated'));
        } else {
            CleanlinessContract::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.cleanliness_contract_created'));
        }

        $this->redirect(route('cleanliness-contracts.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.cleanliness-contracts.create');
    }
}
