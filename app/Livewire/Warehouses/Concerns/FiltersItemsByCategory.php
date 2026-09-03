<?php

namespace App\Livewire\Warehouses\Concerns;

use App\Models\Item;

/**
 * حصر منسدلات الصنف بقسمٍ واحد في فورمات المستندات (وارد · نقل · صرف).
 *
 * المستند هنا صغير — صنفٌ أو ثلاثة — فالمنسدلة صحيحة، لكن ٣٧٧ صنفاً فيها
 * كثير. والتجميع بالقسم (`partials/item-options`) رأسٌ للقائمة، وهذا الفلتر
 * يقصرها على قسمٍ واحد فيصير الاختيار من عشرين لا من ثلاثمائة.
 *
 * ⚠️ **والصنف المختار في صفٍّ يبقى معروضاً مهما ضاق الفلتر.** المنتقي يُبقي
 *    الصنف في صفّه هو، لكنه لا يستطيع ذلك إن أُخرج من القائمة أصلاً — فيختفي
 *    من منسدلته ويبدو الصف فارغاً وقد كان ممتلئاً. ولذلك يُضمّ المختارون إلى
 *    نتيجة الفلتر دائماً.
 *
 * ⚠️ ويشترط في المكوّن المستعمِل خاصيةُ `$lines` (صفوف المستند).
 */
trait FiltersItemsByCategory
{
    /** معرّف قسم، أو 'none' لأصناف بلا قسم، أو '' للكل. */
    public string $itemCategoryId = '';

    /** معرّفات الأصناف المختارة في الصفوف. */
    protected function chosenItemIds(): array
    {
        return collect($this->lines)
            ->pluck('item_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /** أصناف المنسدلة: قسمُ الفلتر + ما اختاره المستخدم فعلاً. */
    protected function categoryFilteredItems()
    {
        $filter = $this->itemCategoryId;
        $chosen = $this->chosenItemIds();

        return Item::query()
            ->where('items.is_active', true)
            ->when($filter !== '', fn ($q) => $q->where(function ($group) use ($filter, $chosen) {
                if ($filter === 'none') {
                    $group->whereNull('items.item_category_id');
                } elseif (ctype_digit($filter)) {
                    $group->where('items.item_category_id', (int) $filter);
                } else {
                    // ⚠️ قيمة تالفة تصل من العميل: تُهمَل ولا تُفرّغ المنسدلة
                    $group->whereRaw('1 = 1');
                }

                if ($chosen !== []) {
                    $group->orWhereIn('items.id', $chosen);
                }
            }))
            // ⚠️ `with(category)` لأجل التجميع في المنتقي — بلا تحميلها
            //    مسبقاً يقرأ القالب القسمَ لكل صنف على حدة
            ->with('category')
            ->inStatementOrder()
            ->get();
    }
}
