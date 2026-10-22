<?php

namespace App\Reports;

use App\Livewire\Vehicles\StatTab\Documentation;
use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Support\VehicleTransactionPeriods as Periods;
use Illuminate\Support\Collection;

/**
 * تقرير «إحصائيات السيارات التفصيلية» — المصدر الواحد للأرقام: الشاشة والـPDF والـExcel يقرؤون منه.
 *
 * مؤشرٌ واحد (المعاملات · العادية · الفورية · إجمالي الانتقالات · النماذج · الحوافظ) في جدولٍ متقاطع:
 *   الصفوف: محافظة، أو سيارة تحت محافظتها (بإجمالي لكل محافظة)
 *   الأعمدة: السنوات (سنوي) أو شهور سنة واحدة (شهري) + عمود الإجمالي
 *
 * ⚠️ مؤشرٌ واحد في المرة: ١٢ شهراً × ستة مؤشرات لا تدخل شاشة ولا صفحة.
 * ⚠️ معاملات التوثيق تتبع `VehicleTransactionPeriods`: حتى ٢٠٢٥ رقمٌ سنوي فلا شهري لها (`isUnavailable()`)،
 *    وفي ٢٠٢٦ عمود «يناير–سبتمبر» (month = NULL، مفتاحه 0) قبل أكتوبر.
 * ⚠️ «—» (null) غير صفر، و«سُجِّل لها» = عدد السيارات التي لها رقم في أعمدة الصفّ — إجمالي محافظةٍ
 *    ناقصة البيانات يبدو منخفضاً، والعمود يفرّق بين الحالتين.
 * ⚠️ المتوسطات اليومية خارج هذا التقرير: رقمٌ واحد على السيارة لا سلسلة زمنية.
 *
 * **الشكل الرأسي** (`isVertical()`، قرار المستخدمة 2026-10-22): سيارةٌ واحدة (فلتر السيارة) أو محافظةٌ واحدة
 * بتجميع المحافظة — الفترات صفوفٌ من فوق لتحت، والأعمدة **عائلة المؤشر** (`FAMILIES`): الانتقالات الثلاثة معاً،
 * أو النماذج والحوافظ، أو المعاملات وحدها. مع صفٍّ واحد لا معنى لجدولٍ عريض.
 */
class VehicleStatisticsBreakdown
{
    public const METRICS = ['tx', 'trips', 'instant', 'trips_all', 'forms', 'folders'];
    public const LEVELS  = ['governorate', 'vehicle'];
    public const PERIODS = ['yearly', 'monthly'];

    /** أعمدة الشكل الرأسي لكل مؤشر: المؤشر وأقرباؤه جنباً إلى جنب */
    public const FAMILIES = [
        'tx'        => ['tx'],
        'trips'     => ['trips', 'instant', 'trips_all'],
        'instant'   => ['trips', 'instant', 'trips_all'],
        'trips_all' => ['trips', 'instant', 'trips_all'],
        'forms'     => ['forms', 'folders'],
        'folders'   => ['forms', 'folders'],
    ];

    /** مفتاح عمود «يناير–سبتمبر» لمعاملات ٢٠٢٦ (الصفّ بـ month = NULL) */
    public const PARTIAL_COLUMN = 0;

    private ?array $cache = null;
    private ?Collection $vehiclesCache = null;

    private function __construct(
        private ?array $allowedGovIds,
        private array $govIds,
        private string $metric,
        private string $level,
        private string $period,
        private int $year,
        private ?int $vehicleId = null,
        private ?int $yearFrom = null,
        private ?int $yearTo = null,
        private ?int $monthFrom = null,
        private ?int $monthTo = null,
    ) {}

