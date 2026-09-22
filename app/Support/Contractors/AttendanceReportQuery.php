<?php

namespace App\Support\Contractors;

use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\Governorate;
use App\Models\Office;
use App\Support\ArabicText;
use App\Support\ContractorScope;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * استعلام تقرير الحضور ككائن — **الشاشة والكنترولر يقرآن منه معاً**.
 *
 * الشاشة مكوّن Livewire، وتقرير PDF كنترولرٌ خارجه. ولو بنى كلٌّ منهما استعلامه
 * لخرج الملف بأرقامٍ تخالف الشاشة — وهو أسوأ من غياب التصدير. فالنطاق والفلاتر
 * وبناء الصفوف كلها هنا، والاثنان يستدعيان.
 *
 * ⚠️ **نقطة النطاق الوحيدة في التقارير**: `officeIds()` هو الذي يمنع تسرّب أيام
 *    عاملٍ نُقل إلى محافظةٍ ليست لي (انظر تعليقها أدناه).
 */
final class AttendanceReportQuery
{
    /** مستويات التقرير — قائمةٌ بيضاء: المستوى يصل من الرابط. */
    public const LEVELS = ['governorates', 'office', 'contractor'];

    private ?AttendanceReport $report = null;

    /**
     * @param  array<int,int>  $governorateIds  محافظاتٌ محدَّدة، والفارغ = كل نطاق المستخدم
     */
    public function __construct(
        public readonly string $level,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly array $governorateIds = [],
        public readonly ?int $officeId = null,
        public readonly ?int $contractorId = null,
        private readonly ?Authenticatable $user = null,
    ) {}

    /**
     * بناء الاستعلام من رابط تقرير الـPDF.
     *
     * ⚠️ **كل قيمة تصل من الرابط تُفحَص**: المستوى من قائمةٍ بيضاء، والمعرّفات
     *    `ctype_digit`، والتاريخ بصيغته. القيمة التالفة تُهمَل ولا تُمرَّر —
     *    تمريرها يُخرج تقريراً فارغاً بلا سببٍ ظاهر للمستخدم.
     */
    public static function fromRequest(Request $request, ?Authenticatable $user = null): ?self
    {
        $level = (string) $request->query('level', '');

        if (! in_array($level, self::LEVELS, true)) {
            return null;
        }

        $from = self::parseDate($request->query('from'));
        $to   = self::parseDate($request->query('to'));

        if (! $from || ! $to) {
            return null;
        }

        return new self(
            level: $level,
            from: $from,
            to: $to,
            governorateIds: self::ids($request->query('gov')),
            officeId: self::id($request->query('office')),
            contractorId: self::id($request->query('contractor')),
            user: $user,
        );
    }

