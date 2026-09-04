<?php

namespace App\Livewire\Warehouses\Concerns;

use App\Models\Item;
use Illuminate\Support\Collection;

/**
 * القسم **داخل صفّ المستند** لا فوق الصفوف (وارد · نقل · صرف).
 *
 * كان حصراً واحداً فوق الجدول يضيّق منسدلات الصفوف كلها. وكان له عيبان
 * كشفتهما المستخدمة على الشاشة:
 *
 *  ١) **حالةٌ مشتركة تتغيّر تحت صفوفٍ ممتلئة.** مَن ملأ صفاً من قسم ثم بدّل
 *     الحصر ليأتي بصنفٍ من قسمٍ آخر، تتبدّل خيارات منسدلات الصفوف كلها —
 *     فيفقد `<select>` قيمته المعروضة ويبدو الصفّ فارغاً وهو ممتلئ. والأخطر
 *     أنّ الحفظ يمضي بالصنف الذي لا يراه صاحبه.
 *  ٢) وحتى مع بقاء المختار في القائمة، بقاؤه **استثناءٌ يشرح نفسه بصعوبة**:
 *     قسمٌ معروض ومعه صنفٌ من غيره.
 *
 * فصار لكل صفٍّ قسمُه: **قسم ← صنف ← عدد**، مستقلاً بذاته. ولا حالة مشتركة
 * أصلاً، فلا شيء يتغيّر تحت صفٍّ ممتلئ.
 *
 * ⚠️ ويشترط في المكوّن المستعمِل خاصيةُ `$lines`، وأن يكون في كل صفٍّ مفتاح
 *    `category_id` (فارغٌ = كل الأقسام).
 */
trait FiltersItemsByCategory
{
    /** أصناف كل قسمٍ داخل الطلب الواحد — الصفوف تتشارك الأقسام كثيراً. */
    protected array $itemsByCategory = [];

    /** صفٌّ جديد فارغ — المصدر الواحد لبنية الصفّ. */
    protected function emptyLine(): array
    {
        return ['category_id' => '', 'item_id' => null, 'quantity' => null];
    }

    /**
     * تغيّر قسمُ صفٍّ ⇐ يُفرَّغ صنفُه.
     *
     * ⚠️ الصنف لا ينتمي لقسمين، فإبقاؤه يُخرج صفاً صنفُه خارج قسمه المعروض.
     *    والإفراغ **محصور في صفّه** — لا يمسّ صفاً آخر (وهي علّة التصميم السابق).
     */
    public function updatedLines($value, $key): void
    {
        if (preg_match('/^(\d+)\.category_id$/', (string) $key, $matches)) {
            $this->lines[(int) $matches[1]]['item_id'] = null;
        }
    }

    /** معرّفات الأصناف المختارة في الصفوف. */
    protected function chosenItemIds(): array
    {
        return collect($this->lines)
            ->pluck('item_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /** أصناف قسمٍ بعينه — أو كلها إن لم يُختر قسم. */
    protected function itemsForCategory(string $categoryId): Collection
    {
        if (array_key_exists($categoryId, $this->itemsByCategory)) {
            return $this->itemsByCategory[$categoryId];
        }

        return $this->itemsByCategory[$categoryId] = Item::query()
            ->where('items.is_active', true)
            ->when($categoryId === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            // ⚠️ قيمة تالفة تصل من العميل: تُهمَل ولا تُفرّغ المنسدلة
            ->when(ctype_digit($categoryId), fn ($q) => $q->where('items.item_category_id', (int) $categoryId))
            // ⚠️ `with(category)` لأجل التجميع في المنتقي — بلا تحميلها
            //    مسبقاً يقرأ القالب القسمَ لكل صنف على حدة
            ->with('category')
            ->inStatementOrder()
            ->get();
    }

    /**
     * أصناف كل صفٍّ بترتيب الصفوف — يقرأها القالب.
     *
     * @return array<int, Collection>
     */
    protected function lineItems(): array
    {
        return collect($this->lines)
            ->map(fn ($line) => $this->itemsForCategory((string) ($line['category_id'] ?? '')))
            ->all();
    }
}
