<?php

namespace App\Livewire\Warehouses\Manage;

use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('بروفايل المخزن')]
class Show extends Component
{
    use WithDateRange;
    use WithPagination {
        resetPage as protected basePaginationReset;
    }
    use WithPerPage;
    use WithTableSorting;

    /** أنواع الحركة المعروفة — القيمة تصل من الرابط فتُحصر فيها. */
    public const MOVEMENT_TYPES = ['opening', 'incoming', 'transfer_out', 'transfer_in'];

    public Warehouse $warehouse;
    public bool $canEdit = false;

    #[Url(as: 'tab', except: 'stock')]
    public string $tab = 'stock';

    /**
     * فلاتر مشتركة بين التابات — لا فلتر لكل تاب.
     *
     * تابٌ واحد معروض في كل لحظة، وتغييرُ التاب يمسح الفلاتر (سطر البحث في
     * الأرصدة يعني اسم صنف وفي النقل يعني اسم مخزن — إبقاؤه بين التابين يُخرج
     * شاشة فارغة بلا سبب ظاهر). فالتوحيد يختصر ١١ خاصية إلى خمس بلا خسارة.
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'item', except: '')]
    public string $itemFilter = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    // فلاتر تاب الأرصدة
    /** معرّف قسم، أو 'none' لأصناف بلا قسم، أو '' للكل. */
    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    /** معرّف وحدة، أو 'none' لأصناف بلا وحدة، أو '' للكل. */
    #[Url(as: 'unit', except: '')]
    public string $unitFilter = '';

    /** 'positive' أكبر من صفر · 'zero' صفر · '' الكل. */
    #[Url(as: 'balance', except: '')]
    public string $balanceFilter = '';

    /** الأصناف التي بلغت حدّها الأدنى — للمخزن الرئيسي وحده. */
    #[Url(as: 'low', except: false)]
    public bool $lowOnly = false;

    public bool $showViewIncoming = false;
    public ?WarehouseIncoming $viewingIncoming = null;

    public bool $showViewTransfer = false;
    public ?WarehouseTransfer $viewingTransfer = null;

