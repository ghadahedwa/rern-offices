<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleLicensesExport;
use App\Models\Governorate;
use App\Models\Vehicle;
use App\Support\TableSort;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير تراخيص السيارات')]
class VehicleLicenses extends Component
{
    // دورة الضغط والرابط من الـtrait، والترتيب نفسه في rowsFor()
    use \App\Livewire\Concerns\WithTableSorting;

    // ── الفلاتر ──
    public array $governorateIds = [];
    /** نطاق التنبيه: عرض ما ينتهي خلال X يوم (بالإضافة للمنتهية) — فارغ = كل السيارات */
    public ?int $withinDays = 60;

    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['governorateIds', 'withinDays'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('vehicles.export'),
            403
        );
    }

    public function search(): void
    {
        $this->applied = [
            'governorateIds' => $this->governorateIds,
            'withinDays'     => $this->withinDays !== null && $this->withinDays !== '' ? (int) $this->withinDays : null,
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->withinDays  = 60;
        $this->applied     = [];
        $this->hasSearched = false;
        $this->dispatch('filters-reset');
    }

    protected function allowedGovIds(): ?array
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? null
            : $user->governorates()->pluck('governorates.id')->all();
    }

    /** عتبة "تنتهي قريباً": نطاق الفلتر المطبَّق إن وُجد، وإلا 30 يوماً (تُستدعى من الـ view أيضاً) */
    public function soonDays(): int
    {
        return $this->applied['withinDays'] ?? 30;
    }

    public function buildRows(?array $allowedGovIds)
    {
        return self::rowsFor($allowedGovIds, $this->filtersWithSort());
    }

    /**
     * قائمة السيارات — **المصدر الواحد للشاشة والـExcel والـPDF** (الـPDF كان يكرّر الاستعلام بنفسه).
     * الافتراضي: تاريخ انتهاء الترخيص (الأقرب/المنتهي أولاً، وبلا تاريخ آخراً) ثم الاسم.
     * فلتر withinDays: يعرض فقط ما انتهى أو ينتهي خلال X يوم (السيارات بلا تاريخ تُستبعد حينها).
     *
     * @param ?array $allowedGovIds null = بلا حدّ · [] = لا شيء
     */
    public static function rowsFor(?array $allowedGovIds, array $filters)
    {
        $govIds     = $filters['governorateIds'] ?? [];
        $withinDays = is_numeric($filters['withinDays'] ?? null) ? (int) $filters['withinDays'] : null;

        $query = Vehicle::query()
            ->with('governorate:id,name')
            ->when($allowedGovIds !== null, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
            ->when($withinDays !== null, fn ($q) => $q
                ->whereNotNull('license_expiry_date')
                ->whereDate('license_expiry_date', '<=', now()->addDays($withinDays)->toDateString())
            );

        return TableSort::apply(
            $query, (string) ($filters['sort'] ?? ''), (string) ($filters['dir'] ?? 'asc'), self::sortMap(),
            fn ($q) => $q->orderByRaw('license_expiry_date IS NULL, license_expiry_date ASC')->orderBy('name'),
            'vehicles.id', 'asc',
        )->get(['id', 'governorate_id', 'name', 'license_plate', 'license_expiry_date']);
    }

    /**
     * أعمدة الترتيب (قائمة بيضاء). تاريخ الانتهاء نفسه هو الترتيب الافتراضي — الضغطة الثالثة تعود إليه —
     * ولذلك ليس عموداً هنا: ترتيبه التنازلي يضع السيارات بلا تاريخ أولاً على MySQL.
     */
    public static function sortMap(): array
    {
        return [
            'governorate' => TableSort::related('governorates', 'order', 'vehicles', 'governorate_id'),
            'name'        => 'vehicles.name',
            'plate'       => 'vehicles.license_plate',
        ];
    }

    protected function sortableColumns(): array
    {
        return self::sortMap();
    }

    /** لا يُستدعى — الترتيب الافتراضي في rowsFor() */
    protected function defaultOrder(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query;
    }

    protected function filtersWithSort(): array
    {
        return $this->applied + ['sort' => $this->sortBy, 'dir' => $this->sortDir];
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        return Excel::download(
            new VehicleLicensesExport($this->buildRows($this->allowedGovIds()), $this->soonDays()),
            'vehicle-licenses-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_licenses_filters' => $this->filtersWithSort()]);
        $this->js("window.open('" . route('reports.vehicle-licenses.pdf') . "', '_blank')");
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id')->all();

        $rows = $this->hasSearched
            ? $this->buildRows($allowedGovIds)
            : collect();

        return view('livewire.reports.vehicle-licenses', [
            'rows'         => $rows,
            'governorates' => $governorates,
        ]);
    }
}
