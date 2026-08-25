<?php

namespace App\Livewire\Warehouses;

use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\ArabicText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أرصدة المخازن')]
class Stock extends Component
{
    use WithPagination;

    public string $search = '';
    public string $warehouseFilter = '';

    /** معرّف قسم، أو 'none' للأصناف بلا قسم، أو '' للكل. */
    public string $categoryFilter = '';

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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $stocks = WarehouseStock::query()
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            ->select('warehouse_stocks.*')
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_stocks.warehouse_id', $this->warehouseFilter))
            // قسم الصنف لا المخزن: القسم صفة على الصنف نفسه، فيصل عبر الـjoin القائم
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('items.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            ->with(['warehouse.type', 'item.unit', 'item.category'])
            ->orderBy('warehouses.name')
            ->orderBy('items.name')
            ->paginate(20);

        return view('livewire.warehouses.stock', [
            'stocks'     => $stocks,
            'warehouses' => Warehouse::ordered()->get(),
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }
}
