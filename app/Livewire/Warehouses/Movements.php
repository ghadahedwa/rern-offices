<?php

namespace App\Livewire\Warehouses;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Support\ArabicText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('سجل الحركات')]
class Movements extends Component
{
    use WithPagination;

    public string $search = '';
    public string $warehouseFilter = '';
    public string $itemFilter = '';
    public string $typeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.index'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingItemFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $movements = WarehouseMovement::query()
            ->join('warehouses', 'warehouse_movements.warehouse_id', '=', 'warehouses.id')
            ->join('items', 'warehouse_movements.item_id', '=', 'items.id')
            ->select('warehouse_movements.*')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('items.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                );
            }))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_movements.warehouse_id', $this->warehouseFilter))
            ->when($this->itemFilter, fn ($q) => $q->where('warehouse_movements.item_id', $this->itemFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('warehouse_movements.type', $this->typeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('warehouse_movements.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('warehouse_movements.created_at', '<=', $this->dateTo))
            ->with(['warehouse', 'item', 'user'])
            ->orderByDesc('warehouse_movements.created_at')
            ->orderByDesc('warehouse_movements.id')
            ->paginate(20);

        return view('livewire.warehouses.movements', [
            'movements'  => $movements,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'items'      => Item::orderBy('name')->get(),
        ]);
    }
}
