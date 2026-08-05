<?php

namespace App\Support\FeedbackResults;

use Illuminate\Database\Eloquent\Builder;

/**
 * ترتيب جداول النتائج.
 *
 * ⚠️ اسم العمود يأتي من الـ URL (الشاشة والتصدير على السواء)، فلا يُمرَّر
 * لـ orderBy إلا بعد التحقق من القائمة البيضاء — وإلا صار حقن SQL عبر الرابط.
 */
final class FeedbackSort
{
    public static function apply(Builder $query, string $column, string $dir, array $allowed): Builder
    {
        $column = in_array($column, $allowed, true) ? $column : 'created_at';
        $dir    = $dir === 'asc' ? 'asc' : 'desc';

        // created_at كمُرجِّح ثانوي حتى يبقى ترتيب الصفوف المتساوية ثابتاً
        return $query->orderBy($column, $dir)
            ->when($column !== 'created_at', fn ($q) => $q->orderBy('created_at', 'desc'));
    }
}
