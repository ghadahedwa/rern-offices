<?php

namespace App\Livewire\Vehicles\StatTab;

use App\Models\StatType;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Support\VehicleTransactionPeriods as Periods;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Documentation extends Component
{
    use WithPagination;

    public Vehicle $vehicle;

    // المتوسطات اليومية (قيمة واحدة لكل منها على جدول السيارات)
    public string $avgDaily   = '';   // المعاملات — avg_daily_transactions
    public string $avgSupport = '';   // الدعم — avg_daily_support
    public string $avgTrips   = '';   // الانتقال — avg_daily_trips

    // ظهور الـ modals (مربوطة بـ Alpine عبر $wire)
    public bool $showForm   = false;
    public bool $showAvg    = false;
    public bool $showDelete = false;

    // فلاتر مستقلة لكل نوع
    public array $filterYear  = []; // [typeId => '']
    public array $filterMonth = []; // [typeId => '']

    // حالة الـ popup (إضافة/تعديل)
    public ?int $editingId = null;     // null = إضافة | رقم = تعديل
    public ?int $formTypeId = null;
    public string $formName      = '';
    public string $formPeriod    = '';  // yearly · monthly · transactions (قاعدة VehicleTransactionPeriods)
    public string $formValueType = 'count';
    public string $formYear  = '';
    public string $formMonth = '';
    public string $formValue = '';

    // حالة حذف
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    /** ترتيب الأقسام في التاب: الانتقالات العادية ← الفورية ← معاملات التوثيق ← النماذج والحوافظ */
    public const GROUP_KEYS = ['vehicle_trips', 'vehicle_instant_trips', 'transactions', 'forms_folders'];

    // تسميات مخصّصة لصفحة السيارة (بدل $type->name المشترك مع المقرات)
    private const LABELS = [
        1 => 'vehicle_stat_transactions',
        2 => 'vehicle_stat_form_sales',
        3 => 'vehicle_stat_folder_sales',
    ];
    private const GROUP_LABELS = [
        'vehicle_trips'         => 'vehicle_stat_trips',
        'vehicle_instant_trips' => 'vehicle_stat_instant_trips',
    ];

    public const MONTHS = [
        1 => 'يناير',  2 => 'فبراير', 3 => 'مارس',    4 => 'أبريل',
        5 => 'مايو',   6 => 'يونيو',  7 => 'يوليو',   8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر',11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    public function mount(Vehicle $vehicle): void
    {
        $this->vehicle = $vehicle;
        $this->fillAverages();

        foreach (StatType::whereIn('group_key', self::GROUP_KEYS)->pluck('id') as $id) {
            $this->filterYear[$id]  = '';
            $this->filterMonth[$id] = '';
        }
    }

    /** الأقسام بترتيب العرض — يقرؤها التاب وشاشة العرض معاً */
    public static function orderedTypes()
    {
        return StatType::whereIn('group_key', self::GROUP_KEYS)->orderBy('order')->get()
            ->sortBy(fn ($t) => array_search($t->group_key, self::GROUP_KEYS, true))
            ->values();
    }

    public static function labelFor(StatType $type): string
    {
        $key = self::LABELS[$type->id] ?? self::GROUP_LABELS[$type->group_key] ?? null;

        return $key ? __('home.' . $key) : $type->name;
    }

    // ── المتوسطات اليومية ──
    private function fillAverages(): void
    {
        $str = fn ($v) => $v !== null ? (string) (0 + $v) : '';

        $this->avgDaily   = $str($this->vehicle->avg_daily_transactions);
        $this->avgSupport = $str($this->vehicle->avg_daily_support);
        $this->avgTrips   = $str($this->vehicle->avg_daily_trips);
    }

    public function openAvg(): void
    {
        $this->resetValidation();
        $this->fillAverages();
        $this->showAvg = true;
    }

    public function saveAvg(): void
    {
        $this->validate(
            [
                'avgDaily'   => 'nullable|integer|min:0',
                'avgSupport' => 'nullable|integer|min:0',
                'avgTrips'   => 'nullable|integer|min:0',
            ],
            [],
            [
                'avgDaily'   => __('home.average_daily_transactions'),
                'avgSupport' => __('home.average_daily_support'),
                'avgTrips'   => __('home.average_daily_trips'),
            ]
        );

        $val = fn (string $v) => $v !== '' ? (int) $v : null;

        $this->vehicle->update([
            'avg_daily_transactions' => $val($this->avgDaily),
            'avg_daily_support'      => $val($this->avgSupport),
            'avg_daily_trips'        => $val($this->avgTrips),
        ]);

        $this->showAvg = false;
        Flux::toast(variant: 'success', text: __('home.vstat_averages_saved'));
    }

    public function updatedFilterYear($value, $key): void
    {
        $this->resetPage('p' . $key);
    }

    public function updatedFilterMonth($value, $key): void
    {
        $this->resetPage('p' . $key);
    }

    /** تغيير السنة في فورم المعاملات يغيّر خيارات الشهر — المختار القديم قد لا يكون منها */
    public function updatedFormYear(): void
    {
        if ($this->formPeriod === 'transactions' && ! array_key_exists($this->formMonth, $this->formMonthOptions())) {
            $this->formMonth = '';
        }
    }

    /** خيارات منسدلة الشهر في الفورم ([] = لا منسدلة) */
    public function formMonthOptions(): array
    {
        if ($this->formPeriod === 'monthly') {
            return self::MONTHS;
        }

        if ($this->formPeriod === 'transactions' && ctype_digit($this->formYear)) {
            return Periods::monthOptions((int) $this->formYear, self::MONTHS);
        }

        return [];
    }

    public function openAdd(int $typeId): void
    {
        $type = StatType::whereIn('group_key', self::GROUP_KEYS)->findOrFail($typeId);

        $this->editingId = null;
        $this->fillMeta($type);
        $this->formYear  = '';
        $this->formMonth = '';
        $this->formValue = '';
        $this->resetValidation();

        $this->showForm = true;
    }

    public function openEdit(int $statId): void
    {
        $stat = VehicleStat::where('vehicle_id', $this->vehicle->id)->findOrFail($statId);

        $this->editingId = $statId;
        $this->fillMeta($stat->statType);
        $this->formYear  = (string) $stat->year;
        $this->formMonth = $stat->month !== null
            ? (string) $stat->month
            : ($this->formPeriod === 'transactions' && Periods::isTransition((int) $stat->year) ? Periods::PARTIAL : '');
        $this->formValue = (string) (0 + $stat->value);
        $this->resetValidation();

        $this->showForm = true;
    }

    private function fillMeta(StatType $type): void
    {
        $this->formTypeId    = $type->id;
        $this->formName      = self::labelFor($type);
        $this->formPeriod    = $type->id === Periods::STAT_TYPE_ID ? 'transactions' : $type->period;
        $this->formValueType = $type->value_type;
    }

    public function save(): void
    {
        $type = StatType::whereIn('group_key', self::GROUP_KEYS)->findOrFail($this->formTypeId);
        $isTransactions = $type->id === Periods::STAT_TYPE_ID;

        $rules = [
            'formYear'  => 'required|integer|min:' . ($isTransactions ? Periods::FIRST_YEAR : 2000),
            'formValue' => 'required|numeric|min:0',
        ];
        $monthOptions = $this->formMonthOptions();
        if ($monthOptions) {
            $rules['formMonth'] = 'required|in:' . implode(',', array_keys($monthOptions));
        }

        $this->validate($rules, [], [
            'formYear'  => 'السنة',
            'formMonth' => 'الشهر',
            'formValue' => $type->value_type === 'amount' ? 'المبلغ' : 'العدد',
        ]);

        $month = match (true) {
            $monthOptions === []                 => null,
            $this->formMonth === Periods::PARTIAL => null,
            default                              => (int) $this->formMonth,
        };

        // حارس ثانٍ للقاعدة بعد التحقق — لا يُكتب رقمٌ يغطّي شهراً مسجَّلاً بفترةٍ أخرى
        if ($isTransactions && ! Periods::allows((int) $this->formYear, $month)) {
            $this->addError('formMonth', __('home.vstat_period_not_allowed'));
            return;
        }

        $dup = VehicleStat::where('vehicle_id', $this->vehicle->id)
            ->where('stat_type_id', $type->id)
            ->where('year', $this->formYear)
            ->when($month === null, fn ($q) => $q->whereNull('month'), fn ($q) => $q->where('month', $month))
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($dup) {
            $this->addError('formYear', 'هذه الفترة مسجلة بالفعل');
            return;
        }

        $data = [
            'vehicle_id'   => $this->vehicle->id,
            'stat_type_id' => $type->id,
            'year'         => $this->formYear,
            'month'        => $month,
            'value'        => $this->formValue,
        ];

        if ($this->editingId) {
            VehicleStat::where('vehicle_id', $this->vehicle->id)->where('id', $this->editingId)->update($data);
            $msg = 'تم التعديل';
        } else {
            VehicleStat::create($data);
            $msg = 'تمت الإضافة';
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: $msg);
    }

    public function askDelete(int $statId): void
    {
        $stat = VehicleStat::where('vehicle_id', $this->vehicle->id)->with('statType')->findOrFail($statId);

        $this->deletingId    = $statId;
        $this->deletingLabel = self::labelFor($stat->statType) . ' — ' . self::periodLabel($stat);
        $this->showDelete    = true;
    }

    /** «٢٠٢٥» · «٢٠٢٦ / يناير – سبتمبر» · «٢٠٢٦ / أكتوبر» */
    public static function periodLabel(VehicleStat $stat): string
    {
        if ($stat->stat_type_id === Periods::STAT_TYPE_ID) {
            return Periods::isYearly($stat->year)
                ? (string) $stat->year
                : $stat->year . ' / ' . Periods::label($stat->year, $stat->month, self::MONTHS);
        }

        return $stat->year . ($stat->month ? ' / ' . (self::MONTHS[$stat->month] ?? $stat->month) : '');
    }

    public function deleteRow(): void
    {
        if ($this->deletingId) {
            VehicleStat::where('vehicle_id', $this->vehicle->id)->where('id', $this->deletingId)->delete();
            $this->deletingId = null;
            $this->deletingLabel = '';
            $this->showDelete = false;
            Flux::toast(variant: 'success', text: 'تم الحذف');
        }
    }

    /**
     * إجمالي كل سنة لمعاملات التوثيق = مجموع صفوفها (الإجمالي + الشهور).
     *
     * @return array<int, float> سنة => إجمالي، الأحدث أولاً
     */
    public static function transactionYearTotals(Vehicle $vehicle, ?string $year = null): array
    {
        return VehicleStat::where('vehicle_id', $vehicle->id)
            ->where('stat_type_id', Periods::STAT_TYPE_ID)
            ->when($year, fn ($q, $y) => $q->where('year', $y))
            ->selectRaw('year, SUM(value) as total')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('total', 'year')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    public function render()
    {
        $statTypes = self::orderedTypes();

        $existing = [];
        foreach ($statTypes as $type) {
            $existing[$type->id] = VehicleStat::where('vehicle_id', $this->vehicle->id)
                ->where('stat_type_id', $type->id)
                ->when($this->filterYear[$type->id] ?? '', fn($q, $y) => $q->where('year', $y))
                ->when($this->filterMonth[$type->id] ?? '', fn($q, $m) => $q->where('month', $m))
                ->orderByDesc('year')
                ->orderByRaw('month IS NULL')   // في سنة التحوّل: الشهور أولاً ثم «يناير–سبتمبر»
                ->orderByDesc('month')
                ->paginate(8, ['*'], 'p' . $type->id);
        }

        return view('livewire.vehicles.stat-tab.documentation', [
            'statTypes'    => $statTypes,
            'existing'     => $existing,
            'yearTotals'   => self::transactionYearTotals($this->vehicle, ($this->filterYear[Periods::STAT_TYPE_ID] ?? '') ?: null),
            'years'        => range((int) date('Y'), Periods::FIRST_YEAR),
            'months'       => self::MONTHS,
            'monthOptions' => $this->formMonthOptions(),
        ]);
    }
}