    /** @param ?array $allowedGovIds null = بلا حدّ (super-admin) · [] = لا يرى شيئاً */
    public static function make(?array $allowedGovIds, array $filters): self
    {
        $govIds = collect(is_array($filters['governorateIds'] ?? null) ? $filters['governorateIds'] : [])
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->values()->all();

        $pick = fn (string $key, array $allowed) => is_string($filters[$key] ?? null) && in_array($filters[$key], $allowed, true)
            ? $filters[$key] : $allowed[0];

        return new self(
            $allowedGovIds,
            $govIds,
            $pick('metric', self::METRICS),
            $pick('level', self::LEVELS),
            $pick('period', self::PERIODS),
            VehicleStatistics::sanitizeYear($filters['year'] ?? null),
            is_scalar($filters['vehicleId'] ?? null) && ctype_digit((string) $filters['vehicleId']) ? (int) $filters['vehicleId'] : null,
            ...self::sanitizeYearRange($filters['yearFrom'] ?? null, $filters['yearTo'] ?? null),
            ...self::sanitizeMonthRange($filters['monthFrom'] ?? null, $filters['monthTo'] ?? null),
        );
    }

    /**
     * مدى الشهور في الشهري «من شهر … إلى شهر» (طلب المستخدمة 2026-10-22) — الشهر نفسه في الاثنين = شهرٌ واحد.
     * الفارغ أو التالف يُهمَل (كل الشهور)، والمقلوب يُعدَل.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private static function sanitizeMonthRange(mixed $from, mixed $to): array
    {
        $clean = fn ($m) => is_scalar($m) && ctype_digit((string) $m) && (int) $m >= 1 && (int) $m <= 12 ? (int) $m : null;

        [$from, $to] = [$clean($from), $clean($to)];

        return $from !== null && $to !== null && $from > $to ? [$to, $from] : [$from, $to];
    }

    /** @return array{0: int, 1: int} مدى الشهور المختار (بلا اختيار: السنة كلها) */
    public function monthRange(): array
    {
        return [$this->monthFrom ?? 1, $this->monthTo ?? 12];
    }

    /** المدى يمسّ «يناير–سبتمبر» المجمَّع لمعاملات ٢٠٢٦ — عموده لا يُقسَم شهوراً فيظهر كاملاً باسمه */
    private function includesPartialColumn(): bool
    {
        return $this->metric === 'tx' && Periods::isTransition($this->year)
            && $this->monthRange()[0] < Periods::MONTHLY_FROM_MONTH;
    }

    /**
     * مدى المقارنة السنوية «من سنة … إلى سنة» (طلب المستخدمة 2026-10-22). الفارغ أو التالف أو خارج
     * المدى المسموح يُهمَل (فيعود التلقائي: من أول سنة فيها رقم · إلى السنة الجارية)، والمقلوب يُعدَل.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private static function sanitizeYearRange(mixed $from, mixed $to): array
    {
        $current = VehicleColumns::currentYear();
        $clean   = fn ($y) => is_scalar($y) && ctype_digit((string) $y) && (int) $y >= Periods::FIRST_YEAR && (int) $y <= $current
            ? (int) $y : null;

        [$from, $to] = [$clean($from), $clean($to)];

        return $from !== null && $to !== null && $from > $to ? [$to, $from] : [$from, $to];
    }

    /** @return array{0: int, 1: int} مدى السنوات المعروض في السنوي — المختار أو التلقائي */
    public function yearRange(): array
    {
        $current = VehicleColumns::currentYear();
        $from    = $this->yearFrom ?? ($this->load()['firstYear'] ?? $current);
        $to      = $this->yearTo ?? $current;

        // «إلى» وحدها قبل أول سنةٍ فيها رقم: يبدأ المدى منها لا بعدها
        return [min($from, $to), $to];
    }

    public function metric(): string { return $this->metric; }
    public function level(): string  { return $this->level; }
    public function period(): string { return $this->period; }
    public function year(): int      { return $this->year; }

    public function metricLabel(): string
    {
        return __('home.vbd_metric_' . $this->metric);
    }

    /** معاملات التوثيق شهرياً لسنةٍ سُجِّلت رقماً واحداً — لا جدول */
    public function isUnavailable(): bool
    {
        return $this->metric === 'tx' && $this->period === 'monthly' && Periods::isYearly($this->year);
    }

