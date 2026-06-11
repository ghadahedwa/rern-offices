<?php

namespace App\Livewire\ContractualStatuses;

use App\Models\ContractualStatus;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الحالة التعاقدية')]
class Create extends Component
{
    public ?ContractualStatus $contractualStatus = null;

    public string $name = '';

    public function mount(?ContractualStatus $contractualStatus = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($contractualStatus?->exists) {
            $this->contractualStatus = $contractualStatus;
            $this->name              = $contractualStatus->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->contractualStatus?->exists) {
            $this->contractualStatus->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.contractual_status_updated'));
        } else {
            ContractualStatus::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.contractual_status_created'));
        }

        $this->redirect(route('contractual-statuses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.contractual-statuses.create');
    }
}
