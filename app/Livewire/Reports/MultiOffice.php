<?php

namespace App\Livewire\Reports;

use App\Models\BuffetService;
use App\Models\CleanlinessContract;
use App\Models\ConnectionType;
use App\Models\ContractualStatus;
use App\Models\DisabilitieAccess;
use App\Models\DocumentPhotocopyingService;
use App\Models\FireSafety;
use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\MicrofilmOption;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\StructuralCondition;
use App\Models\WorkingHour;
use App\Models\WorkSystem;
use App\Exports\OfficesExport;
use App\Reports\OfficeColumns;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير بحث متقدم')]
class MultiOffice extends Component
{
    use WithPagination;

    /** أقصى عدد مقرات لتقرير الـ PDF الشامل (تقرير مقارنة) — الأعداد الأكبر تُصدَّر Excel */
    private const MAX_PDF_OFFICES = 150;

    /** أقصى عدد أعمدة لتقرير الـ PDF المخصّص (A4 خط 10pt: ≤8 عمودي · 9-12 عرضي · >12 → Excel) */
    private const MAX_CUSTOM_PDF_COLS = 12;

    public bool $showAdvanced = false;

    /** المحددات المُطبَّقة فعلياً (snapshot وقت الضغط على "بحث") — الاستعلام يقرأ منها فقط */
    public array $applied = [];
    public bool $hasSearched = false;

    /** أعمدة التقرير المخصّص المختارة (تُملأ تلقائياً عند البحث = ثابتة + فلاتر مستخدَمة) */
    public array $selectedColumns = [];

    // ── الفلاتر الأساسية (Step 1) ──
    public array $governorateIds = [];
    public array $officeIds = [];
    public array $typeIds = [];
    public array $locationIds = [];
    public ?string $establishedFrom = null;
    public ?string $establishedTo = null;

    public array $workSystemIds = [];
    public array $workingHoursIds = [];
    public array $workingDays = [];
    public ?string $mechanizationFrom = null;
    public ?string $mechanizationTo = null;

    public array $contractualStatusIds = [];
    public string $districtCourt = '';
    public array $connectionTypeIds = [];

    // ── الفلاتر المتقدمة — نظام المتابعة (Steps 2 و 3) ──
    // المجموعة 3: الخدمات والتجهيزات
    public array $microfilmIds = [];
    public array $disabilitiesAccessIds = [];
    public array $fireSafetyIds = [];
    public array $photocopyingIds = [];
    public array $buffetIds = [];
    public array $cleanlinessContractIds = [];

    // المجموعة 4: الأنظمة التقنية
    public string $braille = '';            // ثنائي (متاح/غير متاح) → single
    public array $queueSystem = [];
    public array $cameras = [];
    public array $electricityMeterType = [];
    public string $electricityMeterDebt = '';   // نعم/لا → single
    public array $waterMeterType = [];
    public string $waterMeterDebt = '';         // نعم/لا → single

    // المجموعة 5: التقييم والجودة
    public array $cleanlinessRating = [];
    public array $archiveRating = [];
    public array $scheduleCommitment = [];
    public array $citizenCommitment = [];
    public array $structuralConditionIds = [];

    // المجموعة 6: المتابعة والزيارات
    public bool $neverVisited = false;
    public ?int $notVisitedMonths = null;

    /** كل أسماء خصائص الفلاتر — تُستخدم في snapshot الـ "بحث" والتصفير */
    protected array $filterKeys = [
        'governorateIds', 'officeIds', 'typeIds', 'locationIds',
        'establishedFrom', 'establishedTo',
        'workSystemIds', 'workingHoursIds', 'workingDays',
        'mechanizationFrom', 'mechanizationTo',
        'contractualStatusIds', 'districtCourt', 'connectionTypeIds',
        'microfilmIds', 'disabilitiesAccessIds', 'fireSafetyIds',
        'photocopyingIds', 'buffetIds', 'cleanlinessContractIds',
        'braille', 'queueSystem', 'cameras',
        'electricityMeterType', 'electricityMeterDebt',
        'waterMeterType', 'waterMeterDebt',
        'cleanlinessRating', 'archiveRating',
        'scheduleCommitment', 'citizenCommitment', 'structuralConditionIds',
        'neverVisited', 'notVisitedMonths',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
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

        // أعمدة التقرير المخصّص الافتراضية: الثابتة + الأعمدة المقابلة للفلاتر المستخدَمة
        $this->selectedColumns = OfficeColumns::defaultKeysForFilters($snapshot);

        $this->resetPage();
    }

