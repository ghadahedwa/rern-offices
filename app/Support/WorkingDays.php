<?php

namespace App\Support;

use App\Models\AttendanceDay;
use App\Models\DataEntryOperator;
use App\Models\OfficialHoliday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * حاسبة أيام العمل — **المصدر الواحد** لمعادلة الموديول كله:
 *
 *     أيام العمل   = أيام المدى − الجُمَع − العطلات الرسمية
 *     أيام الحضور  = أيام العمل − الغياب − الإجازات
 *
 * تقرأ منها شاشةُ التسجيل (لتُعطِّل اليوم غير العامل) والتقاريرُ الثلاثة واللوحة،
 * فلا يتفرّع الحساب في ثلاثة مواضع تختلف أرقامها.
 *
 * ⚠️ **العطلة الواقعة يوم جمعة لا تُخصم مرتين** — الجمعة تُستبعد أولاً ثم العطلات،
 *    فترحيل عطلةٍ إلى الجمعة لا يُنقص يوم عملٍ لم يكن موجوداً أصلاً.
 * ⚠️ **الحساب لحظي لا مخزَّن** — إضافة عطلةٍ متأخرةٍ تصحّح تقارير الشهر الماضي معها،
 *    وهو المقصود: الشاشة مفتوحة والقرار يصل بعد وقوعه.
 * ⚠️ **الحضور مقصور على مدة الخدمة**: مَن التحق يوم ١٥ لا يُحسب حاضراً من يوم ١،
 *    ومَن انتهت خدمته لا يُحسب بعدها — وإلا صار المنقطع عن العمل حاضراً كل يوم.
 */
final class WorkingDays
{
    /** أيام العطلة الأسبوعية — الجمعة وحدها (قرار العميلة). */
    public const WEEKEND_DAYS = [CarbonInterface::FRIDAY];

