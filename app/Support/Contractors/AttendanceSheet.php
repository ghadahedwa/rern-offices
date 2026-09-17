<?php

namespace App\Support\Contractors;

use App\Models\AttendanceDay;
use App\Models\AttendanceReview;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\Office;
use App\Models\User;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * كشف حضور مقرٍّ عن شهر — منطق شبكة التسجيل كله: ما يُعرض وما يُقفل وما يُحفظ.
 *
 * ⚠️ **الشبكة مصدر الحقيقة لما تعرضه وحده**: الحفظ يُنشئ ويعدّل ويحذف في صفوف هذا المقر
 *    وهذا الشهر **وفي الأيام المفتوحة وحدها**. يومٌ قضاه العامل في مقرٍّ آخر، أو جمعة، أو
 *    عطلة، لا يُمسّ — ولو وصل من العميل.
 * ⚠️ **الحفظ دفعةً بصمة**: الشبكة تُحمَّل ببصمة حالتها، والحفظ يُرفض إن تغيّرت البصمة في
 *    الأثناء. بدونها يمحو حفظُ مستخدمٍ غياباً سجّله زميلٌ بعد أن فتح هو الشاشة — لأن الخلية
 *    الراجعة «حاضر» تحذف ما تحتها.
 */
final class AttendanceSheet
{
    public const STALE = 'stale';

    public readonly CarbonImmutable $start;
    public readonly CarbonImmutable $end;

    private ?Collection $contractors = null;
    private ?array $calendar = null;

    /** [contractorId => ['Y-m-d' => attendance_days.id]] — يملؤه `storedMarks`. */
    private array $storedIds = [];

    public function __construct(public readonly Office $office, CarbonImmutable $month)
    {
        $this->start = $month->startOfMonth()->startOfDay();
        $this->end   = $month->endOfMonth()->startOfDay();
    }

