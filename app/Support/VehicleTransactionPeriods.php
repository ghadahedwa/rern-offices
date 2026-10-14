<?php

namespace App\Support;

/**
 * فترات «معاملات التوثيق» للسيارات المتنقلة — المصدر الواحد للقاعدة (قرار العميل 2026-10-14):
 *
 *   ٢٠٢٠ … ٢٠٢٥          رقم سنوي واحد لكل سنة
 *   ٢٠٢٦                 رقم واحد «يناير–سبتمبر» + رقم لكل شهر من أكتوبر
 *   ٢٠٢٧ فصاعداً          رقم لكل شهر
 *
 * التخزين: الصفّ بلا شهر (`month = NULL`) **إجمالي فترة** (السنة كلها، أو يناير–سبتمبر في ٢٠٢٦)،
 * والصفّ بشهر رقمٌ شهري. فـ**إجمالي أي سنة = مجموع صفوفها** — ولذلك تبقى الأماكن التي تجمع
 * «آخر سنة» (اللوحة · أعمدة التقرير) صحيحة بلا تعديل.
 *
 * ⚠️ القاعدة للسيارات وحدها: نوع الإحصائية (`stat_types.id = 1`) مشترك مع المقرات، فلا تُنقل
 *    القاعدة إلى `stat_types.period` — ذلك يغيّر إدخال المقرات معه.
 * ⚠️ لا تُقبل فترتان تغطّيان الشهر نفسه (رقم سنوي لـ٢٠٢٦ أو شهر قبل أكتوبر ٢٠٢٦) — تُحسب مرتين.
 */
class VehicleTransactionPeriods
{
    /** نوع الإحصائية الذي تسري عليه القاعدة */
    public const STAT_TYPE_ID = 1;

    public const FIRST_YEAR = 2020;

    /** أول شهر يُسجَّل منفرداً */
    public const MONTHLY_FROM_YEAR  = 2026;
    public const MONTHLY_FROM_MONTH = 10;

    /** قيمة خيار «يناير–سبتمبر» في منسدلة الشهر — تُخزَّن NULL */
    public const PARTIAL = '0';

    /** هل تُسجَّل هذه السنة رقماً سنوياً واحداً؟ */
    public static function isYearly(int $year): bool
    {
        return $year < self::MONTHLY_FROM_YEAR;
    }

    /** سنة التحوّل: إجمالي يناير–سبتمبر + شهور منفردة */
    public static function isTransition(int $year): bool
    {
        return $year === self::MONTHLY_FROM_YEAR;
    }

    /**
     * خيارات منسدلة الشهر لسنة معيّنة — [] للسنة السنوية (لا منسدلة).
     *
     * @return array<string,string>
     */
    public static function monthOptions(int $year, array $monthNames): array
    {
        if (self::isYearly($year)) {
            return [];
        }

        $options = [];
        $from    = 1;

        if (self::isTransition($year)) {
            $options[self::PARTIAL] = self::partialLabel();
            $from = self::MONTHLY_FROM_MONTH;
        }

        foreach (range($from, 12) as $m) {
            $options[(string) $m] = $monthNames[$m];
        }

        return $options;
    }

    /** هل (سنة، شهر) فترةٌ مسموحة؟ $month = null للصفّ الإجمالي */
    public static function allows(int $year, ?int $month): bool
    {
        if ($year < self::FIRST_YEAR) {
            return false;
        }

        if ($month === null) {
            return $year <= self::MONTHLY_FROM_YEAR;
        }

        if ($month < 1 || $month > 12) {
            return false;
        }

        return $year > self::MONTHLY_FROM_YEAR
            || ($year === self::MONTHLY_FROM_YEAR && $month >= self::MONTHLY_FROM_MONTH);
    }

    /** تسمية الفترة في الجداول: «السنة كاملة» · «يناير – سبتمبر» · اسم الشهر */
    public static function label(int $year, ?int $month, array $monthNames): string
    {
        if ($month !== null) {
            return $monthNames[$month] ?? (string) $month;
        }

        return self::isTransition($year) ? self::partialLabel() : __('home.vstat_full_year');
    }

    public static function partialLabel(): string
    {
        return __('home.vstat_partial_2026');
    }
}
