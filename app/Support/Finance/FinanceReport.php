<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceMonthItem;
use App\Models\FinanceUnit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * تقارير الشئون المالية (المرحلة ٥ · نموذج وافقت عليه المستخدمة 2026-11-17) — المصدر الواحد للشاشة
 * والـPDF والـExcel، فلا يخرج ملفٌّ بأرقامٍ تخالف الشاشة.
 *
 *   - **تقرير وحدة**: شهور الفترة صفوف × الحسابات الأربعة (اتصالات: إيراد · منصرف · رصيد) + أوامر الدفع.
 *   - **التقرير الإجمالي**: الوحدات صفوف × الحسابات مجموع الفترة (اتصالات: رصيد آخر الفترة) + «الشهور المُدخَلة».
 *   - ومعهما العناصر الثمانية لأبنية المحاكم مجموعةً في الفترة.
 *
 * ⚠️ **كل ما يصل من الرابط يُفحص هنا** (`fromInput`): النوع والفترة بقائمةٍ بيضاء، والشهور داخل
 *    [أول شهر · الشهر الجاري]، والمقلوب يُعدَل — القيمة التالفة تسقط إلى الافتراضي لا إلى خطأ.
 * ⚠️ أرصدة اتصالات من `TelecomLedger` لا من هنا — والرصيد «—» لوحدةٍ بلا افتتاحي، لا صفر.
 */
class FinanceReport
{
    public const KINDS   = ['unit', 'total'];
    public const PERIODS = ['month', 'range', 'year'];

    /** المفاتيح بترتيب أعمدة التقرير — ترتيب الحسابات نفسه */
    protected ?Collection $accounts = null;

