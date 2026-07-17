<?php

namespace App\Livewire\Warehouses\Types;

use App\Models\WarehouseType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('نوع مخزن')]
class Create extends Component
{
    public ?WarehouseType $warehouseType = null;

    public string $name = '';
    public ?int $level = 1;
    public ?int $order = 0;

    public function mount(?WarehouseType $warehouseType = null): void
    {
        abort_unless(auth()->user()?->can('warehouses.settings'), 403);

        if ($warehouseType?->exists) {
            $this->warehouseType = $warehouseType;
            $this->name  = $warehouseType->name;
            $this->level = $warehouseType->level;
            $this->order = $warehouseType->order;
        }
    }

    protected function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:99'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['order'] = $this->order ?? 0;

        if ($this->warehouseType?->exists) {
            $this->warehouseType->update($data);
            Flux::toast(variant: 'success', text: __('home.warehouse_type_updated'));
        } else {
            WarehouseType::create($data);
            Flux::toast(variant: 'success', text: __('home.warehouse_type_created'));
        }

        $this->redirect(route('warehouse-types.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.types.create');
    }
}
