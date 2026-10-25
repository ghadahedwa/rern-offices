<?php

namespace App\Support;

/**
 * رابط الرجوع إلى قائمة المقرات بآخر فلاترها (طلب العميل 2026-10-25).
 *
 * القائمة تحفظ فلاترها في الرابط، لكن أزرار الرجوع في العرض والتعديل والإحصائيات
 * كانت `route('offices.index')` مجرّداً فيعود المستخدم لقائمةٍ بلا فلاتر.
 * فالقائمة تحفظ معاملات رابطها في الجلسة مع كل عرض، والأزرار تقرأ منها.
 *
 * ⚠️ تُحفظ **المعاملات لا الرابط كاملاً** — الرابط يُبنى هنا من `route()` دائماً،
 *    فلا يصير زرّ الرجوع توجيهاً إلى عنوانٍ آخر.
 */
class OfficeListUrl
{
    public const SESSION_KEY = 'offices.list_query';

    public static function remember(array $query): void
    {
        session()->put(self::SESSION_KEY, $query);
    }

    public static function back(): string
    {
        $query = session(self::SESSION_KEY, []);

        return route('offices.index', is_array($query) ? $query : []);
    }
}
