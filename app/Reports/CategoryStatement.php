<?php

namespace App\Reports;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Support\ArabicDigits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * «بيان بأرصدة {القسم}» — مصدر واحد للشاشة وللتقرير المطبوع.
 *
 * ⚠️ الشاشة تعرض والكنترولر يطبع، ولو بنى كلٌّ منهما استعلامه لخرج الورق
 *    بأرقام تخالف ما رآه الموظف على الشاشة — وهو أسوأ من غياب التقرير.
 */
class CategoryStatement
{
    /** الشرطة التي يكتبها الدفتر مكان الصفر. */
    public const ZERO_MARK = '----';

    /**
     * ⚠️ **لا سقف صفوف هنا ولا في أي مكان.** كان هنا ثابتان مقيسان
     *    (`ROWS_PER_COLUMN` · `ROWS_SINGLE_COLUMN`) فحُذفا: سقفٌ واحد يُقاس
     *    على أطول الأقسام أسماءً يظلم أقصرها، فيُخرج «مخزن المستديم» في
     *    ورقتين والدفتر الورقي يطبعه في ورقة. التخطيط الآن **مُجرَّب** في
     *    App\Reports\StatementLayout — وفيه لماذا سقط التقدير بالصفوف
     *    وبالأحرف وبـGetStringWidth جميعاً.
     */

    /**
     * @return array{warehouse: Warehouse, category: ItemCategory, rows: Collection, hasCodes: bool, total: int}
     */
    public static function build(Warehouse $warehouse, ItemCategory $category): array
    {
        $rows = Item::query()
            // الأقسام لأجل ترتيب الدفتر (statementOrder يشترط ضمّها)
            ->leftJoin('item_categories', 'items.item_category_id', '=', 'item_categories.id')
            // ⚠️ `left` لا `inner`، والشرط على المخزن **داخل** الانضمام لا في where:
            //    البيان يشمل كل أصناف القسم كما يطبعها الدفتر، ومَن لا رصيد له
            //    يُطبع صفراً (شرطةً). نقلُ الشرط إلى where يُسقط تلك الصفوف كلها.
            ->leftJoin('warehouse_stocks', fn ($join) => $join
                ->on('warehouse_stocks.item_id', '=', 'items.id')
                ->where('warehouse_stocks.warehouse_id', '=', $warehouse->id))
            ->where('items.item_category_id', $category->id)
            // ⚠️ يُؤهَّل بـ`items.` — العمود على جدول الأقسام أيضاً فيصير ملتبساً بعد الضمّ
            ->where('items.is_active', true)
            ->select([
                'items.id',
                'items.name',
                'items.code',
                DB::raw('COALESCE(warehouse_stocks.quantity, 0) as quantity'),
            ])
            ->tap(fn ($q) => Item::statementOrder($q))
            ->get();

        return [
            'warehouse' => $warehouse,
            'category'  => $category,
            'rows'      => $rows,
            // عمود «رقم الصنف» يظهر حيث للأصناف أرقام (الدفتر العقاري · فهرس
            // التوثيق) ويغيب حيث لا أرقام — القاعدة من البيانات لا من اسم القسم
            'hasCodes'  => $rows->contains(fn ($row) => filled($row->code)),
            'total'     => (int) $rows->sum('quantity'),
        ];
    }

    /** صورة العدد في الورق: أرقام هندية، والصفر شرطة كما يكتبه الدفتر. */
    public static function amount(int $quantity): string
    {
        return $quantity === 0
            ? self::ZERO_MARK
            : ArabicDigits::toArabic((string) $quantity);
    }
}
