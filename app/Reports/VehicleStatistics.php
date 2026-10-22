<?php

namespace App\Reports;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Support\VehicleTransactionPeriods as Periods;
use Illuminate\Support\Collection;

/**
 * تقرير «إحصائيات السيارات» — المصدر الواحد للأرقام: الشاشة والـPDF والـExcel يقرؤون منه.
 *
 * صفٌّ لكل سيارة تحت محافظتها: المتوسطات اليومية الثلاثة + الانتقالات (العادية والفورية وإجماليهما) ومعاملات التوثيق في السنة
 * + معاملات السنة السابقة، وإجمالي لكل محافظة وإجمالي عام.
 *
 * ⚠️ إجمالي السنة = مجموع صفوفها (إجمالي فترة + شهور) — نفس قاعدة VehicleTransactionPeriods.
 * ⚠️ المتوسطات لا تُجمع في صفوف الإجمالي: مجموع متوسطات رقمٌ بلا معنى.
 * ⚠️ السنة الجارية لا تُقارَن بالسابقة (قرار المستخدمة 2026-10-15): تسعة أشهر بجوار سنة كاملة تُقرأ هبوطاً.
 * ⚠️ «لا رقم» (null) غير «صفر»: سيارة لم يُسجَّل لها شيء تظهر «—» لا 0.
 */
class VehicleStatistics
{
    private ?array $rowsCache = null;

    /**
     * أعمدة الترتيب (قائمة بيضاء) — **داخل كل محافظة**: صفوف الإجمالي مقسومة بالمحافظة،
     * فترتيبٌ عابر لها يبعثر السيارات ويُفقد الإجماليات معناها. ⚠️ «—» (null) آخراً في الاتجاهين.
     */
    public const SORTABLE = ['name', 'avg_tx', 'avg_support', 'avg_trips', 'trips', 'instant', 'trips_all', 'tx', 'tx_prev'];

    private function __construct(
        private ?array $allowedGovIds,
        private array $govIds,
        private int $year,
        private string $sort = '',
        private string $dir = 'asc',
    ) {}

