<?php

namespace App\Livewire\Warehouses;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\ArabicDigits;
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

    /** معرّف وحدة، أو 'none' للأصناف بلا وحدة، أو '' للكل. */
    #[Url(as: 'unit', except: '')]
    public string $unitFilter = '';

    /** 'positive' أكبر من صفر · 'zero' صفر · '' الكل. */
    #[Url(as: 'balance', except: '')]
    public string $balanceFilter = '';

    /**
     * الأصناف التي بلغت حدّها الأدنى.
     * ⚠️ القاعدة على **المخازن الرئيسية وحدها** (level=1) كما في الداشبورد
     *    وبروفايل المخزن — الحد الأدنى قيمة واحدة للصنف تُقاس على الرئيسي.
     */
    #[Url(as: 'low', except: false)]
    public bool $lowOnly = false;

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

    public function updatingUnitFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBalanceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLowOnly(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'warehouseFilter', 'categoryFilter', 'unitFilter', 'balanceFilter', 'lowOnly');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->warehouseFilter !== '' || $this->categoryFilter !== ''
            || $this->unitFilter !== '' || $this->balanceFilter !== '' || $this->lowOnly;
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

    /** المخزن أولاً، وداخله ترتيب الدفتر (القسم ثم ترتيب الصنف) لا الأبجدي. */
    protected function defaultOrder(Builder $query): Builder
    {
        return Item::statementOrder($query->orderBy('warehouses.name'));
    }

    public function render()
    {
        $stocks = WarehouseStock::query()
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            // مضمومان لأجل الترتيب: الأقسام لترتيب الدفتر، والوحدات لعمود الوحدة
            ->leftJoin('item_categories', 'items.item_category_id', '=', 'item_categories.id')
            ->leftJoin('item_units', 'items.item_unit_id', '=', 'item_units.id')
            ->select('warehouse_stocks.*')
            // قيمة غير رقمية تصل من الرابط تُهمَل — وإلا خرجت شاشة فارغة بلا سبب ظاهر
            ->when(ctype_digit($this->warehouseFilter), fn ($q) => $q->where('warehouse_stocks.warehouse_id', (int) $this->warehouseFilter))
            // قسم الصنف لا المخزن: القسم صفة على الصنف نفسه، فيصل عبر الـjoin القائم
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when($this->unitFilter === 'none', fn ($q) => $q->whereNull('items.item_unit_id'))
            ->when(ctype_digit($this->unitFilter), fn ($q) => $q->where('items.item_unit_id', (int) $this->unitFilter))
            // «صفر» تشمل ما دونه: الرصيد السالب خطأ بيانات، وإخفاؤه أسوأ من إظهاره
            ->when($this->balanceFilter === 'zero', fn ($q) => $q->where('warehouse_stocks.quantity', '<=', 0))
            ->when($this->balanceFilter === 'positive', fn ($q) => $q->where('warehouse_stocks.quantity', '>', 0))
            // ⚠️ الحد الأدنى يُقاس على المخازن الرئيسية وحدها — الشرط على نوع مخزن
            //    الصف نفسه، لا على الصنف مطلقاً، وإلا ظهرت صفوف فروعٍ لا حدّ لها
            ->when($this->lowOnly, fn ($q) => $q
                ->whereNotNull('items.min_stock')
                ->whereColumn('warehouse_stocks.quantity', '<=', 'items.min_stock')
                ->whereExists(fn ($sub) => $sub->from('warehouse_types')
                    ->whereColumn('warehouse_types.id', 'warehouses.warehouse_type_id')
                    ->where('warehouse_types.level', 1)))
            // البحث يشمل رقم الصنف كشاشة الأصناف: الموظف يعرف أصناف الدفتر
            // العقاري بأرقامها. والرقم مخزَّن بأرقام هندية فتُحوَّل كلمة البحث
            ->when($this->search, function ($q) {
                $term = ArabicText::normalize($this->search);

                $q->where(fn ($sub) => $sub
                    ->whereRaw(ArabicText::sqlNormalize('items.name').' LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw(
                        ArabicText::sqlNormalize('items.code').' LIKE ?',
                        ['%'.ArabicDigits::toArabic($term).'%']
                    ));
            })
            ->with(['warehouse.type', 'item.unit', 'item.category'])
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_stocks.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.stock', [
            'stocks'     => $stocks,
            'warehouses' => Warehouse::ordered()->get(),
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
            'units'      => ItemUnit::orderBy('name')->get(),
        ]);
    }
}