    /** «اليوم» بتوقيت القاهرة لا بـUTC (قاعدة التوقيت في CLAUDE.md). */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(LocalTime::timezone())->startOfDay();
    }

    /** خريطة أيام العطلات الرسمية في المدى: 'Y-m-d' => اسم العطلة. */
    public static function holidayMap(DateTimeInterface|string $from, DateTimeInterface|string $to): array
    {
        [$start, $end] = self::range($from, $to);

        $map = [];

        foreach (OfficialHoliday::overlapping($start, $end)->ordered()->get() as $holiday) {
            foreach ($holiday->dates() as $day => $name) {
                if ($day >= $start->toDateString() && $day <= $end->toDateString()) {
                    $map[$day] = $name;
                }
            }
        }

        ksort($map);

        return $map;
    }

    /** أيام العمل في المدى: 'Y-m-d' مرتَّبة، بلا الجُمَع وبلا العطلات. */
    public static function calendar(DateTimeInterface|string $from, DateTimeInterface|string $to): array
    {
        [$start, $end] = self::range($from, $to);

        if ($start->gt($end)) {
            return [];
        }

        $holidays = self::holidayMap($start, $end);
        $dates    = [];

        // ⚠️ `$day = $day->addDay()` لا `$day->addDay()` — CarbonImmutable لا تتغيّر بالإضافة،
        //    والصيغة المعتادة تُبقي الشرط صادقاً أبداً: حلقة لا نهائية.
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            if (self::isWeekend($day)) {
                continue;
            }

            $key = $day->toDateString();

            if (isset($holidays[$key])) {
                continue;
            }

            $dates[] = $key;
        }

        return $dates;
    }

    public static function count(DateTimeInterface|string $from, DateTimeInterface|string $to): int
    {
        return count(self::calendar($from, $to));
    }

    public static function isWeekend(DateTimeInterface|string $date): bool
    {
        return in_array(self::day($date)->dayOfWeek, self::WEEKEND_DAYS, true);
    }

    /** يوم عمل: ليس جمعة وليس عطلة رسمية. */
    public static function isWorkingDay(DateTimeInterface|string $date): bool
    {
        $day = self::day($date);

        return ! self::isWeekend($day) && ! isset(self::holidayMap($day, $day)[$day->toDateString()]);
    }

    /**
     * تفكيك يُعرض في التقرير: ٣٠ يوماً − ٤ جُمَع − عطلة = ٢٥.
     *
     * ⚠️ يُعرض التفكيك لا الرقم النهائي وحده — رقمٌ خاطئ في المعادلة يُرى بالعين
     *    ساعتها بدل أن يمرّ في تقريرٍ يبدو سليماً.
     */
    public static function breakdown(DateTimeInterface|string $from, DateTimeInterface|string $to): array
    {
        [$start, $end] = self::range($from, $to);

        if ($start->gt($end)) {
            return ['total' => 0, 'weekend' => 0, 'holidays' => 0, 'working' => 0];
        }

        $total    = (int) $start->diffInDays($end) + 1;
        $weekend  = 0;
        $holidays = self::holidayMap($start, $end);
        $counted  = 0;

        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            if (self::isWeekend($day)) {
                $weekend++;

                continue;
            }

            // العطلة الواقعة يوم جمعة أُحصيت ضمن الجُمَع، فلا تُحصى هنا مرة ثانية.
            if (isset($holidays[$day->toDateString()])) {
                $counted++;
            }
        }

        return [
            'total'    => $total,
            'weekend'  => $weekend,
            'holidays' => $counted,
            'working'  => $total - $weekend - $counted,
        ];
    }

    /** مدد خدمة المدخل: [['from' => 'Y-m-d', 'to' => 'Y-m-d'|null], …] */
    public static function serviceIntervals(DataEntryOperator $operator): array
    {
        $assignments = $operator->relationLoaded('assignments')
            ? $operator->assignments
            : $operator->assignments()->orderBy('started_on')->get();

        return $assignments
            ->map(fn ($assignment) => [
                'from' => $assignment->started_on?->toDateString(),
                'to'   => $assignment->ended_on?->toDateString(),
            ])
            ->filter(fn (array $interval) => $interval['from'] !== null)
            ->values()
            ->all();
    }

    /** أيام عمل المدخل داخل المدى — أيام العمل ∩ مدد تسكينه. */
    public static function operatorCalendar(
        DataEntryOperator $operator,
        DateTimeInterface|string $from,
        DateTimeInterface|string $to,
        ?array $calendar = null
    ): array {
        $calendar  = $calendar ?? self::calendar($from, $to);
        $intervals = self::serviceIntervals($operator);

        if ($intervals === []) {
            return [];
        }

        return array_values(array_filter(
            $calendar,
            fn (string $day) => self::coveredBy($day, $intervals)
        ));
    }

    /**
     * ملخّص المدخل في المدى: أيام العمل · الحضور · الاستثناءات لكل حالة.
     *
     * ⚠️ **لا يُحتسب استثناءٌ وقع في يومٍ غير عامل** (جمعة · عطلة · خارج الخدمة):
     *    اليوم مخصوم أصلاً من أيام العمل، فاحتسابه غياباً يخصمه مرة ثانية ويُنقص
     *    الحضور بلا سبب — وهذا يقع فعلاً حين تُضاف عطلةٌ بعد أن سُجِّل ذلك اليوم.
     *
     * @return array{working:int, present:int, exceptions:array<int,int>, dates:array<string,int>}
     */
    public static function summaryFor(
        DataEntryOperator $operator,
        DateTimeInterface|string $from,
        DateTimeInterface|string $to,
        ?array $calendar = null
    ): array {
        [$start, $end] = self::range($from, $to);

        $days    = self::operatorCalendar($operator, $start, $end, $calendar);
        $working = count($days);
        $lookup  = array_flip($days);

        $rows = $operator->relationLoaded('attendanceDays')
            ? $operator->attendanceDays
            : AttendanceDay::query()->forOperator($operator)->between($start, $end)->get();

        $exceptions = [];
        $dates      = [];

        foreach ($rows as $row) {
            $key = $row->date instanceof DateTimeInterface ? $row->date->format('Y-m-d') : (string) $row->date;

            if ($key < $start->toDateString() || $key > $end->toDateString() || ! isset($lookup[$key])) {
                continue;
            }

            $exceptions[$row->status_id] = ($exceptions[$row->status_id] ?? 0) + 1;
            $dates[$key]                 = $row->status_id;
        }

        return [
            'working'    => $working,
            'present'    => $working - array_sum($exceptions),
            'exceptions' => $exceptions,
            'dates'      => $dates,
        ];
    }

    /** هل يقع اليوم داخل إحدى مدد الخدمة؟ (المدة المفتوحة تمتدّ بلا نهاية). */
    private static function coveredBy(string $day, array $intervals): bool
    {
        foreach ($intervals as $interval) {
            if ($day >= $interval['from'] && ($interval['to'] === null || $day <= $interval['to'])) {
                return true;
            }
        }

        return false;
    }

    private static function day(DateTimeInterface|string $value): CarbonImmutable
    {
        return ($value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value))->startOfDay();
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    private static function range(DateTimeInterface|string $from, DateTimeInterface|string $to): array
    {
        return [self::day($from), self::day($to)];
    }
}