    /** @param ?array $allowedGovIds null = بلا حدّ (super-admin) · [] = لا يرى شيئاً */
    public static function make(?array $allowedGovIds, array $filters): self
    {
        $govIds = collect($filters['governorateIds'] ?? [])
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->values()->all();

        $sort = $filters['sort'] ?? '';
        $sort = is_string($sort) && in_array($sort, self::SORTABLE, true) ? $sort : '';
        $dir  = ($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return new self($allowedGovIds, $govIds, self::sanitizeYear($filters['year'] ?? null), $sort, $dir);
    }

    /** سنة من الرابط/الـsession: رقمٌ داخل المدى وإلا السنة الجارية */
    public static function sanitizeYear(mixed $year): int
    {
        $current = VehicleColumns::currentYear();

        if (is_scalar($year) && ctype_digit((string) $year)) {
            $y = (int) $year;
            if ($y >= Periods::FIRST_YEAR && $y <= $current) {
                return $y;
            }
        }

        return $current;
    }

    /** @return int[] السنوات المتاحة في الفلتر، الأحدث أولاً */
    public static function years(): array
    {
        return range(VehicleColumns::currentYear(), Periods::FIRST_YEAR);
    }

    public function year(): int
    {
        return $this->year;
    }

    /** السنة لم تكتمل بعد → «حتى الآن» ولا عمود مقارنة */
    public function isPartial(): bool
    {
        return $this->year >= VehicleColumns::currentYear();
    }

    /**
     * @return Collection<int, array{governorate: Governorate, vehicles: array, totals: array}>
     *         vehicles[] = ['vehicle' => Vehicle, 'trips' => ?float, 'instant' => ?float, 'tripsAll' => ?float, 'tx' => ?float, 'txPrev' => ?float]
     *         (trips = الانتقالات العادية · instant = الفورية · tripsAll = مجموعهما)
     */
    public function groups(): Collection
    {
        if ($this->rowsCache !== null) {
            return collect($this->rowsCache);
        }

        $vehicles = Vehicle::query()
            ->when($this->allowedGovIds !== null, fn ($q) => $q->whereIn('governorate_id', $this->allowedGovIds))
            ->when($this->govIds, fn ($q) => $q->whereIn('governorate_id', $this->govIds))
            ->with('governorate:id,name,order')
            ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
            ->orderBy('governorate_id')
            ->tap(fn ($q) => \App\Support\NaturalSort::orderBy($q, 'vehicles.name'))
            ->get(['id', 'governorate_id', 'name', 'avg_daily_transactions', 'avg_daily_support', 'avg_daily_trips']);

        $sums = $this->sums($vehicles->pluck('id')->all());
        $trips   = VehicleColumns::tripsTypeId();
        $instant = VehicleColumns::instantTripsTypeId();

        $groups = [];
        foreach ($vehicles->groupBy('governorate_id') as $govVehicles) {
            $rows = $govVehicles->map(function (Vehicle $v) use ($sums, $trips, $instant) {
                $regular = $trips ? ($sums[$v->id][$trips][$this->year] ?? null) : null;
                $fast    = $instant ? ($sums[$v->id][$instant][$this->year] ?? null) : null;

                return [
                    'vehicle'  => $v,
                    'trips'    => $regular,
                    'instant'  => $fast,
                    'tripsAll' => self::sumOrNull([$regular, $fast]),
                    'tx'       => $sums[$v->id][Periods::STAT_TYPE_ID][$this->year] ?? null,
                    'txPrev'   => $sums[$v->id][Periods::STAT_TYPE_ID][$this->year - 1] ?? null,
                ];
            })->values()->all();

            $rows = $this->sortRows($rows);

            $groups[] = [
                'governorate' => $govVehicles->first()->governorate,
                'vehicles'    => $rows,
                'totals'      => self::totals($rows),
            ];
        }

        $this->rowsCache = $groups;

        return collect($groups);
    }

    /** ترتيب سيارات محافظة واحدة بالعمود المختار — بلا عمود يبقى ترتيب الاسم من الاستعلام */
    private function sortRows(array $rows): array
    {
        if ($this->sort === '') {
            return $rows;
        }

        $value = fn (array $r) => match ($this->sort) {
            'name'        => $r['vehicle']->name,
            'avg_tx'      => $r['vehicle']->avg_daily_transactions,
            'avg_support' => $r['vehicle']->avg_daily_support,
            'avg_trips'   => $r['vehicle']->avg_daily_trips,
            'trips'       => $r['trips'],
            'instant'     => $r['instant'],
            'trips_all'   => $r['tripsAll'],
            'tx'          => $r['tx'],
            'tx_prev'     => $r['txPrev'],
        };
        $sign = $this->dir === 'desc' ? -1 : 1;

        usort($rows, function ($a, $b) use ($value, $sign) {
            [$va, $vb] = [$value($a), $value($b)];

            // ما لم يُسجَّل آخراً في الاتجاهين — «الأعلى» لا يبدأ بسيارات بلا أرقام
            if ($va === null || $vb === null) {
                return (($va === null) <=> ($vb === null)) ?: strnatcmp($a['vehicle']->name, $b['vehicle']->name);
            }

            return $sign * (is_string($va) ? strnatcmp($va, $vb) : $va <=> $vb)
                ?: strnatcmp($a['vehicle']->name, $b['vehicle']->name);
        });

        return $rows;
    }

    /** الإجمالي العام */
    public function grandTotals(): array
    {
        return self::totals($this->groups()->flatMap(fn ($g) => $g['vehicles'])->all());
    }

    /** مجموع عمود — null إن لم يكن في العمود رقمٌ واحد (فيظهر «—» لا 0) */
    private static function totals(array $rows): array
    {
        $sum = fn (string $key) => self::sumOrNull(array_column($rows, $key));

        return [
            'trips' => $sum('trips'), 'instant' => $sum('instant'), 'tripsAll' => $sum('tripsAll'),
            'tx'    => $sum('tx'),    'txPrev'  => $sum('txPrev'),
        ];
    }

    /** مجموع ما سُجِّل وحده — null إن لم يُسجَّل شيء («—» غير 0) */
    public static function sumOrNull(array $values): ?float
    {
        $values = array_filter($values, fn ($v) => $v !== null);

        return $values ? (float) array_sum($values) : null;
    }

    /**
     * استعلام تجميعي واحد: sums[vehicle][type][year] = مجموع الصفوف.
     *
     * @param int[] $vehicleIds
     */
    private function sums(array $vehicleIds): array
    {
        if (! $vehicleIds) {
            return [];
        }

        $types = array_filter([Periods::STAT_TYPE_ID, VehicleColumns::tripsTypeId(), VehicleColumns::instantTripsTypeId()]);

        $rows = VehicleStat::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereIn('stat_type_id', $types)
            ->whereIn('year', [$this->year, $this->year - 1])
            ->selectRaw('vehicle_id, stat_type_id, year, SUM(value) as total')
            ->groupBy('vehicle_id', 'stat_type_id', 'year')
            ->get();

        $sums = [];
        foreach ($rows as $r) {
            $sums[$r->vehicle_id][$r->stat_type_id][(int) $r->year] = (float) $r->total;
        }

        return $sums;
    }
}