    /** رابط تقرير الـPDF — الفلاتر في الـquery string فالرابط قابل للمشاركة. */
    public function toQuery(): array
    {
        return array_filter([
            'level'      => $this->level,
            'from'       => $this->from->toDateString(),
            'to'         => $this->to->toDateString(),
            'gov'        => implode(',', $this->governorateIds),
            'office'     => $this->officeId,
            'contractor' => $this->contractorId,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function report(): AttendanceReport
    {
        return $this->report ??= new AttendanceReport($this->from, $this->to);
    }

    /**
     * المقارّ التي تُحتسب أيامها — **حارس تسرّب النطاق**، و`null` تعني بلا حدّ.
     *
     * ⚠️ `ContractorScope::applyToContractors` يُبقي العامل مرئياً بتسكينٍ واحدٍ داخل
     *    النطاق (عمداً: تقارير فتراته عندي تخصّني)، **فصفوفه تحمل معها تسكيناته في
     *    محافظاتٍ ليست لي**. فلولا قصرُ المقارّ هنا لظهرت في تقريري أيامُ عاملٍ نُقل
     *    إلى محافظةٍ أخرى — وهو تسرّبٌ صامت لا يشكو منه أحد.
     */
    public function officeIds(): ?array
    {
        $scope    = ContractorScope::governorateIds($this->user);
        $selected = $this->governorateIds;

        if ($scope !== null) {
            // محافظةٌ تصل من الفورم أو الرابط وليست في النطاق تُهمَل ولا تُمرَّر.
            $selected = $selected === [] ? $scope : array_values(array_intersect($selected, $scope));

            if ($selected === []) {
                return [];
            }
        }

        if ($selected === []) {
            return null; // super-admin بلا تحديد = الجمهورية
        }

        $offices = Office::query()->whereIn('governorate_id', $selected)->pluck('id')->all();

        if ($this->officeId === null) {
            return $offices;
        }

        // ⚠️ المقر يصل من العميل فيُفحص انتماؤه للنطاق، ولا يُمرَّر كما جاء.
        return in_array($this->officeId, $offices, true) ? [$this->officeId] : [];
    }

    /**
     * العامل المطلوب — **يُقرأ عبر النطاق لا بـ`findOrFail`**، فمعرّفٌ مدسوس من
     * محافظةٍ أخرى لا يُخرج بياناته.
     */
    public function subject(): ?Contractor
    {
        if ($this->contractorId === null) {
            return null;
        }

        return ContractorScope::applyToContractors(
            Contractor::query()->whereKey($this->contractorId)->with('profession:id,name'),
            $this->user
        )->first();
    }

    /** صفوف (عامل × مقر) — **مصدرٌ واحد لما يُعرض وما يُصدَّر ويُطبع**. */
    public function rows(): array
    {
        $officeIds = $this->officeIds();

        if ($officeIds === []) {
            return [];
        }

        if ($this->level === 'contractor') {
            $contractor = $this->subject();

            if (! $contractor) {
                return [];
            }

            return $this->report()->rows(collect([$contractor]), $officeIds);
        }

        $rows = $this->report()->rows($this->scopedContractors($officeIds), $officeIds);

        if ($this->level === 'office') {
            // ترتيب الكشف: المقر ثم اسم العامل — ترتيب الورقة التي تُطبع.
            usort($rows, fn ($a, $b) => [$a['office_name'], $a['contractor_name']] <=> [$b['office_name'], $b['contractor_name']]);
        }

        return $rows;
    }

    /**
     * العاملون داخل النطاق.
     *
     * 📌 **طبقة احتياط: لا يسقط اختبارٌ بحذف `applyToContractors` منها** (مُثبَتٌ
     *    بالكسر) — `officeIds()` وحده يكفي لمنع التسرّب، لأن صفّاً لا يُبنى إلا
     *    لمقرٍّ مسموح. وتبقى **لتقليل المحمَّل** (لا تُسحب عمالة الجمهورية لتقرير
     *    محافظة) **ولبقاء الحدّ قائماً** لو نادى مستدعٍ لاحقٌ بـ`$officeIds = null`.
     *
     * @return Collection<int,Contractor>
     */
    private function scopedContractors(?array $officeIds): Collection
    {
        $query = Contractor::query()->with('profession:id,name');

        ContractorScope::applyToContractors($query, $this->user);

        // `null` = بلا حدّ (super-admin بلا تحديد) — فلا قيد على المقارّ.
        if ($officeIds !== null) {
            $query->whereHas('assignments', fn ($q) => $q->whereIn('office_id', $officeIds));
        }

        return $query->orderBy('name')->orderBy('id')->get();
    }

    /**
     * تواريخ الاستثناءات داخل المدى — تفصيل تقرير العامل.
     *
     * ⚠️ **المعروض منها ما دخل الحساب وحده**: يومٌ سُجِّل ثم صار عطلةً لا يُعدّ في
     *    الأرقام، فعرضه هنا يُظهر تناقضاً بين التفصيل والعمود. ولذلك تُقرأ حدوده
     *    من أيام الصفوف المعروضة لا من جدول الحضور مباشرة.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<string, array{date:string, status:string, color:string, office:string}>
     */
    public function exceptionDates(array $rows): array
    {
        $contractor = $this->subject();

        if (! $contractor || $rows === []) {
            return [];
        }

        $statuses = AttendanceStatus::query()->get(['id', 'name', 'color'])->keyBy('id');
        $calendar = array_flip($this->report()->calendar());
        $spans    = array_map(fn ($row) => [$row['started_on'], $row['ended_on'], $row['office_name']], $rows);
        $out      = [];

        AttendanceDay::query()
            ->where('attendable_type', Contractor::class)
            ->where('attendable_id', $contractor->getKey())
            ->between($this->from, $this->to)
            ->orderBy('date')
            ->get()
            ->each(function (AttendanceDay $day) use (&$out, $statuses, $calendar, $spans) {
                $key = $day->date->toDateString();

                if (! isset($calendar[$key])) {
                    return; // جمعة أو عطلة — خارج الحساب فخارج التفصيل
                }

                foreach ($spans as [$from, $to, $office]) {
                    if ($key >= $from && ($to === null || $key <= $to)) {
                        $out[$key] = [
                            'date'   => $key,
                            'status' => $statuses[$day->status_id]->name ?? '—',
                            'color'  => $statuses[$day->status_id]->color ?? '#a1a1aa',
                            'office' => $office,
                        ];

                        return;
                    }
                }
            });

        ksort($out);

        return $out;
    }

    /**
     * سطر الفلتر في رأس التقرير المطبوع.
     *
     * ⚠️ **تقريرٌ بلا سياق فلتره رقمٌ بلا معنى** — ومَن يطبع التقرير قد يرسله لغيره.
     *
     * @return array<int, array{0:string, 1:string}>
     */
    public function describe(): array
    {
        $out = [[__('home.ct_rep_period'), __('home.ct_rep_period_label', [
            'from' => LocalTime::date($this->from),
            'to'   => LocalTime::date($this->to),
        ])]];

        $out[] = [__('home.ct_rep_governorate'), $this->governorateNames()];

        if ($this->level === 'office') {
            $out[] = [__('home.ct_rep_office'), $this->officeId
                ? (Office::whereKey($this->officeId)->value('name') ?? '—')
                : __('home.ct_worker_all_offices')];
        }

        if ($this->level === 'contractor') {
            $out[] = [__('home.ct_rep_contractor'), $this->subject()?->name ?? '—'];
        }

        return $out;
    }

    private function governorateNames(): string
    {
        $ids = $this->governorateIds;

        if ($ids === []) {
            return __('home.ct_rep_all_governorates');
        }

        $names = Governorate::whereIn('id', $ids)->orderBy('order')->pluck('name')->all();

        return $names === [] ? '—' : implode(' · ', $names);
    }

    /** تصفية خيارات منسدلةٍ طويلة — يقرؤها منتقي المقر والعامل في الشاشة. */
    public static function filterOptions(iterable $options, string $term, int|string|null $selected = null): array
    {
        $needle = ArabicText::normalize($term);
        $out    = [];

        foreach ($options as $option) {
            $matches = $needle === ''
                || str_contains(ArabicText::normalize($option->name), $needle)
                // ⚠️ المختار يبقى ولو لم يطابق — وإلا بدا للمستخدم أنه ضاع أو انتقل صامتاً.
                || (int) $option->id === (int) $selected;

            if ($matches) {
                $out[] = [
                    'id'    => $option->id,
                    'label' => $option->short_name ?? $option->name,
                    'title' => $option->name,
                ];
            }
        }

        return $out;
    }

    public static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** معرّفٌ من الرابط: رقمٌ خالص وحده — «12x» بالتحويل المجرّد يصير ١٢. */
    private static function id(mixed $value): ?int
    {
        return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array<int,int> */
    private static function ids(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $part) => self::id(trim($part)),
            explode(',', $value)
        )));
    }
}
