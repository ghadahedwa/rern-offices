<?php

namespace App\Livewire\Reports;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Models\VehicleWorkingHour;
use App\Models\VehicleWorkSystem;
use App\Exports\VehiclesExport;
use App\Reports\VehicleColumns;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير بحث السيارات')]
class MultiVehicle extends Component
{
    use WithPagination;

    /** أقصى عدد سيارات لتقرير الـ PDF الشامل (تقرير مقارنة) — الأعداد الأكبر تُصدَّر Excel */
    private const MAX_PDF_VEHICLES = 200;

    /** أقصى عدد أعمدة لتقرير الـ PDF المخصّص (A4 خط 10pt: ≤8 عمودي · 9-12 عرضي · >12 → Excel) */
    private const MAX_CUSTOM_PDF_COLS = 12;

    /** المحددات المُطبَّقة فعلياً (snapshot وقت الضغط على "بحث") — الاستعلام يقرأ منها فقط */
    public array $applied = [];
    public bool $hasSearched = false;

    /** أعمدة التقرير المخصّص المختارة (تُملأ تلقائياً عند البحث = ثابتة + فلاتر مستخدَمة) */
    public array $selectedColumns = [];

    /** كاش لإجمالي عدد خيارات كل فلتر متعدد (لاكتشاف "تحديد الكل" = بلا تقييد صفوف) */
    private ?array $optionTotalsCache = null;

    // ── الفلاتر ──
    public array $governorateIds = [];
    public array $vehicleIds = [];
    public array $typeIds = [];
    public array $workSystemIds = [];
    public array $workingHoursIds = [];
    public array $brandIds = [];
    public array $statuses = [];
    public ?int $manufactureYearFrom = null;
    public ?int $manufactureYearTo = null;
    public ?string $operatedAtFrom = null;
    public ?string $operatedAtTo = null;
    public ?string $licenseExpiryFrom = null;
    public ?string $licenseExpiryTo = null;
    public string $mobilityBag = '';
    public array $generatorStatus = [];
    public array $cameras = [];