    /** وصف الفترة المعروضة — رأس الـPDF واسم الملف */
    public function periodLabel(): string
    {
        if ($this->period === 'monthly') {
            [$from, $to] = $this->monthRange();
            $months = [$from, $to] === [1, 12] ? ''
                : ' — ' . ($from === $to ? Documentation::MONTHS[$from] : Documentation::MONTHS[$from] . ' – ' . Documentation::MONTHS[$to]);

            return __('home.vbd_period_year', ['year' => $this->year]) . $months
                . ($this->year >= VehicleColumns::currentYear() ? ' (' . __('home.vstat_so_far') . ')' : '');
        }

        $years = array_keys($this->columns());

        return __('home.vbd_period_years', ['from' => min($years), 'to' => max($years)]);
    }

    /** سيارةٌ واحدة، أو محافظةٌ واحدة بتجميع المحافظة → الفترات صفوف والعائلة أعمدة */
    public function isVertical(): bool
    {
        if ($this->vehicleId !== null) {
            return true;
        }

        return $this->level === 'governorate' && $this->vehicles()->pluck('governorate_id')->unique()->count() === 1;
    }

    /** @return string[] المؤشرات المعروضة: عائلة المؤشر في الرأسي، والمؤشر وحده في الأفقي */
    public function series(): array
    {
        return $this->isVertical() ? self::FAMILIES[$this->metric] : [$this->metric];
    }

    /**
     * العرضي لإجمالي الانتقالات: تحت كل فترة **ثلاثة أعمدة** (عادية · فورية · إجمالي) — قرار المستخدمة
     * 2026-10-22. الجدول يعرض بها (١٠ شهور = ٣٠ عموداً) فيتمرّر داخل إطاره كأي جدولٍ متقاطع.
     *
     * @return string[] فارغة = عمودٌ واحد للفترة
     */
    public function splitSeries(): array
    {
        return ! $this->isVertical() && $this->metric === 'trips_all' ? self::FAMILIES['trips_all'] : [];
    }

    /** اسمٌ قصير لعمودٍ فرعي تحت الفترة */
    public static function subLabel(string $metric): string
    {
        return __('home.vbd_sub_' . $metric);
    }

    public static function seriesLabel(string $metric): string
    {
        return __($metric === 'trips_all' ? 'home.vbd_series_trips_all' : 'home.vbd_metric_' . $metric);
    }

    /** الموضوع في الشكل الرأسي: اسم السيارة أو المحافظة */
    public function subjectLabel(): string
    {
        $first = $this->vehicles()->first();

        return $this->vehicleId !== null ? ($first?->name ?? '') : ($first?->governorate?->name ?? '');
    }

    /** @return int[] معرّفات أنواع الإحصائية التي يقرؤها التقرير (اتحاد أنواع المؤشرات المعروضة) */
    public function typeIds(): array
    {
        return array_values(array_unique(array_merge(...array_map(fn ($m) => self::typesFor($m), $this->series()))));
    }

    /** @return int[] أنواع مؤشرٍ واحد */
    private static function typesFor(string $metric): array
    {
        return array_values(array_filter(match ($metric) {
            'tx'        => [Periods::STAT_TYPE_ID],
            'trips'     => [VehicleColumns::tripsTypeId()],
            'instant'   => [VehicleColumns::instantTripsTypeId()],
            'trips_all' => [VehicleColumns::tripsTypeId(), VehicleColumns::instantTripsTypeId()],
            'forms'     => [2],
            'folders'   => [3],
        }));
    }

