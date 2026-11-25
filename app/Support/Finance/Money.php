<?php

namespace App\Support\Finance;

use App\Support\ArabicDigits;

/**
 * مبالغ الشئون المالية بالقروش — المصدر الواحد لقراءة ما يُكتب وعرض ما يُحفظ (طلب المستخدمة 2026-11-25:
 * «المبالغ تقبل قروش»، وكانت جنيهاً صحيحاً).
 *
 * ⚠️ **التخزين بالقروش عدداً صحيحاً** (١٬٢٣٤٫٥٠ جنيه = 123450) لا كسراً عشرياً: المجاميع وسلسلة أرصدة
 *    اتصالات وفحص السالب تبقى حساباً صحيحاً بلا أخطاء تقريب الكسور العائمة.
 * ⚠️ **العرض يُظهر القروش حين توجد وحدها** (١٬٢٣٤ · ١٬٢٣٤٫٥٠) — المبالغ الصحيحة تبقى كما كانت.
 * ⚠️ الفاصلة (`,` `٬` `،`) فاصل آلاف تُحذف، والكسر **نقطة** (`.` أو `٫`) بخانتين على الأكثر.
 */
class Money
{
    /** المبلغ المكتوب بعد التوحيد — حتى ١٢ خانة جنيهات وخانتا قروش */
    public const PATTERN = '/^\d{1,12}(\.\d{1,2})?$/';

    /** يوحّد ما كتبه المستخدم: الأرقام العربية · الكسر العربي «٫» · حذف فواصل الآلاف والمسافات */
    public static function normalize(?string $value): string
    {
        $value = ArabicDigits::toLatin(trim((string) $value));
        $value = str_replace('٫', '.', $value);

        return preg_replace('/[\s,٬،]/u', '', $value) ?? '';
    }

    /** المكتوب ← قروش، أو null إن لم يكن مبلغاً صالحاً */
    public static function parse(?string $value): ?int
    {
        $value = self::normalize($value);

        if (! preg_match(self::PATTERN, $value)) {
            return null;
        }

        [$pounds, $piasters] = array_pad(explode('.', $value, 2), 2, '');

        return (int) $pounds * 100 + (int) str_pad($piasters, 2, '0');
    }

    /** قروش ← نصٌّ يُملأ به حقل الإدخال (1500 · 1500.5 ← «1500.50») */
    public static function input(?int $piasters): string
    {
        if ($piasters === null) {
            return '';
        }

        $rest = $piasters % 100;

        return intdiv($piasters, 100).($rest ? '.'.str_pad((string) $rest, 2, '0', STR_PAD_LEFT) : '');
    }

    /** قروش ← عرض بفواصل الآلاف، والقروش حين توجد (والسالب بإشارته — رسائل الرصيد) */
    public static function format(?int $piasters): string
    {
        if ($piasters === null) {
            return '—';
        }

        $sign  = $piasters < 0 ? '-' : '';
        $abs   = abs($piasters);
        $rest  = $abs % 100;

        return $sign.number_format(intdiv($abs, 100)).($rest ? '.'.str_pad((string) $rest, 2, '0', STR_PAD_LEFT) : '');
    }

    /** قروش ← جنيهات (كسر) لخلايا Excel الرقمية */
    public static function pounds(?int $piasters): ?float
    {
        return $piasters === null ? null : $piasters / 100;
    }
}
