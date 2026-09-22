<?php

namespace App\Support\Contractors;

use App\Models\AttendanceDay;
use App\Models\AttendanceReview;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * محرّك تقارير الحضور — **المصدر الواحد للأرقام**: الشاشات الثلاث والتصدير تقرأ منه،
 * فلا يخرج ملفٌّ بأرقامٍ تخالف الشاشة.
 *
 * الوحدة الذرّية صفٌّ لكل **(عامل × مقر)** في المدى، وكل ما فوقها جمعٌ لها:
 * تقرير العامل يجمع صفوفه، وتقرير المقر يعرضها، وتقرير المحافظة يجمع مقارّها.
 *
 *   أيام العمل = حضر + (غاب + إجازة + …)
 *
 * ⚠️ **الأعمدة تجمع أيام العمل بالضبط** — رقمٌ لا يُغلق المعادلة خطأٌ يُرى بالعين.
 * ⚠️ **المحرّك يفصل `present` عن `unreviewed` والعرضُ لا يفصلهما**: الفرق قائمٌ في
 *    البيانات (خانة «وصل الكشف» في الشبكة) ويُقرأ في **تنبيهٍ تحت الجدول** لا في عمودٍ
 *    ثالث — انظر `attended()` و`unrecordedContractors()`.
 * ⚠️ **الغياب والإجازة المسجَّلان يبقيان كما هما** رُصد الكشف أو لم يُرصَد — واقعةٌ كتبها
 *    المستخدم لا اشتقاق، فلا يبتلعها «حضر».
 */
final class AttendanceReport
{
    public readonly CarbonImmutable $start;

    public readonly CarbonImmutable $end;

    /** @var array<int,string>|null أيام العمل في المدى ('Y-m-d') */
    private ?array $calendar = null;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        $from = $from->startOfDay();
        $to   = $to->startOfDay();