    /**
     * أعمدة الجدول بترتيبها: مفتاح => عنوان.
     *   سنوي: من أول سنةٍ فيها رقمٌ للمؤشر (داخل النطاق) إلى السنة الجارية — لا أعمدة فارغة من ٢٠٢٠.
     *   شهري: شهور السنة، وفي السنة الجارية حتى الشهر الحالي؛ ومعاملات ٢٠٢٦ تبدأ بـ«يناير–سبتمبر».
     *
     * @return array<int, string>
     */
    public function columns(): array
    {
        $current = VehicleColumns::currentYear();

        if ($this->period === 'yearly') {
            [$from, $to] = $this->yearRange();

            $cols = [];
            foreach (range($from, $to) as $y) {
                $cols[$y] = $y >= $current ? $y . ' (' . __('home.vstat_so_far') . ')' : (string) $y;
            }

            return $cols;
        }

        if ($this->isUnavailable()) {
            return [];
        }

        $lastMonth = $this->year >= $current ? (int) now(\App\Support\LocalTime::timezone())->month : 12;
        [$fromMonth, $toMonth] = $this->monthRange();
        $months    = array_filter(range(1, $lastMonth), fn ($m) => $m >= $fromMonth && $m <= $toMonth);
        $cols      = [];

        if ($this->metric === 'tx' && Periods::isTransition($this->year)) {
            if ($this->includesPartialColumn()) {
                $cols[self::PARTIAL_COLUMN] = __('home.vstat_partial_2026');
            }
            $months = array_filter($months, fn ($m) => $m >= Periods::MONTHLY_FROM_MONTH);
        }

        foreach ($months as $m) {
            $cols[$m] = Documentation::MONTHS[$m];
        }

        return $cols;
    }

    /**
     * @return Collection<int, array{governorate: Governorate, vehicleRows: array, total: array}>
     *   vehicleRows[] = ['vehicle' => Vehicle, 'cells' => [col => ?float], 'total' => ?float]
     *   total         = ['cells' => [...], 'total' => ?float, 'vehicles' => int, 'reporting' => int]
     *   مستوى المحافظة يعرض total وحده صفّاً، ومستوى السيارة يعرض vehicleRows ثم total إجمالياً للمحافظة.
     */
    public function groups(): Collection
    {
        return collect($this->load()['groups']);
    }

    public function grandTotal(): array
    {
        $vehicleRows = $this->groups()->flatMap(fn ($g) => $g['vehicleRows'])->all();

        return self::aggregate($vehicleRows, array_keys($this->columns()));
    }

    /**
     * الشكل الرأسي: صفٌّ لكل فترة (مفاتيح `columns()` نفسها) × عمودٌ لكل مؤشرٍ في `series()` — مجموع السيارات المعروضة.
     *
     * @return array{rows: array<int, array{label: string, values: array<string, ?float>}>, total: array<string, ?float>, vehicles: int, reporting: int}
     */
    public function vertical(): array
    {
        $raw     = $this->load()['raw'];
        $ids     = $this->vehicles()->pluck('id')->all();
        $columns = $this->columns();
        $rows    = [];

        foreach ($columns as $col => $label) {
            $values = [];
            foreach ($this->series() as $m) {
                $values[$m] = VehicleStatistics::sumOrNull(array_merge([], ...array_map(
                    fn ($type) => array_map(fn ($id) => $raw[$type][$id][$col] ?? null, $ids),
                    self::typesFor($m)
                )));
            }
            $rows[$col] = ['label' => $label, 'values' => $values];
        }

        $total = [];
        foreach ($this->series() as $m) {
            $total[$m] = VehicleStatistics::sumOrNull(array_map(fn ($r) => $r['values'][$m], $rows));
        }

        // سُجِّل لها: سيارةٌ لها رقمٌ في أي مؤشرٍ معروض خلال الفترة
        $reporting = count(array_filter($ids, function ($id) use ($raw, $columns) {
            foreach ($raw as $byVehicle) {
                if (array_intersect_key($byVehicle[$id] ?? [], $columns)) {
                    return true;
                }
            }

            return false;
        }));

        return ['rows' => $rows, 'total' => $total, 'vehicles' => count($ids), 'reporting' => $reporting];
    }

