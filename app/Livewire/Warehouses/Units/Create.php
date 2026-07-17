<?php

namespace App\Livewire\Warehouses\Units;

use App\Models\ItemUnit;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('وحدة صنف')]
class Create extends Component
{
    public ?ItemUnit $itemUnit = null;

    public string $name = '';

    public function mount(?ItemUnit $itemUnit = null): void
    {
        abort_unless(auth()->user()?->can('warehouses.settings'), 403);

        if ($itemUnit?->exists) {
            $this->itemUnit = $itemUnit;
            $this->name     = $itemUnit->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->itemUnit?->exists) {
            $this->itemUnit->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.item_unit_updated'));
        } else {
            ItemUnit::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.item_unit_created'));
        }

        $this->redirect(route('item-units.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.units.create');
    }
}
