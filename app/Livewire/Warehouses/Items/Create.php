<?php

namespace App\Livewire\Warehouses\Items;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Support\ArabicDigits;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('صنف')]
class Create extends Component
{
    public ?Item $item = null;

    public string $name = '';
    public ?int $item_category_id = null;

    /**
     * «هل للصنف رقم؟» — واجهة بحتة بلا عمود في القاعدة.
     * التشييك يجعل الرقم إلزامياً، فالخانة مشتقّة من `code` وحدها
     * («متشيَّكة وفارغة» حالة لا تُحفظ أصلاً، فلا معلومة يحملها عمود ثانٍ).
     */
    public bool $has_code = false;
    public string $code = '';

    public ?int $item_unit_id = null;
    public ?int $min_stock = null;
    public ?int $order = null;
    public bool $is_active = true;

    /** نصّ تحذير تكرار الرقم، ويُعرض قبل الحفظ لا بدلاً منه. */
    public string $duplicateWarning = '';

    /**
     * بصمة «قسم|رقم» التي أكّدها المستخدم بالفعل.
     * تخزين البصمة لا مجرد راية: أي تعديل على الرقم أو القسم يُبطل
     * التأكيد من تلقائه بلا hooks ولا ترتيب استدعاءات هشّ.
     */
    public string $confirmedFor = '';

    public function mount(?Item $item = null): void
    {
        abort_unless(auth()->user()?->can('warehouses.settings'), 403);

        if ($item?->exists) {
            $this->item             = $item;
            $this->name             = $item->name;
            $this->item_category_id = $item->item_category_id;
            $this->code             = (string) $item->code;
            $this->has_code         = $this->code !== '';
            $this->item_unit_id     = $item->item_unit_id;
            $this->min_stock        = $item->min_stock;
            $this->order            = $item->order;
            $this->is_active        = (bool) $item->is_active;
        } else {
            // القيمة الافتراضية: «قطعة» (أو أول وحدة متاحة) لتقليل الاحتكاك
            $this->item_unit_id = ItemUnit::where('name', 'قطعة')->value('id')
                ?? ItemUnit::orderBy('id')->value('id');
        }
    }

    /** رفع علامة «له رقم» يمسح الرقم ومعه تحذيره. */
    public function updatedHasCode(): void
    {
        if (! $this->has_code) {
            $this->code = '';
            $this->duplicateWarning = '';
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // إلزامي في الفورم و nullable في القاعدة: الأصناف المسجَّلة قبل الأقسام
            // تُصنَّف تدريجياً عبر فلتر «بلا قسم»، ولا يدخل صنف جديد بلا قسم.
            'item_category_id' => ['required', 'exists:item_categories,id'],
            'code'             => ['exclude_if:has_code,false', 'required', 'string', 'max:50'],
            'item_unit_id'     => ['required', 'exists:item_units,id'],
            'min_stock'        => ['nullable', 'integer', 'min:0'],
            'order'            => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['boolean'],
        ];
    }

    /**
     * ⚠️ اسم `code` العربي يُضبط هنا لا في `lang/ar/validation.php`:
     *    المفتاح مأخوذ هناك لرمز طرف المراسلات («الرمز»)، وإضافته ثانيةً
     *    تُبدّل اسم ذاك الحقل في رسائله بلا أن يظهر خطأ.
     */
    protected function validationAttributes(): array
    {
        return ['code' => __('home.item_code')];
    }

    /** الصنف الآخر الحامل للرقم نفسه في القسم نفسه، إن وُجد. */
    protected function codeTwin(string $code): ?Item
    {
        return Item::query()
            ->where('item_category_id', $this->item_category_id)
            ->where('code', $code)
            ->when($this->item?->exists, fn ($q) => $q->whereKeyNot($this->item->getKey()))
            ->first();
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['order'] = $data['order'] ?? 0;

        // الرقم يُخزَّن بأرقام هندية دائماً — بلا صورة واحدة يصير «٤٠ ق» و«40 ق»
        // قيمتين مختلفتين فلا يكشف فحص التكرار أنهما الرقم نفسه.
        $data['code'] = $this->has_code
            ? ArabicDigits::toArabic(trim($this->code))
            : null;

        if ($data['code'] !== null) {
            $this->code = $data['code'];

            // ⚠️ تحذير لا منع: هل يمتنع صنفان من نفس الرقم سؤالٌ لم يُحسم بعد،
            //    ورفضُ رقمٍ موجود فعلاً في الدفتر يدفع الموظف لكتابة رقم يخالف
            //    الورق — وهو بعينه ما وُجد النظام ليمنعه.
            $fingerprint = $this->item_category_id.'|'.$data['code'];

            if ($this->confirmedFor !== $fingerprint && $twin = $this->codeTwin($data['code'])) {
                $this->confirmedFor     = $fingerprint;
                $this->duplicateWarning = __('home.item_code_duplicate_warning', [
                    'code' => $data['code'],
                    'name' => $twin->name,
                ]);

                return;
            }
        }

        $this->duplicateWarning = '';

        if ($this->item?->exists) {
            $this->item->update($data);
            Flux::toast(variant: 'success', text: __('home.item_updated'));
        } else {
            Item::create($data);
            Flux::toast(variant: 'success', text: __('home.item_created'));
        }

        $this->redirect(route('items.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.items.create', [
            'units' => ItemUnit::orderBy('name')->get(),
            // القسم المتوقّف لا يُعرض لصنف جديد، لكنه يبقى معروضاً على الصنف المرتبط
            // به فعلاً — وإلا أفرغ الـselect حقلَه صامتاً عند أول حفظ لسبب آخر.
            'categories' => ItemCategory::query()
                ->where(fn ($q) => $q->where('is_active', true)
                    ->orWhere('id', $this->item_category_id))
                ->orderBy('order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