    /**
     * صفوف البيانات الخام لورقة Excel الطويلة: [محافظة, سيارة, مؤشر, سنة, شهر, قيمة] —
     * لكل نوعٍ صفّه (إجمالي الانتقالات يخرج عادية وفورية منفصلتين، فيجمعهما الجدول المحوري).
     */
    public function dataRows(): array
    {
        if ($this->isUnavailable()) {
            return [];
        }

        $vehicles = $this->vehicles()->keyBy('id');
        $position = $vehicles->keys()->flip();   // ترتيب الشاشة: المحافظة ثم الاسم الطبيعي
        $labels   = [
            Periods::STAT_TYPE_ID                 => __('home.vbd_metric_tx'),
            VehicleColumns::tripsTypeId()         => __('home.vbd_metric_trips'),
            VehicleColumns::instantTripsTypeId()  => __('home.vbd_metric_instant'),
            2                                     => __('home.vbd_metric_forms'),
            3                                     => __('home.vbd_metric_folders'),
        ];

        return $this->statQuery($vehicles->keys()->all())
            ->get(['vehicle_id', 'stat_type_id', 'year', 'month', 'value'])
            ->sort(fn ($a, $b) => [$position[$a->vehicle_id], $a->year, $a->month ?? 0, $a->stat_type_id]
                              <=> [$position[$b->vehicle_id], $b->year, $b->month ?? 0, $b->stat_type_id])
            ->map(function ($s) use ($vehicles, $labels) {
                $v = $vehicles[$s->vehicle_id];
                $month = $s->month === null
                    ? (Periods::isTransition((int) $s->year) && $s->stat_type_id === Periods::STAT_TYPE_ID ? __('home.vstat_partial_2026') : __('home.vstat_full_year'))
                    : Documentation::MONTHS[(int) $s->month];

                return [$v->governorate?->name ?? '', $v->name, $labels[$s->stat_type_id] ?? '', (int) $s->year, $month, (float) $s->value];
            })
            ->values()->all();
    }

    // ───────────────────────────────────────────────

    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $vehicles = $this->vehicles();
        $ids      = $vehicles->pluck('id')->all();

        // أول سنة فيها رقمٌ للمؤشر — قبل `columns()` لأنها تقرؤها
        $firstYear = $ids && $this->period === 'yearly' && $this->yearFrom === null
            ? VehicleStat::whereIn('vehicle_id', $ids)->whereIn('stat_type_id', $this->typeIds())->min('year')
            : null;
        $this->cache = ['groups' => [], 'raw' => [], 'firstYear' => $firstYear !== null ? max((int) $firstYear, Periods::FIRST_YEAR) : null];

        $cols  = array_keys($this->columns());
        $this->cache['raw'] = $cols ? $this->cells($ids) : [];
        $cells = $this->cellsFor(self::typesFor($this->metric));

        // الأعمدة الفرعية (عادية · فورية · إجمالي) لكل مؤشرٍ منها
        $splitCells = [];
        foreach ($this->splitSeries() as $m) {
            $splitCells[$m] = $this->cellsFor(self::typesFor($m));
        }

        $groups = [];
        foreach ($vehicles->groupBy('governorate_id') as $govVehicles) {
            $vehicleRows = $govVehicles->map(function (Vehicle $v) use ($cells, $cols, $splitCells) {
                $pick = function (array $source) use ($v, $cols) {
                    $row = [];
                    foreach ($cols as $c) {
                        $row[$c] = $source[$v->id][$c] ?? null;
                    }

                    return ['cells' => $row, 'total' => VehicleStatistics::sumOrNull($row)];
                };

                return ['vehicle' => $v, ...$pick($cells), 'split' => array_map($pick, $splitCells)];
            })->values()->all();

            $groups[] = [
                'governorate' => $govVehicles->first()->governorate,
                'vehicleRows' => $vehicleRows,
                'total'       => self::aggregate($vehicleRows, $cols),
            ];
        }

        $this->cache['groups'] = $groups;

