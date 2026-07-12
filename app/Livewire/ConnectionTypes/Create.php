<?php

namespace App\Livewire\ConnectionTypes;

use App\Models\ConnectionType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نوع الاتصال')]
class Create extends Component
{
    public ?ConnectionType $connectionType = null;

    public string $name = '';

    public function mount(?ConnectionType $connectionType = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($connectionType?->exists) {
            $this->connectionType = $connectionType;
            $this->name           = $connectionType->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->connectionType?->exists) {
            $this->connectionType->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.connection_type_updated'));
        } else {
            ConnectionType::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.connection_type_created'));
        }

        $this->redirect(route('connection-types.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.connection-types.create');
    }
}
