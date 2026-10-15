<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ترتيب طبيعي للأسماء المنتهية برقم: «سيارة … 2» قبل «سيارة … 10».
 *
 * الترتيب النصي يقارن الرقم حرفاً حرفاً، فيضع 10 · 11 … 19 قبل 2، و9 آخر القائمة —
 * وأسماء السيارات كلها «سيارة التوثيق المتنقل N» (بلاغ السيرفر 2026-10-15).
 *
 * المعنى: **النص بلا الرقم الأخير** ← **الرقم الأخير كعدد** ← الاسم كاملاً (للتعادل).
 * اسمٌ بلا رقم في آخره رقمه NULL، فيأتي قبل أخواته المرقَّمة في التصاعدي.
 *
 * ⚠️ التعبير يتفرّع بالمحرّك (كـ`DashboardReport::monthExpression`): MySQL بـREGEXP (8.0+)،
 *    وsqlite (الاختبارات) بـ`rtrim` بقائمة الأرقام — المعنى نفسه: الرقم **الأخير** لا أول رقم.
 */
final class NaturalSort
{
    /**
     * أعمدة الترتيب بالتتابع — تصلح قيمةً في خريطة `TableSort` (الاتجاه يُطبَّق على الثلاثة).
     *
     * @return array<int, Expression|string>
     */
    public static function columns(string $column): array
    {
        $c = DB::getQueryGrammar()->wrap($column);

        if (DB::connection()->getDriverName() === 'sqlite') {
            $prefix = "rtrim({$c}, '0123456789')";

            return [
                DB::raw($prefix),
                DB::raw("CAST(NULLIF(substr({$c}, length({$prefix}) + 1), '') AS INTEGER)"),
                $column,
            ];
        }

        return [
            DB::raw("REGEXP_REPLACE({$c}, '[0-9]+$', '')"),
            DB::raw("CAST(REGEXP_SUBSTR({$c}, '[0-9]+$') AS UNSIGNED)"),
            $column,
        ];
    }

    public static function orderBy(Builder $query, string $column, string $dir = 'asc'): Builder
    {
        foreach (self::columns($column) as $col) {
            $query->orderBy($col, $dir);
        }

        return $query;
    }
}
