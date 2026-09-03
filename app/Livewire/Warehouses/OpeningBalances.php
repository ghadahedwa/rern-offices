<?php

namespace App\Livewire\Warehouses;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\WarehouseLedger;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * الأرصدة الافتتاحية — **على صورة بيان القسم الورقي، لا صفوفاً بمنسدلات**.
 *
 * ⚠️ كانت الشاشة صفوفاً، في كل صفٍّ منسدلةٌ بـ٣٧٧ صنفاً. والمفتش يُدخل
 *    **محتوى مخزنه كله** عند التشغيل — عشرات الأصناف أو مئاتها — فكان عليه
 *    أن يفتش في القائمة نفسها مرة بعد مرة. لن يفعلها أحد.
 *
 * والدفتر الذي يقرأ منه ليس قائمة أصناف بل **بيان بأرصدة {القسم}**: صفحةٌ
 * فيها كل أصناف القسم وأمام كل صنف خانة العدد. فهذه الشاشة صورته: يختار
 * القسم مرة، فتظهر أصنافه كلها صفوفاً جاهزة، ويملأ ما عنده. ولا منسدلة صنف.
 *
 * ⚠️ **الخانة الفارغة تُترك ولا تُسجَّل، والصفر المكتوب يُسجَّل.** الافتتاحي
 *    **يكتب الرصيد كتابةً**، فلو عُدّ الفارغ صفراً لَمحا حفظُ قسمٍ أرصدةَ كل
 *    صنفٍ لم يصل إليه المُدخِل بعد. والصفر المكتوب إقرارٌ بالعدّ فيُسجَّل حركةً.
 */
#[Layout('layouts.app')]
#[Title('الأرصدة الافتتاحية')]
class OpeningBalances extends Component
{
    public ?int $warehouse_id = null;

    /** معرّف القسم، أو 'none' لأصناف بلا قسم. */
    public string $category_id = '';

    /**
     * الكميات المُدخَلة: معرّف الصنف => القيمة (نصّاً كما تصل من الحقل).
     * الفارغ يعني «لم يُدخَل» لا «صفر».
     *
     * @var array<int|string, mixed>
     */
    public array $quantities = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.opening'), 403);
    }

    /**
     * تبديل المخزن أو القسم يمسح المُدخَل.
     *
     * ⚠️ وإلا حُملت أرقامُ قسمٍ إلى قسمٍ آخر بمعرّفات أصنافٍ لم تعد معروضة،
     *    فتُسجَّل أرصدة لأصناف لا يراها المُدخِل أمامه.
     */
    public function updatedWarehouseId(): void
    {
        $this->quantities = [];
    }

    public function updatedCategoryId(): void
    {
        $this->quantities = [];
    }

    /** أصناف القسم المختار بترتيب الدفتر — أو لا شيء قبل اكتمال الاختيار. */
    public function categoryItems()
    {
        if (! $this->warehouse_id || $this->category_id === '') {
            return collect();
        }

        return Item::query()
            ->where('items.is_active', true)
            ->when($this->category_id === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->category_id), fn ($q) => $q->where('items.item_category_id', (int) $this->category_id))
            ->with('unit')
            ->inStatementOrder()
            ->get();
    }

    protected function rules(): array
    {
        return [
            'warehouse_id'  => ['required', 'exists:warehouses,id'],
            'category_id'   => ['required', 'string'],
            'quantities'    => ['array'],
            'quantities.*'  => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $warehouse = Warehouse::findOrFail($this->warehouse_id);

        // ⚠️ المعرّف يصل من العميل، والافتتاحي **يكتب الرصيد كتابةً** — فبلا
        //    هذا الحارس يدسّ صاحبُ مخزنٍ معرّفَ الرئيسي فيمحو أرصدته.
        //    والمنسدلة المفلترة لا تكفي: الطلب يُبنى بغيرها.
        abort_unless(WarehouseScope::allows($warehouse->id), 403);

        $items = $this->categoryItems()->keyBy('id');

        // ⚠️ لا يُسجَّل إلا لصنفٍ **معروضٍ في القسم المختار**: مفتاحٌ يُدسّ في
        //    المصفوفة من العميل لصنفٍ خارج الشاشة لا يُمسّ رصيدُه.
        $entered = collect($this->quantities)
            ->filter(fn ($value, $itemId) => $value !== null && $value !== '' && $items->has((int) $itemId));

        if ($entered->isEmpty()) {
            $this->addError('quantities', __('home.wh_opening_nothing_entered'));

            return;
        }

        $user = Auth::user();

        foreach ($entered as $itemId => $quantity) {
            WarehouseLedger::recordOpening($warehouse, $items[(int) $itemId], (int) $quantity, $user);
        }

        Flux::toast(variant: 'success', text: __('home.wh_opening_saved_count', ['count' => $entered->count()]));

        // ⚠️ يبقى على الشاشة ولا يُعاد توجيهه: الإدخال قسمٌ بعد قسم، فإخراجُه
        //    إلى اللوحة بعد كل قسم يُطيل عملاً هو أصلاً طويل.
        $this->quantities = [];
    }

    public function render()
    {
        $items = $this->categoryItems();

        // الرصيد المسجَّل حالياً بجوار كل صنف — فيرى المُدخِل ما سيكتب فوقه
        $current = $this->warehouse_id
            ? WarehouseStock::where('warehouse_id', $this->warehouse_id)
                ->whereIn('item_id', $items->pluck('id'))
                ->pluck('quantity', 'item_id')
            : collect();

        return view('livewire.warehouses.opening-balances', [
            // المنسدلة مفلترة كالحارس — خيارٌ خارج النطاق يقود إلى ٤٠٣
            'warehouses' => WarehouseScope::apply(
                Warehouse::with('type')->where('warehouses.is_active', true)->ordered()
            )->get(),
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
            'items'      => $items,
            'current'    => $current,
        ]);
    }
}