    /** كل أسماء خصائص الفلاتر — تُستخدم في snapshot الـ "بحث" والتصفير */
    protected array $filterKeys = [
        'governorateIds', 'vehicleIds', 'typeIds', 'workSystemIds', 'workingHoursIds', 'brandIds', 'statuses',
        'manufactureYearFrom', 'manufactureYearTo', 'operatedAtFrom', 'operatedAtTo', 'licenseExpiryFrom', 'licenseExpiryTo',
        'mobilityBag', 'generatorStatus', 'cameras',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('vehicles.export'),
            403
        );
    }

    /** تطبيق المحددات وعرض النتائج */
    public function search(): void
    {
        $snapshot = [];
        foreach ($this->filterKeys as $key) {
            $snapshot[$key] = $this->$key;
        }
        $this->applied     = $snapshot;
        $this->hasSearched = true;

        $this->selectedColumns = VehicleColumns::defaultKeysForFilters($snapshot, $this->optionTotals());

        $this->resetPage();
    }

    /** عند تغيير المحافظات: إزالة السيارات المختارة التي خرجت من النطاق */
    public function updatedGovernorateIds(): void
    {
        if (empty($this->vehicleIds)) {
            return;
        }

        $valid = $this->scopedVehicles($this->allowedGovIds())->pluck('id')->all();
        $this->vehicleIds = array_values(array_intersect($this->vehicleIds, $valid));
    }

    /** المحافظات المسموح بها للمستخدم (null = super-admin يرى الكل) */
    protected function allowedGovIds(): ?array
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? null
            : $user->governorates()->pluck('governorates.id')->all();
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->applied         = [];
        $this->hasSearched     = false;
        $this->selectedColumns = [];
        $this->resetPage();
        $this->dispatch('filters-reset');
    }

    /** مجموعة السيارات المطابقة (بكل العلاقات) — للتصدير، بلا ترقيم صفحات */
    protected function exportVehicles()
    {
        return $this->buildQuery($this->allowedGovIds())
            ->with([
                'locations', 'brokenDevices.deviceType', 'statistics',
            ])
            ->get();
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        return Excel::download(
            new VehiclesExport($this->exportVehicles()),
            'vehicles-report-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $ids = $this->buildQuery($this->allowedGovIds())->pluck('id')->all();

        if (count($ids) > self::MAX_PDF_VEHICLES) {
            Flux::toast(variant: 'warning', text: __('home.report_pdf_max_exceeded', ['max' => self::MAX_PDF_VEHICLES]));
            return;
        }

        session(['report_vehicle_ids' => $ids]);
        $this->js("window.open('" . route('reports.multi-vehicle.pdf') . "', '_blank')");
    }

    /**
     * الأعمدة المتاحة في منتقي التقرير المخصّص = الثابتة + أعمدة الفلاتر المستخدَمة + الاختيارية.
     *
     * @return array<string>
     */
    protected function availableCustomColumns(): array
    {
        return VehicleColumns::customPickerKeys();
    }

    /** الأعمدة النهائية للتصدير المخصّص: الثابتة دائماً + المختارة، ضمن المتاح، بترتيب الكتالوج */
    protected function resolvedCustomKeys(): array
    {
        $available = $this->availableCustomColumns();
        $fixed     = VehicleColumns::fixedKeys();

        return array_values(array_filter(
            $available,
            fn ($key) => in_array($key, $fixed, true) || in_array($key, $this->selectedColumns, true)
        ));
    }

    public function exportCustomExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        return Excel::download(
            new VehiclesExport($this->exportVehicles(), $this->resolvedCustomKeys()),
            'vehicles-custom-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportCustomPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $catalog = VehicleColumns::all();
        $keys    = array_values(array_filter(
            $this->resolvedCustomKeys(),
            fn ($key) => ! ($catalog[$key]['excelOnly'] ?? false)
        ));

        if (count($keys) > self::MAX_CUSTOM_PDF_COLS) {
            Flux::toast(variant: 'warning', text: __('home.report_custom_pdf_cols_exceeded', ['max' => self::MAX_CUSTOM_PDF_COLS]));
            return;
        }

        $ids = $this->buildQuery($this->allowedGovIds())->pluck('id')->all();

        session([
            'report_vehicle_ids'    => $ids,
            'report_custom_columns' => $keys,
        ]);
        $this->js("window.open('" . route('reports.multi-vehicle.custom-pdf') . "', '_blank')");
    }

    /** إجمالي عدد خيارات كل فلتر متعدد (لاكتشاف "تحديد الكل") — memoized */
    protected function optionTotals(): array
    {
        if ($this->optionTotalsCache !== null) {
            return $this->optionTotalsCache;
        }

        $allowed = $this->allowedGovIds();

        return $this->optionTotalsCache = [
            'governorateIds'  => $allowed === null ? Governorate::count() : count($allowed),
            'vehicleIds'      => $this->scopedVehicles($allowed)->count(),
            'typeIds'         => VehicleType::count(),
            'workSystemIds'   => VehicleWorkSystem::count(),
            'workingHoursIds' => VehicleWorkingHour::count(),
            'brandIds'        => VehicleBrand::count(),
            'statuses'        => count(Vehicle::STATUSES),
            'generatorStatus' => 3,
            'cameras'         => 3,
        ];
    }

    /**
     * هل الفلتر المتعدد يقيّد الصفوف فعلاً؟
     * يقيّد فقط عند اختيار جزئي؛ "تحديد الكل" (أو لا شيء) = بلا تقييد.
     */
    protected function multiActive(string $key): bool
    {
        $selected = $this->applied[$key] ?? [];

        if (empty($selected)) {
            return false;
        }

        $total = $this->optionTotals()[$key] ?? PHP_INT_MAX;

        return count($selected) < $total;
    }

    /** يبني الاستعلام من المحددات المُطبَّقة ($applied) فقط */
    protected function buildQuery(?array $allowedGovIds)
    {
        $f = $this->applied;

        return Vehicle::query()
            ->with(['governorate', 'type', 'workSystem', 'workingHour', 'brand'])
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($this->multiActive('governorateIds'), fn ($q) => $q->whereIn('governorate_id', $f['governorateIds']))
            ->when($this->multiActive('vehicleIds'), fn ($q) => $q->whereIn('id', $f['vehicleIds']))
            ->when($this->multiActive('typeIds'), fn ($q) => $q->whereIn('type_id', $f['typeIds']))
            ->when($this->multiActive('workSystemIds'), fn ($q) => $q->whereIn('work_system_id', $f['workSystemIds']))
            ->when($this->multiActive('workingHoursIds'), fn ($q) => $q->whereIn('working_hours_id', $f['workingHoursIds']))
            ->when($this->multiActive('brandIds'), fn ($q) => $q->whereIn('brand_id', $f['brandIds']))
            ->when($this->multiActive('statuses'), fn ($q) => $q->whereIn('status', $f['statuses']))
            ->when($f['manufactureYearFrom'] ?? null, fn ($q) => $q->where('manufacture_year', '>=', $f['manufactureYearFrom']))
            ->when($f['manufactureYearTo'] ?? null, fn ($q) => $q->where('manufacture_year', '<=', $f['manufactureYearTo']))
            ->when($f['operatedAtFrom'] ?? null, fn ($q) => $q->whereDate('operated_at', '>=', $f['operatedAtFrom']))
            ->when($f['operatedAtTo'] ?? null, fn ($q) => $q->whereDate('operated_at', '<=', $f['operatedAtTo']))
            ->when($f['licenseExpiryFrom'] ?? null, fn ($q) => $q->whereDate('license_expiry_date', '>=', $f['licenseExpiryFrom']))
            ->when($f['licenseExpiryTo'] ?? null, fn ($q) => $q->whereDate('license_expiry_date', '<=', $f['licenseExpiryTo']))
            ->when($f['mobilityBag'] ?? '', fn ($q) => $q->where('mobility_bag', $f['mobilityBag']))
            ->when($this->multiActive('generatorStatus'), fn ($q) => $q->whereIn('generator_status', $f['generatorStatus']))
            ->when($this->multiActive('cameras'), fn ($q) => $q->whereIn('surveillance_cameras', $f['cameras']))
            ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
            ->orderBy('governorate_id')
            ->orderBy('name');
    }

    /** استعلام سيارات مقيّد بنطاق الصلاحيات + المحافظات المختارة حالياً (لخيارات المقارنة) */
    protected function scopedVehicles(?array $allowedGovIds)
    {
        return Vehicle::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($this->governorateIds, fn ($q) => $q->whereIn('governorate_id', $this->governorateIds));
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id')->all();

        $vehicles = $this->hasSearched
            ? $this->buildQuery($allowedGovIds)->paginate(15)
            : new LengthAwarePaginator([], 0, 15, 1, ['path' => Paginator::resolveCurrentPath()]);

        $vehicleOptions = $this->scopedVehicles($allowedGovIds)
            ->orderBy('name')->get(['id', 'name']);

        $customColumnGroups = [];
        if ($this->hasSearched) {
            $catalog    = VehicleColumns::all();
            $fixed      = VehicleColumns::fixedKeys();
            $extraLabel = __('home.report_custom_extra_group');

            foreach ($this->availableCustomColumns() as $key) {
                $def   = $catalog[$key];
                // pickerGroup: عمود يظهر تحت "بيانات إضافية" في المنتقي فقط (الـ group الأصلي يبقى للعناوين في التقرير الشامل)
                $group = ! empty($def['pickerGroup']) ? $extraLabel : $def['group'];
                $customColumnGroups[$group][] = [
                    'key'   => $key,
                    'label' => $def['label'],
                    'fixed' => in_array($key, $fixed, true),
                ];
            }

            // "بيانات إضافية" دائماً آخر قسم
            if (isset($customColumnGroups[$extraLabel])) {
                $extra = $customColumnGroups[$extraLabel];
                unset($customColumnGroups[$extraLabel]);
                $customColumnGroups[$extraLabel] = $extra;
            }
        }

        return view('livewire.reports.multi-vehicle', [
            'customColumnGroups' => $customColumnGroups,
            'vehicles'            => $vehicles,
            'vehicleOptions'      => $vehicleOptions,
            'governorates'        => $governorates,
            'vehicleTypes'        => VehicleType::orderBy('name')->get(),
            'workSystems'         => VehicleWorkSystem::orderBy('name')->get(),
            'workingHoursOptions' => VehicleWorkingHour::orderBy('name')->get(),
            'brands'              => VehicleBrand::orderBy('name')->get(),
        ]);
    }
}
