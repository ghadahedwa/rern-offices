<?php

namespace App\Livewire\Warehouses\Items;

use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use App\Support\ArabicText;
use App\Support\WarehouseScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * صفحة الصنف — تجيب سؤالاً لا تجيبه شاشةٌ أخرى: **أين هذا الصنف؟**
 *
 * شاشة الأرصدة تعرض (مخزن × صنف) فتُري صفّاً واحداً في كل مرة، وسجل الحركات
 * يعرض الأحداث لا الأرصدة. فهنا الصنفُ واحدٌ والمخازنُ كلها أمامه.
 *
 * ⚠️ الصلاحية `warehouses.index` **أو** `warehouses.settings`: الشاشة قراءةٌ
 *    تشغيلية (كالأرصدة والحركات) ومدخلها من شاشتين في فرعين — الأرصدة في فرع
 *    المخازن (`index`) والأصناف في إدارة النظام (`settings`). واشتراطُ واحدة
 *    منهما وحدها يكسر أحد المدخلين على مَن يملك الأخرى.
 */
#[Layout('layouts.app')]
#[Title('الصنف')]
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

    public Item $item;

    public bool $canManage = false;

    #[Url(as: 'tab', except: 'balances')]
    public string $tab = 'balances';

    /**
     * فلاتر موحّدة بين التابين — لا فلتر لكل تاب (نفس قاعدة بروفايل المخزن):
     * تابٌ واحد معروض في كل لحظة، و`setTab()` يمسح الفلاتر لأن سطر البحث
     * في التابين معناه واحد (اسم مخزن) لكن الفلاتر الأخرى تختلف.
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** معرّف مخزن — تاب الحركات وحده (تاب الأرصدة يعرض المخازن كلها). */
    #[Url(as: 'wh', except: '')]
    public string $warehouseFilter = '';

    /** معرّف نوع مخزن، أو '' للكل — تاب الأرصدة. */
    #[Url(as: 'wtype', except: '')]
    public string $warehouseTypeFilter = '';

    /** 'positive' أكبر من صفر · 'zero' صفر (ومنه غير المسجَّل) · '' الكل. */
    #[Url(as: 'balance', except: '')]
    public string $balanceFilter = '';

    /** نوع الحركة — تاب الحركات. */
    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    public function mount(Item $item): void
    {
        $user = Auth::user();

        abort_unless($user?->can('warehouses.index') || $user?->can('warehouses.settings'), 403);

        $this->item      = $item->load('category', 'unit');
        $this->canManage = (bool) $user?->can('warehouses.settings');

        // التاب يصل من الرابط أيضاً — يُفحص هنا لا في setTab وحدها
        if (! in_array($this->tab, ['balances', 'movements'], true)) {
            $this->tab = 'balances';
        }
    }

    /**
     * وجهة زر الرجوع — الشاشة التي يملكها القارئ فعلاً.
     *
     * ⚠️ للصفحة مدخلان في فرعين: الأرصدة خلف `warehouses.index` والأصناف خلف
     *    `warehouses.settings`. ووجهةٌ ثابتة كانت تهبط بأحد الفريقين على ٤٠٣.
     *    والقرار هنا لا في القالب حتى يقبل اختباراً لا يخلطه بروابط السايدبار.
     */
    public function backRoute(): string
    {
        return Auth::user()?->can('warehouses.index')
            ? route('warehouses.stock')
            : route('items.index');
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['balances', 'movements'], true) ? $tab : 'balances';

        // الفلاتر والترتيب يخصّان جدول التاب السابق — أعمدة التاب الجديد غيرها
        $this->resetFilters();
        $this->resetSort();
    }

    /** مُرقِّم التاب المعروض — لكل تاب مُرقِّمه حتى لا يتداخل ترقيم جدولين. */
    protected function tabPageName(): string
    {
        return $this->tab === 'movements' ? 'movPage' : 'balPage';
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

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBalanceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'warehouseFilter', 'warehouseTypeFilter', 'balanceFilter', 'typeFilter', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    /** الفلاتر المعروضة تختلف بالتاب، فكذلك حساب «هل من فلتر مفعّل؟». */
    public function hasActiveFilters(): bool
    {
        return $this->tab === 'movements'
            ? $this->search !== '' || $this->warehouseFilter !== '' || $this->typeFilter !== '' || $this->hasDateFilter()
            : $this->search !== '' || $this->warehouseTypeFilter !== '' || $this->balanceFilter !== '';
    }

    protected function sortableColumns(): array
    {
        return $this->tab === 'movements'
            ? [
                'warehouse'     => 'warehouses.name',
                'type'          => 'warehouse_movements.type',
                'quantity'      => 'warehouse_movements.quantity',
                'balance_after' => 'warehouse_movements.balance_after',
                'date'          => 'warehouse_movements.created_at',
            ]
            : [
                'warehouse'    => 'warehouses.name',
                'type'         => 'warehouse_types.name',
                'governorate'  => ['governorates.order', 'governorates.name'],
                'quantity'     => 'stock_quantity',
            ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $this->tab === 'movements'
            ? $query
                ->orderByDesc('warehouse_movements.created_at')
                ->orderByDesc('warehouse_movements.id')
            // المخازن بقاعدة العرض الموحّدة: المستوى ثم المحافظة ثم الاسم
            : Warehouse::displayOrder($query);
    }

    /**
     * رصيد الصنف في **كل** مخزن — لا في المخازن التي له فيها صفُّ رصيد وحدها.
     *
     * ⚠️ شرط الصنف **داخل الانضمام** لا في `where`: نقلُه إلى `where` يقلب
     *    الانضمام الخارجي داخلياً فتسقط كل المخازن التي لا رصيد للصنف فيها —
     *    وهي بالضبط ما تسأل عنه الشاشة («أين ليس هذا الصنف؟» نصفُ الجواب).
     *    نفس مِزلقة `CategoryStatement` مقلوبةً: هناك الصنف بلا رصيد، وهنا المخزن.
     */
    protected function balancesList()
    {
        return WarehouseScope::apply(Warehouse::query())
            ->leftJoin('warehouse_stocks', function ($join) {
                $join->on('warehouse_stocks.warehouse_id', '=', 'warehouses.id')
                    ->where('warehouse_stocks.item_id', '=', $this->item->id);
            })
            // `left` لا `inner`: المخزن الرئيسي بلا محافظة، والداخلي يُسقطه
            ->leftJoin('warehouse_types', 'warehouses.warehouse_type_id', '=', 'warehouse_types.id')
            ->leftJoin('governorates', 'warehouses.governorate_id', '=', 'governorates.id')
            ->select('warehouses.*')
            // المخزن بلا صفِّ رصيد رصيدُه صفر لا NULL — فالفرز والفلترة تعملان عليه.
            // ⚠️ والاسم `stock_quantity` لا `quantity`: الاسم المجرّد يلتبس في
            //    ORDER BY بين اسم مستعار وعمود `warehouse_stocks.quantity` المنضمّ.
            ->selectRaw('COALESCE(warehouse_stocks.quantity, 0) as stock_quantity')
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            // قيمة غير رقمية تصل من الرابط تُهمَل — وإلا خرجت شاشة فارغة بلا سبب ظاهر
            ->when(ctype_digit($this->warehouseTypeFilter), fn ($q) => $q->where('warehouses.warehouse_type_id', (int) $this->warehouseTypeFilter))
            // «صفر» تشمل ما دونه وتشمل المخزن بلا صفِّ رصيد أصلاً
            ->when($this->balanceFilter === 'zero', fn ($q) => $q->whereRaw('COALESCE(warehouse_stocks.quantity, 0) <= 0'))
            ->when($this->balanceFilter === 'positive', fn ($q) => $q->whereRaw('COALESCE(warehouse_stocks.quantity, 0) > 0'))
            ->with('type', 'governorate')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouses.id'))
            ->paginate($this->perPage(), ['*'], 'balPage');
    }

    protected function movementsList()
    {
        return WarehouseMovement::query()
            ->where('warehouse_movements.item_id', $this->item->id)
            ->join('warehouses', 'warehouse_movements.warehouse_id', '=', 'warehouses.id')
            ->select('warehouse_movements.*')
            ->tap(fn ($q) => WarehouseScope::apply($q, 'warehouse_movements.warehouse_id'))
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            // القيم تصل من الرابط — غير الرقمية وغير المعروفة تُهمَل
            ->when(ctype_digit($this->warehouseFilter), fn ($q) => $q->where('warehouse_movements.warehouse_id', (int) $this->warehouseFilter))
            ->when(in_array($this->typeFilter, self::MOVEMENT_TYPES, true), fn ($q) => $q->where('warehouse_movements.type', $this->typeFilter))
            // ⚠️ created_at لحظة مخزَّنة بـUTC والفلتر يوم بتوقيت القاهرة
            ->tap(fn ($q) => $this->applyTimestampRange($q, 'warehouse_movements.created_at'))
            ->with(['warehouse.type', 'user'])
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_movements.id'))
            ->paginate($this->perPage(), ['*'], 'movPage');
    }

    /**
     * أرقام الرأس — محسوبة على **كل** المخازن لا على الصفحة المعروضة ولا على
     * ما بقي بعد الفلترة: بطاقةٌ تتغيّر بفلتر الجدول تحتها تكذب على القارئ.
     */
    protected function summary(): array
    {
        $stocks = WarehouseScope::apply(
            WarehouseStock::query()->where('item_id', $this->item->id),
            'warehouse_stocks.warehouse_id'
        )
            ->selectRaw('COALESCE(SUM(quantity), 0) as total')
            ->selectRaw('SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as with_stock')
            ->first();

        // ⚠️ رصيد الرئيسي يُقرأ من مخزنٍ رئيسي بعينه — والحدّ الأدنى يُقاس عليه
        //    وحده (نفس قاعدة الداشبورد والبروفايل وشاشة الأرصدة).
        $main = WarehouseScope::apply(
            Warehouse::query()->whereHas('type', fn ($q) => $q->where('level', 1))->ordered()
        )->first();

        $mainQuantity = $main
            ? (int) WarehouseStock::query()
                ->where('warehouse_id', $main->id)
                ->where('item_id', $this->item->id)
                ->value('quantity')
            : null;

        return [
            'total'          => (int) ($stocks->total ?? 0),
            'withStock'      => (int) ($stocks->with_stock ?? 0),
            'warehousesAll'  => WarehouseScope::apply(Warehouse::query())->count(),
            'mainWarehouse'  => $main,
            'mainQuantity'   => $mainQuantity,
            // ⚠️ فرقٌ بين «لا مخزن رئيسي في المنظومة» و«الرئيسي خارج نطاقي»:
            //    الأولى تُبلَّغ، والثانية تُخفى — وإلا أخبرنا المفتش بغياب مخزنٍ قائم
            'showMain'       => $main !== null || WarehouseScope::unlimited(),
            'mainBelowMin'   => $main !== null
                && $this->item->min_stock !== null
                && (int) $mainQuantity <= $this->item->min_stock,
        ];
    }

    public function render()
    {
        return view('livewire.warehouses.items.show', [
            'balances'  => $this->tab === 'balances' ? $this->balancesList() : null,
            'movements' => $this->tab === 'movements' ? $this->movementsList() : null,
            'summary'   => $this->summary(),
            'types'     => self::MOVEMENT_TYPES,
            'warehouseTypes' => WarehouseType::orderBy('level')->orderBy('name')->get(),
            // منسدلة تاب الحركات: مخازن تحرّك فيها هذا الصنف فعلاً — خيارٌ بلا
            // صفوف خلفه يُوهم المستخدم أن الشاشة معطّلة لا أن الحركة غائبة
            'movementWarehouses' => WarehouseScope::apply(Warehouse::query())
                ->whereIn('warehouses.id', WarehouseMovement::query()
                    ->where('item_id', $this->item->id)
                    ->select('warehouse_id'))
                ->ordered()
                ->get(),
        ]);
    }
}
