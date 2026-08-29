<?php

namespace App\Livewire\Warehouses;

use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Support\ArabicText;
use App\Support\WarehouseScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('سجل الحركات')]
class Movements extends Component
{
    use WithDateRange;
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    /** أنواع الحركة المعروفة — القيمة تصل من الرابط فتُحصر فيها. */
    public const TYPES = ['opening', 'incoming', 'transfer_out', 'transfer_in'];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'wh', except: '')]
    public string $warehouseFilter = '';

    #[Url(as: 'item', except: '')]
    public string $itemFilter = '';

    /** معرّف قسم، أو 'none' للحركات على أصناف بلا قسم، أو '' للكل. */
    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();

        // ⚠️ الصنف المختار قد لا ينتمي للقسم الجديد، فيبقى مُطبَّقاً ولا يظهر
        //    في المنسدلة — فتُعرض شاشةٌ فارغة بلا سببٍ ظاهر للمستخدم.
        $this->itemFilter = '';
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'warehouseFilter', 'itemFilter', 'categoryFilter', 'typeFilter', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->warehouseFilter !== '' || $this->itemFilter !== ''
            || $this->categoryFilter !== '' || $this->typeFilter !== '' || $this->hasDateFilter();
    }

    protected function sortableColumns(): array
    {
        return [
            'date'           => 'warehouse_movements.created_at',
            'warehouse'      => 'warehouses.name',
            'item'           => 'items.name',
            'type'           => 'warehouse_movements.type',
            'quantity'       => 'warehouse_movements.quantity',
            'balance_after'  => 'warehouse_movements.balance_after',
        ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('warehouse_movements.created_at')
            ->orderByDesc('warehouse_movements.id');
    }

    public function render()
    {
        $movements = WarehouseMovement::query()
            ->join('warehouses', 'warehouse_movements.warehouse_id', '=', 'warehouses.id')
            ->join('items', 'warehouse_movements.item_id', '=', 'items.id')
            ->select('warehouse_movements.*')
            ->tap(fn ($q) => WarehouseScope::apply($q, 'warehouse_movements.warehouse_id'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('items.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                );
            }))
            // القيم تصل من الرابط — غير الرقمية تُهمَل ولا تُمرَّر لاستعلام
            ->when(ctype_digit($this->warehouseFilter), fn ($q) => $q->where('warehouse_movements.warehouse_id', (int) $this->warehouseFilter))
            ->when(ctype_digit($this->itemFilter), fn ($q) => $q->where('warehouse_movements.item_id', (int) $this->itemFilter))
            // قسم الصنف لا المخزن — يصل عبر الـjoin القائم على items
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when(in_array($this->typeFilter, self::TYPES, true), fn ($q) => $q->where('warehouse_movements.type', $this->typeFilter))
            // ⚠️ created_at لحظة مخزَّنة بـUTC والفلتر يوم بتوقيت القاهرة — التحويل في WithDateRange
            ->tap(fn ($q) => $this->applyTimestampRange($q, 'warehouse_movements.created_at'))
            ->with(['warehouse', 'item', 'user'])
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_movements.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.movements', [
            'movements'  => $movements,
            'warehouses' => WarehouseScope::warehouses(),
            // منسدلة الأصناف تتبع القسم المختار — ٣٧٧ صنفاً في قائمة واحدة
            // مسطّحة لا تُتصفَّح، وحصرُها في القسم هو ما يجعل الفلتر صالحاً
            'items' => Item::query()
                ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
                ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
                ->inStatementOrder()
                ->get(),
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
            'types'      => self::TYPES,
        ]);
    }
}
