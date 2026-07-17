<?php

namespace App\Livewire\Warehouses\Items;

use App\Models\Item;
use App\Models\ItemUnit;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('صنف')]
class Create extends Component
{
    public ?Item $item = null;

    public string $name = '';
    public ?int $item_unit_id = null;
    public ?int $min_stock = null;
    public bool $is_active = true;

    public function mount(?Item $item = null): void
    {
        abort_unless(auth()->user()?->can('warehouses.settings'), 403);

        if ($item?->exists) {
            $this->item         = $item;
            $this->name         = $item->name;
            $this->item_unit_id = $item->item_unit_id;
            $this->min_stock    = $item->min_stock;
            $this->is_active    = $item->is_active;
        } else {
            // القيمة الافتراضية: «قطعة» (أو أول وحدة متاحة) لتقليل الاحتكاك
            $this->item_unit_id = ItemUnit::where('name', 'قطعة')->value('id')
                ?? ItemUnit::orderBy('id')->value('id');
        }
    }

    protected function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'item_unit_id' => ['required', 'exists:item_units,id'],
            'min_stock'    => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->item?->exists) {
            $this->item->update($data);
            Flux::toast(variant: 'success', text: __('home.item_updated'));
        } else {
            Item::create($data);
            Flux::toast(variant: 'success', text: __('home.item_created'));
        }

        $this->redirect(route('items.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.items.create', [
            'units' => ItemUnit::orderBy('name')->get(),
        ]);
    }
}