    /** عند تغيير المحافظات: إزالة المقرات المختارة التي خرجت من النطاق */
    public function updatedGovernorateIds(): void
    {
        if (empty($this->officeIds)) {
            return;
        }

        $valid = $this->scopedOffices($this->allowedGovIds())->pluck('id')->all();
        $this->officeIds = array_values(array_intersect($this->officeIds, $valid));
        // عرض dropdown المقر يُحدَّث تلقائياً عبر تغيّر wire:key (md5 لقائمة المقرات)
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

    /** مجموعة المقرات المطابقة (بكل العلاقات) — للتصدير، بلا ترقيم صفحات */
    protected function exportOffices()
    {
        return $this->buildQuery($this->allowedGovIds())
            ->with([
                'locationDescription', 'workSystem', 'workingHour',
                'contractualStatus',
                'MicrofilmOption', 'DisabilitieAccess', 'FireSafety',
                'DocumentPhotocopyingService', 'BuffetService', 'CleanlinessContract',
                'brokenDevices.deviceType',
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
            new OfficesExport($this->exportOffices()),
            'offices-report-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $ids = $this->buildQuery($this->allowedGovIds())->pluck('id')->all();

        // حد أقصى لتقرير الـ PDF (تقرير مقارنة — الأعداد الكبيرة تُصدَّر Excel)
        if (count($ids) > self::MAX_PDF_OFFICES) {
            Flux::toast(variant: 'warning', text: __('home.report_pdf_max_exceeded', ['max' => self::MAX_PDF_OFFICES]));
            return;
        }

        // خزّن معرّفات نتائج البحث ثم افتح التقرير في تاب جديدة (inline)
        session(['report_office_ids' => $ids]);
        $this->js("window.open('" . route('reports.multi-office.pdf') . "', '_blank')");
    }

    /**
     * الأعمدة المتاحة للتقرير المخصّص = الثابتة + المقابلة للفلاتر المستخدَمة.
     * المنتقي لا يعرض غيرها (طلب: لا تُضاف فلاتر لم يُبحث بها).
     *
     * @return array<string>
     */
    protected function availableCustomColumns(): array
    {
        return OfficeColumns::defaultKeysForFilters($this->applied);
    }

    /** الأعمدة النهائية للتصدير المخصّص: الثابتة دائماً + المختارة، ضمن المتاح، بترتيب الكتالوج */
    protected function resolvedCustomKeys(): array
    {
        $available = $this->availableCustomColumns();
        $fixed     = OfficeColumns::fixedKeys();

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
            new OfficesExport($this->exportOffices(), $this->resolvedCustomKeys()),
            'offices-custom-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportCustomPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        // أعمدة الـ PDF تستبعد النصوص الطويلة (Excel فقط)
        $catalog = OfficeColumns::all();
        $keys    = array_values(array_filter(
            $this->resolvedCustomKeys(),
            fn ($key) => ! ($catalog[$key]['excelOnly'] ?? false)
        ));

        // حد الأعمدة على A4 (خط 10pt ثابت) — الأكثر يُصدَّر Excel
        if (count($keys) > self::MAX_CUSTOM_PDF_COLS) {
            Flux::toast(variant: 'warning', text: __('home.report_custom_pdf_cols_exceeded', ['max' => self::MAX_CUSTOM_PDF_COLS]));
            return;
        }

        $ids = $this->buildQuery($this->allowedGovIds())->pluck('id')->all();

        session([
            'report_office_ids'     => $ids,
            'report_custom_columns' => $keys,
        ]);
        $this->js("window.open('" . route('reports.multi-office.custom-pdf') . "', '_blank')");
    }

    /** يبني الاستعلام من المحددات المُطبَّقة ($applied) فقط */
    protected function buildQuery(?array $allowedGovIds)
    {
        $f = $this->applied;

        return Office::query()
            ->with(['governorate', 'officeType', 'connectionType', 'structuralCondition'])
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            // ── أساسية ──
            ->when($f['governorateIds'] ?? [], fn ($q) => $q->whereIn('governorate_id', $f['governorateIds']))
            ->when($f['officeIds'] ?? [], fn ($q) => $q->whereIn('id', $f['officeIds']))
            ->when($f['typeIds'] ?? [], fn ($q) => $q->whereIn('type_id', $f['typeIds']))
            ->when($f['locationIds'] ?? [], fn ($q) => $q->whereIn('location_description_id', $f['locationIds']))
            ->when($f['establishedFrom'] ?? null, fn ($q) => $q->whereDate('established_at', '>=', $f['establishedFrom']))
            ->when($f['establishedTo'] ?? null, fn ($q) => $q->whereDate('established_at', '<=', $f['establishedTo']))
            ->when($f['workSystemIds'] ?? [], fn ($q) => $q->whereIn('work_system_id', $f['workSystemIds']))
            ->when($f['workingHoursIds'] ?? [], fn ($q) => $q->whereIn('working_hours_id', $f['workingHoursIds']))
            ->when($f['workingDays'] ?? [], fn ($q) => $q->whereIn('working_days', $f['workingDays']))
            ->when($f['mechanizationFrom'] ?? null, fn ($q) => $q->whereDate('mechanization_at', '>=', $f['mechanizationFrom']))
            ->when($f['mechanizationTo'] ?? null, fn ($q) => $q->whereDate('mechanization_at', '<=', $f['mechanizationTo']))
            ->when($f['contractualStatusIds'] ?? [], fn ($q) => $q->whereIn('contractual_status_id', $f['contractualStatusIds']))
            ->when($f['districtCourt'] ?? '', fn ($q) => $q->where('district_court', 'like', "%{$f['districtCourt']}%"))
            ->when($f['connectionTypeIds'] ?? [], fn ($q) => $q->whereIn('connection_type_id', $f['connectionTypeIds']))
            // ── متقدمة: المجموعة 3 ──
            ->when($f['microfilmIds'] ?? [], fn ($q) => $q->whereIn('microfilm_option_id', $f['microfilmIds']))
            ->when($f['disabilitiesAccessIds'] ?? [], fn ($q) => $q->whereIn('disabilities_access_id', $f['disabilitiesAccessIds']))
            ->when($f['fireSafetyIds'] ?? [], fn ($q) => $q->whereIn('fire_safety_id', $f['fireSafetyIds']))
            ->when($f['photocopyingIds'] ?? [], fn ($q) => $q->whereIn('document_photocopying_service_id', $f['photocopyingIds']))
            ->when($f['buffetIds'] ?? [], fn ($q) => $q->whereIn('buffet_service_id', $f['buffetIds']))
            ->when($f['cleanlinessContractIds'] ?? [], fn ($q) => $q->whereIn('cleanliness_contract_id', $f['cleanlinessContractIds']))
            // ── متقدمة: المجموعة 4 ──
            ->when($f['braille'] ?? '', fn ($q) => $q->where('Braille_sign_device', $f['braille']))
            ->when($f['queueSystem'] ?? [], fn ($q) => $q->whereIn('queue_management_system', $f['queueSystem']))
            ->when($f['cameras'] ?? [], fn ($q) => $q->whereIn('surveillance_cameras', $f['cameras']))
            ->when($f['electricityMeterType'] ?? [], fn ($q) => $q->whereIn('electricity_meter_type', $f['electricityMeterType']))
            ->when($f['electricityMeterDebt'] ?? '', fn ($q) => $q->where('electricity_meter_debt', $f['electricityMeterDebt']))
            ->when($f['waterMeterType'] ?? [], fn ($q) => $q->whereIn('water_meter_type', $f['waterMeterType']))
            ->when($f['waterMeterDebt'] ?? '', fn ($q) => $q->where('water_meter_debt', $f['waterMeterDebt']))
            // ── متقدمة: المجموعة 5 ──
            ->when($f['cleanlinessRating'] ?? [], fn ($q) => $q->whereIn('cleanliness_rating', $f['cleanlinessRating']))
            ->when($f['archiveRating'] ?? [], fn ($q) => $q->whereIn('archive_rating', $f['archiveRating']))
            ->when($f['scheduleCommitment'] ?? [], fn ($q) => $q->whereIn('work_schedule_commitment', $f['scheduleCommitment']))
            ->when($f['citizenCommitment'] ?? [], fn ($q) => $q->whereIn('citizen_treatment_commitment', $f['citizenCommitment']))
            ->when($f['structuralConditionIds'] ?? [], fn ($q) => $q->whereIn('structural_condition_id', $f['structuralConditionIds']))
            // ── متقدمة: المجموعة 6 ──
            ->when($f['neverVisited'] ?? false, fn ($q) => $q->whereNull('visited_at'))
            ->when($f['notVisitedMonths'] ?? null, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('visited_at')
                ->orWhere('visited_at', '<', now()->subMonths($f['notVisitedMonths']))
            ))
            ->orderBy('name');
    }

    /** استعلام مقرات مقيّد بنطاق الصلاحيات + المحافظات المختارة حالياً (لخيارات المقر) */
    protected function scopedOffices(?array $allowedGovIds)
    {
        return Office::query()
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

        // النتائج تُحسب فقط بعد الضغط على "بحث"
        $offices = $this->hasSearched
            ? $this->buildQuery($allowedGovIds)->paginate(15)
            : new LengthAwarePaginator([], 0, 15, 1, ['path' => Paginator::resolveCurrentPath()]);

        // خيارات المقرات (مقيّدة بالنطاق + المحافظات المختارة حالياً) — للمقارنة بين مقرات محددة
        $officeOptions = $this->scopedOffices($allowedGovIds)
            ->orderBy('name')->get(['id', 'name']);

        // أعمدة التقرير المخصّص — مجمّعة حسب القسم للمنتقي (بعد البحث فقط)
        $customColumnGroups = [];
        if ($this->hasSearched) {
            $catalog = OfficeColumns::all();
            $fixed   = OfficeColumns::fixedKeys();
            foreach ($this->availableCustomColumns() as $key) {
                $def = $catalog[$key];
                $customColumnGroups[$def['group']][] = [
                    'key'   => $key,
                    'label' => $def['label'],
                    'fixed' => in_array($key, $fixed, true),
                ];
            }
        }

        return view('livewire.reports.multi-office', [
            'customColumnGroups' => $customColumnGroups,
            'offices'              => $offices,
            'officeOptions'        => $officeOptions,
            'governorates'         => $governorates,
            'officeTypes'          => OfficeType::orderBy('name')->get(),
            'locations'            => LocationDescription::orderBy('name')->get(),
            'workSystems'          => WorkSystem::orderBy('name')->get(),
            'workingHoursOptions'  => WorkingHour::orderBy('name')->get(),
            'contractualStatuses'  => ContractualStatus::orderBy('name')->get(),
            'connections'          => ConnectionType::orderBy('name')->get(),
            'microfilmOptions'     => MicrofilmOption::orderBy('name')->get(),
            'disabilitiesAccess'   => DisabilitieAccess::orderBy('name')->get(),
            'fireSafetyOptions'    => FireSafety::orderBy('name')->get(),
            'photocopyingOptions'  => DocumentPhotocopyingService::orderBy('name')->get(),
            'buffetOptions'        => BuffetService::orderBy('name')->get(),
            'cleanlinessContracts' => CleanlinessContract::orderBy('name')->get(),
            'structuralConditions' => StructuralCondition::orderBy('name')->get(),
        ]);
    }
}
