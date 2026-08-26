<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * عرض التواريخ والأوقات بالتوقيت المحلي (مصر) وبنظام ١٢ ساعة.
 *
 * ⚠️ التخزين في قاعدة البيانات بـ UTC (config('app.timezone') = UTC)، والتحويل
 * يتم عند العرض فقط. هذا الفصل مقصود: التخزين موحّد والعرض محلي.
 * لو غُيّر APP_TIMEZONE لاحقاً فلا بد من تحويل الصفوف المخزَّنة معه، وإلا
 * ستُقرأ قيم UTC القديمة كأنها توقيت محلي (فرق ٢–٣ ساعات حسب التوقيت الصيفي).
 */
class LocalTime
{
    public static function timezone(): string
    {
        return config('app.display_timezone', 'Africa/Cairo');
    }

    public static function at(?DateTimeInterface $value): ?Carbon
    {
        return $value ? Carbon::instance($value)->timezone(self::timezone()) : null;
    }

    /** التاريخ: 2026-08-03 */
    public static function date(?DateTimeInterface $value): string
    {
        return self::at($value)?->format('Y-m-d') ?? '—';
    }

    /** الساعة بنظام ١٢ مع ص/م: 8:30 م */
    public static function time(?DateTimeInterface $value): string
    {
        $dt = self::at($value);

        if (! $dt) {
            return '—';
        }

        return $dt->format('g:i').' '.($dt->format('A') === 'AM' ? 'ص' : 'م');
    }

    /**
     * ساعة حائط مخزَّنة كنص ('14:30:00') → '2:30 م'.
     * ⚠️ بلا أي تحويل توقيت: هذه ساعة كتبها المستخدم لا لحظة زمنية،
     * فتحويلها سيزيحها ٢–٣ ساعات عمّا قصده.
     */
    public static function clock(?string $time): string
    {
        if (! $time) {
            return '—';
        }

        [$h, $m] = array_pad(explode(':', $time), 2, '00');
        $h = (int) $h;

        return ($h % 12 ?: 12).':'.str_pad((string) (int) $m, 2, '0', STR_PAD_LEFT)
            .' '.($h < 12 ? 'ص' : 'م');
    }

    /** التاريخ والوقت معاً: 2026-08-03 11:47 م */
    public static function stamp(?DateTimeInterface $value): string
    {
        return $value ? self::date($value).' '.self::time($value) : '—';
    }

    /** التاريخ والوقت مفصولين بشرطة (للـ tooltip). */
    public static function full(?DateTimeInterface $value): string
    {
        return $value ? self::date($value).' — '.self::time($value) : '—';
    }

    /**
     * بداية اليوم المحلي (مصر) كلحظةٍ صالحة لمقارنة عمود طابع زمني مخزَّن بـUTC.
     *
     * ⚠️ لا يُقارَن عمود طابع زمني بتاريخٍ مجرّد: المستخدم يكتب يوماً بتوقيت
     *    القاهرة والعمود مخزَّن UTC — فحركة الواحدة فجراً بالقاهرة محفوظة في
     *    اليوم السابق بـUTC، وتغيب عن فلتر يومها الذي وقعت فيه فعلاً.
     * ⚠️ والفرق ٢ أو ٣ ساعات حسب التوقيت الصيفي، فلا تصلح إزاحة ثابتة.
     *
     * التاريخ قد يصل من الرابط تالفاً — يُهمَل بدل إسقاط الصفحة.
     */
    public static function dayStart(?string $date): ?Carbon
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($date), self::timezone())->startOfDay()->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * بداية اليوم المحلي التالي كلحظة UTC — ليكون الحدّ الأعلى مفتوحاً (`<`)
     * فيدخل يوم النهاية كاملاً بلا دالة DATE() على العمود (وهي تمنع الفهرس
     * وتحوّل الفلترة إلى مسح كامل للجدول).
     */
    public static function dayAfter(?string $date): ?Carbon
    {
        return self::dayStart($date)?->addDay();
    }
}