        // مدىً مقلوب من الرابط لا يُسقط الصفحة — يُقوَّم.
        $this->start = $from->lte($to) ? $from : $to;
        $this->end   = $from->lte($to) ? $to : $from;
    }

    /** أيام العمل في المدى — تُحسب مرة واحدة ويقرؤها كل العاملين. */
    public function calendar(): array
    {
        return $this->calendar ??= WorkingDays::calendar($this->start, $this->end);
    }

    /** تفكيك أيام العمل لرأس التقرير: ٣٠ − ٤ جُمَع − عطلة = ٢٥. */
    public function breakdown(): array
    {
        return WorkingDays::breakdown($this->start, $this->end);
    }

    /** عطلات المدى: 'Y-m-d' => الاسم. */
    public function holidays(): array
    {
        return WorkingDays::holidayMap($this->start, $this->end);
    }

    /**
     * صفٌّ لكل (عامل × مقر) خدم فيه العامل داخل المدى.
     *
     * ⚠️ **اليوم يُنسب لمقرّه من `contractor_assignments` لا لمقر العامل الحالي** — تقرير
     *    شهرٍ مضى يضع أيامه في المقر الذي كان فيه وقتها، فالنقل لا يعيد كتابة التاريخ.
     * ⚠️ **اليوم الواحد لا يُعدّ مرتين**: `ContractorAssignment::overlapsExisting()` يمنع
     *    التداخل عند الحفظ، وهنا **أول تسكينٍ بالتاريخ يملك اليوم** احتياطاً — لولاه
     *    لأعطى صفٌّ متداخلٌ قديمٌ في الداتابيز أيامَ عملٍ أكثر من أيام المدى نفسه.
     * ⚠️ **الأيام تُوزَّع قبل فلتر المقر لا بعده** — الحارس أعلاه يقرأ تسكينات العامل كلها،
     *    فلو صُفِّيت أولاً لَورث المقرُّ المعروض يوماً يملكه تسكينٌ أسبق منه.
     *
     * @param  Collection<int,Contractor>  $contractors
     * @param  array<int,int>|null  $officeIds  قصرُ الصفوف على مقارٍّ بعينها
     * @return array<int, array<string,mixed>>
     */
    public function rows(Collection $contractors, ?array $officeIds = null): array
    {
        // `map(getKey)` لا `modelKeys()`: تقرير العامل يمرّر مجموعةً عادية بعنصرٍ واحد.
        $ids = $contractors->map(fn (Contractor $contractor) => $contractor->getKey())->all();

        if ($ids === []) {
            return [];
        }

        $calendar    = $this->calendar();
        $assignments = $this->assignmentsFor($ids);
        $marks       = $this->marksFor($ids);
        $reviews     = $this->reviewsFor($ids);
        $officeSet   = $officeIds === null ? null : array_flip(array_map('intval', $officeIds));

        $rows = [];

        foreach ($contractors as $contractor) {
            $owned = []; // 'Y-m-d' => true — حارس ألّا يُعدّ يومٌ مرتين لهذا العامل
            $mine  = $marks[$contractor->getKey()] ?? [];

            foreach ($assignments[$contractor->getKey()] ?? [] as $assignment) {
                $from = max($assignment->started_on->toDateString(), $this->start->toDateString());
                $to   = $assignment->ended_on
                    ? min($assignment->ended_on->toDateString(), $this->end->toDateString())
                    : $this->end->toDateString();

                $tally = [
                    'working'    => 0,
                    'present'    => 0,
                    'unreviewed' => 0,
                    'exceptions' => [],
                ];

                foreach ($calendar as $day) {
                    if ($day < $from || $day > $to || isset($owned[$day])) {
                        continue;
                    }

                    $owned[$day] = true;
                    $tally['working']++;

                    if (isset($mine[$day])) {
                        $status = $mine[$day];
                        $tally['exceptions'][$status] = ($tally['exceptions'][$status] ?? 0) + 1;

                        continue;
                    }

                    $key = $contractor->getKey().'|'.$assignment->office_id.'|'.substr($day, 0, 7);

                    if (isset($reviews[$key])) {
                        $tally['present']++;
                    } else {
                        $tally['unreviewed']++;
                    }
                }

                if ($officeSet !== null && ! isset($officeSet[$assignment->office_id])) {
                    continue;
                }

                $rows[] = [
                    'contractor_id'    => $contractor->getKey(),
                    'contractor_name'  => $contractor->name,
                    'phone'            => $contractor->phone,
                    'profession'       => $contractor->profession?->name,
                    'office_id'        => $assignment->office_id,
                    'office_name'      => $assignment->office?->name ?? '—',
                    'governorate_id'   => $assignment->office?->governorate_id,
                    'governorate_name' => $assignment->office?->governorate?->name,
                    'started_on'       => $assignment->started_on->toDateString(),
                    'ended_on'         => $assignment->ended_on?->toDateString(),
                ] + $tally;
            }
        }

        return $rows;
    }

    /**
     * «حضر» كما يُعرض — **المرصود وغير المرصود معاً**.
     *
     * ⚠️ المحرّك يفصل `present` عن `unreviewed` لأن الفرق قائمٌ في البيانات (خانة
     *    «وصل الكشف» في شبكة التسجيل)، **لكن العرض لا يفصلهما**: عمودٌ ثالث يكون
     *    **صفراً لكل الصفوف في الشهر العادي** ضجيجٌ لا معلومة، ومصطلحُه («غير مراجَع»)
     *    لا يعرفه مَن يقرأ التقرير (طلب المستخدمة ٢٠٢٦-٠٩-٢٢).
     *    وما يبقى من الفرق **تنبيهٌ تحت الجدول يظهر حين يقع وحده** — انظر
     *    `unrecordedContractors()`.
     * ⚠️ **دالةٌ واحدة يقرؤها الثلاثة** (شاشة · Excel · PDF): الجمع في ثلاثة قوالب
     *    يفترق أولَ تعديل.
     *
     * @param  array<string,mixed>  $row
     */
    public static function attended(array $row): int
    {
        return $row['present'] + $row['unreviewed'];
    }

    /**
     * عدد العاملين الذين لم تُرصد أيام حضورهم في مقرٍّ وشهرٍ داخل المدى.
     *
     * ⚠️ **عددُ عاملين لا عددُ أيام**: التنبيه يقول «مَن» لا «كم يوماً»، لأن الإجراء
     *    المطلوب من المفتش أن يسأل عن كشفٍ لم يصل لا أن يصحّح رقماً.
     *
     * @param  array<int,array<string,mixed>>  $rows
     */
    public static function unrecordedContractors(array $rows): int
    {
        $ids = [];

        foreach ($rows as $row) {
            if ($row['unreviewed'] > 0) {
                $ids[$row['contractor_id']] = true;
            }
        }

        return count($ids);
    }

    /**
     * جمعُ صفوفٍ في صفٍّ واحد — الأعداد تُجمع والاستثناءات تُجمع بمفتاح الحالة.
     *
     * @param  array<int,array<string,mixed>>  $rows
     */
    public static function sum(array $rows): array
    {
        $total = ['working' => 0, 'present' => 0, 'unreviewed' => 0, 'exceptions' => []];

        foreach ($rows as $row) {
            $total['working']    += $row['working'];
            $total['present']    += $row['present'];
            $total['unreviewed'] += $row['unreviewed'];

            foreach ($row['exceptions'] as $status => $count) {
                $total['exceptions'][$status] = ($total['exceptions'][$status] ?? 0) + $count;
            }
        }

        return $total;
    }

    /**
     * تجميع الصفوف بمفتاح (`contractor_id` · `office_id` · `governorate_id`) مع عدّ
     * **العاملين المتمايزين** — تقرير المقر والمحافظة يعرض «عدد العاملين» لا عدد الصفوف،
     * والعامل المنقول داخل المقر الواحد له صفّان.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int|string, array<string,mixed>>
     */
    public static function groupBy(array $rows, string $key): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $id = $row[$key] ?? 0;

            $groups[$id]['rows'][]                            = $row;
            $groups[$id]['contractors'][$row['contractor_id']] = true;
        }

        foreach ($groups as $id => $group) {
            $first = $group['rows'][0];

            $groups[$id] = self::sum($group['rows']) + [
                'key'            => $id,
                'rows'           => $group['rows'],
                'contractors'    => count($group['contractors']),
                'contractor_ids' => array_keys($group['contractors']),
                // ⚠️ **الصفّ المجمَّع يحمل أسماءه** — الشاشة تقرأ الاسم من خريطةٍ عندها،
                //    أما الملف فلا يملك إلا الصفّ. وبدونها كان يطبع **رقم** المحافظة
                //    مكان اسمها، فيخالف الملفُّ الشاشةَ في أول عمودٍ فيه.
                'governorate_name' => $first['governorate_name'] ?? null,
                'office_name'      => $first['office_name'] ?? null,
                'contractor_name'  => $first['contractor_name'] ?? null,
            ];
        }

        return $groups;
    }

    /**
     * أعمدة الحالات في التقرير: المفعَّلة غير الافتراضية، **ومعها المعطَّلة التي لها
     * أرقامٌ في هذا المدى** — حالةٌ عُطِّلت بعد أن سُجِّلت بها أيامٌ لا تختفي أرقامها من
     * تقريرٍ عن أيامها، وإلا اختلّ جمعُ الأعمدة على أيام العمل بلا سببٍ ظاهر.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return Collection<int,AttendanceStatus>
     */
    public static function statusColumns(array $rows): Collection
    {
        $used = array_keys(self::sum($rows)['exceptions']);

        return AttendanceStatus::query()
            ->where('is_default', false)
            ->where(fn ($q) => $q->where('is_active', true)
                ->when($used !== [], fn ($inner) => $inner->orWhereIn('id', $used)))
            ->ordered()
            ->get();
    }

    /** @return array<int, Collection<int,ContractorAssignment>> */
    private function assignmentsFor(array $ids): array
    {
        return ContractorAssignment::query()
            ->whereIn('contractor_id', $ids)
            ->overlapping($this->start, $this->end)
            ->with(['office:id,name,governorate_id', 'office.governorate:id,name'])
            ->orderBy('started_on')
            ->orderBy('id')
            ->get()
            ->groupBy('contractor_id')
            ->all();
    }

    /** [contractorId => ['Y-m-d' => status_id]] */
    private function marksFor(array $ids): array
    {
        $marks = [];

        AttendanceDay::query()
            ->where('attendable_type', Contractor::class)
            ->whereIn('attendable_id', $ids)
            ->between($this->start, $this->end)
            ->get(['id', 'attendable_id', 'date', 'status_id'])
            ->each(function (AttendanceDay $row) use (&$marks) {
                $marks[$row->attendable_id][$row->date->toDateString()] = $row->status_id;
            });

        return $marks;
    }

    /**
     * مفاتيح «وصل الكشف»: "contractorId|officeId|Y-m".
     *
     * ⚠️ **نطاقٌ مفتوح لا `whereIn` بنصوص الشهور**: `month` المصبوب `'date'` يُكتب
     *    `'Y-m-d 00:00:00'` على sqlite، فالمساواة بنصّ اليوم لا تطابق.
     */
    private function reviewsFor(array $ids): array
    {
        $rows = AttendanceReview::query()
            ->where('attendable_type', Contractor::class)
            ->whereIn('attendable_id', $ids)
            ->where('month', '>=', $this->start->startOfMonth()->toDateString())
            ->where('month', '<', $this->end->startOfMonth()->addMonth()->toDateString())
            ->get(['id', 'attendable_id', 'office_id', 'month']);

        $keys = [];

        foreach ($rows as $row) {
            $keys[$row->attendable_id.'|'.$row->office_id.'|'.$row->month->format('Y-m')] = true;
        }

        return $keys;
    }
}
