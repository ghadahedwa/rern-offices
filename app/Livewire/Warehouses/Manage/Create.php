<?php

namespace App\Livewire\Warehouses\Manage;

use App\Models\Governorate;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('مخزن')]
class Create extends Component
{
    public ?Warehouse $warehouse = null;

    public string $name = '';
    public ?int $warehouse_type_id = null;
    public ?int $governorate_id = null;
    public bool $is_active = true;

    public function mount(?Warehouse $warehouse = null): void
    {
        abort_unless(auth()->user()?->can('warehouses.settings'), 403);

        if ($warehouse?->exists) {
            $this->warehouse         = $warehouse;
            $this->name              = $warehouse->name;
            $this->warehouse_type_id = $warehouse->warehouse_type_id;
            $this->governorate_id    = $warehouse->governorate_id;
            $this->is_active         = $warehouse->is_active;
        }
    }

    protected function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'warehouse_type_id' => ['required', 'exists:warehouse_types,id'],
            'governorate_id'    => ['nullable', 'exists:governorates,id'],
            'is_active'         => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->warehouse?->exists) {
            $this->warehouse->update($data);
            Flux::toast(variant: 'success', text: __('home.warehouse_updated'));
        } else {
            Warehouse::create($data);
            Flux::toast(variant: 'success', text: __('home.warehouse_created'));
        }

        $this->redirect(route('warehouse-manage.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.manage.create', [
            'types'         => WarehouseType::orderBy('level')->orderBy('order')->get(),
            'governorates'  => Governorate::orderBy('name')->get(),
        ]);
    }
}
