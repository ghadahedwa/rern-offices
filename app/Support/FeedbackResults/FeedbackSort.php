<?php

namespace App\Support\FeedbackResults;

use App\Support\TableSort;
use Illuminate\Database\Eloquent\Builder;

/**
 * ترتيب جداول النتائج.
 *
 * ⚠️ اسم العمود يأتي من الـ URL (الشاشة والتصدير على السواء)، فلا يُمرَّر
 * لـ orderBy إلا بعد التحقق من القائمة البيضاء — وإلا صار حقن SQL عبر الرابط.
 *
 * التنفيذ مشترك مع بقية جداول النظام في App\Support\TableSort؛ ويبقى هنا
 * ما يخصّ هذا الموديول: العمود غير المعروف يسقط إلى created_at (لا إلى
 * ترتيبٍ افتراضي آخر)، والاتجاه الافتراضي تنازلي لأن الأحدث هو المطلوب.
 */
final class FeedbackSort
{
    public static function apply(Builder $query, string $column, string $dir, array $allowed): Builder
    {
        $column = in_array($column, $allowed, true) ? $column : 'created_at';
        $dir    = $dir === 'asc' ? 'asc' : 'desc';

        $map = array_combine($allowed, $allowed) + ['created_at' => 'created_at'];

        // created_at كمُرجِّح ثانوي حتى يبقى ترتيب الصفوف المتساوية ثابتاً
        return TableSort::apply($query, $column, $dir, $map, tieBreaker: 'created_at');
    }
}
