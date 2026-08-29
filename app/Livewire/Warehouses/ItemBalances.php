<?php

namespace App\Livewire\Warehouses;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\WarehouseStock;
use App\Support\ArabicDigits;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * أرصدة الأصناف — صفٌّ لكل **صنف** ومعه إجماليه في المخازن كلها.
 *
 * ⚠️ لا تُخلط بشاشة `Stock` («أرصدة المخازن»): تلك صفٌّ لكل (مخزن × صنف)
 *    وتجيب «ما في هذا المخزن؟»، وهذه تجيب «كم عندنا من هذا الصنف كلّه؟».
 *    محوران مختلفان، ولذلك بندان في المنيو باسمين يقولان محورَيهما.
 *
 * ⚠️ وهي مدخل الأصناف لمن يملك `warehouses.index` وحده: شاشة «الأصناف»
 *    تسكن إدارة النظام خلف `warehouses.settings`، فأمين المخزن لم يكن يرى
 *    قائمة أصناف أصلاً ولا سبيل له إلى صفحة الصنف إلا من شاشة الأرصدة.
 */
#[Layout('layouts.app')]
#[Title('أرصدة الأصناف')]
class ItemBalances extends Component
{
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** معرّف قسم، أو 'none' للأصناف بلا قسم، أو '' للكل. */
    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    /** معرّف وحدة، أو 'none' للأصناف بلا وحدة، أو '' للكل. */
    #[Url(as: 'unit', except: '')]
    public string $unitFilter = '';

    /** 'positive' له رصيد في مخزنٍ ما · 'zero' لا رصيد له في أي مخزن · '' الكل. */
    #[Url(as: 'balance', except: '')]
    public string $balanceFilter = '';

    /** 'yes' نشط · 'no' موقوف · '' الكل. */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /**
     * الأصناف التي بلغت حدّها الأدنى **في المخزن الرئيسي**.
     * ⚠️ نفس قاعدة الداشبورد والبروفايل وشاشة الأرصدة وصفحة الصنف — الحد
     *    الأدنى قيمة واحدة للصنف تُقاس على الرئيسي وحده.
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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLowOnly(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'categoryFilter', 'unitFilter', 'balanceFilter', 'statusFilter', 'lowOnly');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->categoryFilter !== '' || $this->unitFilter !== ''
            || $this->balanceFilter !== '' || $this->statusFilter !== '' || $this->lowOnly;
    }

    protected function sortableColumns(): array
    {
        return [
            'name'       => 'items.name',
            'code'       => 'items.code',
            'category'   => ['item_categories.order', 'item_categories.name'],
            'unit'       => 'item_units.name',
            'total'      => 'total_quantity',
            'warehouses' => 'warehouses_count',
            'main'       => 'main_quantity',
        ];
    }

    /** ترتيب الدفتر — تعريفه الواحد في Item::statementOrder. */
    protected function defaultOrder(Builder $query): Builder
    {
        return Item::statementOrder($query);
    }

    /**
     * مجموع رصيد الصنف في المخازن كلها — استعلامٌ مرتبط بصفّ الصنف.
     *
     * ⚠️ يُبنى في دالة لا يُكتب مرتين: العمود المعروض والفلتر يقرآنه معاً،
     *    فتعريفان لرقمٍ واحد يفترقان عند أول تعديل.
     */
    protected function totalSub(): QueryBuilder
    {
        return WarehouseStock::query()
            ->selectRaw('COALESCE(SUM(warehouse_stocks.quantity), 0)')
            ->whereColumn('warehouse_stocks.item_id', 'items.id')
            ->toBase();
    }

    /**
     * رصيد الصنف في المخزن الرئيسي — و**الصنف بلا صفِّ رصيد هناك رصيده صفر**
     * لا NULL، فيدخل تنبيه الحد الأدنى كما يدخله صاحب الصفر المسجَّل.
     *
     * ⚠️ وهنا يفترق هذا الحساب عن شارة شاشة «أرصدة المخازن» بلا مخالفة:
     *    تلك تعرض صفوف الرصيد فلا صفَّ لها تُعلِّمه أصلاً، وهذه تعرض الأصناف.
     *    القاعدة واحدة (الرئيسي وحده) والمعروض مختلف.
     */
    protected function mainSub(): QueryBuilder
    {
        return WarehouseStock::query()
            ->selectRaw('COALESCE(SUM(warehouse_stocks.quantity), 0)')
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->join('warehouse_types', 'warehouses.warehouse_type_id', '=', 'warehouse_types.id')
            ->whereColumn('warehouse_stocks.item_id', 'items.id')
            ->where('warehouse_types.level', 1)
            ->toBase();
    }

    public function render()
    {
        $total = $this->totalSub();
        $main  = $this->mainSub();

        $items = Item::query()
            ->leftJoin('item_categories', 'items.item_category_id', '=', 'item_categories.id')
            ->leftJoin('item_units', 'items.item_unit_id', '=', 'item_units.id')
            ->select('items.*')
            ->selectSub($this->totalSub(), 'total_quantity')
            ->selectSub($this->mainSub(), 'main_quantity')
            // عدد المخازن التي للصنف فيها رصيد فعلي — لا التي له فيها صفٌّ بصفر
            ->selectSub(
                WarehouseStock::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('warehouse_stocks.item_id', 'items.id')
                    ->where('warehouse_stocks.quantity', '>', 0)
                    ->toBase(),
                'warehouses_count'
            )
            ->with(['unit', 'category'])
            // الفلتر يصل من الرابط، فقيمة غير 'none' وغير رقمية تُهمَل ولا تُمرَّر
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when($this->unitFilter === 'none', fn ($q) => $q->whereNull('items.item_unit_id'))
            ->when(ctype_digit($this->unitFilter), fn ($q) => $q->where('items.item_unit_id', (int) $this->unitFilter))
            // ⚠️ `items.is_active` مُؤهَّل: العمود على جدول الأقسام أيضاً فيلتبس بعد الضمّ
            ->when(
                in_array($this->statusFilter, ['yes', 'no'], true),
                fn ($q) => $q->where('items.is_active', $this->statusFilter === 'yes')
            )
            // الرصيد يُقارَن بالاستعلام المرتبط نفسه الذي يبني العمود المعروض
            ->when($this->balanceFilter === 'positive', fn ($q) => $q->whereRaw('('.$total->toSql().') > 0', $total->getBindings()))
            ->when($this->balanceFilter === 'zero', fn ($q) => $q->whereRaw('('.$total->toSql().') <= 0', $total->getBindings()))
            ->when($this->lowOnly, fn ($q) => $q
                ->whereNotNull('items.min_stock')
                ->whereRaw('('.$main->toSql().') <= items.min_stock', $main->getBindings()))
            // البحث يشمل رقم الصنف كشاشتَي الأصناف والأرصدة — والرقم مخزَّن
            // بأرقام هندية فتُحوَّل كلمة البحث
            ->when($this->search, function ($q) {
                $term = ArabicText::normalize($this->search);

                $q->where(fn ($sub) => $sub
                    ->whereRaw(ArabicText::sqlNormalize('items.name').' LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw(
                        ArabicText::sqlNormalize('items.code').' LIKE ?',
                        ['%'.ArabicDigits::toArabic($term).'%']
                    ));
            })
            ->tap(fn ($q) => $this->applySorting($q, 'items.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.item-balances', [
            'items'      => $items,
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
            'units'      => ItemUnit::orderBy('name')->get(),
        ]);
    }
}