    public function __construct(
        public readonly string $kind,
        public readonly string $period,
        public readonly ?FinanceUnit $unit,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    /** الافتراضي: آخر شهرٍ منتهٍ (أو أول شهر إن لم ينتهِ شهر بعد) */
    public static function defaultMonth(): CarbonImmutable
    {
        return FinanceCalendar::currentMonth()->subMonth()->max(FinanceCalendar::firstMonth());
    }

    /** شهور التقرير المتاحة — من أول شهر حتى الشهر الجاري */
    public static function availableMonths(): array
    {
        $months = [];
        for ($m = FinanceCalendar::firstMonth(); $m <= FinanceCalendar::currentMonth(); $m = $m->addMonth()) {
            $months[] = $m;
        }

        return $months;
    }

    /**
     * يبني التقرير من مدخلات الرابط/الشاشة. null لتقرير وحدةٍ بلا وحدةٍ صالحة.
     *
     * @param  array{kind?:mixed, unit?:mixed, period?:mixed, month?:mixed, from?:mixed, to?:mixed, year?:mixed}  $in
     */
    public static function fromInput(array $in): ?self
    {
        $kind   = in_array($in['kind'] ?? null, self::KINDS, true) ? $in['kind'] : 'unit';
        $period = in_array($in['period'] ?? null, self::PERIODS, true) ? $in['period'] : 'month';

        $unit = null;
        if ($kind === 'unit') {
            $id   = (string) ($in['unit'] ?? '');
            $unit = ctype_digit($id) ? FinanceUnit::find((int) $id) : null;
            if ($unit === null) {
                return null;
            }
        }

        [$from, $to] = match ($period) {
            'year'  => self::yearRange($in['year'] ?? null),
            'range' => self::range(self::monthOf($in['from'] ?? null), self::monthOf($in['to'] ?? null)),
            default => [($m = self::monthOf($in['month'] ?? null) ?? self::defaultMonth()), $m],
        };

        return new self($kind, $period, $unit, $from, $to);
    }

    /** `Y-m` داخل المتاح — وإلا null */
    protected static function monthOf(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        $month = CarbonImmutable::createFromFormat('!Y-m', $value) ?: null;

        return $month && $month->betweenIncluded(FinanceCalendar::firstMonth(), FinanceCalendar::currentMonth()) ? $month : null;
    }

    protected static function range(?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $from ??= FinanceCalendar::firstMonth();
        $to   ??= FinanceCalendar::currentMonth();

        return $from->greaterThan($to) ? [$to, $from] : [$from, $to];
    }

    /** السنة المالية حتى الشهر الجاري — والتالفة السنة الجارية */
    protected static function yearRange(mixed $year): array
    {
        $year = is_numeric($year) && FinanceCalendar::isValidFiscalYear((int) $year)
            ? (int) $year
            : FinanceCalendar::fiscalYearOf(FinanceCalendar::currentMonth());
        $months = FinanceCalendar::monthsOf($year);

        return [$months[0], min(end($months), FinanceCalendar::currentMonth())];
    }

    public function fiscalYear(): int
    {
        return FinanceCalendar::fiscalYearOf($this->from);
    }

    /** @return CarbonImmutable[] */
    public function months(): array
    {
        $months = [];
        for ($m = $this->from; $m <= $this->to; $m = $m->addMonth()) {
            $months[] = $m;
        }

        return $months;
    }

    public function periodLabel(): string
    {
        return $this->from->equalTo($this->to)
            ? FinanceCalendar::monthLabel($this->from)
            : __('home.fin_year_range', ['from' => FinanceCalendar::monthLabel($this->from), 'to' => FinanceCalendar::monthLabel($this->to)]);
    }

    public function title(): string
    {
        return $this->kind === 'unit'
            ? __('home.fin_rep_unit_title', ['unit' => $this->unit->name])
            : __('home.fin_rep_total_title');
    }

    /** مدخلات الرابط — لرابط الـPDF ولإعادة بناء التقرير */
    public function query(): array
    {
        return array_filter([
            'kind'   => $this->kind,
            'unit'   => $this->unit?->id,
            'period' => $this->period,
            'month'  => $this->period === 'month' ? $this->from->format('Y-m') : null,
            'from'   => $this->period === 'range' ? $this->from->format('Y-m') : null,
            'to'     => $this->period === 'range' ? $this->to->format('Y-m') : null,
            'year'   => $this->period === 'year' ? $this->fiscalYear() : null,
        ], fn ($v) => $v !== null);
    }

    public function accounts(): Collection
    {
        return $this->accounts ??= FinanceAccount::unitAccounts();
    }

    /**
     * شهور الفترة المسجَّلة — للوحدة أو للوحدات كلها.
     * ⚠️ نطاقٌ مفتوح على الأعلى (`< الشهر التالي`) لا `whereBetween`: العمود المصبوب `date` يُكتب
     *    `Y-m-d 00:00:00` على sqlite فيسقط آخر شهر.
     */
    protected function records(): Collection
    {
        return FinanceMonth::query()
            ->whereIn('finance_account_id', $this->accounts()->pluck('id'))
            ->whereNotNull('finance_unit_id')
            ->when($this->unit, fn ($q) => $q->where('finance_unit_id', $this->unit->id))
            ->where('month', '>=', $this->from->toDateString())
            ->where('month', '<', $this->to->addMonth()->toDateString())
            ->withCount('paymentOrders')
            ->get();
    }

    /**
     * رصيد اتصالات للوحدة في الفترة: الأشهر بأرصدتها، والمرحَّل إليها، والرصيد في آخرها.
     * ⚠️ وحدةٌ بلا افتتاحي: «—» (null) لا صفر.
     */
    protected function telecom(int $unitId): array
    {
        $account = $this->accounts()->firstWhere('key', FinanceAccount::TELECOM);
        $chain   = TelecomLedger::chain($account, $unitId);
        $opening = $account->openingFor($unitId)?->amount;

        $before = collect($chain)->filter(fn ($r) => $r['month']->lessThan($this->from))->last();
        $inside = collect($chain)->filter(fn ($r) => $r['month']->betweenIncluded($this->from, $this->to));
        $start  = $before['balance'] ?? $opening;

        return [
            'chain'   => $chain,
            'start'   => $start,
            'end'     => $inside->isNotEmpty() ? $inside->last()['balance'] : $start,
            'income'  => $inside->sum('income'),
            'expense' => $inside->sum('expense'),
            'entered' => $inside->isNotEmpty(),
        ];
    }

    /**
     * تقرير الوحدة: صفٌّ لكل شهر — `cells[حساب]` المبلغ أو null، و`telecom` الإيراد والمنصرف والرصيد أو null.
     */
    public function unitReport(): array
    {
        $records  = $this->records()->groupBy(fn (FinanceMonth $m) => $m->month->format('Y-m'));
        $telecom  = $this->telecom($this->unit->id);
        $current  = FinanceCalendar::currentMonth();
        $accounts = $this->accounts();

        $rows = [];
        foreach ($this->months() as $month) {
            $key     = $month->format('Y-m');
            $entries = ($records->get($key) ?? collect())->keyBy('finance_account_id');

            $cells = [];
            foreach ($accounts as $account) {
                $cells[$account->key] = $entries->get($account->id)?->amount;
            }

            $tel = $telecom['chain'][$key] ?? null;

            $rows[] = [
                'month'   => $month,
                'label'   => FinanceCalendar::monthLabel($month),
                'ended'   => $month->lessThan($current),
                'cells'   => $cells,
                'telecom' => $tel ? ['income' => $tel['income'], 'expense' => $tel['expense'], 'balance' => $tel['balance']] : null,
                'orders'  => $entries->sum('payment_orders_count'),
            ];
        }

        $rows = collect($rows);

        return [
            'rows'   => $rows->all(),
            'totals' => [
                'cells'   => $accounts->mapWithKeys(fn ($a) => [$a->key => self::sumOrNull($rows->pluck('cells.'.$a->key))])->all(),
                'telecom' => ['income' => $telecom['income'], 'expense' => $telecom['expense'], 'balance' => $telecom['end']],
                'orders'  => $rows->sum('orders'),
            ],
            'start'  => $telecom['start'],
        ];
    }

    /**
     * التقرير الإجمالي: صفٌّ لكل وحدة (المفعَّلة، والمعطَّلة التي لها شهرٌ في الفترة) — مجموع الفترة لكل حساب،
     * واتصالات رصيد آخر الفترة، و«المُدخَل» عددُ (شهر × حساب) المسجَّل من المطلوب.
     */
    public function totalReport(): array
    {
        $records  = $this->records();
        $accounts = $this->accounts();
        $required = count($this->months()) * $accounts->count();

        $units = FinanceUnit::ordered()->get()
            ->filter(fn (FinanceUnit $u) => $u->is_active || $records->contains('finance_unit_id', $u->id))
            ->values();

        $rows = [];
        foreach ($units as $unit) {
            $mine    = $records->where('finance_unit_id', $unit->id);
            $telecom = $this->telecom($unit->id);

            $cells = [];
            foreach ($accounts as $account) {
                $own = $mine->where('finance_account_id', $account->id);
                $cells[$account->key] = $own->isNotEmpty() ? $own->sum('amount') : null;
            }

            $rows[] = [
                'unit'     => $unit,
                'cells'    => $cells,
                'telecom'  => ['income' => $telecom['income'], 'expense' => $telecom['expense'], 'balance' => $telecom['end']],
                'entered'  => $mine->count(),
                'required' => $required,
            ];
        }

        $rows = collect($rows);

        return [
            'rows'   => $rows->all(),
            'totals' => [
                'cells'   => $accounts->mapWithKeys(fn ($a) => [$a->key => self::sumOrNull($rows->pluck('cells.'.$a->key))])->all(),
                'telecom' => [
                    'income'  => $rows->sum('telecom.income'),
                    'expense' => $rows->sum('telecom.expense'),
                    'balance' => self::sumOrNull($rows->pluck('telecom.balance')),
                ],
            ],
        ];
    }

    /** العناصر الثمانية لأبنية المحاكم مجموعةً في الفترة — [الاسم => المبلغ] بترتيب العناصر */
    public function items(): array
    {
        $account = $this->accounts()->firstWhere('key', FinanceAccount::COURT_BUILDINGS);

        return FinanceMonthItem::query()
            ->join('finance_months', 'finance_months.id', '=', 'finance_month_items.finance_month_id')
            ->join('court_building_items', 'court_building_items.id', '=', 'finance_month_items.court_building_item_id')
            ->where('finance_months.finance_account_id', $account->id)
            ->when($this->unit, fn ($q) => $q->where('finance_months.finance_unit_id', $this->unit->id))
            ->where('finance_months.month', '>=', $this->from->toDateString())
            ->where('finance_months.month', '<', $this->to->addMonth()->toDateString())
            ->groupBy('court_building_items.id', 'court_building_items.name', 'court_building_items.order')
            ->orderBy('court_building_items.order')
            ->orderBy('court_building_items.id')
            ->selectRaw('court_building_items.name as name, SUM(finance_month_items.amount) as total')
            ->pluck('total', 'name')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * ورقة «البيانات» في Excel: صفٌّ لكل (وحدة × حساب × شهر) مسجَّل — الشكل الصالح لجدول محوري.
     * [الوحدة · الحساب · الشهر · المبلغ · الإيراد · الرصيد · أوامر الدفع]
     */
    public function dataRows(): array
    {
        $accounts = $this->accounts()->keyBy('id');
        $units    = FinanceUnit::pluck('name', 'id');
        $chains   = [];

        return $this->records()
            ->sortBy([['finance_unit_id', 'asc'], ['month', 'asc']])
            ->map(function (FinanceMonth $m) use ($accounts, $units, &$chains) {
                $account = $accounts[$m->finance_account_id];
                $key     = $m->month->format('Y-m');
                $balance = null;

                if ($account->isLedger()) {
                    $chains[$m->finance_unit_id] ??= TelecomLedger::chain($account, $m->finance_unit_id);
                    $balance = $chains[$m->finance_unit_id][$key]['balance'] ?? null;
                }

                return [
                    $units[$m->finance_unit_id] ?? '',
                    __('home.fin_tab_'.$account->key),
                    FinanceCalendar::monthLabel(CarbonImmutable::parse($m->month)),
                    (int) $m->amount,
                    $account->isLedger() ? (int) $m->income : null,
                    $balance,
                    (int) $m->payment_orders_count,
                ];
            })
            ->values()
            ->all();
    }

    /** المجموع — أو null إن لم يُسجَّل شيء (فـ«—» لا صفر) */
    public static function sumOrNull(Collection $values): ?int
    {
        $known = $values->filter(fn ($v) => $v !== null);

        return $known->isEmpty() ? null : (int) $known->sum();
    }
}