    /** 'Y-m' من الرابط — القيمة التالفة تُهمَل (null) ولا تُسقط الصفحة. */
    public static function parseMonth(?string $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $value, $m)) {
            return null;
        }

        $year = (int) $m[1];

        if ($year < 2000 || $year > 2100) {
            return null;
        }

        return CarbonImmutable::create($year, (int) $m[2], 1)->startOfDay();
    }

    /** أيام العمل في الشهر ('Y-m-d') — من الحاسبة الواحدة. */
    public function calendar(): array
    {
        return $this->calendar ??= WorkingDays::calendar($this->start, $this->end);
    }

    /**
     * أعمدة الشبكة: يوم لكل عمود بنوعه (work · weekend · holiday).
     *
     * @return array<int, array{date:string, day:int, weekday:int, kind:string, holiday:?string}>
     */
    public function columns(): array
    {
        $holidays = WorkingDays::holidayMap($this->start, $this->end);
        $columns  = [];

        for ($day = $this->start; $day->lte($this->end); $day = $day->addDay()) {
            $key = $day->toDateString();

            $columns[] = [
                'date'    => $key,
                'day'     => $day->day,
                'weekday' => $day->dayOfWeek,
                'kind'    => WorkingDays::isWeekend($day) ? 'weekend' : (isset($holidays[$key]) ? 'holiday' : 'work'),
                'holiday' => $holidays[$key] ?? null,
            ];
        }

        return $columns;
    }

    /** مَن خدم في هذا المقر يوماً واحداً على الأقل من الشهر. */
    public function contractors(): Collection
    {
        return $this->contractors ??= Contractor::query()
            ->whereHas('assignments', fn ($q) => $q->where('office_id', $this->office->id)->overlapping($this->start, $this->end))
            ->with(['profession', 'assignments' => fn ($q) => $q->orderBy('started_on')])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * صفوف الشبكة بحالتها المخزَّنة.
     *
     * @return array<int, array{id:int, name:string, profession:?string, open:array<int,string>, marks:array<string,int>, reviewed:bool}>
     */
    public function rows(): array
    {
        $contractors = $this->contractors();
        $marks       = $this->storedMarks($contractors->modelKeys());
        $reviewed    = array_flip($this->storedReviews($contractors->modelKeys()));
        $rows        = [];

        foreach ($contractors as $contractor) {
            $open = $this->openDays($contractor);

            $rows[] = [
                'id'         => $contractor->id,
                'name'       => $contractor->name,
                'profession' => $contractor->profession?->name,
                'open'       => $open,
                // ⚠️ العلامة في يومٍ غير مفتوح لا تُعرض ولا تُحذف — تخصّ مقراً آخر أو يوماً
                //    صار عطلة بعد تسجيله (والحاسبة تتجاهلها أصلاً).
                'marks'      => array_intersect_key($marks[$contractor->id] ?? [], array_flip($open)),
                'reviewed'   => isset($reviewed[$contractor->id]),
            ];
        }

        return $rows;
    }

    /** بصمة الحالة المخزَّنة لما تعرضه الشبكة — تُقارن قبل الحفظ. */
    public function fingerprint(?array $rows = null): string
    {
        $rows = $rows ?? $this->rows();

        return md5(json_encode(array_map(
            fn (array $row) => [$row['id'], $row['open'], $row['marks'], $row['reviewed']],
            $rows
        )));
    }

    /**
     * يحفظ الشبكة كما أرسلها العميل.
     *
     * @param  array  $marks     [contractorId => ['Y-m-d' => statusId]]
     * @param  array  $reviewed  [contractorId => bool] — ومفاتيحه **الصفوف التي يعرفها العميل**
     * @return string|array{created:int, updated:int, deleted:int}
     */
    public function save(array $marks, array $reviewed, string $fingerprint, User $user): string|array
    {
        return DB::transaction(function () use ($marks, $reviewed, $fingerprint, $user) {
            $rows = $this->rows();

            if (! hash_equals($this->fingerprint($rows), $fingerprint)) {
                return self::STALE;
            }

            $markable = AttendanceStatus::markable()->pluck('id')->all();
            $counts   = ['created' => 0, 'updated' => 0, 'deleted' => 0];

            foreach ($rows as $row) {
                $id = $row['id'];

                // ⚠️ صفٌّ لم يُرسله العميل لا يُمسّ: غيابه من الطلب لا يعني «حاضر الشهر كله».
                if (! array_key_exists($id, $reviewed)) {
                    continue;
                }

                $wanted = $this->sanitizeMarks($marks[$id] ?? [], $row, $markable);

                $this->applyMarks($id, $row['marks'], $wanted, $user, $counts);
                $this->applyReview($id, (bool) ($reviewed[$id] ?? false), $user);
            }

            return $counts;
        });
    }

    /**
     * ⚠️ كل ما يصل من العميل يُصفّى: يومٌ داخل الأيام المفتوحة لهذا الصفّ، وحالةٌ من أدوات
     *    التعليم — أو **الحالة المخزَّنة نفسها** في الخلية نفسها (حالةٌ عُطِّلت بعد تسجيلها
     *    تبقى كما هي، ولا تُكتب في خليةٍ جديدة).
     */
    private function sanitizeMarks(mixed $input, array $row, array $markable): array
    {
        if (! is_array($input)) {
            return [];
        }

        $open   = array_flip($row['open']);
        $wanted = [];

        foreach ($input as $date => $status) {
            $date = (string) $date;

            if (! isset($open[$date]) || ! (is_int($status) || (is_string($status) && ctype_digit($status)))) {
                continue;
            }

            $status = (int) $status;

            if (in_array($status, $markable, true) || ($row['marks'][$date] ?? null) === $status) {
                $wanted[$date] = $status;
            }
        }

        return $wanted;
    }

    /**
     * ⚠️ الحذف والتعديل **بمعرّف الصفّ** لا بـ`where('date', …)`: عمود `date` المصبوب يُكتب
     *    `'Y-m-d 00:00:00'` على sqlite، فالمطابقة بالنصّ `'Y-m-d'` لا تطابق شيئاً هناك.
     */
    private function applyMarks(int $contractorId, array $stored, array $wanted, User $user, array &$counts): void
    {
        $ids = $this->storedIds[$contractorId] ?? [];

        $removed = array_values(array_intersect_key($ids, array_diff_key($stored, $wanted)));

        if ($removed !== []) {
            $counts['deleted'] += AttendanceDay::query()->whereKey($removed)->delete();
        }

        foreach ($wanted as $date => $status) {
            if (! isset($stored[$date])) {
                AttendanceDay::create([
                    'attendable_type' => Contractor::class,
                    'attendable_id'   => $contractorId,
                    'date'            => $date,
                    'status_id'       => $status,
                    'recorded_by'     => $user->id,
                ]);
                $counts['created']++;
            } elseif ($stored[$date] !== $status) {
                AttendanceDay::query()->whereKey($ids[$date])
                    ->update(['status_id' => $status, 'recorded_by' => $user->id, 'updated_at' => now()]);
                $counts['updated']++;
            }
        }
    }

    private function applyReview(int $contractorId, bool $reviewed, User $user): void
    {
        $query = $this->reviewsQuery()->forContractor($contractorId);

        if (! $reviewed) {
            $query->delete();

            return;
        }

        if (! $query->exists()) {
            AttendanceReview::create([
                'attendable_type' => Contractor::class,
                'attendable_id'   => $contractorId,
                'office_id'       => $this->office->id,
                'month'           => $this->start->toDateString(),
                'reviewed_by'     => $user->id,
            ]);
        }
    }

    /** الأيام المفتوحة للتعليم: أيام العمل ∩ تسكينه في **هذا** المقر. */
    private function openDays(Contractor $contractor): array
    {
        return WorkingDays::contractorCalendar($contractor, $this->start, $this->end, $this->calendar(), $this->office->id);
    }

    /** @return array<int, array<string,int>> */
    private function storedMarks(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $map             = [];
        $this->storedIds = [];

        AttendanceDay::query()
            ->where('attendable_type', Contractor::class)
            ->whereIn('attendable_id', $ids)
            ->between($this->start, $this->end)
            ->orderBy('date')
            ->get(['id', 'attendable_id', 'date', 'status_id'])
            ->each(function (AttendanceDay $day) use (&$map) {
                $key = $day->date->toDateString();

                $map[$day->attendable_id][$key]             = (int) $day->status_id;
                $this->storedIds[$day->attendable_id][$key] = $day->id;
            });

        return $map;
    }

    /** @return array<int, int> معرّفات مَن وصل كشفه في هذا المقر عن هذا الشهر */
    private function storedReviews(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->reviewsQuery()
            ->where('attendable_type', Contractor::class)
            ->whereIn('attendable_id', $ids)
            ->pluck('attendable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * مراجعات هذا المقر عن هذا الشهر.
     *
     * ⚠️ نطاقٌ مفتوح لا مساواة: `month` المصبوب `'date'` يُكتب `'Y-m-d 00:00:00'` على sqlite.
     */
    private function reviewsQuery()
    {
        return AttendanceReview::query()
            ->where('office_id', $this->office->id)
            ->where('month', '>=', $this->start->toDateString())
            ->where('month', '<', $this->start->addDay()->toDateString());
    }
}
