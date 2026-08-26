<?php

namespace App\Livewire\Warehouses;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أرصدة المخازن')]
class Stock extends Component
{
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** معرّف مخزن، أو '' للكل. */
    #[Url(as: 'wh', except: '')]
    public string $warehouseFilter = '';

    /** معرّف قسم، أو 'none' للأصناف بلا قسم، أو '' للكل. */
    #[Url(as: 'category', except: '')]
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

    public function resetFilters(): void
    {
        $this->reset('search', 'warehouseFilter', 'categoryFilter');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->warehouseFilter !== '' || $this->categoryFilter !== '';
    }

    protected function sortableColumns(): array
    {
        return [
            'warehouse' => 'warehouses.name',
            'item'      => 'items.name',
            'unit'      => 'item_units.name',
            'quantity'  => 'warehouse_stocks.quantity',
        ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $query->orderBy('warehouses.name')->orderBy('items.name');
    }

    public function render()
    {
        $stocks = WarehouseStock::query()
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            // مضموم لأجل الترتيب بالوحدة؛ والعرض يبقى على العلاقات المحمَّلة
            ->leftJoin('item_units', 'items.item_unit_id', '=', 'item_units.id')
            ->select('warehouse_stocks.*')
            // قيمة غير رقمية تصل من الرابط تُهمَل — وإلا خرجت شاشة فارغة بلا سبب ظاهر
            ->when(ctype_digit($this->warehouseFilter), fn ($q) => $q->where('warehouse_stocks.warehouse_id', (int) $this->warehouseFilter))
            // قسم الصنف لا المخزن: القسم صفة على الصنف نفسه، فيصل عبر الـjoin القائم
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('items.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            ->with(['warehouse.type', 'item.unit', 'item.category'])
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_stocks.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.stock', [
            'stocks'     => $stocks,
            'warehouses' => Warehouse::ordered()->get(),
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }
}