    public function mount(Warehouse $warehouse): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);

        $this->warehouse = $warehouse->load('type', 'governorate', 'stocks.item');
        $this->canEdit   = (bool) Auth::user()?->can('warehouses.settings');

        // التاب يصل من الرابط أيضاً — يُفحص هنا لا في setTab وحدها
        if (! in_array($this->tab, $this->validTabs(), true)) {
            $this->tab = 'stock';
        }
    }

    /** @return array<int, string> */
    protected function validTabs(): array
    {
        $tabs = ['stock', 'movements', 'transfers'];

        // الوارد يُسجَّل على المخزن الرئيسي وحده، فلا تاب له في غيره
        if ($this->warehouse->isMain()) {
            $tabs[] = 'incoming';
        }

        return $tabs;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, $this->validTabs(), true) ? $tab : 'stock';

        // الفلاتر والترتيب يخصّان جدول التاب السابق — أعمدة التاب الجديد غيرها
        $this->resetFilters();
        $this->resetSort();
    }

    /** مُرقِّم التاب المعروض — لكل تاب مُرقِّمه حتى لا يتداخل ترقيم جدولين. */
    protected function tabPageName(): string
    {
        return match ($this->tab) {
            'movements' => 'movPage',
            'incoming'  => 'incPage',
            'transfers' => 'transPage',
            default     => 'stockPage',
        };
    }

    /**
     * ⚠️ الـtraits المشتركة تنادي resetPage() بلا اسم، والصفحة هنا مُرقِّم
     *    مسمّى — فبلا هذا التوجيه يُصفَّر مُرقِّم 'page' الذي لا يستعمله أحد،
     *    ويبقى المستخدم على صفحة ٣ من نتيجةٍ صارت صفحة واحدة.
     */
    public function resetPage($pageName = null)
    {
        return $this->basePaginationReset($pageName ?? $this->tabPageName());
    }

    public function updatingSearch(): void
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
        $this->reset('search', 'itemFilter', 'typeFilter', 'categoryFilter', 'unitFilter', 'balanceFilter', 'lowOnly', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    /** الفلاتر المعروضة تختلف بالتاب، فكذلك حساب «هل من فلتر مفعّل؟». */
    public function hasActiveFilters(): bool
    {
        return match ($this->tab) {
            'stock'     => $this->search !== '' || $this->categoryFilter !== ''
                || $this->unitFilter !== '' || $this->balanceFilter !== '' || $this->lowOnly,
            'movements' => $this->itemFilter !== '' || $this->typeFilter !== '' || $this->hasDateFilter(),
            default     => $this->search !== '' || $this->hasDateFilter(),
        };
    }

    protected function sortableColumns(): array
    {
        return match ($this->tab) {
            'movements' => [
                'item'          => 'items.name',
                'type'          => 'warehouse_movements.type',
                'quantity'      => 'warehouse_movements.quantity',
                'balance_after' => 'warehouse_movements.balance_after',
                'date'          => 'warehouse_movements.created_at',
            ],
            'incoming' => [
                'received_at' => 'warehouse_incomings.received_at',
                'supplier'    => 'warehouse_incomings.supplier_name',
                'items_count' => 'items_count',
            ],
            'transfers' => [
                'transferred_at' => 'warehouse_transfers.transferred_at',
                'from'           => 'w_from.name',
                'to'             => 'w_to.name',
                'document_type'  => 'warehouse_transfers.document_type',
                'items_count'    => 'items_count',
            ],
            default => [
                'item'     => 'items.name',
                'unit'     => 'item_units.name',
                'quantity' => 'warehouse_stocks.quantity',
            ],
        };
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return match ($this->tab) {
            'movements' => $query
                ->orderByDesc('warehouse_movements.created_at')
                ->orderByDesc('warehouse_movements.id'),
            'incoming' => $query
                ->orderByDesc('warehouse_incomings.received_at')
                ->orderByDesc('warehouse_incomings.id'),
            'transfers' => $query
                ->orderByDesc('warehouse_transfers.transferred_at')
                ->orderByDesc('warehouse_transfers.id'),
            // تاب الأرصدة: ترتيب الدفتر لا الأبجدي (كشاشة الأصناف والأرصدة)
            default => Item::statementOrder($query),
        };
    }

    public function viewIncoming(int $id): void
    {
        $this->viewingIncoming = WarehouseIncoming::with(['warehouse', 'items.item.unit'])->findOrFail($id);
        $this->showViewIncoming = true;
    }

    public function viewTransfer(int $id): void
    {
        $this->viewingTransfer = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'items.item.unit'])->findOrFail($id);
        $this->showViewTransfer = true;
    }

    protected function stockList()
    {
        return WarehouseStock::query()
            ->where('warehouse_stocks.warehouse_id', $this->warehouse->id)
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            // مضمومان لأجل الترتيب: الأقسام لترتيب الدفتر، والوحدات لعمود الوحدة
            ->leftJoin('item_categories', 'items.item_category_id', '=', 'item_categories.id')
            ->leftJoin('item_units', 'items.item_unit_id', '=', 'item_units.id')
            ->select('warehouse_stocks.*')
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('items.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            // القيم تصل من الرابط — غير 'none' وغير الرقمية تُهمَل
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when($this->unitFilter === 'none', fn ($q) => $q->whereNull('items.item_unit_id'))
            ->when(ctype_digit($this->unitFilter), fn ($q) => $q->where('items.item_unit_id', (int) $this->unitFilter))
            // «صفر» تشمل ما دونه: الرصيد السالب خطأ بيانات، وإخفاؤه أسوأ من إظهاره
            ->when($this->balanceFilter === 'zero', fn ($q) => $q->where('warehouse_stocks.quantity', '<=', 0))
            ->when($this->balanceFilter === 'positive', fn ($q) => $q->where('warehouse_stocks.quantity', '>', 0))
            // ⚠️ الحد الأدنى قاعدةٌ على المخزن الرئيسي وحده (كشارة الجدول تماماً)،
            //    والفحص هنا لا في القالب فقط — الفلتر يصل من الرابط أيضاً
            ->when($this->lowOnly && $this->warehouse->isMain(), fn ($q) => $q
                ->whereNotNull('items.min_stock')
                ->whereColumn('warehouse_stocks.quantity', '<=', 'items.min_stock'))
            ->with('item.unit')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_stocks.id'))
            ->paginate($this->perPage(), ['*'], 'stockPage');
    }

    protected function movementsList()
    {
        return WarehouseMovement::query()
            ->where('warehouse_movements.warehouse_id', $this->warehouse->id)
            ->join('items', 'warehouse_movements.item_id', '=', 'items.id')
            ->select('warehouse_movements.*')
            // القيم تصل من الرابط — غير الرقمية وغير المعروفة تُهمَل
            ->when(ctype_digit($this->itemFilter), fn ($q) => $q->where('warehouse_movements.item_id', (int) $this->itemFilter))
            ->when(in_array($this->typeFilter, self::MOVEMENT_TYPES, true), fn ($q) => $q->where('warehouse_movements.type', $this->typeFilter))
            // ⚠️ created_at لحظة مخزَّنة بـUTC والفلتر يوم بتوقيت القاهرة
            ->tap(fn ($q) => $this->applyTimestampRange($q, 'warehouse_movements.created_at'))
            ->with(['item', 'user'])
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_movements.id'))
            ->paginate($this->perPage(), ['*'], 'movPage');
    }

    protected function incomingList()
    {
        if (! $this->warehouse->isMain()) {
            return null;
        }

        return WarehouseIncoming::query()
            ->where('warehouse_incomings.warehouse_id', $this->warehouse->id)
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('warehouse_incomings.supplier_name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            // received_at يومٌ كتبه المستخدم (مصبوب 'date') — بلا تحويل توقيت
            ->tap(fn ($q) => $this->applyDayRange($q, 'warehouse_incomings.received_at'))
            ->withCount('items')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_incomings.id'))
            ->paginate($this->perPage(), ['*'], 'incPage');
    }

    protected function transfersList()
    {
        return WarehouseTransfer::query()
            ->where(function ($q) {
                $q->where('from_warehouse_id', $this->warehouse->id)
                    ->orWhere('to_warehouse_id', $this->warehouse->id);
            })
            ->join('warehouses as w_from', 'warehouse_transfers.from_warehouse_id', '=', 'w_from.id')
            ->join('warehouses as w_to', 'warehouse_transfers.to_warehouse_id', '=', 'w_to.id')
            ->select('warehouse_transfers.*')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('w_from.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('w_to.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('warehouse_transfers.document_type').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                );
            }))
            ->tap(fn ($q) => $this->applyDayRange($q, 'warehouse_transfers.transferred_at'))
            ->with(['fromWarehouse', 'toWarehouse'])
            ->withCount('items')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_transfers.id'))
            ->paginate($this->perPage(), ['*'], 'transPage');
    }

    public function render()
    {
        return view('livewire.warehouses.manage.show', [
            'stocks'    => $this->tab === 'stock' ? $this->stockList() : null,
            'movements' => $this->tab === 'movements' ? $this->movementsList() : null,
            'incomings' => $this->tab === 'incoming' ? $this->incomingList() : null,
            'transfers' => $this->tab === 'transfers' ? $this->transfersList() : null,
            'types' => self::MOVEMENT_TYPES,
            // ⚠️ كانت المنسدلة تقرأ علاقة stocks مباشرةً — أي بترتيب إدراج
            //    الصفوف في القاعدة لا بترتيبٍ معلن. أصنافُ هذا المخزن بترتيب الدفتر:
            'movementItems' => Item::query()
                ->whereIn('items.id', WarehouseStock::query()
                    ->where('warehouse_id', $this->warehouse->id)
                    ->select('item_id'))
                ->inStatementOrder()
                ->get(),
            // أقسام ووحدات أصناف هذا المخزن وحدها — قائمةٌ بخيارات لا صفوف خلفها
            // في مخزنٍ بعينه تُوهم المستخدم أن الشاشة فارغة لخللٍ لا لغياب الصنف
            'categories' => \App\Models\ItemCategory::whereIn(
                'id',
                WarehouseStock::query()
                    ->where('warehouse_stocks.warehouse_id', $this->warehouse->id)
                    ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
                    ->whereNotNull('items.item_category_id')
                    ->select('items.item_category_id')
            )->orderBy('order')->orderBy('name')->get(),
            'units' => \App\Models\ItemUnit::whereIn(
                'id',
                WarehouseStock::query()
                    ->where('warehouse_stocks.warehouse_id', $this->warehouse->id)
                    ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
                    ->whereNotNull('items.item_unit_id')
                    ->select('items.item_unit_id')
            )->orderBy('name')->get(),
        ]);
    }
}
