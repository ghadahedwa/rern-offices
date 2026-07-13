<?php

namespace App\Support;

/**
 * توحيد النص العربي للبحث — يوحّد أشكال الألف والياء والتاء المربوطة ويزيل المسافات.
 * (التشكيل خارج التوحيد حالياً.)
 *
 * نفس التحويلات تُطبَّق على:
 *   - كلمة البحث   → normalize()   (في PHP)
 *   - عمود القاعدة → sqlNormalize() (في SQL بـ REPLACE، للحفاظ على الـ pagination)
 */
class ArabicText
{
    /** خريطة التوحيد: شكل الحرف => الشكل الموحّد */
    protected const MAP = [
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ى' => 'ي',
        'ة' => 'ه',
    ];

    /** توحيد نص في PHP (لكلمة البحث). */
    public static function normalize(?string $text): string
    {
        $text = str_replace(array_keys(self::MAP), array_values(self::MAP), (string) $text);

        // إزالة كل المسافات
        return preg_replace('/\s+/u', '', $text) ?? '';
    }

    /** تعبير SQL يوحّد عموداً بنفس القواعد (نفس MAP + إزالة المسافات). */
    public static function sqlNormalize(string $column): string
    {
        $expr = $column;

        foreach (self::MAP as $from => $to) {
            $expr = "REPLACE({$expr}, '{$from}', '{$to}')";
        }

        // إزالة المسافات
        return "REPLACE({$expr}, ' ', '')";
    }
}
