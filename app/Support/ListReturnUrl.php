<?php

namespace App\Support;

/**
 * رابط الرجوع إلى قائمةٍ بآخر فلاترها (طلب العميل 2026-10-25 — بدأ بالمقرات).
 *
 * القوائم تحفظ فلاترها في الرابط، لكن أزرار الرجوع في صفحات السجلّ الواحد كانت
 * `route('…index')` مجرّداً فيعود المستخدم لقائمةٍ بلا فلاتر. فالقائمة تحفظ معاملات
 * رابطها في الجلسة مع كل طلب (`Concerns\RemembersListUrl`)، والأزرار تقرأ من هنا.
 *
 * ⚠️ تُحفظ **المعاملات لا الرابط كاملاً** — الرابط يُبنى هنا من `route()` دائماً،
 *    فلا يصير زرّ الرجوع توجيهاً إلى عنوانٍ آخر.
 * المفتاح اسم الراوت ومعاملاته: قائمة تقارير المفتش غير قائمة المستشار.
 */
class ListReturnUrl
{
    public static function remember(string $route, array $params, array $query): void
    {
        session()->put(self::key($route, $params), $query);
    }

    /** المعاملات المحفوظة لهذه القائمة ([] إن لم تُفتح في الجلسة) */
    public static function query(string $route, array $params = []): array
    {
        $query = session(self::key($route, $params), []);

        return is_array($query) ? $query : [];
    }

    public static function back(string $route, array $params = []): string
    {
        return route($route, $params + self::query($route, $params));
    }

    private static function key(string $route, array $params): string
    {
        return 'list_return.'.$route.($params ? ':'.implode(',', $params) : '');
    }
}