        return $this->cache;
    }

    /** إجمالي مجموعة صفوف سيارات: مجموع كل عمود + عدد السيارات ومَن سُجِّل له رقم */
    private static function aggregate(array $vehicleRows, array $cols): array
    {
        $sums = [];
        foreach ($cols as $c) {
            $sums[$c] = VehicleStatistics::sumOrNull(array_map(fn ($r) => $r['cells'][$c], $vehicleRows));
        }

        // الأعمدة الفرعية: مجموع كل عمودٍ فرعي عبر الصفوف
        $split = [];
        foreach (array_keys($vehicleRows[0]['split'] ?? []) as $m) {
            $cells = [];
            foreach ($cols as $c) {
                $cells[$c] = VehicleStatistics::sumOrNull(array_map(fn ($r) => $r['split'][$m]['cells'][$c], $vehicleRows));
            }
            $split[$m] = ['cells' => $cells, 'total' => VehicleStatistics::sumOrNull($cells)];
        }

        return [
            'cells'     => $sums,
            'split'     => $split,
            'total'     => VehicleStatistics::sumOrNull($sums),
            'vehicles'  => count($vehicleRows),
            'reporting' => count(array_filter($vehicleRows, fn ($r) => $r['total'] !== null)),
        ];
    }

    /** سيارات النطاق والفلتر — سيارةٌ مدسوسة من خارج النطاق لا تُرجع شيئاً */
    private function vehicles(): Collection
    {
        return $this->vehiclesCache ??= Vehicle::query()
            ->when($this->allowedGovIds !== null, fn ($q) => $q->whereIn('governorate_id', $this->allowedGovIds))
            ->when($this->govIds, fn ($q) => $q->whereIn('governorate_id', $this->govIds))
            ->when($this->vehicleId !== null, fn ($q) => $q->whereKey($this->vehicleId))
            ->with('governorate:id,name,order')
            ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
            ->orderBy('governorate_id')
            ->tap(fn ($q) => \App\Support\NaturalSort::orderBy($q, 'vehicles.name'))
            ->get(['id', 'governorate_id', 'name']);
    }

    /** إحصائيات المؤشر لهذه السيارات في فترة التقرير */
    private function statQuery(array $vehicleIds)
    {
        return VehicleStat::query()
            ->whereIn('vehicle_id', $vehicleIds ?: [0])
            ->whereIn('stat_type_id', $this->typeIds() ?: [0])
            ->when($this->period === 'monthly', fn ($q) => $q->where('year', $this->year)
                // داخل مدى الشهور (والمجمَّع «يناير–سبتمبر» إن مسّه المدى) — يقرؤه ملف البيانات الطويل أيضاً
                ->where(fn ($w) => $w->whereBetween('month', $this->monthRange())
                    ->when($this->includesPartialColumn(), fn ($w) => $w->orWhereNull('month'))))
            // السنوي: داخل المدى المعروض وحده (يقرؤه ملف البيانات الطويل أيضاً)
            ->when($this->period === 'yearly', fn ($q) => $q->whereBetween('year', $this->yearRange()));
    }

    /** cells[vehicle][عمود] لمؤشر — مجموع أنواعه (عادية + فورية لإجمالي الانتقالات) */
    private function cellsFor(array $types): array
    {
        $cells = [];
        foreach ($types as $type) {
            foreach ($this->cache['raw'][$type] ?? [] as $vehicleId => $cols) {
                foreach ($cols as $col => $value) {
                    $cells[$vehicleId][$col] = ($cells[$vehicleId][$col] ?? 0) + $value;
                }
            }
        }

        return $cells;
    }

    /** raw[نوع][vehicle][عمود] = مجموع — العمود سنةٌ أو شهرٌ (0 لـ«يناير–سبتمبر») */
    private function cells(array $vehicleIds): array
    {
        if (! $vehicleIds) {
            return [];
        }

        $byMonth = $this->period === 'monthly';

        $rows = $this->statQuery($vehicleIds)
            ->selectRaw('stat_type_id, vehicle_id, ' . ($byMonth ? 'month' : 'year') . ' as col, SUM(value) as total')
            ->groupBy('stat_type_id', 'vehicle_id', $byMonth ? 'month' : 'year')
            ->get();

        $cells = [];
        foreach ($rows as $r) {
            $col = $r->col === null ? self::PARTIAL_COLUMN : (int) $r->col;
            $cells[$r->stat_type_id][$r->vehicle_id][$col] = (float) $r->total;
        }

        return $cells;
    }
}
