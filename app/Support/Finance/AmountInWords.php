<?php

namespace App\Support\Finance;

/**
 * التفقيط — المبلغ بالحروف بصيغة الشيكات: «فقط مائة وخمسون ألف جنيه مصري لا غير».
 *
 * ضمانةٌ وافقت عليها المستخدمة: الصفر الزائد في الرقم (٨٥٠٬٠٠٠ بدل ٨٥٬٠٠٠) لا تلاحظه
 * العين في الأرقام، ويلاحظه القارئ حين يصير «ثمانمائة وخمسون ألفاً».
 *
 * جنيهات صحيحة فقط (لا قروش) وحتى ما دون التريليون.
 */
class AmountInWords
{
    private const ONES = [
        '', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة',
        'عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر',
        'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر',
    ];

    private const TENS = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];

    private const HUNDREDS = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

    /** [مفرد · مثنى · جمع (٣–١٠) · تمييز (١١ فأكثر)] */
    private const SCALES = [
        1_000_000_000 => ['مليار', 'ملياران', 'مليارات', 'مليار'],
        1_000_000     => ['مليون', 'مليونان', 'ملايين', 'مليون'],
        1_000         => ['ألف', 'ألفان', 'آلاف', 'ألف'],
    ];

    public static function pounds(int $amount): string
    {
        return 'فقط '.self::number($amount).' جنيه مصري لا غير';
    }

    public static function number(int $n): string
    {
        if ($n === 0) {
            return 'صفر';
        }

        $parts = [];

        foreach (self::SCALES as $scale => [$one, $two, $plural, $many]) {
            $count = intdiv($n, $scale);
            $n %= $scale;

            if ($count === 0) {
                continue;
            }

            $parts[] = match (true) {
                $count === 1 => $one,
                $count === 2 => $two,
                $count <= 10 => self::belowThousand($count).' '.$plural,
                default      => self::belowThousand($count).' '.$many,
            };
        }

        if ($n > 0) {
            $parts[] = self::belowThousand($n);
        }

        return implode(' و', $parts);
    }

    private static function belowThousand(int $n): string
    {
        $parts = [];

        if ($n >= 100) {
            $parts[] = self::HUNDREDS[intdiv($n, 100)];
            $n %= 100;
        }

        if ($n >= 20) {
            $ones = $n % 10;
            $parts[] = $ones ? self::ONES[$ones].' و'.self::TENS[intdiv($n, 10)] : self::TENS[intdiv($n, 10)];
        } elseif ($n > 0) {
            $parts[] = self::ONES[$n];
        }

        return implode(' و', $parts);
    }
}
