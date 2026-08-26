<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * ترتيب الجداول بعمودٍ يختاره المستخدم.
 *
 * ⚠️ مفتاح العمود يأتي من الـ URL، فلا يُمرَّر لـ orderBy إلا بعد التحقق من
 *    خريطة الأعمدة المسموحة — وإلا صار حقن SQL عبر الرابط. الخريطة
 *    (مفتاح ← عمود/أعمدة SQL) يكتبها المطوّر، فالقيمة وحدها موثوقة.
 *
 * ⚠️ والمفتاح غير المعروف يسقط إلى **الترتيب الافتراضي للشاشة** لا إلى
 *    عمودٍ عشوائي: ترتيب المخازن (المستوى ثم المحافظة) وترتيب الأصناف
 *    (القسم ثم ترتيب الصنف) ترتيبان ذوا معنى تنظيمي، والأبجدي يبعثرهما.
 */
final class TableSort
{
    /**
     * @param  array<string, string|array<int, string>>  $map  مفتاح الرابط ← عمود SQL أو عدة أعمدة
     * @param  Closure(Builder): Builder|null  $default  الترتيب حين لا يختار المستخدم عموداً صالحاً
     * @param  string|null  $tieBreaker  عمود مُرجِّح يثبّت ترتيب الصفوف المتساوية بين الصفحات
     */
    public static function apply(
        Builder $query,
        string $key,
        string $dir,
        array $map,
        ?Closure $default = null,
        ?string $tieBreaker = null,
        string $tieBreakerDir = 'desc',
    ): Builder {
        if (! array_key_exists($key, $map)) {
            return $default ? $default($query) : $query;
        }

        $dir     = $dir === 'desc' ? 'desc' : 'asc';
        $columns = (array) $map[$key];

        foreach ($columns as $column) {
            $query->orderBy($column, $dir);
        }

        // بلا مُرجِّح يتبدّل ترتيب الصفوف المتساوية بين الصفحتين، فيتكرّر صف ويسقط آخر
        if ($tieBreaker !== null && ! in_array($tieBreaker, $columns, true)) {
            $query->orderBy($tieBreaker, $tieBreakerDir);
        }

        return $query;
    }
}
