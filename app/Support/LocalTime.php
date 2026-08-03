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
}
